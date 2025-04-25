<template>
  <div class="casino-winner-wrapper">
    <div class="container">
      <div v-if="currentWinner" class="winner-container" :class="{ active: isActive }" @click="redirecionarParaJogo(currentWinner.jogo)">
        <div class="trophy-section">
          <img class="trophy-icon" src="/public/trofeu.webp" alt="Troféu">
<div class="top-ganhos">
  <span>TOP</span>
  <span>GANHOS</span>
</div>        </div>
        <div class="winner-info">
          <div class="casino-winner__img" :style="{ backgroundImage: 'url(' + currentWinner.imgSrc + ')' }"></div>
          <div class="casino-winner__details">
            <div class="casino-winner__title">Parabéns: {{ currentWinner.jogador }}</div>
            
              <div class="casino-winner-text__inner">Ganhou: {{ currentWinner.valor }}</div>

            <div class="casino-winner__text">
                            <div class="casino-winner__game">{{ currentWinner.jogo }}</div>

            </div>
          </div>
        </div>
        <div class="highlight"></div>
      </div>
    </div>
  </div>
</template>

<script>
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
      components: { CassinoGameCard },

  data() {
    return {
      urlsJogos: {
        "FortuneTiger": "/games/play/8680/9946",
        "JackFrost": "/games/play/12043/9921",
        "FortunePanda": "/games/play/12042/9873",
        "FortuneOx": "/games/play/12040/9922",
        "FortuneMouse": "/games/play/12041/9898",
        "FortuneRabbit": "/games/play/12039/9959",
        "FortuneDragon": "/games/play/12038/12575",
        "GaneshaGold": "/games/play/12046/9882",
        "BlackJack": "/games/play/12153/9622",
        "CandyBonanza": "/games/play/12048/9923",
        "SideBetCity": "/games/play/12156/9297",
        "LightnRoullet": "/games/play/12158/9676",
        "PiggyGold": "/games/play/12044/9879",
        "SicBo": "/games/play/12154/9331",
        "Athena": "/games/play/12084/9864",
        "SantasGift": "/games/play/12077/9877",
        "Oriental": "/games/play/12121/9933",
        "NinjaVsSamurai": "/games/play/12120/9890",
        "MuayThai": "/games/play/12119/9895",

      },
      jogadores: [],
      currentWinner: null,
      isActive: false
    };
  },
  created() {
    this.gerarJogadores();
    this.selecionarJogadorEJogo();
    setInterval(this.selecionarJogadorEJogo, 5000);
  },
  methods: {
gerarJogadores() {
  const pedacosNomes = ["Ba", "Be", "Bi", "Bo", "Bu", "Da", "De", "Di", "Do", "Du", "Fa", "Fe", "Fi", "Fo", "Fu", "Ga", "Ge", "Gi", "Go", "Gu", "Ha", "He", "Hi", "Ho", "Hu", "Ja", "Je", "Ji", "Jo", "Ju", "Ka", "Ke", "Ki", "Ko", "Ku", "La", "Le", "Li", "Lo", "Lu", "Ma", "Me", "Mi", "Mo", "Mu", "Na", "Ne", "Ni", "No", "Nu", "Pa", "Pe", "Pi", "Po", "Pu", "Ra", "Re", "Ri", "Ro", "Ru", "Sa", "Se", "Si", "So", "Su", "Ta", "Te", "Ti", "To", "Tu", "Va", "Ve", "Vi", "Vo", "Vu", "Za", "Ze", "Zi", "Zo", "Zu", "Bea", "Mari", "Luca", "Rena", "Sara", "Nina", "Gio", "Leo", "Ana", "Lia", "Luca", "Rena", "Sara", "Nina", "Gio", "Leo", "Ana", "Lia", "Mila", "Theo", "Zoe", "Kai", "Eli", "Ivy", "Eva", "Max", "Noa", "Ben", "Mia", "Tom", "Liv", "Rio", "Jax", "Luz", "Ira", "Lux", "Ada", "Sam", "Ray", "Tia", "Eve", "Lea", "Amy", "Mae", "Pia", "Jay", "Sky", "Sid", "Tess", "Moe"];

  this.jogadores = []; // Certifique-se de inicializar a lista de jogadores

  for (let i = 0; i < 100; i++) {
    let tipo = Math.floor(Math.random() * 3); // Gera um número aleatório entre 0 e 2
    let jogador;

    if (tipo === 0) {
      // Um pedaço de nome aleatório
      let pedacoAleatorio = pedacosNomes[Math.floor(Math.random() * pedacosNomes.length)];
      let asteriscos = '*'.repeat(Math.floor(Math.random() * 2) + 3);
      jogador = pedacoAleatorio + asteriscos;
    } else if (tipo === 1) {
      // Combinação de dois pedaços aleatórios
      let pedacoAleatorio1 = pedacosNomes[Math.floor(Math.random() * pedacosNomes.length)];
      let pedacoAleatorio2 = pedacosNomes[Math.floor(Math.random() * pedacosNomes.length)].toLowerCase();
      let asteriscos = '*'.repeat(Math.floor(Math.random() * 2) + 3);
      jogador = pedacoAleatorio1 + pedacoAleatorio2 + asteriscos;
    } else {
      // Um número aleatório seguido de asteriscos
      let numeroAleatorio = Math.floor(Math.random() * (99999 - 10000 + 1)) + 10000;
      let asteriscos = '*'.repeat(Math.floor(Math.random() * 2) + 3);
      jogador = numeroAleatorio + asteriscos;
    }

    this.jogadores.push(jogador);
  }
},
    gerarValorAleatorio() {
  return Math.floor(Math.random() * 10591) + 398;
    },
    selecionarJogadorEJogo() {
      this.isActive = false;
      setTimeout(() => {
        let jogadorSelecionado = this.jogadores[Math.floor(Math.random() * this.jogadores.length)];
        let jogos = Object.keys(this.urlsJogos);
        let jogoSelecionado = jogos[Math.floor(Math.random() * jogos.length)];
        let valorAleatorio = this.gerarValorAleatorio();
        this.currentWinner = {
          jogador: jogadorSelecionado,
          jogo: jogoSelecionado,
          valor: `R$${valorAleatorio}`,
          imgSrc: `/${jogoSelecionado.toLowerCase().replace(/\s+/g, '-')}.png`
        };
        this.isActive = true;
      }, 100); // Pequeno atraso para reiniciar a animação
    },
    redirecionarParaJogo(jogo) {
      let url = this.urlsJogos[jogo];
      if (url) {
        window.location.href = url;
      } else {
        console.error("URL do jogo não encontrada para o jogo: " + jogo);
      }
    }
  }
};
</script>

