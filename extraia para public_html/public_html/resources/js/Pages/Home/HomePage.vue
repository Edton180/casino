<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading data from the platform') }}</span>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading" class="">

            <!-- Banners carousel -->
            <div class="carousel-banners">
                <div class="md:w-6/6 2xl:w-5/6 mx-auto p-4">
                    <div class="mb-5">
                        <Carousel v-bind="settings" :breakpoints="breakpoints" ref="carouselBanner">
                            <Slide v-for="(banner, index) in banners" :key="index">
                                <div class="carousel__item rounded w-full">
                                    <a :href="banner.link" class="w-full h-full bg-blue-800 rounded">
                                        <img :src="`/storage/`+banner.image" alt="" class="h-[80%] w-full rounded">
                                    </a>
                                </div>
                            </Slide>


                        </Carousel>
                    </div>

                    <div class="">
                        <Carousel v-bind="settingsRecommended" :breakpoints="breakpointsRecommended" ref="carouselSubBanner">
                            <Slide v-for="(banner, index) in bannersHome" :key="index">
                                <div class="carousel__item  min-h-[60px] md:min-h-[150px] rounded w-full mr-4">
                                    <a :href="banner.link" class="w-full h-full rounded">
                                        <img :src="`/storage/`+banner.image" alt="" class="h-full w-full rounded">
                                    </a>
                                </div>
                            </Slide>

                            <template #addons>
                                <Pagination />
                            </template>
                        </Carousel>
                    </div>
                </div>
            </div>

            <div class="md:w-6/6 2xl:w-5/6 mx-auto p-4">
                <!-- Searchbar action -->
                <div class="mb-5 cursor-pointer w-full">
                    <div class="flex">
                        <div class="relative w-full">
                            <input @click.prevent="toggleSearch" type="search"
                                   readonly
                                   class="block dark:focus:border-green-500 p-2.5 w-full z-20 text-sm text-gray-900 rounded-e-lg input-color-primary border-none focus:outline-none dark:border-s-gray-800  dark:border-gray-800 dark:placeholder-gray-400 dark:text-white "
                                   placeholder="Pesquise Um Jogo" required>

                            <button type="submit" class="absolute top-0 end-0 h-full p-2.5 text-sm font-medium text-white rounded-e-lg
                                 dark:bg-[#1C1E22] ">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                                <span class="sr-only">Search</span>
                            </button>
                            
                            
                        </div>
                        
                    </div>






                </div>






<div v-if="!pc">

                <!-- categories -->
<div v-if="categories" class="category-list">
                <div class="flex mb-5 gap-4" style="max-height: 160px; overflow-x: auto; overflow-y: hidden;">
                    <div class="flex flex-row justify-between items-center w-full" style="min-width: 100%; white-space: nowrap;">
                        <!-- Loop sobre as categorias -->
                        <template v-for="(category, index) in categories" :key="index">
                            <!-- Verifique se a categoria é a específica -->
                            <a v-if="category.slug === 'Esportes'"
           :href="setting.football_link"
                               class="flex flex-col justify-center items-center min-w-[80px] text-center">
                                <div class="category-img">
                                    <img :src="`/storage/` + category.image" alt="" width="27" style="border-radius: 8px !important;">
                                </div>
                                <p class="mt-3">{{ $t(category.name) }}</p>
                            </a>

                            <!-- Caso contrário, use RouterLink como de costume -->
                            <RouterLink v-else
                                        :to="{ name: 'casinosAll', params: { provider: 'all', category: category.slug }}"
                                        class="flex flex-col justify-center items-center min-w-[80px] text-center">
                                <div class="category-img">
                                    <img :src="`/storage/` + category.image" alt="" width="27" style="border-radius: 8px !important;">
                                </div>
                                <p class="mt-3">{{ $t(category.name) }}</p>
                            </RouterLink>
                        </template>
                    </div>
                </div>
            </div>

                        <div v-if="setting && setting.cassinowinner" >
                        <CasinoWinner :showMobile="true" />
                        </div>
</div>

                <div class="mt-5">
                    <ShowProviders v-for="(provider, index) in providers" :provider="provider" :index="index" />
                </div>


            </div>
        </div>
    </BaseLayout>
</template>

<script>
import {Carousel, Navigation, Pagination, Slide} from 'vue3-carousel';
import {onMounted, ref} from "vue";
import CasinoWinner from "@/Components/UI/CasinoWinner.vue";

import BaseLayout from "@/Layouts/BaseLayout.vue";
import MakeDeposit from "@/Components/UI/MakeDeposit.vue";
import {RouterLink, useRoute} from "vue-router";
import {useAuthStore} from "@/Stores/Auth.js";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";
import HttpApi from "@/Services/HttpApi.js";
import ShowCarousel from "@/Pages/Home/Components/ShowCarousel.vue";
import {useSettingStore} from "@/Stores/SettingStore.js";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import ShowProviders from "@/Pages/Home/Components/ShowProviders.vue";
import {searchGameStore} from "@/Stores/SearchGameStore.js";
import CustomPagination from "@/Components/UI/CustomPagination.vue";
const CACHE_VERSION = '1.2'; // Atualize esta versão para forçar a limpeza do cache
const CACHE_EXPIRATION_TIME = 24 * 60 * 60 * 1000; // 24 horas em milissegundos

function setCache(key, data) {
    const cacheEntry = {
        data,
        timestamp: Date.now(),
        version: CACHE_VERSION
    };
    localStorage.setItem(key, JSON.stringify(cacheEntry));
}

function getCache(key) {
    const cacheEntry = JSON.parse(localStorage.getItem(key));
    if (cacheEntry) {
        const isExpired = (Date.now() - cacheEntry.timestamp) > CACHE_EXPIRATION_TIME;
        const isVersionMismatch = cacheEntry.version !== CACHE_VERSION;
        if (isExpired || isVersionMismatch) {
            localStorage.removeItem(key);
            return null;
        }
        return cacheEntry.data;
    }
    return null;
}


export default {
    props: [],
    components: {
        CustomPagination,
        Pagination,
        ShowProviders,
        LoadingComponent,
        ShowCarousel,
        CassinoGameCard,
        Carousel,
        Navigation,
        Slide,
        LanguageSelector,
        MakeDeposit,
        BaseLayout,
        RouterLink,
        CasinoWinner
    },
    data() {
        return {
            isLoading: true,

            /// banners settings
            settings: {
                itemsToShow: 1,
                snapAlign: 'center',
                autoplay: 6000,
                wrapAround: true
            },
            breakpoints: {
                700: {
                    itemsToShow: 1,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 1,
                    snapAlign: 'center',
                },
            },

            settingsRecommended: {
                itemsToShow: 2,
                snapAlign: 'start',
            },
            breakpointsRecommended: {
                700: {
                    itemsToShow: 3,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 3,
                    snapAlign: 'start',
                },
            },

            /// banners
            banners: null,
            bannersHome: null,

            settingsGames: {
                itemsToShow: 2.5,
                snapAlign: 'start',
            },
            breakpointsGames: {
                700: {
                    itemsToShow: 3.5,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 7,
                    snapAlign: 'start',
                },
            },
            providers: null,

            featured_games: null,
            categories: null,
            pc: false,
        }
    },
    setup(props) {
        const ckCarouselOriginals = ref(null)

        onMounted(() => {

        });

        return {
            ckCarouselOriginals
        };
    },
    computed: {
        searchGameStore() {
            return searchGameStore();
        },
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        setting() {
            const settingStore = useSettingStore();
            return settingStore.setting;
        }
    },
async mounted() {
    await this.initializeMethods();

},
    methods: {
    checkScreenWidth() {
      this.pc = window.innerWidth > 1024;
    },

getCasinoCategories: async function() {
    const _this = this;
    const cachedCategories = getCache('categories');
    if (cachedCategories) {
        _this.categories = cachedCategories;
    } else {
        try {
            const response = await HttpApi.get('categories');
            _this.categories = response.data.categories;
            setCache('categories', _this.categories);
        } catch (error) {
            console.error(error);
        }
    }
},

toggleSearch: function() {
    this.searchGameStore.setSearchGameToogle();
},

getBanners: async function() {
    const _this = this;
    const cachedBanners = getCache('banners');
    const cachedBannersHome = getCache('bannersHome');
    if (cachedBanners && cachedBannersHome) {
        _this.banners = cachedBanners;
        _this.bannersHome = cachedBannersHome;
    } else {
        try {
            const response = await HttpApi.get('settings/banners');
            const allBanners = response.data.banners;

            _this.banners = allBanners.filter(banner => banner.type === 'carousel');
            _this.bannersHome = allBanners.filter(banner => banner.type === 'home');

            setCache('banners', _this.banners);
            setCache('bannersHome', _this.bannersHome);
        } catch (error) {
            console.error(error);
        }
    }
},
getAllGames: async function() {
    const _this = this;

    // Verifica se há dados armazenados no localStorage
    const cachedProviders = localStorage.getItem('providers');
    if (cachedProviders) {
        _this.providers = JSON.parse(cachedProviders);
        _this.isLoading = false;
    } else {
        try {
            const response = await HttpApi.get('games/all');
            if (response.data !== undefined) {
                _this.providers = response.data.providers;
                
                // Armazena os dados no localStorage
                localStorage.setItem('providers', JSON.stringify(_this.providers));
                _this.isLoading = false;
            }
        } catch (error) {
            console.error(error);
            _this.isLoading = false;
        }
    }
},

getFeaturedGames: async function() {
    const _this = this;

    // Verifica se há dados armazenados no localStorage
    const cachedFeaturedGames = localStorage.getItem('featured_games');
    if (cachedFeaturedGames) {
        _this.featured_games = JSON.parse(cachedFeaturedGames);
        _this.isLoading = false;
    } else {
        try {
            const response = await HttpApi.get('featured/games');
            if (response.data !== undefined) {
                _this.featured_games = response.data.featured_games;
                
                // Armazena os dados no localStorage
                localStorage.setItem('featured_games', JSON.stringify(_this.featured_games));
                _this.isLoading = false;
            }
        } catch (error) {
            console.error(error);
            _this.isLoading = false;
        }
    }
},

        initializeMethods: async function() {
            await this.getCasinoCategories();
            await this.getBanners();
            await this.getAllGames();
            await this.getFeaturedGames();
        }

    },
    async created() {
        await this.initializeMethods();
            this.checkScreenWidth();
    window.addEventListener('resize', this.checkScreenWidth);
    },
    watch: {


    },
};
</script>
<style scoped>
.container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    position: relative;
}

