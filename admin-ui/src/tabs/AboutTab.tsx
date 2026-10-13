import { ExternalLink, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { config } from '../config';

export const AboutTab = () => (
	<>
		<Notice status="warning" isDismissible={ false }>
			{ __(
				'AI clients can change your pages. Try MCP Builder on a staging site first, and keep regular backups of every site you connect.',
				'mcp-builder'
			) }
		</Notice>

		<Section title={ __( 'About MCP Builder', 'mcp-builder' ) }>
			<p>
				{ sprintf(
					/* translators: %s: version number. */
					__(
						'Version %s. Free and open source under the GPL-2.0-or-later license.',
						'mcp-builder'
					),
					config.version
				) }
			</p>
			<p>
				{ __(
					"MCP Builder lets AI clients such as Claude and Cursor build and edit Bricks and Elementor pages using each builder's native elements.",
					'mcp-builder'
				) }
			</p>
			<p>
				<strong>{ __( 'Privacy:', 'mcp-builder' ) }</strong>{ ' ' }
				{ __(
					'MCP Builder sends no telemetry. Your AI app talks to your site directly. The plugin only makes outbound requests to read an app\'s sign-in details during OAuth and to download images you ask it to upload.',
					'mcp-builder'
				) }
			</p>
			<p>
				<ExternalLink href={ config.docsUrl }>
					{ __( 'Documentation', 'mcp-builder' ) }
				</ExternalLink>
			</p>
			<p>
				{ __( 'Built by', 'mcp-builder' ) }{ ' ' }
				<ExternalLink href={ config.authorUrl }>
					{ config.author }
				</ExternalLink>
			</p>
		</Section>
	</>
);