<style scoped>
.top-ganhos span {
  display: block;
}

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.casino-winner-wrapper {
  display: flex;
  align-items: center;
  justify-content: flex-start; /* Alinhar à esquerda */
  width: 100%;
  padding: 0;
}

.container {
  display: flex;
  align-items: center;
  justify-content: flex-start; /* Alinhar à esquerda */
  height: auto;
  width: 100%;
  overflow: hidden;
}

.winner-container {
  display: flex;
  align-items: center;
  justify-content: flex-start; /* Alinhar à esquerda */
  width: 100%; /* Ocupa toda a largura disponível */
  height: 80px;
  background-color: transparent;
  position: relative;
  overflow: hidden;
  transform: translateX(100%);
  transition: transform 2s ease-in-out;
}

.winner-container::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 20%; /* Ajuste a largura conforme necessário */
  height: 100%;
  background: linear-gradient(to left, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
  z-index: 1;
  transition: width 2s ease-in-out;
}

.winner-container.active::before {
  width: 10%; /* Ajuste a largura conforme necessário */
}

.winner-container.active {
  transform: translateX(0);
}

.trophy-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 100%;
  background-color: transparent;
}

.trophy-icon {
  width: 30px;
  height: 30px;
  margin-bottom: 5px; /* Adicionado para centralizar melhor */
}

.top-ganhos {
  font-size: 10px;
  font-weight: bold;
  color: white;
  text-align: center; /* Centralizado */
}

.winner-info {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: calc(100% - 1px);
  height: 100%;
  padding: 10px;
  position: relative;
  border-radius: 10px;
  background: linear-gradient(to left, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0));
}

.casino-winner__img {
  width: 75px; /* Aumentado */
  height: 75px; /* Aumentado */
  background-size: cover;
  background-position: center;
  border-radius: 10px;
  margin-right: 10px;
  object-fit: cover; /* Garante que a imagem seja cortada para se ajustar ao contêiner */
}

.casino-winner__details {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.casino-winner__game {
  font-size: 14px; /* Ajustado */
  font-weight: bold;
  color: white;
  margin-bottom: 1px; /* Reduzido o espaço entre os textos */
}

.casino-winner__title {
  font-size: 18px; /* Ajustado */
  color: white;
    font-weight: bold;

  margin-bottom: 1px; /* Reduzido o espaço entre os textos */
}

.casino-winner__text {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.casino-winner-text__inner {
  font-size: 16px; /* Ajustado */
  color: gold;
  margin-bottom: 1px; /* Reduzido o espaço entre os textos */
}

.highlight {
  position: absolute;
  left: 0;
  top: 0;
  width: 0;
  height: 100%;
  background: linear-gradient(to right, rgba(255, 215, 0, 0.4), rgba(255, 215, 0, 0)); /* Gradiente dourado */
  z-index: 1;
  transition: width 2s ease-in-out;
  border-radius: 10px;
}

.winner-container.active .highlight {
  width: 20%;
}
</style>

