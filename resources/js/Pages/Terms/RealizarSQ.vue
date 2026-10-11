<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
               <div class="hidden md:block">
                    <div class="w-full">
                        <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                    </div>
                </div>
                <div class="">
                    <h4 class="text-2xl">Saque Mínimo e Máximo</h4>
                    <br>
                    <h5 class="text-lg font-bold">Saque Mínimo:</h5>
                    <p>O valor mínimo para saque é de <span class="highlighted-variable">R$: 30.00</span>.</p>
                    <br>
                    <h5 class="text-lg font-bold">Saque Máximo:</h5>
                    <p>O valor máximo para saque é de <span class="highlighted-variable">R$: 5000.00</span> por saque.</p>
                    <br>
                    <h5 class="text-lg font-bold">Quantidade de Saques:</h5>
                    <p>É permitido realizar <span class="highlighted-variable">1 saque a cada 23 horas</span>, respeitando o limite máximo de saque diário de <span class="highlighted-variable">R$5.216,35</span>.</p>
                    <br>
                    <h5 class="text-lg font-bold">Processamento de Saques:</h5>
                    <p>Os saques PIX são processados preferencialmente de forma imediata, com limite máximo de 24 horas para pagamento.</p>
                    <br>
                    <h5 class="text-lg font-bold">Cancelamento de Bônus/Giros-Grátis:</h5>
                    <p>Ao efetuar o saque, todos os seus Bônus/Giros-Grátis serão automaticamente cancelados.</p>
                    <br>
                    <button class="yellow-button">Fechar</button>
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
