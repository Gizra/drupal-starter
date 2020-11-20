module.exports = {
  syntax: 'postcss-scss',
  plugins: [
    require('tailwindcss'),
    require('postcss-preset-env')({ stage: 1 }),
  ],
};
