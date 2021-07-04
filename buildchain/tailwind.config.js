// module exports
module.exports = {
  mode: 'jit',
  purge: {
    content: [
      '../src/templates/**/*.{twig,html}',
      './src/vue/**/*.{vue,html}',
    ],
    layers: [
      'base',
      'components',
      'utilities',
    ],
    mode: 'layers',
    options: {
      whitelist: [
        './src/css/components/*.css',
      ],
    }
  },
  theme: {
    screens: {
      'sm': '40em',
      'md': '48em',
      'lg': '64em',
      'xl': '80em',
    },
    minWidth: {
      '0': '0',
      '48': '12em',
      '64': '16em',
      'full': '100%',
    },
    extend: {
      maxWidth: {
        '40': '10em',
      }
    }
  },
  corePlugins: {},
  plugins: [],
};
