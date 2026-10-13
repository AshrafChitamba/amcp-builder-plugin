# Deployment and connecting AI apps

## 1. Install

1. WordPress 7.1+, PHP 8.1+, pretty permalinks.
2. Install and activate the **MCP Adapter** plugin (0.7.0+).
3. Upload `mcp-builder-<version>.zip` (Plugins → Add New → Upload) and activate it. Build the zip from this repo with `scripts/build-zip.sh`.
4. Open **Settings → MCP Builder → Status** and fix anything marked red.

The endpoint is `https://your-site/wp-json/mcp-builder/mcp`.

## 2. Choose how each app signs in

| App | Sign-in | Needs a public HTTPS site? |
|---|---|---|
| claude.ai (web, desktop, mobile) | OAuth (WordPress approval page) | yes |
| ChatGPT (web, desktop) | OAuth | yes |
| Claude Code | OAuth or access token | no |
| OpenAI Codex (CLI, IDE, app) | OAuth or access token | no |
| Cursor, VS Code, Gemini CLI | OAuth or access token | no |
| Claude Desktop with a local site | Application Password through a local proxy (needs Node.js) | no |

**OAuth** opens a WordPress page where the signed-in user picks **Read and edit** or **Read only** and clicks **Allow access**. The app gets short-lived tokens that act as that user. No password is shared.

**Access tokens** are created under Connection → Coding tools. They are shown once, can be read-only, and can expire after 30, 90 or 365 days.

All connections are listed under Connection → Active connections and can be revoked there. Turning off **Allow OAuth sign-in** (Safety tab) stops new OAuth connections and disconnects existing ones.

## 3. Web apps

### claude.ai and Claude Desktop

1. In Claude open **Customize → Connectors → Add custom connector**.
2. Paste the endpoint URL. Leave the advanced OAuth fields empty.
3. Click **Connect**, sign in to WordPress if asked, and approve.

Custom connectors are available on all Claude plans. The Free plan allows one. Connectors added on the web also appear in Claude Desktop and mobile.

### ChatGPT

1. Turn on **Developer mode** in ChatGPT settings (under Apps / Connectors → Advanced), if your plan or workspace requires it.
2. Create a custom app/connector with the endpoint URL and **OAuth** authentication.
3. Approve on the WordPress page.

Which ChatGPT plans can add custom MCP servers changes over time and can be limited by workspace admins. Check your plan if the option is missing.

### Requirements for web apps

- The site must be reachable from the internet over **HTTPS** with a valid certificate. Run **Test sign-in discovery** on the Connection tab.
- `https://your-site/.well-known/oauth-protected-resource` and `/.well-known/oauth-authorization-server` must reach WordPress. Some hosts or nginx configs serve `/.well-known/` from disk only. Allow those paths through to `index.php`.
- Security plugins and WAFs must not block `/wp-json/mcp-builder/` or strip the `Authorization` header.

### Local sites (LocalWP, MAMP, Docker)

Web apps cannot reach `localhost`. Do one of these:

- use a coding tool (Claude Code, Codex, Cursor), which runs on your computer;
- use Claude Desktop with the local proxy (Connection → Claude Desktop with a local site);
- expose the site with a tunnel such as LocalWP **Live Link** or **Cloudflare Tunnel**, and set WordPress' Site URL to the tunnel address while connecting.

## 4. Coding tools

Copy the snippet for your tool from Connection → Coding tools. Examples with OAuth:

```bash
claude mcp add --transport http --scope user mcp-builder https://your-site/wp-json/mcp-builder/mcp
```

```bash
codex mcp add mcp-builder --url https://your-site/wp-json/mcp-builder/mcp
```

With an access token, Codex reads it from an environment variable:

```toml
[mcp_servers.mcp_builder]
url = "https://your-site/wp-json/mcp-builder/mcp"
bearer_token_env_var = "MCP_BUILDER_TOKEN"
```

## 5. Recommended settings for production

- Create a dedicated WordPress user (Editor) for AI work. Only grant Administrator if the AI needs site-wide changes (global classes, theme styles, templates, kit globals).
- Keep **Allow custom code** off unless you need it.
- Keep **dry run by default** on until you trust your workflow.
- Prefer read-only connections for apps that only need to look.
- Check the audit log under Safety after each session.
- Keep regular backups. Snapshots cover builder data and global settings, not media or database-wide changes.

## 6. Updating and uninstalling

Upload the new zip over the old one. Database tables upgrade on load.

Deleting the plugin from the Plugins screen removes its settings, audit log, snapshots, OAuth clients and tokens. Application Passwords belong to users and stay until revoked under Users → Profile.
