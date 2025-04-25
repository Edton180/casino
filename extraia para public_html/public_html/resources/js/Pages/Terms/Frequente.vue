<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Perguntas Frequentes</h4>
                    <br>
                    <h5 class="text-lg font-bold">1. Quais são os métodos de pagamento disponíveis?</h5>
                    <p>Atualmente, oferecemos os seguintes métodos de pagamento: cartão de crédito, transferência bancária e PIX.</p>
                    <br>
                    <h5 class="text-lg font-bold">2. Como posso solicitar um saque?</h5>
                    <p>Para solicitar um saque, basta acessar a sua conta, selecionar a opção de saque e seguir as instruções fornecidas.</p>
                    <br>
                    <h5 class="text-lg font-bold">3. Qual é o prazo de processamento para saques?</h5>
                    <p>O prazo de processamento para saques pode variar dependendo do método de pagamento escolhido. Geralmente, os saques são processados de forma instantanea porêm em casos extraordinarios o prazo vale de 1 a 3 dias úteis.</p>
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
