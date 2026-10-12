import type { Config } from './types';

declare global {
	interface Window {
		mcpBuilder: Config;
	}
}

export const config: Config = window.mcpBuilder;
