import { __ } from '@wordpress/i18n';

import { CopyButton } from './CopyButton';

interface Props {
	code: string;
	label: string;
}

export const CodeBlock = ( { code, label }: Props ) => (
	<div className="amcpb-code">
		<div className="amcpb-code__bar">
			<span>{ label }</span>
			<CopyButton
				text={ code }
				label={ __( 'Copy snippet', 'amcp-builder' ) }
			/>
		</div>
		<pre tabIndex={ 0 } aria-label={ label }>
			<code>{ code }</code>
		</pre>
	</div>
);
