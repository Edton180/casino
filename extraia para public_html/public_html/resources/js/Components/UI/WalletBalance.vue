<template>
        <button  type="button" class="botam" style="margin-left: -60px;">

    <div class="flex items-center gap-2">
        <div class="flex items-center mx-auto relative pr-12 " > <!-- Movido mais para a direita -->
            <div id="select-currancy" class="flex items-center group relative">
                <div class="inline-flex items-center h-max relative w-full">
                    <div class="w-full">
                        <div class="flex items-center w-full min-w-auto justify-between cursor-pointer bg-gray-600/[.5] rounded-md xs:h-8 h-10 pl-2 pr-3"> <!-- Ajustado a altura -->
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-max h-6 px-2 rounded-md bg-gradient-to-t from-orange-500 to-orange-400 shadow-orange-icon"> <!-- Ajustado o tamanho do elemento BRL -->
                                    <span class="relative block text-shadow-icon-light font-black leading-none pt-1 pb-1 text-white text-xs">BRL</span> <!-- Ajustado o tamanho do texto BRL -->
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-white text-[.85rem] xs:text-[.70rem] font-extrabold ml-2.5 block leading-none"> <!-- Ajustado o tamanho do saldo -->
                                        <strong style="color: #ffffff">{{ state.currencyFormat(wallet?.total_balance, wallet?.currency) }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="absolute -right-6 flex items-center justify-center w-8 h-8 bg-gradient-to-t from-orange-500 to-orange-400 rounded-full"> <!-- Movido mais para a direita -->
                    <span class="pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" class="icon size-8 drop-shadow-3xl" width="1em" height="1em" viewBox="0 0 20 20">
                            <path fill="white" d="M10.75 6.75a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5z"></path>
                        </svg>
                    </span>
                </button>
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
.pr-12 {
  left: 38px !important; /* Ajustar para mover mais para a direita */
}

</style>
