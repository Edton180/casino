<template>
    <div class="full-screen">
        <div v-if="(paymentType == null || paymentType === '') && wallet && setting">
            <div class="">
                <ul>
                    <li v-if="setting.asaas_is_enable" @click="setPaymentMethod('pix', 'asaas')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/pix.png`" alt="" width="100">
                            <p>ASAAS PIX</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                    <li v-if="setting.asaas_is_enable && setting.asaas_credit_card_enable" @click="setPaymentMethod('credit_card', 'asaas')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/credit-card.png`" alt="" width="100">
                            <p>ASAAS CARTÃO</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                    <li v-if="setting.mercadopago_is_enable" @click="setPaymentMethod('pix', 'mercadopago')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/pix.png`" alt="" width="100">
                            <p>MERCADO PAGO PIX</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                    <li v-if="setting.mercadopago_is_enable && setting.mercadopago_credit_card_enable" @click="setPaymentMethod('credit_card', 'mercadopago')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/credit-card.png`" alt="" width="100">
                            <p>MERCADO PAGO CARTÃO</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                    <li v-if="setting.paypal_is_enable" @click="setPaymentMethod('pix', 'paypal')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/pix.png`" alt="" width="100">
                            <p>PAYPAL PIX</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                    <li v-if="setting.paypal_is_enable && setting.paypal_credit_card_enable" @click="setPaymentMethod('credit_card', 'paypal')" class="bg-white dark:bg-gray-900 cursor-pointer flex justify-between hover:bg-green-700/20 px-4 py-3 mb-3">
                        <div class="flex items-center gap-4">
                            <img :src="`/assets/images/credit-card.png`" alt="" width="100">
                            <p>PAYPAL CARTÃO</p>
                        </div>
                        <div class="flex justify-center items-center text-gray-500 gap-4">
                            <i class="fa-solid fa-chevron-right ml-2"></i>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div v-if="paymentType === 'stripe' && publishableKey && setting && setting.stripe_is_enable" class="p-4">
            <stripe-checkout
                ref="checkoutRef"
                :pk="publishableKey"
                :sessionId="sessionId"
            />
            <div class="flex w-full mt-3 mb-3">
                <div class="w-36 mr-2">
                    <label for="currency" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $t('Currency') }}</label>
                    <select id="currency" v-model="currency" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="USD">$ {{ $t('Dollar') }}</option>
                        <option value="BRL">R$ {{ $t('Real') }}</option>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $t('Amount') }}</label>
                    <input type="number"
                           v-model="amount"
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                           :min="setting.min_deposit"
                           :max="setting.max_deposit"
                           :placeholder="$t('0,00')"
                           required
                    >
                </div>
            </div>

            <button :disabled="!sessionId" @click.prevent="checkoutStripe" class="ui-button-blue rounded w-full">{{ $t('Pay With Stripe') }}</button>
        </div>
        <div v-if="setting && paymentType === 'pix' && (setting.suitpay_is_enable || setting.mercadopago_is_enable || setting.digitopay_is_enable || setting.bspay_is_enable)">
            <div v-if="showPixQRCode && wallet" class="flex flex-col mt-5">


        <div>
          <p class="centered-text2 " style="color: #ffffff">Copie o código "copia e cola" abaixo para realizar o pagamento</p>
        </div>

     <div class="dashed-container" @click="copyQRCode">
        <div>
          <p class="centered-text" >{{ state.currencyFormat(parseFloat(deposit.amount), wallet.currency) }}</p>
        </div>
        <div class="mt-4">
          <input id="pixcopiaecola" type="text" class="input" v-model="qrcodecopypast" readonly>
        </div>
        <svg class="hand-icon" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512" width="1em">
          <path d="M397.9,286.8c-7.2,0-13.8,2.1-19.5,5.6c-3.3-17.4-18.5-30.6-36.8-30.6c-13.6,0-25.4,7.3-32,18.1 c-3.9-17.4-18.5-30.6-36.8-30.6c-9.7,0-18.4,3.8-25,9.7V143c0-17.3-14-31.3-30.6-31.3c-18,0-32,14-32,31.3v252.3l-43.8-58.4 c-6.1-8.2-15.5-12.5-25-12.5c-16.6,0-31.3,13.3-31.3,31.3c0,6.5,2,13.1,6.3,18.7l71.3,95.1c20,26.7,51.8,42.5,85,42.5h75.1 c62.1,0,112.6-50.5,112.6-112.6v-75.1C435.4,303.6,418.6,286.8,397.9,286.8z M272.8,424.4c0,6.9-5.6,12.5-12.5,12.5 c-6.9,0-12.5-5.6-12.5-12.5v-75.1c0-6.9,5.6-12.5,12.5-12.5c6.9,0,12.5,5.6,12.5,12.5V424.4z M322.8,424.4c0,6.9-5.6,12.5-12.5,12.5 s-12.5-5.6-12.5-12.5v-75.1c0-6.9,5.6-12.5,12.5-12.5s12.5,5.6,12.5,12.5V424.4z M372.9,424.4c0,6.9-5.6,12.5-12.5,12.5 c-6.9,0-12.5-5.6-12.5-12.5v-75.1c0-6.9,5.6-12.5,12.5-12.5s12.5,5.6,12.5,12.5V424.4z" fill="currentColor" opacity="0.4"></path>
          <path d="M260.3,336.9c-6.9,0-12.5,5.6-12.5,12.5v75.1c0,6.9,5.6,12.5,12.5,12.5c6.9,0,12.5-5.6,12.5-12.5v-75.1 C272.8,342.5,267.2,336.9,260.3,336.9z M310.3,336.9c-6.9,0-12.5,5.6-12.5,12.5v75.1c0,6.9,5.6,12.5,12.5,12.5s12.5-5.6,12.5-12.5 v-75.1C322.8,342.5,317.2,336.9,310.3,336.9z M360.4,336.9c-6.9,0-12.5,5.6-12.5,12.5v75.1c0,6.9,5.6,12.5,12.5,12.5 c6.9,0,12.5-5.6,12.5-12.5v-75.1C372.9,342.5,367.2,336.9,360.4,336.9z M341.8,42.1c-7.3-7.3-19.2-7.3-26.5,0l-37.5,37.5 c-7.3,7.3-7.3,19.2,0,26.5c4.3,3.7,9.2,5.5,13.9,5.5c4.8,0,9.6-1.8,13.3-5.5l37.5-37.5C349.8,61.3,349.8,49.5,341.8,42.1z M362.8,160.4c0-10.4-8.4-18.8-18.8-18.8H291c-10.4,0-18.8,8.4-18.8,18.8c0.5,5.7,2.6,10.4,6,13.7c3.4,3.4,8.1,5.5,13.3,5.5h53.1 C354.9,179.6,363.3,171.3,362.8,160.4z M167.2,160.4c0-10.4-8.4-18.8-18.8-18.8H95.3c-10.4,0-18.8,8.4-18.8,18.8 c0.5,5.7,2.6,10.4,6,13.7c3.4,3.4,8.1,5.5,13.3,5.5h53.1C159.2,179.6,167.6,171.3,167.2,160.4z M97,68.7l37.5,37.5 c3.7,3.7,8.5,5.5,13.3,5.5c4.8,0,9.6-1.8,13.9-5.5c7.3-7.3,7.3-19.2,0-26.5l-37.5-37.5c-7.3-7.3-19.2-7.3-26.5,0 C89.7,49.5,89.7,61.3,97,68.7z M200.7,18.3v53.1c0,5.2,2.1,9.9,5.5,13.3c3.4,3.4,8.1,5.5,13.7,6c10.4,0,18.8-8.4,18.8-18.8V18.8 c0-10.4-8.4-18.8-18.8-18.8C209.1-0.4,200.7,8,200.7,18.3z" fill="currentColor"></path>
        </svg>
                            <div class="mt-5 w-full flex items-center justify-center">
                        <button @click.prevent="copyQRCode" type="button" class="ui-button-blue w-full">
                            <span class="uppercase font-semibold text-sm">{{ $t('Copy code') }}</span>
                        </button>
                    </div>
      </div>

      
