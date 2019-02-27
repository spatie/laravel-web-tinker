<template>
    <section class="output">
        <pre><code v-html="value"></code></pre>
    </section>
</template>

<script>
export default {
    props: ['value'],
};
</script>
