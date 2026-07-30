<?php

declare(strict_types=1);

namespace App\Filament\Avatares;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gera o avatar localmente, como um SVG embutido em data: URI.
 *
 * Substitui o `UiAvatarsProvider` que o Filament usa por omissão, e que pede a
 * imagem a `https://ui-avatars.com` passando o **nome do utilizador no URL**.
 * Isso enviava o nome de cada membro do staff para um serviço de terceiros em
 * cada carregamento de página do painel — dados pessoais a sair da plataforma
 * sem necessidade nem base para o fazer, o que a secção 10 do agent.md trata
 * como exposição a evitar.
 *
 * Foi detetado porque o CSP do painel bloqueou os pedidos: a política existia
 * para conter XSS e acabou por revelar uma fuga de dados que ninguém tinha
 * procurado. A correção certa era parar de contactar o serviço, não autorizar
 * o domínio.
 *
 * Sem rede, sem terceiros, e compatível com `img-src 'self' data:`.
 */
class AvatarLocalComIniciais implements AvatarProvider
{
    public function get(Model $record): string
    {
        $nome = (string) ($record->getAttribute('name') ?? '');

        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($this->iniciais($nome)));
    }

    private function iniciais(string $nome): string
    {
        $palavras = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($palavras === []) {
            return '?';
        }

        $primeira = Str::upper(Str::substr((string) $palavras[0], 0, 1));

        if (count($palavras) === 1) {
            return $primeira;
        }

        return $primeira.Str::upper(Str::substr((string) $palavras[count($palavras) - 1], 0, 1));
    }

    private function svg(string $iniciais): string
    {
        $texto = htmlspecialchars($iniciais, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
                <rect width="64" height="64" fill="#1d4ed8"/>
                <text x="50%" y="50%" dy=".35em" text-anchor="middle"
                      font-family="system-ui, sans-serif" font-size="26" font-weight="600" fill="#ffffff">{$texto}</text>
            </svg>
            SVG;
    }
}
