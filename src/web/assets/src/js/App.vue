<template>
  <div>
    <div v-for="block in blocks" :key="block.id">
      <input type="hidden" :name="getCharacteristicFieldName(block.id)" :value="block.characteristicId"/>
      <input type="hidden" v-for="value in block.values" :name="getValuesFieldName(block.id)" :value="value"/>
    </div>

    <characteristic-link-block v-for="block in blocks"
                               :block="block"
                               :key="block.id"
                               @delete-link-block="handleDeleteBlock(block)"
                               @selectValue="(value) => handleUpdateValue(block, value)"
    />
    <characteristic-controls :characteristics="unusedCharacteristics" @add-link-block="handleAddBlock" />
  </div>
</template>

<script lang="ts">
import {defineComponent, computed, computed} from 'vue'
import CharacteristicControls from "./CharacteristicControls.vue";
import CharacteristicLinkBlock from "./CharacteristicLinkBlock.vue";
import {characteristicStore} from "./store/CharacteristicStore";

export default defineComponent({
  name: 'App',
  components: {
    CharacteristicLinkBlock,
    CharacteristicControls
  },
  setup() {
    const getCharacteristicFieldName = (blockId: number): string => {
      return characteristicStore.getState().fieldName + '[' + blockId + '][characteristic]'
    }
    const getValuesFieldName = (blockId: number): string => {
      return characteristicStore.getState().fieldName + '[' + blockId + '][values][]'
    }
    return {
      getValuesFieldName,
      getCharacteristicFieldName,
      blocks: computed(() => characteristicStore.getState().blocks),
      characteristics: characteristicStore.getState().characteristics,
      unusedCharacteristics: computed(() => characteristicStore.getUnusedCharacteristics()),
      characteristicFieldName: characteristicStore.getState().fieldName,
      handleAddBlock: (block: characteristic.CharacteristicLinkBlock) => characteristicStore.addBlock(block),
      handleDeleteBlock: (block: characteristic.CharacteristicLinkBlock) => characteristicStore.deleteBlock(block),
      handleUpdateValue: (block: characteristic.CharacteristicLinkBlock, value: any) => characteristicStore.updateBlockValue(block, value)
    }
  },
  props: {
  }
})
</script>