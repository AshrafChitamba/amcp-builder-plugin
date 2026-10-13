# Troubleshooting

Start with **Settings → MCP Builder → Status**: it checks versions, permalinks, HTTPS, Application Passwords and the `Authorization` header, and can run a full MCP connection test.

## Connecting

| Symptom | Cause and fix |
|---|---|
| `404` / "MCP endpoint not found" | MCP Builder or MCP Adapter is not active, or permalinks are "Plain". Activate both and choose any other permalink structure. Check that `https://your-site/wp-json/` lists the `mcp-builder` namespace. |
| `401` with a correct token or password | The server strips the `Authorization` header. Apache: add `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` to `.htaccess`. nginx + PHP-FPM: `fastcgi_param HTTP_AUTHORIZATION $http_authorization;`. The Status tab's header test confirms the fix. |
| `401 invalid_token` | The token expired or was revoked. Reconnect (OAuth) or create a new access token. |
| `403` | The connection is read-only, the user lacks the capability for that action (for example editing someone else's page), or a security plugin/WAF blocks the request. Check the audit log, then the security plugin's log. |
| `503 mcp_builder_disabled` | The kill switch is on (Safety tab or the `MCP_BUILDER_DISABLE` constant). |
| "Bad Request: missing MCP-Protocol-Version" from a custom script | Send `MCP-Protocol-Version` and `Mcp-Session-Id` on every request after `initialize`. |

## OAuth (claude.ai, ChatGPT, Codex, Claude Code)

| Symptom | Cause and fix |
|---|---|
| The app says it can't find the authorization server | Run **Test sign-in discovery** (Connection tab). If the root `/.well-known/…` checks fail, the web server answers those paths itself: let them fall through to WordPress (nginx: `location ^~ /.well-known/oauth- { try_files $uri /index.php?$args; }`). |
| Discovery or `/oauth/…` URLs fail (401/403/404) | **Allow OAuth sign-in** is off (Safety tab) or the kill switch is on. |
| App connects but tokens are refused | Web apps need HTTPS with a valid certificate; the token's resource must match the endpoint URL exactly (check the Site URL setting, `www`, and `https`). |
| "This app is not registered" or "unexpected return address" | The app registered a different callback than it uses. Remove the connector and add it again. Only https callbacks and loopback (`127.0.0.1`, `localhost`) callbacks are accepted. |
| Login loop on the approval page | A caching or security plugin caches or blocks `/wp-json/mcp-builder/v1/oauth/authorize`. Exclude that path from caching. |
| Works in the browser but the app says unauthorized | A CDN or proxy removes the `Authorization` header or the `WWW-Authenticate` response header. Allow both. |
| Web app can't connect to a local site | Expected: web apps need a public HTTPS URL. See deployment.md → Local sites. |

## Building

| Symptom | Cause and fix |
|---|---|
| "Unknown setting key" | Call `bricks-get-element-schema` / `elementor-get-widget-schema`. Keys differ per element and builder version. |
| Changes saved but the page looks the same | CSS cache. Bricks: Settings → Performance → regenerate CSS files (in external-files mode). Elementor: Tools → Regenerate CSS & Data. Also clear page and CDN caches. |
| Bricks grid item doesn't span | Use `"span 2"`, not `"2"`. |
| Bricks custom CSS has no effect | Use `%root%` in `_cssCustom`; MCP Builder replaces it with the element or class selector. |
| Bricks Code element shows nothing | Code that runs PHP or HTML must be signed by an admin: Bricks → Settings → Custom code. `bricks-code-status` lists unsigned code. |
| "custom_code_disabled" | Custom code is off (default). An administrator can enable it under Tools; it then works only for accounts with `unfiltered_html`. |
| Elementor header or footer never shows | The active theme is a block theme (e.g. Twenty Twenty-Five). Elementor Pro headers and footers need a classic theme such as Hello Elementor. |
| A template doesn't appear on the site | Templates must be **published** and have matching conditions. Check with `bricks-list-templates` / `elementor-list-templates`. |
| Elementor "atomic_not_supported" | The page uses Elementor v4 Atomic elements, which are read-only in MCP Builder. |
| Elementor HTML or scripts removed after save | The account lacks `unfiltered_html` (common on multisite); Elementor strips them. |
| "post_locked" | Someone has the page open in the editor. Close it or pass `force: true`. |
| "rate_limited" | Too many calls per minute for this user. Wait, or raise the limits in Safety. |

## Undoing changes

Every write takes a snapshot. Use `list-snapshots` and `restore-snapshot`, or the restore buttons under Safety → Recent snapshots. A restore snapshots the current state first, so it can be undone too.

## Security warnings

Avoid unofficial npm packages that claim to be Bricks MCP servers. `bricks-builder-mcp` was flagged as malicious (MAL-2026-4500). MCP Builder needs no npm package except the optional, official `@automattic/mcp-wordpress-remote` proxy for Claude Desktop with local sites.
