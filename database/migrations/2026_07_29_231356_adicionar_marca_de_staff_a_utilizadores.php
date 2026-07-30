<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca mínima de staff, só para o painel administrativo poder negar acesso.
 *
 * NÃO é o modelo de papéis e permissões. Esse é do Marco 1 (B-020), depende de
 * empresas e cinemas existirem, e vai substituir esta coluna. Criar aqui um
 * sistema de permissões seria decidir por adivinhação o que ainda não tem
 * cinemas para modelar.
 *
 * `false` por omissão, e nunca atribuível em massa — ver App\Models\User.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_staff')->default(false)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_staff');
        });
    }
};
