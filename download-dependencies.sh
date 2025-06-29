#!/bin/bash

# Manual dependency download script for Cleaner Marketing Forms
# Use this if composer is not available

echo "========================================"
echo "Manual Dependency Download"
echo "========================================"
echo ""

# Create vendor directory structure
mkdir -p vendor/yahnis-elsts/plugin-update-checker

# Download Plugin Update Checker
echo "Downloading Plugin Update Checker..."
cd vendor/yahnis-elsts/plugin-update-checker

# Download the latest release
curl -L https://github.com/YahnisElsts/plugin-update-checker/archive/refs/tags/v5.3.zip -o puc.zip

# Extract files
unzip -q puc.zip
mv plugin-update-checker-5.3/* .
rm -rf plugin-update-checker-5.3
rm puc.zip

# Go back to plugin root
cd ../../..

# Create autoload file
cat > vendor/autoload.php << 'EOF'
<?php
// Minimal autoload for Plugin Update Checker
require_once __DIR__ . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
EOF

echo ""
echo "✅ Dependencies downloaded successfully!"
echo ""
echo "The Plugin Update Checker has been installed manually."
echo "You can now build your release with ./build-release.sh"