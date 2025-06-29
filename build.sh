#!/bin/bash

# Build script for Cleaner Marketing Forms plugin
# This script creates a distribution-ready zip file

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get plugin version from main file
VERSION=$(grep "Version:" cleaner-marketing-forms.php | awk '{print $3}')
PLUGIN_SLUG="cleaner-marketing-forms"

echo -e "${GREEN}Building Cleaner Marketing Forms Plugin v${VERSION}${NC}"

# Clean up any previous builds
echo "Cleaning up previous builds..."
rm -rf dist/
rm -f ${PLUGIN_SLUG}-*.zip

# Create dist directory
echo "Creating distribution directory..."
mkdir -p dist/${PLUGIN_SLUG}

# Install production dependencies
if [ -f "composer.json" ]; then
    echo "Installing production dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Copy files to dist directory
echo "Copying plugin files..."
rsync -av --exclude-from='.distignore' . dist/${PLUGIN_SLUG}/

# Create zip file
echo "Creating zip file..."
cd dist
zip -r ../${PLUGIN_SLUG}-${VERSION}.zip ${PLUGIN_SLUG}/
cd ..

# Clean up dist directory
rm -rf dist/

# Final message
echo -e "${GREEN}✓ Build complete!${NC}"
echo -e "Plugin package: ${YELLOW}${PLUGIN_SLUG}-${VERSION}.zip${NC}"
echo -e "File size: $(du -h ${PLUGIN_SLUG}-${VERSION}.zip | cut -f1)"

# Verify the package
echo -e "\n${GREEN}Package contents:${NC}"
unzip -l ${PLUGIN_SLUG}-${VERSION}.zip | head -20
echo "..."
echo -e "\nTotal files: $(unzip -l ${PLUGIN_SLUG}-${VERSION}.zip | tail -1 | awk '{print $2}')"

# Optional: Calculate checksums
echo -e "\n${GREEN}Checksums:${NC}"
if command -v md5sum &> /dev/null; then
    echo "MD5: $(md5sum ${PLUGIN_SLUG}-${VERSION}.zip | awk '{print $1}')"
fi
if command -v shasum &> /dev/null; then
    echo "SHA256: $(shasum -a 256 ${PLUGIN_SLUG}-${VERSION}.zip | awk '{print $1}')"
fi