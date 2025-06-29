<?php
/**
 * A/B Test Analytics View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize A/B testing manager
$ab_testing_manager = new DCF_AB_Testing_Manager();

// Get test ID
$test_id = intval($_GET['test_id'] ?? 0);
if (!$test_id) {
    wp_die(__('Invalid test ID.', 'dry-cleaning-forms'));
}

// Get test data
$test = $ab_testing_manager->get_ab_test($test_id);
if (!$test) {
    wp_die(__('A/B test not found.', 'dry-cleaning-forms'));
}

// Get test performance data
$variants_performance = $ab_testing_manager->get_test_variants_performance($test_id);
$test_analytics = $ab_testing_manager->get_test_analytics($test_id);

// Calculate overall metrics
$total_displays = array_sum(array_column($variants_performance, 'displays'));
$total_conversions = array_sum(array_column($variants_performance, 'conversions'));
$overall_conversion_rate = $total_displays > 0 ? round(($total_conversions / $total_displays) * 100, 2) : 0;

// Find best performing variant
$best_variant = null;
$best_rate = 0;
foreach ($variants_performance as $variant) {
    if ($variant['conversion_rate'] > $best_rate) {
        $best_rate = $variant['conversion_rate'];
        $best_variant = $variant;
    }
}

// Get date range for analytics
$date_range = $_GET['range'] ?? '7';
$end_date = current_time('Y-m-d');
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));

?>

<div class="dcf-ab-test-analytics">
    <div class="dcf-analytics-header">
        <div class="dcf-analytics-title">
            <h2><?php echo esc_html($test['test_name']); ?></h2>
            <div class="dcf-test-meta">
                <span class="dcf-status dcf-status-<?php echo esc_attr($test['status']); ?>">
                    <?php echo ucfirst($test['status']); ?>
                </span>
                <span class="dcf-test-duration">
                    <?php if ($test['start_date']): ?>
                        <?php printf(__('Started: %s', 'dry-cleaning-forms'), date('M j, Y', strtotime($test['start_date']))); ?>
                    <?php endif; ?>
                    <?php if ($test['end_date']): ?>
                        | <?php printf(__('Ends: %s', 'dry-cleaning-forms'), date('M j, Y', strtotime($test['end_date']))); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="dcf-analytics-actions">
            <div class="dcf-date-range">
                <select id="date-range-select">
                    <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'dry-cleaning-forms'); ?></option>
                    <option value="14" <?php selected($date_range, '14'); ?>><?php _e('Last 14 days', 'dry-cleaning-forms'); ?></option>
                    <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'dry-cleaning-forms'); ?></option>
                    <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'dry-cleaning-forms'); ?></option>
                </select>
            </div>
            
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_edit&test_id=' . $test_id); ?>" 
               class="button">
                <?php _e('Edit Test', 'dry-cleaning-forms'); ?>
            </a>
            
            <button type="button" class="button" id="export-data">
                <?php _e('Export Data', 'dry-cleaning-forms'); ?>
            </button>
        </div>
    </div>
    
    <!-- Key Metrics Overview -->
    <div class="dcf-metrics-overview">
        <div class="dcf-metric-card">
            <div class="dcf-metric-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dcf-metric-content">
                <div class="dcf-metric-value"><?php echo number_format($total_displays); ?></div>
                <div class="dcf-metric-label"><?php _e('Total Displays', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dcf-metric-content">
                <div class="dcf-metric-value"><?php echo number_format($total_conversions); ?></div>
                <div class="dcf-metric-label"><?php _e('Total Conversions', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dcf-metric-content">
                <div class="dcf-metric-value"><?php echo $overall_conversion_rate; ?>%</div>
                <div class="dcf-metric-label"><?php _e('Overall Conversion Rate', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="dcf-metric-content">
                <div class="dcf-metric-value">
                    <?php echo $best_variant ? esc_html($best_variant['popup_name']) : '—'; ?>
                </div>
                <div class="dcf-metric-label"><?php _e('Best Performer', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <div class="dcf-metric-card">
            <div class="dcf-metric-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dcf-metric-content">
                <div class="dcf-metric-value"><?php echo count($variants_performance); ?></div>
                <div class="dcf-metric-label"><?php _e('Variants', 'dry-cleaning-forms'); ?></div>
            </div>
        </div>
        
        <?php if ($test['winner_id']): ?>
            <div class="dcf-metric-card dcf-winner-card">
                <div class="dcf-metric-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="dcf-metric-content">
                    <div class="dcf-metric-value"><?php _e('Winner Declared', 'dry-cleaning-forms'); ?></div>
                    <div class="dcf-metric-label">
                        <?php
                        foreach ($variants_performance as $variant) {
                            if ($variant['popup_id'] == $test['winner_id']) {
                                echo esc_html($variant['popup_name']);
                                break;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Performance Charts -->
    <div class="dcf-charts-section">
        <div class="dcf-chart-container">
            <div class="dcf-chart-header">
                <h3><?php _e('Performance Over Time', 'dry-cleaning-forms'); ?></h3>
                <div class="dcf-chart-controls">
                    <label>
                        <input type="radio" name="chart_metric" value="displays" checked>
                        <?php _e('Displays', 'dry-cleaning-forms'); ?>
                    </label>
                    <label>
                        <input type="radio" name="chart_metric" value="conversions">
                        <?php _e('Conversions', 'dry-cleaning-forms'); ?>
                    </label>
                    <label>
                        <input type="radio" name="chart_metric" value="conversion_rate">
                        <?php _e('Conversion Rate', 'dry-cleaning-forms'); ?>
                    </label>
                </div>
            </div>
            <canvas id="performance-chart" width="800" height="400"></canvas>
        </div>
        
        <div class="dcf-chart-container">
            <div class="dcf-chart-header">
                <h3><?php _e('Variant Comparison', 'dry-cleaning-forms'); ?></h3>
            </div>
            <canvas id="comparison-chart" width="400" height="400"></canvas>
        </div>
    </div>
    
    <!-- Detailed Variant Analysis -->
    <div class="dcf-variants-analysis">
        <h3><?php _e('Variant Performance Analysis', 'dry-cleaning-forms'); ?></h3>
        
        <div class="dcf-variants-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column"><?php _e('Variant', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Traffic Split', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Displays', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Conversions', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Conversion Rate', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Confidence', 'dry-cleaning-forms'); ?></th>
                        <th class="manage-column"><?php _e('Status', 'dry-cleaning-forms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants_performance as $index => $variant): ?>
                        <?php
                        $is_winner = $variant['popup_id'] == $test['winner_id'];
                        $is_leading = !$is_winner && $variant === $best_variant;
                        
                        // Calculate confidence interval for this variant
                        $confidence_data = $ab_testing_manager->calculate_confidence_interval($variant);
                        ?>
                        <tr class="<?php echo $is_winner ? 'dcf-winner-row' : ($is_leading ? 'dcf-leading-row' : ''); ?>">
                            <td class="column-variant">
                                <div class="dcf-variant-info">
                                    <strong><?php echo esc_html($variant['popup_name']); ?></strong>
                                    <?php if ($is_winner): ?>
                                        <span class="dcf-winner-badge">
                                            <span class="dashicons dashicons-awards"></span>
                                            <?php _e('Winner', 'dry-cleaning-forms'); ?>
                                        </span>
                                    <?php elseif ($is_leading): ?>
                                        <span class="dcf-leading-badge">
                                            <span class="dashicons dashicons-chart-line"></span>
                                            <?php _e('Leading', 'dry-cleaning-forms'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-traffic">
                                <div class="dcf-traffic-split">
                                    <span class="dcf-split-percentage"><?php echo $variant['traffic_split']; ?>%</span>
                                    <div class="dcf-split-bar">
                                        <div class="dcf-split-fill" style="width: <?php echo $variant['traffic_split']; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="column-displays">
                                <span class="dcf-metric-large"><?php echo number_format($variant['displays']); ?></span>
                            </td>
                            <td class="column-conversions">
                                <span class="dcf-metric-large"><?php echo number_format($variant['conversions']); ?></span>
                            </td>
                            <td class="column-rate">
                                <div class="dcf-conversion-rate">
                                    <span class="dcf-rate-value"><?php echo $variant['conversion_rate']; ?>%</span>
                                    <?php if ($best_variant && $variant !== $best_variant): ?>
                                        <?php
                                        $improvement = (($variant['conversion_rate'] - $best_variant['conversion_rate']) / $best_variant['conversion_rate']) * 100;
                                        $improvement_class = $improvement >= 0 ? 'dcf-positive' : 'dcf-negative';
                                        ?>
                                        <span class="dcf-improvement <?php echo $improvement_class; ?>">
                                            <?php echo $improvement >= 0 ? '+' : ''; ?><?php echo round($improvement, 1); ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-confidence">
                                <?php if ($confidence_data): ?>
                                    <div class="dcf-confidence-interval">
                                        <span class="dcf-confidence-value"><?php echo $confidence_data['confidence']; ?>%</span>
                                        <div class="dcf-confidence-range">
                                            <?php echo $confidence_data['lower']; ?>% - <?php echo $confidence_data['upper']; ?>%
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="dcf-no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-status">
                                <?php if ($variant['displays'] < $test['minimum_sample_size']): ?>
                                    <span class="dcf-status-badge dcf-status-collecting">
                                        <?php _e('Collecting Data', 'dry-cleaning-forms'); ?>
                                    </span>
                                <?php elseif ($is_winner): ?>
                                    <span class="dcf-status-badge dcf-status-winner">
                                        <?php _e('Winner', 'dry-cleaning-forms'); ?>
                                    </span>
                                <?php elseif ($is_leading): ?>
                                    <span class="dcf-status-badge dcf-status-leading">
                                        <?php _e('Leading', 'dry-cleaning-forms'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="dcf-status-badge dcf-status-active">
                                        <?php _e('Active', 'dry-cleaning-forms'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Statistical Analysis -->
    <?php if (count($variants_performance) >= 2): ?>
        <div class="dcf-statistical-analysis">
            <h3><?php _e('Statistical Analysis', 'dry-cleaning-forms'); ?></h3>
            
            <?php
            // Perform pairwise comparisons
            $comparisons = array();
            for ($i = 0; $i < count($variants_performance); $i++) {
                for ($j = $i + 1; $j < count($variants_performance); $j++) {
                    $variant_a = $variants_performance[$i];
                    $variant_b = $variants_performance[$j];
                    $stats = $ab_testing_manager->calculate_statistical_significance($variant_a, $variant_b);
                    $comparisons[] = array(
                        'variant_a' => $variant_a,
                        'variant_b' => $variant_b,
                        'stats' => $stats
                    );
                }
            }
            ?>
            
            <div class="dcf-comparisons-grid">
                <?php foreach ($comparisons as $comparison): ?>
                    <div class="dcf-comparison-card">
                        <div class="dcf-comparison-header">
                            <h4>
                                <?php echo esc_html($comparison['variant_a']['popup_name']); ?> 
                                vs 
                                <?php echo esc_html($comparison['variant_b']['popup_name']); ?>
                            </h4>
                        </div>
                        
                        <div class="dcf-comparison-result <?php echo $comparison['stats']['significant'] ? 'dcf-significant' : 'dcf-not-significant'; ?>">
                            <div class="dcf-result-icon">
                                <?php if ($comparison['stats']['significant']): ?>
                                    <span class="dashicons dashicons-yes-alt"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning"></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="dcf-result-content">
                                <div class="dcf-result-message">
                                    <?php echo esc_html($comparison['stats']['message']); ?>
                                </div>
                                
                                <div class="dcf-result-metrics">
                                    <div class="dcf-metric-item">
                                        <span class="dcf-metric-label"><?php _e('Confidence:', 'dry-cleaning-forms'); ?></span>
                                        <span class="dcf-metric-value"><?php echo $comparison['stats']['confidence']; ?>%</span>
                                    </div>
                                    <div class="dcf-metric-item">
                                        <span class="dcf-metric-label"><?php _e('P-Value:', 'dry-cleaning-forms'); ?></span>
                                        <span class="dcf-metric-value"><?php echo $comparison['stats']['p_value']; ?></span>
                                    </div>
                                    <?php if ($comparison['stats']['improvement'] != 0): ?>
                                        <div class="dcf-metric-item">
                                            <span class="dcf-metric-label"><?php _e('Improvement:', 'dry-cleaning-forms'); ?></span>
                                            <span class="dcf-metric-value <?php echo $comparison['stats']['improvement'] > 0 ? 'dcf-positive' : 'dcf-negative'; ?>">
                                                <?php echo $comparison['stats']['improvement'] > 0 ? '+' : ''; ?><?php echo $comparison['stats']['improvement']; ?>%
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Test Recommendations -->
    <div class="dcf-recommendations">
        <h3><?php _e('Recommendations', 'dry-cleaning-forms'); ?></h3>
        
        <div class="dcf-recommendations-list">
            <?php
            $recommendations = $ab_testing_manager->generate_test_recommendations($test, $variants_performance);
            foreach ($recommendations as $recommendation):
            ?>
                <div class="dcf-recommendation-item dcf-recommendation-<?php echo esc_attr($recommendation['type']); ?>">
                    <div class="dcf-recommendation-icon">
                        <span class="dashicons dashicons-<?php echo esc_attr($recommendation['icon']); ?>"></span>
                    </div>
                    <div class="dcf-recommendation-content">
                        <h4><?php echo esc_html($recommendation['title']); ?></h4>
                        <p><?php echo esc_html($recommendation['description']); ?></p>
                        <?php if (!empty($recommendation['action'])): ?>
                            <div class="dcf-recommendation-action">
                                <?php echo $recommendation['action']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Chart data
    var chartData = <?php echo json_encode($test_analytics); ?>;
    var variantsData = <?php echo json_encode($variants_performance); ?>;
    
    // Initialize charts
    initializePerformanceChart();
    initializeComparisonChart();
    
    // Date range change
    $('#date-range-select').on('change', function() {
        var range = $(this).val();
        var url = new URL(window.location);
        url.searchParams.set('range', range);
        window.location = url;
    });
    
    // Chart metric change
    $('input[name="chart_metric"]').on('change', function() {
        updatePerformanceChart($(this).val());
    });
    
    // Export data
    $('#export-data').on('click', function() {
        exportTestData();
    });
    
    function initializePerformanceChart() {
        var ctx = document.getElementById('performance-chart').getContext('2d');
        
        window.performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.dates,
                datasets: variantsData.map(function(variant, index) {
                    return {
                        label: variant.popup_name,
                        data: chartData.displays[variant.popup_id] || [],
                        borderColor: getVariantColor(index),
                        backgroundColor: getVariantColor(index, 0.1),
                        borderWidth: 2,
                        fill: false,
                        tension: 0.1
                    };
                })
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
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    function initializeComparisonChart() {
        var ctx = document.getElementById('comparison-chart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: variantsData.map(function(variant) {
                    return variant.popup_name;
                }),
                datasets: [{
                    data: variantsData.map(function(variant) {
                        return variant.conversions;
                    }),
                    backgroundColor: variantsData.map(function(variant, index) {
                        return getVariantColor(index);
                    }),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var variant = variantsData[context.dataIndex];
                                return variant.popup_name + ': ' + variant.conversions + ' conversions (' + variant.conversion_rate + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    function updatePerformanceChart(metric) {
        if (!window.performanceChart) return;
        
        var datasets = variantsData.map(function(variant, index) {
            var data;
            switch(metric) {
                case 'conversions':
                    data = chartData.conversions[variant.popup_id] || [];
                    break;
                case 'conversion_rate':
                    data = chartData.conversion_rates[variant.popup_id] || [];
                    break;
                default:
                    data = chartData.displays[variant.popup_id] || [];
            }
            
            return {
                label: variant.popup_name,
                data: data,
                borderColor: getVariantColor(index),
                backgroundColor: getVariantColor(index, 0.1),
                borderWidth: 2,
                fill: false,
                tension: 0.1
            };
        });
        
        window.performanceChart.data.datasets = datasets;
        window.performanceChart.update();
    }
    
    function getVariantColor(index, alpha = 1) {
        var colors = [
            'rgba(34, 113, 177, ' + alpha + ')',
            'rgba(255, 99, 132, ' + alpha + ')',
            'rgba(255, 205, 86, ' + alpha + ')',
            'rgba(75, 192, 192, ' + alpha + ')',
            'rgba(153, 102, 255, ' + alpha + ')',
            'rgba(255, 159, 64, ' + alpha + ')'
        ];
        return colors[index % colors.length];
    }
    
    function exportTestData() {
        var data = {
            action: 'dcf_export_ab_test_data',
            test_id: <?php echo $test_id; ?>,
            nonce: '<?php echo wp_create_nonce('dcf_export_ab_test_data'); ?>'
        };
        
        // Create download link
        var form = $('<form>', {
            method: 'POST',
            action: ajaxurl
        });
        
        $.each(data, function(key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
});
</script>

<style>
.dcf-ab-test-analytics {
    max-width: 1400px;
}

.dcf-analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dcf-analytics-title h2 {
    margin: 0 0 10px 0;
    font-size: 24px;
}

.dcf-test-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    font-size: 14px;
    color: #646970;
}

.dcf-status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.dcf-status-draft { background: #f0f0f1; color: #646970; }
.dcf-status-active { background: #d1e7dd; color: #0f5132; }
.dcf-status-paused { background: #fff3cd; color: #664d03; }
.dcf-status-completed { background: #cff4fc; color: #055160; }

.dcf-analytics-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.dcf-metrics-overview {
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
    display: flex;
    align-items: center;
    gap: 15px;
}

.dcf-metric-card.dcf-winner-card {
    border-color: #00a32a;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f7fa 100%);
}

.dcf-metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #2271b1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.dcf-winner-card .dcf-metric-icon {
    background: #00a32a;
}

.dcf-metric-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1;
}

.dcf-metric-label {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.dcf-charts-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.dcf-chart-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.dcf-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dcf-chart-header h3 {
    margin: 0;
}

.dcf-chart-controls {
    display: flex;
    gap: 15px;
}

.dcf-chart-controls label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    cursor: pointer;
}

.dcf-variants-analysis {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.dcf-variants-analysis h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.dcf-variants-table-container {
    overflow-x: auto;
}

.dcf-winner-row {
    background: #f0f9ff;
}

.dcf-leading-row {
    background: #fff9e6;
}

.dcf-variant-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dcf-winner-badge, .dcf-leading-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 3px;
}

.dcf-winner-badge {
    background: #00a32a;
    color: white;
}

.dcf-leading-badge {
    background: #2271b1;
    color: white;
}

.dcf-traffic-split {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dcf-split-percentage {
    font-weight: 600;
    font-size: 14px;
}

.dcf-split-bar {
    width: 60px;
    height: 6px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.dcf-split-fill {
    height: 100%;
    background: #2271b1;
}

.dcf-metric-large {
    font-size: 16px;
    font-weight: 600;
}

.dcf-conversion-rate {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.dcf-rate-value {
    font-size: 16px;
    font-weight: 600;
}

.dcf-improvement {
    font-size: 12px;
    font-weight: 600;
}

.dcf-improvement.dcf-positive {
    color: #00a32a;
}

.dcf-improvement.dcf-negative {
    color: #d63638;
}

.dcf-confidence-interval {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.dcf-confidence-value {
    font-weight: 600;
}

.dcf-confidence-range {
    font-size: 11px;
    color: #646970;
}

.dcf-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.dcf-status-collecting { background: #f0f0f1; color: #646970; }
.dcf-status-winner { background: #d1e7dd; color: #0f5132; }
.dcf-status-leading { background: #fff3cd; color: #664d03; }
.dcf-status-active { background: #cff4fc; color: #055160; }

.dcf-statistical-analysis {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.dcf-statistical-analysis h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.dcf-comparisons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.dcf-comparison-card {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
}

.dcf-comparison-header h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
}

.dcf-comparison-result {
    display: flex;
    gap: 10px;
    padding: 10px;
    border-radius: 3px;
}

.dcf-comparison-result.dcf-significant {
    background: #d1e7dd;
    border: 1px solid #00a32a;
}

.dcf-comparison-result.dcf-not-significant {
    background: #fff3cd;
    border: 1px solid #ffc107;
}

.dcf-result-icon {
    font-size: 16px;
}

.dcf-result-content {
    flex: 1;
}

.dcf-result-message {
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 12px;
}

.dcf-result-metrics {
    display: flex;
    gap: 15px;
}

.dcf-metric-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dcf-metric-label {
    font-size: 10px;
    color: #646970;
}

.dcf-metric-value {
    font-size: 12px;
    font-weight: 600;
}

.dcf-metric-value.dcf-positive {
    color: #00a32a;
}

.dcf-metric-value.dcf-negative {
    color: #d63638;
}

.dcf-recommendations {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.dcf-recommendations h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.dcf-recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.dcf-recommendation-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid;
}

.dcf-recommendation-success {
    background: #f0f9ff;
    border-left-color: #00a32a;
}

.dcf-recommendation-warning {
    background: #fff9e6;
    border-left-color: #ffc107;
}

.dcf-recommendation-info {
    background: #f0f9ff;
    border-left-color: #2271b1;
}

.dcf-recommendation-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.dcf-recommendation-content h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
}

.dcf-recommendation-content p {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #646970;
}

.dcf-recommendation-action {
    font-size: 12px;
}

@media (max-width: 1200px) {
    .dcf-charts-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dcf-analytics-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .dcf-test-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .dcf-metrics-overview {
        grid-template-columns: 1fr;
    }
    
    .dcf-chart-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .dcf-comparisons-grid {
        grid-template-columns: 1fr;
    }
    
    .dcf-result-metrics {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</rewritten_file> 