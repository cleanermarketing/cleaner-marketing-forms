# Cleaner Marketing Forms WordPress Plugin

A comprehensive WordPress plugin for dry cleaning and laundry service businesses that handles customer signup, contact forms, and opt-in forms with multiple POS system integrations.

## 🚀 Automatic Updates

This plugin supports automatic updates from GitHub releases. Updates appear in your WordPress admin just like plugins from WordPress.org.

## Features

### 🎯 Core Functionality
- **Multi-step Customer Signup Form** - 4-step process with progress tracking
- **Contact Forms** - Customizable contact forms with AJAX submission
- **Opt-in Forms** - Email subscription forms with marketing automation
- **Drag & Drop Form Builder** - Visual form creation with 10+ field types
- **Webhook Integration** - Real-time event notifications

### 🔌 POS System Integrations
- **SMRT** - GraphQL API integration with bearer token authentication
- **SPOT** - REST API integration with basic authentication
- **CleanCloud** - REST API integration with bearer token authentication

### 📊 Analytics & Reporting
- **Dashboard Analytics** - Real-time statistics and metrics
- **Conversion Tracking** - Multi-step form funnel analysis
- **Submission Management** - Complete submission lifecycle tracking
- **Integration Logs** - Detailed API interaction logging

### 🛡️ Security Features
- **Data Encryption** - Sensitive API keys and data encryption
- **CSRF Protection** - WordPress nonce verification
- **Input Sanitization** - Comprehensive data validation
- **Webhook Signatures** - Secure webhook verification

## Installation

### From GitHub Release (Recommended)

1. **Download Latest Release**
   - Go to [Releases](https://github.com/cleanermarketing/cleaner-marketing-forms/releases)
   - Download the latest `cleaner-marketing-forms-x.x.x.zip`

2. **Install in WordPress**
   - Go to WordPress Admin → Plugins → Add New → Upload Plugin
   - Choose the downloaded zip file
   - Click "Install Now" and then "Activate"

3. **Configure Auto Updates** (Optional)
   - Add to `wp-config.php`:
   ```php
   define('CMF_UPDATE_REPO_URL', 'https://github.com/cleanermarketing/cleaner-marketing-forms/');
   ```

### Manual Installation

1. **Upload Plugin Files**
   ```
   /wp-content/plugins/cleaner-marketing-forms/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Cleaner Marketing Forms" and click "Activate"

3. **Database Setup**
   - Plugin automatically creates required database tables on activation
   - Tables: `wp_cmf_submissions`, `wp_cmf_forms`, `wp_cmf_integration_logs`, `wp_cmf_settings`

## Configuration

### 1. General Settings
Navigate to **Cleaner Marketing Forms → Settings**

- **POS System**: Select your primary POS system
- **Login Page URL**: Customer login page URL
- **Success/Error Messages**: Customize form messages

### 2. POS Integration Setup

#### SMRT Integration
- **GraphQL URL**: Your SMRT GraphQL endpoint
- **API Key**: Bearer token for authentication

#### SPOT Integration
- **Username**: Your SPOT username
- **License Key**: Your SPOT license key

#### CleanCloud Integration
- **API Key**: Your CleanCloud API key

**Note**: Cleaner Marketing integration has been removed. Use webhooks to send data to Cleaner Marketing instead.

### 3. Webhook Configuration
- **Webhook URL**: Endpoint to receive form events
- **Webhook Secret**: Optional signature verification key

## Usage

### Shortcodes

#### Multi-Step Signup Form
```php
[cmf_signup_form]
```

#### Contact Form
```php
[cmf_contact_form title="Contact Us"]
```

#### Opt-in Form
```php
[cmf_optin_form title="Stay Updated" style="inline"]
```

#### Custom Forms
```php
[cmf_form id="123"]
```

### Multi-Step Signup Process

1. **Step 1: Basic Information**
   - First Name, Last Name, Phone, Email
   - Customer existence check in POS system

2. **Step 2: Service Selection**
   - Retail store visit
   - Pickup & delivery service
   - Not sure yet

3. **Step 3: Address Collection** (if pickup/delivery selected)
   - Street address, city, state, ZIP code
   - Address validation

4. **Step 4: Payment & Scheduling**
   - Pickup date and time selection
   - Payment information collection
   - Account creation and appointment scheduling

### Form Builder

1. **Access Form Builder**
   - Go to **Cleaner Marketing Forms → Forms**
   - Click "Add New" or edit existing form

2. **Available Field Types**
   - Text Field
   - Email Field
   - Phone Field
   - Textarea
   - Select Dropdown
   - Radio Buttons
   - Checkboxes
   - Address Field
   - Date Field
   - Hidden Field

3. **Form Configuration**
   - Drag fields from palette to canvas
   - Configure field settings in sidebar
   - Set form title, description, and submit text
   - Configure webhook URL for form events

## API Integration Details

### Customer Management
```php
// Check if customer exists
$integrations_manager = new CMF_Integrations_Manager();
$result = $integrations_manager->customer_exists($email, $phone);

// Create new customer
$customer_data = array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '555-1234'
);
$result = $integrations_manager->create_customer($customer_data);
```

### Pickup Scheduling
```php
// Get available pickup dates
$pickup_dates = $integrations_manager->get_pickup_dates($customer_id, $address);

