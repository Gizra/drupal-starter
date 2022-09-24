module.exports = {
  syntax: 'postcss-scss',
  plugins: {
    'postcss-import': {},
    'postcss-strip-inline-comments': {},
    'tailwindcss/nesting': {},
    'tailwindcss': {},
    'postcss-preset-env': {stage: 1},
    'cssnano': {'preset': 'default'},
  },
};
