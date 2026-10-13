import { CopyButton } from './CopyButton';

interface Props {
	label: string;
	value: string;
	help?: string;
}

export const CopyField = ( { label, value, help }: Props ) => (
	<div className="mcpb-copy-field">
		<span className="mcpb-copy-field__label">{ label }</span>
		<div className="mcpb-copy-field__row">
			<code className="mcpb-copy-field__value">{ value }</code>
			<CopyButton text={ value } />
		</div>
		{ help && <p className="mcpb-help">{ help }</p> }
	</div>
);
