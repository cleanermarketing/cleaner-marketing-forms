<?php
/**
 * Backwards Compatibility
 * 
 * Maintains compatibility with old DCF shortcodes and functions
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register backwards compatible shortcodes
 */
function cmf_register_legacy_shortcodes() {
    // Since the plugin still uses dcf_ internally, we'll add cmf_ aliases
    // that point to the dcf_ shortcodes
    $new_to_old_mapping = array(
        'cmf_signup_form' => 'dcf_signup_form',
        'cmf_contact_form' => 'dcf_contact_form',
        'cmf_optin_form' => 'dcf_optin_form',
        'cmf_form' => 'dcf_form'
    );
    
    foreach ($new_to_old_mapping as $new => $old) {
        add_shortcode($new, function($atts) use ($old) {
            // Call the existing DCF shortcode handler
            return do_shortcode('[' . $old . ' ' . cmf_build_shortcode_attributes($atts) . ']');
        });
    }
}
add_action('init', 'cmf_register_legacy_shortcodes', 20); // Run after DCF_Public_Forms init

/**
 * Build shortcode attributes string
 */
function cmf_build_shortcode_attributes($atts) {
    if (empty($atts)) {
        return '';
    }
    
    $attributes = array();
    foreach ($atts as $key => $value) {
        if (is_numeric($key)) {
            $attributes[] = $value;
        } else {
            $attributes[] = $key . '="' . esc_attr($value) . '"';
        }
    }
    
    return implode(' ', $attributes);
}

/**
 * Create class aliases for backwards compatibility
 */
function cmf_create_class_aliases() {
    $class_mappings = array(
        'DCF_Form_Builder' => 'CMF_Form_Builder',
        'DCF_Public_Forms' => 'CMF_Public_Forms',
        'DCF_Integrations_Manager' => 'CMF_Integrations_Manager',
        'DCF_Webhook_Handler' => 'CMF_Webhook_Handler',
        'DCF_Admin_Dashboard' => 'CMF_Admin_Dashboard',
        'DCF_Settings_Page' => 'CMF_Settings_Page',
        'DCF_Popup_Manager' => 'CMF_Popup_Manager',
        'DCF_Popup_Triggers' => 'CMF_Popup_Triggers',
        'DCF_Template_Manager' => 'CMF_Template_Manager',
        'DCF_Multi_Step_Handler' => 'CMF_Multi_Step_Handler',
        'DCF_AB_Testing_Manager' => 'CMF_AB_Testing_Manager',
        'DCF_SMRT_Integration' => 'CMF_SMRT_Integration',
        'DCF_SPOT_Integration' => 'CMF_SPOT_Integration',
        'DCF_CleanCloud_Integration' => 'CMF_CleanCloud_Integration'
    );
    
    foreach ($class_mappings as $old => $new) {
        if (!class_exists($old) && class_exists($new)) {
            class_alias($new, $old);
        }
    }
}
add_action('plugins_loaded', 'cmf_create_class_aliases', 5);

/**
 * Map old hooks to new ones
 */
function cmf_map_legacy_hooks() {
    $hook_mappings = array(
        // Filters
        'dcf_field_types' => 'cmf_field_types',
        'dcf_render_field_input' => 'cmf_render_field_input',
        'dcf_form_classes' => 'cmf_form_classes',
        'dcf_form_attributes' => 'cmf_form_attributes',
        'dcf_submission_data' => 'cmf_submission_data',
        'dcf_webhook_data' => 'cmf_webhook_data',
        'dcf_integration_customer_data' => 'cmf_integration_customer_data',
        
        // Actions
        'dcf_after_form_submission' => 'cmf_after_form_submission',
        'dcf_before_form_render' => 'cmf_before_form_render',
        'dcf_after_form_render' => 'cmf_after_form_render',
        'dcf_webhook_sent' => 'cmf_webhook_sent',
        'dcf_customer_created' => 'cmf_customer_created',
        'dcf_settings_updates_section' => 'cmf_settings_updates_section'
    );
    
    // Map filters
    foreach ($hook_mappings as $old => $new) {
        add_filter($old, function(...$args) use ($new) {
            return apply_filters($new, ...$args);
        }, 10, 10);
    }
    
    // Map actions
    foreach ($hook_mappings as $old => $new) {
        add_action($old, function(...$args) use ($new) {
            do_action($new, ...$args);
        }, 10, 10);
    }
}
add_action('plugins_loaded', 'cmf_map_legacy_hooks', 1);

/**
 * Define legacy constants
 */
function cmf_define_legacy_constants() {
    $constant_mappings = array(
        'CMF_PLUGIN_FILE' => CMF_PLUGIN_FILE,
        'CMF_PLUGIN_DIR' => CMF_PLUGIN_DIR,
        'CMF_PLUGIN_URL' => CMF_PLUGIN_URL,
        'CMF_PLUGIN_VERSION' => CMF_PLUGIN_VERSION,
        'CMF_PLUGIN_BASENAME' => CMF_PLUGIN_BASENAME
    );
    
    foreach ($constant_mappings as $old => $new) {
        if (!defined($old)) {
            define($old, $new);
        }
    }
}
add_action('plugins_loaded', 'cmf_define_legacy_constants', 0);

/**
 * Show admin notice about migration
 */
function cmf_show_migration_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if user has dismissed the notice
    if (get_option('cmf_migration_notice_dismissed')) {
        return;
    }
    
    // Check if old shortcodes are being used
    global $wpdb;
    $old_shortcodes_query = "
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_status = 'publish' 
        AND (
            post_content LIKE '%[dcf_signup_form%' 
            OR post_content LIKE '%[dcf_contact_form%'
            OR post_content LIKE '%[dcf_optin_form%'
            OR post_content LIKE '%[dcf_form%'
        )
    ";
    
    $count = $wpdb->get_var($old_shortcodes_query);
    
    if ($count > 0) {
        ?>
        <div class="notice notice-warning is-dismissible" id="cmf-migration-notice">
            <p><strong>Cleaner Marketing Forms:</strong> 
            We detected <?php echo $count; ?> page(s) using old DCF shortcodes. 
            These will continue to work, but we recommend updating them to the new CMF shortcodes for better performance.
            <a href="<?php echo admin_url('admin.php?page=cleaner-marketing-forms&tab=migration'); ?>">View Migration Guide</a>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#cmf-migration-notice').on('click', '.notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'cmf_dismiss_migration_notice',
                    nonce: '<?php echo wp_create_nonce('cmf_dismiss_notice'); ?>'
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_notices', 'cmf_show_migration_notice');

/**
 * Handle AJAX request to dismiss migration notice
 */
function cmf_ajax_dismiss_migration_notice() {
    if (!wp_verify_nonce($_POST['nonce'], 'cmf_dismiss_notice')) {
        wp_die();
    }
    
    update_option('cmf_migration_notice_dismissed', true);
    wp_die();
}
add_action('wp_ajax_cmf_dismiss_migration_notice', 'cmf_ajax_dismiss_migration_notice');