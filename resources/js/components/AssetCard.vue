<template>
  <div class="asset" :style="{ backgroundColor: backgroundColor }">
    <div class="asset-header">{{ asset.code }}</div>
    <div class="asset-body">
      <p>Quantidade investida: {{ asset.quantity }}</p>
      <p>Valorização: {{ asset.gain }}</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    asset: {
      type: Object,
      required: true
    }
  },
  computed: {
    backgroundColor() {
      if (this.asset.gain < 0) {
        return '#f53b3b'; // Cor para valores negativos
      } else if (this.asset.gain === 0) {
        return '#5fe6e8'; // Cor para valor zero
      } else {
        return '#16ba60'; // Cor para valores positivos
      }
    }
  }
};
</script>

<style>
.asset{
  border-radius: 10px;
  padding: 10px;
}
/* Estilos do componente, se necessário */
</style>
