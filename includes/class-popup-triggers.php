<?php
/**
 * Popup Triggers Class
 *
 * Handles different popup trigger mechanisms including exit-intent,
 * time-based, scroll-based, and click-triggered popups
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Triggers {
    
    /**
     * Popup manager instance
     */
    private $popup_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->popup_manager = new DCF_Popup_Manager();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_footer', array($this, 'render_popup_triggers'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_trigger_scripts'));
        add_action('wp_ajax_dcf_check_popup_eligibility', array($this, 'ajax_check_popup_eligibility'));
        add_action('wp_ajax_nopriv_dcf_check_popup_eligibility', array($this, 'ajax_check_popup_eligibility'));
    }
    
    /**
     * Enqueue trigger scripts
     */
    public function enqueue_trigger_scripts() {
        if (!$this->should_load_triggers()) {
            return;
        }
        
        wp_enqueue_script(
            'dcf-popup-triggers',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/popup-engine.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'dcf-popup-styles',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/popup-styles.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue modern forms CSS for popups
        wp_enqueue_style(
            'dcf-modern-forms',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/modern-forms.css',
            array('dcf-popup-styles'),
            '1.0.0'
        );
        
        // Enqueue popup animations CSS
        wp_enqueue_style(
            'dcf-popup-animations',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/popup-animations.css',
            array('dcf-popup-styles'),
            '1.0.0'
        );
        
        // Enqueue mobile gestures script
        wp_enqueue_script(
            'dcf-popup-mobile-gestures',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/popup-mobile-gestures.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script with popup data
        $popup_data = $this->get_active_popups_for_page();
        
        wp_localize_script('dcf-popup-triggers', 'dcf_popup_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_popup_nonce'),
            'popups' => $popup_data,
            'user_id' => get_current_user_id(),
            'session_id' => session_id() ?: $this->generate_session_id(),
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'is_mobile' => wp_is_mobile(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }
    
    /**
     * Check if triggers should be loaded on current page
     */
    private function should_load_triggers() {
        // Don't load on admin pages
        if (is_admin()) {
            return false;
        }
        
        // Don't load on login/register pages
        if (in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
            return false;
        }
        
        // Check if there are any active popups
        $active_popups = $this->get_active_popups_for_page();
        return !empty($active_popups);
    }
    
    /**
     * Get active popups for current page
     */
    private function get_active_popups_for_page() {
        $popups = $this->popup_manager->get_popups(array(
            'status' => 'active',
            'limit' => 50
        ));
        
        $eligible_popups = array();
        
        foreach ($popups as $popup) {
            if ($this->is_popup_eligible_for_page($popup)) {
                $eligible_popups[] = $this->prepare_popup_for_frontend($popup);
            }
        }
        
        return $eligible_popups;
    }
    
    /**
     * Check if popup is eligible for current page
     */
    private function is_popup_eligible_for_page($popup) {
        $targeting_rules = $popup['targeting_rules'] ?: array();
        
        // Check page targeting
        if (!$this->check_page_targeting($targeting_rules)) {
            return false;
        }
        
        // Check user targeting
        if (!$this->check_user_targeting($targeting_rules)) {
            return false;
        }
        
        // Check device targeting
        if (!$this->check_device_targeting($targeting_rules)) {
            return false;
        }
        
        // Check schedule
        if (!$this->check_schedule($targeting_rules)) {
            return false;
        }
        
        // Check frequency limits
        if (!$this->popup_manager->should_display_popup($popup['id'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check page targeting rules
     */
    private function check_page_targeting($targeting_rules) {
        if (empty($targeting_rules['pages'])) {
            return true; // No page restrictions
        }
        
        $page_rules = $targeting_rules['pages'];
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $current_post_id = get_queried_object_id();
        
        // Check include rules
        if (!empty($page_rules['include'])) {
            $included = false;
            
            foreach ($page_rules['include'] as $rule) {
                if ($this->match_page_rule($rule, $current_url, $current_post_id)) {
                    $included = true;
                    break;
                }
            }
            
            if (!$included) {
                return false;
            }
        }
        
        // Check exclude rules
        if (!empty($page_rules['exclude'])) {
            foreach ($page_rules['exclude'] as $rule) {
                if ($this->match_page_rule($rule, $current_url, $current_post_id)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Match individual page rule
     */
    private function match_page_rule($rule, $current_url, $current_post_id) {
        switch ($rule['type']) {
            case 'all':
                return true;
            
            case 'homepage':
                return is_front_page();
            
            case 'specific_page':
                return $current_post_id == $rule['value'];
            
            case 'post_type':
                return is_singular($rule['value']);
            
            case 'category':
                return is_category($rule['value']) || has_category($rule['value']);
            
            case 'tag':
                return is_tag($rule['value']) || has_tag($rule['value']);
            
            case 'url_contains':
                return strpos($current_url, $rule['value']) !== false;
            
            case 'url_regex':
                return preg_match($rule['value'], $current_url);
            
            default:
                return false;
        }
    }
    
    /**
     * Check user targeting rules
     */
    private function check_user_targeting($targeting_rules) {
        if (empty($targeting_rules['users'])) {
            return true; // No user restrictions
        }
        
        $user_rules = $targeting_rules['users'];
        $current_user = wp_get_current_user();
        
        // Check login status
        if (isset($user_rules['login_status'])) {
            if ($user_rules['login_status'] === 'logged_in' && !is_user_logged_in()) {
                return false;
            }
            if ($user_rules['login_status'] === 'logged_out' && is_user_logged_in()) {
                return false;
            }
        }
        
        // Check user roles
        if (!empty($user_rules['roles']) && is_user_logged_in()) {
            $user_roles = $current_user->roles;
            $allowed_roles = $user_rules['roles'];
            
            if (!array_intersect($user_roles, $allowed_roles)) {
                return false;
            }
        }
        
        // Check visitor type (new vs returning)
        if (isset($user_rules['visitor_type'])) {
            $is_returning = $this->is_returning_visitor();
            
            if ($user_rules['visitor_type'] === 'new' && $is_returning) {
                return false;
            }
            if ($user_rules['visitor_type'] === 'returning' && !$is_returning) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check device targeting rules
     */
    private function check_device_targeting($targeting_rules) {
        if (empty($targeting_rules['devices'])) {
            return true; // No device restrictions
        }
        
        $device_rules = $targeting_rules['devices'];
        
        // Check device type
        if (!empty($device_rules['types'])) {
            $current_device = wp_is_mobile() ? 'mobile' : 'desktop';
            
            if (!in_array($current_device, $device_rules['types'])) {
                return false;
            }
        }
        
        // Check browser
        if (!empty($device_rules['browsers'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $current_browser = $this->detect_browser($user_agent);
            
            if (!in_array($current_browser, $device_rules['browsers'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check schedule rules
     */
    private function check_schedule($targeting_rules) {
        if (empty($targeting_rules['schedule'])) {
            return true; // No schedule restrictions
        }
        
        $schedule = $targeting_rules['schedule'];
        $current_time = current_time('timestamp');
        
        // Check date range
        if (!empty($schedule['start_date'])) {
            $start_time = strtotime($schedule['start_date']);
            if ($current_time < $start_time) {
                return false;
            }
        }
        
        if (!empty($schedule['end_date'])) {
            $end_time = strtotime($schedule['end_date']);
            if ($current_time > $end_time) {
                return false;
            }
        }
        
        // Check days of week
        if (!empty($schedule['days_of_week'])) {
            $current_day = date('w', $current_time); // 0 = Sunday, 6 = Saturday
            if (!in_array($current_day, $schedule['days_of_week'])) {
                return false;
            }
        }
        
        // Check time of day
        if (!empty($schedule['time_range'])) {
            $current_hour = date('H', $current_time);
            $start_hour = $schedule['time_range']['start'] ?? 0;
            $end_hour = $schedule['time_range']['end'] ?? 23;
            
            if ($current_hour < $start_hour || $current_hour > $end_hour) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Prepare popup data for frontend
     */
    private function prepare_popup_for_frontend($popup) {
        // Extract mobile_enabled from trigger settings if it exists
        $mobile_enabled = true; // Default to true
        if (!empty($popup['trigger_settings']) && is_array($popup['trigger_settings'])) {
            // Check if there's a mobile_enabled setting in the trigger configuration
            if (isset($popup['trigger_settings']['mobile_enabled'])) {
                $mobile_enabled = (bool) $popup['trigger_settings']['mobile_enabled'];
            }
            // Also check for exit intent specific mobile settings
            if ($popup['trigger_settings']['type'] === 'exit_intent' && 
                isset($popup['trigger_settings']['settings']['mobile_enabled'])) {
                $mobile_enabled = (bool) $popup['trigger_settings']['settings']['mobile_enabled'];
            }
        }
        
        // Get popup config and check for template content
        $config = $popup['popup_config'] ?: array();
        $template_content = null;
        
        // Check if this popup has a template with multi-step content
        if (!empty($popup['template_id'])) {
            $template_manager = new DCF_Popup_Template_Manager();
            $template = $template_manager->get_template($popup['template_id']);
            
            if ($template && !empty($template['default_content'])) {
                $template_content = $template['default_content'];
                // Add template content to config for JavaScript access
                $config['template_content'] = $template_content;
            }
        }
        
        // Check if there's raw content in the config that contains a shortcode
        $content = '';
        if (!empty($config['content']) && is_string($config['content'])) {
            // If content contains [dcf_form shortcode, extract the form ID
            if (preg_match('/\[dcf_form\s+id="(\d+)"\]/', $config['content'], $matches)) {
                // Store the form ID in config for client-side rendering
                $config['form_id'] = $matches[1];
                // Clear the raw content to prevent confusion
                unset($config['content']);
            } else {
                // Keep the content if it's not a form shortcode
                $content = $config['content'];
            }
        }
        
        return array(
            'id' => $popup['id'],
            'name' => $popup['popup_name'], // Add the name field
            'type' => $popup['popup_type'],
            'status' => $popup['status'], // Add the status field
            'config' => $config,
            'triggers' => $popup['trigger_settings'] ?: array(),
            'design' => $popup['design_settings'] ?: array(),
            'mobile_enabled' => $mobile_enabled, // Add mobile_enabled at top level for easier access
            'content' => $content // Don't pre-render content to avoid multiple view tracking
        );
    }
    
    /**
     * Get popup content (form HTML)
     */
    private function get_popup_content($popup) {
        $config = $popup['popup_config'] ?: array();
        
        // Debug logging to understand the data structure
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DCF Popup Content Debug - Popup ID: ' . ($popup['id'] ?? 'unknown'));
            error_log('DCF Popup Content Debug - Config keys: ' . implode(', ', array_keys($config)));
            if (!empty($config['visual_editor'])) {
                error_log('DCF Popup Content Debug - Visual editor flag found');
            }
            if (!empty($config['steps'])) {
                error_log('DCF Popup Content Debug - Steps found: ' . count($config['steps']));
            }
            if (!empty($config['blocks'])) {
                error_log('DCF Popup Content Debug - Blocks found: ' . count($config['blocks']));
            }
        }
        
        // Check if this popup has visual editor content
        // Check multiple possible locations where visual editor data might be stored
        if (!empty($config['visual_editor_content'])) {
            return $this->render_visual_editor_content($config['visual_editor_content']);
        }
        
        // Check if visual editor flag is set and steps exist
        if (!empty($config['visual_editor']) && !empty($config['steps'])) {
            $visual_editor_data = array(
                'steps' => $config['steps'],
                'settings' => $config['settings'] ?? array()
            );
            return $this->render_visual_editor_content($visual_editor_data);
        }
        
        // Check if content is directly in config (for single-step visual editor popups)
        if (!empty($config['blocks']) && is_array($config['blocks'])) {
            return $this->render_visual_editor_content($config);
        }
        
        // Check if this popup has a template with multi-step content
        if (!empty($popup['template_id'])) {
            $template_manager = new DCF_Popup_Template_Manager();
            $template = $template_manager->get_template($popup['template_id']);
            
            if ($template && !empty($template['default_content']['steps'])) {
                // This is a multi-step template
                return DCF_Multi_Step_Handler::render_multi_step_content($config, $template['default_content']);
            }
        }
        
        // Default to form-based content
        if (empty($config['form_id'])) {
            return '<p>No form configured for this popup.</p>';
        }
        
        // Get form content using existing form builder
        $form_builder = new DCF_Form_Builder();
        return $form_builder->render_form($config['form_id'], array('popup_mode' => true));
    }
    
    /**
     * Render visual editor content
     */
    private function render_visual_editor_content($visual_editor_data) {
        if (empty($visual_editor_data) || !is_array($visual_editor_data)) {
            return '';
        }
        
        $html = '';
        
        // Check if this is multi-step content
        if (!empty($visual_editor_data['steps']) && is_array($visual_editor_data['steps'])) {
            $html .= '<div class="dcf-multi-step-popup" data-total-steps="' . count($visual_editor_data['steps']) . '">';
            
            foreach ($visual_editor_data['steps'] as $index => $step) {
                $step_index = $index + 1;
                $step_id = 'step-' . $step_index;
                
                $html .= '<div class="dcf-popup-step" data-step-id="' . esc_attr($step_id) . '" data-step-index="' . $index . '"';
                if ($index === 0) {
                    $html .= ' style="display: block;"';
                } else {
                    $html .= ' style="display: none;"';
                }
                $html .= '>';
                
                // Render blocks for this step
                if (!empty($step['blocks']) && is_array($step['blocks'])) {
                    foreach ($step['blocks'] as $block) {
                        // Check if this is a raw shortcode string (happens when visual editor saves incorrectly)
                        if (is_string($block) && preg_match('/\[dcf_form\s+id="(\d+)"\]/', $block, $matches)) {
                            // Convert shortcode to form block
                            $form_block = array(
                                'type' => 'form',
                                'settings' => array(
                                    'formId' => $matches[1],
                                    'showTitle' => false,
                                    'showDescription' => false,
                                    'alignment' => 'left'
                                )
                            );
                            $html .= $this->render_block($form_block);
                        } else {
                            $html .= $this->render_block($block);
                        }
                    }
                }
                
                // Check if step has raw content with shortcode
                if (!empty($step['content']) && is_string($step['content'])) {
                    if (preg_match('/\[dcf_form\s+id="(\d+)"\]/', $step['content'], $matches)) {
                        // Convert shortcode to form block
                        $form_block = array(
                            'type' => 'form',
                            'settings' => array(
                                'formId' => $matches[1],
                                'showTitle' => false,
                                'showDescription' => false,
                                'alignment' => 'left'
                            )
                        );
                        $html .= $this->render_block($form_block);
                    } else {
                        $html .= $step['content'];
                    }
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        } else {
            // Single step content - render blocks directly
            if (!empty($visual_editor_data['blocks']) && is_array($visual_editor_data['blocks'])) {
                foreach ($visual_editor_data['blocks'] as $block) {
                    $html .= $this->render_block($block);
                }
            }
        }
        
        // Check if there's any remaining content that contains shortcodes
        // This handles cases where shortcodes are appended to the content
        if (!empty($visual_editor_data['content']) && is_string($visual_editor_data['content'])) {
            if (preg_match('/\[dcf_form\s+id="(\d+)"\]/', $visual_editor_data['content'], $matches)) {
                // Convert shortcode to form block and render it
                $form_block = array(
                    'type' => 'form',
                    'settings' => array(
                        'formId' => $matches[1],
                        'showTitle' => false,
                        'showDescription' => false,
                        'alignment' => 'left'
                    )
                );
                $html .= $this->render_block($form_block);
            } else {
                $html .= $visual_editor_data['content'];
            }
        }
        
        return $html;
    }
    
    /**
     * Render a single block
     */
    private function render_block($block) {
        if (empty($block['type'])) {
            return '';
        }
        
        $block_type = $block['type'];
        $settings = $block['settings'] ?? array();
        $attributes = $block['attributes'] ?? array();
        
        // Skip rendering empty or placeholder blocks
        if ($block_type === 'empty' || $block_type === 'placeholder') {
            return '';
        }
        
        // Build style attribute from settings
        $styles = array();
        
        switch ($block_type) {
            case 'text':
                $text = $settings['text'] ?? '';
                if (!empty($settings['fontSize'])) $styles[] = 'font-size: ' . $settings['fontSize'];
                if (!empty($settings['fontWeight'])) $styles[] = 'font-weight: ' . $settings['fontWeight'];
                if (!empty($settings['textAlign'])) $styles[] = 'text-align: ' . $settings['textAlign'];
                if (!empty($settings['color'])) $styles[] = 'color: ' . $settings['color'];
                
                $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
                return '<p class="dcf-block dcf-block-text"' . $style_attr . '>' . wp_kses_post($text) . '</p>';
                
            case 'heading':
                $text = $settings['text'] ?? '';
                $level = $settings['level'] ?? 'h2';
                if (!empty($settings['fontSize'])) $styles[] = 'font-size: ' . $settings['fontSize'];
                if (!empty($settings['fontWeight'])) $styles[] = 'font-weight: ' . $settings['fontWeight'];
                if (!empty($settings['textAlign'])) $styles[] = 'text-align: ' . $settings['textAlign'];
                if (!empty($settings['color'])) $styles[] = 'color: ' . $settings['color'];
                
                $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
                return '<' . $level . ' class="dcf-block dcf-block-heading"' . $style_attr . '>' . wp_kses_post($text) . '</' . $level . '>';
                
            case 'button':
                $text = $settings['text'] ?? 'Click Me';
                $action = $settings['action'] ?? 'close';
                $nextStep = $settings['nextStep'] ?? '';
                
                if (!empty($settings['bgColor'])) $styles[] = 'background-color: ' . $settings['bgColor'];
                if (!empty($settings['textColor'])) $styles[] = 'color: ' . $settings['textColor'];
                if (!empty($settings['borderRadius'])) $styles[] = 'border-radius: ' . $settings['borderRadius'];
                if (!empty($settings['padding'])) $styles[] = 'padding: ' . $settings['padding'];
                if (!empty($settings['fontSize'])) $styles[] = 'font-size: ' . $settings['fontSize'];
                if (!empty($settings['fontWeight'])) $styles[] = 'font-weight: ' . $settings['fontWeight'];
                
                $style_attr = !empty($styles) ? ' style="' . implode('; ', $styles) . '"' : '';
                $data_attrs = '';
                
                // Add appropriate classes based on action
                $button_classes = 'dcf-block dcf-block-button dcf-button';
                
                if ($action === 'close') {
                    $button_classes .= ' dcf-popup-close';
                    $data_attrs .= ' data-action="close"';
                } elseif ($action === 'next' && $nextStep) {
                    $button_classes .= ' dcf-next-button';
                    $data_attrs .= ' data-action="next" data-next-step="' . esc_attr($nextStep) . '"';
                } elseif ($action === 'yes') {
                    $button_classes .= ' dcf-yes-button';
                    $data_attrs .= ' data-action="' . esc_attr($settings['yesAction'] ?? 'close') . '"';
                    if (!empty($settings['yesNextStep'])) {
                        $data_attrs .= ' data-next-step="' . esc_attr($settings['yesNextStep']) . '"';
                    }
                } elseif ($action === 'no') {
                    $button_classes .= ' dcf-no-button';
                    $data_attrs .= ' data-action="' . esc_attr($settings['noAction'] ?? 'close') . '"';
                    if (!empty($settings['noNextStep'])) {
                        $data_attrs .= ' data-next-step="' . esc_attr($settings['noNextStep']) . '"';
                    }
                }
                
                return '<button class="' . $button_classes . '"' . $style_attr . $data_attrs . '>' . esc_html($text) . '</button>';
                
            case 'image':
                $src = $settings['src'] ?? '';
                $alt = $settings['alt'] ?? '';
                $width = $settings['width'] ?? '';
                $height = $settings['height'] ?? '';
                $align = $settings['align'] ?? 'center';
                
                if (empty($src)) {
                    return '';
                }
                
                $img_attrs = 'src="' . esc_url($src) . '"';
                if ($alt) $img_attrs .= ' alt="' . esc_attr($alt) . '"';
                if ($width) $img_attrs .= ' width="' . esc_attr($width) . '"';
                if ($height) $img_attrs .= ' height="' . esc_attr($height) . '"';
                
                $wrapper_style = 'text-align: ' . $align;
                
                return '<div class="dcf-block dcf-block-image" style="' . $wrapper_style . '"><img ' . $img_attrs . ' /></div>';
                
            case 'countdown':
                $endTime = $settings['endTime'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'));
                $message = $settings['message'] ?? 'Offer ends in:';
                
                return '<div class="dcf-block dcf-block-countdown dcf-popup-countdown" data-end-time="' . esc_attr($endTime) . '">
                    <p class="dcf-countdown-message">' . esc_html($message) . '</p>
                    <div class="dcf-countdown-timer">
                        <div class="dcf-countdown-unit">
                            <span class="dcf-countdown-days">00</span>
                            <span class="dcf-countdown-label">Days</span>
                        </div>
                        <div class="dcf-countdown-unit">
                            <span class="dcf-countdown-hours">00</span>
                            <span class="dcf-countdown-label">Hours</span>
                        </div>
                        <div class="dcf-countdown-unit">
                            <span class="dcf-countdown-minutes">00</span>
                            <span class="dcf-countdown-label">Minutes</span>
                        </div>
                        <div class="dcf-countdown-unit">
                            <span class="dcf-countdown-seconds">00</span>
                            <span class="dcf-countdown-label">Seconds</span>
                        </div>
                    </div>
                </div>';
                
            case 'form':
                $formId = $settings['formId'] ?? 0;
                $showTitle = $settings['showTitle'] ?? false;
                $showDescription = $settings['showDescription'] ?? false;
                $alignment = $settings['alignment'] ?? 'left';
                
                if (empty($formId)) {
                    return '<p class="dcf-block dcf-block-form">No form selected</p>';
                }
                
                // Use the form builder to render the form
                $form_builder = new DCF_Form_Builder();
                $form_options = array(
                    'popup_mode' => true,
                    'show_title' => $showTitle,
                    'show_description' => $showDescription
                );
                
                $form_html = $form_builder->render_form($formId, $form_options);
                
                return '<div class="dcf-block dcf-block-form" style="text-align: ' . esc_attr($alignment) . ';">' . $form_html . '</div>';
                
            case 'divider':
                $color = $settings['color'] ?? '#e0e0e0';
                $width = $settings['width'] ?? '100%';
                $height = $settings['height'] ?? '1px';
                $margin = $settings['margin'] ?? '20px 0';
                
                $style = 'background-color: ' . $color . '; width: ' . $width . '; height: ' . $height . '; margin: ' . $margin . '; border: none;';
                
                return '<hr class="dcf-block dcf-block-divider" style="' . $style . '" />';
                
            case 'spacer':
                $height = $settings['height'] ?? '20px';
                return '<div class="dcf-block dcf-block-spacer" style="height: ' . $height . ';"></div>';
                
            default:
                // For unknown block types, return empty
                return '';
        }
    }
    
    /**
     * Render popup triggers in footer
     */
    public function render_popup_triggers() {
        if (!$this->should_load_triggers()) {
            return;
        }
        
        // Add popup container to DOM
        echo '<div id="dcf-popup-container"></div>';
        
        // Add trigger initialization script
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof DCF_PopupEngine !== 'undefined') {
                DCF_PopupEngine.init();
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for checking popup eligibility
     */
    public function ajax_check_popup_eligibility() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_popup_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $popup_id = intval($_POST['popup_id'] ?? 0);
        
        if (!$popup_id) {
            wp_send_json_error('Invalid popup ID');
        }
        
        $popup = $this->popup_manager->get_popup($popup_id);
        
        if (!$popup) {
            wp_send_json_error('Popup not found');
        }
        
        $eligible = $this->is_popup_eligible_for_page($popup);
        
        if ($eligible) {
            $popup_data = $this->prepare_popup_for_frontend($popup);
            wp_send_json_success($popup_data);
        } else {
            wp_send_json_error('Popup not eligible for current page');
        }
    }
    
    /**
     * Check if visitor is returning
     */
    private function is_returning_visitor() {
        // Check for existing cookie
        $visitor_cookie = $_COOKIE['dcf_visitor'] ?? '';
        
        if (empty($visitor_cookie)) {
            // Set cookie for new visitor
            setcookie('dcf_visitor', '1', time() + (30 * 24 * 60 * 60), '/'); // 30 days
            return false;
        }
        
        return true;
    }
    
    /**
     * Detect browser from user agent
     */
    private function detect_browser($user_agent) {
        $browsers = array(
            'chrome' => '/Chrome/i',
            'firefox' => '/Firefox/i',
            'safari' => '/Safari/i',
            'edge' => '/Edge/i',
            'ie' => '/MSIE|Trident/i',
            'opera' => '/Opera|OPR/i'
        );
        
        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $browser;
            }
        }
        
        return 'other';
    }
    
    /**
     * Generate session ID
     */
    private function generate_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Get available trigger types with their configuration
     *
     * @return array
     */
    public static function get_trigger_types() {
        return array(
            'exit_intent' => array(
                'name' => __('Exit Intent', 'dry-cleaning-forms'),
                'description' => __('Show popup when user is about to leave the page', 'dry-cleaning-forms'),
                'settings' => array(
                    'sensitivity' => array(
                        'type' => 'select',
                        'label' => __('Sensitivity', 'dry-cleaning-forms'),
                        'options' => array(
                            'low' => __('Low', 'dry-cleaning-forms'),
                            'medium' => __('Medium', 'dry-cleaning-forms'),
                            'high' => __('High', 'dry-cleaning-forms')
                        ),
                        'default' => 'medium',
                        'description' => __('How sensitive the exit intent detection should be', 'dry-cleaning-forms')
                    ),
                    'mobile_enabled' => array(
                        'type' => 'checkbox',
                        'label' => __('Enable on Mobile', 'dry-cleaning-forms'),
                        'default' => true,
                        'description' => __('Enable exit intent detection on mobile devices', 'dry-cleaning-forms')
                    )
                )
            ),
            'time_delay' => array(
                'name' => __('Time Delay', 'dry-cleaning-forms'),
                'description' => __('Show popup after a specified time delay', 'dry-cleaning-forms'),
                'settings' => array(
                    'delay' => array(
                        'type' => 'number',
                        'label' => __('Delay (seconds)', 'dry-cleaning-forms'),
                        'min' => 1,
                        'max' => 300,
                        'default' => 5,
                        'description' => __('Number of seconds to wait before showing popup', 'dry-cleaning-forms')
                    )
                )
            ),
            'scroll_percentage' => array(
                'name' => __('Scroll Percentage', 'dry-cleaning-forms'),
                'description' => __('Show popup when user scrolls to a certain percentage of the page', 'dry-cleaning-forms'),
                'settings' => array(
                    'percentage' => array(
                        'type' => 'number',
                        'label' => __('Scroll Percentage', 'dry-cleaning-forms'),
                        'min' => 1,
                        'max' => 100,
                        'default' => 50,
                        'description' => __('Percentage of page scroll before showing popup', 'dry-cleaning-forms')
                    )
                )
            ),
            'click_trigger' => array(
                'name' => __('Click Trigger', 'dry-cleaning-forms'),
                'description' => __('Show popup when user clicks on specific elements', 'dry-cleaning-forms'),
                'settings' => array(
                    'selector' => array(
                        'type' => 'text',
                        'label' => __('CSS Selector', 'dry-cleaning-forms'),
                        'default' => '.popup-trigger',
                        'description' => __('CSS selector for elements that should trigger the popup', 'dry-cleaning-forms')
                    )
                )
            ),
            'page_views' => array(
                'name' => __('Page Views', 'dry-cleaning-forms'),
                'description' => __('Show popup after user has viewed a certain number of pages', 'dry-cleaning-forms'),
                'settings' => array(
                    'views' => array(
                        'type' => 'number',
                        'label' => __('Number of Page Views', 'dry-cleaning-forms'),
                        'min' => 1,
                        'max' => 50,
                        'default' => 3,
                        'description' => __('Number of pages user must view before showing popup', 'dry-cleaning-forms')
                    )
                )
            ),
            'session_time' => array(
                'name' => __('Session Time', 'dry-cleaning-forms'),
                'description' => __('Show popup after user has been on site for a certain amount of time', 'dry-cleaning-forms'),
                'settings' => array(
                    'duration' => array(
                        'type' => 'number',
                        'label' => __('Session Duration (minutes)', 'dry-cleaning-forms'),
                        'min' => 1,
                        'max' => 60,
                        'default' => 5,
                        'description' => __('Minutes user must be on site before showing popup', 'dry-cleaning-forms')
                    )
                )
            )
        );
    }

    /**
     * Check if popup should be displayed based on triggers
     *
     * @param array $popup_data
     * @return bool
     */
    public function should_display_popup($popup_data) {
        // Check if popup is active
        if ($popup_data['status'] !== 'active') {
            return false;
        }

        // Check frequency limits
        if (!$this->check_frequency_limits($popup_data)) {
            return false;
        }

        // Check targeting rules
        if (!$this->check_targeting_rules($popup_data)) {
            return false;
        }

        return true;
    }
}

// Initialize popup triggers
new DCF_Popup_Triggers(); 