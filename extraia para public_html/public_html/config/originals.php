<?php

/*
|--------------------------------------------------------------------------
| Jogos Originais (Mines, Dice, Plinko, Crash, Double)
|--------------------------------------------------------------------------
| Jogos próprios da plataforma, sem provedor externo. O resultado é gerado
| no servidor com "provably fair" (HMAC-SHA256 da semente do servidor).
*/

return [
    // Vantagem da casa (0.03 = RTP de 97%)
    'house_edge' => (float) env('ORIGINALS_HOUSE_EDGE', 0.03),

    'min_bet' => (float) env('ORIGINALS_MIN_BET', 0.10),
    'max_bet' => (float) env('ORIGINALS_MAX_BET', 1000),

    // Prêmio máximo por rodada (protege a banca)
    'max_win' => (float) env('ORIGINALS_MAX_WIN', 50000),

    'games' => ['mines', 'dice', 'plinko', 'crash', 'double'],
];
