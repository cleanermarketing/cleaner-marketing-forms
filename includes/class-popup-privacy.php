<?php
/**
 * Popup Privacy Compliance Class
 *
 * Handles GDPR/CCPA compliance for popup tracking and data collection
 * as requested by the user.
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Privacy {
    
    /**
     * Initialize privacy compliance
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_privacy_scripts'));
        add_action('wp_ajax_dcf_set_privacy_consent', array($this, 'handle_privacy_consent'));
        add_action('wp_ajax_nopriv_dcf_set_privacy_consent', array($this, 'handle_privacy_consent'));
        add_action('wp_ajax_dcf_get_privacy_data', array($this, 'export_user_data'));
        add_action('wp_ajax_dcf_delete_privacy_data', array($this, 'delete_user_data'));
        add_filter('dcf_popup_render', array($this, 'add_privacy_compliance'), 10, 2);
    }

    /**
     * Enqueue privacy compliance scripts
     */
    public function enqueue_privacy_scripts() {
        wp_enqueue_script(
            'dcf-popup-privacy',
            CMF_PLUGIN_URL . 'public/js/popup-privacy.js',
            array('jquery'),
            CMF_PLUGIN_VERSION,
            true
        );

        wp_localize_script('dcf-popup-privacy', 'dcf_privacy', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_privacy_nonce'),
            'consent_required' => $this->is_consent_required(),
            'privacy_policy_url' => get_privacy_policy_url(),
            'messages' => array(
                'consent_required' => __('We need your consent to show personalized content.', 'dry-cleaning-forms'),
                'data_processing' => __('We process your data to improve your experience.', 'dry-cleaning-forms'),
                'opt_out' => __('You can opt out at any time.', 'dry-cleaning-forms')
            )
        ));
    }

    /**
     * Add privacy compliance to popup rendering
     */
    public function add_privacy_compliance($html, $popup_data) {
        if (!$this->is_consent_required()) {
            return $html;
        }

        // Check if user has given consent
        if (!$this->has_user_consent()) {
            // Add consent banner to popup
            $consent_html = $this->get_consent_banner_html();
            $html = str_replace('</div>', $consent_html . '</div>', $html);
        }

        // Add privacy notice to popup
        $privacy_notice = $this->get_privacy_notice_html($popup_data);
        $html = str_replace('</form>', $privacy_notice . '</form>', $html);

        return $html;
    }

    /**
     * Get consent banner HTML
     */
    private function get_consent_banner_html() {
        return '
        <div class="dcf-privacy-consent-banner" style="
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 15px;
            font-size: 14px;
            text-align: center;
        ">
            <p style="margin: 0 0 10px 0;">
                ' . __('We use cookies and similar technologies to personalize content and analyze traffic.', 'dry-cleaning-forms') . '
            </p>
            <div class="dcf-consent-buttons">
                <button type="button" class="dcf-consent-accept" style="
                    background: #28a745;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    margin-right: 10px;
                    cursor: pointer;
                ">
                    ' . __('Accept', 'dry-cleaning-forms') . '
                </button>
                <button type="button" class="dcf-consent-decline" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    margin-right: 10px;
                    cursor: pointer;
                ">
                    ' . __('Decline', 'dry-cleaning-forms') . '
                </button>
                <a href="' . get_privacy_policy_url() . '" target="_blank" style="
                    color: #007bff;
                    text-decoration: none;
                    font-size: 12px;
                ">
                    ' . __('Privacy Policy', 'dry-cleaning-forms') . '
                </a>
            </div>
        </div>';
    }

    /**
     * Get privacy notice HTML
     */
    private function get_privacy_notice_html($popup_data) {
        $privacy_policy_url = get_privacy_policy_url();
        
        return '
        <div class="dcf-privacy-notice" style="
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        ">
            <p style="margin: 0;">
                ' . sprintf(
                    __('By submitting this form, you agree to our %s and consent to data processing for marketing purposes.', 'dry-cleaning-forms'),
                    '<a href="' . $privacy_policy_url . '" target="_blank">' . __('Privacy Policy', 'dry-cleaning-forms') . '</a>'
                ) . '
            </p>
            <div class="dcf-privacy-options" style="margin-top: 8px;">
                <label style="font-size: 11px; display: block;">
                    <input type="checkbox" name="dcf_marketing_consent" value="1" style="margin-right: 5px;">
                    ' . __('I consent to receive marketing communications', 'dry-cleaning-forms') . '
                </label>
                <label style="font-size: 11px; display: block; margin-top: 5px;">
                    <input type="checkbox" name="dcf_analytics_consent" value="1" style="margin-right: 5px;">
                    ' . __('I consent to analytics tracking for service improvement', 'dry-cleaning-forms') . '
                </label>
            </div>
        </div>';
    }

    /**
     * Handle privacy consent AJAX request
     */
    public function handle_privacy_consent() {
        check_ajax_referer('dcf_privacy_nonce', 'nonce');

        $consent_type = sanitize_text_field($_POST['consent_type']);
        $consent_value = sanitize_text_field($_POST['consent_value']);
        $user_id = get_current_user_id();

        // Store consent in database
        $this->store_consent($user_id, $consent_type, $consent_value);

        // Set consent cookie
        $this->set_consent_cookie($consent_type, $consent_value);

        wp_send_json_success(array(
            'message' => __('Privacy preferences updated successfully.', 'dry-cleaning-forms')
        ));
    }

    /**
     * Store consent in database
     */
    private function store_consent($user_id, $consent_type, $consent_value) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcf_privacy_consents';
        
        // Create table if it doesn't exist
        $this->create_privacy_table();

        $wpdb->replace(
            $table_name,
            array(
                'user_id' => $user_id,
                'ip_address' => $this->get_user_ip(),
                'consent_type' => $consent_type,
                'consent_value' => $consent_value,
                'consent_date' => current_time('mysql'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Set consent cookie
     */
    private function set_consent_cookie($consent_type, $consent_value) {
        $cookie_name = 'dcf_consent_' . $consent_type;
        $cookie_value = $consent_value;
        $expiry = time() + (365 * 24 * 60 * 60); // 1 year

        setcookie($cookie_name, $cookie_value, $expiry, '/', '', is_ssl(), true);
    }

    /**
     * Check if user has given consent
     */
    private function has_user_consent() {
        // Check for consent cookie
        if (isset($_COOKIE['dcf_consent_general']) && $_COOKIE['dcf_consent_general'] === 'accepted') {
            return true;
        }

        // Check database for logged-in users
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            return $this->get_user_consent($user_id, 'general') === 'accepted';
        }

        return false;
    }

    /**
     * Get user consent from database
     */
    private function get_user_consent($user_id, $consent_type) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcf_privacy_consents';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT consent_value FROM {$table_name} WHERE user_id = %d AND consent_type = %s ORDER BY consent_date DESC LIMIT 1",
            $user_id,
            $consent_type
        ));

        return $result;
    }

    /**
     * Check if consent is required based on user location
     */
    private function is_consent_required() {
        // Check if GDPR/CCPA compliance is enabled in settings
        $settings = get_option('dcf_privacy_settings', array());
        
        if (!isset($settings['enable_compliance']) || !$settings['enable_compliance']) {
            return false;
        }

        // Check user location (simplified - in production, use proper geolocation)
        $user_country = $this->get_user_country();
        
        // GDPR countries (EU)
        $gdpr_countries = array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE');
        
        // CCPA states (California)
        $ccpa_states = array('CA');

        return in_array($user_country, $gdpr_countries) || in_array($user_country, $ccpa_states);
    }

    /**
     * Get user country (simplified implementation)
     */
    private function get_user_country() {
        // In production, use proper geolocation service
        // For now, return a default or check IP
        return 'US'; // Default
    }

    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Export user data for GDPR compliance
     */
    public function export_user_data() {
        check_ajax_referer('dcf_privacy_nonce', 'nonce');

        $user_email = sanitize_email($_POST['user_email']);
        $user = get_user_by('email', $user_email);

        if (!$user) {
            wp_send_json_error(__('User not found.', 'dry-cleaning-forms'));
        }

        $user_data = $this->get_user_privacy_data($user->ID);
        
        wp_send_json_success($user_data);
    }

    /**
     * Delete user data for GDPR compliance
     */
    public function delete_user_data() {
        check_ajax_referer('dcf_privacy_nonce', 'nonce');

        $user_email = sanitize_email($_POST['user_email']);
        $user = get_user_by('email', $user_email);

        if (!$user) {
            wp_send_json_error(__('User not found.', 'dry-cleaning-forms'));
        }

        $this->delete_user_privacy_data($user->ID);
        
        wp_send_json_success(__('User data deleted successfully.', 'dry-cleaning-forms'));
    }

    /**
     * Get user privacy data
     */
    private function get_user_privacy_data($user_id) {
        global $wpdb;

        $data = array();

        // Get consent records
        $consent_table = $wpdb->prefix . 'dcf_privacy_consents';
        $consents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$consent_table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        $data['consents'] = $consents;

        // Get popup interaction data
        $popup_table = $wpdb->prefix . 'dcf_popup_analytics';
        $popup_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$popup_table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        $data['popup_interactions'] = $popup_data;

        return $data;
    }

    /**
     * Delete user privacy data
     */
    private function delete_user_privacy_data($user_id) {
        global $wpdb;

        // Delete consent records
        $consent_table = $wpdb->prefix . 'dcf_privacy_consents';
        $wpdb->delete($consent_table, array('user_id' => $user_id), array('%d'));

        // Delete popup analytics data
        $popup_table = $wpdb->prefix . 'dcf_popup_analytics';
        $wpdb->delete($popup_table, array('user_id' => $user_id), array('%d'));

        // Delete form submissions
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        $wpdb->delete($submissions_table, array('user_id' => $user_id), array('%d'));
    }

    /**
     * Create privacy consent table
     */
    private function create_privacy_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcf_privacy_consents';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            ip_address varchar(45) NOT NULL,
            consent_type varchar(50) NOT NULL,
            consent_value varchar(20) NOT NULL,
            consent_date datetime NOT NULL,
            user_agent text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY consent_type (consent_type)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get privacy settings for admin
     */
    public function get_privacy_settings() {
        return get_option('dcf_privacy_settings', array(
            'enable_compliance' => true,
            'consent_banner_text' => __('We use cookies to personalize content and analyze traffic.', 'dry-cleaning-forms'),
            'privacy_policy_url' => get_privacy_policy_url(),
            'data_retention_days' => 365,
            'auto_delete_data' => false
        ));
    }

    /**
     * Update privacy settings
     */
    public function update_privacy_settings($settings) {
        return update_option('dcf_privacy_settings', $settings);
    }
} 