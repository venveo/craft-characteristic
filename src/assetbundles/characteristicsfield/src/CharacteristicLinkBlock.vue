<template>
    <transition name="fade">
        <div class="characteristic-item matrixblock">
            <input type="hidden" :name="characteristicFieldName" :value="characteristic.id"/>
            <input type="hidden" v-for="value in formattedValues" :name="valuesFieldName" :value="value.id"/>

            <div class="fields flex flex-nowrap">
                <div class="input ltr characteristic__title">
                    <strong>{{characteristic.title}}</strong>
                </div>
                <div class="input ltr flex">
                    <multiselect
                            :allow-empty="false"
                            :clear-on-select="false"
                            :close-on-select="false"
                            :hide-selected="true"
                            :max="characteristic.maxValues"
                            :multiple="true"
                            label="value"
                            track-by="id"
                            :options="availableValues"
                            :tag-placeholder="'Press enter to create a new value'"
                            :taggable="characteristic.allowCustomOptions"
                            @tag="addTag"
                            v-if="characteristic.maxValues === null || characteristic.maxValues > 1"
                            v-model="valuesInternal">
                    </multiselect>
                    <multiselect
                            :allow-empty="false"
                            :clear-on-select="true"
                            :hide-selected="true"
                            :multiple="false"
                            label="value"
                            track-by="id"
                            :options="availableValues"
                            :tag-placeholder="'Press enter to create a new value'"
                            :taggable="characteristic.allowCustomOptions"
                            @tag="addTag"
                            v-else
                            v-model="valuesInternal">
                    </multiselect>
                </div>
            </div>
            <div class="actions">
                <a @click="handleDelete" class="error icon delete" data-icon="remove"
                   role="button" title="Delete"
                   v-if="!characteristic.required"></a>
            </div>
        </div>
    </transition>
</template>
<script>
    import Multiselect from 'vue-multiselect'
    import 'vue-multiselect/dist/vue-multiselect.min.css'

    /* eslint-disable */
    /* global Craft */
    export default {
        components: {
            Multiselect
        },
        props: {
            characteristicId: {
                type: Number,
                required: true
            },
            blockId: {
                type: String,
                required: true
            },
            valueIds: {
                type: Array
            },
            isNew: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                valuesInternal: []
            }
        },
        methods: {
            addTag(newTag) {
                console.log(newTag);
                // this.values.push(newTag);
            },
            handleDelete(e) {
                e.preventDefault();
                this.$root.deleteBlock(this.blockId);
            }
        },
        mounted() {
            // console.log(this.props);
        },
        beforeMount() {
            const valueMap = this.valueIds.map((v) => {
                return this.characteristic.values.find(o => o.id === v);
            })
            this.valuesInternal = valueMap;
        },
        watch: {},
        computed: {
            formattedValues() {
                if (Array.isArray(this.valuesInternal)) {
                    return this.valuesInternal;
                }
                return [this.valuesInternal]
            },
            valuesFieldName() {
                return this.$root.name + '[' + this.blockId + '][values][]';
            },
            characteristicFieldName() {
                return this.$root.name + '[' + this.blockId + '][characteristic]';
            },
            characteristic() {
                return this.$root.characteristics.find(o => o.id === this.characteristicId);
            },
            availableValues() {
                return this.characteristic.values.filter(o => !o.idempotent);
            }
        }
    }
</script>

<style lang="scss" scoped>
    @import "../node_modules/craftcms-sass/mixins";

    .fade-enter-active, .fade-leave-active {
        transition: opacity .25s;
    }

    .fade-enter, .fade-leave-to {
        opacity: 0;
    }

    .characteristic-item.matrixblock {
        @include padding(0, 10px, 0, 0);


        .input .select, .input .text, .input .flex, .input span, .input .ltr .flex, .flex > div {
            margin-bottom: 0;
        }

        .input.ltr.flex {
            align-items: baseline;
        }

        .characteristic__title {
            background-color: $lightSelColor;
            @include padding(13px, 20px, 13px, 10px);

            border-radius: $smallBorderRadius 0 0 $smallBorderRadius;
            min-width: 100px;
            margin-bottom: 0;
        }

        .actions {
            margin-left: auto;
            right: 10px;
            top: 11px;
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
    @import "../node_modules/craftcms-sass/mixins";
    .multiselect__tags {
    }
    .characteristic-item .fields {
        padding-top: 0 !important;
        border-top: 0 !important;
    }

    .characteristic-item.matrixblock {
        .multiselect__input, .multiselect__single, .multiselect__option {
            /*font-size: 14px;*/
        }
    }
</style>