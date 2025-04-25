<template>
    <div class="flex sm:hidden" >
        <div class="fixed z-40 w-full navtop-color h-20 -translate-x-1/2 bottom-0 left-1/2 translucent-menu">
            <div class="flex h-full max-w-lg justify-between items-center mx-auto">
                <!-- Botão Home -->


                <!-- Botão Menu -->
                <button @click.prevent="toggleMenu" type="button" class="inline-flex items-center flex-col justify-center p-2 m-0 text-sm text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                    </svg>
                    <span class="text-[10px] font-bold">Menu</span>
                </button>
                <button @click="$router.push('/')" data-tooltip-target="tooltip-home" type="button" class="inline-flex items-center flex-col justify-center p-2 m-0 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <i class="fa-solid fa-house mb-1 text-lg"></i>
                    <span class="text-[10px] font-bold">{{ $t('Home') }}</span>
                </button>
                <div id="tooltip-home" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    {{ $t('Home') }}
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                
<a v-if="setting && setting.turn_on_football" 
     :href="setting.football_link" 
     data-tooltip-target="tooltip-sport" 
     class="inline-flex flex-col items-center justify-center p-2 m-0 hover:bg-gray-50 dark:hover:bg-gray-800 group">                    <svg class="text-lg mb-1 w-6 h-6" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"  fill="currentColor">
                        <path d="M355.5 45.53L342.4 14.98c-27.95-9.983-57.18-14.98-86.42-14.98c-29.25 0-58.51 4.992-86.46 14.97L156.5 45.53l99.5 55.13L355.5 45.53zM86.78 96.15L53.67 99.09c-34.79 44.75-53.67 99.8-53.67 156.5L.0001 256c0 2.694 .0519 5.379 .1352 8.063l24.95 21.76l83.2-77.67L86.78 96.15zM318.8 336L357.3 217.4L255.1 144L154.7 217.4l38.82 118.6L318.8 336zM512 255.6c0-56.7-18.9-111.8-53.72-156.5L425.6 96.16L403.7 208.2l83.21 77.67l24.92-21.79C511.1 260.1 512 258.1 512 255.6zM51.77 367.7l-7.39 32.46c33.48 49.11 82.96 85.07 140 101.7l28.6-16.99l-48.19-103.3L51.77 367.7zM347.2 381.5l-48.19 103.3l28.57 17c57.05-16.66 106.5-52.62 140-101.7l-7.38-32.46L347.2 381.5z"></path>
                        <path d="M458.3 99.08L458.3 99.08L458.3 99.08zM511.8 264c-1.442 48.66-16.82 95.87-44.28 136.1l-7.38-32.46l-113 13.86l-48.19 103.3l28.22 16.84c-23.48 6.78-47.67 10.2-71.85 10.2c-23.76 0-47.51-3.302-70.58-9.962l28.23-17.06l-48.19-103.3l-113-13.88l-7.39 32.46c-27.45-40.19-42.8-87.41-44.25-136.1l24.95 21.76l83.2-77.67L86.78 96.15L53.67 99.09c29.72-38.29 69.67-67.37 115.2-83.88l.3613 .2684L156.5 45.53l99.5 55.13l99.5-55.13L342.4 14.98c45.82 16.48 86 45.64 115.9 84.11L425.6 96.16L403.7 208.2l83.21 77.67L511.8 264zM357.3 217.4L255.1 144L154.7 217.4l38.82 118.6L318.8 336L357.3 217.4z" opacity="0.4"></path>
                    </svg>
                    <span class="text-[12px] font-bold">Esportes</span>
                                        <span class="status-indicator"></span>

                </a>
                <div id="tooltip-sport" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    Esportes
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                <!-- Novo Botão no Centro -->


<!-- Componente MakeDeposit escondido -->



                <!-- Botão Bonus -->
<button @click="$router.push('/events')" data-tooltip-target="tooltip-casino" type="button" class="inline-flex flex-col items-center justify-center p-2 m-0 ml-2 group" style="background-color: transparent; border: none; padding: 0; outline: none;">
    <i class="fa-solid fa-gift mb-1 text-lg" style="background-color: transparent;"></i>
    <span class="text-[10px] font-bold">{{ $t('Bonus') }}</span>
</button>
<div id="tooltip-casino" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
    {{ $t('Bonus') }}
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>


                <!-- Botão Esportes -->
                

                <!-- Botão Convidar -->
                <button @click="$router.push('/profile/affiliate')" data-tooltip-target="tooltip-convite" type="button" class="inline-flex flex-col items-center justify-center p-2 m-0 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <i class="fa-solid fa-people-group mb-1 text-lg"></i>
                    <span class="text-[10px] font-bold">Convidar</span>
                    <span class="status-indicator"></span>
                </button>
                <div id="tooltip-convite" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    Ganhe R$
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>




