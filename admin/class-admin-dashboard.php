<?php
/**
 * Admin Dashboard
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Dashboard class
 */
class DCF_Admin_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle form submissions early
        add_action('admin_init', array($this, 'handle_early_form_submission'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_dcf_admin_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_dcf_test_ajax', array($this, 'test_ajax_handler'));
        add_action('wp_ajax_dcf_save_form', array($this, 'handle_save_form_direct'));
        add_action('wp_ajax_dcf_export_popup_data', array($this, 'handle_popup_export'));
        add_action('wp_ajax_dcf_export_ab_test_data', array($this, 'handle_ab_test_export'));
        add_action('wp_ajax_dcf_ab_test_action', array($this, 'handle_ab_test_ajax'));
        add_action('wp_ajax_dcf_get_template_preview', array($this, 'handle_template_preview'));
        add_action('wp_ajax_dcf_unique_form_save_12345', array($this, 'handle_unique_form_save'));
        add_action('wp_ajax_dcf_preview_template', array($this, 'handle_preview_template'));
        add_action('wp_ajax_dcf_create_from_template', array($this, 'handle_create_from_template'));
        add_action('wp_ajax_dcf_get_all_forms', array($this, 'handle_get_all_forms'));
        add_action('wp_ajax_dcf_get_form_data', array($this, 'handle_get_form_data'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Cleaner Marketing Forms', 'cleaner-marketing-forms'),
            __('CM Forms', 'cleaner-marketing-forms'),
            'manage_options',
            'cleaner-marketing-forms',
            array($this, 'dashboard_page'),
            'dashicons-feedback',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Dashboard', 'cleaner-marketing-forms'),
            __('Dashboard', 'cleaner-marketing-forms'),
            'manage_options',
            'cleaner-marketing-forms',
            array($this, 'dashboard_page')
        );
        
        // Form Builder submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Form Builder', 'cleaner-marketing-forms'),
            __('Form Builder', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-form-builder',
            array($this, 'form_builder_page')
        );
        
        // Templates submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Templates', 'cleaner-marketing-forms'),
            __('Templates', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-templates',
            array($this, 'templates_page')
        );
        
        // Popup Manager submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Popup Manager', 'cleaner-marketing-forms'),
            __('Popup Manager', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-popup-manager',
            array($this, 'popup_manager_page')
        );
        
        // Submissions submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Submissions', 'cleaner-marketing-forms'),
            __('Submissions', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-submissions',
            array($this, 'submissions_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Analytics', 'cleaner-marketing-forms'),
            __('Analytics', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-analytics',
            array($this, 'analytics_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'cleaner-marketing-forms',
            __('Settings', 'cleaner-marketing-forms'),
            __('Settings', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-settings',
            array($this, 'settings_page')
        );
        
        // Debug submenu (hidden from menu but accessible)
        add_submenu_page(
            null, // parent slug - null makes it hidden
            __('Debug Info', 'cleaner-marketing-forms'),
            __('Debug', 'cleaner-marketing-forms'),
            'manage_options',
            'cmf-debug',
            array($this, 'debug_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'cleaner-marketing-forms') === false && 
            strpos($hook, 'dcf-') === false && 
            strpos($hook, 'cmf-') === false && 
            strpos($hook, 'cm-forms') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        
        // Enqueue jQuery UI CSS for drag and drop styling
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // Admin CSS
        wp_enqueue_style(
            'dcf-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css',
            array(),
            '1.0.0'
        );
        
        // Admin JS
        wp_enqueue_script(
            'dcf-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            '1.0.0',
            true
        );
        
        // Page-specific scripts
        if (strpos($hook, 'cmf-popup-manager') !== false || strpos($hook, 'dcf-popup-manager') !== false) {
            // Visual editor scripts
            wp_enqueue_script(
                'dcf-visual-editor',
                plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                '1.0.0',
                true
            );
            
            wp_enqueue_script(
                'dcf-visual-editor-blocks',
                plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-blocks.js',
                array('dcf-visual-editor'),
                '1.0.0',
                true
            );
            
            wp_enqueue_script(
                'dcf-popup-admin',
                plugin_dir_url(dirname(__FILE__)) . 'admin/js/popup-admin.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Visual editor CSS
            wp_enqueue_style(
                'dcf-visual-editor-css',
                plugin_dir_url(dirname(__FILE__)) . 'admin/css/visual-editor.css',
                array(),
                '1.0.0'
            );
        }
        
        // Localize script for AJAX
        wp_localize_script('dcf-admin-js', 'dcf_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_admin_nonce'),
            'admin_url' => admin_url(),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
                'testing' => __('Testing...', 'dry-cleaning-forms'),
                'refreshing' => __('Refreshing...', 'dry-cleaning-forms'),
                'processing' => __('Processing...', 'dry-cleaning-forms'),
                'form_saved' => __('Form saved successfully!', 'dry-cleaning-forms'),
                'copied' => __('Copied!', 'dry-cleaning-forms'),
                'connection_success' => __('Connection successful!', 'dry-cleaning-forms'),
                'connection_error' => __('Connection failed!', 'dry-cleaning-forms'),
                'cache_cleared' => __('Cache cleared successfully!', 'dry-cleaning-forms'),
                'settings_imported' => __('Settings imported successfully!', 'dry-cleaning-forms'),
                'invalid_file' => __('Invalid file format!', 'dry-cleaning-forms'),
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'dry-cleaning-forms'),
                'select_action' => __('Please select an action.', 'dry-cleaning-forms'),
                'select_items' => __('Please select at least one item.', 'dry-cleaning-forms'),
                'confirm_action' => __('Are you sure you want to perform this action?', 'dry-cleaning-forms'),
                'error' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'confirm_delete_title' => __('Delete Field?', 'dry-cleaning-forms'),
            ),
            'messages' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
                'testing' => __('Testing...', 'dry-cleaning-forms'),
                'refreshing' => __('Refreshing...', 'dry-cleaning-forms'),
                'processing' => __('Processing...', 'dry-cleaning-forms'),
                'form_saved' => __('Form saved successfully!', 'dry-cleaning-forms'),
                'copied' => __('Copied!', 'dry-cleaning-forms'),
                'connection_success' => __('Connection successful!', 'dry-cleaning-forms'),
                'connection_error' => __('Connection failed!', 'dry-cleaning-forms'),
                'cache_cleared' => __('Cache cleared successfully!', 'dry-cleaning-forms'),
                'settings_imported' => __('Settings imported successfully!', 'dry-cleaning-forms'),
                'invalid_file' => __('Invalid file format!', 'dry-cleaning-forms'),
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'dry-cleaning-forms'),
                'select_action' => __('Please select an action.', 'dry-cleaning-forms'),
                'select_items' => __('Please select at least one item.', 'dry-cleaning-forms'),
                'confirm_action' => __('Are you sure you want to perform this action?', 'dry-cleaning-forms'),
                'error' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'confirm_delete_title' => __('Delete Field?', 'dry-cleaning-forms'),
            )
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function dashboard_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        // Get dashboard data
        $stats = $this->get_dashboard_stats();
        $recent_submissions = $this->get_recent_submissions();
        $integration_statuses = $this->get_integration_statuses();
        
        include CMF_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render forms page
     */
    public function form_builder_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        // Handle form actions
        if ($action === 'delete' && $form_id) {
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_form_' . $form_id)) {
                wp_die(__('Security check failed.', 'dry-cleaning-forms'));
            }
            
            $form_builder = new DCF_Form_Builder();
            if ($form_builder->delete_form($form_id)) {
                wp_redirect(admin_url('admin.php?page=cmf-form-builder&message=deleted'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=cmf-form-builder&message=delete_failed'));
                exit;
            }
        } elseif ($action === 'duplicate' && $form_id) {
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_form_' . $form_id)) {
                wp_die(__('Security check failed.', 'dry-cleaning-forms'));
            }
            
            $form_builder = new DCF_Form_Builder();
            $new_form_id = $form_builder->duplicate_form($form_id);
            if ($new_form_id) {
                wp_redirect(admin_url('admin.php?page=cmf-form-builder&message=duplicated'));
                exit;
            } else {
                wp_redirect(admin_url('admin.php?page=cmf-form-builder&message=duplicate_failed'));
                exit;
            }
        }
        
        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_form_editor($form_id);
                break;
            default:
                $this->render_forms_list();
                break;
        }
    }
    
    /**
     * Render submissions page
     */
    public function submissions_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        $submissions = $this->get_submissions();
        include CMF_PLUGIN_DIR . 'admin/views/submissions.php';
    }
    
    /**
     * Render analytics page
     */
    public function analytics_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'dcf-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css',
            array(),
            '1.0.0'
        );
        
        $analytics_data = $this->get_analytics_data();
        include CMF_PLUGIN_DIR . 'admin/views/analytics.php';
    }
    
    /**
     * Render settings page
     */
    public function settings_page() {
        $settings_page = new DCF_Settings_Page();
        $settings_page->render();
    }
    
    /**
     * Render templates page
     */
    public function templates_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        // Enqueue template-specific styles
        wp_enqueue_style(
            'dcf-templates-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/templates.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue template-specific scripts
        wp_enqueue_script(
            'dcf-templates-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/templates.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Get all templates
        $template_manager = DCF_Template_Manager::get_instance();
        $templates = $template_manager->get_all_templates();
        
        include CMF_PLUGIN_DIR . 'admin/views/templates.php';
    }
    
    /**
     * Render popup manager page
     */
    public function popup_manager_page() {
        if (!DCF_Plugin_Core::current_user_can()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'dry-cleaning-forms'));
        }
        
        // Form submissions are now handled in admin_init hook
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $popup_id = isset($_GET['popup_id']) ? intval($_GET['popup_id']) : 0;
        
        switch ($action) {
            case 'templates':
                $this->render_popup_templates();
                break;
            case 'edit':
            case 'new':
                $this->render_popup_editor($popup_id);
                break;
            case 'visual-edit':
                $this->render_popup_visual_editor($popup_id);
                break;
            case 'preview':
                $this->render_popup_preview($popup_id);
                break;
            case 'analytics':
                $this->render_popup_analytics($popup_id);
                break;
            case 'ab-testing':
                $this->render_ab_testing();
                break;
            case 'ab-test-edit':
                $this->render_ab_test_editor();
                break;
            case 'ab-test-analytics':
                $this->render_ab_test_analytics();
                break;
            case 'delete':
                $this->handle_popup_delete_request($popup_id);
                break;
            default:
                $this->render_popup_list();
                break;
        }
    }
    
    /**
     * Handle form submissions early in admin_init
     */
    public function handle_early_form_submission() {
        // Only process on our admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'dcf-popup-manager') {
            return;
        }
        
        // Check if this is a form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dcf_popup_nonce']) && wp_verify_nonce($_POST['dcf_popup_nonce'], 'dcf_popup_action')) {
            $this->handle_popup_form_submission();
        }
    }
    
    /**
     * Handle popup form submission
     */
    private function handle_popup_form_submission() {
        // Don't use error_log before potential redirects
        $popup_action = $_POST['popup_action'] ?? '';
        $popup_manager = new DCF_Popup_Manager();
        
        switch ($popup_action) {
            case 'update':
                $this->handle_popup_update($popup_manager);
                break;
            case 'create':
                $this->handle_popup_create($popup_manager);
                break;
        }
    }
    
    /**
     * Handle popup update
     */
    private function handle_popup_update($popup_manager) {
        $popup_id = intval($_POST['popup_id']);
        $popup_data = array(
            'popup_name' => sanitize_text_field($_POST['popup_name']),
            'popup_type' => sanitize_text_field($_POST['popup_type']),
            'status' => sanitize_text_field($_POST['status']),
            'popup_config' => array(
                'form_id' => intval($_POST['popup_config']['form_id'] ?? 0),
                'auto_close' => !empty($_POST['popup_config']['auto_close']),
                'auto_close_delay' => intval($_POST['popup_config']['auto_close_delay'] ?? 5)
            ),
            'targeting_rules' => array(
                'pages' => $_POST['targeting_rules']['pages'] ?? array(),
                'users' => $_POST['targeting_rules']['users'] ?? array(),
                'devices' => $_POST['targeting_rules']['devices'] ?? array(),
                'schedule' => $_POST['targeting_rules']['schedule'] ?? array()
            ),
            'trigger_settings' => $_POST['trigger_settings'] ?? array(),
            'design_settings' => $_POST['design_settings'] ?? array()
        );
        
        // Check for visual editor data
        if (isset($_POST['popup_data'])) {
            $visual_data = json_decode(stripslashes($_POST['popup_data']), true);
            
            if ($visual_data && !empty($visual_data)) {
                
                // Get existing popup to preserve form_id and other settings
                $existing_popup = $popup_manager->get_popup($popup_id);
                if ($existing_popup && isset($existing_popup['popup_config'])) {
                    $existing_config = is_array($existing_popup['popup_config']) ? $existing_popup['popup_config'] : json_decode($existing_popup['popup_config'], true);
                    // Preserve form_id and other non-visual settings
                    if (isset($existing_config['form_id'])) {
                        $popup_data['popup_config']['form_id'] = $existing_config['form_id'];
                    }
                    if (isset($existing_config['auto_close'])) {
                        $popup_data['popup_config']['auto_close'] = $existing_config['auto_close'];
                    }
                    if (isset($existing_config['auto_close_delay'])) {
                        $popup_data['popup_config']['auto_close_delay'] = $existing_config['auto_close_delay'];
                    }
                }
                
                // Override popup_config with visual editor data
                $popup_data['popup_config']['visual_editor'] = true;
                $popup_data['popup_config']['steps'] = $visual_data['steps'] ?? array();
                $popup_data['popup_config']['settings'] = $visual_data['settings'] ?? array();
                
                // Also check if visual data contains popup name/type
                if (!empty($visual_data['popup_name'])) {
                    $popup_data['popup_name'] = sanitize_text_field($visual_data['popup_name']);
                }
                if (!empty($visual_data['popup_type'])) {
                    $popup_data['popup_type'] = sanitize_text_field($visual_data['popup_type']);
                }
            }
        }
        
        $result = $popup_manager->update_popup($popup_id, $popup_data);
        
        if ($result) {
            // Check if we're in visual editor mode
            if (!empty($_POST['visual_editor_mode'])) {
                // Redirect back to visual editor
                wp_safe_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&message=updated'));
                exit;
            } else {
                // Regular edit page
                wp_safe_redirect(admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id . '&message=updated'));
                exit;
            }
        } else {
            // Update failed - store error in transient to display after redirect
            set_transient('dcf_popup_error_' . get_current_user_id(), __('Failed to update popup.', 'dry-cleaning-forms'), 30);
            
            if (!empty($_POST['visual_editor_mode'])) {
                wp_safe_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&error=1'));
            } else {
                wp_safe_redirect(admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id . '&error=1'));
            }
            exit;
        }
    }
    
    /**
     * Render popup templates page
     */
    private function render_popup_templates() {
        // Enqueue template-specific styles
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        include CMF_PLUGIN_DIR . 'admin/views/popup-templates.php';
    }
    
    /**
     * Render popup editor
     */
    private function render_popup_editor($popup_id) {
        // Handle form submissions first
        if ($_POST && wp_verify_nonce($_POST['dcf_popup_nonce'] ?? '', 'dcf_popup_action')) {
            error_log('DCF Admin Dashboard: Processing popup form submission');
            error_log('DCF Admin Dashboard: POST data - ' . print_r($_POST, true));
            
            $popup_action = $_POST['popup_action'] ?? '';
            $popup_manager = new DCF_Popup_Manager();
            
            switch ($popup_action) {
                case 'create':
                    $popup_data = array(
                        'popup_name' => sanitize_text_field($_POST['popup_name']),
                        'popup_type' => sanitize_text_field($_POST['popup_type']),
                        'status' => sanitize_text_field($_POST['status']),
                        'popup_config' => array(
                            'form_id' => intval($_POST['popup_config']['form_id'] ?? 0),
                            'auto_close' => !empty($_POST['popup_config']['auto_close']),
                            'auto_close_delay' => intval($_POST['popup_config']['auto_close_delay'] ?? 5)
                        ),
                        'targeting_rules' => array(
                            'pages' => $_POST['targeting_rules']['pages'] ?? array(),
                            'users' => $_POST['targeting_rules']['users'] ?? array(),
                            'devices' => $_POST['targeting_rules']['devices'] ?? array(),
                            'schedule' => $_POST['targeting_rules']['schedule'] ?? array()
                        ),
                        'trigger_settings' => $_POST['trigger_settings'] ?? array(),
                        'design_settings' => $_POST['design_settings'] ?? array()
                    );
                    
                    $result = $popup_manager->create_popup($popup_data);
                    
                    if ($result) {
                        error_log('DCF Admin Dashboard: Create successful');
                        $success_message = __('Popup created successfully.', 'dry-cleaning-forms');
                        
                        // Check if headers have been sent
                        if (!headers_sent()) {
                            error_log('DCF Admin Dashboard: Headers not sent, using PHP redirect');
                            wp_redirect(admin_url('admin.php?page=cmf-popup-manager&message=created'));
                            exit;
                        } else {
                            error_log('DCF Admin Dashboard: Headers already sent, will stay on page with success message');
                            // Headers already sent, we'll stay on the page and show success message
                        }
                    } else {
                        error_log('DCF Admin Dashboard: Create failed');
                        $error_message = __('Failed to create popup.', 'dry-cleaning-forms');
                    }
                    break;
                    
                case 'update':
                    error_log('DCF Admin Dashboard: Update case triggered');
                    
                    $popup_id = intval($_POST['popup_id']);
                    $popup_data = array(
                        'popup_name' => sanitize_text_field($_POST['popup_name']),
                        'popup_type' => sanitize_text_field($_POST['popup_type']),
                        'status' => sanitize_text_field($_POST['status']),
                        'popup_config' => array(
                            'form_id' => intval($_POST['popup_config']['form_id'] ?? 0),
                            'auto_close' => !empty($_POST['popup_config']['auto_close']),
                            'auto_close_delay' => intval($_POST['popup_config']['auto_close_delay'] ?? 5)
                        ),
                        'targeting_rules' => array(
                            'pages' => $_POST['targeting_rules']['pages'] ?? array(),
                            'users' => $_POST['targeting_rules']['users'] ?? array(),
                            'devices' => $_POST['targeting_rules']['devices'] ?? array(),
                            'schedule' => $_POST['targeting_rules']['schedule'] ?? array()
                        ),
                        'trigger_settings' => $_POST['trigger_settings'] ?? array(),
                        'design_settings' => $_POST['design_settings'] ?? array()
                    );
                    
                    // Check for visual editor data
                    if (isset($_POST['popup_data'])) {
                        error_log('DCF Admin Dashboard: Visual editor data found');
                        error_log('DCF Admin Dashboard: popup_data length: ' . strlen($_POST['popup_data']));
                        $visual_data = json_decode(stripslashes($_POST['popup_data']), true);
                        
                        if ($visual_data && !empty($visual_data)) {
                            error_log('DCF Admin Dashboard: Visual data parsed successfully');
                            error_log('DCF Admin Dashboard: Visual data structure: ' . print_r(array_keys($visual_data), true));
                            
                            // Override popup_config with visual editor data
                            $popup_data['popup_config']['visual_editor'] = true;
                            $popup_data['popup_config']['steps'] = $visual_data['steps'] ?? array();
                            $popup_data['popup_config']['settings'] = $visual_data['settings'] ?? array();
                            
                            // Also check if visual data contains popup name/type
                            if (!empty($visual_data['popup_name'])) {
                                $popup_data['popup_name'] = sanitize_text_field($visual_data['popup_name']);
                                error_log('DCF Admin Dashboard: Overriding popup name from visual data: ' . $popup_data['popup_name']);
                            }
                            if (!empty($visual_data['popup_type'])) {
                                $popup_data['popup_type'] = sanitize_text_field($visual_data['popup_type']);
                            }
                            
                            error_log('DCF Admin Dashboard: Visual editor steps count: ' . count($popup_data['popup_config']['steps']));
                            if (!empty($popup_data['popup_config']['steps'][0]['blocks'])) {
                                error_log('DCF Admin Dashboard: Step 1 blocks count: ' . count($popup_data['popup_config']['steps'][0]['blocks']));
                            }
                        } else {
                            error_log('DCF Admin Dashboard: Failed to parse visual editor data');
                            error_log('DCF Admin Dashboard: JSON error: ' . json_last_error_msg());
                        }
                    } else {
                        error_log('DCF Admin Dashboard: No visual editor data in POST');
                    }
                    
                    error_log('DCF Admin Dashboard: Popup data prepared - ' . print_r($popup_data, true));
                    
                    $result = $popup_manager->update_popup($popup_id, $popup_data);
                    
                    if ($result) {
                        error_log('DCF Admin Dashboard: Update successful');
                        $success_message = __('Popup updated successfully.', 'dry-cleaning-forms');
                        
                        // Check if headers have been sent
                        if (!headers_sent()) {
                            error_log('DCF Admin Dashboard: Headers not sent, using PHP redirect');
                            
                            // Check if we're in visual editor mode
                            if (!empty($_POST['visual_editor_mode'])) {
                                // Always redirect to visual editor
                                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&message=updated'));
                            } else {
                                // Always redirect to visual editor
                                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&message=updated'));
                            }
                            exit;
                        } else {
                            error_log('DCF Admin Dashboard: Headers already sent, will stay on page with success message');
                            // Headers already sent, we'll stay on the page and show success message
                        }
                    } else {
                        error_log('DCF Admin Dashboard: Update failed');
                        $error_message = __('Failed to update popup.', 'dry-cleaning-forms');
                    }
                    break;
            }
        }
        
        // Enqueue popup admin styles
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue media library
        wp_enqueue_media();
        
        // Enqueue popup admin JavaScript
        wp_enqueue_script(
            'dcf-popup-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/popup-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('dcf-popup-admin-js', 'dcf_popup_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_admin_nonce'),
            'trigger_types' => DCF_Popup_Triggers::get_trigger_types(),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this popup?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
            )
        ));
        
        // Create popup manager instance for the view
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_id ? $popup_manager->get_popup($popup_id) : null;
        
        // Pass required variables to the view
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'new';
        
        // Get trigger types for the view
        $trigger_types = DCF_Popup_Triggers::get_trigger_types();
        
        // Display messages
        $message = $_GET['message'] ?? '';
        $success_message = '';
        $error_message = '';
        
        switch ($message) {
            case 'created':
                $success_message = __('Popup created successfully.', 'dry-cleaning-forms');
                break;
            case 'updated':
                $success_message = __('Popup updated successfully.', 'dry-cleaning-forms');
                break;
        }
        
        // Make sure success_message and error_message are available for the view
        if (!isset($success_message)) {
            $success_message = '';
        }
        if (!isset($error_message)) {
            $error_message = '';
        }
        
        // Redirect to visual editor instead of loading classic editor
        wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit' . ($popup_id ? '&popup_id=' . $popup_id : '')));
        exit;
    }
    
    /**
     * Render popup visual editor
     */
    private function render_popup_visual_editor($popup_id) {
        // Handle form submissions first
        if ($_POST && wp_verify_nonce($_POST['dcf_popup_nonce'] ?? '', 'dcf_popup_action')) {
            error_log('DCF Admin Dashboard: Processing popup form submission from visual editor');
            error_log('DCF Admin Dashboard: POST data - ' . print_r($_POST, true));
            
            $popup_action = $_POST['popup_action'] ?? '';
            $popup_manager = new DCF_Popup_Manager();
            
            switch ($popup_action) {
                case 'create':
                    $popup_data = array(
                        'popup_name' => sanitize_text_field($_POST['popup_name']),
                        'popup_type' => sanitize_text_field($_POST['popup_type']),
                        'status' => sanitize_text_field($_POST['status']),
                        'popup_config' => array(
                            'form_id' => intval($_POST['popup_config']['form_id'] ?? 0),
                            'auto_close' => !empty($_POST['popup_config']['auto_close']),
                            'auto_close_delay' => intval($_POST['popup_config']['auto_close_delay'] ?? 5)
                        ),
                        'targeting_rules' => array(
                            'pages' => $_POST['targeting_rules']['pages'] ?? array(),
                            'users' => $_POST['targeting_rules']['users'] ?? array(),
                            'devices' => $_POST['targeting_rules']['devices'] ?? array(),
                            'schedule' => $_POST['targeting_rules']['schedule'] ?? array()
                        ),
                        'trigger_settings' => $_POST['trigger_settings'] ?? array(),
                        'design_settings' => $_POST['design_settings'] ?? array()
                    );
                    
                    $result = $popup_manager->create_popup($popup_data);
                    
                    if ($result) {
                        error_log('DCF Admin Dashboard: Create successful');
                        $success_message = __('Popup created successfully.', 'dry-cleaning-forms');
                        
                        // Check if headers have been sent
                        if (!headers_sent()) {
                            error_log('DCF Admin Dashboard: Headers not sent, using PHP redirect');
                            wp_redirect(admin_url('admin.php?page=cmf-popup-manager&message=created'));
                            exit;
                        }
                    } else {
                        error_log('DCF Admin Dashboard: Create failed');
                        $error_message = __('Failed to create popup.', 'dry-cleaning-forms');
                    }
                    break;
                
                case 'update':
                    $popup_id = intval($_POST['popup_id']);
                    $popup_data = array(
                        'popup_name' => sanitize_text_field($_POST['popup_name']),
                        'popup_type' => sanitize_text_field($_POST['popup_type']),
                        'status' => sanitize_text_field($_POST['status']),
                        'popup_config' => array(
                            'form_id' => intval($_POST['popup_config']['form_id'] ?? 0),
                            'auto_close' => !empty($_POST['popup_config']['auto_close']),
                            'auto_close_delay' => intval($_POST['popup_config']['auto_close_delay'] ?? 5)
                        ),
                        'targeting_rules' => array(
                            'pages' => $_POST['targeting_rules']['pages'] ?? array(),
                            'users' => $_POST['targeting_rules']['users'] ?? array(),
                            'devices' => $_POST['targeting_rules']['devices'] ?? array(),
                            'schedule' => $_POST['targeting_rules']['schedule'] ?? array()
                        ),
                        'trigger_settings' => $_POST['trigger_settings'] ?? array(),
                        'design_settings' => $_POST['design_settings'] ?? array()
                    );
                    
                    error_log('DCF Admin Dashboard: Popup data prepared - ' . print_r($popup_data, true));
                    
                    $result = $popup_manager->update_popup($popup_id, $popup_data);
                    
                    if ($result) {
                        error_log('DCF Admin Dashboard: Update successful');
                        $success_message = __('Popup updated successfully.', 'dry-cleaning-forms');
                        
                        // Check if headers have been sent
                        if (!headers_sent()) {
                            error_log('DCF Admin Dashboard: Headers not sent, using PHP redirect');
                            wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&message=updated'));
                            exit;
                        }
                    } else {
                        error_log('DCF Admin Dashboard: Update failed');
                        $error_message = __('Failed to update popup.', 'dry-cleaning-forms');
                    }
                    break;
            }
        }
        
        // Enqueue visual editor styles
        wp_enqueue_style(
            'dcf-visual-editor-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/visual-editor.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue popup admin styles (for common popup styles)
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue media library
        wp_enqueue_media();
        
        // Enqueue popup admin JavaScript (for common functionality like device preview)
        wp_enqueue_script(
            'dcf-popup-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/popup-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('dcf-popup-admin-js', 'dcf_popup_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_admin_nonce'),
            'trigger_types' => DCF_Popup_Triggers::get_trigger_types(),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this popup?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
            )
        ));
        
        // Enqueue visual editor blocks JavaScript first
        wp_enqueue_script(
            'dcf-visual-editor-blocks-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-blocks.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Enqueue enhanced drag and drop JavaScript
        wp_enqueue_script(
            'dcf-visual-editor-dragdrop-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-dragdrop.js',
            array('jquery', 'jquery-ui-draggable'),
            '1.0.0',
            true
        );
        
        // Enqueue inline editing JavaScript
        wp_enqueue_script(
            'dcf-visual-editor-inline-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-inline.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Enqueue step navigation JavaScript
        wp_enqueue_script(
            'dcf-visual-editor-steps-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-steps.js',
            array('jquery', 'jquery-ui-sortable'),
            '1.0.0',
            true
        );
        
        // Enqueue visual editor JavaScript
        wp_enqueue_script(
            'dcf-visual-editor-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-sortable', 'dcf-visual-editor-blocks-js', 'dcf-visual-editor-dragdrop-js', 'dcf-visual-editor-inline-js', 'dcf-visual-editor-steps-js'),
            '1.0.0',
            true
        );
        
        // Enqueue visual editor fix
        wp_enqueue_script(
            'dcf-visual-editor-fix-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-fix.js',
            array('dcf-visual-editor-js'),
            '1.0.0',
            true
        );
        
        // Enqueue simple drag and drop as fallback
        wp_enqueue_script(
            'dcf-visual-editor-simple-drag-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/visual-editor-simple-drag.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'dcf-visual-editor-js'),
            '1.0.0',
            true
        );
        
        // Enqueue popup admin JavaScript (for common functionality)
        wp_enqueue_script(
            'dcf-popup-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/popup-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('dcf-visual-editor-js', 'dcf_visual_editor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_visual_editor_nonce'),
            'trigger_types' => DCF_Popup_Triggers::get_trigger_types(),
            'placeholder_image' => plugin_dir_url(dirname(__FILE__)) . 'assets/images/template-placeholder.svg',
            'admin_url' => admin_url('admin.php?page=cmf-popup-manager'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this popup?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
                'switch_to_classic' => __('Switch to Classic Editor', 'dry-cleaning-forms'),
                'switch_to_visual' => __('Switch to Visual Editor', 'dry-cleaning-forms'),
            )
        ));
        
        wp_localize_script('dcf-popup-admin-js', 'dcf_popup_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_admin_nonce'),
            'trigger_types' => DCF_Popup_Triggers::get_trigger_types(),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this popup?', 'dry-cleaning-forms'),
                'error_occurred' => __('An error occurred. Please try again.', 'dry-cleaning-forms'),
                'saving' => __('Saving...', 'dry-cleaning-forms'),
                'saved' => __('Saved!', 'dry-cleaning-forms'),
            )
        ));
        
        // Create popup manager instance for the view
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_id ? $popup_manager->get_popup($popup_id) : null;
        
        // Pass required variables to the view
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'new';
        
        // Get trigger types for the view
        $trigger_types = DCF_Popup_Triggers::get_trigger_types();
        
        // Display messages
        $message = $_GET['message'] ?? '';
        $success_message = '';
        $error_message = '';
        
        switch ($message) {
            case 'created':
                $success_message = __('Popup created successfully.', 'dry-cleaning-forms');
                break;
            case 'updated':
                $success_message = __('Popup updated successfully.', 'dry-cleaning-forms');
                break;
        }
        
        // Make sure success_message and error_message are available for the view
        if (!isset($success_message)) {
            $success_message = '';
        }
        if (!isset($error_message)) {
            $error_message = '';
        }
        
        include CMF_PLUGIN_DIR . 'admin/views/popup-visual-editor.php';
    }
    
    /**
     * Render popup preview
     */
    private function render_popup_preview($popup_id) {
        error_log('DCF Admin Dashboard: Preview method called for popup_id=' . $popup_id);
        
        if (!$popup_id) {
            wp_die(__('Invalid popup ID.', 'dry-cleaning-forms'));
        }
        
        // Create popup manager instance
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_manager->get_popup($popup_id);
        
        if (!$popup) {
            wp_die(__('Popup not found.', 'dry-cleaning-forms'));
        }
        
        error_log('DCF Admin Dashboard: Popup data loaded - ' . print_r($popup, true));
        
        // Decode JSON fields
        $popup_config = is_string($popup['popup_config']) ? json_decode($popup['popup_config'], true) : $popup['popup_config'];
        $design_settings = is_string($popup['design_settings']) ? json_decode($popup['design_settings'], true) : $popup['design_settings'];
        $trigger_settings = is_string($popup['trigger_settings']) ? json_decode($popup['trigger_settings'], true) : $popup['trigger_settings'];
        
        // Get the form if one is selected
        $form = null;
        $form_html = '';
        if (!empty($popup_config['form_id'])) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($popup_config['form_id']);
            
            if ($form) {
                error_log('DCF Admin Dashboard: Form loaded - ' . print_r($form, true));
                
                // Convert stdClass to array if needed
                $form_array = is_object($form) ? (array) $form : $form;
                
                // Generate form HTML for preview
                $form_config = is_string($form_array['form_config']) ? json_decode($form_array['form_config'], true) : $form_array['form_config'];
                if ($form_config && isset($form_config['fields'])) {
                    $form_html = '<form class="dcf-preview-form">';
                    foreach ($form_config['fields'] as $field) {
                        $form_html .= $this->generate_field_html($field);
                    }
                    $form_html .= '<button type="submit" class="dcf-submit-btn">Submit</button>';
                    $form_html .= '</form>';
                }
            }
        }
        
        // Include the preview view
        include CMF_PLUGIN_DIR . 'admin/views/popup-preview.php';
    }
    
    /**
     * Generate HTML for a form field (for preview)
     */
    private function generate_field_html($field) {
        $html = '<div class="dcf-field-wrapper">';
        $html .= '<label>' . esc_html($field['label']) . '</label>';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'tel':
                $html .= '<input type="' . esc_attr($field['type']) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" />';
                break;
            case 'name':
                $html .= '<div class="dcf-name-fields">';
                $html .= '<input type="text" placeholder="' . esc_attr($field['first_placeholder'] ?? 'First Name') . '" />';
                $html .= '<input type="text" placeholder="' . esc_attr($field['last_placeholder'] ?? 'Last Name') . '" />';
                $html .= '</div>';
                break;
            case 'address':
                $html .= '<div class="dcf-address-fields">';
                $html .= '<input type="text" placeholder="' . esc_attr($field['line1_placeholder'] ?? 'Address Line 1') . '" />';
                $html .= '<input type="text" placeholder="' . esc_attr($field['line2_placeholder'] ?? 'Address Line 2') . '" />';
                $html .= '<div class="dcf-address-row">';
                $html .= '<input type="text" placeholder="' . esc_attr($field['city_placeholder'] ?? 'City') . '" />';
                $html .= '<input type="text" placeholder="' . esc_attr($field['state_placeholder'] ?? 'State') . '" />';
                $html .= '<input type="text" placeholder="' . esc_attr($field['zip_placeholder'] ?? 'Zip Code') . '" />';
                $html .= '</div>';
                $html .= '</div>';
                break;
            case 'textarea':
                $html .= '<textarea placeholder="' . esc_attr($field['placeholder'] ?? '') . '"></textarea>';
                break;
            case 'select':
                $html .= '<select>';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $html .= '<option>' . esc_html($option) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render popup analytics
     */
    private function render_popup_analytics($popup_id) {
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Create popup manager instance for the view
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_id ? $popup_manager->get_popup($popup_id) : null;
        
        include CMF_PLUGIN_DIR . 'admin/views/popup-analytics.php';
    }
    
    /**
     * Render popup list
     */
    private function render_popup_list() {
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Create popup manager instance for the view
        $popup_manager = new DCF_Popup_Manager();
        
        include CMF_PLUGIN_DIR . 'admin/views/popup-list.php';
    }
    
    /**
     * Render A/B testing list
     */
    private function render_ab_testing() {
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Create AB testing manager instance for the view
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        
        include CMF_PLUGIN_DIR . 'admin/views/ab-testing-list.php';
    }
    
    /**
     * Render A/B test editor
     */
    private function render_ab_test_editor() {
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Create AB testing manager instance for the view
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $ab_test = $test_id ? $ab_testing_manager->get_test($test_id) : null;
        
        include CMF_PLUGIN_DIR . 'admin/views/ab-test-edit.php';
    }
    
    /**
     * Render A/B test analytics
     */
    private function render_ab_test_analytics() {
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_enqueue_style(
            'dcf-popup-admin-css',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/popup-admin.css',
            array(),
            '1.0.0'
        );
        
        // Create AB testing manager instance for the view
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $ab_test = $test_id ? $ab_testing_manager->get_test($test_id) : null;
        
        include CMF_PLUGIN_DIR . 'admin/views/ab-test-analytics.php';
    }
    
    /**
     * Render forms list
     */
    private function render_forms_list() {
        $form_builder = new DCF_Form_Builder();
        $forms = $form_builder->get_forms();
        
        // Add the default signup form to the beginning of the list
        $default_signup_form = new stdClass();
        $default_signup_form->id = 'default_signup';
        $default_signup_form->form_name = __('New Customer Signup (Default)', 'dry-cleaning-forms');
        $default_signup_form->form_type = 'signup_form';
        $default_signup_form->created_at = '2024-01-01 00:00:00'; // Static date for consistency
        $default_signup_form->form_config = array(
            'title' => __('New Customer Signup', 'dry-cleaning-forms'),
            'description' => __('Multi-step customer registration form with POS integration', 'dry-cleaning-forms'),
            'fields' => array() // The actual fields are handled by the shortcode
        );
        
        // Prepend the default form to the array
        array_unshift($forms, $default_signup_form);
        
        include CMF_PLUGIN_DIR . 'admin/views/forms-list.php';
    }
    
    /**
     * Render form editor
     */
    private function render_form_editor($form_id) {
        $form_builder = new DCF_Form_Builder();
        $form = $form_id ? $form_builder->get_form($form_id) : null;
        $field_types = $form_builder->get_field_types();
        include CMF_PLUGIN_DIR . 'admin/views/form-editor.php';
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        $stats = array(
            'total_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table"),
            'completed_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'completed'"),
            'pending_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status LIKE '%pending%' OR status LIKE '%step_%'"),
            'this_month_submissions' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $submissions_table WHERE created_at >= %s",
                date('Y-m-01')
            ))
        );
        
        return $stats;
    }
    
    /**
     * Get recent submissions
     */
    private function get_recent_submissions() {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        return $wpdb->get_results(
            "SELECT * FROM $submissions_table ORDER BY created_at DESC LIMIT 10"
        );
    }
    
    /**
     * Get integration statuses
     */
    private function get_integration_statuses() {
        $integrations_manager = new DCF_Integrations_Manager();
        $active_status = $integrations_manager->get_active_integration_status();
        
        // Return only the active integration status, or empty array if none selected
        return $active_status ? array($active_status['type'] => $active_status) : array();
    }
    
    /**
     * Get submissions for submissions page
     */
    private function get_submissions() {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        $forms_table = $wpdb->prefix . 'dcf_forms';
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($page - 1) * $per_page;
        
        $where = '';
        $where_params = array();
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where = 'WHERE s.status = %s';
            $where_params[] = sanitize_text_field($_GET['status']);
        }
        
        // Join with forms table to get form names for form_builder submissions
        $query = "SELECT s.*, 
                         CASE 
                             WHEN s.form_id REGEXP '^[0-9]+$' THEN f.form_name
                             ELSE s.form_id
                         END as form_name,
                         CASE 
                             WHEN s.form_id REGEXP '^[0-9]+$' THEN f.form_type
                             ELSE s.form_id
                         END as form_type
                  FROM $submissions_table s
                  LEFT JOIN $forms_table f ON s.form_id = f.id AND s.form_id REGEXP '^[0-9]+$'
                  $where 
                  ORDER BY s.created_at DESC 
                  LIMIT %d OFFSET %d";
        
        // Combine all parameters
        $all_params = array_merge($where_params, array($per_page, $offset));
        
        return $wpdb->get_results($wpdb->prepare($query, $all_params));
    }
    
    /**
     * Get analytics data
     */
    private function get_analytics_data() {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        // Get submissions by day for the last 30 days
        $daily_submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $submissions_table 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            date('Y-m-d', strtotime('-30 days'))
        ));
        
        // Get submissions by form type
        $form_type_stats = $wpdb->get_results(
            "SELECT form_id, COUNT(*) as count 
             FROM $submissions_table 
             GROUP BY form_id 
             ORDER BY count DESC"
        );
        
        // Get conversion funnel for signup forms
        $funnel_stats = array();
        for ($step = 1; $step <= 4; $step++) {
            $funnel_stats[$step] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $submissions_table WHERE step_completed >= %d AND form_id = 'customer_signup'",
                $step
            ));
        }
        
        return array(
            'daily_submissions' => $daily_submissions,
            'form_type_stats' => $form_type_stats,
            'funnel_stats' => $funnel_stats
        );
    }
    
    /**
     * Handle admin AJAX requests
     */
    public function handle_ajax_request() {
        // Simple test log
        error_log('DCF AJAX HANDLER CALLED!');
        
        // Debug logging
        error_log('DCF AJAX: Request received - ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            error_log('DCF AJAX: Nonce verification failed');
            wp_die('Security check failed');
        }
        
        $action = $_POST['dcf_action'] ?? '';
        error_log('DCF AJAX: Action requested - ' . $action);
        
        switch ($action) {
            case 'save_form':
                error_log('DCF AJAX: Calling handle_save_form');
                $this->handle_save_form();
                break;
                
            case 'delete_form':
                $this->handle_delete_form();
                break;
                
            case 'duplicate_form':
                $this->handle_duplicate_form();
                break;
                
            case 'get_form_preview':
                $this->handle_get_form_preview();
                break;
                
            case 'save_popup':
                $this->handle_save_popup();
                break;
                
            case 'delete_popup':
                $this->handle_delete_popup();
                break;
                
            case 'get_popup_analytics':
                $this->handle_get_popup_analytics();
                break;
                
            case 'test_integration':
                $this->handle_test_integration();
                break;
                
            case 'reset_settings':
                $this->handle_reset_settings();
                break;
                
            case 'clear_cache':
                $this->handle_clear_cache();
                break;
                
            case 'test_pos_feature':
                $this->handle_test_pos_feature();
                break;
                
            case 'get_form_template':
                $this->handle_get_form_template();
                break;
                
            case 'get_submission_details':
                $this->handle_get_submission_details();
                break;
                
            case 'delete_submission':
                $this->handle_delete_submission();
                break;
                
            case 'bulk_submissions':
                $this->handle_bulk_submissions();
                break;
                
            case 'get_popup_preview':
                $this->handle_get_popup_preview();
                break;
                
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Handle save form AJAX
     */
    private function handle_save_form() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_id = intval($_POST['form_id'] ?? 0);
        $form_name = sanitize_text_field($_POST['form_name'] ?? '');
        $form_type = sanitize_text_field($_POST['form_type'] ?? 'contact');
        $form_config_json = $_POST['form_config'] ?? '{}';
        $webhook_url = sanitize_url($_POST['webhook_url'] ?? '');
        
        if (empty($form_name)) {
            wp_send_json_error('Form name is required');
        }
        
        // Handle escaped JSON (WordPress sometimes escapes quotes)
        if (strpos($form_config_json, '\"') !== false) {
            $form_config_json = stripslashes($form_config_json);
            error_log('DCF Form Save: Unescaped JSON - ' . $form_config_json);
        }
        
        $form_config = json_decode($form_config_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('DCF Form Save: JSON decode error - ' . json_last_error_msg());
            error_log('DCF Form Save: Raw form_config JSON - ' . $form_config_json);
            $form_config = array();
        }
        
        // Debug logging
        error_log('DCF Form Save Debug: form_id=' . $form_id);
        error_log('DCF Form Save Debug: form_name=' . $form_name);
        error_log('DCF Form Save Debug: form_config=' . print_r($form_config, true));
        
        // Prepare form data
        $form_data = array(
            'form_name' => $form_name,
            'form_type' => $form_type,
            'form_config' => $form_config,
            'webhook_url' => $webhook_url
        );
        
        // Save form
        $form_builder = new DCF_Form_Builder();
        
        if ($form_id) {
            $result = $form_builder->update_form($form_id, $form_data);
            $response_form_id = $form_id;
        } else {
            $result = $form_builder->create_form($form_data);
            $response_form_id = $result;
        }
        
        if ($result && !is_wp_error($result)) {
            wp_send_json_success(array(
                'message' => __('Form saved successfully', 'dry-cleaning-forms'),
                'form_id' => $response_form_id
            ));
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'Failed to save form';
            error_log('DCF Form Save Error: ' . $error_message);
            wp_send_json_error($error_message);
        }
    }
    
    /**
     * Handle delete form AJAX
     */
    private function handle_delete_form() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_id = intval($_POST['form_id'] ?? 0);
        
        if (!$form_id) {
            wp_send_json_error('Invalid form ID');
        }
        
        $form_builder = new DCF_Form_Builder();
        $result = $form_builder->delete_form($form_id);
        
        if ($result) {
            wp_send_json_success('Form deleted successfully');
        } else {
            wp_send_json_error('Failed to delete form');
        }
    }
    
    /**
     * Handle duplicate form AJAX
     */
    private function handle_duplicate_form() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_id = intval($_POST['form_id'] ?? 0);
        
        if (!$form_id) {
            wp_send_json_error('Invalid form ID');
        }
        
        $form_builder = new DCF_Form_Builder();
        $result = $form_builder->duplicate_form($form_id);
        
        if ($result && !is_wp_error($result)) {
            wp_send_json_success(array(
                'message' => __('Form duplicated successfully', 'dry-cleaning-forms'),
                'form_id' => $result
            ));
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'Failed to duplicate form';
            wp_send_json_error($error_message);
        }
    }
    
    /**
     * Handle get form preview AJAX
     */
    private function handle_get_form_preview() {
        $form_id = intval($_POST['form_id'] ?? 0);
        
        if (!$form_id) {
            wp_send_json_error('Invalid form ID');
        }
        
        $form_builder = new DCF_Form_Builder();
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_send_json_error('Form not found');
        }
        
        // Generate form HTML
        $html = $form_builder->render_form($form_id, true); // true for preview mode
        
        wp_send_json_success($html);
    }
    
    /**
     * Handle save popup AJAX
     */
    private function handle_save_popup() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $popup_data = $_POST['popup_data'] ?? array();
        $popup_id = intval($_POST['popup_id'] ?? 0);
        
        // Sanitize popup data
        $sanitized_data = $this->sanitize_popup_data($popup_data);
        
        // Save popup
        $popup_manager = new DCF_Popup_Manager();
        
        if ($popup_id) {
            $result = $popup_manager->update_popup($popup_id, $sanitized_data);
        } else {
            $result = $popup_manager->create_popup($sanitized_data);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Popup saved successfully', 'dry-cleaning-forms'),
                'popup_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to save popup');
        }
    }
    
    /**
     * Handle delete popup AJAX
     */
    private function handle_delete_popup() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $popup_id = intval($_POST['popup_id'] ?? 0);
        
        if (!$popup_id) {
            wp_send_json_error('Invalid popup ID');
        }
        
        $popup_manager = new DCF_Popup_Manager();
        $result = $popup_manager->delete_popup($popup_id);
        
        if ($result) {
            wp_send_json_success('Popup deleted successfully');
        } else {
            wp_send_json_error('Failed to delete popup');
        }
    }
    
    /**
     * Handle popup deletion via GET request
     */
    private function handle_popup_delete_request($popup_id) {
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_popup_' . $popup_id)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to delete this popup.');
        }
        
        if (!$popup_id) {
            wp_die('Invalid popup ID');
        }
        
        $popup_manager = new DCF_Popup_Manager();
        $result = $popup_manager->delete_popup($popup_id);
        
        if ($result) {
            // Use safe redirect to ensure no output interference
            $redirect_url = admin_url('admin.php?page=cmf-popup-manager&deleted=1');
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Use JavaScript redirect as fallback if headers already sent
            if (headers_sent()) {
                echo '<script type="text/javascript">';
                echo 'window.location.href="' . esc_url($redirect_url) . '";';
                echo '</script>';
                echo '<noscript>';
                echo '<meta http-equiv="refresh" content="0;url=' . esc_url($redirect_url) . '" />';
                echo '</noscript>';
                exit;
            }
            
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            wp_die('Failed to delete popup');
        }
    }
    
    /**
     * Handle get popup analytics AJAX
     */
    private function handle_get_popup_analytics() {
        $popup_id = intval($_POST['popup_id'] ?? 0);
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        if (!$popup_id) {
            wp_send_json_error('Invalid popup ID');
        }
        
        $popup_manager = new DCF_Popup_Manager();
        $analytics = $popup_manager->get_popup_analytics($popup_id, $date_range);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Handle get popup preview AJAX
     */
    private function handle_get_popup_preview() {
        $form_id = intval($_POST['form_id'] ?? 0);
        $popup_type = sanitize_text_field($_POST['popup_type'] ?? 'modal');
        $design_settings = $_POST['design_settings'] ?? array();
        $popup_id = intval($_POST['popup_id'] ?? 0);
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        
        // Sanitize design settings
        $sanitized_design_settings = $this->sanitize_design_settings($design_settings);
        
        // Get popup content
        $popup_content = '';
        
        // Check if this is a template-based popup
        if ($template_id || $popup_id) {
            // Try to get popup data to determine if it's template-based
            if ($popup_id) {
                $popup_manager = new DCF_Popup_Manager();
                $popup = $popup_manager->get_popup($popup_id);
                
                if ($popup && !empty($popup['template_id'])) {
                    $template_id = $popup['template_id'];
                }
                
                // Check if it's a split-screen popup
                if ($popup && $popup['popup_type'] === 'split-screen') {
                    $this->render_split_screen_preview($popup, $sanitized_design_settings);
                    wp_die();
                }
            }
            
            // If we have a template ID, render template content
            if ($template_id) {
                $template_manager = new DCF_Popup_Template_Manager();
                $template = $template_manager->get_template($template_id);
                
                if ($template && !empty($template['default_content'])) {
                    // Check if this is a multi-step template
                    if (!empty($template['default_content']['steps'])) {
                        // Render multi-step content
                        $config = array();
                        if ($popup) {
                            $config = $popup['popup_config'] ?: array();
                        }
                        $popup_content = DCF_Multi_Step_Handler::render_multi_step_content($config, $template['default_content']);
                    } else {
                        // Single step template content
                        $popup_content = $template['default_content']['content'] ?? '';
                    }
                }
            }
        }
        
        // If no template content, fall back to form-based content
        if (empty($popup_content) && $form_id) {
            // Get form builder instance
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            
            if (!$form) {
                wp_send_json_error('Form not found');
            }
            
            // Render the form content
            $popup_content = $form_builder->render_form($form_id, array(
                'ajax' => true,
                'show_title' => true,
                'show_description' => true,
                'popup_mode' => true,
                'force_render' => true,
                'preview_mode' => true
            ));
        }
        
        if (empty($popup_content)) {
            wp_send_json_error('No content available for preview');
        }
        
        // Generate popup preview HTML
        ob_start();
        ?>
        <div class="dcf-popup-preview-wrapper">
            <div class="dcf-popup dcf-popup-<?php echo esc_attr($popup_type); ?>" data-popup-id="preview" data-popup-type="<?php echo esc_attr($popup_type); ?>">
                <?php if ($popup_type !== 'bar'): ?>
                    <button class="dcf-popup-close" aria-label="Close popup">×</button>
                <?php endif; ?>
                
                <div class="dcf-popup-content">
                    <?php echo $popup_content; ?>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        // Add inline styles based on design settings
        $custom_css = $this->generate_popup_preview_css($sanitized_design_settings, $popup_type);
        $html = '<style>' . $custom_css . '</style>' . $html;
        
        // Add multi-step specific styles if needed
        if ($template_id && strpos($popup_content, 'dcf-multi-step-popup') !== false) {
            $html .= '<style>
                .dcf-popup-preview-wrapper .dcf-popup-step { display: none; }
                .dcf-popup-preview-wrapper .dcf-popup-step.dcf-step-active { display: block; }
                .dcf-popup-preview-wrapper .dcf-popup-step:first-child { display: block; }
            </style>';
        }
        
        wp_send_json_success($html);
    }
    
    /**
     * Generate CSS for popup preview
     */
    private function generate_popup_preview_css($settings, $popup_type) {
        $css = '';
        
        // Base popup styles
        $css .= '.dcf-popup-preview-wrapper { position: relative; width: 100%; height: 100%; min-height: 400px; }';
        $css .= '.dcf-popup { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); }';
        
        // Apply design settings
        if (!empty($settings['width'])) {
            $css .= '.dcf-popup { width: ' . esc_attr($settings['width']) . '; max-width: 90%; }';
        }
        
        if (!empty($settings['height']) && $settings['height'] !== 'auto') {
            $css .= '.dcf-popup { height: ' . esc_attr($settings['height']) . '; }';
        }
        
        // Background styles
        if (!empty($settings['use_gradient']) && $settings['use_gradient'] === '1' && !empty($settings['gradient_color_1']) && !empty($settings['gradient_color_2'])) {
            $gradient = '';
            if ($settings['gradient_type'] === 'radial') {
                $gradient = 'radial-gradient(circle, ' . $settings['gradient_color_1'] . ', ' . $settings['gradient_color_2'];
                if (!empty($settings['gradient_add_third']) && $settings['gradient_add_third'] === '1' && !empty($settings['gradient_color_3'])) {
                    $gradient = 'radial-gradient(circle, ' . $settings['gradient_color_1'] . ', ' . $settings['gradient_color_2'] . ', ' . $settings['gradient_color_3'];
                }
            } else {
                $angle = !empty($settings['gradient_angle']) ? $settings['gradient_angle'] : '135';
                $gradient = 'linear-gradient(' . $angle . 'deg, ' . $settings['gradient_color_1'] . ', ' . $settings['gradient_color_2'];
                if (!empty($settings['gradient_add_third']) && $settings['gradient_add_third'] === '1' && !empty($settings['gradient_color_3'])) {
                    $gradient = 'linear-gradient(' . $angle . 'deg, ' . $settings['gradient_color_1'] . ', ' . $settings['gradient_color_2'] . ', ' . $settings['gradient_color_3'];
                }
            }
            $gradient .= ')';
            $css .= '.dcf-popup { background: ' . $gradient . '; }';
        } else {
            if (!empty($settings['background_color'])) {
                $css .= '.dcf-popup { background-color: ' . esc_attr($settings['background_color']) . '; }';
            }
            if (!empty($settings['background_image'])) {
                $css .= '.dcf-popup { background-image: url(' . esc_url($settings['background_image']) . '); ';
                $css .= 'background-position: ' . esc_attr($settings['background_position'] ?? 'center center') . '; ';
                $css .= 'background-size: ' . esc_attr($settings['background_size'] ?? 'cover') . '; ';
                $css .= 'background-repeat: ' . esc_attr($settings['background_repeat'] ?? 'no-repeat') . '; }';
            }
        }
        
        // Typography
        if (!empty($settings['text_color'])) {
            $css .= '.dcf-popup { color: ' . esc_attr($settings['text_color']) . '; }';
        }
        if (!empty($settings['font_size'])) {
            $css .= '.dcf-popup { font-size: ' . esc_attr($settings['font_size']) . '; }';
        }
        if (!empty($settings['font_weight'])) {
            $css .= '.dcf-popup { font-weight: ' . esc_attr($settings['font_weight']) . '; }';
        }
        if (!empty($settings['line_height'])) {
            $css .= '.dcf-popup { line-height: ' . esc_attr($settings['line_height']) . '; }';
        }
        if (!empty($settings['text_align'])) {
            $css .= '.dcf-popup { text-align: ' . esc_attr($settings['text_align']) . '; }';
        }
        
        // Headings
        if (!empty($settings['heading_font_size'])) {
            $css .= '.dcf-popup h1, .dcf-popup h2, .dcf-popup h3, .dcf-popup h4, .dcf-popup h5, .dcf-popup h6 { font-size: ' . esc_attr($settings['heading_font_size']) . '; }';
        }
        if (!empty($settings['heading_font_weight'])) {
            $css .= '.dcf-popup h1, .dcf-popup h2, .dcf-popup h3, .dcf-popup h4, .dcf-popup h5, .dcf-popup h6 { font-weight: ' . esc_attr($settings['heading_font_weight']) . '; }';
        }
        
        // Other styles
        if (!empty($settings['border_radius'])) {
            $css .= '.dcf-popup { border-radius: ' . esc_attr($settings['border_radius']) . '; }';
        }
        if (!empty($settings['padding'])) {
            $css .= '.dcf-popup { padding: ' . esc_attr($settings['padding']) . '; }';
        }
        
        // Button styles
        if (!empty($settings['button_bg_color'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { background-color: ' . esc_attr($settings['button_bg_color']) . '; }';
        }
        if (!empty($settings['button_text_color'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { color: ' . esc_attr($settings['button_text_color']) . '; }';
        }
        if (!empty($settings['button_border_radius'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { border-radius: ' . esc_attr($settings['button_border_radius']) . '; }';
        }
        if (!empty($settings['button_padding'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { padding: ' . esc_attr($settings['button_padding']) . '; }';
        }
        if (!empty($settings['button_font_size'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { font-size: ' . esc_attr($settings['button_font_size']) . '; }';
        }
        if (!empty($settings['button_font_weight'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { font-weight: ' . esc_attr($settings['button_font_weight']) . '; }';
        }
        if (!empty($settings['button_text_transform'])) {
            $css .= '.dcf-popup button, .dcf-popup .button, .dcf-popup input[type="submit"] { text-transform: ' . esc_attr($settings['button_text_transform']) . '; }';
        }
        
        // Form field styles
        if (!empty($settings['field_style'])) {
            $css .= '.dcf-popup .dcf-field { data-field-style: ' . esc_attr($settings['field_style']) . '; }';
        }
        
        if (!empty($settings['field_border_color'])) {
            $css .= '.dcf-popup input[type="text"], .dcf-popup input[type="email"], .dcf-popup input[type="tel"], .dcf-popup textarea, .dcf-popup select { border-color: ' . esc_attr($settings['field_border_color']) . '; }';
        }
        if (!empty($settings['field_bg_color'])) {
            $css .= '.dcf-popup input[type="text"], .dcf-popup input[type="email"], .dcf-popup input[type="tel"], .dcf-popup textarea, .dcf-popup select { background-color: ' . esc_attr($settings['field_bg_color']) . '; }';
        }
        if (!empty($settings['field_text_color'])) {
            $css .= '.dcf-popup input[type="text"], .dcf-popup input[type="email"], .dcf-popup input[type="tel"], .dcf-popup textarea, .dcf-popup select { color: ' . esc_attr($settings['field_text_color']) . '; }';
        }
        if (!empty($settings['field_padding'])) {
            $css .= '.dcf-popup input[type="text"], .dcf-popup input[type="email"], .dcf-popup input[type="tel"], .dcf-popup textarea, .dcf-popup select { padding: ' . esc_attr($settings['field_padding']) . '; }';
        }
        if (!empty($settings['field_border_radius'])) {
            $css .= '.dcf-popup input[type="text"], .dcf-popup input[type="email"], .dcf-popup input[type="tel"], .dcf-popup textarea, .dcf-popup select { border-radius: ' . esc_attr($settings['field_border_radius']) . '; }';
        }
        
        return $css;
    }
    
    /**
     * Handle popup export AJAX
     */
    public function handle_popup_export() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'dcf_export_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $popup_id = intval($_GET['popup_id'] ?? 0);
        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        $date_range = sanitize_text_field($_GET['range'] ?? '30');
        
        if (!$popup_id) {
            wp_die('Invalid popup ID');
        }
        
        $popup_manager = new DCF_Popup_Manager();
        $popup = $popup_manager->get_popup($popup_id);
        
        if (!$popup) {
            wp_die('Popup not found');
        }
        
        // Get analytics data
        $analytics = $popup_manager->get_popup_analytics($popup_id, $date_range);
        
        switch ($format) {
            case 'csv':
                $this->export_csv($popup, $analytics);
                break;
            case 'json':
                $this->export_json($popup, $analytics);
                break;
            case 'pdf':
                $this->export_pdf($popup, $analytics);
                break;
            default:
                wp_die('Invalid export format');
        }
    }
    
    /**
     * Export submissions to CSV
     */
    private function export_csv($popup, $analytics) {
        $filename = 'popup-analytics-' . $popup['id'] . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array('Metric', 'Value'));
        
        // Add analytics data
        fputcsv($output, array('Popup Name', $popup['popup_name']));
        fputcsv($output, array('Total Displays', $analytics['total_displays'] ?? 0));
        fputcsv($output, array('Total Interactions', $analytics['total_interactions'] ?? 0));
        fputcsv($output, array('Total Conversions', $analytics['total_conversions'] ?? 0));
        fputcsv($output, array('Conversion Rate', ($analytics['conversion_rate'] ?? 0) . '%'));
        
        fclose($output);
        exit;
    }
    
    private function export_json($popup, $analytics) {
        $filename = 'popup-analytics-' . $popup['id'] . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $data = array(
            'popup' => $popup,
            'analytics' => $analytics,
            'exported_at' => current_time('mysql')
        );
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    private function export_pdf($popup, $analytics) {
        // For PDF export, you would typically use a library like TCPDF or FPDF
        // For now, we'll create a simple HTML version
        $filename = 'popup-analytics-' . $popup['id'] . '-' . date('Y-m-d') . '.html';
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html><head><title>Popup Analytics Report</title></head><body>';
        echo '<h1>Popup Analytics Report</h1>';
        echo '<h2>' . esc_html($popup['popup_name']) . '</h2>';
        echo '<p><strong>Total Displays:</strong> ' . ($analytics['total_displays'] ?? 0) . '</p>';
        echo '<p><strong>Total Interactions:</strong> ' . ($analytics['total_interactions'] ?? 0) . '</p>';
        echo '<p><strong>Total Conversions:</strong> ' . ($analytics['total_conversions'] ?? 0) . '</p>';
        echo '<p><strong>Conversion Rate:</strong> ' . ($analytics['conversion_rate'] ?? 0) . '%</p>';
        echo '<p><em>Generated on ' . date('Y-m-d H:i:s') . '</em></p>';
        echo '</body></html>';
        exit;
    }
    
    private function sanitize_form_data($data) {
        // Implement form data sanitization
        $sanitized = array();
        
        if (isset($data['form_name'])) {
            $sanitized['form_name'] = sanitize_text_field($data['form_name']);
        }
        
        if (isset($data['form_description'])) {
            $sanitized['form_description'] = sanitize_textarea_field($data['form_description']);
        }
        
        if (isset($data['fields']) && is_array($data['fields'])) {
            $sanitized['fields'] = array();
            foreach ($data['fields'] as $field) {
                $sanitized['fields'][] = $this->sanitize_field_data($field);
            }
        }
        
        return $sanitized;
    }
    
    private function sanitize_field_data($field) {
        $sanitized = array();
        
        if (isset($field['type'])) {
            $sanitized['type'] = sanitize_text_field($field['type']);
        }
        
        if (isset($field['label'])) {
            $sanitized['label'] = sanitize_text_field($field['label']);
        }
        
        if (isset($field['required'])) {
            $sanitized['required'] = (bool) $field['required'];
        }
        
        if (isset($field['options']) && is_array($field['options'])) {
            $sanitized['options'] = array_map('sanitize_text_field', $field['options']);
        }
        
        return $sanitized;
    }
    
    private function sanitize_popup_data($data) {
        $sanitized = array();
        
        if (isset($data['popup_name'])) {
            $sanitized['popup_name'] = sanitize_text_field($data['popup_name']);
        }
        
        if (isset($data['popup_type'])) {
            $sanitized['popup_type'] = sanitize_text_field($data['popup_type']);
        }
        
        if (isset($data['status'])) {
            $sanitized['status'] = sanitize_text_field($data['status']);
        }
        
        if (isset($data['popup_config']) && is_array($data['popup_config'])) {
            $sanitized['popup_config'] = array();
            foreach ($data['popup_config'] as $key => $value) {
                if ($key === 'form_id') {
                    $sanitized['popup_config'][$key] = intval($value);
                } elseif ($key === 'auto_close') {
                    $sanitized['popup_config'][$key] = (bool) $value;
                } else {
                    $sanitized['popup_config'][$key] = sanitize_text_field($value);
                }
            }
        }
        
        if (isset($data['targeting_rules']) && is_array($data['targeting_rules'])) {
            $sanitized['targeting_rules'] = $data['targeting_rules']; // More complex sanitization needed
        }
        
        if (isset($data['trigger_settings']) && is_array($data['trigger_settings'])) {
            $sanitized['trigger_settings'] = $data['trigger_settings']; // More complex sanitization needed
        }
        
        if (isset($data['design_settings']) && is_array($data['design_settings'])) {
            $sanitized['design_settings'] = $this->sanitize_design_settings($data['design_settings']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize design settings
     * Ensures CSS values have proper units
     */
    private function sanitize_design_settings($settings) {
        $sanitized = array();
        
        // Helper function to ensure CSS units
        $ensure_units = function($value, $property) {
            $value = sanitize_text_field($value);
            
            // Properties that need units
            $unit_properties = array('width', 'height', 'padding', 'margin', 'border_radius', 'top', 'left', 'right', 'bottom');
            
            if (in_array($property, $unit_properties) && $value !== 'auto' && $value !== 'inherit') {
                // If value is just a number, add 'px'
                if (preg_match('/^\d+$/', $value)) {
                    $value .= 'px';
                }
            }
            
            return $value;
        };
        
        foreach ($settings as $key => $value) {
            if ($key === 'close_button' || $key === 'close_on_overlay') {
                $sanitized[$key] = (bool) $value;
            } elseif ($key === 'background_color' || $key === 'text_color' || $key === 'overlay_color') {
                // Sanitize color values
                $sanitized[$key] = sanitize_hex_color($value) ?: sanitize_text_field($value);
            } else {
                // Apply unit enforcement for dimension properties
                $sanitized[$key] = $ensure_units($value, $key);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Handle A/B test AJAX requests
     */
    public function handle_ab_test_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['dcf_ab_test_nonce'] ?? '', 'dcf_ab_test_action')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $action = sanitize_text_field($_POST['ab_test_action'] ?? '');
        
        switch ($action) {
            case 'create':
                $this->handle_create_ab_test();
                break;
            case 'update':
                $this->handle_update_ab_test();
                break;
            case 'delete':
                $this->handle_delete_ab_test();
                break;
            case 'declare_winner':
                $this->handle_declare_winner();
                break;
            case 'get_analytics':
                $this->handle_get_ab_test_analytics();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    /**
     * Handle create A/B test
     */
    private function handle_create_ab_test() {
        $test_data = $this->sanitize_ab_test_data($_POST);
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $test_id = $ab_testing_manager->create_ab_test($test_data);
        
        if ($test_id) {
            wp_send_json_success(array(
                'message' => __('A/B test created successfully', 'dry-cleaning-forms'),
                'test_id' => $test_id
            ));
        } else {
            wp_send_json_error(__('Failed to create A/B test', 'dry-cleaning-forms'));
        }
    }
    
    /**
     * Handle update A/B test
     */
    private function handle_update_ab_test() {
        $test_id = intval($_POST['test_id'] ?? 0);
        $test_data = $this->sanitize_ab_test_data($_POST);
        
        if (!$test_id) {
            wp_send_json_error(__('Invalid test ID', 'dry-cleaning-forms'));
        }
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $success = $ab_testing_manager->update_ab_test($test_id, $test_data);
        
        if ($success) {
            wp_send_json_success(__('A/B test updated successfully', 'dry-cleaning-forms'));
        } else {
            wp_send_json_error(__('Failed to update A/B test', 'dry-cleaning-forms'));
        }
    }
    
    /**
     * Handle delete A/B test
     */
    private function handle_delete_ab_test() {
        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error(__('Invalid test ID', 'dry-cleaning-forms'));
        }
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $success = $ab_testing_manager->delete_ab_test($test_id);
        
        if ($success) {
            wp_send_json_success(__('A/B test deleted successfully', 'dry-cleaning-forms'));
        } else {
            wp_send_json_error(__('Failed to delete A/B test', 'dry-cleaning-forms'));
        }
    }
    
    /**
     * Handle declare winner
     */
    private function handle_declare_winner() {
        $test_id = intval($_POST['test_id'] ?? 0);
        $winner_id = intval($_POST['winner_id'] ?? 0);
        
        if (!$test_id || !$winner_id) {
            wp_send_json_error(__('Invalid test or winner ID', 'dry-cleaning-forms'));
        }
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $success = $ab_testing_manager->declare_winner($test_id, $winner_id);
        
        if ($success) {
            wp_send_json_success(__('Winner declared successfully', 'dry-cleaning-forms'));
        } else {
            wp_send_json_error(__('Failed to declare winner', 'dry-cleaning-forms'));
        }
    }
    
    /**
     * Handle get A/B test analytics
     */
    private function handle_get_ab_test_analytics() {
        $test_id = intval($_POST['test_id'] ?? 0);
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        if (!$test_id) {
            wp_send_json_error(__('Invalid test ID', 'dry-cleaning-forms'));
        }
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $analytics = $ab_testing_manager->get_test_analytics($test_id, $date_range);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Handle A/B test export
     */
    public function handle_ab_test_export() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_export_ab_test_data')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $test_id = intval($_POST['test_id'] ?? 0);
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
        if (!$test_id) {
            wp_die('Invalid test ID');
        }
        
        $ab_testing_manager = new DCF_AB_Testing_Manager();
        $test = $ab_testing_manager->get_ab_test($test_id);
        
        if (!$test) {
            wp_die('A/B test not found');
        }
        
        // Get test analytics data
        $analytics = $ab_testing_manager->get_test_analytics($test_id);
        $variants_performance = $ab_testing_manager->get_test_variants_performance($test_id);
        
        switch ($format) {
            case 'csv':
                $this->export_ab_test_csv($test, $analytics, $variants_performance);
                break;
            case 'json':
                $this->export_ab_test_json($test, $analytics, $variants_performance);
                break;
            case 'pdf':
                $this->export_ab_test_pdf($test, $analytics, $variants_performance);
                break;
            default:
                wp_die('Invalid export format');
        }
    }
    
    /**
     * Export A/B test data to CSV
     */
    private function export_ab_test_csv($test, $analytics, $variants_performance) {
        $filename = 'ab-test-' . $test['id'] . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Test overview
        fputcsv($output, array('Test Overview'));
        fputcsv($output, array('Test Name', $test['test_name']));
        fputcsv($output, array('Status', $test['status']));
        fputcsv($output, array('Start Date', $test['start_date']));
        fputcsv($output, array('End Date', $test['end_date'] ?: 'N/A'));
        fputcsv($output, array(''));
        
        // Variants performance
        fputcsv($output, array('Variant Performance'));
        fputcsv($output, array('Variant Name', 'Traffic Split', 'Displays', 'Conversions', 'Conversion Rate'));
        
        foreach ($variants_performance as $variant) {
            fputcsv($output, array(
                $variant['popup_name'],
                $variant['traffic_split'] . '%',
                $variant['displays'],
                $variant['conversions'],
                $variant['conversion_rate'] . '%'
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export A/B test data to JSON
     */
    private function export_ab_test_json($test, $analytics, $variants_performance) {
        $filename = 'ab-test-' . $test['id'] . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $data = array(
            'test' => $test,
            'analytics' => $analytics,
            'variants_performance' => $variants_performance,
            'exported_at' => current_time('mysql')
        );
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Export A/B test data to PDF (HTML)
     */
    private function export_ab_test_pdf($test, $analytics, $variants_performance) {
        $filename = 'ab-test-' . $test['id'] . '-' . date('Y-m-d') . '.html';
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html><head><title>A/B Test Report</title></head><body>';
        echo '<h1>A/B Test Report</h1>';
        echo '<h2>' . esc_html($test['test_name']) . '</h2>';
        echo '<p><strong>Status:</strong> ' . esc_html($test['status']) . '</p>';
        echo '<p><strong>Start Date:</strong> ' . esc_html($test['start_date']) . '</p>';
        echo '<p><strong>End Date:</strong> ' . esc_html($test['end_date'] ?: 'N/A') . '</p>';
        
        echo '<h3>Variant Performance</h3>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Variant</th><th>Traffic Split</th><th>Displays</th><th>Conversions</th><th>Conversion Rate</th></tr>';
        
        foreach ($variants_performance as $variant) {
            echo '<tr>';
            echo '<td>' . esc_html($variant['popup_name']) . '</td>';
            echo '<td>' . esc_html($variant['traffic_split']) . '%</td>';
            echo '<td>' . esc_html($variant['displays']) . '</td>';
            echo '<td>' . esc_html($variant['conversions']) . '</td>';
            echo '<td>' . esc_html($variant['conversion_rate']) . '%</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '<p><em>Generated on ' . date('Y-m-d H:i:s') . '</em></p>';
        echo '</body></html>';
        exit;
    }
    
    /**
     * Sanitize A/B test data
     */
    private function sanitize_ab_test_data($data) {
        $sanitized = array();
        
        if (isset($data['test_name'])) {
            $sanitized['test_name'] = sanitize_text_field($data['test_name']);
        }
        
        if (isset($data['popup_ids']) && is_array($data['popup_ids'])) {
            $sanitized['popup_ids'] = array_map('intval', $data['popup_ids']);
        }
        
        if (isset($data['traffic_split']) && is_array($data['traffic_split'])) {
            $sanitized['traffic_split'] = array_map('intval', $data['traffic_split']);
        }
        
        if (isset($data['start_date'])) {
            $sanitized['start_date'] = sanitize_text_field($data['start_date']);
        }
        
        if (isset($data['end_date'])) {
            $sanitized['end_date'] = sanitize_text_field($data['end_date']);
        }
        
        if (isset($data['status'])) {
            $sanitized['status'] = sanitize_text_field($data['status']);
        }
        
        if (isset($data['test_type'])) {
            $sanitized['test_type'] = sanitize_text_field($data['test_type']);
        }
        
        if (isset($data['minimum_sample_size'])) {
            $sanitized['minimum_sample_size'] = intval($data['minimum_sample_size']);
        }
        
        if (isset($data['confidence_level'])) {
            $sanitized['confidence_level'] = floatval($data['confidence_level']);
        }
        
        if (isset($data['auto_declare_winner'])) {
            $sanitized['auto_declare_winner'] = (bool) $data['auto_declare_winner'];
        }
        
        return $sanitized;
    }
    
    /**
     * Handle template preview AJAX
     */
    public function handle_template_preview() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        
        if (!$template_id) {
            wp_send_json_error('Invalid template ID');
        }
        
        $template_manager = new DCF_Popup_Template_Manager();
        $preview_html = $template_manager->get_template_preview($template_id);
        
        if ($preview_html) {
            wp_send_json_success($preview_html);
        } else {
            wp_send_json_error('Template not found');
        }
    }
    
    /**
     * Simple test AJAX handler
     */
    public function test_ajax_handler() {
        error_log('DCF TEST AJAX HANDLER CALLED!');
        wp_send_json_success('Test AJAX is working!');
    }
    
    /**
     * Direct form save handler
     */
    public function handle_save_form_direct() {
        error_log('DCF DIRECT FORM SAVE HANDLER CALLED!');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            error_log('DCF Direct Save: Nonce verification failed');
            wp_send_json_error('Security check failed');
        }
        
        // Call the existing save form method
        $this->handle_save_form();
    }
    
    /**
     * Unique form save handler
     */
    public function handle_unique_form_save() {
        error_log('DCF UNIQUE FORM SAVE HANDLER CALLED!');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
            error_log('DCF Unique Save: Nonce verification failed');
            wp_send_json_error('Security check failed');
        }
        
        // Call the existing save form method
        $this->handle_save_form();
    }
    
    /**
     * Handle test integration AJAX request
     */
    private function handle_test_integration() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $integration_type = sanitize_text_field($_POST['integration'] ?? '');
        
        if (empty($integration_type)) {
            wp_send_json_error(array('message' => 'Integration type is required'));
        }
        
        // Get integration manager
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($integration_type);
        
        if (!$integration) {
            wp_send_json_error(array('message' => 'Integration not found'));
        }
        
        // Test connection
        $result = $integration->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle reset settings AJAX request
     */
    private function handle_reset_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Reset all plugin settings
        delete_option('dcf_settings');
        
        wp_send_json_success(array('message' => 'Settings have been reset'));
    }
    
    /**
     * Handle clear cache AJAX request
     */
    private function handle_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Clear all plugin transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_dcf_%' 
            OR option_name LIKE '_transient_timeout_dcf_%'"
        );
        
        wp_send_json_success(array('message' => 'Cache has been cleared'));
    }
    
    /**
     * Handle test POS feature AJAX request
     */
    private function handle_test_pos_feature() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $feature = sanitize_text_field($_POST['feature'] ?? '');
        $test_data = $_POST['test_data'] ?? array();
        
        if (empty($feature)) {
            wp_send_json_error(array('message' => 'Feature is required'));
        }
        
        // Get POS system
        $pos_system = DCF_Plugin_Core::get_setting('pos_system');
        
        if (!$pos_system) {
            wp_send_json_error(array('message' => 'No POS system configured'));
        }
        
        // Get integration
        $integrations_manager = new DCF_Integrations_Manager();
        $integration = $integrations_manager->get_integration($pos_system);
        
        if (!$integration || !$integration->is_configured()) {
            wp_send_json_error(array('message' => 'POS integration is not configured'));
        }
        
        // Test the feature
        try {
            switch ($feature) {
                case 'check_customer':
                    $result = $integration->customer_exists(
                        $test_data['email'] ?? '',
                        $test_data['phone'] ?? ''
                    );
                    
                    if (is_wp_error($result)) {
                        wp_send_json_error(array(
                            'message' => $result->get_error_message(),
                            'details' => array(
                                'test_data' => $test_data,
                                'pos_system' => $pos_system,
                                'error_code' => $result->get_error_code(),
                                'error_data' => $result->get_error_data()
                            )
                        ));
                    }
                    
                    wp_send_json_success(array(
                        'result' => $result,
                        'test_data' => $test_data,
                        'pos_system' => $pos_system
                    ));
                    break;
                    
                case 'create_customer':
                    $result = $integration->create_customer($test_data);
                    
                    if (is_wp_error($result)) {
                        wp_send_json_error(array(
                            'message' => $result->get_error_message(),
                            'details' => array(
                                'test_data' => $test_data,
                                'pos_system' => $pos_system
                            )
                        ));
                    }
                    
                    wp_send_json_success(array(
                        'result' => $result,
                        'test_data' => $test_data,
                        'pos_system' => $pos_system
                    ));
                    break;
                    
                case 'update_customer':
                    if (empty($test_data['customer_id'])) {
                        wp_send_json_error(array('message' => 'Customer ID is required for update test'));
                    }
                    
                    $result = $integration->update_customer(
                        $test_data['customer_id'],
                        $test_data
                    );
                    
                    if (is_wp_error($result)) {
                        wp_send_json_error(array(
                            'message' => $result->get_error_message(),
                            'details' => array(
                                'test_data' => $test_data,
                                'pos_system' => $pos_system
                            )
                        ));
                    }
                    
                    wp_send_json_success(array(
                        'result' => $result,
                        'test_data' => $test_data,
                        'pos_system' => $pos_system
                    ));
                    break;
                    
                default:
                    wp_send_json_error(array('message' => 'Invalid feature: ' . $feature));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Exception: ' . $e->getMessage(),
                'details' => array(
                    'feature' => $feature,
                    'test_data' => $test_data,
                    'pos_system' => $pos_system
                )
            ));
        }
    }
    
    /**
     * Handle get form template AJAX request
     */
    private function handle_get_form_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $template_key = sanitize_text_field($_POST['template_key'] ?? '');
        
        if (empty($template_key)) {
            wp_send_json_error(array('message' => 'Template key is required'));
        }
        
        // Get the form builder instance
        $form_builder = new DCF_Form_Builder();
        $template = $form_builder->get_form_template($template_key);
        
        if (!$template) {
            wp_send_json_error(array('message' => 'Template not found'));
        }
        
        wp_send_json_success(array(
            'template' => $template
        ));
    }
    
    /**
     * Debug page
     */
    public function debug_page() {
        global $wpdb;
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Dry Cleaning Forms - Debug Info', 'dry-cleaning-forms'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Database Tables', 'dry-cleaning-forms'); ?></h2>
                <?php
                $tables = array(
                    'dcf_forms' => $wpdb->prefix . 'dcf_forms',
                    'dcf_submissions' => $wpdb->prefix . 'dcf_submissions',
                    'dcf_integration_logs' => $wpdb->prefix . 'dcf_integration_logs',
                    'dcf_settings' => $wpdb->prefix . 'dcf_settings'
                );
                
                foreach ($tables as $name => $table) {
                    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
                    echo '<p>' . esc_html($name) . ': ' . ($exists ? '<span style="color: green;">✓ Exists</span>' : '<span style="color: red;">✗ Missing</span>') . '</p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Forms', 'dry-cleaning-forms'); ?></h2>
                <?php
                $forms_table = $wpdb->prefix . 'dcf_forms';
                if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") == $forms_table) {
                    $forms = $wpdb->get_results("SELECT id, form_name, form_type, created_at FROM $forms_table ORDER BY id DESC LIMIT 20");
                    if ($forms) {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Created</th><th>Shortcode</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($forms as $form) {
                            echo '<tr>';
                            echo '<td>' . esc_html($form->id) . '</td>';
                            echo '<td>' . esc_html($form->form_name) . '</td>';
                            echo '<td>' . esc_html($form->form_type) . '</td>';
                            echo '<td>' . esc_html($form->created_at) . '</td>';
                            echo '<td><code>[dcf_form id="' . esc_attr($form->id) . '"]</code></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>' . __('No forms found.', 'dry-cleaning-forms') . '</p>';
                    }
                } else {
                    echo '<p style="color: red;">' . __('Forms table does not exist!', 'dry-cleaning-forms') . '</p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Shortcode Test', 'dry-cleaning-forms'); ?></h2>
                <p><?php _e('Test if shortcodes are registered:', 'dry-cleaning-forms'); ?></p>
                <ul>
                    <li>dcf_form: <?php echo shortcode_exists('dcf_form') ? '<span style="color: green;">✓ Registered</span>' : '<span style="color: red;">✗ Not registered</span>'; ?></li>
                    <li>dcf_signup_form: <?php echo shortcode_exists('dcf_signup_form') ? '<span style="color: green;">✓ Registered</span>' : '<span style="color: red;">✗ Not registered</span>'; ?></li>
                    <li>dcf_contact_form: <?php echo shortcode_exists('dcf_contact_form') ? '<span style="color: green;">✓ Registered</span>' : '<span style="color: red;">✗ Not registered</span>'; ?></li>
                    <li>dcf_optin_form: <?php echo shortcode_exists('dcf_optin_form') ? '<span style="color: green;">✓ Registered</span>' : '<span style="color: red;">✗ Not registered</span>'; ?></li>
                </ul>
            </div>
            
            <div class="card">
                <h2><?php _e('PHP Info', 'dry-cleaning-forms'); ?></h2>
                <p>PHP Version: <?php echo PHP_VERSION; ?></p>
                <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
                <p>Plugin Version: <?php echo CMF_PLUGIN_VERSION; ?></p>
                <p>Error Reporting: <?php echo error_reporting(); ?></p>
                <p>Display Errors: <?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></p>
                <p>Log Errors: <?php echo ini_get('log_errors') ? 'On' : 'Off'; ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle get submission details AJAX
     */
    private function handle_get_submission_details() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        if (!$submission_id) {
            wp_send_json_error('Invalid submission ID');
        }
        
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        $forms_table = $wpdb->prefix . 'dcf_forms';
        
        // Get submission with form info - check if columns exist
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $submissions_table");
        
        $select_fields = "s.id, s.form_id, s.status, s.step_completed, s.user_data, s.created_at, s.updated_at";
        
        // Add optional columns if they exist
        if (in_array('integration_data', $columns)) {
            $select_fields .= ", s.integration_data";
        }
        if (in_array('error_log', $columns)) {
            $select_fields .= ", s.error_log";
        }
        if (in_array('utm_data', $columns)) {
            $select_fields .= ", s.utm_data";
        }
        
        $submission = $wpdb->get_row($wpdb->prepare("
            SELECT $select_fields, 
                   CASE 
                       WHEN s.form_id REGEXP '^[0-9]+$' THEN f.form_name
                       ELSE s.form_id
                   END as form_name,
                   CASE 
                       WHEN s.form_id REGEXP '^[0-9]+$' THEN f.form_type
                       ELSE s.form_id
                   END as form_type
            FROM $submissions_table s
            LEFT JOIN $forms_table f ON s.form_id = f.id AND s.form_id REGEXP '^[0-9]+$'
            WHERE s.id = %d
        ", $submission_id));
        
        if (!$submission) {
            wp_send_json_error('Submission not found');
        }
        
        // Decode data (with property checks to avoid errors)
        $user_data = isset($submission->user_data) ? json_decode($submission->user_data, true) ?: array() : array();
        $integration_data = isset($submission->integration_data) ? json_decode($submission->integration_data, true) ?: array() : array();
        $error_log = isset($submission->error_log) ? json_decode($submission->error_log, true) ?: array() : array();
        $utm_data = isset($submission->utm_data) ? json_decode($submission->utm_data, true) ?: array() : array();
        
        // For legacy submissions, check if integration_data is embedded in user_data
        if (empty($integration_data) && isset($user_data['integration_data'])) {
            $integration_data = $user_data['integration_data'];
            // Remove it from user_data to avoid duplicate display
            unset($user_data['integration_data']);
        }
        
        // Initialize variable to store UTM data extracted from user_data
        $utm_from_user_data = array();
        
        // Map form IDs to human-readable names
        $form_names = array(
            'customer_signup' => 'New Customer Signup Form',
            'quick_signup' => 'Quick Signup Form',
            'contact' => 'Contact Form',
            'price_quote' => 'Price Quote Form',
            'pickup_request' => 'Pickup Request Form'
        );
        
        // Get human-readable form name
        $display_form_name = $submission->form_name;
        if (array_key_exists($submission->form_id, $form_names)) {
            $display_form_name = $form_names[$submission->form_id];
        } elseif (!empty($submission->form_name) && $submission->form_name !== $submission->form_id) {
            $display_form_name = $submission->form_name;
        }
        
        // Calculate completion percentage based on status
        $completion_percentage = $this->calculate_completion_percentage($submission->status, $submission->step_completed);
        
        // Build HTML response
        $html = '<div class="dcf-submission-details">';
        
        // Basic info
        $html .= '<div class="dcf-detail-section">';
        $html .= '<h4>' . __('Submission Information', 'dry-cleaning-forms') . '</h4>';
        $html .= '<table class="dcf-detail-table">';
        $html .= '<tr><th>' . __('ID', 'dry-cleaning-forms') . ':</th><td>' . esc_html($submission->id) . '</td></tr>';
        $html .= '<tr><th>' . __('Form', 'dry-cleaning-forms') . ':</th><td>' . esc_html($display_form_name) . '</td></tr>';
        
        // Format status display
        $status_display = ucwords($submission->status);
        if (preg_match('/STEP_(\d+)_COMPLETED/i', $submission->status, $matches)) {
            $status_display = sprintf('Step %d Completed', $matches[1]);
        }
        
        $html .= '<tr><th>' . __('Status', 'dry-cleaning-forms') . ':</th><td>';
        $html .= '<span class="dcf-status-badge dcf-status-' . esc_attr($submission->status) . '">' . esc_html($status_display) . '</span>';
        if ($completion_percentage !== null) {
            $html .= ' <span class="dcf-completion-percentage">(' . esc_html($completion_percentage) . '% ' . __('Complete', 'dry-cleaning-forms') . ')</span>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><th>' . __('Step Completed', 'dry-cleaning-forms') . ':</th><td>' . esc_html($submission->step_completed) . '</td></tr>';
        $html .= '<tr><th>' . __('Created', 'dry-cleaning-forms') . ':</th><td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at))) . '</td></tr>';
        $html .= '<tr><th>' . __('Updated', 'dry-cleaning-forms') . ':</th><td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->updated_at))) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // User data
        if (!empty($user_data)) {
            // First pass - extract UTM data before displaying user fields
            foreach ($user_data as $key => $value) {
                if ($key === 'utm_parameters') {
                    if (is_array($value)) {
                        // Filter out empty values
                        $utm_values = array_filter($value, function($v) { return !empty($v); });
                        $utm_values = array_values($utm_values); // Re-index
                        
                        if (count($utm_values) >= 5) {
                            $utm_from_user_data = array(
                                'utm_source' => $utm_values[0] ?? '',
                                'utm_medium' => $utm_values[1] ?? '',
                                'utm_campaign' => $utm_values[2] ?? '',
                                'utm_term' => $utm_values[3] ?? '',
                                'utm_content' => $utm_values[4] ?? ''
                            );
                            
                            if (isset($utm_values[5])) $utm_from_user_data['utm_id_1'] = $utm_values[5];
                            if (isset($utm_values[6])) $utm_from_user_data['utm_id_2'] = $utm_values[6];
                            if (isset($utm_values[7])) $utm_from_user_data['gclid'] = $utm_values[7];
                            if (isset($utm_values[8])) $utm_from_user_data['fbclid'] = $utm_values[8];
                            
                            // Remove empty values from the final array
                            $utm_from_user_data = array_filter($utm_from_user_data);
                        }
                    } elseif (is_string($value)) {
                        $utm_values = array_map('trim', explode(',', $value));
                        // Filter out empty values
                        $utm_values = array_filter($utm_values, function($v) { return !empty($v); });
                        $utm_values = array_values($utm_values); // Re-index
                        
                        if (count($utm_values) >= 5) {
                            $utm_from_user_data = array(
                                'utm_source' => $utm_values[0] ?? '',
                                'utm_medium' => $utm_values[1] ?? '',
                                'utm_campaign' => $utm_values[2] ?? '',
                                'utm_term' => $utm_values[3] ?? '',
                                'utm_content' => $utm_values[4] ?? ''
                            );
                            
                            if (isset($utm_values[5])) $utm_from_user_data['utm_id_1'] = $utm_values[5];
                            if (isset($utm_values[6])) $utm_from_user_data['utm_id_2'] = $utm_values[6];
                            if (isset($utm_values[7])) $utm_from_user_data['gclid'] = $utm_values[7];
                            if (isset($utm_values[8])) $utm_from_user_data['fbclid'] = $utm_values[8];
                            
                            // Remove empty values from the final array
                            $utm_from_user_data = array_filter($utm_from_user_data);
                        }
                    }
                    
                    // Always remove the field from user_data so it won't be displayed
                    unset($user_data['utm_parameters']);
                    break;
                }
            }
            
            $html .= '<div class="dcf-detail-section">';
            $html .= '<h4>' . __('Customer Information', 'dry-cleaning-forms') . '</h4>';
            $html .= '<table class="dcf-detail-table">';
            
            // Extract address if it's in JSON format
            $address_data = null;
            if (isset($user_data['address']) && is_string($user_data['address'])) {
                $decoded_address = json_decode($user_data['address'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $address_data = $decoded_address;
                    unset($user_data['address']); // Remove from main data to handle separately
                }
            }
            
            // Extract UTM parameters if they're in user_data (check multiple possible field names)
            $utm_field_names = array('utm_parameters', 'utm parameters', 'Utm Parameters');
            
            foreach ($utm_field_names as $utm_field) {
                if (isset($user_data[$utm_field]) && is_string($user_data[$utm_field])) {
                    $decoded_utm = json_decode($user_data[$utm_field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $utm_from_user_data = $decoded_utm;
                    } else {
                        // If not JSON, it might be a comma-separated string
                        // Based on your example: adwords, black_friday, blackday10, marketingbanner, getdiscounteddeal, e, 12345, 2394984903, 93844980940
                        $utm_values = array_map('trim', explode(',', $user_data[$utm_field]));
                        
                        // Try to map the values to standard UTM parameters based on position
                        if (count($utm_values) >= 5) {
                            $utm_from_user_data = array(
                                'utm_source' => $utm_values[0] ?? '',      // adwords
                                'utm_medium' => $utm_values[1] ?? '',      // black_friday
                                'utm_campaign' => $utm_values[2] ?? '',    // blackday10
                                'utm_term' => $utm_values[3] ?? '',        // marketingbanner
                                'utm_content' => $utm_values[4] ?? ''      // getdiscounteddeal
                            );
                            
                            // Additional values might be click IDs or other tracking parameters
                            if (isset($utm_values[5])) $utm_from_user_data['utm_id_1'] = $utm_values[5];
                            if (isset($utm_values[6])) $utm_from_user_data['utm_id_2'] = $utm_values[6];
                            if (isset($utm_values[7])) $utm_from_user_data['gclid'] = $utm_values[7];
                            if (isset($utm_values[8])) $utm_from_user_data['fbclid'] = $utm_values[8];
                        }
                    }
                    unset($user_data[$utm_field]); // Remove from main data to handle separately
                    break; // Found it, stop looking
                }
            }
            
            // Check for individual UTM fields in user_data
            $utm_fields = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_keyword', 'utm_matchtype', 'campaign_id', 'ad_group_id', 'ad_id', 'gclid', 'fbclid');
            foreach ($utm_fields as $utm_field) {
                if (isset($user_data[$utm_field])) {
                    $utm_from_user_data[$utm_field] = $user_data[$utm_field];
                    unset($user_data[$utm_field]);
                }
            }
            
            // Merge UTM data from both sources is now handled in the display loop
            
            // Display non-address fields first (check both lowercase and capitalized versions)
            $field_order = array('first_name', 'last_name', 'email', 'Email', 'phone', 'Phone', 'company');
            
            // Display ordered fields first
            foreach ($field_order as $field) {
                if (isset($user_data[$field]) && !empty($user_data[$field])) {
                    // Handle capitalized field names
                    $label = ucwords(str_replace('_', ' ', strtolower($field)));
                    $html .= '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($user_data[$field]) . '</td></tr>';
                    unset($user_data[$field]);
                }
            }
            
            // Display any remaining fields (excluding JSON strings)
            foreach ($user_data as $key => $value) {
                // Check if this field is "utm_parameters" - always skip it (backup check)
                if ($key === 'utm_parameters' || $key === 'Utm Parameters') {
                    continue; // Always skip displaying this field
                }
                
                // Also check for other UTM field variations
                $key_lower = strtolower(str_replace(array(' ', '_'), '', $key));
                if ($key_lower === 'utmparameters' && is_string($value) && strpos($value, ',') !== false) {
                    if (empty($utm_from_user_data)) {
                        $utm_values = array_map('trim', explode(',', $value));
                        if (count($utm_values) >= 5) {
                            $utm_from_user_data = array(
                                'utm_source' => $utm_values[0] ?? '',
                                'utm_medium' => $utm_values[1] ?? '',
                                'utm_campaign' => $utm_values[2] ?? '',
                                'utm_term' => $utm_values[3] ?? '',
                                'utm_content' => $utm_values[4] ?? ''
                            );
                            
                            if (isset($utm_values[5])) $utm_from_user_data['utm_id_1'] = $utm_values[5];
                            if (isset($utm_values[6])) $utm_from_user_data['utm_id_2'] = $utm_values[6];
                            if (isset($utm_values[7])) $utm_from_user_data['gclid'] = $utm_values[7];
                            if (isset($utm_values[8])) $utm_from_user_data['fbclid'] = $utm_values[8];
                        }
                    }
                    continue; // Skip displaying this field
                }
                
                // Skip JSON-encoded strings
                if (is_string($value) && (json_decode($value) !== null && json_last_error() === JSON_ERROR_NONE)) {
                    continue;
                }
                
                // Skip integration_data field if it's in user_data (legacy submissions)
                if ($key === 'integration_data') {
                    continue;
                }
                
                $label = ucwords(str_replace('_', ' ', $key));
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $html .= '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($value) . '</td></tr>';
            }
            
            // Display address in a formatted way
            if ($address_data) {
                $html .= '<tr><th>' . __('Address', 'dry-cleaning-forms') . ':</th><td>';
                $address_parts = array();
                
                if (!empty($address_data['address1'])) {
                    $address_parts[] = esc_html($address_data['address1']);
                }
                if (!empty($address_data['address2'])) {
                    $address_parts[] = esc_html($address_data['address2']);
                }
                
                if (!empty($address_parts)) {
                    $html .= implode('<br>', $address_parts) . '<br>';
                }
                
                $city_state_zip = array();
                if (!empty($address_data['city'])) {
                    $city_state_zip[] = esc_html($address_data['city']);
                }
                if (!empty($address_data['state'])) {
                    $city_state_zip[] = esc_html($address_data['state']);
                }
                if (!empty($address_data['zip'])) {
                    $city_state_zip[] = esc_html($address_data['zip']);
                }
                
                if (!empty($city_state_zip)) {
                    $html .= implode(', ', $city_state_zip);
                }
                
                if (!empty($address_data['country'])) {
                    $html .= '<br>' . esc_html($address_data['country']);
                }
                
                $html .= '</td></tr>';
            }
            
            $html .= '</table>';
            $html .= '</div>';
        }
        
        // Marketing Attribution section
        if (!empty($utm_from_user_data) || !empty($utm_data)) {
            $html .= '<div class="dcf-detail-section">';
            $html .= '<h4>' . __('Marketing Attribution', 'dry-cleaning-forms') . '</h4>';
            
            // Merge all UTM data sources
            if (!empty($utm_from_user_data)) {
                $utm_data = array_merge($utm_data, $utm_from_user_data);
            }
            
            $html .= '<table class="dcf-detail-table">';
                
                // Define UTM parameter labels
                $utm_labels = array(
                'utm_source' => 'Source',
                'utm_medium' => 'Medium',
                'utm_campaign' => 'Campaign',
                'utm_term' => 'Term',
                'utm_content' => 'Content',
                'utm_keyword' => 'Keyword',
                'utm_matchtype' => 'Match Type',
                'campaign_id' => 'Campaign ID',
                'ad_group_id' => 'Ad Group ID',
                'ad_id' => 'Ad ID',
                'utm_id_1' => 'Tracking ID 1',
                'utm_id_2' => 'Tracking ID 2',
                'gclid' => 'Google Click ID',
                'fbclid' => 'Facebook Click ID'
            );
            
            foreach ($utm_labels as $key => $label) {
                if (isset($utm_data[$key]) && !empty($utm_data[$key])) {
                    $value = $utm_data[$key];
                    
                    // Make certain values more readable
                    if ($key === 'utm_source') {
                        $value = ucwords(str_replace(array('-', '_'), ' ', $value));
                    }
                    if ($key === 'utm_medium') {
                        $medium_labels = array(
                            'cpc' => 'CPC (Paid Search)',
                            'organic' => 'Organic Search',
                            'social' => 'Social Media',
                            'email' => 'Email',
                            'referral' => 'Referral',
                            'direct' => 'Direct',
                            'display' => 'Display Advertising'
                        );
                        if (isset($medium_labels[strtolower($value)])) {
                            $value = $medium_labels[strtolower($value)];
                        } else {
                            // Format other medium values
                            $value = ucwords(str_replace(array('-', '_'), ' ', $value));
                        }
                    }
                    if ($key === 'utm_campaign' || $key === 'utm_term' || $key === 'utm_content') {
                        // Format campaign, term, and content values
                        $value = str_replace(array('-', '_'), ' ', $value);
                        $value = ucwords($value);
                    }
                    
                    $html .= '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($value) . '</td></tr>';
                }
            }
            
            // Show any other UTM parameters not in our list
            foreach ($utm_data as $key => $value) {
                if (!isset($utm_labels[$key]) && !empty($value)) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    $html .= '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($value) . '</td></tr>';
                }
            }
            
            $html .= '</table>';
            $html .= '</div>';
        }
        
        // Integration data
        if (!empty($integration_data)) {
            $html .= '<div class="dcf-detail-section">';
            $html .= '<h4>' . __('POS Integration Status', 'dry-cleaning-forms') . '</h4>';
            $html .= '<table class="dcf-detail-table">';
            
            // Check for specific integration status fields
            $customer_existed = isset($integration_data['customer_existed']) ? $integration_data['customer_existed'] : null;
            $customer_updated = isset($integration_data['customer_updated']) ? $integration_data['customer_updated'] : null;
            $customer_created = isset($integration_data['customer_created']) ? $integration_data['customer_created'] : null;
            $pos_system = isset($integration_data['pos_system']) ? $integration_data['pos_system'] : 'Unknown';
            $customer_id = isset($integration_data['customer_id']) ? $integration_data['customer_id'] : null;
            
            // Display POS system
            $html .= '<tr><th>' . __('POS System', 'dry-cleaning-forms') . ':</th><td>' . esc_html(strtoupper($pos_system)) . '</td></tr>';
            
            // Display customer status
            if ($customer_existed !== null) {
                $existed_text = $customer_existed ? __('Yes - Existing Customer', 'dry-cleaning-forms') : __('No - New Customer', 'dry-cleaning-forms');
                $existed_class = $customer_existed ? 'dcf-status-info' : 'dcf-status-new';
                $html .= '<tr><th>' . __('Customer Existed', 'dry-cleaning-forms') . ':</th><td><span class="dcf-integration-status ' . $existed_class . '">' . esc_html($existed_text) . '</span></td></tr>';
            }
            
            // Display update status
            if ($customer_updated !== null) {
                $updated_text = $customer_updated ? __('Yes - Contact Info Updated', 'dry-cleaning-forms') : __('No - Info Unchanged', 'dry-cleaning-forms');
                $updated_class = $customer_updated ? 'dcf-status-success' : 'dcf-status-neutral';
                $html .= '<tr><th>' . __('Information Updated', 'dry-cleaning-forms') . ':</th><td><span class="dcf-integration-status ' . $updated_class . '">' . esc_html($updated_text) . '</span></td></tr>';
            }
            
            // Display update message if available
            if (isset($integration_data['update_message'])) {
                $html .= '<tr><th>' . __('Update Details', 'dry-cleaning-forms') . ':</th><td><em>' . esc_html($integration_data['update_message']) . '</em></td></tr>';
            }
            
            // Display creation status
            if ($customer_created !== null && !$customer_existed) {
                $created_text = $customer_created ? __('Successfully Created', 'dry-cleaning-forms') : __('Creation Failed', 'dry-cleaning-forms');
                $created_class = $customer_created ? 'dcf-status-success' : 'dcf-status-error';
                $html .= '<tr><th>' . __('Customer Creation', 'dry-cleaning-forms') . ':</th><td><span class="dcf-integration-status ' . $created_class . '">' . esc_html($created_text) . '</span></td></tr>';
            }
            
            // Display customer ID if available
            if ($customer_id) {
                $html .= '<tr><th>' . __('POS Customer ID', 'dry-cleaning-forms') . ':</th><td><code>' . esc_html($customer_id) . '</code></td></tr>';
            }
            
            // Display any other integration data not already shown
            $shown_keys = array('customer_existed', 'customer_updated', 'customer_created', 'pos_system', 'customer_id', 'pos_customer_id', 'update_message');
            foreach ($integration_data as $key => $value) {
                if (!in_array($key, $shown_keys) && !empty($value)) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_PRETTY_PRINT);
                        $html .= '<tr><th>' . esc_html($label) . ':</th><td><pre>' . esc_html($value) . '</pre></td></tr>';
                    } else {
                        $html .= '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($value) . '</td></tr>';
                    }
                }
            }
            
            $html .= '</table>';
            $html .= '</div>';
        }
        
        // Error log
        if (!empty($error_log)) {
            $html .= '<div class="dcf-detail-section dcf-error-section">';
            $html .= '<h4>' . __('Error Log', 'dry-cleaning-forms') . '</h4>';
            $html .= '<pre class="dcf-error-log">' . esc_html(json_encode($error_log, JSON_PRETTY_PRINT)) . '</pre>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Add styles
        $html .= '<style>
        .dcf-submission-details { padding: 20px; }
        .dcf-detail-section { margin-bottom: 30px; }
        .dcf-detail-section h4 { margin-bottom: 15px; font-size: 16px; }
        .dcf-detail-table { width: 100%; border-collapse: collapse; }
        .dcf-detail-table th, .dcf-detail-table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        .dcf-detail-table th { background: #f5f5f5; font-weight: 600; width: 200px; }
        .dcf-detail-table pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .dcf-error-section { background: #fff5f5; padding: 15px; border-radius: 4px; }
        .dcf-error-log { background: #333; color: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .dcf-completion-percentage { margin-left: 10px; color: #666; font-weight: normal; font-size: 13px; }
        .dcf-status-pending .dcf-completion-percentage { color: #0073aa; }
        .dcf-status-completed .dcf-completion-percentage { color: #0f5132; }
        .dcf-integration-status { display: inline-block; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .dcf-status-info { background: #e3f2fd; color: #1976d2; }
        .dcf-status-new { background: #f3e5f5; color: #7b1fa2; }
        .dcf-status-success { background: #e8f5e9; color: #388e3c; }
        .dcf-status-neutral { background: #f5f5f5; color: #616161; }
        .dcf-status-error { background: #ffebee; color: #c62828; }
        </style>';
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Handle delete submission AJAX
     */
    private function handle_delete_submission() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $submission_id = intval($_POST['submission_id'] ?? 0);
        
        if (!$submission_id) {
            wp_send_json_error('Invalid submission ID');
        }
        
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        $result = $wpdb->delete($submissions_table, array('id' => $submission_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error('Failed to delete submission');
        }
        
        wp_send_json_success(array('message' => __('Submission deleted successfully', 'dry-cleaning-forms')));
    }
    
    /**
     * Handle bulk submissions AJAX
     */
    private function handle_bulk_submissions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $submission_ids = array_map('intval', $_POST['submission_ids'] ?? array());
        
        if (empty($bulk_action) || empty($submission_ids)) {
            wp_send_json_error('Invalid parameters');
        }
        
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        switch ($bulk_action) {
            case 'mark_completed':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $submissions_table SET status = 'completed' WHERE id IN (" . implode(',', array_fill(0, count($submission_ids), '%d')) . ")",
                    $submission_ids
                ));
                break;
                
            case 'mark_pending':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $submissions_table SET status = 'pending' WHERE id IN (" . implode(',', array_fill(0, count($submission_ids), '%d')) . ")",
                    $submission_ids
                ));
                break;
                
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $submissions_table WHERE id IN (" . implode(',', array_fill(0, count($submission_ids), '%d')) . ")",
                    $submission_ids
                ));
                break;
                
            case 'export_csv':
                $this->export_submissions_csv($submission_ids);
                return;
                
            default:
                wp_send_json_error('Invalid bulk action');
        }
        
        if ($result === false) {
            wp_send_json_error('Failed to perform bulk action');
        }
        
        wp_send_json_success(array('message' => __('Bulk action completed successfully', 'dry-cleaning-forms')));
    }
    
    /**
     * Calculate completion percentage based on status and step
     */
    private function calculate_completion_percentage($status, $step_completed) {
        // Handle different status formats
        if ($status === 'completed') {
            return 100;
        }
        
        if ($status === 'failed' || $status === 'abandoned') {
            // For multi-step forms, calculate based on steps
            if ($step_completed > 0) {
                $total_steps = 3; // Default to 3 steps for signup forms
                return round(($step_completed / $total_steps) * 100);
            }
            return 0;
        }
        
        // Handle step-based statuses like "STEP_1_COMPLETED", "STEP_2_COMPLETED", etc.
        if (preg_match('/STEP_(\d+)_COMPLETED/i', $status, $matches)) {
            $step = intval($matches[1]);
            $total_steps = 3; // Default to 3 steps
            return round(($step / $total_steps) * 100);
        }
        
        // Handle pending status
        if ($status === 'pending') {
            if ($step_completed > 0) {
                $total_steps = 3; // Default to 3 steps for signup forms
                return round(($step_completed / $total_steps) * 100);
            }
            return 0;
        }
        
        // For single-step forms or unknown statuses
        return null;
    }
    
    /**
     * Export submissions to CSV
     */
    private function export_submissions_csv($submission_ids) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'dcf_submissions';
        
        $placeholders = implode(',', array_fill(0, count($submission_ids), '%d'));
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $submissions_table WHERE id IN ($placeholders) ORDER BY created_at DESC",
            $submission_ids
        ));
        
        if (empty($submissions)) {
            wp_send_json_error('No submissions found');
        }
        
        // Build CSV
        $csv_data = array();
        $headers = array('ID', 'Form ID', 'Status', 'Step', 'Created', 'First Name', 'Last Name', 'Email', 'Phone', 'Address', 'City', 'State', 'Zip', 'UTM Source', 'UTM Medium', 'UTM Campaign');
        $csv_data[] = $headers;
        
        foreach ($submissions as $submission) {
            $user_data = json_decode($submission->user_data, true) ?: array();
            $utm_data = json_decode($submission->utm_data, true) ?: array();
            
            $row = array(
                $submission->id,
                $submission->form_id,
                $submission->status,
                $submission->step_completed,
                $submission->created_at,
                $user_data['first_name'] ?? '',
                $user_data['last_name'] ?? '',
                $user_data['email'] ?? '',
                $user_data['phone'] ?? '',
                $user_data['address'] ?? '',
                $user_data['city'] ?? '',
                $user_data['state'] ?? '',
                $user_data['zip'] ?? '',
                $utm_data['utm_source'] ?? '',
                $utm_data['utm_medium'] ?? '',
                $utm_data['utm_campaign'] ?? ''
            );
            $csv_data[] = $row;
        }
        
        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        wp_send_json_success(array('csv' => $csv));
    }
    
    /**
     * Handle template preview AJAX request
     */
    public function handle_preview_template() {
        if (!check_ajax_referer('dcf_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        if (empty($template_id)) {
            wp_send_json_error('No template ID provided');
        }
        
        $template_manager = DCF_Template_Manager::get_instance();
        $templates = $template_manager->get_all_templates();
        
        $template = null;
        foreach ($templates as $t) {
            if ($t['id'] === $template_id) {
                $template = $t;
                break;
            }
        }
        
        if (!$template) {
            wp_send_json_error('Template not found');
        }
        
        // Generate preview HTML
        $preview_html = '<div class="dcf-template-preview-wrapper">';
        
        if (!empty($template['preview_image'])) {
            $preview_html .= '<img src="' . esc_url($template['preview_image']) . '" alt="' . esc_attr($template['name']) . '" style="max-width: 100%; height: auto;">';
        } else {
            // Generate a basic preview based on template type
            $preview_html .= '<div class="dcf-template-preview-demo">';
            $preview_html .= '<h3>' . esc_html($template['name']) . '</h3>';
            $preview_html .= '<p>' . esc_html($template['description']) . '</p>';
            
            if (isset($template['config']['fields'])) {
                $preview_html .= '<div class="dcf-form-preview">';
                foreach ($template['config']['fields'] as $field) {
                    $preview_html .= '<div class="dcf-field-preview">';
                    $preview_html .= '<label>' . esc_html($field['label'] ?? '') . '</label>';
                    $preview_html .= '<input type="text" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" disabled>';
                    $preview_html .= '</div>';
                }
                $preview_html .= '</div>';
            }
            
            $preview_html .= '</div>';
        }
        
        $preview_html .= '</div>';
        
        wp_send_json_success(array('preview' => $preview_html));
    }
    
    /**
     * Handle create from template AJAX request
     */
    public function handle_create_from_template() {
        if (!check_ajax_referer('dcf_admin_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        if (empty($template_id) || empty($name)) {
            wp_send_json_error('Missing required fields');
        }
        
        $template_manager = DCF_Template_Manager::get_instance();
        $result = $template_manager->create_from_template($template_id, $name);
        
        if ($result === false) {
            wp_send_json_error('Failed to create from template');
        }
        
        // Determine redirect URL based on template type
        $templates = $template_manager->get_all_templates();
        $template = null;
        foreach ($templates as $t) {
            if ($t['id'] === $template_id) {
                $template = $t;
                break;
            }
        }
        
        if ($template && isset($template['is_form']) && $template['is_form']) {
            // Redirect to form editor
            $redirect_url = admin_url('admin.php?page=cmf-form-builder&action=edit&form_id=' . $result);
        } else {
            // Redirect to popup editor
            $redirect_url = admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $result);
        }
        
        wp_send_json_success(array(
            'id' => $result,
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * Render split-screen popup preview
     */
    private function render_split_screen_preview($popup, $design_settings) {
        $form_id = $popup['popup_config']['form_id'] ?? 0;
        $split_layout = $design_settings['split_layout'] ?? 'image-left';
        $split_ratio = $design_settings['split_ratio'] ?? '50-50';
        $split_image = $design_settings['split_image'] ?? '';
        $split_content_bg = $design_settings['split_content_bg'] ?? '#5DBCD2';
        $split_content_padding = $design_settings['split_content_padding'] ?? '40px';
        
        // Get form content
        $form_content = '';
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form_content = $form_builder->render_form($form_id, array(
                'ajax' => true,
                'show_title' => true,
                'show_description' => true,
                'popup_mode' => true,
                'force_render' => true,
                'preview_mode' => true
            ));
        }
        
        // Generate split-screen HTML
        ob_start();
        ?>
        <div class="dcf-popup-preview-wrapper">
            <div class="dcf-popup dcf-popup-split-screen dcf-split-screen-popup dcf-split-ratio-<?php echo esc_attr($split_ratio); ?> dcf-split-layout-<?php echo esc_attr($split_layout); ?>" data-popup-id="preview">
                <button class="dcf-popup-close" aria-label="Close popup">×</button>
                
                <div class="dcf-split-image-section" <?php if ($split_image): ?>style="background-image: url('<?php echo esc_url($split_image); ?>');"<?php endif; ?>>
                    <!-- Image section -->
                </div>
                
                <div class="dcf-split-content-section" style="background-color: <?php echo esc_attr($split_content_bg); ?>; padding: <?php echo esc_attr($split_content_padding); ?>;">
                    <div class="dcf-popup-content">
                        <?php echo $form_content; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        // Add custom CSS for split-screen
        $custom_css = $this->generate_split_screen_css($design_settings);
        $html = '<style>' . $custom_css . '</style>' . $html;
        
        wp_send_json_success($html);
    }
    
    /**
     * Generate CSS for split-screen popup preview
     */
    private function generate_split_screen_css($settings) {
        $css = '';
        
        // Base styles
        $css .= '.dcf-popup-preview-wrapper { position: relative; width: 100%; height: 100%; min-height: 500px; }';
        $css .= '.dcf-popup-split-screen { 
            position: absolute; 
            left: 50%; 
            top: 50%; 
            transform: translate(-50%, -50%); 
            width: 90%; 
            max-width: 1200px; 
            height: 600px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }';
        
        // Apply general design settings
        if (!empty($settings['border_radius'])) {
            $css .= '.dcf-popup-split-screen { border-radius: ' . esc_attr($settings['border_radius']) . '; }';
        }
        
        // Image section styles
        $css .= '.dcf-split-image-section { 
            background-size: ' . esc_attr($settings['split_image_size'] ?? 'cover') . ';
            background-position: ' . esc_attr($settings['split_image_position'] ?? 'center center') . ';
            background-repeat: no-repeat;
        }';
        
        // Content section styles
        $css .= '.dcf-split-content-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            overflow-y: auto;
        }';
        
        // Apply text color to content section
        if (!empty($settings['text_color'])) {
            $css .= '.dcf-split-content-section { color: ' . esc_attr($settings['text_color']) . '; }';
        }
        
        // Form styles within split-screen
        $css .= '.dcf-popup-split-screen .dcf-popup-content { width: 100%; max-width: 500px; }';
        
        // Button styles
        if (!empty($settings['button_bg_color'])) {
            $css .= '.dcf-popup-split-screen button, .dcf-popup-split-screen .button, .dcf-popup-split-screen input[type="submit"] { 
                background-color: ' . esc_attr($settings['button_bg_color']) . '; 
                border: none;
                cursor: pointer;
            }';
        }
        if (!empty($settings['button_text_color'])) {
            $css .= '.dcf-popup-split-screen button, .dcf-popup-split-screen .button, .dcf-popup-split-screen input[type="submit"] { 
                color: ' . esc_attr($settings['button_text_color']) . '; 
            }';
        }
        if (!empty($settings['button_border_radius'])) {
            $css .= '.dcf-popup-split-screen button, .dcf-popup-split-screen .button, .dcf-popup-split-screen input[type="submit"] { 
                border-radius: ' . esc_attr($settings['button_border_radius']) . '; 
            }';
        }
        if (!empty($settings['button_padding'])) {
            $css .= '.dcf-popup-split-screen button, .dcf-popup-split-screen .button, .dcf-popup-split-screen input[type="submit"] { 
                padding: ' . esc_attr($settings['button_padding']) . '; 
            }';
        }
        
        // Close button positioning
        $css .= '.dcf-popup-split-screen .dcf-popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            background: rgba(255,255,255,0.9);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }';
        
        $css .= '.dcf-popup-split-screen .dcf-popup-close:hover {
            background: rgba(255,255,255,1);
            transform: scale(1.1);
        }';
        
        return $css;
    }
    
    /**
     * Handle AJAX request to get all forms for visual editor
     */
    public function handle_get_all_forms() {
        // Verify nonce
        if (!check_ajax_referer('dcf_visual_editor_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get form builder instance
        $form_builder = new DCF_Form_Builder();
        
        // Get all forms
        $forms = $form_builder->get_forms(array(
            'limit' => 100,
            'orderby' => 'form_name',
            'order' => 'ASC'
        ));
        
        // Format forms for response
        $formatted_forms = array();
        foreach ($forms as $form) {
            $formatted_forms[] = array(
                'id' => $form->id,
                'form_name' => $form->form_name,
                'form_type' => $form->form_type,
                'field_count' => isset($form->form_config['fields']) ? count($form->form_config['fields']) : 0
            );
        }
        
        wp_send_json_success($formatted_forms);
    }
    
    /**
     * Handle AJAX request to get form data for visual editor
     */
    public function handle_get_form_data() {
        // Verify nonce
        if (!check_ajax_referer('dcf_visual_editor_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get form ID
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error('Invalid form ID');
        }
        
        // Get form builder instance
        $form_builder = new DCF_Form_Builder();
        
        // Get form data
        $form = $form_builder->get_form($form_id);
        
        if (!$form) {
            wp_send_json_error('Form not found');
        }
        
        // Return form data
        wp_send_json_success($form);
    }
} 