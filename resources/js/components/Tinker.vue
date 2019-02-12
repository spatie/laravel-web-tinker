<template>
    <div class="layout">
        <tinker-input
            v-model="this.input"
            v-on:executed="onExecuted"
        ></tinker-input>
        <div class="layout-gutter"></div>
        <tinker-output :value="this.output"></tinker-output>
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
        output: '<span class="text-dimmed">//use cmd+enter or ctrl+enter to run.</span>',
    }),

    methods: {
        onExecuted(output) {
            this.output = output;
        },
    },
};
</script>
