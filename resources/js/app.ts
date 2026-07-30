import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME ?? 'Bilhete.ao';

// O Inertia não lê a meta tag sozinho: espera o nonce como opção de
// createInertiaApp. Sem isto, os <style> que injeta em runtime — barra de
// progresso e diálogo de erro — são bloqueados pelo Content-Security-Policy.
const cspNonce = document.querySelector<HTMLMetaElement>('meta[name="csp-nonce"]')?.content;

void createInertiaApp({
    nonce: cspNonce,

    title: (title) => (title ? `${title} · ${appName}` : appName),

    // O diretório é `pages`, em minúscula, porque é onde o inertia-laravel procura
    // por omissão (resource_path('js/pages')). Estava em `Pages`: o macOS não
    // distingue maiúsculas e resolvia à mesma, mas no Linux da CI o Inertia não
    // encontrava componente nenhum. Um bind mount de macOS torna isto
    // indetetável localmente, mesmo dentro de um container Linux.
    //
    // Sem `eager`: cada página vira um chunk próprio, carregado só quando
    // visitada. Com `eager: true` todas as páginas entrariam no bundle de
    // entrada, e a partir do Marco 1 — catálogo, lugares, checkout, pagamento,
    // bilhete, scanner — abrir a home descarregaria o JS do site inteiro, o que
    // contraria o requisito de ser rápida em redes móveis (agent.md, secção 11).
    resolve: async (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

        const page = pages[`./pages/${name}.vue`];

        if (page === undefined) {
            throw new Error(`Página Inertia não encontrada: ${name}`);
        }

        return await page();
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#1d4ed8',
    },
});
