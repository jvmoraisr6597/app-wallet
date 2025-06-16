<template>
  <div class="container mt-4">
    <div v-if="loading" class="text-center mt-4">
      <p>Carregando...</p>
    </div>
    <div v-else>
      <div class="row" style="display: flex;gap: 20px; /* espaço entre as colunas */">
        <div class="col-5 mr-2" style="flex:1;max-height:350px;background-color: #f8f9fa; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); font-size: 1.1rem; display: flex; flex-direction: column; gap: 12px;">
            <h3>Rentabilidade da Carteira</h3>
            <p class="mb-3 mb-md-0">Total Investido: R${{ resume['total_invest'] || 0 }}</p>
            <p class="mb-3 mb-md-0">Total de Dividendos: R${{ resume['total_dividend'] || 0 }}</p>
            <p class="mb-3 mb-md-0 ml-2">Lucro proveniente de vendas: R${{ (resume['gain_per_sale'] || 0).toFixed(2) }}</p>
            <p class="mb-3 mb-md-0">Lucro Corrente (Sem dividendos): R${{ resume['total_gain'] || 0 }}</p>
            <p class="mb-3 mb-md-0">
              Lucro Corrente (Com dividendos): R${{ ((resume['total_gain'] || 0) + (resume['total_dividend'] || 0)).toFixed(2) }}
            </p>
            <p class="mb-2 mb-md-0">
              Lucro Corrente (Com dividendos + vendas): R${{ ((resume['total_gain'] || 0) + (resume['total_dividend'] || 0) + (resume['gain_per_sale'] || 0)).toFixed(2) }}
            </p>
        </div>
        <div class="col-7" style="flex:1;max-height:350px;background-color: #f8f9fa;border-radius: 12px;padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); min-height: 350px; display: flex; flex-direction: column; justify-content: flex-start;">
          <h3>Balanceamento da Carteira</h3>
          <BalancedChart :data="balancedData" :options="chartOptions"/>
        </div>
      </div>
      <div class="row mt-3" style="background-color: #f8f9fa;border-radius: 12px;padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <div class="mb-2" style="display: flex; justify-content: space-between;">
          <h3>Ativos</h3>
          <a
            href="/add-asset"
            style="
              font-size: 35px;
              padding: 0;
              border: solid 1px white;
              border-radius: 50px;
              width: 40px;
              height: 40px;
              text-decoration: none;
              color: white;
              display: flex;
              align-items: center;
              background-color: #48e092;
              justify-content: center;
            "
            >+</a>
        </div>      
        <div v-for="(asset, index) in assets" :key="index" class="col-md-4 col-lg-3 mb-4">
          <div class="custom-card p-0">
            <AssetCard :asset="asset"></AssetCard>
          </div>
        </div>
      </div>
      <!-- Renderizar o gráfico apenas se chartData estiver definido -->
      <div class="row mt-3" style="background-color: #f8f9fa;border-radius: 12px;padding: 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
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
import BalancedChart from "./BalancedChart.vue";
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from "chart.js";
import { Bar } from "vue-chartjs";

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

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
    BalancedChart
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
      balancedData: {
        labels: ['Maçã', 'Banana', 'Laranja'],
        datasets: [
          {
            data: [30, 50, 20],
            backgroundColor: ['#f87979', '#a3d3f7', '#ffc107']
          }
        ]
      },
      chartOptions: {
        responsive: true,
        plugins: {
          legend: {
            position: 'right', // ⬅️ Aqui você escolhe onde vai a legenda: 'top', 'left', 'right', 'bottom'
            labels: {
              boxWidth: 20,
              padding: 15
            }
          }
        }
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
          const totalPerType = response.data.resume.total_per_type
          const totalValue = Object.values(totalPerType).reduce((sum, val) => sum + val, 0)


          this.balancedData.labels = Object.entries(totalPerType).map(([key, value]) => {
            const percent = ((value / totalValue) * 100).toFixed(2)
            return `${key.toUpperCase()} - ${percent}%`
          })
          this.balancedData.datasets[0].data = Object.values(totalPerType)
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
