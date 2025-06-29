<?php
/**
 * Quick setup script for Resend API
 * 
 * Run this once to configure Resend settings
 */

// Load WordPress
// Go up from plugins/cleaner-marketing-forms to public root
// __FILE__ is in /wp-content/plugins/cleaner-marketing-forms/
// Need to go up 3 levels: cleaner-marketing-forms -> plugins -> wp-content -> root
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('Error: Could not find wp-load.php at ' . $wp_load_path);
}
require_once($wp_load_path);

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

// Save Resend settings
update_option('dcf_use_resend_api', 1);
update_option('dcf_resend_api_key', 're_g3mpEAvQ_3DAtxEwUZW4XsEcokynpphaW');
update_option('dcf_enable_admin_notifications', 1);
update_option('dcf_enable_customer_confirmations', 1);

echo "Resend API settings have been configured successfully!<br>";
echo "API Key: " . substr(get_option('dcf_resend_api_key'), 0, 10) . "...<br>";
echo "Resend API Enabled: Yes<br>";
echo "Admin Notifications: Enabled<br>";
echo "Customer Confirmations: Enabled<br><br>";
echo '<a href="' . admin_url('admin.php?page=dcf-settings&tab=email') . '">Go to Email Settings</a><br>';
echo '<a href="test-email.php">Test Email Functionality</a>';