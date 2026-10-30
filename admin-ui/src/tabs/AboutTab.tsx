import { ExternalLink, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { config } from '../config';

export const AboutTab = () => (
	<>
		<Notice status="warning" isDismissible={ false }>
			{ __(
				'AI assistants can change your pages. Try AC MCP for Bricks and Elementor on a staging site first, and keep regular backups of every site you connect.',
				'ac-mcp-for-bricks-elementor'
			) }
		</Notice>

		<Section title={ __( 'About AC MCP for Bricks and Elementor', 'ac-mcp-for-bricks-elementor' ) }>
			<p>
				{ sprintf(
					/* translators: %s: version number. */
					__(
						'Version %s. Free and open source under the GPL-2.0-or-later license.',
						'ac-mcp-for-bricks-elementor'
					),
					config.version
				) }
			</p>
			<p>
				{ __(
					"AC MCP for Bricks and Elementor lets AI assistants such as Claude and Cursor build and edit Bricks and Elementor pages using each builder's native elements.",
					'ac-mcp-for-bricks-elementor'
				) }
			</p>
			<p>
				<strong>{ __( 'Privacy:', 'ac-mcp-for-bricks-elementor' ) }</strong>{ ' ' }
				{ __(
					"AC MCP for Bricks and Elementor sends no telemetry. Your AI assistant talks to your site directly. The plugin only makes outbound requests to read an AI assistant's sign-in details during OAuth and to download images you ask it to upload.",
					'ac-mcp-for-bricks-elementor'
				) }
			</p>
			<p>
				<ExternalLink href={ config.docsUrl }>
					{ __( 'Documentation', 'ac-mcp-for-bricks-elementor' ) }
				</ExternalLink>
			</p>
			<p>
				{ __( 'Built by', 'ac-mcp-for-bricks-elementor' ) }{ ' ' }
				<ExternalLink href={ config.authorUrl }>
					{ config.author }
				</ExternalLink>
			</p>
		</Section>
	</>
);
