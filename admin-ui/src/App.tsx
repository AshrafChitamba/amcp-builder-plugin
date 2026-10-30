import { ExternalLink, Icon } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { cancelCircleFilled, info, lock, published } from '@wordpress/icons';
import { addQueryArgs, getQueryArg } from '@wordpress/url';
import type { ComponentProps, KeyboardEvent } from 'react';

import { getSettings } from './api';
import { CopyButton } from './components/CopyButton';
import { Logo } from './components/Logo';
import { config } from './config';
import { AboutTab } from './tabs/AboutTab';
import { ConnectionTab } from './tabs/ConnectionTab';
import { DesignTab } from './tabs/DesignTab';
import { SafetyTab } from './tabs/SafetyTab';
import { StatusTab } from './tabs/StatusTab';
import { ToolsTab } from './tabs/ToolsTab';
import { TutorialTab } from './tabs/TutorialTab';
import type { Settings } from './types';

const TABS = [
	{
		name: 'connection',
		title: __( 'Connection', 'ac-mcp-for-bricks-elementor' ),
		Component: ConnectionTab,
	},
	{
		name: 'design',
		title: __( 'Design rules', 'ac-mcp-for-bricks-elementor' ),
		Component: DesignTab,
	},
	{
		name: 'tools',
		title: __( 'Tools', 'ac-mcp-for-bricks-elementor' ),
		Component: ToolsTab,
	},
	{
		name: 'safety',
		title: __( 'Safety & logs', 'ac-mcp-for-bricks-elementor' ),
		Component: SafetyTab,
	},
	{
		name: 'status',
		title: __( 'Status', 'ac-mcp-for-bricks-elementor' ),
		Component: StatusTab,
	},
	{
		name: 'tutorial',
		title: __( 'Tutorial', 'ac-mcp-for-bricks-elementor' ),
		Component: TutorialTab,
	},
	{
		name: 'about',
		title: __( 'About', 'ac-mcp-for-bricks-elementor' ),
		Component: AboutTab,
	},
];

const initialTab = (): string => {
	const tab = getQueryArg( window.location.href, 'tab' );
	return TABS.some( ( item ) => item.name === tab )
		? String( tab )
		: 'connection';
};

/**
 * Keeps the URL and the WordPress sidebar submenu in step with the open tab.
 *
 * @param {string} tab Tab name.
 */
const syncLocation = ( tab: string ) => {
	window.history.replaceState(
		null,
		'',
		addQueryArgs( window.location.href, { tab } )
	);
	const submenu = document.querySelector( '#toplevel_page_ac-mcp' );
	submenu?.querySelectorAll( 'li' ).forEach( ( item ) => {
		const href = item.querySelector( 'a' )?.getAttribute( 'href' ) ?? '';
		const match =
			tab === 'connection'
				? /page=ac-mcp$/.test( href )
				: href.endsWith( `tab=${ tab }` );
		item.classList.toggle( 'current', match );
		item.querySelector( 'a' )?.classList.toggle( 'current', match );
	} );
};

type SiteState = {
	label: string;
	tone: 'ok' | 'warning' | 'error';
	icon: ComponentProps< typeof Icon >[ 'icon' ];
};

const siteState = ( settings: Settings | null ): SiteState | null => {
	if ( ! config.canRun ) {
		return {
			label: __( 'Needs setup', 'ac-mcp-for-bricks-elementor' ),
			tone: 'error',
			icon: info,
		};
	}
	if ( ! settings ) {
		return null;
	}
	if ( settings.kill_switch ) {
		return {
			label: __( 'Switched off', 'ac-mcp-for-bricks-elementor' ),
			tone: 'error',
			icon: cancelCircleFilled,
		};
	}
	if ( settings.read_only ) {
		return {
			label: __( 'Read-only', 'ac-mcp-for-bricks-elementor' ),
			tone: 'warning',
			icon: lock,
		};
	}
	return {
		label: __( 'Accepting changes', 'ac-mcp-for-bricks-elementor' ),
		tone: 'ok',
		icon: published,
	};
};

