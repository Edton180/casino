function hideDiv(el) {
    el.style.display = "none";
}
function showDiv(el) {
    el.style.display = "";
}

/******************* TIMER CONFIG ******************/
function updateTimer(el, seconds) {
    if (configGame.stateGame) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        const formattedMinutes = minutes < 10 ? "0" + minutes : minutes;
        const formattedSeconds =
            remainingSeconds < 10 ? "0" + remainingSeconds : remainingSeconds;
        el.innerText = formattedMinutes + ":" + formattedSeconds;
    }
}

function startTimer(el, seconds) {
    updateTimer(el, seconds);
    const timerInterval = setInterval(function () {
        seconds--; // Decrementa primeiro
        if (seconds <= 0) {
            clearInterval(timerInterval); // Limpa o intervalo imediatamente
            const urlParams = new URLSearchParams(window.location.search);
            const baseurl = decodeURIComponent(
                urlParams.get("baseurl")
            ).toString();
            window.parent.location.href = baseurl;
            return; // Sai da função para não executar updateTimer
        }
        if (seconds <= 20) {
            el.style.color = "red";
            el.style.fontSize = "28px";
        }
        updateTimer(el, seconds); // Atualiza o temporizador depois de verificar a condição de parada
    }, 1000);
}

/******************* GAME CONFIG  *********************/
const configGame = {
    stateGame: true,
    value: "",
    currentValue: 0,
    timer: typeGame == "real" ? 90 : 90,
    meta: () => {
        return typeGame == "real" ? configGame.value * 3 : configGame.value * 3;
    },
};
function setText(el, value) {
    el.innerText = value;
}
const els = {
    currentRound: () => {
        return document.querySelector(`#round-1`);
    },
    currentPoints: () => {
        return document.querySelector(`#round-1 .currentPoint`);
    },
    currentMeta: () => {
        return document.querySelector(`#round-1 .currentMeta`);
    },
    currentTimer: () => {
        return document.querySelector(`#round-1 .currentTimer`);
    },
};
function extTriggerPoints(coin = 0.2) {
    var currentPoints = els.currentPoints();
    var percent = typeGame == "real" ? 100 : 10;
    var point = (coin / percent) * configGame.value;
    var calc = (Number(point) + Number(configGame.currentValue)).toFixed(2);
    configGame.currentValue = calc;
    currentPoints.innerText = calc;
    if (+currentPoints.innerText >= configGame.meta()) {
        execGreen();
    }
}
function execGreen() {
    if (configGame.stateGame && configGame.currentValue >= configGame.meta()) {
        configGame.stateGame = false;
        if (typeGame == "real") {
            $.post(
                "../auth?action=game&type=win",
                {
                    session: session,
                    bet: configGame.value,
                    val: configGame.currentValue,
                },
                function (data) {
                    let msg =
                        "Parabens, você ganhou R$ " +
                        configGame.currentValue +
                        "!";
                    location.href = "../panel?type=win&msg=" + msg;
                }
            );
        } else if (typeGame == "demo") {
            window.location.replace(
                "../e-wallet-deposit?type=demo&value=" + configGame.currentValue
            );
        } else if (typeGame == "presell") {
            window.location.replace(
                "../register?type=demo&value=" + configGame.currentValue
            );
        }
    }
}
function execRed() {
    console.log("execRed: Iniciando função execRed."); // Log ao iniciar a função
    const urlParams = new URLSearchParams(window.location.search);
    const baseurl = decodeURIComponent(urlParams.get("baseurl")).toString();
    console.log(`execRed: Base URL recuperada: ${baseurl}`); // Log para mostrar a URL base

    if (configGame.stateGame) {
        console.log(
            "execRed: O jogo está ativo, procedendo com a finalização."
        ); // Log quando o jogo ainda está ativo
        configGame.stateGame = false; // Altera o estado do jogo para inativo

        // Logs específicos para cada tipo de jogo
        if (typeGame == "real") {
            console.log(
                "execRed: Tipo de jogo real. Redirecionando para a URL base."
            );
            window.parent.location.href = baseurl;
        } else if (typeGame == "demo") {
            console.log(
                "execRed: Tipo de jogo demo. Redirecionando para a URL base."
            );
            window.parent.location.href = baseurl;
        } else if (typeGame == "presell") {
            console.log(
                "execRed: Tipo de jogo presell. Redirecionando para a URL base."
            );
            window.parent.location.href = baseurl;
        }
    } else {
        window.location.replace(baseurl);
        console.log(
            "execRed: O jogo já está inativo. Nenhuma ação adicional necessária."
        ); // Log quando o jogo já está inativo
    }
}

function loadGame() {
    var currentRound = els.currentRound();
    var currentMeta = els.currentMeta();
    var currentTimer = els.currentTimer();

    currentRound.style.display = "";
    setText(currentMeta, configGame.meta().toFixed(2));
    startTimer(currentTimer, configGame.timer);
}

window.addEventListener("load", (event) => {
    var container = document.querySelector("#containerFormBet");
    var inpBet = document.querySelector("#valueBet");
    var btnStart = document.querySelector("#startGame");
    btnStart.addEventListener("click", () => {
        if (+inpBet.value > balance && typeGame == "real") {
            alert("Valor da aposta acima do saldo em conta!");
        } else {
            hideDiv(container);
            configGame.value = +inpBet.value;
            loadGame();
        }
    });
});
