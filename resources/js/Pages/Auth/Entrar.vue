<script setup lang="ts">
import CampoTexto from '@/Components/CampoTexto.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    estado?: string;
}>();

const formulario = useForm({
    email: '',
    password: '',
    remember: false,
});

function submeter(): void {
    // onFinish limpa a palavra-passe do estado do cliente, para não ficar em
    // memória do componente depois de a resposta chegar.
    formulario.post('/login', {
        onFinish: () => formulario.reset('password'),
    });
}
</script>

<template>
    <Head title="Entrar" />

    <AuthLayout titulo="Entrar" descricao="Aceda à sua conta para ver os seus bilhetes.">
        <p v-if="estado" role="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ estado }}
        </p>

        <form class="flex flex-col gap-5" novalidate @submit.prevent="submeter">
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
                autocomplete="current-password"
                obrigatorio
                :erro="formulario.errors.password"
            />

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="formulario.remember" type="checkbox" class="size-4 rounded border-slate-300" />
                Manter sessão iniciada
            </label>

            <button
                type="submit"
                :disabled="formulario.processing"
                class="min-h-11 rounded-lg bg-blue-700 px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none disabled:opacity-60"
            >
                {{ formulario.processing ? 'A entrar…' : 'Entrar' }}
            </button>
        </form>

        <div class="mt-6 flex flex-col gap-2 text-sm">
            <Link href="/forgot-password" class="text-blue-700 underline underline-offset-4">
                Esqueceu-se da palavra-passe?
            </Link>
            <Link href="/register" class="text-blue-700 underline underline-offset-4"> Criar conta </Link>
        </div>
    </AuthLayout>
</template>
