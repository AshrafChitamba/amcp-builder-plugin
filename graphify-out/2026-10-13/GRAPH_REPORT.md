# Graph Report - mcp-builder-plugin  (2026-10-13)

## Corpus Check
- 160 files · ~75,746 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 8 file(s) not represented in the graph (top: (none) 5, .dist 2, .scss 1)

## Summary
- 1525 nodes · 2743 edges · 100 communities (44 shown, 56 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 62 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1568631e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- api.ts
- CreatePage
- Release 0.2.0
- package.json
- Governance
- BricksAbility
- Ids
- Environment
- composer.json
- ValidatorTest.php
- Validator
- Ability
- Tree
- compilerOptions
- Registry
- Settings
- AdminController.php
- BricksWriteAbility
- .validator
- Schema
- GlobalClasses
- Snapshots
- Errors
- TestCase
- RestoreSnapshot
- WP_Error
- Posts
- AuditLog
- DesignTokens
- UpdateElement
- ListSnapshots
- Server
- DuplicateElement
- GetPage
- Untrusted
- TreeError
- Tree
- Ids
- RequirementsTest
- OAuth/Server.php
- MCP Adapter 0.7.0 bugs (#298, #308, #368)
- styles.d.ts
- build-zip.sh
- Clients
- ElementorAbility
- ConnectionTab.tsx
- App.tsx
- UpdatePageSettings
- GuideResource
- Validator
- ElementorCatalog
- Templates
- Guides
- UpsertGlobalClass
- ElementorWriteAbility
- snippets.ts
- SafetyTab.tsx
- Deployment and connecting AI apps
- UpsertCodeSnippet
- Design guide: building pages that do not look generated
- UpdateGlobals
- UpdatePage
- SafetyTest
- CreateTemplate
- GetElementSchema
- UpdateThemeStyle
- CreateTemplate
- GetWidgetSchema
- UploadMedia
- DesignPagePrompt
- QA checklist
- ListElements
- SetTemplateConditions
- ListWidgets
- RemoveElement
- UpdateElement
- UpdatePageSettings
- GetDesignGuide
- Security model
- Tool reference
- Bricks guide
- Elementor guide
- AddElements
- AddElements
- authorize
- .wp-env.json
- dependencies
- devDependencies
- scripts
- Troubleshooting
- SafetyTest.php
- ElementorGuideResource
- safety.md
- deploy-local.sh
- mcp-call.sh

## God Nodes (most connected - your core abstractions)
1. `Errors` - 63 edges
2. `Ability` - 62 edges
3. `Tree` - 55 edges
4. `TreeError` - 48 edges
5. `Tree` - 43 edges
6. `Settings` - 40 edges
7. `Validator` - 35 edges
8. `Governance` - 35 edges
9. `Ids` - 31 edges
10. `Schema` - 31 edges

## Surprising Connections (you probably didn't know these)
- `General` --references--> `status()`  [INFERRED]
  docs/tool-reference.md → plugin/src/Rest/AdminController.php
- `WordPress.org plugin readme` --semantically_similar_to--> `MCP Builder plugin`  [INFERRED] [semantically similar]
  plugin/readme.txt → README.md
- `Bricks 2.4 AI Abilities (official)` --conceptually_related_to--> `Bricks builder`  [INFERRED]
  docs/PLAN.md → README.md
- `Core ability hooks (wp_ability_permission_result, wp_pre_execute_ability, wp_after_execute_ability)` --conceptually_related_to--> `WordPress Abilities API`  [INFERRED]
  docs/PLAN.md → README.md
- `Elementor 4.3 MCP (official, Atomic only)` --conceptually_related_to--> `Elementor builder`  [INFERRED]
  docs/PLAN.md → README.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **MCP connection stack** — readme_abilities_api, readme_mcp_adapter, readme_mcp_endpoint, readme_application_passwords [EXTRACTED 1.00]
- **Safety layer governance controls** — docs_plan_kill_switch, docs_plan_read_only_mode, docs_plan_per_tool_toggles, docs_plan_custom_code_group, docs_plan_rate_limit, docs_plan_post_lock, docs_plan_audit_log, docs_plan_snapshots [EXTRACTED 1.00]
- **Bricks write pipeline (spec -> flat array -> guarded save)** — docs_plan_bricks_spec, docs_plan_bricks_data_model, docs_plan_bricks_save_flow, docs_plan_bricks_toolset [INFERRED 0.85]

## Communities (100 total, 56 thin omitted)

### Community 0 - "api.ts"
Cohesion: 0.11
Nodes (32): getSettings(), getStatus(), JsonRpcResponse, MCP_HEADERS, mcpCall(), path(), saveSettings(), sessionHeaders() (+24 more)

### Community 1 - "CreatePage"
Cohesion: 0.23
Nodes (3): CreatePage, WP_Post_Type, AbilitiesTest

### Community 2 - "Release 0.2.0"
Cohesion: 0.07
Nodes (43): Keep a Changelog / Semantic Versioning, list-media tool, MCP-Protocol-Version header fix, Release 0.1.0, Release 0.2.0, Admin UI (Connection/Tools/Safety/Status/About), Audit log (mcpb_audit table), Bricks code signature (wp_hash) (+35 more)

### Community 3 - "package.json"
Cohesion: 0.10
Nodes (18): author, description, homepage, license, name, private, version, Props (+10 more)

### Community 4 - "Governance"
Cohesion: 0.12
Nodes (4): {closure#1}(), Governance, Targets, WP_Ability

### Community 7 - "Environment"
Cohesion: 0.06
Nodes (5): SiteInfo, AdminPage, Notices, Environment, Requirements

### Community 8 - "composer.json"
Cohesion: 0.07
Nodes (29): dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+21 more)

### Community 9 - "ValidatorTest.php"
Cohesion: 0.09
Nodes (12): Catalog, ArrayCatalog, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+4 more)

### Community 10 - "Validator"
Cohesion: 0.10
Nodes (3): MoveElement, RemoveElement, Validator

### Community 11 - "Ability"
Cohesion: 0.12
Nodes (6): Ability, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#3}()

### Community 12 - "Tree"
Cohesion: 0.14
Nodes (3): {closure#3}(), {closure#4}(), Tree

### Community 13 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, esModuleInterop, isolatedModules, jsx, lib, module, moduleResolution, noFallthroughCasesInSwitch (+8 more)

### Community 16 - "AdminController.php"
Cohesion: 0.21
Nodes (11): revoke(), abilities(), audit(), auth_header_test(), create_token(), discovery_test(), list_snapshots(), restore_snapshot() (+3 more)

### Community 18 - ".validator"
Cohesion: 0.08
Nodes (18): Catalog, ElementorArrayCatalog, {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}() (+10 more)

### Community 19 - "Schema"
Cohesion: 0.09
Nodes (5): Installer, authorization_server_metadata(), protected_resource_metadata(), Tokens, Schema

### Community 20 - "GlobalClasses"
Cohesion: 0.10
Nodes (3): ApplyGlobalClass, ListGlobalClasses, GlobalClasses

### Community 26 - "Posts"
Cohesion: 0.07
Nodes (5): ListTemplates, ListTemplates, ListPages, PageBuilder, Posts

### Community 27 - "AuditLog"
Cohesion: 0.20
Nodes (3): __construct(), routes(), AuditLog

### Community 28 - "DesignTokens"
Cohesion: 0.06
Nodes (4): GetDesignTokens, UpsertColors, UpsertVariables, DesignTokens

### Community 31 - "Server"
Cohesion: 0.15
Nodes (3): Server, {closure#1}(), Plugin

### Community 34 - "Untrusted"
Cohesion: 0.09
Nodes (4): GetGlobals, ListMedia, Globals, Untrusted

### Community 37 - "Ids"
Cohesion: 0.12
Nodes (3): Ids, Diff, ElementorTreeTest

### Community 39 - "OAuth/Server.php"
Cohesion: 0.11
Nodes (12): authenticate(), challenge(), {closure#1}(), {closure#2}(), enabled(), is_enabled_for_request(), json(), oauth_error() (+4 more)

### Community 47 - "Clients"
Cohesion: 0.10
Nodes (3): Clients, __construct(), OAuthTest

### Community 48 - "ElementorAbility"
Cohesion: 0.09
Nodes (3): ElementorAbility, GetPage, Store

### Community 49 - "ConnectionTab.tsx"
Cohesion: 0.18
Nodes (21): createAccessToken(), createAppPassword(), errorMessage(), getConnections(), listAppPasswords(), revokeAppPassword(), revokeConnection(), testDiscovery() (+13 more)

### Community 50 - "App.tsx"
Cohesion: 0.19
Nodes (15): getAbilities(), App(), initialTab(), TABS, Props, Section(), config, Window (+7 more)

### Community 51 - "UpdatePageSettings"
Cohesion: 0.10
Nodes (3): CodeStatus, UpdatePageSettings, CustomCode

### Community 52 - "GuideResource"
Cohesion: 0.11
Nodes (4): BricksGuideResource, DesignGuideResource, GuideResource, SafetyGuideResource

### Community 59 - "snippets.ts"
Cohesion: 0.20
Nodes (12): AuthMode, basicAuth(), buildSnippets(), ClientId, json(), PASSWORD_PLACEHOLDER, Snippet, SnippetInput (+4 more)

### Community 60 - "SafetyTab.tsx"
Cohesion: 0.33
Nodes (11): getAudit(), getSnapshots(), restoreSnapshot(), AuditLog(), confirmRestore(), formatDate(), NUMBER_FIELDS, NumberKey (+3 more)

### Community 61 - "Deployment and connecting AI apps"
Cohesion: 0.17
Nodes (11): 1. Install, 2. Choose how each app signs in, 3. Web apps, 4. Coding tools, 5. Recommended settings for production, 6. Updating and uninstalling, ChatGPT, claude.ai and Claude Desktop (+3 more)

### Community 63 - "Design guide: building pages that do not look generated"
Cohesion: 0.18
Nodes (10): 1. Read the brief first, 2. Respect what exists, 3. Hierarchy and the hero, 4. Layout, 5. Color, shape and depth, 6. Typography and copy, 7. Images and icons, 8. Interaction and accessibility (+2 more)

### Community 74 - "QA checklist"
Cohesion: 0.22
Nodes (8): Connection tab, Install, OAuth, QA checklist, Safety, Tools (Bricks site), Tools (Elementor site), Uninstall

### Community 82 - "Security model"
Cohesion: 0.25
Nodes (7): Guard rails, Privacy, Prompt injection, Reporting a vulnerability, Security model, Sign-in, Who can do what

### Community 83 - "Tool reference"
Cohesion: 0.25
Nodes (7): Bricks, Custom code, Elementor, General, Prompts, Resources, Tool reference

### Community 84 - "Bricks guide"
Cohesion: 0.25
Nodes (7): Bricks guide, Custom code, Design system, Query loops (lists of posts), Settings that are easy to get wrong, Structure, Templates

### Community 85 - "Elementor guide"
Cohesion: 0.25
Nodes (7): Custom code, Design system, Elementor guide, Lists of posts, Settings that are easy to get wrong, Structure, Templates (Elementor Pro Theme Builder)

### Community 88 - "authorize"
Cohesion: 0.29
Nodes (4): Consent, authorize(), redirect(), redirect_error()

### Community 89 - ".wp-env.json"
Cohesion: 0.25
Nodes (7): config, WP_DEBUG, WP_ENVIRONMENT_TYPE, core, phpVersion, plugins, $schema

### Community 90 - "dependencies"
Cohesion: 0.29
Nodes (7): dependencies, @wordpress/api-fetch, @wordpress/components, @wordpress/compose, @wordpress/element, @wordpress/i18n, @wordpress/url

### Community 91 - "devDependencies"
Cohesion: 0.29
Nodes (7): devDependencies, @types/react, @types/react-dom, typescript, vite, vitest, @wordpress/scripts

### Community 92 - "scripts"
Cohesion: 0.29
Nodes (7): scripts, build, format, lint, start, test, typecheck

### Community 93 - "Troubleshooting"
Cohesion: 0.29
Nodes (6): Building, Connecting, OAuth (claude.ai, ChatGPT, Codex, Claude Code), Security warnings, Troubleshooting, Undoing changes

## Knowledge Gaps
- **161 isolated node(s):** `$schema`, `core`, `phpVersion`, `plugins`, `WP_DEBUG` (+156 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 691 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **56 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Ability` connect `Ability` to `CreatePage`, `BricksAbility`, `Environment`, `Registry`, `Settings`, `AdminController.php`, `Snapshots`, `RestoreSnapshot`, `WP_Error`, `Posts`, `ListSnapshots`, `Untrusted`, `ElementorAbility`, `GuideResource`, `Guides`, `UpdatePage`, `UploadMedia`, `DesignPagePrompt`, `GetDesignGuide`?**
  _High betweenness centrality (0.192) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Errors` (e.g. with `.editable_type()` and `.to_wp_error()`) actually correct?**
  _`Errors` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `core`, `phpVersion` to the rest of the system?**
  _161 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `api.ts` be split into smaller, more focused modules?**
  _Cohesion score 0.1111111111111111 - nodes in this community are weakly interconnected._
- **Why does `BricksAbility` connect `BricksAbility` to `GetPage`, `CreateTemplate`, `GetElementSchema`, `UpdateThemeStyle`, `Ability`, `ListElements`, `SetTemplateConditions`, `BricksWriteAbility`, `UpdatePageSettings`, `GlobalClasses`, `WP_Error`, `Posts`, `DesignTokens`, `UpsertGlobalClass`?**
  _High betweenness centrality (0.081) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Tree` (e.g. with `.write()` and `.assert_not_atomic()`) actually correct?**
  _`Tree` has 2 INFERRED edges - model-reasoned connections that need verification._
- **Should `Release 0.2.0` be split into smaller, more focused modules?**
  _Cohesion score 0.06887755102040816 - nodes in this community are weakly interconnected._