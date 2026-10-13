#!/usr/bin/env bash
# Builds the plugin ZIP and copies it into LocalWP sites for testing.
# Usage: scripts/deploy-local.sh "<Local Sites dir name>" [...]
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
"$ROOT/scripts/build-zip.sh" >/dev/null
ZIP="$(ls -t "$ROOT"/dist/mcp-builder-*.zip | head -1)"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
unzip -q "$ZIP" -d "$STAGE"
for SITE in "$@"; do
	TARGET="$HOME/Local Sites/${SITE:?}/app/public/wp-content/plugins/mcp-builder"
	mkdir -p "$TARGET"
	for DIR in src build vendor guides languages; do rm -rf "${TARGET:?}/${DIR:?}"; done
	cp -R "$STAGE/mcp-builder/." "$TARGET/"
	echo "Deployed $(basename "$ZIP") to $SITE"
done
