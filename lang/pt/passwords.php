<?php

declare(strict_types=1);

return [
    'reset' => 'A sua palavra-passe foi redefinida.',

    /*
     * Esta mensagem é devolvida tanto quando a conta existe como quando não
     * existe — ver App\Http\Responses\RespostaUniformeDeRecuperacao. Por isso o
     * texto é deliberadamente condicional: não afirma que a conta existe.
     */
    'sent' => 'Se existir uma conta com esse email, enviámos uma ligação para definir uma nova palavra-passe.',

    'throttled' => 'Aguarde antes de tentar novamente.',
    'token' => 'Esta ligação de recuperação é inválida ou expirou.',
    'user' => 'Se existir uma conta com esse email, enviámos uma ligação para definir uma nova palavra-passe.',
];
