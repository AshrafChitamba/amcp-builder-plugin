# Graph Report - mcp-builder-plugin  (2026-10-12)

## Corpus Check
- Corpus is ~34,678 words - fits in a single context window. You may not need a graph.

## Summary
- 808 nodes · 1524 edges · 47 communities (15 shown, 32 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 53 edges (avg confidence: 0.84)
- Token cost: 74,813 input · 0 output

## Community Hubs (Navigation)
- Admin UI App
- Tool Base and Create Page
- Project Docs and Plan
- Admin UI Packages
- Governance Rules Engine
- Bricks Read Tools
- Global Class Upsert
- Site Info and Requirements
- Composer Config
- Test Element Catalog
- Add and Move Elements
- Ability Contract
- Bricks Element Tree
- TypeScript Config
- Tool Registry and Server
- Settings Store
- Admin REST Routes
- Bricks Write Flow
- Environment Detection
- Install and Database
- Apply Global Class
- Snapshots
- Untrusted Content Handling
- PHPUnit Base
- Restore Snapshot Tool
- Global Class Listing
- Page and Media Listing
- Audit Log
- Remove Element Tool
- Update Element Tool
- List Snapshots Tool
- Plugin Bootstrap
- Duplicate Element Tool
- Get Page Tool
- List Media Tool
- Tree Errors and Validation
- Ability Tests
- Tree Outline Helpers
- Requirements Tests
- Registry Closures
- Client Connectivity Notes
- SCSS Typings
- ZIP Build Script

## God Nodes (most connected - your core abstractions)
1. `Ability` - 45 edges
2. `Tree` - 42 edges
3. `Settings` - 33 edges
4. `Snapshots` - 30 edges
5. `Governance` - 29 edges
6. `Errors` - 29 edges
7. `Environment` - 28 edges
8. `Registry` - 26 edges
9. `Validator` - 26 edges
10. `Requirements` - 24 edges

## Surprising Connections (you probably didn't know these)
- `WordPress.org plugin readme` --semantically_similar_to--> `MCP Builder plugin`  [INFERRED] [semantically similar]
  plugin/readme.txt → README.md
- `Release 0.2.0` --references--> `Post-lock protection`  [EXTRACTED]
  CHANGELOG.md → docs/PLAN.md
- `Release 0.2.0` --references--> `Per-user rate limit`  [EXTRACTED]
  CHANGELOG.md → docs/PLAN.md
- `Release 0.2.0` --references--> `Read-only mode`  [EXTRACTED]
  CHANGELOG.md → docs/PLAN.md
- `WordPress.org plugin readme` --references--> `Release 0.2.0`  [INFERRED]
  plugin/readme.txt → CHANGELOG.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Safety layer governance controls** — docs_plan_kill_switch, docs_plan_read_only_mode, docs_plan_per_tool_toggles, docs_plan_custom_code_group, docs_plan_rate_limit, docs_plan_post_lock, docs_plan_audit_log, docs_plan_snapshots [EXTRACTED 1.00]
- **Bricks write pipeline (spec -> flat array -> guarded save)** — docs_plan_bricks_spec, docs_plan_bricks_data_model, docs_plan_bricks_save_flow, docs_plan_bricks_toolset [INFERRED 0.85]
- **MCP connection stack** — readme_abilities_api, readme_mcp_adapter, readme_mcp_endpoint, readme_application_passwords [EXTRACTED 1.00]

## Communities (47 total, 32 thin omitted)

### Community 0 - "Admin UI App"
Cohesion: 0.06
Nodes (69): createAppPassword(), errorMessage(), getAbilities(), getAudit(), getSettings(), getSnapshots(), getStatus(), JsonRpcResponse (+61 more)

### Community 1 - "Tool Base and Create Page"
Cohesion: 0.07
Nodes (8): CreatePage, Store, Errors, PageBuilder, Posts, WP_Error, WP_Post_Type, AbilitiesTest

### Community 2 - "Project Docs and Plan"
Cohesion: 0.07
Nodes (43): Keep a Changelog / Semantic Versioning, list-media tool, MCP-Protocol-Version header fix, Release 0.1.0, Release 0.2.0, Admin UI (Connection/Tools/Safety/Status/About), Audit log (mcpb_audit table), Bricks code signature (wp_hash) (+35 more)

### Community 3 - "Admin UI Packages"
Cohesion: 0.05
Nodes (44): author, dependencies, @wordpress/api-fetch, @wordpress/components, @wordpress/compose, @wordpress/element, @wordpress/i18n, @wordpress/url (+36 more)

### Community 4 - "Governance Rules Engine"
Cohesion: 0.09
Nodes (6): {closure#1}(), Governance, RateLimiter, Targets, WP_Ability, SafetyTest

### Community 5 - "Bricks Read Tools"
Cohesion: 0.07
Nodes (4): BricksAbility, GetElementSchema, ListElements, BricksCatalog

### Community 6 - "Global Class Upsert"
Cohesion: 0.10
Nodes (3): UpsertGlobalClass, Ids, TreeTest

### Community 7 - "Site Info and Requirements"
Cohesion: 0.08
Nodes (4): SiteInfo, AdminPage, Notices, Requirements

### Community 8 - "Composer Config"
Cohesion: 0.07
Nodes (29): dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+21 more)

### Community 9 - "Test Element Catalog"
Cohesion: 0.12
Nodes (10): ArrayCatalog, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 10 - "Add and Move Elements"
Cohesion: 0.09
Nodes (4): AddElements, MoveElement, Catalog, Validator

### Community 13 - "TypeScript Config"
Cohesion: 0.12
Nodes (16): compilerOptions, esModuleInterop, isolatedModules, jsx, lib, module, moduleResolution, noFallthroughCasesInSwitch (+8 more)

### Community 16 - "Admin REST Routes"
Cohesion: 0.23
Nodes (8): abilities(), audit(), auth_header_test(), {closure#2}(), list_snapshots(), restore_snapshot(), status(), update_settings()

### Community 27 - "Audit Log"
Cohesion: 0.22
Nodes (3): __construct(), routes(), AuditLog

### Community 39 - "Registry Closures"
Cohesion: 0.50
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

## Knowledge Gaps
- **96 isolated node(s):** `name`, `version`, `private`, `description`, `author` (+91 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 325 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **32 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Ability` connect `Ability Contract` to `Tool Base and Create Page`, `List Media Tool`, `Bricks Read Tools`, `Site Info and Requirements`, `Registry Closures`, `Tool Registry and Server`, `Settings Store`, `Admin REST Routes`, `Environment Detection`, `Snapshots`, `Restore Snapshot Tool`, `Page and Media Listing`, `List Snapshots Tool`?**
  _High betweenness centrality (0.150) - this node is a cross-community bridge._
- **What connects `name`, `version`, `private` to the rest of the system?**
  _96 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Admin UI App` be split into smaller, more focused modules?**
  _Cohesion score 0.06442127773322641 - nodes in this community are weakly interconnected._
- **Why does `BricksAbility` connect `Bricks Read Tools` to `Tool Base and Create Page`, `Get Page Tool`, `Global Class Upsert`, `Ability Contract`, `Bricks Write Flow`, `Global Class Listing`?**
  _High betweenness centrality (0.085) - this node is a cross-community bridge._
- **Should `Tool Base and Create Page` be split into smaller, more focused modules?**
  _Cohesion score 0.07456140350877193 - nodes in this community are weakly interconnected._
- **Why does `TestCase` connect `PHPUnit Base` to `Tool Base and Create Page`, `Ability Tests`, `Governance Rules Engine`, `Requirements Tests`, `Global Class Upsert`, `Test Element Catalog`, `Settings Store`, `Untrusted Content Handling`?**
  _High betweenness centrality (0.069) - this node is a cross-community bridge._
- **Should `Project Docs and Plan` be split into smaller, more focused modules?**
  _Cohesion score 0.06887755102040816 - nodes in this community are weakly interconnected._