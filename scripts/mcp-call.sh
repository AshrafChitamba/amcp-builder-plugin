#!/usr/bin/env bash
# Minimal MCP client for testing an MCP Builder site from a terminal.
#
#   export MCP_URL="https://example.com/wp-json/mcp-builder/mcp"
#   export MCP_AUTH="Authorization: Bearer mcpbp_..."   # or "Authorization: Basic <base64 user:app-password>"
#   scripts/mcp-call.sh tools/list
#   scripts/mcp-call.sh tools/call '{"name":"mcp-builder-site-info","arguments":{}}'
#
# Opens a session, sends one request, prints the JSON response, closes the session.
set -euo pipefail
: "${MCP_URL:?Set MCP_URL}"
: "${MCP_AUTH:?Set MCP_AUTH}"
METHOD="${1:?Usage: mcp-call.sh <method> [params-json]}"
PARAMS="${2:-{\}}"
VERSION="2025-11-25"
H=(-H "$MCP_AUTH" -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream')

SID=$(curl -sS -D - -o /dev/null -X POST "$MCP_URL" "${H[@]}" \
	-d "{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"initialize\",\"params\":{\"protocolVersion\":\"$VERSION\",\"capabilities\":{},\"clientInfo\":{\"name\":\"mcp-call\",\"version\":\"1\"}}}" \
	| awk -F': ' 'tolower($1)=="mcp-session-id"{print $2}' | tr -d '\r')
if [ -z "$SID" ]; then
	echo "No session: check MCP_URL and MCP_AUTH (try: curl -i \"\$MCP_URL\")." >&2
	exit 1
fi
S=(-H "Mcp-Session-Id: $SID" -H "MCP-Protocol-Version: $VERSION")
curl -sS -X POST "$MCP_URL" "${H[@]}" "${S[@]}" -d '{"jsonrpc":"2.0","method":"notifications/initialized"}' >/dev/null
curl -sS -X POST "$MCP_URL" "${H[@]}" "${S[@]}" -d "{\"jsonrpc\":\"2.0\",\"id\":2,\"method\":\"$METHOD\",\"params\":$PARAMS}"
echo
curl -sS -X DELETE "$MCP_URL" -H "$MCP_AUTH" "${S[@]}" >/dev/null || true
