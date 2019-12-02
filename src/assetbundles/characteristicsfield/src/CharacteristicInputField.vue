<template>
    <div>
        <input class="text" type="text" :value="this.content" @input="handleChange">
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {},
        props: ['value'],
        data() {
            return {
                content: this.value
            }
        },
        methods: {
            handleChange(e) {
                this.content = e.target.value;
                this.$emit('input', this.content);
            }
        },
    }
</script>

<style lang="scss" scoped>
</style>