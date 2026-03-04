/**
 * Builds on the @WordPress/scripts webpack config.
 *
 * try to quiet the output as much as possible
 * manage fonts
 * remove console.log() from javascript in production
 *
 * export NODE_ENV=development for sourcemaps, unminified and easy to debug code
 * export NODE_ENV=production for squashed scripts and styles, all the vars are mangled and it's unreadable
 * echo $NODE_ENV to see what you are using
 *
 *
 **/
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');
const TerserPlugin = require('terser-webpack-plugin');
var debug = process.env.NODE_ENV == 'production';
// console.log( "node env is set to: " + process.env.NODE_ENV )
// console.log( "debug is " + debug )

module.exports = {
	mode: process.env.NODE_ENV ? process.env.NODE_ENV : 'development',
	...defaultConfig,
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				type: 'asset/resource',
				generator: {
					filename: '../fonts/[name][ext]',
				},
			},
			{
				test: /\.html$/,
				use: 'html-loader', // This loader will transform HTML into a string
			},
		],
	},
	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					compress: {
						drop_console: debug,
					},
				},
			}),
		],
	},
	performance: {
		hints: false,
		maxEntrypointSize: 512000,
		maxAssetSize: 512000,
	},
	resolve: {
		alias: {
			node_modules: path.join(__dirname, 'node_modules'),
		},
	},
	stats: {
		...defaultConfig.stats,
		assets: false, // Show asset information
		chunks: false, // Hide chunk information
		modules: false, // Hide module information
		entrypoints: false, // Show entrypoint information
		colors: true, // Enable colored output
		warnings: false, // Hide warnings
		errors: true, // Show errors
	},
};
