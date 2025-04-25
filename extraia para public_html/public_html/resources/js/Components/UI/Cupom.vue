
<template>
  <div class="cashback-container rounded-lg p-4 mx-auto text-center mt-2">
    <p class="text-white text-xl font-bold items-center justify-center">Digite um código promocional!</p>
    <input type="text" v-model="cupomForm.promoCode" class="input-group p-2 rounded border text-center" placeholder="" />


    <button class="ui-button-cupom" @click.prevent="makeWithdrawal">RESGATAR!</button>
  </div>
</template>

<script>
import HttpApi from "@/Services/HttpApi.js";
import {onMounted, ref, watchEffect} from "vue";
import {useRoute} from "vue-router";
import {useToast} from "vue-toastification";

export default {
    data() {
        return {
            isLoadingWallet: true,
            wallet: null,
            processInterval: null,
            cupomForm: {
                   promoCode: ''
   
            }
        };
    },
    setup() {
        const route = useRoute();
        const isCasinoPlayPage = ref(false);

        watchEffect(() => {
            checkRoute();
        });

        onMounted(() => {
            checkRoute();
        });

        function checkRoute() {
            isCasinoPlayPage.value = route.name === 'casinoPlayPage';
        }

        return {
            isCasinoPlayPage
        };
    },
    beforeUnmount() {
        clearInterval(this.processInterval);
    },
    methods: {
    openModalDeposit: function() {
      setTimeout(() => {
        window.location.reload();
      }, 5000); // 5000 milissegundos = 5 segundos
    },
            
    makeWithdrawal: async function() {
            const _this = this;
            const _toast = useToast();

            _this.isLoading = true;

            HttpApi.post('profile/affiliates/cupom', _this.cupomForm)
                .then(response => {

                    _toast.success(_this.$t(response.data.message));
                    _this.isLoading = false;
                    _this.openModalDeposit();
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        }
    },
    async created() {
        if (this.isCasinoPlayPage) {
            this.processInterval = setInterval(this.getWallet, 5000);
        }
        await this.getWallet();
    },
};
</script>

<style scoped>
.input-group {
   background-color: #424344; /* Cor de fundo mais clara */
    color: #f2f2f2; /* Cor do texto mais escura */
    padding: 12px 16px; /* Aumenta o padding para deixar o input mais gordinho */
    padding-left: 40px; /* Adiciona padding à esquerda para os ícones */
    border-radius: 8px; /* Deixa as bordas mais arredondadas */
    font-size: 20px; /* Aumenta o tamanho da fonte */
    height: 40px; /* Define uma altura mínima para o input */
}
.cashback-container {
  background-color: transparent; /* Ajuste conforme necessário */
  border: 1px solid #dee2e6; /* Ajuste conforme necessário */
  max-width: 400px; /* Ajuste conforme necessário */
  margin: auto; /* Centraliza o container */
  text-align: center; /* Centraliza o texto */
}

.reivindicar-btn {
  background-color: #007bff; /* Ajuste conforme necessário */
  color: white; /* Ajuste conforme necessário */
  border: none;
  cursor: pointer;
}

.reivindicar-btn:hover {
  background-color: #0056b3; /* Ajuste conforme necessário */
}
</style>
