<?php
/**
 * Settings View
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle clear logs action
if (isset($_GET['action']) && $_GET['action'] === 'clear_logs' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'clear_logs')) {
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}dcf_integration_logs");
    wp_redirect(admin_url('admin.php?page=cmf-settings&tab=logs'));
    exit;
}

// Get current settings
$settings = get_option('dcf_settings', array());
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Define tabs
$tabs = array(
    'general' => __('General', 'dry-cleaning-forms'),
    'integrations' => __('POS Integrations', 'dry-cleaning-forms'),
    'webhooks' => __('Webhooks', 'dry-cleaning-forms'),
    'email' => __('Email Notifications', 'dry-cleaning-forms'),
    'advanced' => __('Advanced', 'dry-cleaning-forms'),
    'logs' => __('Integration Logs', 'dry-cleaning-forms'),
    'migration' => __('Migration', 'dry-cleaning-forms')
);
?>

<div class="wrap">
    <h1><?php _e('Dry Cleaning Forms Settings', 'dry-cleaning-forms'); ?></h1>
    
    <?php settings_errors('dcf_settings'); ?>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="<?php echo admin_url('admin.php?page=cmf-settings&tab=' . $tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <form method="post" action="" class="dcf-settings-form">
        <?php wp_nonce_field('dcf_settings-options'); ?>
        
        <div class="dcf-tab-content">
            <?php if ($current_tab === 'general'): ?>
                <!-- General Settings -->
                <div class="dcf-settings-section">
                    <h2><?php _e('General Settings', 'dry-cleaning-forms'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="login_page_url"><?php _e('Login Page URL', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="login_page_url" name="dcf_settings[login_page_url]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('login_page_url')); ?>" 
                                       class="regular-text" placeholder="https://example.com/login">
                                <p class="description">
                                    <?php _e('URL to redirect existing customers to login.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="success_message"><?php _e('Success Message', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <textarea id="success_message" name="dcf_settings[success_message]" 
                                          rows="3" class="large-text"><?php echo esc_textarea(DCF_Plugin_Core::get_setting('success_message') ?: 'Thank you! Your account has been created successfully.'); ?></textarea>
                                <p class="description">
                                    <?php _e('Message shown when form submission is successful.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="error_message"><?php _e('Error Message', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <textarea id="error_message" name="dcf_settings[error_message]" 
                                          rows="3" class="large-text"><?php echo esc_textarea(DCF_Plugin_Core::get_setting('error_message') ?: 'Sorry, there was an error processing your request. Please try again.'); ?></textarea>
                                <p class="description">
                                    <?php _e('Message shown when form submission fails.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="enable_logging"><?php _e('Enable Logging', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_logging" name="dcf_settings[enable_logging]" 
                                           value="1" <?php checked(DCF_Plugin_Core::get_setting('enable_logging'), 1); ?>>
                                    <?php _e('Log form submissions and API calls for debugging', 'dry-cleaning-forms'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($current_tab === 'integrations'): ?>
                <!-- POS Integrations -->
                <div class="dcf-settings-section">
                    <h2><?php _e('POS System Integration', 'dry-cleaning-forms'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Primary POS System', 'dry-cleaning-forms'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="dcf_pos_system" value="smrt" 
                                               <?php checked(DCF_Plugin_Core::get_setting('pos_system'), 'smrt'); ?>>
                                        <?php _e('SMRT', 'dry-cleaning-forms'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="dcf_pos_system" value="spot" 
                                               <?php checked(DCF_Plugin_Core::get_setting('pos_system'), 'spot'); ?>>
                                        <?php _e('SPOT', 'dry-cleaning-forms'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="dcf_pos_system" value="cleancloud" 
                                               <?php checked(DCF_Plugin_Core::get_setting('pos_system'), 'cleancloud'); ?>>
                                        <?php _e('CleanCloud', 'dry-cleaning-forms'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- SMRT Settings -->
                    <div class="dcf-integration-settings" id="smrt-settings" style="<?php echo DCF_Plugin_Core::get_setting('pos_system') !== 'smrt' ? 'display: none;' : ''; ?>">
                        <h3><?php _e('SMRT Integration Settings', 'dry-cleaning-forms'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="smrt_graphql_url"><?php _e('GraphQL URL', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="smrt_graphql_url" name="dcf_smrt_graphql_url" 
                                           value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('smrt_graphql_url')); ?>" 
                                           class="regular-text" placeholder="https://api.smrt.com/graphql">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smrt_api_key"><?php _e('API Key', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="smrt_api_key" name="dcf_smrt_api_key" 
                                           value="<?php $api_key = DCF_Plugin_Core::get_setting('smrt_api_key'); echo esc_attr($api_key ? DCF_Plugin_Core::decrypt($api_key) : ''); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smrt_store_id"><?php _e('Store ID', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="smrt_store_id" name="dcf_smrt_store_id" 
                                           value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('smrt_store_id')); ?>" 
                                           class="regular-text" placeholder="store123">
                                    <p class="description">
                                        <?php _e('Required for scheduling appointments and some customer operations. You can find your Store ID by going to Settings > Stations. The Store ID is the name of the store.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smrt_agent_id"><?php _e('Agent ID', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="smrt_agent_id" name="dcf_smrt_agent_id" 
                                           value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('smrt_agent_id')); ?>" 
                                           class="regular-text" placeholder="agent456">
                                    <p class="description">
                                        <?php _e('Optional. Used for customer creation. Will use Store ID if not provided. You can find your Agent ID by going to Settings > Stations and selecting the gear icon next to the name of the store. The Agent ID will appear in the top-left corner.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smrt_delivery_route_id"><?php _e('Delivery Route ID', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="smrt_delivery_route_id" name="dcf_smrt_delivery_route_id" 
                                           value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('smrt_delivery_route_id')); ?>" 
                                           class="regular-text" placeholder="DeliveryZone_537_18">
                                    <p class="description">
                                        <?php _e('The route ID for scheduling pickup appointments. You can find this in your SMRT dashboard under Routes/Zones.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php _e('Connection Test', 'dry-cleaning-forms'); ?>
                                </th>
                                <td>
                                    <button type="button" class="button dcf-test-connection" data-integration="smrt">
                                        <?php _e('Test Connection', 'dry-cleaning-forms'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('Test API credentials and connectivity.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- SPOT Settings -->
                    <div class="dcf-integration-settings" id="spot-settings" style="<?php echo DCF_Plugin_Core::get_setting('pos_system') !== 'spot' ? 'display: none;' : ''; ?>">
                        <h3><?php _e('SPOT Integration Settings', 'dry-cleaning-forms'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="spot_username"><?php _e('Username', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="spot_username" name="dcf_spot_username" 
                                           value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('spot_username')); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="spot_license_key"><?php _e('License Key', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="spot_license_key" name="dcf_spot_license_key" 
                                           value="<?php $license_key = DCF_Plugin_Core::get_setting('spot_license_key'); echo esc_attr($license_key ? DCF_Plugin_Core::decrypt($license_key) : ''); ?>" 
                                           class="regular-text">
                                    <button type="button" class="button dcf-test-connection" data-integration="spot">
                                        <?php _e('Test Connection', 'dry-cleaning-forms'); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- CleanCloud Settings -->
                    <div class="dcf-integration-settings" id="cleancloud-settings" style="<?php echo DCF_Plugin_Core::get_setting('pos_system') !== 'cleancloud' ? 'display: none;' : ''; ?>">
                        <h3><?php _e('CleanCloud Integration Settings', 'dry-cleaning-forms'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="cleancloud_api_key"><?php _e('API Key', 'dry-cleaning-forms'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="cleancloud_api_key" name="dcf_cleancloud_api_key" 
                                           value="<?php $cc_api_key = DCF_Plugin_Core::get_setting('cleancloud_api_key'); echo esc_attr($cc_api_key ? DCF_Plugin_Core::decrypt($cc_api_key) : ''); ?>" 
                                           class="regular-text">
                                    <button type="button" class="button dcf-test-connection" data-integration="cleancloud">
                                        <?php _e('Test Connection', 'dry-cleaning-forms'); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
                
            <?php elseif ($current_tab === 'webhooks'): ?>
                <!-- Webhook Settings -->
                <div class="dcf-settings-section">
                    <h2><?php _e('Webhook Configuration', 'dry-cleaning-forms'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="global_webhook_url"><?php _e('Global Webhook URL', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="global_webhook_url" name="dcf_settings[global_webhook_url]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('global_webhook_url')); ?>" 
                                       class="regular-text" placeholder="https://example.com/webhook">
                                <p class="description">
                                    <?php _e('Default webhook URL for all forms (can be overridden per form).', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="webhook_secret"><?php _e('Webhook Secret', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="webhook_secret" name="dcf_settings[webhook_secret]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('webhook_secret')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Secret key for webhook signature verification (optional).', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="webhook_timeout"><?php _e('Webhook Timeout', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="webhook_timeout" name="dcf_settings[webhook_timeout]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('webhook_timeout') ?: 30); ?>" 
                                       min="5" max="300" class="small-text"> <?php _e('seconds', 'dry-cleaning-forms'); ?>
                                <p class="description">
                                    <?php _e('Maximum time to wait for webhook response.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="webhook_retry_attempts"><?php _e('Retry Attempts', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="webhook_retry_attempts" name="dcf_settings[webhook_retry_attempts]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('webhook_retry_attempts') ?: 3); ?>" 
                                       min="0" max="10" class="small-text">
                                <p class="description">
                                    <?php _e('Number of retry attempts for failed webhooks.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($current_tab === 'email'): ?>
                <!-- Email Notification Settings -->
                <div class="dcf-settings-section">
                    <h2><?php _e('Email Notifications', 'dry-cleaning-forms'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_admin_notifications"><?php _e('Admin Notifications', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_admin_notifications" name="dcf_settings[enable_admin_notifications]" 
                                           value="1" <?php checked(DCF_Plugin_Core::get_setting('enable_admin_notifications'), 1); ?>>
                                    <?php _e('Send email notifications to admin for new submissions', 'dry-cleaning-forms'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="admin_notification_email"><?php _e('Admin Email', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="admin_notification_email" name="dcf_settings[admin_notification_email]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('admin_notification_email') ?: get_option('admin_email')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Email address to receive admin notifications.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="enable_customer_confirmations"><?php _e('Customer Confirmations', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_customer_confirmations" name="dcf_settings[enable_customer_confirmations]" 
                                           value="1" <?php checked(DCF_Plugin_Core::get_setting('enable_customer_confirmations'), 1); ?>>
                                    <?php _e('Send confirmation emails to customers', 'dry-cleaning-forms'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="from_email"><?php _e('From Email', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="from_email" name="dcf_settings[from_email]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('from_email') ?: get_option('admin_email')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Email address to send notifications from.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="from_name"><?php _e('From Name', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="from_name" name="dcf_settings[from_name]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('from_name') ?: get_bloginfo('name')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Name to send notifications from.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th colspan="2">
                                <h3><?php _e('Resend API Integration', 'dry-cleaning-forms'); ?></h3>
                            </th>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="use_resend_api"><?php _e('Use Resend API', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="use_resend_api" name="dcf_settings[use_resend_api]" 
                                           value="1" <?php checked(DCF_Plugin_Core::get_setting('use_resend_api'), 1); ?>>
                                    <?php _e('Send emails using Resend API instead of default WordPress mail', 'dry-cleaning-forms'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Resend provides reliable email delivery with better deliverability rates.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="resend_api_key"><?php _e('Resend API Key', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="resend_api_key" name="dcf_settings[resend_api_key]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('resend_api_key')); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Enter your Resend API key. Get one at', 'dry-cleaning-forms'); ?> 
                                    <a href="https://resend.com/api-keys" target="_blank">resend.com/api-keys</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($current_tab === 'advanced'): ?>
                <!-- Advanced Settings -->
                <div class="dcf-settings-section">
                    <h2><?php _e('Advanced Settings', 'dry-cleaning-forms'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cache_duration"><?php _e('Cache Duration', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cache_duration" name="dcf_settings[cache_duration]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('cache_duration') ?: 300); ?>" 
                                       min="60" max="3600" class="small-text"> <?php _e('seconds', 'dry-cleaning-forms'); ?>
                                <p class="description">
                                    <?php _e('How long to cache API responses.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="rate_limit"><?php _e('API Rate Limit', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="rate_limit" name="dcf_settings[rate_limit]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('rate_limit') ?: 60); ?>" 
                                       min="10" max="1000" class="small-text"> <?php _e('requests per minute', 'dry-cleaning-forms'); ?>
                                <p class="description">
                                    <?php _e('Maximum API requests per minute.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cleanup_logs_days"><?php _e('Log Cleanup', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cleanup_logs_days" name="dcf_settings[cleanup_logs_days]" 
                                       value="<?php echo esc_attr(DCF_Plugin_Core::get_setting('cleanup_logs_days') ?: 30); ?>" 
                                       min="1" max="365" class="small-text"> <?php _e('days', 'dry-cleaning-forms'); ?>
                                <p class="description">
                                    <?php _e('Automatically delete logs older than this many days.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="debug_mode"><?php _e('Debug Mode', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="debug_mode" name="dcf_settings[debug_mode]" 
                                           value="1" <?php checked(DCF_Plugin_Core::get_setting('debug_mode'), 1); ?>>
                                    <?php _e('Enable detailed debug logging', 'dry-cleaning-forms'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Only enable for troubleshooting. May impact performance.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="dcf-danger-zone">
                        <h3><?php _e('Danger Zone', 'dry-cleaning-forms'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Reset Settings', 'dry-cleaning-forms'); ?></th>
                                <td>
                                    <button type="button" class="button button-secondary dcf-reset-settings">
                                        <?php _e('Reset All Settings', 'dry-cleaning-forms'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('This will reset all plugin settings to their default values.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Clear Cache', 'dry-cleaning-forms'); ?></th>
                                <td>
                                    <button type="button" class="button button-secondary dcf-clear-cache">
                                        <?php _e('Clear All Cache', 'dry-cleaning-forms'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('Clear all cached API responses and transients.', 'dry-cleaning-forms'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($current_tab === 'logs'): ?>
                <!-- Integration Logs -->
                <div class="dcf-settings-section">
                    <h2><?php _e('Integration Logs', 'dry-cleaning-forms'); ?></h2>
                    <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'dcf_integration_logs';
                    $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
                    ?>
                    
                    <?php if ($logs): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'dry-cleaning-forms'); ?></th>
                                    <th><?php _e('Integration', 'dry-cleaning-forms'); ?></th>
                                    <th><?php _e('Action', 'dry-cleaning-forms'); ?></th>
                                    <th><?php _e('Status', 'dry-cleaning-forms'); ?></th>
                                    <th><?php _e('Request Data', 'dry-cleaning-forms'); ?></th>
                                    <th><?php _e('Response', 'dry-cleaning-forms'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo esc_html($log->created_at); ?></td>
                                        <td><?php echo esc_html($log->integration_type); ?></td>
                                        <td><?php echo esc_html($log->action); ?></td>
                                        <td><?php echo esc_html($log->status); ?></td>
                                        <td>
                                            <details>
                                                <summary><?php _e('View Request', 'dry-cleaning-forms'); ?></summary>
                                                <pre style="white-space: pre-wrap; word-wrap: break-word; max-width: 300px; font-size: 11px;"><?php echo esc_html($log->request_data); ?></pre>
                                            </details>
                                        </td>
                                        <td>
                                            <details>
                                                <summary><?php _e('View Response', 'dry-cleaning-forms'); ?></summary>
                                                <pre style="white-space: pre-wrap; word-wrap: break-word; max-width: 300px; font-size: 11px;"><?php echo esc_html($log->response_data); ?></pre>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p style="margin-top: 20px;">
                            <button type="button" class="button button-secondary" onclick="if(confirm('Clear all integration logs?')) { window.location.href='<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-settings&tab=logs&action=clear_logs'), 'clear_logs'); ?>'; }">
                                <?php _e('Clear All Logs', 'dry-cleaning-forms'); ?>
                            </button>
                        </p>
                    <?php else: ?>
                        <p><?php _e('No integration logs found.', 'dry-cleaning-forms'); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($current_tab === 'migration'): ?>
                <!-- Migration Tool -->
                <?php do_action('dcf_settings_migration_tab'); ?>
            <?php endif; ?>
        </div>
        
        <?php if ($current_tab !== 'logs' && $current_tab !== 'migration'): ?>
            <?php submit_button(); ?>
        <?php endif; ?>
    </form>
</div>

<style>
.dcf-settings-form {
    max-width: 1000px;
}

.dcf-tab-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px;
    border-radius: 0 0 4px 4px;
}

.dcf-settings-section {
    margin-bottom: 40px;
}

.dcf-settings-section:last-child {
    margin-bottom: 0;
}

.dcf-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #c3c4c7;
}

.dcf-settings-section h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    color: #1d2327;
}

.dcf-integration-settings {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.dcf-integration-settings h3 {
    margin-top: 0;
}

.dcf-test-connection {
    margin-left: 10px;
}

.dcf-danger-zone {
    background: #fef7f7;
    border: 1px solid #f1aeb5;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.dcf-danger-zone h3 {
    color: #721c24;
    margin-top: 0;
}

.dcf-connection-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 10px;
}

.dcf-connection-success {
    background: #d1e7dd;
    color: #0f5132;
}

.dcf-connection-error {
    background: #f8d7da;
    color: #721c24;
}

.dcf-connection-testing {
    background: #cff4fc;
    color: #055160;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show/hide integration settings based on selected POS system
    $('input[name="dcf_pos_system"]').on('change', function() {
        $('.dcf-integration-settings').hide();
        $('#' + $(this).val() + '-settings').show();
    });
    
    // Test connection buttons
    $('.dcf-test-connection').on('click', function() {
        var button = $(this);
        var integration = button.data('integration');
        var originalText = button.text();
        
        button.prop('disabled', true).text(dcf_admin.messages.testing);
        
        // Remove any existing status indicators
        button.siblings('.dcf-connection-status').remove();
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'test_integration',
                integration: integration,
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                // Remove any existing results
                button.siblings('.dcf-connection-status, .dcf-connection-details').remove();
                button.nextAll('br, small').remove();
                
                var isSuccess = response.success && response.data && response.data.success;
                var statusClass = isSuccess ? 'dcf-connection-success' : 'dcf-connection-error';
                var statusText = isSuccess ? dcf_admin.messages.connection_success : dcf_admin.messages.connection_error;
                
                button.after('<span class="dcf-connection-status ' + statusClass + '">' + statusText + '</span>');
                
                // Show detailed results
                if (response.data) {
                    var details = response.data.details || {};
                    var message = response.data.message || '';
                    
                    var detailsHtml = '<div class="dcf-connection-details" style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px;">';
                    
                    if (message && !isSuccess) {
                        detailsHtml += '<p style="color: #721c24; margin: 0 0 8px 0;"><strong>Error:</strong> ' + message + '</p>';
                    }
                    
                    if (details.endpoint) {
                        detailsHtml += '<p style="margin: 2px 0;"><strong>Endpoint:</strong> ' + details.endpoint + '</p>';
                    }
                    
                    if (details.response_time) {
                        detailsHtml += '<p style="margin: 2px 0;"><strong>Response Time:</strong> ' + details.response_time + '</p>';
                    }
                    
                    if (details.customer_count !== undefined && details.customer_count !== 'N/A') {
                        detailsHtml += '<p style="margin: 2px 0;"><strong>Customers Found:</strong> ' + details.customer_count + '</p>';
                    }
                    
                    if (details.error_code) {
                        detailsHtml += '<p style="margin: 2px 0; color: #721c24;"><strong>Error Code:</strong> ' + details.error_code + '</p>';
                    }
                    
                    if (isSuccess) {
                        detailsHtml += '<p style="margin: 8px 0 0 0; color: #0f5132;"><span class="dashicons dashicons-yes-alt"></span> API authentication verified</p>';
                    }
                    
                    detailsHtml += '</div>';
                    
                    button.after(detailsHtml);
                }
            },
            error: function() {
                button.after('<span class="dcf-connection-status dcf-connection-error">' + dcf_admin.messages.connection_error + '</span>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Reset settings
    $('.dcf-reset-settings').on('click', function() {
        if (!confirm(dcf_admin.messages.confirm_reset)) {
            return;
        }
        
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text(dcf_admin.messages.processing);
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'reset_settings',
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || dcf_admin.messages.error);
                }
            },
            error: function() {
                alert(dcf_admin.messages.error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Clear cache
    $('.dcf-clear-cache').on('click', function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text(dcf_admin.messages.processing);
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'clear_cache',
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(dcf_admin.messages.cache_cleared);
                } else {
                    alert(response.data.message || dcf_admin.messages.error);
                }
            },
            error: function() {
                alert(dcf_admin.messages.error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script> 