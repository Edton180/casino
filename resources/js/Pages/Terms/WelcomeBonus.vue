<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
<h4 class="text-2xl">Bônus e Giros Grátis</h4>
<br>
<p>1. Ao participar de qualquer promoção de bônus ou receber giros grátis no {{ setting.software_name }}, você concorda em estar vinculado por estes Termos e Condições, bem como por quaisquer termos específicos da promoção. É sua responsabilidade ler e compreender os Termos e Condições de cada promoção antes de participar.</p>
<br>
<p>2. Os giros grátis concedidos como parte de uma promoção têm um valor de R$0,40 (quarenta centavos de real) cada. Esses giros grátis podem ser usados para somar o valor total da aposta em jogos específicos ou assim que o saldo real acabar.</p>
<br>
<p>3. O saldo ganho com giros grátis é considerado um bônus e está sujeito aos requisitos de apostas antes que possa ser sacado. Consulte os termos específicos da promoção para conhecer os requisitos de apostas aplicáveis.</p>
<br>
<p>4. Os giros grátis não são sacáveis e serão removidos da conta após a expiração da promoção ou ao efetuar um saque.</p>
<br>
<p>5. Ao solicitar um saque, todos os giros grátis pendentes na conta serão cancelados.</p>


                    

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
