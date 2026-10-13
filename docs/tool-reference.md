# Tool reference

All tools are served at `https://your-site/wp-json/mcp-builder/mcp`. In MCP clients their names start with `mcp-builder-` (for example `mcp-builder-site-info`). Builder tools appear only when that builder is active. `*` marks a required input.

Every write tool:

- accepts `dryRun` (returns the change without saving; the default comes from Settings → MCP Builder → Safety);
- takes a snapshot first, so `restore-snapshot` can undo it;
- is refused in read-only mode, by the kill switch, when the tool is switched off, for read-only connections, and when another user is editing the page (pass `force: true` to override, where offered);
- is recorded in the audit log.

## General

| Tool | Type | Inputs | What it does |
|---|---|---|---|
| `site-info` | read | – | Active builders and versions, read-only and dry-run state, custom-code state, and where to start. Call it first. |
| `get-design-guide` | read | `topic` (design, bricks, elementor, safety, site) | Building guidance as markdown: design rules plus the site's own guidelines, and the builder's data formats and pitfalls. |
| `list-pages` | read | `builder`, `postType`, `status`, `search`, `page`, `perPage` | Pages the user can edit, with builder, edit links and who has them open. |
| `create-page` | write | `title*`, `builder*`, `postType`, `status`, `dryRun` | Creates an empty page set up for Bricks or Elementor (draft by default). |
| `update-page` | write | `postId*`, `title`, `slug`, `status`, `dryRun` | Changes title, slug or status. Publishing needs permission to publish that post. |
| `list-media` | read | `search`, `mimeType`, `page`, `perPage` | Media Library attachments with IDs, URLs, sizes and alt text. |
| `upload-media` | write | `url*`, `altText*`, `title`, `dryRun` | Imports an image (JPEG, PNG, GIF, WebP, AVIF; max 10 MB) from a public https URL. |
| `list-snapshots` | read | `postId`, `limit` | Automatic backups taken before each change. |
| `restore-snapshot` | write | `snapshotId*`, `dryRun` | Restores a snapshot and snapshots the current state first, so the restore can be undone. |

## Bricks

| Tool | Type | Inputs | What it does |
|---|---|---|---|
| `bricks-get-page` | read | `postId*`, `rootId`, `depth`, `full` | Elements of a page or template as a nested outline. |
| `bricks-list-elements` | read | `category`, `search` | Element types on this site, including custom elements. |
| `bricks-get-element-schema` | read | `name*` | Every settings key one element accepts, with value shapes. |
| `bricks-add-elements` | write | `postId*`, `elements*`, `parentId`, `position`, `dryRun`, `force` | Adds a nested tree of native elements. IDs are generated and returned. |
| `bricks-update-element` | write | `postId*`, `elementId*`, `settings`, `mode` (merge/replace), `label`, `dryRun`, `force` | Changes one element. `null` removes a key. `%root%` in `_cssCustom` becomes the element selector. |
| `bricks-move-element` | write | `postId*`, `elementId*`, `parentId`, `position`, `dryRun`, `force` | Moves or reorders an element and its children. |
| `bricks-duplicate-element` | write | `postId*`, `elementId*`, `dryRun`, `force` | Copies an element with new IDs, right after the original. |
| `bricks-remove-element` | write | `postId*`, `elementId*`, `dryRun`, `force` | Deletes an element and its children. |
| `bricks-list-global-classes` | read | `search` | Global classes with the style keys they set. |
| `bricks-upsert-global-class` | write | `name*`, `settings*`, `category`, `mode`, `dryRun` | Creates or updates a global class (site-wide). |
| `bricks-apply-global-class` | write | `postId*`, `elementIds*`, `classes*`, `action` (add/remove), `dryRun`, `force` | Adds or removes classes on elements. |
| `bricks-list-templates` | read | `type` | Headers, footers, single, archive, section and popup templates with their conditions. |
| `bricks-create-template` | write | `title*`, `type*`, `conditions`, `dryRun` | Creates an empty draft template. Fill it with `bricks-add-elements` using its ID. |
| `bricks-set-template-conditions` | write | `templateId*`, `conditions*`, `dryRun` | Sets where a template is used (entire site, front page, post types, IDs, terms, archives, hooks, exclusions). |
| `bricks-get-design-tokens` | read | `variableSearch` | Color palettes, CSS variables, theme styles and the class count. |
| `bricks-upsert-colors` | write | `colors*`, `palette`, `dryRun` | Adds or updates palette colors (site-wide). |
| `bricks-upsert-variables` | write | `variables*`, `dryRun` | Adds or updates global CSS variables (site-wide). |
| `bricks-update-theme-style` | write | `settings*`, `styleId` or `label`, `conditions`, `dryRun` | Changes a theme style group by group (site-wide). |
| `bricks-update-page-settings` | write | `postId*`, `settings*`, `dryRun` | Body classes, header/footer visibility, SEO, sharing and page CSS. Page scripts need custom code. |
| `bricks-code-status` | read | `postId` | Code execution state and the signature status of every Code and SVG-code element. |