.trophy-container {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.trophy-icon {
    width: 100px;
    height: 100px;
}

.swiper-container {
    width: 100%;
    height: 100px;
    display: flex;
    align-items: center;
}

.swiper-wrapper {
    display: flex;
}

.swiper-slide {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 300px;
    height: 100px;
    background-color: #f0f0f0;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transform: translateX(100%);
    transition: transform 1s;
}

.casino-winner {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
}

.casino-winner__img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 10px;
}

.casino-winner__info {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.casino-winner__game {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
}

.casino-winner__title {
    font-size: 12px;
    margin-bottom: 5px;
}

.casino-winner__text {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.casino-winner-text__inner {
    font-size: 10px;
}


.category-link {
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  align-items: center !important;
  min-width: 80px !important;
  text-align: center !important;
  text-decoration: none !important;
  margin-right: 10px !important; /* Diminuir o espaço lateral entre os elementos */
}

.category-img {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  width: 60px !important;  /* Aumenta o tamanho do quadrado */
  height: 60px !important; /* Aumenta o tamanho do quadrado */
  border: 1px solid var(--ci-primary-color) !important; /* Cor do contorno */
  border-radius: 12px !important; /* Bordas arredondadas */
  padding: 5px !important;
  box-sizing: border-box !important;
  background-color: transparent !important; /* Remove o fundo verde */
  margin-bottom: -10px !important;

  
}

.category-img img {
  width: 30px !important; /* Define o tamanho do ícone */
  height: 30px !important; /* Define o tamanho do ícone */
  border-radius: 0 !important; /* Remove o arredondamento da imagem */
}

.category-link p {
  margin-top: 10px !important;
  font-size: 14px !important;
  color: #000 !important; /* Cor do texto */
}
</style>