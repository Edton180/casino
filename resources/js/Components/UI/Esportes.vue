<template>
    <a v-if="sportsLink" :href="sportsLink" :class="[showMobile === false ? 'hidden md:block' : '', isFull ? 'w-full' : '']" class="ui-button-blue mr-3 rounded text-center">
        Esportes
    </a>

</template>

<script>
import { lazyModal } from '@/Services/LazyModal.js';
    import {useAuthStore} from "@/Stores/Auth.js";
    import {useSettingStore} from "@/Stores/SettingStore.js";
    import DepositWidget from "@/Components/Widgets/DepositWidget.vue";
    import {onMounted} from "vue";
    import {initFlowbite} from "flowbite";

    export default {
        props: ['showMobile', 'title', 'isFull'],
        components: { DepositWidget },
        data() {
            return {
                isLoading: false,
                modalDeposit: null,
                sumirmodal: false,
            }
        },
        setup(props) {
            onMounted(() => {
                initFlowbite();
            });

            return {};
        },
        computed: {
            // aparece só quando o link de esportes estiver configurado e ligado no painel
            sportsLink() {
                const setting = useSettingStore().setting;
                return setting && setting.turn_on_football && setting.football_link ? setting.football_link : null;
            },
            isAuthenticated() {
                const authStore = useAuthStore();
                return authStore.isAuth;
            },
        },
        mounted() {
            this.modalDeposit = lazyModal('modalElDeposit', {
                placement: 'center',
                backdrop: 'dynamic',
                backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
                closable: true,
                onHide: () => {
                    this.paymentType = null;
                },
                onShow: () => {

                },
                onToggle: () => {

                },
            });
        },
        beforeUnmount() {

        },
        methods: {
            toggleModalDeposit: function() {
                this.modalDeposit.toggle();
            },
        },
        created() {

        },
        watch: {

        },
    };
</script>

<style scoped>

</style>