<div class="countdown-container">
  <p class="countdown-text">O tempo para você pagar acaba em:</p>
  <p class="countdown-timer" id="countdown-timer">05:00</p>
  <div class="progress-bar-container">
    <div class="progress-bar" id="progress-bar"></div>
  </div>
</div>

<div class="button-container">
  <button class="paid-button" @click="redirectToHome">Já Paguei</button>
</div>

<div class="text-container">
  <a @click="toggleQRCode" class="toggle-text">Exibir QR Code</a>
</div>

<div v-if="showQRCode" class="text-container">
  <QRCodeVue3 :value="qrcodecopypast"/>
</div>

                  </div>

            
            
            <div v-if="!showPixQRCode">
                <div v-if="setting != null && wallet != null && isLoading === false" class="flex flex-col w-full mt-4 ">

                    
                    <form action="" @submit.prevent="submitQRCode"  class="form-box">




                        <div class="mt-3">
                            <p class="mb-2 text-gray-500" style="color: #ffffff">Valor a ser depositado: </p>
                            
        <div :class="[' flex items-center justify-between rounded input-container', {'dark': isDarkMode}]">
          <input type="text" v-model="deposit.amount"
            :min="setting.min_deposit"
            :max="setting.max_deposit"
            :placeholder="$t('Enter the value here')"
            required>
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
            </svg>            <span>BRL</span>
          </div>


                        <div  v-if="setting.initial_bonus !== 1 && setting.initial_bonus !== '1'">
                            <label class="inline-flex items-center mb-5 cursor-pointer">
                                <input type="checkbox" v-model="deposit.accept_bonus" value="" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Aceitar Giros Grátis</span>
                            </label>
                        </div>
                            </div>
                        </div>


