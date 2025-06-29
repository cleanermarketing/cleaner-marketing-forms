<?php
/**
 * Seasonal Sale Countdown Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Seasonal Sale Countdown', 'dry-cleaning-forms'),
    'description' => __('Bold seasonal sale popup with countdown timer and eye-catching graphics.', 'dry-cleaning-forms'),
    'type' => 'modal',
    'category' => 'seasonal',
    'preview_image' => 'seasonal-countdown-preview.png',
    'features' => array('Countdown Timer', 'Bold Graphics', 'Two-Step Flow'),
    'is_featured' => true,
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 650,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.7)',
        'background_color' => '#FF4757',
        'use_gradient' => true,
        'gradient_type' => 'linear',
        'gradient_angle' => '135',
        'gradient_color_1' => '#FF4757',
        'gradient_color_2' => '#ee5a6f',
        'gradient_add_third' => true,
        'gradient_color_3' => '#f783ac',
        'text_color' => '#ffffff',
        'heading_font_size' => '48px',
        'heading_font_weight' => '900',
        'font_size' => '18px',
        'line_height' => '1.5',
        'text_align' => 'center',
        'border_radius' => '20px',
        'padding' => '50px',
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'bounceIn',
        'animation_duration' => 500,
        // Button styles
        'button_bg_color' => '#1E272E',
        'button_text_color' => '#ffffff',
        'button_hover_bg_color' => '#000000',
        'button_border_radius' => '50px',
        'button_padding' => '18px 50px',
        'button_font_size' => '20px',
        'button_font_weight' => '700',
        'button_shadow' => 'large'
    ),
    'default_content' => array(
        'steps' => array(
            array(
                'id' => 'step_1',
                'headline' => __('BLACK FRIDAY SALE', 'dry-cleaning-forms'),
                'subheadline' => __('GET FREE SHIPPING!', 'dry-cleaning-forms'),
                'description' => __('Save even more this Black Friday with a FREE SHIPPING coupon!', 'dry-cleaning-forms'),
                'show_countdown' => true,
                'countdown_end' => '+7 days', // 7 days from now
                'type' => 'yes_no',
                'yes_button' => array(
                    'text' => __('Yes! I Want Free Shipping', 'dry-cleaning-forms'),
                    'next_step' => 'step_2'
                ),
                'no_button' => array(
                    'text' => __('No thanks I don\'t want to save', 'dry-cleaning-forms'),
                    'action' => 'close'
                )
            ),
            array(
                'id' => 'step_2',
                'headline' => __('GET FREE SHIPPING!', 'dry-cleaning-forms'),
                'description' => __('Enter your email below and we\'ll send you a FREE SHIPPING COUPON so that you can save even more!', 'dry-cleaning-forms'),
                'show_countdown' => true,
                'type' => 'form',
                'form_fields' => array(
                    array(
                        'type' => 'text',
                        'placeholder' => __('Enter your name here...', 'dry-cleaning-forms'),
                        'required' => true,
                        'width' => '100'
                    ),
                    array(
                        'type' => 'email',
                        'placeholder' => __('Enter your email address here...', 'dry-cleaning-forms'),
                        'required' => true,
                        'width' => '100'
                    )
                ),
                'submit_button' => array(
                    'text' => __('Send My Free Shipping Coupon', 'dry-cleaning-forms')
                )
            )
        )
    ),
    'css_template' => '
        .dcf-popup-modal {
            background: {{background_color}};
            color: {{text_color}};
            position: relative;
            overflow: hidden;
        }
        
        .dcf-popup-modal::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(15deg);
            width: 150%;
            height: 300px;
            background: rgba(0,0,0,0.3);
            z-index: 0;
        }
        
        .dcf-popup-content {
            position: relative;
            z-index: 1;
        }
        
        .dcf-popup-step {
            text-align: {{text_align}};
        }
        
        .dcf-popup-headline {
            font-size: {{heading_font_size}};
            font-weight: {{heading_font_weight}};
            line-height: 1;
            margin: 0 0 20px 0;
            letter-spacing: -2px;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            transform: rotate(-2deg);
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(45deg, rgba(0,0,0,0.2), transparent);
        }
        
        .dcf-popup-subheadline {
            font-size: 36px;
            font-weight: 800;
            margin: 0 0 30px 0;
            letter-spacing: 1px;
        }
        
        .dcf-popup-countdown {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
        }
        
        .dcf-countdown-item {
            background: rgba(255,255,255,0.9);
            color: #333;
            border-radius: 10px;
            padding: 15px;
            min-width: 80px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .dcf-countdown-number {
            font-size: 36px;
            font-weight: 900;
            line-height: 1;
            display: block;
        }
        
        .dcf-countdown-label {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 5px;
            display: block;
        }
        
        .dcf-popup-description {
            font-size: {{font_size}};
            line-height: {{line_height}};
            margin: 0 0 30px 0;
            color: {{text_color}};
        }
        
        .dcf-popup-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .dcf-popup-button {
            background: {{button_bg_color}};
            color: {{button_text_color}};
            border: none;
            border-radius: {{button_border_radius}};
            padding: {{button_padding}};
            font-size: {{button_font_size}};
            font-weight: {{button_font_weight}};
            cursor: pointer;
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dcf-popup-button:hover {
            background: {{button_hover_bg_color}};
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .dcf-popup-button.secondary {
            background: transparent;
            color: {{text_color}};
            font-size: 16px;
            padding: 10px 20px;
            text-transform: none;
            text-decoration: underline;
            font-weight: 400;
        }
        
        .dcf-popup-button.secondary:hover {
            background: transparent;
            text-decoration: none;
            transform: none;
            box-shadow: none;
        }
        
        .dcf-popup-form {
            width: 100%;
        }
        
        .dcf-popup-field {
            margin-bottom: 15px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 18px 25px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            background: rgba(255,255,255,0.95);
            color: #333;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
        }
        
        @media (max-width: 768px) {
            .dcf-popup-modal {
                padding: 30px 20px;
            }
            
            .dcf-popup-headline {
                font-size: 36px;
            }
            
            .dcf-popup-subheadline {
                font-size: 28px;
            }
            
            .dcf-countdown-item {
                min-width: 60px;
                padding: 10px;
            }
            
            .dcf-countdown-number {
                font-size: 28px;
            }
            
            .dcf-popup-button {
                font-size: 18px;
                padding: 16px 40px;
            }
        }
    '
);

return $template_config;