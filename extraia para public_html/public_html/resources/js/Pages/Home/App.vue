<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading events') }}</span>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading && setting && setting.turn_on_aplicativo" class="md:w-4/6 2xl:w-4/6 mx-auto my-16 p-4">




            <div>
                            <img :href="setting.aplicativo_link" :src="`/storage/`+setting.software_aplicativo" class="rounded bonus-icon w-full mt-1" alt="Bonus Icon"> <!-- Espaço ajustado abaixo da linha -->
            </div>
              <h1 class="title mt-2">SUPER APP</h1>
              <p class="description text-white">
                Baixe nosso APP android e participe das promoções:<br>
                <br>
                👉 Banca Surpresa<br>
                <br>
                👉 Giros-Grátis<br>
                <br>
                👉 Dias com Ganhos 2X e 3X ATIVOS!<br>
                <br>
                👉 CASHBACK MAIOR!<br>
                <br>
                👉 E MUITO MAIS SURPRESAS !!<br>
        
              </p>
            <div>
                              <h1 class="title mt-2 text-center">Click e Baixe:</h1>

                <a :href="setting.aplicativo_link" target="_blank">
                  <img :src="`/googleplay.png`" class="rounded bonus-icon mt-2" alt="Bonus Icon">
                </a>
                            
            </div>
        </div>
        
    </BaseLayout>
</template>


<script>

import BaseLayout from "@/Layouts/BaseLayout.vue";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import HeaderComponent from "@/Components/UI/HeaderComponent.vue";
import HttpApi from "@/Services/HttpApi.js";
import {useSettingStore} from "@/Stores/SettingStore.js";

export default {
    props: [],
    components: {HeaderComponent, LoadingComponent, BaseLayout },
    data() {
        return {
            isLoading: false,
            games: null,
        }
    },
    setup(props) {


        return {};
    },
    computed: {
        setting() {
            const authStore = useSettingStore();
            return authStore.setting;
        }
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

.text-container {
  margin-top: 1rem;
}

.title {
  font-size: 1.75rem;
  font-weight: bold;
  margin-bottom: 1rem;
  color: var(--ci-primary-color);
}

.description {
  font-size: 1.25rem;
  line-height: 1.5;
}
.header-container {
      display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
}

.bonus-text {
  display: inline-block;
  background-color: #ebf8ff; /* bg-blue-100 */
  color: #2c5282; /* text-blue-800 */
  font-size: 1.5rem; /* text-2xl */
  font-weight: 600; /* font-semibold */
  margin-inline-end: 0.5rem; /* me-2 */
  padding: 0.625rem 0.625rem; /* px-2.5 py-0.5 */
  border-radius: 0.375rem; /* rounded */
}
</style>