<div class="mt-2 item-selected">
  <div class="item-list">
    <div class="item" @click.prevent="setAmount(30)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 30}">
          {{ wallet.symbol }}30
        </div>
        <div class="bonus">+15 GIROS GRÁTIS</div>
      </div>
    </div>
    <div class="item" @click.prevent="setAmount(50)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 50}">
          {{ wallet.symbol }}50
        </div>
        <div class="bonus">+25 GIROS GRÁTIS</div>
      </div>
    </div>
    <div class="item" @click.prevent="setAmount(100)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 100}">
          {{ wallet.symbol }}100
        </div>
        <div class="bonus">+40 GIROS GRÁTIS</div>
      </div>
    </div>
    <div class="item" @click.prevent="setAmount(250)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 250}">
          {{ wallet.symbol }}250
        </div>
        <div class="bonus">+55 GIROS GRÁTIS</div>
      </div>
    </div>
    <div class="item" @click.prevent="setAmount(500)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 500}">
          {{ wallet.symbol }}500
        </div>
        <div class="bonus">+65 GIROS GRÁTIS</div>
      </div>
    </div>
    <div class="item" @click.prevent="setAmount(1000)">
      <div class="header">Primeiro depósito</div>
      <div class="content">
        <div :class="{'amount': true, 'selected': selectedAmount === 1000}">
          {{ wallet.symbol }}1000
        </div>
        <div class="bonus">+95 GIROS GRÁTIS</div>
      </div>
    </div>
  </div>
</div>


                        <div class="mt-5" v-if="showCPFInput">
                            <p class="text-gray-500" style="color: #01ec01">CPF/CNPJ</p>
                            <input type="text" 
                                   style="color: #01ec01"
                                   v-model="deposit.cpf"
                                   v-maska
                                   data-maska="[
                                    '###.###.###-##',
                                    '##.###.###/####-##'
                                   ]"
                                   class="mt-2 border-none text-gray-600 placeholder:text-gray-300 dark:text-gray-200 dark:placeholder:text-gray-500  w-full bg-white dark:bg-gray-900 font-sans transition-all duration-300 disabled:cursor-not-allowed disabled:opacity-75 px-2 text-sm leading-5 rounded py-3"
                                   placeholder="Digite o CPF"
                                   required>
                        </div>

                        <div class="mt-5 w-full flex items-center justify-center">
                            <button type="submit" class="ui-button-blue w-full">
                                <span class="uppercase font-semibold text-sm">Gerar PIX</span>
                            </button>
                        </div>
                        

                    </form>

                </div>


                <div v-if="isLoading" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-green-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    <span class="sr-only">{{ $t('Loading') }}...</span>
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

    export default {
        props: ['showMobile', 'title', 'isFull'],
        components: { QRCodeVue3, StripeCheckout },
        data() {

            return {
                    
                amounts: [30, 50, 100, 250, 500, 1000],
                showForm: true,

                isLoading: false,
                minutes: 15,
                seconds: 0,
                timer: null,
                setting: null,
                wallet: null,
                      wallet2: {
        symbol: 'R$'
      },
                deposit: {
                    amount: '0',
                    cpf: '',
                    gateway: '',
                    accept_bonus: true
                },
                selectedAmount: 0,
                showPixQRCode: false,
                qrcodecopypast: '',
                idTransaction: '',
                intervalId: null,
                paymentType: null,

                /// stripe
                elementsOptions: {
                    appearance: {}, // appearance options
                },
                confirmParams: {
                    return_url: null, // success url
                },
                successURL: null,
                cancelURL: null,
                amount: null,
                currency: null,
                publishableKey: null,
                sessionId: null,
                paymentGateway: '',
                showQRCode: false,
                paymentMethod: null,
                creditCard: {
                    number: '',
                    name: '',
                    expiry: '',
                    cvc: ''
                }
            }
        },
        setup(props) {


            return {};
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
            this.modalDeposit = new Modal(document.getElementById('modalElDeposit'), {
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
               this.setPaymentMethod('pix', 'digitopay');

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
    window.location.reload();
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
            setPaymentMethod: function(method, gateway) {
                if(method === 'stripe') {
                    this.getPublicKeyStripe();
                }
                this.paymentType = method;
                this.paymentGateway = gateway;
                this.paymentMethod = method;
            },
            openModalDeposit: function() {
    window.location.reload();
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
    _this.deposit.payment_method = _this.paymentMethod;

    if (_this.paymentMethod === 'credit_card') {
        _this.deposit.credit_card = _this.creditCard;
    }

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
            } else if (response.data && response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            } else {
                throw new Error('Invalid response');
            }
        }).catch(error => {
            Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                _toast.error(`${value}`);
            });
            _this.showPixQRCode = false;
            _this.isLoading = false;
        });
    };

    attemptPayment(_this.paymentGateway);
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
.input-container {
  width: 335px;
  margin: 0 -15px 0 0; /* Move um pouco para a esquerda */
  padding: 0 15px; /* Adiciona um pouco de padding para evitar que chegue até a borda */
  box-sizing: border-box; /* Garante que o padding não aumente a largura total */
}

