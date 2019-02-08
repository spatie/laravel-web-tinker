module.exports = {
    plugins: [
        require('postcss-easy-import')(),
        require('tailwindcss')('./tailwind.js'),
        require('postcss-nesting'),
    ],
};
