<?php
/**
 * Form Editor View
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$is_edit = $form_id > 0;
$form_data = null;

if ($is_edit) {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'dcf_forms';
    $form_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $form_id));
    
    if (!$form_data) {
        wp_die(__('Form not found.', 'dry-cleaning-forms'));
    }
}

$form_config = $form_data ? json_decode($form_data->form_config, true) : array();

// Extract style settings or use defaults
$form_styles = isset($form_config['styles']) ? $form_config['styles'] : array(
    'layout_type' => 'single-column',
    'input_style' => 'box',
    'width' => '650',
    'width_unit' => 'px',
    'field_spacing' => '16',
    'label_width' => '200',
    'label_alignment' => 'top',
    'padding_top' => '30',
    'padding_right' => '40',
    'padding_bottom' => '30',
    'padding_left' => '40',
    'show_labels' => true
);

// Debug output (commented out to prevent REST API issues)
// if ($is_edit) {
//     echo '<!-- DEBUG: Form ID: ' . $form_id . ' -->';
//     echo '<!-- DEBUG: Form data exists: ' . ($form_data ? 'Yes' : 'No') . ' -->';
//     if ($form_data) {
//         echo '<!-- DEBUG: Raw form_config: ' . esc_html($form_data->form_config) . ' -->';
//         echo '<!-- DEBUG: Decoded form_config: ' . esc_html(print_r($form_config, true)) . ' -->';
//         echo '<!-- DEBUG: Fields count: ' . (isset($form_config['fields']) ? count($form_config['fields']) : 0) . ' -->';
//     }
// }
?>

<div class="wrap dcf-form-editor">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Form', 'dry-cleaning-forms') : __('Create New Form', 'dry-cleaning-forms'); ?>
    </h1>
    
    <?php if ($is_edit): ?>
        <a href="<?php echo admin_url('admin.php?page=cmf-form-builder&action=new'); ?>" class="page-title-action">
            <?php _e('Add New', 'dry-cleaning-forms'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <div class="dcf-editor-container">
        <div class="dcf-editor-sidebar">
            <div class="dcf-sidebar-section">
                <h3><?php _e('Form Settings', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-section-content">
                <div class="dcf-form-settings">
                    <div class="dcf-field-group">
                        <label for="form-name"><?php _e('Form Name', 'dry-cleaning-forms'); ?></label>
                        <input type="text" id="form-name" value="<?php echo $is_edit ? esc_attr($form_data->form_name) : ''; ?>" placeholder="<?php _e('Enter form name', 'dry-cleaning-forms'); ?>">
                    </div>
                    
                    <div class="dcf-field-group">
                        <label for="form-title"><?php _e('Form Title', 'dry-cleaning-forms'); ?></label>
                        <input type="text" id="form-title" value="<?php echo isset($form_config['title']) ? esc_attr($form_config['title']) : ''; ?>" placeholder="<?php _e('Enter form title', 'dry-cleaning-forms'); ?>">
                    </div>
                    
                    <div class="dcf-field-group">
                        <label for="form-type"><?php _e('Form Type', 'dry-cleaning-forms'); ?></label>
                        <select id="form-type">
                            <option value="contact" <?php selected($is_edit ? $form_data->form_type : '', 'contact'); ?>><?php _e('Contact Form', 'dry-cleaning-forms'); ?></option>
                            <option value="optin" <?php selected($is_edit ? $form_data->form_type : '', 'optin'); ?>><?php _e('Opt-in Form', 'dry-cleaning-forms'); ?></option>
                            <option value="signup" <?php selected($is_edit ? $form_data->form_type : '', 'signup'); ?>><?php _e('Customer Signup', 'dry-cleaning-forms'); ?></option>
                        </select>
                    </div>
                    
                    <div class="dcf-field-group">
                        <label for="form-description"><?php _e('Description', 'dry-cleaning-forms'); ?></label>
                        <textarea id="form-description" rows="3" placeholder="<?php _e('Enter form description', 'dry-cleaning-forms'); ?>"><?php echo isset($form_config['description']) ? esc_textarea($form_config['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="dcf-field-group">
                        <label>
                            <input type="checkbox" id="webhook-enabled" <?php checked(!empty($form_config['webhook_enabled']), true); ?>>
                            <?php _e('Enable Webhooks', 'dry-cleaning-forms'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Send webhook notifications when this form is submitted', 'dry-cleaning-forms'); ?>
                        </p>
                    </div>
                    
                    <div class="dcf-field-group" id="webhook-url-group" style="<?php echo empty($form_config['webhook_enabled']) ? 'display:none;' : ''; ?>">
                        <label for="webhook-url"><?php _e('Webhook URL', 'dry-cleaning-forms'); ?></label>
                        <input type="url" id="webhook-url" value="<?php echo $is_edit ? esc_attr($form_data->webhook_url) : ''; ?>" placeholder="<?php _e('https://example.com/webhook', 'dry-cleaning-forms'); ?>">
                        <p class="description">
                            <?php _e('Leave empty to use the global webhook URL from settings', 'dry-cleaning-forms'); ?>
                        </p>
                    </div>
                    
                    <div class="dcf-field-group">
                        <label>
                            <input type="checkbox" id="include-utm-parameters" <?php checked(!empty($form_config['include_utm_parameters']), true); ?>>
                            <?php _e('Include UTM Parameters', 'dry-cleaning-forms'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Automatically add UTM tracking fields as hidden fields to this form', 'dry-cleaning-forms'); ?>
                        </p>
                    </div>
                    
                    <div class="dcf-field-group">
                        <label for="success-message"><?php _e('Success Message', 'dry-cleaning-forms'); ?></label>
                        <textarea id="success-message" rows="3" placeholder="<?php _e('Thank you for your submission!', 'dry-cleaning-forms'); ?>"><?php echo isset($form_config['success_message']) ? esc_textarea($form_config['success_message']) : ''; ?></textarea>
                        <p class="description">
                            <?php _e('Message shown to users after successful form submission', 'dry-cleaning-forms'); ?>
                        </p>
                    </div>
                </div>
                </div>
            </div>
            
            <div class="dcf-sidebar-section">
                <h3><?php _e('POS Integration', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-section-content">
                <div class="dcf-pos-settings">
                    <?php 
                    $pos_system = DCF_Plugin_Core::get_setting('pos_system');
                    $pos_integration = isset($form_config['pos_integration']) ? $form_config['pos_integration'] : array();
                    ?>
                    
                    <?php if ($pos_system): ?>
                        <p class="description" style="margin-bottom: 15px;">
                            <?php printf(__('Your POS system is set to: <strong>%s</strong>', 'dry-cleaning-forms'), strtoupper($pos_system)); ?>
                        </p>
                        
                        <div class="dcf-field-group">
                            <label>
                                <input type="checkbox" id="pos-sync-enabled" <?php checked(!empty($pos_integration['enabled']), true); ?>>
                                <?php _e('Send data to POS system', 'dry-cleaning-forms'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, form submissions will be sent to your POS system.', 'dry-cleaning-forms'); ?>
                            </p>
                        </div>
                        
                        <div class="dcf-pos-options" style="<?php echo empty($pos_integration['enabled']) ? 'display: none;' : ''; ?>">
                            <div class="dcf-field-group">
                                <label>
                                    <input type="checkbox" id="pos-check-existing" <?php checked(!empty($pos_integration['check_existing_customer']), true); ?>>
                                    <?php _e('Check for existing customers', 'dry-cleaning-forms'); ?>
                                </label>
                            </div>
                            
                            <div class="dcf-field-group">
                                <label>
                                    <input type="checkbox" id="pos-create-customer" <?php checked(!empty($pos_integration['create_customer']), true); ?>>
                                    <?php _e('Create new customers', 'dry-cleaning-forms'); ?>
                                </label>
                            </div>
                            
                            <div class="dcf-field-group">
                                <label>
                                    <input type="checkbox" id="pos-update-customer" <?php checked(!empty($pos_integration['update_customer']), true); ?>>
                                    <?php _e('Update existing customers', 'dry-cleaning-forms'); ?>
                                </label>
                            </div>
                            
                            <?php 
                            $show_advanced = ($is_edit && $form_data->form_type === 'signup') || 
                                           (!$is_edit && isset($form_config['multi_step']) && $form_config['multi_step']);
                            if ($show_advanced): 
                            ?>
                            <div class="dcf-field-group">
                                <label>
                                    <input type="checkbox" id="pos-create-route" <?php checked(!empty($pos_integration['create_route']), true); ?>>
                                    <?php _e('Create pickup/delivery routes', 'dry-cleaning-forms'); ?>
                                </label>
                            </div>
                            
                            <div class="dcf-field-group">
                                <label>
                                    <input type="checkbox" id="pos-process-payment" <?php checked(!empty($pos_integration['process_payment']), true); ?>>
                                    <?php _e('Process payment information', 'dry-cleaning-forms'); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="description">
                            <?php _e('No POS system configured. Configure a POS system in Settings to enable integration.', 'dry-cleaning-forms'); ?>
                            <a href="<?php echo admin_url('admin.php?page=cmf-settings&tab=integrations'); ?>">
                                <?php _e('Go to Settings', 'dry-cleaning-forms'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Test Integration Features -->
                    <?php if ($pos_system && !empty($pos_integration['enabled'])): ?>
                    <div class="dcf-test-features" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                        <h4 style="margin: 0 0 10px 0;"><?php _e('Test Integration Features', 'dry-cleaning-forms'); ?></h4>
                        <p class="description" style="margin-bottom: 15px;">
                            <?php _e('Test individual POS integration features with custom data:', 'dry-cleaning-forms'); ?>
                        </p>
                        
                        <div class="dcf-test-inputs" style="margin-bottom: 15px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                                <div style="flex: 1; min-width: 150px;">
                                    <label for="test-email" style="display: block; font-size: 12px; margin-bottom: 3px;"><?php _e('Test Email:', 'dry-cleaning-forms'); ?></label>
                                    <input type="email" id="test-email" placeholder="test@example.com" style="width: 100%; padding: 4px 8px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px;">
                                </div>
                                <div style="flex: 1; min-width: 150px;">
                                    <label for="test-phone" style="display: block; font-size: 12px; margin-bottom: 3px;"><?php _e('Test Phone:', 'dry-cleaning-forms'); ?></label>
                                    <input type="tel" id="test-phone" placeholder="(555) 123-4567" style="width: 100%; padding: 4px 8px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px;">
                                </div>
                                <div style="flex: 1; min-width: 150px;">
                                    <label for="test-customer-id" style="display: block; font-size: 12px; margin-bottom: 3px;"><?php _e('Customer ID (for updates):', 'dry-cleaning-forms'); ?></label>
                                    <input type="text" id="test-customer-id" placeholder="customer123" style="width: 100%; padding: 4px 8px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="dcf-test-buttons">
                            <?php if (!empty($pos_integration['check_existing_customer'])): ?>
                            <button type="button" class="button button-secondary dcf-test-feature" data-feature="check_customer" style="margin: 2px;">
                                <?php _e('Test Customer Check', 'dry-cleaning-forms'); ?>
                            </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($pos_integration['create_customer'])): ?>
                            <button type="button" class="button button-secondary dcf-test-feature" data-feature="create_customer" style="margin: 2px;">
                                <?php _e('Test Create Customer', 'dry-cleaning-forms'); ?>
                            </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($pos_integration['update_customer'])): ?>
                            <button type="button" class="button button-secondary dcf-test-feature" data-feature="update_customer" style="margin: 2px;">
                                <?php _e('Test Update Customer', 'dry-cleaning-forms'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="dcf-test-results" style="margin-top: 15px; display: none;">
                            <h5 style="margin: 0 0 10px 0;"><?php _e('Test Results:', 'dry-cleaning-forms'); ?></h5>
                            <div class="dcf-test-output" style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
            
            <?php if (!$is_edit): ?>
            <div class="dcf-sidebar-section">
                <h3><?php _e('Form Templates', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-section-content">
                <div class="dcf-template-selector">
                    <select id="form-template-selector" class="widefat">
                        <option value=""><?php _e('Select a template...', 'dry-cleaning-forms'); ?></option>
                        <option value="new_customer_signup"><?php _e('New Customer Signup', 'dry-cleaning-forms'); ?></option>
                        <option value="quick_signup"><?php _e('Quick Signup', 'dry-cleaning-forms'); ?></option>
                        <option value="contact"><?php _e('Contact Form', 'dry-cleaning-forms'); ?></option>
                    </select>
                    <button type="button" class="button button-primary widefat" id="apply-template" style="margin-top: 10px;">
                        <?php _e('Apply Template', 'dry-cleaning-forms'); ?>
                    </button>
                    <p class="description" style="margin-top: 10px;">
                        <?php _e('Start with a pre-built form template and customize it to your needs.', 'dry-cleaning-forms'); ?>
                    </p>
                </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dcf-sidebar-section">
                <h3><?php _e('Form Styles', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-section-content">
                    <div class="dcf-form-styles">
                        <!-- Layout Type -->
                        <div class="dcf-field-group">
                            <label><?php _e('Layout Type', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-layout-selector">
                                <div class="dcf-layout-option <?php echo $form_styles['layout_type'] === 'single-column' ? 'active' : ''; ?>" data-layout="single-column">
                                    <div class="dcf-layout-preview">
                                        <div class="dcf-preview-field"></div>
                                        <div class="dcf-preview-field"></div>
                                    </div>
                                    <span><?php _e('Single Column', 'dry-cleaning-forms'); ?></span>
                                </div>
                                <div class="dcf-layout-option <?php echo $form_styles['layout_type'] === 'two-column' ? 'active' : ''; ?>" data-layout="two-column">
                                    <div class="dcf-layout-preview two-col">
                                        <div class="dcf-preview-field"></div>
                                        <div class="dcf-preview-field"></div>
                                        <div class="dcf-preview-field"></div>
                                        <div class="dcf-preview-field"></div>
                                    </div>
                                    <span><?php _e('Two Column', 'dry-cleaning-forms'); ?></span>
                                </div>
                                <div class="dcf-layout-option <?php echo $form_styles['layout_type'] === 'single-line' ? 'active' : ''; ?>" data-layout="single-line">
                                    <div class="dcf-layout-preview single-line">
                                        <div class="dcf-preview-field"></div>
                                        <div class="dcf-preview-field"></div>
                                    </div>
                                    <span><?php _e('Single Line', 'dry-cleaning-forms'); ?></span>
                                </div>
                            </div>
                            <input type="hidden" id="form-layout-type" value="<?php echo esc_attr($form_styles['layout_type']); ?>">
                        </div>
                        
                        <!-- Input Style -->
                        <div class="dcf-field-group">
                            <label for="form-input-style"><?php _e('Input Style', 'dry-cleaning-forms'); ?></label>
                            <select id="form-input-style">
                                <option value="box" <?php selected($form_styles['input_style'], 'box'); ?>><?php _e('Box', 'dry-cleaning-forms'); ?></option>
                                <option value="underline" <?php selected($form_styles['input_style'], 'underline'); ?>><?php _e('Underline', 'dry-cleaning-forms'); ?></option>
                                <option value="rounded" <?php selected($form_styles['input_style'], 'rounded'); ?>><?php _e('Rounded', 'dry-cleaning-forms'); ?></option>
                                <option value="material" <?php selected($form_styles['input_style'], 'material'); ?>><?php _e('Material', 'dry-cleaning-forms'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Width -->
                        <div class="dcf-field-group">
                            <label for="form-width"><?php _e('Width', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-input-with-unit">
                                <input type="number" id="form-width" value="<?php echo esc_attr($form_styles['width']); ?>" min="200" max="1200">
                                <select id="form-width-unit">
                                    <option value="px" <?php selected($form_styles['width_unit'], 'px'); ?>><?php _e('PX', 'dry-cleaning-forms'); ?></option>
                                    <option value="%" <?php selected($form_styles['width_unit'], '%'); ?>><?php _e('%', 'dry-cleaning-forms'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Field Spacing -->
                        <div class="dcf-field-group">
                            <label for="form-field-spacing"><?php _e('Field Spacing', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-input-with-unit">
                                <input type="number" id="form-field-spacing" value="<?php echo esc_attr($form_styles['field_spacing']); ?>" min="0" max="100">
                                <span class="dcf-unit-label"><?php _e('PX', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Label Width (for two-column layout) -->
                        <div class="dcf-field-group" id="label-width-group" style="display: none;">
                            <label for="form-label-width"><?php _e('Label Width', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-input-with-unit">
                                <input type="number" id="form-label-width" value="<?php echo esc_attr($form_styles['label_width']); ?>" min="100" max="400">
                                <span class="dcf-unit-label"><?php _e('PX', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Label Alignment -->
                        <div class="dcf-field-group">
                            <label><?php _e('Label Alignment', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-alignment-selector">
                                <button type="button" class="dcf-alignment-option <?php echo $form_styles['label_alignment'] === 'left' ? 'active' : ''; ?>" data-align="left" title="<?php _e('Left', 'dry-cleaning-forms'); ?>">
                                    <span class="dashicons dashicons-editor-alignleft"></span>
                                </button>
                                <button type="button" class="dcf-alignment-option <?php echo $form_styles['label_alignment'] === 'top' ? 'active' : ''; ?>" data-align="top" title="<?php _e('Top', 'dry-cleaning-forms'); ?>">
                                    <span class="dashicons dashicons-arrow-up-alt"></span>
                                </button>
                                <button type="button" class="dcf-alignment-option <?php echo $form_styles['label_alignment'] === 'right' ? 'active' : ''; ?>" data-align="right" title="<?php _e('Right', 'dry-cleaning-forms'); ?>">
                                    <span class="dashicons dashicons-editor-alignright"></span>
                                </button>
                            </div>
                            <input type="hidden" id="form-label-alignment" value="<?php echo esc_attr($form_styles['label_alignment']); ?>">
                        </div>
                        
                        <!-- Padding -->
                        <div class="dcf-field-group">
                            <label><?php _e('Padding', 'dry-cleaning-forms'); ?></label>
                            <div class="dcf-padding-editor">
                                <div class="dcf-padding-visual">
                                    <div class="dcf-padding-margin-label"><?php _e('MARGIN', 'dry-cleaning-forms'); ?></div>
                                    <div class="dcf-padding-container">
                                        <input type="number" class="dcf-padding-input dcf-padding-top" id="form-padding-top" value="<?php echo esc_attr($form_styles['padding_top']); ?>" min="0" max="200">
                                        <div class="dcf-padding-inner">
                                            <div class="dcf-padding-label"><?php _e('PADDING', 'dry-cleaning-forms'); ?></div>
                                            <div class="dcf-padding-horizontal">
                                                <input type="number" class="dcf-padding-input dcf-padding-left" id="form-padding-left" value="<?php echo esc_attr($form_styles['padding_left']); ?>" min="0" max="200">
                                                <div class="dcf-padding-content"></div>
                                                <input type="number" class="dcf-padding-input dcf-padding-right" id="form-padding-right" value="<?php echo esc_attr($form_styles['padding_right']); ?>" min="0" max="200">
                                            </div>
                                        </div>
                                        <input type="number" class="dcf-padding-input dcf-padding-bottom" id="form-padding-bottom" value="<?php echo esc_attr($form_styles['padding_bottom']); ?>" min="0" max="200">
                                    </div>
                                    <div class="dcf-padding-unit"><?php _e('PX', 'dry-cleaning-forms'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Show Label Toggle -->
                        <div class="dcf-field-group">
                            <label>
                                <input type="checkbox" id="form-show-labels" <?php checked($form_styles['show_labels'], true); ?>>
                                <?php _e('Show Labels', 'dry-cleaning-forms'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dcf-sidebar-section">
                <h3><?php _e('Form Fields', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-section-content">
                <div class="dcf-field-library dcf-field-categories">
                    <!-- Personal Info -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Personal Info', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item" data-field-type="name">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span class="dcf-field-label"><?php _e('Full Name', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <span class="dcf-field-label"><?php _e('Date of Birth', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="tel">
                                <span class="dashicons dashicons-phone"></span>
                                <span class="dcf-field-label"><?php _e('Phone', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="email">
                                <span class="dashicons dashicons-email"></span>
                                <span class="dcf-field-label"><?php _e('Email', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Submit', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item dcf-field-item-wide" data-field-type="submit">
                                <span class="dashicons dashicons-button"></span>
                                <span class="dcf-field-label"><?php _e('Button', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Address', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item" data-field-type="address">
                                <span class="dashicons dashicons-location"></span>
                                <span class="dcf-field-label"><?php _e('Address', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="text" data-field-subtype="city">
                                <span class="dashicons dashicons-building"></span>
                                <span class="dcf-field-label"><?php _e('City', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="text" data-field-subtype="state">
                                <span class="dashicons dashicons-location-alt"></span>
                                <span class="dcf-field-label"><?php _e('State', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="select" data-field-subtype="country">
                                <span class="dashicons dashicons-admin-site"></span>
                                <span class="dcf-field-label"><?php _e('Country', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="text" data-field-subtype="postal">
                                <span class="dashicons dashicons-tag"></span>
                                <span class="dcf-field-label"><?php _e('Postal Code', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Text -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Text', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item" data-field-type="text">
                                <span class="dashicons dashicons-edit"></span>
                                <span class="dcf-field-label"><?php _e('Single Line', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="textarea">
                                <span class="dashicons dashicons-text"></span>
                                <span class="dcf-field-label"><?php _e('Multi Line', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Choice Elements -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Choice Elements', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item" data-field-type="select">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <span class="dcf-field-label"><?php _e('Single Dropdown', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="select" data-field-subtype="multiple">
                                <span class="dashicons dashicons-menu"></span>
                                <span class="dcf-field-label"><?php _e('Multi Dropdown', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="checkbox">
                                <span class="dashicons dashicons-yes"></span>
                                <span class="dcf-field-label"><?php _e('Checkbox', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="radio">
                                <span class="dashicons dashicons-marker"></span>
                                <span class="dcf-field-label"><?php _e('Radio', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other Elements -->
                    <div class="dcf-field-category">
                        <h4 class="dcf-category-title"><?php _e('Other Elements', 'dry-cleaning-forms'); ?></h4>
                        <div class="dcf-field-grid">
                            <div class="dcf-field-item" data-field-type="image">
                                <span class="dashicons dashicons-format-image"></span>
                                <span class="dcf-field-label"><?php _e('Image', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="hidden">
                                <span class="dashicons dashicons-hidden"></span>
                                <span class="dcf-field-label"><?php _e('Hidden', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="date">
                                <span class="dashicons dashicons-calendar"></span>
                                <span class="dcf-field-label"><?php _e('Date Picker', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <div class="dcf-field-item" data-field-type="terms">
                                <span class="dashicons dashicons-privacy"></span>
                                <span class="dcf-field-label"><?php _e('Terms & Conditions', 'dry-cleaning-forms'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <div class="dcf-editor-main">
            <div class="dcf-editor-toolbar">
                <button type="button" class="button" id="dcf-test-ajax">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Test AJAX', 'dry-cleaning-forms'); ?>
                </button>
                <button type="button" class="button" id="dcf-preview-form">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Preview', 'dry-cleaning-forms'); ?>
                </button>
                <button type="button" class="button button-primary" id="dcf-save-form"<?php echo $is_edit ? ' data-form-id="' . esc_attr($form_id) . '"' : ''; ?>>
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo $is_edit ? __('Update Form', 'dry-cleaning-forms') : __('Save Form', 'dry-cleaning-forms'); ?>
                </button>
            </div>
            
            <div class="dcf-form-canvas">
                <div class="dcf-form-header">
                    <h2 id="canvas-form-title"><?php echo isset($form_config['title']) ? esc_html($form_config['title']) : __('Untitled Form', 'dry-cleaning-forms'); ?></h2>
                    <p id="canvas-form-description"><?php echo isset($form_config['description']) ? esc_html($form_config['description']) : ''; ?></p>
                </div>
                
                <div class="dcf-form-fields" id="dcf-form-fields">
                    <?php if (empty($form_config['fields'])): ?>
                        <div class="dcf-empty-form">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <p><?php _e('Drag fields from the sidebar to build your form', 'dry-cleaning-forms'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($form_config['fields'] as $field): ?>
                            <?php
                            // Generate field classes
                            $field_classes = ['dcf-form-field'];
                            if (!empty($field['required'])) {
                                $field_classes[] = 'dcf-field-required';
                            }
                            if (!empty($field['css_class'])) {
                                $field_classes[] = esc_attr($field['css_class']);
                            }
                            ?>
                            <div class="<?php echo implode(' ', $field_classes); ?>" data-field-id="<?php echo esc_attr($field['id']); ?>" data-field-type="<?php echo esc_attr($field['type']); ?>">
                                <div class="dcf-field-handle">
                                    <span class="dashicons dashicons-menu"></span>
                                </div>
                                <div class="dcf-field-preview">
                                    <?php
                                    // Generate field preview HTML
                                    $field_label = !empty($field['label']) ? $field['label'] : ucfirst($field['type']) . ' Field';
                                    $field_placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
                                    $field_required = !empty($field['required']);
                                    $field_name = $field['id']; // Use the field ID as the name
                                    ?>
                                    <?php if ($field['type'] !== 'submit'): ?>
                                    <label>
                                        <?php echo esc_html($field_label); ?>
                                        <?php if ($field_required): ?>
                                            <span class="dcf-required">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php endif; ?>
                                    
                                    <?php switch ($field['type']): 
                                        case 'name': ?>
                                            <div class="dcf-name-field">
                                                <div class="dcf-name-row">
                                                    <div class="dcf-name-first">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_first" placeholder="<?php echo esc_attr($field['first_placeholder'] ?? 'First Name'); ?>" disabled>
                                                    </div>
                                                    <div class="dcf-name-last">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_last" placeholder="<?php echo esc_attr($field['last_placeholder'] ?? 'Last Name'); ?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'terms': ?>
                                            <div class="dcf-terms-field">
                                                <label>
                                                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>" disabled>
                                                    <?php 
                                                    $terms_text = $field['terms_text'] ?? 'I have read and agree to the Terms and Conditions and Privacy Policy';
                                                    $terms_url = $field['terms_url'] ?? '';
                                                    $privacy_url = $field['privacy_url'] ?? '';
                                                    
                                                    if ($terms_url || $privacy_url) {
                                                        $terms_text = str_replace(
                                                            ['Terms and Conditions', 'Privacy Policy'],
                                                            [
                                                                $terms_url ? '<a href="' . esc_url($terms_url) . '" target="_blank">Terms and Conditions</a>' : 'Terms and Conditions',
                                                                $privacy_url ? '<a href="' . esc_url($privacy_url) . '" target="_blank">Privacy Policy</a>' : 'Privacy Policy'
                                                            ],
                                                            $terms_text
                                                        );
                                                    }
                                                    echo wp_kses($terms_text, ['a' => ['href' => [], 'target' => []]]);
                                                    ?>
                                                </label>
                                            </div>
                                            <?php break;
                                        case 'address': ?>
                                            <div class="dcf-address-field">
                                                <div class="dcf-address-row">
                                                    <div class="dcf-address-line1">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_line1" placeholder="<?php echo esc_attr($field['line1_placeholder'] ?? 'Address Line 1'); ?>" disabled>
                                                    </div>
                                                    <div class="dcf-address-line2">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_line2" placeholder="<?php echo esc_attr($field['line2_placeholder'] ?? 'Address Line 2'); ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="dcf-address-row">
                                                    <div class="dcf-address-city">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_city" placeholder="<?php echo esc_attr($field['city_placeholder'] ?? 'City'); ?>" disabled>
                                                    </div>
                                                    <div class="dcf-address-state">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_state" placeholder="<?php echo esc_attr($field['state_placeholder'] ?? 'State'); ?>" disabled>
                                                    </div>
                                                    <div class="dcf-address-zip">
                                                        <input type="text" name="<?php echo esc_attr($field_name); ?>_zip" placeholder="<?php echo esc_attr($field['zip_placeholder'] ?? 'Zip Code'); ?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php break;
                                        case 'textarea': ?>
                                            <textarea name="<?php echo esc_attr($field_name); ?>" placeholder="<?php echo esc_attr($field_placeholder); ?>" disabled></textarea>
                                            <?php break;
                                        case 'select': ?>
                                            <select name="<?php echo esc_attr($field_name); ?>" disabled>
                                                <?php if (!empty($field_placeholder)): ?>
                                                    <option value=""><?php echo esc_html($field_placeholder); ?></option>
                                                <?php endif; ?>
                                                <?php if (!empty($field['options'])): ?>
                                                    <?php foreach ($field['options'] as $option): ?>
                                                        <option><?php echo esc_html($option['label'] ?? $option); ?></option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option>Option 1</option>
                                                    <option>Option 2</option>
                                                <?php endif; ?>
                                            </select>
                                            <?php break;
                                        case 'radio': ?>
                                            <div class="dcf-radio-preview">
                                                <?php if (!empty($field['options'])): ?>
                                                    <?php foreach ($field['options'] as $option): ?>
                                                        <div><label><input type="radio" name="<?php echo esc_attr($field_name); ?>" disabled> <?php echo esc_html($option['label'] ?? $option); ?></label></div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div><label><input type="radio" name="<?php echo esc_attr($field_name); ?>" disabled> Option 1</label></div>
                                                    <div><label><input type="radio" name="<?php echo esc_attr($field_name); ?>" disabled> Option 2</label></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php break;
                                        case 'checkbox': ?>
                                            <div class="dcf-checkbox-preview">
                                                <?php if (!empty($field['options'])): ?>
                                                    <?php foreach ($field['options'] as $option): ?>
                                                        <div><label><input type="checkbox" name="<?php echo esc_attr($field_name); ?>" disabled> <?php echo esc_html($option['label'] ?? $option); ?></label></div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div><label><input type="checkbox" disabled> Checkbox option</label></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php break;
                                        case 'hidden': ?>
                                            <input type="hidden" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($field['default_value'] ?? 'hidden_value'); ?>">
                                            <em><?php _e('Hidden field (not visible to users)', 'dry-cleaning-forms'); ?></em>
                                            <?php break;
                                        case 'submit': ?>
                                            <?php
                                            $button_text = $field['button_text'] ?? 'Submit';
                                            $button_size = $field['button_size'] ?? 'medium';
                                            $alignment = $field['alignment'] ?? 'center';
                                            $bg_color = $field['bg_color'] ?? '#2271b1';
                                            $text_color = $field['text_color'] ?? '#ffffff';
                                            $border_color = $field['border_color'] ?? '#2271b1';
                                            $border_radius = $field['border_radius'] ?? '4';
                                            $min_width = $field['min_width'] ?? '';
                                            
                                            // Size classes
                                            $size_styles = array(
                                                'small' => 'padding: 8px 16px; font-size: 14px;',
                                                'medium' => 'padding: 12px 24px; font-size: 16px;',
                                                'large' => 'padding: 16px 32px; font-size: 18px;'
                                            );
                                            
                                            $button_style = $size_styles[$button_size] ?? $size_styles['medium'];
                                            $button_style .= "background-color: {$bg_color}; color: {$text_color}; border: 1px solid {$border_color}; border-radius: {$border_radius}px;";
                                            
                                            if ($min_width && $min_width !== '0') {
                                                $button_style .= " min-width: {$min_width}px;";
                                            }
                                            ?>
                                            <div style="text-align: <?php echo esc_attr($alignment); ?>; margin-top: 10px;">
                                                <button type="button" style="<?php echo esc_attr($button_style); ?> cursor: pointer;" disabled>
                                                    <?php echo esc_html($button_text); ?>
                                                </button>
                                                <br><small style="color: #666; font-style: italic;"><?php _e('Custom submit button preview', 'dry-cleaning-forms'); ?></small>
                                            </div>
                                            <?php break;
                                        default: ?>
                                            <input type="<?php echo esc_attr($field['type']); ?>" name="<?php echo esc_attr($field_name); ?>" placeholder="<?php echo esc_attr($field_placeholder); ?>" disabled>
                                            <?php break;
                                    endswitch; ?>
                                </div>
                                <div class="dcf-field-actions">
                                    <button type="button" class="dcf-edit-field" title="<?php _e('Edit Field', 'dry-cleaning-forms'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="dcf-duplicate-field" title="<?php _e('Duplicate Field', 'dry-cleaning-forms'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                    <button type="button" class="dcf-delete-field" title="<?php _e('Delete Field', 'dry-cleaning-forms'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Selection Modal (for new forms) -->
<?php if (!$is_edit): ?>
<div id="dcf-template-modal" class="dcf-modal">
    <div class="dcf-modal-content dcf-template-modal-content">
        <div class="dcf-modal-header">
            <h3><?php _e('Choose Your Starting Point', 'dry-cleaning-forms'); ?></h3>
            <button type="button" class="dcf-modal-close">&times;</button>
        </div>
        <div class="dcf-modal-body">
            <p class="dcf-template-intro"><?php _e('Get started quickly with a pre-built template or create your own form from scratch.', 'dry-cleaning-forms'); ?></p>
            
            <div class="dcf-template-options">
                <div class="dcf-template-grid">
                    <div class="dcf-template-option dcf-blank-template" data-template="">
                        <div class="dcf-template-icon">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </div>
                        <h4><?php _e('Blank Form', 'dry-cleaning-forms'); ?></h4>
                        <p><?php _e('Start with a completely blank form and build it yourself.', 'dry-cleaning-forms'); ?></p>
                        <button type="button" class="button button-secondary dcf-select-template" data-template="">
                            <?php _e('Start Blank', 'dry-cleaning-forms'); ?>
                        </button>
                    </div>
                    
                    <div class="dcf-template-option" data-template="contact">
                        <div class="dcf-template-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <h4><?php _e('Contact Form', 'dry-cleaning-forms'); ?></h4>
                        <p><?php _e('Standard contact form for customer inquiries and support requests.', 'dry-cleaning-forms'); ?></p>
                        <button type="button" class="button button-primary dcf-select-template" data-template="contact">
                            <?php _e('Use Template', 'dry-cleaning-forms'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Field Editor Modal -->
<div id="dcf-field-modal" class="dcf-modal" style="display: none;">
    <div class="dcf-modal-content">
        <div class="dcf-modal-header">
            <h3 id="dcf-modal-title"><?php _e('Edit Field', 'dry-cleaning-forms'); ?></h3>
            <button type="button" class="dcf-modal-close">&times;</button>
        </div>
        <div class="dcf-modal-body">
            <div class="dcf-field-group">
                <label for="field-label"><?php _e('Field Label', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-label" placeholder="<?php _e('Enter field label', 'dry-cleaning-forms'); ?>">
            </div>
            
            <div class="dcf-field-group">
                <label for="field-name"><?php _e('Field Name', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-name" placeholder="<?php _e('field_name', 'dry-cleaning-forms'); ?>">
            </div>
            
            <div class="dcf-field-group">
                <label for="field-placeholder"><?php _e('Placeholder Text', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-placeholder" placeholder="<?php _e('Enter placeholder text', 'dry-cleaning-forms'); ?>">
            </div>
            
            <div class="dcf-field-group">
                <label>
                    <input type="checkbox" id="field-required">
                    <?php _e('Required Field', 'dry-cleaning-forms'); ?>
                </label>
            </div>
            
            <div class="dcf-field-group" id="field-options-group" style="display: none;">
                <label for="field-options"><?php _e('Options (one per line)', 'dry-cleaning-forms'); ?></label>
                <textarea id="field-options" rows="4" placeholder="<?php _e('Option 1\nOption 2\nOption 3', 'dry-cleaning-forms'); ?>"></textarea>
            </div>
            
            <!-- Name Field Specific Options -->
            <div class="dcf-field-group" id="name-field-options" style="display: none;">
                <label for="field-first-placeholder"><?php _e('First Name Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-first-placeholder" placeholder="<?php _e('First Name', 'dry-cleaning-forms'); ?>">
                
                <label for="field-last-placeholder" style="margin-top: 10px;"><?php _e('Last Name Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-last-placeholder" placeholder="<?php _e('Last Name', 'dry-cleaning-forms'); ?>">
            </div>
            
            <!-- Terms Field Specific Options -->
            <div class="dcf-field-group" id="terms-field-options" style="display: none;">
                <label for="field-terms-text"><?php _e('Terms Text', 'dry-cleaning-forms'); ?></label>
                <textarea id="field-terms-text" rows="3" placeholder="<?php _e('I have read and agree to the Terms and Conditions and Privacy Policy', 'dry-cleaning-forms'); ?>"></textarea>
                
                <label for="field-terms-url" style="margin-top: 10px;"><?php _e('Terms and Conditions URL', 'dry-cleaning-forms'); ?></label>
                <input type="url" id="field-terms-url" placeholder="<?php _e('https://example.com/terms', 'dry-cleaning-forms'); ?>">
                
                <label for="field-privacy-url" style="margin-top: 10px;"><?php _e('Privacy Policy URL', 'dry-cleaning-forms'); ?></label>
                <input type="url" id="field-privacy-url" placeholder="<?php _e('https://example.com/privacy', 'dry-cleaning-forms'); ?>">
            </div>
            
            <!-- Hidden Field Specific Options -->
            <div class="dcf-field-group" id="hidden-field-options" style="display: none;">
                <label for="field-default-value"><?php _e('Default Value', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-default-value" placeholder="<?php _e('Enter default value', 'dry-cleaning-forms'); ?>">
            </div>
            
            <!-- Address Field Specific Options -->
            <div class="dcf-field-group" id="address-field-options" style="display: none;">
                <label for="field-line1-placeholder"><?php _e('Address Line 1 Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-line1-placeholder" placeholder="<?php _e('Address Line 1', 'dry-cleaning-forms'); ?>">
                
                <label for="field-line2-placeholder" style="margin-top: 10px;"><?php _e('Address Line 2 Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-line2-placeholder" placeholder="<?php _e('Address Line 2', 'dry-cleaning-forms'); ?>">
                
                <label for="field-city-placeholder" style="margin-top: 10px;"><?php _e('City Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-city-placeholder" placeholder="<?php _e('City', 'dry-cleaning-forms'); ?>">
                
                <label for="field-state-placeholder" style="margin-top: 10px;"><?php _e('State Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-state-placeholder" placeholder="<?php _e('State', 'dry-cleaning-forms'); ?>">
                
                <label for="field-zip-placeholder" style="margin-top: 10px;"><?php _e('Zip Code Placeholder', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-zip-placeholder" placeholder="<?php _e('Zip Code', 'dry-cleaning-forms'); ?>">
            </div>
            
            <!-- Submit Button Specific Options -->
            <div class="dcf-field-group" id="submit-field-options" style="display: none;">
                <label for="field-button-text"><?php _e('Button Text', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-button-text" placeholder="<?php _e('Submit', 'dry-cleaning-forms'); ?>">
                
                <label for="field-button-size" style="margin-top: 10px;"><?php _e('Button Size', 'dry-cleaning-forms'); ?></label>
                <select id="field-button-size">
                    <option value="small"><?php _e('Small', 'dry-cleaning-forms'); ?></option>
                    <option value="medium" selected><?php _e('Medium', 'dry-cleaning-forms'); ?></option>
                    <option value="large"><?php _e('Large', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <label for="field-alignment" style="margin-top: 10px;"><?php _e('Alignment', 'dry-cleaning-forms'); ?></label>
                <select id="field-alignment">
                    <option value="left"><?php _e('Left', 'dry-cleaning-forms'); ?></option>
                    <option value="center" selected><?php _e('Center', 'dry-cleaning-forms'); ?></option>
                    <option value="right"><?php _e('Right', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <label for="field-bg-color" style="margin-top: 10px;"><?php _e('Background Color', 'dry-cleaning-forms'); ?></label>
                <input type="color" id="field-bg-color" value="#2271b1">
                
                <label for="field-text-color" style="margin-top: 10px;"><?php _e('Text Color', 'dry-cleaning-forms'); ?></label>
                <input type="color" id="field-text-color" value="#ffffff">
                
                <label for="field-border-color" style="margin-top: 10px;"><?php _e('Border Color', 'dry-cleaning-forms'); ?></label>
                <input type="color" id="field-border-color" value="#2271b1">
                
                <label for="field-border-radius" style="margin-top: 10px;"><?php _e('Border Radius (px)', 'dry-cleaning-forms'); ?></label>
                <input type="number" id="field-border-radius" value="4" min="0" max="50">
                
                <label for="field-min-width" style="margin-top: 10px;"><?php _e('Min Width (px, leave blank for auto)', 'dry-cleaning-forms'); ?></label>
                <input type="number" id="field-min-width" placeholder="<?php _e('Auto', 'dry-cleaning-forms'); ?>" min="0">
            </div>
            
            <div class="dcf-field-group">
                <label for="field-css-class"><?php _e('CSS Class', 'dry-cleaning-forms'); ?></label>
                <input type="text" id="field-css-class" placeholder="<?php _e('custom-class', 'dry-cleaning-forms'); ?>">
            </div>
        </div>
        <div class="dcf-modal-footer">
            <button type="button" class="button" id="dcf-cancel-field"><?php _e('Cancel', 'dry-cleaning-forms'); ?></button>
            <button type="button" class="button button-primary" id="dcf-save-field"><?php _e('Save Field', 'dry-cleaning-forms'); ?></button>
        </div>
    </div>
</div>

<style>
.dcf-form-editor {
    margin-right: 20px;
}

.dcf-editor-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.dcf-editor-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.dcf-sidebar-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
}

.dcf-sidebar-section h3 {
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #c3c4c7;
    background: #f6f7f7;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    user-select: none;
    transition: background-color 0.2s ease;
}

.dcf-sidebar-section h3:hover {
    background: #eaeaea;
}

.dcf-sidebar-section h3::after {
    content: '\f140'; /* Dashicon down arrow */
    font-family: dashicons;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
}

