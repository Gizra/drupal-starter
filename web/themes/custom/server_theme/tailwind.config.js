const plugin = require('tailwindcss/plugin');

module.exports = {
  theme: {
    extend: {
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
  ],
};
