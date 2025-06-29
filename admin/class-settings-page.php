<?php
/**
 * Settings Page
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Page class
 */
class DCF_Settings_Page {
    
    /**
     * Settings sections
     */
    private $sections = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
        $this->init_sections();
    }
    
    /**
     * Initialize settings sections
     */
    private function init_sections() {
        $this->sections = array(
            'general' => array(
                'title' => __('General Settings', 'dry-cleaning-forms'),
                'fields' => array(
                    'pos_system' => array(
                        'title' => __('POS System', 'dry-cleaning-forms'),
                        'type' => 'select',
                        'options' => array(
                            '' => __('Select POS System', 'dry-cleaning-forms'),
                            'smrt' => 'SMRT',
                            'spot' => 'SPOT',
                            'cleancloud' => 'CleanCloud'
                        ),
                        'description' => __('Select your primary POS system for customer management.', 'dry-cleaning-forms')
                    ),
                    'login_page_url' => array(
                        'title' => __('Login Page URL', 'dry-cleaning-forms'),
                        'type' => 'url',
                        'description' => __('URL where customers can log in to their account.', 'dry-cleaning-forms')
                    ),
                    'success_message' => array(
                        'title' => __('Success Message', 'dry-cleaning-forms'),
                        'type' => 'textarea',
                        'description' => __('Message displayed when forms are submitted successfully.', 'dry-cleaning-forms')
                    ),
                    'error_message' => array(
                        'title' => __('Error Message', 'dry-cleaning-forms'),
                        'type' => 'textarea',
                        'description' => __('Message displayed when form submission fails.', 'dry-cleaning-forms')
                    )
                )
            ),
            'smrt' => array(
                'title' => __('SMRT Integration', 'dry-cleaning-forms'),
                'fields' => array(
                    'smrt_graphql_url' => array(
                        'title' => __('GraphQL URL', 'dry-cleaning-forms'),
                        'type' => 'url',
                        'description' => __('SMRT GraphQL API endpoint URL.', 'dry-cleaning-forms')
                    ),
                    'smrt_api_key' => array(
                        'title' => __('API Key', 'dry-cleaning-forms'),
                        'type' => 'password',
                        'description' => __('Your SMRT API key (Bearer token).', 'dry-cleaning-forms')
                    ),
                    'smrt_store_id' => array(
                        'title' => __('Store ID', 'dry-cleaning-forms'),
                        'type' => 'text',
                        'description' => __('Required for scheduling appointments and some customer operations. You can find your Store ID by going to Settings > Stations. The Store ID is the name of the store.', 'dry-cleaning-forms')
                    ),
                    'smrt_agent_id' => array(
                        'title' => __('Agent ID', 'dry-cleaning-forms'),
                        'type' => 'text',
                        'description' => __('Optional. Used for customer creation. Will use Store ID if not provided. You can find your Agent ID by going to Settings > Stations and selecting the gear icon next to the name of the store. The Agent ID will appear in the top-left corner.', 'dry-cleaning-forms')
                    ),
                    'smrt_delivery_route_id' => array(
                        'title' => __('Delivery Route ID', 'dry-cleaning-forms'),
                        'type' => 'text',
                        'description' => __('Route ID for delivery appointments (e.g., DeliveryZone_537_18). You can find this in your SMRT routes configuration.', 'dry-cleaning-forms')
                    )
                )
            ),
            'spot' => array(
                'title' => __('SPOT Integration', 'dry-cleaning-forms'),
                'fields' => array(
                    'spot_username' => array(
                        'title' => __('Username', 'dry-cleaning-forms'),
                        'type' => 'text',
                        'description' => __('Your SPOT username.', 'dry-cleaning-forms')
                    ),
                    'spot_license_key' => array(
                        'title' => __('License Key', 'dry-cleaning-forms'),
                        'type' => 'password',
                        'description' => __('Your SPOT license key.', 'dry-cleaning-forms')
                    )
                )
            ),
            'cleancloud' => array(
                'title' => __('CleanCloud Integration', 'dry-cleaning-forms'),
                'fields' => array(
                    'cleancloud_api_key' => array(
                        'title' => __('API Key', 'dry-cleaning-forms'),
                        'type' => 'password',
                        'description' => __('Your CleanCloud API key.', 'dry-cleaning-forms')
                    )
                )
            ),

            'webhooks' => array(
                'title' => __('Webhook Settings', 'dry-cleaning-forms'),
                'fields' => array(
                    'webhook_url' => array(
                        'title' => __('Webhook URL', 'dry-cleaning-forms'),
                        'type' => 'url',
                        'description' => __('URL to receive webhook notifications for form events.', 'dry-cleaning-forms')
                    ),
                    'webhook_secret' => array(
                        'title' => __('Webhook Secret', 'dry-cleaning-forms'),
                        'type' => 'password',
                        'description' => __('Secret key for webhook signature verification (optional).', 'dry-cleaning-forms')
                    )
                )
            ),
            'notifications' => array(
                'title' => __('Email Notifications', 'dry-cleaning-forms'),
                'fields' => array(
                    'email_notifications' => array(
                        'title' => __('Enable Email Notifications', 'dry-cleaning-forms'),
                        'type' => 'checkbox',
                        'description' => __('Send email notifications for form submissions.', 'dry-cleaning-forms')
                    ),
                    'notification_email' => array(
                        'title' => __('Notification Email', 'dry-cleaning-forms'),
                        'type' => 'email',
                        'description' => __('Email address to receive notifications (leave blank to use admin email).', 'dry-cleaning-forms')
                    )
                )
            )
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        foreach ($this->sections as $section_id => $section) {
            add_settings_section(
                'dcf_' . $section_id,
                $section['title'],
                array($this, 'render_section_description'),
                'dcf_settings'
            );
            
            foreach ($section['fields'] as $field_id => $field) {
                add_settings_field(
                    'dcf_' . $field_id,
                    $field['title'],
                    array($this, 'render_field'),
                    'dcf_settings',
                    'dcf_' . $section_id,
                    array(
                        'field_id' => $field_id,
                        'field' => $field
                    )
                );
                
                register_setting('dcf_settings', 'dcf_' . $field_id, array(
                    'sanitize_callback' => array($this, 'sanitize_field')
                ));
            }
        }
    }
    
    /**
     * Render settings page
     */
    public function render() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        // Debug: Log all POST data
        if (!empty($_POST)) {
            error_log('DCF Settings: POST data received: ' . print_r($_POST, true));
        }
        
        // Handle form submission - check for POST data with nonce instead of submit button
        if (!empty($_POST) && isset($_POST['_wpnonce'])) {
            error_log('DCF Settings: Form submission detected');
            if (wp_verify_nonce($_POST['_wpnonce'], 'dcf_settings-options')) {
                error_log('DCF Settings: Nonce verified, saving settings');
                $this->save_settings();
            } else {
                error_log('DCF Settings: Nonce verification failed');
                error_log('DCF Settings: Expected nonce: dcf_settings-options, Received: ' . ($_POST['_wpnonce'] ?? 'none'));
                add_settings_error(
                    'dcf_settings',
                    'nonce_failed',
                    __('Security check failed. Please try again.', 'dry-cleaning-forms'),
                    'error'
                );
            }
        }
        
        include CMF_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render section description
     */
    public function render_section_description($args) {
        // Section descriptions can be added here if needed
    }
    
    /**
     * Render individual field
     */
    public function render_field($args) {
        $field_id = $args['field_id'];
        $field = $args['field'];
        $value = DCF_Plugin_Core::get_setting($field_id);
        
        // Decrypt sensitive fields
        if ($field['type'] === 'password' && !empty($value)) {
            $value = DCF_Plugin_Core::decrypt($value);
        }
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'url':
                printf(
                    '<input type="%s" id="dcf_%s" name="dcf_%s" value="%s" class="regular-text" />',
                    esc_attr($field['type']),
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_attr($value)
                );
                break;
            
            case 'password':
                printf(
                    '<input type="password" id="dcf_%s" name="dcf_%s" value="%s" class="regular-text" />',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_attr($value)
                );
                break;
            
            case 'textarea':
                printf(
                    '<textarea id="dcf_%s" name="dcf_%s" rows="4" cols="50" class="large-text">%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    esc_textarea($value)
                );
                break;
            
            case 'select':
                printf('<select id="dcf_%s" name="dcf_%s">', esc_attr($field_id), esc_attr($field_id));
                foreach ($field['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                break;
            
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="dcf_%s" name="dcf_%s" value="yes" %s />',
                    esc_attr($field_id),
                    esc_attr($field_id),
                    checked($value, 'yes', false)
                );
                break;
        }
        
        if (!empty($field['description'])) {
            printf('<p class="description">%s</p>', esc_html($field['description']));
        }
        
        // Add test connection button for integration fields
        if (in_array($field_id, array('smrt_api_key', 'spot_license_key', 'cleancloud_api_key'))) {
            $integration_type = str_replace('_api_key', '', str_replace('_license_key', '', $field_id));
            if ($integration_type === 'spot') {
                $integration_type = 'spot';
            }
            
            printf(
                '<br><button type="button" class="button dcf-test-integration" data-integration="%s">%s</button>',
                esc_attr($integration_type),
                __('Test Connection', 'dry-cleaning-forms')
            );
            printf('<span class="dcf-test-result" id="dcf-test-%s"></span>', esc_attr($integration_type));
        }
    }
    
    /**
     * Sanitize field value
     */
    public function sanitize_field($value) {
        return DCF_Plugin_Core::sanitize_form_data(array('value' => $value))['value'];
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $saved_count = 0;
        
        // Handle array format fields (dcf_settings[field_name])
        if (isset($_POST['dcf_settings']) && is_array($_POST['dcf_settings'])) {
            foreach ($_POST['dcf_settings'] as $field_name => $value) {
                // Sanitize value
                $value = $this->sanitize_field($value);
                
                $result = DCF_Plugin_Core::update_setting($field_name, $value);
                if ($result) {
                    $saved_count++;
                }
                
                // Debug logging
                error_log("DCF Settings: Saved {$field_name} = " . (is_string($value) ? substr($value, 0, 20) . '...' : print_r($value, true)));
            }
        }
        
        // Handle individual field format (dcf_field_name)
        foreach ($this->sections as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (isset($_POST['dcf_' . $field_id])) {
                    $value = $_POST['dcf_' . $field_id];
                    
                    // Sanitize value
                    $value = $this->sanitize_field($value);
                    
                    // Encrypt sensitive fields
                    if ($field['type'] === 'password' && !empty($value)) {
                        $value = DCF_Plugin_Core::encrypt($value);
                    }
                    
                    $result = DCF_Plugin_Core::update_setting($field_id, $value);
                    if ($result) {
                        $saved_count++;
                    }
                    
                    // Debug logging
                    error_log("DCF Settings: Saved {$field_id} = " . (is_string($value) ? substr($value, 0, 20) . '...' : print_r($value, true)));
                }
            }
        }
        
        // Handle special POS system field
        if (isset($_POST['dcf_pos_system'])) {
            $value = sanitize_text_field($_POST['dcf_pos_system']);
            $result = DCF_Plugin_Core::update_setting('pos_system', $value);
            if ($result) {
                $saved_count++;
            }
            error_log("DCF Settings: Saved pos_system = {$value}");
        }
        
        // Handle POS-specific fields
        $pos_fields = array(
            'dcf_smrt_graphql_url' => 'smrt_graphql_url',
            'dcf_smrt_api_key' => 'smrt_api_key',
            'dcf_smrt_store_id' => 'smrt_store_id',
            'dcf_smrt_agent_id' => 'smrt_agent_id',
            'dcf_spot_username' => 'spot_username', 
            'dcf_spot_license_key' => 'spot_license_key',
            'dcf_cleancloud_api_key' => 'cleancloud_api_key'
        );
        
        foreach ($pos_fields as $post_field => $setting_name) {
            if (isset($_POST[$post_field])) {
                $value = $_POST[$post_field];
                
                // Sanitize value
                $value = $this->sanitize_field($value);
                
                // Encrypt sensitive fields (API keys and license keys)
                if (strpos($setting_name, 'api_key') !== false || strpos($setting_name, 'license_key') !== false) {
                    if (!empty($value)) {
                        $value = DCF_Plugin_Core::encrypt($value);
                    }
                }
                
                $result = DCF_Plugin_Core::update_setting($setting_name, $value);
                if ($result) {
                    $saved_count++;
                }
                
                // Debug logging
                error_log("DCF Settings: Saved {$setting_name} = " . (is_string($value) ? substr($value, 0, 20) . '...' : print_r($value, true)));
            }
        }
        
        add_settings_error(
            'dcf_settings',
            'settings_saved',
            sprintf(__('Settings saved successfully. (%d fields updated)', 'dry-cleaning-forms'), $saved_count),
            'updated'
        );
    }
    
    /**
     * Get settings sections
     */
    public function get_sections() {
        return $this->sections;
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        $settings = array();
        
        foreach ($this->sections as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                $value = DCF_Plugin_Core::get_setting($field_id);
                
                // Don't export sensitive data
                if ($field['type'] === 'password') {
                    $value = !empty($value) ? '***ENCRYPTED***' : '';
                }
                
                $settings[$field_id] = $value;
            }
        }
        
        $filename = 'dcf-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo wp_json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Import settings
     */
    public function import_settings($file_content) {
        if (!DCF_Plugin_Core::current_user_can()) {
            return new WP_Error('insufficient_permissions', __('You do not have sufficient permissions.', 'dry-cleaning-forms'));
        }
        
        $settings = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON file.', 'dry-cleaning-forms'));
        }
        
        $imported_count = 0;
        
        foreach ($this->sections as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (isset($settings[$field_id])) {
                    $value = $settings[$field_id];
                    
                    // Skip encrypted values
                    if ($value === '***ENCRYPTED***') {
                        continue;
                    }
                    
                    // Sanitize value
                    $value = $this->sanitize_field($value);
                    
                    // Encrypt sensitive fields
                    if ($field['type'] === 'password' && !empty($value)) {
                        $value = DCF_Plugin_Core::encrypt($value);
                    }
                    
                    DCF_Plugin_Core::update_setting($field_id, $value);
                    $imported_count++;
                }
            }
        }
        
        return array(
            'success' => true,
            'imported_count' => $imported_count,
            'message' => sprintf(__('%d settings imported successfully.', 'dry-cleaning-forms'), $imported_count)
        );
    }
} 