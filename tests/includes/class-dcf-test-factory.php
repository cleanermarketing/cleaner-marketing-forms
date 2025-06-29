<?php
/**
 * Test Factory for Dry Cleaning Forms Plugin
 *
 * @package CleanerMarketingForms
 */

class DCF_Test_Factory extends WP_UnitTest_Factory {

    /**
     * Form factory
     *
     * @var DCF_Form_Factory
     */
    public $form;

    /**
     * Submission factory
     *
     * @var DCF_Submission_Factory
     */
    public $submission;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->form = new DCF_Form_Factory($this);
        $this->submission = new DCF_Submission_Factory($this);
    }
}

/**
 * Form Factory
 */
class DCF_Form_Factory extends WP_UnitTest_Factory_For_Thing {

    /**
     * Constructor
     */
    public function __construct($factory = null) {
        parent::__construct($factory);
        
        $this->default_generation_definitions = array(
            'form_name' => new WP_UnitTest_Generator_Sequence('test_form_%s'),
            'form_type' => 'contact',
            'form_config' => json_encode(array(
                'title' => 'Test Form',
                'description' => 'Test form description',
                'fields' => array()
            )),
            'webhook_url' => 'https://example.com/webhook',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
    }

    /**
     * Create form
     *
     * @param array $args Form arguments
     * @return int Form ID
     */
    public function create_object($args) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'dcf_forms',
            $args
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Update form
     *
     * @param int $form_id Form ID
     * @param array $fields Fields to update
     * @return int Form ID
     */
    public function update_object($form_id, $fields) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'dcf_forms',
            $fields,
            array('id' => $form_id)
        );
        
        return $form_id;
    }

    /**
     * Get form
     *
     * @param int $form_id Form ID
     * @return object|null
     */
    public function get_object_by_id($form_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dcf_forms WHERE id = %d",
            $form_id
        ));
    }
}

/**
 * Submission Factory
 */
class DCF_Submission_Factory extends WP_UnitTest_Factory_For_Thing {

    /**
     * Constructor
     */
    public function __construct($factory = null) {
        parent::__construct($factory);
        
        $this->default_generation_definitions = array(
            'form_id' => 'test_form',
            'user_data' => json_encode(array(
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '555-1234'
            )),
            'step_completed' => 1,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
    }

    /**
     * Create submission
     *
     * @param array $args Submission arguments
     * @return int Submission ID
     */
    public function create_object($args) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'dcf_submissions',
            $args
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Update submission
     *
     * @param int $submission_id Submission ID
     * @param array $fields Fields to update
     * @return int Submission ID
     */
    public function update_object($submission_id, $fields) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'dcf_submissions',
            $fields,
            array('id' => $submission_id)
        );
        
        return $submission_id;
    }

    /**
     * Get submission
     *
     * @param int $submission_id Submission ID
     * @return object|null
     */
    public function get_object_by_id($submission_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dcf_submissions WHERE id = %d",
            $submission_id
        ));
    }

    /**
     * Create multi-step submission
     *
     * @param array $steps Array of step data
     * @return int Submission ID
     */
    public function create_multi_step($steps = array()) {
        $defaults = array(
            array(
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '555-1234'
            ),
            array(
                'service_type' => 'pickup_delivery'
            ),
            array(
                'address' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zip' => '12345'
            ),
            array(
                'card_number' => '4111111111111111',
                'exp_month' => '12',
                'exp_year' => '2025',
                'cvv' => '123'
            )
        );
        
        $steps = wp_parse_args($steps, $defaults);
        $user_data = array();
        
        foreach ($steps as $step_data) {
            $user_data = array_merge($user_data, $step_data);
        }
        
        return $this->create(array(
            'user_data' => json_encode($user_data),
            'step_completed' => count($steps),
            'status' => 'completed'
        ));
    }
} 