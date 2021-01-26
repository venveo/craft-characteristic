<template>
  <div>
    <div v-for="block in blocks">
      <input type="hidden" :name="getCharacteristicFieldName(block.id)" :value="block.characteristicId"/>
      <input type="hidden" v-for="value in block.values" :name="getValuesFieldName(block.id)" :value="value"/>
    </div>

    <characteristic-link-block v-for="block in blocks"
                               :block="block"
                               @deleteLinkBlock="handleDeleteBlock(block)"
                               @selectValue="(value) => handleUpdateValue(block, value)"
    />
    <characteristic-controls :characteristics="characteristics" @addLinkBlock="handleAddBlock" />
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType, ref, provide, computed} from 'vue'
import CharacteristicControls from "./CharacteristicControls.vue";
import Characteristic = characteristic.Characteristic;
import CharacteristicLinkBlock from "./CharacteristicLinkBlock.vue";
import {characteristicStore} from "./store/CharacteristicStore";

export default defineComponent({
  name: 'App',
  components: {
    CharacteristicLinkBlock,
    CharacteristicControls
  },
  setup(props) {
    const getCharacteristicFieldName = (blockId) => {
      return characteristicStore.getState().fieldName + '[' + blockId + '][characteristic]'
    }
    const getValuesFieldName = (blockId) => {
      return characteristicStore.getState().fieldName + '[' + blockId + '][values][]'
    }
    return {
      getValuesFieldName,
      getCharacteristicFieldName,
      blocks: computed(() => characteristicStore.getState().blocks),
      characteristics: characteristicStore.getState().characteristics,
      characteristicFieldName: characteristicStore.getState().fieldName,
      handleAddBlock: (block: CharacteristicLinkBlock) => characteristicStore.addBlock(block),
      handleDeleteBlock: (block: CharacteristicLinkBlock) => characteristicStore.deleteBlock(block),
      handleUpdateValue: (block, value) => characteristicStore.updateBlockValue(block, value)
    }
  },
  props: {
  }
})
</script>