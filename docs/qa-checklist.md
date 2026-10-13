# QA checklist

Run on a staging or LocalWP site before each release. Use a fresh install of the zip from `scripts/build-zip.sh`. Tick each item.

## Install

- [ ] Activating without MCP Adapter is blocked by WordPress (Requires Plugins).
- [ ] Activation creates the four tables (`mcpb_audit`, `mcpb_snapshots`, `mcpb_oauth_clients`, `mcpb_tokens`).
- [ ] Status tab: all checks green on a correctly configured site.
- [ ] Plugin Check (plugin-check plugin) shows no errors.

## Connection tab

- [ ] Endpoint URL copies.
- [ ] Test sign-in discovery passes on an HTTPS site and explains the failure on `localhost`.
- [ ] Creating an access token shows it once; the snippets update with it.
- [ ] Read-only token: writes are refused with `read_only_connection`.
- [ ] Revoking a connection makes its token fail with `401 invalid_token`.
- [ ] Application Password creation for the Claude Desktop proxy works and the snippet fills in.

## OAuth

- [ ] claude.ai: add custom connector → WordPress login → approval page → connected; tools list.
- [ ] ChatGPT (if your plan allows): same.
- [ ] Codex: `codex mcp add … --url …` then `codex mcp login` → approval → tools list.
- [ ] Claude Code: `/mcp` → Authenticate → approval → connected.
- [ ] "Read only" on the approval page hides or refuses writes.
- [ ] Deny on the approval page returns `access_denied` to the app.
- [ ] Disabling **Allow OAuth sign-in** disconnects OAuth apps.
- [ ] Token refresh works after 1 hour (leave a session idle, then use it).

## Tools (Bricks site)

- [ ] `site-info`, `get-design-guide` (design, bricks), resources and prompts list and read.
- [ ] Create a page, add a section with grid, heading, text, image (from `upload-media`) and button; open in Bricks; front end matches.
- [ ] Update, move, duplicate, remove an element; each creates a snapshot; restore works and can be undone.
- [ ] Global class create + apply; `%root%` CSS works on the front end.
- [ ] Create a header template, add elements, set conditions to one page, publish with `update-page`; it shows only on that page. Delete the test template afterwards.
- [ ] Design tokens: dry runs of colors, variables, theme style; one real change, then restore its snapshot.
- [ ] Page settings: SEO title and body class appear on the front end.
- [ ] Code element refused while custom code is off; allowed (unsigned) when on; `bricks-code-status` reports it as missing signature.
- [ ] Query loop of posts renders.

## Tools (Elementor site)

- [ ] `elementor-get-globals`, `elementor-list-widgets`, `elementor-get-widget-schema container`.
- [ ] Create a page and add containers with heading, text, image and button using `__globals__`; open in Elementor; front end matches.
- [ ] Update, move, duplicate, remove; snapshots and restore.
- [ ] `elementor-update-globals` adds a color (then restore the kit snapshot).
- [ ] Create a header template (Pro), add elements, set conditions to one page, publish; it shows only there. Delete it afterwards.
- [ ] HTML widget refused while custom code is off.
- [ ] `elementor-upsert-code-snippet` creates a draft snippet only with custom code on.
- [ ] An Atomic page is readable and edits are refused.

## Safety

- [ ] Read-only mode hides write tools.
- [ ] Kill switch returns 503 on the endpoint and blocks official Bricks/Elementor abilities.
- [ ] Turning a tool off hides it from `tools/list` and refuses calls.
- [ ] Rate limit triggers at the configured number.
- [ ] Page open in the editor by another user: writes refused unless `force`.
- [ ] Audit log lists every call, refusal and error; filters work.
- [ ] Daily cleanup removes old audit rows, snapshots and expired tokens (run `wp cron event run mcp_builder_prune`).

## Uninstall

- [ ] Deleting the plugin drops all four tables and the options.
