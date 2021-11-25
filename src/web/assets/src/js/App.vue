<template>
  <div>
    <div v-for="block in blocks" :key="block.id">
      <input type="hidden" :name="getCharacteristicFieldName(block.id)" :value="block.characteristicId"/>
      <input type="hidden" v-for="value in block.values" :name="getValuesFieldName(block.id)" :value="value"/>
    </div>

    <characteristic-link-block v-for="block in blocks"
                               :block="block"
                               :key="block.id"
                               @delete-link-block="handleDeleteBlock(block.id)"
                               @select-value="(value) => handleUpdateValue(block.id, value)"
    />
    <characteristic-controls :characteristics="unusedCharacteristics" @add-link-block="handleAddBlock" />
  </div>
</template>

<script lang="ts">
import {defineComponent, computed} from 'vue'
import CharacteristicControls from "./CharacteristicControls.vue";
import CharacteristicLinkBlock from "./CharacteristicLinkBlock.vue";
import { useMainStore } from './store/main'
import {storeToRefs} from "pinia";

import Id = characteristic.Id;
import TemporaryId = characteristic.TemporaryId;
import CharacteristicLinkBlockInterface = characteristic.CharacteristicLinkBlock;
import CharacteristicValueValue = characteristic.CharacteristicValueValue;

export default defineComponent({
  name: 'App',
  components: {
    CharacteristicLinkBlock,
    CharacteristicControls
  },
  setup() {
    const store = useMainStore()
    const { fieldName, blocks, characteristics, unusedCharacteristics } = storeToRefs(store);

    const getCharacteristicFieldName = (blockId: Id|TemporaryId): string => {
      return fieldName.value + '[' + blockId + '][characteristic]'
    }
    const getValuesFieldName = (blockId: Id|TemporaryId): string => {
      return fieldName.value + '[' + blockId + '][values][]'
    }
    return {
      getValuesFieldName,
      getCharacteristicFieldName,
      blocks,
      characteristics,
      unusedCharacteristics: computed(() => unusedCharacteristics),
      handleAddBlock: (block: CharacteristicLinkBlockInterface) => store.addBlock(block),
      handleDeleteBlock: (blockId: Id|TemporaryId) => store.deleteBlockById(blockId),
      handleUpdateValue: (blockId: Id|TemporaryId, value: CharacteristicValueValue) => store.updateBlockValue(blockId, value)
    }
  },
  props: {
  }
})
</script>