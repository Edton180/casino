<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Apostas Responsáveis: Jogue com Cuidado</h4>
                    <br>
                    <p>Estamos comprometidos em promover práticas de jogo responsável e queremos garantir que sua experiência em nossa plataforma seja segura e agradável. Aqui estão algumas dicas para jogar com responsabilidade:</p>
                    <br>
                    <h5 class="text-lg font-bold">Defina Limites Financeiros:</h5>
                    <ul class="list-disc ml-6">
                        <li>Nunca comprometa dinheiro de contas essenciais, como contas de aluguel, hipoteca ou alimentação.</li>
                        <li>Estabeleça um orçamento claro e não exceda o valor que você pode se dar ao luxo de perder.</li>
                    </ul>
                    <br>
                    <h5 class="text-lg font-bold">Gerencie o Tempo de Jogo:</h5>
                    <ul class="list-disc ml-6">
                        <li>Defina limites de tempo para suas sessões de jogo e faça pausas regulares.</li>
                        <li>Evite jogar quando estiver cansado, estressado ou emocionalmente instável.</li>
                    </ul>
                    <br>
                    <h5 class="text-lg font-bold">Conheça seus Limites:</h5>
                    <ul class="list-disc ml-6">
                        <li>Reconheça os sinais de vício em jogos de azar e busque ajuda se necessário.</li>
                        <li>Não persiga perdas ou tente recuperar dinheiro perdido com mais apostas.</li>
                    </ul>
                    <br>
                    <p>Lembre-se sempre de que o jogo é apenas uma forma de entretenimento e nunca deve interferir em sua vida pessoal, financeira ou profissional. Se você sentir que está perdendo o controle ou precisa de suporte, não hesite em entrar em contato com organizações de ajuda especializadas.</p>
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




