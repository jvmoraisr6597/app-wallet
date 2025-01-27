<template>
  <div class="container mt-4">
    <div v-if="loading" class="text-center mt-4">
      <p>Carregando...</p>
    </div>
    <div v-else>
      <div class="mb-2">
        <h3>Rentabilidade da Carteira</h3>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between">
            <p class="mb-2 mb-md-0">Total Investido: R${{ resume['total_invest'] || 0 }}</p>
            <p class="mb-2 mb-md-0">Total de Dividendos: R${{ resume['total_dividend'] || 0 }}</p>
            <p class="mb-2 mb-md-0 ml-2">Lucro proveniente de vendas: R${{ (resume['gain_per_sale'] || 0).toFixed(2) }}</p>
            <p class="mb-2 mb-md-0">Lucro Corrente (Sem dividendos): R${{ resume['total_gain'] || 0 }}</p>
            
            
          </div>
          <div class="d-flex flex-column flex-md-row justify-content-between mt-2">
            <p class="mb-2 mb-md-0">
              Lucro Corrente (Com dividendos): R${{ ((resume['total_gain'] || 0) + (resume['total_dividend'] || 0)).toFixed(2) }}
            </p>
            <p class="mb-2 mb-md-0">
              Lucro Corrente (Com dividendos + vendas): R${{ ((resume['total_gain'] || 0) + (resume['total_dividend'] || 0) + (resume['gain_per_sale'] || 0)).toFixed(2) }}
            </p>
            <p></p>
            <p></p>
          </div>
        </div>
      </div>
      <div class="mt-3" style="display: flex; justify-content: space-between">
        <h3>Ativos</h3>
        <a
          href="/add-asset"
          style="
            font-size: 30px;
            padding: 0;
            border: solid 1px white;
            border-radius: 50px;
            width: 30px;
            height: 30px;
            text-align: center;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            background-color: #48e092;
            justify-content: center;
          "
          >+</a
        >
      </div>
      <div class="row">
        <div v-for="(asset, index) in assets" :key="index" class="col-md-4 col-lg-3 mb-4">
          <div class="custom-card p-0">
            <AssetCard :asset="asset"></AssetCard>
          </div>
        </div>
      </div>
      <!-- Renderizar o gráfico apenas se chartData estiver definido -->
      <div class="mt-3">
        <h3>Histórico de Dividendos</h3>
        <DividendsChart :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import AssetCard from "./AssetCard.vue";
import DividendsChart from "./DividendsChart.vue";
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from "chart.js";
import { Bar } from "vue-chartjs";

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

export default {
  props: {
    userId: {
      type: Number,
      required: true,
    },
  },
  components: {
    AssetCard,
    DividendsChart,
  },
  data() {
    return {
      assets: [],
      resume: [],
      loading: true,
      chartData: {
        labels: ['April', 'May', 'June'],
        datasets: [{ data: [25, 30, 15] }]
      },
      chartOptions: {
        responsive: true
      }
    };
  },
  mounted() {
    this.fetchUserAssets();
  },
  methods: {
    fetchUserAssets() {
      axios
        .get("/api/assets/current/" + this.userId)
        .then((response) => {
          const assetsData = response.data;
          this.resume = assetsData["resume"] || {};
          this.assets = Object.fromEntries(
            Object.entries(assetsData).filter(([key, asset]) => asset.quantity > 0)
          );
          delete this.assets["resume"];

          const historicDividends = assetsData["historic_dividends"];
          if (historicDividends && Object.keys(historicDividends).length) {
            const mountedLabels = [];
            const data = [];

            // Criar um array para armazenar os dividendos com os labels
            const dividendsArray = [];
            Object.keys(historicDividends).forEach((year) => {
              Object.keys(historicDividends[year]).forEach((month) => {
                const label = `${month}/${year}`;
                dividendsArray.push({
                  label,
                  total: historicDividends[year][month].total,
                  date: new Date(`${month}/01/${year}`) // Criar um objeto Date para ordenação
                });
              });
            });

            // Ordenar os dividendos pelo ano e mês (data)
            dividendsArray.sort((a, b) => a.date - b.date);

            // Separar os labels e os dados após a ordenação
            dividendsArray.forEach((item) => {
              mountedLabels.push(item.label);
              data.push(item.total);
            });

            this.chartData = {
              labels: mountedLabels,
              datasets: [
                {
                  label: "Dividendos Totais",
                  backgroundColor: "#4bdb78",
                  data,
                },
              ],
            };
          } else {
            this.chartData = null; // Nenhum dado disponível
          }
          this.loading = false;
        })
        .catch((error) => {
          console.error("Erro ao buscar ativos:", error);
          this.loading = false;
        });
    },
  },
};
</script>

<style>
.card-custom {
  border-radius: 10px;
  border: none;
  max-width: calc(33.333% - 1rem);
}
</style>
