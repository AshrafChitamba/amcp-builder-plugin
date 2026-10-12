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
