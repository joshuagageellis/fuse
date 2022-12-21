/**
 * Analyze the webpack bundle stats.
 * CMD:
 * 		> STATS=true webpack --json > stats.json
 * 		'npm run stats'
 * 
 * @param {object} stats - Webpack bundle stats.
 */

const analyzer = require('webpack-bundle-analyzer');
const base = require('./webpack.base.js');

const {
	BundleAnalyzerPlugin,
} = analyzer;

module.exports = {
	...base,
	plugins: [
		...base.plugins,
		new BundleAnalyzerPlugin(),
	],
};