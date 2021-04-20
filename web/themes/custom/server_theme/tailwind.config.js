const plugin = require('tailwindcss/plugin');

module.exports = {
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
      safelist: [
        // Add here custom class names.
      ],
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('tailwindcss-line-clamp')
  ],
};
