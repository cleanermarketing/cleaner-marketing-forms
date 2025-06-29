<?php
/**
 * Popup Manager Class
 *
 * Handles popup creation, management, and database operations
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Manager {
    
    /**
     * Database table names
     */
    private $popups_table;
    private $popup_displays_table;
    private $popup_interactions_table;
    private $popup_frequency_table;
    private $popup_templates_table;
    private $popup_campaigns_table;
    private $ab_tests_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->popups_table = $wpdb->prefix . 'dcf_popups';
        $this->popup_displays_table = $wpdb->prefix . 'dcf_popup_displays';
        $this->popup_interactions_table = $wpdb->prefix . 'dcf_popup_interactions';
        $this->popup_frequency_table = $wpdb->prefix . 'dcf_popup_frequency';
        $this->popup_templates_table = $wpdb->prefix . 'dcf_popup_templates';
        $this->popup_campaigns_table = $wpdb->prefix . 'dcf_popup_campaigns';
        $this->ab_tests_table = $wpdb->prefix . 'dcf_ab_tests';
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'create_popup_tables'));
        add_action('wp_ajax_dcf_popup_action', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_nopriv_dcf_popup_action', array($this, 'handle_ajax_requests'));
    }
    
    /**
     * Create popup database tables
     */
    public function create_popup_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Popup configurations table
        $sql_popups = "CREATE TABLE IF NOT EXISTS {$this->popups_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_name varchar(255) NOT NULL,
            popup_type varchar(50) NOT NULL DEFAULT 'modal',
            popup_config longtext,
            targeting_rules longtext,
            trigger_settings longtext,
            design_settings longtext,
            template_id varchar(100) DEFAULT NULL,
            ab_test_id int(11) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY popup_type (popup_type),
            KEY status (status),
            KEY template_id (template_id),
            KEY ab_test_id (ab_test_id)
        ) $charset_collate;";
        
        // Popup display tracking table
        $sql_displays = "CREATE TABLE IF NOT EXISTS {$this->popup_displays_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_id int(11) NOT NULL,
            user_id int(11) DEFAULT NULL,
            session_id varchar(255),
            ip_address varchar(45),
            user_agent text,
            page_url text,
            display_time datetime DEFAULT CURRENT_TIMESTAMP,
            action_taken varchar(50) DEFAULT 'displayed',
            conversion_value decimal(10,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY popup_id (popup_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY action_taken (action_taken),
            KEY display_time (display_time)
        ) $charset_collate;";
        
        // A/B testing data table
        $sql_ab_tests = "CREATE TABLE IF NOT EXISTS {$this->ab_tests_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            test_name varchar(255) NOT NULL,
            popup_ids text,
            traffic_split text,
            start_date datetime,
            end_date datetime,
            status varchar(20) NOT NULL DEFAULT 'draft',
            winner_id int(11) DEFAULT NULL,
            confidence_level decimal(5,2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY winner_id (winner_id)
        ) $charset_collate;";
        
        // Popup templates table
        $sql_templates = "CREATE TABLE IF NOT EXISTS {$this->popup_templates_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_type varchar(50) NOT NULL,
            template_config longtext,
            preview_image varchar(255),
            category varchar(100),
            tags text,
            rating decimal(3,2) DEFAULT 0.00,
            download_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_type (template_type),
            KEY category (category),
            KEY rating (rating)
        ) $charset_collate;";
        
        // User popup interactions table
        $sql_interactions = "CREATE TABLE IF NOT EXISTS {$this->popup_interactions_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_id int(11) NOT NULL,
            user_identifier varchar(255),
            interaction_type varchar(50) NOT NULL,
            interaction_data longtext,
            page_url text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(255),
            PRIMARY KEY (id),
            KEY popup_id (popup_id),
            KEY user_identifier (user_identifier),
            KEY interaction_type (interaction_type),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Popup frequency tracking table
        $sql_frequency = "CREATE TABLE IF NOT EXISTS {$this->popup_frequency_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_id int(11) NOT NULL,
            user_identifier varchar(255) NOT NULL,
            display_count int(11) DEFAULT 0,
            last_displayed datetime,
            last_dismissed datetime,
            last_converted datetime,
            cooldown_until datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY popup_user (popup_id, user_identifier),
            KEY display_count (display_count),
            KEY cooldown_until (cooldown_until)
        ) $charset_collate;";
        
        // Campaign scheduling table
        $sql_campaigns = "CREATE TABLE IF NOT EXISTS {$this->popup_campaigns_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            campaign_name varchar(255) NOT NULL,
            popup_ids text,
            schedule_config longtext,
            targeting_config longtext,
            status varchar(20) NOT NULL DEFAULT 'draft',
            start_date datetime,
            end_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_popups);
        dbDelta($sql_displays);
        dbDelta($sql_ab_tests);
        dbDelta($sql_templates);
        dbDelta($sql_interactions);
        dbDelta($sql_frequency);
        dbDelta($sql_campaigns);
        
        // Insert default popup templates
        $this->insert_default_templates();
    }
    
    /**
     * Insert default popup templates
     */
    private function insert_default_templates() {
        global $wpdb;
        
        // Check if templates already exist
        $existing_templates = $wpdb->get_var("SELECT COUNT(*) FROM {$this->popup_templates_table}");
        if ($existing_templates > 0) {
            return;
        }
        
        $default_templates = array(
            array(
                'template_name' => 'Exit-Intent Modal',
                'template_type' => 'modal',
                'template_config' => json_encode(array(
                    'width' => '500px',
                    'height' => 'auto',
                    'background_color' => '#ffffff',
                    'border_radius' => '8px',
                    'overlay_color' => 'rgba(0,0,0,0.7)',
                    'animation' => 'fadeIn',
                    'close_button' => true,
                    'close_on_overlay' => true
                )),
                'category' => 'exit-intent',
                'tags' => 'modal,exit-intent,lead-capture'
            ),
            array(
                'template_name' => 'Sidebar Slide-In',
                'template_type' => 'sidebar',
                'template_config' => json_encode(array(
                    'position' => 'right',
                    'width' => '300px',
                    'height' => '400px',
                    'background_color' => '#f8f9fa',
                    'border_radius' => '8px 0 0 8px',
                    'animation' => 'slideInRight',
                    'close_button' => true,
                    'minimizable' => true
                )),
                'category' => 'sidebar',
                'tags' => 'sidebar,slide-in,non-intrusive'
            ),
            array(
                'template_name' => 'Bottom Bar',
                'template_type' => 'bar',
                'template_config' => json_encode(array(
                    'position' => 'bottom',
                    'height' => '80px',
                    'background_color' => '#2271b1',
                    'text_color' => '#ffffff',
                    'animation' => 'slideInUp',
                    'close_button' => true,
                    'sticky' => true
                )),
                'category' => 'notification',
                'tags' => 'bar,notification,sticky'
            ),
            array(
                'template_name' => 'Multi-Step Modal',
                'template_type' => 'multi-step',
                'template_config' => json_encode(array(
                    'width' => '600px',
                    'height' => 'auto',
                    'background_color' => '#ffffff',
                    'border_radius' => '12px',
                    'steps' => 3,
                    'progress_bar' => true,
                    'animation' => 'fadeIn',
                    'close_button' => true
                )),
                'category' => 'multi-step',
                'tags' => 'modal,multi-step,form,progressive'
            )
        );
        
        foreach ($default_templates as $template) {
            $wpdb->insert(
                $this->popup_templates_table,
                $template,
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Create a new popup
     */
    public function create_popup($data) {
        global $wpdb;
        
        $defaults = array(
            'popup_name' => 'New Popup',
            'popup_type' => 'modal',
            'popup_config' => '{}',
            'targeting_rules' => '{}',
            'trigger_settings' => '{}',
            'design_settings' => '{}',
            'template_id' => null,
            'status' => 'draft'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Sanitize data
        $data['popup_name'] = sanitize_text_field($data['popup_name']);
        $data['popup_type'] = sanitize_text_field($data['popup_type']);
        $data['status'] = sanitize_text_field($data['status']);
        
        // Encode array fields as JSON
        $json_fields = array('popup_config', 'targeting_rules', 'trigger_settings', 'design_settings');
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
                error_log('DCF Popup Create: Encoded ' . $field . ' - ' . $data[$field]);
            }
        }
        
        $result = $wpdb->insert(
            $this->popups_table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('DCF Popup Create: Failed to create popup - ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to create popup');
        }
        
        $popup_id = $wpdb->insert_id;
        error_log('DCF Popup Create: Successfully created popup ' . $popup_id);
        return $popup_id;
    }
    
    /**
     * Get popup by ID
     */
    public function get_popup($popup_id) {
        global $wpdb;
        
        $popup = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->popups_table} WHERE id = %d",
                $popup_id
            ),
            ARRAY_A
        );
        
        if ($popup) {
            // Decode JSON fields
            $popup['popup_config'] = json_decode($popup['popup_config'], true);
            $popup['targeting_rules'] = json_decode($popup['targeting_rules'], true);
            $popup['trigger_settings'] = json_decode($popup['trigger_settings'], true);
            $popup['design_settings'] = json_decode($popup['design_settings'], true);
            // template_id is already a string, no need to decode
        }
        
        return $popup;
    }
    
    /**
     * Update popup
     */
    public function update_popup($popup_id, $data) {
        global $wpdb;
        
        // Sanitize data
        if (isset($data['popup_name'])) {
            $data['popup_name'] = sanitize_text_field($data['popup_name']);
        }
        if (isset($data['popup_type'])) {
            $data['popup_type'] = sanitize_text_field($data['popup_type']);
        }
        if (isset($data['status'])) {
            $data['status'] = sanitize_text_field($data['status']);
        }
        
        // Encode array fields as JSON
        $json_fields = array('popup_config', 'targeting_rules', 'trigger_settings', 'design_settings');
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
                error_log('DCF Popup Update: Encoded ' . $field . ' - ' . $data[$field]);
            }
        }
        
        $result = $wpdb->update(
            $this->popups_table,
            $data,
            array('id' => $popup_id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            error_log('DCF Popup Update: Successfully updated popup ' . $popup_id);
        } else {
            error_log('DCF Popup Update: Failed to update popup ' . $popup_id . ' - ' . $wpdb->last_error);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete popup
     */
    public function delete_popup($popup_id) {
        global $wpdb;
        
        // Delete related data first
        $wpdb->delete($this->popup_displays_table, array('popup_id' => $popup_id), array('%d'));
        $wpdb->delete($this->popup_interactions_table, array('popup_id' => $popup_id), array('%d'));
        $wpdb->delete($this->popup_frequency_table, array('popup_id' => $popup_id), array('%d'));
        
        // Delete the popup
        $result = $wpdb->delete(
            $this->popups_table,
            array('id' => $popup_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get all popups
     */
    public function get_popups($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'all',
            'type' => 'all',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if ($args['status'] !== 'all') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['type'] !== 'all') {
            $where_clauses[] = 'popup_type = %s';
            $where_values[] = $args['type'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($args['orderby']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        
        $sql = "SELECT * FROM {$this->popups_table} {$where_sql} {$order_sql} {$limit_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $popups = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON fields for each popup
        foreach ($popups as &$popup) {
            $popup['popup_config'] = json_decode($popup['popup_config'], true);
            $popup['targeting_rules'] = json_decode($popup['targeting_rules'], true);
            $popup['trigger_settings'] = json_decode($popup['trigger_settings'], true);
            $popup['design_settings'] = json_decode($popup['design_settings'], true);
        }
        
        return $popups;
    }
    
    /**
     * Get popup templates
     */
    public function get_templates($category = 'all') {
        global $wpdb;
        
        $where_sql = '';
        $where_values = array();
        
        if ($category !== 'all') {
            $where_sql = 'WHERE category = %s';
            $where_values[] = $category;
        }
        
        $sql = "SELECT * FROM {$this->popup_templates_table} {$where_sql} ORDER BY rating DESC, download_count DESC";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $templates = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON config for each template
        foreach ($templates as &$template) {
            $template['template_config'] = json_decode($template['template_config'], true);
            $template['tags'] = !empty($template['tags']) ? explode(',', $template['tags']) : array();
        }
        
        return $templates;
    }
    
    /**
     * Track popup display
     */
    public function track_display($popup_id, $user_data = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'action_taken' => 'displayed'
        );
        
        $data = wp_parse_args($user_data, $defaults);
        $data['popup_id'] = intval($popup_id);
        
        $result = $wpdb->insert(
            $this->popup_displays_table,
            $data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Update frequency tracking
        $this->update_frequency_tracking($popup_id, $data['user_id'] ?: $data['session_id'], 'display');
        
        return $result !== false;
    }
    
    /**
     * Track popup interaction
     */
    public function track_interaction($popup_id, $interaction_type, $interaction_data = array()) {
        global $wpdb;
        
        $data = array(
            'popup_id' => intval($popup_id),
            'user_identifier' => get_current_user_id() ?: $this->get_session_id(),
            'interaction_type' => sanitize_text_field($interaction_type),
            'interaction_data' => json_encode($interaction_data),
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'session_id' => $this->get_session_id()
        );
        
        $result = $wpdb->insert(
            $this->popup_interactions_table,
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Update frequency tracking for conversions
        if ($interaction_type === 'converted') {
            $this->update_frequency_tracking($popup_id, $data['user_identifier'], 'conversion');
        } elseif ($interaction_type === 'dismissed') {
            $this->update_frequency_tracking($popup_id, $data['user_identifier'], 'dismissal');
        }
        
        return $result !== false;
    }
    
    /**
     * Update frequency tracking
     */
    private function update_frequency_tracking($popup_id, $user_identifier, $action) {
        global $wpdb;
        
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->popup_frequency_table} WHERE popup_id = %d AND user_identifier = %s",
                $popup_id,
                $user_identifier
            ),
            ARRAY_A
        );
        
        if ($existing) {
            $update_data = array();
            
            if ($action === 'display') {
                $update_data['display_count'] = $existing['display_count'] + 1;
                $update_data['last_displayed'] = current_time('mysql');
            } elseif ($action === 'dismissal') {
                $update_data['last_dismissed'] = current_time('mysql');
            } elseif ($action === 'conversion') {
                $update_data['last_converted'] = current_time('mysql');
            }
            
            $wpdb->update(
                $this->popup_frequency_table,
                $update_data,
                array('id' => $existing['id']),
                null,
                array('%d')
            );
        } else {
            $insert_data = array(
                'popup_id' => $popup_id,
                'user_identifier' => $user_identifier,
                'display_count' => $action === 'display' ? 1 : 0,
                'last_displayed' => $action === 'display' ? current_time('mysql') : null,
                'last_dismissed' => $action === 'dismissal' ? current_time('mysql') : null,
                'last_converted' => $action === 'conversion' ? current_time('mysql') : null
            );
            
            $wpdb->insert(
                $this->popup_frequency_table,
                $insert_data,
                array('%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Check if popup should be displayed based on frequency rules
     */
    public function should_display_popup($popup_id, $user_identifier = null) {
        global $wpdb;
        
        if (!$user_identifier) {
            $user_identifier = get_current_user_id() ?: $this->get_session_id();
        }
        
        $frequency_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->popup_frequency_table} WHERE popup_id = %d AND user_identifier = %s",
                $popup_id,
                $user_identifier
            ),
            ARRAY_A
        );
        
        if (!$frequency_data) {
            return true; // First time user, show popup
        }
        
        // Check cooldown period
        if ($frequency_data['cooldown_until'] && strtotime($frequency_data['cooldown_until']) > time()) {
            return false;
        }
        
        // Check if user already converted
        if ($frequency_data['last_converted']) {
            return false; // Don't show to converted users
        }
        
        // Get popup settings to check frequency limits
        $popup = $this->get_popup($popup_id);
        if (!$popup) {
            return false;
        }
        
        $trigger_settings = $popup['trigger_settings'] ?: array();
        $max_displays = $trigger_settings['max_displays'] ?? 3;
        
        if ($frequency_data['display_count'] >= $max_displays) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_requests() {
        // Verify nonce for admin requests
        if (is_admin() && !wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $action = sanitize_text_field($_POST['dcf_popup_action'] ?? '');
        
        switch ($action) {
            case 'track_display':
                $this->ajax_track_display();
                break;
            case 'track_interaction':
                $this->ajax_track_interaction();
                break;
            case 'get_popup':
                $this->ajax_get_popup();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * AJAX handler for tracking display
     */
    private function ajax_track_display() {
        $popup_id = intval($_POST['popup_id'] ?? 0);
        
        if (!$popup_id) {
            wp_send_json_error('Invalid popup ID');
        }
        
        $result = $this->track_display($popup_id);
        
        if ($result) {
            wp_send_json_success('Display tracked');
        } else {
            wp_send_json_error('Failed to track display');
        }
    }
    
    /**
     * AJAX handler for tracking interaction
     */
    private function ajax_track_interaction() {
        $popup_id = intval($_POST['popup_id'] ?? 0);
        $interaction_type = sanitize_text_field($_POST['interaction_type'] ?? '');
        $interaction_data = $_POST['interaction_data'] ?? array();
        
        if (!$popup_id || !$interaction_type) {
            wp_send_json_error('Missing required parameters');
        }
        
        $result = $this->track_interaction($popup_id, $interaction_type, $interaction_data);
        
        if ($result) {
            wp_send_json_success('Interaction tracked');
        } else {
            wp_send_json_error('Failed to track interaction');
        }
    }
    
    /**
     * AJAX handler for getting popup data
     */
    private function ajax_get_popup() {
        $popup_id = intval($_POST['popup_id'] ?? 0);
        
        if (!$popup_id) {
            wp_send_json_error('Invalid popup ID');
        }
        
        $popup = $this->get_popup($popup_id);
        
        if ($popup) {
            wp_send_json_success($popup);
        } else {
            wp_send_json_error('Popup not found');
        }
    }

    /**
     * Get active popups for current page
     *
     * @return array
     */
    public function get_active_popups_for_page() {
        global $wpdb;
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->popups_table}'") === $this->popups_table;
        if (!$table_exists) {
            // Return empty array if table doesn't exist yet
            return array();
        }
        
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $current_page_id = get_queried_object_id();
        
        // Get all active popups
        $popups = $wpdb->get_results(
            "SELECT * FROM {$this->popups_table} WHERE status = 'active'",
            ARRAY_A
        );
        
        $active_popups = array();
        
        foreach ($popups as $popup) {
            // Decode JSON fields
            $popup['popup_config'] = json_decode($popup['popup_config'], true);
            $popup['targeting_rules'] = json_decode($popup['targeting_rules'], true);
            $popup['trigger_settings'] = json_decode($popup['trigger_settings'], true);
            $popup['design_settings'] = json_decode($popup['design_settings'], true);
            
            // Check if popup should be displayed on this page
            if ($this->should_display_on_page($popup, $current_url, $current_page_id)) {
                $active_popups[] = $popup;
            }
        }
        
        return $active_popups;
    }

    /**
     * Check if popup should be displayed on current page
     *
     * @param array $popup
     * @param string $current_url
     * @param int $current_page_id
     * @return bool
     */
    private function should_display_on_page($popup, $current_url, $current_page_id) {
        $targeting_rules = $popup['targeting_rules'] ?? array();
        $pages_rules = $targeting_rules['pages'] ?? array();
        
        // If no page targeting rules, show on all pages
        if (empty($pages_rules) || ($pages_rules['mode'] ?? 'all') === 'all') {
            return true;
        }
        
        // Check specific page targeting
        if ($pages_rules['mode'] === 'specific') {
            $include_rules = $pages_rules['include'] ?? array();
            $exclude_rules = $pages_rules['exclude'] ?? array();
            
            // Check exclude rules first
            if (!empty($exclude_rules)) {
                foreach ($exclude_rules as $rule) {
                    if ($this->matches_page_rule($rule, $current_url, $current_page_id)) {
                        return false; // Excluded
                    }
                }
            }
            
            // Check include rules
            if (!empty($include_rules)) {
                foreach ($include_rules as $rule) {
                    if ($this->matches_page_rule($rule, $current_url, $current_page_id)) {
                        return true; // Included
                    }
                }
                return false; // Not in include list
            }
        }
        
        return true;
    }

    /**
     * Check if current page matches a targeting rule
     *
     * @param array $rule
     * @param string $current_url
     * @param int $current_page_id
     * @return bool
     */
    private function matches_page_rule($rule, $current_url, $current_page_id) {
        $rule_type = $rule['type'] ?? '';
        $rule_value = $rule['value'] ?? '';
        
        switch ($rule_type) {
            case 'page_id':
                return $current_page_id == intval($rule_value);
                
            case 'url_contains':
                return strpos($current_url, $rule_value) !== false;
                
            case 'url_exact':
                return $current_url === $rule_value;
                
            case 'post_type':
                return get_post_type($current_page_id) === $rule_value;
                
            case 'homepage':
                return is_front_page();
                
            case 'blog':
                return is_home();
                
            default:
                return false;
        }
    }

    /**
     * Get popup analytics data
     *
     * @param int $popup_id
     * @param string $date_range
     * @return array
     */
    public function get_popup_analytics($popup_id, $date_range = '30') {
        // Use the conversion analytics class for accurate data
        $analytics = new DCF_Popup_Conversion_Analytics();
        return $analytics->calculate_conversion_metrics($popup_id, $date_range);
    }
}

// Initialize the popup manager
new DCF_Popup_Manager(); 