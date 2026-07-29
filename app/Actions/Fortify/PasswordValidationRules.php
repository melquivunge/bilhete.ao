<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Regras de validação das palavras-passe de clientes.
     *
     * `Password::default()` sozinho exige apenas 8 caracteres. Para uma
     * plataforma que vai guardar histórico de compras e, a partir do Marco 4,
     * ligar-se a pagamentos, isso é aquém do razoável — daí o mínimo de 10.
     *
     * `max:255` não é cosmético: sem limite superior, um pedido com uma
     * palavra-passe de vários megabytes obriga o servidor a hashá-la antes de a
     * rejeitar, o que é negação de serviço barata.
     *
     * `uncompromised()`, que verifica a palavra-passe contra fugas conhecidas,
     * **não** está aqui de propósito: faz uma chamada HTTP a um serviço externo
     * em cada registo, o que introduz uma dependência de rede num caminho
     * crítico e tornaria a suíte de testes dependente da internet. Fica como
     * B-040, para ser decidido com o tratamento de falhas que merece.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'max:255', Password::default()->min(10), 'confirmed'];
    }
}
