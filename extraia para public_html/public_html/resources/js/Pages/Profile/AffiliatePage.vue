<template>
    <BaseLayout>
        <div class="w-full fundo flex flex-col items-center justify-start min-h-screen">
                    <div class="mt-10"> <!-- Adicionando margem superior para descer um pouco -->

<div v-if="wallet && !isLoading" class="max-w-md mx-auto bg-gray-100 dark:bg-gray-800 shadow-lg rounded-lg mb-3 p-4">
    <div class="flex flex-col space-y-4">
        <!-- Cabeçalho -->
        <h2 class="tituloaff">Indique um amigo e ganhe dinheiro</h2>
        <p class="text-white">Ganhe {{ state.currencyFormat(parseFloat(userData.affiliate_cpa), wallet.currency) }} de saldo real por cada amigo que você indicar.</p>
        <p class="text-gold">Para efetivar sua indicação, o primeiro depósito de seu amigo deve ser no mínimo de R${{ userData.affiliate_baseline }}</p>

        <!-- Divisor -->
        <hr class="my-4 border-t border-gray-300">

        <!-- Seu link -->
        <div class="flex items-center space-x-2">
            <span class="textcinza">Seu link:</span>
        </div>
        <div class="flex items-center space-x-2">
            <input type="text"
                   id="referenceLink"
                   class="block flex-1 p-4 text-sm text-gray-900 border-gray-300 bg-gray-50 rounded-lg focus:border-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:border-gray-500"
                   :placeholder="$t('Reference Link')"
                   v-model="referencelink"
                   required>

        </div>
        <div class="flex items-center space-x-2">

<button @click.prevent="copyLink" class="ui-button-custom3 flex items-center space-x-2">
    <svg height="0.9em" viewBox="0 0 512 512" width="0.9em" xmlns="http://www.w3.org/2000/svg">
        <path d="M512 96V336C512 362.508 490.51 384 464 384H272C245.49 384 224 362.508 224 336V48C224 21.492 245.49 0 272 0H416V96H512Z" fill="currentColor"></path>
        <path d="M192 352V128H48C21.49 128 0 149.492 0 176V464C0 490.508 21.49 512 48 512H240C266.51 512 288 490.508 288 464V416H256C220.652 416 192 387.344 192 352ZM416 0V96H512L416 0Z" fill="currentColor" opacity="0.4"></path>
    </svg>
    <span>Copiar Link</span>
