# MCP Builder

A free, open-source WordPress plugin that lets AI clients (Claude Code, Claude Desktop, Cursor and others) build and edit **Bricks** and **Elementor** pages through the [Model Context Protocol](https://modelcontextprotocol.io), using each builder's native elements and settings.

> **Status:** early development (Phase 1 of 7). See [docs/PLAN.md](docs/PLAN.md).

> **Use a staging site first and keep backups.** AI clients can change your pages.

## How it works

The plugin registers its tools with the WordPress Abilities API and serves them over MCP through the official [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin. There is no separate server to run: AI clients connect to `https://your-site/wp-json/mcp-builder/mcp` and sign in with a WordPress Application Password.

## Requirements

| | Minimum |
|---|---|
| WordPress | 7.1 |
| PHP | 8.1 |
| MCP Adapter plugin | 0.7.0 |
| Bricks (optional) | 2.0 |
| Elementor (optional) | 3.25 |

## Quick start

1. Install and activate the **MCP Adapter** plugin.
2. Install `dist/mcp-builder-<version>.zip` (build it with `scripts/build-zip.sh`) and activate it.
3. Open **Settings → MCP Builder → Connection**, create credentials and copy the snippet for your client.
4. Open **Status** and run **Test MCP connection**.

## Privacy

MCP Builder sends no telemetry and never contacts any external server. Your AI client talks to your site directly.

## Development

```bash
cd plugin && composer install && composer test && composer lint
cd admin-ui && npm install && npm test && npm run typecheck && npm run lint && npm run build
```

## License and credits

GPL-2.0-or-later. Copyright © 2026 [Ashraf Chitambaa](https://ashrafchitambaa.com).
