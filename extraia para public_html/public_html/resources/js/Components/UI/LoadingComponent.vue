<template>
    <div v-if="isLoading" class="is-loading-component w-full h-[calc(100vh-60px)] z-[999999]" :class="{ 'is-leaving': !isLoading }">
        <div class="text-center flex-col">
            <div role="status" class="absolute grid -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                <div class="text-center mx-auto mb-5">
                    <img :src="`/storage/`+setting.software_carregamento" class="logo" alt="Logo do Site">
                </div>
                <slot></slot>
            </div>
        </div>
    </div>
</template>


<script>
import {useSettingStore} from "@/Stores/SettingStore.js";

export default {
    props: ['isLoading'],
    data() {
        return {};
    },
    setup(props) {
        return {};
    },
    computed: {
    setting() {
      const settingStore = useSettingStore();
      return settingStore.setting;
    }
    },
};
</script>


<style>
.logo {
    /* Defina as dimensões do logotipo */
    width: 100px; /* Reduzindo o tamanho em 50% */
    height: 50px; /* Reduzindo o tamanho em 50% */
    opacity: 1; /* Opacidade inicial */
    transition: opacity 0.5s ease-in-out; /* Adiciona uma transição suave */
    position: relative; /* Para permitir a posição absoluta do sombreador */
    overflow: hidden; /* Esconde a parte do sombreador que está fora do logotipo */
}

/* Estilo do sombreador */
.logo::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 5px; /* Arredonde as bordas do sombreador */
    box-shadow: 0 0 20px #01ec01; /* Sombra pulsante na cor #01ec01 */
    animation: pulseShadow 5s infinite; /* Animação de pulsação */
    z-index: -1; /* Coloca a sombra atrás do logotipo */
}

/* Animação de pulsação */
@keyframes pulseShadow {
    0% {
        box-shadow: 0 0 20px #01ec01; /* Sombra normal */
    }
    50% {
        box-shadow: 0 0 30px #01ec01; /* Aumenta a intensidade da sombra */
    }
    100% {
        box-shadow: 0 0 20px #01ec01; /* Volta para a sombra normal */
    }
}

.is-loading-component {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out; /* Transição para a animação de saída */
}

.is-loading-component.is-leaving {
    transform: translateY(-100%); /* Move para cima ao sair */
    opacity: 0; /* Reduz a opacidade ao sair */
}
</style>

