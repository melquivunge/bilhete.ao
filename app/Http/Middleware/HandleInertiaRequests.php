<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * O template de raiz carregado na primeira visita.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Propriedades partilhadas por todas as páginas.
     *
     * Nada de dados pessoais ou financeiros é partilhado por omissão: tudo o que
     * for acrescentado aqui viaja em todas as respostas Inertia, inclusive as de
     * páginas públicas.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
        ];
    }
}
