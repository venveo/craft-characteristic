<template>
    <div>
        <input type="hidden" :name="settings.name" />
        <characteristic-item v-for="characteristic in characteristics" :key="characteristic.id" />
        <div class="buttons last">
            <div class="btn add icon" @click="handleAdd">Add Characteristic</div>
        </div>
    </div>
</template>
<script>
    import CharacteristicItem from "./CharacteristicItem";
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
                characteristics: []
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                this.characteristics.push({
                    id: 0,
                    name: 'hello'
                });
            }
        },
        computed: {
        },
        watch: {
        },
        mounted() {
        }
    }
</script>

<style lang="scss">

</style>