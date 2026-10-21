/**
 * Local preview of the admin screens with sample data. No WordPress needed.
 * Run from admin-ui: npm run preview
 */
import { defineConfig } from 'vite';

export default defineConfig( {
	root: __dirname,
	define: {
		'process.env.NODE_ENV': JSON.stringify( 'development' ),
		'process.env.IS_GUTENBERG_PLUGIN': 'false',
		'process.env.IS_WORDPRESS_CORE': 'true',
		'globalThis.SCRIPT_DEBUG': 'false',
	},
	server: { port: 5191, strictPort: true },
} );
