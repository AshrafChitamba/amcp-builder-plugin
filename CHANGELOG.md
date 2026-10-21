# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0] - 2026-10-20

### Changed
- Renamed from MCP Builder to **AMCP Builder**: plugin folder and main file `amcp-builder`, text domain `amcp-builder`, PHP namespace `AmcpBuilder`, MCP endpoint `/wp-json/amcp-builder/mcp`, tool names `amcp-builder-*`, admin REST namespace `amcp-builder/v1`, options `amcp_builder_*` and tables `{prefix}amcpb_*`.
- New top-level **AMCP Builder** admin menu (with a submenu per screen) instead of Settings → MCP Builder.
- Redesigned admin interface with a blue brand system, a branded header with a plain status line and a copyable server URL, keyboard-accessible navigation and restyled tables.
- Design guidelines moved from Tools to a new **Design rules** screen.
- User-facing wording now says "AI assistant" instead of "AI app" or "AI client".
- Rewrote the built-in design guide (drawing on Apple's design principles and the impeccable and taste skills): eyebrow labels above headings are banned; heroes must reflect the business with real imagery; one consistent design system across the site while layouts may vary; no new colors or fonts without the user's permission.

### Added
- Automatic migration from MCP Builder: settings (including disabled tools), snapshots, audit log, OAuth clients and tokens are copied on first load. An admin notice explains the new URL while the old plugin is still active.
- `guidelinesVersion` in every tool result, so AI apps re-read the site owner's rules as soon as they change.
- `preview` links (Bricks links jump to the changed element) and a `reportToUser` instruction on every saved change, so the AI reports each change with a link and a short description.
- **Tutorial** screen with an embedded video (YouTube, Vimeo, Loom, Google Drive or a video file) and a getting-started guide. The video link is `AdminPage::TUTORIAL_URL` or the `amcp_builder_tutorial_url` filter.

- Plain CSS classes set on Bricks elements (`_cssClasses`) are attached as Bricks global classes instead, created if needed, so they appear in the Bricks class selector next to the element ID rather than only under Style → CSS. New classes are reported as `globalClassesCreated` and snapshotted.
- `npm run preview` in `admin-ui` opens the admin screens locally with sample data, no WordPress needed.
- `REFERENCES.md` lists the documentation of every platform, standard and tool the plugin is built on.

### Removed
- Planning documents, internal docs, local helper scripts and other files that are not part of the plugin.

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
