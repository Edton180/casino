<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="text-center">
                <span>{{ $t('Loading events') }}</span>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading" class="md:w-4/6 2xl:w-4/6 mx-auto my-16 p-4">




            <div>
                            <img @click.prevent="$router.push('giros')" :src="`/storage/`+setting.software_login" class="rounded bonus-icon w-full mt-2" alt="Bonus Icon"> <!-- Espaço ajustado abaixo da linha -->
            </div>
      <h1 class="title mt-2">CASHBACK DIARIO</h1>
      <p class="description">
        Slots e betting games.<br>
        <br>
        Jogue em seus slots de cassino e betting games favoritos durante o dia, e receba até 10% de reembolso em dinheiro real no dia seguinte.<br>
        <br>
        👉 Mínimo de cash back - R$0,01 por dia,<br>
        <br>
        👉 Cashback máximo R$5.000,00 por dia.<br>
        <br>
        👉 Porcentagem de cashback - até 10% com base no valor da perda diaria.<br>
        <br>
        👉 Tipo de cashback - dinheiro real.<br>
        <br>
        👉 Calculo do cashback - até 2% de todo dinheiro real depositado e perdido em 1 dia.<br>
        <br>
        👉 Saldo Bônus não é contabilizado no cashback. <br>
        <br>
        👉 O CashBack é somado e liberado todo dia!
      </p>

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
