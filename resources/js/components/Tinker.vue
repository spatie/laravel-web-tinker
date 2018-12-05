<template>
    <div>
        <codemirror
          :value="code"
          :options="options"
        ></codemirror>
    </div>
</template>

<style src="codemirror/lib/codemirror.css" />
<style src="codemirror/theme/dracula.css" />

<script>
    import { codemirror } from 'vue-codemirror';
    import 'codemirror/mode/php/php'

    export default {
        components: {
            codemirror,
        },

        created() {
            console.log('the Vue component has been created');
        },

        data() {
            return {
                code: 'my code',
                options: {
                    tabSize: 4,
                    indentWithTabs: true,
                    lineWrapping: true,
                    lineNumbers: true,
                    mode: 'text/x-php',
                    theme: 'dracula',
                }
            }
        },


        methods: {
            markUnsavedChanges() {

            },

            saveFile() {

            },
        }
    }
</script>