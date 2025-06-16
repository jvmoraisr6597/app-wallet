<template>
  <div class="w-150" style="height: 350px; margin-top: -50px">
    <Pie :data="data" :options="options" />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Pie } from 'vue-chartjs'

export default defineComponent({
  name: 'TestPieChart',
  components: {
    Pie
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
