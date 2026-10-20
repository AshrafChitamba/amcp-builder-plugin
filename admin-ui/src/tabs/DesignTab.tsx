import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { useSettings } from '../hooks/useSettings';
import { DesignGuidelines } from './DesignGuidelines';

const RULES = [
	{
		title: __( 'Your rules come first', 'amcp-builder' ),
		text: __(
			'The AI reads your rules before every build and re-reads them as soon as you change them.',
			'amcp-builder'
		),
	},
	{
		title: __( 'One design system', 'amcp-builder' ),
		text: __(
			'Typography, colors, spacing, corner radius and buttons stay consistent on every page. Layouts can still differ.',
			'amcp-builder'
		),
	},
	{
		title: __( 'No new colors or fonts', 'amcp-builder' ),
		text: __(
			'It only uses your existing palette and fonts, and asks before adding anything new.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Heroes that fit the business', 'amcp-builder' ),
		text: __(
			'Real imagery, one clear headline and one main action, in a layout chosen for the brand.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Clean, honest pages', 'amcp-builder' ),
		text: __(
			'No small labels above headings, no filler words, no invented facts or testimonials.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Every change reported', 'amcp-builder' ),
		text: __(
			'After each change you get a preview link and a short note of what changed.',
			'amcp-builder'
		),
	},
];

export const DesignTab = () => {
	const { settings, save, saving, error, clearError } = useSettings();

	if ( ! settings ) {
		return error ? (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		) : (
			<Spinner />
		);
	}

	return (
		<>
			{ error && (
				<Notice status="error" onRemove={ clearError }>
					{ error }
				</Notice>
			) }
			<DesignGuidelines
				settings={ settings }
				save={ save }
				saving={ saving }
			/>
			<Section
				title={ __(
					'What the built-in guide enforces',
					'amcp-builder'
				) }
				description={ __(
					'A summary of the design guide every AI app receives, unless you switch it off above.',
					'amcp-builder'
				) }
			>
				<ul className="amcpb-rules">
					{ RULES.map( ( rule ) => (
						<li key={ rule.title } className="amcpb-rules__item">
							<h3 className="amcpb-rules__title">
								{ rule.title }
							</h3>
							<p className="amcpb-rules__text">{ rule.text }</p>
						</li>
					) ) }
				</ul>
			</Section>
		</>
	);
};
