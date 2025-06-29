<?php
/**
 * Public Forms Handler
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public Forms class
 */
class DCF_Public_Forms {
    
    /**
     * Constructor
     */
    public function __construct() {
        // error_log('DCF_Public_Forms constructor called');
        
        // Check if init has already fired
        if (did_action('init')) {
            // error_log('DCF_Public_Forms: init already fired, calling init() directly');
            $this->init();
        } else {
            // error_log('DCF_Public_Forms: adding init action hook');
            add_action('init', array($this, 'init'));
        }
    }
    
    /**
     * Initialize public forms
     */
    public function init() {
        // error_log('DCF Public Forms init() called');
        
        // Register shortcodes
        $this->register_shortcodes();
        
        // Add a simple test to see if we can hook into wp_footer
        // add_action('wp_footer', function() {
        //     echo '<!-- DCF Plugin is loaded and hooks are working -->';
        // });
        
        // Ensure shortcodes work in FSE themes
        add_filter('the_content', 'do_shortcode', 11);
        add_filter('widget_text', 'do_shortcode');
        
        // For FSE themes, also ensure shortcodes work in block content
        add_filter('render_block', array($this, 'process_shortcodes_in_blocks'), 10, 2);
        
        // Additional hooks for FSE theme compatibility
        add_action('wp_loaded', array($this, 'ensure_shortcode_support'));
        
        // Add a test endpoint to bypass theme issues
        add_action('init', array($this, 'add_shortcode_test_endpoint'));
        
        // error_log('DCF Shortcodes registered: dcf_form, dcf_signup_form, dcf_contact_form, dcf_optin_form, dcf_test');
        
        // Debug: Add admin action to list all shortcodes
        if (is_admin()) {
            add_action('wp_ajax_dcf_list_shortcodes', array($this, 'debug_list_shortcodes'));
        }
        
        // AJAX handlers
        add_action('wp_ajax_dcf_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_dcf_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_dcf_signup_step', array($this, 'handle_signup_step'));
        add_action('wp_ajax_nopriv_dcf_signup_step', array($this, 'handle_signup_step'));
        add_action('wp_ajax_dcf_get_pickup_dates', array($this, 'handle_get_pickup_dates'));
        add_action('wp_ajax_nopriv_dcf_get_pickup_dates', array($this, 'handle_get_pickup_dates'));
        add_action('wp_ajax_dcf_check_existing_customer', array($this, 'handle_check_existing_customer'));
        add_action('wp_ajax_nopriv_dcf_check_existing_customer', array($this, 'handle_check_existing_customer'));
        add_action('wp_ajax_dcf_create_customer_account', array($this, 'handle_create_customer_account'));
        add_action('wp_ajax_nopriv_dcf_create_customer_account', array($this, 'handle_create_customer_account'));
        add_action('wp_ajax_dcf_update_customer_address', array($this, 'handle_update_customer_address'));
        add_action('wp_ajax_nopriv_dcf_update_customer_address', array($this, 'handle_update_customer_address'));
        add_action('wp_ajax_dcf_schedule_pickup', array($this, 'handle_schedule_pickup'));
        add_action('wp_ajax_nopriv_dcf_schedule_pickup', array($this, 'handle_schedule_pickup'));
        add_action('wp_ajax_dcf_add_credit_card', array($this, 'handle_add_credit_card'));
        add_action('wp_ajax_nopriv_dcf_add_credit_card', array($this, 'handle_add_credit_card'));
        add_action('wp_ajax_dcf_get_form_html', array($this, 'handle_get_form_html'));
        add_action('wp_ajax_nopriv_dcf_get_form_html', array($this, 'handle_get_form_html'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'dcf-public',
            CMF_PLUGIN_URL . 'public/js/public-forms.js',
            array('jquery'),
            CMF_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'dcf-public',
            CMF_PLUGIN_URL . 'public/css/public-forms.css',
            array(),
            CMF_PLUGIN_VERSION
        );
        
        // Enqueue modern forms CSS
        wp_enqueue_style(
            'dcf-modern-forms',
            CMF_PLUGIN_URL . 'public/css/modern-forms.css',
            array('dcf-public'),
            CMF_PLUGIN_VERSION
        );
        
        // Enqueue form styles CSS
        wp_enqueue_style(
            'dcf-form-styles',
            CMF_PLUGIN_URL . 'public/css/form-styles.css',
            array('dcf-public'),
            CMF_PLUGIN_VERSION
        );
        
        // Get POS settings
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        $check_existing = false;
        
        if ($pos_system) {
            $manage_users = DCF_Plugin_Core::get_setting($pos_system . '_manage_users');
            $check_existing = $manage_users ? true : false;
        }
        
        // Localize script
        wp_localize_script('dcf-public', 'dcf_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_public_nonce'),
            'pos_system' => $pos_system,
            'check_existing_customer' => $check_existing,
            'login_url' => DCF_Plugin_Core::get_setting('login_page_url'),
            'messages' => array(
                'loading' => __('Loading...', 'dry-cleaning-forms'),
                'error' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'required_field' => __('This field is required.', 'dry-cleaning-forms'),
                'invalid_email' => __('Please enter a valid email address.', 'dry-cleaning-forms'),
                'invalid_phone' => __('Please enter a valid phone number.', 'dry-cleaning-forms'),
                'customer_exists' => __('You already have an account. Please login instead.', 'dry-cleaning-forms')
            )
        ));
    }
    
    /**
     * Render form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render_form_shortcode($atts) {
        // For block editor REST API requests, return a placeholder
        if (defined('REST_REQUEST') && REST_REQUEST) {
            // Check if this is a block editor preview request
            $rest_route = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (strpos($rest_route, '/wp/v2/') !== false || strpos($rest_route, '/wp-json/') !== false) {
                $id = isset($atts['id']) ? intval($atts['id']) : 0;
                return sprintf(
                    '<div class="dcf-form-placeholder" style="padding: 20px; border: 2px dashed #ddd; text-align: center; background: #f5f5f5;">' .
                    '<p style="margin: 0; color: #666;">%s</p></div>',
                    sprintf(__('Dry Cleaning Form (ID: %d) - Form will display on the frontend', 'dry-cleaning-forms'), $id)
                );
            }
        }
        
        // Don't render during admin AJAX requests (except for popup form loading)
        if (defined('DOING_AJAX') && DOING_AJAX && is_admin()) {
            // Allow rendering for popup form loading
            if ((isset($_POST['action']) && $_POST['action'] === 'dcf_get_form_html') || 
                (isset($_POST['dcf_ajax_form_render']) && $_POST['dcf_ajax_form_render'])) {
                // Continue with rendering
            } else {
                return '';
            }
        }
        
        $atts = shortcode_atts(array(
            'id' => 0,
            'ajax' => 'true',
            'show_title' => 'true',
            'show_description' => 'true',
            'css_class' => ''
        ), $atts);
        
        // Debug: Log shortcode call (commented out to prevent JSON errors)
        // error_log('DCF Shortcode called with ID: ' . $atts['id']);
        
        if (empty($atts['id'])) {
            return '<p>' . __('Form ID is required.', 'dry-cleaning-forms') . '</p>';
        }
        
        try {
            // Ensure classes are loaded
            if (!class_exists('DCF_Form_Builder')) {
                return '<p>' . __('Form system not available.', 'dry-cleaning-forms') . '</p>';
            }
            
            $form_builder = new DCF_Form_Builder();
            
            // Check if form exists first
            $form = $form_builder->get_form($atts['id']);
            if (!$form) {
                // error_log('DCF Form not found with ID: ' . $atts['id']);
                return '<p>Form not found (ID: ' . esc_html($atts['id']) . '). Please check if the form exists.</p>';
            }
            
            // error_log('DCF Form found: ' . $form->form_name);
            
            $result = $form_builder->render_form($atts['id'], array(
                'ajax' => $atts['ajax'] === 'true',
                'show_title' => $atts['show_title'] === 'true',
                'show_description' => $atts['show_description'] === 'true',
                'css_class' => $atts['css_class']
            ));
            
            if (is_wp_error($result)) {
                // error_log('DCF Form render error: ' . $result->get_error_message());
                return '<p>' . esc_html($result->get_error_message()) . '</p>';
            }
            
            // error_log('DCF Form rendered successfully');
            return $result;
        } catch (Exception $e) {
            // error_log('DCF Shortcode exception: ' . $e->getMessage());
            return '<p>Error rendering form: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Render signup form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Signup form HTML
     */
    public function render_signup_form_shortcode($atts) {
        // Don't render during REST API requests
        if ((defined('REST_REQUEST') && REST_REQUEST) || 
            (defined('DOING_AJAX') && DOING_AJAX && is_admin()) ||
            (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return '[dcf_signup_form]';
        }
        
        $atts = shortcode_atts(array(
            'css_class' => ''
        ), $atts);
        
        ob_start();
        ?>
        <div class="dcf-signup-form-container <?php echo esc_attr($atts['css_class']); ?>">
            <div class="dcf-signup-progress">
                <div class="dcf-progress-step active" data-step="1">
                    <span class="dcf-step-number">1</span>
                    <span class="dcf-step-label"><?php _e('Basic Info', 'dry-cleaning-forms'); ?></span>
                </div>
                <div class="dcf-progress-step" data-step="2">
                    <span class="dcf-step-number">2</span>
                    <span class="dcf-step-label"><?php _e('Service Type', 'dry-cleaning-forms'); ?></span>
                </div>
                <div class="dcf-progress-step" data-step="3">
                    <span class="dcf-step-number">3</span>
                    <span class="dcf-step-label"><?php _e('Address', 'dry-cleaning-forms'); ?></span>
                </div>
                <div class="dcf-progress-step" data-step="4">
                    <span class="dcf-step-number">4</span>
                    <span class="dcf-step-label"><?php _e('Pickup', 'dry-cleaning-forms'); ?></span>
                </div>
            </div>
            
            <div class="dcf-signup-form-content">
                <?php echo $this->render_signup_step_1(); ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render contact form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Contact form HTML
     */
    public function render_contact_form_shortcode($atts) {
        // Don't render during REST API requests
        if ((defined('REST_REQUEST') && REST_REQUEST) || 
            (defined('DOING_AJAX') && DOING_AJAX && is_admin()) ||
            (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return '[dcf_contact_form]';
        }
        
        $atts = shortcode_atts(array(
            'title' => __('Contact Us', 'dry-cleaning-forms'),
            'css_class' => ''
        ), $atts);
        
        ob_start();
        ?>
        <div class="dcf-contact-form-container <?php echo esc_attr($atts['css_class']); ?>">
            <h3 class="dcf-form-title"><?php echo esc_html($atts['title']); ?></h3>
            
            <form class="dcf-contact-form" method="post" data-ajax="true">
                <?php wp_nonce_field('dcf_contact_form', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_submit_form">
                <input type="hidden" name="form_type" value="contact">
                
                <div class="dcf-field">
                    <label for="dcf_name" class="dcf-field-label">
                        <?php _e('Name', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="text" id="dcf_name" name="dcf_field[name]" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_email" class="dcf-field-label">
                        <?php _e('Email', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="email" id="dcf_email" name="dcf_field[email]" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_phone" class="dcf-field-label">
                        <?php _e('Phone', 'dry-cleaning-forms'); ?>
                    </label>
                    <input type="tel" id="dcf_phone" name="dcf_field[phone]" class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_subject" class="dcf-field-label">
                        <?php _e('Subject', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="text" id="dcf_subject" name="dcf_field[subject]" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_message" class="dcf-field-label">
                        <?php _e('Message', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <textarea id="dcf_message" name="dcf_field[message]" rows="5" required class="dcf-textarea"></textarea>
                </div>
                
                <div class="dcf-form-submit">
                    <button type="submit" class="dcf-submit-button">
                        <?php _e('Send Message', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render opt-in form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Opt-in form HTML
     */
    public function render_optin_form_shortcode($atts) {
        // Don't render during REST API requests
        if ((defined('REST_REQUEST') && REST_REQUEST) || 
            (defined('DOING_AJAX') && DOING_AJAX && is_admin()) ||
            (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return '[dcf_optin_form]';
        }
        
        $atts = shortcode_atts(array(
            'title' => __('Stay Updated', 'dry-cleaning-forms'),
            'description' => __('Subscribe to receive updates and special offers.', 'dry-cleaning-forms'),
            'css_class' => '',
            'style' => 'inline' // inline or popup
        ), $atts);
        
        ob_start();
        ?>
        <div class="dcf-optin-form-container dcf-optin-<?php echo esc_attr($atts['style']); ?> <?php echo esc_attr($atts['css_class']); ?>">
            <h4 class="dcf-optin-title"><?php echo esc_html($atts['title']); ?></h4>
            <p class="dcf-optin-description"><?php echo esc_html($atts['description']); ?></p>
            
            <form class="dcf-optin-form" method="post" data-ajax="true">
                <?php wp_nonce_field('dcf_optin_form', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_submit_form">
                <input type="hidden" name="form_type" value="optin">
                
                <div class="dcf-optin-fields">
                    <div class="dcf-field">
                        <input type="email" name="dcf_field[email]" placeholder="<?php esc_attr_e('Your email address', 'dry-cleaning-forms'); ?>" required class="dcf-input">
                    </div>
                    
                    <div class="dcf-field">
                        <input type="text" name="dcf_field[first_name]" placeholder="<?php esc_attr_e('First name (optional)', 'dry-cleaning-forms'); ?>" class="dcf-input">
                    </div>
                </div>
                
                <div class="dcf-form-submit">
                    <button type="submit" class="dcf-submit-button">
                        <?php _e('Subscribe', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Process shortcodes in block content for FSE themes
     */
    public function process_shortcodes_in_blocks($block_content, $block) {
        // Only process certain block types that might contain shortcodes
        $shortcode_blocks = array('core/paragraph', 'core/html', 'core/shortcode');
        
        if (in_array($block['blockName'], $shortcode_blocks)) {
            return do_shortcode($block_content);
        }
        
        return $block_content;
    }
    
    /**
     * Ensure shortcode support for FSE themes
     */
    public function ensure_shortcode_support() {
        // Re-register shortcodes to ensure they're available
        global $shortcode_tags;
        
        // Log current shortcode status
        // error_log('DCF: Ensuring shortcode support. Current DCF shortcodes: ' . 
        //          implode(', ', array_filter(array_keys($shortcode_tags), function($tag) {
        //              return strpos($tag, 'dcf_') === 0;
        //          })));
        
        // Force re-registration if needed
        if (!isset($shortcode_tags['dcf_test'])) {
            // error_log('DCF: Re-registering shortcodes as they were not found');
            $this->register_shortcodes();
        }
    }
    
    /**
     * Register shortcodes (separate method for re-use)
     */
    private function register_shortcodes() {
        add_shortcode('dcf_form', array($this, 'render_form_shortcode'));
        add_shortcode('dcf_signup_form', array($this, 'render_signup_form_shortcode'));
        add_shortcode('dcf_contact_form', array($this, 'render_contact_form_shortcode'));
        add_shortcode('dcf_optin_form', array($this, 'render_optin_form_shortcode'));
        
        // Debug shortcode
        add_shortcode('dcf_test', function() {
            // error_log('DCF Test shortcode called');
            return '<p style="background: yellow; padding: 10px; border: 2px solid red;">DCF Shortcodes are working!</p>';
        });
    }
    
    /**
     * Add shortcode test endpoint
     */
    public function add_shortcode_test_endpoint() {
        add_rewrite_rule('^dcf-test/?$', 'index.php?dcf_test_page=1', 'top');
        add_filter('query_vars', function($vars) {
            $vars[] = 'dcf_test_page';
            return $vars;
        });
        add_action('template_redirect', array($this, 'handle_shortcode_test_page'));
    }
    
    /**
     * Handle shortcode test page
     */
    public function handle_shortcode_test_page() {
        if (get_query_var('dcf_test_page')) {
            global $shortcode_tags;
            
            $dcf_shortcodes = array_filter(array_keys($shortcode_tags), function($tag) {
                return strpos($tag, 'dcf_') === 0;
            });
            
            header('Content-Type: text/html');
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>DCF Shortcode Test</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; }
                    .test-result { background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px; }
                    .success { background: #d4edda; border: 1px solid #c3e6cb; }
                    .error { background: #f8d7da; border: 1px solid #f5c6cb; }
                </style>
            </head>
            <body>
                <h1>DCF Shortcode Test Page</h1>
                
                <div class="test-result">
                    <h3>Plugin Status</h3>
                    <p>Plugin is loaded: <strong>Yes</strong></p>
                    <p>Registered DCF shortcodes: <strong><?php echo implode(', ', $dcf_shortcodes); ?></strong></p>
                    <p>Total shortcodes: <strong><?php echo count($shortcode_tags); ?></strong></p>
                </div>
                
                <div class="test-result">
                    <h3>Test Shortcode Result</h3>
                    <?php echo do_shortcode('[dcf_test]'); ?>
                </div>
                
                <div class="test-result">
                    <h3>Contact Form Test</h3>
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
     * Debug function to list all registered shortcodes
     */
    public function debug_list_shortcodes() {
        global $shortcode_tags;
        
        $dcf_shortcodes = array();
        foreach ($shortcode_tags as $tag => $callback) {
            if (strpos($tag, 'dcf_') === 0) {
                $dcf_shortcodes[] = $tag;
            }
        }
        
        wp_send_json_success(array(
            'dcf_shortcodes' => $dcf_shortcodes,
            'all_shortcodes_count' => count($shortcode_tags)
        ));
    }
    
    /**
     * Render signup step 1 (Basic Information)
     *
     * @return string Step 1 HTML
     */
    private function render_signup_step_1() {
        ob_start();
        ?>
        <div class="dcf-signup-step" data-step="1">
            <h3 class="dcf-step-title"><?php _e('Basic Information', 'dry-cleaning-forms'); ?></h3>
            
            <form class="dcf-signup-form" data-step="1">
                <?php wp_nonce_field('dcf_signup_step_1', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_signup_step">
                <input type="hidden" name="step" value="1">
                
                <?php 
                // UTM parameter hidden fields
                $utm_params = array(
                    'utm_source' => '',
                    'utm_medium' => '',
                    'utm_campaign' => '',
                    'utm_content' => '',
                    'utm_keyword' => '',
                    'utm_matchtype' => '',
                    'campaign_id' => '',
                    'ad_group_id' => '',
                    'ad_id' => ''
                );
                
                // Get UTM parameters from URL
                foreach ($utm_params as $param => $default) {
                    $value = isset($_GET[$param]) ? sanitize_text_field($_GET[$param]) : $default;
                    echo '<input type="hidden" name="' . esc_attr($param) . '" value="' . esc_attr($value) . '" id="dcf_' . esc_attr($param) . '">';
                }
                ?>
                
                <div class="dcf-field">
                    <label for="dcf_first_name" class="dcf-field-label">
                        <?php _e('First Name', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="text" id="dcf_first_name" name="first_name" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_last_name" class="dcf-field-label">
                        <?php _e('Last Name', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="text" id="dcf_last_name" name="last_name" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_phone" class="dcf-field-label">
                        <?php _e('Phone Number', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="tel" id="dcf_phone" name="phone" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_email" class="dcf-field-label">
                        <?php _e('Email Address', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="email" id="dcf_email" name="email" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_promo_code" class="dcf-field-label">
                        <?php _e('Promo Code', 'dry-cleaning-forms'); ?>
                    </label>
                    <input type="text" id="dcf_promo_code" name="promo_code" class="dcf-input" placeholder="<?php _e('Enter promo code (optional)', 'dry-cleaning-forms'); ?>">
                </div>
                
                <div class="dcf-form-submit">
                    <button type="submit" class="dcf-submit-button">
                        <?php _e('Continue', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Check if this is a form builder form
        if (isset($_POST['dcf_form_id'])) {
            $this->handle_form_builder_submission();
            return;
        }
        
        // Verify nonce for built-in forms
        if (!wp_verify_nonce($_POST['dcf_nonce'], 'dcf_contact_form') && 
            !wp_verify_nonce($_POST['dcf_nonce'], 'dcf_optin_form')) {
            wp_send_json_error(array('message' => __('Security check failed', 'dry-cleaning-forms')));
        }
        
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_data = isset($_POST['dcf_field']) ? $_POST['dcf_field'] : array();
        $form_data = DCF_Plugin_Core::sanitize_form_data($form_data);
        
        // Create submission record
        $submission_id = DCF_Plugin_Core::create_submission($form_type, $form_data, 1, 'completed');
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => __('Failed to save submission', 'dry-cleaning-forms')));
        }
        
        // Send webhook
        DCF_Webhook_Handler::send_form_submission_webhook($form_type, $form_data, $submission_id);
        
        // Send email notifications
        DCF_Email_Notifications::send_form_submission_notifications($form_type, $form_data, $submission_id);
        
        $success_message = DCF_Plugin_Core::get_setting('success_message', __('Thank you! Your submission has been received.', 'dry-cleaning-forms'));
        
        wp_send_json_success(array(
            'message' => $success_message,
            'submission_id' => $submission_id
        ));
    }
    
    /**
     * Handle form builder form submission
     */
    private function handle_form_builder_submission() {
        // error_log('DCF: handle_form_builder_submission called');
        
        try {
            $form_id = intval($_POST['dcf_form_id']);
            // error_log('DCF: Form ID: ' . $form_id);
            
            // Verify nonce for form builder forms
            if (!wp_verify_nonce($_POST['dcf_nonce'], 'dcf_submit_form_' . $form_id)) {
                // error_log('DCF: Nonce verification failed for form ' . $form_id);
                wp_send_json_error(array('message' => __('Security check failed', 'dry-cleaning-forms')));
            }
            
            // error_log('DCF: Nonce verified successfully');
        
        // Get form configuration
        $form_builder = new DCF_Form_Builder();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_send_json_error(array('message' => __('Form not found', 'dry-cleaning-forms')));
        }
        
        // Get form data
        $form_data = isset($_POST['dcf_field']) ? $_POST['dcf_field'] : array();
        $form_data = DCF_Plugin_Core::sanitize_form_data($form_data);
        
        // Validate required fields
        $form_config = $form->form_config; // Already decoded by get_form method
        $fields = isset($form_config['fields']) ? $form_config['fields'] : array();
        
        foreach ($fields as $field) {
            if (isset($field['required']) && $field['required']) {
                if (empty($form_data[$field['id']])) {
                    $field_label = !empty($field['label']) ? $field['label'] : 'Field ' . $field['id'];
                    wp_send_json_error(array(
                        'message' => sprintf(__('%s is required', 'dry-cleaning-forms'), $field_label)
                    ));
                }
            }
        }
        
        // Initialize integration data array
        $integration_data = array();
        
        // Check if POS integration is enabled
        $pos_integration = isset($form_config['pos_integration']) ? $form_config['pos_integration'] : array();
        
        if (!empty($pos_integration['enabled'])) {
            $pos_system = DCF_Plugin_Core::get_setting('pos_system');
            
            if ($pos_system) {
                $integrations_manager = new DCF_Integrations_Manager();
                $integration = $integrations_manager->get_integration($pos_system);
                
                if ($integration && $integration->is_configured()) {
                    // Check if customer already exists (handle case-insensitive field names)
                    $email = '';
                    if (isset($form_data['email'])) {
                        $email = $form_data['email'];
                    } elseif (isset($form_data['Email'])) {
                        $email = $form_data['Email'];
                    }
                    
                    $phone = '';
                    if (isset($form_data['phone'])) {
                        $phone = $form_data['phone'];
                    } elseif (isset($form_data['Phone'])) {
                        $phone = $form_data['Phone'];
                    }
                    
                    $customer_existed = false;
                    $customer_updated = false;
                    $customer_id = null;
                    
                    if ($email && $phone && !empty($pos_integration['check_existing_customer'])) {
                        // Log what we're checking
                        DCF_Plugin_Core::log_integration($pos_system, 'form_submission_check', 
                            array('email' => $email, 'phone' => $phone, 'form_id' => $form_id), 
                            'Checking for existing customer', 'info');
                        
                        $check_result = $integration->customer_exists($email, $phone);
                        
                        if (!is_wp_error($check_result) && $check_result['exists']) {
                            $customer_existed = true;
                            $customer_id = isset($check_result['customer']['id']) ? $check_result['customer']['id'] : null;
                            
                            // Update existing customer if enabled
                            if ($customer_id && !empty($pos_integration['update_customer'])) {
                                // Ensure email and phone are in the update data
                                $update_data = array(
                                    'email' => $email,
                                    'phone' => $phone
                                );
                                $update_result = $integration->update_customer($customer_id, $update_data);
                                
                                if (!is_wp_error($update_result)) {
                                    // Check if it was actually updated or just already had the email
                                    if (isset($update_result['updated']) && $update_result['updated'] === true) {
                                        $customer_updated = true;
                                        $integration_data['update_message'] = $update_result['message'];
                                    } else {
                                        $customer_updated = false;
                                        $integration_data['update_message'] = isset($update_result['message']) ? $update_result['message'] : 'Email already exists';
                                    }
                                    DCF_Plugin_Core::log_integration($pos_system, 'update_customer', $form_data, $update_result, 'success');
                                }
                            }
                        }
                    }
                    
                    // Create new customer if doesn't exist and creation is enabled
                    if (!$customer_existed && !empty($pos_integration['create_customer'])) {
                        $create_result = $integration->create_customer($form_data);
                        
                        if (!is_wp_error($create_result)) {
                            $customer_id = isset($create_result['id']) ? $create_result['id'] : 
                                          (isset($create_result['customer_id']) ? $create_result['customer_id'] : null);
                            
                            DCF_Plugin_Core::log_integration($pos_system, 'create_customer', $form_data, $create_result, 'success');
                            
                            $integration_data['customer_created'] = true;
                            $integration_data['pos_customer_id'] = $customer_id;
                        } else {
                            DCF_Plugin_Core::log_integration($pos_system, 'create_customer', $form_data, 
                                array('error' => $create_result->get_error_message()), 'error');
                        }
                    }
                    
                    // Store integration results
                    $integration_data['pos_system'] = $pos_system;
                    $integration_data['customer_existed'] = $customer_existed;
                    $integration_data['customer_updated'] = $customer_updated;
                    if ($customer_id) {
                        $integration_data['pos_customer_id'] = $customer_id;
                    }
                }
            }
        }
        
        // Create submission record
        $submission_id = DCF_Plugin_Core::create_submission($form_id, $form_data, 1, 'completed');
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => __('Failed to save submission', 'dry-cleaning-forms')));
        }
        
        // Update submission with integration data if available
        if (!empty($integration_data)) {
            DCF_Plugin_Core::update_submission($submission_id, array(
                'integration_data' => wp_json_encode($integration_data)
            ));
        }
        
        // Send webhook
        DCF_Webhook_Handler::send_form_submission_webhook('form_builder', $form_data, $submission_id, $form_id);
        
        // Send email notifications
        DCF_Email_Notifications::send_form_submission_notifications('form_builder', $form_data, $submission_id, $form_id);
        
        // Get success message from form config or use default
        $success_message = '';
        
        // Check form config first
        if (isset($form_config['success_message']) && !empty($form_config['success_message'])) {
            $success_message = $form_config['success_message'];
        } else {
            // Try plugin settings
            $settings_message = DCF_Plugin_Core::get_setting('success_message');
            if (!empty($settings_message)) {
                $success_message = $settings_message;
            } else {
                // Use default fallback
                $success_message = __('Thank you! Your submission has been received.', 'dry-cleaning-forms');
            }
        }
        
        wp_send_json_success(array(
            'message' => $success_message,
            'submission_id' => $submission_id,
            'form_id' => $form_id
        ));
        
        } catch (Exception $e) {
            // error_log('DCF: Exception in handle_form_builder_submission: ' . $e->getMessage());
            // error_log('DCF: Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('An error occurred while processing your submission', 'dry-cleaning-forms')));
        }
    }
    
    /**
     * Handle signup step processing
     */
    public function handle_signup_step() {
        // Verify nonce
        $step = intval($_POST['step']);
        $nonce_action = 'dcf_signup_step_' . $step;
        
        if (!wp_verify_nonce($_POST['dcf_nonce'], $nonce_action)) {
            wp_send_json_error(array('message' => __('Security check failed', 'dry-cleaning-forms')));
        }
        
        $integrations_manager = new DCF_Integrations_Manager();
        
        switch ($step) {
            case 1:
                $this->handle_signup_step_1($integrations_manager);
                break;
            case 2:
                $this->handle_signup_step_2($integrations_manager);
                break;
            case 3:
                $this->handle_signup_step_3($integrations_manager);
                break;
            case 4:
                $this->handle_signup_step_4($integrations_manager);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid step', 'dry-cleaning-forms')));
        }
    }
    
    /**
     * Handle signup step 1 (Basic Information)
     *
     * @param DCF_Integrations_Manager $integrations_manager
     */
    private function handle_signup_step_1($integrations_manager) {
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'promo_code' => isset($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : ''
        );
        
        // Capture UTM parameters
        $utm_params = array(
            'utm_source' => isset($_POST['utm_source']) ? sanitize_text_field($_POST['utm_source']) : '',
            'utm_medium' => isset($_POST['utm_medium']) ? sanitize_text_field($_POST['utm_medium']) : '',
            'utm_campaign' => isset($_POST['utm_campaign']) ? sanitize_text_field($_POST['utm_campaign']) : '',
            'utm_content' => isset($_POST['utm_content']) ? sanitize_text_field($_POST['utm_content']) : '',
            'utm_keyword' => isset($_POST['utm_keyword']) ? sanitize_text_field($_POST['utm_keyword']) : '',
            'utm_matchtype' => isset($_POST['utm_matchtype']) ? sanitize_text_field($_POST['utm_matchtype']) : '',
            'campaign_id' => isset($_POST['campaign_id']) ? sanitize_text_field($_POST['campaign_id']) : '',
            'ad_group_id' => isset($_POST['ad_group_id']) ? sanitize_text_field($_POST['ad_group_id']) : '',
            'ad_id' => isset($_POST['ad_id']) ? sanitize_text_field($_POST['ad_id']) : ''
        );
        
        // Add UTM parameters to data array
        $data['utm_parameters'] = $utm_params;
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone']) || empty($data['email'])) {
            wp_send_json_error(array('message' => __('All fields are required', 'dry-cleaning-forms')));
        }
        
        // Validate email and phone
        if (!DCF_Plugin_Core::validate_email($data['email'])) {
            wp_send_json_error(array('message' => __('Please enter a valid email address', 'dry-cleaning-forms')));
        }
        
        if (!DCF_Plugin_Core::validate_phone($data['phone'])) {
            wp_send_json_error(array('message' => __('Please enter a valid phone number', 'dry-cleaning-forms')));
        }
        
        // Check if customer exists in POS system
        $customer_check = $integrations_manager->customer_exists($data['email'], $data['phone']);
        
        if (is_wp_error($customer_check)) {
            wp_send_json_error(array('message' => $customer_check->get_error_message()));
        }
        
        if ($customer_check['exists']) {
            $login_url = DCF_Plugin_Core::get_setting('login_page_url', home_url('/login'));
            wp_send_json_success(array(
                'customer_exists' => true,
                'message' => __('You already have an account with us. Please log in to continue.', 'dry-cleaning-forms'),
                'redirect_url' => $login_url
            ));
        }
        
        // Create submission record
        $submission_id = DCF_Plugin_Core::create_submission('customer_signup', $data, 1, 'step_1_completed');
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => __('Failed to save submission', 'dry-cleaning-forms')));
        }
        
        // Send webhook
        DCF_Webhook_Handler::send_step_completion_webhook($submission_id, 1, $data);
        
        wp_send_json_success(array(
            'submission_id' => $submission_id,
            'next_step' => $this->render_signup_step_2(),
            'message' => __('Information saved. Please continue to the next step.', 'dry-cleaning-forms')
        ));
    }
    
    /**
     * Handle signup step 2 (Service Selection)
     *
     * @param DCF_Integrations_Manager $integrations_manager
     */
    private function handle_signup_step_2($integrations_manager) {
        $submission_id = intval($_POST['submission_id']);
        $service_preference = sanitize_text_field($_POST['service_preference']);
        
        if (empty($service_preference)) {
            wp_send_json_error(array('message' => __('Please select a service option', 'dry-cleaning-forms')));
        }
        
        // Get existing submission data
        $submission = DCF_Plugin_Core::get_submission($submission_id);
        if (!$submission) {
            wp_send_json_error(array('message' => __('Submission not found', 'dry-cleaning-forms')));
        }
        
        $user_data = json_decode($submission->user_data, true);
        $user_data['service_preference'] = $service_preference;
        
        // Update submission
        DCF_Plugin_Core::update_submission($submission_id, array(
            'user_data' => wp_json_encode($user_data),
            'step_completed' => 2,
            'status' => 'step_2_completed'
        ));
        
        // Send webhook
        DCF_Webhook_Handler::send_step_completion_webhook($submission_id, 2, $user_data);
        
        if ($service_preference === 'pickup_delivery') {
            // Continue to address collection
            wp_send_json_success(array(
                'submission_id' => $submission_id,
                'next_step' => $this->render_signup_step_3(),
                'message' => __('Service preference saved. Please provide your address.', 'dry-cleaning-forms')
            ));
        } else {
            // Create customer account and finish for retail store or not sure customers
            $customer_result = $integrations_manager->create_customer($user_data);
            
            if (is_wp_error($customer_result)) {
                wp_send_json_error(array('message' => $customer_result->get_error_message()));
            }
            
            // Update submission as completed
            DCF_Plugin_Core::update_submission($submission_id, array(
                'step_completed' => 4,
                'status' => 'completed'
            ));
            
            // Send webhook
            DCF_Webhook_Handler::send_customer_created_webhook($customer_result['id'], $customer_result, $submission_id);
            
            // Send email notifications
            DCF_Email_Notifications::send_signup_completion_notifications($user_data, $submission_id);
            
            // Generate completion message with POS-specific instructions
            $completion_data = $this->generate_completion_message($service_preference, $user_data);
            
            wp_send_json_success(array(
                'completed' => true,
                'service_completion' => true,
                'service_type' => $service_preference,
                'message' => $completion_data['message'],
                'login_instructions' => $completion_data['login_instructions'],
                'redirect_url' => $completion_data['login_url']
            ));
        }
    }
    
    /**
     * Generate completion message with POS-specific login instructions
     *
     * @param string $service_preference Service preference (retail_store, not_sure)
     * @param array $user_data User data
     * @return array Completion data with message, instructions, and login URL
     */
    private function generate_completion_message($service_preference, $user_data) {
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        $login_url = DCF_Plugin_Core::get_setting('login_page_url', home_url('/login'));
        
        // Base message
        if ($service_preference === 'retail_store') {
            $message = __('Account Created Successfully! You can now visit any of our retail locations.', 'dry-cleaning-forms');
        } else {
            $message = __('Account Created Successfully! We\'ll contact you soon with more information about our services.', 'dry-cleaning-forms');
        }
        
        // POS-specific login instructions
        $login_instructions = '';
        switch ($pos_system) {
            case 'smrt':
                $login_instructions = __('You can now log in to the SMRT customer portal using your email address and the password that was sent to your email.', 'dry-cleaning-forms');
                break;
            case 'spot':
                $login_instructions = __('You can now log in to your SPOT account using your email address.', 'dry-cleaning-forms');
                break;
            case 'cleancloud':
                $login_instructions = __('You can now log in to CleanCloud using your phone number or email address.', 'dry-cleaning-forms');
                break;
            default:
                $login_instructions = __('You can now log in using your email address.', 'dry-cleaning-forms');
        }
        
        return array(
            'message' => $message,
            'login_instructions' => $login_instructions,
            'login_url' => $login_url,
            'pos_system' => $pos_system
        );
    }
    
    /**
     * Handle signup step 3 (Address Collection)
     *
     * @param DCF_Integrations_Manager $integrations_manager
     */
    private function handle_signup_step_3($integrations_manager) {
        $submission_id = intval($_POST['submission_id']);
        $address_data = array(
            'street' => sanitize_text_field($_POST['street']),
            'street2' => sanitize_text_field($_POST['street2']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'zip' => sanitize_text_field($_POST['zip'])
        );
        
        // Validate required address fields
        if (empty($address_data['street']) || empty($address_data['city']) || empty($address_data['state']) || empty($address_data['zip'])) {
            wp_send_json_error(array('message' => __('Please fill in all required address fields', 'dry-cleaning-forms')));
        }
        
        if (!DCF_Plugin_Core::validate_zip($address_data['zip'])) {
            wp_send_json_error(array('message' => __('Please enter a valid ZIP code', 'dry-cleaning-forms')));
        }
        
        // Get existing submission data
        $submission = DCF_Plugin_Core::get_submission($submission_id);
        if (!$submission) {
            wp_send_json_error(array('message' => __('Submission not found', 'dry-cleaning-forms')));
        }
        
        $user_data = json_decode($submission->user_data, true);
        $user_data['address'] = $address_data;
        
        // Check if customer was already created in a previous attempt
        $customer_id = isset($user_data['customer_id']) ? $user_data['customer_id'] : null;
        
        if (!$customer_id) {
            // Create customer account first
            $customer_result = $integrations_manager->create_customer($user_data);
            
            if (is_wp_error($customer_result)) {
                // Check if error is because customer already exists
                $error_message = $customer_result->get_error_message();
                if (stripos($error_message, 'already exists') !== false) {
                    // Try to get the existing customer
                    $customer_check = $integrations_manager->customer_exists($user_data['email'], $user_data['phone']);
                    if (!is_wp_error($customer_check) && $customer_check['exists'] && isset($customer_check['customer']['id'])) {
                        $customer_id = $customer_check['customer']['id'];
                    } else {
                        wp_send_json_error(array('message' => $error_message));
                    }
                } else {
                    wp_send_json_error(array('message' => $error_message));
                }
            } else {
                $customer_id = $customer_result['id'];
            }
        }
        
        // Update customer with address
        $update_result = $integrations_manager->update_customer($customer_id, array(
            'street' => $address_data['street'],
            'street2' => $address_data['street2'],
            'city' => $address_data['city'],
            'state' => $address_data['state'],
            'zip' => $address_data['zip']
        ));
        
        if (is_wp_error($update_result)) {
            wp_send_json_error(array('message' => $update_result->get_error_message()));
        }
        
        // Extract address ID from update result
        $address_id = null;
        if (isset($update_result['address_id'])) {
            $address_id = $update_result['address_id'];
        }
        
        // Skip getting pickup dates here to avoid timeout
        // We'll get them when step 4 loads
        $pickup_dates = array();
        
        $user_data['customer_id'] = $customer_id;
        
        // Update submission
        DCF_Plugin_Core::update_submission($submission_id, array(
            'user_data' => wp_json_encode($user_data),
            'step_completed' => 3,
            'status' => 'step_3_completed'
        ));
        
        // Send webhook
        DCF_Webhook_Handler::send_step_completion_webhook($submission_id, 3, $user_data);
        if (isset($customer_result)) {
            DCF_Webhook_Handler::send_customer_created_webhook($customer_id, $customer_result, $submission_id);
        }
        
        wp_send_json_success(array(
            'submission_id' => $submission_id,
            'customer_id' => $customer_id,
            'address_id' => $address_id,
            'phone' => isset($user_data['phone']) ? $user_data['phone'] : '',
            'pickup_dates' => $pickup_dates,
            'next_step' => $this->render_signup_step_4($pickup_dates),
            'message' => __('Address saved. Please select a pickup date and time.', 'dry-cleaning-forms')
        ));
    }
    
    /**
     * Handle signup step 4 (Schedule Pickup)
     *
     * @param DCF_Integrations_Manager $integrations_manager
     */
    private function handle_signup_step_4($integrations_manager) {
        $submission_id = intval($_POST['submission_id']);
        $customer_id = sanitize_text_field($_POST['customer_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $time_slot = sanitize_text_field($_POST['time_slot']);
        
        $payment_data = array(
            'card_number' => sanitize_text_field($_POST['card_number']),
            'expiry_month' => sanitize_text_field($_POST['expiry_month']),
            'expiry_year' => sanitize_text_field($_POST['expiry_year']),
            'security_code' => sanitize_text_field($_POST['security_code']),
            'billing_zip' => sanitize_text_field($_POST['billing_zip']),
            'amount' => 0 // Initial setup fee or deposit
        );
        
        // Validate payment data (basic validation - real validation happens in POS system)
        if (empty($payment_data['card_number']) || empty($payment_data['expiry_month']) || 
            empty($payment_data['expiry_year']) || empty($payment_data['security_code']) || 
            empty($payment_data['billing_zip'])) {
            wp_send_json_error(array('message' => __('Please fill in all payment fields', 'dry-cleaning-forms')));
        }
        
        // Get submission data
        $submission = DCF_Plugin_Core::get_submission($submission_id);
        if (!$submission) {
            wp_send_json_error(array('message' => __('Submission not found', 'dry-cleaning-forms')));
        }
        
        $user_data = json_decode($submission->user_data, true);
        
        // Schedule pickup appointment
        $appointment_data = array(
            'time_slot' => $time_slot,
            'address' => $user_data['address'],
            'notes' => ''
        );
        
        $appointment_result = $integrations_manager->schedule_pickup($customer_id, $pickup_date, $appointment_data);
        
        if (is_wp_error($appointment_result)) {
            wp_send_json_error(array('message' => $appointment_result->get_error_message()));
        }
        
        // Process payment if amount > 0
        if ($payment_data['amount'] > 0) {
            $payment_result = $integrations_manager->process_payment($customer_id, $payment_data);
            
            if (is_wp_error($payment_result)) {
                wp_send_json_error(array('message' => $payment_result->get_error_message()));
            }
            
            // Send payment webhook
            DCF_Webhook_Handler::send_payment_processed_webhook($payment_result['id'], 'success', $payment_result, $submission_id);
        }
        
        // Update submission as completed
        DCF_Plugin_Core::update_submission($submission_id, array(
            'step_completed' => 4,
            'status' => 'completed'
        ));
        
        // Send webhooks
        DCF_Webhook_Handler::send_step_completion_webhook($submission_id, 4, $user_data, 'completed');
        DCF_Webhook_Handler::send_appointment_scheduled_webhook($appointment_result['id'], $appointment_result, $submission_id);
        
        // Send email notifications
        DCF_Email_Notifications::send_signup_completion_notifications($user_data, $submission_id);
        
        $success_message = DCF_Plugin_Core::get_setting('success_message');
        $login_url = DCF_Plugin_Core::get_setting('login_page_url', home_url('/login'));
        
        wp_send_json_success(array(
            'completed' => true,
            'message' => $success_message,
            'appointment' => $appointment_result,
            'redirect_url' => $login_url
        ));
    }
    
    /**
     * Handle get pickup dates AJAX request
     */
    public function handle_get_pickup_dates() {
        if (!wp_verify_nonce($_POST['nonce'], 'dcf_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'dry-cleaning-forms')));
        }
        
        $customer_id = sanitize_text_field($_POST['customer_id']);
        $address = $_POST['address'];
        
        $integrations_manager = new DCF_Integrations_Manager();
        $pickup_dates = $integrations_manager->get_pickup_dates($customer_id, $address);
        
        if (is_wp_error($pickup_dates)) {
            wp_send_json_error(array('message' => $pickup_dates->get_error_message()));
        }
        
        wp_send_json_success(array('pickup_dates' => $pickup_dates));
    }
    
    /**
     * Render signup step 2 (Service Selection)
     *
     * @return string Step 2 HTML
     */
    private function render_signup_step_2() {
        ob_start();
        ?>
        <div class="dcf-signup-step" data-step="2">
            <h3 class="dcf-step-title"><?php _e('How do you plan to use our service?', 'dry-cleaning-forms'); ?></h3>
            
            <form class="dcf-signup-form" data-step="2">
                <?php wp_nonce_field('dcf_signup_step_2', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_signup_step">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="submission_id" value="">
                
                <div class="dcf-service-options">
                    <div class="dcf-service-option">
                        <input type="radio" id="dcf_retail_store" name="service_preference" value="retail_store" class="dcf-radio">
                        <label for="dcf_retail_store" class="dcf-service-label">
                            <span class="dcf-service-title"><?php _e('Visit a retail store', 'dry-cleaning-forms'); ?></span>
                            <span class="dcf-service-description"><?php _e('Drop off and pick up at one of our locations', 'dry-cleaning-forms'); ?></span>
                        </label>
                        <span class="dcf-checkmark">✓</span>
                    </div>
                    
                    <div class="dcf-service-option">
                        <input type="radio" id="dcf_pickup_delivery" name="service_preference" value="pickup_delivery" class="dcf-radio">
                        <label for="dcf_pickup_delivery" class="dcf-service-label">
                            <span class="dcf-service-title"><?php _e('Use our FREE home pickup & delivery service', 'dry-cleaning-forms'); ?></span>
                            <span class="dcf-service-description"><?php _e('We come to you - convenient and free', 'dry-cleaning-forms'); ?></span>
                        </label>
                        <span class="dcf-checkmark">✓</span>
                    </div>
                    
                    <div class="dcf-service-option">
                        <input type="radio" id="dcf_not_sure" name="service_preference" value="not_sure" class="dcf-radio">
                        <label for="dcf_not_sure" class="dcf-service-label">
                            <span class="dcf-service-title"><?php _e('Not sure yet', 'dry-cleaning-forms'); ?></span>
                            <span class="dcf-service-description"><?php _e('I\'ll decide later', 'dry-cleaning-forms'); ?></span>
                        </label>
                        <span class="dcf-checkmark">✓</span>
                    </div>
                </div>
                
                <div class="dcf-form-submit">
                    <button type="submit" class="dcf-submit-button">
                        <?php _e('Continue', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render signup step 3 (Address Collection)
     *
     * @return string Step 3 HTML
     */
    private function render_signup_step_3() {
        ob_start();
        ?>
        <div class="dcf-signup-step" data-step="3">
            <h3 class="dcf-step-title"><?php _e('Pickup Address', 'dry-cleaning-forms'); ?></h3>
            
            <form class="dcf-signup-form" data-step="3">
                <?php wp_nonce_field('dcf_signup_step_3', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_signup_step">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="submission_id" value="">
                
                <div class="dcf-field">
                    <label for="dcf_street" class="dcf-field-label">
                        <?php _e('Street Address', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                    </label>
                    <input type="text" id="dcf_street" name="street" required class="dcf-input">
                </div>
                
                <div class="dcf-field">
                    <label for="dcf_street2" class="dcf-field-label">
                        <?php _e('Suite/Apartment #', 'dry-cleaning-forms'); ?>
                    </label>
                    <input type="text" id="dcf_street2" name="street2" class="dcf-input">
                </div>
                
                <div class="dcf-address-row">
                    <div class="dcf-field">
                        <label for="dcf_city" class="dcf-field-label">
                            <?php _e('City', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                        </label>
                        <input type="text" id="dcf_city" name="city" required class="dcf-input">
                    </div>
                    
                    <div class="dcf-field">
                        <label for="dcf_state" class="dcf-field-label">
                            <?php _e('State', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                        </label>
                        <select id="dcf_state" name="state" required class="dcf-select">
                            <option value=""><?php _e('Select State', 'dry-cleaning-forms'); ?></option>
                            <?php foreach (DCF_Plugin_Core::get_us_states() as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="dcf-field">
                        <label for="dcf_zip" class="dcf-field-label">
                            <?php _e('ZIP Code', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                        </label>
                        <input type="text" id="dcf_zip" name="zip" required class="dcf-input">
                    </div>
                </div>
                
                <div class="dcf-form-submit">
                    <button type="submit" class="dcf-submit-button">
                        <?php _e('Continue', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render signup step 4 (Schedule Pickup)
     *
     * @param array $pickup_dates Available pickup dates
     * @return string Step 4 HTML
     */
    private function render_signup_step_4($pickup_dates = array()) {
        ob_start();
        ?>
        <div class="dcf-signup-step" data-step="4">
            <h3 class="dcf-step-title"><?php _e('Schedule Pickup', 'dry-cleaning-forms'); ?></h3>
            
            <form class="dcf-signup-form" data-step="4">
                <?php wp_nonce_field('dcf_signup_step_4', 'dcf_nonce'); ?>
                <input type="hidden" name="action" value="dcf_signup_step">
                <input type="hidden" name="step" value="4">
                <input type="hidden" name="submission_id" value="">
                <input type="hidden" name="customer_id" value="">
                
                <div class="dcf-pickup-section">
                    <h4><?php _e('Select Pickup Date & Time', 'dry-cleaning-forms'); ?></h4>
                    
                    <div class="dcf-field">
                        <label for="dcf_pickup_date" class="dcf-field-label">
                            <?php _e('Pickup Date', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                        </label>
                        <select id="dcf_pickup_date" name="pickup_date" required class="dcf-select">
                            <option value=""><?php _e('Select Date', 'dry-cleaning-forms'); ?></option>
                            <?php foreach ($pickup_dates as $date_info): ?>
                                <option value="<?php echo esc_attr($date_info['date']); ?>">
                                    <?php echo esc_html(date('l, F j, Y', strtotime($date_info['date']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="dcf-field" id="dcf_time_slot_field">
                        <label for="dcf_time_slot" class="dcf-field-label">
                            <?php _e('Time Slot', 'dry-cleaning-forms'); ?> <span class="dcf-required">*</span>
                        </label>
                        <select id="dcf_time_slot" name="time_slot" required class="dcf-select">
                            <option value=""><?php _e('Select Time', 'dry-cleaning-forms'); ?></option>
                        </select>
                    </div>
                </div>
                
                
                <div class="dcf-form-submit">
                    <button type="button" id="dcf_schedule_pickup" class="dcf-submit-button" disabled>
                        <?php _e('Schedule Pickup', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('DCF: Embedded signup form script loaded');
            
            // Store pickup dates globally for the pickup handler
            window.dcfPickupDates = <?php echo wp_json_encode($pickup_dates); ?>;
            console.log('DCF: Stored pickup dates:', window.dcfPickupDates);
            
            // If no pickup dates are available, load them via AJAX
            if (!window.dcfPickupDates || window.dcfPickupDates.length === 0) {
                console.log('DCF: Loading pickup dates via AJAX...');
                var $dateSelect = $('#dcf_pickup_date');
                $dateSelect.prop('disabled', true);
                $dateSelect.empty().append('<option value="">Loading available dates...</option>');
                
                // Get customer ID and address ID from current submission
                var customerId = DCF.currentSubmission.customer_id;
                var addressId = DCF.currentSubmission.address_id;
                // Try different places where phone might be stored
                var phone = DCF.currentSubmission.data.phone || 
                           DCF.currentSubmission.phone || 
                           (DCF.currentSubmission.data && DCF.currentSubmission.data.user_data && DCF.currentSubmission.data.user_data.phone) ||
                           '';
                
                // Also check if we have it in the form from step 1
                if (!phone && window.dcf_phone_number) {
                    phone = window.dcf_phone_number;
                }
                
                console.log('DCF: Loading dates for customer:', customerId, 'address:', addressId, 'phone:', phone);
                console.log('DCF: Full currentSubmission:', DCF.currentSubmission);
                
                if (customerId && addressId && phone) {
                    $.ajax({
                        url: dcf_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'dcf_get_pickup_dates',
                            customer_id: customerId,
                            address: {
                                address_id: addressId,
                                phone: phone
                            },
                            nonce: dcf_ajax.nonce
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('DCF: Pickup dates response:', response);
                            if (response.success && response.data.pickup_dates) {
                                window.dcfPickupDates = response.data.pickup_dates;
                                console.log('DCF: Loaded pickup dates with time slots:', window.dcfPickupDates);
                                
                                // Populate the date dropdown
                                $dateSelect.empty().append('<option value="">Choose a date...</option>');
                                response.data.pickup_dates.forEach(function(dateInfo) {
                                    var dateObj = new Date(dateInfo.date);
                                    var dateLabel = dateObj.toLocaleDateString('en-US', { 
                                        weekday: 'long', 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    });
                                    $dateSelect.append('<option value="' + dateInfo.date + '">' + dateLabel + '</option>');
                                });
                                $dateSelect.prop('disabled', false);
                                
                                // If we previously had a date selected, trigger change to reload time slots
                                if ($dateSelect.val()) {
                                    $dateSelect.trigger('change');
                                }
                            } else {
                                $dateSelect.empty().append('<option value="">No dates available</option>');
                                alert('Unable to load pickup dates. Please try again.');
                            }
                        },
                        error: function() {
                            console.log('DCF: Error loading pickup dates');
                            $dateSelect.empty().append('<option value="">Error loading dates</option>');
                            alert('Error loading pickup dates. Please try again.');
                        }
                    });
                } else {
                    console.log('DCF: Missing required data for loading pickup dates');
                    $dateSelect.empty().append('<option value="">Unable to load dates</option>');
                }
            }
            
            // Update time slots when date changes
            $('#dcf_pickup_date').on('change', function() {
                console.log('DCF: Embedded handlePickupDateChange called');
                var selectedDate = $(this).val();
                var timeSlotSelect = $('#dcf_time_slot');
                var timeSlotField = $('#dcf_time_slot_field');
                var scheduleButton = $('#dcf_schedule_pickup');
                // Use the global window.dcfPickupDates which gets updated by AJAX
                var pickupDates = window.dcfPickupDates || <?php echo wp_json_encode($pickup_dates); ?>;
                
                console.log('DCF: Selected date:', selectedDate);
                console.log('DCF: Time slot field found:', timeSlotField.length);
                
                timeSlotSelect.empty().append('<option value=""><?php _e('Select Time', 'dry-cleaning-forms'); ?></option>');
                scheduleButton.prop('disabled', true);
                
                if (selectedDate) {
                    console.log('DCF: Looking for date in pickupDates:', selectedDate);
                    console.log('DCF: Available dates:', pickupDates.map(function(d) { return d.date; }));
                    
                    var dateInfo = pickupDates.find(function(date) {
                        return date.date === selectedDate;
                    });
                    
                    console.log('DCF: Found dateInfo:', dateInfo);
                    
                    if (dateInfo && dateInfo.timeSlots && dateInfo.timeSlots.length > 0) {
                        console.log('DCF: Processing timeSlots:', dateInfo.timeSlots);
                        
                        // Show the time slot field
                        timeSlotField.show().css('display', 'block');
                        
                        dateInfo.timeSlots.forEach(function(slot) {
                            // Check if slot is available (default to true if not specified)
                            var isAvailable = slot.available !== false;
                            
                            if (isAvailable && slot.id) {
                                // Format slot ID for display
                                var slotLabel = slot.id;
                                if (slot.id && slot.id.startsWith('anytime_')) {
                                    slotLabel = 'Anytime';
                                } else if (slot.id && slot.id.includes('_')) {
                                    // Try to extract time from ID
                                    var parts = slot.id.split('_');
                                    slotLabel = parts[parts.length - 1] || slot.id;
                                }
                                console.log('DCF: Adding time slot:', slot.id, 'with label:', slotLabel);
                                timeSlotSelect.append('<option value="' + slot.id + '">' + slotLabel + '</option>');
                            }
                        });
                        
                        console.log('DCF: Added', dateInfo.timeSlots.length, 'time slots');
                        console.log('DCF: Time slot field display:', timeSlotField.css('display'));
                    } else {
                        timeSlotField.hide();
                    }
                } else {
                    timeSlotField.hide();
                }
            });
            
            // Enable schedule button when both date and time are selected
            $('#dcf_pickup_date, #dcf_time_slot').on('change', function() {
                var dateSelected = $('#dcf_pickup_date').val();
                var timeSelected = $('#dcf_time_slot').val();
                var scheduleButton = $('#dcf_schedule_pickup');
                
                scheduleButton.prop('disabled', !(dateSelected && timeSelected));
            });
            
            // Handle schedule pickup button click
            $('#dcf_schedule_pickup').on('click', function(e) {
                e.preventDefault();
                if (typeof DCF !== 'undefined' && DCF.handleSchedulePickup) {
                    DCF.handleSchedulePickup($('.dcf-signup-form-container'));
                }
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle check existing customer AJAX request
     */
    public function handle_check_existing_customer() {
        // Get form ID first
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        // Verify nonce with form ID
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dcf_submit_form_' . $form_id)) {
            wp_send_json_error(array('message' => __('Invalid security token', 'dry-cleaning-forms')));
        }
        
        // Get email and phone
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? preg_replace('/[^0-9]/', '', $_POST['phone']) : '';
        
        if (empty($email) || empty($phone)) {
            wp_send_json_error(array('message' => __('Email and phone are required', 'dry-cleaning-forms')));
        }
        
        // Get form ID from request
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_success(array('exists' => false));
        }
        
        // Get form configuration
        $form_builder = new DCF_Form_Builder();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_send_json_success(array('exists' => false));
        }
        
        // Decode form_config if it's a JSON string
        $form_config = $form->form_config;
        if (is_string($form_config)) {
            $form_config = json_decode($form_config, true);
        }
        
        if (!isset($form_config['pos_integration'])) {
            wp_send_json_success(array('exists' => false));
        }
        
        $pos_integration = $form_config['pos_integration'];
        
        // Check if POS integration is enabled and customer checking is enabled
        if (empty($pos_integration['enabled']) || empty($pos_integration['check_existing_customer'])) {
            wp_send_json_success(array('exists' => false));
        }
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            wp_send_json_success(array('exists' => false));
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            wp_send_json_success(array('exists' => false));
        }
        
        // Check if customer exists
        $result = $integration->customer_exists($email, $phone);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Send response
        wp_send_json_success(array(
            'exists' => $result['exists'],
            'customer' => isset($result['customer']) ? $result['customer'] : null
        ));
    }
    
    /**
     * Handle create customer account AJAX request
     */
    public function handle_create_customer_account() {
        // Verify nonce - handle both form-specific and public nonce
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        $valid_nonce = false;
        if ($form_id && wp_verify_nonce($nonce, 'dcf_submit_form_' . $form_id)) {
            $valid_nonce = true;
        } elseif (wp_verify_nonce($nonce, 'dcf_public_nonce')) {
            $valid_nonce = true;
        }
        
        if (!$valid_nonce) {
            wp_send_json_error(array('message' => __('Invalid security token', 'dry-cleaning-forms')));
        }
        
        $customer_data = isset($_POST['customer_data']) ? $_POST['customer_data'] : array();
        
        if (empty($customer_data)) {
            wp_send_json_error(array('message' => __('Invalid form data', 'dry-cleaning-forms')));
        }
        
        // Try to get form configuration if form_id is provided
        $pos_integration = array();
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            
            if ($form && isset($form->form_config['pos_integration'])) {
                $pos_integration = $form->form_config['pos_integration'];
            }
        }
        
        // For hardcoded signup forms without form_id, assume full POS integration
        if (empty($pos_integration)) {
            $pos_integration = array(
                'enabled' => true,
                'create_customer' => true
            );
        }
        
        // Check if POS integration is enabled and customer creation is enabled
        if (empty($pos_integration['enabled']) || empty($pos_integration['create_customer'])) {
            // Just save the submission without POS integration
            $submission_id = DCF_Plugin_Core::create_submission($form_id ?: 'signup', $customer_data, 1, 'completed');
            wp_send_json_success(array('submission_id' => $submission_id));
            return;
        }
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            $submission_id = DCF_Plugin_Core::create_submission($form_id, $customer_data, 1, 'completed');
            wp_send_json_success(array('submission_id' => $submission_id));
            return;
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            $submission_id = DCF_Plugin_Core::create_submission($form_id, $customer_data, 1, 'completed');
            wp_send_json_success(array('submission_id' => $submission_id));
            return;
        }
        
        // Create customer in POS
        $result = $integration->create_customer($customer_data);
        
        if (is_wp_error($result)) {
            // Save submission with error status
            $submission_id = DCF_Plugin_Core::create_submission($form_id, $customer_data, 1, 'error');
            DCF_Plugin_Core::log_integration($pos_system, 'create_customer', $customer_data, $result->get_error_message());
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Get customer ID from result (different integrations might use different field names)
        $customer_id = null;
        if (isset($result['customer_id'])) {
            $customer_id = $result['customer_id'];
        } elseif (isset($result['id'])) {
            $customer_id = $result['id'];
        }
        
        // Save submission with POS customer ID
        $customer_data['pos_customer_id'] = $customer_id;
        $submission_id = DCF_Plugin_Core::create_submission($form_id, $customer_data, 1, 'completed');
        
        // Log successful integration
        DCF_Plugin_Core::log_integration($pos_system, 'create_customer', $customer_data, 'success', $result);
        
        wp_send_json_success(array(
            'submission_id' => $submission_id,
            'customer_id' => $customer_id
        ));
    }
    
    /**
     * Handle update customer address AJAX request
     */
    public function handle_update_customer_address() {
        // Verify nonce - handle both form-specific and public nonce
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        $valid_nonce = false;
        if ($form_id && wp_verify_nonce($nonce, 'dcf_submit_form_' . $form_id)) {
            $valid_nonce = true;
        } elseif (wp_verify_nonce($nonce, 'dcf_public_nonce')) {
            $valid_nonce = true;
        }
        
        if (!$valid_nonce) {
            wp_send_json_error(array('message' => __('Invalid security token', 'dry-cleaning-forms')));
        }
        
        $customer_data = isset($_POST['customer_data']) ? $_POST['customer_data'] : array();
        
        if (empty($customer_data)) {
            wp_send_json_error(array('message' => __('Invalid form data', 'dry-cleaning-forms')));
        }
        
        // Try to get form configuration if form_id is provided
        $pos_integration = array();
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            
            if ($form && isset($form->form_config['pos_integration'])) {
                $pos_integration = $form->form_config['pos_integration'];
            }
        }
        
        // For hardcoded signup forms without form_id, assume full POS integration
        if (empty($pos_integration)) {
            $pos_integration = array(
                'enabled' => true,
                'update_customer' => true,
                'create_route' => true
            );
        }
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            wp_send_json_success(array('updated' => true));
            return;
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            wp_send_json_success(array('updated' => true));
            return;
        }
        
        // Update customer address in POS
        $customer_id = isset($customer_data['pos_customer_id']) ? $customer_data['pos_customer_id'] : null;
        
        if (!$customer_id) {
            // Try to find customer by email/phone
            if (isset($customer_data['email']) && isset($customer_data['phone'])) {
                $result = $integration->customer_exists($customer_data['email'], $customer_data['phone']);
                if (!is_wp_error($result) && $result['exists'] && isset($result['customer']['id'])) {
                    $customer_id = $result['customer']['id'];
                }
            }
        }
        
        $address_id = null;
        
        if ($customer_id) {
            $update_result = $integration->update_customer($customer_id, array(
                'address' => isset($customer_data['address']) ? $customer_data['address'] : '',
                'apartment' => isset($customer_data['apartment']) ? $customer_data['apartment'] : '',
                'city' => isset($customer_data['city']) ? $customer_data['city'] : '',
                'state' => isset($customer_data['state']) ? $customer_data['state'] : '',
                'zip' => isset($customer_data['zip']) ? $customer_data['zip'] : ''
            ));
            
            if (is_wp_error($update_result)) {
                DCF_Plugin_Core::log_integration($pos_system, 'update_customer', $customer_data, $update_result->get_error_message());
            } else {
                DCF_Plugin_Core::log_integration($pos_system, 'update_customer', $customer_data, 'success', $update_result);
                
                // Extract address ID from update result
                if (isset($update_result['address_id'])) {
                    $address_id = $update_result['address_id'];
                }
            }
        }
        
        // Get available pickup dates if route creation is enabled
        $pickup_dates = array();
        
        // Debug logging
        error_log('DCF: Checking for pickup dates - address_id: ' . $address_id . ', customer_id: ' . $customer_id);
        
        // For hardcoded signup forms, always try to get pickup dates when address is updated
        if (method_exists($integration, 'get_pickup_dates') && $address_id) {
            // Pass address_id and phone to get_pickup_dates
            $address_data = array(
                'address_id' => $address_id,
                'phone' => isset($customer_data['phone']) ? $customer_data['phone'] : ''
            );
            error_log('DCF: Calling get_pickup_dates with data: ' . json_encode($address_data));
            $dates_result = $integration->get_pickup_dates($customer_id, $address_data);
            if (!is_wp_error($dates_result)) {
                $pickup_dates = $dates_result;
                error_log('DCF: Got pickup dates: ' . json_encode($pickup_dates));
            } else {
                // Log the error for debugging
                error_log('DCF: Error getting pickup dates: ' . $dates_result->get_error_message());
            }
        } else {
            error_log('DCF: Not fetching pickup dates - method exists: ' . (method_exists($integration, 'get_pickup_dates') ? 'yes' : 'no') . ', address_id: ' . $address_id);
        }
        
        // Debug logging
        error_log('DCF: Returning response - updated: true, address_id: ' . $address_id . ', pickup_dates count: ' . count($pickup_dates));
        
        wp_send_json_success(array(
            'updated' => true,
            'address_id' => $address_id,
            'pickup_dates' => $pickup_dates
        ));
    }
    
    /**
     * Handle schedule pickup AJAX request
     */
    public function handle_schedule_pickup() {
        // Verify nonce - handle both form-specific and public nonce
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        $valid_nonce = false;
        if ($form_id && wp_verify_nonce($nonce, 'dcf_submit_form_' . $form_id)) {
            $valid_nonce = true;
        } elseif (wp_verify_nonce($nonce, 'dcf_public_nonce')) {
            $valid_nonce = true;
        }
        
        if (!$valid_nonce) {
            wp_send_json_error(array('message' => __('Invalid security token', 'dry-cleaning-forms')));
        }
        
        $appointment_data = isset($_POST['appointment_data']) ? $_POST['appointment_data'] : array();
        
        if (empty($appointment_data)) {
            wp_send_json_error(array('message' => __('Invalid form data', 'dry-cleaning-forms')));
        }
        
        // Try to get form configuration if form_id is provided
        $pos_integration = array();
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            
            if ($form && isset($form->form_config['pos_integration'])) {
                $pos_integration = $form->form_config['pos_integration'];
            }
        }
        
        // For hardcoded signup forms without form_id, assume full POS integration
        if (empty($pos_integration)) {
            $pos_integration = array(
                'enabled' => true,
                'create_route' => true
            );
        }
        
        // Check if POS integration is enabled
        if (empty($pos_integration['enabled']) || empty($pos_integration['create_route'])) {
            wp_send_json_success(array('scheduled' => true));
            return;
        }
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            wp_send_json_success(array('scheduled' => true));
            return;
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            wp_send_json_success(array('scheduled' => true));
            return;
        }
        
        // Get customer ID from appointment data
        $customer_id = isset($appointment_data['customer_id']) ? $appointment_data['customer_id'] : null;
        
        if (!$customer_id) {
            wp_send_json_error(array('message' => __('Customer ID is required for scheduling', 'dry-cleaning-forms')));
        }
        
        // Schedule pickup in POS
        if (method_exists($integration, 'schedule_pickup')) {
            $result = $integration->schedule_pickup($customer_id, $appointment_data);
            
            if (is_wp_error($result)) {
                DCF_Plugin_Core::log_integration($pos_system, 'schedule_pickup', $appointment_data, $result->get_error_message());
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            
            DCF_Plugin_Core::log_integration($pos_system, 'schedule_pickup', $appointment_data, 'success', $result);
            
            wp_send_json_success(array(
                'scheduled' => true,
                'appointment' => $result
            ));
        } else {
            wp_send_json_success(array('scheduled' => true));
        }
    }
    
    /**
     * Handle add credit card AJAX request
     */
    public function handle_add_credit_card() {
        // Verify nonce - try both form-specific and public nonce
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        $valid_nonce = false;
        if ($form_id && wp_verify_nonce($nonce, 'dcf_submit_form_' . $form_id)) {
            $valid_nonce = true;
        } elseif (wp_verify_nonce($nonce, 'dcf_public_nonce')) {
            $valid_nonce = true;
        }
        
        if (!$valid_nonce) {
            wp_send_json_error(array('message' => __('Invalid security token', 'dry-cleaning-forms')));
        }
        
        $customer_id = isset($_POST['customer_id']) ? sanitize_text_field($_POST['customer_id']) : '';
        $card_data = isset($_POST['card_data']) ? $_POST['card_data'] : array();
        
        if (!$customer_id || empty($card_data)) {
            wp_send_json_error(array('message' => __('Invalid request data', 'dry-cleaning-forms')));
        }
        
        // Sanitize card data (don't log sensitive info)
        $card_data = array(
            'card_number' => sanitize_text_field($card_data['card_number']),
            'expiry_month' => sanitize_text_field($card_data['expiry_month']),
            'expiry_year' => sanitize_text_field($card_data['expiry_year']),
            'cvv' => sanitize_text_field($card_data['cvv']),
            'name_on_card' => sanitize_text_field($card_data['name_on_card'])
        );
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            wp_send_json_error(array('message' => __('POS system not configured', 'dry-cleaning-forms')));
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            wp_send_json_error(array('message' => __('POS integration not configured', 'dry-cleaning-forms')));
        }
        
        // Add credit card to POS
        if (method_exists($integration, 'put_credit_card')) {
            $result = $integration->put_credit_card($customer_id, $card_data);
            
            if (is_wp_error($result)) {
                DCF_Plugin_Core::log_integration($pos_system, 'put_credit_card', array('customer_id' => $customer_id), $result->get_error_message(), 'error');
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            
            DCF_Plugin_Core::log_integration($pos_system, 'put_credit_card', array('customer_id' => $customer_id), 'success', $result);
            
            wp_send_json_success(array(
                'card_added' => true,
                'message' => __('Payment information saved successfully', 'dry-cleaning-forms')
            ));
        } else {
            wp_send_json_error(array('message' => __('Credit card functionality not available for this POS system', 'dry-cleaning-forms')));
        }
    }
    
    /**
     * Handle AJAX request to get form HTML
     */
    public function handle_get_form_html() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_popup_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'dry-cleaning-forms')));
        }
        
        $form_id = intval($_POST['form_id'] ?? 0);
        $popup_mode = isset($_POST['popup_mode']) && $_POST['popup_mode'];
        
        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form ID', 'dry-cleaning-forms')));
        }
        
        // Try to render the form directly
        error_log('DCF: handle_get_form_html - Starting for form ID: ' . $form_id);
        
        // Get the form builder and render directly
        $form_builder = new DCF_Form_Builder();
        
        // First check if form exists
        $form = $form_builder->get_form($form_id);
        if (!$form) {
            error_log('DCF: Form ' . $form_id . ' not found in database');
            
            // Try a simple test form
            $test_html = '<form class="dcf-form" data-form-id="' . $form_id . '">';
            $test_html .= '<p>Test form ' . $form_id . ' - If you see this, forms are not loading properly.</p>';
            $test_html .= '<input type="email" name="email" placeholder="Enter email" required>';
            $test_html .= '<button type="submit">Submit</button>';
            $test_html .= '</form>';
            
            wp_send_json_success(array(
                'html' => $test_html,
                'form_id' => $form_id,
                'debug' => 'Form not found, showing test form'
            ));
            return;
        }
        
        error_log('DCF: Form found, attempting to render');
        
        // Use output buffering to capture the form
        ob_start();
        
        // Call the render method directly with force_render to bypass AJAX check
        $form_html = $form_builder->render_form($form_id, array(
            'popup_mode' => true,
            'ajax_load' => true,
            'show_title' => false,
            'show_description' => false,
            'force_render' => true  // This bypasses the AJAX check in render_form
        ));
        
        // Get any buffered output
        $buffered = ob_get_clean();
        
        // Combine buffered and returned HTML
        if (!empty($buffered)) {
            $form_html = $buffered . $form_html;
        }
        
        error_log('DCF: Form HTML length: ' . strlen($form_html));
        error_log('DCF: First 200 chars: ' . substr($form_html, 0, 200));
        
        // If still empty or just shortcode, something is wrong
        if (empty($form_html) || $form_html === '[dcf_form id="' . $form_id . '"]') {
            error_log('DCF: Form rendering failed, form_html is empty or shortcode');
            
            // Return a basic form as fallback
            $fallback_html = '<form class="dcf-form dcf-form-' . $form_id . '" data-form-id="' . $form_id . '">';
            $fallback_html .= '<div class="dcf-form-error">Unable to load form. Please refresh the page.</div>';
            $fallback_html .= '</form>';
            
            wp_send_json_success(array(
                'html' => $fallback_html,
                'form_id' => $form_id,
                'debug' => 'Form rendering failed'
            ));
            return;
        }
        
        wp_send_json_success(array(
            'html' => $form_html,
            'form_id' => $form_id
        ));
    }
} 