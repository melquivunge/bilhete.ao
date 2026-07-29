<?php

declare(strict_types=1);

/*
 * Traduções das regras de validação efetivamente usadas até ao Marco 0.
 *
 * Não é o ficheiro completo do Laravel: traduzir cento e tantas regras que
 * nenhum formulário usa seria trabalho por adivinhação. Cada marco acrescenta
 * as regras que os seus formulários introduzem — ver B-029.
 *
 * O que falta aqui recai no fallback em inglês, o que é visível e portanto
 * corrigível. O contrário — texto inventado para regras que não usamos — seria
 * pior, porque parece pronto.
 */
return [
    'accepted' => 'O campo :attribute tem de ser aceite.',
    'confirmed' => 'A confirmação de :attribute não coincide.',
    'current_password' => 'A palavra-passe está incorreta.',
    'email' => 'O campo :attribute tem de ser um endereço de email válido.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais de :max elementos.',
        'file' => 'O campo :attribute não pode ter mais de :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser superior a :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute tem de ter pelo menos :min elementos.',
        'file' => 'O campo :attribute tem de ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute tem de ser pelo menos :min.',
        'string' => 'O campo :attribute tem de ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute tem de ser um número.',
    'present' => 'O campo :attribute tem de estar presente.',
    'required' => 'O campo :attribute é obrigatório.',
    'same' => 'Os campos :attribute e :other têm de coincidir.',
    'string' => 'O campo :attribute tem de ser texto.',
    'unique' => 'O campo :attribute já está em uso.',

    'password' => [
        'letters' => 'A palavra-passe tem de conter pelo menos uma letra.',
        'mixed' => 'A palavra-passe tem de conter pelo menos uma maiúscula e uma minúscula.',
        'numbers' => 'A palavra-passe tem de conter pelo menos um número.',
        'symbols' => 'A palavra-passe tem de conter pelo menos um símbolo.',
        'uncompromised' => 'Esta palavra-passe apareceu em fugas de dados conhecidas. Escolha outra.',
    ],

    /*
     * Nomes dos campos em português, para as mensagens não dizerem "o campo
     * password" a um utilizador angolano.
     */
    'attributes' => [
        'email' => 'email',
        'name' => 'nome',
        'password' => 'palavra-passe',
        'password_confirmation' => 'confirmação da palavra-passe',
    ],
];