.dcf-sidebar-section.collapsed h3::after {
    transform: translateY(-50%) rotate(-90deg);
}

.dcf-sidebar-section .dcf-section-content {
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    max-height: 2000px; /* Large enough for content */
}

.dcf-sidebar-section.collapsed .dcf-section-content {
    max-height: 0;
    border-bottom: none;
}

.dcf-form-settings {
    padding: 20px;
}

.dcf-field-group {
    margin-bottom: 15px;
}

.dcf-field-group:last-child {
    margin-bottom: 0;
}

.dcf-field-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 13px;
}

.dcf-field-group input,
.dcf-field-group select,
.dcf-field-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    font-size: 13px;
}

/* Fix checkbox styling to not take full width */
.dcf-field-group input[type="checkbox"] {
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    min-height: 16px !important;
    display: inline-block !important;
    margin-right: 8px !important;
    vertical-align: middle;
    padding: 0 !important;
    flex-shrink: 0;
}

/* Ensure POS settings checkboxes also display properly */
.dcf-pos-settings input[type="checkbox"] {
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    min-height: 16px !important;
    margin-right: 8px !important;
    padding: 0 !important;
    flex-shrink: 0;
}

.dcf-field-library {
    padding: 10px;
}

.dcf-field-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    margin-bottom: 5px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    cursor: grab;
    transition: all 0.2s ease;
    font-size: 13px;
}

