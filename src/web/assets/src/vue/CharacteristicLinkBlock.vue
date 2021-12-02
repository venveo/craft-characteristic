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
        <VueMultiselect
        v-model="internalValue"
        @update:model-value="handleChange"
        :multiple="characteristic.maxValues == null || characteristic.maxValues > 1"
        :max="(characteristic.maxValues !== null && characteristic.maxValues > 1) ? characteristic.maxValues : null"
        :taggable="!!characteristic.allowCustomOptions"
        track-by="id"
        label="value"
        :searchable="true"
        @tag="handleCreateTag"
        :options="characteristic.values"
        >
          <template v-slot:tag="{option, remove}">
            <div class="multiselect__tag" @mousedown.prevent @click.exact="handleTagClicked(option)">
              <span>{{ option.value }}</span>
              <button type="button" v-on:click.stop="remove(option)" class="ml-2 delete icon"
                      title="Remove" :aria-label="'Remove' + option.value"></button>
            </div>
          </template>
        </VueMultiselect>
      </div>
    </td>
  </tr>
<!--  <pre>{{JSON.stringify(internalValue)}}</pre>-->
<!--  <pre>{{JSON.stringify(internalValue)}}</pre>-->
<!--  <pre>{{JSON.stringify(block.values)}}</pre>-->
</template>
<script lang="ts">
import {computed, defineComponent, ref, toRef, PropType, watch} from "vue";
import {useMainStore} from '@/js/store/main'

import { TemporaryId, Id, RootState, Characteristic, CharacteristicLinkBlock as CharacteristicLinkBlockInterface, CharacteristicValue, CharacteristicValueValue } from '@/js/types/characteristics';


import VueMultiselect from 'vue-multiselect'

/* eslint-disable */
/* global Craft */
export default defineComponent({
  components: {
    VueMultiselect
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
    const characteristic = computed(() => store.getCharacteristicById(block.characteristicId)) as Characteristic;
    const values = computed(() => characteristic.value.values) as CharacteristicValue[]
    const initialValues = []
    console.log(block.values)
    const blockValueIds = block.values;
    console.log({blockValueIds})
    // console.log({blockValueIds})
    values.value.forEach((value) => {
      console.log('Checking', value)
      if (blockValueIds.includes(value.id)) {
        initialValues.push(value)
      }
    })
    const internalValue = ref(initialValues);

    const isLoading = ref(false);
    // const characteristicValues = computed(() => characteristic.values)

    const handleChange = (value: any) => {
      let resolvedValue = value;
      if (!Array.isArray(value)) {
        resolvedValue = [value.id]
      } else {
        resolvedValue = value.map((v) => v.id)
      }
      store.updateBlockValue(block.id, resolvedValue)
      // emit('selectValue', internalValue.value)
    }

    function handleValueSaveResponse(tag, response) {
      // TODO
      console.log({tag, response})
    }

    const handleTagClicked = (tag) => {
      console.log('Click!', {tag})
      const elementInfo = {
        elementId: tag.id,
        onHideHud: () => {
          isLoading.value = false
        },
        onSaveElement: response => {
          handleValueSaveResponse(tag, response)
        },
      }
      Craft.createElementEditor('venveo\\characteristic\\elements\\CharacteristicValue', elementInfo)
    }

    const handleCreateTag = (tag) => {
      console.log('Create tag!', {tag})
      isLoading.value = true
      const elementInfo = {
        attributes: {
          characteristicId: characteristic.value.id,
          value: tag
        },
        onHideHud: () => {
          isLoading.value = false
        },
        onSaveElement: response => {
          handleValueSaveResponse(tag, response)
        },
      }
      console.log({elementInfo})
      Craft.createElementEditor('venveo\\characteristic\\elements\\CharacteristicValue', elementInfo)
    }

    return {
      block,
      characteristic,
      handleChange,
      handleCreateTag,
      handleTagClicked,
      internalValue,
      values,
      isLoading
    }
  },
})
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style lang="postcss">
.multiselect-wrapper:focus-within {
  box-shadow: 0 0 0 1px #127fbf, 0 0 0 3px rgb(18 127 191 / 50%) !important;
  border-radius: 4px;
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
  margin-bottom: 0 !important;
}

.multiselect-wrapper {
  .multiselect__tags {
    padding: 8px 40px 0 8px;
    border-radius: 5px;
    border: none;
    background: var(--ms-bg);
    min-height: 50px;
    display: flex;
    align-items: center;
  }
  .multiselect__option--highlight {
    background-color: var(--ms-option-bg-pointed);
    color: var(--ms-option-color-pointed);
  }
  .multiselect__content-wrapper {
    box-shadow: 0 0 0 1px rgb(31 41 51 / 10%), 0 5px 20px rgb(31 41 51 / 25%);
    border: none;
    border-radius: 5px;
  }
  .multiselect__single, .multiselect__tag {
    width: auto;
    background: var(--ms-tag-bg);
    padding: var(--ms-tag-py) var(--ms-tag-px);
    font-size: var(--ms-font-size);
    height: 36px;
  }
  .multiselect__placeholder {
    padding-top: 4px;
  }
  .multiselect__tags {
    padding-top: 4px;
  }
  .multiselect__tag {
    cursor: pointer;
    line-height: initial;
    color: var(--ms-tag-color);
    display: flex;
    align-items: center;
    &:hover {
      background-color: rgba(96,125,159,.35);
    }
  }
  .multiselect__tags-wrap {
    display: flex;
  }
  .multiselect__input.focus-visible {
    box-shadow: none;
    background: transparent;
  }
  .multiselect__select {
    top: 6px;
  }
  .multiselect__option--selected.multiselect__option--highlight:after {
    background-color: #cf1124;
  }
}
</style>
