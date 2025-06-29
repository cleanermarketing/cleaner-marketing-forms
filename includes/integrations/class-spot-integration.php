<?php
/**
 * SPOT Integration
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SPOT Integration class
 */
class DCF_SPOT_Integration {
    
    /**
     * Integration name
     */
    private $name = 'SPOT';
    
    /**
     * API Base URL
     */
    private $api_base_url = 'https://api.spotpos.com/v1/';
    
    /**
     * Username
     */
    private $username;
    
    /**
     * License Key
     */
    private $license_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->username = DCF_Plugin_Core::decrypt(DCF_Plugin_Core::get_setting('spot_username'));
        $this->license_key = DCF_Plugin_Core::decrypt(DCF_Plugin_Core::get_setting('spot_license_key'));
    }
    
    /**
     * Get integration name
     *
     * @return string Integration name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Check if integration is configured
     *
     * @return bool True if configured
     */
    public function is_configured() {
        return !empty($this->username) && !empty($this->license_key);
    }
    
    /**
     * Test connection to SPOT API
     *
     * @return array|WP_Error Test result or error
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('SPOT integration is not configured', 'dry-cleaning-forms'));
        }
        
        $response = $this->make_api_request('auth/test', 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'success' => true,
            'message' => __('Successfully connected to SPOT API', 'dry-cleaning-forms')
        );
    }
    
    /**
     * Check if customer exists
     *
     * @param string $email Customer email
     * @param string $phone Customer phone
     * @return array|WP_Error Customer data or error
     */
    public function customer_exists($email, $phone = '') {
        $params = array(
            'email' => $email
        );
        
        if (!empty($phone)) {
            $params['phone'] = $phone;
        }
        
        $response = $this->make_api_request('customers/search', 'GET', $params);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'customer_exists', $params, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['customers']) && !empty($response['customers'])) {
            $customer = $response['customers'][0];
            DCF_Plugin_Core::log_integration('spot', 'customer_exists', $params, $customer, 'success');
            return array(
                'exists' => true,
                'customer' => $customer
            );
        }
        
        DCF_Plugin_Core::log_integration('spot', 'customer_exists', $params, array('exists' => false), 'success');
        return array('exists' => false);
    }
    
    /**
     * Create customer
     *
     * @param array $customer_data Customer data
     * @return array|WP_Error Customer data or error
     */
    public function create_customer($customer_data) {
        $data = array(
            'email' => $customer_data['email'],
            'phone' => $customer_data['phone'],
            'first_name' => $customer_data['first_name'],
            'last_name' => $customer_data['last_name']
        );
        
        $response = $this->make_api_request('customers', 'POST', $data);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'create_customer', $data, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['customer'])) {
            DCF_Plugin_Core::log_integration('spot', 'create_customer', $data, $response['customer'], 'success');
            return $response['customer'];
        }
        
        DCF_Plugin_Core::log_integration('spot', 'create_customer', $data, $response, 'error');
        return new WP_Error('create_customer_failed', __('Unknown error creating customer', 'dry-cleaning-forms'));
    }
    
    /**
     * Update customer
     *
     * @param string $customer_id Customer ID
     * @param array $customer_data Customer data
     * @return array|WP_Error Updated customer data or error
     */
    public function update_customer($customer_id, $customer_data) {
        $response = $this->make_api_request('customers/' . $customer_id, 'PUT', $customer_data);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'update_customer', array('id' => $customer_id, 'data' => $customer_data), array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        // Add address if provided
        if (isset($customer_data['address'])) {
            $address_response = $this->make_api_request('customers/' . $customer_id . '/addresses', 'POST', $customer_data['address']);
            
            if (is_wp_error($address_response)) {
                DCF_Plugin_Core::log_integration('spot', 'add_customer_address', array('customer_id' => $customer_id, 'address' => $customer_data['address']), array('error' => $address_response->get_error_message()), 'error');
                return $address_response;
            }
        }
        
        if (isset($response['customer'])) {
            DCF_Plugin_Core::log_integration('spot', 'update_customer', array('id' => $customer_id, 'data' => $customer_data), $response['customer'], 'success');
            return $response['customer'];
        }
        
        DCF_Plugin_Core::log_integration('spot', 'update_customer', array('id' => $customer_id, 'data' => $customer_data), $response, 'error');
        return new WP_Error('update_customer_failed', __('Unknown error updating customer', 'dry-cleaning-forms'));
    }
    
    /**
     * Get available pickup dates
     *
     * @param string $customer_id Customer ID
     * @param array $address Customer address
     * @return array|WP_Error Available dates or error
     */
    public function get_pickup_dates($customer_id, $address) {
        $params = array(
            'customer_id' => $customer_id,
            'address' => $address
        );
        
        $response = $this->make_api_request('pickup/available-dates', 'GET', $params);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'get_pickup_dates', $params, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['available_dates'])) {
            DCF_Plugin_Core::log_integration('spot', 'get_pickup_dates', $params, $response['available_dates'], 'success');
            return $response['available_dates'];
        }
        
        DCF_Plugin_Core::log_integration('spot', 'get_pickup_dates', $params, $response, 'error');
        return new WP_Error('get_pickup_dates_failed', __('Unknown error getting pickup dates', 'dry-cleaning-forms'));
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
        $data = array(
            'customer_id' => $customer_id,
            'pickup_date' => $pickup_date,
            'time_slot' => $appointment_data['time_slot'],
            'address' => $appointment_data['address'],
            'notes' => isset($appointment_data['notes']) ? $appointment_data['notes'] : ''
        );
        
        $response = $this->make_api_request('pickup/schedule', 'POST', $data);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'schedule_pickup', $data, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['appointment'])) {
            DCF_Plugin_Core::log_integration('spot', 'schedule_pickup', $data, $response['appointment'], 'success');
            return $response['appointment'];
        }
        
        DCF_Plugin_Core::log_integration('spot', 'schedule_pickup', $data, $response, 'error');
        return new WP_Error('schedule_pickup_failed', __('Unknown error scheduling pickup', 'dry-cleaning-forms'));
    }
    
    /**
     * Process payment
     *
     * @param string $customer_id Customer ID
     * @param array $payment_data Payment data
     * @return array|WP_Error Payment result or error
     */
    public function process_payment($customer_id, $payment_data) {
        $data = array(
            'customer_id' => $customer_id,
            'amount' => $payment_data['amount'],
            'currency' => isset($payment_data['currency']) ? $payment_data['currency'] : 'USD',
            'payment_method' => array(
                'type' => 'card',
                'card_number' => $payment_data['card_number'],
                'expiry_month' => $payment_data['expiry_month'],
                'expiry_year' => $payment_data['expiry_year'],
                'security_code' => $payment_data['security_code'],
                'billing_zip' => $payment_data['billing_zip']
            )
        );
        
        $response = $this->make_api_request('payments/process', 'POST', $data);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('spot', 'process_payment', array('customer_id' => $customer_id, 'amount' => $payment_data['amount']), array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['payment'])) {
            DCF_Plugin_Core::log_integration('spot', 'process_payment', array('customer_id' => $customer_id, 'amount' => $payment_data['amount']), $response['payment'], 'success');
            return $response['payment'];
        }
        
        DCF_Plugin_Core::log_integration('spot', 'process_payment', array('customer_id' => $customer_id, 'amount' => $payment_data['amount']), $response, 'error');
        return new WP_Error('process_payment_failed', __('Unknown error processing payment', 'dry-cleaning-forms'));
    }
    
    /**
     * Make API request to SPOT
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    private function make_api_request($endpoint, $method = 'GET', $data = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('SPOT integration is not configured', 'dry-cleaning-forms'));
        }
        
        $url = $this->api_base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->license_key),
                'User-Agent' => 'DryCleaningForms/' . CMF_PLUGIN_VERSION
            ),
            'timeout' => 30
        );
        
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        } elseif (in_array($method, array('POST', 'PUT', 'PATCH')) && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code < 200 || $response_code >= 300) {
            return new WP_Error('api_error', sprintf(__('SPOT API returned status %d: %s', 'dry-cleaning-forms'), $response_code, $response_body));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response from SPOT API', 'dry-cleaning-forms'));
        }
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']);
        }
        
        return $data;
    }
} 