.dcf-field-item:hover {
    background: #e6f3ff;
    border-color: #0073aa;
}

.dcf-field-item:active {
    cursor: grabbing;
}

.dcf-field-item .dashicons {
    color: #646970;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.dcf-editor-main {
    flex: 1;
    min-width: 0;
}

.dcf-editor-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dcf-form-canvas {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    min-height: 500px;
}

.dcf-form-header {
    padding: 30px;
    border-bottom: 1px solid #c3c4c7;
    background: #f6f7f7;
}

.dcf-form-header h2 {
    margin: 0 0 10px 0;
    font-size: 24px;
    color: #1d2327;
}

.dcf-form-header p {
    margin: 0;
    color: #646970;
    font-size: 16px;
}

.dcf-form-fields {
    padding: 30px;
    min-height: 400px;
}

.dcf-empty-form {
    text-align: center;
    padding: 60px 20px;
    color: #646970;
}

.dcf-empty-form .dashicons {
    font-size: 48px;
    margin-bottom: 15px;
    color: #c3c4c7;
}

.dcf-empty-form p {
    font-size: 16px;
    margin: 0;
}

.dcf-form-field {
    position: relative;
    margin-bottom: 20px;
    padding: 15px;
    border: 2px dashed transparent;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.dcf-form-field:hover {
    border-color: #c3c4c7;
    background: #f6f7f7;
}

.dcf-form-field.dcf-field-active {
    border-color: #0073aa;
    background: #e6f3ff;
}

.dcf-field-actions {
    position: absolute;
    top: 5px;
    right: 5px;
    display: none;
    gap: 5px;
}

.dcf-form-field:hover .dcf-field-actions {
    display: flex;
}

.dcf-field-actions button {
    padding: 5px;
    border: none;
    background: #0073aa;
    color: #fff;
    border-radius: 3px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.dcf-field-actions button:hover {
    background: #005a87;
}

.dcf-field-actions .dcf-delete-field {
    background: #d63638;
}

.dcf-field-actions .dcf-delete-field:hover {
    background: #b32d2e;
}

.dcf-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-modal-content {
    background: #fff;
    border-radius: 4px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.dcf-modal-header {
    padding: 20px;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dcf-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.dcf-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-modal-close:hover {
    color: #d63638;
}

.dcf-modal-body {
    padding: 20px;
}

.dcf-modal-footer {
    padding: 20px;
    border-top: 1px solid #c3c4c7;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Fix checkbox styling in modal editor */
.dcf-modal input[type="checkbox"],
.dcf-modal input[type="radio"] {
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
    margin-right: 8px !important;
    flex-shrink: 0;
    display: inline-block !important;
    vertical-align: middle;
}

.dcf-modal .dcf-field-group label {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dcf-modal .dcf-field-group label input[type="checkbox"] {
    order: -1; /* Put checkbox before text */
}

/* New Field Type Styles */
.dcf-name-field .dcf-name-row {
    display: flex;
    gap: 10px;
}

.dcf-name-first,
.dcf-name-last {
    flex: 1;
}

.dcf-name-first input,
.dcf-name-last input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    font-size: 13px;
}

.dcf-terms-field label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    line-height: 1.4;
}

.dcf-terms-field input[type="checkbox"] {
    margin-top: 2px;
    flex-shrink: 0;
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
}

.dcf-terms-field a {
    color: #0073aa;
    text-decoration: none;
}

.dcf-terms-field a:hover {
    text-decoration: underline;
}

/* Fix checkbox styling in all contexts */
.dcf-field-preview input[type="checkbox"],
.dcf-field-preview input[type="radio"] {
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
    margin-right: 8px;
    flex-shrink: 0;
}

.dcf-checkbox-preview label,
.dcf-radio-preview label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 8px;
}

.dcf-checkbox-preview input[type="checkbox"],
.dcf-radio-preview input[type="radio"] {
    margin: 0;
    margin-right: 8px;
}

@media (max-width: 768px) {
    .dcf-name-field .dcf-name-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .dcf-address-field .dcf-address-row {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 1200px) {
    .dcf-editor-container {
        flex-direction: column;
    }
    
    .dcf-editor-sidebar {
        width: 100%;
        order: 2;
    }
    
    .dcf-editor-main {
        order: 1;
    }
}

/* Address Field */
.dcf-address-field .dcf-address-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
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
    padding: 8px 12px;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    font-size: 13px;
}

/* Template Modal Styles */
.dcf-modal#dcf-template-modal {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
}

.dcf-template-modal-content {
    max-width: 800px;
    width: 95%;
    position: relative;
    transform: none;
    background: #fff;
    border-radius: 4px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
}

.dcf-template-intro {
    font-size: 16px;
    color: #646970;
    margin: 0 0 30px 0;
    text-align: center;
}

.dcf-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dcf-template-option {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: #fff;
    cursor: pointer;
}

.dcf-template-option:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.1);
    transform: translateY(-2px);
}

