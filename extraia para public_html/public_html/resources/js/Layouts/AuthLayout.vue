<template>


    <div class="w-full fundo">
        <div class="relative w-full fundo">
            <slot></slot>

        </div>
    </div>
</template>

<script>
import { initFlowbite } from 'flowbite';
import { onMounted } from "vue";
import NavTopComponent from "@/Components/Nav/NavTopComponent.vue";
import SideBarComponent from "@/Components/Nav/SideBarComponent.vue";
import FooterComponent from "@/Components/UI/FooterComponent.vue";
import BottomNavComponent from "@/Components/Nav/BottomNavComponent.vue";
import CookiesComponent from "@/Components/UI/CookiesComponent.vue";

export default {
    props: ['simple'],
    components: {CookiesComponent, BottomNavComponent, FooterComponent, SideBarComponent, NavTopComponent },
    data() {
        return {
            logo: '/assets/images/logo_verde.png',
            isLoading: false,
        }
    },
    setup(props) {
        onMounted(() => {
            initFlowbite();
        });

        return {};
    },
    computed: {

    },
    mounted() {


        // setTimeout(() => {
        //     this.isLoading = false
        // }, 3000)
    },
    methods: {

    },
    watch: {

    },
};
</script>
<style scoped>
.fundo {
    
    background-color: #1d1d1d !important;

}
</style>