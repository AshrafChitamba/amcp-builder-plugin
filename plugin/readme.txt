=== AC MCP for Bricks and Elementor ===
Contributors: ashrafchitambaa
Tags: mcp, ai, bricks, elementor, page builder
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: mcp-adapter
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lets AI assistants such as Claude, ChatGPT and Codex build and edit Bricks and Elementor pages through MCP.

== Description ==

AC MCP for Bricks and Elementor connects AI assistants to your site through the Model Context Protocol (MCP). The AI uses each builder's native elements and settings, never hidden code unless you turn on the optional custom-code switch.

* One MCP endpoint for Bricks and Elementor.
* Builds Bricks pages and templates from native elements, with display conditions, global classes, colors, variables, theme styles and page settings.
* Builds Elementor pages and Theme Builder templates from containers and widgets, with kit globals and display conditions.
* Gives the AI a strong built-in design guide plus your own design rules, which it re-reads whenever you change them, so it builds in your style and keeps the whole site consistent.
* Reports every change with a front-end preview link and a short description.
* Every change is snapshotted first and can be restored.
* Read-only mode, per-tool switches, rate limits, an audit log and a kill switch, applied to the official Bricks and Elementor AI tools too.
* Works with claude.ai, ChatGPT, Claude Code, Codex, Cursor and more: web apps sign in with OAuth (you approve each app, read-only if you like), coding tools with OAuth or access tokens.
* Tools only appear for builders that are active on the site.

AC MCP for Bricks and Elementor sends no telemetry. It only makes outbound requests to read an app's sign-in metadata during OAuth and to download images you ask it to upload.

**Use a staging site first and keep backups.** AI assistants can change your pages.

Built by [Ashraf Chitambaa](https://ashrafchitambaa.com).

== Installation ==

1. Install and activate the MCP Adapter plugin.
2. Upload and activate AC MCP for Bricks and Elementor.
3. Open AC MCP for Bricks and Elementor in the admin menu, go to Connection and follow the steps for your AI assistant. Web apps such as claude.ai and ChatGPT need the site on public HTTPS.
4. Add your brand rules on the Design rules screen.

== Changelog ==

= 1.0.0 =
* MCP Builder is now AC MCP for Bricks and Elementor. The connection URL changes to /wp-json/ac-mcp/mcp. Settings, snapshots, the audit log and connected apps are copied over automatically from MCP Builder.
* New top-level AC MCP for Bricks and Elementor admin menu with a redesigned interface, a Design rules screen and a Tutorial screen.
* Rewritten built-in design guide: no labels above headings, heroes that reflect the business, one consistent design system across the site, and no new colors or fonts without permission.
* The AI re-reads your design rules as soon as you change them.
* Every change now returns front-end preview links, and the AI reports each change in plain words.

= 0.4.0 =
* Sign in with OAuth 2.1 for claude.ai, Claude Desktop, ChatGPT, Codex, Claude Code and other apps; access tokens for coding tools; active connections list with revoke.
* Bricks: templates and display conditions, color palettes, variables, theme styles, page settings, code-signature status.
* Elementor: templates, Pro display conditions, kit globals, Pro custom code snippets.
* New tools: get-design-guide, update-page, upload-media. Guides as MCP resources and prompts; your own design guidelines in the Tools tab.
* Custom code (Bricks Code/SVG code, page scripts, Elementor HTML widget) is now refused unless enabled.

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

== Upgrade Notice ==

= 1.0.0 =
MCP Builder is now AC MCP for Bricks and Elementor. After updating, deactivate the old MCP Builder plugin and change the server URL in each AI assistant to /wp-json/ac-mcp/mcp. Your settings and history are copied over automatically.
