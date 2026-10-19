import { afterEach, expect, test, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import axios from 'axios';
import TinkerInput from '../../resources/js/components/TinkerInput.vue';

vi.mock('axios', () => ({ default: { post: vi.fn() } }));

let wrapper;

function editor(props = {}) {
    wrapper = mount(TinkerInput, {
        attachTo: document.body,
        propsData: {
            path: '/tinker',
            completionPath: '/tinker/completions',
            completionEnabled: true,
            ...props,
        },
    });

    return wrapper.vm.codeEditor;
}

function complete(codeEditor, code) {
    codeEditor.setValue(code);
    codeEditor.execCommand('goDocEnd');
    codeEditor.execCommand('autocomplete');
}

async function response() {
    await new Promise(resolve => setTimeout(resolve, 30));
}

afterEach(() => {
    wrapper?.destroy();
    document.body.innerHTML = '';
    localStorage.clear();
    vi.resetAllMocks();
});

test.each([
    { completionEnabled: false },
    { completionPath: '' },
    { completionPath: '', completionEnabled: false },
])('Ctrl+Space never sends code when completions are unavailable: %j', async props => {
    axios.post.mockResolvedValue({ data: { completions: [] } });
    complete(editor(props), 'file_put_contents("important", "changed");');
    await response();

    expect(axios.post).not.toHaveBeenCalled();
});

test('replaces a method after Unicode text and leaves the caret inside its call', async () => {
    const codeEditor = editor();
    const prefix = '$label = "é😀"; $date = new DateTime(); $date->';
    axios.post.mockResolvedValue({
        data: {
            from: Array.from(prefix).length,
            completions: [{ value: 'format', label: 'format', meta: 'method' }],
        },
    });

    complete(codeEditor, `${prefix}for`);
    await response();

    expect(axios.post).toHaveBeenCalledWith('/tinker/completions', {
        code: `${prefix}for`,
        cursor: Array.from(`${prefix}for`).length,
    });
    expect(document.querySelector('.CodeMirror-hint').textContent).toBe('formatmethod');
    document
        .querySelector('.CodeMirror-hint')
        .dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));
    expect(codeEditor.getValue()).toBe(`${prefix}format()`);
    expect(codeEditor.indexFromPos(codeEditor.getCursor())).toBe(`${prefix}format(`.length);
});

test('does not duplicate an existing method call parenthesis', async () => {
    const codeEditor = editor();
    axios.post.mockResolvedValue({
        data: {
            from: 7,
            completions: [{ value: 'format', label: 'format', meta: 'method' }],
        },
    });
    codeEditor.setValue('$date->for("Y")');
    codeEditor.setCursor({ line: 0, ch: 10 });
    codeEditor.execCommand('autocomplete');
    await response();
    document
        .querySelector('.CodeMirror-hint')
        .dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));

    expect(codeEditor.getValue()).toBe('$date->format("Y")');
});

test('ignores an in-flight response after the document changes', async () => {
    let resolve;
    axios.post.mockReturnValue(
        new Promise(done => {
            resolve = done;
        })
    );
    const codeEditor = editor();
    complete(codeEditor, '$date->for');
    codeEditor.setValue('$other->cou');
    resolve({
        data: { from: 7, completions: [{ value: 'format', label: 'format', meta: 'method' }] },
    });
    await response();

    expect(document.querySelector('.CodeMirror-hints')).toBeNull();
    expect(codeEditor.getValue()).toBe('$other->cou');
});
