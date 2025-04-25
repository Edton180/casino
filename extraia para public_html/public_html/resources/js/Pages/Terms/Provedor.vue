<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Qualidade e Segurança Garantidas</h4>
                    <br>
                    <p>Aqui na nossa casa, priorizamos a qualidade e segurança dos nossos jogadores. Utilizamos exclusivamente jogos originais dos maiores provedores do mercado, tais como Playson, Habanero, Evoplay, Pragmatic Play, BetGames, Evolution, PGSoft e Spribe. Isso garante uma experiência de jogo imersiva e confiável para todos os nossos usuários.</p>
                    <br>
                    <p>Nossa seleção cuidadosa de provedores de jogos nos permite oferecer uma ampla variedade de opções de entretenimento, desde slots emocionantes até jogos de mesa ao vivo, todos projetados para proporcionar diversão e emoção aos nossos jogadores.</p>
                    <br>
                    <p>Além disso, estamos comprometidos em garantir que todos os jogos sejam justos e imparciais. Isso significa que os resultados de cada jogo, sejam ganhos ou perdas, são decididos de forma completamente aleatória e independente, proporcionando uma experiência de jogo transparente e confiável para todos.</p>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>


<script>

import BaseLayout from "@/Layouts/BaseLayout.vue";
import {useSettingStore} from "@/Stores/SettingStore.js";

export default {
    props: [],
    components: {BaseLayout},
    data() {
        return {
            isLoading: false,
            setting: null,
        }
    },
    setup(props) {


        return {};
    },
    computed: {

    },
    mounted() {

    },
    methods: {
        getSetting: function() {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;

            if(settingData) {
                _this.setting = settingData;
            }
        },
    },
    created() {
        this.getSetting();
    },
    watch: {

    },
};
</script>

<style scoped>

</style>
