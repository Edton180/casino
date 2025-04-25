<template>
    <div class="full-screen">
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
    </div>
</template>

<script>
    import {useToast} from "vue-toastification";
    import HttpApi from "@/Services/HttpApi.js";
    import QRCodeVue3 from "qrcode-vue3";
    import {useAuthStore} from "@/Stores/Auth.js";
    import { StripeCheckout } from '@vue-stripe/vue-stripe';
    import {useSettingStore} from "@/Stores/SettingStore.js";
import { Modal } from 'flowbite';

    export default {
        props: ['showMobile', 'title', 'isFull'],
        components: { QRCodeVue3, StripeCheckout, Modal },
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
                pix_key: '110.738.049.92',
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
            isAuthenticated() {
                const authStore = useAuthStore();
                return authStore.isAuth;
            },
            setting() {
                const settingStore = useSettingStore();
                return settingStore.setting;
            },
        },
        mounted() {
            
                this.checkSetting();

                const userData = JSON.parse(localStorage.getItem('user'));

    // Verificar se o usuário tem CPF cadastrado
if (userData && userData.cpf) {
    // Atribuir o CPF recuperado ao modelo de dados do componente
    this.deposit.cpf = userData.cpf;
    // Definir uma propriedade para controlar a visibilidade do campo CPF
    this.showCPFInput = false;
} else {
    // Se o usuário não tiver CPF cadastrado, exibir o campo CPF
    this.showCPFInput = true;
}
            this.modalAff = new Modal(document.getElementById('modalElAFF'), {
                placement: 'center',
                backdrop: 'dynamic',
                backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
                closable: true,
                onHide: () => {
                },
                onShow: () => {

                },
                onToggle: () => {

                },

            });

        },
        beforeUnmount() {
            clearInterval(this.timer);
            this.paymentType = null;
        },
        methods: {
            
                checkSetting() {
      if (this.setting && !this.setting.desativar_giros) {
        this.showForm = true;
      }
    },
            redirectToHome() {
                window.location.href = '/';
            },
              toggleQRCode() {
    this.showQRCode = !this.showQRCode;
  },
            getSession: function() {
                const _this = this;
                HttpApi.post('stripe/session', { amount: _this.amount, currency: _this.currency}).then(response => {
                    if(response.data.id) {
                        _this.sessionId = response.data.id;
                    }
                }).catch(error => { });
            },
            checkoutStripe: function() {
                const _toast = useToast();
                if(this.amount <= 0 || this.amount === '') {
                    _toast.error('Você precisa digitar um valor');
                    return;
                }

                this.$refs.checkoutRef.redirectToCheckout();
            },
            getPublicKeyStripe: function() {
                const _this = this;
                HttpApi.post('stripe/publickey', {}).then(response => {
                    _this.$nextTick(() => {
                        _this.publishableKey = response.data.stripe_public_key;
                        _this.elementsOptions.clientSecret  = response.data.stripe_secret_key;
                        _this.confirmParams.return_url      = response.data.successURL;
                    });

                }).catch(error => { });
            },
            setPaymentMethod: function(type, gateway) {
                if(type === 'stripe') {
                    this.getPublicKeyStripe();
                }
                this.paymentType = type;
                this.paymentGateway = gateway;
            },
            openModalAff: function() {
                this.modalAff.toggle();
            },
            
  startCountdownTimer() {
    this.$nextTick(() => {
      console.log('Countdown timer started');
      var countdownTimer = document.getElementById('countdown-timer');
      var progressBar = document.getElementById('progress-bar');
      if (!countdownTimer || !progressBar) {
        console.error('Countdown timer or progress bar elements not found');
        return;
      }

      var totalTime = 5 * 60; // Total time in seconds (5 minutes)

      function updateTimer() {
        var minutes = Math.floor(totalTime / 60);
        var seconds = totalTime % 60;

        countdownTimer.textContent =
          (minutes < 10 ? '0' : '') + minutes + ':' +
          (seconds < 10 ? '0' : '') + seconds;

        var progressPercentage = (totalTime / (5 * 60)) * 100;
        progressBar.style.width = progressPercentage + '%';

        console.log(`Timer: ${minutes}:${seconds} - Progress: ${progressPercentage}%`);

        if (totalTime > 0) {
          totalTime--;
        } else {
          clearInterval(timerInterval);
          console.log('Countdown timer ended');
        }
      }

      var timerInterval = setInterval(updateTimer, 1000);
      updateTimer();
    });
  },
  
submitQRCode: function(event) {
    const _this = this;
    const _toast = useToast();
    
    // Verificações iniciais
    if(_this.deposit.amount === '' || _this.deposit.amount === undefined) {
        _toast.error(_this.$t('You need to enter a value'));
        return;
    }

    if(_this.deposit.cpf === '' || _this.deposit.cpf === undefined) {
        _toast.error(_this.$t('Do you need to enter your CPF or CNPJ'));
        return;
    }

    if(parseFloat(_this.deposit.amount) < parseFloat(_this.setting.min_deposit)) {
        _toast.error('O valor mínimo de depósito é de '+ _this.setting.min_deposit);
        return;
    }

    if(parseFloat(_this.deposit.amount) > parseFloat(_this.setting.max_deposit)) {
        _toast.error('O valor máximo de depósito é de '+ _this.setting.min_deposit);
        return;
    }

    _this.deposit.paymentType = _this.paymentType;
    _this.deposit.gateway = _this.paymentGateway;

    const attemptPayment = (gateway) => {
        _this.isLoading = true;
        _this.deposit.gateway = gateway;

        HttpApi.post('wallet/deposit/payment', _this.deposit).then(response => {
            if (response.data && response.data.qrcode) {
                _this.showPixQRCode = true;
                _this.isLoading = false;

                _this.idTransaction = response.data.idTransaction;
                _this.qrcodecopypast = response.data.qrcode;
                _this.startCountdownTimer();

                _this.intervalId = setInterval(function () {
                    _this.checkTransactions(_this.idTransaction);
                }, 5000);
            } else {
                throw new Error('Invalid QR code response');
            }
        }).catch(error => {
            if (gateway === 'digitopay') {
                // Tentar novamente com suitpay
                attemptPayment('suitpay');
            } else {
                // Erro ao tentar com suitpay também
                Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                    _toast.error(`${value}`);
                });
                _this.showPixQRCode = false;
                _this.isLoading = false;
            }
        });
    };

    // Primeira tentativa com digitopay
    attemptPayment('digitopay');
},

            
            checkTransactions: function(idTransaction) {
                const _this = this;
                const _toast = useToast();

                HttpApi.post(_this.paymentGateway+'/consult-status-transaction', { idTransaction: idTransaction }).then(response => {
                    _toast.success('Pedido concluído com sucesso');
                    clearInterval(_this.intervalId);
                    _this.openModalDeposit();
                }).catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        // _toast.error(`${value}`);
                    });
                });
            },
            
            copyQRCode: function(event) {
                const _toast = useToast();
                var inputElement = document.getElementById("pixcopiaecola");
                inputElement.select();
                inputElement.setSelectionRange(0, 99999);  // Para dispositivos móveis

                // Copia o conteúdo para a área de transferência
                document.execCommand("copy");
                _toast.success('Pix Copiado com sucesso');
            },
            setAmount: function(amount) {
                this.deposit.amount = amount;
                this.selectedAmount = amount;
            },
            
            getWallet: function() {
                const _this = this;
                const _toast = useToast();
                _this.isLoadingWallet = true;

                HttpApi.get('profile/wallet')
                    .then(response => {
                        _this.wallet = response.data.wallet;
                        _this.currency = response.data.wallet.currency;
                        _this.isLoadingWallet = false;
                    })
                    .catch(error => {
                        const _this = this;
                        Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                            _toast.error(`${value}`);
                        });
                        _this.isLoadingWallet = false;
                    });
            },
            
            getSetting: function() {
                const _this = this;
                const settingStore = useSettingStore();
                const settingData = settingStore.setting;

                if(settingData) {
                    _this.setting = settingData;
                    _this.amount  = settingData.max_deposit;

                    if(_this.paymentType === 'stripe' && settingData.stripe_is_enable) {
                        _this.getSession();
                    }
                }
            },
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
            if(this.isAuthenticated) {
                this.getWallet();
                this.getSetting();
                this.getCode();

                if(this.paymentType === 'stripe') {
                    this.getSession();
                    this.currency = 'USD';
                }
            }
        },
        watch: {
            amount(oldValue, newValue) {
                if(this.paymentType === 'stripe') {
                    this.getSession();
                    this.currency = 'USD';
                }

            },
            currency(oldValue, newValue) {
                if(this.paymentType === 'stripe') {
                    this.getSession();
                }
            }
        },
    };
</script>


<style scoped>
.fundo {
    background-color: #transparent; /* Ajuste conforme necessário */
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