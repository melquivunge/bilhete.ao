<script setup lang="ts">
import CampoTexto from '@/Components/CampoTexto.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const formulario = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submeter(): void {
    formulario.post('/register', {
        onFinish: () => formulario.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Criar conta" />

    <AuthLayout titulo="Criar conta" descricao="Precisa de uma conta para comprar e guardar os seus bilhetes.">
        <form class="flex flex-col gap-5" novalidate @submit.prevent="submeter">
            <CampoTexto
                v-model="formulario.name"
                etiqueta="Nome"
                autocomplete="name"
                obrigatorio
                :erro="formulario.errors.name"
            />

            <CampoTexto
                v-model="formulario.email"
                etiqueta="Email"
                tipo="email"
                inputmode="email"
                autocomplete="email"
                obrigatorio
                :erro="formulario.errors.email"
            />

            <CampoTexto
                v-model="formulario.password"
                etiqueta="Palavra-passe"
                tipo="password"
                autocomplete="new-password"
                obrigatorio
                :erro="formulario.errors.password"
            />

            <CampoTexto
                v-model="formulario.password_confirmation"
                etiqueta="Confirmar palavra-passe"
                tipo="password"
                autocomplete="new-password"
                obrigatorio
            />

            <button
                type="submit"
                :disabled="formulario.processing"
                class="min-h-11 rounded-lg bg-blue-700 px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none disabled:opacity-60"
            >
                {{ formulario.processing ? 'A criar conta…' : 'Criar conta' }}
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-700">
            Já tem conta?
            <Link href="/login" class="text-blue-700 underline underline-offset-4">Entrar</Link>
        </p>
    </AuthLayout>
</template>
