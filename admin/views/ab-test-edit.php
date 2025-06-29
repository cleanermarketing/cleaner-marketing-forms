<?php
/**
 * A/B Test Edit View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize managers
$ab_testing_manager = new DCF_AB_Testing_Manager();
$popup_manager = new DCF_Popup_Manager();

// Get test data if editing
$test = null;
$is_new = $action === 'ab_test_new';
$test_id = intval($_GET['test_id'] ?? 0);

if (!$is_new && $test_id) {
    $test = $ab_testing_manager->get_ab_test($test_id);
    if (!$test) {
        wp_die(__('A/B test not found.', 'dry-cleaning-forms'));
    }
}

// Get available popups for variants
$available_popups = $popup_manager->get_popups(array(
    'status' => array('active', 'draft'),
    'limit' => 100
));

// Default values
$defaults = array(
    'test_name' => '',
    'popup_ids' => array(),
    'traffic_split' => array(),
    'start_date' => current_time('Y-m-d'),
    'end_date' => '',
    'status' => 'draft',
    'test_type' => 'conversion',
    'minimum_sample_size' => 100,
    'confidence_level' => 95.0,
    'auto_declare_winner' => true
);

$test_data = $test ? array_merge($defaults, $test) : $defaults;
if (isset($test_data['test_config'])) {
    $test_data = array_merge($test_data, $test_data['test_config']);
}

?>

<div class="dcf-ab-test-edit">
    <form method="post" action="" id="ab-test-edit-form">
        <?php wp_nonce_field('dcf_ab_test_action', 'dcf_ab_test_nonce'); ?>
        <input type="hidden" name="ab_test_action" value="<?php echo $is_new ? 'create' : 'update'; ?>">
        <?php if (!$is_new): ?>
            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
        <?php endif; ?>
        
        <div class="dcf-test-header">
            <div class="dcf-test-title">
                <input type="text" name="test_name" value="<?php echo esc_attr($test_data['test_name']); ?>" 
                       placeholder="<?php _e('Enter test name...', 'dry-cleaning-forms'); ?>" 
                       class="dcf-test-name-input" required>
            </div>
            
            <div class="dcf-test-actions">
                <select name="status" class="dcf-status-select">
                    <option value="draft" <?php selected($test_data['status'], 'draft'); ?>><?php _e('Draft', 'dry-cleaning-forms'); ?></option>
                    <option value="active" <?php selected($test_data['status'], 'active'); ?>><?php _e('Active', 'dry-cleaning-forms'); ?></option>
                    <option value="paused" <?php selected($test_data['status'], 'paused'); ?>><?php _e('Paused', 'dry-cleaning-forms'); ?></option>
                </select>
                
                <button type="submit" class="button button-primary">
                    <?php echo $is_new ? __('Create Test', 'dry-cleaning-forms') : __('Update Test', 'dry-cleaning-forms'); ?>
                </button>
                
                <?php if (!$is_new): ?>
                    <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=ab_test_analytics&test_id=' . $test_id); ?>" 
                       class="button">
                        <?php _e('View Analytics', 'dry-cleaning-forms'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dcf-test-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#variants" class="nav-tab nav-tab-active"><?php _e('Variants', 'dry-cleaning-forms'); ?></a>
                <a href="#settings" class="nav-tab"><?php _e('Settings', 'dry-cleaning-forms'); ?></a>
                <a href="#schedule" class="nav-tab"><?php _e('Schedule', 'dry-cleaning-forms'); ?></a>
                <?php if (!$is_new): ?>
                    <a href="#results" class="nav-tab"><?php _e('Results', 'dry-cleaning-forms'); ?></a>
                <?php endif; ?>
            </nav>
            
            <!-- Variants Tab -->
            <div id="variants" class="dcf-tab-content dcf-tab-active">
                <div class="dcf-test-card">
                    <h3><?php _e('Test Variants', 'dry-cleaning-forms'); ?></h3>
                    <p class="description">
                        <?php _e('Select the popup variants you want to test against each other. Each variant will be shown to a percentage of your visitors based on the traffic split you configure.', 'dry-cleaning-forms'); ?>
                    </p>
                    
                    <div id="variants-container">
                        <?php if (!empty($test_data['popup_ids'])): ?>
                            <?php foreach ($test_data['popup_ids'] as $index => $popup_id): ?>
                                <?php
                                $popup = $popup_manager->get_popup($popup_id);
                                $traffic_split = $test_data['traffic_split'][$index] ?? 50;
                                ?>
                                <div class="dcf-variant-row" data-index="<?php echo $index; ?>">
                                    <div class="dcf-variant-info">
                                        <label><?php printf(__('Variant %s', 'dry-cleaning-forms'), chr(65 + $index)); ?></label>
                                        <select name="popup_ids[]" class="dcf-popup-select" required>
                                            <option value=""><?php _e('Select a popup...', 'dry-cleaning-forms'); ?></option>
                                            <?php foreach ($available_popups as $available_popup): ?>
                                                <option value="<?php echo $available_popup['id']; ?>" 
                                                        <?php selected($popup_id, $available_popup['id']); ?>>
                                                    <?php echo esc_html($available_popup['popup_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="dcf-traffic-split">
                                        <label><?php _e('Traffic %', 'dry-cleaning-forms'); ?></label>
                                        <input type="number" name="traffic_split[]" value="<?php echo $traffic_split; ?>" 
                                               min="1" max="100" class="dcf-traffic-input" required>
                                    </div>
                                    
                                    <div class="dcf-variant-actions">
                                        <?php if ($popup): ?>
                                            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id); ?>" 
                                               class="button button-small" target="_blank">
                                                <?php _e('Edit Popup', 'dry-cleaning-forms'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="button button-small dcf-remove-variant">
                                            <?php _e('Remove', 'dry-cleaning-forms'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default two variants -->
                            <div class="dcf-variant-row" data-index="0">
                                <div class="dcf-variant-info">
                                    <label><?php _e('Variant A', 'dry-cleaning-forms'); ?></label>
                                    <select name="popup_ids[]" class="dcf-popup-select" required>
                                        <option value=""><?php _e('Select a popup...', 'dry-cleaning-forms'); ?></option>
                                        <?php foreach ($available_popups as $popup): ?>
                                            <option value="<?php echo $popup['id']; ?>">
                                                <?php echo esc_html($popup['popup_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="dcf-traffic-split">
                                    <label><?php _e('Traffic %', 'dry-cleaning-forms'); ?></label>
                                    <input type="number" name="traffic_split[]" value="50" 
                                           min="1" max="100" class="dcf-traffic-input" required>
                                </div>
                                
                                <div class="dcf-variant-actions">
                                    <button type="button" class="button button-small dcf-remove-variant">
                                        <?php _e('Remove', 'dry-cleaning-forms'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="dcf-variant-row" data-index="1">
                                <div class="dcf-variant-info">
                                    <label><?php _e('Variant B', 'dry-cleaning-forms'); ?></label>
                                    <select name="popup_ids[]" class="dcf-popup-select" required>
                                        <option value=""><?php _e('Select a popup...', 'dry-cleaning-forms'); ?></option>
                                        <?php foreach ($available_popups as $popup): ?>
                                            <option value="<?php echo $popup['id']; ?>">
                                                <?php echo esc_html($popup['popup_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="dcf-traffic-split">
                                    <label><?php _e('Traffic %', 'dry-cleaning-forms'); ?></label>
                                    <input type="number" name="traffic_split[]" value="50" 
                                           min="1" max="100" class="dcf-traffic-input" required>
                                </div>
                                
                                <div class="dcf-variant-actions">
                                    <button type="button" class="button button-small dcf-remove-variant">
                                        <?php _e('Remove', 'dry-cleaning-forms'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dcf-variants-controls">
                        <button type="button" id="add-variant" class="button">
                            <?php _e('Add Variant', 'dry-cleaning-forms'); ?>
                        </button>
                        
                        <div class="dcf-traffic-total">
                            <span><?php _e('Total Traffic:', 'dry-cleaning-forms'); ?></span>
                            <span id="traffic-total">100</span>%
                            <span id="traffic-warning" class="dcf-warning" style="display: none;">
                                <?php _e('Traffic split must equal 100%', 'dry-cleaning-forms'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tab -->
            <div id="settings" class="dcf-tab-content">
                <div class="dcf-test-card">
                    <h3><?php _e('Test Configuration', 'dry-cleaning-forms'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_type"><?php _e('Test Type', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <select name="test_type" id="test_type" class="regular-text">
                                    <option value="conversion" <?php selected($test_data['test_type'], 'conversion'); ?>>
                                        <?php _e('Conversion Rate', 'dry-cleaning-forms'); ?>
                                    </option>
                                    <option value="engagement" <?php selected($test_data['test_type'], 'engagement'); ?>>
                                        <?php _e('Engagement Rate', 'dry-cleaning-forms'); ?>
                                    </option>
                                    <option value="click_through" <?php selected($test_data['test_type'], 'click_through'); ?>>
                                        <?php _e('Click-Through Rate', 'dry-cleaning-forms'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('The primary metric to optimize for in this test.', 'dry-cleaning-forms'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="minimum_sample_size"><?php _e('Minimum Sample Size', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="minimum_sample_size" id="minimum_sample_size" 
                                       value="<?php echo esc_attr($test_data['minimum_sample_size']); ?>" 
                                       min="50" max="10000" class="regular-text">
                                <p class="description">
                                    <?php _e('Minimum number of visitors per variant before statistical analysis can be performed.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="confidence_level"><?php _e('Confidence Level', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <select name="confidence_level" id="confidence_level" class="regular-text">
                                    <option value="90" <?php selected($test_data['confidence_level'], 90); ?>>90%</option>
                                    <option value="95" <?php selected($test_data['confidence_level'], 95); ?>>95%</option>
                                    <option value="99" <?php selected($test_data['confidence_level'], 99); ?>>99%</option>
                                </select>
                                <p class="description">
                                    <?php _e('Statistical confidence level required to declare a winner.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Auto-Declare Winner', 'dry-cleaning-forms'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_declare_winner" value="1" 
                                           <?php checked($test_data['auto_declare_winner']); ?>>
                                    <?php _e('Automatically declare winner when statistical significance is reached', 'dry-cleaning-forms'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, the test will automatically end and declare a winner once statistical significance is achieved.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Schedule Tab -->
            <div id="schedule" class="dcf-tab-content">
                <div class="dcf-test-card">
                    <h3><?php _e('Test Schedule', 'dry-cleaning-forms'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="start_date"><?php _e('Start Date', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="date" name="start_date" id="start_date" 
                                       value="<?php echo esc_attr($test_data['start_date']); ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('When should this test begin?', 'dry-cleaning-forms'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="end_date"><?php _e('End Date', 'dry-cleaning-forms'); ?></label>
                            </th>
                            <td>
                                <input type="date" name="end_date" id="end_date" 
                                       value="<?php echo esc_attr($test_data['end_date']); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('Optional: Set an end date for the test. Leave blank to run indefinitely.', 'dry-cleaning-forms'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Results Tab -->
            <?php if (!$is_new && $test): ?>
                <div id="results" class="dcf-tab-content">
                    <div class="dcf-test-card">
                        <h3><?php _e('Test Results', 'dry-cleaning-forms'); ?></h3>
                        
                        <?php if (!empty($test['variants'])): ?>
                            <div class="dcf-results-summary">
                                <?php
                                $best_variant = null;
                                $best_rate = 0;
                                
                                foreach ($test['variants'] as $variant) {
                                    if ($variant['conversion_rate'] > $best_rate) {
                                        $best_rate = $variant['conversion_rate'];
                                        $best_variant = $variant;
                                    }
                                }
                                ?>
                                
                                <div class="dcf-variants-comparison">
                                    <?php foreach ($test['variants'] as $index => $variant): ?>
                                        <div class="dcf-variant-result <?php echo $variant['is_winner'] ? 'dcf-winner' : ''; ?>">
                                            <div class="dcf-variant-header">
                                                <h4><?php echo esc_html($variant['popup_name']); ?></h4>
                                                <?php if ($variant['is_winner']): ?>
                                                    <span class="dcf-winner-badge">
                                                        <span class="dashicons dashicons-awards"></span>
                                                        <?php _e('Winner', 'dry-cleaning-forms'); ?>
                                                    </span>
                                                <?php elseif ($variant === $best_variant): ?>
                                                    <span class="dcf-leading-badge">
                                                        <span class="dashicons dashicons-chart-line"></span>
                                                        <?php _e('Leading', 'dry-cleaning-forms'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="dcf-variant-metrics">
                                                <div class="dcf-metric">
                                                    <span class="dcf-metric-value"><?php echo number_format($variant['displays']); ?></span>
                                                    <span class="dcf-metric-label"><?php _e('Displays', 'dry-cleaning-forms'); ?></span>
                                                </div>
                                                <div class="dcf-metric">
                                                    <span class="dcf-metric-value"><?php echo number_format($variant['conversions']); ?></span>
                                                    <span class="dcf-metric-label"><?php _e('Conversions', 'dry-cleaning-forms'); ?></span>
                                                </div>
                                                <div class="dcf-metric dcf-metric-primary">
                                                    <span class="dcf-metric-value"><?php echo $variant['conversion_rate']; ?>%</span>
                                                    <span class="dcf-metric-label"><?php _e('Conversion Rate', 'dry-cleaning-forms'); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="dcf-variant-progress">
                                                <div class="dcf-progress-bar">
                                                    <div class="dcf-progress-fill" 
                                                         style="width: <?php echo min(100, ($variant['conversion_rate'] / max(1, $best_rate)) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($test['variants']) >= 2): ?>
                                    <div class="dcf-statistical-analysis">
                                        <h4><?php _e('Statistical Analysis', 'dry-cleaning-forms'); ?></h4>
                                        <?php
                                        $variant_a = $test['variants'][0];
                                        $variant_b = $test['variants'][1];
                                        $stats = $ab_testing_manager->calculate_statistical_significance($variant_a, $variant_b);
                                        ?>
                                        
                                        <div class="dcf-stats-result <?php echo $stats['significant'] ? 'dcf-significant' : 'dcf-not-significant'; ?>">
                                            <div class="dcf-stats-header">
                                                <span class="dcf-stats-icon">
                                                    <?php if ($stats['significant']): ?>
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                    <?php else: ?>
                                                        <span class="dashicons dashicons-warning"></span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="dcf-stats-message"><?php echo esc_html($stats['message']); ?></span>
                                            </div>
                                            
                                            <div class="dcf-stats-details">
                                                <div class="dcf-stat-item">
                                                    <span class="dcf-stat-label"><?php _e('Confidence:', 'dry-cleaning-forms'); ?></span>
                                                    <span class="dcf-stat-value"><?php echo $stats['confidence']; ?>%</span>
                                                </div>
                                                <div class="dcf-stat-item">
                                                    <span class="dcf-stat-label"><?php _e('P-Value:', 'dry-cleaning-forms'); ?></span>
                                                    <span class="dcf-stat-value"><?php echo $stats['p_value']; ?></span>
                                                </div>
                                                <?php if ($stats['improvement'] != 0): ?>
                                                    <div class="dcf-stat-item">
                                                        <span class="dcf-stat-label"><?php _e('Improvement:', 'dry-cleaning-forms'); ?></span>
                                                        <span class="dcf-stat-value"><?php echo $stats['improvement']; ?>%</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($test['status'] === 'active' && !$test['winner_id']): ?>
                                    <div class="dcf-declare-winner">
                                        <h4><?php _e('Declare Winner', 'dry-cleaning-forms'); ?></h4>
                                        <p><?php _e('You can manually declare a winner at any time, even if statistical significance has not been reached.', 'dry-cleaning-forms'); ?></p>
                                        
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('dcf_ab_test_action', 'dcf_ab_test_nonce'); ?>
                                            <input type="hidden" name="ab_test_action" value="declare_winner">
                                            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                                            
                                            <select name="winner_id" required>
                                                <option value=""><?php _e('Select winner...', 'dry-cleaning-forms'); ?></option>
                                                <?php foreach ($test['variants'] as $variant): ?>
                                                    <option value="<?php echo $variant['popup_id']; ?>">
                                                        <?php echo esc_html($variant['popup_name']); ?> 
                                                        (<?php echo $variant['conversion_rate']; ?>%)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <button type="submit" class="button button-primary" 
                                                    onclick="return confirm('<?php _e('Are you sure you want to declare this variant as the winner? This will end the test.', 'dry-cleaning-forms'); ?>')">
                                                <?php _e('Declare Winner', 'dry-cleaning-forms'); ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="dcf-no-results">
                                <?php _e('No test data available yet. Start the test to begin collecting data.', 'dry-cleaning-forms'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var variantIndex = <?php echo !empty($test_data['popup_ids']) ? count($test_data['popup_ids']) : 2; ?>;
    
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // Update tab states
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update content states
        $('.dcf-tab-content').removeClass('dcf-tab-active');
        $(target).addClass('dcf-tab-active');
    });
    
    // Add variant
    $('#add-variant').on('click', function() {
        var variantLetter = String.fromCharCode(65 + variantIndex);
        var newVariant = `
            <div class="dcf-variant-row" data-index="${variantIndex}">
                <div class="dcf-variant-info">
                    <label><?php _e('Variant', 'dry-cleaning-forms'); ?> ${variantLetter}</label>
                    <select name="popup_ids[]" class="dcf-popup-select" required>
                        <option value=""><?php _e('Select a popup...', 'dry-cleaning-forms'); ?></option>
                        <?php foreach ($available_popups as $popup): ?>
                            <option value="<?php echo $popup['id']; ?>">
                                <?php echo esc_html($popup['popup_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="dcf-traffic-split">
                    <label><?php _e('Traffic %', 'dry-cleaning-forms'); ?></label>
                    <input type="number" name="traffic_split[]" value="0" 
                           min="1" max="100" class="dcf-traffic-input" required>
                </div>
                
                <div class="dcf-variant-actions">
                    <button type="button" class="button button-small dcf-remove-variant">
                        <?php _e('Remove', 'dry-cleaning-forms'); ?>
                    </button>
                </div>
            </div>
        `;
        
        $('#variants-container').append(newVariant);
        variantIndex++;
        updateTrafficTotal();
    });
    
    // Remove variant
    $(document).on('click', '.dcf-remove-variant', function() {
        if ($('.dcf-variant-row').length > 2) {
            $(this).closest('.dcf-variant-row').remove();
            updateTrafficTotal();
        } else {
            alert('<?php _e('You must have at least 2 variants for an A/B test.', 'dry-cleaning-forms'); ?>');
        }
    });
    
    // Update traffic total when inputs change
    $(document).on('input', '.dcf-traffic-input', function() {
        updateTrafficTotal();
    });
    
    function updateTrafficTotal() {
        var total = 0;
        $('.dcf-traffic-input').each(function() {
            total += parseInt($(this).val()) || 0;
        });
        
        $('#traffic-total').text(total);
        
        if (total !== 100) {
            $('#traffic-warning').show();
            $('#traffic-total').addClass('dcf-error');
        } else {
            $('#traffic-warning').hide();
            $('#traffic-total').removeClass('dcf-error');
        }
    }
    
    // Form validation
    $('#ab-test-edit-form').on('submit', function(e) {
        var total = 0;
        $('.dcf-traffic-input').each(function() {
            total += parseInt($(this).val()) || 0;
        });
        
        if (total !== 100) {
            e.preventDefault();
            alert('<?php _e('Traffic split must equal 100% before you can save the test.', 'dry-cleaning-forms'); ?>');
            return false;
        }
        
        // Check for duplicate popups
        var popupIds = [];
        var hasDuplicates = false;
        
        $('.dcf-popup-select').each(function() {
            var value = $(this).val();
            if (value && popupIds.includes(value)) {
                hasDuplicates = true;
                return false;
            }
            if (value) {
                popupIds.push(value);
            }
        });
        
        if (hasDuplicates) {
            e.preventDefault();
            alert('<?php _e('Each variant must use a different popup. Please select unique popups for each variant.', 'dry-cleaning-forms'); ?>');
            return false;
        }
    });
    
    // Initialize traffic total
    updateTrafficTotal();
});
</script>

<style>
.dcf-ab-test-edit {
    max-width: 1200px;
}

.dcf-test-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dcf-test-name-input {
    font-size: 20px;
    font-weight: 600;
    border: none;
    background: none;
    width: 100%;
    max-width: 500px;
}

.dcf-test-name-input:focus {
    outline: 2px solid #2271b1;
    outline-offset: 2px;
}

.dcf-test-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.dcf-status-select {
    min-width: 100px;
}

.dcf-test-tabs {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.dcf-tab-content {
    display: none;
    padding: 20px;
}

.dcf-tab-content.dcf-tab-active {
    display: block;
}

.dcf-test-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.dcf-test-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.dcf-variant-row {
    display: flex;
    align-items: end;
    gap: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 10px;
}

.dcf-variant-info {
    flex: 2;
}

.dcf-variant-info label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.dcf-popup-select {
    width: 100%;
}

.dcf-traffic-split {
    flex: 1;
}

.dcf-traffic-split label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.dcf-traffic-input {
    width: 100%;
}

.dcf-variant-actions {
    flex: 1;
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.dcf-variants-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.dcf-traffic-total {
    font-weight: 600;
}

.dcf-traffic-total #traffic-total.dcf-error {
    color: #d63638;
}

.dcf-warning {
    color: #d63638;
    font-size: 12px;
    margin-left: 10px;
}

.dcf-results-summary {
    margin-top: 20px;
}

.dcf-variants-comparison {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dcf-variant-result {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.dcf-variant-result.dcf-winner {
    border-color: #00a32a;
    background: #f0f9ff;
}

.dcf-variant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.dcf-variant-header h4 {
    margin: 0;
}

.dcf-winner-badge {
    background: #00a32a;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.dcf-leading-badge {
    background: #2271b1;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.dcf-variant-metrics {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.dcf-metric {
    text-align: center;
}

.dcf-metric-value {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
}

.dcf-metric-primary .dcf-metric-value {
    font-size: 24px;
    color: #2271b1;
}

.dcf-metric-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-top: 3px;
}

.dcf-variant-progress {
    margin-top: 10px;
}

.dcf-progress-bar {
    width: 100%;
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
}

.dcf-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #72aee6);
    transition: width 0.3s ease;
}

.dcf-statistical-analysis {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.dcf-stats-result {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.dcf-stats-result.dcf-significant {
    background: #d1e7dd;
    border: 1px solid #00a32a;
}

.dcf-stats-result.dcf-not-significant {
    background: #fff3cd;
    border: 1px solid #ffc107;
}

.dcf-stats-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.dcf-stats-message {
    font-weight: 600;
}

.dcf-stats-details {
    display: flex;
    gap: 20px;
}

.dcf-stat-item {
    display: flex;
    flex-direction: column;
}

.dcf-stat-label {
    font-size: 12px;
    color: #646970;
}

.dcf-stat-value {
    font-weight: 600;
}

.dcf-declare-winner {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.dcf-declare-winner h4 {
    margin-top: 0;
}

.dcf-no-results {
    text-align: center;
    color: #646970;
    font-style: italic;
    padding: 40px 20px;
}

@media (max-width: 768px) {
    .dcf-test-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .dcf-variant-row {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .dcf-variants-controls {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .dcf-variants-comparison {
        grid-template-columns: 1fr;
    }
    
    .dcf-stats-details {
        flex-direction: column;
        gap: 10px;
    }
}
</style> 