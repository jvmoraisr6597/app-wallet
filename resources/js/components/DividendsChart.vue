<template>
  <Bar :data="data" :options="options" />
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Bar } from 'vue-chartjs'

export default defineComponent({
  name: 'TestChart',
  components: {
    Bar
  },
  props: {
    data: {
      type: Object,
      required: true
    },
    options: {
      type: Object,
      required: true
    }
  }
})
</script>
