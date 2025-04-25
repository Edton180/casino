<template>
    <BaseLayout>
        <div v-if="setting != null" class="md:w-4/6 2xl:w-4/6 mx-auto mt-20">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative col-span-2">
                    <div v-if="!isLoadingWallet" class="wallet-container">
                        
                        <div class="wallet-header">
        <div class="header-left">
<h2 class="wallet-title">
                BRL
                <div data-v-7d6a64b8="" class="wallet-icon">
                    <svg data-v-7d6a64b8="" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                        <path d="M48 127.1L448 128C448.4 128 448.9 128 449.3 128C460.5 128.3 470.9 131.6 480 136.9V136.6C499.1 147.6 512 168.3 512 192V416C512 451.3 483.3 480 448 480H64C28.65 480 0 451.3 0 416V80C0 106.5 21.49 128 48 128L48 127.1zM416 336C433.7 336 448 321.7 448 304C448 286.3 433.7 272 416 272C398.3 272 384 286.3 384 304C384 321.7 398.3 336 416 336z" fill="currentColor"></path>
                        <path d="M0 80C0 53.49 21.49 32 48 32H432C458.5 32 480 53.49 480 80V136.6C470.6 131.1 459.7 128 448 128L48 128C21.49 128 0 106.5 0 80V80z" fill="currentColor" opacity="0.4"></path>
                    </svg>
                </div>
            </h2>
            <h1 class="wallet-balance">{{ state.currencyFormat(parseFloat(wallet.balance_withdrawal), wallet.currency) }}</h1>
        </div>
                        </div>

                        <div class="mt-5 flex gap-4">
                            <button @click.prevent="$router.push('/profile/deposit')" class="ui-button-custom6">Depositar</button>
                            <button @click.prevent="$router.push('/profile/withdraw')" class="ui-button-custom7">Sacar</button>
                        </div>
                    </div>
