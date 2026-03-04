const HtmlWebpackPlugin = require('html-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCSSExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

const rules = [
	{
		test: /\.s[ac]ss$/i, // Matches .scss and .sass files
		use: [
			MiniCSSExtractPlugin.loader, // Extracts CSS into separate files
			'css-loader', // Interprets CSS imports
			'sass-loader', // Compiles Sass to CSS
		],
	},
	{
		test: /\.html$/,
		use: 'html-loader', // This loader will transform HTML into a string
	},
];

const webpackExtras = (isProduction) => {
	return {
		optimization: {
			minimize: isProduction,
			minimizer: [
				new TerserPlugin({
					terserOptions: {
						compress: {
							drop_console: isProduction,
						},
					},
				}),
			],
		},
		performance: {
			hints: 'error',
			maxEntrypointSize: 512000,
			maxAssetSize: 512000,
		},
		resolve: {
			alias: {
				node_modules: path.join(__dirname, 'node_modules'),
			},
		},
		stats: {
			assets: false, // Show asset information
			chunks: false, // Hide chunk information
			modules: false, // Hide module information
			entrypoints: false, // Show entrypoint information
			colors: true, // Enable colored output
			warnings: false, // Hide warnings
			errors: true, // Show errors
		},
	}
}

module.exports = (env, argv) => {
	const debug = env.mode === 'development' ? true : false;
	const isProduction = !debug;
	return [
		{
			entry: './src/webscript-utility-bar/reference.js',
			mode: isProduction ? 'production' : 'development',
			devtool: false,
			output: {
				filename: 'reference.js',
				path: path.resolve(__dirname, '../../build/utility-bar'),
			},
			module: {
				rules: rules
			},
			plugins: [
				new MiniCSSExtractPlugin({
					filename: 'reference.css',
				}),
				new HtmlWebpackPlugin({
					template: './src/webscript-utility-bar/reference.html', // Path to your custom template
					filename: 'index.html', // Output filename
				}),
				new CopyPlugin({
					patterns: [
						{ from: './src/webscript-utility-bar/composer.json', to: '../../build/utility-bar/composer.json' }
					],
				}),
			],
			...webpackExtras(isProduction)
		},
		{
			entry: './src/webscript-utility-bar/index.js',
			mode: isProduction ? 'production' : 'development',
			devtool: false,
			output: {
				//make the js that will go out everywhere
				filename: 'utility-bar.min.js',
				path: path.resolve(__dirname, '../../build/utility-bar'),
			},
			module: {
				rules: rules
			},
			plugins: [
				//make the css that will go out everywhere
				new MiniCSSExtractPlugin({
					filename: 'utility-bar.min.css',
				}),
			],
			...webpackExtras(isProduction)
		}
	]
};
