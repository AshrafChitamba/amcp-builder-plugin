import { createRoot } from '@wordpress/element';

import { App } from './App';
import './style.scss';

const root = document.getElementById( 'ac-mcp-root' );

if ( root ) {
	createRoot( root ).render( <App /> );
}
