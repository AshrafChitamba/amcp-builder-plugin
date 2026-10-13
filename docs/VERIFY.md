# Unverified assumptions

Every place the code relies on builder or WordPress internals is marked
`// VERIFY:` in the source. This list is the consolidated view.

**Verified live** (LocalWP, 2026-10-12/13: WordPress 7.1.3, PHP 8.2, Bricks 2.3.1, MCP Adapter 0.7.0, admin user) means
the behaviour was exercised through the MCP endpoint and read back.

## Verified live

| File | Assumption |
|---|---|
| `src/Support/PageBuilder.php` | Bricks pages are found by `_bricks_editor_mode = bricks` or existing `_bricks_page_content_2` (4 real pages found). |
| `src/Bricks/Store.php` | Saving like `Bricks\Ajax::save_post`: `wp_slash`, `Helpers::security_check_elements_before_save()`, revision, `update_post_meta`, editor mode, `wp_update_post()`. Writes read back intact. |
| `src/Bricks/BricksCatalog.php` | `Bricks\Elements::$elements` is populated in REST requests and `Elements::get_element()` loads controls on demand (88 container controls, custom elements listed). |
| `src/Abilities/Bricks/GetElementSchema.php` | `Bricks\Breakpoints::get_breakpoints()` returns `key`/`width` pairs. |
| `src/Bricks/GlobalClasses.php` | `bricks_global_classes` is a list of `{id, name, settings, …}`; classes are referenced from `settings._cssGlobalClasses`. |
| `src/Safety/Governance.php` | WordPress 7.1 `wp_pre_execute_ability` / `wp_ability_execute_result` and the adapter's `mcp_adapter_tools_list` filter (read-only hides writes, denials audited). |
| `src/Elementor/Store.php` | `Documents::get( $id, false )`, `Document::save( [ 'elements' ] )` / `( [ 'settings' ] )`, `get_elements_data()`; read-back element counts match (Elementor 4.3.4, 141 elements). |
| `src/Elementor/ElementorCatalog.php` | In REST requests `get_controls()` includes style and common (`_`-prefixed) controls; responsive base keys accept `_tablet` / `_mobile`; group switches found by the `elementor-group-control-{type}` class; 406 container controls read. |
| `src/Abilities/Elementor/UpdatePageSettings.php` | Page settings save merges correctly and `template` writes `_wp_page_template` (`elementor_canvas`). |
| `src/Bricks/Templates.php` | `bricks_template` posts with `_bricks_template_type` and `_bricks_template_settings['templateConditions']`; conditions save and list back (template 1046, 2026-10-13). |
| `src/Bricks/DesignTokens.php` | `bricks_color_palette`, `bricks_global_variables`, `bricks_theme_styles` layouts read correctly; dry-run upserts produce the expected diffs. |
| `src/Bricks/CustomCss.php` | `_cssCustom` is stored with the real selector; `%root%` is only replaced by the builder, so we replace it. |
| `src/OAuth/Server.php` | Discovery at REST, root path-inserted and OIDC-appended URLs; DCR; 401 `WWW-Authenticate` with `resource_metadata`; bearer access to the MCP route (LocalWP, http). |
| `src/Abilities/Elementor/CreateTemplate.php`, `src/Elementor/Templates.php` | `Documents_Manager::create()` sets `_elementor_edit_mode`, `_elementor_template_type` and the `elementor_library_type` term (header template, Elementor 4.3.4 / Pro 4.3.1, 2026-10-13). Atomic `e-*` types and `kit` are filtered from the creatable types. |
| `src/Abilities/Elementor/SetConditions.php` | `save_conditions()` updates `_elementor_conditions` and the `elementor_pro_theme_builder_conditions` cache; `get_documents_for_location('header')` matches only the chosen page. Restoring the snapshot clears both. |
| `src/Abilities/Elementor/UpdateGlobals.php` | Kit `save()` keeps settings; on a new kit the default system colors/fonts are seeded so `id: "primary"` updates in place; `--e-global-color-primary` in the kit CSS follows. |
| `src/Abilities/Elementor/UpsertCodeSnippet.php` | Meta keys match Pro's `Custom_Code_Metabox` fields; a published snippet with `include/singular/page/ID` prints on that page only. |
| `src/Abilities/General/CreatePage.php` | create-page with `builder: elementor` sets up a page that the Elementor tools can edit (page 43, 2026-10-13). |

**Known limitation (verified):** block themes such as Twenty Twenty-Five don't call `get_header()` / `get_footer()`, so Elementor Pro header and footer templates never show there even when conditions match. The template tools now return a warning.

## Still to verify

| # | File | Assumption | How to check |
|---|---|---|---|
| 1 | `src/Environment.php` | Elementor's Atomic editor experiment is `e_atomic_elements`. | Toggle Atomic in Elementor → Settings → Features; site-info `atomicOn` should follow. |
| 2 | `src/Abilities/General/CreatePage.php` | Bricks' enabled post types are `bricks_global_settings['postTypes']`. | Enable/disable "Posts" in Bricks → Settings, then create-page with `postType: post`. |
| 3 | `src/Abilities/General/CreatePage.php` | Elementor's enabled post types are the `elementor_cpt_support` option. | Same, in Elementor → Settings. |
| 4 | `src/Abilities/General/CreatePage.php` | `_bricks_editor_mode = bricks` is enough for Bricks to open a new page in the builder. | Open page 983's `builderEditLink`. |
| 6 | `src/Bricks/Store.php` | `wp_update_post()` makes Bricks regenerate the CSS file in **External files** mode. | Bricks → Settings → Performance → CSS loading: External files; edit a page via MCP; check `uploads/bricks/css/post-{ID}.min.css` updates. |
| 7 | `src/Bricks/Store.php` | A non-admin role with Bricks "Edit content" access gets a clear error, not a silent partial save. | Use an Editor account with content-only access; try bricks-add-elements. |
| 8 | `src/Safety/Targets.php` | Bricks 2.4 official ability names contain `global-class`, `variable`, `color-palette`, `theme-style`, `component`, `global-quer`, `global-settings` for option snapshots. | On Bricks 2.4+, run an official global write and check Safety → Recent snapshots. |
| 9 | `src/Safety/Snapshots.php` | `\Elementor\Core\Files\CSS\Post::create( $id )->delete()` clears Elementor CSS after a restore. | Restore an Elementor page snapshot; the page should show restored styles. |
| 10 | `src/Bricks/BricksCatalog.php` | Value-shape hints match what Bricks saves. Box-shadow, background image, gradient overlay, border, icon (`fontawesomeSolid` / `fas fa-…`) and internal link were checked against real data on 2026-10-12; the box-shadow hint was corrected (Bricks 2.3.1 stores `values` as an object, unlike the 2.4 schema). | Check the remaining types (typography, transform, filters) the same way. |
| 15 | `src/OAuth/*` | Interactive approval with claude.ai, ChatGPT and Codex (callbacks, CIMD fetch, refresh rotation). | Public HTTPS site or tunnel; run the OAuth section of qa-checklist.md. |
| 16 | `src/Bricks/Templates.php` | Bricks applies conditions from the cache after `wp_cache_set( 'last_changed', …, 'bricks_bricks_template' )`. | Publish a header template with a single-page condition and view that page and another page. |

Checked against source (not live): MCP Adapter 0.7.0 `create_server()`,
tool errors and schema normalisation; WordPress 7.1 `WP_Ability::execute()`
hook order; Elementor 4.3.4 `Document::save()`; Bricks 2.3.1 `Ajax::save_post`,
`Helpers::security_check_elements_before_save`, `Capabilities::current_user_can_use_builder`.