<div class="z-50 hidden my-4 text-base list-none divide-y divide-gray-100 rounded shadow translucent-menu" id="dropdown-user">
    <div class="flex flex-col items-center px-4 py-6 bg-white">
                    <button class="absolute top-2 right-2 text-gray-900 dark:text-black" data-dropdown-toggle="dropdown-user">
        <i class="fas fa-times"></i>
    </button>
        <img class="w-16 h-16 rounded-full mb-3" :src="userData?.avatar ? '/storage/'+userData.avatar : `/assets/images/profile.jpg`" alt="Profile Picture">
        <p class="text-sm text-gray-900 dark:text-black" role="none">
            {{ userData?.name }}
        </p>
        <p class="text-sm font-medium text-gray-900 truncate dark:text-black" role="none">
            {{ userData?.email }}
        </p>
    </div>
    <ul class="py-1 flex flex-wrap justify-center" role="none">
        <li class="w-1/2 p-2">
            <RouterLink :to="{ name: 'profileAffiliate' }" active-class="profile-menu-active" class="block p-4 bg-white rounded text-center hover:bg-gray-100 dark:text-black dark:hover:bg-gray-600 dark:hover:text-white">
                <span class="block mb-2">
                    <i class="fa-duotone fa-people-group"></i>
                </span>
                Indique e Ganhe!
            </RouterLink>
        </li>
        <li class="w-1/2 p-2">
            <RouterLink :to="{ name: 'profileDeposit' }" active-class="profile-menu-active" class="block p-4 bg-white rounded text-center hover:bg-gray-100 dark:text-black dark:hover:bg-gray-600 dark:hover:text-white">
                <span class="block mb-2">
                    <i class="fa-regular fa-money-bill-trend-up"></i>
                </span>
                {{ $t('Deposit') }}
            </RouterLink>
        </li>
        <li class="w-1/2 p-2">
            <RouterLink :to="{ name: 'profileWallet' }" active-class="profile-menu-active" class="block p-4 bg-white rounded text-center hover:bg-gray-100 dark:text-black dark:hover:bg-gray-600 dark:hover:text-white">
                <span class="block mb-2">
                  <i class="fa-duotone fa-wallet"></i>
                </span>
                {{ $t('My Wallet') }}
            </RouterLink>
        </li>
        <li class="w-1/2 p-2">
            <RouterLink :to="{ name: 'profileWallet' }" active-class="profile-menu-active" class="block p-4 bg-white rounded text-center hover:bg-gray-100 dark:text-black dark:hover:bg-gray-600 dark:hover:text-white">
                <span class="block mb-2">
                  <i class="fa-duotone fa-wallet"></i>
                </span>
                Saque
            </RouterLink>
        </li>
        <li class="w-full p-2">
    <button @click.prevent="logoutAccount" class="flex items-center justify-center w-full p-2 bg-white rounded text-center hover:bg-gray-100 dark:text-black dark:hover:bg-gray-600 dark:hover:text-white">
        <i class="fas fa-sign-out-alt mr-2"></i>
        Desconectar
    </button>
</li>

    </ul>
</div>



            </div>
        </div>
    </div>
</template>



<script>
import { RouterLink, useRoute } from "vue-router";
import { sidebarStore } from "@/Stores/SideBarStore.js";
import { Modal } from 'flowbite';
import { useAuthStore } from "@/Stores/Auth.js";
import { useToast } from "vue-toastification";
import { useRouter } from 'vue-router';

import DropdownDarkLight from "@/Components/UI/DropdownDarkLight.vue";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
import WalletBalance from "@/Components/UI/WalletBalance.vue";
import HttpApi from "@/Services/HttpApi.js";
import MakeDeposit from "@/Components/UI/MakeDeposit.vue";
import Login from "@/Components/UI/Login.vue";

import Esportes from "@/Components/UI/Esportes.vue";

