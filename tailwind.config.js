const defaultTheme = require('tailwindcss/defaultTheme');
module.exports = {
    purge: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans]
            },
        },
    },
    variants: {
        extend: {},
    },
    plugins: [],
}
