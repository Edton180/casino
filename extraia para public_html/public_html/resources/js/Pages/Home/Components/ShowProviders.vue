<template>
    <div :key="index" class="game-list flex flex-col mt-5 relative">
        <div class="w-full flex justify-between mb-1">
            <h2 class="text-xl font-bold ml-3">{{ $t(provider.name) }}</h2>
            <div class="flex">
                <RouterLink
                    :to="{ name: 'casinosAll', params: { provider: provider.id, category: 'all' } }"
                    class="botaovertodos">
                    Todos
                </RouterLink>
            </div>
        </div>

        <Carousel ref="ckCarousel"
                  v-bind="settingsGames"
                  :breakpoints="breakpointsGames"
                  @init="onCarouselInit(index)"
                  @slide-start="onSlideStart(index)"
        >
            <Slide v-if="isLoading" v-for="(i, iloading) in 10" :index="iloading">
                <div  role="status" class="w-full flex items-center justify-center h-48 mr-6 max-w-sm bg-gray-300 rounded-lg animate-pulse dark:bg-gray-700 text-4xl">
                    <i class="fa-duotone fa-gamepad-modern"></i>
                </div>
            </Slide>

            <Slide v-if="provider.games && !isLoading" v-for="(game, providerId) in provider.games" :index="providerId" class="p-2">
                <CassinoGameCard
                    :index="providerId"
                    :title="game.game_name"
                    :cover="game.cover"
                    :gamecode="game.game_code"
                    :type="game.distribution"
                    :game="game"
                />
            </Slide>
        </Carousel>
                            <div class="carousel-shadow"></div>

    </div>
</template>

<script>

import { Carousel, Navigation, Slide } from 'vue3-carousel';
import {onMounted, ref} from "vue";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
    props: ['provider', 'index'],
    components: {CassinoGameCard, Carousel, Navigation, Slide },
    data() {
        return {
            isLoading: false,
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
                    itemsToShow: 6,
                    snapAlign: 'start',
                },
            },
        }
    },
    setup(props) {
        const ckCarousel = ref(null)

        onMounted(() => {

        });

        return {
            ckCarousel
        };
    },
    computed: {

    },
    mounted() {

    },
    methods: {
        onCarouselInit(index) {

        },
        onSlideStart(index) {

        },
    },
    watch: {

    },
};
</script>



<style scoped>
.provider-card {
    width: 150px;
    height: 100px;
}



.carousel-shadow {
    position: absolute;
    top: 33px;
    right: -5px;
    width: 100px; /* Ajuste conforme necessário */
    height: 80%;
    background: linear-gradient(to left, rgba(0, 0, 0, 0.5), transparent);
    pointer-events: none; /* Garantir que a sombra não interfira com a navegação do carrossel */
}
</style>