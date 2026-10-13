import { Notice, Spinner, ToggleControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { errorMessage, getAbilities } from '../api';
import { Section } from '../components/Section';
import { useSettings } from '../hooks/useSettings';
import { DesignGuidelines } from './DesignGuidelines';
import type { ManagedAbility } from '../types';

const GROUPS: Array< { id: string; title: string; description: string } > = [
	{
		id: 'general',
		title: __( 'General', 'mcp-builder' ),
		description: __(
			'Site info, pages, media and snapshots.',
			'mcp-builder'
		),
	},
	{
		id: 'bricks',
		title: __( 'Bricks', 'mcp-builder' ),
		description: __(
			'Read and edit Bricks pages and global classes.',
			'mcp-builder'
		),
	},
	{
		id: 'elementor',
		title: __( 'Elementor', 'mcp-builder' ),
		description: __( 'Read and edit Elementor pages.', 'mcp-builder' ),
	},
	{
		id: 'custom-code',
		title: __( 'Custom code', 'mcp-builder' ),
		description: __(
			'Tools that add HTML, CSS, JavaScript or PHP. Also need the custom-code switch above.',
			'mcp-builder'
		),
	},
	{
		id: 'official-bricks',
		title: __( 'Official Bricks tools', 'mcp-builder' ),
		description: __(
			"Bricks' own AI abilities (Bricks 2.4+). MCP Builder's safety rules apply to them too.",
			'mcp-builder'
		),
	},
	{
		id: 'official-elementor',
		title: __( 'Official Elementor tools', 'mcp-builder' ),
		description: __(
			"Elementor's own MCP abilities. MCP Builder's safety rules apply to them too.",
			'mcp-builder'
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
					'Custom-code tools let AI clients add HTML, CSS, JavaScript or PHP to your site. Only administrators can use them. Enable?',
					'mcp-builder'
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

			<Section title={ __( 'Access', 'mcp-builder' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Read-only mode', 'mcp-builder' ) }
					help={ __(
						'AI clients can read pages but every change is refused. Write tools are hidden from them.',
						'mcp-builder'
					) }
					checked={ settings.read_only }
					disabled={ saving }
					onChange={ ( value ) => save( { read_only: value } ) }
				/>
				<div className="mcpb-spacer" />
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Allow custom code', 'mcp-builder' ) }
					help={ __(
						'Off by default. When on, administrators (with unfiltered HTML) can use custom-code tools. Bricks code still has to be signed by an admin in Bricks before it runs.',
						'mcp-builder'
					) }
					checked={ settings.custom_code_enabled }
					disabled={ saving }
					onChange={ toggleCustomCode }
				/>
			</Section>

			<DesignGuidelines
				settings={ settings }
				save={ save }
				saving={ saving }
			/>

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
						<ul className="mcpb-tools">
							{ items.map( ( ability ) => (
								<li key={ ability.name }>
									<ToggleControl
										__nextHasNoMarginBottom
										label={
											<>
												{ ability.label }{ ' ' }
												<span className="mcpb-badge">
													{ ability.readonly
														? __(
																'Read',
																'mcp-builder'
															)
														: __(
																'Write',
																'mcp-builder'
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