.full-screen {
  width: 90vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: flex-start; /* Alinha o conteúdo ao topo */
  justify-content: flex-start; /* Garante que o conteúdo comece do topo */

}
.amount.selected {
  color: var(--ci-primary-color);
}
.item-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.2rem; /* Espaçamento entre os itens */
}

.item-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.header {
  background-color: red; /* Retângulo vermelho */
  color: white; /* Texto branco */
  font-size: 12px; /* Diminuir o tamanho do título */
  padding: 2px 5px; /* Ajustar o padding para um retângulo mais fino */
  width: 100%; /* Largura ajustada para alinhar com os itens */
  text-align: center;
  font-weight: bold;
  box-sizing: border-box;
  margin-bottom: 0.2rem; /* Espaçamento entre o header e o item */
  border-radius: 5px 5px 0 0; /* Ajustar o border-radius */
  position: absolute; /* Para posicionamento */
  top: 0;
  left: 0;
}

.item {
  width: 102px; /* Largura fixa em pixels */
  height: 80px; /* Altura fixa em pixels */
  box-sizing: border-box;
  padding: 5px; /* Padding interno dos itens */
  border: 1px solid #ccc;
  border-radius: 5px; /* Ajustando o border-radius */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative; /* Para posicionamento do header */
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 25px; /* Espaçamento para ajustar o conteúdo abaixo do header */
}

.amount {
  margin-bottom: 0.2rem; /* Espaçamento entre o valor e o bônus */
  color: #fff;
  font-weight: bold;
  font-size: 25px;
}

.bonus {
  font-size: 10px; /* Diminuir o tamanho do texto do bônus */
    color: gold;
  font-weight: bold;

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
  background-color: #323637; /* Cor de fundo do contêiner */
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
  color: gold;
  font-size: 2rem; /* Aumentar o tamanho do texto */
  font-weight: bold;
  text-align: center; /* Centralizar o texto */
}
.centered-text2 {
  color: #ffffff;
  font-size: 1.1rem; /* Aumentar o tamanho do texto */
  text-align: center; /* Centralizar o texto */

}
.countdown-container {
  text-align: left; /* Alinha o texto e o número à esquerda */
  margin-top: 1rem;
}

.countdown-text {
  color: orange;
  font-size: 1rem;
  margin-bottom: 0.3rem;
}

.countdown-timer {
  font-size: 1rem;
  color: #ffffff;
  margin-bottom: 0.3rem;
}

.progress-bar-container {
  width: 100%;
  background-color: #ddd;
  border-radius: 0.25rem;
  height: 5px; /* Reduz a espessura da barra de progresso */
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  width: 100%;
  background-color: var(--ci-primary-color);
  transition: width 1s linear;
}

.button-container {
  display: flex;
  justify-content: center;
  margin-top: 1rem;
}


.toggle-text {
  color: #9c9c9c; /* Verde escuro */
  cursor: pointer;
  text-decoration: underline;

}
.text-container {
  display: flex;
  justify-content: center; /* Centraliza horizontalmente */
  align-items: center; /* Centraliza verticalmente */
  margin-top: 1rem;
  width: 100%; /* Garante que o container ocupe toda a largura */
}
.toggle-text:hover {
  color: #004d00; /* Verde mais escuro para o hover */
}

.bonus-icon {
    display: block;
    height: 170px; /* Reduzido em 50% */
    width: auto;
    margin: 0 auto;
         margin-bottom: -20px; /* Ajuste conforme necessário para encostar no logo */
    top: -50px;
}

.credit-card-form {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.credit-card-form input {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.credit-card-form .row {
    display: flex;
    gap: 15px;
}

.credit-card-form .row .col {
    flex: 1;
}
</style>
