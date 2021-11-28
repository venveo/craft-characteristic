<template>
  <tr>
    <td class="text-left w-[1px] whitespace-nowrap">
      <a @click="$emit('deleteLinkBlock')" class="error icon delete" data-icon="remove"
         role="button" title="Delete"
         v-if="!characteristic.required"></a>
    </td>
    <td class="w-[1px] whitespace-nowrap">
        <a :href="characteristic.cpEditUrl" target="_blank">
          <strong>{{ characteristic.title }}</strong>
        </a>
    </td>
    <td>
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
          placeholder="Enter a value"
          :object="false"
      >
        <template v-slot:tag="{ option, handleTagRemove, disabled }">
          <div class="multiselect-tag">
            {{ option.value }}
            <span
                v-if="!disabled"
                class="multiselect-tag-remove"
                @mousedown.prevent="handleTagRemove(option, $event)"
            >
          <span class="multiselect-tag-remove-icon"></span>
        </span>
          </div>
        </template>
      </Multiselect>
      </div>
    </td>
  </tr>
</template>
<script lang="ts">
import {computed, defineComponent, ref, toRef, PropType, watch} from "vue";
import {useMainStore} from '@/js/store/main'

import { TemporaryId, Id, RootState, Characteristic, CharacteristicLinkBlock as CharacteristicLinkBlockInterface, CharacteristicValue, CharacteristicValueValue } from '@/js/types/characteristics';


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
      type: Object as PropType<CharacteristicLinkBlockInterface>,
      required: true
    },
  },
  setup(props, {emit}) {
    const store = useMainStore()
    const {block} = props
    watch(block, (v) => {
      console.log('Updated', v)
    })

    const characteristic = computed(() => store.getCharacteristicById(block.characteristicId)) as Characteristic;
    const values = computed(() => characteristic.value.values) as CharacteristicValue[]
    // const characteristicValues = computed(() => characteristic.values)
    const handleChange = (value: any) => {
      emit('selectValue', value)
    }

    const clickAddValue = () => {
      this.$refs.multiselect.open()
    }

    const getMultiselectMode = () => {
      if (characteristic.allowCustomOptions) {
        return 'tags'
      }
      if (characteristic.maxValues > 1) {
        return 'multiple'
      }
      return 'single'
    }

    return {
      block,
      characteristic,
      handleChange,
      values,
      clickAddValue,
      getMultiselectMode
    }
  },
})
</script>

<style lang="css">
.multiselect-tags-search {
  background-color: transparent !important;
  padding-top: 7px !important;
  padding-bottom: 7px !important;
}
.reduce-focus-visibility .multiselect-tags-search.focus-visible {
  box-shadow: none !important;
}
.multiselect-wrapper {
  --ms-bg: rgba(96,125,159,.25);
  --ms-border-width: 0;
  --ms-tag-bg: rgba(96, 125, 159, .25);
  --ms-tag-color: #3f4d5a;
  --ms-line-height: 20px;
  --ms-font-size: 14px;
  --ms-tag-font-weight: 'normal';
  --ms-ring-width: 0;

  --ms-option-bg-pointed: #8b96a2;
  --ms-option-color-pointed: #ffffff;

  --ms-dropdown-border-width: 0;

  --ms-tag-py: 7px;
  --ms-tag-px: 10px;
  //--ms-py: 0;
  margin-bottom: 0 !important;
}
.multiselect-wrapper:focus-within {
  box-shadow: 0 0 0 1px #127fbf, 0 0 0 3px rgb(18 127 191 / 50%) !important;
  border-radius: 4px;
}

.multiselect-tags-search-wrapper {
  @apply py-2;
}

.multiselect-dropdown {
  @apply w-full;
  overflow: auto !important;
  border-radius: 5px !important;
  box-shadow: 0 1px 5px -1px rgb(31 41 51 / 20%) !important;
}

.multiselect-options {
  box-shadow: 0 0 0 1px rgb(31 41 51 / 10%), 0 5px 20px rgb(31 41 51 / 25%);
}

</style>

<style src="@vueform/multiselect/themes/default.css"></style>