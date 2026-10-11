<?php

namespace App\Services\Games;

/**
 * Gerador "provably fair": o hash da semente do servidor é mostrado antes do
 * jogo e a semente é revelada no fim, permitindo ao jogador conferir que o
 * resultado não foi alterado. Cada número sai de HMAC-SHA256(semente, "cliente:nonce:cursor").
 */
class ProvablyFair
{
    private int $cursor = 0;

    public function __construct(
        public readonly string $serverSeed,
        public readonly string $clientSeed,
        public readonly int $nonce = 0,
    ) {
    }

    public static function make(?string $clientSeed = null): self
    {
        $clientSeed = $clientSeed !== null && $clientSeed !== ''
            ? mb_substr(preg_replace('/[^\w\-]/u', '', $clientSeed), 0, 64)
            : bin2hex(random_bytes(8));

        return new self(bin2hex(random_bytes(32)), $clientSeed ?: bin2hex(random_bytes(8)), random_int(1, PHP_INT_MAX >> 16));
    }

    public function hash(): string
    {
        return hash('sha256', $this->serverSeed);
    }

    /** Número uniforme em [0, 1). */
    public function float(): float
    {
        $hmac = hash_hmac('sha256', $this->clientSeed . ':' . $this->nonce . ':' . $this->cursor++, $this->serverSeed);

        // 52 bits = precisão total de um double
        return hexdec(substr($hmac, 0, 13)) / 0x10000000000000;
    }

    /** Inteiro em [0, $max). */
    public function int(int $max): int
    {
        return (int) floor($this->float() * $max);
    }

    public function reveal(): array
    {
        return [
            'server_seed'      => $this->serverSeed,
            'server_seed_hash' => $this->hash(),
            'client_seed'      => $this->clientSeed,
            'nonce'            => $this->nonce,
        ];
    }
}
