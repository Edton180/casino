<?php

namespace App\Services\Payments;

use App\Models\User;

/**
 * Dados de uma cobrança de depósito enviados para o gateway.
 */
class ChargeData
{
    public function __construct(
        public readonly User $user,
        public readonly float $amount,
        public readonly string $document,
        public readonly string $reference,
        public readonly string $currency = 'BRL',
        public readonly string $description = 'Depósito',
    ) {
    }

    /** Valor em centavos (evita erros de arredondamento de float). */
    public function amountInCents(): int
    {
        return (int) round($this->amount * 100);
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->user->name))[0] ?: 'Cliente';
    }

    public function lastName(): string
    {
        if (!empty($this->user->last_name)) {
            return $this->user->last_name;
        }

        $parts = explode(' ', trim($this->user->name), 2);

        return $parts[1] ?? $parts[0];
    }

    public function fullName(): string
    {
        return trim($this->firstName() . ' ' . $this->lastName());
    }

    /**
     * Telefone separado em DDD e número (formato brasileiro).
     * @return array{area_code: string, number: string}|null
     */
    public function phone(): ?array
    {
        $digits = preg_replace('/\D/', '', (string) $this->user->phone);
        if (strlen($digits) > 11 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return ['area_code' => substr($digits, 0, 2), 'number' => substr($digits, 2)];
    }

    public function documentType(): string
    {
        return strlen($this->document) > 11 ? 'CNPJ' : 'CPF';
    }
}
