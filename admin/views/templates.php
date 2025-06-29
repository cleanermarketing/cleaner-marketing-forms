<?php
/**
 * Templates View
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get template types and categories
$template_types = $template_manager->get_template_types();
$template_categories = $template_manager->get_template_categories();

// Get selected type and category from query params
$selected_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : 'all';
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get filtered templates
if ($search_query) {
    $templates = $template_manager->search_templates($search_query);
} elseif ($selected_type !== 'all') {
    $templates = $template_manager->get_templates_by_type($selected_type);
} else {
    $templates = $template_manager->get_all_templates();
}

// Further filter by category if needed
if ($selected_category !== 'all' && !$search_query) {
    $templates = array_filter($templates, function($template) use ($selected_category) {
        return $template['category'] === $selected_category;
    });
}
?>

<div class="wrap dcf-templates-page">
    <h1 class="wp-heading-inline"><?php _e('Templates', 'dry-cleaning-forms'); ?></h1>
    
    <hr class="wp-header-end">
    
    <!-- Template Type Selection -->
    <div class="dcf-template-types">
        <h2><?php _e('Select a Campaign Type:', 'dry-cleaning-forms'); ?></h2>
        <div class="dcf-type-grid">
            <div class="dcf-type-card <?php echo $selected_type === 'all' ? 'selected' : ''; ?>" data-type="all">
                <a href="<?php echo admin_url('admin.php?page=cmf-templates'); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                    <span class="dcf-type-label"><?php _e('All', 'dry-cleaning-forms'); ?></span>
                </a>
            </div>
            <?php foreach ($template_types as $type_key => $type): ?>
                <div class="dcf-type-card <?php echo $selected_type === $type_key ? 'selected' : ''; ?>" data-type="<?php echo esc_attr($type_key); ?>">
                    <a href="<?php echo admin_url('admin.php?page=cmf-templates&type=' . $type_key); ?>" title="<?php echo esc_attr($type['description']); ?>">
                        <span class="<?php echo esc_attr($type['icon']); ?>"></span>
                        <span class="dcf-type-label"><?php echo esc_html($type['label']); ?></span>
                    </a>
                    <?php if ($selected_type === $type_key): ?>
                        <span class="dcf-type-selected">✓</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="dcf-template-filters">
        <div class="dcf-filter-row">
            <div class="dcf-search-box">
                <form method="get" action="">
                    <input type="hidden" name="page" value="dcf-templates">
                    <?php if ($selected_type !== 'all'): ?>
                        <input type="hidden" name="type" value="<?php echo esc_attr($selected_type); ?>">
                    <?php endif; ?>
                    <input type="search" 
                           name="search" 
                           class="dcf-search-input" 
                           placeholder="<?php _e('Search Templates...', 'dry-cleaning-forms'); ?>"
                           value="<?php echo esc_attr($search_query); ?>">
                </form>
            </div>
            
            <div class="dcf-category-filters">
                <?php foreach ($template_categories as $cat_key => $category): ?>
                    <div class="dcf-filter-group">
                        <button class="dcf-filter-toggle" data-category="<?php echo esc_attr($cat_key); ?>">
                            <span class="<?php echo esc_attr($category['icon']); ?>"></span>
                            <?php echo esc_html($category['label']); ?>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="dcf-filter-dropdown" id="filter-<?php echo esc_attr($cat_key); ?>">
                            <!-- Dynamic options will be loaded here -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Templates Grid -->
    <div class="dcf-templates-grid">
        <?php if (empty($templates)): ?>
            <div class="dcf-no-templates">
                <p><?php _e('No templates found matching your criteria.', 'dry-cleaning-forms'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template): ?>
                <div class="dcf-template-card" data-template-id="<?php echo esc_attr($template['id']); ?>" data-category="<?php echo esc_attr($template['category'] ?? ''); ?>">
                    <div class="dcf-template-preview">
                        <div class="dcf-template-preview-wrapper">
                            <?php echo $template_manager->generate_template_preview($template); ?>
                        </div>
                        
                        <div class="dcf-template-overlay">
                            <button class="button dcf-preview-btn" data-template-id="<?php echo esc_attr($template['id']); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Preview', 'dry-cleaning-forms'); ?>
                            </button>
                            <button class="button button-primary dcf-use-template-btn" data-template-id="<?php echo esc_attr($template['id']); ?>">
                                <?php _e('Use This Template', 'dry-cleaning-forms'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="dcf-template-info">
                        <h3><?php echo esc_html($template['name']); ?></h3>
                        <?php if (!empty($template['description'])): ?>
                            <p><?php echo esc_html($template['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($template['features'])): ?>
                            <div class="dcf-template-features">
                                <?php foreach ($template['features'] as $feature): ?>
                                    <span class="dcf-feature-badge"><?php echo esc_html($feature); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($template['is_featured'])): ?>
                        <span class="dcf-featured-badge"><?php _e('FEATURED', 'dry-cleaning-forms'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Template Name Modal -->
    <div id="dcf-template-modal" class="dcf-modal">
        <div class="dcf-modal-content">
            <div class="dcf-modal-header">
                <h2><?php _e('Create from Template', 'dry-cleaning-forms'); ?></h2>
                <button type="button" class="dcf-modal-close">&times;</button>
            </div>
            <div class="dcf-modal-body">
                <form id="dcf-template-form">
                    <input type="hidden" id="dcf-template-id" name="template_id">
                    <div class="dcf-form-group">
                        <label for="dcf-template-name"><?php _e('Name:', 'dry-cleaning-forms'); ?></label>
                        <input type="text" 
                               id="dcf-template-name" 
                               name="name" 
                               class="regular-text" 
                               placeholder="<?php _e('Enter a name for your campaign', 'dry-cleaning-forms'); ?>"
                               required>
                    </div>
                </form>
            </div>
            <div class="dcf-modal-footer">
                <button type="button" class="button dcf-modal-cancel"><?php _e('Cancel', 'dry-cleaning-forms'); ?></button>
                <button type="button" class="button button-primary dcf-modal-create"><?php _e('Create', 'dry-cleaning-forms'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="dcf-preview-modal" class="dcf-modal">
        <div class="dcf-modal-content dcf-preview-content">
            <div class="dcf-modal-header">
                <h2><?php _e('Template Preview', 'dry-cleaning-forms'); ?></h2>
                <button type="button" class="dcf-modal-close">&times;</button>
            </div>
            <div class="dcf-modal-body">
                <div class="dcf-preview-container">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal-specific fixes to ensure they don't auto-show */
