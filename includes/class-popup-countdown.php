<?php
/**
 * Popup Countdown Timer Helper Class
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Countdown {
    
    /**
     * Generate countdown timer HTML
     *
     * @param string $end_time End time for countdown (can be relative like '+7 days' or absolute)
     * @param array $options Optional display options
     * @return string HTML for countdown timer
     */
    public static function generate_countdown_html($end_time, $options = array()) {
        $defaults = array(
            'show_days' => true,
            'show_hours' => true,
            'show_minutes' => true,
            'show_seconds' => true,
            'labels' => array(
                'days' => __('Day', 'dry-cleaning-forms'),
                'hours' => __('Hr', 'dry-cleaning-forms'),
                'minutes' => __('Min', 'dry-cleaning-forms'),
                'seconds' => __('Sec', 'dry-cleaning-forms')
            )
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Calculate the actual end time
        if (strpos($end_time, '+') === 0 || strpos($end_time, '-') === 0) {
            // Relative time
            $end_timestamp = strtotime($end_time);
        } else {
            // Absolute time
            $end_timestamp = strtotime($end_time);
        }
        
        // Format as JavaScript-friendly date string
        $end_date = date('c', $end_timestamp);
        
        $html = '<div class="dcf-popup-countdown" data-end-time="' . esc_attr($end_date) . '">';
        
        if ($options['show_days']) {
            $html .= '<div class="dcf-countdown-item">';
            $html .= '<span class="dcf-countdown-number dcf-countdown-days">00</span>';
            $html .= '<span class="dcf-countdown-label">' . esc_html($options['labels']['days']) . '</span>';
            $html .= '</div>';
        }
        
        if ($options['show_hours']) {
            $html .= '<div class="dcf-countdown-item">';
            $html .= '<span class="dcf-countdown-number dcf-countdown-hours">00</span>';
            $html .= '<span class="dcf-countdown-label">' . esc_html($options['labels']['hours']) . '</span>';
            $html .= '</div>';
        }
        
        if ($options['show_minutes']) {
            $html .= '<div class="dcf-countdown-item">';
            $html .= '<span class="dcf-countdown-number dcf-countdown-minutes">00</span>';
            $html .= '<span class="dcf-countdown-label">' . esc_html($options['labels']['minutes']) . '</span>';
            $html .= '</div>';
        }
        
        if ($options['show_seconds']) {
            $html .= '<div class="dcf-countdown-item">';
            $html .= '<span class="dcf-countdown-number dcf-countdown-seconds">00</span>';
            $html .= '<span class="dcf-countdown-label">' . esc_html($options['labels']['seconds']) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render countdown inline styles
     *
     * @return string CSS styles for countdown timer
     */
    public static function get_countdown_styles() {
        return '
            .dcf-popup-countdown {
                display: flex;
                justify-content: center;
                gap: 15px;
                margin: 20px 0;
            }
            
            .dcf-countdown-item {
                background: rgba(255,255,255,0.9);
                color: #333;
                border-radius: 8px;
                padding: 15px;
                min-width: 70px;
                text-align: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .dcf-countdown-number {
                display: block;
                font-size: 32px;
                font-weight: 700;
                line-height: 1;
            }
            
            .dcf-countdown-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                margin-top: 5px;
                opacity: 0.8;
            }
            
            .dcf-countdown-expired {
                font-size: 24px;
                font-weight: 600;
                text-align: center;
                padding: 20px;
                color: #e74c3c;
            }
            
            @media (max-width: 768px) {
                .dcf-countdown-item {
                    min-width: 60px;
                    padding: 10px;
                }
                
                .dcf-countdown-number {
                    font-size: 24px;
                }
                
                .dcf-countdown-label {
                    font-size: 10px;
                }
            }
        ';
    }
}