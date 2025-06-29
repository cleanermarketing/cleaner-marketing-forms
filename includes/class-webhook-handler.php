<?php
/**
 * Webhook Handler
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Webhook Handler class
 */
class DCF_Webhook_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize webhook handler
     */
    public function init() {
        // Add webhook endpoint
        add_action('wp_ajax_dcf_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_dcf_webhook', array($this, 'handle_webhook'));
        
        // Add custom rewrite rule for webhook endpoint
        add_rewrite_rule(
            '^dcf-webhook/?$',
            'index.php?dcf_webhook=1',
            'top'
        );
        
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_webhook_endpoint'));
    }
    
    /**
     * Add query vars
     *
     * @param array $vars Query vars
     * @return array Modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'dcf_webhook';
        return $vars;
    }
    
    /**
     * Handle webhook endpoint
     */
    public function handle_webhook_endpoint() {
        // Only handle webhook if the query var is explicitly set to 1
        if (get_query_var('dcf_webhook') === '1') {
            $this->handle_webhook();
            exit;
        }
    }
    
    /**
     * Handle incoming webhook
     */
    public function handle_webhook() {
        // Verify request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die('Method not allowed', 'Method Not Allowed', array('response' => 405));
        }
        
        // Get raw POST data
        $raw_data = file_get_contents('php://input');
        $data = json_decode($raw_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die('Invalid JSON', 'Bad Request', array('response' => 400));
        }
        
        // Verify webhook signature if configured
        if (!$this->verify_webhook_signature($raw_data)) {
            wp_die('Invalid signature', 'Unauthorized', array('response' => 401));
        }
        
        // Process webhook data
        $result = $this->process_webhook_data($data);
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message(), 'Internal Server Error', array('response' => 500));
        }
        
        // Send success response
        wp_send_json_success(array(
            'message' => 'Webhook processed successfully',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $raw_data Raw webhook data
     * @return bool True if signature is valid
     */
    private function verify_webhook_signature($raw_data) {
        $webhook_secret = DCF_Plugin_Core::get_setting('webhook_secret');
        
        if (empty($webhook_secret)) {
            return true; // No signature verification if secret not set
        }
        
        $signature = isset($_SERVER['HTTP_X_DCF_SIGNATURE']) ? $_SERVER['HTTP_X_DCF_SIGNATURE'] : '';
        
        if (empty($signature)) {
            return false;
        }
        
        $expected_signature = 'sha256=' . hash_hmac('sha256', $raw_data, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Process webhook data
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function process_webhook_data($data) {
        if (!isset($data['event_type'])) {
            return new WP_Error('missing_event_type', 'Event type is required');
        }
        
        switch ($data['event_type']) {
            case 'form.submitted':
                return $this->handle_form_submission($data);
            
            case 'form.step_completed':
                return $this->handle_step_completion($data);
            
            case 'customer.created':
                return $this->handle_customer_created($data);
            
            case 'payment.processed':
                return $this->handle_payment_processed($data);
            
            case 'appointment.scheduled':
                return $this->handle_appointment_scheduled($data);
            
            default:
                return $this->handle_custom_event($data);
        }
    }
    
    /**
     * Handle form submission webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_form_submission($data) {
        if (!isset($data['form_id']) || !isset($data['submission_data'])) {
            return new WP_Error('missing_data', 'Form ID and submission data are required');
        }
        
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'form_submitted', $data, array(), 'success');
        
        // Trigger custom action
        do_action('dcf_form_submitted_webhook', $data);
        
        return true;
    }
    
    /**
     * Handle step completion webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_step_completion($data) {
        if (!isset($data['submission_id']) || !isset($data['step'])) {
            return new WP_Error('missing_data', 'Submission ID and step are required');
        }
        
        // Update submission in database
        $update_data = array(
            'step_completed' => $data['step'],
            'status' => isset($data['status']) ? $data['status'] : 'in_progress'
        );
        
        if (isset($data['user_data'])) {
            $update_data['user_data'] = wp_json_encode($data['user_data']);
        }
        
        DCF_Plugin_Core::update_submission($data['submission_id'], $update_data);
        
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'step_completed', $data, array(), 'success');
        
        // Trigger custom action
        do_action('dcf_step_completed_webhook', $data);
        
        return true;
    }
    
    /**
     * Handle customer created webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_customer_created($data) {
        if (!isset($data['customer_id'])) {
            return new WP_Error('missing_data', 'Customer ID is required');
        }
        
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'customer_created', $data, array(), 'success');
        
        // Update related submission if submission_id is provided
        if (isset($data['submission_id'])) {
            $update_data = array(
                'status' => 'customer_created'
            );
            
            DCF_Plugin_Core::update_submission($data['submission_id'], $update_data);
        }
        
        // Trigger custom action
        do_action('dcf_customer_created_webhook', $data);
        
        return true;
    }
    
    /**
     * Handle payment processed webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_payment_processed($data) {
        if (!isset($data['payment_id']) || !isset($data['status'])) {
            return new WP_Error('missing_data', 'Payment ID and status are required');
        }
        
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'payment_processed', $data, array(), 'success');
        
        // Update related submission if submission_id is provided
        if (isset($data['submission_id'])) {
            $status = $data['status'] === 'success' ? 'payment_completed' : 'payment_failed';
            
            $update_data = array(
                'status' => $status
            );
            
            DCF_Plugin_Core::update_submission($data['submission_id'], $update_data);
        }
        
        // Trigger custom action
        do_action('dcf_payment_processed_webhook', $data);
        
        return true;
    }
    
    /**
     * Handle appointment scheduled webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_appointment_scheduled($data) {
        if (!isset($data['appointment_id'])) {
            return new WP_Error('missing_data', 'Appointment ID is required');
        }
        
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'appointment_scheduled', $data, array(), 'success');
        
        // Update related submission if submission_id is provided
        if (isset($data['submission_id'])) {
            $update_data = array(
                'status' => 'appointment_scheduled'
            );
            
            DCF_Plugin_Core::update_submission($data['submission_id'], $update_data);
        }
        
        // Trigger custom action
        do_action('dcf_appointment_scheduled_webhook', $data);
        
        return true;
    }
    
    /**
     * Handle custom event webhook
     *
     * @param array $data Webhook data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function handle_custom_event($data) {
        // Log the webhook
        DCF_Plugin_Core::log_integration('webhook', 'custom_event', $data, array(), 'success');
        
        // Trigger custom action with event type
        do_action('dcf_custom_webhook_' . $data['event_type'], $data);
        do_action('dcf_custom_webhook', $data);
        
        return true;
    }
    
    /**
     * Send webhook notification
     *
     * @param string $event_type Event type
     * @param array $data Event data
     * @param string $webhook_url Webhook URL (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_webhook($event_type, $data, $webhook_url = null) {
        if (empty($webhook_url)) {
            $webhook_url = DCF_Plugin_Core::get_setting('global_webhook_url');
        }
        
        if (empty($webhook_url)) {
            return new WP_Error('no_webhook_url', 'No webhook URL configured');
        }
        
        $payload = array(
            'event_type' => $event_type,
            'timestamp' => current_time('mysql'),
            'source' => 'dry_cleaning_forms',
            'version' => CMF_PLUGIN_VERSION
        );
        
        $payload = array_merge($payload, $data);
        
        return DCF_Plugin_Core::send_webhook($webhook_url, $payload);
    }
    
    /**
     * Send form submission webhook
     *
     * @param string $form_type Form type (e.g., 'contact', 'optin', 'form_builder')
     * @param array $submission_data Submission data
     * @param int $submission_id Submission ID
     * @param int $form_id Form ID (for form builder forms)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_form_submission_webhook($form_type, $submission_data, $submission_id, $form_id = null) {
        $webhook_url = null;
        $webhook_enabled = true; // Default to enabled for non-form-builder forms
        
        // For form builder forms, check if webhooks are enabled and get the form-specific URL
        if ($form_id !== null && $form_type === 'form_builder') {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            
            if ($form) {
                // Check if webhooks are enabled for this form
                if (isset($form->form_config['webhook_enabled'])) {
                    $webhook_enabled = (bool) $form->form_config['webhook_enabled'];
                }
                
                // If webhooks are enabled, use the form-specific URL if available
                if ($webhook_enabled && !empty($form->webhook_url)) {
                    $webhook_url = $form->webhook_url;
                }
            }
        }
        
        // If webhooks are not enabled for this form, don't send
        if (!$webhook_enabled) {
            return true;
        }
        
        $data = array(
            'form_type' => $form_type,
            'submission_id' => $submission_id,
            'submission_data' => $submission_data
        );
        
        if ($form_id !== null) {
            $data['form_id'] = $form_id;
        }
        
        // Use the form-specific URL or fall back to global setting
        return self::send_webhook('form.submitted', $data, $webhook_url);
    }
    
    /**
     * Send step completion webhook
     *
     * @param int $submission_id Submission ID
     * @param int $step Step number
     * @param array $user_data User data
     * @param string $status Status
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_step_completion_webhook($submission_id, $step, $user_data, $status = 'in_progress') {
        $data = array(
            'submission_id' => $submission_id,
            'step' => $step,
            'user_data' => $user_data,
            'status' => $status
        );
        
        return self::send_webhook('form.step_completed', $data);
    }
    
    /**
     * Send customer created webhook
     *
     * @param string $customer_id Customer ID
     * @param array $customer_data Customer data
     * @param int $submission_id Submission ID (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_customer_created_webhook($customer_id, $customer_data, $submission_id = null) {
        $data = array(
            'customer_id' => $customer_id,
            'customer_data' => $customer_data
        );
        
        if ($submission_id) {
            $data['submission_id'] = $submission_id;
        }
        
        return self::send_webhook('customer.created', $data);
    }
    
    /**
     * Send payment processed webhook
     *
     * @param string $payment_id Payment ID
     * @param string $status Payment status
     * @param array $payment_data Payment data
     * @param int $submission_id Submission ID (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_payment_processed_webhook($payment_id, $status, $payment_data, $submission_id = null) {
        $data = array(
            'payment_id' => $payment_id,
            'status' => $status,
            'payment_data' => $payment_data
        );
        
        if ($submission_id) {
            $data['submission_id'] = $submission_id;
        }
        
        return self::send_webhook('payment.processed', $data);
    }
    
    /**
     * Send appointment scheduled webhook
     *
     * @param string $appointment_id Appointment ID
     * @param array $appointment_data Appointment data
     * @param int $submission_id Submission ID (optional)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_appointment_scheduled_webhook($appointment_id, $appointment_data, $submission_id = null) {
        $data = array(
            'appointment_id' => $appointment_id,
            'appointment_data' => $appointment_data
        );
        
        if ($submission_id) {
            $data['submission_id'] = $submission_id;
        }
        
        return self::send_webhook('appointment.scheduled', $data);
    }
} 