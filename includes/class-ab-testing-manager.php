<?php
/**
 * A/B Testing Manager Class
 *
 * Handles A/B testing functionality for popups including creating tests,
 * managing variants, tracking performance, and statistical analysis
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_AB_Testing_Manager {
    
    /**
     * Database table names
     */
    private $ab_tests_table;
    private $popups_table;
    private $popup_displays_table;
    private $popup_interactions_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->ab_tests_table = $wpdb->prefix . 'dcf_ab_tests';
        $this->popups_table = $wpdb->prefix . 'dcf_popups';
        $this->popup_displays_table = $wpdb->prefix . 'dcf_popup_displays';
        $this->popup_interactions_table = $wpdb->prefix . 'dcf_popup_interactions';
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_dcf_ab_test_action', array($this, 'handle_ajax_requests'));
        add_action('wp_ajax_dcf_get_ab_variant', array($this, 'ajax_get_ab_variant'));
        add_action('wp_ajax_nopriv_dcf_get_ab_variant', array($this, 'ajax_get_ab_variant'));
        
        // Hook into popup display to handle A/B testing
        add_filter('dcf_popup_before_display', array($this, 'select_ab_variant'), 10, 2);
        
        // Daily cron to check for test completion
        add_action('dcf_check_ab_tests', array($this, 'check_test_completion'));
        if (!wp_next_scheduled('dcf_check_ab_tests')) {
            wp_schedule_event(time(), 'daily', 'dcf_check_ab_tests');
        }
    }
    
    /**
     * Create a new A/B test
     *
     * @param array $test_data Test configuration data
     * @return int|false Test ID on success, false on failure
     */
    public function create_ab_test($test_data) {
        global $wpdb;
        
        $defaults = array(
            'test_name' => '',
            'popup_ids' => array(),
            'traffic_split' => array(),
            'start_date' => current_time('mysql'),
            'end_date' => null,
            'status' => 'draft',
            'test_type' => 'conversion', // conversion, engagement, click_through
            'minimum_sample_size' => 100,
            'confidence_level' => 95.0,
            'auto_declare_winner' => true
        );
        
        $test_data = wp_parse_args($test_data, $defaults);
        
        // Validate required fields
        if (empty($test_data['test_name']) || empty($test_data['popup_ids'])) {
            return false;
        }
        
        // Ensure traffic split adds up to 100%
        $total_split = array_sum($test_data['traffic_split']);
        if ($total_split != 100) {
            return false;
        }
        
        $insert_data = array(
            'test_name' => sanitize_text_field($test_data['test_name']),
            'popup_ids' => json_encode($test_data['popup_ids']),
            'traffic_split' => json_encode($test_data['traffic_split']),
            'start_date' => $test_data['start_date'],
            'end_date' => $test_data['end_date'],
            'status' => $test_data['status'],
            'test_config' => json_encode(array(
                'test_type' => $test_data['test_type'],
                'minimum_sample_size' => $test_data['minimum_sample_size'],
                'confidence_level' => $test_data['confidence_level'],
                'auto_declare_winner' => $test_data['auto_declare_winner']
            )),
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->ab_tests_table, $insert_data);
        
        if ($result) {
            $test_id = $wpdb->insert_id;
            
            // Update popup records to link them to this test
            $this->link_popups_to_test($test_id, $test_data['popup_ids']);
            
            return $test_id;
        }
        
        return false;
    }
    
    /**
     * Get A/B test by ID
     *
     * @param int $test_id
     * @return array|null
     */
    public function get_ab_test($test_id) {
        global $wpdb;
        
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->ab_tests_table} WHERE id = %d",
            $test_id
        ), ARRAY_A);
        
        if ($test) {
            $test['popup_ids'] = json_decode($test['popup_ids'], true);
            $test['traffic_split'] = json_decode($test['traffic_split'], true);
            $test['test_config'] = json_decode($test['test_config'], true);
            
            // Get variant performance data
            $test['variants'] = $this->get_test_variants_performance($test_id);
        }
        
        return $test;
    }
    
    /**
     * Update A/B test
     *
     * @param int $test_id
     * @param array $test_data
     * @return bool
     */
    public function update_ab_test($test_id, $test_data) {
        global $wpdb;
        
        $update_data = array();
        
        if (isset($test_data['test_name'])) {
            $update_data['test_name'] = sanitize_text_field($test_data['test_name']);
        }
        
        if (isset($test_data['popup_ids'])) {
            $update_data['popup_ids'] = json_encode($test_data['popup_ids']);
            $this->link_popups_to_test($test_id, $test_data['popup_ids']);
        }
        
        if (isset($test_data['traffic_split'])) {
            $update_data['traffic_split'] = json_encode($test_data['traffic_split']);
        }
        
        if (isset($test_data['start_date'])) {
            $update_data['start_date'] = $test_data['start_date'];
        }
        
        if (isset($test_data['end_date'])) {
            $update_data['end_date'] = $test_data['end_date'];
        }
        
        if (isset($test_data['status'])) {
            $update_data['status'] = $test_data['status'];
        }
        
        if (isset($test_data['winner_id'])) {
            $update_data['winner_id'] = intval($test_data['winner_id']);
        }
        
        if (isset($test_data['confidence_level'])) {
            $update_data['confidence_level'] = floatval($test_data['confidence_level']);
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = current_time('mysql');
            
            return $wpdb->update(
                $this->ab_tests_table,
                $update_data,
                array('id' => $test_id)
            ) !== false;
        }
        
        return false;
    }
    
    /**
     * Delete A/B test
     *
     * @param int $test_id
     * @return bool
     */
    public function delete_ab_test($test_id) {
        global $wpdb;
        
        // Unlink popups from this test
        $wpdb->update(
            $this->popups_table,
            array('ab_test_id' => null),
            array('ab_test_id' => $test_id)
        );
        
        // Delete the test
        return $wpdb->delete(
            $this->ab_tests_table,
            array('id' => $test_id)
        ) !== false;
    }
    
    /**
     * Get all A/B tests
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_ab_tests($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->ab_tests_table} 
             WHERE {$where_clause} 
             ORDER BY {$args['orderby']} {$args['order']} 
             LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['limit'], $args['offset']))
        );
        
        $tests = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($tests as &$test) {
            $test['popup_ids'] = json_decode($test['popup_ids'], true);
            $test['traffic_split'] = json_decode($test['traffic_split'], true);
            $test['test_config'] = json_decode($test['test_config'], true);
        }
        
        return $tests;
    }
    
    /**
     * Select A/B variant for display
     *
     * @param array $popup_data
     * @param array $user_data
     * @return array Modified popup data
     */
    public function select_ab_variant($popup_data, $user_data = array()) {
        // Check if popup is part of an A/B test
        if (empty($popup_data['ab_test_id'])) {
            return $popup_data;
        }
        
        $test = $this->get_ab_test($popup_data['ab_test_id']);
        
        if (!$test || $test['status'] !== 'active') {
            return $popup_data;
        }
        
        // Get user identifier for consistent variant assignment
        $user_identifier = $this->get_user_identifier($user_data);
        
        // Check if user has already been assigned a variant
        $assigned_variant = $this->get_user_variant_assignment($test['id'], $user_identifier);
        
        if ($assigned_variant) {
            $variant_popup_id = $assigned_variant['popup_id'];
        } else {
            // Assign new variant based on traffic split
            $variant_popup_id = $this->assign_variant($test, $user_identifier);
        }
        
        // Get the variant popup data
        $popup_manager = new DCF_Popup_Manager();
        $variant_popup = $popup_manager->get_popup($variant_popup_id);
        
        if ($variant_popup) {
            // Track the assignment
            $this->track_variant_assignment($test['id'], $variant_popup_id, $user_identifier);
            return $variant_popup;
        }
        
        return $popup_data;
    }
    
    /**
     * Assign variant to user based on traffic split
     *
     * @param array $test
     * @param string $user_identifier
     * @return int Popup ID of assigned variant
     */
    private function assign_variant($test, $user_identifier) {
        $popup_ids = $test['popup_ids'];
        $traffic_split = $test['traffic_split'];
        
        // Use user identifier to generate consistent random number
        $hash = md5($test['id'] . $user_identifier);
        $random = hexdec(substr($hash, 0, 8)) / 0xffffffff * 100;
        
        $cumulative = 0;
        foreach ($popup_ids as $index => $popup_id) {
            $cumulative += $traffic_split[$index];
            if ($random <= $cumulative) {
                return $popup_id;
            }
        }
        
        // Fallback to first variant
        return $popup_ids[0];
    }
    
    /**
     * Track variant assignment
     *
     * @param int $test_id
     * @param int $popup_id
     * @param string $user_identifier
     */
    private function track_variant_assignment($test_id, $popup_id, $user_identifier) {
        global $wpdb;
        
        $assignment_table = $wpdb->prefix . 'dcf_ab_assignments';
        
        // Create assignments table if it doesn't exist
        $this->create_assignments_table();
        
        $wpdb->replace(
            $assignment_table,
            array(
                'test_id' => $test_id,
                'popup_id' => $popup_id,
                'user_identifier' => $user_identifier,
                'assigned_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get user's variant assignment
     *
     * @param int $test_id
     * @param string $user_identifier
     * @return array|null
     */
    private function get_user_variant_assignment($test_id, $user_identifier) {
        global $wpdb;
        
        $assignment_table = $wpdb->prefix . 'dcf_ab_assignments';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$assignment_table} 
             WHERE test_id = %d AND user_identifier = %s",
            $test_id, $user_identifier
        ), ARRAY_A);
    }
    
    /**
     * Get test variants performance data
     *
     * @param int $test_id
     * @return array
     */
    public function get_test_variants_performance($test_id) {
        global $wpdb;
        
        $test = $this->get_ab_test($test_id);
        if (!$test) {
            return array();
        }
        
        $variants = array();
        
        foreach ($test['popup_ids'] as $index => $popup_id) {
            // Get popup details
            $popup = $wpdb->get_row($wpdb->prepare(
                "SELECT popup_name FROM {$this->popups_table} WHERE id = %d",
                $popup_id
            ), ARRAY_A);
            
            // Get performance metrics
            $displays = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->popup_displays_table} 
                 WHERE popup_id = %d AND action_taken = 'displayed'",
                $popup_id
            ));
            
            $interactions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->popup_interactions_table} 
                 WHERE popup_id = %d AND interaction_type = 'clicked'",
                $popup_id
            ));
            
            $conversions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->popup_displays_table} 
                 WHERE popup_id = %d AND action_taken = 'converted'",
                $popup_id
            ));
            
            $conversion_rate = $displays > 0 ? ($conversions / $displays) * 100 : 0;
            $interaction_rate = $displays > 0 ? ($interactions / $displays) * 100 : 0;
            
            $variants[] = array(
                'popup_id' => $popup_id,
                'popup_name' => $popup['popup_name'] ?? 'Variant ' . ($index + 1),
                'traffic_split' => $test['traffic_split'][$index],
                'displays' => intval($displays),
                'interactions' => intval($interactions),
                'conversions' => intval($conversions),
                'conversion_rate' => round($conversion_rate, 2),
                'interaction_rate' => round($interaction_rate, 2),
                'is_winner' => $test['winner_id'] == $popup_id
            );
        }
        
        return $variants;
    }
    
    /**
     * Calculate statistical significance
     *
     * @param array $variant_a
     * @param array $variant_b
     * @return array Statistical analysis results
     */
    public function calculate_statistical_significance($variant_a, $variant_b) {
        $n1 = $variant_a['displays'];
        $n2 = $variant_b['displays'];
        $x1 = $variant_a['conversions'];
        $x2 = $variant_b['conversions'];
        
        if ($n1 == 0 || $n2 == 0) {
            return array(
                'significant' => false,
                'confidence' => 0,
                'p_value' => 1,
                'message' => 'Insufficient data for statistical analysis'
            );
        }
        
        $p1 = $x1 / $n1;
        $p2 = $x2 / $n2;
        $p_pooled = ($x1 + $x2) / ($n1 + $n2);
        
        $se = sqrt($p_pooled * (1 - $p_pooled) * (1/$n1 + 1/$n2));
        
        if ($se == 0) {
            return array(
                'significant' => false,
                'confidence' => 0,
                'p_value' => 1,
                'message' => 'No variation in conversion rates'
            );
        }
        
        $z_score = ($p1 - $p2) / $se;
        $p_value = 2 * (1 - $this->normal_cdf(abs($z_score)));
        
        $confidence = (1 - $p_value) * 100;
        $significant = $p_value < 0.05; // 95% confidence level
        
        $improvement = (($p1 - $p2) / $p2) * 100;
        
        return array(
            'significant' => $significant,
            'confidence' => round($confidence, 2),
            'p_value' => round($p_value, 4),
            'z_score' => round($z_score, 4),
            'improvement' => round($improvement, 2),
            'message' => $this->get_significance_message($significant, $confidence, $improvement)
        );
    }
    
    /**
     * Check for test completion and auto-declare winners
     */
    public function check_test_completion() {
        $active_tests = $this->get_ab_tests(array('status' => 'active'));
        
        foreach ($active_tests as $test) {
            $test_config = $test['test_config'] ?: array();
            
            // Check if test has reached end date
            if (!empty($test['end_date']) && strtotime($test['end_date']) <= time()) {
                $this->complete_test($test['id']);
                continue;
            }
            
            // Check if auto-declare winner is enabled
            if (empty($test_config['auto_declare_winner'])) {
                continue;
            }
            
            // Check if minimum sample size is reached
            $variants = $this->get_test_variants_performance($test['id']);
            $min_sample_size = $test_config['minimum_sample_size'] ?? 100;
            
            $sufficient_data = true;
            foreach ($variants as $variant) {
                if ($variant['displays'] < $min_sample_size) {
                    $sufficient_data = false;
                    break;
                }
            }
            
            if (!$sufficient_data) {
                continue;
            }
            
            // Find the best performing variant
            $best_variant = null;
            $best_rate = 0;
            
            foreach ($variants as $variant) {
                if ($variant['conversion_rate'] > $best_rate) {
                    $best_rate = $variant['conversion_rate'];
                    $best_variant = $variant;
                }
            }
            
            if ($best_variant) {
                // Check statistical significance against other variants
                $is_significant = false;
                $confidence_level = $test_config['confidence_level'] ?? 95.0;
                
                foreach ($variants as $variant) {
                    if ($variant['popup_id'] != $best_variant['popup_id']) {
                        $stats = $this->calculate_statistical_significance($best_variant, $variant);
                        if ($stats['significant'] && $stats['confidence'] >= $confidence_level) {
                            $is_significant = true;
                            break;
                        }
                    }
                }
                
                if ($is_significant) {
                    $this->declare_winner($test['id'], $best_variant['popup_id']);
                }
            }
        }
    }
    
    /**
     * Declare test winner
     *
     * @param int $test_id
     * @param int $winner_popup_id
     */
    public function declare_winner($test_id, $winner_popup_id) {
        $this->update_ab_test($test_id, array(
            'winner_id' => $winner_popup_id,
            'status' => 'completed'
        ));
        
        // Optionally pause other variants
        $test = $this->get_ab_test($test_id);
        foreach ($test['popup_ids'] as $popup_id) {
            if ($popup_id != $winner_popup_id) {
                $popup_manager = new DCF_Popup_Manager();
                $popup_manager->update_popup($popup_id, array('status' => 'paused'));
            }
        }
    }
    
    /**
     * Complete test without declaring winner
     *
     * @param int $test_id
     */
    public function complete_test($test_id) {
        $this->update_ab_test($test_id, array('status' => 'completed'));
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_requests() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $action = $_POST['ab_action'] ?? '';
        
        switch ($action) {
            case 'create_test':
                $this->ajax_create_test();
                break;
            case 'update_test':
                $this->ajax_update_test();
                break;
            case 'delete_test':
                $this->ajax_delete_test();
                break;
            case 'get_test_performance':
                $this->ajax_get_test_performance();
                break;
            case 'declare_winner':
                $this->ajax_declare_winner();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * AJAX: Get A/B variant for user
     */
    public function ajax_get_ab_variant() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_popup_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $popup_id = intval($_POST['popup_id'] ?? 0);
        $user_data = $_POST['user_data'] ?? array();
        
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_manager->get_popup($popup_id);
        
        if (!$popup) {
            wp_send_json_error('Popup not found');
        }
        
        $variant_popup = $this->select_ab_variant($popup, $user_data);
        wp_send_json_success($variant_popup);
    }
    
    // Additional helper methods...
    
    /**
     * Create assignments table
     */
    private function create_assignments_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dcf_ab_assignments';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            test_id int(11) NOT NULL,
            popup_id int(11) NOT NULL,
            user_identifier varchar(255) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY test_user (test_id, user_identifier),
            KEY popup_id (popup_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Link popups to A/B test
     */
    private function link_popups_to_test($test_id, $popup_ids) {
        global $wpdb;
        
        foreach ($popup_ids as $popup_id) {
            $wpdb->update(
                $this->popups_table,
                array('ab_test_id' => $test_id),
                array('id' => $popup_id)
            );
        }
    }
    
    /**
     * Get user identifier for consistent variant assignment
     */
    private function get_user_identifier($user_data = array()) {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        // Use session ID or IP address for anonymous users
        $session_id = session_id();
        if ($session_id) {
            return 'session_' . $session_id;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return 'ip_' . md5($ip . $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    /**
     * Normal cumulative distribution function approximation
     */
    private function normal_cdf($x) {
        return 0.5 * (1 + $this->erf($x / sqrt(2)));
    }
    
    /**
     * Error function approximation
     */
    private function erf($x) {
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;
        
        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);
        
        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);
        
        return $sign * $y;
    }
    
    /**
     * Get significance message
     */
    private function get_significance_message($significant, $confidence, $improvement) {
        if (!$significant) {
            return 'No statistically significant difference detected.';
        }
        
        $direction = $improvement > 0 ? 'improvement' : 'decrease';
        return sprintf(
            'Statistically significant %s of %.2f%% with %.2f%% confidence.',
            $direction,
            abs($improvement),
            $confidence
        );
    }
    
    // AJAX handler methods
    private function ajax_create_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_data = $_POST['test_data'] ?? array();
        $test_id = $this->create_ab_test($test_data);
        
        if ($test_id) {
            wp_send_json_success(array('test_id' => $test_id));
        } else {
            wp_send_json_error('Failed to create test');
        }
    }
    
    private function ajax_update_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        $test_data = $_POST['test_data'] ?? array();
        
        if ($this->update_ab_test($test_id, $test_data)) {
            wp_send_json_success('Test updated successfully');
        } else {
            wp_send_json_error('Failed to update test');
        }
    }
    
    private function ajax_delete_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        
        if ($this->delete_ab_test($test_id)) {
            wp_send_json_success('Test deleted successfully');
        } else {
            wp_send_json_error('Failed to delete test');
        }
    }
    
    private function ajax_get_test_performance() {
        $test_id = intval($_POST['test_id'] ?? 0);
        $performance = $this->get_test_variants_performance($test_id);
        wp_send_json_success($performance);
    }
    
    private function ajax_declare_winner() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        $winner_id = intval($_POST['winner_id'] ?? 0);
        
        $this->declare_winner($test_id, $winner_id);
        wp_send_json_success('Winner declared successfully');
    }
}

// Initialize A/B Testing Manager
new DCF_AB_Testing_Manager(); 