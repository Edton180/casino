<template>
    <AuthLayout class="fundo min-h-screen flex flex-col">
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading') }}</span>
            </div>
        </LoadingComponent>

<div v-if="fechando" class="w-full fundo flex flex-col items-center justify-start min-h-screen">
        <div class="mt-20"> <!-- Adicionando margem superior para descer um pouco -->

    <button class="bg-gray-500 text-white px-2 py-1 rounded focus:outline-none close-button2 absolute top-10 right-5" @click.prevent="toggleFechando">
        <i class="fas fa-times"></i>
    </button>
    <div class="text-white text-center">
        <h1 class="text-3xl font-bold">Tem certeza que deseja</h1>
        <h1 class="text-3xl font-bold">cancelar seu registro?</h1>
        <p class="text-base mt-2 text-gray-300">Cadastre-se e deposite agora</p>
        <p class="text-base mt-1 text-gray-300">e ganhe até 120 giros grátis!</p>

    </div>
    <button @click.prevent="toggleFechando" class="mt-4 ui-button-custom2">
        Continuar
    </button>
        <p @click.prevent="$router.push('home')" class="text-base text-gray-300 mt-2 flex items-center justify-center">Sim, quero cancelar</p>
</div>

</div>




<div v-if="!fechando"  class="w-full fundo">
    
    <div class="w-full " >
        <div class=" w-full justify-between items-center mx-0">
            <div class=" w-full rounded-lg shadow-lg border-none md:mt-0 xl:p-0 bg-base">
                <div class=" w-fullp-6 space-y-4 md:space-y-6 sm:p-8">
                    <div class="header tirafundo">

                    <img v-if="!entrar" :src="`/storage/`+setting.software_login" class="logo w-full" alt="Logo do Site">
                    <img v-if="entrar" :src="`/storage/`+setting.software_registro" class="logo w-full" alt="Logo do Site">
                    </div>
                    
                    
                    
<div class=" menu-bar w-full flex justify-between items-center p-4  ">

        <div class="relative">
            <button v-if="!entrar" class="bg-black text-white px-2 py-1 rounded focus:outline-none close-button" @click.prevent="$router.push('home')">
                <i class="fas fa-times"></i>
            </button>
            <button v-if="entrar" class="bg-black text-white px-2 py-1 rounded focus:outline-none close-button" @click.prevent="toggleFechando">
                <i class="fas fa-times"></i>
            </button>
        </div>
</div>
<!-- Divider -->

                    <div  v-if="!entrar" class="mt-4 px-4 fundo">
                                    <hr class="mb-3 mt-2 dark:border-gray-600">


                        <form @submit.prevent="loginSubmit" method="post" action="" class="fundo">
                            <div>
                                

                                <div class="relative mb-3">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                        <i class="fa-regular fa-envelope text-success-emphasis"></i>
                                    </div>
                                    <input 
                                        required 
                                        type="text" 
                                        v-model="loginForm.email" 
                                        name="email" 
        class="input-group bg-light text-dark w-full"
                                        placeholder="Digite seu E-mail"

                                    >

                                </div>

                                <div class="relative mb-6">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                        <i class="fa-regular fa-lock text-success-emphasis"></i>
                                    </div>
    <input 
      required 
      :type="typeInputPassword"
      v-model="loginForm.password"
      name="password"
      class="input-group bg-light text-dark w-full"
      :placeholder="$t('Type the password')"
      @blur="checkPasswordLength"
    >
    <div v-if="showPasswordError" class="password-error">
      A senha deve ter no mínimo 6 caracteres
    </div>
                                    <button 
                                        type="button" 
                                        @click.prevent="togglePassword" 
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5"
                                    >
                                        <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                        <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                                <a href="/forgot-password" class="text-white text-sm">{{ $t('Forgot password') }}</a>

                            <div class="mt-3 w-full">
    <button 
        type="submit" 
        :class="['ui-button-custom', {'button-disabled': !isFormValid}]"
        :disabled="!isFormValid"
        class="w-full mb-4">
        <span>Entrar</span>
    </button>
                            </div>
                            <div class="flex justify-between items-center mb-6">
                                <p class="text-lg text-white dark:text-white">
                                    Novo por aqui? <a href="" @click.prevent="toggleEntrar" class="text-lg text-gold dark:text-gold" ><strong>Crie Uma Conta Grátis</strong></a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

        
    
