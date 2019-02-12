<template>
    <div ref="grid" class="layout">
        <tinker-input
            v-model="this.input"
            v-on:executed="onExecuted"
        ></tinker-input>
        <div ref="gutter" class="layout-gutter"></div>
        <tinker-output :value="this.output"></tinker-output>
    </div>
</template>

<script>
import TinkerInput from './TinkerInput';
import TinkerOutput from './TinkerOutput';
import Split from 'split-grid';

export default {
    components: {
        TinkerInput,
        TinkerOutput,
    },

    data: () => ({
        gutterWidth: 9, // px
        breakpoint: 768,
        split: undefined,
        input: '',
        output: '<span class="text-dimmed">//use cmd+enter or ctrl+enter to run.</span>',
    }),

    computed: {
        columnPercentage() {
            return (( 1 - this.gutterWidth/window.innerWidth) / 2 ) * 100 + '%';
        },

        rowPercentage() {
            return (( 1 - this.gutterWidth/window.innerHeight) / 2 ) * 100 + '%';
        },
    },

    methods: {
        onExecuted(output) {
            this.output = output;
        },

        needsColumnLayout() {
            return window.innerWidth > this.breakpoint;
        },

        destroySplit() {
            if(this.split) {
                this.split.destroy();
                this.$refs["grid"].removeAttribute("style");
            };
        },

        initSplit() {
            this.destroySplit();

            let splitOptions = {};

            if(this.needsColumnLayout()) {
                this.$refs["grid"].classList.add('layout-columns');
                this.$refs["grid"].style.gridTemplateColumns = `${this.columnPercentage} ${this.gutterWidth}px ${this.columnPercentage}`;
                splitOptions = {
                    columnGutters: [{
                        track: 1,
                        element: this.$refs["gutter"],
                    }],
                }
            } 
            else {
                this.$refs["grid"].classList.remove('layout-columns');
                this.$refs["grid"].style.gridTemplateRows = `${this.rowPercentage} ${this.gutterWidth}px ${this.rowPercentage}`;
                splitOptions = {
                    rowGutters: [{
                        track: 1,
                        element: this.$refs["gutter"],
                    }],
                }
            }

            this.split = Split({...splitOptions, minSize: 200 });
        },
    },

    mounted() {
        this.initSplit();

        window.addEventListener('resize', () => {
            window.requestAnimationFrame( () => {
                if(this.$refs["grid"].classList.contains('layout-columns') != this.needsColumnLayout()) {
                    this.initSplit();
                }
            });
        });
    }
};
</script>
