<?php
/**
 * Popup Preview View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Convert form object to array if needed
$form_array = null;
if ($form) {
    $form_array = is_object($form) ? (array) $form : $form;
}
?>

<div class="dcf-popup-preview-page">
    <div class="dcf-popup-preview-header">
        <h1><?php echo esc_html__('Popup Preview', 'dry-cleaning-forms'); ?></h1>
        <div class="dcf-preview-actions">
            <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id); ?>" class="button">
                <?php echo esc_html__('← Back to Edit', 'dry-cleaning-forms'); ?>
            </a>
        </div>
    </div>

    <div class="dcf-popup-preview-info">
        <h2><?php echo esc_html($popup['popup_name']); ?></h2>
        <div class="dcf-popup-meta">
            <span class="dcf-preview-status dcf-status-<?php echo esc_attr($popup['status']); ?>">
                <?php echo esc_html(ucfirst($popup['status'])); ?>
            </span>
            <span class="dcf-preview-type">
                <?php echo esc_html(ucfirst($popup['popup_type'])); ?>
            </span>
            <?php if (!empty($popup_config['form_id']) && $form_array): ?>
                <span class="dcf-preview-form">
                    Form: <?php echo esc_html($form_array['form_name']); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="dcf-popup-preview-container">
        <div class="dcf-preview-backdrop">
            <div class="dcf-popup-preview dcf-popup-type-<?php echo esc_attr($popup['popup_type']); ?>">
                <div class="dcf-popup-header">
                    <h3><?php echo esc_html($popup['popup_name']); ?></h3>
                    <button class="dcf-popup-close" type="button">×</button>
                </div>
                
                <div class="dcf-popup-content">
                    <?php if (!empty($form_html)): ?>
                        <?php echo $form_html; ?>
                    <?php else: ?>
                        <div class="dcf-no-form-message">
                            <p><?php echo esc_html__('No form selected for this popup.', 'dry-cleaning-forms'); ?></p>
                            <p><a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id); ?>">
                                <?php echo esc_html__('Select a form to display in this popup.', 'dry-cleaning-forms'); ?>
                            </a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="dcf-popup-preview-details">
        <h3><?php echo esc_html__('Popup Configuration', 'dry-cleaning-forms'); ?></h3>
        
        <div class="dcf-config-grid">
            <div class="dcf-config-section">
                <h4><?php echo esc_html__('Basic Settings', 'dry-cleaning-forms'); ?></h4>
                <ul>
                    <li><strong><?php echo esc_html__('Type:', 'dry-cleaning-forms'); ?></strong> <?php echo esc_html(ucfirst($popup['popup_type'])); ?></li>
                    <li><strong><?php echo esc_html__('Status:', 'dry-cleaning-forms'); ?></strong> <?php echo esc_html(ucfirst($popup['status'])); ?></li>
                    <?php if (!empty($popup_config['auto_close'])): ?>
                        <li><strong><?php echo esc_html__('Auto Close:', 'dry-cleaning-forms'); ?></strong> 
                            <?php echo esc_html(sprintf(__('After %d seconds', 'dry-cleaning-forms'), $popup_config['auto_close_delay'] ?? 5)); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if (!empty($trigger_settings)): ?>
                <div class="dcf-config-section">
                    <h4><?php echo esc_html__('Trigger Settings', 'dry-cleaning-forms'); ?></h4>
                    <ul>
                        <?php if (!empty($trigger_settings['trigger_type'])): ?>
                            <li><strong><?php echo esc_html__('Trigger:', 'dry-cleaning-forms'); ?></strong> 
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $trigger_settings['trigger_type']))); ?>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($trigger_settings['time_delay'])): ?>
                            <li><strong><?php echo esc_html__('Delay:', 'dry-cleaning-forms'); ?></strong> 
                                <?php echo esc_html(sprintf(__('%d seconds', 'dry-cleaning-forms'), $trigger_settings['time_delay'])); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($popup_config['form_id']) && $form_array): ?>
                <div class="dcf-config-section">
                    <h4><?php echo esc_html__('Form Details', 'dry-cleaning-forms'); ?></h4>
                    <ul>
                        <li><strong><?php echo esc_html__('Form Name:', 'dry-cleaning-forms'); ?></strong> <?php echo esc_html($form_array['form_name']); ?></li>
                        <li><strong><?php echo esc_html__('Form Type:', 'dry-cleaning-forms'); ?></strong> <?php echo esc_html(ucfirst($form_array['form_type'])); ?></li>
                        <?php 
                        $form_config = is_string($form_array['form_config']) ? json_decode($form_array['form_config'], true) : $form_array['form_config'];
                        if ($form_config && isset($form_config['fields'])): 
                        ?>
                            <li><strong><?php echo esc_html__('Fields:', 'dry-cleaning-forms'); ?></strong> 
                                <?php echo esc_html(sprintf(__('%d fields', 'dry-cleaning-forms'), count($form_config['fields']))); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dcf-popup-preview-page {
    background: #f1f1f1;
    margin: -20px -20px -20px -2px;
    padding: 20px;
    min-height: 100vh;
}

.dcf-popup-preview-header {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dcf-popup-preview-header h1 {
    margin: 0;
    color: #1d2327;
}

.dcf-popup-preview-info {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dcf-popup-preview-info h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.dcf-popup-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.dcf-popup-meta span {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.dcf-preview-status {
    background: #f0f0f1;
    color: #50575e;
}

.dcf-status-active {
    background: #00a32a !important;
    color: #fff !important;
}

.dcf-status-draft {
    background: #dba617 !important;
    color: #fff !important;
}

.dcf-preview-type {
    background: #2271b1;
    color: #fff;
}

.dcf-preview-form {
    background: #8c8f94;
    color: #fff;
}

.dcf-popup-preview-container {
    background: #fff;
    border-radius: 8px;
    padding: 40px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    position: relative;
    min-height: 400px;
}

.dcf-preview-backdrop {
    position: relative;
    background: rgba(0,0,0,0.5);
    border-radius: 4px;
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
}

.dcf-popup-preview {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 100%;
    position: relative;
}

.dcf-popup-header {
    padding: 20px 20px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.dcf-popup-header h3 {
    margin: 0;
    color: #1d2327;
    font-size: 18px;
}

.dcf-popup-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-popup-close:hover {
    color: #000;
}

.dcf-popup-content {
    padding: 20px;
}

.dcf-preview-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.dcf-field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dcf-field-wrapper label {
    font-weight: 500;
    color: #1d2327;
}

.dcf-field-wrapper input,
.dcf-field-wrapper textarea,
.dcf-field-wrapper select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.dcf-name-fields,
.dcf-address-row {
    display: flex;
    gap: 10px;
}

.dcf-address-fields {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dcf-submit-btn {
    background: #2271b1;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.dcf-submit-btn:hover {
    background: #135e96;
}

.dcf-no-form-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.dcf-no-form-message p {
    margin: 10px 0;
}

.dcf-popup-preview-details {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dcf-popup-preview-details h3 {
    margin: 0 0 20px 0;
    color: #1d2327;
}

.dcf-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.dcf-config-section h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dcf-config-section ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.dcf-config-section li {
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f1;
}

.dcf-config-section li:last-child {
    border-bottom: none;
}
</style> 