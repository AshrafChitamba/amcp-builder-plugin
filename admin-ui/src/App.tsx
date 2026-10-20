import { ExternalLink } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs, getQueryArg } from '@wordpress/url';
import type { KeyboardEvent } from 'react';

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
		title: __( 'Connection', 'amcp-builder' ),
		Component: ConnectionTab,
	},
	{
		name: 'design',
		title: __( 'Design rules', 'amcp-builder' ),
		Component: DesignTab,
	},
	{
		name: 'tools',
		title: __( 'Tools', 'amcp-builder' ),
		Component: ToolsTab,
	},
	{
		name: 'safety',
		title: __( 'Safety & logs', 'amcp-builder' ),
		Component: SafetyTab,
	},
	{
		name: 'status',
		title: __( 'Status', 'amcp-builder' ),
		Component: StatusTab,
	},
	{
		name: 'tutorial',
		title: __( 'Tutorial', 'amcp-builder' ),
		Component: TutorialTab,
	},
	{
		name: 'about',
		title: __( 'About', 'amcp-builder' ),
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
	const submenu = document.querySelector( '#toplevel_page_amcp-builder' );
	submenu?.querySelectorAll( 'li' ).forEach( ( item ) => {
		const href = item.querySelector( 'a' )?.getAttribute( 'href' ) ?? '';
		const match =
			tab === 'connection'
				? /page=amcp-builder$/.test( href )
				: href.endsWith( `tab=${ tab }` );
		item.classList.toggle( 'current', match );
		item.querySelector( 'a' )?.classList.toggle( 'current', match );
	} );
};

type SiteState = { label: string; tone: 'ok' | 'warning' | 'error' };

const siteState = ( settings: Settings | null ): SiteState | null => {
	if ( ! config.canRun ) {
		return { label: __( 'Needs setup', 'amcp-builder' ), tone: 'error' };
	}
	if ( ! settings ) {
		return null;
	}
	if ( settings.kill_switch ) {
		return { label: __( 'Switched off', 'amcp-builder' ), tone: 'error' };
	}
	if ( settings.read_only ) {
		return { label: __( 'Read-only', 'amcp-builder' ), tone: 'warning' };
	}
	return { label: __( 'Live', 'amcp-builder' ), tone: 'ok' };
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
		<div className="amcpb-app">
			<header className="amcpb-header">
				<div className="amcpb-header__brand">
					<Logo size={ 44 } />
					<div>
						<h1 className="amcpb-header__title">
							{ __( 'AMCP Builder', 'amcp-builder' ) }
						</h1>
						<p className="amcpb-header__tagline">
							{ __(
								'Build and edit Bricks and Elementor pages with your AI app.',
								'amcp-builder'
							) }
						</p>
					</div>
				</div>
				<div className="amcpb-header__meta">
					{ state && (
						<span
							className={ `amcpb-state amcpb-state--${ state.tone }` }
						>
							<span
								className="amcpb-state__dot"
								aria-hidden="true"
							/>
							{ state.label }
						</span>
					) }
					<span className="amcpb-version">
						{ sprintf(
							/* translators: %s: version number. */
							__( 'Version %s', 'amcp-builder' ),
							config.version
						) }
					</span>
				</div>
				<div className="amcpb-endpoint">
					<span className="amcpb-endpoint__label">
						{ __( 'Server URL', 'amcp-builder' ) }
					</span>
					<code className="amcpb-endpoint__value">
						{ config.mcpUrl }
					</code>
					<CopyButton
						text={ config.mcpUrl }
						label={ __( 'Copy URL', 'amcp-builder' ) }
					/>
				</div>
			</header>

			<div
				className="amcpb-nav"
				role="tablist"
				aria-label={ __( 'AMCP Builder screens', 'amcp-builder' ) }
			>
				{ TABS.map( ( tab, index ) => (
					<button
						key={ tab.name }
						ref={ ( element ) => {
							tabRefs.current[ index ] = element;
						} }
						type="button"
						role="tab"
						id={ `amcpb-tab-${ tab.name }` }
						aria-selected={ active === tab.name }
						aria-controls="amcpb-panel"
						tabIndex={ active === tab.name ? 0 : -1 }
						className="amcpb-nav__tab"
						onClick={ () => open( tab.name ) }
						onKeyDown={ ( event ) => onKeyDown( event, index ) }
					>
						{ tab.title }
					</button>
				) ) }
			</div>

			<main
				id="amcpb-panel"
				className="amcpb-panel"
				role="tabpanel"
				aria-labelledby={ `amcpb-tab-${ active }` }
				key={ active }
			>
				<Component />
			</main>

			<footer className="amcpb-footer">
				<span>
					{ __( 'AMCP Builder by', 'amcp-builder' ) }{ ' ' }
					<ExternalLink href={ config.authorUrl }>
						{ config.author }
					</ExternalLink>
				</span>
				<span>
					{ __(
						'Free and open source under the GPL. No telemetry.',
						'amcp-builder'
					) }
				</span>
			</footer>
		</div>
	);
};
