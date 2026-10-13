import {
	Button,
	Notice,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { Section } from '../components/Section';
import type { Settings } from '../types';

const MAX = 20000;

export const DesignGuidelines = ( {
	settings,
	save,
	saving,
}: {
	settings: Settings;
	save: ( patch: Partial< Settings > ) => Promise< boolean >;
	saving: boolean;
} ) => {
	const [ draft, setDraft ] = useState( settings.design_guidelines );
	const [ saved, setSaved ] = useState( false );

	useEffect( () => setDraft( settings.design_guidelines ), [ settings ] );

	const submit = async () => {
		setSaved( false );
		setSaved( await save( { design_guidelines: draft } ) );
	};

	return (
		<Section
			title={ __( 'Design guidelines for AI', 'mcp-builder' ) }
			description={ __(
				'AI apps read these through the get-design-guide tool, the design guide resource and the "Design a page" prompt before they build.',
				'mcp-builder'
			) }
		>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Include the built-in design guide',
					'mcp-builder'
				) }
				help={ __(
					'Layout, hierarchy, copy and accessibility rules that keep AI-built pages from looking generated.',
					'mcp-builder'
				) }
				checked={ settings.design_guide }
				disabled={ saving }
				onChange={ ( value ) => save( { design_guide: value } ) }
			/>
			<div className="mcpb-spacer" />
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( "This site's own guidelines", 'mcp-builder' ) }
				help={ sprintf(
					/* translators: 1: characters used, 2: maximum. */
					__(
						'Brand rules, tone of voice, components to reuse, things to avoid. You can paste the text of your own design skills here. They take priority over the built-in guide. %1$d / %2$d characters.',
						'mcp-builder'
					),
					draft.length,
					MAX
				) }
				rows={ 12 }
				value={ draft }
				onChange={ ( value ) => setDraft( value.slice( 0, MAX ) ) }
			/>
			<div className="mcpb-actions">
				<Button
					variant="primary"
					onClick={ submit }
					isBusy={ saving }
					disabled={ saving || draft === settings.design_guidelines }
				>
					{ __( 'Save guidelines', 'mcp-builder' ) }
				</Button>
				<span aria-live="polite">
					{ saved && __( 'Saved.', 'mcp-builder' ) }
				</span>
			</div>
			{ draft.includes( '<script' ) && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Guidelines are plain text; HTML tags are removed when saving.',
						'mcp-builder'
					) }
				</Notice>
			) }
		</Section>
	);
};