## Elementor

| Tool | Type | Inputs | What it does |
|---|---|---|---|
| `elementor-get-page` | read | `postId*`, `rootId`, `depth`, `full` | Elements of a page or template as a nested outline, with its structure (containers, sections, atomic). |
| `elementor-list-widgets` | read | `category`, `search` | Widgets registered on this site. |
| `elementor-get-widget-schema` | read | `widget*`, `search`, `tab` | Settings keys for a widget or `container`, with responsive variants. |
| `elementor-get-globals` | read | – | Kit colors and typography with `__globals__` references. |
| `elementor-add-elements` | write | `postId*`, `elements*`, `parentId`, `position`, `dryRun`, `force` | Adds a nested tree of containers and widgets. |
| `elementor-update-element` | write | `postId*`, `elementId*`, `settings`, `mode`, `dryRun`, `force` | Changes one element. |
| `elementor-move-element` | write | `postId*`, `elementId*`, `parentId`, `position`, `dryRun`, `force` | Moves or reorders an element. |
| `elementor-duplicate-element` | write | `postId*`, `elementId*`, `dryRun`, `force` | Copies an element with new IDs. |
| `elementor-remove-element` | write | `postId*`, `elementId*`, `dryRun`, `force` | Deletes an element and its children. |
| `elementor-update-page-settings` | write | `postId*`, `settings*`, `dryRun` | Page settings such as template and hide title. |
| `elementor-update-globals` | write | `colors`, `typography`, `dryRun` | Adds or updates kit colors and fonts (site-wide; needs `edit_theme_options`). |
| `elementor-list-templates` | read | `type` | Library and Theme Builder templates with conditions, plus the supported types. |
| `elementor-create-template` | write | `title*`, `type*`, `dryRun` | Creates an empty draft template of a supported type (header, footer, single, archive, section, loop item, popup…). |
| `elementor-set-conditions` | write | `templateId*`, `conditions*`, `dryRun` | Theme Builder display conditions (Elementor Pro). |
| `elementor-upsert-code-snippet` | write, custom code | `code*`, `snippetId`, `title`, `location`, `priority`, `conditions`, `dryRun` | Elementor Pro Custom Code snippet, created as a draft. |

Atomic (v4) Elementor elements are read-only. Edits to them are refused with a pointer to Elementor's own tools.

## Custom code

These are refused until an administrator turns on **Allow custom code** (Settings → MCP Builder → Tools), and they then work only for accounts with `manage_options` and `unfiltered_html`:

- the Bricks Code element and SVG code;
- Bricks page scripts;
- the Elementor HTML widget;
- `elementor-upsert-code-snippet`.

Bricks code that runs PHP or HTML is saved unsigned. An administrator signs it in Bricks. MCP Builder never signs code.

## Resources

| URI | Content |
|---|---|
| `mcp-builder://guides/design` | Design rules plus the site's own guidelines. |
| `mcp-builder://guides/bricks` | Bricks data formats, pitfalls, templates, loops, code. (Bricks sites only) |
| `mcp-builder://guides/elementor` | Elementor formats, globals, templates, conditions. (Elementor sites only) |
| `mcp-builder://guides/safety` | Snapshots, dry run, read-only, untrusted content. |

## Prompts

| Prompt | Arguments | Use |
|---|---|---|
| `design-page` | `brief*`, `postId` | Plan and build a page step by step with the site's design system. |
| `review-page` | `postId*` | Check a page for structure, accessibility, responsiveness and consistency before publishing. |

Clients that don't show resources or prompts can call `get-design-guide`, which returns the same guides.
