<template>
    <div v-if="current" class="casino-winner-wrapper">
        <RouterLink
            :key="index"
            :to="{ name: 'casinoPlayPage', params: { id: current.game.id, slug: current.game.game_code } }"
            class="winner-container"
            :class="{ active: isActive }"
        >
            <div class="trophy-section">
                <img class="trophy-icon" src="/trofeu.webp" alt="" aria-hidden="true">
                <div class="top-ganhos">
                    <span>TOP</span>
                    <span>GANHOS</span>
                </div>
            </div>
            <div class="winner-info">
                <img class="casino-winner__img" :src="coverUrl(current.game)" :alt="current.game.game_name" loading="lazy" @error="onImageError">
                <div class="casino-winner__details">
                    <div class="casino-winner__title">Parabéns: {{ current.player }}</div>
                    <div class="casino-winner-text__inner">Ganhou: {{ state.currencyFormat(current.amount, currency) }}</div>
                    <div class="casino-winner__game">{{ current.game.game_name }}</div>
                </div>
            </div>
            <div class="highlight"></div>
        </RouterLink>
    </div>
</template>

<script>
import HttpApi from "@/Services/HttpApi.js";
import { useSettingStore } from "@/Stores/SettingStore.js";

/**
 * Faixa "Top ganhos": mostra, em rodízio, os prêmios reais mais recentes dos jogadores
 * (nome parcialmente oculto). Se ainda não houver nenhum prêmio, não aparece.
 */
export default {
    props: {
        showMobile: { type: Boolean, default: true },
    },
    data() {
        return {
            wins: [],
            index: 0,
            isActive: false,
            timer: null,
        };
    },
    computed: {
        current() {
            return this.wins.length ? this.wins[this.index % this.wins.length] : null;
        },
        currency() {
            return useSettingStore().setting?.currency_code || 'BRL';
        },
    },
    async mounted() {
        try {
            const { data } = await HttpApi.get('games/recent-wins');
            this.wins = Array.isArray(data?.wins) ? data.wins.filter((w) => w && w.game && w.game.id) : [];
        } catch (e) {
            this.wins = [];
        }
        if (!this.wins.length) {
            return;
        }
        this.show();
        if (this.wins.length > 1) {
            this.timer = setInterval(() => {
                this.index = (this.index + 1) % this.wins.length;
                this.show();
            }, 5000);
        }
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        show() {
            this.isActive = false;
            // pequeno atraso para a animação de entrada recomeçar
            setTimeout(() => { this.isActive = true; }, 60);
        },
        coverUrl(game) {
            const cover = game?.cover || '';
            if (!cover) return '/assets/images/game-placeholder.svg';
            if (/^(https?:)?\/\//.test(cover) || cover.startsWith('/') || game.distribution === 'kagaming') return cover;
            return '/storage/' + cover;
        },
        onImageError(event) {
            if (!event.target.src.endsWith('/assets/images/game-placeholder.svg')) {
                event.target.src = '/assets/images/game-placeholder.svg';
            }
        },
    },
};
</script>

<style scoped>
.top-ganhos span {
    display: block;
}

.casino-winner-wrapper {
    display: flex;
    align-items: center;
    width: 100%;
    overflow: hidden;
}

.winner-container {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
    height: 80px;
    position: relative;
    overflow: hidden;
    transform: translateX(100%);
    transition: transform 1.2s ease-in-out;
    text-decoration: none;
}

.winner-container.active {
    transform: translateX(0);
}

.trophy-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 0 0 72px;
    height: 100%;
}

.trophy-icon {
    width: 30px;
    height: 30px;
    margin-bottom: 5px;
}

.top-ganhos {
    font-size: 10px;
    font-weight: bold;
    color: #fff;
    text-align: center;
}

.winner-info {
    display: flex;
    align-items: center;
    flex: 1 1 auto;
    min-width: 0;
    height: 100%;
    padding: 8px 10px;
    border-radius: 10px;
    background: linear-gradient(to left, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0));
}

.casino-winner__img {
    flex: 0 0 64px;
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    margin-right: 10px;
}

.casino-winner__details {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.casino-winner__title,
.casino-winner-text__inner,
.casino-winner__game {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.casino-winner__title {
    font-size: 16px;
    font-weight: bold;
    color: #fff;
}

.casino-winner-text__inner {
    font-size: 15px;
    color: gold;
}

.casino-winner__game {
    font-size: 13px;
    font-weight: bold;
    color: #fff;
}

.highlight {
    position: absolute;
    left: 0;
    top: 0;
    width: 0;
    height: 100%;
    background: linear-gradient(to right, rgba(255, 215, 0, 0.4), rgba(255, 215, 0, 0));
    z-index: 1;
    transition: width 1.2s ease-in-out;
    border-radius: 10px;
    pointer-events: none;
}

.winner-container.active .highlight {
    width: 20%;
}

@media (max-width: 400px) {
    .trophy-section {
        flex-basis: 56px;
    }
    .casino-winner__img {
        flex-basis: 52px;
        width: 52px;
        height: 52px;
    }
    .casino-winner__title {
        font-size: 14px;
    }
    .casino-winner-text__inner {
        font-size: 13px;
    }
}
</style>
