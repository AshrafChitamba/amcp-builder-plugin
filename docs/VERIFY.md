# Unverified assumptions

Every place the code relies on builder or WordPress internals that could not be
checked against a live site is marked `// VERIFY:` in the source. This list is
the consolidated view; tick items off during the LocalWP QA pass.

| # | File | Assumption | How to check |
|---|---|---|---|
| 1 | `plugin/src/Environment.php` | Elementor's Atomic editor experiment is named `e_atomic_elements`. | Elementor → Settings → Features: toggle Atomic, then see that site-info `atomicOn` follows it. |
| 2 | `plugin/src/Support/PageBuilder.php` | Bricks pages carry `_bricks_editor_mode = bricks` or have `_bricks_page_content_2`. | Edit a page with Bricks, save, and check that list-pages reports `builder: bricks`. |
| 3 | `plugin/src/Abilities/General/CreatePage.php` | Bricks' enabled post types live in `bricks_global_settings['postTypes']`. | Enable/disable "Posts" in Bricks → Settings → Post types, then call create-page with `postType: post`. |
| 4 | `plugin/src/Abilities/General/CreatePage.php` | Elementor's enabled post types live in the `elementor_cpt_support` option. | Same, in Elementor → Settings. |
| 5 | `plugin/src/Abilities/General/CreatePage.php` | Setting `_bricks_editor_mode = bricks` is enough for Bricks to open a new page in the builder. | create-page with Bricks, then open `builderEditLink`. |
| 6 | `plugin/src/Abilities/General/CreatePage.php` | `Documents::get()->save(['elements' => []])` + `set_is_built_with_elementor(true)` sets up an empty Elementor page. | create-page with Elementor, then open `builderEditLink`. |

Checked against source (not live): MCP Adapter 0.7.0 `create_server()` signature,
tool error handling and schema normalisation; WordPress 7.1 `WP_Ability`
validation and permission flow; Elementor 4.3.4 `Document::save()`.
