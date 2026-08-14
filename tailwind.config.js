/** @type {import('tailwindcss').Config} */
export default {
	prefix: 'sil-',
	important: '.sillage-wrap',
	corePlugins: {
		preflight: false,
	},
	content: [
		'./admin/views/**/*.php',
		'./src/admin/**/*.js',
	],
	theme: {
		extend: {},
	},
	plugins: [],
};
