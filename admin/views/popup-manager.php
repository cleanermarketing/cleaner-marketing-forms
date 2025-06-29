<?php
/**
 * Popup Manager Main View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug logging - log every request that reaches this file
error_log('DCF Popup Manager: ===== FILE ACCESSED =====');
error_log('DCF Popup Manager: REQUEST_METHOD=' . $_SERVER['REQUEST_METHOD']);
error_log('DCF Popup Manager: REQUEST_URI=' . $_SERVER['REQUEST_URI']);
error_log('DCF Popup Manager: GET params - ' . print_r($_GET, true));
error_log('DCF Popup Manager: Action parameter = ' . ($_GET['action'] ?? 'none'));
error_log('DCF Popup Manager: Popup ID parameter = ' . ($_GET['popup_id'] ?? 'none'));

if ($_POST) {
    error_log('DCF Popup Manager: POST data received - ' . print_r($_POST, true));
    error_log('DCF Popup Manager: Has nonce: ' . (isset($_POST['dcf_popup_nonce']) ? 'yes' : 'no'));
    error_log('DCF Popup Manager: Nonce valid: ' . (wp_verify_nonce($_POST['dcf_popup_nonce'] ?? '', 'dcf_popup_action') ? 'yes' : 'no'));
} else {
    error_log('DCF Popup Manager: No POST data');
}

// Initialize popup manager
$popup_manager = new DCF_Popup_Manager();

// Handle form submissions
if ($_POST && isset($_POST['dcf_popup_nonce'])) {
    error_log('DCF Popup Manager: Checking nonce...');
    $nonce_valid = wp_verify_nonce($_POST['dcf_popup_nonce'], 'dcf_popup_action');
    error_log('DCF Popup Manager: Nonce verification result: ' . ($nonce_valid ? 'VALID' : 'INVALID'));
    
    if (!$nonce_valid) {
        error_log('DCF Popup Manager: Nonce value: ' . $_POST['dcf_popup_nonce']);
        error_log('DCF Popup Manager: Expected action: dcf_popup_action');
    }
}

if ($_POST && wp_verify_nonce($_POST['dcf_popup_nonce'] ?? '', 'dcf_popup_action')) {
    $popup_action = $_POST['popup_action'] ?? '';
    
    error_log('DCF Popup Manager: Form submission detected');
    error_log('DCF Popup Manager: Action: ' . $popup_action);
    error_log('DCF Popup Manager: Has popup_data: ' . (!empty($_POST['popup_data']) ? 'yes' : 'no'));
    if (!empty($_POST['popup_data'])) {
        error_log('DCF Popup Manager: popup_data content: ' . substr($_POST['popup_data'], 0, 200) . '...');
    }
    
    switch ($popup_action) {
        case 'create':
            // Check if this is from visual editor
            if (!empty($_POST['popup_data'])) {
                error_log('DCF Popup Manager: Visual editor data detected');
                $visual_data = json_decode(stripslashes($_POST['popup_data']), true);
                
                if ($visual_data) {
                    // Use popup name from the form input, not from the JSON data
                    $popup_name = !empty($_POST['popup_name_visible']) ? sanitize_text_field($_POST['popup_name_visible']) : 
                                  (!empty($_POST['popup_name']) ? sanitize_text_field($_POST['popup_name']) : 
                                  (!empty($visual_data['popup_name']) ? sanitize_text_field($visual_data['popup_name']) : 'Untitled Popup'));
                    
                    $popup_data = array(
                        'popup_name' => $popup_name,
                        'popup_type' => sanitize_text_field($visual_data['popup_type'] ?? $_POST['popup_type'] ?? 'modal'),
                        'status' => sanitize_text_field($visual_data['status'] ?? $_POST['status'] ?? 'draft'),
                        'popup_config' => array(
                            'visual_editor' => true,
                            'steps' => $visual_data['steps'] ?? array(),
                            'settings' => $visual_data['settings'] ?? array()
                        ),
                        'targeting_rules' => $visual_data['targeting_rules'] ?? $_POST['targeting_rules'] ?? array(
                            'pages' => array('mode' => 'all'),
                            'users' => array('login_status' => '', 'visitor_type' => ''),
                            'devices' => array('types' => array('desktop', 'mobile'))
                        ),
                        'trigger_settings' => $visual_data['trigger_settings'] ?? $_POST['trigger_settings'] ?? array(
                            'type' => 'time_delay',
                            'max_displays' => 3,
                            'delay' => 5
                        ),
                        'design_settings' => array()
                    );
                    
                    error_log('DCF Popup Manager: Processed visual editor data - ' . print_r($popup_data, true));
                } else {
                    error_log('DCF Popup Manager: Failed to decode visual editor data');
                }
            } else {
                // Standard form data
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
            }
            
            $result = $popup_manager->create_popup($popup_data);
            
            if ($result) {
                // Always redirect back to visual editor
                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $result . '&message=created'));
                exit;
            } else {
                $error_message = __('Failed to create popup.', 'dry-cleaning-forms');
            }
            break;
            
        case 'update':
            error_log('DCF Popup Manager: Update case triggered');
            error_log('DCF Popup Manager: popup_id = ' . $_POST['popup_id']);
            error_log('DCF Popup Manager: popup_action = ' . $_POST['popup_action']);
            error_log('DCF Popup Manager: popup_data length = ' . (isset($_POST['popup_data']) ? strlen($_POST['popup_data']) : 'not set'));
            if (!empty($_POST['popup_data'])) {
                $preview = substr($_POST['popup_data'], 0, 500);
                error_log('DCF Popup Manager: popup_data preview = ' . $preview);
            }
            
            $popup_id = intval($_POST['popup_id']);
            
            // Check if this is from visual editor
            if (!empty($_POST['popup_data'])) {
                error_log('DCF Popup Manager: Visual editor data detected for update');
                $visual_data = json_decode(stripslashes($_POST['popup_data']), true);
                
                if ($visual_data) {
                    // Use popup name from the form input, not from the JSON data
                    $popup_name = !empty($_POST['popup_name_visible']) ? sanitize_text_field($_POST['popup_name_visible']) : 
                                  (!empty($_POST['popup_name']) ? sanitize_text_field($_POST['popup_name']) : 
                                  (!empty($visual_data['popup_name']) ? sanitize_text_field($visual_data['popup_name']) : 'Untitled Popup'));
                    
                    // Get existing popup to preserve other settings
                    $existing_popup = $popup_manager->get_popup($popup_id);
                    
                    $popup_data = array(
                        'popup_name' => $popup_name,
                        'popup_type' => sanitize_text_field($visual_data['popup_type'] ?? $_POST['popup_type'] ?? 'modal'),
                        'status' => sanitize_text_field($visual_data['status'] ?? $_POST['status'] ?? 'draft'),
                        'popup_config' => array(
                            'visual_editor' => true,
                            'steps' => $visual_data['steps'] ?? array(),
                            'settings' => $visual_data['settings'] ?? array()
                        )
                    );
                    
                    // Preserve existing settings if they exist, unless new ones are provided
                    if ($existing_popup) {
                        // Use new targeting rules if provided, otherwise preserve existing
                        if (!empty($visual_data['targeting_rules'])) {
                            $popup_data['targeting_rules'] = $visual_data['targeting_rules'];
                        } elseif (!empty($_POST['targeting_rules'])) {
                            $popup_data['targeting_rules'] = $_POST['targeting_rules'];
                        } elseif (isset($existing_popup['targeting_rules'])) {
                            $popup_data['targeting_rules'] = $existing_popup['targeting_rules'];
                        }
                        
                        // Use new trigger settings if provided, otherwise preserve existing
                        if (!empty($visual_data['trigger_settings'])) {
                            $popup_data['trigger_settings'] = $visual_data['trigger_settings'];
                        } elseif (!empty($_POST['trigger_settings'])) {
                            $popup_data['trigger_settings'] = $_POST['trigger_settings'];
                        } elseif (isset($existing_popup['trigger_settings'])) {
                            $popup_data['trigger_settings'] = $existing_popup['trigger_settings'];
                        }
                        
                        // Preserve design settings
                        if (isset($existing_popup['design_settings'])) {
                            $popup_data['design_settings'] = $existing_popup['design_settings'];
                        }
                    } else {
                        // For new popups, use defaults if not provided
                        if (empty($popup_data['targeting_rules'])) {
                            $popup_data['targeting_rules'] = array(
                                'pages' => array('mode' => 'all'),
                                'users' => array('login_status' => '', 'visitor_type' => ''),
                                'devices' => array('types' => array('desktop', 'mobile'))
                            );
                        }
                        if (empty($popup_data['trigger_settings'])) {
                            $popup_data['trigger_settings'] = array(
                                'type' => 'time_delay',
                                'max_displays' => 3,
                                'delay' => 5
                            );
                        }
                    }
                    
                    error_log('DCF Popup Manager: Update - Processed visual editor data - ' . print_r($popup_data, true));
                    error_log('DCF Popup Manager: About to call update_popup with id=' . $popup_id);
                } else {
                    error_log('DCF Popup Manager: Failed to decode visual editor data');
                }
            } else {
                // Standard form data
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
            }
            
            error_log('DCF Popup Manager: Popup data prepared - ' . print_r($popup_data, true));
            
            $result = $popup_manager->update_popup($popup_id, $popup_data);
            
            if ($result) {
                error_log('DCF Popup Manager: Update successful, redirecting');
                // Always redirect back to visual editor
                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id . '&message=updated'));
                exit;
            } else {
                error_log('DCF Popup Manager: Update failed');
                $error_message = __('Failed to update popup.', 'dry-cleaning-forms');
            }
            break;
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] !== '-1' && !empty($_POST['popup_ids'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk-popups')) {
        wp_die(__('Security check failed.', 'dry-cleaning-forms'));
    }
    
    $popup_ids = array_map('intval', $_POST['popup_ids']);
    $bulk_action = $_POST['bulk_action'];
    
    switch ($bulk_action) {
        case 'activate':
            foreach ($popup_ids as $popup_id) {
                $popup_manager->update_popup($popup_id, array('status' => 'active'));
            }
            $success_message = sprintf(__('%d popups activated.', 'dry-cleaning-forms'), count($popup_ids));
            break;
            
        case 'deactivate':
            foreach ($popup_ids as $popup_id) {
                $popup_manager->update_popup($popup_id, array('status' => 'paused'));
            }
            $success_message = sprintf(__('%d popups deactivated.', 'dry-cleaning-forms'), count($popup_ids));
            break;
            
        case 'delete':
            foreach ($popup_ids as $popup_id) {
                $popup_manager->delete_popup($popup_id);
            }
            $success_message = sprintf(__('%d popups deleted.', 'dry-cleaning-forms'), count($popup_ids));
            break;
    }
    
    if (isset($success_message)) {
        wp_redirect(admin_url('admin.php?page=cmf-popup-manager&message=bulk_success&count=' . count($popup_ids) . '&action=' . $bulk_action));
        exit;
    }
}

// Handle individual actions
$action = $_GET['action'] ?? 'list';
$popup_id = intval($_GET['popup_id'] ?? 0);

// Handle delete action
if ($action === 'delete' && $popup_id) {
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_popup_' . $popup_id)) {
        wp_die(__('Security check failed.', 'dry-cleaning-forms'));
    }
    
    $result = $popup_manager->delete_popup($popup_id);
    
    if ($result) {
        wp_redirect(admin_url('admin.php?page=cmf-popup-manager&message=deleted'));
        exit;
    } else {
        $error_message = __('Failed to delete popup.', 'dry-cleaning-forms');
    }
}

// Handle duplicate action
if ($action === 'duplicate' && $popup_id) {
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'duplicate_popup_' . $popup_id)) {
        wp_die(__('Security check failed.', 'dry-cleaning-forms'));
    }
    
    $original_popup = $popup_manager->get_popup($popup_id);
    if ($original_popup) {
        $original_popup['popup_name'] .= ' (Copy)';
        $original_popup['status'] = 'draft';
        unset($original_popup['id']);
        
        $result = $popup_manager->create_popup($original_popup);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=cmf-popup-manager&message=duplicated'));
            exit;
        } else {
            $error_message = __('Failed to duplicate popup.', 'dry-cleaning-forms');
        }
    }
}

// Display messages
$message = $_GET['message'] ?? '';
$success_message = '';

switch ($message) {
    case 'created':
        $success_message = __('Popup created successfully.', 'dry-cleaning-forms');
        break;
    case 'updated':
        $success_message = __('Popup updated successfully.', 'dry-cleaning-forms');
        break;
    case 'deleted':
        $success_message = __('Popup deleted successfully.', 'dry-cleaning-forms');
        break;
    case 'duplicated':
        $success_message = __('Popup duplicated successfully.', 'dry-cleaning-forms');
        break;
    case 'bulk_success':
        $count = intval($_GET['count'] ?? 0);
        $bulk_action = $_GET['action'] ?? '';
        switch ($bulk_action) {
            case 'activate':
                $success_message = sprintf(__('%d popups activated.', 'dry-cleaning-forms'), $count);
                break;
            case 'deactivate':
                $success_message = sprintf(__('%d popups deactivated.', 'dry-cleaning-forms'), $count);
                break;
            case 'delete':
                $success_message = sprintf(__('%d popups deleted.', 'dry-cleaning-forms'), $count);
                break;
        }
        break;
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Popup Manager', 'dry-cleaning-forms'); ?></h1>
    
    <?php if ($action === 'list' || $action === ''): ?>
        <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=visual-edit'); ?>" class="page-title-action">
            <?php _e('Add New Popup', 'dry-cleaning-forms'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($success_message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php
    // Route to appropriate view
    switch ($action) {
        case 'new':
            // Redirect new action to visual editor
            wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit'));
            exit;
            break;
            
        case 'edit':
            // Redirect edit action to visual editor
            if ($popup_id) {
                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup_id));
            } else {
                wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=visual-edit'));
            }
            exit;
            break;
            
        case 'visual-edit':
            include_once plugin_dir_path(__FILE__) . 'popup-visual-editor.php';
            break;
            
        case 'analytics':
            include_once plugin_dir_path(__FILE__) . 'popup-analytics.php';
            break;
            
        case 'preview':
            // Handle popup preview
            error_log('DCF Popup Manager: Preview case triggered for popup_id=' . $popup_id);
            
            if ($popup_id) {
                $popup = $popup_manager->get_popup($popup_id);
                error_log('DCF Popup Manager: Popup data - ' . print_r($popup, true));
                
                if ($popup) {
                    // Decode JSON fields
                    $popup_config = is_string($popup['popup_config']) ? json_decode($popup['popup_config'], true) : $popup['popup_config'];
                    $design_settings = is_string($popup['design_settings']) ? json_decode($popup['design_settings'], true) : $popup['design_settings'];
                    $trigger_settings = is_string($popup['trigger_settings']) ? json_decode($popup['trigger_settings'], true) : $popup['trigger_settings'];
                    
                    error_log('DCF Popup Manager: Decoded popup_config - ' . print_r($popup_config, true));
                    error_log('DCF Popup Manager: Form ID from config - ' . ($popup_config['form_id'] ?? 'none'));
                    
                    // Get the form if one is selected
                    $form_html = '';
                    if (!empty($popup_config['form_id'])) {
                        error_log('DCF Popup Manager: Attempting to load form with ID=' . $popup_config['form_id']);
                        $form_builder = new DCF_Form_Builder();
                        $form = $form_builder->get_form($popup_config['form_id']);
                        error_log('DCF Popup Manager: Form data - ' . print_r($form, true));
                        
                        if ($form) {
                            $form_html = $form_builder->render_form($popup_config['form_id'], true); // true for preview mode
                            error_log('DCF Popup Manager: Form HTML generated - ' . strlen($form_html) . ' characters');
                        } else {
                            error_log('DCF Popup Manager: Form not found for ID=' . $popup_config['form_id']);
                        }
                    } else {
                        error_log('DCF Popup Manager: No form ID in popup config');
                    }
                    
                    // Generate preview HTML
                    echo '<div class="dcf-popup-preview-wrapper">';
                    echo '<div class="dcf-popup-preview-header">';
                    echo '<h2>' . __('Popup Preview', 'dry-cleaning-forms') . ' - ' . esc_html($popup['popup_name']) . '</h2>';
                    echo '<div class="dcf-popup-preview-info">';
                    echo '<span class="dcf-preview-status dcf-status-' . esc_attr($popup['status']) . '">' . esc_html(ucfirst($popup['status'])) . '</span>';
                    echo '<span class="dcf-preview-type">' . esc_html(ucfirst($popup['popup_type'])) . '</span>';
                    if (!empty($trigger_settings['type'])) {
                        echo '<span class="dcf-preview-trigger">Trigger: ' . esc_html(ucfirst(str_replace('_', ' ', $trigger_settings['type']))) . '</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="dcf-popup-preview-container">';
                    
                    // Apply design settings to preview
                    $preview_styles = array();
                    if (!empty($design_settings['width'])) {
                        $preview_styles[] = 'width: ' . esc_attr($design_settings['width']) . (is_numeric($design_settings['width']) ? 'px' : '');
                    }
                    if (!empty($design_settings['background_color'])) {
                        $preview_styles[] = 'background-color: ' . esc_attr($design_settings['background_color']);
                    }
                    if (!empty($design_settings['text_color'])) {
                        $preview_styles[] = 'color: ' . esc_attr($design_settings['text_color']);
                    }
                    if (!empty($design_settings['border_radius'])) {
                        $preview_styles[] = 'border-radius: ' . esc_attr($design_settings['border_radius']) . (is_numeric($design_settings['border_radius']) ? 'px' : '');
                    }
                    if (!empty($design_settings['padding'])) {
                        $preview_styles[] = 'padding: ' . esc_attr($design_settings['padding']) . (is_numeric($design_settings['padding']) ? 'px' : '');
                    }
                    
                    $style_attr = !empty($preview_styles) ? 'style="' . implode('; ', $preview_styles) . '"' : '';
                    
                    echo '<div class="dcf-popup-preview" ' . $style_attr . '>';
                    
                    if ($form_html) {
                        echo $form_html;
                    } else {
                        echo '<div class="dcf-no-form-selected">';
                        echo '<h3>' . esc_html($popup['popup_name']) . '</h3>';
                        echo '<p>' . __('No form selected for this popup.', 'dry-cleaning-forms') . '</p>';
                        echo '<p><em>' . __('Please select a form in the popup settings to see the preview.', 'dry-cleaning-forms') . '</em></p>';
                        echo '</div>';
                    }
                    
                    // Show close button if enabled
                    if (!empty($design_settings['close_button'])) {
                        echo '<button type="button" class="dcf-preview-close" onclick="alert(\'This is a preview - close button would work in live popup\')">&times;</button>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="dcf-popup-preview-actions">';
                    echo '<a href="' . admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id) . '" class="button button-primary">' . __('Edit Popup', 'dry-cleaning-forms') . '</a>';
                    echo '<a href="' . admin_url('admin.php?page=cmf-popup-manager') . '" class="button">' . __('Back to Popup Manager', 'dry-cleaning-forms') . '</a>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Popup not found.', 'dry-cleaning-forms') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . __('No popup ID provided.', 'dry-cleaning-forms') . '</p></div>';
            }
            break;
            
        case 'list':
        default:
            include_once plugin_dir_path(__FILE__) . 'popup-list.php';
            break;
    }
    ?>
</div>

<style>
.dcf-popup-preview-wrapper {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.dcf-popup-preview-header {
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.dcf-popup-preview-header h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.dcf-popup-preview-info {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.dcf-popup-preview-info span {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.dcf-preview-status {
    background: #f0f0f1;
    color: #50575e;
}

.dcf-status-active {
    background: #00a32a !important;
    color: white !important;
}

.dcf-status-draft {
    background: #dba617 !important;
    color: white !important;
}

.dcf-status-paused {
    background: #d63638 !important;
    color: white !important;
}

.dcf-preview-type {
    background: #2271b1;
    color: white;
}

.dcf-preview-trigger {
    background: #8c8f94;
    color: white;
}

.dcf-popup-preview-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 40px 20px;
    margin: 20px 0;
    background: #f9f9f9;
    text-align: center;
    position: relative;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-popup-preview {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 30px;
    max-width: 600px;
    width: 100%;
    margin: 0 auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    position: relative;
    text-align: left;
}

.dcf-popup-preview h3 {
    margin-top: 0;
    color: #333;
    text-align: center;
}

.dcf-no-form-selected {
    text-align: center;
    color: #666;
}

.dcf-no-form-selected h3 {
    color: #333;
    margin-bottom: 15px;
}

.dcf-no-form-selected p {
    margin: 10px 0;
}

.dcf-no-form-selected em {
    color: #999;
    font-size: 14px;
}

.dcf-preview-close {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.dcf-preview-close:hover {
    background: #f0f0f0;
    color: #333;
}

.dcf-popup-preview-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
    margin-top: 20px;
}

.dcf-popup-preview-actions .button {
    margin: 0 5px;
}

/* Form styling in preview */
.dcf-popup-preview .dcf-form {
    margin: 0;
}

.dcf-popup-preview .dcf-form-field {
    margin-bottom: 15px;
}

.dcf-popup-preview .dcf-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.dcf-popup-preview .dcf-form-field input,
.dcf-popup-preview .dcf-form-field textarea,
.dcf-popup-preview .dcf-form-field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.dcf-popup-preview .dcf-form-field input:focus,
.dcf-popup-preview .dcf-form-field textarea:focus,
.dcf-popup-preview .dcf-form-field select:focus {
    outline: none;
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}

.dcf-popup-preview .dcf-form-submit {
    text-align: center;
    margin-top: 20px;
}

.dcf-popup-preview .dcf-form-submit button {
    background: #2271b1;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.dcf-popup-preview .dcf-form-submit button:hover {
    background: #135e96;
}
</style> 