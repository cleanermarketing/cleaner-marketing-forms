<?php
/**
 * Popup Templates Admin View
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize template manager
$template_manager = new DCF_Popup_Template_Manager();
$templates = $template_manager->get_templates();
$categories = $template_manager->get_categories();

// Handle template selection
if (isset($_POST['create_from_template']) && wp_verify_nonce($_POST['_wpnonce'], 'dcf_create_popup_template')) {
    $template_id = sanitize_text_field($_POST['template_id']);
    $popup_name = sanitize_text_field($_POST['popup_name']);
    
    if ($template_id && $popup_name) {
        try {
            $popup_data = $template_manager->create_popup_from_template($template_id, array(
                'name' => $popup_name
            ));
            
            if ($popup_data) {
                $popup_manager = new DCF_Popup_Manager();
                $popup_id = $popup_manager->create_popup($popup_data);
                
                if ($popup_id && !is_wp_error($popup_id)) {
                    wp_redirect(admin_url('admin.php?page=cmf-popup-manager&action=edit&popup_id=' . $popup_id . '&created=1'));
                    exit;
                } else {
                    $error_message = is_wp_error($popup_id) ? $popup_id->get_error_message() : 'Failed to create popup';
                    error_log('DCF Template Error: ' . $error_message);
                    wp_die('Error creating popup: ' . $error_message);
                }
            } else {
                error_log('DCF Template Error: Failed to create popup data from template ' . $template_id);
                wp_die('Error: Failed to create popup data from template');
            }
        } catch (Exception $e) {
            error_log('DCF Template Exception: ' . $e->getMessage());
            wp_die('Error: ' . $e->getMessage());
        }
    } else {
        wp_die('Error: Missing template ID or popup name');
    }
}
?>

<div class="wrap dcf-popup-templates">
    <h1 class="wp-heading-inline">
        <?php _e('Popup Templates', 'dry-cleaning-forms'); ?>
        <span class="title-count"><?php echo count($templates); ?></span>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=cmf-popup-manager&action=new'); ?>" class="page-title-action">
        <?php _e('Create Blank Popup', 'dry-cleaning-forms'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="dcf-templates-header">
        <p class="description">
            <?php _e('Choose from professionally designed popup templates to get started quickly. Each template can be fully customized to match your brand and goals.', 'dry-cleaning-forms'); ?>
        </p>
        
        <div class="dcf-template-filters">
            <button class="dcf-filter-btn active" data-category="all">
                <?php _e('All Templates', 'dry-cleaning-forms'); ?>
            </button>
            <?php foreach ($categories as $category): ?>
                <button class="dcf-filter-btn" data-category="<?php echo esc_attr($category); ?>">
                    <?php echo esc_html(ucfirst($category)); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="dcf-templates-grid">
        <?php foreach ($templates as $template_id => $template): ?>
            <div class="dcf-template-card" data-category="<?php echo esc_attr($template['category']); ?>">
                <div class="dcf-template-preview-container">
                    <?php echo $template_manager->get_template_preview($template_id); ?>
                </div>
                
                <div class="dcf-template-info">
                    <h3 class="dcf-template-name"><?php echo esc_html($template['name']); ?></h3>
                    <p class="dcf-template-description"><?php echo esc_html($template['description']); ?></p>
                    
                    <div class="dcf-template-meta">
                        <span class="dcf-template-type"><?php echo esc_html(ucfirst($template['type'])); ?></span>
                        <span class="dcf-template-category"><?php echo esc_html(ucfirst($template['category'])); ?></span>
                    </div>
                    
                    <div class="dcf-template-actions">
                        <button class="button button-primary dcf-use-template" data-template="<?php echo esc_attr($template_id); ?>">
                            <?php _e('Use This Template', 'dry-cleaning-forms'); ?>
                        </button>
                        <button class="button dcf-preview-template" data-template="<?php echo esc_attr($template_id); ?>">
                            <?php _e('Preview', 'dry-cleaning-forms'); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Template Selection Modal -->
<div id="dcf-template-modal" class="dcf-modal">
    <div class="dcf-modal-content">
        <div class="dcf-modal-header">
            <h2><?php _e('Create Popup from Template', 'dry-cleaning-forms'); ?></h2>
            <button class="dcf-modal-close">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('dcf_create_popup_template'); ?>
            <input type="hidden" name="template_id" id="selected-template-id">
            
            <div class="dcf-modal-body">
                <div class="dcf-field-group">
                    <label for="popup_name"><?php _e('Popup Name', 'dry-cleaning-forms'); ?></label>
                    <input type="text" id="popup_name" name="popup_name" class="regular-text" required 
                           placeholder="<?php _e('Enter a name for your popup...', 'dry-cleaning-forms'); ?>">
                    <p class="description"><?php _e('Give your popup a descriptive name for easy identification.', 'dry-cleaning-forms'); ?></p>
                </div>
                
                <div class="dcf-template-preview-large" id="template-preview-large">
                    <!-- Template preview will be loaded here -->
                </div>
            </div>
            
            <div class="dcf-modal-footer">
                <button type="button" class="button button-secondary dcf-modal-close"><?php _e('Cancel', 'dry-cleaning-forms'); ?></button>
                <button type="submit" name="create_from_template" class="button button-primary">
                    <?php _e('Create Popup', 'dry-cleaning-forms'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template Preview Modal -->
<div id="dcf-preview-modal" class="dcf-modal">
    <div class="dcf-modal-content dcf-preview-modal-content">
        <div class="dcf-modal-header">
            <h2 id="preview-template-name"><?php _e('Template Preview', 'dry-cleaning-forms'); ?></h2>
            <button class="dcf-modal-close">&times;</button>
        </div>
        
        <div class="dcf-modal-body">
            <div class="dcf-template-preview-full" id="template-preview-full">
                <!-- Full template preview will be loaded here -->
            </div>
        </div>
        
        <div class="dcf-modal-footer">
            <button type="button" class="button button-secondary dcf-modal-close"><?php _e('Close', 'dry-cleaning-forms'); ?></button>
            <button type="button" class="button button-primary dcf-use-template-from-preview">
                <?php _e('Use This Template', 'dry-cleaning-forms'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.dcf-popup-templates {
    max-width: 1200px;
}

.dcf-templates-header {
    margin: 20px 0 30px 0;
}

.dcf-template-filters {
    margin-top: 15px;
}

.dcf-filter-btn {
    background: #f1f1f1;
    border: 1px solid #ddd;
    padding: 8px 16px;
    margin-right: 10px;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.dcf-filter-btn:hover,
.dcf-filter-btn.active {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.dcf-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.dcf-template-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dcf-template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dcf-template-card.hidden {
    display: none;
}

.dcf-template-preview-container {
    height: 200px;
    background: #f8f9fa;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid #eee;
}

.dcf-template-preview {
    transform: scale(0.6);
    transform-origin: top left;
    width: 166.67%;
    height: 166.67%;
}

.dcf-template-info {
    padding: 20px;
}

.dcf-template-name {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
}

.dcf-template-description {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.dcf-template-meta {
    margin-bottom: 15px;
}

.dcf-template-type,
.dcf-template-category {
    display: inline-block;
    background: #f1f1f1;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 8px;
    color: #666;
}

.dcf-template-actions {
    display: flex;
    gap: 10px;
}

.dcf-template-actions .button {
    flex: 1;
    text-align: center;
    justify-content: center;
}

/* Modal Styles */
.dcf-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 999999;
    display: none !important; /* Hidden by default */
    align-items: center;
    justify-content: center;
}