import {useSettingStore} from "@/Stores/SettingStore.js";
import {searchGameStore} from "@/Stores/SearchGameStore.js";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
    props: ['simple'],
    components: {CassinoGameCard, MakeDeposit, Login, WalletBalance, LanguageSelector, DropdownDarkLight, RouterLink, Esportes },
    data() {
        return {
            showNewElement: true,

            isLoadingLogin: false,
            isLoadingRegister: false,
            isReferral: false,
            modalAuth: null,
            modalRegister: null,
            modalProfile: null,
            modalElpresente: null,

            typeInputPassword: 'password',
            readonly: false,
            profileUser: null,
            loginForm: {
                email: '',
                password: '',
            },
            registerForm: {
                name: '',
                email: '',
                cpf: '',
                password: '',
                password_confirmation: '',
                reference_code: '',
                term_a: true,
                agreement: true,
            },
            avatarUrl: '/assets/images/profile.jpg',
            isLoadingProfile: false,
            profileName: '',
            sumBets: 0,
            totalBets: 0,
            totalEarnings: 0,
            showSearchMenu: false,
            games: null,
            searchTerm: '',
            isLoadingSearch: true,
        }
    },
    setup() {
        const router = useRouter();

        return {
            router,
        };
    },
    computed: {
        searchGameDataStore() {
            return searchGameStore();
        },
        searchGameMenu() {
            const search = searchGameStore();
            return search.getSearchGameStatus;
        },
        sidebarMenuStore() {
            return sidebarStore();
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        setting() {
            const authStore = useSettingStore();
            return authStore.setting;
        }
    },
    unmounted() {

    },
    mounted() {
        /*
        * $targetEl: required
        * options: optional
        */
        this.modalProfile = new Modal(document.getElementById('modalProfileEl'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });

        /*
        * $targetEl: required
        * options: optional
        */
        this.modalAuth = new Modal(document.getElementById('modalElAuth'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });

        /*
       * $targetEl: required
       * options: optional
       */
        this.modalRegister = new Modal(document.getElementById('modalElRegister'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });
        
        this.modalElpresente = new Modal(document.getElementById('modalElpresente'), {
            placement: 'center',
            backdrop: 'dynamic',
            backdropClasses: 'bg-gray-700 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40',
            closable: false,
            onHide: () => {

            },
            onShow: () => {

            },
            onToggle: () => {

            }
        });
        


    },
    methods: {
        deposit() {
            this.$refs.makeDeposit.open();
        },


                    closeNewElement() {
      // Lógica para fechar o novo elemento
      this.showNewElement = false;
      
    },
        toggleSearch: function() {
            this.searchGameDataStore.setSearchGameToogle();
        },
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        like: async function(id) {
            const _this = this;
            const _toast = useToast();
            await HttpApi.post('/profile/like/' + id, {})
                .then(response => {

                    _this.getProfile();
                    _toast.success(_this.$t(response.data.message));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                });
        },
        updateName: async function(event) {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.post('/profile/updateName', { name: _this.profileName })
                .then(response => {
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
        loginSubmit: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingLogin = true;
            const authStore = useAuthStore();

            HttpApi.post('auth/login', _this.loginForm)
                .then(async response => {
                    await new Promise(r => {
                        setTimeout(() => {
                            authStore.setToken(response.data.access_token);
                            authStore.setUser(response.data.user);
                            authStore.setIsAuth(true);

                            _this.loginForm = {
                                email: '',
                                password: '',
                            }

                            _this.modalAuth.toggle();
                            _toast.success(_this.$t('You have been authenticated, welcome!'));

                            _this.isLoadingLogin = false;

                        }, 1000)
                    });

                })

                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingLogin = false;
                });

        },
        registerSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingRegister = true;

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
                            password_confirmation: '',
                            reference_code: '',
                            term_a: false,
                            agreement: false,
                        }

                        _this.modalRegister.toggle();
                        _this.router.push({ name: 'home' });
                        _toast.success(_this.$t('Cadastro Criado Com Sucesso!, aguarde!'));
                    }

                    _this.isLoadingRegister = false;
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingRegister = false;
                });
        },
        logoutAccount: function() {
            const authStore = useAuthStore();
            const _toast = useToast();

            HttpApi.post('auth/logout', {})
                .then(response => {
                    authStore.logout();
                    this.router.push({ name: 'home' });

                    _toast.success(this.$t('You have been successfully disconnected'));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(value);
                        //_toast.error(`${value}`);
                    });
                });
        },
        hideLoginShowRegisterToggle: function() {
            this.modalAuth.toggle();
            this.modalRegister.toggle();
        },
        toggleMenu: function() {
            this.sidebarMenuStore.setSidebarToogle();
        },
        loginToggle: function() {
            this.modalAuth.toggle();
        },
        registerToggle: function() {
            this.modalRegister.toggle();
        },
        profileToggle: function() {
            this.modalProfile.toggle();
        },
        openFileInput() {
            this.$refs.fileInput.click();
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            const formData = new FormData();
            formData.append('avatar', file);

            const reader = new FileReader();
            reader.onload = () => {
                this.avatarUrl = reader.result;
            };
            reader.readAsDataURL(file);

            await HttpApi.post('/profile/upload-avatar', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }).then(response => {
                console.log('Avatar atualizado com sucesso', response.data);
            })
                .catch(error => {
                    console.error('Erro ao atualizar avatar', error);
                });
        },
        getProfile: async function() {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.get('/profile/')
                .then(response => {
                    _this.sumBets = response.data.sumBets;
                    _this.totalBets = response.data.totalBets;
                    _this.totalEarnings = response.data.totalEarnings;

                    const user = response.data.user;

                    if(user?.avatar != null) {
                        _this.avatarUrl = '/storage/'+user.avatar;
                    }

                    _this.profileName = user.name;
                    _this.profileUser = user;
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        getSearch: async function() {
            const _this = this;

            await HttpApi.get('/search/games?searchTerm='+this.searchTerm)
                .then(response => {
                    _this.games = response.data.games;
                    _this.isLoadingSearch = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingSearch = false;
                });
        },
        clearData: async function() {
            this.searchTerm = '';
            await this.getSearch();
        }
    },
    async created() {
        if(this.isAuthenticated) {

            await this.getProfile();
        }
    },
    watch: {
        searchTerm(newValue, oldValue) {
            this.getSearch();
        },
        async searchGameMenu(newValue, oldValue) {

            await this.getSearch();
            this.showSearchMenu = !this.showSearchMenu;
        },
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

.new-button-container {
    position: relative;
    width: 80px;
    height: 80px;
    margin-top: -35px;
    display: flex;
    align-items: center;
    justify-content: center;
    
}

.new-button-inner {
    width: 80px;
    height: 80px;
    background: linear-gradient(to top, rgba(255, 140, 0, 1), rgba(255, 165, 0, 1)); /* Gradiente laranja */
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); /* Forma hexagonal */
    border-radius: 20px; /* Arredonda os cantos do polígono */
}

.new-button-inner svg {
    width: 28px;
    height: 28px;
}

.new-button-inner div {
    color: black; /* Cor do texto */
    font-weight: bold; /* Texto em negrito */
    text-align: center; /* Alinhamento centralizado */
    font-size: 15px; /* Tamanho da fonte */
}

.status-indicator {
    position: absolute;
    top: 5px; /* Ajuste a posição vertical */
    right: 90px; /* Ajuste a posição horizontal */
    width: 8px; /* Ajuste o tamanho */
    height: 8px; /* Ajuste o tamanho */
    border-radius: 50%;
    background-color: #01ec01; /* Cor verde */
    animation: blink 1s infinite alternate; /* Animação de piscar */
}

/* Animação de piscar */
@keyframes blink {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Garantir que não haja conflitos de estilos */
.fixed {
    background: rgba(0, 0, 0, 0.8) !important;
}

button {
    padding-left: 0.5rem; /* Ajuste da distância lateral */
    padding-right: 0.5rem; /* Ajuste da distância lateral */
}

button i, button svg {
    font-size: 1.4rem; /* Ajustar o tamanho dos ícones */
}

button span {
    font-size: 12px; /* Ajustar o tamanho do texto */
    font-weight: bold; /* Deixar o texto em negrito */
}

#dropdown-user {
    position: absolute;
    top: 100%; /* Para que apareça logo abaixo do botão de perfil */
    right: 0;
    z-index: 50;
    width: 100vw; /* Define a largura para ocupar toda a largura da tela */
    background: rgba(0, 0, 0, 0.7); /* Translucent black background */
    backdrop-filter: blur(10px); /* Blur effect */
    -webkit-backdrop-filter: blur(10px); /* Safari support */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    overflow: hidden;
    padding: 10px; /* Espaçamento interno */
}

#dropdown-user .bg-white {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
}

#dropdown-user .dark\:bg-gray-700 {
    background-color: rgba(0, 0, 0, 0.9); /* Less translucent dark gray background */
}

#dropdown-user .dark\:divide-gray-600 {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
}

#dropdown-user .dark\:text-black {
    color: white; /* White text color */
}

#dropdown-user .hover\:bg-gray-100:hover {
    background-color: rgba(0, 0, 0, 0.9); /* Slightly less translucent hover effect */
}

#dropdown-user .dark\:hover\:bg-gray-600:hover {
    background-color: rgba(75, 85, 99, 0.9); /* Slightly less translucent hover effect in dark mode */
}

#dropdown-user .dark\:hover\:text-white:hover {
    color: white; /* White text color on hover */
}

#dropdown-user .text-sm {
    font-size: 14px; /* Adjust font size */
}

</style>


