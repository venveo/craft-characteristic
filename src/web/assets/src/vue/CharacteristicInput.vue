<template>
  <div>
    <slot name="value" ref="value"></slot>
    <slot name="blocks" v-bind:init="initBlock"></slot>
    <slot name="controls"></slot>
  </div>
</template>
<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({
  setup() {
    return {
      initBlock: () => {
      }
    }
  }
})
</script>