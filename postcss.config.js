module.exports = {
    plugins: [
        require('postcss-easy-import')(),
        require('tailwindcss')('./tailwind.js'),
        require('postcss-nesting'),
        require('@fullhuman/postcss-purgecss').default({
            content: ['resources/**/*.{js,vue,php}', 'src/**/*.php'],
            safelist: {
                standard: [/CodeMirror/, /cm/, /^theme-/, /^wt-/],
                greedy: [/CodeMirror/, /cm-/],
            },
        }),
    ],
};
