import js from '@eslint/js';
import vueTsConfig from '@vue/eslint-config-typescript';
import configPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';

export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    ...vueTsConfig(),
    // Último a aplicar: desliga as regras de estilo do ESLint que competem com o
    // Prettier. O ESLint fica com a correção, o Prettier com a formatação — sem
    // duas ferramentas a discordar sobre indentação.
    configPrettier,
    {
        rules: {
            // O projeto é mobile-first e o HTML é escrito à mão; nomes de
            // componentes com uma palavra são aceites nas páginas Inertia,
            // que seguem a convenção de nomes do lado do servidor.
            'vue/multi-word-component-names': 'off',
            'no-console': ['error', { allow: ['warn', 'error'] }],
            '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        },
    },
];