<div v-if="entrar" class="mt-4 px-4  fundo">
                                    <img  :src="`/storage/`+setting.software_logo_white" class="logo2 " alt="Logo do Site">

                                    <hr class="mb-3 mt-2 dark:border-gray-600">

                                <form @submit.prevent="registerSubmit" method="post" action="" class="fundo">
                                    
                                                                                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-user text-success-emphasis"></i>
                                        </div>
                                        <input type="text"
                                               name="name"
                                               v-model="registerForm.name"
                                               class="input-group"
                                               :placeholder="$t('Enter name')"
                                               required
                                        >
                                    </div>
                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-envelope text-success-emphasis"></i>
                                        </div>
                                        <input type="email"
                                               name="email"
                                               v-model="registerForm.email"
                                               class="input-group"
                                               placeholder="Digite seu E-mail"
                                               @blur="checkemailLength2"

                                               required
                                        >
                                        
                                        <div v-if="showPasswordError4" class="password-error">
                                          Email Invalido coloque tipo: carlos@exemplo.com
                                        </div>
                                    </div>
                                   
                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-lock text-success-emphasis"></i>
                                        </div>
                                        <input 
                                          required 
                                          :type="typeInputPassword"
                                          v-model="registerForm.password"
                                          name="password"
                                          class="input-group bg-light text-dark w-full"
                                          :placeholder="$t('Type the password')"
                                          @blur="checkPasswordLength2"
                                        >
                                        <div v-if="showPasswordError" class="password-error">
                                          A senha deve ter no mínimo 6 caracteres
                                        </div>
                                        <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 ">
                                            <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                            <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                                        </button>
                                    </div>      
                                    
                                                                        <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-light fa-address-card text-success-emphasis"></i>
                                        </div>
                                        <input type="text"
                                               name="cpf"
                                               v-model="registerForm.cpf"
                                               class="input-group"
                                               placeholder="Digite o seu CPF"
                                               @blur="checkcpfLength2"
                                               v-maska
                                               data-maska="[
                                    '###.###.###-##'
                                  ]"
                                               required
                                        >
                                        <div v-if="showPasswordError2" class="password-error">
                                          o CPF Parece Incorreto!
                                        </div>
                                    </div>
                                    
                                    <div class="relative mb-3" style="display: none;">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-user text-success-emphasis"></i>
                                        </div>
                                        <input type="text"
                                               name="name"
                                               v-model="registerForm.name"
                                               class="input-group"
                                               placeholder=""

                                        >
                                    </div>


                                    <div class="relative mb-3">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                            <i class="fa-regular fa-phone"></i>
                                        </div>
                                        <input type="text"
                                               name="phone"
                                               v-maska
                                               data-maska="[
                                    '(##) # ####-####'
                                  ]"
                                               v-model="registerForm.phone"
                                               class="input-group"
                                               :placeholder="$t('Enter your phone')"
                                                @blur="checktelefoneLength2"

                                               required
                                        >
                                        <div v-if="showPasswordError3" class="password-error">
                                          O telefone parece incorreto!
                                        </div>
                                    </div>

                                    <div class="mb-3 mt-5" style="display: none;">
                                        <button @click.prevent="isReferral = !isReferral" type="button" class="flex justify-between w-full" >
                                            <p>{{ $t('Enter Referral/Promo Code') }}</p>
                                            <div class="">
                                                <i v-if="isReferral" class="fa-solid fa-chevron-up"></i>
                                                <i v-if="!isReferral" class="fa-solid fa-chevron-down"></i>
                                            </div>
                                        </button>

                                        <div v-if="isReferral" class="relative mb-3 mt-1" style="display: none;">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                                <i class="fa-regular fa-user text-success-emphasis"></i>
                                            </div>
                                            <input type="text" name="name" v-model="registerForm.reference_code" class="input-group" :placeholder="$t('Code')">
                                        </div>
                                    </div>


                                    <div class="mb-3 mt-1">
                                        <div class="flex">

