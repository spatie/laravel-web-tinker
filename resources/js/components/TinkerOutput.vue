<template>
    <div>
        <pre><code class="language-bash" ref="outputCode" v-text="value"></code></pre>
    </div>
</template>

<script>
import Prism from 'prismjs/components/prism-core';
import 'prismjs/components/prism-bash';

export default {
    watch: {
        value: function() {
            this.applyOutputStyles();
        },
    },

    props: ['value'],

    methods: {
        applyOutputStyles() {
            console.log('called applyOutputStyles');
            Prism.highlightElement(this.$refs.outputCode);
        },
    },
};
</script>

<style src="prismjs/themes/prism.css" />