export const App = () => {
	const [ active, setActive ] = useState( initialTab );
	const [ settings, setSettings ] = useState< Settings | null >( null );
	const tabRefs = useRef< Array< HTMLButtonElement | null > >( [] );

	useEffect( () => {
		getSettings()
			.then( setSettings )
			.catch( () => undefined );
	}, [ active ] );

	const open = ( name: string ) => {
		setActive( name );
		syncLocation( name );
	};

	const onKeyDown = ( event: KeyboardEvent, index: number ) => {
		const keys: Record< string, number > = {
			ArrowRight: index + 1,
			ArrowLeft: index - 1,
			Home: 0,
			End: TABS.length - 1,
		};
		if ( ! ( event.key in keys ) ) {
			return;
		}
		event.preventDefault();
		const next = ( keys[ event.key ] + TABS.length ) % TABS.length;
		open( TABS[ next ].name );
		tabRefs.current[ next ]?.focus();
	};

	const state = siteState( settings );
	const { Component } =
		TABS.find( ( item ) => item.name === active ) ?? TABS[ 0 ];

	return (
		<div className="acmcp-app">
			<header className="acmcp-header">
				<div className="acmcp-header__brand">
					<Logo size={ 44 } />
					<div>
						<h1 className="acmcp-header__title">
							{ __( 'AC MCP for Bricks and Elementor', 'ac-mcp-for-bricks-elementor' ) }
						</h1>
						<p className="acmcp-header__tagline">
							{ __(
								'Build and edit Bricks and Elementor pages with your AI assistant.',
								'ac-mcp-for-bricks-elementor'
							) }
						</p>
					</div>
				</div>
				<div className="acmcp-header__meta">
					{ state && (
						<span
							className={ `acmcp-state acmcp-state--${ state.tone }` }
						>
							<Icon icon={ state.icon } size={ 18 } />
							{ state.label }
						</span>
					) }
					<span className="acmcp-version">
						{ sprintf(
							/* translators: %s: version number. */
							__( 'Version %s', 'ac-mcp-for-bricks-elementor' ),
							config.version
						) }
					</span>
				</div>
				<div className="acmcp-endpoint">
					<span className="acmcp-endpoint__label">
						{ __( 'Server URL', 'ac-mcp-for-bricks-elementor' ) }
					</span>
					<code className="acmcp-endpoint__value">
						{ config.mcpUrl }
					</code>
					<CopyButton
						text={ config.mcpUrl }
						label={ __( 'Copy URL', 'ac-mcp-for-bricks-elementor' ) }
					/>
				</div>
			</header>

			<div
				className="acmcp-nav"
				role="tablist"
				aria-label={ __( 'AC MCP for Bricks and Elementor screens', 'ac-mcp-for-bricks-elementor' ) }
			>
				{ TABS.map( ( tab, index ) => (
					<button
						key={ tab.name }
						ref={ ( element ) => {
							tabRefs.current[ index ] = element;
						} }
						type="button"
						role="tab"
						id={ `acmcp-tab-${ tab.name }` }
						aria-selected={ active === tab.name }
						aria-controls="acmcp-panel"
						tabIndex={ active === tab.name ? 0 : -1 }
						className="acmcp-nav__tab"
						onClick={ () => open( tab.name ) }
						onKeyDown={ ( event ) => onKeyDown( event, index ) }
					>
						{ tab.title }
					</button>
				) ) }
			</div>

			<main
				id="acmcp-panel"
				className="acmcp-panel"
				role="tabpanel"
				aria-labelledby={ `acmcp-tab-${ active }` }
				key={ active }
			>
				<Component />
			</main>

			<footer className="acmcp-footer">
				<span>
					{ __( 'AC MCP for Bricks and Elementor by', 'ac-mcp-for-bricks-elementor' ) }{ ' ' }
					<ExternalLink href={ config.authorUrl }>
						{ config.author }
					</ExternalLink>
				</span>
				<span>
					{ __(
						'Free and open source under the GPL. No telemetry.',
						'ac-mcp-for-bricks-elementor'
					) }
				</span>
			</footer>
		</div>
	);
};
