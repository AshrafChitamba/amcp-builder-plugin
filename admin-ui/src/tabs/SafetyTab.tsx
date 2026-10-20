import {
	Button,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { errorMessage, getAudit, getSnapshots, restoreSnapshot } from '../api';
import { Section } from '../components/Section';
import { useSettings } from '../hooks/useSettings';
import type { AuditFilters, AuditPage, Settings, Snapshot } from '../types';

const OUTCOME_LABELS: Record< string, string > = {
	ok: __( 'Saved', 'amcp-builder' ),
	'dry-run': __( 'Preview', 'amcp-builder' ),
	denied: __( 'Refused', 'amcp-builder' ),
	error: __( 'Error', 'amcp-builder' ),
};

const PER_PAGE = 25;

type NumberKey =
	| 'rate_limit_reads'
	| 'rate_limit_writes'
	| 'snapshot_keep'
	| 'snapshot_days'
	| 'audit_days';

const NUMBER_FIELDS: Array< { key: NumberKey; label: string; help: string } > =
	[
		{
			key: 'rate_limit_reads',
			label: __( 'Reads per minute (per user)', 'amcp-builder' ),
			help: __( 'Stops runaway AI loops.', 'amcp-builder' ),
		},
		{
			key: 'rate_limit_writes',
			label: __( 'Changes per minute (per user)', 'amcp-builder' ),
			help: '',
		},
		{
			key: 'snapshot_keep',
			label: __( 'Snapshots kept per page', 'amcp-builder' ),
			help: __( 'Older ones are deleted daily.', 'amcp-builder' ),
		},
		{
			key: 'snapshot_days',
			label: __( 'Keep snapshots for (days)', 'amcp-builder' ),
			help: '',
		},
		{
			key: 'audit_days',
			label: __( 'Keep audit log for (days)', 'amcp-builder' ),
			help: '',
		},
	];

const formatDate = ( value: string ): string =>
	new Date( value ).toLocaleString();

const confirmRestore = ( id: number ): boolean =>
	// eslint-disable-next-line no-alert
	window.confirm(
		sprintf(
			/* translators: %d: snapshot ID. */
			__(
				'Restore snapshot #%d? Changes made after it are undone. The current state is snapshotted first, so you can undo this.',
				'amcp-builder'
			),
			id
		)
	);

const SettingsForm = ( {
	settings,
	save,
	saving,
}: {
	settings: Settings;
	save: ( patch: Partial< Settings > ) => Promise< boolean >;
	saving: boolean;
} ) => {
	const [ draft, setDraft ] = useState( settings );
	const [ saved, setSaved ] = useState( false );

	const toggleKill = ( value: boolean ) => {
		if (
			value &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Turn on the kill switch? Every AI client is cut off until you turn it off again.',
					'amcp-builder'
				)
			)
		) {
			return;
		}
		save( { kill_switch: value } ).then( () =>
			setDraft( ( current ) => ( { ...current, kill_switch: value } ) )
		);
	};

	const submit = async () => {
		setSaved( false );
		setSaved( await save( draft ) );
	};

	return (
		<>
			<Section title={ __( 'Kill switch', 'amcp-builder' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Switch off AMCP Builder', 'amcp-builder' ) }
					help={ __(
						'Immediately refuses every AI request, including official Bricks and Elementor tools. Use it if something goes wrong.',
						'amcp-builder'
					) }
					checked={ settings.kill_switch }
					disabled={ saving }
					onChange={ toggleKill }
				/>
				<div className="amcpb-spacer" />
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Allow AI apps to sign in with WordPress (OAuth)',
						'amcp-builder'
					) }
					help={ __(
						'Needed for claude.ai, ChatGPT and coding tools that sign in. Turning it off disconnects nothing, but signed-in apps stop working until it is on again. Access tokens and Application Passwords keep working.',
						'amcp-builder'
					) }
					checked={ settings.oauth_enabled }
					disabled={ saving }
					onChange={ ( value ) => save( { oauth_enabled: value } ) }
				/>
			</Section>

			<Section title={ __( 'Defaults and limits', 'amcp-builder' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Preview changes by default (dry run)',
						'amcp-builder'
					) }
					help={ __(
						'Changes are only previewed unless the AI explicitly asks to save.',
						'amcp-builder'
					) }
					checked={ draft.dry_run_default }
					onChange={ ( value ) =>
						setDraft( { ...draft, dry_run_default: value } )
					}
				/>
				<div className="amcpb-spacer" />
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __(
						'When someone has the page open in an editor',
						'amcp-builder'
					) }
					value={ draft.post_lock }
					options={ [
						{
							value: 'deny',
							label: __(
								'Refuse AI changes (recommended)',
								'amcp-builder'
							),
						},
						{
							value: 'allow',
							label: __(
								'Allow AI changes anyway',
								'amcp-builder'
							),
						},
					] }
					onChange={ ( value ) =>
						setDraft( {
							...draft,
							post_lock: value as Settings[ 'post_lock' ],
						} )
					}
				/>
				<div className="amcpb-grid">
					{ NUMBER_FIELDS.map( ( field ) => (
						<TextControl
							key={ field.key }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							type="number"
							min={ 1 }
							label={ field.label }
							help={ field.help }
							value={ String( draft[ field.key ] ) }
							onChange={ ( value ) =>
								setDraft( {
									...draft,
									[ field.key ]: Number( value ),
								} )
							}
						/>
					) ) }
				</div>
				<div className="amcpb-actions">
					<Button
						variant="primary"
						onClick={ submit }
						isBusy={ saving }
						disabled={ saving }
					>
						{ __( 'Save changes', 'amcp-builder' ) }
					</Button>
					<span aria-live="polite">
						{ saved && __( 'Saved.', 'amcp-builder' ) }
					</span>
				</div>
			</Section>
		</>
	);
};

