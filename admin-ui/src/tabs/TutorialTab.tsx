import { ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { config } from '../config';
import { videoEmbed } from '../embed';

const STEPS = [
	{
		title: __( 'Connect your AI assistant', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'On the Connection screen, pick your AI assistant (Claude, ChatGPT, Cursor, Codex…) and follow its steps. Approve the connection when your site asks.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'Tell it your design rules', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'Add your brand rules on the Design rules screen: colors, fonts, tone of voice, things to avoid. The AI reads them before every build.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __(
			'Ask for a page or a change',
			'ac-mcp-for-bricks-elementor'
		),
		text: __(
			'For example: "Build a services page for our dental clinic in Bricks" or "Make the hero button on the home page larger". Start on a draft page.',
			'ac-mcp-for-bricks-elementor'
		),
	},
	{
		title: __( 'Review and undo if needed', 'ac-mcp-for-bricks-elementor' ),
		text: __(
			'The AI shares a preview link and a short note for every change. Every change is backed up first, so you can restore it on the Safety & logs screen.',
			'ac-mcp-for-bricks-elementor'
		),
	},
];

const Video = () => {
	const embed = config.tutorialUrl ? videoEmbed( config.tutorialUrl ) : null;

	if ( ! embed ) {
		return (
			<div className="acmcp-video acmcp-video--empty">
				<p className="acmcp-video__title">
					{ __(
						'The video tutorial is coming soon',
						'ac-mcp-for-bricks-elementor'
					) }
				</p>
				<p>
					{ __(
						'Until then, the four steps below cover everything you need to get started.',
						'ac-mcp-for-bricks-elementor'
					) }
				</p>
			</div>
		);
	}

	return (
		<div className="acmcp-video">
			{ embed.kind === 'iframe' ? (
				<iframe
					src={ embed.src }
					title={ __(
						'AC MCP tutorial',
						'ac-mcp-for-bricks-elementor'
					) }
					loading="lazy"
					allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
					allowFullScreen
					referrerPolicy="strict-origin-when-cross-origin"
				/>
			) : (
				<video src={ embed.src } controls preload="metadata" />
			) }
		</div>
	);
};

export const TutorialTab = () => (
	<>
		<Section
			title={ __(
				'Watch: build your first page',
				'ac-mcp-for-bricks-elementor'
			) }
			description={ __(
				'A short walkthrough of connecting an AI assistant and building a page with it.',
				'ac-mcp-for-bricks-elementor'
			) }
			actions={
				config.tutorialUrl ? (
					<ExternalLink href={ config.tutorialUrl }>
						{ __(
							'Open in a new tab',
							'ac-mcp-for-bricks-elementor'
						) }
					</ExternalLink>
				) : undefined
			}
		>
			<Video />
		</Section>

		<Section
			title={ __( 'Getting started', 'ac-mcp-for-bricks-elementor' ) }
		>
			<ol className="acmcp-quickstart">
				{ STEPS.map( ( step, index ) => (
					<li key={ step.title } className="acmcp-quickstart__item">
						<span
							className="acmcp-quickstart__index"
							aria-hidden="true"
						>
							{ index + 1 }
						</span>
						<div>
							<h3 className="acmcp-quickstart__title">
								{ step.title }
							</h3>
							<p className="acmcp-quickstart__text">
								{ step.text }
							</p>
						</div>
					</li>
				) ) }
			</ol>
		</Section>
	</>
);
