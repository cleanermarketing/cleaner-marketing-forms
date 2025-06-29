<?php
/**
 * Core plugin functionality
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core plugin class
 */
class DCF_Plugin_Core {
    
    /**
     * Encrypt sensitive data
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    public static function encrypt($data) {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($data); // Fallback to base64 if OpenSSL not available
        }
        
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     *
     * @param string $encrypted_data Encrypted data
     * @return string Decrypted data
     */
    public static function decrypt($encrypted_data) {
        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_data); // Fallback from base64 if OpenSSL not available
        }
        
        $key = self::get_encryption_key();
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     *
     * @return string Encryption key
     */
    private static function get_encryption_key() {
        $key = get_option('dcf_encryption_key');
        
        if (!$key) {
            $key = wp_generate_password(32, false);
            add_option('dcf_encryption_key', $key, '', 'no');
        }
        
        return $key;
    }
    
    /**
     * Sanitize form data
     *
     * @param array $data Form data
     * @return array Sanitized data
     */
    public static function sanitize_form_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize_form_data($value);
            } else {
                switch ($key) {
                    case 'email':
                        $sanitized[$key] = sanitize_email($value);
                        break;
                    case 'phone':
                        $sanitized[$key] = preg_replace('/[^0-9+\-\(\)\s]/', '', $value);
                        break;
                    case 'zip':
                    case 'zipcode':
                        $sanitized[$key] = preg_replace('/[^0-9\-]/', '', $value);
                        break;
                    case 'url':
                    case 'webhook_url':
                        $sanitized[$key] = esc_url_raw($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                        break;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate email address
     *
     * @param string $email Email address
     * @return bool True if valid
     */
    public static function validate_email($email) {
        return is_email($email) !== false;
    }
    
    /**
     * Validate phone number
     *
     * @param string $phone Phone number
     * @return bool True if valid
     */
    public static function validate_phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10;
    }
    
    /**
     * Validate ZIP code
     *
     * @param string $zip ZIP code
     * @return bool True if valid
     */
    public static function validate_zip($zip) {
        return preg_match('/^\d{5}(-\d{4})?$/', $zip);
    }
    
    /**
     * Generate nonce for forms
     *
     * @param string $action Action name
     * @return string Nonce
     */
    public static function create_nonce($action) {
        return wp_create_nonce('dcf_' . $action);
    }
    
    /**
     * Verify nonce for forms
     *
     * @param string $nonce Nonce to verify
     * @param string $action Action name
     * @return bool True if valid
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, 'dcf_' . $action);
    }
    
    /**
     * Log integration activity
     *
     * @param string $integration_type Integration type
     * @param string $action Action performed
     * @param array $request_data Request data
     * @param array $response_data Response data
     * @param string $status Status (success/error)
     */
    public static function log_integration($integration_type, $action, $request_data = array(), $response_data = array(), $status = 'success') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_integration_logs';
        
        $wpdb->insert(
            $table,
            array(
                'integration_type' => $integration_type,
                'action' => $action,
                'request_data' => wp_json_encode($request_data),
                'response_data' => wp_json_encode($response_data),
                'status' => $status,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get US states array
     *
     * @return array US states
     */
    public static function get_us_states() {
        return array(
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming'
        );
    }
    
    /**
     * Send webhook notification
     *
     * @param string $webhook_url Webhook URL
     * @param array $data Data to send
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_webhook($webhook_url, $data) {
        if (empty($webhook_url)) {
            return new WP_Error('no_webhook_url', __('No webhook URL configured', 'dry-cleaning-forms'));
        }
        
        $payload = wp_json_encode($data);
        
        $args = array(
            'body' => $payload,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'DryCleaningForms/' . CMF_PLUGIN_VERSION
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($webhook_url, $args);
        
        if (is_wp_error($response)) {
            self::log_integration('webhook', 'send', $data, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            self::log_integration('webhook', 'send', $data, array('response' => $response_body), 'success');
            return true;
        } else {
            self::log_integration('webhook', 'send', $data, array('error' => $response_body, 'code' => $response_code), 'error');
            return new WP_Error('webhook_error', sprintf(__('Webhook failed with status %d', 'dry-cleaning-forms'), $response_code));
        }
    }
    
    /**
     * Get form submission by ID
     *
     * @param int $submission_id Submission ID
     * @return object|null Submission data
     */
    public static function get_submission($submission_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_submissions';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $submission_id)
        );
    }
    
    /**
     * Update form submission
     *
     * @param int $submission_id Submission ID
     * @param array $data Data to update
     * @return bool True on success
     */
    public static function update_submission($submission_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_submissions';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => $submission_id),
            null,
            array('%d')
        ) !== false;
    }
    
    /**
     * Create form submission
     *
     * @param string $form_id Form ID
     * @param array $user_data User data
     * @param int $step_completed Step completed
     * @param string $status Status
     * @return int|false Submission ID on success, false on failure
     */
    public static function create_submission($form_id, $user_data, $step_completed = 0, $status = 'pending') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_submissions';
        
        $result = $wpdb->insert(
            $table,
            array(
                'form_id' => $form_id,
                'user_data' => wp_json_encode($user_data),
                'step_completed' => $step_completed,
                'status' => $status,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Delete form submission
     *
     * @param int $submission_id Submission ID
     * @return bool True on success, false on failure
     */
    public static function delete_submission($submission_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_submissions';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $submission_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Check if user has required capability
     *
     * @param string $capability Capability to check
     * @return bool True if user has capability
     */
    public static function current_user_can($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Get plugin setting
     *
     * @param string $setting_name Setting name
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public static function get_setting($setting_name, $default = '') {
        return get_option('dcf_' . $setting_name, $default);
    }
    
    /**
     * Update plugin setting
     *
     * @param string $setting_name Setting name
     * @param mixed $value Setting value
     * @return bool True on success
     */
    public static function update_setting($setting_name, $value) {
        return update_option('dcf_' . $setting_name, $value);
    }
} 