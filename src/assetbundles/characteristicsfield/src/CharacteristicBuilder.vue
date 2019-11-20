<template>
    <div>
        <input :name="settings.name" type="hidden"/>
        <characteristic-item :consumedOptions="consumedOptions"
                             :data="characteristic"
                             :key="characteristic.id"
                             :name="settings.name + '['+characteristic.id+']'"
                             :options="characteristicAttributes"
                             v-for="(characteristic) in characteristics"
                             v-on:change="handleChange"
                             v-on:delete="() => handleDelete(characteristic)"
        />
        <div class="buttons last">
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
        computed: {},
        watch: {},
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