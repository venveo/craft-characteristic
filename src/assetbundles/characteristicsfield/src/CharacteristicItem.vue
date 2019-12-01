<template>
    <div class="characteristic-item matrixblock">
        <input :name="name + '[attribute]'" :value="attribute" type="hidden"/>
        <input v-for="(value, index) in values" :name="name + '[value]['+index+']'" v-if="value.length" :key="index" :value="value" type="hidden" />

        <div class="fields flex flex-nowrap">
            <div class="input ltr characteristic__title">
                <strong>{{characteristic.title}}</strong>
            </div>
            <div class="input ltr flex">
                <div v-if="characteristic.allowCustomOptions" class="flex">
                    <input class="text" type="text" v-for="(value, index) in values" v-model="values[index]">
                </div>
                <div class="select" v-else  v-for="(value, index) in values">
                    <select v-model="values[index]" >
                        <option disabled="disabled" selected="selected" value="">Select one</option>
                        <option :key="option.value" :value="option.value"
                                v-for="option in characteristic.values">{{option.value}}
                        </option>
                    </select>
                </div>
                <span v-if="canAddValue"><button class="btn small add icon" @click="handleAddValue">Add Value</button></span>
            </div>
        </div>
        <div class="actions">
            <div v-if="linkSet.isNew"><strong>new</strong></div>
            <a @click="$emit('delete')" class="error icon delete" data-icon="remove"
               role="button" title="Delete"
               v-if="!characteristic.required"></a>
        </div>
    </div>
</template>
<script>
    /* eslint-disable */
    /* global Craft */
    export default {
        components: {},
        props: {
            characteristic: Object,
            name: String,
            linkSet: Array,
        },
        data() {
            return {
                attribute: '',
                values: []
            }
        },
        methods: {
            handleAddValue(e) {
                e.preventDefault();
                this.values.push('');
            }
        },
        watch: {
            attribute: function (newVal) {
                this.$emit('change', newVal);
            },
            values: function (newVal) {
                this.$emit('change', newVal);
            }
        },
        computed: {
            canAddValue() {
                return (this.values.length < this.characteristic.maxValues) || !this.characteristic.maxValues;
            }
        },
        beforeMount() {
            this.attribute = this.characteristic.handle;
            if (this.linkSet.hasOwnProperty('links')) {
                for(let link of this.linkSet.links) {
                    this.values.push(link.value);
                }
            }
        }
    }
</script>

<style lang="scss" scoped>
    .characteristic-item.matrixblock {
        padding: 0;
        padding-right: 10px;
        .input .select, .input .text, .input .flex, .input span {
            margin-bottom: 0;
        }

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