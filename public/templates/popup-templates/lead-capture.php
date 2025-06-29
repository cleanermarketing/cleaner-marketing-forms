<?php
/**
 * Lead Capture Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Lead Capture', 'dry-cleaning-forms'),
    'description' => __('A simple lead capture form perfect for collecting email addresses and building your customer list.', 'dry-cleaning-forms'),
    'type' => 'modal',
    'category' => 'lead-capture',
    'preview_image' => 'lead-capture-preview.png',
    'features' => array('Email Collection', 'Simple Forms', 'Quick Setup'),
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 450,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.6)',
        'background_color' => '#ffffff',
        'border_radius' => 8,
        'padding' => 35,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeInUp',
        'animation_duration' => 300
    ),
    'default_content' => array(
        'headline' => __('Join Our VIP List', 'dry-cleaning-forms'),
        'subheadline' => __('Get exclusive offers and free pickup on your first order', 'dry-cleaning-forms'),
        'description' => __('Be the first to know about special promotions and seasonal discounts.', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email Address', 'dry-cleaning-forms'),
                'placeholder' => __('your@email.com', 'dry-cleaning-forms'),
                'required' => true,
                'width' => '100'
            ),
            array(
                'type' => 'text',
                'label' => __('ZIP Code', 'dry-cleaning-forms'),
                'placeholder' => __('12345', 'dry-cleaning-forms'),
                'required' => true,
                'validation' => 'zip',
                'width' => '100'
            )
        ),
        'submit_button' => array(
            'text' => __('Get Started', 'dry-cleaning-forms'),
            'background_color' => '#2271b1',
            'text_color' => '#ffffff',
            'border_radius' => 4
        ),
        'privacy_text' => __('Your information is safe with us. No spam, ever.', 'dry-cleaning-forms')
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dcf-popup-close:hover {
            color: #333;
        }
        
        .dcf-popup-content {
            text-align: center;
        }
        
        .dcf-popup-headline {
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .dcf-popup-subheadline {
            font-size: 18px;
            margin: 0 0 15px 0;
            color: #666;
            line-height: 1.4;
        }
        
        .dcf-popup-description {
            font-size: 14px;
            margin: 0 0 25px 0;
            color: #777;
            line-height: 1.5;
        }
        
        .dcf-popup-form {
            margin-bottom: 15px;
        }
        
        .dcf-popup-field {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .dcf-popup-field label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.1);
        }
        
        .dcf-popup-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 12px 30px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .dcf-popup-privacy {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-modal {
                width: 95%;
                padding: 25px;
            }
            
            .dcf-popup-headline {
                font-size: 24px;
            }
            
            .dcf-popup-subheadline {
                font-size: 16px;
            }
        }
    '
);

return $template_config;