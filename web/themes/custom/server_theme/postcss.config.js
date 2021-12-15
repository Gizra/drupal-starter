module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-import'),
    require('postcss-strip-inline-comments'),
    require('tailwindcss/nesting'),
    require('tailwindcss'),
    require('postcss-preset-env')({ stage: 1 }),
    require('cssnano')({ preset: 'default' }),
  ],
};
