<template>
    <div class="footer pb-32 md:pb-5 mt-5 footer-color p-4 md:p-8 text-center">
    <hr class="border-t border-gray-300 w-full"> <!-- Linha superior -->

        <!-- provedores -->
        <div class="md:w-5/6 2xl:w-5/6 m-auto"> <!-- Adicionando a classe m-auto para centralizar horizontalmente -->
            <div class="text-center mt-5 flex items-center justify-center flex-wrap">
            <a v-if="setting" href="/"> <!-- Adicionando a classe items-center para centralizar verticalmente -->
                <img :src="`/storage/` + setting.software_logo_white" alt="" class="h-10 mr-3 hidden dark:block" />
            </a>
            </div>
        </div>
        
        
<div class="md:w-5/6 2xl:w-5/6 mx-auto p-2">
    <div class="text-center">
        <h3 class="text-lg md:text-xl text-gray-600 dark:text-white font-bold mb-1">
            Suporte
        </h3>
        <div class="flex flex-wrap justify-center mt-2">
            <div class="flex justify-center space-x-1 w-full mb-1">
                <a href="/terms/Antilavagem" class="text-white py-1 px-1 rounded text-center text-xs"> > AML Policy</a>
                <a href="/terms/Regras" class="text-white py-1 px-1 rounded text-center text-xs"> > Termos de Apostas</a>
                <a href="/terms/Responsavel" class="text-white py-1 px-1 rounded text-center text-xs"> > Jogo Responsavel</a>
            </div>
            <div class="flex justify-center space-x-1 w-full">
                <a href="/terms/privacy-policy" class="text-white py-1 px-1 rounded text-center text-xs"> > Política de Privacidade</a>
                <a href="/terms/bonus" class="text-white py-1 px-1 rounded text-center text-xs"> > Política de Bônus</a>
            </div>
        </div>
    </div>
</div>

<div v-if="setting" class="flex mt-2 items-start">
    <!-- Pagamento e ícone do Pix -->
    <div class="flex flex-col items-center">
        <h3 class="text-base md:text-lg text-gray-600 dark:text-white font-bold mb-1">
            Pagamento
        </h3>
        <img src="/assets/images/pix.png" alt="Pix" class="h-11" />
    </div>
    
    <!-- Espaço flexível -->
    <div class="flex-1"></div>
    
    <!-- Siga-Nos e ícones sociais -->
    <div class="flex flex-col items-center">
        <h3 class="text-base md:text-lg text-gray-600 dark:text-white font-bold mb-1">
            Siga-Nos
        </h3>
        <div class="flex space-x-1">
            <div v-if="custom.instagram" class="flex">
                <a :href="custom.instagram" target="_blank" class="text-xl text-gray-100 p-1">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            </div>
            <div v-if="custom.telegram" class="flex">
                <a :href="custom.telegram" target="_blank" class="text-xl text-gray-100 p-1">
                    <i class="fa-brands fa-telegram"></i>
                </a>
            </div>
            <div v-if="custom.whatsapp" class="flex">
                <a :href="custom.whatsapp" target="_blank" class="text-xl text-gray-100 p-1">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
            </div>
            <div v-if="custom.facebook" class="flex">
                <a :href="custom.facebook" target="_blank" class="text-xl text-gray-100 p-1">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
            </div>
        </div>
    </div>
</div>


    <hr class="border-t border-gray-300 w-full mt-5"> <!-- Linha inferior -->

<div class="relative overflow-hidden max-h-24" id="text-container">
        <div v-if="setting && setting.footer_about" class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm whitespace-pre-line" id="text-content">{{ setting.footer_about }}</div>
        <div v-else class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm" id="text-content">
{{ setting.software_name }} é um cassino online que oferece aos seus usuários uma experiência única de jogos de cassino. Com uma plataforma intuitiva e fácil de usar, {{ setting.software_name }} permite que seus usuários desfrutem de uma ampla variedade de jogos, incluindo os clássicos e os mais recentes lançamentos.

Com o objetivo de proporcionar a melhor experiência de jogos aos seus usuários, {{ setting.software_name }} oferece uma série de recursos e ferramentas, como estatísticas detalhadas dos jogos, histórico de resultados e análises de especialistas. Além disso, o site destaca-se por suas diversas promoções e bônus exclusivos, que aumentam as chances de lucro e tornam a experiência ainda mais emocionante.

{{ setting.software_name }} é reconhecido por sua segurança e confiabilidade, oferecendo suporte ao cliente 24 horas por dia, sete dias por semana. Com uma equipe de especialistas em cassino online, a plataforma está comprometida em garantir que seus usuários tenham uma experiência positiva e segura, podendo desfrutar de toda a emoção e oportunidades oferecidas pelos jogos de cassino de forma responsável e consciente.
        </div>
        <div class="absolute bottom-0 left-0 w-full h-8 bg-gradient-to-t from-white dark:from-gray-800" id="fade"></div>
        <button class="absolute bottom-0 left-1/2 transform -translate-x-1/2 text-white p-1" id="toggle-button"  @click.prevent="toggleText">Ver mais</button>
    </div>
    
    <hr class="border-t border-gray-300 w-full"> <!-- Linha superior -->

    <hr class="border-t border-gray-300 w-full mt-5"> <!-- Linha superior -->

<div class="flex justify-center items-center mt-5 space-x-4">
    <img src="/public/ssl.svg" alt="Image 1" class="h-12"> <!-- Ajuste o caminho e o tamanho da imagem conforme necessário -->
    <img src="/public/+18.png" alt="Image 2" class="h-12"> <!-- Ajuste o caminho e o tamanho da imagem conforme necessário -->
</div>