.dcf-modal.show {
    display: flex !important;
}

.dcf-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.dcf-preview-modal-content {
    max-width: 800px;
}

.dcf-modal-header {
    padding: 20px 20px 0 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dcf-modal-header h2 {
    margin: 0;
}

.dcf-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-modal-body {
    padding: 20px;
}

.dcf-modal-footer {
    padding: 0 20px 20px 20px;
    text-align: right;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.dcf-modal-footer .button {
    margin-left: 0;
    min-width: 80px;
}

.dcf-field-group {
    margin-bottom: 20px;
}

.dcf-field-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.dcf-template-preview-large,
.dcf-template-preview-full {
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fa;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Template Preview Styles */
.dcf-template-preview {
    position: relative;
    width: 100%;
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.dcf-preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.dcf-preview-popup {
    position: relative;
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.dcf-slide-in-popup {
    position: absolute;
    bottom: 20px;
    right: 20px;
    width: 250px;
    margin: 0;
}

.dcf-sidebar-popup {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 200px;
    margin: 0;
    background: #2271b1;
    color: white;
}

.dcf-exit-intent-popup {
    border: 2px solid #ff6b35;
}

.dcf-multi-step-popup {
    width: 300px;
}

.dcf-preview-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #999;
}

.dcf-preview-headline {
    font-size: 16px;
    font-weight: bold;
    margin: 0 0 8px 0;
    color: inherit;
}

.dcf-preview-subheadline {
    font-size: 14px;
    margin: 0 0 10px 0;
    color: inherit;
}

.dcf-preview-description {
    font-size: 12px;
    margin: 0 0 15px 0;
    color: inherit;
}

.dcf-preview-urgency {
    background: #fff3cd;
    padding: 8px;
    border-radius: 4px;
    font-size: 11px;
    margin-bottom: 10px;
    color: #856404;
}

.dcf-preview-form {
    margin-bottom: 10px;
}

.dcf-preview-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 8px;
}

.dcf-preview-button {
    background: #2271b1;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    width: 100%;
}

.dcf-preview-progress {
    margin-bottom: 15px;
}

.dcf-preview-progress-bar {
    width: 100%;
    height: 4px;
    background: #f0f0f0;
    border-radius: 2px;
    overflow: hidden;
}

.dcf-preview-progress-bar::after {
    content: '';
    display: block;
    width: 33%;
    height: 100%;
    background: #2271b1;
}

.dcf-preview-options {
    margin-bottom: 15px;
}

.dcf-preview-option {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 5px;
    font-size: 12px;
    cursor: pointer;
}

.dcf-preview-option:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .dcf-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .dcf-template-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ensure modals are hidden on page load
    $('#dcf-template-modal, #dcf-preview-modal').removeClass('show').hide();
    
    // Double-check after a short delay to handle any race conditions
    setTimeout(function() {
        $('#dcf-template-modal, #dcf-preview-modal').removeClass('show');
    }, 100);
    
    // Template filtering
    $('.dcf-filter-btn').on('click', function() {
        const category = $(this).data('category');
        
        $('.dcf-filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (category === 'all') {
            $('.dcf-template-card').removeClass('hidden');
        } else {
            $('.dcf-template-card').addClass('hidden');
            $(`.dcf-template-card[data-category="${category}"]`).removeClass('hidden');
        }
    });
    
    // Use template button
    $('.dcf-use-template').on('click', function() {
        const templateId = $(this).data('template');
        const templateName = $(this).closest('.dcf-template-card').find('.dcf-template-name').text();
        
        $('#selected-template-id').val(templateId);
        $('#popup_name').val(templateName);
        
        // Load preview in modal
        loadTemplatePreview(templateId, '#template-preview-large');
        
        $('#dcf-template-modal').addClass('show');
    });
    
    // Preview template button
    $('.dcf-preview-template').on('click', function() {
        const templateId = $(this).data('template');
        const templateName = $(this).closest('.dcf-template-card').find('.dcf-template-name').text();
        
        $('#preview-template-name').text(templateName + ' - Preview');
        
        // Load full preview
        loadTemplatePreview(templateId, '#template-preview-full');
        
        $('#dcf-preview-modal').addClass('show');
        
        // Store template ID for "Use This Template" button
        $('.dcf-use-template-from-preview').data('template', templateId);
    });
    
    // Use template from preview
    $('.dcf-use-template-from-preview').on('click', function() {
        const templateId = $(this).data('template');
        $('#dcf-preview-modal').removeClass('show');
        
        // Trigger the use template flow
        $('#selected-template-id').val(templateId);
        $('#popup_name').val('');
        
        // Load preview in creation modal
        loadTemplatePreview(templateId, '#template-preview-large');
        
        $('#dcf-template-modal').addClass('show');
    });
    
    // Close modals
    $('.dcf-modal-close').on('click', function() {
        $('.dcf-modal').removeClass('show');
    });
    
    // Close modal on overlay click
    $('.dcf-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });
    
    // Function to load template preview via AJAX
    function loadTemplatePreview(templateId, targetSelector) {
        const $target = $(targetSelector);
        $target.html('<div style="text-align: center; padding: 20px;">Loading preview...</div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dcf_get_template_preview',
                template_id: templateId,
                nonce: '<?php echo wp_create_nonce('dcf_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $target.html(response.data);
                } else {
                    $target.html('<div style="text-align: center; padding: 20px; color: #d63638;">Error loading preview: ' + (response.data || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                $target.html('<div style="text-align: center; padding: 20px; color: #d63638;">Error loading preview</div>');
            }
        });
    }
});
</script> 