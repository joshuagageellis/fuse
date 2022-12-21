const fs = require('fs');

/**
 * Load browser sync config.
 *
 * @param {string} filePath - Path to config file.
 * @returns {object} - Lando browser config.
 */
function parseOptionalConfig(filePath) {
	let config;
	if (fs.existsSync(filePath)) {
		config = JSON.parse(
			fs.readFileSync(filePath),
		);
	} else {
		config = {
			lando: 'localhost',
		};
	}
	return config;
}

module.exports = {
	parseOptionalConfig
};