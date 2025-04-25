<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Termos de Afiliação (Indique e Ganhe)</h4>
                    <br>
                    <p>1. Ao participar do programa de afiliação (Indique e Ganhe) do {{ setting.software_name }}, você concorda em estar vinculado por estes Termos e Condições.</p>
                    <br>
                    <p>2. Ao indicar uma pessoa para o {{ setting.software_name }}, você garante que essa pessoa seja real e não esteja usando métodos fraudulentos para burlar o sistema de indicação, como o uso de robôs, proxies, contas falsas, ou qualquer outra forma de manipulação.</p>
                    <br>
                    <p>3. Qualquer tentativa de burlar o sistema de indicação resultará na anulação das comissões geradas e poderá resultar em outras medidas disciplinares, conforme determinado pela nossa plataforma.</p>
                    <br>
                    <p>4. O valor da comissão, o depósito mínimo e as condições para validar a comissão estão disponíveis na página do programa Indique e Ganhe. Certifique-se de revisar essas informações antes de participar do programa.</p>
                    <br>
                    <p>5. Você entende que o saldo de comissão gerado pelo programa de afiliação tem um limite mínimo de saque de R$50,00 (cinquenta reais). Você só poderá solicitar o saque quando o saldo de comissão atingir ou exceder esse valor.</p>
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
