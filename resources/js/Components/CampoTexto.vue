<script setup lang="ts">
import { useId } from 'vue';

defineProps<{
    etiqueta: string;
    tipo?: string;
    erro?: string;
    autocomplete?: string;
    obrigatorio?: boolean;
    inputmode?: 'text' | 'email' | 'numeric';
}>();

const valor = defineModel<string>({ required: true });

const id = useId();
const idErro = `${id}-erro`;
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-sm font-medium text-slate-800">{{ etiqueta }}</label>

        <input
            :id="id"
            v-model="valor"
            :type="tipo ?? 'text'"
            :autocomplete="autocomplete"
            :required="obrigatorio"
            :inputmode="inputmode"
            :aria-invalid="erro ? 'true' : undefined"
            :aria-describedby="erro ? idErro : undefined"
            class="min-h-11 rounded-lg border border-slate-300 px-3 text-base text-slate-900 outline-none focus-visible:border-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600/30"
            :class="erro ? 'border-red-500' : ''"
        />

        <!-- role="alert" para o erro ser anunciado por leitores de ecrã quando
             aparece depois de submeter, e não ficar silenciosamente no DOM. -->
        <p v-if="erro" :id="idErro" role="alert" class="text-sm text-red-700">
            {{ erro }}
        </p>
    </div>
</template>
