<?php
/**
 * Form Builder
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Builder class
 */
class DCF_Form_Builder {
    
    /**
     * Available field types
     */
    private $field_types = array();
    
    /**
     * Form templates
     */
    private $form_templates = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_field_types();
        $this->init_form_templates();
    }
    
    /**
     * Initialize available field types
     */
    private function init_field_types() {
        $this->field_types = array(
            'text' => array(
                'label' => __('Text Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-edit',
                'category' => 'basic',
                'settings' => array('label', 'placeholder', 'required', 'validation')
            ),
            'email' => array(
                'label' => __('Email Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-email',
                'category' => 'basic',
                'settings' => array('label', 'placeholder', 'required', 'validation')
            ),
            'phone' => array(
                'label' => __('Phone Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-phone',
                'category' => 'basic',
                'settings' => array('label', 'placeholder', 'required', 'validation', 'format')
            ),
            'tel' => array(
                'label' => __('Telephone Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-phone',
                'category' => 'basic',
                'settings' => array('label', 'placeholder', 'required', 'validation', 'format')
            ),
            'name' => array(
                'label' => __('Name Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-admin-users',
                'category' => 'basic',
                'settings' => array('label', 'first_placeholder', 'last_placeholder', 'required')
            ),
            'textarea' => array(
                'label' => __('Textarea', 'dry-cleaning-forms'),
                'icon' => 'dashicons-text',
                'category' => 'basic',
                'settings' => array('label', 'placeholder', 'required', 'rows')
            ),
            'select' => array(
                'label' => __('Select Dropdown', 'dry-cleaning-forms'),
                'icon' => 'dashicons-arrow-down-alt2',
                'category' => 'advanced',
                'settings' => array('label', 'required', 'options', 'multiple')
            ),
            'radio' => array(
                'label' => __('Radio Buttons', 'dry-cleaning-forms'),
                'icon' => 'dashicons-marker',
                'category' => 'advanced',
                'settings' => array('label', 'required', 'options')
            ),
            'checkbox' => array(
                'label' => __('Checkboxes', 'dry-cleaning-forms'),
                'icon' => 'dashicons-yes',
                'category' => 'advanced',
                'settings' => array('label', 'required', 'options')
            ),
            'address' => array(
                'label' => __('Address Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-location',
                'category' => 'advanced',
                'settings' => array('label', 'required', 'components')
            ),
            'date' => array(
                'label' => __('Date Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-calendar-alt',
                'category' => 'advanced',
                'settings' => array('label', 'required', 'format', 'min_date', 'max_date')
            ),
            'hidden' => array(
                'label' => __('Hidden Field', 'dry-cleaning-forms'),
                'icon' => 'dashicons-hidden',
                'category' => 'advanced',
                'settings' => array('name', 'value')
            ),
            'terms' => array(
                'label' => __('Terms & Conditions', 'dry-cleaning-forms'),
                'icon' => 'dashicons-privacy',
                'category' => 'advanced',
                'settings' => array('label', 'terms_text', 'terms_url', 'privacy_url', 'required')
            ),
            'submit' => array(
                'label' => __('Submit Button', 'dry-cleaning-forms'),
                'icon' => 'dashicons-admin-generic',
                'category' => 'advanced',
                'settings' => array('button_text', 'button_size', 'alignment', 'bg_color', 'text_color', 'border_color', 'border_radius', 'min_width')
            )
        );
    }
    
    /**
     * Get available field types
     *
     * @return array Field types
     */
    public function get_field_types() {
        return apply_filters('dcf_field_types', $this->field_types);
    }
    
    /**
     * Get field type by key
     *
     * @param string $type Field type key
     * @return array|null Field type data
     */
    public function get_field_type($type) {
        $field_types = $this->get_field_types();
        return isset($field_types[$type]) ? $field_types[$type] : null;
    }
    
    /**
     * Create new form
     *
     * @param array $form_data Form data
     * @return int|WP_Error Form ID on success, WP_Error on failure
     */
    public function create_form($form_data) {
        global $wpdb;
        
        // Debug logging
        // error_log('DCF Form Builder create_form called with data: ' . print_r($form_data, true));
        
        $form_data = DCF_Plugin_Core::sanitize_form_data($form_data);
        
        // Validate required fields
        if (empty($form_data['form_name'])) {
            return new WP_Error('missing_form_name', __('Form name is required', 'dry-cleaning-forms'));
        }
        
        if (empty($form_data['form_type'])) {
            return new WP_Error('missing_form_type', __('Form type is required', 'dry-cleaning-forms'));
        }
        
        // Add UTM fields to form config if not already present
        $form_config = isset($form_data['form_config']) ? $form_data['form_config'] : array();
        
        // Initialize fields array if not set
        if (!isset($form_config['fields'])) {
            $form_config['fields'] = array();
        }
        
        // Get existing field IDs
        $existing_field_ids = array_map(function($field) {
            return isset($field['id']) ? $field['id'] : '';
        }, $form_config['fields']);
        
        // Add UTM fields if they don't exist and include_utm_parameters is enabled
        if (!empty($form_config['include_utm_parameters'])) {
            $utm_fields = $this->get_utm_fields();
            foreach ($utm_fields as $utm_field) {
                if (!in_array($utm_field['id'], $existing_field_ids)) {
                    $form_config['fields'][] = $utm_field;
                }
            }
        }
        
        $table = $wpdb->prefix . 'dcf_forms';
        
        $result = $wpdb->insert(
            $table,
            array(
                'form_name' => $form_data['form_name'],
                'form_type' => $form_data['form_type'],
                'form_config' => wp_json_encode($form_config),
                'webhook_url' => isset($form_data['webhook_url']) ? $form_data['webhook_url'] : '',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('create_form_failed', __('Failed to create form', 'dry-cleaning-forms'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update form
     *
     * @param int $form_id Form ID
     * @param array $form_data Form data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_form($form_id, $form_data) {
        global $wpdb;
        
        $form_data = DCF_Plugin_Core::sanitize_form_data($form_data);
        
        $table = $wpdb->prefix . 'dcf_forms';
        
        $update_data = array(
            'updated_at' => current_time('mysql')
        );
        
        if (isset($form_data['form_name'])) {
            $update_data['form_name'] = $form_data['form_name'];
        }
        
        if (isset($form_data['form_type'])) {
            $update_data['form_type'] = $form_data['form_type'];
        }
        
        if (isset($form_data['form_config'])) {
            $form_config = $form_data['form_config'];
            
            // Ensure fields array exists
            if (!isset($form_config['fields'])) {
                $form_config['fields'] = array();
            }
            
            // Get existing field IDs
            $existing_field_ids = array_map(function($field) {
                return isset($field['id']) ? $field['id'] : '';
            }, $form_config['fields']);
            
            // Add UTM fields if they don't exist and include_utm_parameters is enabled
            if (!empty($form_config['include_utm_parameters'])) {
                $utm_fields = $this->get_utm_fields();
                foreach ($utm_fields as $utm_field) {
                    if (!in_array($utm_field['id'], $existing_field_ids)) {
                        $form_config['fields'][] = $utm_field;
                    }
                }
            }
            
            $update_data['form_config'] = wp_json_encode($form_config);
        }
        
        if (isset($form_data['webhook_url'])) {
            $update_data['webhook_url'] = $form_data['webhook_url'];
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $form_id),
            null,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_form_failed', __('Failed to update form', 'dry-cleaning-forms'));
        }
        
        return true;
    }
    
    /**
     * Get form by ID
     *
     * @param int $form_id Form ID
     * @return object|null Form data
     */
    public function get_form($form_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_forms';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return null;
        }
        
        $form = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id)
        );
        
        if ($form && !empty($form->form_config)) {
            $decoded = json_decode($form->form_config, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $form->form_config = $decoded;
                
                // If include_utm_parameters is set, ensure UTM fields are in the fields array
                if (!empty($form->form_config['include_utm_parameters'])) {
                    $existing_field_ids = array();
                    if (isset($form->form_config['fields']) && is_array($form->form_config['fields'])) {
                        $existing_field_ids = array_map(function($field) {
                            return isset($field['id']) ? $field['id'] : '';
                        }, $form->form_config['fields']);
                    } else {
                        $form->form_config['fields'] = array();
                    }
                    
                    // Add UTM fields if they don't exist
                    $utm_fields = $this->get_utm_fields();
                    foreach ($utm_fields as $utm_field) {
                        if (!in_array($utm_field['id'], $existing_field_ids)) {
                            $form->form_config['fields'][] = $utm_field;
                        }
                    }
                }
            } else {
                $form->form_config = array();
            }
        }
        
        return $form;
    }
    
    /**
     * Get all forms
     *
     * @param array $args Query arguments
     * @return array Forms
     */
    public function get_forms($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'form_type' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'dcf_forms';
        
        $where = '';
        $params = array();
        
        if (!empty($args['form_type'])) {
            $where = 'WHERE form_type = %s';
            $params[] = $args['form_type'];
        }
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        $query = "SELECT * FROM $table $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        
        $forms = $wpdb->get_results($wpdb->prepare($query, $params));
        
        foreach ($forms as $form) {
            $form->form_config = json_decode($form->form_config, true);
        }
        
        return $forms;
    }
    
    /**
     * Delete form
     *
     * @param int $form_id Form ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_form($form_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'dcf_forms';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $form_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_form_failed', __('Failed to delete form', 'dry-cleaning-forms'));
        }
        
        return true;
    }
    
    /**
     * Duplicate form
     *
     * @param int $form_id Form ID to duplicate
     * @return int|WP_Error New form ID on success, WP_Error on failure
     */
    public function duplicate_form($form_id) {
        $original_form = $this->get_form($form_id);
        
        if (!$original_form) {
            return new WP_Error('form_not_found', __('Original form not found', 'dry-cleaning-forms'));
        }
        
        $new_form_data = array(
            'form_name' => $original_form->form_name . ' (Copy)',
            'form_type' => $original_form->form_type,
            'form_config' => $original_form->form_config,
            'webhook_url' => $original_form->webhook_url
        );
        
        return $this->create_form($new_form_data);
    }
    
    /**
     * Render form HTML
     *
     * @param int $form_id Form ID
     * @param array $args Render arguments
     * @return string|WP_Error Form HTML or error
     */
    public function render_form($form_id, $args = array()) {
        // Don't render during REST API requests (unless force_render is true)
        $force_render = isset($args['force_render']) && $args['force_render'];
        if (!$force_render && ((defined('REST_REQUEST') && REST_REQUEST) || 
            (defined('DOING_AJAX') && DOING_AJAX && is_admin()) ||
            (function_exists('wp_is_json_request') && wp_is_json_request()))) {
            return '[dcf_form id="' . esc_attr($form_id) . '"]';
        }
        
        $form = $this->get_form($form_id);
        
        if (!$form) {
            return '<!-- Form not found (ID: ' . esc_attr($form_id) . ') -->';
        }
        
        $defaults = array(
            'ajax' => true,
            'show_title' => true,
            'show_description' => true,
            'css_class' => '',
            'popup_mode' => false,
            'preview_mode' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Decode form_config if it's a JSON string
        $form_config = $form->form_config;
        if (is_string($form_config)) {
            $form_config = json_decode($form_config, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $form_config = array();
            }
        }
        
        $fields = isset($form_config['fields']) ? $form_config['fields'] : array();
        $is_multi_step = isset($form_config['multi_step']) && $form_config['multi_step'];
        $steps = isset($form_config['steps']) ? $form_config['steps'] : array();
        
        // Debug logging - commented out to prevent output issues
        // error_log('[DCF Debug] render_form called for form ID: ' . $form_id);
        // error_log('[DCF Debug] Form config: ' . print_r($form_config, true));
        // error_log('[DCF Debug] Number of fields: ' . count($fields));
        // error_log('[DCF Debug] Field types: ' . implode(', ', array_column($fields, 'type')));
        
        // Track form view (only if not in preview mode)
        if (!$args['preview_mode'] && !is_admin()) {
            $this->track_form_view($form_id);
        }
        
        ob_start();
        
        // Extract style settings
        $styles = isset($form_config['styles']) ? $form_config['styles'] : array();
        $layout_type = isset($styles['layout_type']) ? $styles['layout_type'] : 'single-column';
        $input_style = isset($styles['input_style']) ? $styles['input_style'] : 'box';
        $width = isset($styles['width']) ? $styles['width'] : '650';
        $width_unit = isset($styles['width_unit']) ? $styles['width_unit'] : 'px';
        $field_spacing = isset($styles['field_spacing']) ? $styles['field_spacing'] : '16';
        $label_width = isset($styles['label_width']) ? $styles['label_width'] : '200';
        $label_alignment = isset($styles['label_alignment']) ? $styles['label_alignment'] : 'top';
        $padding_top = isset($styles['padding_top']) ? $styles['padding_top'] : '30';
        $padding_right = isset($styles['padding_right']) ? $styles['padding_right'] : '40';
        $padding_bottom = isset($styles['padding_bottom']) ? $styles['padding_bottom'] : '30';
        $padding_left = isset($styles['padding_left']) ? $styles['padding_left'] : '40';
        $show_labels = isset($styles['show_labels']) ? $styles['show_labels'] : true;
        
        // Build style attribute
        $form_styles = array();
        $form_styles[] = 'max-width: ' . esc_attr($width) . esc_attr($width_unit);
        $form_styles[] = 'padding: ' . esc_attr($padding_top) . 'px ' . esc_attr($padding_right) . 'px ' . esc_attr($padding_bottom) . 'px ' . esc_attr($padding_left) . 'px';
        
        // Build CSS classes based on style settings
        $style_classes = array();
        $style_classes[] = 'dcf-layout-' . esc_attr($layout_type);
        $style_classes[] = 'dcf-input-' . esc_attr($input_style);
        $style_classes[] = 'dcf-label-' . esc_attr($label_alignment);
        if (!$show_labels) {
            $style_classes[] = 'dcf-hide-labels';
        }
        
        // In popup mode, we don't need the wrapper div since the popup engine provides its own
        if (!$args['popup_mode']):
        ?>
        <div class="dcf-form-container <?php echo esc_attr($args['css_class']); ?> <?php echo $is_multi_step ? 'dcf-multi-step-form' : ''; ?> <?php echo implode(' ', $style_classes); ?>" data-form-id="<?php echo esc_attr($form_id); ?>" style="<?php echo implode('; ', $form_styles); ?>; --field-spacing: <?php echo esc_attr($field_spacing); ?>px; --label-width: <?php echo esc_attr($label_width); ?>px;">
        <?php endif; ?>
            <?php if ($args['show_title'] && !empty($form_config['title'])): ?>
                <h3 class="dcf-form-title"><?php echo esc_html($form_config['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($args['show_description'] && !empty($form_config['description'])): ?>
                <div class="dcf-form-description"><?php echo wp_kses_post($form_config['description']); ?></div>
            <?php endif; ?>
            
            <?php if ($is_multi_step && !empty($steps)): ?>
                <!-- Multi-step progress indicator -->
                <div class="dcf-form-steps">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="dcf-step <?php echo $index === 0 ? 'active' : ''; ?>" data-step="<?php echo $index; ?>">
                            <span class="dcf-step-number"><?php echo $index + 1; ?></span>
                            <span class="dcf-step-title"><?php echo esc_html($step['title']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form class="dcf-form <?php echo $is_multi_step ? 'dcf-multi-step-form' : ''; ?>" method="post" <?php echo $args['ajax'] ? 'data-ajax="true"' : ''; ?> <?php echo $is_multi_step ? 'data-multi-step="true"' : ''; ?> data-form-id="<?php echo esc_attr($form_id); ?>">
                <?php wp_nonce_field('dcf_submit_form_' . $form_id, 'dcf_nonce'); ?>
                <input type="hidden" name="dcf_form_id" value="<?php echo esc_attr($form_id); ?>">
                <input type="hidden" name="action" value="dcf_submit_form">
                
                <!-- Render UTM hidden fields if enabled in form settings -->
                <?php
                if (!empty($form_config['include_utm_parameters'])) {
                    $utm_fields = $this->get_utm_fields();
                    $existing_field_ids = array_map(function($field) {
                        return isset($field['id']) ? $field['id'] : '';
                    }, $fields);
                    
                    foreach ($utm_fields as $utm_field) {
                        if (!in_array($utm_field['id'], $existing_field_ids)) {
                            echo $this->render_field($utm_field);
                        }
                    }
                }
                ?>
                
                <?php if ($is_multi_step && !empty($steps)): ?>
                    <!-- Render fields in steps -->
                    <?php foreach ($steps as $step_index => $step): ?>
                        <?php 
                        // In preview mode, show all steps; otherwise only show first step
                        $step_style = '';
                        if (!$args['preview_mode']) {
                            $step_style = $step_index === 0 ? '' : 'display: none;';
                        } else {
                            // In preview mode, add some spacing between steps
                            $step_style = $step_index > 0 ? 'margin-top: 30px; border-top: 2px solid #e0e0e0; padding-top: 20px;' : '';
                        }
                        ?>
                        <div class="dcf-step-content" data-step="<?php echo $step_index; ?>" style="<?php echo $step_style; ?>">
                            <?php if ($args['preview_mode'] && count($steps) > 1): ?>
                                <h4 class="dcf-step-title"><?php echo esc_html($step['title']); ?> (Step <?php echo $step_index + 1; ?>)</h4>
                            <?php endif; ?>
                            <?php
                            // Render fields for this step
                            foreach ($step['fields'] as $field_id) {
                                // Find the field by ID
                                $field = $this->find_field_by_id($field_id, $fields);
                                if ($field) {
                                    echo $this->render_field($field);
                                }
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Step navigation -->
                    <?php if (!$args['preview_mode']): ?>
                        <div class="dcf-step-navigation">
                            <button type="button" class="dcf-prev-step" style="display: none;">
                                <?php _e('Previous', 'dry-cleaning-forms'); ?>
                            </button>
                            <button type="button" class="dcf-next-step">
                                <?php _e('Next', 'dry-cleaning-forms'); ?>
                            </button>
                            <button type="submit" class="dcf-submit-button" style="display: none;">
                                <?php echo esc_html(isset($form_config['submit_text']) ? $form_config['submit_text'] : __('Submit', 'dry-cleaning-forms')); ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Preview mode: Show submit button directly -->
                        <div class="dcf-form-submit dcf-submit-wrapper">
                            <button type="submit" class="dcf-submit-button">
                                <?php echo esc_html(isset($form_config['submit_text']) ? $form_config['submit_text'] : __('Submit', 'dry-cleaning-forms')); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Regular form - render all fields -->
                    <div class="dcf-form-fields">
                        <?php 
                        // Debug logging
                        error_log('[DCF Debug] About to render fields in regular form. Total fields: ' . count($fields));
                        error_log('[DCF Debug] Fields array: ' . print_r($fields, true));
                        
                        foreach ($fields as $index => $field): 
                            error_log('[DCF Debug] === Processing field ' . $index . ' ===');
                            error_log('[DCF Debug] Field data: ' . print_r($field, true));
                            error_log('[DCF Debug] Field ID: ' . (isset($field['id']) ? $field['id'] : 'no-id'));
                            error_log('[DCF Debug] Field Type: ' . (isset($field['type']) ? $field['type'] : 'no-type'));
                            error_log('[DCF Debug] Field Label: ' . (isset($field['label']) ? $field['label'] : 'no-label'));
                            error_log('[DCF Debug] Field Required: ' . (isset($field['required']) ? $field['required'] : 'not-set'));
                            
                            // Check if this is the terms field we're looking for
                            if (isset($field['id']) && $field['id'] === 'field_1750724768472') {
                                error_log('[DCF Debug] !!! FOUND TERMS FIELD field_1750724768472 !!!');
                                error_log('[DCF Debug] Terms field full data: ' . print_r($field, true));
                            }
                            
                            // Render the field
                            $field_html = $this->render_field($field);
                            error_log('[DCF Debug] Field HTML length: ' . strlen($field_html));
                            error_log('[DCF Debug] Field HTML empty: ' . (empty($field_html) ? 'YES' : 'NO'));
                            if (empty($field_html)) {
                                error_log('[DCF Debug] WARNING: Field rendered as empty HTML!');
                            }
                        ?>
                            <?php echo $field_html; ?>
                        <?php 
                            error_log('[DCF Debug] === End of field ' . $index . ' ===');
                        endforeach; 
                        ?>
                    </div>
                    
                    <?php
                    // Check if form has a custom submit button field
                    $has_custom_submit = false;
                    foreach ($fields as $field) {
                        if ($field['type'] === 'submit') {
                            $has_custom_submit = true;
                            break;
                        }
                    }
                    
                    // Only show default submit button if no custom submit button exists
                    if (!$has_custom_submit):
                    ?>
                    <div class="dcf-form-submit dcf-submit-wrapper">
                        <button type="submit" class="dcf-submit-button">
                            <?php echo esc_html(isset($form_config['submit_text']) ? $form_config['submit_text'] : __('Submit', 'dry-cleaning-forms')); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        <?php if (!$args['popup_mode']): ?>
        </div>
        <?php endif; ?>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual field
     *
     * @param array $field Field configuration
     * @return string Field HTML
     */
    public function render_field($field) {
        error_log('[DCF Debug] ========== START render_field ==========');
        error_log('[DCF Debug] Field parameter: ' . print_r($field, true));
        
        // Check if field has required properties
        if (!isset($field['type'])) {
            error_log('[DCF Debug] ERROR: Field missing type property!');
            return '';
        }
        
        $field_type = $field['type'];
        error_log('[DCF Debug] Field type extracted: ' . $field_type);
        
        $field_config = $this->get_field_type($field_type);
        error_log('[DCF Debug] Field config lookup result: ' . ($field_config ? 'FOUND' : 'NOT FOUND'));
        
        if ($field_config) {
            error_log('[DCF Debug] Field config details: ' . print_r($field_config, true));
        }
        
        // Debug logging
        error_log('[DCF Debug] Field ID: ' . (isset($field['id']) ? $field['id'] : 'NO ID'));
        error_log('[DCF Debug] Field type: ' . $field_type);
        error_log('[DCF Debug] Field config found: ' . ($field_config ? 'yes' : 'no'));
        
        if (!$field_config) {
            error_log('[DCF Debug] ERROR: Field type "' . $field_type . '" not found in registry!');
            error_log('[DCF Debug] Available field types: ' . implode(', ', array_keys($this->get_field_types())));
            return '';
        }
        
        $field_id = 'dcf_field_' . $field['id'];
        $field_name = 'dcf_field[' . $field['id'] . ']';
        $required = isset($field['required']) && $field['required'];
        $css_class = 'dcf-field dcf-field-' . $field_type . ' dcf-field-type-' . $field_type;
        
        // Add specific classes for common field types to support layout
        if (in_array($field_type, ['textarea', 'message', 'comments'])) {
            $css_class .= ' dcf-field-type-textarea';
        } elseif ($field_type === 'email') {
            $css_class .= ' dcf-email-field';
        } elseif ($field_type === 'phone' || $field_type === 'tel') {
            $css_class .= ' dcf-phone-field';
        } elseif (in_array($field['id'], ['first_name', 'last_name', 'name'])) {
            $css_class .= ' dcf-name-field';
        } elseif ($field_type === 'terms') {
            $css_class .= ' dcf-terms-field-wrapper';
        }
        
        if ($required) {
            $css_class .= ' dcf-field-required';
        }
        
        if (!empty($field['css_class'])) {
            $css_class .= ' ' . $field['css_class'];
        }
        
        // For hidden fields, render directly without wrapper
        if ($field_type === 'hidden') {
            return $this->render_field_input($field, $field_id, $field_name);
        }
        
        // Handle submit button alignment
        $field_input_style = '';
        if ($field_type === 'submit' && isset($field['alignment'])) {
            $field_input_style = 'text-align: ' . esc_attr($field['alignment']) . ';';
        }
        
        error_log('[DCF Debug] Starting field HTML generation');
        error_log('[DCF Debug] CSS classes: ' . $css_class);
        error_log('[DCF Debug] Field ID for HTML: ' . $field_id);
        error_log('[DCF Debug] Field name for HTML: ' . $field_name);
        error_log('[DCF Debug] Required: ' . ($required ? 'YES' : 'NO'));
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($css_class); ?>" data-field-id="<?php echo esc_attr($field['id']); ?>">
            <?php 
            // Debug logging for label rendering
            error_log('[DCF Debug] Label check - field_type: ' . $field_type . ', has label: ' . (!empty($field['label']) ? 'yes' : 'no') . ', label value: ' . (isset($field['label']) ? $field['label'] : 'not set'));
            
            // Special check for terms field
            if ($field_type === 'terms') {
                error_log('[DCF Debug] TERMS FIELD - Label will be skipped (terms has its own label in render_field_input)');
            }
            ?>
            <?php if (!empty($field['label']) && $field_type !== 'hidden' && $field_type !== 'submit' && $field_type !== 'terms'): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="dcf-field-label">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($required): ?>
                        <span class="dcf-required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <div class="dcf-field-input" <?php echo $field_input_style ? 'style="' . $field_input_style . '"' : ''; ?>>
                <?php 
                error_log('[DCF Debug] About to call render_field_input for type: ' . $field_type);
                $input_html = $this->render_field_input($field, $field_id, $field_name);
                error_log('[DCF Debug] render_field_input returned HTML length: ' . strlen($input_html));
                echo $input_html;
                ?>
            </div>
            
            <?php if (!empty($field['description'])): ?>
                <div class="dcf-field-description"><?php echo wp_kses_post($field['description']); ?></div>
            <?php endif; ?>
        </div>
        <?php
        
        $html = ob_get_clean();
        error_log('[DCF Debug] Final field HTML length: ' . strlen($html));
        error_log('[DCF Debug] ========== END render_field ==========');
        
        return $html;
    }
    
    /**
     * Render field input
     *
     * @param array $field Field configuration
     * @param string $field_id Field ID
     * @param string $field_name Field name
     * @return string Field input HTML
     */
    private function render_field_input($field, $field_id, $field_name) {
        error_log('[DCF Debug] render_field_input START - Type: ' . $field['type'] . ', ID: ' . $field_id);
        
        $required = isset($field['required']) && $field['required'];
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        $value = isset($field['default_value']) ? $field['default_value'] : '';
        
        error_log('[DCF Debug] Field properties - Required: ' . ($required ? 'YES' : 'NO') . ', Placeholder: ' . $placeholder . ', Default value: ' . $value);
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'phone':
            case 'tel':
                $input_type = ($field['type'] === 'phone' || $field['type'] === 'tel') ? 'tel' : $field['type'];
                return sprintf(
                    '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" %s class="dcf-input">',
                    esc_attr($input_type),
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($value),
                    esc_attr($placeholder),
                    $required ? 'required' : ''
                );
            
            case 'textarea':
                $rows = isset($field['rows']) ? $field['rows'] : 4;
                return sprintf(
                    '<textarea id="%s" name="%s" rows="%d" placeholder="%s" %s class="dcf-textarea">%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    intval($rows),
                    esc_attr($placeholder),
                    $required ? 'required' : '',
                    esc_textarea($value)
                );
            
            case 'select':
                $multiple = isset($field['multiple']) && $field['multiple'];
                $options = isset($field['options']) ? $field['options'] : array();
                
                $html = sprintf(
                    '<select id="%s" name="%s%s" %s %s class="dcf-select">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    $multiple ? '[]' : '',
                    $multiple ? 'multiple' : '',
                    $required ? 'required' : ''
                );
                
                if (!$multiple && !$required) {
                    $html .= '<option value="">' . __('Select an option', 'dry-cleaning-forms') . '</option>';
                }
                
                foreach ($options as $option) {
                    // Handle both string and array options
                    if (is_string($option)) {
                        $option_value = $option;
                        $option_label = $option;
                    } else {
                        $option_value = isset($option['value']) ? $option['value'] : '';
                        $option_label = isset($option['label']) ? $option['label'] : $option_value;
                    }
                    
                    $selected = $value === $option_value ? 'selected' : '';
                    $html .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        $selected,
                        esc_html($option_label)
                    );
                }
                
                $html .= '</select>';
                return $html;
            
            case 'radio':
                $options = isset($field['options']) ? $field['options'] : array();
                $html = '';
                
                foreach ($options as $i => $option) {
                    $option_id = $field_id . '_' . $i;
                    // Handle both string and array options
                    if (is_string($option)) {
                        $option_value = $option;
                        $option_label = $option;
                    } else {
                        $option_value = isset($option['value']) ? $option['value'] : '';
                        $option_label = isset($option['label']) ? $option['label'] : $option_value;
                    }
                    
                    $checked = $value === $option_value ? 'checked' : '';
                    
                    $html .= sprintf(
                        '<div class="dcf-radio-option"><input type="radio" id="%s" name="%s" value="%s" %s %s class="dcf-radio"> <label for="%s">%s</label></div>',
                        esc_attr($option_id),
                        esc_attr($field_name),
                        esc_attr($option_value),
                        $checked,
                        $required ? 'required' : '',
                        esc_attr($option_id),
                        esc_html($option_label)
                    );
                }
                
                return $html;
            
            case 'name':
                $first_placeholder = isset($field['first_placeholder']) ? $field['first_placeholder'] : __('First Name', 'dry-cleaning-forms');
                $last_placeholder = isset($field['last_placeholder']) ? $field['last_placeholder'] : __('Last Name', 'dry-cleaning-forms');
                
                $html = '<div class="dcf-name-field">';
                $html .= '<div class="dcf-name-row">';
                $html .= sprintf(
                    '<div class="dcf-name-first"><input type="text" id="%s_first" name="%s_first" placeholder="%s" %s class="dcf-input"></div>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($first_placeholder),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="dcf-name-last"><input type="text" id="%s_last" name="%s_last" placeholder="%s" %s class="dcf-input"></div>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($last_placeholder),
                    $required ? 'required' : ''
                );
                $html .= '</div></div>';
                return $html;
            
            case 'checkbox':
                $options = isset($field['options']) ? $field['options'] : array();
                $html = '';
                
                foreach ($options as $i => $option) {
                    $option_id = $field_id . '_' . $i;
                    // Handle both string and array options
                    if (is_string($option)) {
                        $option_value = $option;
                        $option_label = $option;
                    } else {
                        $option_value = isset($option['value']) ? $option['value'] : '';
                        $option_label = isset($option['label']) ? $option['label'] : $option_value;
                    }
                    
                    $checked = is_array($value) && in_array($option_value, $value) ? 'checked' : '';
                    
                    $html .= sprintf(
                        '<div class="dcf-checkbox-option"><input type="checkbox" id="%s" name="%s[]" value="%s" %s class="dcf-checkbox"> <label for="%s">%s</label></div>',
                        esc_attr($option_id),
                        esc_attr($field_name),
                        esc_attr($option_value),
                        $checked,
                        esc_attr($option_id),
                        esc_html($option_label)
                    );
                }
                
                return $html;
            
            case 'address':
                return $this->render_address_field($field, $field_id, $field_name);
            
            case 'terms':
                error_log('[DCF Debug] render_field_input: Rendering terms field');
                error_log('[DCF Debug] Full field data: ' . print_r($field, true));
                $terms_text = isset($field['terms_text']) ? $field['terms_text'] : __('I have read and agree to the Terms and Conditions and Privacy Policy', 'dry-cleaning-forms');
                $terms_url = isset($field['terms_url']) ? $field['terms_url'] : '';
                $privacy_url = isset($field['privacy_url']) ? $field['privacy_url'] : '';
                error_log('[DCF Debug] Terms field data - text: ' . $terms_text . ', terms_url: ' . $terms_url . ', privacy_url: ' . $privacy_url);
                
                // Replace text with links if URLs are provided
                if ($terms_url || $privacy_url) {
                    if ($terms_url) {
                        $terms_text = str_replace(
                            'Terms and Conditions',
                            '<a href="' . esc_url($terms_url) . '" target="_blank">Terms and Conditions</a>',
                            $terms_text
                        );
                    }
                    if ($privacy_url) {
                        $terms_text = str_replace(
                            'Privacy Policy',
                            '<a href="' . esc_url($privacy_url) . '" target="_blank">Privacy Policy</a>',
                            $terms_text
                        );
                    }
                }
                
                $html = '<div class="dcf-terms-field">';
                $html .= sprintf(
                    '<label><input type="checkbox" id="%s" name="%s" value="1" %s class="dcf-checkbox"> %s</label>',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    $required ? 'required' : '',
                    wp_kses($terms_text, array(
                        'a' => array(
                            'href' => array(),
                            'target' => array()
                        )
                    ))
                );
                $html .= '</div>';
                error_log('[DCF Debug] Terms field HTML generated: ' . $html);
                error_log('[DCF Debug] render_field_input END - Returning terms HTML of length: ' . strlen($html));
                return $html;
            
            case 'date':
                return sprintf(
                    '<input type="date" id="%s" name="%s" value="%s" %s class="dcf-input">',
                    esc_attr($field_id),
                    esc_attr($field_name),
                    esc_attr($value),
                    $required ? 'required' : ''
                );
            
            case 'hidden':
                return sprintf(
                    '<input type="hidden" name="%s" value="%s">',
                    esc_attr($field_name),
                    esc_attr($value)
                );
            
            case 'submit':
                $button_text = isset($field['button_text']) ? $field['button_text'] : 'Submit';
                $button_size = isset($field['button_size']) ? $field['button_size'] : 'medium';
                $alignment = isset($field['alignment']) ? $field['alignment'] : 'center';
                $bg_color = isset($field['bg_color']) ? $field['bg_color'] : '#2271b1';
                $text_color = isset($field['text_color']) ? $field['text_color'] : '#ffffff';
                $border_color = isset($field['border_color']) ? $field['border_color'] : '#2271b1';
                $border_radius = isset($field['border_radius']) ? $field['border_radius'] : '4';
                $min_width = isset($field['min_width']) ? $field['min_width'] : '';
                
                // Size styles
                $size_styles = array(
                    'small' => 'padding: 8px 16px; font-size: 14px;',
                    'medium' => 'padding: 12px 24px; font-size: 16px;',
                    'large' => 'padding: 16px 32px; font-size: 18px;'
                );
                
                $button_style = isset($size_styles[$button_size]) ? $size_styles[$button_size] : $size_styles['medium'];
                $button_style .= "background-color: {$bg_color}; color: {$text_color}; border: 1px solid {$border_color}; border-radius: {$border_radius}px; cursor: pointer; transition: opacity 0.2s;";
                
                if ($min_width && $min_width !== '0') {
                    $button_style .= " min-width: {$min_width}px;";
                }
                
                // Return just the button without wrapper div
                // The wrapper will be handled by the field container
                return sprintf(
                    '<button type="submit" style="%s" class="dcf-submit-button dcf-custom-submit">%s</button>',
                    esc_attr($button_style),
                    esc_html($button_text)
                );
            
            default:
                error_log('[DCF Debug] render_field_input - Unknown field type: ' . $field['type']);
                $filter_result = apply_filters('dcf_render_field_input', '', $field, $field_id, $field_name);
                error_log('[DCF Debug] render_field_input - Filter result length: ' . strlen($filter_result));
                return $filter_result;
        }
    }
    
    /**
     * Render address field
     *
     * @param array $field Field configuration
     * @param string $field_id Field ID
     * @param string $field_name Field name
     * @return string Address field HTML
     */
    private function render_address_field($field, $field_id, $field_name) {
        $required = isset($field['required']) && $field['required'];
        $components = isset($field['components']) ? $field['components'] : array('street', 'city', 'state', 'zip');
        
        $html = '<div class="dcf-address-field">';
        
        if (in_array('street', $components)) {
            $html .= sprintf(
                '<div class="dcf-address-street"><input type="text" name="%s[street]" placeholder="%s" %s class="dcf-input"></div>',
                esc_attr($field_name),
                esc_attr(__('Street Address', 'dry-cleaning-forms')),
                $required ? 'required' : ''
            );
        }
        
        if (in_array('street2', $components)) {
            $html .= sprintf(
                '<div class="dcf-address-street2"><input type="text" name="%s[street2]" placeholder="%s" class="dcf-input"></div>',
                esc_attr($field_name),
                esc_attr(__('Suite/Apartment #', 'dry-cleaning-forms'))
            );
        }
        
        $html .= '<div class="dcf-address-row">';
        
        if (in_array('city', $components)) {
            $html .= sprintf(
                '<div class="dcf-address-city"><input type="text" name="%s[city]" placeholder="%s" %s class="dcf-input"></div>',
                esc_attr($field_name),
                esc_attr(__('City', 'dry-cleaning-forms')),
                $required ? 'required' : ''
            );
        }
        
        if (in_array('state', $components)) {
            $html .= sprintf(
                '<div class="dcf-address-state"><select name="%s[state]" %s class="dcf-select">',
                esc_attr($field_name),
                $required ? 'required' : ''
            );
            
            $html .= '<option value="">' . __('State', 'dry-cleaning-forms') . '</option>';
            
            foreach (DCF_Plugin_Core::get_us_states() as $code => $name) {
                $html .= sprintf('<option value="%s">%s</option>', esc_attr($code), esc_html($name));
            }
            
            $html .= '</select></div>';
        }
        
        if (in_array('zip', $components)) {
            $html .= sprintf(
                '<div class="dcf-address-zip"><input type="text" name="%s[zip]" placeholder="%s" %s class="dcf-input"></div>',
                esc_attr($field_name),
                esc_attr(__('ZIP Code', 'dry-cleaning-forms')),
                $required ? 'required' : ''
            );
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Generate form shortcode
     *
     * @param int $form_id Form ID
     * @return string Shortcode
     */
    public function generate_shortcode($form_id) {
        return '[dcf_form id="' . intval($form_id) . '"]';
    }
    
    /**
     * Validate form configuration
     *
     * @param array $form_config Form configuration
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_form_config($form_config) {
        if (!isset($form_config['fields']) || !is_array($form_config['fields'])) {
            return new WP_Error('missing_fields', __('Form must have at least one field', 'dry-cleaning-forms'));
        }
        
        foreach ($form_config['fields'] as $field) {
            if (!isset($field['type']) || !isset($field['id'])) {
                return new WP_Error('invalid_field', __('Each field must have a type and ID', 'dry-cleaning-forms'));
            }
            
            if (!$this->get_field_type($field['type'])) {
                return new WP_Error('invalid_field_type', sprintf(__('Invalid field type: %s', 'dry-cleaning-forms'), $field['type']));
            }
        }
        
        return true;
    }
    
    /**
     * Initialize form templates
     */
    private function init_form_templates() {
        $this->form_templates = array(
            'contact' => array(
                'name' => __('Contact Form', 'dry-cleaning-forms'),
                'description' => __('Standard contact form for inquiries', 'dry-cleaning-forms'),
                'icon' => 'dashicons-email-alt',
                'form_type' => 'contact',
                'config' => array(
                    'fields' => array(
                        array(
                            'id' => 'name',
                            'type' => 'text',
                            'label' => __('Your Name', 'dry-cleaning-forms'),
                            'placeholder' => __('John Doe', 'dry-cleaning-forms'),
                            'required' => true
                        ),
                        array(
                            'id' => 'email',
                            'type' => 'email',
                            'label' => __('Email Address', 'dry-cleaning-forms'),
                            'placeholder' => __('your@email.com', 'dry-cleaning-forms'),
                            'required' => true
                        ),
                        array(
                            'id' => 'subject',
                            'type' => 'text',
                            'label' => __('Subject', 'dry-cleaning-forms'),
                            'placeholder' => __('How can we help?', 'dry-cleaning-forms'),
                            'required' => true
                        ),
                        array(
                            'id' => 'message',
                            'type' => 'textarea',
                            'label' => __('Message', 'dry-cleaning-forms'),
                            'placeholder' => __('Enter your message here...', 'dry-cleaning-forms'),
                            'required' => true,
                            'rows' => 5
                        ),
                        array(
                            'id' => 'submit',
                            'type' => 'submit',
                            'button_text' => __('Send Message', 'dry-cleaning-forms'),
                            'alignment' => 'center'
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Get form templates
     *
     * @return array Form templates
     */
    public function get_form_templates() {
        return apply_filters('dcf_form_templates', $this->form_templates);
    }
    
    /**
     * Get form template by key
     *
     * @param string $template_key Template key
     * @return array|null Template data
     */
    public function get_form_template($template_key) {
        $templates = $this->get_form_templates();
        return isset($templates[$template_key]) ? $templates[$template_key] : null;
    }
    
    /**
     * Create form from template
     *
     * @param string $template_key Template key
     * @param array $overrides Optional overrides
     * @return int|WP_Error Form ID on success, WP_Error on failure
     */
    public function create_form_from_template($template_key, $overrides = array()) {
        $template = $this->get_form_template($template_key);
        
        if (!$template) {
            return new WP_Error('template_not_found', __('Form template not found', 'dry-cleaning-forms'));
        }
        
        // Get template config
        $form_config = $template['config'];
        
        // Ensure fields array exists
        if (!isset($form_config['fields'])) {
            $form_config['fields'] = array();
        }
        
        // Get existing field IDs
        $existing_field_ids = array_map(function($field) {
            return isset($field['id']) ? $field['id'] : '';
        }, $form_config['fields']);
        
        // Add UTM fields if they don't exist
        $utm_fields = $this->get_utm_fields();
        foreach ($utm_fields as $utm_field) {
            if (!in_array($utm_field['id'], $existing_field_ids)) {
                $form_config['fields'][] = $utm_field;
            }
        }
        
        $form_data = array(
            'form_name' => isset($overrides['form_name']) ? $overrides['form_name'] : $template['name'],
            'form_type' => $template['form_type'],
            'form_config' => $form_config,
            'webhook_url' => isset($overrides['webhook_url']) ? $overrides['webhook_url'] : ''
        );
        
        return $this->create_form($form_data);
    }
    
    /**
     * Find field by ID
     *
     * @param string $field_id Field ID to find
     * @param array $fields Array of fields
     * @return array|null Field data or null if not found
     */
    private function find_field_by_id($field_id, $fields) {
        foreach ($fields as $field) {
            if (isset($field['id']) && $field['id'] === $field_id) {
                return $field;
            }
        }
        return null;
    }
    
    /**
     * Get default UTM hidden fields
     *
     * @return array UTM fields configuration
     */
    private function get_utm_fields() {
        $utm_fields = array(
            array(
                'id' => 'utm_source',
                'type' => 'hidden',
                'label' => 'UTM Source',
                'required' => false
            ),
            array(
                'id' => 'utm_medium',
                'type' => 'hidden',
                'label' => 'UTM Medium',
                'required' => false
            ),
            array(
                'id' => 'utm_campaign',
                'type' => 'hidden',
                'label' => 'UTM Campaign',
                'required' => false
            ),
            array(
                'id' => 'utm_content',
                'type' => 'hidden',
                'label' => 'UTM Content',
                'required' => false
            ),
            array(
                'id' => 'utm_keyword',
                'type' => 'hidden',
                'label' => 'UTM Keyword',
                'required' => false
            ),
            array(
                'id' => 'utm_matchtype',
                'type' => 'hidden',
                'label' => 'UTM Match Type',
                'required' => false
            ),
            array(
                'id' => 'campaign_id',
                'type' => 'hidden',
                'label' => 'Campaign ID',
                'required' => false
            ),
            array(
                'id' => 'ad_group_id',
                'type' => 'hidden',
                'label' => 'Ad Group ID',
                'required' => false
            ),
            array(
                'id' => 'ad_id',
                'type' => 'hidden',
                'label' => 'Ad ID',
                'required' => false
            ),
            array(
                'id' => 'gclid',
                'type' => 'hidden',
                'label' => 'Google Click ID',
                'required' => false
            )
        );
        
        return apply_filters('dcf_utm_fields', $utm_fields);
    }
    
    /**
     * Get US states for select fields
     *
     * @return array US states
     */
    private function get_us_states() {
        return array(
            array('value' => 'AL', 'label' => 'Alabama'),
            array('value' => 'AK', 'label' => 'Alaska'),
            array('value' => 'AZ', 'label' => 'Arizona'),
            array('value' => 'AR', 'label' => 'Arkansas'),
            array('value' => 'CA', 'label' => 'California'),
            array('value' => 'CO', 'label' => 'Colorado'),
            array('value' => 'CT', 'label' => 'Connecticut'),
            array('value' => 'DE', 'label' => 'Delaware'),
            array('value' => 'FL', 'label' => 'Florida'),
            array('value' => 'GA', 'label' => 'Georgia'),
            array('value' => 'HI', 'label' => 'Hawaii'),
            array('value' => 'ID', 'label' => 'Idaho'),
            array('value' => 'IL', 'label' => 'Illinois'),
            array('value' => 'IN', 'label' => 'Indiana'),
            array('value' => 'IA', 'label' => 'Iowa'),
            array('value' => 'KS', 'label' => 'Kansas'),
            array('value' => 'KY', 'label' => 'Kentucky'),
            array('value' => 'LA', 'label' => 'Louisiana'),
            array('value' => 'ME', 'label' => 'Maine'),
            array('value' => 'MD', 'label' => 'Maryland'),
            array('value' => 'MA', 'label' => 'Massachusetts'),
            array('value' => 'MI', 'label' => 'Michigan'),
            array('value' => 'MN', 'label' => 'Minnesota'),
            array('value' => 'MS', 'label' => 'Mississippi'),
            array('value' => 'MO', 'label' => 'Missouri'),
            array('value' => 'MT', 'label' => 'Montana'),
            array('value' => 'NE', 'label' => 'Nebraska'),
            array('value' => 'NV', 'label' => 'Nevada'),
            array('value' => 'NH', 'label' => 'New Hampshire'),
            array('value' => 'NJ', 'label' => 'New Jersey'),
            array('value' => 'NM', 'label' => 'New Mexico'),
            array('value' => 'NY', 'label' => 'New York'),
            array('value' => 'NC', 'label' => 'North Carolina'),
            array('value' => 'ND', 'label' => 'North Dakota'),
            array('value' => 'OH', 'label' => 'Ohio'),
            array('value' => 'OK', 'label' => 'Oklahoma'),
            array('value' => 'OR', 'label' => 'Oregon'),
            array('value' => 'PA', 'label' => 'Pennsylvania'),
            array('value' => 'RI', 'label' => 'Rhode Island'),
            array('value' => 'SC', 'label' => 'South Carolina'),
            array('value' => 'SD', 'label' => 'South Dakota'),
            array('value' => 'TN', 'label' => 'Tennessee'),
            array('value' => 'TX', 'label' => 'Texas'),
            array('value' => 'UT', 'label' => 'Utah'),
            array('value' => 'VT', 'label' => 'Vermont'),
            array('value' => 'VA', 'label' => 'Virginia'),
            array('value' => 'WA', 'label' => 'Washington'),
            array('value' => 'WV', 'label' => 'West Virginia'),
            array('value' => 'WI', 'label' => 'Wisconsin'),
            array('value' => 'WY', 'label' => 'Wyoming')
        );
    }
    
    /**
     * Track form view in analytics
     *
     * @param int $form_id Form ID
     */
    private function track_form_view($form_id) {
        global $wpdb;
        
        // Use a session variable to prevent multiple tracking within the same request
        $session_key = 'dcf_form_view_tracked_' . $form_id;
        if (isset($_SESSION[$session_key])) {
            return; // Already tracked in this session/request
        }
        
        // Also check if this is an AJAX request for form content (popup loading)
        if (defined('DOING_AJAX') && DOING_AJAX) {
            // For AJAX requests, use a transient to prevent rapid repeated tracking
            $transient_key = 'dcf_form_view_' . $form_id . '_' . md5($_SERVER['REMOTE_ADDR']);
            if (get_transient($transient_key)) {
                return; // Already tracked recently from this IP
            }
            set_transient($transient_key, true, 5); // Prevent tracking for 5 seconds
        }
        
        $analytics_table = $wpdb->prefix . 'dcf_analytics';
        $today = current_time('Y-m-d');
        
        // Try to update existing record first
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $analytics_table 
             SET views = views + 1, updated_at = NOW() 
             WHERE entity_type = 'form' AND entity_id = %s AND date = %s",
            $form_id,
            $today
        ));
        
        // If no existing record, insert new one
        if ($updated === 0) {
            $wpdb->insert(
                $analytics_table,
                array(
                    'entity_type' => 'form',
                    'entity_id' => $form_id,
                    'views' => 1,
                    'date' => $today
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
        
        // Mark as tracked for this session
        if (!session_id()) {
            @session_start();
        }
        $_SESSION[$session_key] = true;
    }
} 