<template>
    <div class="output">
        <pre><code v-text="value"></code></pre>
    </div>
</template>

<script>
export default {
    props: ['value'],
};
</script>
