<?php
/**
 * Plugin Core Tests
 *
 * @package CleanerMarketingForms
 */

class Test_Plugin_Core extends DCF_Test_Case {

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertInstanceOf('DCF_Plugin_Core', $this->plugin);
        $this->assertTrue(class_exists('DCF_Plugin_Core'));
    }

    /**
     * Test plugin activation
     */
    public function test_plugin_activation() {
        // Test database tables are created
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'dcf_forms',
            $wpdb->prefix . 'dcf_submissions',
            $wpdb->prefix . 'dcf_integration_logs',
            $wpdb->prefix . 'dcf_settings'
        );
        
        foreach ($tables as $table) {
            $this->assertTrue($this->table_exists($table), "Table {$table} should exist");
        }
    }

    /**
     * Test plugin deactivation
     */
    public function test_plugin_deactivation() {
        // Test that scheduled events are cleared
        $this->assertFalse(wp_next_scheduled('dcf_cleanup_logs'));
        $this->assertFalse(wp_next_scheduled('dcf_process_webhooks'));
    }

    /**
     * Test settings management
     */
    public function test_settings_management() {
        $test_settings = array(
            'pos_system' => 'smrt',
            'smrt_api_key' => 'test_key',
            'enable_logging' => true
        );
        
        update_option('dcf_settings', $test_settings);
        $retrieved_settings = get_option('dcf_settings');
        
        $this->assertEquals($test_settings, $retrieved_settings);
    }

    /**
     * Test form builder integration
     */
    public function test_form_builder_integration() {
        $form_builder = new DCF_Form_Builder();
        $this->assertInstanceOf('DCF_Form_Builder', $form_builder);
        
        // Test form creation
        $form_data = array(
            'title' => 'Test Form',
            'fields' => array(
                array(
                    'type' => 'text',
                    'label' => 'Name',
                    'required' => true
                )
            )
        );
        
        $form_html = $form_builder->render_form($form_data);
        $this->assertStringContainsString('<form', $form_html);
        $this->assertStringContainsString('Name', $form_html);
    }

    /**
     * Test integrations manager
     */
    public function test_integrations_manager() {
        $integrations_manager = new DCF_Integrations_Manager();
        $this->assertInstanceOf('DCF_Integrations_Manager', $integrations_manager);
        
        // Test integration loading
        $integration = $integrations_manager->get_integration('smrt');
        $this->assertInstanceOf('DCF_SMRT_Integration', $integration);
    }

    /**
     * Test webhook handler
     */
    public function test_webhook_handler() {
        $webhook_handler = new DCF_Webhook_Handler();
        $this->assertInstanceOf('DCF_Webhook_Handler', $webhook_handler);
        
        // Test webhook processing
        $webhook_data = array(
            'form_id' => 'test_form',
            'submission_data' => array(
                'name' => 'John Doe',
                'email' => 'john@example.com'
            )
        );
        
        // Mock HTTP request
        $this->mock_http_requests(array(
            'example.com/webhook' => array(
                'response' => array('code' => 200),
                'body' => json_encode(array('success' => true))
            )
        ));
        
        $result = $webhook_handler->send_webhook('https://example.com/webhook', $webhook_data);
        $this->assertTrue($result);
    }

    /**
     * Test admin dashboard
     */
    public function test_admin_dashboard() {
        $admin_dashboard = new DCF_Admin_Dashboard();
        $this->assertInstanceOf('DCF_Admin_Dashboard', $admin_dashboard);
        
        // Test dashboard stats
        $stats = $admin_dashboard->get_dashboard_stats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_submissions', $stats);
        $this->assertArrayHasKey('completed_submissions', $stats);
    }

    /**
     * Test public forms
     */
    public function test_public_forms() {
        $public_forms = new DCF_Public_Forms();
        $this->assertInstanceOf('DCF_Public_Forms', $public_forms);
        
        // Test shortcode registration
        $this->assertTrue(shortcode_exists('dcf_form'));
        $this->assertTrue(shortcode_exists('dcf_signup_form'));
        $this->assertTrue(shortcode_exists('dcf_contact_form'));
        $this->assertTrue(shortcode_exists('dcf_optin_form'));
    }

    /**
     * Helper method to check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        return $result === $table_name;
    }
} 