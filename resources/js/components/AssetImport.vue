<template>
  <div>
    <h2>Importar CSV de Ativos</h2>
    
    <input type="file" @change="handleFileUpload" />
    <button @click="submitForm">Importar</button>

    <p v-if="message" :style="{ color: messageColor }">{{ message }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      file: null,
      message: '',
      messageColor: 'green'
    };
  },
  methods: {
    handleFileUpload(event) {
      this.file = event.target.files[0];
    },
    async submitForm() {
      if (!this.file) {
        this.message = 'Por favor, selecione um arquivo.';
        this.messageColor = 'red';
        return;
      }

      const formData = new FormData();
      formData.append('csv_file', this.file);

      try {
        const response = await axios.post('/import-assets', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        this.message = response.data.message || 'Importado com sucesso!';
        this.messageColor = 'green';
      } catch (error) {
        this.message = 'Erro ao importar o arquivo.';
        this.messageColor = 'red';
      }
    }
  }
};
</script>
