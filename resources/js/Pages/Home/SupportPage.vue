<template>
    <BaseLayout>
 
    <div class="support-container">
      <p class="support-text">Se precisar de suporte, entre em contato com a equipe da GugaBET clicando no link abaixo:</p>
      <div class="support-link-container">
        <div class="support-link" @click="redirectToSupport">Contatar Equipe GugaBET</div>
      </div>
    </div>


    </BaseLayout>
           
</template>


<script>
import BaseLayout from "@/Layouts/BaseLayout.vue";
export default {
  components: { BaseLayout },
  methods: {
    redirectToSupport() {
      // Redirecionar para a página de suporte
      window.location.href = "https://tawk.to/gugabet";
    },
  },
};
</script>

<style scoped>
.support-container {
  padding: 20px;
  border-radius: 10px;
  background-color: #000;
  text-align: center;
}

.support-text {
  color: #fff;
  margin-bottom: 20px;
}

.support-link-container {
  display: flex;
  justify-content: center;
}

.support-link {
  cursor: pointer;
  padding: 8px 16px;
  border-radius: 20px;
  background-color: #007bff;
  color: white;
  text-decoration: none;
}

.support-link:hover {
  background-color: #0056b3;
}
</style>

