<?php
/**
 * Multi-Step Popup Handler Class
 *
 * Handles the rendering and processing of multi-step popups with branching logic
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Multi_Step_Handler {
    
    /**
     * Render multi-step popup content
     *
     * @param array $popup_config The popup configuration
     * @param array $template_content The template content with steps
     * @return string HTML content for the popup
     */
    public static function render_multi_step_content($popup_config, $template_content) {
        if (!isset($template_content['steps']) || !is_array($template_content['steps'])) {
            return '<div class="dcf-error">No steps defined for this popup.</div>';
        }
        
        $html = '<div class="dcf-multi-step-popup" data-current-step="0">';
        
        // Render each step
        foreach ($template_content['steps'] as $index => $step) {
            $step_id = isset($step['id']) ? $step['id'] : 'step_' . $index;
            $display = $index === 0 ? 'block' : 'none';
            
            $html .= '<div class="dcf-popup-step" data-step-id="' . esc_attr($step_id) . '" data-step-index="' . $index . '" style="display: ' . $display . ';">';
            
            // Step content based on type
            if (isset($step['type'])) {
                switch ($step['type']) {
                    case 'yes_no':
                        $html .= self::render_yes_no_step($step);
                        break;
                    case 'form':
                        $html .= self::render_form_step($step, $popup_config);
                        break;
                    default:
                        $html .= self::render_content_step($step);
                        break;
                }
            } else {
                $html .= self::render_content_step($step);
            }
            
            $html .= '</div>'; // .dcf-popup-step
        }
        
        $html .= '</div>'; // .dcf-multi-step-popup
        
        // Add JavaScript configuration
        $html .= '<script type="text/javascript">';
        $html .= 'var dcfMultiStepConfig = ' . json_encode(array(
            'steps' => $template_content['steps']
        )) . ';';
        $html .= '</script>';
        
        return $html;
    }
    
    /**
     * Render Yes/No step
     */
    private static function render_yes_no_step($step) {
        $html = '';
        
        // Headline
        if (!empty($step['headline'])) {
            $html .= '<h2 class="dcf-popup-headline">' . wp_kses_post($step['headline']) . '</h2>';
        }
        
        // Subheadline
        if (!empty($step['subheadline'])) {
            $html .= '<h3 class="dcf-popup-subheadline">' . wp_kses_post($step['subheadline']) . '</h3>';
        }
        
        // Description
        if (!empty($step['description'])) {
            $html .= '<p class="dcf-popup-description">' . esc_html($step['description']) . '</p>';
        }
        
        // Countdown timer
        if (!empty($step['show_countdown']) && !empty($step['countdown_end'])) {
            $html .= DCF_Popup_Countdown::generate_countdown_html($step['countdown_end']);
        }
        
        // Yes/No buttons
        $html .= '<div class="dcf-popup-buttons">';
        
        if (!empty($step['yes_button'])) {
            $yes_action = isset($step['yes_button']['next_step']) ? 
                'data-next-step="' . esc_attr($step['yes_button']['next_step']) . '"' : 
                'data-action="close"';
            
            $html .= '<button class="dcf-popup-button dcf-yes-button" ' . $yes_action . '>';
            $html .= esc_html($step['yes_button']['text']);
            $html .= '</button>';
        }
        
        if (!empty($step['no_button'])) {
            $no_action = isset($step['no_button']['next_step']) ? 
                'data-next-step="' . esc_attr($step['no_button']['next_step']) . '"' : 
                'data-action="close"';
            
            $html .= '<button class="dcf-popup-button dcf-no-button secondary" ' . $no_action . '>';
            $html .= esc_html($step['no_button']['text']);
            $html .= '</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render form step
     */
    private static function render_form_step($step, $popup_config) {
        $html = '';
        
        // Headline
        if (!empty($step['headline'])) {
            $html .= '<h2 class="dcf-popup-headline">' . wp_kses_post($step['headline']) . '</h2>';
        }
        
        // Subheadline
        if (!empty($step['subheadline'])) {
            $html .= '<h3 class="dcf-popup-subheadline">' . wp_kses_post($step['subheadline']) . '</h3>';
        }
        
        // Description
        if (!empty($step['description'])) {
            $html .= '<p class="dcf-popup-description">' . esc_html($step['description']) . '</p>';
        }
        
        // Countdown timer
        if (!empty($step['show_countdown']) && !empty($step['countdown_end'])) {
            $html .= DCF_Popup_Countdown::generate_countdown_html($step['countdown_end']);
        }
        
        // Form fields
        if (!empty($step['form_fields'])) {
            $html .= '<form class="dcf-popup-form dcf-multi-step-form">';
            $html .= '<div class="dcf-form-wrapper">';
            
            foreach ($step['form_fields'] as $field) {
                $html .= self::render_form_field($field);
            }
            
            $html .= '</div>'; // .dcf-form-wrapper
            
            // Buttons (handle both submit_button and buttons array)
            if (!empty($step['buttons']) && is_array($step['buttons'])) {
                $html .= '<div class="dcf-popup-buttons">';
                foreach ($step['buttons'] as $button) {
                    $button_class = isset($button['class']) ? $button['class'] : 'dcf-popup-button';
                    $button_type = isset($button['type']) ? $button['type'] : 'button';
                    
                    if ($button_type === 'submit') {
                        $html .= '<button type="submit" class="' . esc_attr($button_class) . '"';
                    } else {
                        $html .= '<button type="button" class="' . esc_attr($button_class) . '"';
                    }
                    
                    if (!empty($button['next_step'])) {
                        $html .= ' data-next-step="' . esc_attr($button['next_step']) . '"';
                    }
                    if (!empty($button['action'])) {
                        $html .= ' data-action="' . esc_attr($button['action']) . '"';
                    }
                    
                    $html .= '>' . esc_html($button['text']) . '</button>';
                }
                $html .= '</div>';
            } elseif (!empty($step['submit_button'])) {
                // Fallback for old format
                $html .= '<button type="submit" class="dcf-popup-button dcf-popup-submit">';
                $html .= esc_html($step['submit_button']['text']);
                $html .= '</button>';
            }
            
            $html .= '</form>';
        } elseif (!empty($popup_config['form_id'])) {
            // Use existing form
            $form_builder = new DCF_Form_Builder();
            $html .= $form_builder->render_form($popup_config['form_id'], array('popup_mode' => true));
        }
        
        return $html;
    }
    
    /**
     * Render content step (generic)
     */
    private static function render_content_step($step) {
        $html = '';
        
        // Headline
        if (!empty($step['headline'])) {
            $html .= '<h2 class="dcf-popup-headline">' . wp_kses_post($step['headline']) . '</h2>';
        }
        
        // Subheadline  
        if (!empty($step['subheadline'])) {
            $html .= '<h3 class="dcf-popup-subheadline">' . wp_kses_post($step['subheadline']) . '</h3>';
        }
        
        // Content
        if (!empty($step['content'])) {
            $html .= '<div class="dcf-popup-content">' . wp_kses_post($step['content']) . '</div>';
        }
        
        // Buttons
        if (!empty($step['buttons']) && is_array($step['buttons'])) {
            $html .= '<div class="dcf-popup-buttons">';
            foreach ($step['buttons'] as $button) {
                $button_class = isset($button['class']) ? $button['class'] : 'dcf-popup-button';
                $button_type = isset($button['type']) ? $button['type'] : 'button';
                
                $html .= '<button type="' . esc_attr($button_type) . '" class="' . esc_attr($button_class) . '"';
                
                if (!empty($button['next_step'])) {
                    $html .= ' data-next-step="' . esc_attr($button['next_step']) . '"';
                }
                if (!empty($button['action'])) {
                    $html .= ' data-action="' . esc_attr($button['action']) . '"';
                }
                
                $html .= '>' . esc_html($button['text']) . '</button>';
            }
            $html .= '</div>';
        } elseif (!empty($step['next_button'])) {
            // Fallback for old format
            $next_action = isset($step['next_button']['next_step']) ? 
                'data-next-step="' . esc_attr($step['next_button']['next_step']) . '"' : 
                'data-action="close"';
            
            $html .= '<button class="dcf-popup-button dcf-next-button" ' . $next_action . '>';
            $html .= esc_html($step['next_button']['text']);
            $html .= '</button>';
        }
        
        return $html;
    }
    
    /**
     * Render individual form field
     */
    private static function render_form_field($field) {
        $field_id = 'dcf_field_' . uniqid();
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        $width = isset($field['width']) ? $field['width'] : '100';
        
        $custom_classes = isset($field['custom_classes']) ? ' ' . esc_attr($field['custom_classes']) : '';
        $html = '<div class="dcf-form-field dcf-popup-field' . $custom_classes . '" style="width: ' . esc_attr($width) . '%;">';
        
        if (!empty($field['label'])) {
            $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label']) . '</label>';
        }
        
        switch ($field['type']) {
            case 'text':
                $html .= '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" placeholder="' . esc_attr($placeholder) . '" ' . $required . '>';
                break;
                
            case 'email':
                $field_name = isset($field['name']) ? $field['name'] : $field_id;
                $html .= '<input type="email" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" placeholder="' . esc_attr($placeholder) . '" ' . $required . '>';
                break;
                
            case 'tel':
                $html .= '<input type="tel" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" placeholder="' . esc_attr($placeholder) . '" ' . $required . '>';
                break;
                
            case 'textarea':
                $rows = isset($field['rows']) ? $field['rows'] : 4;
                $html .= '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" rows="' . esc_attr($rows) . '" placeholder="' . esc_attr($placeholder) . '" ' . $required . '></textarea>';
                break;
                
            case 'select':
                $html .= '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" ' . $required . '>';
                if ($placeholder) {
                    $html .= '<option value="">' . esc_html($placeholder) . '</option>';
                }
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $value => $label) {
                        $html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
}