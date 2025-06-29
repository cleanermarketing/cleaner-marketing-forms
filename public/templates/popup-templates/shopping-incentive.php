<?php
/**
 * Shopping Incentive Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Shopping Incentive', 'dry-cleaning-forms'),
    'description' => __('A vibrant two-step popup with Yes/No branching to capture leads with discount offers.', 'dry-cleaning-forms'),
    'type' => 'modal',
    'category' => 'conversion',
    'preview_image' => 'shopping-incentive-preview.png',
    'features' => array('Two-Step Flow', 'Yes/No Branching', 'Discount Offer'),
    'is_featured' => true,
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 600,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.6)',
        'background_color' => '#5DCECC',
        'background_image' => '',
        'background_position' => 'left center',
        'background_size' => 'contain',
        'background_repeat' => 'no-repeat',
        'text_color' => '#ffffff',
        'heading_font_size' => '32px',
        'heading_font_weight' => '700',
        'font_size' => '18px',
        'line_height' => '1.4',
        'text_align' => 'center',
        'border_radius' => 0,
        'padding' => '0',
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeIn',
        'animation_duration' => 300,
        // Button styles
        'button_bg_color' => '#FF69B4',
        'button_text_color' => '#ffffff',
        'button_hover_bg_color' => '#FF1493',
        'button_border_radius' => '50px',
        'button_padding' => '16px 40px',
        'button_font_size' => '18px',
        'button_font_weight' => '600',
        'button_shadow' => 'medium'
    ),
    'default_content' => array(
        'steps' => array(
            array(
                'id' => 'step_1',
                'headline' => __('Want to get an extra 5% off your order?', 'dry-cleaning-forms'),
                'description' => '',
                'type' => 'yes_no',
                'yes_button' => array(
                    'text' => __('Yes please!', 'dry-cleaning-forms'),
                    'next_step' => 'step_2'
                ),
                'no_button' => array(
                    'text' => __('No thanks, I don\'t like saving money', 'dry-cleaning-forms'),
                    'action' => 'close'
                )
            ),
            array(
                'id' => 'step_2',
                'headline' => __('Get an additional 5% discount on your order!', 'dry-cleaning-forms'),
                'description' => __('Enter your email below to instantly receive your 5% discount code, and join our newsletter for more coupons and updates.', 'dry-cleaning-forms'),
                'type' => 'form',
                'form_fields' => array(
                    array(
                        'type' => 'email',
                        'label' => __('Email Address', 'dry-cleaning-forms'),
                        'placeholder' => __('Your email address', 'dry-cleaning-forms'),
                        'required' => true,
                        'width' => '100'
                    )
                ),
                'submit_button' => array(
                    'text' => __('Submit →', 'dry-cleaning-forms'),
                    'background_color' => '#FF69B4',
                    'text_color' => '#ffffff'
                )
            )
        )
    ),
    'css_template' => '
        .dcf-popup-modal {
            background: {{background_color}};
            {{#if background_image}}
            background-image: url({{background_image}});
            background-position: {{background_position}};
            background-size: {{background_size}};
            background-repeat: {{background_repeat}};
            {{/if}}
            color: {{text_color}};
            padding: 0;
            overflow: hidden;
        }
        
        .dcf-popup-content {
            padding: {{padding}};
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .dcf-popup-content.has-background-image {
            padding-left: 40%;
        }
        
        .dcf-popup-step {
            width: 100%;
            max-width: 400px;
            text-align: {{text_align}};
        }
        
        .dcf-popup-headline {
            font-size: {{heading_font_size}};
            font-weight: {{heading_font_weight}};
            line-height: 1.2;
            margin: 0 0 30px 0;
            color: {{text_color}};
        }
        
        .dcf-popup-headline strong {
            text-decoration: underline;
            text-decoration-thickness: 3px;
            text-underline-offset: 3px;
        }
        
        .dcf-popup-description {
            font-size: {{font_size}};
            line-height: {{line_height}};
            margin: 0 0 30px 0;
            color: {{text_color}};
            opacity: 0.95;
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
            max-width: 300px;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-button:hover {
            background: {{button_hover_bg_color}};
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        
        .dcf-popup-button.secondary {
            background: transparent;
            color: {{text_color}};
            font-size: 14px;
            padding: 10px 20px;
            opacity: 0.8;
        }
        
        .dcf-popup-button.secondary:hover {
            opacity: 1;
            background: rgba(255,255,255,0.1);
        }
        
        .dcf-popup-form {
            width: 100%;
        }
        
        .dcf-popup-field {
            margin-bottom: 20px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 16px 20px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            background: rgba(255,255,255,0.9);
            color: #333;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }
        
        .dcf-popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.3);
            border: none;
            color: {{text_color}};
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .dcf-popup-close:hover {
            background: rgba(255,255,255,0.5);
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .dcf-popup-content {
                padding: 40px 20px;
            }
            
            .dcf-popup-content.has-background-image {
                padding-left: 20px;
            }
            
            .dcf-popup-headline {
                font-size: 24px;
            }
            
            .dcf-popup-description {
                font-size: 16px;
            }
            
            .dcf-popup-button {
                font-size: 16px;
                padding: 14px 30px;
            }
        }
    '
);

return $template_config;