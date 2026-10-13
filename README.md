# MCP Builder

A free, open-source WordPress plugin that lets AI apps (claude.ai, ChatGPT, Claude Code, OpenAI Codex, Cursor, VS Code, Gemini CLI and others) build and edit **Bricks** and **Elementor** pages, templates and design systems through the [Model Context Protocol](https://modelcontextprotocol.io). It uses each builder's native elements and settings, never hidden code.

> **Status:** 0.4.0. All planned phases are built. See [docs/PLAN.md](docs/PLAN.md) for what was verified live.

> **Use a staging site first and keep backups.** AI apps can change your pages.

## How it works

The plugin registers its tools with the WordPress Abilities API and serves them over MCP through the official [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin. There is no separate server to run. AI apps connect to `https://your-site/wp-json/mcp-builder/mcp` and sign in with WordPress:

- **Web apps** (claude.ai, Claude Desktop, ChatGPT) use OAuth: you approve the app on a WordPress page, choosing read-and-edit or read-only.
- **Coding tools** (Claude Code, Codex, Cursor, VS Code, Gemini CLI) use OAuth or an access token.
- **Local sites** work with coding tools directly, or with Claude Desktop through a local proxy.

## What it can do

- **Bricks:** pages and templates (header, footer, single, archive, section, popup) with display conditions; every native element with settings validated against your live site; global classes; color palettes, CSS variables and theme styles; page settings (SEO, body classes, header/footer); query loops; code-signature status.
- **Elementor:** containers and widgets, page settings, kit colors and fonts, Theme Builder templates and Pro display conditions, Pro custom code snippets. Atomic (v4) elements are read-only.
- **General:** list and create pages, change title/slug/status, search and upload media, snapshots and restore.
- **Design guidance:** the AI receives design rules, builder-specific know-how and **your own design guidelines** (Tools tab) through a tool, resources and prompts, so it builds in your style.
- **Safety:** snapshots before every change, dry run, read-only mode and read-only connections, per-tool switches, rate limits, editor-lock protection, audit log and kill switch. These rules also apply to the official Bricks and Elementor AI tools. Custom code is off by default and Bricks code is never signed by the plugin.

Full list: [docs/tool-reference.md](docs/tool-reference.md).

## Requirements

| | Minimum |
|---|---|
| WordPress | 7.1 |
| PHP | 8.1 |
| MCP Adapter plugin | 0.7.0 |
| Bricks (optional) | 2.0 |
| Elementor (optional) | 3.25 (Pro for Theme Builder conditions and code snippets) |
| Web apps | public HTTPS site |

## Quick start

1. Install and activate the **MCP Adapter** plugin.
2. Install `mcp-builder-<version>.zip` (build it with `scripts/build-zip.sh`) and activate it.
3. Open **Settings → MCP Builder → Connection** and follow the steps for your app.
4. Open **Status** and run **Test MCP connection**.

Details: [docs/deployment.md](docs/deployment.md). Problems: [docs/troubleshooting.md](docs/troubleshooting.md).

## Documentation

- [Tool reference](docs/tool-reference.md)
- [Deployment and connecting apps](docs/deployment.md)
- [Security model](docs/security.md)
- [Troubleshooting](docs/troubleshooting.md)
- [QA checklist](docs/qa-checklist.md)
- [Unverified assumptions](docs/VERIFY.md)
- [Plan and roadmap](docs/PLAN.md)

## Privacy

No telemetry. Your AI app talks to your site directly. The plugin makes outbound requests only to read an app's sign-in metadata during OAuth and to download images you ask it to upload.

## Development

```bash
cd plugin && composer install && composer test && composer lint
cd admin-ui && npm install && npm test && npm run typecheck && npm run lint && npm run build
scripts/build-zip.sh
```

`scripts/mcp-call.sh` is a small terminal client for testing an endpoint. `scripts/wp-local.sh <site-folder> <wp args>` runs WP-CLI against a running LocalWP site, e.g. `scripts/wp-local.sh my-site plugin check mcp-builder`.

## License and credits

GPL-2.0-or-later. Copyright © 2026 [Ashraf Chitambaa](https://ashrafchitambaa.com).
