<template>
    <div class="characteristic-item matrixblock">
        <input :name="name + '[attribute]'" :value="attribute" type="hidden"/>
        <input v-for="(value, index) in values" :name="name + '[value]['+index+']'" v-if="value.length" :key="index" :value="value" type="hidden" />

        <div class="fields flex flex-nowrap">
            <div class="input ltr characteristic__title">
                <strong>{{characteristic.title}}</strong>
            </div>
            <div class="input ltr flex">
                <multiselect
                        v-if="this.characteristic.maxValues == 0 || this.characteristic.maxValues > 1"
                        v-model="values"
                        :hide-selected="true"
                        :multiple="true"
                        :max="this.characteristic.maxValues"
                        :clear-on-select="true"
                        :allow-empty="false"
                        :taggable="characteristic.allowCustomOptions"
                        :tag-placeholder="'Press enter to create a new value'"
                        @tag="addTag"
                        :options="options">
                </multiselect>
                <multiselect
                        v-else
                        v-model="values"
                        :hide-selected="true"
                        :multiple="false"
                        :clear-on-select="true"
                        :allow-empty="false"
                        :options="options">
                </multiselect>
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
    // import CharacteristicInputField from "./CharacteristicInputField";
    import Multiselect from 'vue-multiselect'
    import _ from 'lodash'
    import 'vue-multiselect/dist/vue-multiselect.min.css'

    /* eslint-disable */
    /* global Craft */
    export default {
        components: {
            Multiselect
        },
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
            addTag (newTag) {
                this.values.push(newTag);
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
            options() {
                return this.characteristic.values.map(value => value.value);
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
        .input .select, .input .text, .input .flex, .input span, .input .ltr .flex, .flex > div {
            margin-bottom: 0;
        }

        .input.ltr.flex {
            align-items: baseline;
        }

        .characteristic__title {
            background-color: #cdd8e4;
            padding: 10px 20px 10px 10px;
            border-radius: 5px 0 0 5px;
            border-right: 1px solid #cdd8e4;
            min-width: 100px;
            margin-bottom: 0;
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

<style lang="scss">
    .characteristic-item.matrixblock {

    }
</style>