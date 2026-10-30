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
	ok: __( 'Saved', 'ac-mcp-for-bricks-elementor' ),
	'dry-run': __( 'Preview', 'ac-mcp-for-bricks-elementor' ),
	denied: __( 'Refused', 'ac-mcp-for-bricks-elementor' ),
	error: __( 'Error', 'ac-mcp-for-bricks-elementor' ),
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
			label: __(
				'Reads per minute (per user)',
				'ac-mcp-for-bricks-elementor'
			),
			help: __(
				'Stops runaway AI loops.',
				'ac-mcp-for-bricks-elementor'
			),
		},
		{
			key: 'rate_limit_writes',
			label: __(
				'Changes per minute (per user)',
				'ac-mcp-for-bricks-elementor'
			),
			help: '',
		},
		{
			key: 'snapshot_keep',
			label: __(
				'Snapshots kept per page',
				'ac-mcp-for-bricks-elementor'
			),
			help: __(
				'Older ones are deleted daily.',
				'ac-mcp-for-bricks-elementor'
			),
		},
		{
			key: 'snapshot_days',
			label: __(
				'Keep snapshots for (days)',
				'ac-mcp-for-bricks-elementor'
			),
			help: '',
		},
		{
			key: 'audit_days',
			label: __(
				'Keep audit log for (days)',
				'ac-mcp-for-bricks-elementor'
			),
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
				'ac-mcp-for-bricks-elementor'
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
					'Turn on the kill switch? Every AI assistant is cut off until you turn it off again.',
					'ac-mcp-for-bricks-elementor'
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
			<Section
				title={ __( 'Kill switch', 'ac-mcp-for-bricks-elementor' ) }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Switch off AC MCP',
						'ac-mcp-for-bricks-elementor'
					) }
					help={ __(
						'Immediately refuses every AI request, including official Bricks and Elementor tools. Use it if something goes wrong.',
						'ac-mcp-for-bricks-elementor'
					) }
					checked={ settings.kill_switch }
					disabled={ saving }
					onChange={ toggleKill }
				/>
				<div className="acmcp-spacer" />
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Allow AI assistants to sign in with WordPress (OAuth)',
						'ac-mcp-for-bricks-elementor'
					) }
					help={ __(
						'Needed for claude.ai, ChatGPT and coding tools that sign in. Turning it off disconnects nothing, but signed-in apps stop working until it is on again. Access tokens and Application Passwords keep working.',
						'ac-mcp-for-bricks-elementor'
					) }
					checked={ settings.oauth_enabled }
					disabled={ saving }
					onChange={ ( value ) => save( { oauth_enabled: value } ) }
				/>
			</Section>

			<Section
				title={ __(
					'Defaults and limits',
					'ac-mcp-for-bricks-elementor'
				) }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Preview changes by default (dry run)',
						'ac-mcp-for-bricks-elementor'
					) }
					help={
						draft.dry_run_default
							? __(
									'On: changes are only previewed, and nothing is saved unless the AI explicitly asks to save.',
									'ac-mcp-for-bricks-elementor'
								)
							: __(
									'Off: changes are saved straight away. Every save is still snapshotted, so you can undo it.',
									'ac-mcp-for-bricks-elementor'
								)
					}
					checked={ draft.dry_run_default }
					onChange={ ( value ) =>
						setDraft( { ...draft, dry_run_default: value } )
					}
				/>
				<div className="acmcp-spacer" />
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __(
						'When someone has the page open in an editor',
						'ac-mcp-for-bricks-elementor'
					) }
					value={ draft.post_lock }
					options={ [
						{
							value: 'deny',
							label: __(
								'Refuse AI changes (recommended)',
								'ac-mcp-for-bricks-elementor'
							),
						},
						{
							value: 'allow',
							label: __(
								'Allow AI changes anyway',
								'ac-mcp-for-bricks-elementor'
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
				<div className="acmcp-grid">
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
				<div className="acmcp-actions">
					<Button
						variant="primary"
						onClick={ submit }
						isBusy={ saving }
						disabled={ saving }
					>
						{ __( 'Save changes', 'ac-mcp-for-bricks-elementor' ) }
					</Button>
					<span aria-live="polite">
						{ saved &&
							__( 'Saved.', 'ac-mcp-for-bricks-elementor' ) }
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
			title={ __( 'Audit log', 'ac-mcp-for-bricks-elementor' ) }
			description={ __(
				'Every change, refusal and error made through MCP. Successful reads are not logged.',
				'ac-mcp-for-bricks-elementor'
			) }
		>
			{ error && (
				<Notice status="error" onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }
			<div className="acmcp-filters">
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Outcome', 'ac-mcp-for-bricks-elementor' ) }
					value={ filters.outcome }
					options={ [
						{
							value: '',
							label: __( 'All', 'ac-mcp-for-bricks-elementor' ),
						},
						{
							value: 'ok',
							label: __( 'Saved', 'ac-mcp-for-bricks-elementor' ),
						},
						{
							value: 'dry-run',
							label: __(
								'Preview',
								'ac-mcp-for-bricks-elementor'
							),
						},
						{
							value: 'denied',
							label: __(
								'Refused',
								'ac-mcp-for-bricks-elementor'
							),
						},
						{
							value: 'error',
							label: __( 'Error', 'ac-mcp-for-bricks-elementor' ),
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
					label={ __( 'Tool', 'ac-mcp-for-bricks-elementor' ) }
					placeholder="ac-mcp/bricks-add-elements"
					value={ filters.ability }
					onChange={ ( value ) =>
						setFilter( { ability: value.trim() } )
					}
				/>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					type="number"
					label={ __( 'Page ID', 'ac-mcp-for-bricks-elementor' ) }
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
					<div className="acmcp-table-wrap">
						<table className="acmcp-table">
							<thead>
								<tr>
									<th scope="col">
										{ __(
											'When',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
									<th scope="col">
										{ __(
											'User',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
									<th scope="col">
										{ __(
											'Tool',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
									<th scope="col">
										{ __(
											'Outcome',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
									<th scope="col">
										{ __(
											'Page',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
									<th scope="col">
										{ __(
											'Details',
											'ac-mcp-for-bricks-elementor'
										) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ data.items.length === 0 && (
									<tr>
										<td
											colSpan={ 6 }
											className="acmcp-empty"
										>
											{ __(
												'Nothing logged yet.',
												'ac-mcp-for-bricks-elementor'
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
												className="acmcp-tool-name"
												title={ entry.ability }
											>
												{ entry.ability.replace(
													/^ac-mcp\//,
													''
												) }
											</code>
										</td>
										<td>
											<span
												className={ `acmcp-badge acmcp-badge--outcome-${ entry.outcome }` }
											>
												{ OUTCOME_LABELS[
													entry.outcome
												] ?? entry.outcome }
											</span>
										</td>
										<td>{ entry.postId || '—' }</td>
										<td>
											{ entry.message && (
												<p className="acmcp-message">
													{ entry.message }
												</p>
											) }
											{ entry.inputSummary && (
												<details>
													<summary>
														{ __(
															'Input',
															'ac-mcp-for-bricks-elementor'
														) }
													</summary>
													<code className="acmcp-input">
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
															'ac-mcp-for-bricks-elementor'
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
					<div className="acmcp-pagination">
						<Button
							variant="secondary"
							size="compact"
							disabled={ page <= 1 }
							onClick={ () => setPage( page - 1 ) }
						>
							{ __( 'Previous', 'ac-mcp-for-bricks-elementor' ) }
						</Button>
						<span>
							{ sprintf(
								/* translators: 1: current page, 2: total pages. */
								__(
									'Page %1$d of %2$d',
									'ac-mcp-for-bricks-elementor'
								),
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
							{ __( 'Next', 'ac-mcp-for-bricks-elementor' ) }
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
						'ac-mcp-for-bricks-elementor'
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
			title={ __( 'Recent snapshots', 'ac-mcp-for-bricks-elementor' ) }
			description={ __(
				'Automatic backups taken right before each change.',
				'ac-mcp-for-bricks-elementor'
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
				<div className="acmcp-table-wrap">
					<table className="acmcp-table">
						<thead>
							<tr>
								<th scope="col">#</th>
								<th scope="col">
									{ __(
										'When',
										'ac-mcp-for-bricks-elementor'
									) }
								</th>
								<th scope="col">
									{ __(
										'What',
										'ac-mcp-for-bricks-elementor'
									) }
								</th>
								<th scope="col">
									{ __(
										'Taken before',
										'ac-mcp-for-bricks-elementor'
									) }
								</th>
								<th scope="col">
									<span className="screen-reader-text">
										{ __(
											'Actions',
											'ac-mcp-for-bricks-elementor'
										) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ items.length === 0 && (
								<tr>
									<td colSpan={ 5 } className="acmcp-empty">
										{ __(
											'No snapshots yet.',
											'ac-mcp-for-bricks-elementor'
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
														'ac-mcp-for-bricks-elementor'
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
											{ __(
												'Restore',
												'ac-mcp-for-bricks-elementor'
											) }
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
