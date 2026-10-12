<template>
    <BaseLayout>
        <div class="w-full max-w-2xl mx-auto p-4">
            <h1 class="text-xl font-bold mb-4">Depositar</h1>
            <DepositWidget />
        </div>
    </BaseLayout>
</template>



<script>

import { RouterLink } from "vue-router";
import BaseLayout from "@/Layouts/BaseLayout2.vue";
import DepositWidget from "@/Components/Widgets/DepositWidget.vue";
import WalletSideMenu from "@/Pages/Profile/Components/WalletSideMenu.vue";
import {useAuthStore} from "@/Stores/Auth.js";

export default {
    props: [],
    components: {WalletSideMenu, DepositWidget, BaseLayout, RouterLink },
    data() {
        return {
            isLoading: false,

        }
    },
    setup(props) {


        return {};
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    mounted() {

    },
    methods: {

    },
    created() {

    },
    watch: {

    },
};
</script>

<style scoped>

</style>
