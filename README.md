# AMCP Builder

A free, open-source WordPress plugin that lets AI apps (claude.ai, ChatGPT, Claude Code, OpenAI Codex, Cursor, VS Code, Gemini CLI and others) build and edit **Bricks** and **Elementor** pages, templates and design systems through the [Model Context Protocol](https://modelcontextprotocol.io). It uses each builder's native elements and settings, never hidden code.

Built by [Ashraf Chitambaa](https://ashrafchitambaa.com).

> **Use a staging site first and keep backups.** AI apps can change your pages.

## How it works

The plugin registers its tools with the WordPress Abilities API and serves them over MCP through the official [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) plugin. There is no separate server to run. AI apps connect to `https://your-site/wp-json/amcp-builder/mcp` and sign in with WordPress:

- **Web apps** (claude.ai, Claude Desktop, ChatGPT) use OAuth: you approve the app on a WordPress page, choosing read-and-edit or read-only.
- **Coding tools** (Claude Code, Codex, Cursor, VS Code, Gemini CLI) use OAuth or an access token.
- **Local sites** work with coding tools directly, or with Claude Desktop through a local proxy.

## What it can do

- **Bricks:** pages and templates (header, footer, single, archive, section, popup) with display conditions; every native element with settings validated against your live site; global classes; color palettes, CSS variables and theme styles; page settings; query loops; code-signature status.
- **Elementor:** containers and widgets, page settings, kit colors and fonts, Theme Builder templates and Pro display conditions, Pro custom code snippets. Atomic (v4) elements are read-only.
- **General:** list and create pages, change title, slug and status, search and upload media, snapshots and restore.
- **Design that fits your site:** a built-in design guide (strong heroes that reflect the business, no labels above headings, one consistent design system, no new colors or fonts without permission) plus **your own design rules**, which the AI re-reads the moment you change them.
- **Clear change reports:** every change returns front-end preview links, and the AI describes each changed section in a line or two.
- **Safety:** snapshots before every change, dry run, read-only mode and read-only connections, per-tool switches, rate limits, editor-lock protection, an audit log and a kill switch. These rules also apply to the official Bricks and Elementor AI tools. Custom code is off by default, and Bricks code is never signed by the plugin.

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
2. Install the latest `amcp-builder-<version>.zip` from `dist/` and activate it.
3. Open **AMCP Builder → Connection** in the admin menu and follow the steps for your app.
4. Add your brand rules on **AMCP Builder → Design rules**.
5. Open **Status** and run **Test MCP connection**.

### Upgrading from MCP Builder

AMCP Builder 1.0 replaces MCP Builder 0.x. On first load it copies your settings, snapshots, audit log and connected apps. Then deactivate the old plugin and change the server URL in each AI app from `/wp-json/mcp-builder/mcp` to `/wp-json/amcp-builder/mcp`.

## Development

```bash
cd plugin && composer install && vendor/bin/phpunit && vendor/bin/phpcs
cd admin-ui && npm ci && npm test && npm run build
scripts/build-zip.sh   # writes dist/amcp-builder-<version>.zip
```

The tutorial video on the Tutorial screen is set in `AdminPage::TUTORIAL_URL` (YouTube, Vimeo, Loom, Google Drive or a direct video file), or per site with the `amcp_builder_tutorial_url` filter.

## Privacy

No telemetry. Your AI app talks to your site directly. The plugin makes outbound requests only to read an app's sign-in metadata during OAuth and to download images you ask it to upload.

## License

GPL-2.0-or-later.
