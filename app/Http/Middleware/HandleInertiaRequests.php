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
     * Tudo o que for acrescentado aqui viaja em todas as respostas Inertia,
     * incluindo as de páginas públicas. Por isso a identidade partilhada é
     * deliberadamente mínima: id e nome, o suficiente para a interface saber
     * quem está autenticado.
     *
     * O email nunca entra aqui. É um dado pessoal, é o identificador de login, e
     * não é preciso para desenhar interface nenhuma. Quando o Marco 3 trouxer
     * checkout, quem precisar do email vai buscá-lo à página que o justifica.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $utilizador = $request->user();

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $utilizador === null ? null : [
                    'id' => $utilizador->id,
                    'name' => $utilizador->name,
                ],
            ],
        ];
    }
}
