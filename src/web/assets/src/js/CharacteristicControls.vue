<!--
  -  @link      https://www.venveo.com
  -  @copyright Copyright (c) 2020 Venveo
  -->

<template>
  <div class="buttons">
    <div class="select">
      <select v-model.number="selectedCharacteristicIndex" class="!rounded-r-none">
        <option :value="index" v-for="(characteristic, index) in characteristics" :key="characteristic.id">
          {{ characteristic.title }}
        </option>
      </select>
    </div>
    <button @click.prevent="handleAddBlock(characteristics[selectedCharacteristicIndex])" class="btn add icon rounded-l-none">Add</button>
  </div>
</template>
<script lang="ts">
import CharacteristicLinkBlock from './CharacteristicLinkBlock.vue';
import {defineComponent, PropType, ref} from 'vue';
import Characteristic = characteristic.Characteristic;

export default defineComponent({
  components: {},
  emits: ['addLinkBlock'],
  props: {
    characteristics: {
      type: Array as PropType<Array<Characteristic>>,
      required: true
    }
  },
  setup(props, {emit}) {
    const selectedCharacteristicIndex = ref<number>(0)
    const {characteristics} = props
    const handleAddBlock = function (characteristic: Characteristic) {
      let linkBlock: CharacteristicLinkBlock = {
        id: null,
        characteristicId: characteristic.id,
        values: []
      }
      emit('addLinkBlock', linkBlock)
    }
    return {
      selectedCharacteristicIndex,
      characteristics,
      handleAddBlock
    }
  },
  // methods: {
  //   handleAdd(e) {
  //     e.preventDefault();
  //     this.$root.addBlock(this.selectedCharacteristic);
  //     this.selectedCharacteristicIndex = 0;
  //   }
  // },
  // beforeMount() {
  // if (this.characteristics.length && this.selectedCharacteristicIndex === null) {
  //   this.selectedCharacteristicIndex = 0;
  // }
  // },
  // computed: {
  // availableCharacteristics() {
  //   return this.characteristics.filter((c) => {
  //     return this.$root.usedCharacteristics[c.id] === "undefined" || !this.$root.usedCharacteristics[c.id];
  //   })
  // },
  // selectedCharacteristic() {
  //   return this.availableCharacteristics[this.selectedCharacteristicIndex];
  // }
  // },
  /**
   * Load in our characteristics and their value options
   */
});
</script>
