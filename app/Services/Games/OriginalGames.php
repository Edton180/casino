<?php

namespace App\Services\Games;

/**
 * Regras matemáticas dos jogos originais (sem acesso a banco).
 * Todas recebem o gerador ProvablyFair e a vantagem da casa.
 */
class OriginalGames
{
    public const MINES_CELLS = 25;
    public const CRASH_GROWTH = 0.00006; // multiplicador = e^(0.00006 * ms)
    public const CRASH_MAX = 1000.0;

    /** Tower: 10 andares com 5 portas; 1 a 4 bombas por andar (como no Luck2x). */
    public const TOWER_ROWS = 10;
    public const TOWER_COLS = 5;

    /** HiLo: cartas sorteadas com reposição (A=1 ... K=13). */
    public const HILO_DECK = 200;

    /**
     * Wheel: 54 casas (mesma distribuição da roda do Luck2x).
     * Y = amarelo 50x, G = verde 5x, R = vermelho 3x, B = preto 2x.
     */
    public const WHEEL_LAYOUT = 'YBGBRBRBGBRBGBRBRBGBRBGBRBRRGBRBRBGBRBGBRBRBGBRBGBRBRB';
    public const WHEEL_COLORS = ['B' => 'black', 'R' => 'red', 'G' => 'green', 'Y' => 'yellow'];
    public const WHEEL_PAYOUT = ['black' => 2.0, 'red' => 3.0, 'green' => 5.0, 'yellow' => 50.0];

    /** Tabelas de Plinko (12 linhas). São normalizadas para o RTP configurado. */
    public const PLINKO = [
        'low'    => [10, 3, 1.6, 1.4, 1.1, 1, 0.5, 1, 1.1, 1.4, 1.6, 3, 10],
        'medium' => [33, 11, 4, 2, 1.1, 0.6, 0.3, 0.6, 1.1, 2, 4, 11, 33],
        'high'   => [170, 24, 8.1, 2, 0.7, 0.2, 0.2, 0.2, 0.7, 2, 8.1, 24, 170],
    ];

    /**
     * Dice: rola 0.00–99.99. "over" ganha se o número for maior que o alvo.
     */
    public static function dice(ProvablyFair $pf, float $target, bool $over, float $edge): array
    {
        $roll = floor($pf->float() * 10000) / 100;
        $chance = $over ? 100 - $target : $target;
        $won = $over ? $roll > $target : $roll < $target;

        return [
            'roll'       => $roll,
            'chance'     => round($chance, 2),
            'multiplier' => $won ? self::diceMultiplier($chance, $edge) : 0.0,
            'payout_multiplier' => self::diceMultiplier($chance, $edge),
            'won'        => $won,
        ];
    }

    public static function diceMultiplier(float $chance, float $edge): float
    {
        return floor((100 * (1 - $edge)) / $chance * 10000) / 10000;
    }

    /**
     * Double (roleta de 15 casas): 0 = branco (14x), 1–7 = vermelho (2x), 8–14 = preto (2x).
     */
    public static function double(ProvablyFair $pf, string $color): array
    {
        $slot = $pf->int(15);
        $result = $slot === 0 ? 'white' : ($slot <= 7 ? 'red' : 'black');
        $won = $result === $color;

        return [
            'slot'       => $slot,
            'color'      => $result,
            'multiplier' => $won ? ($color === 'white' ? 14.0 : 2.0) : 0.0,
            'won'        => $won,
        ];
    }

    /**
     * Plinko: 12 pinos; cada um desvia para a direita com 50% de chance.
     */
    public static function plinko(ProvablyFair $pf, string $risk, float $edge): array
    {
        $path = [];
        $bucket = 0;
        for ($i = 0; $i < 12; $i++) {
            $right = $pf->float() >= 0.5;
            $path[] = $right ? 1 : 0;
            $bucket += $right ? 1 : 0;
        }

        $table = self::plinkoTable($risk, $edge);

        return [
            'path'       => $path,
            'bucket'     => $bucket,
            'multiplier' => $table[$bucket],
            'won'        => $table[$bucket] > 0,
            'table'      => $table,
        ];
    }

    public static function plinkoTable(string $risk, float $edge): array
    {
        $base = self::PLINKO[$risk] ?? self::PLINKO['medium'];

        // RTP teórico da tabela (distribuição binomial de 12 linhas)
        $rtp = 0;
        foreach ($base as $k => $mult) {
            $rtp += $mult * self::binomial(12, $k) / 4096;
        }

        $factor = (1 - $edge) / $rtp;

        return array_map(fn ($m) => floor($m * $factor * 100) / 100, $base);
    }

    /** Posições das minas (0–24). */
    public static function minesLayout(ProvablyFair $pf, int $mines): array
    {
        $cells = range(0, self::MINES_CELLS - 1);
        for ($i = self::MINES_CELLS - 1; $i > 0; $i--) {
            $j = $pf->int($i + 1);
            [$cells[$i], $cells[$j]] = [$cells[$j], $cells[$i]];
        }
        $layout = array_slice($cells, 0, $mines);
        sort($layout);

        return $layout;
    }

