# Setting Up Automatic Updates - Complete Guide

## Current Status ✅
- ✅ GitHub repository created and code pushed
- ✅ Update URIs configured in plugin files
- ✅ Update checker class implemented
- ❌ Plugin Update Checker library not installed
- ❌ No releases created yet

## What You Need to Do

### 1. Install the Plugin Update Checker Library (REQUIRED)

The plugin uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library. You MUST install it for automatic updates to work.

#### Option A: Using Composer (Recommended)
```bash
# Navigate to plugin directory
cd "/Users/cohenwills/Local Sites/testplugin/app/public/wp-content/plugins/cleaner-marketing-forms"

# Install dependencies
composer install
```

#### Option B: Manual Installation
If you don't have Composer, download the library manually:

1. Download from: https://github.com/YahnisElsts/plugin-update-checker/releases
2. Extract to: `/vendor/yahnis-elsts/plugin-update-checker/`
3. The structure should be:
   ```
   cleaner-marketing-forms/
   └── vendor/
       └── yahnis-elsts/
           └── plugin-update-checker/
               └── plugin-update-checker.php
   ```

### 2. Create Your First Release

1. **Build the release package** (with vendor directory):
   ```bash
   ./build-release.sh
   ```

2. **Create a GitHub release**:
   - Go to: https://github.com/cleanermarketing/cleaner-marketing-forms/releases/new
   - Tag: `v1.0.0`
   - Title: `Version 1.0.0`
   - Attach the zip file created by build-release.sh

### 3. Test the Update System

1. **Install the plugin** on a test WordPress site
2. **Change the version** in your local copy to `1.0.1`
3. **Push and create a new release** with tag `v1.0.1`
4. **Check for updates** in WordPress admin

## How the Update System Works

1. **Version Check**: 
   - Plugin checks GitHub releases every 12 hours
   - Compares installed version with latest release tag

2. **Update Detection**:
   - GitHub tags should match version in plugin header
   - Use format: `v1.0.0`, `v1.0.1`, etc.

3. **Update Process**:
   - User sees update notification in WordPress admin
   - One-click update like WordPress.org plugins
   - Plugin files are replaced automatically

## Troubleshooting

### Updates Not Showing
1. **Check error log** for "CMF Update Checker" messages
2. **Verify vendor directory** exists with library
3. **Check GitHub releases** have proper version tags
4. **Clear WordPress transients**:
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE '%_site_transient_%';
   ```

### Manual Update Check
- Go to: Settings → Cleaner Marketing Forms → Updates
- Click "Check Now" button

### Debug Mode
Add to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check `/wp-content/debug.log` for errors.

## Best Practices

1. **Version Numbers**:
   - Always increment version in main plugin file
   - Tag format: `v1.0.0` (with 'v' prefix)
   - Follow semantic versioning

2. **Release Process**:
   ```bash
   # 1. Update version in cleaner-marketing-forms.php
   # 2. Update CHANGELOG.md
   # 3. Commit changes
   git add .
   git commit -m "Prepare version 1.0.1"
   git push
   
   # 4. Build release
   ./build-release.sh
   
   # 5. Create GitHub release with tag v1.0.1
   ```

3. **Testing**:
   - Always test updates on staging first
   - Verify file permissions after update
   - Check that settings are preserved

## Optional: Automated Builds with GitHub Actions

To automate the build process, the repository includes a GitHub Actions workflow that:
- Triggers on new tags
- Installs dependencies
- Creates release zip
- Attaches to GitHub release

To enable:
1. The workflow is already in `.github/workflows/release.yml`
2. It will run automatically when you push tags

## Summary Checklist

- [ ] Install Plugin Update Checker library (composer install)
- [ ] Build release with vendor directory included
- [ ] Create v1.0.0 release on GitHub
- [ ] Test update detection on a WordPress site
- [ ] Document your release process for team

Once these steps are complete, automatic updates will work seamlessly!