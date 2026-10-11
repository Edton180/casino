<template>
    <BaseLayout>
        <div class="w-full p-4">
            <div class="grid grid-cols-1 md:grid-cols-2">
             
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
