const path = require('path');
const webpack = require('webpack');
const TerserPlugin = require('terser-webpack-plugin');

const cognitoIdentityPoolId = require('./credentials');

// If we are running an interactive devserver
const isDevServer =
  process.env.ENGINE || process.env.NODE_ENV === 'development';

let prodOnlyExternals = [];

if (!isDevServer) {
  // do not bundle peer dependencies, unless we're running demos
  prodOnlyExternals = [
    // eslint-disable-next-line no-unused-vars
    function({context, request}, callback) {
      if (/^@babylonjs\/core.*$/.test(request)) {
        return callback(null, {
          root: 'BABYLON',
          commonjs: '@babylonjs/core',
          commonjs2: '@babylonjs/core',
          amd: '@babylonjs/core',
        });
      } else if (/^@babylonjs\/loaders.*$/i.test(request)) {
        return callback(null, {
          root: 'BABYLON',
          commonjs: '@babylonjs/loaders',
          commonjs2: '@babylonjs/loaders',
          amd: '@babylonjs/loaders',
        });
      }
      callback();
    },
  ];
}

module.exports = {
  // Turn on source maps if we aren't doing a production build, so tests and `start` for the examples.
  devtool: process.env.NODE_ENV === 'development' ? 'source-map' : undefined,
  entry: './app.js',
  output: {
    filename: 'dist/[name].js',
    path: path.resolve(__dirname),
    library: {
      name: 'HOST',
      type: 'umd',
      umdNamedDefine: true,
    },
    globalObject:
      '(typeof self !== "undefined" ? self : typeof global !== "undefined" ? global : this)',
    hotUpdateChunkFilename: '.hot-reload/[id].[fullhash].hot-update.js',
    hotUpdateMainFilename: '.hot-reload/[runtime].[fullhash].hot-update.json',
  },
  devServer: {
    devMiddleware: {
      // HTML files aren't fully modeled in webpack and may refer to on-dsk files
      // So let's make sure these get written out when watching
      writeToDisk: true,
    },
    open: ['/'],
    liveReload: true,
    hot: true,
    static: {
      directory: path.join(__dirname),
      watch: true,
    },
    setupMiddlewares: (middlewares, devServer) => {
      // Let's create a fake file to serve up config to be used by the tests
      // At some point we may move all the tests to be Webpack entry points and this could be easier
      // But this makes things straight forward to use from our raw HTML files
      devServer.app.get('/devConfig.json', (_, res) => {
        res.json({cognitoIdentityPoolId});
      });
      return middlewares;
    },
  },
  // We need to override some of the defaults for the minimization step --
  // There are issues with mangling otherwise, as logic relies on class names being preserved
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          keep_classnames: true,
        },
      }),
    ],
  },
  externals: [...prodOnlyExternals],
  target: 'browserslist',
};
