<template>
    <div class="output">
        <pre><code v-html="value"></code></pre>       
    </div>
</template>

<script>
export default {
    props: ['value'],
};
</script>
