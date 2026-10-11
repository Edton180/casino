<template>
    <button @click.prevent="toggleModalDeposit" type="button" :class="[showMobile === false ? 'hidden md:block' : '', isFull ? 'w-full' : '']" class="ui-button-custom-deposito  rounded">
        Depositar
    </button>

    <div v-if="!sumirmodal" id="modalElDeposit" tabindex="-1" aria-hidden="true" class="fixed  top-0 left-0 right-0 z-50 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-2xl max-h-full dark:bg-[#17181b] ">
            <div class="flex flex-col md:justify-between px-6 pb-8 my-auto">
                <div class="flex justify-between modal-header mb-6 mt-6">
                    <div>
                        <h1 class="font-bold text-xl" style="color: #01ec01">Depositar</h1>
                    </div>
                    
                        <div>
                    <button @click.prevent="toggleModalDeposit" class="text-red-500 hover:text-red-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    </div>
                </div>

                <DepositWidget />

            </div>
        </div>
    </div>

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
