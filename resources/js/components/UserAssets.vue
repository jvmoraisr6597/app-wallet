<template>
  <div class="container mt-4">
      <!-- Loading Indicator -->
      <div v-if="loading" class="text-center mt-4">
          <p>Carregando...</p>
      </div>

      <!-- Content -->
      <div v-else>
          <!-- Header -->
          <div class="mb-2">
              <h3>Rentabilidade da Carteira</h3>
          </div>

          <!-- Summary -->
          <div class="row">
              <div class="col-12">
                  <div class="d-flex flex-column flex-md-row justify-content-between">
                      <p class="mb-2 mb-md-0">Total Investido: R${{ resume['total_invest'] }}</p>
                      <p class="mb-2 mb-md-0">Total de Dividendos: R${{ resume['total_dividend'] }}</p>
                      <p class="mb-2 mb-md-0">Lucro Corrente (Sem dividendos): R${{ resume['total_gain'] }}</p>
                      <p class="mb-2 mb-md-0">Lucro Corrente (Com dividendos): R${{ (resume['total_gain'] + resume['total_dividend']).toFixed(2) }}</p>
                  </div>
              </div>
          </div>
          <div class="mt-3" style="display:flex; justify-content: space-between">
              <h3>Ativos</h3>
              <a href="/add-asset" style="font-size: 30px; padding: 0; border: solid 1px white; border-radius: 50px; width: 30px; height: 30px; text-align: center; text-decoration: none; color: white; display: flex; align-items:center; background-color: #48e092; justify-content: center">+</a>
          </div>
          <!-- Asset Cards -->
          <div class="row">
              <div v-for="(asset, index) in assets" :key="index" class="col-md-4 col-lg-3 mb-4">
                  <div class="custom-card p-0">
                      <AssetCard :asset="asset"></AssetCard>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script>
import axios from 'axios';
import AssetCard from './AssetCard.vue'; // Importe o componente de card de ativo

export default {
  props: {
    userId: {
      type: Number,
      required: true
    }
  },
  components: {
    AssetCard
  },
  data() {
    return {
      assets: [],
      resume: [],
      loading: true
    };
  },
  mounted() {
    this.fetchUserAssets();
  },
  methods: {
    fetchUserAssets() {
      axios.get('/api/assets/current/' + this.userId) // Endpoint da API para obter ativos do usuário
        .then(response => {
          this.assets = response.data;
          this.resume = this.assets["resume"];
          delete this.assets["resume"];
          this.loading = false;
        })
        .catch(error => {
          console.error('Erro ao buscar ativos:', error);
          this.loading = false;
        });
    }
  }
};
</script>

<style>
.card-custom {
        border-radius: 10px;
        border: none;
        max-width: calc(33.333% - 1rem);
    }
/* Estilos opcionais para o componente */
</style>
