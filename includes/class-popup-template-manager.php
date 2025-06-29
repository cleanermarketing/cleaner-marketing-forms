<?php
/**
 * Popup Template Manager
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Template_Manager {
    
    /**
     * Available popup templates
     *
     * @var array
     */
    private $templates = array();
    
    /**
     * Template directory path
     *
     * @var string
     */
    private $template_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->template_dir = plugin_dir_path(dirname(__FILE__)) . 'public/templates/popup-templates/';
        $this->load_templates();
    }
    
    /**
     * Load all available templates
     */
    private function load_templates() {
        $template_files = glob($this->template_dir . '*.php');
        
        foreach ($template_files as $file) {
            $template_config = include $file;
            
            if (is_array($template_config) && isset($template_config['name'])) {
                $template_id = basename($file, '.php');
                $template_config['id'] = $template_id;
                $this->templates[$template_id] = $template_config;
            }
        }
    }
    
    /**
     * Get all available templates
     *
     * @return array
     */
    public function get_templates() {
        return $this->templates;
    }
    
    /**
     * Get templates by category
     *
     * @param string $category
     * @return array
     */
    public function get_templates_by_category($category = '') {
        if (empty($category)) {
            return $this->templates;
        }
        
        $filtered = array();
        foreach ($this->templates as $id => $template) {
            if (isset($template['category']) && $template['category'] === $category) {
                $filtered[$id] = $template;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get a specific template
     *
     * @param string $template_id
     * @return array|null
     */
    public function get_template($template_id) {
        return isset($this->templates[$template_id]) ? $this->templates[$template_id] : null;
    }
    
    /**
     * Apply template to popup data
     *
     * @param string $template_id
     * @param array $popup_data
     * @return array
     */
    public function apply_template($template_id, $popup_data = array()) {
        $template = $this->get_template($template_id);
        if (!$template) {
            return $popup_data;
        }
        
        // Merge template defaults with existing popup data
        $merged_data = array_merge($popup_data, array(
            'template_id' => $template_id,
            'popup_type' => $template['type'],
            'settings' => array_merge(
                isset($template['default_settings']) ? $template['default_settings'] : array(),
                isset($popup_data['settings']) ? $popup_data['settings'] : array()
            ),
            'content' => array_merge(
                isset($template['default_content']) ? $template['default_content'] : array(),
                isset($popup_data['content']) ? $popup_data['content'] : array()
            )
        ));
        
        return $merged_data;
    }
    
    /**
     * Generate CSS for a template
     *
     * @param string $template_id
     * @param array $settings
     * @return string
     */
    public function generate_template_css($template_id, $settings = array()) {
        $template = $this->get_template($template_id);
        if (!$template || !isset($template['css_template'])) {
            return '';
        }
        
        $css = $template['css_template'];
        
        // Replace template variables with actual values
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    $css = str_replace('{{' . $key . '.' . $sub_key . '}}', $sub_value, $css);
                }
            } else {
                $css = str_replace('{{' . $key . '}}', $value, $css);
            }
        }
        
        // Handle conditional CSS (Mustache-like syntax)
        $css = preg_replace('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function($matches) use ($settings) {
            $condition = $matches[1];
            $content = $matches[2];
            return isset($settings[$condition]) && $settings[$condition] ? $content : '';
        }, $css);
        
        return $css;
    }
    
    /**
     * Get template categories
     *
     * @return array
     */
    public function get_categories() {
        $categories = array();
        foreach ($this->templates as $template) {
            if (isset($template['category']) && !in_array($template['category'], $categories)) {
                $categories[] = $template['category'];
            }
        }
        return $categories;
    }
    
    /**
     * Create popup from template
     *
     * @param string $template_id
     * @param array $overrides
     * @return array
     */
    public function create_popup_from_template($template_id, $overrides = array()) {
        $template = $this->get_template($template_id);
        if (!$template) {
            return false;
        }
        
        // First, create a form based on the template's form fields
        $form_id = '';
        
        // Check for form fields in different locations
        $has_form_fields = false;
        
        // Check for regular form fields
        if (isset($template['default_content']['form_fields']) && !empty($template['default_content']['form_fields'])) {
            $has_form_fields = true;
        }
        
        // Check for multi-step forms
        if (isset($template['default_content']['steps']) && is_array($template['default_content']['steps'])) {
            foreach ($template['default_content']['steps'] as $step) {
                if (isset($step['form_fields']) && !empty($step['form_fields'])) {
                    $has_form_fields = true;
                    break;
                }
            }
        }
        
        if ($has_form_fields) {
            $form_id = $this->create_form_for_popup($template, $overrides);
        }
        
        // Create popup config
        $popup_config = array(
            'form_id' => $form_id, // Use the newly created form ID
            'auto_close' => false,
            'auto_close_delay' => 5
        );
        
        // Add template content if available
        if (isset($template['default_content'])) {
            $popup_config['content'] = $template['default_content'];
        }
        
        // Create targeting rules
        $targeting_rules = array(
            'pages' => array('mode' => 'all'),
            'users' => array(),
            'devices' => array('types' => array('desktop', 'mobile')),
            'schedule' => array()
        );
        
        // Merge template targeting rules if available
        if (isset($template['targeting_rules'])) {
            $targeting_rules = array_merge($targeting_rules, $template['targeting_rules']);
        }
        
        // Create trigger settings
        $trigger_settings = array(
            'type' => 'time_delay',
            'delay' => 3,
            'max_displays' => 3
        );
        
        // Merge template trigger settings if available
        if (isset($template['trigger_settings'])) {
            $trigger_settings = array_merge($trigger_settings, $template['trigger_settings']);
        }
        
        // Create design settings from template
        $design_settings = array(
            'width' => '500px',
            'height' => 'auto',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'border_radius' => '8px',
            'padding' => '30px',
            'overlay_color' => 'rgba(0,0,0,0.7)',
            'animation' => 'fadeIn',
            'close_button' => true,
            'close_on_overlay' => true
        );
        
        // Merge template settings if available
        if (isset($template['default_settings'])) {
            $design_settings = array_merge($design_settings, $template['default_settings']);
        }
        
        // Override with any provided settings
        if (isset($overrides['settings'])) {
            $design_settings = array_merge($design_settings, $overrides['settings']);
        }
        
        $popup_data = array(
            'popup_name' => isset($overrides['name']) ? $overrides['name'] : $template['name'],
            'popup_type' => $template['type'],
            'template_id' => $template_id,
            'status' => 'draft',
            'popup_config' => json_encode($popup_config),
            'targeting_rules' => json_encode($targeting_rules),
            'trigger_settings' => json_encode($trigger_settings),
            'design_settings' => json_encode($design_settings)
        );
        
        return $popup_data;
    }
    
    /**
     * Create form for popup based on template
     *
     * @param array $template Template configuration
     * @param array $overrides Optional overrides
     * @return int Form ID
     */
    private function create_form_for_popup($template, $overrides = array()) {
        $form_builder = new DCF_Form_Builder();
        
        // Convert template form fields to form builder format
        $form_fields = array();
        $field_counter = 1;
        $steps = array();
        $is_multi_step = false;
        
        // Check if this is a multi-step template
        if (isset($template['default_content']['steps']) && is_array($template['default_content']['steps'])) {
            $is_multi_step = true;
            $step_fields = array();
            
            // Process multi-step form - but only include steps that have form fields
            foreach ($template['default_content']['steps'] as $step_index => $step) {
                // Skip steps that are yes/no only (no form fields)
                if (isset($step['type']) && $step['type'] === 'yes_no' && !isset($step['form_fields'])) {
                    continue;
                }
                
                $step_field_ids = array();
                
                // Support both 'fields' and 'form_fields' for backward compatibility
                $fields = isset($step['fields']) ? $step['fields'] : (isset($step['form_fields']) ? $step['form_fields'] : array());
                
                if (is_array($fields) && !empty($fields)) {
                    foreach ($fields as $field) {
                        // Generate unique field ID
                        $field_id = (isset($field['name']) ? $field['name'] : $field['type']) . '_' . $field_counter;
                        
                        $form_field = array(
                            'id' => $field_id,
                            'type' => $field['type'],
                            'label' => isset($field['label']) ? $field['label'] : '',
                            'placeholder' => isset($field['placeholder']) ? $field['placeholder'] : '',
                            'required' => isset($field['required']) ? $field['required'] : false
                        );
                        
                        // Add any additional field properties
                        if (isset($field['options'])) {
                            // Convert simple array to proper format
                            $options = array();
                            foreach ($field['options'] as $key => $value) {
                                $options[] = array('value' => $key, 'label' => $value);
                            }
                            $form_field['options'] = $options;
                        }
                        
                        if (isset($field['validation'])) {
                            $form_field['validation'] = $field['validation'];
                        }
                        
                        $form_fields[] = $form_field;
                        $step_field_ids[] = $field_id;
                        $field_counter++;
                    }
                }
                
                // Only create step configuration if it has fields
                if (!empty($step_field_ids)) {
                    $steps[] = array(
                        'id' => 'step_' . ($step_index + 1),
                        'title' => isset($step['headline']) ? $step['headline'] : 'Step ' . ($step_index + 1),
                        'fields' => $step_field_ids
                    );
                }
            }
            
            // Add submit button from the last form step
            $last_form_step = null;
            for ($i = count($template['default_content']['steps']) - 1; $i >= 0; $i--) {
                $step = $template['default_content']['steps'][$i];
                if (isset($step['type']) && $step['type'] === 'form' && isset($step['submit_button'])) {
                    $last_form_step = $step;
                    break;
                }
            }
            
            if ($last_form_step && isset($last_form_step['submit_button'])) {
                $submit_button = $last_form_step['submit_button'];
                $form_fields[] = array(
                    'id' => 'submit_' . $field_counter,
                    'type' => 'submit',
                    'button_text' => isset($submit_button['text']) ? $submit_button['text'] : 'Submit',
                    'alignment' => 'center',
                    'bg_color' => isset($submit_button['background_color']) ? $submit_button['background_color'] : '#2271b1',
                    'text_color' => isset($submit_button['text_color']) ? $submit_button['text_color'] : '#ffffff',
                    'border_radius' => '4'
                );
            } else {
                // Default submit button
                $form_fields[] = array(
                    'id' => 'submit_' . $field_counter,
                    'type' => 'submit',
                    'button_text' => 'Submit',
                    'alignment' => 'center',
                    'bg_color' => '#2271b1',
                    'text_color' => '#ffffff',
                    'border_radius' => '4'
                );
            }
            
        } else if (isset($template['default_content']['form_fields'])) {
            // Process regular single-step form
            foreach ($template['default_content']['form_fields'] as $field) {
                // Generate unique field ID
                $field_id = $field['type'] . '_' . $field_counter;
                
                $form_field = array(
                    'id' => $field_id,
                    'type' => $field['type'],
                    'label' => isset($field['label']) ? $field['label'] : '',
                    'placeholder' => isset($field['placeholder']) ? $field['placeholder'] : '',
                    'required' => isset($field['required']) ? $field['required'] : false
                );
                
                // Add any additional field properties
                if (isset($field['options'])) {
                    $form_field['options'] = $field['options'];
                }
                if (isset($field['validation'])) {
                    $form_field['validation'] = $field['validation'];
                }
                if (isset($field['width'])) {
                    $form_field['width'] = $field['width'];
                }
                
                $form_fields[] = $form_field;
                $field_counter++;
            }
            
            // Add submit button if template has one
            if (isset($template['default_content']['submit_button'])) {
                $submit_button = $template['default_content']['submit_button'];
                $form_fields[] = array(
                    'id' => 'submit_' . $field_counter,
                    'type' => 'submit',
                    'button_text' => isset($submit_button['text']) ? $submit_button['text'] : 'Submit',
                    'alignment' => 'center',
                    'bg_color' => isset($submit_button['background_color']) ? $submit_button['background_color'] : '#2271b1',
                    'text_color' => isset($submit_button['text_color']) ? $submit_button['text_color'] : '#ffffff',
                    'border_radius' => isset($submit_button['border_radius']) ? $submit_button['border_radius'] : '4'
                );
            }
        }
        
        // Create form configuration
        $form_config = array(
            'fields' => $form_fields,
            'title' => isset($template['default_content']['headline']) ? $template['default_content']['headline'] : '',
            'description' => isset($template['default_content']['description']) ? $template['default_content']['description'] : '',
            'submit_text' => isset($template['default_content']['submit_button']['text']) ? $template['default_content']['submit_button']['text'] : 'Submit'
        );
        
        // Add multi-step configuration if needed
        if ($is_multi_step && !empty($steps)) {
            $form_config['multi_step'] = true;
            $form_config['steps'] = $steps;
        }
        
        // Determine form type based on template
        $form_type = 'contact'; // Default
        if (isset($template['category'])) {
            switch ($template['category']) {
                case 'lead-capture':
                case 'exit-intent':
                    $form_type = 'signup';
                    break;
                case 'newsletter':
                    $form_type = 'newsletter';
                    break;
                case 'conversion':
                    $form_type = 'signup';
                    break;
                case 'engagement':
                    $form_type = 'contact';
                    break;
            }
        }
        
        // Override form type for specific template types
        if (isset($template['type']) && $template['type'] === 'multi-step') {
            $form_type = 'signup';
        }
        
        // Create form data
        $form_data = array(
            'form_name' => (isset($overrides['name']) ? $overrides['name'] : $template['name']) . ' Form',
            'form_type' => $form_type,
            'form_config' => $form_config
        );
        
        // Create the form
        $form_id = $form_builder->create_form($form_data);
        
        // Return form ID or empty string if creation failed
        return is_wp_error($form_id) ? '' : $form_id;
    }
    
    /**
     * Get template preview HTML
     *
     * @param string $template_id
     * @return string
     */
    public function get_template_preview($template_id) {
        $template = $this->get_template($template_id);
        if (!$template) {
            return '';
        }
        
        $settings = isset($template['default_settings']) ? $template['default_settings'] : array();
        $content = isset($template['default_content']) ? $template['default_content'] : array();
        
        // Generate preview HTML based on template type
        switch ($template['type']) {
            case 'modal':
                return $this->generate_modal_preview($content, $settings);
            case 'slide-in':
                return $this->generate_slide_in_preview($content, $settings);
            case 'sidebar':
                return $this->generate_sidebar_preview($content, $settings);
            case 'exit-intent':
                return $this->generate_exit_intent_preview($content, $settings);
            case 'multi-step':
                return $this->generate_multi_step_preview($content, $settings);
            case 'floating-bar':
                return $this->generate_floating_bar_preview($content, $settings);
            case 'fullscreen':
                return $this->generate_fullscreen_preview($content, $settings);
            case 'spin-wheel':
                return $this->generate_spin_wheel_preview($content, $settings);
            case 'newsletter':
                return $this->generate_newsletter_preview($content, $settings);
            default:
                return $this->generate_default_preview($content, $settings);
        }
    }
    
    /**
     * Generate modal preview HTML
     */
    private function generate_modal_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-modal">';
        $html .= '<div class="dcf-preview-overlay"></div>';
        $html .= '<div class="dcf-preview-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['headline'])) {
            $html .= '<h3 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h3>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<p class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</p>';
        }
        
        if (isset($content['description'])) {
            $html .= '<p class="dcf-preview-description">' . esc_html($content['description']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="Email address..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Submit') . '</button>';
        $html .= '</div>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate slide-in preview HTML
     */
    private function generate_slide_in_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-slide-in">';
        $html .= '<div class="dcf-preview-popup dcf-slide-in-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['headline'])) {
            $html .= '<h4 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h4>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<p class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="Email..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Submit') . '</button>';
        $html .= '</div>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate sidebar preview HTML
     */
    private function generate_sidebar_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-sidebar">';
        $html .= '<div class="dcf-preview-popup dcf-sidebar-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['headline'])) {
            $html .= '<h4 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h4>';
        }
        
        if (isset($content['description'])) {
            $html .= '<p class="dcf-preview-description">' . esc_html($content['description']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="Email..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Submit') . '</button>';
        $html .= '</div>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate exit-intent preview HTML
     */
    private function generate_exit_intent_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-exit-intent">';
        $html .= '<div class="dcf-preview-overlay"></div>';
        $html .= '<div class="dcf-preview-popup dcf-exit-intent-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['headline'])) {
            $html .= '<h3 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h3>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<p class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</p>';
        }
        
        if (isset($content['urgency_text'])) {
            $html .= '<div class="dcf-preview-urgency">' . esc_html($content['urgency_text']) . '</div>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="Email..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Submit') . '</button>';
        $html .= '</div>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate multi-step preview HTML
     */
    private function generate_multi_step_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-multi-step">';
        $html .= '<div class="dcf-preview-overlay"></div>';
        $html .= '<div class="dcf-preview-popup dcf-multi-step-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-progress"><div class="dcf-preview-progress-bar"></div></div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['steps'][0]['headline'])) {
            $html .= '<h3 class="dcf-preview-headline">' . esc_html($content['steps'][0]['headline']) . '</h3>';
        }
        
        if (isset($content['steps'][0]['description'])) {
            $html .= '<p class="dcf-preview-description">' . esc_html($content['steps'][0]['description']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-options">';
        $html .= '<div class="dcf-preview-option">Option 1</div>';
        $html .= '<div class="dcf-preview-option">Option 2</div>';
        $html .= '</div>';
        
        $html .= '<button class="dcf-preview-button">Next Step</button>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate default preview HTML
     */
    private function generate_default_preview($content, $settings) {
        return $this->generate_modal_preview($content, $settings);
    }
    
    /**
     * Generate floating bar preview HTML
     */
    private function generate_floating_bar_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-floating-bar">';
        $html .= '<div class="dcf-preview-floating-bar">';
        $html .= '<div class="dcf-preview-floating-bar-content">';
        
        if (isset($content['headline'])) {
            $html .= '<span class="dcf-preview-headline">' . esc_html($content['headline']) . '</span>';
        }
        
        if (isset($content['description'])) {
            $html .= '<span class="dcf-preview-description">' . esc_html($content['description']) . '</span>';
        }
        
        $html .= '<div class="dcf-preview-form-inline">';
        $html .= '<input type="email" placeholder="Email..." class="dcf-preview-input-inline">';
        $html .= '<button class="dcf-preview-button-inline">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Submit') . '</button>';
        $html .= '</div>';
        
        $html .= '<span class="dcf-preview-close">×</span>';
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate fullscreen preview HTML
     */
    private function generate_fullscreen_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-fullscreen">';
        $html .= '<div class="dcf-preview-fullscreen">';
        $html .= '<div class="dcf-preview-fullscreen-content">';
        $html .= '<div class="dcf-preview-close">×</div>';
        
        if (isset($content['headline'])) {
            $html .= '<h2 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h2>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<h3 class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</h3>';
        }
        
        if (isset($content['description'])) {
            $html .= '<p class="dcf-preview-description">' . esc_html($content['description']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="text" placeholder="Name..." class="dcf-preview-input">';
        $html .= '<input type="email" placeholder="Email..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Get Started') . '</button>';
        $html .= '</div>';
        
        if (isset($content['benefits']) && is_array($content['benefits'])) {
            $html .= '<div class="dcf-preview-benefits">';
            foreach (array_slice($content['benefits'], 0, 2) as $benefit) {
                $html .= '<span class="dcf-preview-benefit">✓ ' . esc_html($benefit) . '</span>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate spin wheel preview HTML
     */
    private function generate_spin_wheel_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-spin-wheel">';
        $html .= '<div class="dcf-preview-overlay"></div>';
        $html .= '<div class="dcf-preview-popup dcf-spin-wheel-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content dcf-spin-wheel-content">';
        
        // Left side - wheel
        $html .= '<div class="dcf-preview-wheel-container">';
        $html .= '<div class="dcf-preview-wheel">';
        if (isset($content['prizes']) && is_array($content['prizes'])) {
            foreach (array_slice($content['prizes'], 0, 3) as $index => $prize) {
                $rotation = $index * 120; // 3 segments shown
                $html .= '<div class="dcf-preview-wheel-segment" style="transform: rotate(' . $rotation . 'deg);">';
                $html .= '<span class="dcf-preview-wheel-text">' . esc_html($prize['text']) . '</span>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        $html .= '<div class="dcf-preview-wheel-pointer">▼</div>';
        $html .= '</div>';
        
        // Right side - form
        $html .= '<div class="dcf-preview-form-section">';
        if (isset($content['headline'])) {
            $html .= '<h3 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h3>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<p class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="Email address..." class="dcf-preview-input">';
        $html .= '<button class="dcf-preview-button dcf-spin-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'SPIN NOW!') . '</button>';
        $html .= '</div>';
        
        if (isset($content['terms_text'])) {
            $html .= '<p class="dcf-preview-terms">' . esc_html($content['terms_text']) . '</p>';
        }
        $html .= '</div>';
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Generate newsletter preview HTML
     */
    private function generate_newsletter_preview($content, $settings) {
        $html = '<div class="dcf-template-preview dcf-template-newsletter">';
        $html .= '<div class="dcf-preview-overlay"></div>';
        $html .= '<div class="dcf-preview-popup dcf-newsletter-popup">';
        $html .= '<div class="dcf-preview-close">×</div>';
        $html .= '<div class="dcf-preview-content">';
        
        if (isset($content['headline'])) {
            $html .= '<h3 class="dcf-preview-headline">' . esc_html($content['headline']) . '</h3>';
        }
        
        if (isset($content['subheadline'])) {
            $html .= '<p class="dcf-preview-subheadline">' . esc_html($content['subheadline']) . '</p>';
        }
        
        $html .= '<div class="dcf-preview-form">';
        $html .= '<input type="email" placeholder="' . (isset($content['form_fields'][0]['placeholder']) ? esc_attr($content['form_fields'][0]['placeholder']) : 'Enter your email address') . '" class="dcf-preview-input dcf-newsletter-input">';
        $html .= '<button class="dcf-preview-button dcf-newsletter-button">' . (isset($content['submit_button']['text']) ? esc_html($content['submit_button']['text']) : 'Subscribe') . '</button>';
        $html .= '</div>';
        
        if (isset($content['privacy_text'])) {
            $html .= '<p class="dcf-preview-privacy">' . esc_html($content['privacy_text']) . '</p>';
        }
        
        $html .= '</div></div></div>';
        
        return $html;
    }
} 