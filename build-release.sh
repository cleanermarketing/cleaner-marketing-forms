#!/bin/bash

# Cleaner Marketing Forms - Build Release Script
# This script creates a clean release package of the plugin

echo "========================================"
echo "Cleaner Marketing Forms - Build Release"
echo "========================================"
echo ""

# Get the plugin version from the main file
VERSION=$(grep "Version:" cleaner-marketing-forms.php | sed 's/.*Version: //')
echo "Building version: $VERSION"
echo ""

# Create a temporary directory for the build
BUILD_DIR="build"
PLUGIN_SLUG="cleaner-marketing-forms"
RELEASE_DIR="$BUILD_DIR/$PLUGIN_SLUG"

# Clean up any existing build directory
rm -rf "$BUILD_DIR"
mkdir -p "$RELEASE_DIR"

echo "Copying plugin files..."

# Copy all plugin files except those we want to exclude
rsync -av --progress . "$RELEASE_DIR" \
    --exclude="$BUILD_DIR" \
    --exclude=".git" \
    --exclude=".gitignore" \
    --exclude=".github" \
    --exclude="node_modules" \
    --exclude="tests" \
    --exclude="*.sh" \
    --exclude="*.md" \
    --exclude="composer.lock" \
    --exclude="package.json" \
    --exclude="package-lock.json" \
    --exclude=".env*" \
    --exclude="*.log" \
    --exclude=".DS_Store" \
    --exclude="Thumbs.db" \
    --exclude="*.zip" \
    --exclude="*.tar.gz" \
    --exclude="update-config.php" \
    --exclude="local-config.php" \
    --exclude="phpunit.xml" \
    --exclude=".phpunit*" \
    --exclude="*.sublime-*" \
    --exclude=".idea" \
    --exclude=".vscode" \
    --exclude="*.swp" \
    --exclude="*.swo" \
    --exclude="*~"

# Install production dependencies if composer.json exists
if [ -f "composer.json" ]; then
    echo ""
    echo "Installing production dependencies..."
    cd "$RELEASE_DIR"
    
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader --no-interaction
        rm -f composer.json composer.lock
    else
        echo "Warning: Composer not found. Skipping dependency installation."
        echo "Make sure to run 'composer install --no-dev' before creating the release."
    fi
    
    cd - > /dev/null
fi

# Create a minimal README.txt for WordPress.org (if it doesn't exist)
if [ ! -f "$RELEASE_DIR/readme.txt" ]; then
    cat > "$RELEASE_DIR/readme.txt" << 'EOF'
=== Cleaner Marketing Forms ===
Contributors: cleanermarketing
Tags: forms, dry cleaning, laundry, pos integration, marketing
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress plugin for dry cleaning and laundry service businesses.

== Description ==

Cleaner Marketing Forms provides customer signup forms, contact forms, and opt-in forms with multiple POS system integrations for dry cleaning and laundry businesses.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cleaner-marketing-forms` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Cleaner Marketing Forms screen to configure the plugin

== Changelog ==

= 1.0.0 =
* Initial release
EOF
fi

# Create the release zip file
echo ""
echo "Creating release package..."
cd "$BUILD_DIR"
zip -r "../$PLUGIN_SLUG-$VERSION.zip" "$PLUGIN_SLUG" -q

cd ..

# Clean up build directory
rm -rf "$BUILD_DIR"

echo ""
echo "✅ Release package created: $PLUGIN_SLUG-$VERSION.zip"
echo ""
echo "File size: $(du -h "$PLUGIN_SLUG-$VERSION.zip" | cut -f1)"
echo ""
echo "Next steps:"
echo "1. Test the plugin package on a clean WordPress install"
echo "2. Create a new release on GitHub"
echo "3. Upload the .zip file as a release asset"
echo ""