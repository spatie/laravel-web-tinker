<template>
    <div>
        <h1>Web Tinker</h1>

        <div>Press Cmd+Enter or Ctrl+Enter to execute the code.</div>

        <div class="flex">
            <tinker-input
              v-model="this.input"
              v-on:executed="onExecuted"
              class="flex-none w-1/2 px-4"
            ></tinker-input>

            <tinker-output
              :value="this.output"
              class="flex-none w-1/2"
            ></tinker-output>
        </div>
    </div>
</template>

<script>
    import TinkerInput from './TinkerInput';
    import TinkerOutput from './TinkerOutput';

    export default {
        components: {
            TinkerInput,
            TinkerOutput,
        },

        data: () => ({
            input: '',
            output: '',
        }),

        methods: {
            onExecuted(output) {
                this.output = output;
            },
        },
    };
</script>
