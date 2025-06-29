<?php
/**
 * Integrations Manager
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integrations Manager class
 */
class DCF_Integrations_Manager {
    
    /**
     * Available integrations
     */
    private $integrations = array();
    
    /**
     * Current active integration
     */
    private $active_integration = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_integrations();
        $this->set_active_integration();
    }
    
    /**
     * Load all available integrations
     */
    private function load_integrations() {
        $this->integrations = array(
            'smrt' => new DCF_SMRT_Integration(),
            'spot' => new DCF_SPOT_Integration(),
            'cleancloud' => new DCF_CleanCloud_Integration()
        );
    }
    
    /**
     * Set active integration based on settings
     */
    private function set_active_integration() {
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!empty($pos_system) && isset($this->integrations[$pos_system])) {
            $this->active_integration = $this->integrations[$pos_system];
        }
    }
    
    /**
     * Get active integration
     *
     * @return object|null Active integration instance
     */
    public function get_active_integration() {
        return $this->active_integration;
    }
    
    /**
     * Get integration by type
     *
     * @param string $type Integration type
     * @return object|null Integration instance
     */
    public function get_integration($type) {
        return isset($this->integrations[$type]) ? $this->integrations[$type] : null;
    }
    
    /**
     * Check if customer exists in POS system
     *
     * @param string $email Customer email
     * @param string $phone Customer phone
     * @return array|WP_Error Customer data or error
     */
    public function customer_exists($email, $phone = '') {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->customer_exists($email, $phone);
    }
    
    /**
     * Create customer in POS system
     *
     * @param array $customer_data Customer data
     * @return array|WP_Error Customer data or error
     */
    public function create_customer($customer_data) {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->create_customer($customer_data);
    }
    
    /**
     * Update customer in POS system
     *
     * @param string $customer_id Customer ID
     * @param array $customer_data Customer data
     * @return array|WP_Error Updated customer data or error
     */
    public function update_customer($customer_id, $customer_data) {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->update_customer($customer_id, $customer_data);
    }
    
    /**
     * Get available pickup dates
     *
     * @param string $customer_id Customer ID
     * @param array $address Customer address
     * @return array|WP_Error Available dates or error
     */
    public function get_pickup_dates($customer_id, $address) {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->get_pickup_dates($customer_id, $address);
    }
    
    /**
     * Schedule pickup appointment
     *
     * @param string $customer_id Customer ID
     * @param string $pickup_date Pickup date
     * @param array $appointment_data Appointment data
     * @return array|WP_Error Appointment data or error
     */
    public function schedule_pickup($customer_id, $pickup_date, $appointment_data) {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->schedule_pickup($customer_id, $pickup_date, $appointment_data);
    }
    
    /**
     * Process payment
     *
     * @param string $customer_id Customer ID
     * @param array $payment_data Payment data
     * @return array|WP_Error Payment result or error
     */
    public function process_payment($customer_id, $payment_data) {
        if (!$this->active_integration) {
            return new WP_Error('no_integration', __('No POS integration configured', 'dry-cleaning-forms'));
        }
        
        return $this->active_integration->process_payment($customer_id, $payment_data);
    }
    
    /**
     * Test integration connection
     *
     * @param string $integration_type Integration type
     * @return array|WP_Error Test result or error
     */
    public function test_connection($integration_type = null) {
        $integration = $integration_type ? $this->get_integration($integration_type) : $this->active_integration;
        
        if (!$integration) {
            return new WP_Error('no_integration', __('Integration not found', 'dry-cleaning-forms'));
        }
        
        return $integration->test_connection();
    }
    

    
    /**
     * Get integration status for all configured integrations
     *
     * @return array Integration statuses
     */
    public function get_integration_statuses() {
        $statuses = array();
        
        foreach ($this->integrations as $type => $integration) {
            $test_result = $integration->test_connection();
            
            $statuses[$type] = array(
                'name' => $integration->get_name(),
                'configured' => $integration->is_configured(),
                'connected' => !is_wp_error($test_result),
                'error' => is_wp_error($test_result) ? $test_result->get_error_message() : null
            );
        }
        
        return $statuses;
    }
    
    /**
     * Get integration status for active POS system only
     *
     * @return array|null Active integration status
     */
    public function get_active_integration_status() {
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (empty($pos_system) || !isset($this->integrations[$pos_system])) {
            return null;
        }
        
        $integration = $this->integrations[$pos_system];
        $test_result = $integration->test_connection();
        
        return array(
            'type' => $pos_system,
            'name' => $integration->get_name(),
            'configured' => $integration->is_configured(),
            'connected' => !is_wp_error($test_result),
            'error' => is_wp_error($test_result) ? $test_result->get_error_message() : null
        );
    }
    
    /**
     * Get available POS systems
     *
     * @return array Available POS systems
     */
    public function get_available_pos_systems() {
        $systems = array();
        
        foreach ($this->integrations as $type => $integration) {
            $systems[$type] = $integration->get_name();
        }
        
        return $systems;
    }
    
    /**
     * Get integration logs
     *
     * @param string $integration_type Integration type (optional)
     * @param int $limit Number of logs to retrieve
     * @return array Integration logs
     */
    public function get_integration_logs($integration_type = null, $limit = 50) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_integration_logs';
        
        $where = '';
        $params = array();
        
        if ($integration_type) {
            $where = 'WHERE integration_type = %s';
            $params[] = $integration_type;
        }
        
        $params[] = $limit;
        
        $query = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    /**
     * Clear integration logs
     *
     * @param string $integration_type Integration type (optional)
     * @param int $days_old Delete logs older than X days (optional)
     * @return bool True on success
     */
    public function clear_integration_logs($integration_type = null, $days_old = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_integration_logs';
        
        $where_conditions = array();
        $params = array();
        
        if ($integration_type) {
            $where_conditions[] = 'integration_type = %s';
            $params[] = $integration_type;
        }
        
        if ($days_old) {
            $where_conditions[] = 'created_at < DATE_SUB(NOW(), INTERVAL %d DAY)';
            $params[] = $days_old;
        }
        
        $where = '';
        if (!empty($where_conditions)) {
            $where = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query = "DELETE FROM $table $where";
        
        if (!empty($params)) {
            return $wpdb->query($wpdb->prepare($query, $params)) !== false;
        } else {
            return $wpdb->query($query) !== false;
        }
    }
} 