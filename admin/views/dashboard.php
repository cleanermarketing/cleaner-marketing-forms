<?php
/**
 * Admin Dashboard View
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Dry Cleaning Forms Dashboard', 'dry-cleaning-forms'); ?></h1>
    
    <!-- Statistics Cards -->
    <div class="dcf-stats-grid">
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon">
                <span class="dashicons dashicons-forms"></span>
            </div>
            <div class="dcf-stat-content">
                <h3><?php echo number_format($stats['total_submissions']); ?></h3>
                <p><?php _e('Total Submissions', 'dry-cleaning-forms'); ?></p>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dcf-stat-content">
                <h3><?php echo number_format($stats['completed_submissions']); ?></h3>
                <p><?php _e('Completed Signups', 'dry-cleaning-forms'); ?></p>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dcf-stat-content">
                <h3><?php echo number_format($stats['pending_submissions']); ?></h3>
                <p><?php _e('Pending Submissions', 'dry-cleaning-forms'); ?></p>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dcf-stat-content">
                <h3><?php echo number_format($stats['this_month_submissions']); ?></h3>
                <p><?php _e('This Month', 'dry-cleaning-forms'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="dcf-dashboard-content">
        <!-- Recent Submissions -->
        <div class="dcf-dashboard-section">
            <div class="dcf-section-header">
                <h2><?php _e('Recent Submissions', 'dry-cleaning-forms'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=cmf-submissions'); ?>" class="button">
                    <?php _e('View All', 'dry-cleaning-forms'); ?>
                </a>
            </div>
            
            <div class="dcf-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Form Type', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Status', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Step', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Date', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Actions', 'dry-cleaning-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_submissions)): ?>
                            <?php foreach ($recent_submissions as $submission): ?>
                                <?php $user_data = json_decode($submission->user_data, true); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $submission->form_id))); ?></strong>
                                        <?php if (isset($user_data['email'])): ?>
                                            <br><small><?php echo esc_html($user_data['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="dcf-status dcf-status-<?php echo esc_attr(str_replace('_', '-', $submission->status)); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $submission->status))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($submission->form_id === 'customer_signup'): ?>
                                            <?php echo sprintf(__('Step %d of 4', 'dry-cleaning-forms'), $submission->step_completed); ?>
                                        <?php else: ?>
                                            <?php _e('Complete', 'dry-cleaning-forms'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at))); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=cmf-submissions&action=view&id=' . $submission->id); ?>" class="button button-small">
                                            <?php _e('View', 'dry-cleaning-forms'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="dcf-no-data">
                                    <?php _e('No submissions yet.', 'dry-cleaning-forms'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Integration Status -->
        <div class="dcf-dashboard-section">
            <div class="dcf-section-header">
                <h2><?php _e('POS Integration Status', 'dry-cleaning-forms'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=cmf-settings&tab=integrations'); ?>" class="button">
                    <?php _e('Configure', 'dry-cleaning-forms'); ?>
                </a>
            </div>
            
            <?php if (empty($integration_statuses)): ?>
                <div class="dcf-no-integration">
                    <div class="dcf-no-integration-content">
                        <span class="dashicons dashicons-admin-settings" style="font-size: 48px; color: #c3c4c7; margin-bottom: 15px;"></span>
                        <h3><?php _e('No POS System Selected', 'dry-cleaning-forms'); ?></h3>
                        <p><?php _e('Choose your POS system (SMRT, SPOT, or CleanCloud) to enable customer management and order processing.', 'dry-cleaning-forms'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=cmf-settings&tab=integrations'); ?>" class="button button-primary">
                            <?php _e('Select POS System', 'dry-cleaning-forms'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="dcf-integrations-grid">
                    <?php foreach ($integration_statuses as $type => $status): ?>
                        <div class="dcf-integration-card">
                            <div class="dcf-integration-header">
                                <h3><?php echo esc_html($status['name']); ?> <?php _e('POS System', 'dry-cleaning-forms'); ?></h3>
                                <span class="dcf-integration-status dcf-status-<?php echo $status['connected'] ? 'connected' : 'disconnected'; ?>">
                                    <?php if ($status['connected']): ?>
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Connected', 'dry-cleaning-forms'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php _e('Not Connected', 'dry-cleaning-forms'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="dcf-integration-details">
                                <?php if ($status['configured']): ?>
                                    <p class="dcf-configured">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <?php _e('Configured', 'dry-cleaning-forms'); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="dcf-not-configured">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <?php _e('Not Configured', 'dry-cleaning-forms'); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($status['error']): ?>
                                    <p class="dcf-error">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php echo esc_html($status['error']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="dcf-integration-actions">
                                <?php if ($status['configured']): ?>
                                    <button type="button" class="button dcf-test-integration" data-integration="<?php echo esc_attr($type); ?>">
                                        <?php _e('Test Connection', 'dry-cleaning-forms'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo admin_url('admin.php?page=cmf-settings&tab=integrations'); ?>" class="button button-primary">
                                        <?php _e('Configure', 'dry-cleaning-forms'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="dcf-dashboard-section">
            <div class="dcf-section-header">
                <h2><?php _e('Quick Actions', 'dry-cleaning-forms'); ?></h2>
            </div>
            
            <div class="dcf-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=cmf-form-builder&action=new'); ?>" class="dcf-action-card">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <h3><?php _e('Create New Form', 'dry-cleaning-forms'); ?></h3>
                    <p><?php _e('Build a custom form with drag & drop', 'dry-cleaning-forms'); ?></p>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=cmf-analytics'); ?>" class="dcf-action-card">
                    <span class="dashicons dashicons-chart-area"></span>
                    <h3><?php _e('View Analytics', 'dry-cleaning-forms'); ?></h3>
                    <p><?php _e('Track form performance and conversions', 'dry-cleaning-forms'); ?></p>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=cmf-settings'); ?>" class="dcf-action-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h3><?php _e('Plugin Settings', 'dry-cleaning-forms'); ?></h3>
                    <p><?php _e('Configure integrations and options', 'dry-cleaning-forms'); ?></p>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test integration connections
    $('.dcf-test-integration').on('click', function() {
        var button = $(this);
        var integration = button.data('integration');
        var originalText = button.text();
        
        button.text('<?php _e('Testing...', 'dry-cleaning-forms'); ?>').prop('disabled', true);
        
        $.ajax({
            url: dcf_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'dcf_admin_action',
                dcf_action: 'test_integration',
                integration_type: integration,
                nonce: dcf_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('.dcf-integration-card').find('.dcf-integration-status')
                        .removeClass('dcf-status-disconnected')
                        .addClass('dcf-status-connected')
                        .html('<span class="dashicons dashicons-yes-alt"></span> <?php _e('Connected', 'dry-cleaning-forms'); ?>');
                } else {
                    alert(response.data.message || '<?php _e('Connection test failed', 'dry-cleaning-forms'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred while testing the connection', 'dry-cleaning-forms'); ?>');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script> 