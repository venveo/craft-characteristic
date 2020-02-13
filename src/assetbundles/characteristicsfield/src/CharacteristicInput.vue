<template>
    <div>
        <slot name="value" ref="value"></slot>
        <slot name="blocks" v-bind:init="initBlock"></slot>
        <slot name="controls"></slot>
    </div>
</template>
<script>
    export default {
        components: {},
        props: {
        },
        data() {
            return {
            }
        },
        methods: {
            initBlock() {

            }
        },
        computed: {},
        watch: {},
        /**
         * Load in our characteristics and their value options
         */
        mounted() {
        }
    }
</script>

<style lang="scss">
</style>