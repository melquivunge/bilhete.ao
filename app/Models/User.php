<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * `is_staff` está fora do #[Fillable] de propósito, e tem de continuar fora.
 *
 * Clientes e staff partilham este modelo e o guard `web` (ADR-006), pelo que a
 * única fronteira entre um cliente e o painel administrativo é a autorização. Se
 * `is_staff` fosse atribuível em massa, essa fronteira caía no formulário público
 * de registo. A promoção passa por App\Actions\Identity\PromoverUtilizadorAStaff.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * O valor por omissão também vive no modelo, e não só na base de dados.
     *
     * Sem isto, uma instância recém-criada tem `is_staff` a null até ser
     * recarregada — o Laravel não relê a linha depois de inserir. O
     * canAccessPanel() nega perante null, por comparar estritamente com true,
     * mas deixar null a circular é uma armadilha para quem escrever a próxima
     * verificação de forma menos estrita.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_staff' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
        ];
    }

    /**
     * Nega o acesso ao painel por omissão.
     *
     * A comparação é estrita contra `true`. Não é preciosismo: se a coluna
     * estivesse ausente do resultado, ou viesse como null, `1`, ou string vazia,
     * uma verificação laxa poderia conceder acesso por acidente. O único caso que
     * concede é o valor booleano verdadeiro.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_staff === true;
    }
}
