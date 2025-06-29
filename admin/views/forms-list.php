<?php
/**
 * Forms List View - Redesigned to match Popup Manager
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get counts for different statuses
$total_forms = count($forms);
$form_types = array();
foreach ($forms as $form) {
    $type = $form->form_type;
    if (!isset($form_types[$type])) {
        $form_types[$type] = 0;
    }
    $form_types[$type]++;
}

// Handle messages
$message = $_GET['message'] ?? '';
$success_message = '';

switch ($message) {
    case 'created':
        $success_message = __('Form created successfully.', 'dry-cleaning-forms');
        break;
    case 'updated':
        $success_message = __('Form updated successfully.', 'dry-cleaning-forms');
        break;
    case 'deleted':
        $success_message = __('Form deleted successfully.', 'dry-cleaning-forms');
        break;
    case 'duplicated':
        $success_message = __('Form duplicated successfully.', 'dry-cleaning-forms');
        break;
}

// Filters
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Filter forms
if ($type_filter !== 'all' || !empty($search)) {
    $forms = array_filter($forms, function($form) use ($type_filter, $search) {
        // Type filter
        if ($type_filter !== 'all' && $form->form_type !== $type_filter) {
            return false;
        }
        
        // Search filter
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $form_config = is_array($form->form_config) ? $form->form_config : array();
            $form_title = !empty($form_config['title']) ? $form_config['title'] : $form->form_name;
            
            if (strpos(strtolower($form_title), $search_lower) === false &&
                strpos(strtolower($form->form_name), $search_lower) === false) {
                return false;
            }
        }
        
        return true;
    });
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Form Builder', 'dry-cleaning-forms'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=cmf-form-builder&action=new'); ?>" class="page-title-action">
        <?php _e('Add New Form', 'dry-cleaning-forms'); ?>
    </a>
    
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
    
    <!-- Status filters -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo admin_url('admin.php?page=cmf-form-builder'); ?>" 
               class="<?php echo $type_filter === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $total_forms; ?>)</span>
            </a>
        </li>
        <?php foreach ($form_types as $type => $count): ?>
            <li class="<?php echo esc_attr($type); ?>">
                |
                <a href="<?php echo admin_url('admin.php?page=cmf-form-builder&type=' . $type); ?>" 
                   class="<?php echo $type_filter === $type ? 'current' : ''; ?>">
                    <?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?> 
                    <span class="count">(<?php echo $count; ?>)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Search and filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="dcf-form-builder">
                
                <select name="type">
                    <option value="all"><?php _e('All Form Types', 'dry-cleaning-forms'); ?></option>
                    <option value="contact" <?php selected($type_filter, 'contact'); ?>><?php _e('Contact', 'dry-cleaning-forms'); ?></option>
                    <option value="signup" <?php selected($type_filter, 'signup'); ?>><?php _e('Signup', 'dry-cleaning-forms'); ?></option>
                    <option value="optin" <?php selected($type_filter, 'optin'); ?>><?php _e('Opt-in', 'dry-cleaning-forms'); ?></option>
                    <option value="form_builder" <?php selected($type_filter, 'form_builder'); ?>><?php _e('Custom', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'dry-cleaning-forms'); ?>">
            </form>
        </div>
        
        <div class="alignright actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="dcf-form-builder">
                <?php if ($type_filter !== 'all'): ?>
                    <input type="hidden" name="type" value="<?php echo esc_attr($type_filter); ?>">
                <?php endif; ?>
                
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Search forms...', 'dry-cleaning-forms'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'dry-cleaning-forms'); ?>">
            </form>
        </div>
    </div>

    <?php if (empty($forms)): ?>
        <div class="dcf-empty-state" style="text-align: center; padding: 60px 20px;">
            <div class="dcf-empty-state-icon" style="font-size: 48px; color: #ddd; margin-bottom: 20px;">
                <span class="dashicons dashicons-feedback"></span>
            </div>
            <h2><?php _e('No forms found', 'dry-cleaning-forms'); ?></h2>
            <p><?php _e('Create your first form to start collecting customer information.', 'dry-cleaning-forms'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=cmf-form-builder&action=new'); ?>" class="button button-primary button-large">
                <?php _e('Create Your First Form', 'dry-cleaning-forms'); ?>
            </a>
        </div>
    <?php else: ?>
        <!-- Forms table -->
        <form method="post" action="">
            <?php wp_nonce_field('dcf_form_action', 'dcf_form_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action">
                        <option value="-1"><?php _e('Bulk Actions', 'dry-cleaning-forms'); ?></option>
                        <option value="delete"><?php _e('Delete', 'dry-cleaning-forms'); ?></option>
                    </select>
                    <input type="submit" class="button action" value="<?php _e('Apply', 'dry-cleaning-forms'); ?>">
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column column-name column-primary">
                            <?php _e('Name', 'dry-cleaning-forms'); ?>
                        </th>
                        <th class="manage-column column-type">
                            <?php _e('Type', 'dry-cleaning-forms'); ?>
                        </th>
                        <th class="manage-column column-fields">
                            <?php _e('Fields', 'dry-cleaning-forms'); ?>
                        </th>
                        <th class="manage-column column-stats">
                            <?php _e('Performance', 'dry-cleaning-forms'); ?>
                        </th>
                        <th class="manage-column column-shortcode">
                            <?php _e('Shortcode', 'dry-cleaning-forms'); ?>
                        </th>
                        <th class="manage-column column-date">
                            <?php _e('Created', 'dry-cleaning-forms'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <?php
                        // Get form data
                        $form_config = is_array($form->form_config) ? $form->form_config : array();
                        $form_title = !empty($form_config['title']) ? $form_config['title'] : $form->form_name;
                        $field_count = !empty($form_config['fields']) ? count($form_config['fields']) : 0;
                        $is_system_form = ($form->id === 'default_signup');
                        
                        // Get performance metrics
                        global $wpdb;
                        $submissions_table = $wpdb->prefix . 'dcf_submissions';
                        $views = 0;
                        $submissions = 0;
                        $conversion_rate = 0;
                        
                        if (!$is_system_form) {
                            // Get submission count - form_id is stored as numeric ID
                            $submissions = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $submissions_table WHERE form_id = %s",
                                $form->id
                            ));
                            
                            // Get actual view count from analytics tracking
                            $analytics_table = $wpdb->prefix . 'dcf_analytics';
                            $views = $wpdb->get_var($wpdb->prepare(
                                "SELECT SUM(views) FROM $analytics_table WHERE entity_type = 'form' AND entity_id = %s",
                                $form->id
                            ));
                            
                            // If no analytics data exists, estimate views
                            if (!$views && $submissions > 0) {
                                $views = $submissions * 10; // Rough estimate
                            }
                            
                            $conversion_rate = $views > 0 ? round(($submissions / $views) * 100, 1) : 0;
                        }
                        
                        // Check if this form is used by any popups
                        $popups_table = $wpdb->prefix . 'dcf_popups';
                        $popup_count = 0;
                        
                        if (!$is_system_form) {
                            $popup_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $popups_table 
                                 WHERE (popup_config LIKE %s OR popup_config LIKE %s) AND status = 'active'",
                                '%"form_id":"' . $form->id . '"%',
                                '%"form_id":' . $form->id . '%'
                            ));
                        }
                        
                        // Build edit URL
                        $edit_url = $is_system_form ? '#' : admin_url('admin.php?page=cmf-form-builder&action=edit&form_id=' . $form->id);
                        $analytics_url = admin_url('admin.php?page=cmf-analytics&form_id=' . $form->id);
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <?php if (!$is_system_form): ?>
                                    <input type="checkbox" name="form_ids[]" value="<?php echo $form->id; ?>">
                                <?php endif; ?>
                            </th>
                            <td class="column-name column-primary">
                                <strong>
                                    <?php if (!$is_system_form): ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="row-title">
                                            <?php echo esc_html($form_title); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($form_title); ?>
                                    <?php endif; ?>
                                </strong>
                                
                                <?php if ($popup_count > 0): ?>
                                    <span class="dcf-form-badge" style="background: #2271b1; color: white;">
                                        <?php echo sprintf(_n('%d Popup', '%d Popups', $popup_count, 'dry-cleaning-forms'), $popup_count); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($is_system_form): ?>
                                    <span class="dcf-form-badge" style="background: #666; color: white;">
                                        <?php _e('System Form', 'dry-cleaning-forms'); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="row-actions">
                                    <?php if (!$is_system_form): ?>
                                        <span class="edit">
                                            <a href="<?php echo esc_url($edit_url); ?>">
                                                <?php _e('Edit', 'dry-cleaning-forms'); ?>
                                            </a> |
                                        </span>
                                    <?php endif; ?>
                                    <span class="analytics">
                                        <a href="<?php echo esc_url($analytics_url); ?>">
                                            <?php _e('Analytics', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="preview">
                                        <a href="<?php echo home_url('?dcf_preview=1&form_id=' . $form->id); ?>" target="_blank">
                                            <?php _e('Preview', 'dry-cleaning-forms'); ?>
                                        </a>
                                        <?php if (!$is_system_form): ?>|<?php endif; ?>
                                    </span>
                                    <?php if (!$is_system_form): ?>
                                        <span class="duplicate">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-form-builder&action=duplicate&form_id=' . $form->id), 'duplicate_form_' . $form->id); ?>">
                                                <?php _e('Duplicate', 'dry-cleaning-forms'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-form-builder&action=delete&form_id=' . $form->id), 'delete_form_' . $form->id); ?>" 
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this form?', 'dry-cleaning-forms'); ?>')">
                                                <?php _e('Delete', 'dry-cleaning-forms'); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-type">
                                <?php
                                $type_labels = array(
                                    'contact' => __('Contact', 'dry-cleaning-forms'),
                                    'signup' => __('Signup', 'dry-cleaning-forms'),
                                    'signup_form' => __('Signup', 'dry-cleaning-forms'),
                                    'optin' => __('Opt-in', 'dry-cleaning-forms'),
                                    'form_builder' => __('Custom', 'dry-cleaning-forms')
                                );
                                $type_label = isset($type_labels[$form->form_type]) ? $type_labels[$form->form_type] : ucwords(str_replace('_', ' ', $form->form_type));
                                ?>
                                <span class="dcf-type-badge dcf-type-<?php echo esc_attr($form->form_type); ?>">
                                    <?php echo esc_html($type_label); ?>
                                </span>
                            </td>
                            <td class="column-fields">
                                <?php echo sprintf(_n('%d field', '%d fields', $field_count, 'dry-cleaning-forms'), $field_count); ?>
                            </td>
                            <td class="column-stats">
                                <?php if (!$is_system_form): ?>
                                    <div class="dcf-stats-inline">
                                        <div class="dcf-stat">
                                            <span class="dcf-stat-value"><?php echo number_format($views); ?></span>
                                            <span class="dcf-stat-label"><?php _e('Views', 'dry-cleaning-forms'); ?></span>
                                        </div>
                                        <div class="dcf-stat">
                                            <span class="dcf-stat-value"><?php echo number_format($submissions); ?></span>
                                            <span class="dcf-stat-label"><?php _e('Submissions', 'dry-cleaning-forms'); ?></span>
                                        </div>
                                        <div class="dcf-stat">
                                            <span class="dcf-stat-value"><?php echo $conversion_rate; ?>%</span>
                                            <span class="dcf-stat-label"><?php _e('Conversion', 'dry-cleaning-forms'); ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="description"><?php _e('Multi-step form', 'dry-cleaning-forms'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-shortcode">
                                <?php if ($is_system_form): ?>
                                    <code>[dcf_signup_form]</code>
                                <?php else: ?>
                                    <code>[dcf_form id="<?php echo $form->id; ?>"]</code>
                                <?php endif; ?>
                                <button type="button" class="button-small dcf-copy-shortcode" data-shortcode="<?php echo $is_system_form ? '[dcf_signup_form]' : '[dcf_form id=&quot;' . $form->id . '&quot;]'; ?>" title="<?php _e('Copy shortcode', 'dry-cleaning-forms'); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </td>
                            <td class="column-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($form->created_at)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php endif; ?>
</div>

<style>
/* Performance stats styling */
.dcf-stats-inline {
    display: flex;
    gap: 15px;
}

