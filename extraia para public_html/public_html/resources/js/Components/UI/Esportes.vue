<template>
    <button onclick="window.location.href='https://gugabet.com/games/play/12174/8357'" type="button" :class="[showMobile === false ? 'hidden md:block' : '', isFull ? 'w-full' : '']" class="ui-button-blue mr-3 rounded">
        Esportes
    </button>

</template>

<script>
    import {useAuthStore} from "@/Stores/Auth.js";
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
            isAuthenticated() {
                const authStore = useAuthStore();
                return authStore.isAuth;
            },
        },
        mounted() {
            this.modalDeposit = new Modal(document.getElementById('modalElDeposit'), {
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
