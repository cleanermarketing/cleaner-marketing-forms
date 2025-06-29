<?php
/**
 * Newsletter Signup Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Newsletter Signup', 'dry-cleaning-forms'),
    'description' => __('A clean, minimal newsletter signup form to grow your email list.', 'dry-cleaning-forms'),
    'type' => 'newsletter',
    'category' => 'newsletter',
    'preview_image' => 'newsletter-preview.png',
    'features' => array('Email Collection', 'Minimal Design', 'Quick Setup'),
    'default_settings' => array(
        'popup_type' => 'newsletter',
        'width' => 400,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.5)',
        'background_color' => '#ffffff',
        'border_radius' => 12,
        'padding' => 30,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeIn',
        'animation_duration' => 300
    ),
    'default_content' => array(
        'headline' => __('Stay Fresh & Clean', 'dry-cleaning-forms'),
        'subheadline' => __('Get laundry tips and exclusive deals', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => '',
                'placeholder' => __('Enter your email address', 'dry-cleaning-forms'),
                'required' => true,
                'width' => '100'
            )
        ),
        'submit_button' => array(
            'text' => __('Subscribe', 'dry-cleaning-forms'),
            'background_color' => '#00a32a',
            'text_color' => '#ffffff',
            'border_radius' => 50
        ),
        'privacy_text' => __('Unsubscribe anytime. We respect your privacy.', 'dry-cleaning-forms')
    ),
    'css_template' => '
        .dcf-popup-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: {{background_color}};
            border-radius: {{border_radius}}px;
            padding: {{padding}}px;
            max-width: {{width}}px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 999999;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .dcf-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: {{overlay_color}};
            z-index: 999998;
        }
        
        .dcf-popup-close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #f0f0f0;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            line-height: 1;
            padding: 0;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .dcf-popup-close:hover {
            background: #e0e0e0;
            color: #333;
        }
        
        .dcf-popup-content {
            text-align: center;
        }
        
        .dcf-popup-headline {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #1d2327;
        }
        
        .dcf-popup-subheadline {
            font-size: 16px;
            margin: 0 0 20px 0;
            color: #646970;
            font-weight: 400;
        }
        
        .dcf-popup-form {
            margin-bottom: 12px;
        }
        
        .dcf-popup-field {
            margin-bottom: 12px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: #00a32a;
        }
        
        .dcf-popup-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 10px 24px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s ease;
        }
        
        .dcf-popup-submit:hover {
            background: #008a20;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 163, 42, 0.3);
        }
        
        .dcf-popup-privacy {
            font-size: 11px;
            color: #8c8f94;
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-modal {
                width: 95%;
                padding: 24px;
            }
            
            .dcf-popup-headline {
                font-size: 22px;
            }
            
            .dcf-popup-subheadline {
                font-size: 14px;
            }
        }
    '
);

return $template_config;