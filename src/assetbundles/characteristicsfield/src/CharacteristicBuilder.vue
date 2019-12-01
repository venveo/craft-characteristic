<template>
    <div>
        <input :name="settings.name" type="hidden"/>
        <characteristic-item
                :characteristic="linkSet.characteristic"
                :key="linkSet.index"
                :linkSet="linkSet"
                :name="settings.name + '['+linkSet.index+']'"
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
</template>
<script>
    import CharacteristicItem from "./CharacteristicItem";
    import api from './api/characteristics';

    /* eslint-disable */
    /* global Craft */
    export default {
        components: {
            CharacteristicItem: CharacteristicItem
        },
        props: {
            'settings': Object
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
                const savedLinks = this.settings.value;
                const requiredCharacteristics = newVal.filter(characteristic => characteristic.required == true);
                for (let characteristic of newVal) {
                    const existingValue = savedLinks.filter(linkSet => linkSet.characteristic.id == characteristic.id);
                    let data = {};
                    if (existingValue && existingValue[0]) {
                        data = {
                            index: existingValue[0].index,
                            characteristic: characteristic,
                            isNew: false,
                            links: existingValue[0].values
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
            api.getCharacteristicsForSource(this.settings.source).then((result) => {
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