<template>
    <section class="output">
        <div class="output-body" v-html="value"></div>
    </section>
</template>

<script>
export default {
    props: ['value'],

    watch: {
        value() {
            this.$nextTick(this.activateDumps);
        },
    },

    mounted() {
        this.activateDumps();
    },

    methods: {
        /**
         * Hand each freshly inserted dump to VarDumper's toggle script.
         *
         * VarDumper normally activates a dump with an inline <script> tag that
         * follows it, which sanitizing the response strips — so the call it
         * would have made is made here instead, once per dump.
         */
        activateDumps() {
            if (typeof window.Sfdump !== 'function') {
                return;
            }

            this.$el.querySelectorAll('pre.sf-dump[id]').forEach(dump => {
                if (dump.dataset.wtActivated) {
                    return;
                }

                dump.dataset.wtActivated = '1';

                window.Sfdump(dump.id);
            });
        },
    },
};
</script>

<style>
pre {
  white-space: pre-wrap;
  overflow-wrap: break-word;
}
</style>
