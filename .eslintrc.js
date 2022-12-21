module.exports = {
	env: {
		browser: true,
		es2021: true,
	},
	extends: [
		'plugin:react/recommended',
		'airbnb',
		'plugin:storybook/recommended',
	],
	parser: '@typescript-eslint/parser',
	parserOptions: {
		ecmaFeatures: {
			jsx: true,
		},
		ecmaVersion: 'latest',
		sourceType: 'module',
	},
	plugins: ['react', '@typescript-eslint'],
	rules: {
		camelcase: 'off',
		'import/prefer-default-export': 'off',
		'no-shadow': 'off',
		'@typescript-eslint/no-shadow': ['error'],
		'jsx-a11y/label-has-associated-control': 'off',
		'react/jsx-props-no-spreading': 'off',
		'react/require-default-props': [
			'error',
			{
				functions: 'defaultArguments',
			},
		],
		'react/jsx-indent-props': ['error', 'tab'],
		'react/jsx-indent': ['error', 'tab'],
		indent: ['error', 'tab'],
		'import/no-unresolved': 'off',
		'import/extensions': 'off',
		'react/react-in-jsx-scope': 'off',
		'no-tabs': 'off',
		'react/function-component-definition': [
			2,
			{
				namedComponents: 'arrow-function',
			},
		],
		'no-unused-vars': 'off',
		'react/jsx-filename-extension': [
			1,
			{
				extensions: ['.tsx', '.ts'],
			},
		],
	},
};
