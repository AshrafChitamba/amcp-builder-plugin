import { createRoot } from '@wordpress/element';

import { App } from './App';
import './style.scss';

const root = document.getElementById( 'mcp-builder-root' );

if ( root ) {
	createRoot( root ).render( <App /> );
}