.dcf-template-option.dcf-blank-template {
    border-style: dashed;
    border-color: #c3c4c7;
}

.dcf-template-option.dcf-blank-template:hover {
    border-color: #646970;
    border-style: dashed;
}

.dcf-template-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #f0f6fc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    transition: all 0.3s ease;
}

.dcf-template-option:hover .dcf-template-icon {
    background: #0073aa;
    color: #fff;
}

.dcf-template-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #0073aa;
    transition: color 0.3s ease;
}

.dcf-template-option:hover .dcf-template-icon .dashicons {
    color: #fff;
}

.dcf-blank-template .dcf-template-icon {
    background: #f6f7f7;
}

.dcf-blank-template .dcf-template-icon .dashicons {
    color: #646970;
}

.dcf-blank-template:hover .dcf-template-icon {
    background: #646970;
}

.dcf-template-option h4 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
}

.dcf-template-option p {
    margin: 0 0 20px 0;
    font-size: 14px;
    color: #646970;
    line-height: 1.5;
    min-height: 42px;
}

.dcf-template-option .button {
    width: 100%;
    font-weight: 500;
    text-transform: none;
    transition: all 0.3s ease;
}

.dcf-template-option .button-primary {
    background: #0073aa;
    border-color: #0073aa;
}

.dcf-template-option .button-primary:hover {
    background: #005a87;
    border-color: #005a87;
}

