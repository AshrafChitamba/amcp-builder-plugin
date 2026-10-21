import { ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { config } from '../config';
import { videoEmbed } from '../embed';

const STEPS = [
	{
		title: __( 'Connect your AI assistant', 'amcp-builder' ),
		text: __(
			'On the Connection screen, pick your AI assistant (Claude, ChatGPT, Cursor, Codex…) and follow its steps. Approve the connection when your site asks.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Tell it your design rules', 'amcp-builder' ),
		text: __(
			'Add your brand rules on the Design rules screen: colors, fonts, tone of voice, things to avoid. The AI reads them before every build.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Ask for a page or a change', 'amcp-builder' ),
		text: __(
			'For example: "Build a services page for our dental clinic in Bricks" or "Make the hero button on the home page larger". Start on a draft page.',
			'amcp-builder'
		),
	},
	{
		title: __( 'Review and undo if needed', 'amcp-builder' ),
		text: __(
			'The AI shares a preview link and a short note for every change. Every change is backed up first, so you can restore it on the Safety & logs screen.',
			'amcp-builder'
		),
	},
];

const Video = () => {
	const embed = config.tutorialUrl ? videoEmbed( config.tutorialUrl ) : null;

	if ( ! embed ) {
		return (
			<div className="amcpb-video amcpb-video--empty">
				<p className="amcpb-video__title">
					{ __(
						'The video tutorial is coming soon',
						'amcp-builder'
					) }
				</p>
				<p>
					{ __(
						'Until then, the four steps below cover everything you need to get started.',
						'amcp-builder'
					) }
				</p>
			</div>
		);
	}

	return (
		<div className="amcpb-video">
			{ embed.kind === 'iframe' ? (
				<iframe
					src={ embed.src }
					title={ __( 'AMCP Builder tutorial', 'amcp-builder' ) }
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
			title={ __( 'Watch: build your first page', 'amcp-builder' ) }
			description={ __(
				'A short walkthrough of connecting an AI assistant and building a page with it.',
				'amcp-builder'
			) }
			actions={
				config.tutorialUrl ? (
					<ExternalLink href={ config.tutorialUrl }>
						{ __( 'Open in a new tab', 'amcp-builder' ) }
					</ExternalLink>
				) : undefined
			}
		>
			<Video />
		</Section>

		<Section title={ __( 'Getting started', 'amcp-builder' ) }>
			<ol className="amcpb-quickstart">
				{ STEPS.map( ( step, index ) => (
					<li key={ step.title } className="amcpb-quickstart__item">
						<span
							className="amcpb-quickstart__index"
							aria-hidden="true"
						>
							{ index + 1 }
						</span>
						<div>
							<h3 className="amcpb-quickstart__title">
								{ step.title }
							</h3>
							<p className="amcpb-quickstart__text">
								{ step.text }
							</p>
						</div>
					</li>
				) ) }
			</ol>
		</Section>
	</>
);
