<template>
    <button @click.prevent="$router.push('/login')" type="button" :class="[showMobile === false ? 'hidden md:block' : '', isFull ? 'w-full' : '']" >
<div class="flex items-center justify-center" @click.prevent="toggleModalDeposit">
    <div class="new-button-container">
        <div class="new-button-inner" @click.prevent="toggleModalDeposit">

            <div class="text-white text-center text-[30px] font-bold">Entrar</div>
        </div>
    </div>
</div>
    </button>


    <div v-if="!sumirmodal" id="modalElDeposit" tabindex="-1" aria-hidden="true" class="fixed  top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full translucent-menu">
        <div class="relative w-full max-w-2xl max-h-full  ">
            <div class="flex flex-col md:justify-between px-6 pb-8 my-auto">
                <div class="flex justify-between modal-header mb-6 mt-6">
                    <div>
                        <h1 class="font-bold text-xl" style="color: #01ec01">Entrar</h1>
                    </div>
                    
                        <div>
                    <button @click.prevent="toggleModalDeposit" class="text-red-500 hover:text-red-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    </div>
                </div>

                <DepositWidget />

            </div>
        </div>
    </div>

</template>

<script>
    import {useAuthStore} from "@/Stores/Auth.js";
    import DepositWidget from "@/Components/Widgets/DepositWidget.vue";
    import {onMounted} from "vue";
    import {initFlowbite} from "flowbite";

    export default {
        props: ['showMobile', 'title', 'isFull'],
        components: { DepositWidget },
        data() {
            return {
                isLoading: false,
                modalDeposit: null,
                sumirmodal: false,
            }
        },
        setup(props) {
            onMounted(() => {
                initFlowbite();
            });

            return {};
        },
        computed: {
            isAuthenticated() {
                const authStore = useAuthStore();
                return authStore.isAuth;
            },
        },
        mounted() {
            this.modalDeposit = new Modal(document.getElementById('modalElDeposit'), {
                 placement: 'top-center', // Altere isso para 'top-center' para abrir para cima
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

        },
        methods: {
            toggleModalDeposit: function() {
                this.modalDeposit.toggle();
            },
        },
        created() {

        },
        watch: {

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
      width: 100px;
      height: 100px;
      background: linear-gradient(to top, rgba(255, 140, 0, 1), rgba(255, 165, 0, 1)); /* Gradiente laranja */
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      mask-image: url('/public/hexagon.svg'); /* URL do SVG */
      mask-size: contain;
      mask-position: center;
      mask-repeat: no-repeat;
      -webkit-mask-image: url('/public/hexagon.svg'); /* Compatibilidade com WebKit */
      -webkit-mask-size: contain;
      -webkit-mask-position: center;
      -webkit-mask-repeat: no-repeat;
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
#modalElDeposit {
    top: auto; /* Remove a definição de topo automática */
    bottom: 30%; /* Define uma margem inferior para o modal */
    transform: translateY(0); /* Ajusta a posição vertical do modal */
    max-height: calc(100vh - 30%); /* Garante que o modal não ultrapasse a altura da tela */
    overflow-y: auto; /* Adiciona rolagem se o conteúdo do modal for muito alto */
}
</style>