<div class="mt-5 flex flex-col items-center">
    <div class="bg-[#323637] p-5 rounded-lg w-full max-w-4xl">
        <h1 class="mb-3 text-2xl text-center text-white">Minhas Transações</h1>

        <!-- Botões para alternar entre saques e depósitos -->
        <div class="flex justify-center space-x-4 mb-4">
            <button @click="showWithdrawals" :class="{ ' text-white': activeTab === 'withdrawals', 'bg-gray-200 text-gray-700': activeTab !== 'withdrawals', 'p-2': true, 'rounded-lg': true, 'transition': true, 'hover:bg-blue-600': true }">
                Saques
            </button>
            <button @click="showDeposits" :class="{ ' text-white': activeTab === 'deposits', 'bg-gray-200 text-gray-700': activeTab !== 'deposits', 'p-2': true, 'rounded-lg': true, 'transition': true, 'hover:bg-blue-600': true }">
                Depósitos
            </button>
        </div>

        <!-- Aviso de nenhuma transação -->
        <div v-if="(activeTab === 'withdrawals' && (!withdraws || withdraws.data.length === 0)) || (activeTab === 'deposits' && (!deposits || deposits.data.length === 0))" class="text-gray-900 bg-orange-200 border border-orange-500 rounded-lg p-4">
            <p class="text-lg font-semibold">Atenção:</p>
            <p>Você ainda não efetuou nenhuma transação.</p>
        </div>

        <!-- Lista de histórico de saques -->
        <div v-if="activeTab === 'withdrawals' && withdraws && withdraws.data.length > 0" class="text-white bg-[#323637] border border-gray-600 rounded-lg w-full p-4">
            <h1 class="text-xl font-bold text-green-500 mb-3">{{ $t('Withdrawal List') }}</h1>

            <div v-if="withdraws && wallet" class="mt-3">
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-200 uppercase bg-gray-700">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-green-500">{{ $t('Value') }}</th>
                                <th scope="col" class="px-3 py-2">{{ $t('Status') }}</th>
                                <th scope="col" class="px-3 py-2">{{ $t('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(withdraw, index) in withdraws.data" :key="index" class="bg-[#3c3f42] border-b border-gray-700">

                                <td class="px-3 py-2 text-green-500">{{ state.currencyFormat(parseFloat(withdraw.amount), withdraw.currency) }}</td>
                                <td class="px-3 py-2">
                                    <span v-if="withdraw.status === 1" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">{{ $t('Concluded') }}</span>
                                    <span v-if="withdraw.status === 0" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">{{ $t('Pending') }}</span>
                                </td>
                                <td class="px-3 py-2">{{ withdraw.dateHumanReadable }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lista de histórico de depósitos -->
        <div v-if="activeTab === 'deposits' && deposits && deposits.data.length > 0" class="text-white bg-[#323637] border border-gray-600 rounded-lg w-full p-4 mt-5">
            <h1 class="text-xl font-bold text-green-500 mb-3">{{ $t('Deposits List') }}</h1>

            <div v-if="deposits && wallet" class="mt-3">
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-200 uppercase bg-gray-700">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-green-500">{{ $t('Value') }}</th>
                                <th scope="col" class="px-3 py-2">{{ $t('Status') }}</th>
                                <th scope="col" class="px-3 py-2">{{ $t('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(deposit, index) in deposits.data" :key="index" class="bg-[#3c3f42] border-b border-gray-700">

                                <td class="px-3 py-2 text-green-500">{{ state.currencyFormat(parseFloat(deposit.amount), deposit.currency) }}</td>
                                <td class="px-3 py-2">
                                    <span v-if="deposit.status === 1" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">{{ $t('Concluded') }}</span>
                                    <span v-if="deposit.status === 0" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">{{ $t('Pending') }}</span>
                                </td>
                                <td class="px-3 py-2">{{ deposit.dateHumanReadable }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


                    <div v-if="isLoadingWallet" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="sr-only">{{ $t('Loading') }}...</span>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>


<script>

import { RouterLink } from "vue-router";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import WalletSideMenu from "@/Pages/Profile/Components/WalletSideMenu.vue";
import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";
import HttpApi from "@/Services/HttpApi.js";
import {useSettingStore} from "@/Stores/SettingStore.js";

export default {
    props: [],
    components: {WalletSideMenu, BaseLayout, RouterLink },
    data() {
        return {
            isLoading: false,
            isLoadingWallet: true,
            wallet: null,
            mywallets: null,
            setting: null,
            withdraws: null,
            deposits: null,
            activeTab: 'withdrawals' // Adicionando a variável activeTab e definindo o valor padrão

        }
    },
    setup(props) {


        return {};
    },
    computed: {

    },
    mounted() {

    },
    methods: {
        setWallet: function(id) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingWallet = true;

            HttpApi.post('profile/mywallet/'+ id, {})
                .then(response => {
                   _this.getMyWallet();
                    _this.isLoadingWallet = false;
                    window.location.href = 'https://gugabet.com/profile/transactions';

                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingWallet = false;
                });
        },
        getWallet: function() {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingWallet = true;

            HttpApi.get('profile/wallet')
                .then(response => {
                    _this.wallet = response.data.wallet;
                    _this.isLoadingWallet = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingWallet = false;
                });
        },
        getMyWallet: function() {
            const _this = this;
            const _toast = useToast();

            HttpApi.get('profile/mywallet')
                .then(response => {
                    _this.mywallets = response.data.wallets;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                });
        },
        getSetting: function() {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;

            if(settingData) {
                _this.setting = settingData;
            }

            _this.isLoading = false;
        },
        rolloverPercentage(balance) {
            return Math.max(0, ((balance / 100) * 100).toFixed(2));
        },
                getWithdraws: function() {
            const _this = this;
            _this.isLoading = true;

            HttpApi.get('wallet/withdraw')
                .then(response => {
                    _this.withdraws = response.data.withdraws;
                    _this.isLoading = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
        getDeposits: function() {
            const _this = this;
            _this.isLoading = true;

            HttpApi.get('wallet/deposit')
                .then(response => {
                    _this.deposits = response.data.deposits;
                    _this.isLoading = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
                showWithdrawals() {
            this.activeTab = 'withdrawals'; // Define o tab ativo como 'saques'
        },
        showDeposits() {
            this.activeTab = 'deposits'; // Define o tab ativo como 'depósitos'
        }
    },
    created() {
        this.getWallet();
        this.getMyWallet();
        this.getSetting();
        this.getWithdraws();
        this.getDeposits();
    },
    watch: {

    },
};
</script>

<style scoped>
.wallet-container {
  background-color: #323637; /* Cinza escuro */
  padding: 1rem;
  border-radius: 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.wallet-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.header-left {
  display: flex;
  flex-direction: column;
}

.wallet-title {
  display: flex;
  align-items: center;
  font-size: 1.5rem;
  color: #ffffff; /* Branco */
}

.wallet-icon {
  width: 1.5rem; /* Ajuste o tamanho conforme necessário */
  height: 1.5rem; /* Ajuste o tamanho conforme necessário */
  fill: #ffffff; /* Cor do ícone */
  margin-left: 0.5rem;
}

.wallet-balance {
  font-size: 1.5rem;
    color: #ffd700; /* Ou a cor dourada que você preferir */
  font-weight: bold;
  margin-top: 0.5rem; /* Adiciona espaço entre o título e o saldo */
}

.btn-container {
  display: flex;
  gap: 0.5rem; /* Espaço entre os botões */
  margin-top: 0.5rem; /* Adiciona espaço acima dos botões */
}

.btn-deposit {
  background-color: #01ec01; /* Verde vivo */
  color: black;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-deposit:hover {
  background-color: #2eb82e; /* Verde mais escuro no hover */
}

.btn-withdraw {
  background-color: white;
  color: black;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  border: 2px solid black;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.3s, color 0.3s;
}

.btn-withdraw:hover {
  background-color: #f0f0f0; /* Cinza claro no hover */
}

.tab-button {
  padding: 0.5rem 1rem;
  border: 2px solid #4caf50; /* Borda verde */
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.3s, color 0.3s;
}

.tab-button.active-tab {
  background-color: #4caf50; /* Fundo verde no tab ativo */
  color: white; /* Texto branco no tab ativo */
}

.tab-button:not(.active-tab) {
  background-color: white; /* Fundo branco no tab inativo */
  color: #4caf50; /* Texto verde no tab inativo */
}

.alert {
  width: 100%;
  text-align: center;
  background-color: #ffcc00; /* Amarelo */
  color: #333;
  padding: 1rem;
  border-radius: 0.5rem;
}

.history-list {
  width: 100%;
  background-color: white;
  border: 2px solid #ccc;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.history-list .header {
  margin-bottom: 1rem;
}

.header h1 {
  color: #01ec01;
}

.header p {
  color: #01ec01;
}

button {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #2563eb; /* Azul mais escuro para hover */
}

.bg-green-500 {
  background-color: #01ec01; /* Verde customizado */
}

.bg-green-600 {
  background-color: #019e01; /* Verde customizado mais escuro */
}
</style>
