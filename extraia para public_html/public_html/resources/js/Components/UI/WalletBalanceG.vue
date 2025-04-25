<template>
  <div class="flex justify-center items-center mr-2 pt-1">
    <button v-if="wallet?.total_balance === undefined || isLoadingWallet" disabled type="button" class="flex justify-center items-center wallet-money">
      <div class="mr-2">
        <i class="fa-solid fa-arrows-rotate refresh-icon" :class="{ 'rotate': isRefreshing }"></i>
      </div>
      <div>
        <strong class="text-sm">Buscando</strong>
      </div>
    </button>
    <button @click="refreshWallet" v-else type="button" class="flex justify-center items-center wallet-money">
      <div class="mr-2">
        <i class="fa-solid fa-arrows-rotate refresh-icon" :class="{ 'rotate': isRefreshing }"></i>
      </div>
      <div>
        <strong class="text-sm">{{ state.currencyFormat(wallet?.total_balance, wallet?.currency) }}</strong>
      </div>
    </button>
  </div>
</template>



<script>
import HttpApi from "@/Services/HttpApi.js";
import {onMounted, ref, watchEffect} from "vue";
import {useRoute} from "vue-router";

export default {
    data() {
        return {
            isLoadingWallet: true,
            wallet: null,
            processInterval: null,
            isRefreshing: false,

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
            refreshWallet() {
      this.isRefreshing = true;
      this.wallet = null,
      setTimeout(() => {
        // Aqui você pode chamar uma função para atualizar o saldo da wallet
      this.getWallet();
         this.isRefreshing = false;
      }, 1000); // Ajuste o tempo conforme necessário
    },
        async getWallet() {
            try {
                const response = await HttpApi.get('profile/wallet');
                this.wallet = response.data.wallet;
            } catch (error) {
                if (JSON.parse(error.request.responseText).unauthenticated) {
                    localStorage.clear();
                    clearInterval(this.processInterval);
                }
            } finally {
                this.isLoadingWallet = false;
            }
        },
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
button {
  padding: .15rem .5rem; /* Diminuindo o tamanho do botão */
      border-radius: 12px; /* Borda arredondada nas quinas */
 top:-10px;
}

.refresh-icon {
  font-size: .80rem; /* Tamanho do ícone */
  transition: transform 0.5s ease;
}

.refresh-icon.rotate {
  transform: rotate(-180deg);
}

.text-sm {
  font-size: .89rem; /* Tamanho menor do texto */
}

.wallet-money strong {
  font-size: .80rem; /* Diminuir o tamanho do texto do saldo */
}
</style>
