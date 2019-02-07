<template>
    <div class="layout">
        <tinker-input
            v-model="this.input"
            v-on:executed="onExecuted"
        ></tinker-input>

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
        output: '',
    }),

    methods: {
        onExecuted(output) {
            this.output = output;
        },
    },
};
</script>
