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
			title={ __( 'Your design rules', 'ac-mcp-for-bricks-elementor' ) }
			description={ __(
				'Brand rules every AI assistant must follow on this site. They override the built-in guide, and assistants pick up changes the moment you save.',
				'ac-mcp-for-bricks-elementor'
			) }
		>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Include the built-in design guide',
					'ac-mcp-for-bricks-elementor'
				) }
				help={ __(
					'Layout, hero, typography, consistency and accessibility rules that keep AI-built pages from looking generated.',
					'ac-mcp-for-bricks-elementor'
				) }
				checked={ settings.design_guide }
				disabled={ saving }
				onChange={ ( value ) => save( { design_guide: value } ) }
			/>
			<div className="acmcp-spacer" />
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Rules for this site', 'ac-mcp-for-bricks-elementor' ) }
				placeholder={ __(
					'For example: Use only our navy and orange. Headings in Merriweather. Buttons are 6px rounded. Write in a warm, plain tone. Never use stock photos of handshakes.',
					'ac-mcp-for-bricks-elementor'
				) }
				help={ sprintf(
					/* translators: 1: characters used, 2: maximum. */
					__(
						'Brand colors and fonts, tone of voice, components to reuse, things to avoid. You can paste your own design skill here. %1$d / %2$d characters.',
						'ac-mcp-for-bricks-elementor'
					),
					draft.length,
					MAX
				) }
				rows={ 12 }
				value={ draft }
				onChange={ ( value ) => setDraft( value.slice( 0, MAX ) ) }
			/>
			<div className="acmcp-actions">
				<Button
					variant="primary"
					onClick={ submit }
					isBusy={ saving }
					disabled={ saving || draft === settings.design_guidelines }
				>
					{ __( 'Save rules', 'ac-mcp-for-bricks-elementor' ) }
				</Button>
				<span aria-live="polite">
					{ saved && __( 'Saved.', 'ac-mcp-for-bricks-elementor' ) }
				</span>
			</div>
			{ draft.includes( '<script' ) && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Guidelines are plain text; HTML tags are removed when saving.',
						'ac-mcp-for-bricks-elementor'
					) }
				</Notice>
			) }
		</Section>
	);
};
