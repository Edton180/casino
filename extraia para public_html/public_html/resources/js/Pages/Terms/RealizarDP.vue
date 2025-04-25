<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
               <div class="hidden md:block">
                <div class="">
                    <h4 class="text-2xl">Realizar Depósito</h4>
                    <br>
                    <p>1. Clique no botão "Depósito" localizado na sua conta do {{ setting.software_name }}.</p>
                    <br>
                    <p>2. Selecione o valor que deseja depositar.</p>
                    <br>
                    <p>3. Prossiga para o pagamento. Após concluir o pagamento, o valor do depósito será adicionado instantaneamente ao seu saldo disponível. Isso inclui qualquer bônus ou giros grátis associados ao depósito, que serão creditados automaticamente assim que o gateway de pagamento confirmar a transação.</p>
                </div>
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
