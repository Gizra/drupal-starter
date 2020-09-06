const plugin = require('tailwindcss/plugin');

module.exports = {
  theme: {
    extend: {
      colors: {
        'purple-primary': '#664493',
        'turquoise': '#03B3AF',
      },
      fluidContainer: {
        'full': {
          width: '100%',
          padding: '0',
        },
        'wider': {
          maxWidth: '1280px',
          padding: '20px',
        },
        'wide': {
          maxWidth: '1200px',
          padding: '20px',
        },
        'narrow': {
          maxWidth: '980px',
          padding: '20px',
        },
      },
      fontFamily: {
        'headers': ['Gotham Medium', 'sans-serif'],
        'body': ['Gotham Bold', 'sans-serif'],
      },
    },
  },
  variants: {
    display: ['responsive', 'hover', 'focus', 'active', 'group-hover', 'group-focus'],
  },
  purge: {
    enabled: !!process.env.PURGE_TAILWIND,
    content: [
      // Look in the twig files.
      './templates/**/*.html.twig',
      // A preprocess function might inject a class.
      './server_theme.theme',
      // Custom module and the Style guide may have needed classes.
      '../../../modules/custom/**/*.php',
    ],
    options: {
      whitelist: [
        'bg-purple-primary',
        'bg-turquoise',
        'border-purple-primary',
        'border-turquoise',
        'text-purple-primary',
        'text-turquoise',
      ],
    },
  },
  plugins: [
    require('@tailwindcss/custom-forms'),
    require('tailwindcss-fluid-container')({
      componentPrefix: 'fluid-',
    }),
    require('tailwindcss-typography'),
  ],
};