const AuditLog = ( { onRestored }: { onRestored: () => void } ) => {
	const [ filters, setFilters ] = useState< AuditFilters >( {
		outcome: '',
		ability: '',
		post_id: 0,
	} );
	const [ page, setPage ] = useState( 1 );
	const [ data, setData ] = useState< AuditPage | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( () => {
		getAudit( filters, page, PER_PAGE )
			.then( setData )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [ filters, page ] );

	useEffect( load, [ load ] );

	const restore = async ( id: number ) => {
		if ( ! confirmRestore( id ) ) {
			return;
		}
		try {
			await restoreSnapshot( id );
			load();
			onRestored();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	const pages = data ? Math.max( 1, Math.ceil( data.total / PER_PAGE ) ) : 1;
	const setFilter = ( patch: Partial< AuditFilters > ) => {
		setPage( 1 );
		setFilters( { ...filters, ...patch } );
	};

	return (
		<Section
			title={ __( 'Audit log', 'amcp-builder' ) }
			description={ __(
				'Every change, refusal and error made through MCP. Successful reads are not logged.',
				'amcp-builder'
			) }
		>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			<div className="amcpb-filters">
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Outcome', 'amcp-builder' ) }
					value={ filters.outcome }
					options={ [
						{ value: '', label: __( 'All', 'amcp-builder' ) },
						{ value: 'ok', label: __( 'Saved', 'amcp-builder' ) },
						{
							value: 'dry-run',
							label: __( 'Preview', 'amcp-builder' ),
						},
						{
							value: 'denied',
							label: __( 'Refused', 'amcp-builder' ),
						},
						{
							value: 'error',
							label: __( 'Error', 'amcp-builder' ),
						},
					] }
					onChange={ ( value ) =>
						setFilter( {
							outcome: value as AuditFilters[ 'outcome' ],
						} )
					}
				/>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Tool', 'amcp-builder' ) }
					placeholder="amcp-builder/bricks-add-elements"
					value={ filters.ability }
					onChange={ ( value ) =>
						setFilter( { ability: value.trim() } )
					}
				/>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					type="number"
					label={ __( 'Page ID', 'amcp-builder' ) }
					value={ filters.post_id ? String( filters.post_id ) : '' }
					onChange={ ( value ) =>
						setFilter( { post_id: Number( value ) || 0 } )
					}
				/>
			</div>

			{ ! data ? (
				<Spinner />
			) : (
				<>
					<div className="amcpb-table-wrap">
						<table className="amcpb-table">
							<thead>
								<tr>
									<th scope="col">
										{ __( 'When', 'amcp-builder' ) }
									</th>
									<th scope="col">
										{ __( 'User', 'amcp-builder' ) }
									</th>
									<th scope="col">
										{ __( 'Tool', 'amcp-builder' ) }
									</th>
									<th scope="col">
										{ __( 'Outcome', 'amcp-builder' ) }
									</th>
									<th scope="col">
										{ __( 'Page', 'amcp-builder' ) }
									</th>
									<th scope="col">
										{ __( 'Details', 'amcp-builder' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ data.items.length === 0 && (
									<tr>
										<td
											colSpan={ 6 }
											className="amcpb-empty"
										>
											{ __(
												'Nothing logged yet.',
												'amcp-builder'
											) }
										</td>
									</tr>
								) }
								{ data.items.map( ( entry ) => (
									<tr key={ entry.id }>
										<td>
											{ formatDate( entry.createdAt ) }
										</td>
										<td>{ entry.user || '—' }</td>
										<td>
											<code
												className="amcpb-tool-name"
												title={ entry.ability }
											>
												{ entry.ability.replace(
													/^amcp-builder\//,
													''
												) }
											</code>
										</td>
										<td>
											<span
												className={ `amcpb-badge amcpb-badge--outcome-${ entry.outcome }` }
											>
												{ OUTCOME_LABELS[
													entry.outcome
												] ?? entry.outcome }
											</span>
										</td>
										<td>{ entry.postId || '—' }</td>
										<td>
											{ entry.message && (
												<p className="amcpb-message">
													{ entry.message }
												</p>
											) }
											{ entry.inputSummary && (
												<details>
													<summary>
														{ __(
															'Input',
															'amcp-builder'
														) }
													</summary>
													<code className="amcpb-input">
														{ entry.inputSummary }
													</code>
												</details>
											) }
											{ entry.snapshotId > 0 && (
												<Button
													variant="link"
													onClick={ () =>
														restore(
															entry.snapshotId
														)
													}
												>
													{ sprintf(
														/* translators: %d: snapshot ID. */
														__(
															'Restore snapshot #%d (state before this)',
															'amcp-builder'
														),
														entry.snapshotId
													) }
												</Button>
											) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
					<div className="amcpb-pagination">
						<Button
							variant="secondary"
							size="compact"
							disabled={ page <= 1 }
							onClick={ () => setPage( page - 1 ) }
						>
							{ __( 'Previous', 'amcp-builder' ) }
						</Button>
						<span>
							{ sprintf(
								/* translators: 1: current page, 2: total pages. */
								__( 'Page %1$d of %2$d', 'amcp-builder' ),
								page,
								pages
							) }
						</span>
						<Button
							variant="secondary"
							size="compact"
							disabled={ page >= pages }
							onClick={ () => setPage( page + 1 ) }
						>
							{ __( 'Next', 'amcp-builder' ) }
						</Button>
					</div>
				</>
			) }
		</Section>
	);
};

const Snapshots = ( { version }: { version: number } ) => {
	const [ items, setItems ] = useState< Snapshot[] | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ message, setMessage ] = useState< string | null >( null );

	const load = useCallback( () => {
		getSnapshots()
			.then( setItems )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	useEffect( load, [ load, version ] );

	const restore = async ( id: number ) => {
		if ( ! confirmRestore( id ) ) {
			return;
		}
		try {
			const result = await restoreSnapshot( id );
			setMessage(
				sprintf(
					/* translators: 1: restored snapshot ID, 2: undo snapshot ID. */
					__(
						'Restored snapshot #%1$d. To undo, restore #%2$d.',
						'amcp-builder'
					),
					result.restored,
					result.undoSnapshotId
				)
			);
			load();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	return (
		<Section
			title={ __( 'Recent snapshots', 'amcp-builder' ) }
			description={ __(
				'Automatic backups taken right before each change.',
				'amcp-builder'
			) }
		>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			{ message && (
				<Notice status="success" onRemove={ () => setMessage( null ) }>
					{ message }
				</Notice>
			) }
			{ ! items ? (
				<Spinner />
			) : (
				<div className="amcpb-table-wrap">
					<table className="amcpb-table">
						<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col">
									{ __( 'When', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'What', 'amcp-builder' ) }
								</th>
								<th scope="col">
									{ __( 'Taken before', 'amcp-builder' ) }
								</th>
								<th scope="col">
									<span className="screen-reader-text">
										{ __( 'Actions', 'amcp-builder' ) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ items.length === 0 && (
								<tr>
									<td colSpan={ 5 } className="amcpb-empty">
										{ __(
											'No snapshots yet.',
											'amcp-builder'
										) }
									</td>
								</tr>
							) }
							{ items.map( ( item ) => (
								<tr key={ item.id }>
									<td>{ item.id }</td>
									<td>{ formatDate( item.createdAt ) }</td>
									<td>
										{ item.targetType === 'post'
											? sprintf(
													/* translators: %d: page ID. */
													__(
														'Page %d',
														'amcp-builder'
													),
													item.targetId
												)
											: item.targetKey }
									</td>
									<td>{ item.label || item.ability }</td>
									<td>
										<Button
											variant="link"
											onClick={ () => restore( item.id ) }
										>
											{ __( 'Restore', 'amcp-builder' ) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</Section>
	);
};

export const SafetyTab = () => {
	const { settings, save, saving, error, clearError } = useSettings();
	const [ restores, setRestores ] = useState( 0 );

	if ( ! settings ) {
		return error ? (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		) : (
			<Spinner />
		);
	}

	return (
		<>
			{ error && (
				<Notice status="error" onRemove={ clearError }>
					{ error }
				</Notice>
			) }
			<SettingsForm
				settings={ settings }
				save={ save }
				saving={ saving }
			/>
			<Snapshots version={ restores } />
			<AuditLog onRestored={ () => setRestores( restores + 1 ) } />
		</>
	);
};
