#!/bin/bash

# Cleaner Marketing Forms - Dependency Installation Script
# This script installs the required dependencies for automatic updates

echo "==================================="
echo "Cleaner Marketing Forms"
echo "Dependency Installation Script"
echo "==================================="
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "ERROR: Composer is not installed."
    echo ""
    echo "Please install Composer first:"
    echo "  macOS: brew install composer"
    echo "  Linux: sudo apt-get install composer"
    echo "  Windows: Download from https://getcomposer.org"
    echo ""
    exit 1
fi

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Installing dependencies in: $SCRIPT_DIR"
echo ""

# Navigate to plugin directory
cd "$SCRIPT_DIR"

# Install dependencies
echo "Running: composer install"
composer install

# Check if installation was successful
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ SUCCESS: Dependencies installed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Update the GitHub repository URL in these files:"
    echo "   - cleaner-marketing-forms.php (line 17)"
    echo "   - includes/class-updater.php (line 48)"
    echo "2. Commit your changes to GitHub"
    echo "3. Create a release on GitHub with a version tag"
    echo ""
    echo "See AUTOMATIC-UPDATES-SETUP.md for detailed instructions."
else
    echo ""
    echo "❌ ERROR: Failed to install dependencies."
    echo "Please check the error messages above."
fi