import { Notice, Spinner, ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { errorMessage, getAbilities } from '../api';
import { Section } from '../components/Section';
import { useSettings } from '../hooks/useSettings';
import type { ManagedAbility } from '../types';

const GROUPS: Array< { id: string; title: string; description: string } > = [
	{
		id: 'general',
		title: __( 'General', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			'Site info, pages, media and snapshots.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		id: 'bricks',
		title: __( 'Bricks', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			'Read and edit Bricks pages and global classes.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		id: 'elementor',
		title: __( 'Elementor', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			'Read and edit Elementor pages.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		id: 'custom-code',
		title: __( 'Custom code', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			'Tools that add HTML, CSS, JavaScript or PHP. Also need the custom-code switch above.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		id: 'official-bricks',
		title: __( 'Official Bricks tools', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			"Bricks' own AI abilities (Bricks 2.4+). AC MCP's safety rules apply to them too.",
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		id: 'official-elementor',
		title: __( 'Official Elementor tools', 'ac-mcp-for-bricks-elementor' ),
		description: __(
			"Elementor's own MCP abilities. AC MCP's safety rules apply to them too.",
			'ac-mcp-for-bricks-elementor'
		),
	},
];

export const ToolsTab = () => {
	const { settings, save, saving, error, clearError } = useSettings();
	const [ abilities, setAbilities ] = useState< ManagedAbility[] | null >(
		null
	);
	const [ loadError, setLoadError ] = useState< string | null >( null );

	useEffect( () => {
		getAbilities()
			.then( setAbilities )
			.catch( ( e ) => setLoadError( errorMessage( e ) ) );
	}, [] );

	if ( loadError ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ loadError }
			</Notice>
		);
	}
	if ( ! settings || ! abilities ) {
		return <Spinner />;
	}

	const toggleTool = ( name: string, enabled: boolean ) =>
		save( {
			disabled_tools: enabled
				? settings.disabled_tools.filter( ( item ) => item !== name )
				: [ ...settings.disabled_tools, name ],
		} );

	const toggleCustomCode = ( enabled: boolean ) => {
		if (
			enabled &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Custom-code tools let AI assistants add HTML, CSS, JavaScript or PHP to your site. Only administrators can use them. Enable?',
					'ac-mcp-for-bricks-elementor'
				)
			)
		) {
			return;
		}
		save( { custom_code_enabled: enabled } );
	};

	return (
		<>
			{ error && (
				<Notice status="error" onRemove={ clearError }>
					{ error }
				</Notice>
			) }

			<Section title={ __( 'Access', 'ac-mcp-for-bricks-elementor' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Read-only mode',
						'ac-mcp-for-bricks-elementor'
					) }
					help={ __(
						'AI assistants can read pages but every change is refused. Write tools are hidden from them.',
						'ac-mcp-for-bricks-elementor'
					) }
					checked={ settings.read_only }
					disabled={ saving }
					onChange={ ( value ) => save( { read_only: value } ) }
				/>
				<div className="acmcp-spacer" />
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Allow custom code',
						'ac-mcp-for-bricks-elementor'
					) }
					help={ __(
						'Off by default. When on, administrators (with unfiltered HTML) can use custom-code tools. Bricks code still has to be signed by an admin in Bricks before it runs.',
						'ac-mcp-for-bricks-elementor'
					) }
					checked={ settings.custom_code_enabled }
					disabled={ saving }
					onChange={ toggleCustomCode }
				/>
			</Section>

			{ GROUPS.map( ( group ) => {
				const items = abilities.filter(
					( ability ) => ability.group === group.id
				);
				if ( items.length === 0 ) {
					return null;
				}
				return (
					<Section
						key={ group.id }
						title={ group.title }
						description={ group.description }
					>
						<ul className="acmcp-tools">
							{ items.map( ( ability ) => (
								<li key={ ability.name }>
									<ToggleControl
										__nextHasNoMarginBottom
										label={
											<>
												{ ability.label }{ ' ' }
												<span className="acmcp-badge">
													{ ability.readonly
														? __(
																'Read',
																'ac-mcp-for-bricks-elementor'
															)
														: __(
																'Write',
																'ac-mcp-for-bricks-elementor'
															) }
												</span>
											</>
										}
										help={ ability.description }
										checked={
											! settings.disabled_tools.includes(
												ability.name
											)
										}
										disabled={ saving }
										onChange={ ( value ) =>
											toggleTool( ability.name, value )
										}
									/>
								</li>
							) ) }
						</ul>
					</Section>
				);
			} ) }
		</>
	);
};
