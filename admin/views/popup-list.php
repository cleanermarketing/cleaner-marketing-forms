<?php
/**
 * Popup List View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['s'] ?? '';

// Get popups with error handling
$args = array(
    'status' => $status_filter,
    'type' => $type_filter,
    'limit' => 20,
    'offset' => 0
);

try {
    $popups = $popup_manager->get_popups($args);
} catch (Exception $e) {
    $popups = array(); // Fallback to empty array if there's an error
}

// Get status counts for filter tabs with error handling
try {
    $all_popups = $popup_manager->get_popups(array('limit' => 1000));
} catch (Exception $e) {
    $all_popups = array(); // Fallback to empty array
}

$status_counts = array(
    'all' => count($all_popups),
    'active' => count(array_filter($all_popups, function($p) { return $p['status'] === 'active'; })),
    'draft' => count(array_filter($all_popups, function($p) { return $p['status'] === 'draft'; })),
    'paused' => count(array_filter($all_popups, function($p) { return $p['status'] === 'paused'; }))
);

?>

<div class="dcf-popup-manager">
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Popup deleted successfully.', 'dry-cleaning-forms'); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Header with actions -->
    <div class="dcf-popup-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="wp-heading-inline"><?php _e('Popup Manager', 'dry-cleaning-forms'); ?></h1>
        <div class="dcf-popup-actions">
            <div class="button-group">
                <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=visual-edit'); ?>" class="button button-primary">
                    <?php _e('Add New Popup', 'dry-cleaning-forms'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=new'); ?>" 
                   class="button button-primary" title="<?php _e('Use Classic Editor', 'dry-cleaning-forms'); ?>">
                    <span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span>
                </a>
            </div>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=templates'); ?>" class="button">
                <?php _e('Browse Templates', 'dry-cleaning-forms'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab-testing'); ?>" class="button">
                <?php _e('A/B Testing', 'dry-cleaning-forms'); ?>
            </a>
        </div>
    </div>

    <!-- Filter tabs -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager'); ?>" 
               class="<?php echo $status_filter === 'all' ? 'current' : ''; ?>">
                <?php _e('All', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['all']; ?>)</span>
            </a> |
        </li>
        <li class="active">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&status=active'); ?>" 
               class="<?php echo $status_filter === 'active' ? 'current' : ''; ?>">
                <?php _e('Active', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['active']; ?>)</span>
            </a> |
        </li>
        <li class="draft">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&status=draft'); ?>" 
               class="<?php echo $status_filter === 'draft' ? 'current' : ''; ?>">
                <?php _e('Draft', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['draft']; ?>)</span>
            </a> |
        </li>
        <li class="paused">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&status=paused'); ?>" 
               class="<?php echo $status_filter === 'paused' ? 'current' : ''; ?>">
                <?php _e('Paused', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['paused']; ?>)</span>
            </a>
        </li>
    </ul>

    <!-- Search and filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="dcf-popup-manager">
                
                <select name="type">
                    <option value="all"><?php _e('All Types', 'dry-cleaning-forms'); ?></option>
                    <option value="modal" <?php selected($type_filter, 'modal'); ?>><?php _e('Modal', 'dry-cleaning-forms'); ?></option>
                    <option value="sidebar" <?php selected($type_filter, 'sidebar'); ?>><?php _e('Sidebar', 'dry-cleaning-forms'); ?></option>
                    <option value="bar" <?php selected($type_filter, 'bar'); ?>><?php _e('Bar', 'dry-cleaning-forms'); ?></option>
                    <option value="multi-step" <?php selected($type_filter, 'multi-step'); ?>><?php _e('Multi-Step', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>
                
                <input type="submit" class="button" value="<?php _e('Filter', 'dry-cleaning-forms'); ?>">
            </form>
        </div>
        
        <div class="alignright actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="dcf-popup-manager">
                <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>
                <?php if ($type_filter !== 'all'): ?>
                    <input type="hidden" name="type" value="<?php echo esc_attr($type_filter); ?>">
                <?php endif; ?>
                
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Search popups...', 'dry-cleaning-forms'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'dry-cleaning-forms'); ?>">
            </form>
        </div>
    </div>

    <!-- Popup table -->
    <form method="post" action="">
        <?php wp_nonce_field('dcf_popup_action', 'dcf_popup_nonce'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value="-1"><?php _e('Bulk Actions', 'dry-cleaning-forms'); ?></option>
                    <option value="activate"><?php _e('Activate', 'dry-cleaning-forms'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'dry-cleaning-forms'); ?></option>
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
                    <th class="manage-column column-trigger">
                        <?php _e('Trigger', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-status">
                        <?php _e('Status', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-stats">
                        <?php _e('Performance', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-date">
                        <?php _e('Created', 'dry-cleaning-forms'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($popups)): ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="7">
                            <?php if ($status_filter !== 'all' || $type_filter !== 'all' || !empty($search)): ?>
                                <?php _e('No popups found matching your criteria.', 'dry-cleaning-forms'); ?>
                                <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager'); ?>">
                                    <?php _e('View all popups', 'dry-cleaning-forms'); ?>
                                </a>
                            <?php else: ?>
                                <?php _e('No popups found.', 'dry-cleaning-forms'); ?>
                                <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=new'); ?>">
                                    <?php _e('Create your first popup', 'dry-cleaning-forms'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($popups as $popup): ?>
                        <?php
                        $edit_url = admin_url('admin.php?page=cmf-popup-manager&action=visual-edit&popup_id=' . $popup['id']);
                        $analytics_url = admin_url('admin.php?page=cmf-popup-manager&action=analytics&popup_id=' . $popup['id']);
                        $preview_url = admin_url('admin.php?page=cmf-popup-manager&action=preview&popup_id=' . $popup['id']);
                        
                        // Get popup stats from analytics
                        $analytics = new DCF_Popup_Conversion_Analytics();
                        $metrics = $analytics->calculate_conversion_metrics($popup['id'], 30);
                        
                        $stats = array(
                            'displays' => $metrics['views'] ?: 0,
                            'conversions' => $metrics['conversions'] ?: 0,
                            'conversion_rate' => $metrics['conversion_rate'] ?: 0
                        );
                        
                        $trigger_settings = $popup['trigger_settings'] ?: array();
                        $trigger_type = $trigger_settings['type'] ?? 'unknown';
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="popup_ids[]" value="<?php echo $popup['id']; ?>">
                            </th>
                            <td class="column-name column-primary">
                                <strong>
                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        <?php echo esc_html($popup['popup_name']); ?>
                                    </a>
                                </strong>
                                <span class="dcf-popup-type-badge">
                                    <?php echo esc_html(ucfirst($popup['popup_type'])); ?>
                                </span>
                                
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url($edit_url); ?>">
                                            <?php _e('Edit', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="analytics">
                                        <a href="<?php echo esc_url($analytics_url); ?>">
                                            <?php _e('Analytics', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="preview">
                                        <a href="<?php echo esc_url($preview_url); ?>" target="_blank">
                                            <?php _e('Preview', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="duplicate">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=duplicate&popup_id=' . $popup['id']), 'duplicate_popup_' . $popup['id']); ?>">
                                            <?php _e('Duplicate', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=delete&popup_id=' . $popup['id']), 'delete_popup_' . $popup['id']); ?>" 
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this popup?', 'dry-cleaning-forms'); ?>')">
                                            <?php _e('Delete', 'dry-cleaning-forms'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-type">
                                <?php
                                $type_labels = array(
                                    'modal' => __('Modal', 'dry-cleaning-forms'),
                                    'sidebar' => __('Sidebar', 'dry-cleaning-forms'),
                                    'bar' => __('Bar', 'dry-cleaning-forms'),
                                    'multi-step' => __('Multi-Step', 'dry-cleaning-forms')
                                );
                                echo esc_html($type_labels[$popup['popup_type']] ?? ucfirst($popup['popup_type']));
                                ?>
                            </td>
                            <td class="column-trigger">
                                <?php
                                $trigger_labels = array(
                                    'exit_intent' => __('Exit Intent', 'dry-cleaning-forms'),
                                    'time_delay' => __('Time Delay', 'dry-cleaning-forms'),
                                    'scroll_percentage' => __('Scroll %', 'dry-cleaning-forms'),
                                    'click_trigger' => __('Click Trigger', 'dry-cleaning-forms'),
                                    'page_views' => __('Page Views', 'dry-cleaning-forms'),
                                    'session_time' => __('Session Time', 'dry-cleaning-forms')
                                );
                                echo esc_html($trigger_labels[$trigger_type] ?? ucfirst(str_replace('_', ' ', $trigger_type)));
                                ?>
                            </td>
                            <td class="column-status">
                                <span class="dcf-status-badge dcf-status-<?php echo esc_attr($popup['status']); ?>">
                                    <?php echo esc_html(ucfirst($popup['status'])); ?>
                                </span>
                            </td>
                            <td class="column-stats">
                                <div class="dcf-popup-stats-mini">
                                    <div><?php echo number_format($stats['displays']); ?> <?php _e('views', 'dry-cleaning-forms'); ?></div>
                                    <div><?php echo number_format($stats['conversions']); ?> <?php _e('conversions', 'dry-cleaning-forms'); ?></div>
                                    <div><?php echo $stats['conversion_rate']; ?>% <?php _e('rate', 'dry-cleaning-forms'); ?></div>
                                </div>
                            </td>
                            <td class="column-date">
                                <?php echo date_i18n(get_option('date_format'), strtotime($popup['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<style>
.dcf-popup-stats-mini {
    font-size: 11px;
    line-height: 1.3;
}

.dcf-popup-stats-mini div {
    margin-bottom: 2px;
}

.column-stats {
    width: 120px;
}

.column-type {
    width: 80px;
}

.column-trigger {
    width: 100px;
}

.column-status {
    width: 80px;
}

.column-date {
    width: 100px;
}

.dcf-popup-type-badge {
    font-size: 10px;
    padding: 2px 6px;
    background: #2271b1;
    color: #fff;
    border-radius: 2px;
    margin-left: 8px;
}

.dcf-status-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: 600;
    text-transform: uppercase;
}

.dcf-status-active {
    background: #00a32a;
    color: #fff;
}

.dcf-status-draft {
    background: #dba617;
    color: #fff;
}

.dcf-status-paused {
    background: #646970;
    color: #fff;
}

@media (max-width: 768px) {
    .column-stats,
    .column-trigger,
    .column-date {
        display: none;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox functionality
    $('#cb-select-all-1').on('change', function() {
        $('input[name="popup_ids[]"]').prop('checked', this.checked);
    });
    
    // Update select all when individual checkboxes change
    $('input[name="popup_ids[]"]').on('change', function() {
        var total = $('input[name="popup_ids[]"]').length;
        var checked = $('input[name="popup_ids[]"]:checked').length;
        $('#cb-select-all-1').prop('checked', total === checked);
    });
});
</script> 