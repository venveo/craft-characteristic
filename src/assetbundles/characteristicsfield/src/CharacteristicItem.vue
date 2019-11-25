<template>
    <div class="characteristic-item matrixblock">
        <input :name="name + '[attribute]'" :value="attribute" type="hidden"/>
        <input :name="name + '[value]'" :value="value" type="hidden"/>

        <div class="fields flex flex-nowrap">
            <div class="input ltr">
                <div class="select" v-if="!currentCharacteristic.required">
                    <select v-model="attribute">
                        <option :key="option.id" :value="handle"
                                v-for="(option, handle) in characteristics">{{option.title}}
                        </option>
                    </select>
                </div>
                <div v-else>
                    {{currentCharacteristic.title}}
                </div>
            </div>
            <div class="input ltr" v-if="currentCharacteristic">
                <input class="text fullwidth" type="text" v-model="value">
            </div>
            <div class="actions" v-if="!currentCharacteristic.required">
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
            characteristics: Array,
            name: String,
            link: Object,
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
        computed: {
            currentCharacteristic() {
                if (this.link.hasOwnProperty('characteristic')) {
                    return this.link.characteristic;
                }
            }
        },
        beforeMount() {
            if (this.link.hasOwnProperty('characteristic')) {
                this.attribute = this.link.characteristic.handle;
            }
            if (this.link.hasOwnProperty('value')) {
                this.value = this.link.value.value;
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