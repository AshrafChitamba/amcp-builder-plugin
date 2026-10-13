# Security model

MCP Builder lets software change your site, so every request is treated as untrusted until proven otherwise.

## Who can do what

- Every tool runs as a real WordPress user and checks that user's capabilities (`edit_post`, `publish_post`, `upload_files`, `edit_theme_options`, `manage_options`…). No permission callback returns true unconditionally.
- Bricks writes also pass Bricks' own builder-access check and its pre-save security filter. Elementor writes go through `Document::save()`, which applies Elementor's own permission and sanitising rules.
- Site-wide changes (global classes, theme styles, colors, variables, templates, kit globals) need theme or admin capabilities.

## Sign-in

- **OAuth 2.1** with PKCE (S256 only), authorization codes valid for 10 minutes and single use, 1-hour access tokens, rotating 30-day refresh tokens, and the RFC 9207 `iss` response parameter.
- Apps register through Dynamic Client Registration or Client ID Metadata Documents. Metadata documents are fetched with `wp_safe_remote_get` (no redirects, 64 KB max, cached for one day).
- Redirect URIs must be https, or loopback http for desktop tools. They must match the registered ones exactly (loopback ports may vary).
- Tokens are bound to the MCP endpoint (resource indicator). Bearer tokens are accepted only on MCP Builder's own REST routes, never for the rest of WordPress.
- Only SHA-256 hashes of tokens are stored.
- The approval page uses a nonce, refuses framing, shows the app name and where it returns to, and offers read-only access.
- Access tokens (for coding tools) can be read-only and expire; Application Passwords are supported for the local proxy.

## Guard rails

- **Kill switch** (setting or `define( 'MCP_BUILDER_DISABLE', true )`) stops everything, including official Bricks and Elementor abilities.
- **Read-only mode** site-wide, and read-only connections per app.
- **Per-tool switches** and **rate limits** per user.
- **Custom code is off by default.** When on, it still needs `manage_options` and `unfiltered_html`. Bricks code is never signed by MCP Builder.
- **Editor lock:** pages open in the editor by someone else are not changed unless the AI passes `force`.
- **Dry run** on every write.
- **Snapshots** before every write, with restore. A restore snapshots the current state first.
- **Audit log** of every call, refusal and error. Secrets in inputs are redacted.

## Prompt injection

Page content, titles and alt text are returned in clearly marked data fields, and tool descriptions and guides tell the AI that site content is data, not instructions. Destructive tools need explicit IDs, and every change is logged and reversible.

## Privacy

No telemetry. The plugin makes outbound requests only:

- to fetch an app's Client ID Metadata Document during sign-in;
- to download an image when `upload-media` is called.

## Reporting a vulnerability

Email the author through https://ashrafchitambaa.com. Please don't open a public issue for security reports.
