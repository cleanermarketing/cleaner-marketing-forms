<?php
/**
 * Analytics View
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

// Get filters
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
$custom_from = isset($_GET['custom_from']) ? sanitize_text_field($_GET['custom_from']) : '';
$custom_to = isset($_GET['custom_to']) ? sanitize_text_field($_GET['custom_to']) : '';
$form_id_filter = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// Get form details if filtering by form
$filtered_form = null;
$form_display_name = '';
if ($form_id_filter) {
    $form_builder = new DCF_Form_Builder();
    $filtered_form = $form_builder->get_form($form_id_filter);
    if ($filtered_form) {
        $form_config = is_array($filtered_form->form_config) ? $filtered_form->form_config : json_decode($filtered_form->form_config, true);
        $form_display_name = !empty($form_config['title']) ? $form_config['title'] : $filtered_form->form_name;
    }
}

// Calculate date range
$end_date = current_time('Y-m-d');
switch ($date_range) {
    case '7':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'custom':
        $start_date = $custom_from ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $custom_to ?: current_time('Y-m-d');
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-30 days'));
}

// Build WHERE clause for form filtering
$form_where = $form_id_filter ? $wpdb->prepare(" AND form_id = %s", $form_id_filter) : "";

// Get overall statistics
$total_submissions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $submissions_table WHERE DATE(created_at) BETWEEN %s AND %s" . $form_where,
    $start_date, $end_date
));

$completed_submissions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $submissions_table WHERE status = 'completed' AND DATE(created_at) BETWEEN %s AND %s" . $form_where,
    $start_date, $end_date
));

$conversion_rate = $total_submissions > 0 ? round(($completed_submissions / $total_submissions) * 100, 1) : 0;

$abandoned_submissions = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $submissions_table WHERE status = 'abandoned' AND DATE(created_at) BETWEEN %s AND %s" . $form_where,
    $start_date, $end_date
));

// Get submissions by date for chart
$daily_submissions = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as date, COUNT(*) as total,
     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
     FROM $submissions_table 
     WHERE DATE(created_at) BETWEEN %s AND %s" . $form_where . "
     GROUP BY DATE(created_at)
     ORDER BY date ASC",
    $start_date, $end_date
));

// Get submissions by form type (only show if not filtering by specific form)
$form_type_stats = array();
if (!$form_id_filter) {
    $form_type_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT f.form_type, COUNT(s.id) as total,
         SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed
         FROM $submissions_table s
         LEFT JOIN $forms_table f ON s.form_id = f.id
         WHERE DATE(s.created_at) BETWEEN %s AND %s
         GROUP BY f.form_type
         ORDER BY total DESC",
        $start_date, $end_date
    ));
}

// Get step abandonment data
$step_abandonment = $wpdb->get_results($wpdb->prepare(
    "SELECT step_completed, COUNT(*) as count
     FROM $submissions_table 
     WHERE DATE(created_at) BETWEEN %s AND %s AND status = 'abandoned'" . $form_where . "
     GROUP BY step_completed
     ORDER BY step_completed ASC",
    $start_date, $end_date
));

// Get top performing forms (or single form stats if filtering)
if ($form_id_filter) {
    // For single form, just get its stats
    $top_forms = $wpdb->get_results($wpdb->prepare(
        "SELECT s.form_id, f.form_name, f.form_type, f.form_config, COUNT(s.id) as total,
         SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed,
         ROUND((SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) / COUNT(s.id)) * 100, 1) as conversion_rate
         FROM $submissions_table s
         LEFT JOIN $forms_table f ON s.form_id = f.id
         WHERE DATE(s.created_at) BETWEEN %s AND %s AND s.form_id = %s
         GROUP BY s.form_id, f.form_name, f.form_type, f.form_config",
        $start_date, $end_date, $form_id_filter
    ));
} else {
    $top_forms = $wpdb->get_results($wpdb->prepare(
        "SELECT s.form_id, f.form_name, f.form_type, f.form_config, COUNT(s.id) as total,
         SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed,
         ROUND((SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) / COUNT(s.id)) * 100, 1) as conversion_rate
         FROM $submissions_table s
         LEFT JOIN $forms_table f ON s.form_id = f.id
         WHERE DATE(s.created_at) BETWEEN %s AND %s
         GROUP BY s.form_id, f.form_name, f.form_type, f.form_config
         HAVING total > 0
         ORDER BY conversion_rate DESC, total DESC
         LIMIT 10",
        $start_date, $end_date
    ));
}

// Prepare chart data
$chart_labels = array();
$chart_total = array();
$chart_completed = array();

foreach ($daily_submissions as $day) {
    $chart_labels[] = date('M j', strtotime($day->date));
    $chart_total[] = (int) $day->total;
    $chart_completed[] = (int) $day->completed;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Analytics', 'dry-cleaning-forms'); ?>
        <?php if ($form_id_filter && $form_display_name): ?>
            - <?php echo esc_html($form_display_name); ?>
        <?php endif; ?>
    </h1>
    
    <?php if ($form_id_filter): ?>
        <a href="<?php echo admin_url('admin.php?page=cmf-analytics'); ?>" class="page-title-action">
            <?php _e('View All Forms', 'dry-cleaning-forms'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <!-- Date Range Filter -->
    <div class="dcf-analytics-filters">
        <form method="get" class="dcf-filter-form">
            <input type="hidden" name="page" value="dcf-analytics">
            <?php if ($form_id_filter): ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id_filter); ?>">
            <?php endif; ?>
            
            <div class="dcf-filter-row">
                <div class="dcf-filter-group">
                    <label for="date_range"><?php _e('Date Range:', 'dry-cleaning-forms'); ?></label>
                    <select name="date_range" id="date_range">
                        <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'dry-cleaning-forms'); ?></option>
                        <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'dry-cleaning-forms'); ?></option>
                        <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'dry-cleaning-forms'); ?></option>
                        <option value="custom" <?php selected($date_range, 'custom'); ?>><?php _e('Custom Range', 'dry-cleaning-forms'); ?></option>
                    </select>
                </div>
                
                <div class="dcf-filter-group dcf-custom-dates" style="<?php echo $date_range !== 'custom' ? 'display: none;' : ''; ?>">
                    <label for="custom_from"><?php _e('From:', 'dry-cleaning-forms'); ?></label>
                    <input type="date" name="custom_from" id="custom_from" value="<?php echo esc_attr($custom_from); ?>">
                </div>
                
                <div class="dcf-filter-group dcf-custom-dates" style="<?php echo $date_range !== 'custom' ? 'display: none;' : ''; ?>">
                    <label for="custom_to"><?php _e('To:', 'dry-cleaning-forms'); ?></label>
                    <input type="date" name="custom_to" id="custom_to" value="<?php echo esc_attr($custom_to); ?>">
                </div>
                
                <div class="dcf-filter-group">
                    <button type="submit" class="button button-primary"><?php _e('Update', 'dry-cleaning-forms'); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Overview Stats -->
    <div class="dcf-stats-grid">
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon">
                <span class="dashicons dashicons-forms"></span>
            </div>
            <div class="dcf-stat-content">
                <div class="dcf-stat-number"><?php echo number_format($total_submissions); ?></div>
                <div class="dcf-stat-label"><?php _e('Total Submissions', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon dcf-stat-success">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dcf-stat-content">
                <div class="dcf-stat-number"><?php echo number_format($completed_submissions); ?></div>
                <div class="dcf-stat-label"><?php _e('Completed', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon dcf-stat-warning">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="dcf-stat-content">
                <div class="dcf-stat-number"><?php echo number_format($abandoned_submissions); ?></div>
                <div class="dcf-stat-label"><?php _e('Abandoned', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-stat-card">
            <div class="dcf-stat-icon dcf-stat-info">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dcf-stat-content">
                <div class="dcf-stat-number"><?php echo $conversion_rate; ?>%</div>
                <div class="dcf-stat-label"><?php _e('Conversion Rate', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="dcf-charts-section">
        <div class="dcf-chart-container">
            <div class="dcf-chart-header">
                <h3><?php _e('Submissions Over Time', 'dry-cleaning-forms'); ?></h3>
            </div>
            <div class="dcf-chart-body">
                <canvas id="submissionsChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <?php if (!$form_id_filter): ?>
        <div class="dcf-chart-container">
            <div class="dcf-chart-header">
                <h3><?php _e('Form Type Performance', 'dry-cleaning-forms'); ?></h3>
            </div>
            <div class="dcf-chart-body">
                <canvas id="formTypeChart" width="400" height="200"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Step Abandonment Analysis -->
    <?php if (!empty($step_abandonment)): ?>
    <div class="dcf-analysis-section">
        <div class="dcf-analysis-card">
            <h3><?php _e('Step Abandonment Analysis', 'dry-cleaning-forms'); ?></h3>
            <div class="dcf-abandonment-chart">
                <canvas id="abandonmentChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Top Performing Forms -->
    <div class="dcf-tables-section">
        <div class="dcf-table-container">
            <h3><?php echo $form_id_filter ? __('Form Performance', 'dry-cleaning-forms') : __('Top Performing Forms', 'dry-cleaning-forms'); ?></h3>
            <?php if (empty($top_forms)): ?>
                <p><?php _e('No form data available for the selected period.', 'dry-cleaning-forms'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Form Name', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Type', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Total Submissions', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Completed', 'dry-cleaning-forms'); ?></th>
                            <th><?php _e('Conversion Rate', 'dry-cleaning-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_forms as $form): ?>
                            <?php
                            // Get form display name
                            $form_display_name = '';
                            if ($form->form_config) {
                                $config = json_decode($form->form_config, true);
                                if (isset($config['title']) && !empty($config['title'])) {
                                    $form_display_name = $config['title'];
                                }
                            }
                            if (empty($form_display_name)) {
                                $form_display_name = $form->form_name ?: 'Form ' . $form->form_id;
                            }
                            
                            // Handle special form IDs
                            if ($form->form_id === 'customer_signup') {
                                $form_display_name = __('New Customer Signup', 'dry-cleaning-forms');
                            } elseif ($form->form_id === 'contact') {
                                $form_display_name = __('Contact Form', 'dry-cleaning-forms');
                            } elseif ($form->form_id === 'optin') {
                                $form_display_name = __('Opt-in Form', 'dry-cleaning-forms');
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($form_display_name); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $form->form_type ?: 'unknown'))); ?>
                                </td>
                                <td><?php echo number_format($form->total); ?></td>
                                <td><?php echo number_format($form->completed); ?></td>
                                <td>
                                    <span class="dcf-conversion-rate <?php echo $form->conversion_rate >= 50 ? 'dcf-rate-good' : ($form->conversion_rate >= 25 ? 'dcf-rate-average' : 'dcf-rate-poor'); ?>">
                                        <?php echo $form->conversion_rate; ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dcf-analytics-filters {
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

.dcf-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.dcf-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.dcf-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f0f6fc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0073aa;
}

.dcf-stat-icon.dcf-stat-success {
    background: #d1e7dd;
    color: #0f5132;
}

.dcf-stat-icon.dcf-stat-warning {
    background: #fff3cd;
    color: #856404;
}

.dcf-stat-icon.dcf-stat-info {
    background: #cff4fc;
    color: #055160;
}

.dcf-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.dcf-stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1;
    margin-bottom: 5px;
}

.dcf-stat-label {
    font-size: 14px;
    color: #646970;
    font-weight: 500;
}

.dcf-charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.dcf-chart-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.dcf-chart-header {
    padding: 20px;
    border-bottom: 1px solid #c3c4c7;
    background: #f6f7f7;
}

.dcf-chart-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1d2327;
}

.dcf-chart-body {
    padding: 20px;
    position: relative;
    height: 300px;
}

.dcf-analysis-section {
    margin: 30px 0;
}

.dcf-analysis-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.dcf-analysis-card h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #1d2327;
}

.dcf-abandonment-chart {
    height: 300px;
    position: relative;
}

.dcf-tables-section {
    margin: 30px 0;
}

.dcf-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.dcf-table-container h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #1d2327;
}

.dcf-conversion-rate {
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: 600;
    font-size: 12px;
}

.dcf-rate-good {
    background: #d1e7dd;
    color: #0f5132;
}

.dcf-rate-average {
    background: #fff3cd;
    color: #856404;
}

.dcf-rate-poor {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .dcf-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dcf-charts-section {
        grid-template-columns: 1fr;
    }
    
    .dcf-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle custom date fields
    $('#date_range').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.dcf-custom-dates').show();
        } else {
            $('.dcf-custom-dates').hide();
        }
    });
    
    // Load Chart.js if not already loaded
    if (typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        script.onload = function() {
            initCharts();
        };
        document.head.appendChild(script);
    } else {
        initCharts();
    }
    
    function initCharts() {
        var chartLabels = <?php echo json_encode($chart_labels); ?>;
        var chartTotal = <?php echo json_encode($chart_total); ?>;
        var chartCompleted = <?php echo json_encode($chart_completed); ?>;
        
        // Submissions over time chart
        var submissionsCtx = document.getElementById('submissionsChart');
        if (submissionsCtx) {
            new Chart(submissionsCtx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Total Submissions',
                        data: chartTotal,
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Completed',
                        data: chartCompleted,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        
        // Form type performance chart
        var formTypeCtx = document.getElementById('formTypeChart');
        if (formTypeCtx) {
            var formTypeData = <?php echo json_encode($form_type_stats); ?>;
            var formTypeLabels = formTypeData.map(function(item) {
                return item.form_type ? item.form_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
            });
            var formTypeTotals = formTypeData.map(function(item) {
                return parseInt(item.total);
            });
            var formTypeCompleted = formTypeData.map(function(item) {
                return parseInt(item.completed);
            });
            
            new Chart(formTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: formTypeLabels,
                    datasets: [{
                        label: 'Total Submissions',
                        data: formTypeTotals,
                        backgroundColor: [
                            '#0073aa',
                            '#00a32a',
                            '#d63638',
                            '#ff8c00',
                            '#8e44ad',
                            '#2ecc71'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Step abandonment chart
        var abandonmentCtx = document.getElementById('abandonmentChart');
        if (abandonmentCtx) {
            var abandonmentData = <?php echo json_encode($step_abandonment); ?>;
            var abandonmentLabels = abandonmentData.map(function(item) {
                return 'Step ' + item.step_completed;
            });
            var abandonmentCounts = abandonmentData.map(function(item) {
                return parseInt(item.count);
            });
            
            new Chart(abandonmentCtx, {
                type: 'bar',
                data: {
                    labels: abandonmentLabels,
                    datasets: [{
                        label: 'Abandoned at Step',
                        data: abandonmentCounts,
                        backgroundColor: '#d63638',
                        borderColor: '#b32d2e',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return 'Abandonment at ' + context[0].label;
                                },
                                label: function(context) {
                                    return context.parsed.y + ' users abandoned here';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }
});
</script> 