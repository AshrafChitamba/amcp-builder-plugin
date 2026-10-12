import { ExternalLink, TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs, getQueryArg } from '@wordpress/url';

import { config } from './config';
import { AboutTab } from './tabs/AboutTab';
import { ConnectionTab } from './tabs/ConnectionTab';
import { StatusTab } from './tabs/StatusTab';

const TABS = [
	{
		name: 'connection',
		title: __( 'Connection', 'mcp-builder' ),
		Component: ConnectionTab,
	},
	{
		name: 'status',
		title: __( 'Status', 'mcp-builder' ),
		Component: StatusTab,
	},
	{ name: 'about', title: __( 'About', 'mcp-builder' ), Component: AboutTab },
];

const initialTab = (): string => {
	const tab = getQueryArg( window.location.href, 'tab' );
	return TABS.some( ( item ) => item.name === tab )
		? String( tab )
		: 'connection';
};

const rememberTab = ( tab: string ) =>
	window.history.replaceState(
		null,
		'',
		addQueryArgs( window.location.href, { tab } )
	);

export const App = () => (
	<div className="mcpb-app">
		<h1>{ __( 'MCP Builder', 'mcp-builder' ) }</h1>
		<TabPanel
			className="mcpb-tabs"
			tabs={ TABS.map( ( { name, title } ) => ( { name, title } ) ) }
			initialTabName={ initialTab() }
			onSelect={ rememberTab }
		>
			{ ( tab ) => {
				const { Component } =
					TABS.find( ( item ) => item.name === tab.name ) ??
					TABS[ 0 ];
				return (
					<div className="mcpb-tab">
						<Component />
					</div>
				);
			} }
		</TabPanel>
		<footer className="mcpb-footer">
			{ __( 'Built by', 'mcp-builder' ) }{ ' ' }
			<ExternalLink href={ config.authorUrl }>
				{ config.author }
			</ExternalLink>
		</footer>
	</div>
);
