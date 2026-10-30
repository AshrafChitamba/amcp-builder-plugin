/**
 * Boots the real admin app with sample data instead of a WordPress site.
 * Nothing is saved: settings changes last until you reload the page.
 *
 * Query options: ?tab=design opens a screen, ?video=<YouTube link> fills the Tutorial video,
 * ?state=read-only|off|setup shows the other header states.
 */
import apiFetch from '@wordpress/api-fetch';
import '@wordpress/components/build-style/style.css';

import { abilities, audit, connections, settings, snapshots, status } from './sample-data';

const params = new URLSearchParams( window.location.search );
const state = params.get( 'state' );
if ( state === 'read-only' ) {
	settings.read_only = true;
}
if ( state === 'off' ) {
	settings.kill_switch = true;
}

window.acMcp = {
	version: '1.0.0',
	restNamespace: 'ac-mcp/v1',
	mcpUrl: 'https://example.com/wp-json/ac-mcp/mcp',
	siteUrl: 'https://example.com/',
	username: 'admin',
	appId: 'preview',
	canRun: state !== 'setup',
	author: 'Ashraf Chitambaa',
	authorUrl: 'https://ashrafchitambaa.com',
	docsUrl: 'https://ashrafchitambaa.com',
	isLocal: false,
	tutorialUrl: params.get( 'video' ) ?? '',
	adminUrl: '#',
};

const routes: Array< [ string, () => unknown ] > = [
	[ '/settings', () => settings ],
	[ '/abilities', () => abilities ],
	[ '/audit', () => audit ],
	[ '/snapshots', () => snapshots ],
	[ '/status', () => status ],
	[ '/connections', () => connections ],
	[ 'application-passwords', () => [] ],
];

apiFetch.use( ( options ) => {
	const path = options.path ?? options.url ?? '';
	if ( path.includes( '/settings' ) && options.method === 'POST' ) {
		Object.assign( settings, options.data );
	}
	const route = routes.find( ( [ match ] ) => path.includes( match ) );
	return Promise.resolve( route ? route[ 1 ]() : {} );
} );

import( '../src/index' );