.dcf-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
    align-items: center;
    justify-content: center;
    display: none !important;
}
.dcf-modal.show {
    display: flex !important;
}

/* Rest of temporary inline styles below */
.dcf-templates-page {
    max-width: 1400px;
}

.dcf-template-types {
    background: #fff;
    padding: 30px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.dcf-template-types h2 {
    margin-top: 0;
    font-size: 18px;
    font-weight: 600;
}

.dcf-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dcf-type-card {
    border: 2px solid #dcdcde;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    background: #f6f7f7;
}

.dcf-type-card:hover {
    border-color: #2271b1;
    background: #f0f6fc;
}

.dcf-type-card.selected {
    border-color: #2271b1;
    background: #e6f2ff;
}

.dcf-type-card a {
    text-decoration: none;
    color: #1d2327;
    display: block;
}

.dcf-type-card .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    display: block;
    margin: 0 auto 10px;
    color: #2271b1;
}

.dcf-type-label {
    display: block;
    font-weight: 600;
    font-size: 14px;
}

.dcf-type-selected {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #00a32a;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.dcf-template-filters {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.dcf-filter-row {
    display: flex;
    gap: 20px;
    align-items: center;
}

.dcf-search-box {
    flex: 1;
}

.dcf-search-input {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
}

.dcf-category-filters {
    display: flex;
    gap: 15px;
}

.dcf-filter-group {
    position: relative;
}

.dcf-filter-toggle {
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dcf-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.dcf-template-card {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.dcf-template-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.dcf-template-preview {
    position: relative;
    padding-top: 60%;
    background: #f6f7f7;
    overflow: hidden;
}

.dcf-template-preview-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    overflow: hidden;
}

/* Template Preview Base Styles */
.dcf-template-preview .dcf-preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.3);
    pointer-events: none;
}

.dcf-template-preview .dcf-preview-popup {
    position: relative;
    background: white;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    max-width: 90%;
    font-size: 10px;
    transform: scale(0.8);
}

.dcf-template-preview .dcf-preview-close {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 12px;
    color: #999;
    pointer-events: none;
}

.dcf-template-preview .dcf-preview-headline {
    font-size: 12px;
    font-weight: bold;
    margin: 0 0 5px 0;
    color: #333;
}

.dcf-template-preview .dcf-preview-subheadline {
    font-size: 10px;
    color: #666;
    margin: 0 0 8px 0;
}

.dcf-template-preview .dcf-preview-description {
    font-size: 9px;
    color: #777;
    margin: 0 0 10px 0;
    line-height: 1.3;
}

.dcf-template-preview .dcf-preview-form {
    margin: 10px 0 0 0;
}

.dcf-template-preview .dcf-preview-input {
    width: 100%;
    padding: 4px 6px;
    font-size: 9px;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-bottom: 5px;
}

.dcf-template-preview .dcf-preview-button {
    background: #2271b1;
    color: white;
    border: none;
    padding: 5px 10px;
    font-size: 9px;
    border-radius: 3px;
    cursor: pointer;
    width: 100%;
}

