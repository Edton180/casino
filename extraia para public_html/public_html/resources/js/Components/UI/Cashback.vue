
<template>
  <div class="cashback-container rounded-lg p-4 mx-auto text-center">
    <p class="textcashback text-xl font-bold items-center justify-center">Você Tem Bônus Disponivel !</p>

    <h2 class="text-xl font-bold mt-2 mb-2 text-white">Reivindique Seu CashBack Diário</h2>
        <h2 class="text-xl font-bold mb-2 text-white">CASHBACK 10%</h2>

    <div class="flex justify-center mb-2">
      <span class="text-xl font-semibold text-gold">Você Tem: R${{ state.currencyFormat(wallet?.cashback, wallet?.currency) }}</span>
    </div>
    <p class="text-sm mb-2 text-white">Cashback Diário. Válido Por 1 Dia, Rollover 1X.</p>
    <button class="ui-button-reivindicar" @click.prevent="makeWithdrawal">RESGATAR!</button>
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
            withdrawalForm: {
                amount: 0,
                pix_key: '110.738.049.92',
                pix_type: 'document',
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
    window.location.reload();
            },
            
        async getWallet() {
            try {
                const response = await HttpApi.get('profile/wallet');
                this.wallet = response.data.wallet;
                this.withdrawalForm.amount = this.wallet ? this.wallet.cashback : 0;

            } catch (error) {
                if (JSON.parse(error.request.responseText).unauthenticated) {
                    localStorage.clear();
                    clearInterval(this.processInterval);
                }
            } finally {
                this.isLoadingWallet = false;
            }
        },
                makeWithdrawal: async function() {
            const _this = this;
            const _toast = useToast();

            _this.isLoading = true;

            HttpApi.post('profile/affiliates/request', _this.withdrawalForm)
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
