/**
 * Cliente comum dos jogos originais: autenticação pelo token da URL,
 * chamadas à API, saldo, notificações e controle de aposta.
 */
(function (window, document) {
  'use strict';

  var params = new URLSearchParams(window.location.search);

  var Originals = {
    token: params.get('token'),
    game: null,
    info: null,
    currency: 'R$',

    init: function (game) {
      Originals.game = game;
      if (!Originals.token) {
        Originals.toast('Abra o jogo pelo cassino para jogar.', 'error');
        return Promise.reject(new Error('no token'));
      }
      return Originals.api('info').then(function (info) {
        Originals.info = info;
        Originals.currency = info.currency || 'R$';
        Originals.setBalance(info.balance);
        document.querySelectorAll('[data-rtp]').forEach(function (el) { el.textContent = info.rtp + '%'; });
        return info;
      });
    },

    api: function (path, data) {
      var url = '/api/originals/' + Originals.game + '/' + path;
      var body = Object.assign({ token: Originals.token }, data || {});
      return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body)
      }).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (json) {
          if (!response.ok) {
            var error = new Error(json.message || 'Não foi possível completar a jogada.');
            error.status = response.status;
            error.body = json;
            throw error;
          }
          if (typeof json.balance !== 'undefined') Originals.setBalance(json.balance);
          return json;
        });
      });
    },

    money: function (value) {
      var n = Number(value || 0);
      return Originals.currency + ' ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    setBalance: function (value) {
      document.querySelectorAll('[data-balance]').forEach(function (el) { el.textContent = Originals.money(value); });
      // atualiza o saldo exibido no cassino (mesma origem)
      try { window.parent.postMessage({ type: 'balance:update', balance: value }, window.location.origin); } catch (e) {}
    },

    toast: function (message, type) {
      var el = document.createElement('div');
      el.className = 'og-toast ' + (type || '');
      el.textContent = message;
      document.body.appendChild(el);
      setTimeout(function () { el.style.opacity = '0'; }, 2600);
      setTimeout(function () { el.remove(); }, 3000);
    },

    fail: function (error) {
      Originals.toast(error && error.message ? error.message : 'Erro inesperado.', 'error');
    },

    /** Liga os botões ½ / 2× / mín / máx ao campo de aposta. */
    betInput: function (input) {
      var root = input.closest('.og-field');
      var clamp = function (v) {
        var info = Originals.info || { min_bet: 0.1, max_bet: 1000 };
        v = Math.max(info.min_bet, Math.min(info.max_bet, v));
        return Math.round(v * 100) / 100;
      };
      root.querySelectorAll('[data-bet]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var v = Number(input.value) || 0;
          var op = btn.getAttribute('data-bet');
          if (op === 'half') v = v / 2;
          if (op === 'double') v = v * 2;
          input.value = clamp(v).toFixed(2);
        });
      });
      input.addEventListener('change', function () { input.value = clamp(Number(input.value) || 0).toFixed(2); });
      return function () { return clamp(Number(input.value) || 0); };
    },

    fairness: function (el, fairness) {
      if (!el || !fairness) return;
      el.innerHTML = '<details><summary>Jogo justo (provably fair)</summary>' +
        'Hash da semente: ' + fairness.server_seed_hash + '<br>' +
        (fairness.server_seed ? 'Semente do servidor: ' + fairness.server_seed + '<br>' : 'A semente é revelada ao fim da rodada.<br>') +
        'Semente do cliente: ' + fairness.client_seed + ' · Nonce: ' + fairness.nonce + '</details>';
    }
  };

  window.Originals = Originals;
})(window, document);
