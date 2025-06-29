<?php
/**
 * Visual Popup Editor View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get popup data if editing
$popup = null;
$is_new = !$popup_id || $action === 'new' || $action === 'visual-edit' && !$popup_id;

error_log('DCF Visual Editor: Initial check - popup_id = ' . $popup_id . ', action = ' . $action . ', is_new = ' . ($is_new ? 'true' : 'false'));

if (!$is_new && $popup_id) {
    $popup = $popup_manager->get_popup($popup_id);
    error_log('DCF Visual Editor: Retrieved popup data: ' . print_r($popup, true));
    if (!$popup) {
        wp_die(__('Popup not found.', 'dry-cleaning-forms'));
    }
}

// Check for success message
$message = $_GET['message'] ?? '';
$show_success = false;
$success_text = '';

if ($message === 'created') {
    $show_success = true;
    $success_text = __('Popup created successfully!', 'dry-cleaning-forms');
} elseif ($message === 'updated') {
    $show_success = true;
    $success_text = __('Popup updated successfully!', 'dry-cleaning-forms');
}

// Default values
$defaults = array(
    'popup_name' => '',
    'popup_type' => 'modal',
    'status' => 'draft',
    'trigger_settings' => array(
        'type' => 'time_delay',
        'max_displays' => 3
    ),
    'targeting_rules' => array(
        'pages' => array('mode' => 'all'),
        'users' => array('login_status' => '', 'visitor_type' => ''),
        'devices' => array('types' => array('desktop', 'mobile'))
    )
);

// If we have popup data, merge with defaults
if ($popup) {
    $popup_data = array_merge($defaults, (array) $popup);
    // Ensure nested arrays are properly merged
    if (!isset($popup_data['trigger_settings']) || !is_array($popup_data['trigger_settings'])) {
        $popup_data['trigger_settings'] = $defaults['trigger_settings'];
    }
    if (!isset($popup_data['targeting_rules']) || !is_array($popup_data['targeting_rules'])) {
        $popup_data['targeting_rules'] = $defaults['targeting_rules'];
    }
} else {
    $popup_data = $defaults;
}

// Check if popup has visual editor data
$visual_editor_data = null;
if ($popup && isset($popup['popup_config']) && is_array($popup['popup_config'])) {
    // Check if this popup was created with visual editor
    if (!empty($popup['popup_config']['visual_editor'])) {
        $visual_editor_data = array(
            'steps' => $popup['popup_config']['steps'] ?? [],
            'settings' => $popup['popup_config']['settings'] ?? [],
            'popup_name' => $popup['popup_name'] ?? '',
            'popup_type' => $popup['popup_type'] ?? 'modal',
            'status' => $popup['status'] ?? 'draft'
        );
        error_log('DCF Visual Editor: Found visual editor data: ' . print_r($visual_editor_data, true));
    } else {
        error_log('DCF Visual Editor: No visual editor flag found in popup_config: ' . print_r($popup['popup_config'], true));
    }
} else {
    error_log('DCF Visual Editor: Popup config not found or not array. Popup data: ' . print_r($popup, true));
}

// Debug: Log important variables
error_log('DCF Visual Editor: popup_id = ' . $popup_id);
error_log('DCF Visual Editor: action = ' . $action);
error_log('DCF Visual Editor: is_new = ' . ($is_new ? 'true' : 'false'));

?>

<div class="dcf-visual-editor-wrapper">
    <?php if ($show_success): ?>
        <div class="dcf-notification dcf-notification-success" style="position: fixed; top: 20px; right: 20px; z-index: 100000; background: white; padding: 15px 20px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); border-left: 4px solid #28a745;">
            <span class="dashicons dashicons-yes" style="color: #28a745; font-size: 20px; margin-right: 10px;"></span>
            <?php echo esc_html($success_text); ?>
        </div>
        <script>
            setTimeout(function() {
                jQuery('.dcf-notification').fadeOut();
            }, 3000);
        </script>
    <?php endif; ?>
    
    <form method="post" action="<?php echo admin_url('admin.php?page=cmf-popup-manager'); ?>" id="visual-popup-form" novalidate>
        <?php wp_nonce_field('dcf_popup_action', 'dcf_popup_nonce'); ?>
        <input type="hidden" name="popup_action" value="<?php echo $is_new ? 'create' : 'update'; ?>">
        <?php if (!$is_new): ?>
            <input type="hidden" name="popup_id" value="<?php echo $popup_id; ?>">
        <?php endif; ?>
        <input type="hidden" name="popup_data" id="popup_data" value="<?php echo $visual_editor_data ? esc_attr(json_encode($visual_editor_data)) : ''; ?>">
        <input type="hidden" name="popup_name" id="popup_name" value="<?php echo esc_attr($popup_data['popup_name'] ?? ''); ?>">
        
        <!-- Top Navigation Bar -->
        <div class="dcf-editor-topbar">
            <div class="dcf-editor-logo">
                <span class="dashicons dashicons-welcome-widgets-menus"></span>
                <input type="text" name="popup_name_visible" class="dcf-popup-name-input" 
                       value="<?php echo esc_attr($popup_data['popup_name'] ?: 'Untitled Popup'); ?>" 
                       placeholder="<?php _e('Enter popup name...', 'dry-cleaning-forms'); ?>" 
                       style="margin-left: 10px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 5px 10px; border-radius: 4px;">
            </div>
            
            <div class="dcf-editor-tabs">
                <a href="#design" class="dcf-editor-tab active" data-tab="design">
                    <?php _e('Design', 'dry-cleaning-forms'); ?>
                </a>
                <a href="#display-rules" class="dcf-editor-tab" data-tab="display-rules">
                    <?php _e('Display Rules', 'dry-cleaning-forms'); ?>
                </a>
                <a href="#publish" class="dcf-editor-tab" data-tab="publish">
                    <?php _e('Publish', 'dry-cleaning-forms'); ?>
                    <span class="dcf-tab-badge">!</span>
                </a>
            </div>
            
            <div class="dcf-editor-actions">
                <!-- Status Toggle -->
                <div class="dcf-status-toggle" style="display: inline-block; margin-right: 15px;">
                    <label style="margin-right: 5px; color: #ffffff;">
                        <?php _e('Status:', 'dry-cleaning-forms'); ?>
                    </label>
                    <select name="status" id="popup_status" class="dcf-status-select" style="height: 30px; min-width: 100px;">
                        <option value="draft" <?php selected($popup_data['status'] ?? 'draft', 'draft'); ?>>
                            <?php _e('Draft', 'dry-cleaning-forms'); ?>
                        </option>
                        <option value="active" <?php selected($popup_data['status'] ?? 'draft', 'active'); ?>>
                            <?php _e('Live', 'dry-cleaning-forms'); ?>
                        </option>
                    </select>
                </div>
                
                <button type="button" class="dcf-fullscreen-btn" title="<?php _e('Toggle Fullscreen', 'dry-cleaning-forms'); ?>">
                    <span class="dashicons dashicons-fullscreen-alt"></span>
                </button>
                <button type="button" class="dcf-editor-support">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('Support', 'dry-cleaning-forms'); ?>
                </button>
                <button type="submit" class="dcf-editor-save">
                    <?php _e('Save', 'dry-cleaning-forms'); ?>
                </button>
                <button type="button" class="dcf-editor-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>
        
        <!-- Tab Content Areas -->
        <div class="dcf-tab-contents">
            <!-- Design Tab Content -->
            <div class="dcf-tab-content dcf-tab-content-design active">
                <!-- Main Editor Area -->
                <div class="dcf-editor-main">
                    <!-- Left Sidebar - Blocks Panel -->
                    <div class="dcf-editor-sidebar">
                        <div class="dcf-sidebar-header">
                            <span class="dashicons dashicons-screenoptions"></span>
                            <?php _e('Blocks', 'dry-cleaning-forms'); ?>
                        </div>
                        
                        <div class="dcf-blocks-search">
                            <input type="text" placeholder="<?php _e('Search blocks...', 'dry-cleaning-forms'); ?>" class="dcf-search-input">
                        </div>
                        
                        <div class="dcf-blocks-container">
                            <div class="dcf-blocks-list">
                                <!-- Block categories will be dynamically populated by JavaScript -->
                            </div>
                        </div>
                        
                        <!-- Step Management Section -->
                        <div class="dcf-sidebar-section dcf-steps-section">
                            <div class="dcf-section-header" style="padding: 10px; border-top: 1px solid #e0e0e0;">
                                <span class="dashicons dashicons-layout"></span>
                                <span><?php _e('Steps', 'dry-cleaning-forms'); ?></span>
                                <button type="button" class="dcf-add-step" style="float: right; background: none; border: none; padding: 0; cursor: pointer;" title="<?php _e('Add Step', 'dry-cleaning-forms'); ?>">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                </button>
                            </div>
                            <div class="dcf-step-tabs-vertical" style="padding: 5px;">
                                <!-- Steps will be dynamically rendered by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="dcf-sidebar-footer">
                            <button type="button" class="dcf-sidebar-tool" title="<?php _e('Undo', 'dry-cleaning-forms'); ?>">
                                <span class="dashicons dashicons-undo"></span>
                            </button>
                            <button type="button" class="dcf-sidebar-tool" title="<?php _e('Redo', 'dry-cleaning-forms'); ?>">
                                <span class="dashicons dashicons-redo"></span>
                            </button>
                            <button type="button" class="dcf-sidebar-tool" title="<?php _e('Settings', 'dry-cleaning-forms'); ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </button>
                            <button type="button" class="dcf-sidebar-tool" title="<?php _e('Mobile Preview', 'dry-cleaning-forms'); ?>">
                                <span class="dashicons dashicons-smartphone"></span>
                            </button>
                            <button type="button" class="dcf-sidebar-tool" title="<?php _e('Import View', 'dry-cleaning-forms'); ?>">
                                <span class="dashicons dashicons-upload"></span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Center - Preview Area -->
                    <div class="dcf-editor-preview">
                        <!-- Device Preview Selector -->
                        <div class="dcf-preview-device-selector">
                            <button type="button" class="dcf-device-btn active" data-device="desktop">
                                <span class="dashicons dashicons-desktop"></span>
                                <?php _e('Desktop', 'dry-cleaning-forms'); ?>
                            </button>
                            <button type="button" class="dcf-device-btn" data-device="tablet">
                                <span class="dashicons dashicons-tablet"></span>
                                <?php _e('Tablet', 'dry-cleaning-forms'); ?>
                            </button>
                            <button type="button" class="dcf-device-btn" data-device="mobile">
                                <span class="dashicons dashicons-smartphone"></span>
                                <?php _e('Mobile', 'dry-cleaning-forms'); ?>
                            </button>
                        </div>
                        <div class="dcf-preview-container dcf-popup-preview-container" data-device="desktop">
                            <div class="dcf-popup-preview" id="visual-popup-preview">
                                <div class="dcf-popup dcf-popup-<?php echo esc_attr($popup_data['popup_type']); ?>" data-popup-type="<?php echo esc_attr($popup_data['popup_type']); ?>">
                                    <button class="dcf-popup-close" contenteditable="false">×</button>
                                    <div class="dcf-popup-content" id="popup-content-area">
                                        <?php if ($visual_editor_data && !empty($visual_editor_data['steps']) && !empty($visual_editor_data['steps'][0]['blocks'])): ?>
                                            <!-- Content will be loaded by JavaScript from saved data -->
                                            <div class="dcf-loading-placeholder" style="text-align: center; padding: 40px; color: #999;">
                                                <span class="dashicons dashicons-update spin" style="font-size: 30px;"></span>
                                                <p>Loading saved content...</p>
                                            </div>
                                        <?php else: ?>
                                            <!-- Default content for new popups -->
                                            <h2 class="dcf-editable" contenteditable="true" data-placeholder="Enter your headline..." data-block-id="block-default-1" data-block-type="heading">
                                                Ready to provide a better customer experience?
                                            </h2>
                                            <p class="dcf-editable" contenteditable="true" data-placeholder="Enter your description..." data-block-id="block-default-2" data-block-type="text">
                                                Subscribe to our weekly rundown of customer experience tips - straight to your inbox
                                            </p>
                                            <div class="dcf-form-container" data-block-type="fields" data-block-id="block-default-3">
                                                <input type="text" class="dcf-form-field" placeholder="Enter your name here...">
                                                <input type="email" class="dcf-form-field" placeholder="Enter your email here...">
                                                <button type="button" class="dcf-submit-button dcf-editable" contenteditable="true">
                                                    SUBSCRIBE
                                                </button>
                                            </div>
                                            <p class="dcf-privacy-text dcf-editable" contenteditable="true" data-placeholder="Privacy text..." data-block-id="block-default-4" data-block-type="text">
                                                We do not sell or share your information with anyone.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Sidebar - Settings Panel -->
                    <div class="dcf-editor-right-sidebar">
                        <div class="dcf-sidebar-header">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Block Settings', 'dry-cleaning-forms'); ?>
                        </div>
                        
                        <div class="dcf-settings-panels">
                            <!-- Block-specific settings will be shown here when a block is selected -->
                            <div class="dcf-no-selection">
                                <p><?php _e('Select a block to see its settings', 'dry-cleaning-forms'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Display Rules Tab Content -->
            <div class="dcf-tab-content dcf-tab-content-display-rules">
                <div class="dcf-tab-container">
                    <div class="dcf-display-rules-grid">
                        <!-- Trigger Settings -->
                        <div class="dcf-settings-section">
                            <h2><?php _e('Trigger Settings', 'dry-cleaning-forms'); ?></h2>
                            <div class="dcf-settings-card">
                                <div class="dcf-setting-group">
                                    <label for="ve_trigger_type"><?php _e('Trigger Type', 'dry-cleaning-forms'); ?></label>
                                    <select name="trigger_settings[type]" id="ve_trigger_type" class="dcf-setting-control">
                                        <?php
                                        $trigger_types = DCF_Popup_Triggers::get_trigger_types();
                                        foreach ($trigger_types as $type => $config): ?>
                                            <option value="<?php echo esc_attr($type); ?>" 
                                                    <?php selected(($popup_data['trigger_settings']['type'] ?? 'time_delay'), $type); ?>>
                                                <?php echo esc_html($config['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="dcf-setting-description" id="ve_trigger_description"></p>
                                </div>
                                
                                <div class="dcf-setting-group">
                                    <label for="ve_max_displays"><?php _e('Max Displays', 'dry-cleaning-forms'); ?></label>
                                    <input type="number" name="trigger_settings[max_displays]" id="ve_max_displays" 
                                           value="<?php echo esc_attr($popup_data['trigger_settings']['max_displays'] ?? 3); ?>" 
                                           min="1" max="20" class="dcf-setting-control">
                                    <p class="dcf-setting-description"><?php _e('Maximum times to show to a user', 'dry-cleaning-forms'); ?></p>
                                </div>
                                
                                <!-- Dynamic trigger-specific settings -->
                                <div id="ve_trigger_specific_settings">
                                    <!-- Will be populated by JavaScript based on trigger type -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Targeting Settings -->
                        <div class="dcf-settings-section">
                            <h2><?php _e('Targeting Settings', 'dry-cleaning-forms'); ?></h2>
                            <div class="dcf-settings-card">
                                <!-- Page Targeting -->
                                <div class="dcf-setting-section">
                                    <h4><?php _e('Page Targeting', 'dry-cleaning-forms'); ?></h4>
                                    <div class="dcf-setting-group">
                                        <label class="dcf-radio-label">
                                            <input type="radio" name="targeting_rules[pages][mode]" value="all" 
                                                   <?php checked(($popup_data['targeting_rules']['pages']['mode'] ?? 'all'), 'all'); ?>>
                                            <?php _e('All pages', 'dry-cleaning-forms'); ?>
                                        </label>
                                        <label class="dcf-radio-label">
                                            <input type="radio" name="targeting_rules[pages][mode]" value="specific" 
                                                   <?php checked(($popup_data['targeting_rules']['pages']['mode'] ?? ''), 'specific'); ?>>
                                            <?php _e('Specific pages', 'dry-cleaning-forms'); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- User Targeting -->
                                <div class="dcf-setting-section">
                                    <h4><?php _e('User Targeting', 'dry-cleaning-forms'); ?></h4>
                                    <div class="dcf-setting-group">
                                        <label for="ve_login_status"><?php _e('Login Status', 'dry-cleaning-forms'); ?></label>
                                        <select name="targeting_rules[users][login_status]" id="ve_login_status" class="dcf-setting-control">
                                            <option value=""><?php _e('All users', 'dry-cleaning-forms'); ?></option>
                                            <option value="logged_in" <?php selected($popup_data['targeting_rules']['users']['login_status'] ?? '', 'logged_in'); ?>>
                                                <?php _e('Logged in only', 'dry-cleaning-forms'); ?>
                                            </option>
                                            <option value="logged_out" <?php selected($popup_data['targeting_rules']['users']['login_status'] ?? '', 'logged_out'); ?>>
                                                <?php _e('Logged out only', 'dry-cleaning-forms'); ?>
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="dcf-setting-group">
                                        <label for="ve_visitor_type"><?php _e('Visitor Type', 'dry-cleaning-forms'); ?></label>
                                        <select name="targeting_rules[users][visitor_type]" id="ve_visitor_type" class="dcf-setting-control">
                                            <option value=""><?php _e('All visitors', 'dry-cleaning-forms'); ?></option>
                                            <option value="new" <?php selected($popup_data['targeting_rules']['users']['visitor_type'] ?? '', 'new'); ?>>
                                                <?php _e('New visitors only', 'dry-cleaning-forms'); ?>
                                            </option>
                                            <option value="returning" <?php selected($popup_data['targeting_rules']['users']['visitor_type'] ?? '', 'returning'); ?>>
                                                <?php _e('Returning visitors only', 'dry-cleaning-forms'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Device Targeting -->
                                <div class="dcf-setting-section">
                                    <h4><?php _e('Device Targeting', 'dry-cleaning-forms'); ?></h4>
                                    <div class="dcf-setting-group">
                                        <label class="dcf-checkbox-label">
                                            <input type="checkbox" name="targeting_rules[devices][types][]" value="desktop" 
                                                   <?php checked(in_array('desktop', $popup_data['targeting_rules']['devices']['types'] ?? array('desktop', 'mobile'))); ?>>
                                            <?php _e('Desktop', 'dry-cleaning-forms'); ?>
                                        </label>
                                        <label class="dcf-checkbox-label">
                                            <input type="checkbox" name="targeting_rules[devices][types][]" value="mobile" 
                                                   <?php checked(in_array('mobile', $popup_data['targeting_rules']['devices']['types'] ?? array('desktop', 'mobile'))); ?>>
                                            <?php _e('Mobile', 'dry-cleaning-forms'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <!-- Publish Tab Content -->
            <div class="dcf-tab-content dcf-tab-content-publish">
                <div class="dcf-tab-container">
                    <h2><?php _e('Publish Settings', 'dry-cleaning-forms'); ?></h2>
                    <div class="dcf-publish-card">
                        <div class="dcf-setting-group">
                            <label><?php _e('Popup Status', 'dry-cleaning-forms'); ?></label>
                            <p class="dcf-current-status">
                                <?php _e('Current status:', 'dry-cleaning-forms'); ?> 
                                <strong class="dcf-status-<?php echo esc_attr($popup_data['status'] ?? 'draft'); ?>">
                                    <?php echo $popup_data['status'] === 'active' ? __('Live', 'dry-cleaning-forms') : __('Draft', 'dry-cleaning-forms'); ?>
                                </strong>
                            </p>
                            <p class="dcf-setting-description">
                                <?php _e('Use the status dropdown in the top bar to change the popup status.', 'dry-cleaning-forms'); ?>
                            </p>
                        </div>
                        
                        <div class="dcf-setting-group">
                            <label><?php _e('Shortcode', 'dry-cleaning-forms'); ?></label>
                            <?php if (!$is_new): ?>
                                <code class="dcf-shortcode">[dcf_popup id="<?php echo $popup_id; ?>"]</code>
                                <p class="dcf-setting-description">
                                    <?php _e('Use this shortcode to display the popup on specific pages.', 'dry-cleaning-forms'); ?>
                                </p>
                            <?php else: ?>
                                <p class="dcf-setting-description">
                                    <?php _e('Save the popup first to get the shortcode.', 'dry-cleaning-forms'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
console.log('=== PHP Debug Info ===');
console.log('Popup ID:', <?php echo json_encode($popup_id); ?>);
console.log('Popup exists:', <?php echo json_encode($popup ? true : false); ?>);
<?php if ($popup): ?>
console.log('Popup config:', <?php echo json_encode($popup['popup_config'] ?? null); ?>);
console.log('Visual editor data:', <?php echo json_encode($visual_editor_data); ?>);
<?php endif; ?>
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.dashicons.spin {
    animation: spin 2s linear infinite;
    display: inline-block;
}
</style>