import { CopyButton } from './CopyButton';

interface Props {
	label: string;
	value: string;
	help?: string;
}

export const CopyField = ( { label, value, help }: Props ) => (
	<div className="amcpb-copy-field">
		<span className="amcpb-copy-field__label">{ label }</span>
		<div className="amcpb-copy-field__row">
			<code className="amcpb-copy-field__value">{ value }</code>
			<CopyButton text={ value } />
		</div>
		{ help && <p className="amcpb-help">{ help }</p> }
	</div>
);
