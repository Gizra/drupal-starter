module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('postcss-import'),
    require('postcss-strip-inline-comments'),
    require('postcss-nested'),
    require('tailwindcss'),
    require('postcss-preset-env')({ stage: 1 }),
  ],
};
