<template>
    <div><textarea ref="codeEditor" /></div>
</template>

<script>
import CodeMirror from 'codemirror';
import axios from 'axios';
import 'codemirror/mode/php/php';

export default {
    data: () => ({
        value: '',
        codeEditor: null,
    }),

    mounted() {
        const config = {
            tabSize: 4,
            indentWithTabs: true,
            lineWrapping: true,
            lineNumbers: true,
            mode: 'text/x-php',
            theme: 'dracula',
            extraKeys: {
                'Cmd-Enter': () => {
                    this.executeCode();
                },
                'Ctrl-Enter': () => {
                    this.executeCode();
                },
            },
        };

        this.codeEditor = CodeMirror.fromTextArea(this.$refs.codeEditor, config);

        this.codeEditor.on('change', editor => {
            localStorage.setItem('tinker-tool', editor.getValue());
        });

        let value = localStorage.getItem('tinker-tool');

        if (typeof value === 'string') {
            this.codeEditor.setValue(value);
        }
    },

    methods: {
        executeCode() {
            let code = this.codeEditor.getValue().trim();

            if (code === '') {
                alert('You must type some code to execute.');

                return;
            }

            axios.post(window.location, { code }).then(({ data }) => {
                this.$emit('executed', data);
            });
        },
    },
};
</script>

<style src="codemirror/lib/codemirror.css" /> <style src="codemirror/theme/dracula.css" />
<style src="prismjs/themes/prism.css" />
