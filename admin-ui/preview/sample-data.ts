/**
 * Sample data for the local preview. All names and numbers are made up.
 */
import type {
	AuditPage,
	Connection,
	ManagedAbility,
	Settings,
	Snapshot,
	Status,
} from '../src/types';

const minutesAgo = ( minutes: number ) =>
	new Date( Date.now() - minutes * 60000 ).toISOString();

export const settings: Settings = {
	kill_switch: false,
	read_only: false,
	dry_run_default: false,
	custom_code_enabled: false,
	disabled_tools: [],
	rate_limit_reads: 120,
	rate_limit_writes: 30,
	snapshot_keep: 20,
	snapshot_days: 30,
	audit_days: 90,
	post_lock: 'deny',
	design_guide: true,
	oauth_enabled: true,
	design_guidelines:
		'Use only our navy #0A1A3F, blue #1E4FB1 and orange #F1592A.\nHeadings in Merriweather, body text in Inter.\nButtons have a 5px corner radius.',
};

export const abilities: ManagedAbility[] = [
	{ name: 'ac-mcp/site-info', label: 'Site info', description: 'Which builders are active and the safety settings.', group: 'general', readonly: true, enabled: true },
	{ name: 'ac-mcp/create-page', label: 'Create page', description: 'Creates a new draft page for a builder.', group: 'general', readonly: false, enabled: true },
	{ name: 'ac-mcp/bricks-get-page', label: 'Bricks: get page', description: 'Reads a page as a nested outline.', group: 'bricks', readonly: true, enabled: true },
	{ name: 'ac-mcp/bricks-add-elements', label: 'Bricks: add elements', description: 'Adds native Bricks elements to a page.', group: 'bricks', readonly: false, enabled: true },
];

export const audit: AuditPage = {
	total: 3,
	items: [
		{ id: 3, createdAt: minutesAgo( 4 ), user: 'Admin', ability: 'ac-mcp/bricks-update-element', outcome: 'ok', postId: 1348, snapshotId: 46, durationMs: 220, message: '', inputSummary: '{"postId":1348,"elementId":"lum3vg"}' },
		{ id: 2, createdAt: minutesAgo( 9 ), user: 'Admin', ability: 'ac-mcp/bricks-add-elements', outcome: 'dry-run', postId: 1348, snapshotId: 0, durationMs: 310, message: '', inputSummary: '' },
		{ id: 1, createdAt: minutesAgo( 30 ), user: 'Admin', ability: 'bricks/set-page-elements', outcome: 'denied', postId: 12, snapshotId: 0, durationMs: 4, message: 'Read-only mode is on.', inputSummary: '' },
	],
};

export const snapshots: Snapshot[] = [
	{ id: 46, createdAt: minutesAgo( 4 ), user: 'Admin', ability: 'ac-mcp/bricks-update-element', targetType: 'post', targetId: 1348, targetKey: '', label: 'Bricks: update element', size: 4100 },
	{ id: 41, createdAt: minutesAgo( 15 ), user: 'Admin', ability: 'ac-mcp/bricks-upsert-global-class', targetType: 'option', targetId: 0, targetKey: 'bricks_global_classes', label: 'Bricks: upsert global class', size: 90000 },
];

export const status: Status = {
	canRun: true,
	endpoint: 'https://example.com/wp-json/ac-mcp/mcp',
	oauth: {
		enabled: true,
		issuer: 'https://example.com',
		resourceMetadata: 'https://example.com/.well-known/oauth-protected-resource',
	},
	environment: {},
	checks: [
		{ id: 'wp', label: 'WordPress', status: 'ok', current: '7.1.3', required: '7.1', message: 'Supported.' },
		{ id: 'adapter', label: 'MCP Adapter', status: 'ok', current: '0.7.0', required: '0.7.0', message: 'Active.' },
		{ id: 'bricks', label: 'Bricks', status: 'ok', current: '2.4.2', required: '2.0', message: 'Active.' },
		{ id: 'elementor', label: 'Elementor', status: 'inactive', current: null, required: '3.25', message: 'Not installed.' },
		{ id: 'https', label: 'HTTPS', status: 'warning', current: 'no', required: null, message: 'Web apps need HTTPS.' },
	],
	tools: abilities.map( ( ability ) => ( {
		name: ability.name,
		label: ability.label,
		group: ability.group,
		readonly: ability.readonly,
		available: true,
	} ) ),
};

export const connections: Connection[] = [
	{ id: 1, kind: 'app', name: 'Claude', clientId: 'acmcp_1', user: 'admin', userId: 1, readOnly: false, createdAt: minutesAgo( 1440 ), expiresAt: null, lastUsedAt: minutesAgo( 3 ) },
	{ id: 2, kind: 'token', name: 'Claude Code laptop', clientId: '', user: 'admin', userId: 1, readOnly: true, createdAt: minutesAgo( 2880 ), expiresAt: minutesAgo( -115200 ), lastUsedAt: null },
];
