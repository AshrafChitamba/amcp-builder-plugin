#!/usr/bin/env bash
# Builds an installable plugin ZIP in dist/: admin UI assets, production
# Composer autoloader, no tests or dev tooling.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(sed -n "s/^ \* Version: *//p" "$ROOT/plugin/ac-mcp-for-bricks-elementor.php" | tr -d '[:space:]')"
STAGE="$(mktemp -d)"
TARGET="$STAGE/ac-mcp-for-bricks-elementor"
trap 'rm -rf "$STAGE"' EXIT

echo "Building AC MCP for Bricks and Elementor $VERSION"

(cd "$ROOT/admin-ui" && npm ci --no-audit --no-fund && npm run build)

mkdir -p "$TARGET"
cp -R "$ROOT/plugin/src" "$ROOT/plugin/build" "$ROOT/plugin/languages" "$ROOT/plugin/guides" "$TARGET/"
cp "$ROOT/plugin/ac-mcp-for-bricks-elementor.php" "$ROOT/plugin/uninstall.php" "$ROOT/plugin/readme.txt" \
	"$ROOT/plugin/LICENSE" "$ROOT/plugin/composer.json" "$ROOT/plugin/composer.lock" "$TARGET/"

(cd "$TARGET" && composer install --no-dev --optimize-autoloader --no-interaction --quiet && rm composer.lock)
# WordPress.org expects composer.json next to vendor/, and no hidden files.
find "$TARGET" -name '.*' -not -name '.' -prune -exec rm -rf {} +

mkdir -p "$ROOT/dist"
rm -f "$ROOT/dist/ac-mcp-for-bricks-elementor-$VERSION.zip"
(cd "$STAGE" && zip -rq "$ROOT/dist/ac-mcp-for-bricks-elementor-$VERSION.zip" ac-mcp-for-bricks-elementor)

echo "Created dist/ac-mcp-for-bricks-elementor-$VERSION.zip"
