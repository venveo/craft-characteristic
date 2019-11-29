<template>
    <div class="characteristic-item matrixblock">
        <input :name="name + '[attribute]'" :value="attribute" type="hidden"/>
        <input :name="name + '[value]'" :value="value" type="hidden"/>

        <div class="fields flex flex-nowrap">
            <div class="input ltr characteristic__title">
                <strong>{{currentCharacteristic.title}}</strong>
            </div>
            <div class="input ltr">
                <div v-if="currentCharacteristic.allowCustomOptions">
                    <input class="text fullwidth" type="text" v-model="value">
                </div>
                <div class="select" v-else>
                    <select v-model="value">
                        <option disabled="disabled" selected="selected" value="">Select one</option>
                        <option :key="option.value" :value="option.value"
                                v-for="option in currentCharacteristic.values">{{option.value}}
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="actions">
            <div v-if="link.isNew"><strong>new</strong></div>
            <a @click="$emit('delete')" class="error icon delete" data-icon="remove"
               role="button" title="Delete"
               v-if="!currentCharacteristic.required"></a>
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
            },
            value: function (newVal) {
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
        padding: 0;
        padding-right: 10px;

        .characteristic__title {
            background-color: #cdd8e4;
            padding: 10px 20px 10px 10px;
            border-radius: 5px 0 0 5px;
            border-right: 1px solid #cdd8e4;
            min-width: 100px;
            margin-bottom: 0;
        }

        .fields {
            align-items: baseline;
        }

        .actions {
            margin-left: auto;
            right: 10px;
            top: 7px;
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