<label for="term-a" class="ml-2 text-xs font-medium text-left text-gray-900 dark:text-gray-300">
    Ao se registrar <span class="text-gold text-xs">você concorda com nossos termos e condições</span>!
</label>

                                        </div>
                                    </div>

                                    <div class="mb-3" style="display: none;">
                                        <div class="flex items-center">
                                            <input id="term-agreement" v-model="registerForm.agreement" name="term_b" required type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="term-agreement" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $t('I agree with the') }} <a href="#" class="text-primary hover:underline">{{ $t('terms and conditions') }}</a>.</label>
                                        </div>
                                    </div>

                                    <div class="mt-5 w-full">
    <button 
        type="submit" 
        :class="['ui-button-custom', {'button-disabled': !isFormValidRegister}]"
        :disabled="!isFormValidRegister"
        class="w-full mb-4">
        <span>Registrar</span>
    </button>
                                    </div>
                                </form>


                            </div>
                        </div>

    </AuthLayout>
</template>


<script>

import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";
import HttpApi from "@/Services/HttpApi.js";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import {useRoute, useRouter} from "vue-router";
import {onMounted, reactive} from "vue";
import {useSpinStoreData} from "@/Stores/SpinStoreData.js";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import {useSettingStore} from "@/Stores/SettingStore.js";

