<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'payment_method',
        'gateway',
        'gateway_response',
        'gateway_error',
        'price',
        'currency',
        'status',
        'paid_at',
        'expired_at',
        'canceled_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'canceled_at' => 'datetime',
        'gateway_response' => 'array',
        'gateway_error' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'payment_id', 'payment_id');
    }
}
