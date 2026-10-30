import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { useSettings } from '../hooks/useSettings';
import { DesignGuidelines } from './DesignGuidelines';

const RULES = [
	{
		title: __( 'Your rules come first', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'The AI reads your rules before every build and re-reads them as soon as you change them.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'One design system', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'Typography, colors, spacing, corner radius and buttons stay consistent on every page. Layouts can still differ.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'No new colors or fonts', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'It only uses your existing palette and fonts, and asks before adding anything new.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __(
			'Heroes that fit the business',
			'ac-mcp-for-bricks-elementor'
		),
		text: __(
			'Real imagery, one clear headline and one main action, in a layout chosen for the brand.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'Clean, honest pages', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'No small labels above headings, no filler words, no invented facts or testimonials.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'Every change reported', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'After each change you get a preview link and a short note of what changed.',
			'ac-mcp-for-bricks-elementor'
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
					'ac-mcp-for-bricks-elementor'
				) }
				description={ __(
					'A summary of the design guide every AI assistant receives, unless you switch it off above.',
					'ac-mcp-for-bricks-elementor'
				) }
			>
				<ul className="acmcp-rules">
					{ RULES.map( ( rule ) => (
						<li key={ rule.title } className="acmcp-rules__item">
							<h3 className="acmcp-rules__title">
								{ rule.title }
							</h3>
							<p className="acmcp-rules__text">{ rule.text }</p>
						</li>
					) ) }
				</ul>
			</Section>
		</>
	);
};
