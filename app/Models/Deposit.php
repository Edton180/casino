<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deposit extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'deposits';
    protected $appends = ['dateHumanReadable', 'createdAt'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'amount',
        'type',
        'proof',
        'currency',
        'symbol',
        'status'
    ];

    /**
     * @return mixed
     */
    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at']);
    }

    /**
     * @return mixed
     */
    public function getDateHumanReadableAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    /**
     * @return BelongsTo
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Cobrança/lançamento que credita este depósito (status 1 = já creditado na carteira). */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'payment_id', 'payment_id');
    }

    /**
     * Marcado como aprovado mas nunca creditado: lançado pelo painel em versões anteriores
     * (que só gravavam o registro) ou aprovado à mão sem passar pelo crédito.
     */
    public function isApprovedWithoutCredit(): bool
    {
        if ((int) $this->status !== 1) {
            return false;
        }
        $credited = $this->getAttribute('credited');   // withExists na listagem
        if ($credited === null) {
            $credited = $this->payment_id && Transaction::where('payment_id', $this->payment_id)->where('status', 1)->exists();
        }

        return !$credited;
    }

    /**
     * @return BelongsToMany
     */
    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
