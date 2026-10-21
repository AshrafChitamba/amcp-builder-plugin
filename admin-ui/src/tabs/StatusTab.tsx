import { Button, Notice, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	errorMessage,
	getStatus,
	testAuthHeader,
	testMcpConnection,
} from '../api';
import { Section } from '../components/Section';
import type { CheckStatus, Status, TestResult } from '../types';

const STATUS_TEXT: Record< CheckStatus, string > = {
	ok: __( 'OK', 'amcp-builder' ),
	warning: __( 'Warning', 'amcp-builder' ),
	error: __( 'Problem', 'amcp-builder' ),
	inactive: __( 'Not active', 'amcp-builder' ),
};

const NOTICE_STATUS = {
	ok: 'success',
	error: 'error',
	unknown: 'warning',
} as const;

const TestButton = ( {
	label,
	run,
}: {
	label: string;
	run: () => Promise< TestResult >;
} ) => {
	const [ busy, setBusy ] = useState( false );
	const [ result, setResult ] = useState< TestResult | null >( null );

	const onClick = async () => {
		setBusy( true );
		setResult( await run() );
		setBusy( false );
	};

	return (
		<div className="amcpb-test">
			<Button
				variant="secondary"
				onClick={ onClick }
				isBusy={ busy }
				disabled={ busy }
			>
				{ label }
			</Button>
			<div aria-live="polite">
				{ result && (
					<Notice
						status={ NOTICE_STATUS[ result.status ] }
						isDismissible={ false }
					>
						{ result.message }
					</Notice>
				) }
			</div>
		</div>
	);
};

export const StatusTab = () => {
	const [ status, setStatus ] = useState< Status | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		getStatus()
			.then( setStatus )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}
	if ( ! status ) {
		return <Spinner />;
	}

	return (
		<>
			<Section
				title={ __( 'Connection tests', 'amcp-builder' ) }
				description={ __(
					'Run these after setup, and whenever an AI assistant cannot connect.',
					'amcp-builder'
				) }
			>
				<TestButton
					label={ __( 'Test MCP connection', 'amcp-builder' ) }
					run={ testMcpConnection }
				/>
				<TestButton
					label={ __( 'Test Authorization header', 'amcp-builder' ) }
					run={ () =>
						testAuthHeader().catch( ( e ) => ( {
							status: 'error',
							message: errorMessage( e ),
						} ) )
					}
				/>
			</Section>

			<Section title={ __( 'Environment', 'amcp-builder' ) }>
				<div className="amcpb-table-wrap">
					<table className="amcpb-table">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'Check', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Status', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Current', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Required', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Notes', 'amcp-builder' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ status.checks.map( ( check ) => (
								<tr key={ check.id }>
									<td>{ check.label }</td>
									<td>
										<span
											className={ `amcpb-badge amcpb-badge--${ check.status }` }
										>
											{ STATUS_TEXT[ check.status ] }
										</span>
									</td>
									<td>{ check.current ?? '—' }</td>
									<td>
										{ check.required
											? `${ check.required }+`
											: '—' }
									</td>
									<td>{ check.message }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</Section>

			<Section
				title={ __( 'Tools', 'amcp-builder' ) }
				description={ __(
					'Tools for builders that are not active are hidden from AI assistants.',
					'amcp-builder'
				) }
			>
				<ul className="amcpb-tool-list">
					{ status.tools.map( ( tool ) => (
						<li key={ tool.name }>
							<code>{ tool.name }</code> { tool.label }{ ' ' }
							<span className="amcpb-badge">
								{ tool.readonly
									? __( 'Read', 'amcp-builder' )
									: __( 'Write', 'amcp-builder' ) }
							</span>
							{ ! tool.available && (
								<span className="amcpb-badge amcpb-badge--inactive">
									{ __( 'Hidden', 'amcp-builder' ) }
								</span>
							) }
						</li>
					) ) }
				</ul>
			</Section>
		</>
	);
};
