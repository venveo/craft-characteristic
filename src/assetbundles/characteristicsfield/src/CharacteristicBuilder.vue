<template>
    <div>
        <input :name="settings.name" type="hidden"/>
        <characteristic-item
                             v-for="link in links"
                             :link="link"
                             :key="link.id"
                             :name="settings.name + '['+link.id+']'"
                             :characteristics="availableCharacteristics"
                             v-on:change="handleChange"
                             v-on:delete="() => handleDelete(link)"
        />
        <div class="buttons last" v-if="availableCharacteristics.length !== 0">
            <div @click="handleAdd" class="btn add icon">Add Characteristic</div>
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
                links: []
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                this.links.push({
                    id: 'new' + this.links.length + 1,
                    characteristic: this.availableCharacteristics[0]
                });
                // if (window.draftEditor) {
                //     window.draftEditor.checkForm();
                // }
            },
            handleDelete(e) {
                const result = this.links.filter(link => link.id != e.id);
                this.links = result;
                // if (window.draftEditor) {
                //     window.draftEditor.checkForm();
                // }
            },
            handleChange(e) {
                console.log(e);
            }
        },
        computed: {
            // links: function() {
            //     if (this.loading !== false) {
            //         return [];
            //     }
            //     const result = this.characteristics.filter(characteristic => characteristic.required == true);
            //     let formatted = result.map((characteristic) => {
            //         return {
            //             characteristic: characteristic
            //         }
            //     });
            //     return formatted;
            // }
            availableCharacteristics() {
                let availableCharacteristics = this.characteristics.filter((characteristic) => {
                    const item = this.links.find(link => link.characteristic.id === characteristic.id);
                    return !!!item;
                });
                return availableCharacteristics;
            }
        },
        watch: {
            characteristics: function (newVal) {
                const requiredCharacteristics = newVal.filter(characteristic => characteristic.required == true);
                for (var requiredCharacteristic of requiredCharacteristics) {
                    this.links.push({
                        id: 'new' + this.links.length + 1,
                        characteristic: requiredCharacteristic
                    })
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

</style>