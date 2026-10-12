# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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
