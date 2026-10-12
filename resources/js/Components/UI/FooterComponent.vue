<template>
    <footer class="site-footer mt-5 px-4 pt-8 pb-32 md:px-8 md:pb-8">
        <div v-if="setting" class="max-w-6xl mx-auto">
            <!-- Marca e redes sociais -->
            <div class="flex flex-col items-center gap-4 md:flex-row md:justify-between">
                <RouterLink to="/" class="inline-flex items-center" active-class="" exact-active-class="">
                    <img v-if="logo" :src="logo" :alt="setting.software_name" class="h-10 w-auto max-w-[200px] object-contain">
                    <span v-else class="text-xl font-bold text-white">{{ setting.software_name }}</span>
                </RouterLink>
                <div v-if="socials.length" class="flex items-center gap-2">
                    <a v-for="social in socials" :key="social.key" :href="social.url" target="_blank" rel="noopener"
                       :aria-label="social.label" class="footer-social">
                        <i :class="social.icon"></i>
                    </a>
                </div>
            </div>

            <!-- Links -->
            <div class="grid grid-cols-1 gap-6 mt-8 text-sm text-center sm:grid-cols-3 sm:text-left">
                <div>
                    <h3 class="footer-title">Institucional</h3>
                    <ul class="space-y-1">
                        <li v-for="link in legalLinks" :key="link.to">
                            <RouterLink :to="link.to" class="footer-link" active-class="" exact-active-class="">{{ link.label }}</RouterLink>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="footer-title">Ajuda</h3>
                    <ul class="space-y-1">
                        <li><RouterLink to="/support" class="footer-link" active-class="" exact-active-class="">Fale com o suporte</RouterLink></li>
                        <li v-if="setting.support_email">
                            <a :href="'mailto:' + setting.support_email" class="footer-link break-all">{{ setting.support_email }}</a>
                        </li>
                        <li v-if="setting.legal_email" class="text-gray-400">
                            Jurídico: <a :href="'mailto:' + setting.legal_email" class="footer-link break-all">{{ setting.legal_email }}</a>
                        </li>
                        <li v-if="setting.partners_email" class="text-gray-400">
                            Parceiros: <a :href="'mailto:' + setting.partners_email" class="footer-link break-all">{{ setting.partners_email }}</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="footer-title">Pagamento</h3>
                    <img src="/assets/images/footer/pix.svg" alt="Pagamentos via PIX" class="h-8 w-auto mx-auto sm:mx-0" loading="lazy">
                </div>
            </div>

            <!-- Sobre o site -->
            <div class="mt-8 pt-6 border-t border-white/10">
                <p class="text-sm leading-relaxed text-gray-400 whitespace-pre-line" :class="{ 'line-clamp-4': !expanded }">{{ aboutText }}</p>
                <button v-if="aboutText.length > 280" type="button" class="mt-2 py-1 text-sm font-semibold text-white hover:underline"
                        :aria-expanded="expanded ? 'true' : 'false'" @click="expanded = !expanded">
                    {{ expanded ? 'Ver menos' : 'Ver mais' }}
                </button>
            </div>

            <!-- Selos -->
            <div class="flex flex-wrap items-center justify-center gap-4 mt-6">
                <img v-for="seal in seals" :key="seal.src" :src="seal.src" :alt="seal.alt" :style="{ height: seal.height }" class="w-auto" loading="lazy">
            </div>

            <div class="flex justify-center mt-6">
                <button type="button" class="footer-top" @click="scrollToTop">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l5 5a1 1 0 11-1.414 1.414L11 6.414V17a1 1 0 11-2 0V6.414L5.707 9.707a1 1 0 11-1.414-1.414l5-5A1 1 0 0110 3z" clip-rule="evenodd" />
                    </svg>
                    <span>De volta ao topo</span>
                </button>
            </div>

            <div class="flex flex-col items-center gap-1 mt-6 text-xs text-center text-gray-500">
                <span>{{ copyright }}</span>
                <span v-if="setting.footer_show_credit && setting.footer_credit">
                    <a v-if="setting.footer_credit_url" :href="setting.footer_credit_url" target="_blank" rel="noopener" class="hover:text-gray-300">{{ setting.footer_credit }}</a>
                    <template v-else>{{ setting.footer_credit }}</template>
                </span>
            </div>
        </div>
    </footer>
