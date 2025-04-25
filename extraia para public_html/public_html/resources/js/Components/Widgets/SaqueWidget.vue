<template>
  <div class="full-screen">
    <div class="col-span-2 relative translucent-menu">
      <div v-if="setting != null && wallet != null && isLoading === false" class="flex flex-col w-full translucent-menu ">

        <form v-if="!showModal" action="" @submit.prevent="submitWithdraw" class="translucent-menu">
          <div class="mt-5">
            <p class="request-withdrawal-text">Solicitar Saque</p>
          </div>
          <div class="dark:text-gray-400 mb-3" v-if="showCPF">
            <label for="" style="color: #ffffff">Sua Chave PIX: {{ cpf }}</label>
          </div>
          <p class="small-orange-text">Você não pode realizar saques para terceiros. Saque somente é válido para contas de sua titularidade.</p>

          <div :class="['w-full flex flex-col items-start justify-between rounded input-container', {'dark': isDarkMode}]">
            <div class="dark:text-gray-400 mb-3" v-if="showCPFInput">
              <label for="" style="color: #01ec01">Chave Pix: </label>
              <input v-model="withdraw.pix_key" type="text" class="input" placeholder="Digite a sua Chave pix CPF" required>
            </div>

            <div class="dark:text-gray-400 mb-3 w-full">
              <div class="flex justify-between mb-3">
                <p style="color: #01ec01">Valor do saque:</p>
              </div>
              <div class="flex translucent-menu w-full items-center rounded input-container">
                <input type="text" class="input w-full" v-model="withdraw.amount" :min="setting.min_withdrawal" :max="setting.max_withdrawal" placeholder="" required>
                <div class="flag-container">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 512 512">
                    <mask id="a">
                      <circle cx="256" cy="256" r="256" fill="#fff"></circle>
                    </mask>
                    <g mask="url(#a)">
                      <path fill="#6da544" d="M0 0h512v512H0z"></path>
                      <path fill="#ffda44" d="M256 100.2 467.5 256 256 411.8 44.5 256z"></path>
                      <path fill="#eee" d="M174.2 221a87 87 0 0 0-7.2 36.3l162 49.8a88.5 88.5 0 0 0 14.4-34c-40.6-65.3-119.7-80.3-169.1-52z"></path>
                      <path fill="#0052b4" d="M255.7 167a89 89 0 0 0-41.9 10.6 89 89 0 0 0-39.6 43.4 181.7 181.7 0 0 1 169.1 52.2 89 89 0 0 0-9-59.4 89 89 0 0 0-78.6-46.8zM212 250.5a149 149 0 0 0-45 6.8 89 89 0 0 0 10.5 40.9 89 89 0 0 0 120.6 36.2 89 89 0 0 0 30.7-27.3A151 151 0 0 0 212 250.5z"></path>
                    </g>
                  </svg>
                  <span>BRL</span>
                </div>
              </div>
            </div>

            <div class="dark:text-gray-400 mb-3 mt-1 w-full">
              <p style="color: #ffffff">Disponível para saque: <span style="color: #01ec01">{{ state.currencyFormat(parseFloat(wallet.balance_withdrawal), wallet.currency) }}</span></p>
            </div>
          </div>

          <div class="dark:text-gray-400 mb-3 mt-1">
            <label for="" style="color: #FF0000; margin-bottom: 0.3rem;">AO SACAR SEUS GIROS GRÁTIS SÃO PERDIDOS!</label>
          </div>

          <div class="mt-5 w-full flex items-center justify-center">
            <button type="submit" class="ui-button-custom5 w-full">
              <span class="uppercase font-semibold text-sm">{{ $t('Request withdrawal') }}</span>
            </button>
          </div>
        </form>
        <button class="yellow-button" v-if="!showModal" @click="toggleFormAndShowModal">Dúvidas? Leia as regras de Saque</button>
      </div>

      <div v-if="showModal" class="modal-overlay translucent-menu">
        <div class="mt-5">
          <h2 class="styled-text">Saque Mínimo <span class="highlighted-variable">R$: {{ setting.min_withdrawal }}</span></h2>
          <h2 class="styled-text">Saque Máximo <span class="highlighted-variable">R$: {{ setting.max_withdrawal }} /Saque</span> </h2>
          <h2 class="styled-text">Quantidade de Saques <span class="highlighted-variable">1 a cada 23 Horas</span> </h2>
          <h2 class="styled-text">O usuário tem a opção de realizar 1 saque a cada 23 horas, desde que respeite um limite máximo de saque diário de R${{ setting.max_withdrawal }}.</h2>
          <h2 class="styled-text">Os saques PIX são processados preferencialmente de forma imediata. Com limite máximo de 24 horas para pagamento!</h2>
          <h2 class="styled-text">Ao efetuar o saque todos seus Bônus/Giros-Grátis são automaticamente cancelados!</h2>
          <button @click="closeModal" class="yellow-button">Fechar</button>
        </div>
      </div>

      <div v-if="isLoading" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
          <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
        </svg>
        <span class="sr-only">{{ $t('Loading') }}...</span>
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
import {RouterLink, useRouter} from "vue-router";
    export default {
        props: ['showMobile', 'title', 'isFull'],
        components: { QRCodeVue3, StripeCheckout, RouterLink },
        data() {

            return {
                    
                showForm: true,

            isLoading: false,
            setting: null,
            wallet: null,
            showModal: false,
            withdraw: {
                name: '',
                pix_key: '',
                pix_type: 'document',
                amount: '',
                type: 'pix',
                currency: '',
                symbol: '',
                accept_terms: true
            },
            withdraw_deposit: {
                name: '',
                bank_info: '',
                amount: '',
                type: 'bank',
                currency: '',
                symbol: '',
                accept_terms: true
            },
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
            
const userData = JSON.parse(localStorage.getItem('user'));

// Verificar se os dados do usuário estão disponíveis e se o nome está presente
if (userData && userData.name) {
    // Atribuir o nome do usuário ao modelo de dados do componente
    this.withdraw.name = userData.name;
}

if (userData && userData.cpf) {
    // Atribuir o CPF recuperado ao modelo de dados do componente
    this.withdraw.pix_key = userData.cpf;

    // Definir uma propriedade para controlar a visibilidade do campo CPF
    this.showCPFInput = false;
    this.showCPF = true;

} else {
    this.showCPF = false;
    this.showCPFInput = true;
}

function formatarCPF(cpf) {
    // Remover caracteres não numéricos do CPF
    cpf = cpf.replace(/\D/g, '');
    
    // Aplicar a máscara do CPF
    cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    
    return cpf;
}

// Exemplo de uso
this.cpf = formatarCPF(userData.cpf);

            this.modalSaque = new Modal(document.getElementById('modalElSaque'), {
                placement: 'center',
                backdrop: 'dynamic',
                backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
                closable: true,
                onHide: () => {
                    this.paymentType = null;
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
            openModalDeposit: function() {
                this.modalSaque.toggle();
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
    attemptPayment('bspay');
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
             toggleFormAndShowModal() {
          this.showForm = false;
          this.showModal = true;
        },
        closeModal() {
          this.showModal = false;
          this.showForm = true;
        },
        setMinAmount: function() {
            this.withdraw.amount = this.setting.min_withdrawal;
        },
        setMaxAmount: function() {
            this.withdraw.amount = this.setting.max_withdrawal;
        },
        setPercentAmount: function(percent) {
            this.withdraw.amount = ( percent / 100 ) * this.wallet.balance_withdrawal;
        },
        getWallet: function() {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingWallet = true;

            HttpApi.get('profile/wallet')
                .then(response => {
                    _this.wallet = response.data.wallet;

                    _this.withdraw.currency = response.data.wallet.currency;
                    _this.withdraw.symbol = response.data.wallet.symbol;

                    _this.withdraw_deposit.currency = response.data.wallet.currency;
                    _this.withdraw_deposit.symbol = response.data.wallet.symbol;

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
                _this.setting                   = settingData;
                _this.withdraw.amount           = settingData.min_withdrawal;
                _this.withdraw_deposit.amount   = settingData.min_withdrawal;
            }

            _this.isLoading                 = false;
        },
        submitWithdrawBank: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;

            HttpApi.post('wallet/withdraw/request', _this.withdraw_deposit).then(response => {
                _this.isLoading = false;
                _this.withdraw_deposit = {
                    name: '',
                    bank_info: '',
                    amount: '',
                    type: '',
                    accept_terms: true
                }

                _this.router.push({ name: 'home' });
                _toast.success(response.data.message);
            }).catch(error => {
                Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                    _toast.error(`${value}`);
                });
                _this.isLoading = false;
            });
        },
        submitWithdraw: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;

            HttpApi.post('wallet/withdraw/request', _this.withdraw).then(response => {
                _this.isLoading = false;
                _this.withdraw = {
                    name: '',
                    pix_key: '',
                    pix_type: 'document',
                    amount: '',
                    type: '',
                    accept_terms: true
                }

                _this.router.push({ name: 'home' });
                _toast.success(response.data.message);
            }).catch(error => {
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

.translucent-menu {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
    backdrop-filter: blur(10px); /* Efeito embassado */
    -webkit-backdrop-filter: blur(10px); /* Suporte para Safari */
    border-top: 1px solid rgba(0, 0, 0, 0.1); /* Borda superior sutil */
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); /* Sombra sutil */
}
.request-withdrawal-text {
  color: #01ec01;
  font-weight: bold; /* Aumenta a espessura do texto */
  margin-bottom: 0.5rem;
}

.item-selected {
  overflow-x: auto;
  white-space: nowrap;
  margin-top: 8px; /* Para aproximar os botões do topo */
}

.item-list {
  display: inline-flex;
  gap: 3px; /* Reduzir o espaçamento entre os itens */
}

.item {
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
}

.item.active {
  font-weight: bold;
}

.img-check {
  width: 20px;
  height: 20px;
  margin-left: 5px;
}

.ratio {
  margin-top: 5px;
}

.form-box {
  background-color: #323637; /* Cor de fundo da caixinha */
  border: 1px solid #ddd;    /* Borda */
  border-radius: 8px;        /* Bordas arredondadas */
  padding: 20px;             /* Espaçamento interno */
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra */
}

.amount-button {
  background-color: #01ec011a;
  border: none; /* Remover a linha verde */
  border-radius: .25rem;
  color: #01ec01;
  cursor: pointer;
  font-size: .875rem;
  font-weight: 700;
  line-height: 1.25rem;
  padding: .3rem .875rem;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: auto; /* Para garantir que a altura do botão seja ajustada ao conteúdo */
}

.amount-button:hover {
  background-color: #01ec0126; /* Efeito hover para o botão */
}

.image-container {
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px solid #ddd;    /* Contorno */
  border-radius: 8px;        /* Bordas arredondadas */
  padding: 10px;             /* Espaçamento interno */
  margin-top: 10px;          /* Espaçamento superior */
  background-color: #323637;    /* Cor de fundo do contêiner */
}

.image-container img {
  max-width: 80px;           /* Ajuste o tamanho da imagem */
  height: auto;
}

.input-container {
  padding: 0.25rem 0.5rem; /* Reduzir o padding vertical */
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: transparent; /* Cor de fundo do contêiner */
  border-radius: 0.25rem;
  border: 1px solid #ddd; /* Adicionar borda ao contêiner */

}

.input-container input {
  padding: 0.25rem; /* Reduzir o padding do input */
  color: #ffffff;
  background: transparent;
  border: none;
  outline: none;
  width: 100%;
}

.flag-container {
  display: flex;
  align-items: center;
  gap: 5px; /* Espaçamento entre a bandeira e o texto */
}

.flag-container img {
  width: 20px; /* Tamanho do SVG da bandeira */
  height: 20px;
  border-radius: 50%; /* Tornar a bandeira redonda */
}

.flag-container span {
  color: #ffffff; /* Cor do texto */
}

.dashed-container {
  border: 2px dashed #ffffff;
  padding: 1rem;
  position: relative;
  cursor: pointer;
  display: flex;
  flex-direction: column;
}

.hand-icon {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 2rem;
  height: 2rem;
  fill: #01ec01;
}

.dashed-container p,
.dashed-container input {
  margin: 0.5rem 0;
}

.centered-text {
  color: #01ec01;
  font-size: 2rem; /* Aumentar o tamanho do texto */
  font-weight: bold;
  text-align: center; /* Centralizar o texto */
}

.centered-text2 {
  color: #ffffff;
  font-size: 1.1rem; /* Aumentar o tamanho do texto */
  text-align: center; /* Centralizar o texto */
}
.small-orange-text {
  color: orange;
  font-size: 0.875rem; /* Tamanho pequeno */
  margin-top: 0.5rem; /* Espaçamento superior */
  margin-bottom: 0.5rem;

}
.yellow-button {
  background-color: rgba(255, 255, 0, 0.5); /* Amarelo meio opaco */
  color: #FFFF00; /* Texto em amarelo vivo */
  padding: 0.5rem 3rem; /* Ajuste a altura e largura do botão */
  font-size: 1rem;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.3s;
  display: inline-block;
  text-align: center;
  margin-top: 1rem; /* Adiciona espaço abaixo do botão */
}

.yellow-button:hover {
  background-color: rgba(255, 255, 0, 0.7); /* Torna o fundo um pouco mais opaco no hover */
}
.styled-text {
  color: #ffffff; /* Texto branco */
  background-color: rgba(0, 100, 0, 0.5); /* Fundo verde escuro meio transparente */
  padding: 0.5rem 1rem; /* Espaçamento interno */
  border-radius: 0.25rem; /* Bordas arredondadas */
  margin-bottom: 1rem; /* Margem inferior */
  display: inline-block; /* Garante que o fundo seja aplicado ao conteúdo */
}

.highlighted-variable {
  color: #90ee90; /* Verde claro */
}

</style>

