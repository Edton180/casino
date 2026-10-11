<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rodada de um jogo original (Mines, Dice, Plinko, Crash, Double).
 */
class OriginalRound extends Model
{
    protected $table = 'original_rounds';

    protected $fillable = [
        'round',
        'user_id',
        'game',
        'bet',
        'multiplier',
        'payout',
        'status',
        'change_bonus',
        'server_seed',
        'server_seed_hash',
        'client_seed',
        'nonce',
        'state',
        'settled_at',
    ];

    protected $hidden = ['server_seed'];

    protected $casts = [
        'bet'        => 'float',
        'multiplier' => 'float',
        'payout'     => 'float',
        'nonce'      => 'integer',
        'state'      => 'array',
        'settled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
