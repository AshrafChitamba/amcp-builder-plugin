import { CopyButton } from './CopyButton';

interface Props {
	label: string;
	value: string;
	help?: string;
}

export const CopyField = ( { label, value, help }: Props ) => (
	<div className="acmcp-copy-field">
		<span className="acmcp-copy-field__label">{ label }</span>
		<div className="acmcp-copy-field__row">
			<code className="acmcp-copy-field__value">{ value }</code>
			<CopyButton text={ value } />
		</div>
		{ help && <p className="acmcp-help">{ help }</p> }
	</div>
);
