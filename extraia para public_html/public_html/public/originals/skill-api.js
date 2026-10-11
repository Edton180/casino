/**
 * Integração dos jogos de habilidade (candy, subway, dino, fruitninja) com a API.
 *
 *  - index.html:  SkillGame.start(valor)  -> debita a aposta e abre a rodada
 *  - jogo.html:   SkillGame.finish(valor) -> encerra a rodada (0 = derrota)
 *
 * A rodada fica em sessionStorage e também na URL (?round=...), então só
 * pode ser encerrada uma vez pelo servidor.
 */
(function (window) {
    'use strict';

    var params = new URLSearchParams(window.location.search);

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (!response.ok) {
                    var error = new Error(body.message || 'Erro ao processar a aposta.');
                    error.body = body;
                    throw error;
                }
                return body;
            });
        });
    }

    function parentUrl() {
        try {
            return window.parent.location.href;
        } catch (e) {
            return '/';
        }
    }

    var SkillGame = {
        /** Token de lançamento do jogo (vem na URL do iframe). */
        token: params.get('token'),

        round: params.get('round') || window.sessionStorage.getItem('skill_round'),

        /** URL da página do jogo no cassino, usada para "jogar novamente". */
        backUrl: params.get('baseurl') || parentUrl(),

        start: function (bet) {
            return post('/api/skillgames/start', { token: SkillGame.token, bet: bet }).then(function (data) {
                SkillGame.round = data.round;
                window.sessionStorage.setItem('skill_round', data.round);
                return data;
            });
        },

        /**
         * Monta a URL do jogo levando a rodada e a aposta.
         */
        gameUrl: function (page, data) {
            var query = new URLSearchParams({
                aposta: data.bet,
                round: data.round,
                token: data.round,
                is_demo_agent: data.is_demo ? 1 : 0,
                baseurl: SkillGame.backUrl
            });
            return page + '?' + query.toString();
        },

        finish: function (value) {
            var round = SkillGame.round;
            if (!round) {
                return Promise.reject(new Error('Rodada não encontrada.'));
            }

            return post('/api/skillgames/finish', { round: round, value: value || 0 }).then(function (data) {
                window.sessionStorage.removeItem('skill_round');
                SkillGame.round = null;
                return data;
            });
        },

        backToCasino: function () {
            try {
                window.parent.location.href = SkillGame.backUrl;
            } catch (e) {
                window.location.href = '/';
            }
        }
    };

    window.SkillGame = SkillGame;
})(window);
