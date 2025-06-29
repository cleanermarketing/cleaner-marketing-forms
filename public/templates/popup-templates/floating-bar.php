<?php
/**
 * Floating Bar Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Floating Bar', 'dry-cleaning-forms'),
    'description' => __('A sticky notification bar that appears at the top or bottom of the page.', 'dry-cleaning-forms'),
    'type' => 'floating-bar',
    'category' => 'conversion',
    'preview_image' => 'floating-bar-preview.png',
    'features' => array('Mobile Optimized', 'Sticky Position', 'Minimal Design'),
    'is_featured' => true,
    'default_settings' => array(
        'popup_type' => 'floating-bar',
        'position' => 'top',
        'width' => '100%',
        'height' => 'auto',
        'background_color' => '#2271b1',
        'text_color' => '#ffffff',
        'padding' => 15,
        'close_button' => true,
        'sticky' => true,
        'animation' => 'slideDown',
        'animation_duration' => 300
    ),
    'default_content' => array(
        'headline' => __('🎉 Limited Time: 20% OFF Your First Order!', 'dry-cleaning-forms'),
        'description' => __('New customers get exclusive savings on premium dry cleaning services.', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'placeholder' => __('Enter your email...', 'dry-cleaning-forms'),
                'required' => true
            )
        ),
        'submit_button' => array(
            'text' => __('Claim Discount', 'dry-cleaning-forms'),
            'background_color' => '#ffffff',
            'text_color' => '#2271b1',
            'border_radius' => 4
        )
    ),
    'css_template' => '
        .dcf-popup-floating-bar {
            position: fixed;
            {{#if position_is_top}}
            top: 0;
            {{else}}
            bottom: 0;
            {{/if}}
            left: 0;
            width: {{width}};
            background: {{background_color}};
            color: {{text_color}};
            padding: {{padding}}px;
            z-index: 999999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dcf-popup-floating-bar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .dcf-popup-floating-bar-text {
            flex: 1;
        }
        
        .dcf-popup-floating-bar-headline {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        
        .dcf-popup-floating-bar-description {
            font-size: 14px;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .dcf-popup-floating-bar-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .dcf-popup-floating-bar-field input {
            padding: 8px 12px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            font-size: 14px;
            background: rgba(255,255,255,0.1);
            color: {{text_color}};
            min-width: 200px;
        }
        
        .dcf-popup-floating-bar-field input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .dcf-popup-floating-bar-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 8px 20px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-floating-bar-submit:hover {
            transform: scale(1.05);
        }
        
        .dcf-popup-floating-bar-close {
            background: none;
            border: none;
            color: {{text_color}};
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
        }
        
        .dcf-popup-floating-bar-close:hover {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-floating-bar-content {
                flex-direction: column;
                text-align: center;
            }
            
            .dcf-popup-floating-bar-form {
                width: 100%;
                flex-direction: column;
            }
            
            .dcf-popup-floating-bar-field input {
                width: 100%;
            }
            
            .dcf-popup-floating-bar-submit {
                width: 100%;
            }
        }
    '
);

return $template_config;