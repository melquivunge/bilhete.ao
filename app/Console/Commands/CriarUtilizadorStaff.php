<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\PasswordValidationRules;
use App\Actions\Identity\PromoverUtilizadorAStaff;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password as pedirPalavraPasse;
use function Laravel\Prompts\text;

/**
 * Cria — ou promove — uma conta com acesso ao painel administrativo.
 *
 * Existe porque não há outra forma legítima de criar o primeiro staff: a coluna
 * `is_staff` não é atribuível em massa e o registo público nunca a concede.
 *
 * A palavra-passe é **sempre** pedida interativamente e nunca aceita como
 * argumento nem como variável de ambiente. Um argumento ficaria no histórico da
 * shell e na lista de processos da máquina — a secção 10 do agent.md proíbe
 * expor credenciais, e o histórico da shell é exposição.
 */
class CriarUtilizadorStaff extends Command
{
    use PasswordValidationRules;

    protected $signature = 'bilhete:criar-staff
                            {--email= : Email da conta}
                            {--name= : Nome a apresentar}';

    protected $description = 'Cria ou promove uma conta com acesso ao painel administrativo';

    public function handle(PromoverUtilizadorAStaff $promover): int
    {
        $email = $this->option('email') ?? text(
            label: 'Email da conta de staff',
            required: true,
        );

        $existente = User::where('email', $email)->first();

        if ($existente !== null) {
            return $this->promoverExistente($existente, $promover);
        }

        $nome = $this->option('name') ?? text(
            label: 'Nome a apresentar no painel',
            required: true,
        );

        $palavraPasse = pedirPalavraPasse(
            label: 'Palavra-passe',
            required: true,
        );

        $confirmacao = pedirPalavraPasse(
            label: 'Confirmar palavra-passe',
            required: true,
        );

        $validador = Validator::make(
            [
                'name' => $nome,
                'email' => $email,
                'password' => $palavraPasse,
                'password_confirmation' => $confirmacao,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
                'password' => $this->passwordRules(),
            ]
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->components->error($erro);
            }

            return self::FAILURE;
        }

        $utilizador = new User;
        $utilizador->name = $nome;
        $utilizador->email = $email;
        $utilizador->password = Hash::make($palavraPasse);
        $utilizador->save();

        $promover->executar($utilizador);

        $this->components->info("Conta de staff criada: {$utilizador->email}");
        $this->components->warn('O painel fica em /admin. Esta conta tem acesso administrativo — trate-a como tal.');

        return self::SUCCESS;
    }

    private function promoverExistente(User $utilizador, PromoverUtilizadorAStaff $promover): int
    {
        if ($utilizador->is_staff === true) {
            $this->components->info("A conta {$utilizador->email} já tem acesso ao painel.");

            return self::SUCCESS;
        }

        // Promover uma conta existente é conceder privilégio administrativo a
        // alguém que já se autentica como cliente. Não acontece sem confirmação.
        if (! confirm("A conta {$utilizador->email} já existe. Conceder-lhe acesso ao painel?", default: false)) {
            $this->components->warn('Nada foi alterado.');

            return self::FAILURE;
        }

        $promover->executar($utilizador);

        $this->components->info("Acesso ao painel concedido a {$utilizador->email}.");

        return self::SUCCESS;
    }
}
