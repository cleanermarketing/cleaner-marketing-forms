<?php
/**
 * A/B Testing List View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize A/B testing manager
$ab_testing_manager = new DCF_AB_Testing_Manager();

// Handle bulk actions
if (isset($_POST['bulk_action']) && $_POST['bulk_action'] !== '-1' && !empty($_POST['test_ids'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk-ab-tests')) {
        wp_die(__('Security check failed.', 'dry-cleaning-forms'));
    }
    
    $test_ids = array_map('intval', $_POST['test_ids']);
    $bulk_action = $_POST['bulk_action'];
    
    switch ($bulk_action) {
        case 'activate':
            foreach ($test_ids as $test_id) {
                $ab_testing_manager->update_ab_test($test_id, array('status' => 'active'));
            }
            $success_message = sprintf(__('%d tests activated.', 'dry-cleaning-forms'), count($test_ids));
            break;
            
        case 'pause':
            foreach ($test_ids as $test_id) {
                $ab_testing_manager->update_ab_test($test_id, array('status' => 'paused'));
            }
            $success_message = sprintf(__('%d tests paused.', 'dry-cleaning-forms'), count($test_ids));
            break;
            
        case 'complete':
            foreach ($test_ids as $test_id) {
                $ab_testing_manager->complete_test($test_id);
            }
            $success_message = sprintf(__('%d tests completed.', 'dry-cleaning-forms'), count($test_ids));
            break;
            
        case 'delete':
            foreach ($test_ids as $test_id) {
                $ab_testing_manager->delete_ab_test($test_id);
            }
            $success_message = sprintf(__('%d tests deleted.', 'dry-cleaning-forms'), count($test_ids));
            break;
    }
    
    if (isset($success_message)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($success_message) . '</p></div>';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['s'] ?? '';

// Get tests
$args = array(
    'limit' => 20,
    'offset' => 0
);

if (!empty($status_filter)) {
    $args['status'] = $status_filter;
}

$tests = $ab_testing_manager->get_ab_tests($args);

// Get status counts for filter tabs
$status_counts = array(
    'all' => count($ab_testing_manager->get_ab_tests()),
    'draft' => count($ab_testing_manager->get_ab_tests(array('status' => 'draft'))),
    'active' => count($ab_testing_manager->get_ab_tests(array('status' => 'active'))),
    'paused' => count($ab_testing_manager->get_ab_tests(array('status' => 'paused'))),
    'completed' => count($ab_testing_manager->get_ab_tests(array('status' => 'completed')))
);

?>

<div class="dcf-ab-testing-list">
    <div class="dcf-list-header">
        <div class="dcf-list-title">
            <h2><?php _e('A/B Tests', 'dry-cleaning-forms'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_new'); ?>" 
               class="button button-primary">
                <?php _e('Create New Test', 'dry-cleaning-forms'); ?>
            </a>
        </div>
        
        <!-- Status Filter Tabs -->
        <div class="dcf-filter-tabs">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_tests'); ?>" 
               class="<?php echo empty($status_filter) ? 'current' : ''; ?>">
                <?php _e('All', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['all']; ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_tests&status=draft'); ?>" 
               class="<?php echo $status_filter === 'draft' ? 'current' : ''; ?>">
                <?php _e('Draft', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['draft']; ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_tests&status=active'); ?>" 
               class="<?php echo $status_filter === 'active' ? 'current' : ''; ?>">
                <?php _e('Active', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['active']; ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_tests&status=paused'); ?>" 
               class="<?php echo $status_filter === 'paused' ? 'current' : ''; ?>">
                <?php _e('Paused', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['paused']; ?>)</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_tests&status=completed'); ?>" 
               class="<?php echo $status_filter === 'completed' ? 'current' : ''; ?>">
                <?php _e('Completed', 'dry-cleaning-forms'); ?> 
                <span class="count">(<?php echo $status_counts['completed']; ?>)</span>
            </a>
        </div>
    </div>
    
    <!-- Search and Bulk Actions -->
    <div class="dcf-list-controls">
        <form method="get" class="dcf-search-form">
            <input type="hidden" name="page" value="dcf-popup-manager">
            <input type="hidden" name="action" value="ab_tests">
            <?php if (!empty($status_filter)): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
            <?php endif; ?>
            
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('Search tests...', 'dry-cleaning-forms'); ?>">
            <button type="submit" class="button"><?php _e('Search', 'dry-cleaning-forms'); ?></button>
        </form>
    </div>
    
    <!-- Tests Table -->
    <form method="post" id="ab-tests-form">
        <?php wp_nonce_field('bulk-ab-tests'); ?>
        
        <div class="dcf-bulk-actions">
            <select name="bulk_action">
                <option value="-1"><?php _e('Bulk Actions', 'dry-cleaning-forms'); ?></option>
                <option value="activate"><?php _e('Activate', 'dry-cleaning-forms'); ?></option>
                <option value="pause"><?php _e('Pause', 'dry-cleaning-forms'); ?></option>
                <option value="complete"><?php _e('Complete', 'dry-cleaning-forms'); ?></option>
                <option value="delete"><?php _e('Delete', 'dry-cleaning-forms'); ?></option>
            </select>
            <button type="submit" class="button"><?php _e('Apply', 'dry-cleaning-forms'); ?></button>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </td>
                    <th class="manage-column column-name">
                        <?php _e('Test Name', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-variants">
                        <?php _e('Variants', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-status">
                        <?php _e('Status', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-performance">
                        <?php _e('Performance', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-winner">
                        <?php _e('Winner', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-dates">
                        <?php _e('Dates', 'dry-cleaning-forms'); ?>
                    </th>
                    <th class="manage-column column-actions">
                        <?php _e('Actions', 'dry-cleaning-forms'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tests)): ?>
                    <tr>
                        <td colspan="8" class="dcf-no-items">
                            <?php _e('No A/B tests found.', 'dry-cleaning-forms'); ?>
                            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_new'); ?>">
                                <?php _e('Create your first test', 'dry-cleaning-forms'); ?>
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tests as $test): ?>
                        <?php
                        $variants = $ab_testing_manager->get_test_variants_performance($test['id']);
                        $best_variant = null;
                        $best_rate = 0;
                        
                        foreach ($variants as $variant) {
                            if ($variant['conversion_rate'] > $best_rate) {
                                $best_rate = $variant['conversion_rate'];
                                $best_variant = $variant;
                            }
                        }
                        
                        $winner_name = '';
                        if ($test['winner_id']) {
                            foreach ($variants as $variant) {
                                if ($variant['popup_id'] == $test['winner_id']) {
                                    $winner_name = $variant['popup_name'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="test_ids[]" value="<?php echo $test['id']; ?>">
                            </th>
                            <td class="column-name">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_edit&test_id=' . $test['id']); ?>">
                                        <?php echo esc_html($test['test_name']); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_edit&test_id=' . $test['id']); ?>">
                                            <?php _e('Edit', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_analytics&test_id=' . $test['id']); ?>">
                                            <?php _e('Analytics', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="duplicate">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=ab_test_duplicate&test_id=' . $test['id']), 'duplicate_test_' . $test['id']); ?>">
                                            <?php _e('Duplicate', 'dry-cleaning-forms'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=ab_test_delete&test_id=' . $test['id']), 'delete_test_' . $test['id']); ?>" 
                                           onclick="return confirm('<?php _e('Are you sure you want to delete this test?', 'dry-cleaning-forms'); ?>')">
                                            <?php _e('Delete', 'dry-cleaning-forms'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-variants">
                                <div class="dcf-variants-summary">
                                    <?php foreach ($variants as $index => $variant): ?>
                                        <div class="dcf-variant-item">
                                            <span class="dcf-variant-name"><?php echo esc_html($variant['popup_name']); ?></span>
                                            <span class="dcf-variant-split"><?php echo $variant['traffic_split']; ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="column-status">
                                <span class="dcf-status dcf-status-<?php echo esc_attr($test['status']); ?>">
                                    <?php echo ucfirst($test['status']); ?>
                                </span>
                            </td>
                            <td class="column-performance">
                                <?php if (!empty($variants)): ?>
                                    <div class="dcf-performance-summary">
                                        <div class="dcf-metric">
                                            <span class="dcf-metric-label"><?php _e('Total Displays:', 'dry-cleaning-forms'); ?></span>
                                            <span class="dcf-metric-value">
                                                <?php echo number_format(array_sum(array_column($variants, 'displays'))); ?>
                                            </span>
                                        </div>
                                        <div class="dcf-metric">
                                            <span class="dcf-metric-label"><?php _e('Best Rate:', 'dry-cleaning-forms'); ?></span>
                                            <span class="dcf-metric-value">
                                                <?php echo $best_rate; ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="dcf-no-data"><?php _e('No data yet', 'dry-cleaning-forms'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-winner">
                                <?php if ($test['winner_id']): ?>
                                    <span class="dcf-winner">
                                        <span class="dashicons dashicons-awards"></span>
                                        <?php echo esc_html($winner_name); ?>
                                    </span>
                                <?php elseif ($test['status'] === 'active' && $best_variant): ?>
                                    <span class="dcf-leading">
                                        <span class="dashicons dashicons-chart-line"></span>
                                        <?php echo esc_html($best_variant['popup_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="dcf-no-winner">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-dates">
                                <div class="dcf-dates">
                                    <div class="dcf-date-item">
                                        <span class="dcf-date-label"><?php _e('Started:', 'dry-cleaning-forms'); ?></span>
                                        <span class="dcf-date-value">
                                            <?php echo $test['start_date'] ? date('M j, Y', strtotime($test['start_date'])) : '—'; ?>
                                        </span>
                                    </div>
                                    <?php if ($test['end_date']): ?>
                                        <div class="dcf-date-item">
                                            <span class="dcf-date-label"><?php _e('Ends:', 'dry-cleaning-forms'); ?></span>
                                            <span class="dcf-date-value">
                                                <?php echo date('M j, Y', strtotime($test['end_date'])); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-actions">
                                <div class="dcf-action-buttons">
                                    <?php if ($test['status'] === 'draft'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=ab_test_activate&test_id=' . $test['id']), 'activate_test_' . $test['id']); ?>" 
                                           class="button button-small">
                                            <?php _e('Start', 'dry-cleaning-forms'); ?>
                                        </a>
                                    <?php elseif ($test['status'] === 'active'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=ab_test_pause&test_id=' . $test['id']), 'pause_test_' . $test['id']); ?>" 
                                           class="button button-small">
                                            <?php _e('Pause', 'dry-cleaning-forms'); ?>
                                        </a>
                                        <?php if (!$test['winner_id'] && !empty($variants)): ?>
                                            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_declare_winner&test_id=' . $test['id']); ?>" 
                                               class="button button-small button-primary">
                                                <?php _e('Declare Winner', 'dry-cleaning-forms'); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif ($test['status'] === 'paused'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cmf-popup-manager&action=ab_test_resume&test_id=' . $test['id']), 'resume_test_' . $test['id']); ?>" 
                                           class="button button-small">
                                            <?php _e('Resume', 'dry-cleaning-forms'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Select all checkbox functionality
    $('#cb-select-all').on('change', function() {
        $('input[name="test_ids[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Individual checkbox change
    $('input[name="test_ids[]"]').on('change', function() {
        var allChecked = $('input[name="test_ids[]"]:checked').length === $('input[name="test_ids[]"]').length;
        $('#cb-select-all').prop('checked', allChecked);
    });
});
</script>

<style>
.dcf-ab-testing-list {
    max-width: 1200px;
}

.dcf-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dcf-list-title {
    display: flex;
    align-items: center;
    gap: 20px;
}

.dcf-list-title h2 {
    margin: 0;
}

.dcf-filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.dcf-filter-tabs a {
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    background: #f6f7f7;
}

.dcf-filter-tabs a.current {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.dcf-filter-tabs .count {
    font-size: 12px;
    opacity: 0.8;
}

.dcf-list-controls {
    margin-bottom: 20px;
}

.dcf-search-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.dcf-search-form input[type="search"] {
    width: 300px;
}

.dcf-bulk-actions {
    margin-bottom: 10px;
}

.dcf-bulk-actions select {
    margin-right: 10px;
}

.dcf-no-items {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.dcf-variants-summary {
    font-size: 12px;
}

.dcf-variant-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.dcf-variant-split {
    font-weight: 600;
    color: #2271b1;
}

.dcf-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.dcf-status-draft {
    background: #f0f0f1;
    color: #646970;
}

.dcf-status-active {
    background: #d1e7dd;
    color: #0f5132;
}

.dcf-status-paused {
    background: #fff3cd;
    color: #664d03;
}

.dcf-status-completed {
    background: #cff4fc;
    color: #055160;
}

.dcf-performance-summary {
    font-size: 12px;
}

.dcf-metric {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.dcf-metric-label {
    color: #646970;
}

.dcf-metric-value {
    font-weight: 600;
}

.dcf-winner {
    color: #00a32a;
    font-weight: 600;
}

.dcf-leading {
    color: #2271b1;
    font-weight: 600;
}

.dcf-no-winner {
    color: #646970;
}

.dcf-dates {
    font-size: 12px;
}

.dcf-date-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}

.dcf-date-label {
    color: #646970;
}

.dcf-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dcf-action-buttons .button {
    font-size: 11px;
    padding: 3px 8px;
    height: auto;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .dcf-list-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .dcf-filter-tabs {
        flex-wrap: wrap;
    }
    
    .dcf-search-form input[type="search"] {
        width: 200px;
    }
    
    .wp-list-table {
        font-size: 12px;
    }
    
    .dcf-variants-summary,
    .dcf-performance-summary,
    .dcf-dates {
        font-size: 11px;
    }
}
</style> 