<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gateways';

    /**
     * Campos com credenciais. São mascarados no modo demo e nunca devem
     * ser enviados para o frontend.
     */
    public const SECRET_FIELDS = [
        'mp_access_token',
        'mp_webhook_key',
        'pagseguro_token',
        'pagarme_secret_key',
        'pagarme_webhook_password',
        'asaas_api_key',
        'asaas_webhook_key',
        'stripe_secret_key',
        'stripe_webhook_key',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Mercado Pago
        'mp_is_enable',
        'mp_credit_card_enable',
        'mp_access_token',
        'mp_public_key',
        'mp_webhook_key',

        // PagSeguro / PagBank
        'pagseguro_is_enable',
        'pagseguro_credit_card_enable',
        'pagseguro_sandbox',
        'pagseguro_token',

        // Pagar.me
        'pagarme_is_enable',
        'pagarme_credit_card_enable',
        'pagarme_secret_key',
        'pagarme_webhook_user',
        'pagarme_webhook_password',

        // Asaas
        'asaas_is_enable',
        'asaas_credit_card_enable',
        'asaas_sandbox',
        'asaas_api_key',
        'asaas_webhook_key',
        'asaas_pix_payout_enable',

        // Stripe
        'stripe_is_enable',
        'stripe_pix_enable',
        'stripe_production',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_key',
    ];

    protected $casts = [
        'mp_is_enable'                 => 'boolean',
        'mp_credit_card_enable'        => 'boolean',
        'pagseguro_is_enable'          => 'boolean',
        'pagseguro_credit_card_enable' => 'boolean',
        'pagseguro_sandbox'            => 'boolean',
        'pagarme_is_enable'            => 'boolean',
        'pagarme_credit_card_enable'   => 'boolean',
        'asaas_is_enable'              => 'boolean',
        'asaas_credit_card_enable'     => 'boolean',
        'asaas_sandbox'                => 'boolean',
        'asaas_pix_payout_enable'      => 'boolean',
        'stripe_is_enable'             => 'boolean',
        'stripe_pix_enable'            => 'boolean',
        'stripe_production'            => 'boolean',
    ];

    protected $hidden = ['updated_at'];

    /**
     * Valor real de uma credencial (ignora a máscara do modo demo).
     */
    public function credential(string $field): ?string
    {
        $value = $this->getAttributes()[$field] ?? null;

        return $value === null ? null : trim((string) $value);
    }

    /**
     * No modo demo as credenciais aparecem mascaradas no painel.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, self::SECRET_FIELDS, true) && env('APP_DEMO') && !empty($value)) {
            return '*********************';
        }

        return $value;
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        if (env('APP_DEMO')) {
            foreach (self::SECRET_FIELDS as $field) {
                if (!empty($attributes[$field])) {
                    $attributes[$field] = '*********************';
                }
            }
        }

        return $attributes;
    }
}
