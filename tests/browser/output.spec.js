const { test, expect } = require('@playwright/test');

test('completion preserves Unicode and whitespace before executing with rich output', async ({
    page,
}) => {
    await page.goto('/tinker');
    const code = '  $label = "é😀"; $date = new DateTime("2026-03-17"); $date->for';
    await page.locator('.CodeMirror').click();
    await page.keyboard.insertText(code);
    await page.keyboard.press('Control+Space');
    const suggestion = page.locator('.CodeMirror-hint').filter({ hasText: 'format' });
    await expect(suggestion).toBeVisible();
    await page.keyboard.press('Enter');
    await page.keyboard.insertText('"Y-m-d"');
    const request = page.waitForRequest(
        (request) => request.method() === 'POST' && new URL(request.url()).pathname === '/tinker'
    );
    await page.keyboard.press('ControlOrMeta+Enter');
    expect((await request).postDataJSON()).toEqual({
        code: '$label = "é😀"; $date = new DateTime("2026-03-17"); $date->format("Y-m-d")',
        format: 'html',
    });
    await expect(page.locator('.output .sf-dump')).toContainText('2026-03-17');
});

async function execute(page, code) {
    await page.locator('.CodeMirror').click();
    await page.keyboard.press('ControlOrMeta+a');
    await page.keyboard.insertText(code);
    const response = page.waitForResponse((response) => response.request().method() === 'POST');
    await page.keyboard.press('ControlOrMeta+Enter');
    expect((await response).ok()).toBeTruthy();
}

test('real dumps expand after sanitizing, refresh, and preserve literal stdout', async ({
    page,
}) => {
    await page.goto('/tinker');
    await execute(
        page,
        'echo "<script>window.injected = true</script>"; return ["nested" => ["answer" => 42]];'
    );

    const output = page.locator('.output');
    await expect(output.locator('.wt-stdout')).toHaveText(
        '<script>window.injected = true</script>'
    );
    await expect(output.locator('script')).toHaveCount(0);
    expect(await page.evaluate(() => window.injected)).toBeUndefined();
    await expect(output.locator('samp[data-depth="2"]')).toHaveClass('sf-dump-compact');
    await output.locator('a.sf-dump-toggle').nth(1).click();
    await expect(output.locator('samp[data-depth="2"]')).toHaveClass('sf-dump-expanded');
    await expect(output).toContainText('42');

    await execute(page, 'return ["replacement" => ["value" => 73]];');
    await expect(output).not.toContainText('answer');
    await expect(output.locator('samp[data-depth="2"]')).toHaveClass('sf-dump-compact');
    await output.locator('a.sf-dump-toggle').nth(1).click();
    await expect(output.locator('samp[data-depth="2"]')).toHaveClass('sf-dump-expanded');
    await expect(output).toContainText('73');
});

test('exceptions retain stdout and an HTML-free fallback remains usable', async ({ page }) => {
    await page.goto('/tinker?theme=dark');
    await execute(
        page,
        'echo "before failure"; throw new RuntimeException("<img src=x onerror=alert(1)>");'
    );
    await expect(page.locator('.wt-stdout')).toContainText('before failure');
    await expect(page.locator('.wt-exception-heading')).toContainText(
        '<img src=x onerror=alert(1)>'
    );
    await expect(page.locator('.output img')).toHaveCount(0);

    await page.goto('/tinker?plain=1');
    const request = page.waitForRequest((request) => request.method() === 'POST');
    await execute(page, 'return 23 + 19;');
    expect((await request).postDataJSON().format).toBe('text');
    await expect(page.locator('.output')).toContainText('42');
    await expect(page.locator('.sf-dump')).toHaveCount(0);
    expect(await page.evaluate(() => typeof window.Sfdump)).toBe('undefined');

    await page.locator('.CodeMirror').click();
    await page.keyboard.press('ControlOrMeta+a');
    await page.keyboard.press('Backspace');
    await page.keyboard.press('ControlOrMeta+Enter');
    await expect(page.locator('.output error')).toContainText('You must type some code');
    await expect(page.locator('.output error')).toHaveCSS('display', 'block');
});

test('sanitizing rejects executable response markup without removing error styling', async ({
    page,
}) => {
    await page.goto('/tinker');
    await page.route('**/tinker', (route) =>
        route.request().method() === 'POST'
            ? route.fulfill({
                  body: '<error>safe error</error><script>window.injected=true</script><img src=x onerror="window.injected=true"><a href="javascript:window.injected=true">unsafe link</a>',
              })
            : route.continue()
    );
    await execute(page, 'return 1;');
    await expect(page.locator('.output error')).toHaveText('safe error');
    await expect(
        page.locator('.output [onerror], .output script, .output [href^="javascript:"]')
    ).toHaveCount(0);
    expect(await page.evaluate(() => window.injected)).toBeUndefined();
});
