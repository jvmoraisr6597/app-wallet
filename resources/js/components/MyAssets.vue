<template>
  <div class="container mt-4">
    <table class="table table-bordered table-hover table-responsive">
      <thead class="table-dark">
        <tr>
          <th scope="col">Código</th>
          <th scope="col">Nome</th>
          <th scope="col">Tipo</th>
          <th scope="col">Compra/Venda</th>
          <th scope="col">Preço Medio</th>
          <th scope="col">Quantidade</th>
          <th scope="col">Data da Operação</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, key) in assets" :key="key">
          <td>{{ item.code }}</td>
          <td>{{ item.name }}</td>
          <td>{{ item.asset_type }}</td>
          <td>{{ item.order_type }}</td>
          <td>{{ formatCurrency(item.original_price) }}</td>
          <td>{{ item.quantity }}</td>
          <td>{{ formatDate(item.order_date) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import axios from 'axios';

export default {
  props: {
    userId: {
      type: Number,
      required: true
    }
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
      axios.get('/api/assets/user/' + this.userId) // Endpoint da API para obter ativos do usuário
        .then(response => {
          this.assets = response.data;
          console.log(response.data);
          this.loading = false;
        })
        .catch(error => {
          console.error('Erro ao buscar ativos:', error);
          this.loading = false;
        });
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
      }).format(value);
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("pt-BR");
    },
  }
};
</script>

<style>
  .card-custom {
      border-radius: 10px;
      border: none;
      max-width: calc(33.333% - 1rem);
  }
</style>