</button>

            <button @click.prevent="shareContent"
                    class="ui-button-custom4">
                Compartilhar
            </button>
        </div>


                        <!-- <div class="mt-5 flex justify-end items-end">
                            <button @click="$router.push('/terms-conditions/reference')" type="button" class="text-gray-500 hover:text-gray-600 dark:text-gray-300 hover:dark:text-gray-400">
                                {{ $t('Terms and Conditions of Reference') }} <i class="fa-regular fa-arrow-right ml-2"></i>
                            </button>
                        </div> -->
                    </div>
                </div>
                <div v-else class="relative flex flex-col items-center justify-center text-center p-6">
                    <div v-if="!isLoadingGenerate" class="">
                        <p class="text-gray-400">
                            {{ $t('Generate the code Description') }}
                        </p>
                        <div class="mt-5 w-full">
                            <button @click.prevent="generateCode" type="button" class="ui-button-violet w-full">
                                {{ $t('Generate the code') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="isLoadingGenerate" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                        <span class="sr-only">{{ $t('Loading') }}...</span>
                    </div>
                </div>

            </div>
     
        </div>

        <!-- MODAL RECOMPENSAS DE REFERÊNCIA -->


        <!-- MODAL RECOMPENSAS POR COMISSÃO -->

        <!-- MODAL SAQUE -->

    </BaseLayout>
</template>


<script>

import BaseLayout from "@/Layouts/BaseLayout.vue";
import { Modal } from 'flowbite';
import HttpApi from "@/Services/HttpApi.js";
import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";
import {useRouter} from "vue-router";

export default {
    props: [],
    components: { BaseLayout, Modal },
    data() {
        return {
            showCPFInput: false,
            isLoading: false,
            referenceRewards: null,
            commissionRewards: null,
            isShowForm: false,
            isLoadingGenerate: false,
            code: '',
            urlCode: '',
            referencecode: '',
            referencelink: '',
            wallet: null,
            indications: 0,
            histories: null,
            withdrawalModal: null,
            withdrawalForm: {
                amount: 0,
                pix_key: '',
                pix_type: 'document',
            }
        }
    },
    setup(props) {
        const router = useRouter();
        return {
            router
        };
    },
    computed: {
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        }
    },
    mounted() {


    },
    methods: {
        
        shareContent: function() {
      if (navigator.share) {
        navigator.share({
          title: 'GUGABET A MELHOR!',
          text: 'Venha Retirar Suas Rodadas Grátis',
          url: this.urlCode  // Concatenação do urlCode ao URL

        }).then(() => {
          console.log('Conteúdo compartilhado com sucesso!');
        }).catch((error) => {
          console.error('Erro ao compartilhar conteúdo:', error.message);
        });
      } else {
        console.log('Web Share API não suportada.');
        // Aqui você pode implementar um fallback para compartilhamento personalizado
      }
    },
        
        copyCode: function(event) {
            const _toast = useToast();
            var inputElement = document.getElementById("referenceCode");
            inputElement.select();
            inputElement.setSelectionRange(0, 99999);  // Para dispositivos móveis

            // Copia o conteúdo para a área de transferência
            document.execCommand("copy");
            _toast.success(this.$t('Code copied successfully'));
        },
        copyLink: function(event) {
            const _toast = useToast();
            var inputElement = document.getElementById("referenceLink");
            inputElement.select();
            inputElement.setSelectionRange(0, 99999);  // Para dispositivos móveis

            // Copia o conteúdo para a área de transferência
            document.execCommand("copy");
            _toast.success(this.$t('Link copied successfully'));
        },
        getCode: function() {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingGenerate = true;

            HttpApi.get('profile/affiliates/')
                .then(response => {
                    if( response.data.code !== '' && response.data.code !== undefined && response.data.code !== null) {
                        _this.isShowForm = true;
                        _this.code          = response.data.code;
                        _this.referencecode = response.data.code;
                        _this.urlCode       = response.data.url;
            } else {
                // Caso o usuário não tenha um código, gerar um novo
                _this.generateCode(); // Chamada para a função generateCode() se não houver código existente
            }

                    _this.indications   = response.data.indications;
                    _this.referencelink = response.data.url;
                    _this.wallet        = response.data.wallet;
                    _this.withdrawalForm.amount = response.data.wallet.refer_rewards;

                    _this.isLoadingGenerate = false;
                })
                .catch(error => {
                    _this.isShowForm = false;
                    _this.isLoadingGenerate = false;
                });
        },
        generateCode: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingGenerate = true;

            HttpApi.get('profile/affiliates/generate')
                .then(response => {
                    if(response.data.status) {
                        _this.getCode();
                        _toast.success(_this.$t('Your code was generated successfully'));
                    }

                    _this.isLoadingGenerate = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingGenerate = false;
                });
        },
        toggleCommissionRewards: function(event) {
            this.commissionRewards.toggle();
        },
        toggleReferenceRewards: function(event) {
            this.referenceRewards.toggle();
        },
        opemModalWithdrawal: function() {
            this.withdrawalModal.toggle();
        },
        makeWithdrawal: async function() {
            const _this = this;
            const _toast = useToast();

            _this.isLoading = true;

            HttpApi.post('profile/affiliates/request', _this.withdrawalForm)
                .then(response => {
                    _this.opemModalWithdrawal();

                    _toast.success(_this.$t(response.data.message));
                    _this.isLoading = false;
                    _this.router.push({ name: 'profileWallet' });
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        }
    },
    created() {
      this.getCode();
    },
    watch: {

    },
};
</script>

<style scoped>
.fundo {
    background-color: #1d1d1d; /* Ajuste conforme necessário */
    background-size: cover;
    background-repeat: no-repeat;
    min-height: 65vh; /* Garante que cubra toda a altura da tela */
    margin-bottom: -50px;
}
.textcinza {
        color: #ccc; /* Cor do texto (preto) */

}
.text-gold {
    color: #ffd700; /* Ou a cor dourada que você preferir */
}
</style>
