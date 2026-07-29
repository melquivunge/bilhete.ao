<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    titulo: string;
    descricao?: string;
}>();
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-slate-50">
        <header class="px-6 py-6">
            <Link
                href="/"
                class="text-sm font-medium tracking-widest text-blue-700 uppercase focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none"
            >
                Bilhete.ao
            </Link>
        </header>

        <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 pb-16">
            <h1 class="text-2xl font-semibold text-slate-900">{{ titulo }}</h1>

            <p v-if="descricao" class="mt-2 text-sm leading-relaxed text-slate-600">
                {{ descricao }}
            </p>

            <div class="mt-8">
                <slot />
            </div>
        </main>
    </div>
</template>
