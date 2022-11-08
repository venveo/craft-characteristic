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
import {defineComponent, PropType, ref} from 'vue';
import { TemporaryId, Id, RootState, Characteristic, CharacteristicLinkBlock as CharacteristicLinkBlockInterface, CharacteristicValueValue } from '@/js/types/characteristics';
import {useMainStore} from '@/js/store/main'
import {computed} from "vue"

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
    // const store = useMainStore()
    const characteristics = props.characteristics
    const selectedCharacteristicIndex = ref<number>(0)
    // const {characteristics} = props
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
  }
});
</script>
