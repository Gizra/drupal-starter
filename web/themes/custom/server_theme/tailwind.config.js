const plugin = require('tailwindcss/plugin');

module.exports = {
  theme: {
    extend: {
      maxWidth: {
        '8xl': '90rem',
      },
    },
    fontFamily: {
      'headers': ["Roboto", 'sans-serif'],
      'body': ["Open Sans", 'sans-serif'],
    }
  },
  content: [
    // Look in the twig files.
    './templates/**/*.html.twig',
    // A preprocess function might inject a class.
    './server_theme.theme',
    // Custom module and the Style guide may have needed classes.
    '../../../modules/custom/**/*.php',
    '../../../modules/custom/**/*.html.twig',
  ],
  safelist: [
    // Add here custom class names.
    // https://tailwindcss.com/docs/content-configuration#safelisting-classes
  ],
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/line-clamp'),
  ],
};