export default {
    props: [],
    components: { LoadingComponent, AuthLayout },
    data() {
        return {
            isLoading: false,
            fechando: false,
            entrar: true,
            typeInputPassword: 'password',
            isReferral: false,
            loginForm: {
                email: '',
                password: '',
            },
            registerForm: {
                name: '',
                email: '',
                password: '',
                cpf: '',
                reference_code: '',
                term_a: true,
                agreement: true,
                spin_data: null
            },
                  showPasswordError: false, // Controle de exibição do erro de senha
                  showPasswordError2: false, // Controle de exibição do erro de senha
                  showPasswordError3: false, // Controle de exibição do erro de senha
                  showPasswordError4: false, // Controle de exibição do erro de senha
                  showPasswordError5: false, // Controle de exibição do erro de senha

        }
    },
    setup() {
        const router = useRouter();
        const routeParams = reactive({
            code: null,
        });

        onMounted(() => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('code')) {
                routeParams.code = params.get('code');
            }
        });

        return {
            routeParams,
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
        isFormValid() {
            const emailValid = this.loginForm.email.includes('@');
            const passwordValid = this.loginForm.password.length >= 6;
            return emailValid && passwordValid;
        },
        isFormValidRegister() {
            const emailValid = this.registerForm.email.includes('@');
            const passwordValid = this.registerForm.password.length >= 6;
            const cpfValid = this.registerForm.cpf.length >= 11;
            const numeroValid = this.registerForm.cpf.length >= 14;

            return emailValid && passwordValid && cpfValid && numeroValid;
        },
    },
    mounted() {
        const router = useRouter();
        if(this.isAuthenticated) {
            router.push({ name: 'home' });
        }

        if (this.router.currentRoute.value.params.code) {
            try {
                const str = atob(this.router.currentRoute.value.params.code);
                const obj = JSON.parse(str);


                this.registerForm.spin_token = this.router.currentRoute.value.params.code;
            } catch (e) {
                this.registerForm.reference_code = this.routeParams.code;
                this.isReferral = true;
            }
        }else if(this.routeParams.code) {
            this.registerForm.reference_code = this.routeParams.code;
            this.isReferral = true;
        }
    },
    methods: {
            toggleEntrar: function() {
      this.entrar = !this.entrar; // Inverte o valor de entrar (false para true e vice-versa)
      
        this.loginForm.email = '';
        this.loginForm.password = '';
        this.registerForm.name = '';
        this.registerForm.email = '';
        this.registerForm.password = '';
        this.registerForm.cpf = '';
        this.registerForm.phone = '';

        this.checkPasswordLength();
        this.checkPasswordLength2();
        this.checkcpfLength2();
        this.checktelefoneLength2();
        this.checkemailLength2();
        this.checkemailLength3();

    
    },
    
        toggleFechando: function() {
      this.fechando = !this.fechando;
      
        },
            checkPasswordLength: function() {
      if (this.loginForm.password.length > 1 && this.loginForm.password.length < 6) {
        this.showPasswordError = true;
      } else {
        this.showPasswordError = false;
      }
    },
                checkPasswordLength2: function() {
      if (this.registerForm.password.length > 1 && this.registerForm.password.length < 6) {
        this.showPasswordError = true;
      } else {
        this.showPasswordError = false;
      }
    },
    
    checkcpfLength2: function() {
      if (this.registerForm.cpf.length > 1 && this.registerForm.cpf.length < 14) {
        this.showPasswordError2 = true;
      } else {
        this.showPasswordError2 = false;
      }
    },
    
    checktelefoneLength2: function() {
      if (this.registerForm.phone.length > 1 && this.registerForm.phone.length < 16) {
        this.showPasswordError3 = true;
      } else {
        this.showPasswordError3 = false;
      }
    },
    
    checkemailLength2: function() {
      if (this.registerForm.email.length > 1 && !this.registerForm.email.includes('@')) {
        this.showPasswordError4 = true;
      } else {
        this.showPasswordError4 = false;
      }
    },
    
    checkemailLength3: function() {
      if (this.loginForm.email.length > 1 && !this.registerForm.email.includes('@')) {
        this.showPasswordError5 = true;
      } else {
        this.showPasswordError5 = false;
      }
    },
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        loginToggle: function() {
            this.modalAuth.toggle();
        },
        loginSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;
            const authStore = useAuthStore();

            await HttpApi.post('auth/login', _this.loginForm)
                .then(async response =>  {
                    await new Promise(r => {
                        setTimeout(() => {
                            authStore.setToken(response.data.access_token);
                            authStore.setUser(response.data.user);
                            authStore.setIsAuth(true);

                            _this.loginForm = {
                                email: '',
                                password: '',
                            }

                            _this.router.push({ name: 'home' });
                            _toast.success(_this.$t('You have been authenticated, welcome!'));

                            _this.isLoading = false;
                        }, 1000)
                    });

                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
        
            registerSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;

            const authStore = useAuthStore();
            await HttpApi.post('auth/register', _this.registerForm)
                .then(response => {
                    if(response.data.access_token !== undefined) {
                        authStore.setToken(response.data.access_token);
                        authStore.setUser(response.data.user);
                        authStore.setIsAuth(true);

                        _this.registerForm = {
                            name: '',
                            email: '',
                            password: '',
                            reference_code: '',
                            term_a: false,
                            agreement: false,
                            spin_data: null
                        };

                        _this.router.push({ name: 'home' });
                        _toast.success(_this.$t('Cadastro Criado Com Sucesso!'));
                    }

                    _this.isLoading = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
    },
    watch: {

    },
};
</script>

<style scoped>
.password-error {
  position: absolute;
  top: 100%;
  right: 0;
  background-color: #ff0000; /* Cor de fundo vermelha */
  color: #fff; /* Cor do texto branco */
  font-size: 12px;
  padding: 5px;
  border-radius: 4px;
        z-index: 999; /* Garante que o texto fique acima da máscara */

}

.password-error.active {
  display: block;

}

.ui-button-custom.button-disabled {
    cursor: not-allowed; /* Cursor de não permitido */
        background: #4d4d33; /* Máscara preta com opacidade */

}

.ui-button-custom.button-disabled::before {
    color: #000; /* Texto preto */
    border-radius: 6px; /* Borda arredondada nas quinas */
    height: 50px; /* Altura do botão */
    width: 180px; /* Largura do botão */
    font-size: 22px; /* Tamanho da fonte */
    display: flex;
    font-weight: bold; /* Texto em negrito */
    background: #4d4d33; /* Máscara preta com opacidade */

    justify-content: center;
    align-items: center;
    padding: 0 20px; /* Padding horizontal para espaçamento interno */
    border: none; /* Remove borda padrão */
    cursor: pointer; /* Adiciona cursor pointer */
    transition: background-color 0.3s ease; /* Transição suave para hover */
    
}

.ui-button-custom.button-disabled span {
    position: relative;
    z-index: 2; /* Garante que o texto fique acima da máscara */
}

.bg-light {
    background-color: #424344; /* Cor de fundo mais clara */
}

.text-dark {
    color: #f2f2f2; /* Cor do texto mais escura para contraste */
}

.input-group {
   background-color: #424344; /* Cor de fundo mais clara */
    color: #f2f2f2; /* Cor do texto mais escura */
    padding: 12px 16px; /* Aumenta o padding para deixar o input mais gordinho */
    padding-left: 40px; /* Adiciona padding à esquerda para os ícones */
    border-radius: 8px; /* Deixa as bordas mais arredondadas */
    font-size: 20px; /* Aumenta o tamanho da fonte */
    height: 60px; /* Define uma altura mínima para o input */
}

.input-group::placeholder {
    color: #888; /* Cor do placeholder mais clara */
}

.tirafundo {
    background-color: transparent !important; /* Força a transparência */
}

.fundo {
    background-color: #1d1d1d; /* Ajuste conforme necessário */
    background-size: cover;
    background-repeat: no-repeat;
    min-height: 100vh; /* Garante que cubra toda a altura da tela */
}
.close-button {
    position: absolute;
    top: -180px; /* Ajuste conforme necessário */
    right: -5px; /* Ajuste conforme necessário */
    border-radius: 4px; /* Cantos ligeiramente arredondados */

}

.close-button2 {
    position: absolute;
    top: 20px; /* Ajuste conforme necessário */
    right: 10px; /* Ajuste conforme necessário */
    border-radius: 4px; /* Cantos ligeiramente arredondados */

}
.text-gold {
    color: gold;
}

.header {
    position: relative;
    height: 170px; /* Ajuste conforme necessário */
    overflow: hidden;

}

.logo {
    display: block;
    height: 170px; /* Reduzido em 50% */
    width: auto;
    margin: 0 auto;
         margin-bottom: -20px; /* Ajuste conforme necessário para encostar no logo */

}

.logo2 {
    display: block;
    height: 50px; /* Reduzido em 50% */
    width: 110px;
    margin: 0 auto;
    margin-top: -40px; /* Ajuste conforme necessário para encostar no logo */
    margin-bottom: 10px; /* Ajuste conforme necessário para encostar no logo */

    
}
.header::after {
    content: '';
    position: absolute;
    bottom: -50px; /* Ajuste conforme necessário */
    left: 0;
    right: 0;
    height: 50px; /* Altura do gradiente */
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0) 50%, rgba(255, 255, 255, 1) 100%);
}

.menu-bar {
    padding: 2px 16px; /* Reduzindo o padding para afinar o retângulo */
    margin-top: 0; /* Ajuste conforme necessário para encostar no logo */

    position: relative;
    top: -17px; /* Ajuste conforme necessário */
    background-color: #1d1d1d; /* Ajuste conforme necessário */
    width: 100%; /* Certifique-se de que a largura é 100% */
    height: auto; /* Permite que a altura seja determinada pelo conteúdo */
    display: flex; /* Garante que o menu seja flexível */
    justify-content: space-between; /* Distribui os itens uniformemente */
    align-items: center; /* Alinha os itens verticalmente no centro */
    min-height: 50px; /* Ajuste conforme necessário para garantir uma altura mínima */
    box-sizing: border-box; /* Inclui padding e border no cálculo da largura e altura */
}

.menu-bar .flex {
    display: flex;
    justify-content: center;
    align-items: center;
}

.menu-bar .relative button {
    border-radius: 4px; /* Cantos ligeiramente arredondados */
}

.menu-bar .relative {
    margin-left: auto;
}
</style>
