export interface Config {
	version: string;
	restNamespace: string;
	mcpUrl: string;
	siteUrl: string;
	username: string;
	appId: string;
	canRun: boolean;
	author: string;
	authorUrl: string;
	docsUrl: string;
	isLocal: boolean;
	tutorialUrl: string;
	adminUrl: string;
}

export type CheckStatus = 'ok' | 'warning' | 'error' | 'inactive';

export interface Check {
	id: string;
	label: string;
	status: CheckStatus;
	current: string | null;
	required: string | null;
	message: string;
}

export interface ToolInfo {
	name: string;
	label: string;
	group: string;
	readonly: boolean;
	available: boolean;
}

export interface Status {
	checks: Check[];
	environment: Record< string, string | boolean | null >;
	canRun: boolean;
	endpoint: string;
	oauth: { enabled: boolean; issuer: string; resourceMetadata: string };
	tools: ToolInfo[];
}

export interface AppPassword {
	uuid: string;
	app_id: string;
	name: string;
	created: string;
	last_used: string | null;
	last_ip: string | null;
}

export interface NewAppPassword extends AppPassword {
	password: string;
}

export interface TestResult {
	status: 'ok' | 'error' | 'unknown';
	message: string;
}

export interface Settings {
	kill_switch: boolean;
	read_only: boolean;
	dry_run_default: boolean;
	disabled_tools: string[];
	rate_limit_reads: number;
	rate_limit_writes: number;
	snapshot_keep: number;
	snapshot_days: number;
	audit_days: number;
	post_lock: 'deny' | 'allow';
	design_guide: boolean;
	design_guidelines: string;
	oauth_enabled: boolean;
}

export interface ManagedAbility {
	name: string;
	label: string;
	description: string;
	group: string;
	readonly: boolean;
	enabled: boolean;
}

export type AuditOutcome = 'ok' | 'error' | 'denied' | 'dry-run';

export interface AuditEntry {
	id: number;
	createdAt: string;
	user: string;
	ability: string;
	outcome: AuditOutcome;
	postId: number;
	snapshotId: number;
	durationMs: number;
	message: string;
	inputSummary: string;
}

export interface AuditPage {
	items: AuditEntry[];
	total: number;
}

export interface AuditFilters {
	outcome: AuditOutcome | '';
	ability: string;
	post_id: number;
}

export interface Snapshot {
	id: number;
	createdAt: string;
	user: string;
	ability: string;
	targetType: 'post' | 'option';
	targetId: number;
	targetKey: string;
	label: string;
	size: number;
}

export interface Connection {
	id: number;
	kind: 'app' | 'token';
	name: string;
	clientId: string;
	user: string;
	userId: number;
	readOnly: boolean;
	createdAt: string;
	expiresAt: string | null;
	lastUsedAt: string | null;
}

export interface DiscoveryCheck {
	url: string;
	ok: boolean;
}

export type DiscoveryResult = Record< string, DiscoveryCheck >;
