import type { Config } from './types';

declare global {
	interface Window {
		acMcp: Config;
	}
}

export const config: Config = window.acMcp;
