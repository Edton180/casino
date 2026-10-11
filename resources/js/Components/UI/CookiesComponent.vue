<template>
    <div v-if="!cookieAccepted" class="flex justify-between items-center fixed left-0 md:left-10 bottom-70 z-[999] p-4 translucent-menu">
        <p>Usamos cookies para fornecer a melhor experiência para você.</p>
        <button @click="acceptCookie" class="ui-button-blue ml-3">Aceitar</button>
    </div>
</template>

<script>
    export default {
        props: {

        },
        data() {
            return {
                cookieAccepted: localStorage.getItem('cookieAccepted') === 'true',
            };
        },
        methods: {
            acceptCookie() {
                localStorage.setItem('cookieAccepted', 'true');
                this.cookieAccepted = true;
            },
        },
        watch: {

        }
    };
</script>
<style scoped>
.translucent-menu {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
    backdrop-filter: blur(10px); /* Efeito embassado */
    -webkit-backdrop-filter: blur(10px); /* Suporte para Safari */
    border-top: 1px solid rgba(0, 0, 0, 0.1); /* Borda superior sutil */
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1); /* Sombra sutil */
}

.new-button-container {
    position: relative;
    width: 80px;
    height: 80px;
    margin-top: -35px;
    display: flex;
    align-items: center;
    justify-content: center;
    
}

.new-button-inner {
    width: 80px;
    height: 80px;
    background: linear-gradient(to top, rgba(255, 140, 0, 1), rgba(255, 165, 0, 1)); /* Gradiente laranja */
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); /* Forma hexagonal */
    border-radius: 20px; /* Arredonda os cantos do polígono */
}

.new-button-inner svg {
    width: 28px;
    height: 28px;
}

.new-button-inner div {
    color: black; /* Cor do texto */
    font-weight: bold; /* Texto em negrito */
    text-align: center; /* Alinhamento centralizado */
    font-size: 15px; /* Tamanho da fonte */
}

.status-indicator {
    position: absolute;
    top: 5px; /* Ajuste a posição vertical */
    right: 90px; /* Ajuste a posição horizontal */
    width: 8px; /* Ajuste o tamanho */
    height: 8px; /* Ajuste o tamanho */
    border-radius: 50%;
    background-color: #01ec01; /* Cor verde */
    animation: blink 1s infinite alternate; /* Animação de piscar */
}

/* Animação de piscar */
@keyframes blink {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Garantir que não haja conflitos de estilos */
.fixed {
    background: rgba(0, 0, 0, 0.8) !important;
}

button {
    padding-left: 0.5rem; /* Ajuste da distância lateral */
    padding-right: 0.5rem; /* Ajuste da distância lateral */
}

button i, button svg {
    font-size: 1.4rem; /* Ajustar o tamanho dos ícones */
}

button span {
    font-size: 12px; /* Ajustar o tamanho do texto */
    font-weight: bold; /* Deixar o texto em negrito */
}

#dropdown-user {
    position: absolute;
    top: 100%; /* Para que apareça logo abaixo do botão de perfil */
    right: 0;
    z-index: 50;
    width: 100vw; /* Define a largura para ocupar toda a largura da tela */
    background: rgba(0, 0, 0, 0.7); /* Translucent black background */
    backdrop-filter: blur(10px); /* Blur effect */
    -webkit-backdrop-filter: blur(10px); /* Safari support */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    overflow: hidden;
    padding: 10px; /* Espaçamento interno */
}

#dropdown-user .bg-white {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
}

#dropdown-user .dark\:bg-gray-700 {
    background-color: rgba(0, 0, 0, 0.9); /* Less translucent dark gray background */
}

#dropdown-user .dark\:divide-gray-600 {
    background: rgba(50, 50, 50, 0.9); /* Cinza translúcido mais opaco */
}

#dropdown-user .dark\:text-black {
    color: white; /* White text color */
}

#dropdown-user .hover\:bg-gray-100:hover {
    background-color: rgba(0, 0, 0, 0.9); /* Slightly less translucent hover effect */
}

#dropdown-user .dark\:hover\:bg-gray-600:hover {
    background-color: rgba(75, 85, 99, 0.9); /* Slightly less translucent hover effect in dark mode */
}

#dropdown-user .dark\:hover\:text-white:hover {
    color: white; /* White text color on hover */
}

#dropdown-user .text-sm {
    font-size: 14px; /* Adjust font size */
}

</style>