<?php
/**
 * Plugin Name: Cleaner Marketing
 * Plugin URI: https://cleaner.marketing/cmforms
 * Description: Comprehensive WordPress plugin for dry cleaning and laundry service businesses that handles customer signup, contact forms, and opt-in forms with multiple POS system integrations.
 * Version: 1.0.1
 * Author: Cleaner Marketing
 * Author URI: https://cleaner.marketing
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cleaner-marketing-forms
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://github.com/cleanermarketing/cleaner-marketing-forms/
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMF_PLUGIN_FILE', __FILE__);
define('CMF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMF_PLUGIN_VERSION', '1.0.1');
define('CMF_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'CMF_') === 0) {
        $class_file = str_replace('_', '-', strtolower($class));
        $class_file = str_replace('cmf-', '', $class_file);
        $file_path = CMF_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

/**
 * Main plugin class
 */
class Cleaner_Marketing_Forms {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Public forms instance
     */
    public $public_forms = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('Dry_Cleaning_Forms', 'uninstall'));
        
        // error_log('DCF Main plugin: Adding init hook');
        add_action('init', array($this, 'init'), 5); // Earlier priority
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('template_redirect', array($this, 'handle_form_preview'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Debug: Log plugin initialization
        // error_log('DCF Plugin initializing...');
        
        // Load core classes
        $this->load_dependencies();
        
        // Check and update database schema
        $this->check_database_schema();
        
        // Debug: Check if classes are loaded
        // error_log('DCF Classes loaded - DCF_Public_Forms exists: ' . (class_exists('DCF_Public_Forms') ? 'Yes' : 'No'));
        // error_log('DCF Classes loaded - DCF_Form_Builder exists: ' . (class_exists('DCF_Form_Builder') ? 'Yes' : 'No'));
        
        // Initialize components
        if (is_admin()) {
            new DCF_Admin_Dashboard();
            new DCF_Settings_Page();
            
            // Load AJAX handlers
            if (file_exists(CMF_PLUGIN_DIR . 'admin/ajax-handlers.php')) {
                require_once CMF_PLUGIN_DIR . 'admin/ajax-handlers.php';
            }
            
            // Add admin notice to verify plugin is loaded
            // add_action('admin_notices', array($this, 'debug_admin_notice'));
        }
        
        try {
            $public_forms = new DCF_Public_Forms();
            // error_log('DCF Public Forms initialized successfully');
            
            // Store reference for debugging
            $this->public_forms = $public_forms;
        } catch (Exception $e) {
            // error_log('DCF Error initializing Public Forms: ' . $e->getMessage());
        }
        
        try {
            new DCF_Integrations_Manager();
            // error_log('DCF Integrations Manager initialized');
        } catch (Exception $e) {
            // error_log('DCF Error initializing Integrations Manager: ' . $e->getMessage());
        }
        
        try {
            new DCF_Webhook_Handler();
            // error_log('DCF Webhook Handler initialized');
        } catch (Exception $e) {
            // error_log('DCF Error initializing Webhook Handler: ' . $e->getMessage());
        }
        
        // Initialize popup enhancement classes
        try {
            new DCF_Popup_Performance();
            // error_log('DCF Popup Performance initialized');
        } catch (Exception $e) {
            // error_log('DCF Error initializing Popup Performance: ' . $e->getMessage());
        }
        
        try {
            new DCF_Popup_Privacy();
            // error_log('DCF Popup Privacy initialized');
        } catch (Exception $e) {
            // error_log('DCF Error initializing Popup Privacy: ' . $e->getMessage());
        }
        
        try {
            new DCF_Popup_Conversion_Analytics();
            // error_log('DCF Popup Conversion Analytics initialized');
        } catch (Exception $e) {
            // error_log('DCF Error initializing Popup Conversion Analytics: ' . $e->getMessage());
        }
        
        // error_log('DCF Plugin initialization complete');
        
        // Initialize Block Editor support
        if (function_exists('register_block_type')) {
            try {
                new DCF_Block_Editor();
            } catch (Exception $e) {
                // error_log('DCF Error initializing Block Editor: ' . $e->getMessage());
            }
        }
        
        // Create a test form if none exists
        add_action('wp_loaded', array($this, 'ensure_test_form_exists'));
        
        // Fallback shortcode registration for testing
        add_action('init', array($this, 'register_fallback_shortcodes'), 20);
        
        // Add simple test endpoint
        add_action('template_redirect', array($this, 'handle_simple_test_page'));
        
        // Flush rewrite rules on activation to ensure test endpoint works
        register_activation_hook(__FILE__, 'flush_rewrite_rules');

        // Initialize popup system on frontend
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_popup_scripts'));
            // Removed - popups are now rendered dynamically by JavaScript
            // add_action('wp_footer', array($this, 'render_active_popups'));
        }
    }
    
    /**
     * Debug admin notice
     */
    public function debug_admin_notice() {
        global $shortcode_tags;
        $dcf_shortcodes = array();
        foreach ($shortcode_tags as $tag => $callback) {
            if (strpos($tag, 'dcf_') === 0) {
                $dcf_shortcodes[] = $tag;
            }
        }
        
        echo '<div class="notice notice-info"><p>DCF Plugin is loaded and active! Registered shortcodes: ' . implode(', ', $dcf_shortcodes) . '</p></div>';
    }
    
    /**
     * Register fallback shortcodes for testing
     */
    public function register_fallback_shortcodes() {
        global $shortcode_tags;
        
        // error_log('DCF Fallback shortcode registration called');
        // error_log('DCF Current shortcodes before fallback: ' . implode(', ', array_filter(array_keys($shortcode_tags), function($tag) {
        //     return strpos($tag, 'dcf_') === 0;
        // })));
        
        // Register a simple test shortcode directly
        if (!shortcode_exists('dcf_test')) {
            add_shortcode('dcf_test', function() {
                // error_log('DCF Fallback test shortcode called');
                return '<div style="background: yellow; padding: 15px; border: 2px solid red; margin: 10px 0;"><strong>DCF Fallback Shortcode Working!</strong><br>This means the plugin is loaded but the Public Forms class may have an issue.</div>';
            });
            // error_log('DCF Fallback test shortcode registered');
        }
        
        // Register a simple form shortcode
        if (!shortcode_exists('dcf_simple_form')) {
            add_shortcode('dcf_simple_form', function() {
                return '<div style="border: 1px solid #ccc; padding: 20px; margin: 10px 0;">
                    <h3>Simple Test Form</h3>
                    <form>
                        <p><label>Name: <input type="text" name="name" style="width: 200px;"></label></p>
                        <p><label>Email: <input type="email" name="email" style="width: 200px;"></label></p>
                        <p><button type="button">Test Button</button></p>
                    </form>
                </div>';
            });
            // error_log('DCF Simple form shortcode registered');
        }
    }
    
    /**
     * Handle simple test page
     */
    public function handle_simple_test_page() {
        if (isset($_GET['dcf_debug']) && $_GET['dcf_debug'] === 'test') {
            global $shortcode_tags;
            
            $dcf_shortcodes = array_filter(array_keys($shortcode_tags), function($tag) {
                return strpos($tag, 'dcf_') === 0;
            });
            
            header('Content-Type: text/html');
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>DCF Debug Test</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                    .test-box { background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px; }
                    .success { background: #d4edda; border: 1px solid #c3e6cb; }
                    .error { background: #f8d7da; border: 1px solid #f5c6cb; }
                </style>
            </head>
            <body>
                <h1>DCF Debug Test Page</h1>
                
                <div class="test-box">
                    <h3>Plugin Status</h3>
                    <p><strong>Plugin loaded:</strong> Yes</p>
                    <p><strong>DCF_Public_Forms class exists:</strong> <?php echo class_exists('DCF_Public_Forms') ? 'Yes' : 'No'; ?></p>
                    <p><strong>DCF_Form_Builder class exists:</strong> <?php echo class_exists('DCF_Form_Builder') ? 'Yes' : 'No'; ?></p>
                    <p><strong>Init action fired:</strong> <?php echo did_action('init') ? 'Yes (' . did_action('init') . ' times)' : 'No'; ?></p>
                    <p><strong>Current hook:</strong> <?php echo current_action() ?: 'None'; ?></p>
                    <p><strong>Total shortcodes:</strong> <?php echo count($shortcode_tags); ?></p>
                    <p><strong>DCF shortcodes:</strong> <?php echo implode(', ', $dcf_shortcodes); ?></p>
                </div>
                
                <div class="test-box">
                    <h3>Manual Public Forms Test</h3>
                    <?php
                    // Try to manually initialize public forms
                    if (class_exists('DCF_Public_Forms')) {
                        echo '<p>Attempting manual DCF_Public_Forms initialization...</p>';
                        try {
                            $test_public_forms = new DCF_Public_Forms();
                            echo '<p style="color: green;">✓ Manual initialization successful</p>';
                            
                            // Check if shortcodes are now registered
                            $updated_dcf_shortcodes = array_filter(array_keys($shortcode_tags), function($tag) {
                                return strpos($tag, 'dcf_') === 0;
                            });
                            echo '<p><strong>DCF shortcodes after manual init:</strong> ' . implode(', ', $updated_dcf_shortcodes) . '</p>';
                        } catch (Exception $e) {
                            echo '<p style="color: red;">✗ Manual initialization failed: ' . $e->getMessage() . '</p>';
                        }
                    }
                    ?>
                </div>
                
                <div class="test-box">
                    <h3>Shortcode Tests</h3>
                    
                    <h4>dcf_test shortcode:</h4>
                    <?php echo do_shortcode('[dcf_test]'); ?>
                    
                    <h4>dcf_simple_form shortcode:</h4>
                    <?php echo do_shortcode('[dcf_simple_form]'); ?>
                    
                    <h4>dcf_contact_form shortcode (if available):</h4>
                    <?php echo do_shortcode('[dcf_contact_form]'); ?>
                </div>
                
                <p><a href="<?php echo home_url(); ?>">← Back to site</a></p>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    /**
     * Ensure a test form exists for shortcode testing
     */
    public function ensure_test_form_exists() {
        $form_builder = new DCF_Form_Builder();
        $forms = $form_builder->get_forms(array('limit' => 1));
        
        if (empty($forms)) {
            // Create a simple test form
            $form_data = array(
                'form_name' => 'Test Form',
                'form_type' => 'contact',
                'form_config' => array(
                    'title' => 'Test Contact Form',
                    'description' => 'This is a test form created automatically.',
                    'fields' => array(
                        array(
                            'id' => 'name',
                            'type' => 'text',
                            'label' => 'Your Name',
                            'placeholder' => 'Enter your name',
                            'required' => true
                        ),
                        array(
                            'id' => 'email',
                            'type' => 'email',
                            'label' => 'Your Email',
                            'placeholder' => 'Enter your email',
                            'required' => true
                        ),
                        array(
                            'id' => 'message',
                            'type' => 'textarea',
                            'label' => 'Message',
                            'placeholder' => 'Enter your message',
                            'required' => false
                        )
                    )
                ),
                'webhook_url' => ''
            );
            
            $result = $form_builder->create_form($form_data);
            if (!is_wp_error($result)) {
                // error_log('DCF Test form created with ID: ' . $result);
            } else {
                // error_log('DCF Failed to create test form: ' . $result->get_error_message());
            }
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load backwards compatibility first
        require_once CMF_PLUGIN_DIR . 'includes/backwards-compatibility.php';
        
        require_once CMF_PLUGIN_DIR . 'includes/class-plugin-core.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-form-builder.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-integrations-manager.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-webhook-handler.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-resend-mailer.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-block-editor.php';
        
        // Load updater class
        require_once CMF_PLUGIN_DIR . 'includes/class-updater.php';
        
        // Load popup system classes
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-manager.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-triggers.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-template-manager.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-template-manager.php';
        
        // Load popup enhancement classes
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-performance.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-privacy.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-conversion-analytics.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-popup-countdown.php';
        require_once CMF_PLUGIN_DIR . 'includes/class-multi-step-handler.php';
        
        // Load A/B testing classes
        require_once CMF_PLUGIN_DIR . 'includes/class-ab-testing-manager.php';
        
        // Load integrations
        require_once CMF_PLUGIN_DIR . 'includes/integrations/class-smrt-integration.php';
        require_once CMF_PLUGIN_DIR . 'includes/integrations/class-spot-integration.php';
        require_once CMF_PLUGIN_DIR . 'includes/integrations/class-cleancloud-integration.php';
        
        // Load admin classes
        if (is_admin()) {
            require_once CMF_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
            require_once CMF_PLUGIN_DIR . 'admin/class-settings-page.php';
            require_once CMF_PLUGIN_DIR . 'admin/ajax-handlers.php';
            require_once CMF_PLUGIN_DIR . 'admin/migration-tool.php';
        }
        
        // Load public classes
        require_once CMF_PLUGIN_DIR . 'public/class-public-forms.php';
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'cleaner-marketing-forms',
            false,
            dirname(CMF_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Handle form preview requests
     */
    public function handle_form_preview() {
        // Strict check to prevent accidental triggers
        if (!isset($_GET['dcf_preview']) || $_GET['dcf_preview'] !== '1' || !isset($_GET['form_id'])) {
            return;
        }
        
        // Check if user has permission to preview forms
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to preview forms.', 'cleaner-marketing-forms'));
        }
        
        $form_id = intval($_GET['form_id']);
        
        // Get the form
        $form_builder = new DCF_Form_Builder();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_die(__('Form not found.', 'cleaner-marketing-forms'));
        }
        
        // Render preview page
        $this->render_form_preview($form);
        exit;
    }
    
    /**
     * Render form preview page
     */
    private function render_form_preview($form) {
        // Decode form_config if it's a JSON string
        $form_config = $form->form_config;
        if (is_string($form_config)) {
            $form_config = json_decode($form_config, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $form_config = array();
            }
        }
        
        $form_title = !empty($form_config['title']) ? $form_config['title'] : $form->form_name;
        
        // Use form builder to render with styles
        $form_builder = new DCF_Form_Builder();
        $rendered_form = $form_builder->render_form($form->id, array(
            'ajax' => false,
            'show_title' => true,
            'show_description' => true,
            'preview_mode' => true,
            'force_render' => true
        ));
        
        // Replace form action to prevent submission in preview
        $rendered_form = str_replace('<form ', '<form onsubmit="event.preventDefault(); alert(\'' . esc_js(__('Form submission is disabled in preview mode.', 'cleaner-marketing-forms')) . '\'); return false;" ', $rendered_form);
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($form_title); ?> - <?php _e('Form Preview', 'cleaner-marketing-forms'); ?></title>
            <?php
            // Load WordPress styles for forms
            wp_enqueue_style('dcf-public', CMF_PLUGIN_URL . 'public/css/public-forms.css', array(), CMF_PLUGIN_VERSION);
            wp_enqueue_style('dcf-modern-forms', CMF_PLUGIN_URL . 'public/css/modern-forms.css', array(), CMF_PLUGIN_VERSION);
            wp_enqueue_style('dcf-form-styles', CMF_PLUGIN_URL . 'public/css/form-styles.css', array(), CMF_PLUGIN_VERSION);
            wp_print_styles();
            ?>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0 auto;
                    padding: 20px;
                    background: #f5f5f5;
                }
                .preview-header {
                    background: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    max-width: 800px;
                    margin-left: auto;
                    margin-right: auto;
                }
                .preview-notice {
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    border-radius: 4px;
                    padding: 15px;
                    margin-bottom: 20px;
                    color: #0073aa;
                    max-width: 800px;
                    margin-left: auto;
                    margin-right: auto;
                }
                .form-preview-wrapper {
                    background: #fff;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin: 0 auto;
                }
                .form-title {
                    margin: 0 0 15px 0;
                    font-size: 28px;
                    color: #1d2327;
                }
                .form-description {
                    margin: 0 0 30px 0;
                    color: #646970;
                    font-size: 16px;
                }
                .dcf-field {
                    margin-bottom: 20px;
                }
                .dcf-field-label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #1d2327;
                }
                .dcf-required {
                    color: #d63638;
                }
                .dcf-input, .dcf-textarea, .dcf-select {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                .dcf-textarea {
                    resize: vertical;
                    min-height: 100px;
                }
                .dcf-radio-option, .dcf-checkbox-option {
                    margin: 8px 0;
                }
                .dcf-radio, .dcf-checkbox {
                    margin-right: 8px;
                }
                .dcf-submit-button {
                    background: #2271b1;
                    color: #fff;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                .dcf-submit-button:hover {
                    background: #135e96;
                }
                .close-preview {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #666;
                    color: #fff;
                    border: none;
                    padding: 10px 15px;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    font-size: 14px;
                }
                .close-preview:hover {
                    background: #333;
                    color: #fff;
                }
                
                /* Override form container styles in preview */
                .form-preview-wrapper .dcf-form-container {
                    background: transparent;
                    box-shadow: none;
                    padding: 0;
                    margin: 0 auto;
                }
                
                /* Complex field types styles */
                .dcf-name-field .dcf-name-row {
                    display: flex;
                    gap: 15px;
                }
                .dcf-name-first,
                .dcf-name-last {
                    flex: 1;
                }
                .dcf-name-first input,
                .dcf-name-last input {
                    width: 100%;
                }
                
                .dcf-address-field .dcf-address-row {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 15px;
                }
                .dcf-address-field .dcf-address-row:last-child {
                    margin-bottom: 0;
                }
                .dcf-address-line1,
                .dcf-address-line2 {
                    flex: 1;
                }
                .dcf-address-city {
                    flex: 2;
                }
                .dcf-address-state {
                    flex: 1;
                }
                .dcf-address-zip {
                    flex: 1;
                }
                .dcf-address-field input {
                    width: 100%;
                }
                
                .dcf-terms-field label {
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                    font-size: 16px;
                    line-height: 1.5;
                    cursor: pointer;
                    font-weight: normal;
                }
                .dcf-terms-field input[type="checkbox"] {
                    margin-top: 3px;
                    flex-shrink: 0;
                    width: 16px !important;
                    height: 16px !important;
                    padding: 0 !important;
                }
                .dcf-terms-field a {
                    color: #2271b1;
                    text-decoration: none;
                }
                .dcf-terms-field a:hover {
                    text-decoration: underline;
                }
                
                /* Responsive design for complex fields */
                @media (max-width: 768px) {
                    .dcf-name-field .dcf-name-row {
                        flex-direction: column;
                        gap: 10px;
                    }
                    .dcf-address-field .dcf-address-row {
                        flex-direction: column;
                        gap: 10px;
                    }
                }
            </style>
        </head>
        <body>
            <a href="javascript:window.close();" class="close-preview"><?php _e('Close Preview', 'cleaner-marketing-forms'); ?></a>
            
            <div class="preview-header">
                <h1><?php _e('Form Preview', 'cleaner-marketing-forms'); ?></h1>
                <p><?php _e('This is how your form will appear to visitors. This is a preview only - submissions will not be processed.', 'cleaner-marketing-forms'); ?></p>
            </div>
            
            <div class="preview-notice">
                <strong><?php _e('Preview Mode:', 'cleaner-marketing-forms'); ?></strong> 
                <?php _e('Form submissions are disabled in preview mode.', 'cleaner-marketing-forms'); ?>
            </div>
            
            <div class="form-preview-wrapper">
                <?php echo $rendered_form; ?>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * PLACEHOLDER: Old code removed
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if ($required): ?>
                                        <span class="dcf-required">*</span>
                                    <?php endif; ?>
                                </label>
                            <?php endif; ?>
                            
                            <?php
                            switch ($field['type']):
                                case 'name':
                                    $first_placeholder = isset($field['first_placeholder']) ? $field['first_placeholder'] : 'First Name';
                                    $last_placeholder = isset($field['last_placeholder']) ? $field['last_placeholder'] : 'Last Name';
                                    echo '<div class="dcf-name-field">';
                                    echo '<div class="dcf-name-row">';
                                    echo sprintf(
                                        '<div class="dcf-name-first"><input type="text" id="%s_first" name="%s_first" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($first_placeholder)
                                    );
                                    echo sprintf(
                                        '<div class="dcf-name-last"><input type="text" id="%s_last" name="%s_last" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($last_placeholder)
                                    );
                                    echo '</div></div>';
                                    break;
                                    
                                case 'address':
                                    $line1_placeholder = isset($field['line1_placeholder']) ? $field['line1_placeholder'] : 'Address Line 1';
                                    $line2_placeholder = isset($field['line2_placeholder']) ? $field['line2_placeholder'] : 'Address Line 2';
                                    $city_placeholder = isset($field['city_placeholder']) ? $field['city_placeholder'] : 'City';
                                    $state_placeholder = isset($field['state_placeholder']) ? $field['state_placeholder'] : 'State';
                                    $zip_placeholder = isset($field['zip_placeholder']) ? $field['zip_placeholder'] : 'Zip Code';
                                    echo '<div class="dcf-address-field">';
                                    echo '<div class="dcf-address-row">';
                                    echo sprintf(
                                        '<div class="dcf-address-line1"><input type="text" id="%s_line1" name="%s_line1" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($line1_placeholder)
                                    );
                                    echo sprintf(
                                        '<div class="dcf-address-line2"><input type="text" id="%s_line2" name="%s_line2" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($line2_placeholder)
                                    );
                                    echo '</div>';
                                    echo '<div class="dcf-address-row">';
                                    echo sprintf(
                                        '<div class="dcf-address-city"><input type="text" id="%s_city" name="%s_city" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($city_placeholder)
                                    );
                                    echo sprintf(
                                        '<div class="dcf-address-state"><input type="text" id="%s_state" name="%s_state" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($state_placeholder)
                                    );
                                    echo sprintf(
                                        '<div class="dcf-address-zip"><input type="text" id="%s_zip" name="%s_zip" placeholder="%s" class="dcf-input"></div>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($zip_placeholder)
                                    );
                                    echo '</div></div>';
                                    break;
                                    
                                case 'terms':
                                    $terms_text = isset($field['terms_text']) ? $field['terms_text'] : 'I have read and agree to the Terms and Conditions and Privacy Policy';
                                    $terms_url = isset($field['terms_url']) ? $field['terms_url'] : '';
                                    $privacy_url = isset($field['privacy_url']) ? $field['privacy_url'] : '';
                                    
                                    // Replace text with links if URLs are provided
                                    if ($terms_url || $privacy_url) {
                                        if ($terms_url) {
                                            $terms_text = str_replace(
                                                'Terms and Conditions',
                                                '<a href="' . esc_url($terms_url) . '" target="_blank">Terms and Conditions</a>',
                                                $terms_text
                                            );
                                        }
                                        if ($privacy_url) {
                                            $terms_text = str_replace(
                                                'Privacy Policy',
                                                '<a href="' . esc_url($privacy_url) . '" target="_blank">Privacy Policy</a>',
                                                $terms_text
                                            );
                                        }
                                    }
                                    
                                    echo '<div class="dcf-terms-field">';
                                    echo sprintf(
                                        '<label><input type="checkbox" id="%s" name="%s" class="dcf-checkbox" %s> %s</label>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        $required ? 'required' : '',
                                        wp_kses($terms_text, ['a' => ['href' => [], 'target' => []]])
                                    );
                                    echo '</div>';
                                    break;
                                    
                                case 'submit':
                                    $button_text = isset($field['button_text']) ? $field['button_text'] : 'Submit';
                                    $button_size = isset($field['button_size']) ? $field['button_size'] : 'medium';
                                    $alignment = isset($field['alignment']) ? $field['alignment'] : 'center';
                                    $bg_color = isset($field['bg_color']) ? $field['bg_color'] : '#2271b1';
                                    $text_color = isset($field['text_color']) ? $field['text_color'] : '#ffffff';
                                    $border_color = isset($field['border_color']) ? $field['border_color'] : '#2271b1';
                                    $border_radius = isset($field['border_radius']) ? $field['border_radius'] : '4';
                                    $min_width = isset($field['min_width']) ? $field['min_width'] : '';
                                    
                                    // Size styles
                                    $size_styles = array(
                                        'small' => 'padding: 8px 16px; font-size: 14px;',
                                        'medium' => 'padding: 12px 24px; font-size: 16px;',
                                        'large' => 'padding: 16px 32px; font-size: 18px;'
                                    );
                                    
                                    $button_style = isset($size_styles[$button_size]) ? $size_styles[$button_size] : $size_styles['medium'];
                                    $button_style .= "background-color: {$bg_color}; color: {$text_color}; border: 1px solid {$border_color}; border-radius: {$border_radius}px; cursor: pointer; transition: opacity 0.2s;";
                                    
                                    if ($min_width && $min_width !== '0') {
                                        $button_style .= " min-width: {$min_width}px;";
                                    }
                                    
                                    echo '<div style="text-align: ' . esc_attr($alignment) . '; margin-top: 20px;">';
                                    echo sprintf(
                                        '<button type="submit" style="%s" onsubmit="event.preventDefault(); alert(\'%s\');">%s</button>',
                                        esc_attr($button_style),
                                        esc_js(__('Form submission is disabled in preview mode.', 'cleaner-marketing-forms')),
                                        esc_html($button_text)
                                    );
                                    echo '</div>';
                                    continue 2;
                                    
                                case 'textarea':
                                    $rows = isset($field['rows']) ? $field['rows'] : 4;
                                    echo sprintf(
                                        '<textarea id="%s" name="%s" rows="%d" placeholder="%s" class="dcf-textarea" %s></textarea>',
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        intval($rows),
                                        esc_attr($placeholder),
                                        $required ? 'required' : ''
                                    );
                                    break;
                                    
                                case 'select':
                                    $options = isset($field['options']) ? $field['options'] : array();
                                    echo sprintf('<select id="%s" name="%s" class="dcf-select" %s>', esc_attr($field_id), esc_attr($field_name), $required ? 'required' : '');
                                    if ($placeholder) {
                                        echo '<option value="">' . esc_html($placeholder) . '</option>';
                                    } else {
                                        echo '<option value="">' . __('Select an option', 'cleaner-marketing-forms') . '</option>';
                                    }
                                    foreach ($options as $option) {
                                        $option_label = is_array($option) ? $option['label'] : $option;
                                        $option_value = is_array($option) ? $option['value'] : $option;
                                        echo sprintf('<option value="%s">%s</option>', esc_attr($option_value), esc_html($option_label));
                                    }
                                    echo '</select>';
                                    break;
                                    
                                case 'radio':
                                    $options = isset($field['options']) ? $field['options'] : array();
                                    foreach ($options as $i => $option) {
                                        $option_id = $field_id . '_' . $i;
                                        $option_label = is_array($option) ? $option['label'] : $option;
                                        $option_value = is_array($option) ? $option['value'] : $option;
                                        echo sprintf(
                                            '<div class="dcf-radio-option"><input type="radio" id="%s" name="%s" value="%s" class="dcf-radio" %s> <label for="%s">%s</label></div>',
                                            esc_attr($option_id),
                                            esc_attr($field_name),
                                            esc_attr($option_value),
                                            $required ? 'required' : '',
                                            esc_attr($option_id),
                                            esc_html($option_label)
                                        );
                                    }
                                    break;
                                    
                                case 'checkbox':
                                    $options = isset($field['options']) ? $field['options'] : array();
                                    foreach ($options as $i => $option) {
                                        $option_id = $field_id . '_' . $i;
                                        $option_label = is_array($option) ? $option['label'] : $option;
                                        $option_value = is_array($option) ? $option['value'] : $option;
                                        echo sprintf(
                                            '<div class="dcf-checkbox-option"><input type="checkbox" id="%s" name="%s[]" value="%s" class="dcf-checkbox"> <label for="%s">%s</label></div>',
                                            esc_attr($option_id),
                                            esc_attr($field_name),
                                            esc_attr($option_value),
                                            esc_attr($option_id),
                                            esc_html($option_label)
                                        );
                                    }
                                    break;
                                    
                                default:
                                    $input_type = $field['type'] === 'phone' ? 'tel' : $field['type'];
                                    echo sprintf(
                                        '<input type="%s" id="%s" name="%s" placeholder="%s" class="dcf-input" %s>',
                                        esc_attr($input_type),
                                        esc_attr($field_id),
                                        esc_attr($field_name),
                                        esc_attr($placeholder),
                                        $required ? 'required' : ''
                                    );
                                    break;
                            endswitch;
                            ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php
                    // Check if form has a custom submit button field
                    $has_custom_submit = false;
                    foreach ($fields as $field) {
                        if ($field['type'] === 'submit') {
                            $has_custom_submit = true;
                            break;
                        }
                    }
                    
                    // Only show default submit button if no custom submit button exists
                    if (!$has_custom_submit):
                    ?>
                    <div class="dcf-form-submit">
                        <button type="submit" class="dcf-submit-button">
                            <?php echo esc_html(isset($form_config['submit_text']) ? $form_config['submit_text'] : __('Submit', 'cleaner-marketing-forms')); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
     */
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        self::drop_tables();
        
        // Remove plugin options
        self::remove_options();
    }
    
    /**
     * Check and update database schema
     */
    public function check_database_schema() {
        $current_db_version = get_option('dcf_db_version', '1.0');
        $plugin_db_version = '1.3'; // Increment this when schema changes
        
        if (version_compare($current_db_version, $plugin_db_version, '<')) {
            $this->create_tables();
            update_option('dcf_db_version', $plugin_db_version);
        }
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Form submissions table
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        $submissions_sql = "CREATE TABLE $submissions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id varchar(100) NOT NULL,
            user_data longtext NOT NULL,
            integration_data longtext,
            utm_data longtext,
            error_log longtext,
            step_completed int(11) NOT NULL DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Forms table
        $forms_table = $wpdb->prefix . 'dcf_forms';
        $forms_sql = "CREATE TABLE $forms_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_name varchar(255) NOT NULL,
            form_type varchar(100) NOT NULL,
            form_config longtext NOT NULL,
            webhook_url varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_type (form_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Integration logs table
        $logs_table = $wpdb->prefix . 'dcf_integration_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            integration_type varchar(100) NOT NULL,
            action varchar(100) NOT NULL,
            request_data longtext,
            response_data longtext,
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY integration_type (integration_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Settings table
        $settings_table = $wpdb->prefix . 'dcf_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            option_name varchar(255) NOT NULL,
            option_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";
        
        // Popup tables
        $popups_table = $wpdb->prefix . 'dcf_popups';
        $popups_sql = "CREATE TABLE $popups_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_name varchar(255) NOT NULL,
            popup_type varchar(50) NOT NULL DEFAULT 'modal',
            popup_config longtext,
            targeting_rules longtext,
            trigger_settings longtext,
            design_settings longtext,
            template_id varchar(100) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY popup_type (popup_type),
            KEY template_id (template_id)
        ) $charset_collate;";
        
        // Popup displays table
        $popup_displays_table = $wpdb->prefix . 'dcf_popup_displays';
        $popup_displays_sql = "CREATE TABLE $popup_displays_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            popup_id int(11) NOT NULL,
            visitor_id varchar(255) NOT NULL,
            session_id varchar(255) NOT NULL,
            display_time datetime DEFAULT CURRENT_TIMESTAMP,
            page_url text,
            referrer_url text,
            user_agent text,
            ip_address varchar(45),
            device_type varchar(50),
            PRIMARY KEY (id),
            KEY popup_id (popup_id),
            KEY visitor_id (visitor_id),
            KEY display_time (display_time)
        ) $charset_collate;";
        
        // A/B tests table
        $ab_tests_table = $wpdb->prefix . 'dcf_ab_tests';
        $ab_tests_sql = "CREATE TABLE $ab_tests_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            test_name varchar(255) NOT NULL,
            popup_ids text NOT NULL,
            traffic_split text NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'draft',
            winner_id int(11) DEFAULT NULL,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";
        
        // Analytics table for tracking form views
        $analytics_table = $wpdb->prefix . 'dcf_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id varchar(100) NOT NULL,
            views int(11) NOT NULL DEFAULT 0,
            date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entity_date (entity_type, entity_id, date),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY date (date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($submissions_sql);
        dbDelta($forms_sql);
        dbDelta($logs_sql);
        dbDelta($settings_sql);
        dbDelta($popups_sql);
        dbDelta($popup_displays_sql);
        dbDelta($ab_tests_sql);
        dbDelta($analytics_sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'dcf_pos_system' => '',
            'dcf_login_page_url' => home_url('/login'),
            'dcf_success_message' => __('Thank you for signing up! Your account has been created successfully.', 'cleaner-marketing-forms'),
            'dcf_error_message' => __('Sorry, there was an error processing your request. Please try again.', 'cleaner-marketing-forms'),
            'dcf_webhook_url' => '',
            'dcf_email_notifications' => 'yes'
        );
        
        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Drop database tables
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'dcf_submissions',
            $wpdb->prefix . 'dcf_forms',
            $wpdb->prefix . 'dcf_integration_logs',
            $wpdb->prefix . 'dcf_settings',
            $wpdb->prefix . 'dcf_popups',
            $wpdb->prefix . 'dcf_popup_displays',
            $wpdb->prefix . 'dcf_popup_interactions',
            $wpdb->prefix . 'dcf_popup_frequency',
            $wpdb->prefix . 'dcf_popup_templates',
            $wpdb->prefix . 'dcf_popup_campaigns',
            $wpdb->prefix . 'dcf_ab_tests'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove plugin options
     */
    private static function remove_options() {
        $options = array(
            'dcf_pos_system',
            'dcf_smrt_graphql_url',
            'dcf_smrt_api_key',
            'dcf_spot_username',
            'dcf_spot_license_key',
            'dcf_cleancloud_api_key',
            'dcf_login_page_url',
            'dcf_success_message',
            'dcf_error_message',
            'dcf_webhook_url',
            'dcf_email_notifications'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }

    /**
     * Enqueue popup scripts
     */
    public function enqueue_popup_scripts() {
        // Enqueue popup engine JavaScript
        wp_enqueue_script(
            'dcf-popup-engine',
            CMF_PLUGIN_URL . 'public/js/popup-engine.js',
            array('jquery'),
            CMF_PLUGIN_VERSION,
            true
        );

        // Enqueue popup styles
        wp_enqueue_style(
            'dcf-popup-styles',
            CMF_PLUGIN_URL . 'public/css/popup-styles.css',
            array(),
            CMF_PLUGIN_VERSION
        );

        // Get active popups for current page
        try {
            $popup_manager = new DCF_Popup_Manager();
            $active_popups = $popup_manager->get_active_popups_for_page();
        } catch (Exception $e) {
            $active_popups = array();
        }

        // Localize script with popup data
        wp_localize_script('dcf-popup-engine', 'dcf_popups', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_popup_nonce'),
            'popups' => $active_popups,
            'user_id' => get_current_user_id(),
            'page_url' => get_permalink(),
            'is_mobile' => wp_is_mobile(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }

    /**
     * Render active popups
     */
    public function render_active_popups() {
        try {
            $popup_manager = new DCF_Popup_Manager();
            $active_popups = $popup_manager->get_active_popups_for_page();

            if (empty($active_popups)) {
                return;
            }
        } catch (Exception $e) {
            // Silently fail if there's an error with popups
            return;
        }

        echo '<div id="dcf-popup-container">';
        
        foreach ($active_popups as $popup) {
            $this->render_popup_html($popup);
        }
        
        echo '</div>';
    }

    /**
     * Render individual popup HTML
     */
    private function render_popup_html($popup) {
        $popup_id = $popup['id'];
        $popup_type = $popup['popup_type'];
        $design_settings = $popup['design_settings'] ?? array();
        $popup_config = $popup['popup_config'] ?? array();

        // Get form HTML if form_id is set
        $form_html = '';
        if (!empty($popup_config['form_id'])) {
            $form_builder = new DCF_Form_Builder();
            $form_html = $form_builder->render_form($popup_config['form_id'], false, true); // popup mode
        }

        // Generate popup HTML based on type
        $popup_classes = array('dcf-popup', 'dcf-popup-' . $popup_type);
        $popup_styles = $this->generate_popup_styles($design_settings);

        echo '<div id="dcf-popup-' . $popup_id . '" class="' . implode(' ', $popup_classes) . '" style="display: none;" data-popup-id="' . $popup_id . '">';
        
        if ($popup_type === 'modal' || $popup_type === 'split-screen') {
            echo '<div class="dcf-popup-overlay"></div>';
        }
        
        echo '<div class="dcf-popup-content" style="' . $popup_styles . '">';
        
        // Close button
        if ($design_settings['close_button'] ?? true) {
            echo '<button class="dcf-popup-close" aria-label="' . __('Close popup', 'cleaner-marketing-forms') . '">&times;</button>';
        }
        
        // Popup content
        echo '<div class="dcf-popup-body">';
        echo $form_html;
        echo '</div>';
        
        echo '</div>'; // .dcf-popup-content
        echo '</div>'; // .dcf-popup
    }

    /**
     * Generate popup styles from design settings
     */
    private function generate_popup_styles($design_settings) {
        $styles = array();

        if (!empty($design_settings['width'])) {
            $styles[] = 'width: ' . esc_attr($design_settings['width']);
        }

        if (!empty($design_settings['height']) && $design_settings['height'] !== 'auto') {
            $styles[] = 'height: ' . esc_attr($design_settings['height']);
        }

        if (!empty($design_settings['background_color'])) {
            $styles[] = 'background-color: ' . esc_attr($design_settings['background_color']);
        }

        if (!empty($design_settings['text_color'])) {
            $styles[] = 'color: ' . esc_attr($design_settings['text_color']);
        }

        if (!empty($design_settings['border_radius'])) {
            $styles[] = 'border-radius: ' . esc_attr($design_settings['border_radius']);
        }

        if (!empty($design_settings['padding'])) {
            $styles[] = 'padding: ' . esc_attr($design_settings['padding']);
        }

        return implode('; ', $styles);
    }
}

// Initialize the plugin
function cleaner_marketing_forms() {
    return Cleaner_Marketing_Forms::get_instance();
}

// Start the plugin
cleaner_marketing_forms(); 