<template>
    <div class="container mt-5">
        <h1 class="mb-4">Add Asset</h1>
        <form @submit.prevent="submitForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="code" class="form-label">Code:</label>
                    <input type="text" v-model="form.code" id="code" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" v-model="form.name" id="name" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="asset_type" class="form-label">Asset Type:</label>
                    <select v-model="form.asset_type" id="asset_type" class="form-select" required>
                        <option value="fii">FII</option>
                        <option value="action">Action</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="order_type" class="form-label">Order Type:</label>
                    <select v-model="form.order_type" id="order_type" class="form-select" required>
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="original_price" class="form-label">Original Price:</label>
                    <input type="number" v-model="form.original_price" id="original_price" class="form-control" step=".1" pattern="^\d*(\.\d{0,2})?$">
                </div>
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Quantity:</label>
                    <input type="number" v-model="form.quantity" id="quantity" class="form-control" step="1" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="order_date" class="form-label">Order Date:</label>
                    <input type="date" v-model="form.order_date" id="order_date" class="form-control" required>
                </div>
            </div>
            <input type="hidden" v-model="form.user_id">
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            form: {
                code: '',
                name: '',
                asset_type: 'fii', // Valor padrão
                order_type: 'buy', // Valor padrão
                original_price: '',
                quantity: '',
                order_date: '', // Novo campo de data
                user_id: this.userId // Obtém o ID do usuário da variável global
            }
        };
    },
    props: {
        userId: {
            type: Number,
            required: true
        }
    },
    methods: {
        async submitForm() {
            try {
                // Envia os dados do formulário via Axios
                const response = await axios.post('/assets', this.form);
                // Exibe uma mensagem de sucesso ou faz outra ação com a resposta
                console.log('Form Submitted Successfully:', response.data);
                // Você pode adicionar um redirecionamento ou limpar o formulário aqui
                window.location.href="/home";
            } catch (error) {
                // Exibe um erro se a requisição falhar
                console.error('Form Submission Error:', error);
                // Exibe uma mensagem de erro para o usuário
            }
        }
    }
};
</script>

<style scoped>
/* Adicione estilos específicos para o componente aqui */
</style>
