module.exports = {
    plugins: [
        require('tailwindcss')('./tailwind.js'),
        require('postcss-nesting')(),
        require('@fullhuman/postcss-purgecss')({
            content: ['resources/**/*.{vue,js,blade.php}'],
            safelist: {
                standard: [/CodeMirror/, /cm/, /^theme-/, /^wt-/, /^sf-dump/, 'error', 'warning'],
            },
        }),
    ],
};
