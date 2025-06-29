# Migration Guide: Dry Cleaning Forms to Cleaner Marketing Forms

This guide helps you migrate from the old "Dry Cleaning Forms" plugin to the new "Cleaner Marketing Forms" plugin.

## Important: Database Tables Remain Compatible

The good news is that all your existing data will continue to work! The database tables use the same structure and prefix (`wp_dcf_*`), so there's no data migration required.

## Migration Steps

### 1. Backup Your Site
Always create a full backup before making changes:
- Database backup
- Files backup
- Note your current plugin settings

### 2. Deactivate Old Plugin
- Go to WordPress Admin → Plugins
- Deactivate "Dry Cleaning Forms"
- DO NOT delete it yet

### 3. Install New Plugin
- Download "Cleaner Marketing Forms" from the repository
- Upload via Plugins → Add New → Upload Plugin
- Activate the plugin

### 4. Update Configuration Constants

If you have any custom configuration in `wp-config.php`, update the constant names:

**Old Constants:**
```php
define('DCF_UPDATE_REPO_URL', 'https://github.com/...');
define('DCF_UPDATE_BRANCH', 'main');
define('DCF_GITHUB_TOKEN', 'your-token');
```

**New Constants:**
```php
define('CMF_UPDATE_REPO_URL', 'https://github.com/...');
define('CMF_UPDATE_BRANCH', 'main');
define('CMF_GITHUB_TOKEN', 'your-token');
```

### 5. Update Shortcodes

Update any shortcodes in your pages/posts:

**Old Shortcodes:**
- `[dcf_signup_form]`
- `[dcf_contact_form]`
- `[dcf_optin_form]`
- `[dcf_form id="123"]`

**New Shortcodes:**
- `[cmf_signup_form]`
- `[cmf_contact_form]`
- `[cmf_optin_form]`
- `[cmf_form id="123"]`

### 6. Update Custom Code

If you have any custom code that references the plugin:

**PHP Class Names:**
- `DCF_*` → `CMF_*` (e.g., `DCF_Form_Builder` → `CMF_Form_Builder`)

**Hook Names:**
- `dcf_*` → `cmf_*` (e.g., `dcf_field_types` → `cmf_field_types`)

**Text Domain:**
- `'dry-cleaning-forms'` → `'cleaner-marketing-forms'`

### 7. Clear Caches
- Clear any caching plugins
- Clear browser cache
- Clear CDN cache if applicable

### 8. Test Everything
- Test all forms
- Verify POS integrations
- Check webhook connections
- Review form submissions

### 9. Remove Old Plugin
Once everything is working correctly:
- Delete the old "Dry Cleaning Forms" plugin
- Keep your backup for at least 30 days

## Compatibility Notes

### What Stays the Same:
- All database tables (`wp_dcf_*`)
- All form data and submissions
- All settings and configurations
- POS integration credentials
- Webhook URLs

### What Changes:
- Plugin name and slug
- Shortcode prefixes
- PHP class prefixes
- Configuration constant prefixes
- Text domain for translations

## Rollback Instructions

If you need to rollback:
1. Deactivate "Cleaner Marketing Forms"
2. Reactivate "Dry Cleaning Forms"
3. Restore original shortcodes
4. Restore original configuration constants

## Need Help?

If you encounter any issues during migration:
1. Check the error logs
2. Enable WP_DEBUG for detailed information
3. Contact support with:
   - Error messages
   - WordPress version
   - PHP version
   - List of active plugins

## Future Updates

Going forward, all updates will be released under "Cleaner Marketing Forms". The automatic update system will work with the new naming convention.