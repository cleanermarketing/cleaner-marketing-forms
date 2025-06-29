<?php
/**
 * Sidebar Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Sidebar Popup', 'dry-cleaning-forms'),
    'description' => __('A fixed sidebar popup that stays visible while users browse, perfect for ongoing promotions.', 'dry-cleaning-forms'),
    'type' => 'sidebar',
    'category' => 'engagement',
    'preview_image' => 'sidebar-preview.png',
    'default_settings' => array(
        'popup_type' => 'sidebar',
        'width' => 300,
        'height' => 'auto',
        'position' => 'right',
        'overlay' => false,
        'background_color' => '#2271b1',
        'border_radius' => 0,
        'padding' => 25,
        'close_button' => true,
        'close_on_overlay' => false,
        'animation' => 'slideInRight',
        'animation_duration' => 400,
        'sticky' => true
    ),
    'default_content' => array(
        'headline' => __('Free Pickup & Delivery', 'dry-cleaning-forms'),
        'subheadline' => __('Schedule Today!', 'dry-cleaning-forms'),
        'description' => __('Convenient dry cleaning service right to your door. No minimum order required.', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email', 'dry-cleaning-forms'),
                'placeholder' => __('Your email...', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'tel',
                'label' => __('Phone', 'dry-cleaning-forms'),
                'placeholder' => __('Your phone...', 'dry-cleaning-forms'),
                'required' => false
            )
        ),
        'submit_button' => array(
            'text' => __('Schedule Pickup', 'dry-cleaning-forms'),
            'background_color' => '#ffffff',
            'text_color' => '#2271b1',
            'border_radius' => 4
        ),
        'privacy_text' => __('We\'ll contact you to schedule.', 'dry-cleaning-forms'),
        'features' => array(
            __('✓ Same-day pickup available', 'dry-cleaning-forms'),
            __('✓ Professional cleaning', 'dry-cleaning-forms'),
            __('✓ 48-hour turnaround', 'dry-cleaning-forms')
        )
    ),
    'css_template' => '
        .dcf-popup-sidebar {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            background: {{background_color}};
            border-radius: {{border_radius}}px;
            padding: {{padding}}px;
            width: {{width}}px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 999999;
            color: #ffffff;
            box-shadow: -5px 0 25px rgba(0,0,0,0.2);
        }
        
        .dcf-popup-sidebar.position-right {
            right: 0;
        }
        
        .dcf-popup-sidebar.position-left {
            left: 0;
        }
        
        .dcf-popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.2);
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #ffffff;
            line-height: 1;
            padding: 5px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .dcf-popup-content {
            padding-right: 35px;
        }
        
        .dcf-popup-headline {
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #ffffff;
            line-height: 1.2;
        }
        
        .dcf-popup-subheadline {
            font-size: 18px;
            margin: 0 0 15px 0;
            color: #ffffff;
            font-weight: 600;
        }
        
        .dcf-popup-description {
            font-size: 14px;
            margin: 0 0 20px 0;
            color: rgba(255,255,255,0.9);
            line-height: 1.5;
        }
        
        .dcf-popup-features {
            margin: 0 0 20px 0;
        }
        
        .dcf-popup-feature {
            font-size: 13px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 5px;
            display: block;
        }
        
        .dcf-popup-form {
            margin-bottom: 15px;
        }
        
        .dcf-popup-field {
            margin-bottom: 12px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            font-size: 14px;
            background: rgba(255,255,255,0.1);
            color: #ffffff;
        }
        
        .dcf-popup-field input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.15);
        }
        
        .dcf-popup-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 12px 20px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 14px;
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
            font-size: 11px;
            color: rgba(255,255,255,0.8);
            margin-top: 10px;
            text-align: center;
        }
        
        /* Animation classes */
        .dcf-popup-sidebar.slideInRight {
            animation: dcfSlideInRight 0.4s ease-out;
        }
        
        .dcf-popup-sidebar.slideInLeft {
            animation: dcfSlideInLeft 0.4s ease-out;
        }
        
        @keyframes dcfSlideInRight {
            from {
                transform: translateY(-50%) translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateY(-50%) translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes dcfSlideInLeft {
            from {
                transform: translateY(-50%) translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(-50%) translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive behavior */
        @media (max-width: 768px) {
            .dcf-popup-sidebar {
                position: fixed;
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                transform: none;
                width: 100%;
                max-width: none;
                border-radius: 15px 15px 0 0;
                max-height: 70vh;
            }
            
            .dcf-popup-sidebar.slideInRight,
            .dcf-popup-sidebar.slideInLeft {
                animation: dcfSlideInUp 0.4s ease-out;
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
            
            .dcf-popup-headline {
                font-size: 20px;
            }
            
            .dcf-popup-subheadline {
                font-size: 16px;
            }
        }
        
        /* Tablet adjustments */
        @media (max-width: 1024px) and (min-width: 769px) {
            .dcf-popup-sidebar {
                width: 280px;
            }
        }
    '
);

return $template_config; 