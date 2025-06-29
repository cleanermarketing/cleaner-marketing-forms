# Automatic Updates Setup Guide for Cleaner Marketing Forms

This guide will help you set up automatic updates for the Cleaner Marketing Forms plugin using GitHub releases.

## Prerequisites

- A GitHub repository for your plugin
- Composer installed on your development machine
- Git installed and configured

## Setup Steps

### 1. Install Plugin Dependencies

The plugin uses the Plugin Update Checker library to enable GitHub-based updates. Install it using Composer:

```bash
cd /path/to/cleaner-marketing-forms/
composer install
```

This will create a `vendor` directory with the required dependencies.

### 2. Configure GitHub Repository URL

Update the following files with your actual GitHub repository information:

#### In `cleaner-marketing-forms.php` (line 17):
```php
 * Update URI: https://github.com/YOUR-GITHUB-USERNAME/YOUR-REPOSITORY-NAME/
```

#### In `includes/class-updater.php` (line 48):
```php
: 'https://github.com/YOUR-GITHUB-USERNAME/YOUR-REPOSITORY-NAME/';
```

### 3. (Optional) Add Configuration Constants

For more control, add these constants to your `wp-config.php`:

```php
// Required: Set your GitHub repository URL
define('CMF_UPDATE_REPO_URL', 'https://github.com/YOUR-GITHUB-USERNAME/YOUR-REPOSITORY-NAME/');

// Optional: Set the branch to check for updates (default: 'main')
define('CMF_UPDATE_BRANCH', 'main');

// Optional: For private repositories only
// define('CMF_GITHUB_TOKEN', 'your-github-personal-access-token');
```

### 4. Prepare Your GitHub Repository

1. **Create a GitHub repository** if you haven't already
2. **Push your plugin code** to the repository
3. **Create a `.gitignore`** file to exclude unnecessary files:

```gitignore
# WordPress
*.log
wp-config.php
wp-content/advanced-cache.php
wp-content/backup-db/
wp-content/backups/
wp-content/blogs.dir/
wp-content/cache/
wp-content/upgrade/
wp-content/uploads/
wp-content/wp-cache-config.php
wp-content/plugins/hello.php

# Development
.DS_Store
Thumbs.db
node_modules/
.sass-cache/
.env
.env.*
!.env.example

# IDE
.idea/
.vscode/
*.sublime-project
*.sublime-workspace

# Plugin specific
tests/
phpunit.xml
.phpunit.result.cache
```

4. **Create a CHANGELOG.md** in your repository root:

```markdown
# Changelog

All notable changes to Cleaner Marketing Forms will be documented in this file.

## [1.0.1] - 2024-01-XX
### Added
- Automatic update functionality from GitHub

### Fixed
- Email notifications now properly filter empty fields

### Changed
- Migrated from DCF to CMF shortcodes

## [1.0.0] - 2024-01-01
### Added
- Initial release
```

## Creating Updates

### 1. Update Version Number

When you're ready to release an update, increment the version number in:

- `cleaner-marketing-forms.php` (line 6 and line 29)
- `readme.txt` (if you have one)
- Any other files that reference the version

### 2. Commit and Push Changes

```bash
git add .
git commit -m "Prepare version 1.0.1"
git push origin main
```

### 3. Create a GitHub Release

1. Go to your repository on GitHub
2. Click on "Releases" → "Create a new release"
3. Click "Choose a tag" and create a new tag (e.g., `v1.0.1`)
4. Set the release title (e.g., "Version 1.0.1")
5. Add release notes (you can copy from CHANGELOG.md)
6. Click "Publish release"

### 4. WordPress Will Detect the Update

- WordPress checks for updates every 12 hours by default
- Users can manually check by going to Dashboard → Updates
- The plugin settings page has a "Check Now" button under the Updates tab

## Important Notes

### Version Numbering
- Always use semantic versioning (MAJOR.MINOR.PATCH)
- The GitHub tag should match the version in your plugin header
- Tags can be with or without 'v' prefix (v1.0.1 or 1.0.1)

### Testing Updates
1. Install the plugin on a test site
2. Create a release with a higher version number
3. Check WordPress admin → Updates to see if the update appears
4. Test the update process before announcing to users

### Private Repositories
If using a private repository:
1. Generate a GitHub Personal Access Token
2. Add to wp-config.php: `define('CMF_GITHUB_TOKEN', 'your-token');`
3. Use minimal permissions (repo:read)

### Excluding Files from Distribution
When creating the release package, exclude development files:

```bash
# Create a clean distribution package
composer install --no-dev --optimize-autoloader
zip -r cleaner-marketing-forms.zip . \
    -x "*.git*" \
    -x "node_modules/*" \
    -x "tests/*" \
    -x "*.md" \
    -x "composer.json" \
    -x "composer.lock" \
    -x "package*.json" \
    -x ".DS_Store"
```

## Troubleshooting

### Updates Not Showing
1. Check the version number is higher than the installed version
2. Verify the GitHub repository URL is correct
3. Clear WordPress transients: `wp transient delete --all`
4. Check for PHP errors in debug.log
5. Visit Settings → Cleaner Marketing Forms → Updates tab for status

### Manual Update Check
Users can force an update check:
1. Go to Settings → Cleaner Marketing Forms → Updates
2. Click "Check Now" button
3. Check WordPress Updates page

### Debug Mode
To enable debug information:
1. Add to wp-config.php: `define('WP_DEBUG', true);`
2. Visit any admin page with `?dcf_debug_updates=1` parameter
3. Check the Updates tab in plugin settings

## Best Practices

1. **Test First**: Always test updates on a staging site
2. **Backup**: Encourage users to backup before updating
3. **Changelog**: Keep CHANGELOG.md updated with clear descriptions
4. **Versioning**: Follow semantic versioning strictly
5. **Communication**: Announce major updates to users
6. **Rollback Plan**: Keep previous versions available as releases

## Support

For issues with automatic updates:
1. Check the Integration Logs in plugin settings
2. Enable WordPress debug mode
3. Check browser console for JavaScript errors
4. Review server error logs

## Additional Resources

- [Plugin Update Checker Documentation](https://github.com/YahnisElsts/plugin-update-checker)
- [GitHub Releases Documentation](https://docs.github.com/en/repositories/releasing-projects-on-github)
- [Semantic Versioning](https://semver.org/)