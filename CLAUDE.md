# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build and Development Commands

```bash
# Install dependencies (development)
composer install

# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Build for distribution
./build.sh

# Run composer build script
composer build

# Run all tests
vendor/bin/phpunit

# Run unit tests only
vendor/bin/phpunit --testsuite unit

# Run integration tests only
vendor/bin/phpunit --testsuite integration

# Run tests with coverage
vendor/bin/phpunit --coverage-html tests/coverage/html
```

## High-Level Architecture

### Plugin Structure
This is a WordPress plugin that follows the MVC pattern with clear separation between admin functionality, public-facing features, and core business logic. The plugin uses PSR-4 autoloading with the `CleanerMarketingForms\` namespace.

### Core Components

**Form System Architecture**
- `DCF_Form_Builder` handles form creation and rendering with 15+ field types
- Multi-step forms with progress tracking and conditional logic
- AJAX-based submission handling through `DCF_Public_Forms`
- UTM parameter tracking automatically captured with submissions

**Popup Engine Architecture**
- `DCF_Popup_Manager` orchestrates the entire popup lifecycle
- Trigger system (`DCF_Popup_Triggers`) handles exit-intent, time delays, scroll percentage
- A/B testing built into the popup system with conversion tracking
- Visual editor only (classic editor removed) with drag-and-drop functionality

**Integration Layer**
- `DCF_Integrations_Manager` provides abstraction over multiple POS systems
- Three POS integrations: SMRT (GraphQL), SPOT (SOAP), CleanCloud (REST)
- Webhook system (`DCF_Webhook_Handler`) for bidirectional communication
- All integrations log to `dcf_integration_logs` table for debugging

### Database Design
The plugin uses custom tables (with `wp_` prefix) to store complex relational data:
- Forms and submissions are linked via foreign keys
- Popup displays and interactions tracked separately for analytics
- UTM parameters stored with each submission for marketing attribution

### Frontend/Backend Communication
- Admin AJAX handlers in `admin/ajax-handlers.php`
- Public AJAX endpoints registered in `DCF_Public_Forms`
- All AJAX requests use WordPress nonces for security
- REST API endpoints for webhook handling

### Security Considerations
- All user inputs sanitized using WordPress sanitization functions
- SQL queries use prepared statements
- Webhook signatures verified for external requests
- File uploads restricted to allowed MIME types

### Performance Optimizations
- Lazy loading for popup content
- Database queries optimized with proper indexing
- Asset loading conditional based on page requirements
- Caching implemented for frequently accessed data

## Development Workflow

1. **Making Changes to Forms**: Edit `includes/class-form-builder.php` for backend logic, update `public/js/public-forms.js` for frontend behavior
2. **Modifying Popups**: Visual editor code in `admin/js/visual-editor.js`, popup display logic in `public/js/popup-engine.js`
3. **Adding Integrations**: Create new class in `includes/integrations/` following existing pattern, register in `DCF_Integrations_Manager`
4. **Database Changes**: Add migration in `admin/migration-tool.php`, update activation hook in main plugin file

## Testing Approach

Tests are organized into unit and integration suites:
- Unit tests mock WordPress functions and database
- Integration tests require WordPress test environment
- Use `DCF_Test_Factory` for creating test data
- Test cases extend `DCF_Test_Case` for common functionality

## Key Files to Understand

1. **`cleaner-marketing-forms.php`**: Entry point, defines constants, sets up hooks
2. **`includes/class-plugin-core.php`**: Core initialization and dependency injection
3. **`includes/class-integrations-manager.php`**: Central hub for all external integrations
4. **`admin/js/visual-editor.js`**: Complex drag-and-drop editor implementation
5. **`public/js/popup-engine.js`**: Sophisticated trigger and display logic

## Automatic Updates

The plugin supports automatic updates via GitHub releases using the Plugin Update Checker library.

### Update Repository
- **GitHub Repository**: https://github.com/cleanermarketing/cleaner-marketing-forms
- This repository controls all plugin updates

### How to Release Updates

1. **Update Version Number**:
   - Edit `cleaner-marketing-forms.php`
   - Update version in the plugin header comment
   - Update `CMF_PLUGIN_VERSION` constant

2. **Create Release Package**:
   ```bash
   ./build.sh
   ```

3. **Push Changes**:
   ```bash
   git add .
   git commit -m "Version X.X.X"
   git push origin main
   ```

4. **Create GitHub Release**:
   ```bash
   # Create and push version tag
   git tag vX.X.X
   git push origin vX.X.X
   ```

5. **Upload Release**:
   - Go to https://github.com/cleanermarketing/cleaner-marketing-forms/releases/new
   - Select the tag you just created
   - Add release title and notes
   - Upload the zip file to "Attach binaries" section (not the Write section)
   - Publish release

### Update System Location
- Updates tab: CM Forms → Settings → Updates
- Direct URL: `/wp-admin/admin.php?page=cmf-settings&tab=updates`
- Shows current version, update status, and allows manual update checks

### Testing Updates
1. Install plugin on a test site
2. Create a new release with higher version number
3. Check CM Forms → Settings → Updates
4. WordPress will show update notification in plugins list