# References

The documentation behind AMCP Builder: the platforms it plugs into, the standards it implements, the tools it is built with, and the design sources behind its interface and its design guide.

## WordPress

| Topic | Used for | Documentation |
|---|---|---|
| Abilities API | Registering every tool, permission checks, the hooks that apply safety rules to all abilities | [WordPress/abilities-api](https://github.com/WordPress/abilities-api) |
| MCP Adapter | Serving the abilities as an MCP server at `/wp-json/amcp-builder/mcp` | [WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter), [plugin page](https://wordpress.org/plugins/mcp-adapter/) |
| REST API | The admin screens' settings, logs and snapshot endpoints | [REST API Handbook](https://developer.wordpress.org/rest-api/) |
| Application Passwords | Sign-in for coding tools without OAuth | [Integration guide](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/) |
| Plugin development | Activation, options, custom tables, uninstall | [Plugin Handbook](https://developer.wordpress.org/plugins/), [`dbDelta()`](https://developer.wordpress.org/reference/functions/dbdelta/) |
| Admin menu | The top-level AMCP Builder menu and its screens | [`add_menu_page()`](https://developer.wordpress.org/reference/functions/add_menu_page/), [`add_submenu_page()`](https://developer.wordpress.org/reference/functions/add_submenu_page/) |
| Internationalization | Translatable strings, the `amcp-builder` text domain, the .pot file | [Internationalization](https://developer.wordpress.org/apis/internationalization/), [`wp i18n make-pot`](https://developer.wordpress.org/cli/commands/i18n/make-pot/) |
| readme.txt | The plugin directory listing | [How your readme.txt works](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) |
| Coding standards | PHP style and security rules | [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/), [WPCS for PHP_CodeSniffer](https://github.com/WordPress/WordPress-Coding-Standards) |

## Page builders

| Builder | Used for | Documentation |
|---|---|---|
| Bricks | Elements, settings, global classes, templates and conditions, theme styles | [Bricks Academy](https://academy.bricksbuilder.io/) |
| Elementor | Containers, widgets, documents, kit globals, Theme Builder conditions | [Elementor Developers](https://developers.elementor.com/docs/) |
| Automatic.css | Recognising its variables and button classes on Bricks sites | [automaticcss.com](https://automaticcss.com/) |

## Model Context Protocol and sign-in

| Standard | Used for | Documentation |
|---|---|---|
| Model Context Protocol | Tools, resources, prompts and the HTTP transport | [Specification](https://modelcontextprotocol.io/specification) |
| MCP authorization | How AI assistants discover and use OAuth | [Authorization](https://modelcontextprotocol.io/specification/2025-06-18/basic/authorization) |
| OAuth 2.1 | The sign-in flow AI assistants use | [draft-ietf-oauth-v2-1](https://datatracker.ietf.org/doc/draft-ietf-oauth-v2-1/) |
| PKCE (RFC 7636) | Protecting the sign-in code exchange | [RFC 7636](https://www.rfc-editor.org/rfc/rfc7636) |
| Authorization server metadata (RFC 8414) | `/.well-known/oauth-authorization-server` | [RFC 8414](https://www.rfc-editor.org/rfc/rfc8414) |
| Protected resource metadata (RFC 9728) | `/.well-known/oauth-protected-resource` | [RFC 9728](https://www.rfc-editor.org/rfc/rfc9728) |
| Dynamic client registration (RFC 7591) | AI assistants registering themselves | [RFC 7591](https://www.rfc-editor.org/rfc/rfc7591) |
| Resource indicators (RFC 8707) | Binding tokens to this site's MCP endpoint | [RFC 8707](https://www.rfc-editor.org/rfc/rfc8707) |
| Client ID metadata documents | AI assistants identified by a URL instead of registration | [draft-parecki-oauth-client-id-metadata-document](https://datatracker.ietf.org/doc/draft-parecki-oauth-client-id-metadata-document/) |

## Admin interface

| Tool | Used for | Documentation |
|---|---|---|
| @wordpress/scripts | Building, linting and formatting the admin app | [Package reference](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) |
| @wordpress/components | Buttons, toggles, fields and notices | [Component reference](https://developer.wordpress.org/block-editor/reference-guides/components/), [Storybook](https://wordpress.github.io/gutenberg/) |
| @wordpress/icons | Icons in the header status | [Package reference](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-icons/) |
| @wordpress/api-fetch | Calls to the plugin's REST endpoints | [Package reference](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/) |
| React | The admin app | [react.dev](https://react.dev/) |
| TypeScript | Types for the admin app | [Handbook](https://www.typescriptlang.org/docs/) |
| Sass | The admin stylesheet | [Documentation](https://sass-lang.com/documentation/) |
| Vite | The local preview (`npm run preview`) | [Guide](https://vite.dev/guide/) |
| Vitest | Admin app unit tests | [Guide](https://vitest.dev/guide/) |

## PHP tooling

| Tool | Used for | Documentation |
|---|---|---|
| Composer | Autoloading and dependencies | [Documentation](https://getcomposer.org/doc/) |
| PHPUnit | Unit tests | [Documentation](https://docs.phpunit.de/) |
| Brain Monkey | Testing without a WordPress install | [Documentation](https://giuseppe-mazzapica.gitbook.io/brain-monkey) |
| PHP_CodeSniffer | Running the coding standards | [PHPCSStandards/PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) |

## Design

| Source | Used for | Link |
|---|---|---|
| Apple Human Interface Guidelines | Principles behind the admin design and the design guide: hierarchy, restraint, feedback, consistency | [HIG](https://developer.apple.com/design/human-interface-guidelines) |
| WWDC 2018: Designing Fluid Interfaces | Immediate feedback and motion rules | [Video](https://developer.apple.com/videos/play/wwdc2018/803/) |
| WWDC 2020: The details of UI typography | Size-specific letter spacing and line height in the design guide | [Video](https://developer.apple.com/videos/play/wwdc2020/10175/) |
| WCAG 2.2 | Contrast and accessibility rules in the admin and the design guide | [W3C Recommendation](https://www.w3.org/TR/WCAG22/) |
| ARIA Authoring Practices: Tabs | Keyboard navigation of the admin screens | [Tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/) |
| impeccable and design-taste-frontend | Claude Code design skills (installed locally) whose rules shaped the design guide: no eyebrow labels, layout variety, honest copy | Not published online |

## Video embeds (Tutorial screen)

| Provider | Documentation |
|---|---|
| YouTube | [Embedded player parameters](https://developers.google.com/youtube/player_parameters) |
| Vimeo | [Embedding videos](https://developer.vimeo.com/player/sdk/embed) |
| Google Drive | The file must be shared as "Anyone with the link"; the player uses the file's `/preview` address |