</template>

<script>
import { RouterLink } from "vue-router";
import { useSettingStore } from "@/Stores/SettingStore.js";

export default {
    components: { RouterLink },
    data() {
        return {
            expanded: false,
            year: new Date().getFullYear(),
            legalLinks: [
                { to: '/terms/service', label: 'Termos e condições' },
                { to: '/terms/Regras', label: 'Termos de apostas' },
                { to: '/terms/privacy-policy', label: 'Política de privacidade' },
                { to: '/terms/bonus', label: 'Política de bônus' },
                { to: '/terms/Responsavel', label: 'Jogo responsável' },
                { to: '/terms/Antilavagem', label: 'Política de prevenção à lavagem de dinheiro' },
            ],
            // selos servidos pelo próprio site
            seals: [
                { src: '/assets/images/footer/18.svg', alt: 'Proibido para menores de 18 anos', height: '40px' },
                { src: '/assets/images/footer/jogo-responsavel.svg', alt: 'Jogo responsável', height: '32px' },
                { src: '/assets/images/footer/site-seguro.svg', alt: 'Site seguro', height: '32px' },
            ],
        };
    },
    computed: {
        setting() {
            return useSettingStore().setting || null;
        },
        custom() {
            // definido em resources/views/layouts/app.blade.php
            return typeof custom !== 'undefined' && custom ? custom : {};
        },
        logo() {
            const image = this.setting && (this.setting.software_logo_white || this.setting.software_logo_black);
            if (!image) return '';
            return /^(https?:)?\/\//.test(image) || image.startsWith('/') ? image : '/storage/' + image;
        },
        socials() {
            const list = [
                { key: 'instagram', label: 'Instagram', icon: 'fa-brands fa-instagram' },
                { key: 'telegram', label: 'Telegram', icon: 'fa-brands fa-telegram' },
                { key: 'whatsapp', label: 'WhatsApp', icon: 'fa-brands fa-whatsapp' },
                { key: 'facebook', label: 'Facebook', icon: 'fa-brands fa-facebook-f' },
            ];
            return list.filter((item) => this.custom[item.key]).map((item) => ({ ...item, url: this.custom[item.key] }));
        },
        aboutText() {
            if (!this.setting) return '';
            if (this.setting.footer_about) return this.setting.footer_about;
            const name = this.setting.software_name || 'Este site';
            return `${name} é um cassino online que oferece aos seus usuários uma experiência única de jogos de cassino. Com uma plataforma intuitiva e fácil de usar, ${name} permite que seus usuários desfrutem de uma ampla variedade de jogos, incluindo os clássicos e os mais recentes lançamentos.\n\n`
                + `Com o objetivo de proporcionar a melhor experiência de jogos aos seus usuários, ${name} oferece promoções e bônus exclusivos e suporte dedicado. Jogue com responsabilidade: proibido para menores de 18 anos.`;
        },
        copyright() {
            if (!this.setting) return '';
            return this.setting.footer_copyright || `© ${this.year} ${this.setting.software_name}. Todos os direitos reservados.`;
        },
    },
    methods: {
        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    },
};
</script>

<style scoped>
.site-footer {
    background-color: #1d1d1d;
    color: #e5e7eb;
}

.footer-title {
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
}

.footer-link {
    display: inline-block;
    padding: 4px 0;
    color: #9ca3af;
    transition: color 0.15s ease;
}

.footer-link:hover {
    color: #fff;
}

.footer-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    background-color: rgba(255, 255, 255, 0.08);
    color: #f3f4f6;
    font-size: 1.15rem;
    transition: background-color 0.15s ease;
}

.footer-social:hover {
    background-color: rgba(255, 255, 255, 0.18);
}

.footer-top {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 2px solid #4ade80;
    border-radius: 9999px;
    background-color: #374151;
    color: #fff;
}
</style>
