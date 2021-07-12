const plugin = require('tailwindcss/plugin');

module.exports = {
  mode: 'jit',
  theme: {
    extend: {
      gridTemplateColumns: {
        'fill-56': 'repeat(auto-fill, 14rem)',
        'fill-64': 'repeat(auto-fill, 16rem)',
      },
    },
    fontFamily: {
      'headers': ["Roboto", 'sans-serif'],
      'body': ["Open Sans", 'sans-serif'],
    },
    lineClamp: {
      '2': '2',
    }
  },
  purge: {
    content: [
      // Look in the twig files.
      './templates/**/*.html.twig',
      // A preprocess function might inject a class.
      './server_theme.theme',
      // Custom module and the Style guide may have needed classes.
      '../../../modules/custom/**/*.php',
      '../../../modules/custom/**/*.html.twig',
    ],
    options: {
      safelist: [
        // Add here custom class names. Since we're using TW's jit (Just-In-
        // Time), `safelist` must be full class names, and not regex.
      ],
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('tailwindcss-line-clamp')
  ],
};
