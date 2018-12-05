<template>
    <div>
        <heading class="mb-6">Tinker</heading>

        <help-text class="mb-6">Press Cmd+Enter / Ctrl+Enter to execute the script.</help-text>

        <card class="bg-90 flex flex-col items-center justify-center" style="min-height: 300px">
            <div class="w-full">
                <div class="form-input form-input-bordered px-0 overflow-hidden">
                    <textarea ref="theTextarea" />
                </div>
            </div>
        </card>

        <h1>Web Tinker</h1>

        <card class="bg-90 flex flex-col" style="min-height: 300px">
            <pre
                id="output"
                class="w-full h-full p-2 text-white"
            ><code class="language-bash" ref="outputCode" v-text="output"></code></pre>
        </card>
    </div>
</template>

<style src="codemirror/lib/codemirror.css" /> <style src="codemirror/theme/dracula.css" />
<style src="prismjs/themes/prism.css" />

<script>
import axios from 'axios';
import CodeMirror from 'codemirror';

// Modes
import 'codemirror/mode/markdown/markdown';
import 'codemirror/mode/javascript/javascript';
import 'codemirror/mode/php/php';
import 'codemirror/mode/ruby/ruby';
import 'codemirror/mode/shell/shell';
import 'codemirror/mode/sass/sass';
import 'codemirror/mode/yaml/yaml';
import 'codemirror/mode/yaml-frontmatter/yaml-frontmatter';
import 'codemirror/mode/nginx/nginx';
import 'codemirror/mode/xml/xml';
import 'codemirror/mode/vue/vue';
import 'codemirror/mode/dockerfile/dockerfile';
import 'codemirror/keymap/vim';

import Prism from 'prismjs/components/prism-core';
import 'prismjs/components/prism-bash';

export default {
    data: () => ({
        codemirror: null,
        output: null,
    }),

    /**
     * Mount the component.
     */
    mounted() {
        const config = {
            tabSize: 4,
            indentWithTabs: true,
            lineWrapping: true,
            lineNumbers: true,
            mode: 'text/x-php',
            theme: 'dracula',
            extraKeys: {
                'Cmd-Enter': cm => {
                    this.sendCode();
                },
                'Ctrl-Enter': cm => {
                    this.sendCode();
                },
            },
        };

        this.codemirror = CodeMirror.fromTextArea(this.$refs.theTextarea, config);
        this.codemirror.on('change', editor => {
            localStorage.setItem('tinker-tool', editor.getValue());
        });

        let value = localStorage.getItem('tinker-tool');
        if (typeof value === 'string') {
            this.codemirror.setValue(value);
        }

        let previousOutput = localStorage.getItem('tinker-tool-output');
        if (typeof previousOutput === 'string') {
            this.output = previousOutput;
            this.applyOutputStyles();
        }
    },

    methods: {
        applyOutputStyles() {
            Prism.highlightElement(this.$refs.outputCode);
        },

        sendCode() {
            axios
                .post('/nova-vendor/beyondcode/tinker-tool/tinker', {
                    code: this.codemirror.getValue(),
                })
                .then(({ data }) => {
                    this.output = data;
                    localStorage.setItem('tinker-tool-output', data);
                    this.$nextTick(() => this.applyOutputStyles());
                });
        },
    },
};
</script>

<style>
.CodeMirror-code {
    font-size: 16px;
}

#output {
    margin: 0;
    min-height: 300px;
}
/**
     * atom-dark theme for `prism.js`
     * Based on Atom's `atom-dark` theme: https://github.com/atom/atom-dark-syntax
     * @author Joe Gibson (@gibsjose)
     */

code[class*='language-'],
pre[class*='language-'] {
    color: #c5c8c6;
    text-shadow: 0 1px rgba(0, 0, 0, 0.3);
    font-family: Inconsolata, Monaco, Consolas, 'Courier New', Courier, monospace;
    direction: ltr;
    text-align: left;
    white-space: pre;
    word-spacing: normal;
    word-break: normal;
    line-height: 1.5;

    -moz-tab-size: 4;
    -o-tab-size: 4;
    tab-size: 4;

    -webkit-hyphens: none;
    -moz-hyphens: none;
    -ms-hyphens: none;
    hyphens: none;
}

/* Code blocks */
pre[class*='language-'] {
    padding: 1em;
    margin: 0.5em 0;
    overflow: auto;
    border-radius: 0.3em;
}

:not(pre) > code[class*='language-'],
pre[class*='language-'] {
    background: #1d1f21;
}

/* Inline code */
:not(pre) > code[class*='language-'] {
    padding: 0.1em;
    border-radius: 0.3em;
}

.token.comment,
.token.prolog,
.token.doctype,
.token.cdata {
    color: #7c7c7c;
}

.token.punctuation {
    color: #c5c8c6;
}

.namespace {
    opacity: 0.7;
}

.token.property,
.token.keyword,
.token.tag {
    color: #96cbfe;
}

.token.class-name {
    color: #ffffb6;
    text-decoration: underline;
}

.token.boolean,
.token.constant {
    color: #99cc99;
}

.token.symbol,
.token.deleted {
    color: #f92672;
}

.token.number {
    color: #ff73fd;
}

.token.selector,
.token.attr-name,
.token.string,
.token.char,
.token.builtin,
.token.inserted {
    color: #a8ff60;
}

.token.variable {
    color: #c6c5fe;
}

.token.operator {
    color: #ededed;
}

.token.entity {
    color: #ffffb6;
    /* text-decoration: underline; */
}

.token.url {
    color: #96cbfe;
}

.language-css .token.string,
.style .token.string {
    color: #87c38a;
}

.token.atrule,
.token.attr-value {
    color: #f9ee98;
}

.token.function {
    color: #dad085;
}

.token.regex {
    color: #e9c062;
}

.token.important {
    color: #fd971f;
}

.token.important,
.token.bold {
    font-weight: bold;
}
.token.italic {
    font-style: italic;
}

.token.entity {
    cursor: help;
}
</style>
