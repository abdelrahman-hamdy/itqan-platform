#!/bin/bash

echo "Testing Laravel Boost MCP Server..."
echo "====================================="

cd /Users/abdelrahmanhamdy/web/itqan-platform

echo "1. Checking if artisan file exists and is executable:"
ls -la artisan

echo -e "\n2. Testing boost:mcp command:"
php artisan boost:mcp --help

echo -e "\n3. Testing MCP server response:"
echo '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{"roots":{"listChanged":true},"sampling":{}}},"id":1}' | timeout 5s php artisan boost:mcp 2>/dev/null | head -n 1

echo -e "\n4. Configuration files created:"
echo "- .qoder/mcp.json"
echo "- mcp.json (root)"
echo "- .qoder.json"

echo -e "\nMCP Server setup complete!"
echo "Please restart Qoder IDE to reload the MCP configuration."