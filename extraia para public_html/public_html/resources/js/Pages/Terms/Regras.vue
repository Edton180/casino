<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Regras da Plataforma</h4>
                    <br>
                    <p>Para garantir a integridade e a equidade de nossos serviços, é importante que todos os usuários respeitem as seguintes regras:</p>
                    <br>
                    <h5 class="text-lg font-bold">1. Uso Justo:</h5>
                    <ul class="list-disc ml-6">
                        <li>Não é permitido utilizar programas, aplicativos, bugs ou qualquer outro meio para obter vantagens ilícitas.</li>
                        <li>Qualquer tentativa de violação dessas regras será detectada e pode resultar na exclusão do cadastro, sem direito a reembolso.</li>
                    </ul>
                    <br>
                    <h5 class="text-lg font-bold">2. Respeito às Normas:</h5>
                    <ul class="list-disc ml-6">
                        <li>Os usuários devem cumprir todas as políticas e regulamentos estabelecidos pela plataforma.</li>
                        <li>Comportamentos inadequados, como assédio, discriminação ou qualquer forma de violação dos direitos dos outros usuários, não serão tolerados.</li>
                    </ul>
                    <br>
                    <h5 class="text-lg font-bold">3. Colaboração:</h5>
                    <ul class="list-disc ml-6">
                        <li>Os usuários são incentivados a relatar qualquer atividade suspeita ou violação das regras para garantir um ambiente seguro e justo para todos.</li>
                        <li>A colaboração de todos é essencial para manter a integridade da plataforma.</li>
                    </ul>
                    <br>
                    <p>Lembramos que o objetivo principal da nossa plataforma é proporcionar uma experiência de jogo segura, justa e divertida para todos os usuários. Contamos com sua cooperação para manter esses padrões elevados e garantir que todos possam desfrutar ao máximo de nossa plataforma.</p>
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