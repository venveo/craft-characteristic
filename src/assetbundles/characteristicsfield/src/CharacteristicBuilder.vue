<template>
    <div>
        <spinner v-if="loading"></spinner>
        <div v-else>
            <characteristic-item
                    :characteristic="linkSet.characteristic"
                    :key="linkSet.index"
                    :linkSet="linkSet"
                    :name="name + '['+linkSet.characteristic.handle+']'"
                    v-for="linkSet in linkSets"
                    v-on:change="handleChange"
                    v-on:delete="() => handleDelete(linkSet)"
            />
            <div class="buttons last add-button" v-if="availableCharacteristics.length !== 0">
                <div class="select">
                    <select v-model="selectedCharacteristic">
                        <option :value="index" v-for="(characteristic, index) in availableCharacteristics">
                            {{characteristic.title}}
                        </option>
                    </select>
                </div>
                <div @click="handleAdd" class="btn add icon">Add</div>
            </div>
        </div>
    </div>
</template>
<script>
    /* global Craft, Vue */
    import CharacteristicItem from "./CharacteristicItem";
    import api from './api/characteristics';
    import Spinner from '@pixelandtonic/craftui/src/components/Spinner';

    export default {
        components: {
            CharacteristicItem,
            Spinner
        },
        props: {
            container: {
                type: String,
            },
            name: {
                type: String
            },
            source: {
                type: String
            },
            value: {
                type: Object
            }
        },
        data() {
            return {
                characteristics: [],
                loading: true,
                linkSets: [],
                selectedCharacteristic: null
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                const newItem =
                    this.linkSets.push({
                        index: 'new' + this.linkSets.length + 1,
                        characteristic: this.availableCharacteristics[this.selectedCharacteristic],
                        links: []
                    });
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            },
            handleDelete(e) {
                const result = this.linkSets.filter(linkSet => linkSet.index != e.index);
                this.linkSets = result;
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            },
            handleChange(e) {
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            }
        },
        computed: {
            availableCharacteristics() {
                let availableCharacteristics = this.characteristics.filter((characteristic) => {
                    const item = this.linkSets.find(linkSet => linkSet.characteristic.id === characteristic.id);
                    return !!!item;
                });
                if (availableCharacteristics.length) {
                    this.selectedCharacteristic = 0;
                }
                return availableCharacteristics;
            }
        },
        watch: {
            /**
             * After characteristics have loaded, we need to parse them
             */
            characteristics: function (newVal) {
                const savedLinks = this.value;
                const requiredCharacteristics = newVal.filter(characteristic => characteristic.required == true);
                for (let characteristic of newVal) {
                    let existingValue = null;
                    if (savedLinks.hasOwnProperty(characteristic.handle)) {
                        existingValue = savedLinks[characteristic.handle];
                    }
                    let data = {};
                    if (existingValue && Array.isArray(existingValue)) {
                        data = {
                            index: characteristic.handle,
                            characteristic: characteristic,
                            isNew: false,
                            links: existingValue
                        };
                        this.linkSets.push(data);
                    } else if (characteristic.required) {
                        data = {
                            index: 'new' + this.linkSets.length + 1,
                            characteristic: characteristic,
                            isNew: true,
                            links: []
                        };
                        this.linkSets.push(data);
                    }

                    if (window.draftEditor) {
                        window.draftEditor.checkForm();
                    }
                }
            }
        },
        /**
         * Load in our characteristics and their value options
         */
        mounted() {
            // this.characteristics = this.settings.value;
            this.loading = true;
            api.getCharacteristicsForSource(this.source).then((result) => {
                this.characteristics = result.data;
            }).finally(() => {
                this.loading = false;
            })
        }
    }
</script>

<style lang="scss">
    .add-button {
        .select > select {
            border-radius: 5px 0 0 5px;
        }

        .btn {
            border-radius: 0 5px 5px 0;
        }
    }
</style>