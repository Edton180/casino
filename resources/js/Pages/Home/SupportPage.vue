<template>
    <BaseLayout>
        <div class="support-container">
            <h1 class="support-title">Suporte</h1>
            <p class="support-text">
                Precisa de ajuda? Fale com a equipe {{ siteName ? 'do ' + siteName : 'de atendimento' }}.
            </p>

            <div v-if="supportUrl || supportEmail" class="support-link-container">
                <a v-if="supportUrl" :href="supportUrl" target="_blank" rel="noopener" class="support-link">
                    Falar com o suporte
                </a>
                <a v-if="supportEmail" :href="'mailto:' + supportEmail" class="support-link support-link-secondary">
                    {{ supportEmail }}
                </a>
            </div>
            <p v-else class="support-text support-muted">
                O atendimento ainda não foi configurado. Tente novamente mais tarde.
            </p>
        </div>
    </BaseLayout>
</template>

<script>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { useSettingStore } from "@/Stores/SettingStore.js";

export default {
    components: { BaseLayout },
    computed: {
        setting() {
            return useSettingStore().setting || {};
        },
        siteName() {
            return this.setting.software_name || '';
        },
        // link configurado no painel (Configurações → Rodapé e contatos)
        supportUrl() {
            return this.setting.support_url || '';
        },
        supportEmail() {
            return this.setting.support_email || '';
        },
    },
};
</script>

<style scoped>
.support-container {
    max-width: 640px;
    margin: 24px auto;
    padding: 24px 20px;
    border-radius: 12px;
    background-color: var(--ci-gray-dark, #111);
    text-align: center;
}

.support-title {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 12px;
}

.support-text {
    color: #fff;
    margin-bottom: 20px;
}

.support-muted {
    color: #9ca3af;
    margin-bottom: 0;
}

.support-link-container {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}

.support-link {
    display: inline-block;
    padding: 10px 18px;
    border-radius: 999px;
    background-color: var(--ci-primary-color, #007bff);
    color: #000;
    font-weight: 700;
    text-decoration: none;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.support-link-secondary {
    background-color: transparent;
    color: #fff;
    border: 1px solid #4b5563;
    font-weight: 500;
}
</style>