<div v-if="setting" class="flex flex-col items-center mt-4 space-y-2">
    <span v-if="setting.support_email" class="text-gray-500 text-sm">Suporte <a :href="'mailto:' + setting.support_email" class="text-gray-400">{{ setting.support_email }}</a></span>
    <span v-if="setting.legal_email" class="text-gray-500 text-sm">Jurídico <a :href="'mailto:' + setting.legal_email" class="text-gray-400">{{ setting.legal_email }}</a></span>
    <span v-if="setting.partners_email" class="text-gray-500 text-sm">Parceiros <a :href="'mailto:' + setting.partners_email" class="text-gray-400">{{ setting.partners_email }}</a></span>
</div>

<div class="flex justify-center items-center mt-5">
    <button class="bg-gray-700 text-white py-2 px-4 rounded-full flex items-center space-x-2 border-2 border-green-400" @click.prevent="scrollToTop">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l5 5a1 1 0 11-1.414 1.414L11 6.414V17a1 1 0 11-2 0V6.414L5.707 9.707a1 1 0 11-1.414-1.414l5-5A1 1 0 0110 3z" clip-rule="evenodd" />
        </svg>
        <span>De volta ao topo</span>
    </button>
</div>

<div v-if="setting" class="flex flex-col items-center mt-5 space-y-1 text-xs text-gray-500">
    <span>{{ setting.footer_copyright || ('© ' + year + ' ' + setting.software_name + '. Todos os direitos reservados.') }}</span>
    <span v-if="setting.footer_show_credit && setting.footer_credit">
        <a v-if="setting.footer_credit_url" :href="setting.footer_credit_url" target="_blank" rel="noopener" class="hover:text-gray-300">{{ setting.footer_credit }}</a>
        <template v-else>{{ setting.footer_credit }}</template>
    </span>
</div>








    </div>
</template>


<script>
import HttpApi from "@/Services/HttpApi.js";
import { useSettingStore } from "@/Stores/SettingStore.js";
import DropdownDarkLight from "@/Components/UI/DropdownDarkLight.vue";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
export default {
    props: [],
    components: { LanguageSelector, DropdownDarkLight, },
    data() {
        return {
            isLoading: false,
            year: new Date().getFullYear(),
            setting: null,
            custom: null,

            icons: [
                { id: 1, src: "/storage/providers/PLAYSON.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 2, src: "/storage/providers/HAMERO.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 3, src: "/storage/providers/EVOPLAY.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 4, src: "/storage/providers/PRAGMATIC.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 5, src: "/storage/providers/BETGAMES.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 6, src: "/storage/providers/EVOLUTION.webp", alt: "Descrição da imagem", width: "125px" },
                { id: 7, src: "/storage/providers/PGSOFT.webp", alt: "Descrição da imagem", width: "125px" }
            ],

            columns: [
                {
                    id: 1,
                    icon: "fas fa-info-circle mr-2",
                    title: "SUPORTE  ",
                    links: [
                        { id: 1, label: "> AML Policy", url: "/terms/Antilavagem" },
                        { id: 2, label: "> Política de Privacidade", url: "/terms/privacy-policy" },
                        { id: 3, label: "> Jogo Responsável", url: "/terms/Responsavel" },
                        { id: 4, label: "> Termos de Apostas", url: "/terms/Regras" },
                        { id: 5, label: "Política de Bônus", url: "/terms/bonus" }

                    ]
                },
            ],

            logos: [
                { id: 1, src: "https://tpmr8.games/assets/images/icons/18.svg", alt: "Descrição da imagem", width: "35px" },
                { id: 2, src: "https://tpmr8.games/assets/images/icons/pix.svg", alt: "Descrição da imagem", width: "60px" },
                { id: 3, src: "https://tpmr8.games/assets/images/icons/jogoresponsavel.svg", alt: "Descrição da imagem", width: "110px" },
                { id: 4, src: "https://tpmr8.games/assets/images/icons/seuslimites.svg", alt: "Descrição da imagem", width: "110px" },
                { id: 5, src: "https://tpmr8.games/assets/images/icons/ssl.svg", alt: "Descrição da imagem", width: "100px" },
                { id: 6, src: "https://tpmr8.games/assets/images/icons/certified.webp", alt: "Descrição da imagem", width: "75px" },
                { id: 7, src: "https://tpmr8.games/assets/images/icons/gambling-curacao.png", alt: "Descrição da imagem", width: "100px" }
            ],
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
        scrollToTop: function () {
         window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        
        getSetting: function () {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;

            if (settingData) {
                _this.setting = settingData;
            }
        },
        
        toggleText: function () {
        var textContainer = document.getElementById("text-container");
        var textContent = document.getElementById("text-content");
        var fade = document.getElementById("fade");
        var button = document.getElementById("toggle-button");

        if (textContainer.classList.contains("max-h-24")) {
            textContainer.classList.remove("max-h-24");
            textContainer.classList.add("max-h-full");
            fade.style.display = "none";
            button.innerText = "Ver menos";
            button.style.bottom = "-2rem"; // Move the button down

        } else {
            textContainer.classList.remove("max-h-full");
            textContainer.classList.add("max-h-24");
            fade.style.display = "block";
            button.innerText = "Ver mais";
            button.style.bottom = "0"; // Move the button back up

        }
        },
    },
    created() {
        this.getSetting();
        this.custom = custom;

    },
    watch: {

    },
};
</script>

<style scoped>
        @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    .animate-pulse {
        animation: pulse 2s infinite;
    }
    
        .max-h-24 {
        max-height: 6rem; /* Approx. 4 lines of text */
    }
    .max-h-full {
        max-height: none;
    }
    
    .footer-color {
    background-color: #1d1d1d !important;
}


</style>
