<?php
/**
 * Modal Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Modal Popup', 'dry-cleaning-forms'),
    'description' => __('A centered modal popup that appears over the page content with an overlay background.', 'dry-cleaning-forms'),
    'type' => 'modal',
    'category' => 'general',
    'preview_image' => 'modal-preview.png',
    'features' => array('Classic Centered Design', 'Customizable Overlay', 'Mobile Responsive'),
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 500,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.7)',
        'background_color' => '#ffffff',
        'border_radius' => 8,
        'padding' => 30,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeIn',
        'animation_duration' => 300
    ),
    'default_content' => array(
        'headline' => __('Don\'t Miss Out!', 'dry-cleaning-forms'),
        'subheadline' => __('Get exclusive offers and updates delivered to your inbox.', 'dry-cleaning-forms'),
        'description' => __('Join thousands of satisfied customers who save time and money with our premium dry cleaning services.', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email Address', 'dry-cleaning-forms'),
                'placeholder' => __('Enter your email...', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => __('First Name', 'dry-cleaning-forms'),
                'placeholder' => __('Enter your name...', 'dry-cleaning-forms'),
                'required' => false
            )
        ),
        'submit_button' => array(
            'text' => __('Get My Discount', 'dry-cleaning-forms'),
            'background_color' => '#2271b1',
            'text_color' => '#ffffff',
            'border_radius' => 4
        ),
        'privacy_text' => __('We respect your privacy. Unsubscribe at any time.', 'dry-cleaning-forms')
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
                padding: 20px;
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