# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.4.0] - 2026-10-13

### Added
- OAuth 2.1 authorization server for remote MCP apps (claude.ai, Claude Desktop, ChatGPT, Codex, Claude Code, Cursor, VS Code): protected-resource and authorization-server metadata (REST and root `/.well-known` paths), Dynamic Client Registration, Client ID Metadata Documents, PKCE S256, rotating refresh tokens, revocation, RFC 9207 `iss`, `WWW-Authenticate` challenges, and a WordPress approval page with a read-only option. Tables `mcpb_oauth_clients` and `mcpb_tokens`.
- Access tokens for coding tools (optionally read-only and expiring), an active-connections list with revoke, and a sign-in discovery test on the Connection tab.
- Bricks: `bricks-list-templates`, `bricks-create-template`, `bricks-set-template-conditions`, `bricks-get-design-tokens`, `bricks-upsert-colors`, `bricks-upsert-variables`, `bricks-update-theme-style`, `bricks-update-page-settings`, `bricks-code-status`.
- Elementor: `elementor-list-templates`, `elementor-create-template`, `elementor-set-conditions` (Pro), `elementor-update-globals`, `elementor-upsert-code-snippet` (Pro, custom code).
- General: `get-design-guide`, `update-page`, `upload-media`.
- Design, Bricks, Elementor and safety guides as MCP resources, `design-page` and `review-page` prompts, server instructions, and site-specific design guidelines (Tools tab).
- Docs: tool reference, deployment, security model, troubleshooting, QA checklist.

### Changed
- Custom code (Bricks Code element, SVG code, page scripts; Elementor HTML widget) is refused unless custom code is enabled and the user has `manage_options` and `unfiltered_html`.
- Bricks `%root%` in custom CSS is resolved to the element or class selector on save.
- Connection tab redesigned around web apps, coding tools and local sites.

### Fixed
- Bricks pages with digit-only element IDs.
- Elementor saves that return null were reported as failures; element-only saves no longer drop page settings; inner containers get `isInner`.
- Restoring an Elementor template snapshot now clears the element cache and refreshes Pro conditions.
- Uninstall drops the OAuth tables.
- `elementor-update-globals`: font fields are now validated (a wrong shape was silently ignored), and default system colors/fonts on a new kit are updated in place instead of duplicated.
- Elementor template types no longer offer Atomic (`e-*`) types or the kit; header/footer tools warn when the active theme is a block theme.
- Plugin Check: SQL now uses `%i` identifier placeholders for table names; the zip keeps `composer.json` and drops hidden files.
- Translation template `languages/mcp-builder.pot` (PHP and admin UI strings).

## [0.3.0] - 2026-10-12

### Added
- Elementor classic tools: `elementor-get-page`, `elementor-list-widgets`, `elementor-get-widget-schema`, `elementor-get-globals`, `elementor-add-elements`, `elementor-update-element`, `elementor-move-element`, `elementor-duplicate-element`, `elementor-remove-element`, `elementor-update-page-settings`. Writes support dry run, validate widgets, nesting and settings keys (with responsive device suffixes and `__globals__` references) against the live Elementor registry, refuse Atomic (v4) elements, snapshot first, and save through Elementor's `Document::save()` with a read-back check.
- Snapshots now include the page template (`_wp_page_template`).

### Changed
- `TreeError` and `Diff` moved to `McpBuilder\Support` so Bricks and Elementor share them.

## [0.2.0] - 2026-10-12

### Added
- Safety layer applied to every MCP Builder, Bricks and Elementor ability through WordPress core's ability hooks: kill switch, read-only mode, per-tool switches, custom-code gate (off by default), editor-lock protection, per-user rate limits.
- Audit log (custom table) of every change, refusal and error, with a viewer and filters in Settings → MCP Builder → Safety.
- Snapshots (custom table) taken before every write, with `list-snapshots`, `restore-snapshot` and restore buttons in settings. Restores are snapshotted too, so they can be undone. Daily cleanup by age and count.
- Bricks tools: `bricks-get-page`, `bricks-list-elements`, `bricks-get-element-schema`, `bricks-add-elements`, `bricks-update-element`, `bricks-move-element`, `bricks-duplicate-element`, `bricks-remove-element`, `bricks-list-global-classes`, `bricks-upsert-global-class`, `bricks-apply-global-class`. All writes support dry run, validate element names and settings keys against the live Bricks registry, and save through Bricks' own security checks and revisions.
- `list-media` tool for real attachment IDs and URLs.
- Settings: Tools tab (read-only, custom code, per-tool switches) and Safety tab (kill switch, defaults, limits, snapshots, audit log).

### Fixed
- "Test MCP connection" now sends the `MCP-Protocol-Version` header required after initialize.
- Drafts no longer report an invalid modified date.

## [0.1.0] - 2026-10-12

### Added
- WordPress plugin skeleton (PHP 8.1+, WordPress 7.1+, requires the MCP Adapter plugin).
- MCP server at `/wp-json/mcp-builder/mcp`.
- Tools: `site-info`, `list-pages`, `create-page` (with dry run).
- Settings → MCP Builder screen: Connection (endpoint, credentials, client snippets), Status (environment checks, connection and Authorization-header tests) and About.
