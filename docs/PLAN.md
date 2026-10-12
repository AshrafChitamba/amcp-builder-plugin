# MCP Builder: Plan (Step 2)

Author: Ashraf Chitambaa (https://ashrafchitambaa.com) · License: GPL-2.0-or-later · Status: draft for review

## 1. What we are building (and why it changed)

Research (Oct 2026) found that both builders now ship **official, free MCP abilities** on the WordPress Abilities API + MCP Adapter:

- **Bricks 2.4** "AI Abilities" (experimental): page elements get/set, element schemas, classes, variables, templates, components, queries, media. Code execution is off by default.
- **Elementor 4.3** "Elementor MCP": full toolset, but **only for v4 Atomic elements**. Classic widgets/Containers are not editable through it.

Decision (owner, 2026-10-12): **build our own toolset and don't depend on the official ones.** MCP Builder is one plugin that:

1. **Builds pages itself**:
   - **Bricks:** a full toolset of our own (pages, elements, classes, theme styles, variables, templates, query loops, code).
   - **Elementor:** **classic** Containers + v3 widgets. Atomic (v4) pages are read-only in v1; Atomic editing is a later phase.
2. **Governs every MCP ability on the site**, ours *and* the official Bricks/Elementor ones if someone enables them: kill switch, read-only mode, per-tool toggles, rate limits, audit log, automatic snapshots + restore, post-lock protection.

One MCP endpoint (`/wp-json/mcp-builder/mcp`) exposes our tools (and, optionally, official abilities the admin enables), so users configure a single connection.

## 2. Architecture

```
AI client ──HTTP (App Password)──► /wp-json/mcp-builder/mcp   (MCP Adapter server we create)
          └─stdio proxy / WP-CLI ─┘                │
                                                   ▼
                                    WP Abilities API (core)
                   ┌──────────────────────┼─────────────────────────┐
             mcp-builder/* abilities   bricks/* abilities    elementor/* abilities
                   │                         ▲                       ▲
                   └─ Governance hooks wrap ALL executions ──────────┘
                      (permission_result → policy, pre/after execute → snapshot, audit)
```

- **PHP only.** Requires the **MCP Adapter plugin** via `Requires Plugins: mcp-adapter` (bundling was deprecated in adapter 0.7.0).
- Governance lives on core ability hooks (`wp_ability_permission_result`, `wp_pre_execute_ability`, `wp_after_execute_ability`). These hooks are what let us govern abilities we don't own. Some arrived in **WP 7.1**, so **minimum WP = 7.1**. `// VERIFY: which of these exist in 6.9/7.0; if needed, 7.0 fallback = hide-only via mcp_adapter_tools_list.`
- The admin UI is React + TS (`@wordpress/scripts`, `@wordpress/components`), talking to `mcp-builder/v1` REST routes (`manage_options`).
- Storage: options for settings; two custom tables, `{prefix}mcpb_audit` and `{prefix}mcpb_snapshots` (gzip-compressed payloads).

### Minimum versions

| Component | Minimum | Why |
|---|---|---|
| WordPress | 7.1 | ability execution filters (governance) |
| PHP | 8.1 | modern syntax; WP 7.x recommends 8.1+ |
| MCP Adapter | 0.7.0 | canonical-plugin model, sessionless protocol |
| Bricks | 2.4 (official abilities); our Bricks code/diagnostic tools work on 2.0+ | |
| Elementor | 3.25 for classic tools; 4.3 to proxy official Elementor MCP | |
| Elementor Pro | optional; enables conditions + custom CSS tools | |

Missing builder → those tools are not registered. Below minimum → admin notice + Status tab warning, tools disabled.

### Clients

| Client | How it connects |
|---|---|
| Claude Code | native HTTP + `Authorization: Basic …` header |
| Cursor | native HTTP + headers |
| Claude Desktop | `npx @automattic/mcp-wordpress-remote` (needs Node on that PC) **or** `wp mcp-adapter serve` (LocalWP, no Node). Remote connectors need OAuth or beta headers, so they're not supported in v1. |

OAuth: **not in v1.** No WordPress-side OAuth server exists in core or the adapter; Application Passwords cover every client above.

## 3. Governance design

| Feature | Behaviour |
|---|---|
| Kill switch | Setting + constant `MCP_BUILDER_DISABLE`. Denies every ability in managed namespaces (`mcp-builder/`, `bricks/`, `elementor/`) and returns 503 on our endpoint. |
| Read-only mode | Denies any managed ability whose `annotations.readonly !== true`. Unknown = write. |
| Per-tool toggles | The Tools tab lists all managed abilities (from `wp_get_abilities()`), grouped. Disabled tools are hidden from `tools/list` and denied on execution. |
| Custom-code group | Off by default; requires `manage_options` **and** `unfiltered_html`. Includes `bricks/execute-php` if present. |
| Rate limit | Per user, sliding minute window (object cache, transient fallback). Defaults: 120 reads/min, 30 writes/min. Best-effort only. |
| Audit log | Every managed execution: time, user, ability, post ID, outcome (ok / error / denied / dry-run), duration, snapshot ID, input summary (truncated, secrets redacted). Retention 90 days (configurable). Daily cron prune. |
| Snapshots | Before a write, if the post or option it targets can be resolved (see Target resolvers), copy the relevant meta/options. Retention: 20 per target, 30 days. `restore-snapshot` restores it *after taking a snapshot of the current state* (so a restore can be undone). |
| Post lock | Writes to a post locked by another user in the editor are denied unless `force: true` (our tools) or the setting allows it (official tools). |
| Dry run | All **our** write tools accept `dryRun` (returns a diff, no save). Official abilities have fixed schemas, so dry run isn't possible for them, and the docs will say so. |

**Target resolvers.** This is a small map from ability name to how to find its target, e.g. `bricks/set-page-elements` → `input.postId` → snapshot `_bricks_page_content_2` / `_bricks_page_settings`. Global-data abilities → snapshot the named options. Unknown write abilities get audit only, with no snapshot, and the Safety tab flags them. `// VERIFY:` every official ability's input keys against live 2.4.2 / 4.3.4 (each map entry is marked).

**Prompt-injection mitigations.** Site content is returned only inside structured fields (`content`, `settings`) and never inside tool descriptions. Every tool description and the guide resource state that page content is untrusted data. Text fields are truncated (default 2 000 chars per field, opt-in `full: true`). Destructive tools require explicit IDs. All writes are audited and snapshotted, so a hijacked session is visible and reversible.

## 4. Tool list (our abilities, namespace `mcp-builder/`)

Every write tool takes `dryRun?: boolean` (default from settings) and returns `{ dryRun, snapshotId?, diff?, warnings[] }`. Errors are `WP_Error`s whose **message** says what went wrong *and how to fix it* ("… How to fix: …"), because the adapter forwards only the message to the client (bug #298 drops error data).

### General
| Tool | Input | Output | RO |
|---|---|---|---|
| `site-info` | — | WP/PHP/builder versions, active builders, enabled tool groups, safety state, guide URIs | ✔ |
| `list-pages` | `builder?: bricks\|elementor\|any`, `postType?`, `status?`, `search?`, `page?`, `perPage≤50` | `[{id,title,status,type,builder,modified,editLink,locked}]`, total | ✔ |
| `create-page` | `title`, `builder`, `postType=page`, `status=draft`, `templateId?` | `{id, editLink}` | |
| `list-snapshots` | `postId?`, `target?`, `limit≤50` | `[{id, target, ability, user, created, size}]` | ✔ |
| `restore-snapshot` | `snapshotId`, `dryRun?` | `{restored, newSnapshotId}` | |

### Elementor classic (registered only when Elementor is active)
Every write tool **refuses Atomic content** (elements with `e-*` types or `version` keys) with `code: atomic_not_supported` and a pointer to the official `elementor/*` tools.

| Tool | Input | Output |
|---|---|---|
| `elementor-get-page` | `postId`, `depth?`, `compact=true` | tree `{id, elType, widgetType?, settings(non-default only when compact), children}` + `structure: containers\|sections\|atomic\|mixed` |
| `elementor-list-widgets` | `category?`, `search?` | `[{name, title, category, isPro}]` |
| `elementor-get-widget-schema` | `widget` (or `container`) | `{controls:[{key,type,default,options?,responsive,group?}]}`, compacted and cached per Elementor version |
| `elementor-add-elements` | `postId`, `parentId?` (null = root), `position?`, `elements: ElementSpec[]` (nested) | created IDs, diff |
| `elementor-update-element` | `postId`, `elementId`, `settings` (patch), `mode=merge\|replace` | diff |
| `elementor-move-element` | `postId`, `elementId`, `newParentId?`, `position` | diff |
| `elementor-duplicate-element` | `postId`, `elementId` | new ID |
| `elementor-remove-element` | `postId`, `elementId` (destructive) | diff |
| `elementor-get-globals` | — | kit colors + typography `{id,title,value}` and the `__globals__` reference format |
| `elementor-update-globals` | `colors?[]`, `typography?[]` (upsert by id) | diff |
| `elementor-list-templates` | `type?` | `[{id,title,type,conditions?}]` |
| `elementor-set-conditions` *(Pro)* | `templateId`, `conditions: string[]` | diff |

`ElementSpec` = `{elType: container|widget, widgetType?, settings?, elements?}`. We generate IDs (7 hex chars, unique within the document) and validate settings keys against the live widget schema (unknown key → error listing close matches). Writes go through `Documents::get()->save()`, then `set_is_built_with_elementor(true)` and post CSS regeneration.

### Bricks (registered only when Bricks is the active parent theme)
Bricks is the office's main builder, so it is built first (Phase 3).

| Tool | Input | Output | RO |
|---|---|---|---|
| `bricks-get-page` | `postId`, `area=content\|header\|footer`, `compact=true` | nested outline `{id,name,label?,settings,children[]}` + element count | ✔ |
| `bricks-list-elements` | `category?`, `search?` | `[{name, label, category, nestable}]` from the live registry | ✔ |
| `bricks-get-element-schema` | `name` | `{controls:[{key,type,default?,options?,group?}]}`, compacted and cached per Bricks version | ✔ |
| `bricks-add-elements` | `postId`, `area`, `parentId?` (null = root), `position?`, `elements: BricksSpec[]` (nested) | created IDs, diff | |
| `bricks-update-element` | `postId`, `area`, `elementId`, `settings` patch, `mode=merge\|replace`, `label?` | diff | |
| `bricks-move-element` | `postId`, `area`, `elementId`, `newParentId?`, `position` | diff | |
| `bricks-duplicate-element` | `postId`, `area`, `elementId` | new IDs (whole subtree re-IDed) | |
| `bricks-remove-element` | `postId`, `area`, `elementId` (destructive, removes subtree) | diff | |
| `bricks-list-global-classes` | `search?`, `category?` | `[{id,name,category,settingsSummary}]` | ✔ |
| `bricks-upsert-global-class` | `name`, `settings`, `category?` | `{id}` | |
| `bricks-apply-global-class` | `postId`, `area`, `elementIds[]`, `classIds[]`, `action=add\|remove` | diff | |
| *(Phase 5)* `bricks-get/update-theme-styles`, `bricks-list/upsert-variables`, `bricks-get/update-color-palette`, `bricks-list-templates`, `bricks-create-template`, `bricks-set-template-conditions`, `bricks-set-query-loop` | | | |
| `bricks-code-status` | `postId?` | execution enabled?, can the current user execute code?, code-bearing elements `{postId, elementId, type, signed: valid\|invalid\|missing}`, `reviewUrl` | ✔ |

`BricksSpec` = `{name, label?, settings?, children?: BricksSpec[]}`. We flatten it to Bricks' flat array (`id` 6 × `[a-z0-9]`, unique on the page, `parent` 0 for root, ordered `children`) and validate names against the registry and settings keys against the element's controls plus the shared `_`-prefixed style keys.

**Saving.**
- Run as the authenticated user, who must pass Bricks' own builder-access check. The `update_post_metadata` guard is **never bypassed**.
- Apply Bricks' pre-save security check if available (`// VERIFY: Helpers::security_check_elements_before_save`).
- Write serialized arrays via `update_post_meta`, set `_bricks_editor_mode=bricks`, create a WP revision, then regenerate the post CSS file in external-files mode (`// VERIFY: Assets_Files::generate_post_css_file`).
- Read back to confirm the write.

### Custom code group (off by default)
| Tool | Notes |
|---|---|
| `elementor-add-html-widget` | Uses the native `html` widget. Elementor itself kses-strips for users without `unfiltered_html`; we refuse up-front with a clear message. |
| `elementor-set-custom-css` *(Pro)* | Element or page `custom_css`. `// VERIFY:` key name in Pro 4.3. |
| `bricks-add-code-element` | Native Code element (HTML/CSS/JS/PHP). Saved **unsigned** by design and added to our "Pending code review" list; `bricks-code-status` explains how an admin signs it in Bricks → Settings → Custom code. **No API signing.** CSS/JS-only code doesn't need a signature and works immediately (if execution rules allow). |

### Resources and prompts
- Resources: `mcp-builder://guides/safety` (how snapshots/dry-run/read-only work, and the untrusted-content warning), `mcp-builder://guides/elementor-classic` (good Container layouts, globals, responsive keys), `mcp-builder://guides/bricks` (when to use official Bricks tools, class-first styling, code-review flow).
- Prompts: `build-elementor-page` (args: postId, brief), `review-before-publish` (runs read tools and summarises the changes since the last snapshot).

## 5. Builder data-model notes (from research; details in `docs/internals.md` during build)

**Elementor classic.** `_elementor_data` (JSON in meta; `save()` slashes and encodes it itself), `_elementor_edit_mode='builder'`, `_elementor_page_settings`. Elements are `{id, elType, widgetType, settings, elements, isInner}`. Responsive keys use `_tablet` / `_mobile` (etc.) suffixes from the breakpoints manager. Globals are referenced via `settings.__globals__[control] = 'globals/colors?id=…'`. `save()` returns false without edit rights and kses-filters everything for users without `unfiltered_html`. Unregistered widgets are **silently dropped** on save, so we validate before saving.

**Elementor Atomic.** Props are `{"$$type":…, value}`, plus a `styles` map and global classes CPT. This is out of scope; it goes to the official tools.

**Bricks.** `_bricks_page_content_2` / `_header_2` / `_footer_2` are **serialized PHP arrays**. Elements are a flat array: `{id(6 [a-z0-9]), name, parent (0=root), children[], settings}`. Globals are options: `bricks_global_classes`, `bricks_global_variables` (no `--`), `bricks_theme_styles`, `bricks_color_palette`, `bricks_components`, `bricks_global_queries`. Code signature = `wp_hash()` of the code (breaks on salt change). It's required for Code element PHP/HTML execution, SVG code, query-editor PHP, component and global queries; **not** for Code-element CSS/JS or `_cssCustom`. The official JSON Schema ZIP (schemaVersion 2.4.2) is used for our offline test fixtures.

**Warnings surfaced on the Status tab:** `BRICKS_DANGEROUSLY_AUTO_SIGN_CODE_ON_*` defined; Bricks code execution enabled; Elementor `unfiltered_html` disallowed; Application Passwords unavailable; plain permalinks; `Authorization` header stripped (we self-test it).

## 6. Admin UI (Settings → MCP Builder)

1. **Connection**: endpoint URL (copy), create/revoke an Application Password for the current user (core REST), and snippets for Claude Code, Cursor, Claude Desktop (proxy) and Claude Desktop (WP-CLI/LocalWP).
2. **Tools**: Read-only switch at the top. Groups: General / Elementor (ours) / Elementor (official) / Bricks (official + ours) / Custom code ⚠ / Other managed. Each tool has a toggle, RO/write badge and a one-line description.
3. **Safety**: kill switch, dry-run default, snapshot retention, rate limits, post-lock policy, audit log viewer (filter by user/tool/post, paginated, CSV export), pending code reviews.
4. **Status**: versions vs minimums, REST reachability, HTTPS, App Passwords, Authorization-header self-test, "Test connection" (runs `initialize` + `tools/list` against our endpoint).
5. **About**: version, docs, "Built by Ashraf Chitambaa" (linked), no-telemetry statement.

## 7. Repo layout

```
/plugin       mcp-builder/ (PHP, PSR-4 `McpBuilder\`, composer, phpunit, phpcs) + built admin assets
/admin-ui     React + TS source → builds into /plugin/build
/docs         PLAN, architecture, tool reference, security, deployment, troubleshooting, QA checklist, VERIFY list
/scripts      build-zip.sh, release.sh
.wp-env.json  optional Docker integration env (WP 7.1 + mcp-adapter)
```

## 8. Roadmap (pause for your OK after each phase)

| Phase | Delivers |
|---|---|
| **1. Foundation** | Plugin skeleton, composer/PSR-4, dependency + version checks, settings store, our MCP server, `site-info`, `list-pages`, `create-page`, admin UI shell with Connection/Status/About, PHPUnit + Brain Monkey, PHPCS, Jest for the UI. |
| **2. Safety layer** | Policy engine (kill switch, read-only, toggles, rate limit, custom-code gate, post lock), audit table + viewer, snapshot table + restore, resolvers for official abilities, Tools + Safety tabs. |
| **3. Bricks pages** | Tree model (flat ⇄ nested), ID generator, validator, registry/schema tools, add/update/move/duplicate/remove, global classes, dry-run diffs, snapshot tests against official schema fixtures. |
| **4. Elementor classic pages** | Same set for Elementor (Atomic read-only). |
| **5. Globals & templates** | Bricks theme styles, variables, palette, templates + conditions, query loops; Elementor kit globals, templates, Pro conditions. |
| **6. Custom code** | Bricks Code element + code status + review queue; Elementor HTML widget / Pro custom CSS. |
| **7. Ship** | README, tool reference, deployment, troubleshooting, LocalWP QA checklist, VERIFY list, `wp-env`, release ZIP script, Plugin Check pass. |
| *Later* | Elementor Atomic (v4) editing; Bricks components. |

## 9. Risks and open items

- Our Bricks tools rely on Bricks internals that have no public API (meta format, registry, save guard). Each one is isolated in one adapter class with `// VERIFY:` notes and read-back checks.
- The official abilities' resolver map (for governing them) is versioned and fails safe (audit-only).
- MCP Adapter 0.7.0 bugs: #298 (error data dropped; we work around it), #308 (WP-CLI stdio exits after 60 s, which affects the LocalWP Desktop route), #368 (list calls through the proxy on 0.7.0). These will be tracked in Troubleshooting.
- Elementor's MCP registers its own server. We list its abilities on ours by name, `// VERIFY:` that its V4 gate still works there.
- Avoid the npm package `bricks-builder-mcp` (flagged malicious, MAL-2026-4500). This goes in the docs.
- Untested against live builds until you run the QA checklist on LocalWP. Every assumption is marked `// VERIFY:`.
