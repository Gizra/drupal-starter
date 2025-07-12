// svgo.config.js
export default {
  multipass: true, // boolean. false by default
  plugins: [
    {
      name: 'preset-default',
      params: {
        overrides: {
          // viewBox is required to resize SVGs with CSS.
          // @see https://github.com/svg/svgo/issues/1128
          removeViewBox: false,
        },
      },
    },
  ],
};