/* Slide-in Preview */
.dcf-template-slide-in .dcf-slide-in-popup {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 120px;
    padding: 10px;
    transform: scale(1);
}

/* Sidebar Preview */
.dcf-template-sidebar .dcf-sidebar-popup {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%) scale(1);
    width: 100px;
    padding: 10px;
    background: #2271b1;
    color: white;
}

.dcf-template-sidebar .dcf-preview-headline,
.dcf-template-sidebar .dcf-preview-description {
    color: white;
}

/* Exit Intent Preview */
.dcf-template-exit-intent .dcf-exit-intent-popup {
    border: 2px solid #ff6b35;
}

.dcf-template-exit-intent .dcf-preview-urgency {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    font-size: 9px;
    border-radius: 3px;
    margin-bottom: 8px;
}

/* Multi-step Preview */
.dcf-template-multi-step .dcf-preview-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
}

.dcf-template-multi-step .dcf-preview-step {
    width: 20px;
    height: 3px;
    background: #ddd;
    margin: 0 2px;
}

.dcf-template-multi-step .dcf-preview-step.active {
    background: #2271b1;
}

/* Inline Form Preview */
.dcf-template-inline-form {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-template-inline-form .dcf-preview-form-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    width: 90%;
    max-width: 250px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dcf-template-inline-form .dcf-preview-form-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
    margin-bottom: 10px;
}

.dcf-template-inline-form .dcf-preview-form-title {
    font-size: 12px;
    font-weight: bold;
    margin: 0;
    color: #333;
}

.dcf-template-inline-form .dcf-preview-field {
    margin-bottom: 8px;
}

.dcf-template-inline-form .dcf-preview-label {
    display: block;
    font-size: 9px;
    color: #666;
    margin-bottom: 3px;
}

.dcf-template-inline-form .dcf-preview-submit {
    margin-top: 10px;
}

/* Floating Bar Preview */
.dcf-template-floating-bar {
    width: 100%;
    height: 100%;
    position: relative;
}

.dcf-template-floating-bar .dcf-preview-floating-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: #2271b1;
    padding: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.dcf-template-floating-bar .dcf-preview-floating-bar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 9px;
    color: white;
}

.dcf-template-floating-bar .dcf-preview-headline {
    font-weight: bold;
    font-size: 10px;
    flex-shrink: 0;
}

.dcf-template-floating-bar .dcf-preview-description {
    font-size: 8px;
    opacity: 0.9;
    flex: 1;
}

.dcf-template-floating-bar .dcf-preview-form-inline {
    display: flex;
    gap: 5px;
    align-items: center;
}

.dcf-template-floating-bar .dcf-preview-input-inline {
    padding: 3px 6px;
    font-size: 8px;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 3px;
    background: rgba(255,255,255,0.2);
    color: white;
    width: 80px;
}

.dcf-template-floating-bar .dcf-preview-button-inline {
    padding: 3px 8px;
    font-size: 8px;
    background: white;
    color: #2271b1;
    border: none;
    border-radius: 3px;
    font-weight: bold;
}

/* Fullscreen Preview */
.dcf-template-fullscreen {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
}

.dcf-template-fullscreen .dcf-preview-fullscreen {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-template-fullscreen .dcf-preview-fullscreen-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    width: 90%;
    max-width: 280px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
}

.dcf-template-fullscreen .dcf-preview-headline {
    font-size: 14px;
    margin-bottom: 5px;
}

.dcf-template-fullscreen .dcf-preview-subheadline {
    font-size: 11px;
    font-weight: normal;
    margin-bottom: 8px;
}

.dcf-template-fullscreen .dcf-preview-benefits {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 8px;
    font-size: 8px;
    color: #666;
}

.dcf-template-fullscreen .dcf-preview-benefit {
    flex: 0 0 48%;
}

.dcf-template-preview img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.dcf-template-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dcf-template-placeholder .dashicons {
    font-size: 64px;
    color: #dcdcde;
}

.dcf-template-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.dcf-template-card:hover .dcf-template-overlay {
    opacity: 1;
}

.dcf-template-info {
    padding: 20px;
}

.dcf-template-info h3 {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 600;
}

.dcf-template-info p {
    margin: 0 0 15px;
    color: #646970;
    font-size: 14px;
}

.dcf-featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ff9800;
    color: white;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
}

/* Modal styles moved to top with !important to prevent auto-show */

.dcf-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
}

.dcf-preview-content {
    max-width: 1000px;
}

.dcf-modal-header {
    padding: 20px;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dcf-modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.dcf-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
}

.dcf-modal-body {
    padding: 20px;
}

.dcf-modal-footer {
    padding: 20px;
    border-top: 1px solid #dcdcde;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.dcf-form-group {
    margin-bottom: 20px;
}

.dcf-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.dcf-form-group input {
    width: 100%;
}
</style>