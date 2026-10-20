import type { Config } from './types';

declare global {
	interface Window {
		amcpBuilder: Config;
	}
}

export const config: Config = window.amcpBuilder;
