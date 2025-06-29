<?php
/**
 * Popup Analytics View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get popup data
$popup = null;
if ($popup_id) {
    $popup = $popup_manager->get_popup($popup_id);
    if (!$popup) {
        wp_die(__('Popup not found.', 'dry-cleaning-forms'));
    }
}

// Get date range from query params
$date_range = $_GET['range'] ?? '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// Get analytics data from the analytics class
$analytics = new DCF_Popup_Conversion_Analytics();
$metrics = $analytics->calculate_conversion_metrics($popup_id, $date_range);

$analytics_data = array(
    'total_displays' => $metrics['views'] ?: 0,
    'total_interactions' => $metrics['interactions'] ?: 0,
    'total_conversions' => $metrics['conversions'] ?: 0,
    'conversion_rate' => $metrics['conversion_rate'] ?: 0,
    'avg_time_to_convert' => $metrics['time_to_conversion'] ?: 0,
    'bounce_rate' => $metrics['conversion_rate'] ? (100 - $metrics['conversion_rate']) : 0
);

// Get daily performance data for chart
$daily_performance = $metrics['daily_performance'] ?: array();
$chart_data = array(
    'labels' => array(),
    'displays' => array(),
    'conversions' => array()
);

// Process daily performance data
foreach ($daily_performance as $day_data) {
    $chart_data['labels'][] = date('M j', strtotime($day_data['date']));
    $chart_data['displays'][] = $day_data['views'] ?: 0;
    $chart_data['conversions'][] = $day_data['conversions'] ?: 0;
}

// If no data, show empty chart
if (empty($chart_data['labels'])) {
    for ($i = $date_range - 1; $i >= 0; $i--) {
        $date = date('M j', strtotime("-{$i} days"));
        $chart_data['labels'][] = $date;
        $chart_data['displays'][] = 0;
        $chart_data['conversions'][] = 0;
    }
}

?>

<div class="dcf-popup-analytics">
    <?php if ($popup): ?>
        <div class="dcf-analytics-header">
            <h2><?php echo esc_html($popup['popup_name']); ?> - <?php _e('Analytics', 'dry-cleaning-forms'); ?></h2>
            <div class="dcf-analytics-controls">
                <select id="date-range" onchange="updateDateRange(this.value)">
                    <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'dry-cleaning-forms'); ?></option>
                    <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'dry-cleaning-forms'); ?></option>
                    <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'dry-cleaning-forms'); ?></option>
                    <option value="365" <?php selected($date_range, '365'); ?>><?php _e('Last year', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id); ?>" 
                   class="button">
                    <?php _e('Edit Popup', 'dry-cleaning-forms'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Key Metrics -->
    <div class="dcf-metrics-grid">
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo number_format($analytics_data['total_displays']); ?></div>
            <div class="dcf-metric-label"><?php _e('Total Displays', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change positive">+12.5%</div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo number_format($analytics_data['total_interactions']); ?></div>
            <div class="dcf-metric-label"><?php _e('Total Interactions', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change positive">+8.3%</div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo number_format($analytics_data['total_conversions']); ?></div>
            <div class="dcf-metric-label"><?php _e('Conversions', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change positive">+15.7%</div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo $analytics_data['conversion_rate']; ?>%</div>
            <div class="dcf-metric-label"><?php _e('Conversion Rate', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change negative">-2.1%</div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo $analytics_data['avg_time_to_convert']; ?>s</div>
            <div class="dcf-metric-label"><?php _e('Avg. Time to Convert', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change positive">-5.2%</div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-value"><?php echo $analytics_data['bounce_rate']; ?>%</div>
            <div class="dcf-metric-label"><?php _e('Bounce Rate', 'dry-cleaning-forms'); ?></div>
            <div class="dcf-metric-change negative">+3.8%</div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="dcf-charts-section">
        <div class="dcf-chart-container">
            <div class="dcf-popup-card">
                <h3><?php _e('Performance Over Time', 'dry-cleaning-forms'); ?></h3>
                <canvas id="performance-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <div class="dcf-chart-container">
            <div class="dcf-popup-card">
                <h3><?php _e('Conversion Funnel', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-funnel-chart">
                    <?php
                    $funnel_data = $metrics['funnel'] ?: array();
                    $max_count = !empty($funnel_data) ? $funnel_data[0]['count'] : 0;
                    
                    if (!empty($funnel_data) && $max_count > 0) {
                        foreach ($funnel_data as $step) {
                            $width = $max_count > 0 ? round(($step['count'] / $max_count) * 100) : 0;
                            ?>
                            <div class="dcf-funnel-step">
                                <div class="dcf-funnel-bar" style="width: <?php echo $width; ?>%;">
                                    <span class="dcf-funnel-label"><?php echo esc_html($step['label']); ?></span>
                                    <span class="dcf-funnel-value"><?php echo number_format($step['count']); ?></span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="dcf-no-data"><?php _e('No funnel data available yet', 'dry-cleaning-forms'); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Device & Browser Breakdown -->
    <div class="dcf-breakdown-section">
        <div class="dcf-breakdown-container">
            <div class="dcf-popup-card">
                <h3><?php _e('Device Breakdown', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-breakdown-chart">
                    <?php
                    $device_breakdown = $metrics['device_breakdown'] ?: array();
                    $total_views = array_sum(array_column($device_breakdown, 'views'));
                    
                    if ($total_views > 0) {
                        foreach ($device_breakdown as $device) {
                            $percentage = round(($device['views'] / $total_views) * 100);
                            ?>
                            <div class="dcf-breakdown-item">
                                <span class="dcf-breakdown-label"><?php echo esc_html($device['device_type']); ?></span>
                                <div class="dcf-breakdown-bar">
                                    <div class="dcf-breakdown-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <span class="dcf-breakdown-percentage"><?php echo $percentage; ?>%</span>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="dcf-no-data"><?php _e('No device data available yet', 'dry-cleaning-forms'); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="dcf-breakdown-container">
            <div class="dcf-popup-card">
                <h3><?php _e('Top Pages', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-top-pages">
                    <?php
                    // Get top pages from database
                    global $wpdb;
                    $events_table = $wpdb->prefix . 'dcf_popup_events';
                    $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";
                    
                    $top_pages = $wpdb->get_results($wpdb->prepare(
                        "SELECT page_url,
                                COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions
                         FROM {$events_table}
                         WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
                         {$popup_condition}
                         AND page_url != ''
                         GROUP BY page_url
                         ORDER BY views DESC
                         LIMIT 5",
                        $date_range
                    ), ARRAY_A);
                    
                    if (!empty($top_pages)) {
                        foreach ($top_pages as $page) {
                            $conv_rate = $page['views'] > 0 ? round(($page['conversions'] / $page['views']) * 100, 1) : 0;
                            $parsed_url = parse_url($page['page_url']);
                            $display_url = $parsed_url['path'] ?? '/';
                            ?>
                            <div class="dcf-page-item">
                                <span class="dcf-page-url"><?php echo esc_html($display_url); ?></span>
                                <span class="dcf-page-displays"><?php echo number_format($page['views']); ?> <?php _e('displays', 'dry-cleaning-forms'); ?></span>
                                <span class="dcf-page-rate"><?php echo $conv_rate; ?>% <?php _e('conv.', 'dry-cleaning-forms'); ?></span>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="dcf-no-data"><?php _e('No page data available yet', 'dry-cleaning-forms'); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="dcf-activity-section">
        <div class="dcf-popup-card">
            <h3><?php _e('Recent Activity', 'dry-cleaning-forms'); ?></h3>
            
            <div class="dcf-activity-filters">
                <select id="activity-filter">
                    <option value="all"><?php _e('All Activities', 'dry-cleaning-forms'); ?></option>
                    <option value="displays"><?php _e('Displays Only', 'dry-cleaning-forms'); ?></option>
                    <option value="interactions"><?php _e('Interactions Only', 'dry-cleaning-forms'); ?></option>
                    <option value="conversions"><?php _e('Conversions Only', 'dry-cleaning-forms'); ?></option>
                </select>
            </div>
            
            <div class="dcf-activity-list">
                <?php
                // Get recent activity from database
                global $wpdb;
                $events_table = $wpdb->prefix . 'dcf_popup_events';
                $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";
                
                $recent_events = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$events_table}
                     WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
                     {$popup_condition}
                     ORDER BY timestamp DESC
                     LIMIT 20",
                    1 // Last 24 hours for recent activity
                ), ARRAY_A);
                
                if (!empty($recent_events)) {
                    foreach ($recent_events as $event) {
                        $event_type = $event['event_type'];
                        $page_url = parse_url($event['page_url']);
                        $display_url = $page_url['path'] ?? '/';
                        $time_ago = human_time_diff(strtotime($event['timestamp']), current_time('timestamp'));
                        
                        // Determine event icon and action text
                        $icon_class = 'dcf-activity-display';
                        $action_text = __('Popup displayed', 'dry-cleaning-forms');
                        $meta_text = sprintf(__('Shown to visitor on %s', 'dry-cleaning-forms'), $display_url);
                        
                        switch ($event_type) {
                            case 'conversion':
                            case 'submission':
                                $icon_class = 'dcf-activity-conversion';
                                $action_text = __('Form submitted', 'dry-cleaning-forms');
                                $meta_text = sprintf(__('User converted on %s', 'dry-cleaning-forms'), $display_url);
                                break;
                            case 'interaction':
                                $icon_class = 'dcf-activity-interaction';
                                $action_text = __('Popup interacted', 'dry-cleaning-forms');
                                $meta_text = sprintf(__('User clicked on %s', 'dry-cleaning-forms'), $display_url);
                                break;
                            case 'view':
                                $icon_class = 'dcf-activity-display';
                                $action_text = __('Popup viewed', 'dry-cleaning-forms');
                                $meta_text = sprintf(__('Viewed by visitor on %s', 'dry-cleaning-forms'), $display_url);
                                break;
                        }
                        ?>
                        <div class="dcf-activity-item">
                            <div class="dcf-activity-icon <?php echo esc_attr($icon_class); ?>"></div>
                            <div class="dcf-activity-details">
                                <div class="dcf-activity-action"><?php echo esc_html($action_text); ?></div>
                                <div class="dcf-activity-meta">
                                    <?php echo esc_html($meta_text); ?> • 
                                    <span class="dcf-activity-time"><?php echo sprintf(__('%s ago', 'dry-cleaning-forms'), $time_ago); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="dcf-no-data"><?php _e('No recent activity', 'dry-cleaning-forms'); ?></div>
                    <?php
                }
                ?>
            </div>
            
            <div class="dcf-activity-pagination">
                <button class="button"><?php _e('Load More', 'dry-cleaning-forms'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Export Options -->
    <div class="dcf-export-section">
        <div class="dcf-popup-card">
            <h3><?php _e('Export Data', 'dry-cleaning-forms'); ?></h3>
            <p><?php _e('Download your popup analytics data for further analysis.', 'dry-cleaning-forms'); ?></p>
            
            <div class="dcf-export-options">
                <button class="button" onclick="exportData('csv')">
                    <?php _e('Export as CSV', 'dry-cleaning-forms'); ?>
                </button>
                <button class="button" onclick="exportData('json')">
                    <?php _e('Export as JSON', 'dry-cleaning-forms'); ?>
                </button>
                <button class="button" onclick="exportData('pdf')">
                    <?php _e('Export Report as PDF', 'dry-cleaning-forms'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize Chart.js
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    } else {
        // Load Chart.js if not already loaded
        $.getScript('https://cdn.jsdelivr.net/npm/chart.js', function() {
            initializeCharts();
        });
    }
    
    function initializeCharts() {
        // Performance chart
        var ctx = document.getElementById('performance-chart').getContext('2d');
        var chartData = <?php echo json_encode($chart_data); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: '<?php _e('Displays', 'dry-cleaning-forms'); ?>',
                    data: chartData.displays,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4
                }, {
                    label: '<?php _e('Conversions', 'dry-cleaning-forms'); ?>',
                    data: chartData.conversions,
                    borderColor: '#00a32a',
                    backgroundColor: 'rgba(0, 163, 42, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
});

function updateDateRange(range) {
    var url = new URL(window.location);
    url.searchParams.set('range', range);
    window.location = url;
}

function exportData(format) {
    var popupId = <?php echo $popup_id ?: 0; ?>;
    var dateRange = '<?php echo esc_js($date_range); ?>';
    
    var url = ajaxurl + '?action=dcf_export_popup_data&popup_id=' + popupId + 
              '&format=' + format + '&range=' + dateRange + 
              '&nonce=<?php echo wp_create_nonce('dcf_export_nonce'); ?>';
    
    window.open(url, '_blank');
}
</script>

<style>
.dcf-popup-analytics {
    max-width: 1200px;
}

.dcf-analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dcf-analytics-header h2 {
    margin: 0;
    font-size: 24px;
}

.dcf-analytics-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.dcf-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dcf-metric-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.dcf-metric-value {
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 5px;
}

.dcf-metric-label {
    font-size: 14px;
    color: #646970;
    margin-bottom: 10px;
}

.dcf-metric-change {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
}

.dcf-metric-change.positive {
    color: #00a32a;
    background: rgba(0, 163, 42, 0.1);
}

.dcf-metric-change.negative {
    color: #d63638;
    background: rgba(214, 54, 56, 0.1);
}

.dcf-charts-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.dcf-chart-container {
    background: #fff;
}

.dcf-chart-container canvas {
    max-height: 300px;
}

.dcf-funnel-chart {
    padding: 20px 0;
}

.dcf-funnel-step {
    margin-bottom: 15px;
}

.dcf-funnel-bar {
    background: linear-gradient(90deg, #2271b1, #72aee6);
    color: white;
    padding: 15px 20px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}

.dcf-breakdown-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.dcf-breakdown-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.dcf-breakdown-label {
    min-width: 80px;
    font-weight: 600;
}

.dcf-breakdown-bar {
    flex: 1;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.dcf-breakdown-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #72aee6);
    border-radius: 10px;
}

.dcf-breakdown-percentage {
    min-width: 40px;
    text-align: right;
    font-weight: 600;
}

.dcf-top-pages {
    max-height: 200px;
    overflow-y: auto;
}

.dcf-page-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.dcf-page-item:last-child {
    border-bottom: none;
}

.dcf-page-url {
    font-weight: 600;
    color: #2271b1;
    flex: 1;
}

.dcf-page-displays {
    font-size: 12px;
    color: #646970;
    margin: 0 15px;
}

.dcf-page-rate {
    font-size: 12px;
    font-weight: 600;
    color: #00a32a;
}

.dcf-activity-section {
    margin-bottom: 30px;
}

.dcf-activity-filters {
    margin-bottom: 20px;
}

.dcf-activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.dcf-activity-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f1;
}

.dcf-activity-item:last-child {
    border-bottom: none;
}

.dcf-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 12px;
    flex-shrink: 0;
}

.dcf-activity-icon.dcf-activity-display {
    background: rgba(34, 113, 177, 0.1);
    color: #2271b1;
}

.dcf-activity-icon.dcf-activity-display::before {
    content: "👁";
}

.dcf-activity-icon.dcf-activity-interaction {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.dcf-activity-icon.dcf-activity-interaction::before {
    content: "👆";
}

.dcf-activity-icon.dcf-activity-conversion {
    background: rgba(0, 163, 42, 0.1);
    color: #00a32a;
}

.dcf-activity-icon.dcf-activity-conversion::before {
    content: "✅";
}

.dcf-activity-details {
    flex: 1;
}

.dcf-activity-action {
    font-weight: 600;
    margin-bottom: 5px;
}

.dcf-activity-meta {
    font-size: 12px;
    color: #646970;
}

.dcf-activity-time {
    font-weight: 600;
}

.dcf-activity-pagination {
    text-align: center;
    margin-top: 20px;
}

.dcf-export-section {
    margin-bottom: 30px;
}

.dcf-export-options {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.dcf-no-data {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
}

@media (max-width: 768px) {
    .dcf-analytics-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .dcf-charts-section,
    .dcf-breakdown-section {
        grid-template-columns: 1fr;
    }
    
    .dcf-metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .dcf-export-options {
        flex-direction: column;
    }
}
</style> 