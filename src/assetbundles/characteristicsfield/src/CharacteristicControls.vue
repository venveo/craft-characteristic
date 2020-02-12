<!--
  -  @link      https://www.venveo.com
  -  @copyright Copyright (c) 2020 Venveo
  -->

<template>
    <transition name="fade">
        <div class="buttons last add-button" v-if="availableCharacteristics.length !== 0">
            <div class="select">
                <select v-model="selectedCharacteristicIndex">
                    <option :value="index" v-for="(characteristic, index) in availableCharacteristics">
                        {{characteristic.title}}
                    </option>
                </select>
            </div>
            <div @click="handleAdd" class="btn add icon">Add</div>
        </div>
    </transition>
</template>
<script>
    export default {
        components: {},
        props: {
            characteristics: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                selectedCharacteristicIndex: null,
            }
        },
        methods: {
            handleAdd(e) {
                e.preventDefault();
                this.$root.addBlock(this.selectedCharacteristic);
                this.selectedCharacteristicIndex = 0;
            }
        },
        beforeMount() {
            if (this.characteristics.length && this.selectedCharacteristicIndex === null) {
                this.selectedCharacteristicIndex = 0;
            }
        },
        computed: {
            availableCharacteristics() {
                return this.characteristics.filter((c) => {
                    return this.$root.usedCharacteristics[c.id] === "undefined" || !this.$root.usedCharacteristics[c.id];
                })
            },
            selectedCharacteristic() {
                return this.availableCharacteristics[this.selectedCharacteristicIndex];
            }
        },
        watch: {},
        /**
         * Load in our characteristics and their value options
         */
        mounted() {
        }
    }
</script>

<style lang="scss">
    .fade-enter-active, .fade-leave-active {
        transition: opacity .25s;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
    {
        opacity: 0;
    }

    .add-button {
        .select > select {
            border-radius: 5px 0 0 5px;
        }

        .btn {
            border-radius: 0 5px 5px 0;
        }
    }
</style>