.dcf-stat {
    text-align: center;
}

.dcf-stat-value {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #2271b1;
}

.dcf-stat-label {
    display: block;
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
}

/* Type badges */
.dcf-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.dcf-type-contact {
    background: #e7f3ff;
    color: #0073aa;
}

.dcf-type-signup,
.dcf-type-signup_form {
    background: #d4f4dd;
    color: #00a32a;
}

.dcf-type-optin {
    background: #fcf0e1;
    color: #996800;
}

.dcf-type-form_builder {
    background: #f0e6ff;
    color: #6528a0;
}

/* Form badges */
.dcf-form-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    margin-left: 5px;
}

/* Copy shortcode button */
.dcf-copy-shortcode {
    padding: 2px 6px !important;
    margin-left: 5px !important;
    vertical-align: middle !important;
    cursor: pointer;
}

.dcf-copy-shortcode .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Column widths */
.column-name {
    width: 25%;
}

.column-type {
    width: 10%;
}

.column-fields {
    width: 10%;
}

.column-stats {
    width: 20%;
}

.column-shortcode {
    width: 20%;
}

.column-date {
    width: 10%;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode functionality
    $('.dcf-copy-shortcode').on('click', function(e) {
        e.preventDefault();
        var shortcode = $(this).data('shortcode');
        
        // Create temporary input
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        var $button = $(this);
        var originalTitle = $button.attr('title');
        $button.attr('title', '<?php _e('Copied!', 'dry-cleaning-forms'); ?>');
        $button.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
        
        setTimeout(function() {
            $button.attr('title', originalTitle);
            $button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
        }, 2000);
    });
});
</script>