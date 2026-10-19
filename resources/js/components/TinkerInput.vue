<template>
    <section class="input"><textarea ref="codeEditor" /></section>
</template>

<script>
import 'codemirror/mode/php/php';
import 'codemirror/addon/hint/show-hint';

import CodeMirror from 'codemirror';
import axios from 'axios';

/** Characters that make a suggestion list worth opening on their own. */
const TRIGGER_SEQUENCES = ['::', '->', '\\'];

/** Wait out a burst of typing before asking the server what fits here. */
const DEBOUNCE_MS = 120;

export default {
    data: () => ({
        value: '',
        codeEditor: null,
        hintTimeout: null,
    }),

    props: {
        path: { type: String, required: true },
        completionPath: { type: String, default: '' },
        completionEnabled: { type: Boolean, default: false },
    },

    mounted() {
        // show-hint calls a hint function with a callback only when it is
        // flagged async; without this it would treat the undefined return
        // value as "no suggestions".
        this.hint.async = true;

        const config = {
            autofocus: true,
            extraKeys: {
                'Cmd-Enter': () => {
                    this.executeCode();
                },
                'Ctrl-Enter': () => {
                    this.executeCode();
                },
                'Ctrl-Space': 'autocomplete',
            },
            indentWithTabs: true,
            lineNumbers: true,
            lineWrapping: true,
            mode: 'text/x-php',
            tabSize: 4,
            theme: 'tinker',
            hintOptions: {
                hint: this.hint,
                completeSingle: false,
                closeOnUnfocus: true,
                paddingForScrollbar: false,
            },
        };

        this.codeEditor = CodeMirror.fromTextArea(this.$refs.codeEditor, config);

        this.codeEditor.on('change', editor => {
            localStorage.setItem('tinker-tool', editor.getValue());
        });

        if (this.completionEnabled && this.completionPath) {
            this.codeEditor.on('inputRead', this.maybeHint);
        }

        let value = localStorage.getItem('tinker-tool');

        if (typeof value === 'string') {
            this.codeEditor.setValue(value);
            this.codeEditor.execCommand('goDocEnd');
        }
    },

    beforeDestroy() {
        clearTimeout(this.hintTimeout);
        this.codeEditor.closeHint();
        this.codeEditor.toTextArea();
    },

    methods: {
        executeCode() {
            let code = this.codeEditor.getValue().trim();

            if (code === '') {
                this.$emit('execute', '<error>You must type some code to execute.</error>');

                return;
            }

            axios.post(this.path, { code }).then(({ data }) => {
                this.$emit('execute', data);
            });
        },

        /**
         * Open the suggestion list while typing, but only where it helps: two
         * characters into an identifier, right after `::`, `->` or `\`, or
         * anywhere inside the string argument of env() and config().
         */
        maybeHint(editor, change) {
            clearTimeout(this.hintTimeout);

            if (change.origin === 'paste' || change.origin === 'setValue') {
                return;
            }

            const line = editor.getRange({ line: 0, ch: 0 }, editor.getCursor());

            const wantsHint =
                /[A-Za-z_][A-Za-z0-9_]$/.test(line) ||
                TRIGGER_SEQUENCES.some(sequence => line.endsWith(sequence)) ||
                /\b(?:env|config)\s*\(\s*['"][\w.\-]*$/i.test(line);

            if (!wantsHint) {
                return;
            }

            this.hintTimeout = setTimeout(() => {
                editor.showHint({ hint: this.hint, completeSingle: false });
            }, DEBOUNCE_MS);
        },

        /**
         * Ask the server what can follow the caret.
         *
         * The replacement range comes back with the suggestions: working out
         * where the current token starts means tokenizing PHP, which the
         * server already does — doing it again here is how the two sides end
         * up disagreeing about what gets replaced.
         */
        hint(editor, callback) {
            if (!this.completionEnabled || !this.completionPath) {
                callback(null);

                return;
            }

            const code = editor.getValue();
            const position = editor.getCursor();
            const cursor = Array.from(code.slice(0, editor.indexFromPos(position))).length;

            axios
                .post(this.completionPath, { code, cursor })
                .then(({ data }) => {
                    const current = editor.getCursor();

                    if (
                        this._isDestroyed ||
                        editor.getValue() !== code ||
                        current.line !== position.line ||
                        current.ch !== position.ch
                    ) {
                        callback(null);

                        return;
                    }

                    const list = (data.completions || []).map(completion => {
                        const item = {
                            text: completion.value,
                            displayText: completion.label,
                            meta: completion.meta,
                            render: this.renderHint,
                        };

                        if (completion.meta === 'method') {
                            item.hint = this.insertCall;
                        }

                        return item;
                    });

                    callback({
                        list,
                        from: editor.posFromIndex(
                            Array.from(code)
                                .slice(0, data.from)
                                .join('').length
                        ),
                        to: position,
                    });
                })
                .catch(() => callback(null));
        },

        /**
         * Accept a method suggestion as a call: insert `name()` and leave the
         * caret between the parentheses, which is where the next keystroke
         * belongs. A `(` already sitting after the caret is left alone rather
         * than doubled.
         */
        insertCall(editor, data, item) {
            const after = { line: data.to.line, ch: data.to.ch + 1 };
            const alreadyCalled = editor.getRange(data.to, after) === '(';

            editor.replaceRange(alreadyCalled ? item.text : `${item.text}()`, data.from, data.to);

            if (!alreadyCalled) {
                const cursor = editor.getCursor();

                editor.setCursor({ line: cursor.line, ch: cursor.ch - 1 });
            }
        },

        renderHint(element, self, data) {
            const value = document.createElement('span');
            value.className = 'wt-hint-value';
            value.textContent = data.displayText;

            const meta = document.createElement('span');
            meta.className = 'wt-hint-meta';
            meta.textContent = data.meta;

            element.appendChild(value);
            element.appendChild(meta);
        },
    },
};
</script>
