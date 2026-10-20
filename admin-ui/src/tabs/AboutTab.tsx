import { ExternalLink, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { Section } from '../components/Section';
import { config } from '../config';

export const AboutTab = () => (
	<>
		<Notice status="warning" isDismissible={ false }>
			{ __(
				'AI clients can change your pages. Try AMCP Builder on a staging site first, and keep regular backups of every site you connect.',
				'amcp-builder'
			) }
		</Notice>

		<Section title={ __( 'About AMCP Builder', 'amcp-builder' ) }>
			<p>
				{ sprintf(
					/* translators: %s: version number. */
					__(
						'Version %s. Free and open source under the GPL-2.0-or-later license.',
						'amcp-builder'
					),
					config.version
				) }
			</p>
			<p>
				{ __(
					"AMCP Builder lets AI clients such as Claude and Cursor build and edit Bricks and Elementor pages using each builder's native elements.",
					'amcp-builder'
				) }
			</p>
			<p>
				<strong>{ __( 'Privacy:', 'amcp-builder' ) }</strong>{ ' ' }
				{ __(
					"AMCP Builder sends no telemetry. Your AI app talks to your site directly. The plugin only makes outbound requests to read an app's sign-in details during OAuth and to download images you ask it to upload.",
					'amcp-builder'
				) }
			</p>
			<p>
				<ExternalLink href={ config.docsUrl }>
					{ __( 'Documentation', 'amcp-builder' ) }
				</ExternalLink>
			</p>
			<p>
				{ __( 'Built by', 'amcp-builder' ) }{ ' ' }
				<ExternalLink href={ config.authorUrl }>
					{ config.author }
				</ExternalLink>
			</p>
		</Section>
	</>
);
