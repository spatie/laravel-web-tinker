<template>
    <div>
        <MonacoEditor
          @editor-update="markUnsavedChanges"
          @editor-save="saveFile"
          class="w-full h-full"
          id="1"
          :read-only="false"
          :height="'100%'"
          content="The content"
          language="php">
        </MonacoEditor>
    </div>
</template>

<script>
    import MonacoEditor from './MonacoEditor';

    export default {
        components: {
            MonacoEditor,
        },

        created() {
            console.log('the Vue component has been created');
        },

        methods: {
            markUnsavedChanges() {

            },

            saveFile() {

            },
        }
    }
</script>