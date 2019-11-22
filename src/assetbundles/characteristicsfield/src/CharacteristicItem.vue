<template>
    <div class="characteristic-item matrixblock">
        <input :name="name + '[attribute]'" :value="attribute" type="hidden"/>
        <input :name="name + '[value]'" :value="value" type="hidden"/>

        <div class="fields flex flex-nowrap">
            <div class="input ltr">
                <div class="select">
                    <select v-model="attribute">
                        <option :disabled="option.disabled" :key="option.id" :value="option.handle"
                                v-for="option in options">{{option.title}}
                        </option>
                    </select>
                </div>
            </div>
            <div class="input ltr">
                <input class="text fullwidth" type="text" v-model="value">
            </div>
            <div class="actions">
                <a @click="$emit('delete')" class="error icon delete" data-icon="remove" role="button"
                   title="Delete"></a>
            </div>
        </div>
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {},
        props: {
            options: Array,
            name: String,
            data: Object
        },
        data() {
            return {
                attribute: '',
                value: ''
            }
        },
        methods: {},
        watch: {
            attribute: function (newVal) {
                this.$emit('change', newVal);
            }
        },
        mounted() {
            if (this.data.hasOwnProperty('characteristic')) {
                this.attribute = this.data.characteristic.handle;
            }
            if (this.data.hasOwnProperty('value')) {
                this.value = this.data.value.value;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .characteristic-item.matrixblock {
        padding-top: 10px;

        .fields {
            align-items: baseline;
        }

        .actions {
            margin-left: auto;
        }

        .actions > .delete {
            margin-right: 10px;
            font-size: 20px;
        }

        .titlebar {
            padding-left: 15px;
        }
    }
</style>