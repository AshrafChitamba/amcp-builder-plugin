=== MCP Builder ===
Contributors: ashrafchitambaa
Tags: mcp, ai, bricks, elementor, page builder
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: mcp-adapter
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lets AI clients such as Claude and Cursor build and edit Bricks and Elementor pages through MCP.

== Description ==

MCP Builder connects AI clients to your site through the Model Context Protocol (MCP). The AI uses each builder's native elements and settings, never hidden code.

* One MCP endpoint for Bricks and Elementor.
* Builds Bricks pages from native elements: add, update, move, duplicate and remove elements, and manage global classes.
* Every change is snapshotted first and can be restored.
* Read-only mode, per-tool switches, rate limits, an audit log and a kill switch, applied to the official Bricks and Elementor AI tools too.
* Signs in with WordPress Application Passwords.
* Tools only appear for builders that are active on the site.

MCP Builder sends no telemetry and never contacts any external server.

**Use a staging site first and keep backups.** AI clients can change your pages.

Built by [Ashraf Chitambaa](https://ashrafchitambaa.com).

== Installation ==

1. Install and activate the MCP Adapter plugin.
2. Upload and activate MCP Builder.
3. Go to Settings → MCP Builder → Connection, create credentials and copy the snippet for your AI client.

== Changelog ==

= 0.3.0 =
* Elementor tools: get page, list widgets, widget schema, site globals, add/update/move/duplicate/remove elements, page settings (template). Atomic (v4) elements stay read-only.
* Snapshots now include the page template.

= 0.2.0 =
* Safety layer: read-only mode, kill switch, per-tool switches, custom-code gate, editor-lock protection, rate limits, audit log and snapshots with restore.
* Bricks tools: get page, list elements, element schema, add/update/move/duplicate/remove elements, global classes.
* New tools: list-media, list-snapshots, restore-snapshot.
* Settings: new Tools and Safety tabs.

= 0.1.0 =
* First development release: connection screen, status checks, site-info, list-pages and create-page tools.
