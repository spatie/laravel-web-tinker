const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/browser',
    use: {
        baseURL: 'http://127.0.0.1:8000',
        viewport: { width: 1280, height: 720 },
        launchOptions: {
            executablePath: process.env.CHROMIUM_PATH || undefined,
        },
    },
    webServer: {
        command: 'php -S 127.0.0.1:8000 tests/browser/server.php',
        url: 'http://127.0.0.1:8000/tinker',
        reuseExistingServer: !process.env.CI,
    },
});
