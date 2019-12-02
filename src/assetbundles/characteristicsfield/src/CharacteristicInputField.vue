<template>
    <div class="wrapper">
        <a v-if="canDelete" class="icon delete" @click="handleRemove"></a>
        <input v-if="allowCustomOptions" class="text" type="text" :value="this.content" @input="handleChange">
        <div class="select" v-else>
            <model-select :options="options"
                          v-model="this.content"
                          placeholder="select item">
            </model-select>

<!--            <select :value="this.content" @input="handleChange">-->
<!--                <option disabled="disabled" selected="selected" value="">Select one</option>-->
<!--                <option :key="option.value" :value="option.value"-->
<!--                        v-for="option in options">{{option.value}}-->
<!--                </option>-->
<!--            </select>-->
        </div>
    </div>
</template>
<script>
    import 'vue-search-select/dist/VueSearchSelect.css'
    import { ModelSelect } from 'vue-search-select'

    /* eslint-disable */
    /* global Craft */
    export default {
        components: {
            ModelSelect
        },
        props: ['value', 'options', 'allowCustomOptions', 'canDelete'],
        data() {
            return {
                content: this.value,
                item: {},
            }
        },
        methods: {
            handleChange(e) {
                this.content = e.target.value;
                this.$emit('input', this.content);
            },
            handleRemove(e) {
                this.$emit('delete');
            }
        },
    }
</script>

<style lang="scss" scoped>
    .wrapper {
    }
    .icon {
        margin-right: 5px;
    }
</style>