@media (max-width: 640px) {
    .dcf-template-grid {
        grid-template-columns: 1fr;
    }
    
    .dcf-template-modal-content {
        width: 98%;
        margin: 10px;
    }
}

/* Form Styles Section */
.dcf-form-styles {
    padding: 20px;
}

/* Layout Selector */
.dcf-layout-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.dcf-layout-option {
    flex: 1;
    text-align: center;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dcf-layout-option:hover {
    border-color: #0073aa;
    background: #f0f8ff;
}

.dcf-layout-option.active {
    border-color: #0073aa;
    background: #e6f3ff;
}

.dcf-layout-preview {
    margin-bottom: 8px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 3px;
    min-height: 60px;
}

.dcf-preview-field {
    height: 8px;
    background: #0073aa;
    margin-bottom: 6px;
    border-radius: 2px;
}

.dcf-preview-field:last-child {
    margin-bottom: 0;
}

.dcf-layout-preview.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}

.dcf-layout-preview.single-line {
    display: flex;
    gap: 6px;
}

.dcf-layout-preview.single-line .dcf-preview-field {
    flex: 1;
    margin-bottom: 0;
}

.dcf-layout-option span {
    font-size: 12px;
    color: #666;
}

/* Input with Unit */
.dcf-input-with-unit {
    display: flex;
    gap: 5px;
    align-items: center;
}

