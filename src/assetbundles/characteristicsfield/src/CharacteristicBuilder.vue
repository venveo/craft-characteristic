<template>
    <div>
        <input :name="settings.name" type="hidden"/>
        <characteristic-item
                             v-for="link in links"
                             :link="link"
                             :key="link.id"
                             :name="settings.name + '['+link.id+']'"
                             :characteristics="characteristics"
                             v-on:change="handleChange"
                             v-on:delete="() => handleDelete(link)"
        />
        <div class="buttons last add-button" v-if="availableCharacteristics.length !== 0">
            <div class="select">
            <select v-model="selectedCharacteristic">
                <option v-for="(characteristic, index) in availableCharacteristics" :value="index">{{characteristic.title}}</option>
            </select>
            </div>
            <div @click="handleAdd" class="btn add icon">Add Selected</div>
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
                links: [],
                selectedCharacteristic: null
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                this.links.push({
                    id: 'new' + this.links.length + 1,
                    characteristic: this.availableCharacteristics[this.selectedCharacteristic]
                });
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            },
            handleDelete(e) {
                const result = this.links.filter(link => link.id != e.id);
                this.links = result;
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
                    const item = this.links.find(link => link.characteristic.id === characteristic.id);
                    return !!!item;
                });
                if (availableCharacteristics.length) {
                    this.selectedCharacteristic = 0;
                }
                return availableCharacteristics;
            }
        },
        watch: {
            characteristics: function (newVal) {
                const savedLinks = this.settings.value;
                // const requiredCharacteristics = newVal.filter(characteristic => characteristic.required == true);
                for (let characteristic of newVal) {
                    const existingValue = savedLinks.filter(link => link.characteristic.id == characteristic.id);
                    let data = {};
                    if(existingValue) {
                        data = {
                            id: existingValue[0].id,
                            characteristic: characteristic,
                            isNew: false,
                            value: existingValue[0].value
                        };
                    } else {
                        data = {
                            id: 'new' + this.links.length + 1,
                            characteristic: characteristic,
                            isNew: true
                        };
                    }
                    this.links.push(data);

                    if (window.draftEditor) {
                        window.draftEditor.checkForm();
                    }
                }
            }
        },
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