    /** Multiplicador após revelar $safe casas sem mina. */
    public static function minesMultiplier(int $mines, int $safe, float $edge): float
    {
        if ($safe <= 0) {
            return 0.0;
        }

        $mult = 1 - $edge;
        for ($i = 0; $i < $safe; $i++) {
            $mult *= (self::MINES_CELLS - $i) / (self::MINES_CELLS - $mines - $i);
        }

        return floor($mult * 10000) / 10000;
    }

    /** Ponto de quebra do Crash (com probabilidade = edge de quebrar em 1.00x). */
    public static function crashPoint(ProvablyFair $pf, float $edge): float
    {
        $r = $pf->float();
        $point = floor(((1 - $edge) / (1 - $r)) * 100) / 100;

        return max(1.0, min(self::CRASH_MAX, $point));
    }

    public static function crashMultiplierAt(int $elapsedMs): float
    {
        return floor(exp(self::CRASH_GROWTH * max(0, $elapsedMs)) * 100) / 100;
    }

    /** Tempo (ms) até o multiplicador atingir $multiplier. */
    public static function crashTimeFor(float $multiplier): int
    {
        return (int) ceil(log(max(1.0, $multiplier)) / self::CRASH_GROWTH);
    }

    /* ---------------------------------------------------------------- Tower */

    /** Posição das bombas em cada andar. */
    public static function towerLayout(ProvablyFair $pf, int $bombs): array
    {
        $rows = [];
        for ($row = 0; $row < self::TOWER_ROWS; $row++) {
            $cols = range(0, self::TOWER_COLS - 1);
            for ($i = self::TOWER_COLS - 1; $i > 0; $i--) {
                $j = $pf->int($i + 1);
                [$cols[$i], $cols[$j]] = [$cols[$j], $cols[$i]];
            }
            $layout = array_slice($cols, 0, $bombs);
            sort($layout);
            $rows[] = $layout;
        }

        return $rows;
    }

    /** Multiplicador após subir $rows andares. */
    public static function towerMultiplier(int $bombs, int $rows, float $edge): float
    {
        if ($rows <= 0) {
            return 0.0;
        }

        $step = self::TOWER_COLS / (self::TOWER_COLS - $bombs);

        return floor((1 - $edge) * ($step ** $rows) * 10000) / 10000;
    }

    /* ----------------------------------------------------------------- HiLo */

    /** Sequência de cartas (0–51) da rodada. */
    public static function hiloDeck(ProvablyFair $pf): array
    {
        $deck = [];
        for ($i = 0; $i < self::HILO_DECK; $i++) {
            $deck[] = $pf->int(52);
        }

        return $deck;
    }

    public static function hiloRank(int $card): int
    {
        return $card % 13 + 1;
    }

    /** Palpites permitidos para a carta atual. */
    public static function hiloOptions(int $rank): array
    {
        return match (true) {
            $rank === 1  => ['higher', 'same'],
            $rank === 13 => ['lower', 'same'],
            default      => ['higher_eq', 'lower_eq'],
        };
    }

    /** Chance (0–1) de acerto do palpite. */
    public static function hiloChance(int $rank, string $guess): float
    {
        return match ($guess) {
            'higher_eq' => (14 - $rank) / 13,
            'lower_eq'  => $rank / 13,
            'higher'    => (13 - $rank) / 13,
            'lower'     => ($rank - 1) / 13,
            'same'      => 1 / 13,
            default     => 0.0,
        };
    }

    public static function hiloWins(int $current, int $next, string $guess): bool
    {
        return match ($guess) {
            'higher_eq' => $next >= $current,
            'lower_eq'  => $next <= $current,
            'higher'    => $next > $current,
            'lower'     => $next < $current,
            'same'      => $next === $current,
            default     => false,
        };
    }

    /** A vantagem da casa é aplicada uma única vez sobre o produto das odds. */
    public static function hiloMultiplier(float $odds, float $edge): float
    {
        return floor((1 - $edge) * $odds * 10000) / 10000;
    }

    /* ---------------------------------------------------------------- Wheel */

    public static function wheel(ProvablyFair $pf, string $color): array
    {
        $segment = $pf->int(strlen(self::WHEEL_LAYOUT));
        $result = self::WHEEL_COLORS[self::WHEEL_LAYOUT[$segment]];
        $won = $result === $color;

        return [
            'segment'    => $segment,
            'color'      => $result,
            'multiplier' => $won ? self::WHEEL_PAYOUT[$color] : 0.0,
            'won'        => $won,
        ];
    }

    private static function binomial(int $n, int $k): int
    {
        $r = 1;
        for ($i = 1; $i <= $k; $i++) {
            $r = $r * ($n - $k + $i) / $i;
        }

        return (int) round($r);
    }
}
