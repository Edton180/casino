<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar padrão do painel gerado no próprio servidor (iniciais do nome num círculo).
 * Substitui o ui-avatars.com: funciona sem internet e não envia nomes de usuários para fora.
 */
class LocalAvatarProvider implements AvatarProvider
{
    private const COLORS = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#7c3aed', '#db2777', '#2563eb'];

    public function get(Model $record): string
    {
        $name = trim((string) Filament::getNameForDefaultAvatar($record));
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        $color = self::COLORS[abs(crc32($name)) % count(self::COLORS)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            . '<rect width="64" height="64" fill="' . $color . '"/>'
            . '<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="600" fill="#ffffff">'
            . htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8')
            . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
