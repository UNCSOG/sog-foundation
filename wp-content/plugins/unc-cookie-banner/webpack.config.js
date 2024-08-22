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
const webpack = require("webpack");
const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config.js");
const TerserPlugin = require("terser-webpack-plugin");
var debug = process.env.NODE_ENV == "production";

module.exports = {
	mode: process.env.NODE_ENV ? process.env.NODE_ENV : "development",
	...defaultConfig,
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				type: "asset/resource",
				generator: {
					filename: "../fonts/[name][ext]",
				},
			},
		],
	},
	optimization: {
		...defaultConfig.optimization,
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
		...defaultConfig.performance,
		hints: false,
		maxEntrypointSize: 512000,
		maxAssetSize: 512000,
	},
	plugins: [
		...defaultConfig.plugins,
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
			"window.jQuery": "jquery'",
			"window.$": "jquery",
		}),
	],
	resolve: {
		...defaultConfig.resolve,
		alias: {
			node_modules: path.join(__dirname, "node_modules"),
		},
	},
	stats: {
		...defaultConfig.stats,
		assets: false,
	},
};
