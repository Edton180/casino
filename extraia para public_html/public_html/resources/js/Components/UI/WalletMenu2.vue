<template>
  <button  type="button" class="botam mx-auto">
    <div class="flex items-center gap-2">
      <div class="flex items-center mx-auto relative">
        <!-- Movido mais para a direita -->
        <div id="select-currancy" class="flex items-center group relative">
          <div class="inline-flex items-center h-max relative w-full">
            <div class="w-full">
              <div class="currency-container flex items-center w-full min-w-auto justify-between cursor-pointer xs:h-8 h-10 pl-2 pr-3">
                <!-- Ajustado a altura -->
                <div class="flex items-center">
                  <div class="currency-element flex items-center justify-center w-max h-6 px-2 rounded-md shadow-orange-icon">
                    <!-- Ajustado o tamanho do elemento BRL -->
                    <span class="currency-text relative block text-shadow-icon-light font-black leading-none pt-1 pb-1 text-black text-xs">BRL</span>
                    <!-- Ajustado o tamanho do texto BRL -->
                  </div>
                  <div class="flex flex-col">
                    <span class="balance-text text-white text-[1rem] xs:text-[.85rem] font-extrabold ml-2.5 block leading-none">
                      <!-- Ajustado o tamanho do saldo -->
                      <strong class="text-gold">{{ state.currencyFormat(wallet?.balance_bonus, wallet?.currency) }}</strong>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </button>
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

.text-gold {
    color: gold;
}
</style>