// Schedule pickup appointment
$appointment_data = array(
    'time_slot' => '10:00-12:00',
    'address' => $address_array,
    'notes' => 'Special instructions'
);
$result = $integrations_manager->schedule_pickup($customer_id, $pickup_date, $appointment_data);
```

### Payment Processing
```php
// Process payment
$payment_data = array(
    'amount' => 25.00,
    'card_number' => '4111111111111111',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'security_code' => '123',
    'billing_zip' => '12345'
);
$result = $integrations_manager->process_payment($customer_id, $payment_data);
```

## Webhook Events

The plugin sends webhook notifications for the following events:

### Form Events
- `form.submitted` - Form submission completed
- `form.step_completed` - Multi-step form step completed

### Customer Events
- `customer.created` - New customer account created

### Payment Events
- `payment.processed` - Payment transaction completed

### Appointment Events
- `appointment.scheduled` - Pickup appointment scheduled

### Webhook Payload Example
```json
{
    "event_type": "form.submitted",
    "timestamp": "2024-01-15 10:30:00",
    "source": "dry_cleaning_forms",
    "version": "1.0.0",
    "form_id": "customer_signup",
    "submission_id": 123,
    "submission_data": {
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "555-1234"
    }
}
```

## Database Schema

### Submissions Table (`wp_cmf_submissions`)
- `id` - Unique submission ID
- `form_id` - Form identifier
- `user_data` - JSON encoded form data
- `step_completed` - Current step (for multi-step forms)
- `status` - Submission status
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Forms Table (`wp_cmf_forms`)
- `id` - Unique form ID
- `form_name` - Form display name
- `form_type` - Form type identifier
- `form_config` - JSON encoded form configuration
- `webhook_url` - Form-specific webhook URL
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Integration Logs Table (`wp_cmf_integration_logs`)
- `id` - Unique log ID
- `integration_type` - POS system type
- `action` - API action performed
- `request_data` - JSON encoded request data
- `response_data` - JSON encoded response data
- `status` - Request status (success/error)
- `created_at` - Log timestamp

## Customization

### Custom Field Types
```php
// Add custom field type
add_filter('cmf_field_types', function($field_types) {
    $field_types['custom_field'] = array(
        'label' => __('Custom Field', 'cleaner-marketing-forms'),
        'icon' => 'dashicons-admin-customizer',
        'category' => 'advanced',
        'settings' => array('label', 'required', 'custom_option')
    );
    return $field_types;
});

// Render custom field
add_filter('cmf_render_field_input', function($html, $field, $field_id, $field_name) {
    if ($field['type'] === 'custom_field') {
        $html = '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="dcf-input custom-field">';
    }
    return $html;
}, 10, 4);
```

### Custom Webhook Events
```php
// Send custom webhook
CMF_Webhook_Handler::send_webhook('custom.event', array(
    'custom_data' => 'value',
    'timestamp' => current_time('mysql')
));

// Handle custom webhook events
add_action('cmf_custom_webhook_custom.event', function($data) {
    // Process custom event
    error_log('Custom webhook received: ' . print_r($data, true));
});
```

## Troubleshooting

### Common Issues

1. **Forms Not Submitting**
   - Check AJAX URL in browser console
   - Verify nonce validation
   - Check server error logs

2. **POS Integration Failures**
   - Test connection in settings
   - Verify API credentials
   - Check integration logs

3. **Webhook Not Receiving**
   - Verify webhook URL accessibility
   - Check webhook signature verification
   - Review webhook logs

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log Files
- WordPress: `/wp-content/debug.log`
- Plugin logs: Admin → Cleaner Marketing Forms → Analytics → Integration Logs

## Support

For support and documentation:
- Plugin settings page includes test connection tools
- Integration logs provide detailed API interaction history
- WordPress admin notices show configuration issues

## Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- cURL extension for API integrations
- OpenSSL extension for data encryption

## Update System

### Automatic Updates from GitHub

The plugin includes an automatic update system that checks for new releases on GitHub.

#### Configuration Options

Add these constants to `wp-config.php` to customize update behavior:

```php
// Required: GitHub repository URL
define('CMF_UPDATE_REPO_URL', 'https://github.com/cleanermarketing/cleaner-marketing-forms/');

// Optional: Specify branch (default: 'main')
define('CMF_UPDATE_BRANCH', 'stable');

// Optional: For private repositories (use with caution)
define('CMF_GITHUB_TOKEN', 'your-personal-access-token');
```

#### Creating a New Release

1. Update version in `cleaner-marketing-forms.php`
2. Update `CHANGELOG.md`
3. Commit and push changes
4. Create a new tag:
   ```bash
   git tag v1.0.1
   git push origin v1.0.1
   ```
5. Create a GitHub release with the tag
6. Upload the plugin zip file

#### Manual Update Check

Go to **Cleaner Marketing Forms → Settings → Updates** and click "Check Now"

### Development Workflow

#### Building for Distribution

```bash
# Install dependencies
composer install --no-dev

# Create distribution package
./build.sh
```

#### Repository Structure

```
cleaner-marketing-forms-plugin/
├── admin/               # Admin interface files
├── includes/           # Core plugin classes
├── public/             # Frontend files
├── vendor/             # Composer dependencies
├── .github/            # GitHub Actions workflows
├── cleaner-marketing-forms.php  # Main plugin file
├── composer.json       # Composer configuration
├── CHANGELOG.md        # Version history
└── README.md          # This file
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html 