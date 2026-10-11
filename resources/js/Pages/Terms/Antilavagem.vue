<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Política Anti Lavagem de Dinheiro</h4>
                    <br>
                    <p>Aqui na nossa casa de jogos, levamos a sério a prevenção da lavagem de dinheiro e estamos comprometidos em garantir a integridade e a segurança de nossos serviços. Para isso, adotamos as seguintes medidas:</p>
                    <br>
                    <h5 class="text-lg font-bold">1. Saques Exclusivos:</h5>
                    <p>Somente permitimos o saque dos valores depositados para o CPF do titular da conta. Isso significa que não é permitido realizar saques para terceiros ou contas de terceiros.</p>
                    <br>
                    <h5 class="text-lg font-bold">2. Monitoramento de Transações:</h5>
                    <p>Realizamos um monitoramento constante das transações realizadas em nossa plataforma para identificar qualquer atividade suspeita que possa indicar lavagem de dinheiro.</p>
                    <br>
                    <h5 class="text-lg font-bold">3. Colaboração com Autoridades:</h5>
                    <p>Colaboramos ativamente com autoridades regulatórias e agências de aplicação da lei para combater a lavagem de dinheiro e outras atividades ilegais.</p>
                    <br>
                    <p>Nosso compromisso é manter um ambiente seguro e transparente para todos os nossos usuários, e isso inclui a implementação de medidas rigorosas de prevenção à lavagem de dinheiro. Agradecemos a cooperação de todos os nossos jogadores para garantir a integridade de nossa plataforma.</p>
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
