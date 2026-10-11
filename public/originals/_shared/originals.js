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
      Originals.stageBar();
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

    /* ---------------------------------------------------------- efeitos */

    muted: (function () { try { return localStorage.getItem('og-muted') === '1'; } catch (e) { return false; } })(),
    _audio: null,

    /** Sons sintetizados (sem arquivos): click, win, big, lose, tick. */
    sound: function (type) {
      if (Originals.muted) return;
      try {
        var ctx = Originals._audio || (Originals._audio = new (window.AudioContext || window.webkitAudioContext)());
        var notes = {
          click: [[660, 0, .05, 'square', .04]],
          tick:  [[880, 0, .03, 'sine', .05]],
          lose:  [[220, 0, .18, 'sawtooth', .06], [150, .12, .25, 'sawtooth', .05]],
          win:   [[523, 0, .12, 'triangle', .09], [659, .09, .12, 'triangle', .09], [784, .18, .2, 'triangle', .09]],
          big:   [[523, 0, .12, 'triangle', .1], [659, .1, .12, 'triangle', .1], [784, .2, .12, 'triangle', .1], [1047, .3, .35, 'triangle', .1]]
        }[type] || [];
        notes.forEach(function (n) {
          var o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + n[1];
          o.type = n[3]; o.frequency.value = n[0];
          g.gain.setValueAtTime(n[4], t); g.gain.exponentialRampToValueAtTime(.0001, t + n[2]);
          o.connect(g); g.connect(ctx.destination); o.start(t); o.stop(t + n[2] + .02);
        });
      } catch (e) {}
    },

    /** Barra do palco com histórico das últimas rodadas e botão de som. */
    stageBar: function () {
      var stage = document.querySelector('.og-stage');
      if (!stage || stage.querySelector('.og-bar')) return;
      var bar = document.createElement('div');
      bar.className = 'og-bar';
      bar.innerHTML = '<div class="og-history"></div><button class="og-sound" type="button" title="Som"></button>';
      stage.appendChild(bar);
      var btn = bar.querySelector('.og-sound');
      var paint = function () { btn.textContent = Originals.muted ? '🔇' : '🔊'; };
      btn.addEventListener('click', function () {
        Originals.muted = !Originals.muted;
        try { localStorage.setItem('og-muted', Originals.muted ? '1' : '0'); } catch (e) {}
        paint(); Originals.sound('click');
      });
      paint();
      var saved = [];
      try { saved = JSON.parse(localStorage.getItem('og-history-' + Originals.game) || '[]'); } catch (e) {}
      saved.slice(-12).forEach(function (h) { Originals._chip(h.m, h.w, false); });
    },

    _chip: function (multiplier, won, store) {
      var box = document.querySelector('.og-history');
      if (!box) return;
      var chip = document.createElement('span');
      chip.className = 'og-chip' + (won ? ' win' : '') + (won && multiplier >= 10 ? ' big' : '');
      chip.textContent = Number(multiplier || 0).toFixed(2).replace('.', ',') + 'x';
      box.appendChild(chip);
      while (box.children.length > 14) box.removeChild(box.firstChild);
      if (store) {
        try {
          var key = 'og-history-' + Originals.game;
          var list = JSON.parse(localStorage.getItem(key) || '[]');
          list.push({ m: multiplier, w: !!won });
          localStorage.setItem(key, JSON.stringify(list.slice(-14)));
        } catch (e) {}
      }
    },

    /**
     * Mostra o resultado de uma rodada: histórico, som e animação.
     * opts: { won, payout, multiplier, quiet }
     */
    result: function (opts) {
      Originals._chip(opts.multiplier, opts.won, true);
      var stage = document.querySelector('.og-stage');
      if (!opts.won) {
        Originals.sound('lose');
        if (stage) { stage.classList.remove('og-flash-lose'); void stage.offsetWidth; stage.classList.add('og-flash-lose'); }
        return;
      }
      var big = Number(opts.multiplier) >= 10;
      Originals.sound(big ? 'big' : 'win');
      if (opts.quiet || !stage) return;
      var old = stage.querySelector('.og-win'); if (old) old.remove();
      var win = document.createElement('div');
      win.className = 'og-win';
      win.innerHTML = '<div class="box' + (big ? ' big' : '') + '"><div class="m">' +
        Number(opts.multiplier).toFixed(2).replace('.', ',') + 'x</div><div class="v">+' + Originals.money(opts.payout) + '</div></div>';
      stage.appendChild(win);
      Originals.confetti(stage, big ? 140 : 60);
      setTimeout(function () { win.classList.add('out'); }, 1600);
      setTimeout(function () { win.remove(); }, 2100);
    },

    confetti: function (stage, count) {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      var canvas = document.createElement('canvas');
      canvas.className = 'og-confetti';
      stage.appendChild(canvas);
      var w = canvas.width = stage.clientWidth, h = canvas.height = stage.clientHeight;
      var ctx = canvas.getContext('2d');
      var colors = ['#00e701', '#ffc83d', '#1475e1', '#ff5d8f', '#ffffff', '#8b5cf6'];
      var parts = [];
      for (var i = 0; i < count; i++) {
        parts.push({ x: w / 2, y: h * .45, vx: (Math.random() - .5) * 12, vy: -Math.random() * 11 - 3,
          s: 4 + Math.random() * 5, r: Math.random() * 6, vr: (Math.random() - .5) * .3, c: colors[i % colors.length] });
      }
      var start = performance.now();
      (function frame(now) {
        var t = now - start;
        ctx.clearRect(0, 0, w, h);
        parts.forEach(function (p) {
          p.vy += .32; p.vx *= .99; p.x += p.vx; p.y += p.vy; p.r += p.vr;
          ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.globalAlpha = Math.max(0, 1 - t / 1800);
          ctx.fillStyle = p.c; ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2); ctx.restore();
        });
        if (t < 1800) requestAnimationFrame(frame); else canvas.remove();
      })(start);
    },

    shake: function (el) {
      if (!el) return;
      el.classList.remove('og-shake'); void el.offsetWidth; el.classList.add('og-shake');
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
