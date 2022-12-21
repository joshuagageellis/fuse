/**
 * Define webpack exports.
 */
const base = require('./.tasks/webpack.base');
const stats = require('./.tasks/webpack.stats');

/**
 * Add more conditional exports.
 * See webpack.base.js for more info.
 */
if (process.env.STATS) {
	module.exports = stats;
} else {
	module.exports = base;
}
