<template>
    <GameLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="flex justify-center items-center h-full">
                <a v-if="setting" href="/" class="logo-animation">
                    <img :src="`/storage/` + setting.software_logo_black" alt="" class="h-10 mr-3 block dark:hidden" />
                    <img :src="`/storage/` + setting.software_logo_white" alt="" class="h-10 mr-3 hidden dark:block" />
                </a>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading && game" class="game-container mx-auto px-2 lg:px-4 py-2 lg:py-6 relative">
            <div v-if="gameSpecificCondition" class="fut-screen" id="fut-screen" >
                <fullscreen v-model="fullscreen" :page-only="pageOnly">

                    <iframe :src="gameUrl" class="fut-full" id="specific-game-iframe"></iframe>
                </fullscreen>
            </div>
            <div v-if="!gameSpecificCondition" class="game-screen" id="game-screen" >
                <fullscreen v-model="fullscreen" :page-only="pageOnly">
                    <iframe :src="gameUrl" class="game-full fullscreen-wrapper" id="normal-game-iframe"></iframe>
                </fullscreen>
            </div>
        </div>

        <div v-if="undermaintenance" class="flex flex-col items-center justify-center text-center py-24">
            <h1 class="text-2xl mb-4">JOGO EM MANUTENÇÃO</h1>
            <img :src="`/assets/images/work-in-progress.gif`" alt="" width="400">
        </div>
    </GameLayout>
</template>

<script>
import { initFlowbite, Tabs, Modal } from 'flowbite';
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/Stores/Auth.js";
import { component } from 'vue-fullscreen';
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import GameLayout from "@/Layouts/GameLayout.vue";
import HttpApi from "@/Services/HttpApi.js";
import { defineComponent, toRefs, reactive } from 'vue';

export default {
    props: [],
    components: {
        GameLayout,
        LoadingComponent,
        RouterLink,
        component
    },
    data() {
        return {
            isLoading: true,
            game: null,
            modeMovie: false,
            gameUrl: null,
            token: null,
            gameId: null,
            tabs: null,
            undermaintenance: false,
            gameSpecificCondition: false, // Adicionando gameSpecificCondition

        }
    },
    setup() {
        const router = useRouter();
        const state = reactive({
            fullscreen: false,
            pageOnly: false,
        })
        function togglefullscreen() {
            console.log("CLICOU");
            state.fullscreen = !state.fullscreen;
        }

        return {
            ...toRefs(state),
            togglefullscreen,
            router
        }
    },
    computed: {
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    mounted() {
        const userAgent = navigator.userAgent.toLowerCase();
        const isSafari = userAgent.includes('safari') && !userAgent.includes('chrome');
        const isSamsungInternet = userAgent.includes('samsung') && userAgent.includes('safari') && !userAgent.includes('chrome');
        const isIOS = userAgent.includes('iphone') || userAgent.includes('ipad');
        this.setLanguageCookie();

        if (isSafari || isSamsungInternet || isIOS) {
            this.showButton = true;
        }
    },
    methods: {

        loadingTab: function() {
            const tabsElement = document.getElementById('tabs-info');
            if(tabsElement) {
                const tabElements = [
                    {
                        id: 'default',
                        triggerEl: document.querySelector('#default-tab'),
                        targetEl: document.querySelector('#default-panel'),
                    },
                    {
                        id: 'descriptions',
                        triggerEl: document.querySelector('#description-tab'),
                        targetEl: document.querySelector('#description-panel'),
                    },
                    {
                        id: 'reviews',
                        triggerEl: document.querySelector('#reviews-tab'),
                        targetEl: document.querySelector('#reviews-panel'),
                    },
                ];

                const options = {
                    defaultTabId: 'default',
                    activeClasses: 'text-green-600 hover:text-green-600 dark:text-green-500 dark:hover:text-green-400 border-green-600 dark:border-green-500',
                    inactiveClasses: 'text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300',
                    onShow: () => {

                    },
                };

                const instanceOptions = {
                    id: 'default',
                    override: true
                };

                this.tabs = new Tabs(tabsElement, tabElements, options, instanceOptions);
            }
        },
        openModal(gameUrl) {
            window.open(gameUrl);
        },

        getGame: async function() {
            const _this = this;

            return await HttpApi.get('games/single/' + _this.gameId)
                .then(async response =>  {
                    if (response.data?.action === 'deposit') {
                        _this.$nextTick(() => {
                            _this.router.push({ name: 'profileDeposit' });
                        });
                    }

                    const game = response.data.game;
                    _this.game = game;

                    _this.gameUrl = response.data.gameUrl;
                    _this.token = response.data.token;
                    _this.isLoading = false;

                    _this.gameSpecificCondition = (_this.game.id.toString() === '12174');
                    if (response.data.refresh) {
                        window.location.reload();
                        
                    }
                    _this.$nextTick(() => {
                        _this.loadingTab();
                    });
                })
                .catch(error => {
                    _this.isLoading = false;
                    _this.undermaintenance = true;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                });
        },
        toggleFavorite: function() {
            const _this = this;
            return HttpApi.post('games/favorite/'+ _this.game.id, {})
                .then(response =>  {
                    _this.getGame();
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.isLoading = false;
                });
        },
        toggleLike: async function() {
            const _this = this;
            return await HttpApi.post('games/like/'+ _this.game.id, {})
                .then(async response =>  {
                    await _this.getGame();
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.isLoading = false;
                });
        }
    },
    async created() {
        if(this.isAuthenticated) {
            const route = useRoute();
            this.gameId = route.params.id;

            await this.getGame();
        } else {
            this.router.push({ name: 'login', params: { action: 'openlogin' } });
        }
    },
    watch: {

    },
};
</script>

<style>
.game-container {
    max-width: 100%; /* Garante que o container ocupe toda a largura */
    height: 100vh; /* Altura da tela inteira */
}

.game-screen {
    margin-top: 70px; /* Ajuste a margem superior para criar espaço acima do iframe */
    width: 100%;
    height: calc(100vh - 70px); /* Ajusta a altura para ocupar a altura total da janela menos a margem superior */
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    overflow: hidden; /* Garante que o conteúdo não vaze */
}

.game-full {
    width: 100%;
    height: 100%; /* Altura total do container pai */
    border: none; /* Remove qualquer borda */
}

.fullscreen-wrapper {
    position: absolute; /* Ajustado para evitar problemas de corte */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Media query para ajustar o tamanho no PC */
@media (min-width: 1024px) {
    .game-screen {
        height: calc(100vh - 70px); /* Altura total da janela menos a margem superior */
    }

    .game-full {
        height: 100%; /* Altura total do container pai */
    }
}

.fut-screen{
    margin-top: 50px;
    width: 100%;
    height: calc(100vh - 115px); /* Ajusta a altura para ocupar a altura total da janela menos a margem superior */
}

.fut-screen .fut-full{
    width: 100%;
    height: calc(100vh - 115px); /* Ajusta a altura para ocupar a altura total da janela menos a margem superior */
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}
.fut-footer{
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}

@media (min-width: 1024px) {
    .fut-screen {
        height: calc(100vh - 70px); /* Altura total da janela menos a margem superior */
    }

    .fut-full {
        height: 100%; /* Altura total do container pai */
    }
}
</style>