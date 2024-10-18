const { merge } = require( 'webpack-merge' );
const build = require( './webpack.config.js' );

module.exports = merge( build, {
	watch: true,
	watchOptions: {
		aggregateTimeout: 200,
		poll: 1000,
	},
	devtool: 'source-map',
} );
