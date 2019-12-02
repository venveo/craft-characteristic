<template>
    <div>
        <input v-if="allowCustomOptions" class="text" type="text" :value="this.content" @input="handleChange">
        <div class="select" v-else>
            <select :value="this.content" @input="handleChange">
                <option disabled="disabled" selected="selected" value="">Select one</option>
                <option :key="option.value" :value="option.value"
                        v-for="option in options">{{option.value}}
                </option>
            </select>
        </div>
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {},
        props: ['value', 'options', 'allowCustomOptions'],
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
    .input .select, .input .text, .input .flex, .input span {
        margin-bottom: 0;
    }
</style>