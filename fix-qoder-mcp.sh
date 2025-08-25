#!/bin/bash

echo "Fixing Qoder MCP Configuration for Laravel Boost..."
echo "=================================================="

QODER_CONFIG="/Users/abdelrahmanhamdy/Library/Application Support/Qoder/SharedClientCache/mcp.json"
PROJECT_PATH="/Users/abdelrahmanhamdy/web/itqan-platform"

if [ ! -f "$QODER_CONFIG" ]; then
    echo "Error: Qoder MCP config file not found at: $QODER_CONFIG"
    exit 1
fi

echo "Backing up original configuration..."
cp "$QODER_CONFIG" "$QODER_CONFIG.backup"

echo "Updating Laravel Boost MCP server configuration..."
sed -i '' 's/"artisan"/"\/Users\/abdelrahmanhamdy\/web\/itqan-platform\/artisan"/g' "$QODER_CONFIG"

echo "Configuration updated successfully!"
echo "Original backed up to: $QODER_CONFIG.backup"

echo -e "\nUpdated configuration:"
cat "$QODER_CONFIG"

echo -e "\nPlease restart Qoder IDE to reload the MCP configuration."