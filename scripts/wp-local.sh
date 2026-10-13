#!/usr/bin/env bash
# Runs WP-CLI against a running LocalWP site (Linux), using that site's MySQL socket.
#
#   scripts/wp-local.sh internal-audit-service plugin list
#   scripts/wp-local.sh mcp-furniture-website plugin check mcp-builder
set -euo pipefail
SITE="${1:?Usage: wp-local.sh <site-folder> <wp args...>}"
shift
LOCAL="$HOME/.config/Local"
ID=$(python3 -c "
import json, os, sys
for k, v in json.load(open(os.path.join(sys.argv[1], 'sites.json'))).items():
    if os.path.basename(os.path.expanduser(v['path'])) == sys.argv[2]:
        print(k)
" "$LOCAL" "$SITE")
if [ -z "$ID" ]; then
	echo "No LocalWP site folder named $SITE." >&2
	exit 1
fi
SOCK="$LOCAL/run/$ID/mysql/mysqld.sock"
if [ ! -S "$SOCK" ]; then
	echo "Site $SITE is not running: start it in LocalWP." >&2
	exit 1
fi
exec php -d "mysqli.default_socket=$SOCK" -d "pdo_mysql.default_socket=$SOCK" "$(command -v wp)" --path="$HOME/Local Sites/$SITE/app/public" "$@"
