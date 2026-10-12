# Unverified assumptions

Every place the code relies on builder or WordPress internals is marked
`// VERIFY:` in the source. This list is the consolidated view.

**Verified live** (LocalWP, 2026-10-12: WordPress 7.1.3, PHP 8.2, Bricks 2.3.1, MCP Adapter 0.7.0, admin user) means
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

## Still to verify

| # | File | Assumption | How to check |
|---|---|---|---|
| 1 | `src/Environment.php` | Elementor's Atomic editor experiment is `e_atomic_elements`. | Toggle Atomic in Elementor → Settings → Features; site-info `atomicOn` should follow. |
| 2 | `src/Abilities/General/CreatePage.php` | Bricks' enabled post types are `bricks_global_settings['postTypes']`. | Enable/disable "Posts" in Bricks → Settings, then create-page with `postType: post`. |
| 3 | `src/Abilities/General/CreatePage.php` | Elementor's enabled post types are the `elementor_cpt_support` option. | Same, in Elementor → Settings. |
| 4 | `src/Abilities/General/CreatePage.php` | `_bricks_editor_mode = bricks` is enough for Bricks to open a new page in the builder. | Open page 983's `builderEditLink`. |
| 5 | `src/Abilities/General/CreatePage.php` | Elementor `Documents::get()->save()` + `set_is_built_with_elementor(true)` sets up an empty page. | create-page with Elementor active. |
| 6 | `src/Bricks/Store.php` | `wp_update_post()` makes Bricks regenerate the CSS file in **External files** mode. | Bricks → Settings → Performance → CSS loading: External files; edit a page via MCP; check `uploads/bricks/css/post-{ID}.min.css` updates. |
| 7 | `src/Bricks/Store.php` | A non-admin role with Bricks "Edit content" access gets a clear error, not a silent partial save. | Use an Editor account with content-only access; try bricks-add-elements. |
| 8 | `src/Safety/Targets.php` | Bricks 2.4 official ability names contain `global-class`, `variable`, `color-palette`, `theme-style`, `component`, `global-quer`, `global-settings` for option snapshots. | On Bricks 2.4+, run an official global write and check Safety → Recent snapshots. |
| 9 | `src/Safety/Snapshots.php` | `\Elementor\Core\Files\CSS\Post::create( $id )->delete()` clears Elementor CSS after a restore. | Restore an Elementor page snapshot; the page should show restored styles. |
| 10 | `src/Bricks/BricksCatalog.php` | Value-shape hints match what Bricks saves. Box-shadow, background image, gradient overlay, border, icon (`fontawesomeSolid` / `fas fa-…`) and internal link were checked against real data on 2026-10-12; the box-shadow hint was corrected (Bricks 2.3.1 stores `values` as an object, unlike the 2.4 schema). | Check the remaining types (typography, transform, filters) the same way. |

Checked against source (not live): MCP Adapter 0.7.0 `create_server()`,
tool errors and schema normalisation; WordPress 7.1 `WP_Ability::execute()`
hook order; Elementor 4.3.4 `Document::save()`; Bricks 2.3.1 `Ajax::save_post`,
`Helpers::security_check_elements_before_save`, `Capabilities::current_user_can_use_builder`.
