<?php
/**
 * Slide-in Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Slide-in Popup', 'dry-cleaning-forms'),
    'description' => __('A popup that slides in from the bottom or corner of the screen, less intrusive than modals.', 'dry-cleaning-forms'),
    'type' => 'slide-in',
    'category' => 'general',
    'preview_image' => 'slide-in-preview.png',
    'features' => array('Non-Intrusive Display', 'Corner Positioning', 'Smooth Animations'),
    'default_settings' => array(
        'popup_type' => 'slide-in',
        'width' => 350,
        'height' => 'auto',
        'position' => 'bottom-right',
        'overlay' => false,
        'background_color' => '#ffffff',
        'border_radius' => 8,
        'padding' => 20,
        'close_button' => true,
        'close_on_overlay' => false,
        'animation' => 'slideInUp',
        'animation_duration' => 400,
        'shadow' => true
    ),
    'default_content' => array(
        'headline' => __('Special Offer!', 'dry-cleaning-forms'),
        'subheadline' => __('Save 20% on your first order', 'dry-cleaning-forms'),
        'description' => __('New customers get 20% off their first dry cleaning order. Limited time offer!', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email', 'dry-cleaning-forms'),
                'placeholder' => __('Your email address', 'dry-cleaning-forms'),
                'required' => true
            )
        ),
        'submit_button' => array(
            'text' => __('Claim Discount', 'dry-cleaning-forms'),
            'background_color' => '#00a32a',
            'text_color' => '#ffffff',
            'border_radius' => 4
        ),
        'privacy_text' => __('No spam, unsubscribe anytime.', 'dry-cleaning-forms')
    ),
    'css_template' => '
        .dcf-popup-slide-in {
            position: fixed;
            background: {{background_color}};
            border-radius: {{border_radius}}px;
            padding: {{padding}}px;
            width: {{width}}px;
            max-width: 90vw;
            z-index: 999999;
            {{#shadow}}box-shadow: 0 5px 25px rgba(0,0,0,0.2);{{/shadow}}
            border: 1px solid #e0e0e0;
        }
        
        .dcf-popup-slide-in.position-bottom-right {
            bottom: 20px;
            right: 20px;
        }
        
        .dcf-popup-slide-in.position-bottom-left {
            bottom: 20px;
            left: 20px;
        }
        
        .dcf-popup-slide-in.position-bottom-center {
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .dcf-popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dcf-popup-close:hover {
            color: #666;
        }
        
        .dcf-popup-content {
            padding-right: 30px;
        }
        
        .dcf-popup-headline {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #333;
        }
        
        .dcf-popup-subheadline {
            font-size: 16px;
            margin: 0 0 10px 0;
            color: #666;
        }
        
        .dcf-popup-description {
            font-size: 13px;
            margin: 0 0 15px 0;
            color: #777;
            line-height: 1.4;
        }
        
        .dcf-popup-form {
            margin-bottom: 10px;
        }
        
        .dcf-popup-field {
            margin-bottom: 10px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
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
            padding: 10px 20px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-submit:hover {
            opacity: 0.9;
        }
        
        .dcf-popup-privacy {
            font-size: 11px;
            color: #999;
            margin-top: 8px;
            text-align: center;
        }
        
        /* Animation classes */
        .dcf-popup-slide-in.slideInUp {
            animation: dcfSlideInUp 0.4s ease-out;
        }
        
        .dcf-popup-slide-in.slideInRight {
            animation: dcfSlideInRight 0.4s ease-out;
        }
        
        .dcf-popup-slide-in.slideInLeft {
            animation: dcfSlideInLeft 0.4s ease-out;
        }
        
        @keyframes dcfSlideInUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes dcfSlideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes dcfSlideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .dcf-popup-slide-in {
                width: calc(100vw - 40px);
                max-width: none;
                left: 20px !important;
                right: 20px !important;
                transform: none !important;
            }
            
            .dcf-popup-headline {
                font-size: 18px;
            }
            
            .dcf-popup-subheadline {
                font-size: 14px;
            }
        }
    '
);

return $template_config; 