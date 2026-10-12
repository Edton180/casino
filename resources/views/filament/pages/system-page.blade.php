<x-filament-panels::page>
    @php
        $needsUpdate = count($pending) > 0 || ($installedVersion && $installedVersion !== $codeVersion);
        $fmt = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->format('d/m/Y H:i') : '—';
        $card = 'padding:12px 14px;border-radius:10px;border:1px solid rgba(127,127,127,.25);min-width:0';
        $label = 'font-size:12px;opacity:.7;margin-bottom:2px';
        $value = 'font-weight:600;overflow-wrap:anywhere';
    @endphp

    {{-- Versão e atualização --}}
    <x-filament::section>
        <x-slot name="heading">Versão e atualização</x-slot>
        <x-slot name="description">Os dados, a configuração e os arquivos enviados nunca são apagados numa atualização.</x-slot>

        @if($needsUpdate)
            <div style="padding:12px 14px;border-radius:10px;margin-bottom:16px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.45)">
                <strong>Há uma atualização para concluir.</strong>
                @if(count($pending))
                    {{ count($pending) }} mudança(s) no banco de dados ainda não aplicada(s).
                @else
                    Os arquivos são da versão {{ $codeVersion }}, mas a instalação está registrada como {{ $installedVersion }}.
                @endif
                Clique em <em>Concluir atualização</em>.
            </div>
        @elseif($pendingError)
            <div style="padding:12px 14px;border-radius:10px;margin-bottom:16px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.45)">
                Não consegui consultar o banco: {{ $pendingError }}
            </div>
        @endif

        <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
            <div style="{{ $card }}"><div style="{{ $label }}">Versão dos arquivos</div><div style="{{ $value }}">{{ $codeVersion }}</div></div>
            <div style="{{ $card }}"><div style="{{ $label }}">Versão instalada</div><div style="{{ $value }}">{{ $installedVersion ?? '—' }}</div></div>
            <div style="{{ $card }}"><div style="{{ $label }}">Instalado em</div><div style="{{ $value }}">{{ $fmt($installedAt) }}</div></div>
            <div style="{{ $card }}"><div style="{{ $label }}">Última atualização</div><div style="{{ $value }}">{{ $fmt($updatedAt) }}</div></div>
            <div style="{{ $card }}"><div style="{{ $label }}">PHP / Laravel</div><div style="{{ $value }}">{{ $php }} / {{ $laravel }}</div></div>
            <div style="{{ $card }}"><div style="{{ $label }}">Espaço livre</div><div style="{{ $value }}">{{ $diskFree ?? '—' }}</div></div>
        </div>

        @if($debug && $environment === 'production')
            <p style="margin-top:12px;color:#dc2626">Atenção: o modo de depuração (APP_DEBUG) está ligado em produção. Desligue no .env (APP_DEBUG=false).</p>
        @endif
        @foreach($writable as $dir => $ok)
            @unless($ok)
                <p style="margin-top:8px;color:#dc2626">Sem permissão de escrita em {{ $dir }}: uploads, caches e backups podem falhar.</p>
            @endunless
        @endforeach

        <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            {{ $this->updateAction }}
        </div>

        <details style="margin-top:16px">
            <summary style="cursor:pointer;font-weight:600">Como atualizar para uma versão nova</summary>
            <ul style="margin-top:8px;padding-left:18px;list-style:disc;line-height:1.7">
                <li><strong>Hospedagem compartilhada (cPanel, Hostinger...):</strong> envie os arquivos do ZIP novo por cima dos atuais pelo gerenciador de arquivos ou FTP (o ZIP não traz o <code>.env</code> nem <code>public/storage</code>, então nada seu é sobrescrito) e depois clique em <em>Concluir atualização</em>.</li>
                <li><strong>VPS ou seu computador:</strong> <code>bash instalar.sh --update</code> (baixa a versão nova, faz backup e conclui sozinho).</li>
                <li><strong>Windows:</strong> <code>instalar-windows.bat</code> → Atualizar.</li>
            </ul>
        </details>
    </x-filament::section>

    {{-- Backups --}}
    <x-filament::section>
        <x-slot name="heading">Backups</x-slot>
        <x-slot name="description">Banco de dados, .env e arquivos enviados num .zip. Ficam em storage/app/backups (protegido do acesso pelo navegador). Guarde cópias fora do servidor.</x-slot>

        <div style="margin-bottom:12px">{{ $this->backupAction }}</div>

        @if(count($backups))
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:14px">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid rgba(127,127,127,.3)">
                            <th style="padding:8px 10px">Arquivo</th>
                            <th style="padding:8px 10px;white-space:nowrap">Data</th>
                            <th style="padding:8px 10px;white-space:nowrap">Tamanho</th>
                            <th style="padding:8px 10px">Versão</th>
                            <th style="padding:8px 10px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $b)
                            <tr wire:key="backup-{{ $b['name'] }}" style="border-bottom:1px solid rgba(127,127,127,.15)">
                                <td style="padding:8px 10px;font-family:monospace;font-size:12px;overflow-wrap:anywhere">{{ $b['name'] }}</td>
                                <td style="padding:8px 10px;white-space:nowrap">{{ date('d/m/Y H:i', $b['created_at']) }}</td>
                                <td style="padding:8px 10px;white-space:nowrap">{{ \App\Console\Commands\SystemBackup::bytes($b['size']) }}</td>
                                <td style="padding:8px 10px">{{ $b['manifest']['version'] ?? '—' }}</td>
                                <td style="padding:8px 10px">
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end">
                                        {{ ($this->downloadBackupAction)(['name' => $b['name']]) }}
                                        {{ ($this->restoreBackupAction)(['name' => $b['name']]) }}
                                        {{ ($this->deleteBackupAction)(['name' => $b['name']]) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="opacity:.7">Nenhum backup ainda.</p>
        @endif
    </x-filament::section>

    {{-- Saída do último comando --}}
    @if($output)
        <x-filament::section>
            <x-slot name="heading">Resultado</x-slot>
            <pre style="overflow-x:auto;white-space:pre-wrap;border-radius:10px;background:#0b1120;color:#e5e7eb;padding:14px;font-size:12px;line-height:1.5">{{ $output }}</pre>
        </x-filament::section>
    @endif

    {{-- Zona de perigo --}}
    <x-filament::section>
        <x-slot name="heading">Desinstalar</x-slot>
        <x-slot name="description">Remove o sistema desta hospedagem, com backup final fora da pasta do site.</x-slot>
        <p style="margin-bottom:12px;line-height:1.6">
            Apaga as tabelas do cassino (tabelas de outros sistemas no mesmo banco ficam), o <code>.env</code>, os arquivos enviados, logs e caches.
            O banco e o usuário do MySQL em si são apagados pelo painel da hospedagem (ou pelo assistente <code>bash instalar.sh --uninstall</code> num VPS/computador).
        </p>
        {{ $this->uninstallAction }}
    </x-filament::section>

</x-filament-panels::page>
