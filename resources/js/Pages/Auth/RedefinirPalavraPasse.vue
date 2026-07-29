<script setup lang="ts">
import CampoTexto from '@/Components/CampoTexto.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    token: string;
    email?: string;
}>();

const formulario = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

function submeter(): void {
    formulario.post('/reset-password', {
        onFinish: () => formulario.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Definir nova palavra-passe" />

    <AuthLayout titulo="Definir nova palavra-passe">
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
                etiqueta="Nova palavra-passe"
                tipo="password"
                autocomplete="new-password"
                obrigatorio
                :erro="formulario.errors.password"
            />

            <CampoTexto
                v-model="formulario.password_confirmation"
                etiqueta="Confirmar nova palavra-passe"
                tipo="password"
                autocomplete="new-password"
                obrigatorio
            />

            <button
                type="submit"
                :disabled="formulario.processing"
                class="min-h-11 rounded-lg bg-blue-700 px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none disabled:opacity-60"
            >
                {{ formulario.processing ? 'A guardar…' : 'Guardar palavra-passe' }}
            </button>
        </form>
    </AuthLayout>
</template>
