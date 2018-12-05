import * as monaco from 'monaco-editor'

export default {
    template: '<div :style="{height: height, width: width}"></div>',

    props: {
        content: {
            type: String,
            required: true
        },
        language: {
            type: String,
            default: 'php'
        },
        theme: {
            type: String,
            default: 'chrome'
        },
        height: {
            type: String,
            default: '100%'
        },
        width: {
            type: String,
            default: '100%'
        },
        sync: {
            type: Boolean,
            default: false
        },
        readOnly: {
            type: Boolean,
            default: false
        },
        options: {
            type: Object,
            default: function () {
                return {};
            }
        },
        id: {
            default: null
        }
    },

    data: function () {
        return {
            editor: null,
        };
    },

    mounted: function () {
        const editor = monaco.editor.create(this.$el, {
            model: monaco.editor.createModel(this.content, this.language, null),
            language: this.language,
            theme: 'vs-dark',
            automaticLayout: true,
            readOnly: this.readOnly
        });

        editor.onDidChangeModelContent(() => {
            this.$emit('editor-update', editor.getModel().getValue(), this.id);
        });

        editor.addAction({
            id: 'save',
            label: 'Save',

            keybindings: [
                monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S,
            ],
            precondition: null,
            keybindingContext: null,
            contextMenuGroupId: 'navigation',
            contextMenuOrder: 1.5,

            run: (ed) => {
                this.$emit('editor-save', editor.getModel().getValue(), this.id);
                return null;
            }
        });
    }
};
