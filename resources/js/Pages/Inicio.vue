<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    ambiente: string;
}>();
</script>

<template>
    <Head title="Início" />

    <main class="mx-auto flex min-h-dvh max-w-2xl flex-col justify-center gap-6 px-6 py-16">
        <p class="text-sm font-medium tracking-widest text-blue-700 uppercase">Bilhete.ao</p>

        <h1 class="text-3xl font-semibold text-balance text-slate-900 sm:text-4xl">
            Bilhetes de cinema em Luanda, sem fila e sem chatice.
        </h1>

        <p class="text-base leading-relaxed text-slate-600">
            Esta é a base técnica da plataforma. As páginas públicas — filmes, cinemas, sessões e escolha de lugares —
            chegam no Marco&nbsp;1.
        </p>

        <p class="text-sm text-slate-500">Ambiente: {{ ambiente }}</p>
    </main>
</template>
