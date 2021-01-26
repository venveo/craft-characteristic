<template>
  <div class="matrixblock">
    <div class="fields flex flex-nowrap" ref="addValueTrigger">
      <a class="input ltr characteristic__title" :href="characteristic.cpEditUrl" target="_blank">
        <strong>{{ characteristic.title }}</strong>
      </a>
      <div class="w-full">

        <multiselect
            :modelValue="block.values"
            @change="handleChange"
            :options="characteristic.values"
            :createTag="!!characteristic.allowCustomOptions"
            mode="tags"
            :max="parseInt(characteristic.maxValues)"
            trackBy="id"
            label="value"
            valueProp="id"
            :object="false"
        />
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
// import {useStore} from "./store";
import {characteristicStore} from "./store/CharacteristicStore";
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'
import CharacteristicLinkBlock = characteristic.CharacteristicLinkBlock;

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
  emits: ['selectValue'],
  setup(props, {emit}) {
    const {block} = props
    watch(block, (v) => {console.log('Updated', v)})

    const characteristic = computed(() => characteristicStore.getCharacteristicById(block.characteristicId))
    const values = computed(() => characteristic.value.values)
    // const characteristicValues = computed(() => characteristic.values)
    const handleChange = (value) => {
      emit('selectValue', value)
    }

    // const internalValue = computed({
    //   get: () => block.values,
    //   set: (value) => emit('selectValue', value)
    // })
    // console.log('C values', characteristicValues)
    //
    //   console.log('Value Ids', props.valueIds)
    //   console.log('Value Ids Unref', unref(props.valueIds))
    //   const res = props.valueIds.map((v) => {
    //     // const values = characteristic.value.values
    //     // return values.value.find((o) => {
    //     //   return o.id === v
    //     // });
    //   })
    //   return []
    // })
    // console.log(valuesInternal)
    // console.log('Characteristic', characteristic.value.values)
    // watch(characteristic, (v) => {
    //   console.log('Characteristic', v)
    // })
    return {
      block,
      characteristic,
      handleChange,
      values
    }
  },
  // methods: {
  //   addTag(newTag) {
  //     Craft.createElementEditor("venveo\\characteristic\\elements\\CharacteristicValue", {
  //       hudTrigger: $(this.$refs.addValueTrigger),
  //       siteId: Craft.siteId,
  //       attributes: {
  //         characteristicId: this.characteristicId
  //       },
  //       onSaveElement: function (response) {
  //         console.log(response)
  //       }
  //     });
  //     console.log(newTag);
  //     // this.values.push(newTag);
  //   },
  // },
  // beforeMount() {
  //   const valueMap = this.valueIds.map((v) => {
  //     return this.characteristic.values.find(o => o.id === v);
  //   })
  //   this.valuesInternal = valueMap;
  // },
  // watch: {},
  // computed: {
  // formattedValues() {
  //   if (Array.isArray(this.valuesInternal)) {
  //     return this.valuesInternal;
  //   }
  //   return [this.valuesInternal]
  // },
  // valuesFieldName() {
  //   return this.$root.name + '[' + this.blockId + '][values][]';
  // },
  // availableValues() {
  //   return this.characteristic.values.filter(o => !o.idempotent);
  // }
  // }
})
</script>

<style>
.multiselect {
  position: relative;
  margin: 0 auto;
  font-size: 0
}

.multiselect > * {
  font-size: medium
}

.multiselect.is-searchable {
  cursor: auto
}

.multiselect-input {
  width: 100%;
  display: flex;
  align-items: center;
  min-height: 40px;
  border: 1px solid #e7e7e7;
  border-radius: 3px;
  box-sizing: border-box;
  cursor: pointer;
  position: relative;
  outline: none
}

.multiselect-input:before {
  position: absolute;
  right: 12px;
  top: 50%;
  color: #999;
  border-color: #999 transparent transparent;
  border-style: solid;
  border-width: 5px 5px 0;
  content: "";
  transform: translateY(-50%);
  transition: transform .3s
}

.is-disabled .multiselect-input {
  background: #f9f9f9
}

.is-open .multiselect-input {
  border-radius: 3px 3px 0 0
}

.is-open .multiselect-input:before {
  transform: translateY(-50%) rotate(180deg)
}

.no-caret .multiselect-input:before {
  display: none
}

.multiselect-multiple-label, .multiselect-placeholder, .multiselect-single-label {
  display: flex;
  align-items: center;
  height: 100%;
  padding-left: 14px;
  position: absolute;
  left: 0;
  top: 0;
  pointer-events: none;
  background: transparent
}

.multiselect-placeholder {
  color: #777
}

.is-multiple .multiselect-search, .is-single .multiselect-search {
  display: flex;
  height: 100%;
  width: 100%;
  background: transparent
}

.is-multiple .multiselect-search input, .is-single .multiselect-search input {
  width: 100%;
  border: 0;
  padding: 8px 35px 8px 14px;
  outline: none;
  background: transparent;
  font-size: 16px;
  font-family: inherit
}