.dcf-input-with-unit input {
    flex: 1;
}

.dcf-input-with-unit select {
    width: 60px;
}

.dcf-unit-label {
    font-size: 12px;
    color: #666;
    font-weight: 600;
    background: #f0f0f0;
    padding: 6px 12px;
    border-radius: 3px;
}

/* Alignment Selector */
.dcf-alignment-selector {
    display: flex;
    gap: 5px;
}

.dcf-alignment-option {
    flex: 1;
    padding: 8px;
    border: 1px solid #c3c4c7;
    background: #f6f7f7;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dcf-alignment-option:hover {
    background: #e0e0e0;
    border-color: #0073aa;
}

.dcf-alignment-option.active {
    background: #0073aa;
    border-color: #0073aa;
    color: #fff;
}

.dcf-alignment-option .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Padding Editor */
.dcf-padding-visual {
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    padding: 20px;
    background: #f9f9f9;
    position: relative;
}

.dcf-padding-margin-label {
    position: absolute;
    top: 5px;
    left: 10px;
    font-size: 10px;
    color: #999;
    font-weight: 600;
}

.dcf-padding-container {
    border: 2px dashed #ccc;
    padding: 20px;
    background: #fff;
    position: relative;
}

.dcf-padding-inner {
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.dcf-padding-label {
    text-align: center;
    font-size: 11px;
    color: #666;
    font-weight: 600;
    margin-bottom: 10px;
}

.dcf-padding-horizontal {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dcf-padding-content {
    flex: 1;
    height: 40px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.dcf-padding-input {
    width: 60px !important;
    padding: 4px 8px !important;
    font-size: 12px !important;
    text-align: center;
    border: 1px solid #0073aa !important;
    background: #e6f3ff !important;
}

.dcf-padding-top {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
}

.dcf-padding-bottom {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
}

.dcf-padding-left {
    margin-right: 10px;
}

.dcf-padding-right {
    margin-left: 10px;
}

.dcf-padding-unit {
    position: absolute;
    bottom: 5px;
    right: 10px;
    font-size: 10px;
    color: #999;
    font-weight: 600;
}

/* Show form styles controls based on layout */
.dcf-form-styles #label-width-group {
    transition: all 0.3s ease;
}
</style>

<!-- Form editor JavaScript is handled by the main admin.js file -->

<?php if ($is_edit && $form_config): ?>
<script>
// Pass form configuration to JavaScript for editing
window.dcfFormConfig = <?php echo wp_json_encode($form_config); ?>;
</script>
<?php endif; ?> 