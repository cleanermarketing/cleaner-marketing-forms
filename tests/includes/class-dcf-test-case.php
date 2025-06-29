<?php
/**
 * Base Test Case for Dry Cleaning Forms Plugin
 *
 * @package CleanerMarketingForms
 */

class DCF_Test_Case extends WP_UnitTestCase {

    /**
     * Plugin instance
     *
     * @var DCF_Plugin_Core
     */
    protected $plugin;

    /**
     * Test factory
     *
     * @var DCF_Test_Factory
     */
    protected $factory;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->plugin = DCF_Plugin_Core::get_instance();
        $this->factory = new DCF_Test_Factory();
        
        // Clean up any existing test data
        $this->clean_up_test_data();
        
        // Set up test environment
        $this->set_up_test_environment();
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        $this->clean_up_test_data();
        parent::tearDown();
    }

    /**
     * Clean up test data
     */
    protected function clean_up_test_data() {
        global $wpdb;
        
        // Clean up test submissions
        $wpdb->query("DELETE FROM {$wpdb->prefix}dcf_submissions WHERE form_id LIKE 'test_%'");
        
        // Clean up test forms
        $wpdb->query("DELETE FROM {$wpdb->prefix}dcf_forms WHERE form_name LIKE 'test_%'");
        
        // Clean up test logs
        $wpdb->query("DELETE FROM {$wpdb->prefix}dcf_integration_logs WHERE action LIKE 'test_%'");
        
        // Clean up test settings
        delete_option('dcf_test_settings');
        
        // Clear any cached data
        wp_cache_flush();
    }

    /**
     * Set up test environment
     */
    protected function set_up_test_environment() {
        // Set test settings
        update_option('dcf_settings', array(
            'pos_system' => 'test',
            'enable_logging' => true,
            'debug_mode' => true
        ));
        
        // Create test user
        $this->test_user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->test_user_id);
    }

    /**
     * Create test form
     *
     * @param array $args Form arguments
     * @return int Form ID
     */
    protected function create_test_form($args = array()) {
        $defaults = array(
            'form_name' => 'test_form_' . uniqid(),
            'form_type' => 'contact',
            'form_config' => json_encode(array(
                'title' => 'Test Form',
                'description' => 'Test form description',
                'fields' => array(
                    array(
                        'id' => 'test_field_1',
                        'type' => 'text',
                        'label' => 'Test Field',
                        'required' => true
                    )
                )
            )),
            'webhook_url' => 'https://example.com/webhook'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory->form->create($args);
    }

    /**
     * Create test submission
     *
     * @param array $args Submission arguments
     * @return int Submission ID
     */
    protected function create_test_submission($args = array()) {
        $defaults = array(
            'form_id' => 'test_form',
            'user_data' => json_encode(array(
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '555-1234'
            )),
            'step_completed' => 1,
            'status' => 'pending'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->factory->submission->create($args);
    }

    /**
     * Assert form exists
     *
     * @param int $form_id Form ID
     */
    protected function assertFormExists($form_id) {
        global $wpdb;
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dcf_forms WHERE id = %d",
            $form_id
        ));
        
        $this->assertNotNull($form, 'Form should exist');
    }

    /**
     * Assert submission exists
     *
     * @param int $submission_id Submission ID
     */
    protected function assertSubmissionExists($submission_id) {
        global $wpdb;
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dcf_submissions WHERE id = %d",
            $submission_id
        ));
        
        $this->assertNotNull($submission, 'Submission should exist');
    }

    /**
     * Assert webhook was called
     *
     * @param string $webhook_url Webhook URL
     */
    protected function assertWebhookCalled($webhook_url) {
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dcf_integration_logs WHERE action = 'webhook_call' AND request_data LIKE %s",
            '%' . $webhook_url . '%'
        ));
        
        $this->assertNotNull($log, 'Webhook should have been called');
    }

    /**
     * Mock HTTP requests
     *
     * @param array $responses Array of mock responses
     */
    protected function mock_http_requests($responses) {
        add_filter('pre_http_request', function($preempt, $args, $url) use ($responses) {
            foreach ($responses as $pattern => $response) {
                if (strpos($url, $pattern) !== false) {
                    return $response;
                }
            }
            return $preempt;
        }, 10, 3);
    }

    /**
     * Get test data directory
     *
     * @return string
     */
    protected function get_test_data_dir() {
        return dirname(dirname(__FILE__)) . '/data/';
    }

    /**
     * Load test data file
     *
     * @param string $filename Filename
     * @return mixed
     */
    protected function load_test_data($filename) {
        $file_path = $this->get_test_data_dir() . $filename;
        
        if (!file_exists($file_path)) {
            $this->fail("Test data file not found: {$filename}");
        }
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'json':
                return json_decode(file_get_contents($file_path), true);
            case 'php':
                return include $file_path;
            default:
                return file_get_contents($file_path);
        }
    }
} 