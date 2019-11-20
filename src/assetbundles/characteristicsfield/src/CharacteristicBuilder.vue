<template>
    <div>
        <input type="hidden" :name="settings.name" />
        <characteristic-item v-for="(characteristic) in characteristics"
                             :key="characteristic.id"
                             :data="characteristic"
                             v-on:change="handleChange"
                             :consumedOptions="consumedOptions"
                             :options="characteristicAttributes"
                             v-on:delete="() => handleDelete(characteristic)"
                             :name="settings.name + '['+characteristic.id+']'"
        />
        <div class="buttons last">
            <div class="btn add icon" @click="handleAdd">Add Characteristic</div>
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
                characteristicAttributes: [],
                consumedOptions: [],
                loading: true
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                this.characteristics.push({
                    id: 'new' + this.characteristics.length + 1
                });
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            },
            handleDelete(e) {
                const result = this.characteristics.filter(characteristic => characteristic.id != e.id);
                this.characteristics = result;
                if (window.draftEditor) {
                    window.draftEditor.checkForm();
                }
            },
            handleChange(e) {
                console.log(e);
            }
        },
        computed: {
        },
        watch: {
        },
        mounted() {
            this.characteristics = this.settings.value;
            this.loading = true;
            api.getCharacteristicsForSource(this.settings.source).then((result) => {
                this.characteristicAttributes = result.data;
            }).finally(() => {
                this.loading = false;
            })
        }
    }
</script>

<style lang="scss">

</style>