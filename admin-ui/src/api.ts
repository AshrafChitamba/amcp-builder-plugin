import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

import { config } from './config';
import { addQueryArgs } from '@wordpress/url';

import type {
	AppPassword,
	AuditFilters,
	AuditPage,
	Connection,
	DiscoveryResult,
	ManagedAbility,
	NewAppPassword,
	Settings,
	Snapshot,
	Status,
	TestResult,
} from './types';

const path = ( route: string ): string =>
	`/${ config.restNamespace }${ route }`;
const appPasswordsPath = '/wp/v2/users/me/application-passwords';

export const getStatus = (): Promise< Status > =>
	apiFetch( { path: path( '/status' ) } );

export const getSettings = (): Promise< Settings > =>
	apiFetch( { path: path( '/settings' ) } );

export const saveSettings = (
	patch: Partial< Settings >
): Promise< Settings > =>
	apiFetch( { path: path( '/settings' ), method: 'POST', data: patch } );

export const getAbilities = (): Promise< ManagedAbility[] > =>
	apiFetch( { path: path( '/abilities' ) } );

export const getAudit = (
	filters: AuditFilters,
	page: number,
	perPage: number
): Promise< AuditPage > =>
	apiFetch( {
		path: addQueryArgs( path( '/audit' ), {
			...filters,
			post_id: filters.post_id || undefined,
			page,
			per_page: perPage,
		} ),
	} );

export const getSnapshots = (): Promise< Snapshot[] > =>
	apiFetch( { path: path( '/snapshots' ) } );

export const restoreSnapshot = (
	id: number
): Promise< { restored: number; undoSnapshotId: number } > =>
	apiFetch( { path: path( `/snapshots/${ id }/restore` ), method: 'POST' } );

export const getConnections = (): Promise< Connection[] > =>
	apiFetch( { path: path( '/connections' ) } );

export const createAccessToken = ( data: {
	name: string;
	readOnly: boolean;
	days: number;
} ): Promise< { token: string } > =>
	apiFetch( { path: path( '/connections' ), method: 'POST', data } );

export const revokeConnection = ( id: number ): Promise< unknown > =>
	apiFetch( { path: path( `/connections/${ id }` ), method: 'DELETE' } );

export const testDiscovery = (): Promise< DiscoveryResult > =>
	apiFetch( { path: path( '/discovery-test' ), method: 'POST' } );

export const testAuthHeader = (): Promise< TestResult > =>
	apiFetch( { path: path( '/auth-header-test' ), method: 'POST' } );

export const listAppPasswords = async (): Promise< AppPassword[] > => {
	const all: AppPassword[] = await apiFetch( { path: appPasswordsPath } );
	return all.filter( ( item ) => item.app_id === config.appId );
};

export const createAppPassword = ( name: string ): Promise< NewAppPassword > =>
	apiFetch( {
		path: appPasswordsPath,
		method: 'POST',
		data: { name, app_id: config.appId },
	} );

export const revokeAppPassword = ( uuid: string ): Promise< unknown > =>
	apiFetch( { path: `${ appPasswordsPath }/${ uuid }`, method: 'DELETE' } );

interface JsonRpcResponse {
	result?: {
		tools?: unknown[];
		serverInfo?: { name: string; version: string };
	};
	error?: { message: string };
}

const MCP_HEADERS = {
	Accept: 'application/json, text/event-stream',
	'Content-Type': 'application/json',
};

const MCP_PROTOCOL_VERSION = '2025-11-25';

const sessionHeaders = ( sessionId: string ) => ( {
	...MCP_HEADERS,
	'Mcp-Session-Id': sessionId,
	'MCP-Protocol-Version': MCP_PROTOCOL_VERSION,
} );

const mcpCall = async (
	method: string,
	params: Record< string, unknown >,
	sessionId?: string
): Promise< { body: JsonRpcResponse; sessionId: string | null } > => {
	const response = ( await apiFetch( {
		url: config.mcpUrl,
		method: 'POST',
		headers: sessionId ? sessionHeaders( sessionId ) : MCP_HEADERS,
		data: { jsonrpc: '2.0', id: Date.now(), method, params },
		parse: false,
	} ) ) as Response;

	return {
		body: ( await response.json() ) as JsonRpcResponse,
		sessionId: response.headers.get( 'Mcp-Session-Id' ),
	};
};

/**
 * Runs initialize + tools/list against AC MCP for Bricks and Elementor's endpoint as the logged-in
 * admin (cookie auth), then closes the session.
 */
export const testMcpConnection = async (): Promise< TestResult > => {
	try {
		const init = await mcpCall( 'initialize', {
			protocolVersion: MCP_PROTOCOL_VERSION,
			capabilities: {},
			clientInfo: {
				name: 'ac-mcp-settings',
				version: config.version,
			},
		} );
		if ( init.body.error || ! init.sessionId ) {
			return {
				status: 'error',
				message:
					init.body.error?.message ??
					__(
						'The server did not return a session.',
						'ac-mcp-for-bricks-elementor'
					),
			};
		}

		const list = await mcpCall( 'tools/list', {}, init.sessionId );
		await apiFetch( {
			url: config.mcpUrl,
			method: 'DELETE',
			headers: sessionHeaders( init.sessionId ),
			parse: false,
		} ).catch( () => undefined );

		if ( list.body.error ) {
			return { status: 'error', message: list.body.error.message };
		}

		return {
			status: 'ok',
			message: sprintf(
				/* translators: %d: number of tools. */
				__( 'Connected. The server offers %d tools.', 'ac-mcp-for-bricks-elementor' ),
				list.body.result?.tools?.length ?? 0
			),
		};
	} catch ( error ) {
		return { status: 'error', message: errorMessage( error ) };
	}
};

export const errorMessage = ( error: unknown ): string => {
	if ( error && typeof error === 'object' && 'message' in error ) {
		return String( ( error as { message: unknown } ).message );
	}
	return __( 'Something went wrong.', 'ac-mcp-for-bricks-elementor' );
};
