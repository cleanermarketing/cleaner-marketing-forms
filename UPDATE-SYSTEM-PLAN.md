# Cleaner Marketing Forms Plugin Update System Implementation Plan

## Overview
This document outlines the implementation of an automated update system for the Cleaner Marketing Forms plugin across multiple WordPress instances using GitHub releases.

## Chosen Solution: Plugin Update Checker + GitHub Releases

After evaluating multiple options, we'll use the **YahnisElsts Plugin Update Checker** library with GitHub releases for the following reasons:
- Well-maintained and widely used
- Supports private repositories
- Integrates seamlessly with WordPress update UI
- Minimal code required
- Supports both plugins and themes

## Architecture

### 1. Repository Structure
- **Current**: Full WordPress site in GitHub repo
- **Required**: Separate repository for just the plugin
- **Repository Name**: `cleaner-marketing-forms-plugin`

### 2. Version Control Strategy
- Use semantic versioning (e.g., 1.0.0, 1.0.1, 1.1.0)
- Create GitHub releases for each version
- Tag format: `v1.0.0` (with 'v' prefix)
- Include changelog in release notes

### 3. Security Approach
Instead of using GitHub Personal Access Tokens (which expose all repos), we'll use one of these approaches:

**Option A: Public Repository (Recommended for open-source)**
- Make the plugin repository public
- No authentication needed
- Simplest implementation

**Option B: Self-Hosted Update Server**
- Host update files on your own server
- Use obscure URLs for security
- More control over distribution

**Option C: Private Repo with Limited Token**
- Create a dedicated GitHub account for plugin distribution
- Use a token with minimal permissions
- Higher security risk

## Implementation Steps

### Phase 1: Repository Setup
1. Create new GitHub repository `cleaner-marketing-forms-plugin`
2. Extract plugin code from current full-site repo
3. Set up `.gitignore` for WordPress plugin development
4. Initialize with current plugin version

### Phase 2: Plugin Update Checker Integration
1. Install Plugin Update Checker library via Composer or direct download
2. Add update checker initialization to main plugin file
3. Configure version checking against GitHub releases
4. Test update mechanism locally

### Phase 3: Release Process
1. Create build script for packaging releases
2. Automate changelog generation
3. Set up GitHub Actions for automated releases
4. Document release procedures

### Phase 4: Client Site Configuration
1. Add configuration constants for update settings
2. Create admin interface for license/update settings
3. Implement update notifications
4. Add rollback capability

## Code Implementation

### 1. Composer Integration (composer.json)
```json
{
    "name": "your-company/cleaner-marketing-forms",
    "description": "Comprehensive forms plugin for dry cleaning businesses",
    "type": "wordpress-plugin",
    "require": {
        "yahnis-elsts/plugin-update-checker": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "DryCleaningForms\\": "includes/"
        }
    }
}
```

### 2. Update Checker Initialization
```php
// In cleaner-marketing-forms.php
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$dcfUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/your-username/cleaner-marketing-forms-plugin/',
    __FILE__,
    'cleaner-marketing-forms'
);

// Optional: Set branch for stable releases
$dcfUpdateChecker->setBranch('main');

// Optional: For private repos (not recommended)
// $dcfUpdateChecker->setAuthentication('your-token-here');
```

### 3. Version Header Update
```php
/**
 * Plugin Name: Cleaner Marketing Forms
 * Version: 1.0.0
 * Update URI: https://github.com/your-username/cleaner-marketing-forms-plugin/
 */
```

## Release Workflow

### Manual Release Process
1. Update version number in plugin header
2. Update changelog
3. Commit changes: `git commit -m "Release v1.0.1"`
4. Create tag: `git tag v1.0.1`
5. Push with tags: `git push origin main --tags`
6. Create GitHub release with changelog
7. Upload plugin ZIP to release

### Automated Release (GitHub Actions)
```yaml
name: Release Plugin
on:
  push:
    tags:
      - 'v*'
jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build plugin
        run: |
          composer install --no-dev
          zip -r cleaner-marketing-forms.zip . -x ".*" -x "*.md" -x "composer.*"
      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: cleaner-marketing-forms.zip
          generate_release_notes: true
```

## Client Configuration

### Option 1: Direct GitHub Updates
No additional configuration needed if using public repository.

### Option 2: License Key System
```php
// Add to plugin settings
define('CMF_LICENSE_KEY', 'client-license-key');
define('CMF_UPDATE_URL', 'https://your-server.com/updates/');
```

## Testing Checklist
- [ ] Version detection works correctly
- [ ] Update notification appears in WordPress admin
- [ ] Update process completes successfully
- [ ] Plugin remains functional after update
- [ ] Rollback works if update fails
- [ ] Works with multisite installations
- [ ] Performance impact is minimal

## Security Considerations
1. Never commit tokens to repository
2. Use environment variables for sensitive data
3. Implement license validation if needed
4. Log update attempts for monitoring
5. Add update capability checks

## Monitoring & Analytics
Consider implementing:
- Update success/failure tracking
- Version distribution analytics
- Error logging for failed updates
- Client site inventory management

## Alternative Solutions Evaluated
1. **Git Updater Plugin**: More complex, requires plugin installation on all sites
2. **Kernl.us**: Paid service, good for commercial plugins
3. **WP Updates API**: More control but requires custom server
4. **Manual Updates**: Not scalable for multiple sites

## Next Steps
1. Set up new GitHub repository
2. Implement basic update checker
3. Test with 2-3 sites
4. Create documentation for clients
5. Set up automated testing
6. Plan rollout strategy