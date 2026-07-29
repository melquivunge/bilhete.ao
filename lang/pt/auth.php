<?php

declare(strict_types=1);

return [
    /*
     * A mesma mensagem para credencial errada e para conta inexistente. Um texto
     * diferente em cada caso transformaria o formulário de entrada num oráculo
     * para descobrir quem tem conta na plataforma — enumeração de recursos,
     * proibida pela secção 10 do agent.md.
     */
    'failed' => 'Estas credenciais não correspondem aos nossos registos.',
    'password' => 'A palavra-passe está incorreta.',
    'throttle' => 'Demasiadas tentativas. Tente novamente em :seconds segundos.',
];
