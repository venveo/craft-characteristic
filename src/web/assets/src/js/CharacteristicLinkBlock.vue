<template>
  <div class="matrixblock">
    <div class="fields flex flex-nowrap" ref="addValueTrigger">
      <a class="input ltr characteristic__title" :href="characteristic.cpEditUrl" target="_blank">
        <strong>{{ characteristic.title }}</strong>
      </a>
      <div class="input-wrapper">
        <div class="multiselect-wrapper">
          <Multiselect
              :modelValue="block.values"
              @change="handleChange"
              :options="characteristic.values"
              :createTag="!!characteristic.allowCustomOptions"
              :mode="'tags'"
              :max="parseInt(characteristic.maxValues)"
              trackBy="id"
              label="value"
              valueProp="id"
              :searchable="true"
              :object="false"
          />
        </div>
      </div>
      <div>
        <a @click="$emit('deleteLinkBlock')" class="error icon delete" data-icon="remove"
           role="button" title="Delete"
           v-if="!characteristic.required"></a>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import {computed, defineComponent, ref, toRef, PropType, watch} from "vue";
import { useMainStore } from './store/main'
import CharacteristicLinkBlock = characteristic.CharacteristicLinkBlock;
import Multiselect from '@vueform/multiselect'

/* eslint-disable */
/* global Craft */
export default defineComponent({
  components: {
    Multiselect
  },
  emits: ['deleteLinkBlock', 'selectValue'],
  props: {
    block: {
      type: Object as PropType<CharacteristicLinkBlock>,
      required: true
    },
  },
  setup(props, {emit}) {
    const store = useMainStore()
    const {block} = props
    watch(block, (v) => {console.log('Updated', v)})

    const values = computed(() => characteristic.value.values)
    // const characteristicValues = computed(() => characteristic.values)
    const handleChange = (value: any) => {
      emit('selectValue', value)
    }

    return {
      block,
      characteristic: computed(() => store.getCharacteristicById(block.characteristicId)),
      handleChange,
      values
    }
  },
})
</script>

<style lang="css">
.multiselect-wrapper {
  --ms-bg: transparent;
  --ms-border-width: 0;
  --ms-tag-bg: rgba(96,125,159,.25);
  --ms-tag-color: #3f4d5a;
  --ms-line-height: 20px;
  --ms-font-size: 14px;
  --ms-tag-font-weight: 'normal';

  --ms-option-bg-pointed: transparent;

  --ms-tag-py: 7px;
  --ms-tag-px: 10px;
}
.multiselect-options {
  box-shadow: 0 0 0 1px rgb(31 41 51 / 10%), 0 5px 20px rgb(31 41 51 / 25%);
}
.input-wrapper {
}
.characteristic__title {
  flex: none;
}
</style>

<style src="@vueform/multiselect/themes/default.css"></style>