<?php
/**
 * Submissions View
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$submissions_table = $wpdb->prefix . 'dcf_submissions';
$forms_table = $wpdb->prefix . 'dcf_forms';

// Get filter parameters
$form_filter = isset($_GET['form_filter']) ? sanitize_text_field($_GET['form_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Build query
$where_conditions = array('1=1');
$query_params = array();

if (!empty($form_filter)) {
    $where_conditions[] = 's.form_id = %s';
    $query_params[] = $form_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = 's.status = %s';
    $query_params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = 'DATE(s.created_at) >= %s';
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = 'DATE(s.created_at) <= %s';
    $query_params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Get submissions with pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$query = "
    SELECT s.*, 
           CASE 
               WHEN s.form_id REGEXP '^[0-9]+$' THEN COALESCE(f.form_name, CONCAT('Form #', s.form_id))
               ELSE s.form_id
           END as form_name,
           CASE 
               WHEN s.form_id REGEXP '^[0-9]+$' THEN COALESCE(f.form_type, 'form_builder')
               ELSE s.form_id
           END as form_type
    FROM $submissions_table s 
    LEFT JOIN $forms_table f ON CAST(s.form_id AS UNSIGNED) = f.id
    WHERE $where_clause 
    ORDER BY s.created_at DESC 
    LIMIT %d OFFSET %d
";

$query_params[] = $per_page;
$query_params[] = $offset;

// Handle the case where there are no filter parameters (only LIMIT/OFFSET)
if (count($query_params) == 2) {
    $submissions = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
} else {
    $submissions = $wpdb->get_results($wpdb->prepare($query, $query_params));
}

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) 
    FROM $submissions_table s 
    LEFT JOIN $forms_table f ON CAST(s.form_id AS UNSIGNED) = f.id
    WHERE $where_clause
";

// Handle count query parameters (exclude LIMIT/OFFSET)
$count_params = array_slice($query_params, 0, -2);
if (empty($count_params)) {
    $total_items = $wpdb->get_var($count_query);
} else {
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, $count_params));
}
$total_pages = ceil($total_items / $per_page);

// Get all forms for filter dropdown
$forms = $wpdb->get_results("SELECT DISTINCT form_name, form_type FROM $forms_table ORDER BY form_name");
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Form Submissions', 'dry-cleaning-forms'); ?></h1>
    
    <hr class="wp-header-end">
    
    <!-- Filters -->
    <div class="dcf-filters">
        <form method="get" class="dcf-filter-form">
            <input type="hidden" name="page" value="dcf-submissions">
            
            <div class="dcf-filter-row">
                <div class="dcf-filter-group">
                    <label for="form_filter"><?php _e('Form:', 'dry-cleaning-forms'); ?></label>
                    <select name="form_filter" id="form_filter">
                        <option value=""><?php _e('All Forms', 'dry-cleaning-forms'); ?></option>
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo esc_attr($form->form_name); ?>" <?php selected($form_filter, $form->form_name); ?>>
                                <?php echo esc_html($form->form_name . ' (' . ucwords(str_replace('_', ' ', $form->form_type)) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="dcf-filter-group">
                    <label for="status_filter"><?php _e('Status:', 'dry-cleaning-forms'); ?></label>
                    <select name="status_filter" id="status_filter">
                        <option value=""><?php _e('All Statuses', 'dry-cleaning-forms'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'dry-cleaning-forms'); ?></option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'dry-cleaning-forms'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'dry-cleaning-forms'); ?></option>
                        <option value="abandoned" <?php selected($status_filter, 'abandoned'); ?>><?php _e('Abandoned', 'dry-cleaning-forms'); ?></option>
                    </select>
                </div>
                
                <div class="dcf-filter-group">
                    <label for="date_from"><?php _e('From:', 'dry-cleaning-forms'); ?></label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="dcf-filter-group">
                    <label for="date_to"><?php _e('To:', 'dry-cleaning-forms'); ?></label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <div class="dcf-filter-group">
                    <button type="submit" class="button"><?php _e('Filter', 'dry-cleaning-forms'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=cmf-submissions'); ?>" class="button"><?php _e('Clear', 'dry-cleaning-forms'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Bulk Actions -->
    <div class="dcf-bulk-actions">
        <form method="post" id="dcf-bulk-form">
            <?php wp_nonce_field('dcf_bulk_action', 'dcf_bulk_nonce'); ?>
            <div class="dcf-bulk-controls">
                <select name="bulk_action" id="bulk_action">
                    <option value=""><?php _e('Bulk Actions', 'dry-cleaning-forms'); ?></option>
                    <option value="mark_completed"><?php _e('Mark as Completed', 'dry-cleaning-forms'); ?></option>
                    <option value="mark_pending"><?php _e('Mark as Pending', 'dry-cleaning-forms'); ?></option>
                    <option value="export_csv"><?php _e('Export to CSV', 'dry-cleaning-forms'); ?></option>
                    <option value="delete"><?php _e('Delete', 'dry-cleaning-forms'); ?></option>
                </select>
                <button type="submit" class="button" id="bulk_apply"><?php _e('Apply', 'dry-cleaning-forms'); ?></button>
            </div>
        </form>
    </div>
    
    <!-- Results Summary -->
    <div class="dcf-results-summary">
        <p><?php echo sprintf(_n('%d submission found', '%d submissions found', $total_items, 'dry-cleaning-forms'), number_format($total_items)); ?></p>
    </div>
    
    <!-- Submissions Table -->
    <?php if (empty($submissions)): ?>
        <div class="dcf-no-submissions">
            <div class="dcf-no-submissions-content">
                <span class="dashicons dashicons-clipboard" style="font-size: 64px; color: #c3c4c7; margin-bottom: 20px;"></span>
                <h2><?php _e('No submissions found', 'dry-cleaning-forms'); ?></h2>
                <p><?php _e('No form submissions match your current filters.', 'dry-cleaning-forms'); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="dcf-submissions-table-container">
            <table class="wp-list-table widefat fixed striped dcf-submissions-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th class="manage-column column-primary"><?php _e('Submission', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Form', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Step', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Date', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Actions', 'dry-cleaning-forms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <?php 
                        $user_data = json_decode($submission->user_data, true);
                        $customer_name = '';
                        $customer_email = '';
                        
                        if (!empty($user_data)) {
                            $customer_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
                            $customer_email = $user_data['email'] ?? '';
                        }
                        
                        $status_class = 'dcf-status-' . $submission->status;
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="submission_ids[]" value="<?php echo $submission->id; ?>" form="dcf-bulk-form">
                            </th>
                            <td class="column-primary">
                                <strong>
                                    <?php if (!empty($customer_name)): ?>
                                        <?php echo esc_html($customer_name); ?>
                                    <?php else: ?>
                                        <?php echo sprintf(__('Submission #%d', 'dry-cleaning-forms'), $submission->id); ?>
                                    <?php endif; ?>
                                </strong>
                                <?php if (!empty($customer_email)): ?>
                                    <br><small><?php echo esc_html($customer_email); ?></small>
                                <?php endif; ?>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details', 'dry-cleaning-forms'); ?></span></button>
                            </td>
                            <td data-colname="<?php _e('Form', 'dry-cleaning-forms'); ?>">
                                <?php 
                                // For form builder forms (numeric IDs), use the actual form name from database
                                if (is_numeric($submission->form_id)) {
                                    $display_form_name = $submission->form_name && $submission->form_name !== 'form_builder' ? $submission->form_name : 'Form #' . $submission->form_id;
                                    $display_form_type = 'Form Builder';
                                } else {
                                    $display_form_name = $submission->form_name ?: $submission->form_id;
                                    $display_form_type = $submission->form_type ?: 'unknown';
                                }
                                ?>
                                <strong><?php echo esc_html($display_form_name); ?></strong>
                                <br><small><?php echo esc_html(ucwords(str_replace('_', ' ', $display_form_type))); ?></small>
                            </td>
                            <td data-colname="<?php _e('Status', 'dry-cleaning-forms'); ?>">
                                <?php 
                                // Calculate completion percentage
                                $completion = 0;
                                if ($submission->status === 'completed') {
                                    $completion = 100;
                                } elseif (preg_match('/STEP_(\d+)_COMPLETED/i', $submission->status, $matches)) {
                                    $step = intval($matches[1]);
                                    $total_steps = 3; // Default to 3 steps
                                    $completion = round(($step / $total_steps) * 100);
                                } elseif ($submission->step_completed > 0) {
                                    $total_steps = 3; // Default to 3 steps
                                    $completion = round(($submission->step_completed / $total_steps) * 100);
                                }
                                
                                // Determine progress bar color based on status
                                $progress_class = 'dcf-progress-pending';
                                if ($submission->status === 'completed') {
                                    $progress_class = 'dcf-progress-completed';
                                } elseif ($submission->status === 'failed') {
                                    $progress_class = 'dcf-progress-failed';
                                } elseif ($submission->status === 'abandoned') {
                                    $progress_class = 'dcf-progress-abandoned';
                                }
                                ?>
                                <div class="dcf-progress-container">
                                    <div class="dcf-progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $completion; ?>%"></div>
                                    <span class="dcf-progress-text"><?php echo $completion; ?>%</span>
                                </div>
                            </td>
                            <td data-colname="<?php _e('Step', 'dry-cleaning-forms'); ?>">
                                <?php 
                                // Only show steps for multi-step forms (signup forms)
                                if ($submission->form_id === 'customer_signup' || strpos($submission->form_id, 'signup') !== false) {
                                    echo sprintf(__('Step %d', 'dry-cleaning-forms'), $submission->step_completed);
                                } else {
                                    echo __('N/A', 'dry-cleaning-forms');
                                }
                                ?>
                            </td>
                            <td data-colname="<?php _e('Date', 'dry-cleaning-forms'); ?>">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at)); ?>
                            </td>
                            <td data-colname="<?php _e('Actions', 'dry-cleaning-forms'); ?>">
                                <button type="button" class="button button-small dcf-view-submission" data-submission-id="<?php echo $submission->id; ?>">
                                    <?php _e('View', 'dry-cleaning-forms'); ?>
                                </button>
                                <button type="button" class="button button-small dcf-delete-submission" data-submission-id="<?php echo $submission->id; ?>">
                                    <?php _e('Delete', 'dry-cleaning-forms'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="dcf-pagination">
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'plain'
                );
                echo paginate_links($pagination_args);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Submission Details Modal -->
<div id="dcf-submission-modal" class="dcf-modal" style="display: none;">
    <div class="dcf-modal-content dcf-modal-large">
        <div class="dcf-modal-header">
            <h3 id="dcf-submission-modal-title"><?php _e('Submission Details', 'dry-cleaning-forms'); ?></h3>
            <button type="button" class="dcf-modal-close">&times;</button>
        </div>
        <div class="dcf-modal-body" id="dcf-submission-details">
            <!-- Content loaded via AJAX -->
        </div>
        <div class="dcf-modal-footer">
            <button type="button" class="button" id="dcf-close-submission-modal"><?php _e('Close', 'dry-cleaning-forms'); ?></button>
        </div>
    </div>
</div>

<style>
.dcf-filters {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.dcf-filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.dcf-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dcf-filter-group label {
    font-weight: 600;
    font-size: 13px;
}

.dcf-filter-group select,
.dcf-filter-group input {
    padding: 6px 8px;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    font-size: 13px;
}

.dcf-bulk-actions {
    margin: 15px 0;
}

.dcf-bulk-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.dcf-results-summary {
    margin: 10px 0;
    color: #646970;
    font-size: 14px;
}

.dcf-no-submissions {
    text-align: center;
    padding: 60px 20px;
}

.dcf-no-submissions-content h2 {
    color: #646970;
    font-size: 24px;
    margin-bottom: 10px;
}

.dcf-no-submissions-content p {
    color: #646970;
    font-size: 16px;
    margin-bottom: 0;
}

.dcf-submissions-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow-x: auto;
}

.dcf-submissions-table {
    margin: 0;
    border: none;
}

.dcf-submissions-table th,
.dcf-submissions-table td {
    padding: 12px 8px;
}

.dcf-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dcf-status-pending {
    background: #f0f6fc;
    color: #0073aa;
    border: 1px solid #c3c4c7;
}

.dcf-status-completed {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #a3cfbb;
}

.dcf-status-failed {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f1aeb5;
}

.dcf-status-abandoned {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.dcf-pagination {
    margin: 20px 0;
    text-align: center;
}

.dcf-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    color: #0073aa;
}

.dcf-pagination .page-numbers:hover,
.dcf-pagination .page-numbers.current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.dcf-modal-large .dcf-modal-content {
    max-width: 800px;
}

.dcf-progress-container {
    position: relative;
    background: #f0f0f0;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    border: 1px solid #ddd;
}

.dcf-progress-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
    border-radius: 10px;
}

.dcf-progress-completed {
    background: #46b450;
}

.dcf-progress-failed {
    background: #dc3232;
}

.dcf-progress-abandoned {
    background: #f0b849;
}

.dcf-progress-pending {
    background: #0073aa;
}

.dcf-progress-text {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: 600;
    color: #333;
    z-index: 1;
}

@media (max-width: 768px) {
    .dcf-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .dcf-filter-group {
        width: 100%;
    }
    
    .dcf-bulk-controls {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="submission_ids[]"]').prop('checked', this.checked);
    });
    
    // Update select all when individual checkboxes change
    $('input[name="submission_ids[]"]').on('change', function() {
        var total = $('input[name="submission_ids[]"]').length;
        var checked = $('input[name="submission_ids[]"]:checked').length;
        $('#cb-select-all').prop('checked', total === checked);
    });
    
    // Bulk actions
    $('#dcf-bulk-form').on('submit', function(e) {
        e.preventDefault();
        
        var action = $('#bulk_action').val();
        var selectedIds = $('input[name="submission_ids[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (!action) {
            alert(dcf_admin.messages.select_action);
            return;
        }
        
        if (selectedIds.length === 0) {
            alert(dcf_admin.messages.select_items);
            return;
        }
        
        if (action === 'delete' && !confirm(dcf_admin.messages.confirm_delete)) {
            return;
        }
        
        var button = $('#bulk_apply');
        var originalText = button.text();
        button.prop('disabled', true).text(dcf_admin.messages.processing);
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'bulk_submissions',
                bulk_action: action,
                submission_ids: selectedIds,
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (action === 'export_csv') {
                        // Handle CSV download
                        var blob = new Blob([response.data.csv], { type: 'text/csv' });
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'submissions-' + new Date().toISOString().split('T')[0] + '.csv';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.data.message || dcf_admin.messages.error);
                }
            },
            error: function() {
                alert(dcf_admin.messages.error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // View submission details
    $('.dcf-view-submission').on('click', function() {
        var submissionId = $(this).data('submission-id');
        
        $('#dcf-submission-details').html('<div class="dcf-loading">Loading...</div>');
        $('#dcf-submission-modal').show();
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'get_submission_details',
                submission_id: submissionId,
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#dcf-submission-details').html(response.data.html);
                } else {
                    $('#dcf-submission-details').html('<p>Error loading submission details.</p>');
                }
            },
            error: function() {
                $('#dcf-submission-details').html('<p>Error loading submission details.</p>');
            }
        });
    });
    
    // Delete single submission
    $('.dcf-delete-submission').on('click', function() {
        if (!confirm(dcf_admin.messages.confirm_delete)) {
            return;
        }
        
        var submissionId = $(this).data('submission-id');
        var row = $(this).closest('tr');
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text(dcf_admin.messages.processing);
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'delete_submission',
                submission_id: submissionId,
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                        
                        // Check if no submissions left
                        if ($('.dcf-submissions-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || dcf_admin.messages.error);
                }
            },
            error: function() {
                alert(dcf_admin.messages.error);
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Close submission modal
    $('.dcf-modal-close, #dcf-close-submission-modal').on('click', function() {
        $('#dcf-submission-modal').hide();
    });
    
    // Close modal when clicking outside
    $('#dcf-submission-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script> 