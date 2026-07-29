<script setup lang="ts">
import CampoTexto from '@/Components/CampoTexto.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    estado?: string;
}>();

const formulario = useForm({
    email: '',
});
</script>

<template>
    <Head title="Recuperar palavra-passe" />

    <AuthLayout
        titulo="Recuperar palavra-passe"
        descricao="Indique o seu email e enviamos-lhe uma ligação para definir uma nova palavra-passe."
    >
        <!-- A mensagem de confirmação é sempre a mesma, exista ou não a conta:
             uma resposta diferente revelaria quem tem conta na plataforma. -->
        <p v-if="estado" role="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ estado }}
        </p>

        <form class="flex flex-col gap-5" novalidate @submit.prevent="formulario.post('/forgot-password')">
            <CampoTexto
                v-model="formulario.email"
                etiqueta="Email"
                tipo="email"
                inputmode="email"
                autocomplete="email"
                obrigatorio
                :erro="formulario.errors.email"
            />

            <button
                type="submit"
                :disabled="formulario.processing"
                class="min-h-11 rounded-lg bg-blue-700 px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none disabled:opacity-60"
            >
                {{ formulario.processing ? 'A enviar…' : 'Enviar ligação' }}
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-700">
            <Link href="/login" class="text-blue-700 underline underline-offset-4">Voltar a entrar</Link>
        </p>
    </AuthLayout>
</template>
