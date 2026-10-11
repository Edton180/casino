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
            execRed(); // tempo esgotado sem atingir a meta
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
        return configGame.value * 3;
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
    var percent = 10; // mesmo ritmo de pontos que o jogo já usava
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

        // prêmio pago pelo servidor (limitado à meta do jogo)
        SkillGame.finish(configGame.currentValue).then(function (data) {
            alert("Parabéns, você ganhou R$ " + Number(data.win).toFixed(2) + "!");
            SkillGame.backToCasino();
        }).catch(function (error) {
            alert(error.message);
            SkillGame.backToCasino();
        });
    }
}
function execRed() {
    if (!configGame.stateGame) {
        return;
    }

    configGame.stateGame = false;
    SkillGame.finish(0).then(SkillGame.backToCasino).catch(SkillGame.backToCasino);
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
    // A aposta já foi confirmada e debitada na tela anterior (index.html)
    const apostaConfirmada = Number(new URLSearchParams(window.location.search).get("aposta"));
    if (apostaConfirmada > 0 && SkillGame.round) {
        hideDiv(container);
        configGame.value = apostaConfirmada;
        loadGame();
        return;
    }

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
