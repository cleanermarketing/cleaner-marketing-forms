<?php
/**
 * Template Manager Class
 *
 * Unified template management for forms and popups
 *
 * @package CleanerMarketingForms
 */

class DCF_Template_Manager {
    
    /**
     * Instance of this class
     *
     * @var DCF_Template_Manager
     */
    private static $instance = null;
    
    /**
     * Template types
     *
     * @var array
     */
    private $template_types = array();
    
    /**
     * Template categories
     *
     * @var array
     */
    private $template_categories = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_template_types();
        $this->init_template_categories();
    }
    
    /**
     * Get instance
     *
     * @return DCF_Template_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize template types
     */
    private function init_template_types() {
        $this->template_types = array(
            'popup' => array(
                'label' => __('Popup', 'dry-cleaning-forms'),
                'icon' => 'dashicons-external',
                'description' => __('Modal overlays that appear on top of your content', 'dry-cleaning-forms')
            ),
            'slide-in' => array(
                'label' => __('Slide-in', 'dry-cleaning-forms'),
                'icon' => 'dashicons-slides',
                'description' => __('Forms that slide in from the edge of the screen', 'dry-cleaning-forms')
            ),
            'floating-bar' => array(
                'label' => __('Floating Bar', 'dry-cleaning-forms'),
                'icon' => 'dashicons-minus',
                'description' => __('Sticky bars that appear at the top or bottom of the page', 'dry-cleaning-forms')
            ),
            'fullscreen' => array(
                'label' => __('Fullscreen', 'dry-cleaning-forms'),
                'icon' => 'dashicons-editor-expand',
                'description' => __('Full page takeover forms for maximum impact', 'dry-cleaning-forms')
            ),
            'inline' => array(
                'label' => __('Inline', 'dry-cleaning-forms'),
                'icon' => 'dashicons-align-center',
                'description' => __('Forms that are embedded within your content', 'dry-cleaning-forms')
            ),
            'sidebar' => array(
                'label' => __('Sidebar', 'dry-cleaning-forms'),
                'icon' => 'dashicons-align-right',
                'description' => __('Forms designed to fit in widget areas', 'dry-cleaning-forms')
            ),
            'spin-wheel' => array(
                'label' => __('Spin Wheel', 'dry-cleaning-forms'),
                'icon' => 'dashicons-image-rotate',
                'description' => __('Interactive gamified popups with spin-to-win functionality', 'dry-cleaning-forms')
            ),
            'newsletter' => array(
                'label' => __('Newsletter', 'dry-cleaning-forms'),
                'icon' => 'dashicons-email-alt',
                'description' => __('Specialized forms for newsletter subscriptions', 'dry-cleaning-forms')
            )
        );
    }
    
    /**
     * Initialize template categories
     */
    private function init_template_categories() {
        $this->template_categories = array(
            'seasonal' => array(
                'label' => __('Seasonal', 'dry-cleaning-forms'),
                'icon' => 'dashicons-calendar-alt',
                'options' => array(
                    'holiday' => __('Holiday', 'dry-cleaning-forms'),
                    'summer' => __('Summer', 'dry-cleaning-forms'),
                    'winter' => __('Winter', 'dry-cleaning-forms'),
                    'spring' => __('Spring', 'dry-cleaning-forms')
                )
            ),
            'goals' => array(
                'label' => __('Goals', 'dry-cleaning-forms'),
                'icon' => 'dashicons-awards',
                'options' => array(
                    'lead-capture' => __('Lead Capture', 'dry-cleaning-forms'),
                    'conversion' => __('Conversion', 'dry-cleaning-forms'),
                    'engagement' => __('Engagement', 'dry-cleaning-forms'),
                    'newsletter' => __('Newsletter', 'dry-cleaning-forms'),
                    'gamification' => __('Gamification', 'dry-cleaning-forms')
                )
            ),
            'industry' => array(
                'label' => __('Industry', 'dry-cleaning-forms'),
                'icon' => 'dashicons-building',
                'options' => array(
                    'dry-cleaning' => __('Dry Cleaning', 'dry-cleaning-forms'),
                    'laundry' => __('Laundry Services', 'dry-cleaning-forms'),
                    'alterations' => __('Alterations', 'dry-cleaning-forms'),
                    'general' => __('General Business', 'dry-cleaning-forms')
                )
            ),
            'features' => array(
                'label' => __('Features', 'dry-cleaning-forms'),
                'icon' => 'dashicons-admin-generic',
                'options' => array(
                    'exit-intent' => __('Exit Intent', 'dry-cleaning-forms'),
                    'countdown' => __('Countdown Timer', 'dry-cleaning-forms'),
                    'multi-step' => __('Multi-Step', 'dry-cleaning-forms'),
                    'gamification' => __('Gamification', 'dry-cleaning-forms')
                )
            )
        );
    }
    
    /**
     * Get all templates
     *
     * @return array
     */
    public function get_all_templates() {
        $templates = array();
        
        // Get popup templates
        $popup_templates = $this->get_popup_templates();
        foreach ($popup_templates as $template) {
            $template['template_type'] = $this->map_popup_type_to_template_type($template['type']);
            // Add placeholder preview image if not set
            if (empty($template['preview_image']) || !file_exists(CMF_PLUGIN_DIR . 'assets/images/' . $template['preview_image'])) {
                $template['preview_image'] = CMF_PLUGIN_URL . 'assets/images/template-placeholder.svg';
            }
            $templates[] = $template;
        }
        
        // Get form templates
        $form_templates = $this->get_form_templates();
        foreach ($form_templates as $template) {
            $template['template_type'] = 'inline';
            $templates[] = $template;
        }
        
        return $templates;
    }
    
    /**
     * Get popup templates
     *
     * @return array
     */
    private function get_popup_templates() {
        $popup_manager = new DCF_Popup_Template_Manager();
        return $popup_manager->get_templates();
    }
    
    /**
     * Get form templates
     *
     * @return array
     */
    private function get_form_templates() {
        $form_builder = new DCF_Form_Builder();
        $form_templates = $form_builder->get_form_templates();
        
        // Transform form templates to match popup template structure
        $templates = array();
        foreach ($form_templates as $key => $template) {
            $templates[] = array(
                'id' => $key,
                'name' => $template['name'],
                'description' => $template['description'],
                'type' => 'inline',
                'category' => $this->determine_form_category($template),
                'preview_image' => $this->generate_form_preview_url($key),
                'is_form' => true,
                'config' => $template['config']
            );
        }
        
        return $templates;
    }
    
    /**
     * Map popup type to template type
     *
     * @param string $popup_type
     * @return string
     */
    private function map_popup_type_to_template_type($popup_type) {
        $mapping = array(
            'modal' => 'popup',
            'slide-in' => 'slide-in',
            'sidebar' => 'sidebar',
            'exit-intent' => 'popup',
            'multi-step' => 'popup',
            'time-delay' => 'popup',
            'floating-bar' => 'floating-bar',
            'fullscreen' => 'fullscreen',
            'spin-wheel' => 'popup',
            'newsletter' => 'popup',
            'lead-capture' => 'popup'
        );
        
        return isset($mapping[$popup_type]) ? $mapping[$popup_type] : 'popup';
    }
    
    /**
     * Determine form category
     *
     * @param array $template
     * @return string
     */
    private function determine_form_category($template) {
        if (strpos($template['name'], 'Signup') !== false || strpos($template['name'], 'Registration') !== false) {
            return 'lead-capture';
        } elseif (strpos($template['name'], 'Contact') !== false) {
            return 'contact';
        }
        
        return 'general';
    }
    
    /**
     * Generate form preview URL
     *
     * @param string $template_id
     * @return string
     */
    private function generate_form_preview_url($template_id) {
        // For now, return a placeholder image
        // In the future, we can generate actual preview images
        return CMF_PLUGIN_URL . 'assets/images/template-placeholder.svg';
    }
    
    /**
     * Get templates by type
     *
     * @param string $type
     * @return array
     */
    public function get_templates_by_type($type) {
        $all_templates = $this->get_all_templates();
        
        if ($type === 'all') {
            return $all_templates;
        }
        
        return array_filter($all_templates, function($template) use ($type) {
            return $template['template_type'] === $type;
        });
    }
    
    /**
     * Get templates by category
     *
     * @param string $category
     * @return array
     */
    public function get_templates_by_category($category) {
        $all_templates = $this->get_all_templates();
        
        if ($category === 'all') {
            return $all_templates;
        }
        
        return array_filter($all_templates, function($template) use ($category) {
            return $template['category'] === $category;
        });
    }
    
    /**
     * Search templates
     *
     * @param string $query
     * @return array
     */
    public function search_templates($query) {
        $all_templates = $this->get_all_templates();
        $query = strtolower($query);
        
        return array_filter($all_templates, function($template) use ($query) {
            return strpos(strtolower($template['name']), $query) !== false ||
                   strpos(strtolower($template['description']), $query) !== false;
        });
    }
    
    /**
     * Get template types
     *
     * @return array
     */
    public function get_template_types() {
        return $this->template_types;
    }
    
    /**
     * Get template categories
     *
     * @return array
     */
    public function get_template_categories() {
        return $this->template_categories;
    }
    
    /**
     * Generate template preview HTML
     *
     * @param array $template
     * @return string
     */
    public function generate_template_preview($template) {
        if (isset($template['is_form']) && $template['is_form']) {
            return $this->generate_form_preview($template);
        } else {
            // Use popup template manager's preview generation
            $popup_manager = new DCF_Popup_Template_Manager();
            return $popup_manager->get_template_preview($template['id']);
        }
    }
    
    /**
     * Generate form preview HTML
     *
     * @param array $template
     * @return string
     */
    private function generate_form_preview($template) {
        $html = '<div class="dcf-template-preview dcf-template-inline-form">';
        $html .= '<div class="dcf-preview-form-container">';
        $html .= '<div class="dcf-preview-form-header">';
        
        if (isset($template['name'])) {
            $html .= '<h4 class="dcf-preview-form-title">' . esc_html($template['name']) . '</h4>';
        }
        
        $html .= '</div>';
        $html .= '<div class="dcf-preview-form-body">';
        
        // Show form fields
        if (isset($template['config']['fields'])) {
            $field_count = 0;
            foreach ($template['config']['fields'] as $field) {
                if ($field_count >= 3) break; // Show max 3 fields in preview
                
                $html .= '<div class="dcf-preview-field">';
                $html .= '<label class="dcf-preview-label">' . esc_html($field['label'] ?? 'Field') . '</label>';
                $html .= '<input type="text" class="dcf-preview-input" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" disabled>';
                $html .= '</div>';
                
                $field_count++;
            }
        }
        
        $html .= '<button class="dcf-preview-button dcf-preview-submit">Submit</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Create from template
     *
     * @param string $template_id
     * @param string $name
     * @return int|false
     */
    public function create_from_template($template_id, $name) {
        $all_templates = $this->get_all_templates();
        
        // Find the template
        $template = null;
        foreach ($all_templates as $t) {
            if ($t['id'] === $template_id) {
                $template = $t;
                break;
            }
        }
        
        if (!$template) {
            return false;
        }
        
        // Check if it's a form or popup template
        if (isset($template['is_form']) && $template['is_form']) {
            // Create form from template
            $form_builder = new DCF_Form_Builder();
            $form_data = array(
                'form_name' => $name
            );
            return $form_builder->create_form_from_template($template_id, $form_data);
        } else {
            // Create popup from template
            $popup_template_manager = new DCF_Popup_Template_Manager();
            $popup_data = $popup_template_manager->create_popup_from_template($template_id, array('name' => $name));
            
            if ($popup_data) {
                // Create the popup using the popup manager
                $popup_manager = new DCF_Popup_Manager();
                $popup_id = $popup_manager->create_popup($popup_data);
                
                if ($popup_id && !is_wp_error($popup_id)) {
                    // Log success
                    error_log('DCF Popup Create: Successfully created popup ' . $popup_id);
                    return $popup_id;
                } else {
                    error_log('DCF Popup Create: Failed to create popup - ' . (is_wp_error($popup_id) ? $popup_id->get_error_message() : 'Unknown error'));
                    return false;
                }
            }
            
            return false;
        }
    }
}