.is-multiple.no-caret .multiselect-search input, .is-single.no-caret .multiselect-search input {
  padding: 8px 14px
}

.is-tags .multiselect-search {
  flex-grow: 1
}

.is-tags .multiselect-search input {
  outline: none;
  border: 0;
  margin: 0 0 5px 3px;
  flex-grow: 1;
  min-width: 100%;
  font-size: 16px;
  font-family: inherit
}

.multiselect-tags {
  display: flex;
  height: 100%;
  width: 100%;
  align-items: center;
  justify-content: flex-start;
  padding-left: 9px;
  margin-top: 5px;
  flex-wrap: wrap;
  padding-right: 36px
}

.no-caret .multiselect-tags {
  padding-right: 9px
}

.multiselect-tag {
  background: #cdd8e4;
  color: #3f4d5a;
  font-size: 14px;
  font-weight: 400;
  padding: 0 0 0 8px;
  border-radius: 3px;
  margin-right: 5px;
  margin-bottom: 5px;
  display: flex;
  align-items: center;
  cursor: text;
  white-space: nowrap
}

.multiselect-tag i {
  cursor: pointer
}

.multiselect-tag i:before {
  content: "\D7";
  color: rgba(123, 135, 147, 0.5);
  font-size: 14px;
  font-weight: 700;
  padding: 1px 5px;
  margin-left: 3px;
  display: flex;
  font-style: normal
}

.multiselect-tag i:hover:before {
  color: #CF1124;
  background: transparent;
}

.is-disabled .multiselect-tag {
  background: #a0a0a0;
  padding: 1px 8px
}

.multiselect-options {
  position: absolute;
  left: 0;
  right: 0;
  border: 1px solid #e8e8e8;
  margin-top: -1px;
  max-height: 160px;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
  z-index: 100;
  background: #fff
}

.multiselect-option {
  display: flex;
  min-height: 40px;
  padding: 9px 12px;
  box-sizing: border-box;
  color: #222;
  text-decoration: none;
  align-items: center;
  justify-content: flex-start;
  text-align: left
}

.multiselect-option.is-pointed {
  background: #e6e6e6
}

.multiselect-option.is-disabled {
  background: #f3f7fc;
  color: #3f4d5a;
  opacity: .25;
  cursor: not-allowed
}

.multiselect-option.is-selected {
  background: #f3f7fc;
  opacity: .25;
  color: #3f4d5a
}

.multiselect-option.is-selected.is-pointed {
  background: #f3f7fc;
  color: #3f4d5a
}

.is-multiple .multiselect-option.is-selected, .is-tags .multiselect-option.is-selected {
  color: #999;
  background: transparent
}

.is-multiple .multiselect-option.is-selected.is-pointed, .is-tags .multiselect-option.is-selected.is-pointed {
  background: #f1f1f1
}

.multiselect-no-options, .multiselect-no-results {
  display: flex;
  padding: 10px 12px;
  color: #777
}

.multiselect-spinner {
  position: absolute;
  right: 12px;
  top: 0;
  width: 16px;
  height: 16px;
  background: #fff;
  display: block;
  transform: translateY(50%)
}

.multiselect-spinner:after, .multiselect-spinner:before {
  position: absolute;
  content: "";
  top: 50%;
  left: 50%;
  margin: -8px 0 0 -8px;
  width: 16px;
  height: 16px;
  border-radius: 100%;
  border: 2px solid transparent;
  border-top-color: #41b883;
  box-shadow: 0 0 0 1px transparent
}

.is-disabled .multiselect-spinner {
  background: #f9f9f9
}

.is-disabled .multiselect-spinner:after, .is-disabled .multiselect-spinner:before {
  border-color: #999 transparent transparent
}

.multiselect-spinner:before {
  -webkit-animation: spinning 2.4s cubic-bezier(.41, .26, .2, .62);
  animation: spinning 2.4s cubic-bezier(.41, .26, .2, .62);
  -webkit-animation-iteration-count: infinite;
  animation-iteration-count: infinite
}

.multiselect-spinner:after {
  -webkit-animation: spinning 2.4s cubic-bezier(.51, .09, .21, .8);
  animation: spinning 2.4s cubic-bezier(.51, .09, .21, .8);
  -webkit-animation-iteration-count: infinite;
  animation-iteration-count: infinite
}

.multiselect-enter-active {
  transition: all .15s ease
}

.multiselect-leave-active {
  transition: all 0s
}

.multiselect-enter, .multiselect-leave-active {
  opacity: 0
}

.multiselect-loading-enter-active, .multiselect-loading-leave-active {
  transition: opacity .4s ease-in-out;
  opacity: 1
}

.multiselect-loading-enter, .multiselect-loading-leave-active {
  opacity: 0
}

@-webkit-keyframes spinning {
  0% {
    transform: rotate(0)
  }
  to {
    transform: rotate(2turn)
  }
}

@keyframes spinning {
  0% {
    transform: rotate(0)
  }
  to {
    transform: rotate(2turn)
  }
}
</style>