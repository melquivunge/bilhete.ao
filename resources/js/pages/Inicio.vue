<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface IdentidadePartilhada {
    auth: {
        user: { id: number; name: string } | null;
    };
    // O Inertia exige assinatura de índice em PageProps: as props partilhadas
    // crescem ao longo dos marcos e não são conhecidas todas aqui.
    [chave: string]: unknown;
}

const utilizador = computed(() => usePage<IdentidadePartilhada>().props.auth.user);

function sair(): void {
    router.post('/logout');
}
</script>

<template>
    <Head title="Início" />

    <div class="flex min-h-dvh flex-col">
        <header class="flex items-center justify-between gap-4 px-6 py-6">
            <span class="text-sm font-medium tracking-widest text-blue-700 uppercase">Bilhete.ao</span>

            <nav aria-label="Conta" class="flex items-center gap-4 text-sm">
                <template v-if="utilizador">
                    <span class="text-slate-600">{{ utilizador.name }}</span>
                    <button
                        type="button"
                        class="min-h-11 rounded-lg px-3 font-medium text-blue-700 underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none"
                        @click="sair"
                    >
                        Sair
                    </button>
                </template>

                <template v-else>
                    <Link
                        href="/login"
                        class="flex min-h-11 items-center px-1 font-medium text-blue-700 underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none"
                    >
                        Entrar
                    </Link>
                    <Link
                        href="/register"
                        class="flex min-h-11 items-center rounded-lg bg-blue-700 px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none"
                    >
                        Criar conta
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center gap-6 px-6 pb-16">
            <h1 class="text-3xl font-semibold text-balance text-slate-900 sm:text-4xl">
                Bilhetes de cinema em Luanda, sem fila e sem chatice.
            </h1>

            <p class="text-base leading-relaxed text-slate-600">
                Esta é a base técnica da plataforma. As páginas públicas — filmes, cinemas, sessões e escolha de lugares
                — chegam no Marco&nbsp;1.
            </p>
        </main>
    </div>
</template>
