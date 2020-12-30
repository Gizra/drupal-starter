const plugin = require('tailwindcss/plugin');

module.exports = {
  theme: {
    extend: {
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
    },
    fontFamily: {
      'headers': ["Roboto", 'sans-serif'],
      'body': ["Open Sans", 'sans-serif'],
    },
  },
  variants: {
    display: ['responsive', 'hover', 'focus', 'active', 'group-hover', 'group-focus'],
  },
  purge: {
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
        // Add here custom class names.
      ],
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('tailwindcss-fluid-container')({
      componentPrefix: 'fluid-',
    }),
  ],
};
