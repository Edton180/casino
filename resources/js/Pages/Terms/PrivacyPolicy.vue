<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="hidden md:block">
                    <img :src="`/assets/images/terms-conditions.png`" alt="" class="w-full">
                </div>
                <div class="">
                    <h4 class="text-2xl">Política de Privacidade</h4>
                    <br>
                    <p>1. Ao se inscrever no {{ setting.software_name }}, você concorda em estar vinculado por esta Política de Privacidade e aceita o uso e a divulgação de suas informações pessoais conforme descrito aqui.</p>
                    <br>
                    <p>2. Ao se inscrever, você concorda em receber publicidade, incluindo mensagens via WhatsApp, e-mails, SMS e ligações da nossa plataforma. Você pode optar por não receber essas comunicações a qualquer momento, seguindo as instruções de cancelamento de inscrição fornecidas em cada comunicação.</p>
                    <br>
                    <p>3. Comprometemo-nos a proteger suas informações pessoais e a utilizá-las apenas para fins internos ou em casos legais, conforme permitido pela legislação aplicável.</p>
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
