<?php
/**
 * Discount Offer Split-Screen Template
 * Matches the turquoise/pink design from mockups
 *
 * @package CleanerMarketingForms
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

return array(
    'name' => __('5% Discount Offer', 'dry-cleaning-forms'),
    'description' => __('Eye-catching split-screen popup with discount offer and email capture', 'dry-cleaning-forms'),
    'type' => 'split-screen',
    'category' => 'conversion',
    'preview_image' => 'discount-offer-preview.jpg',
    
    'default_settings' => array(
        // Popup settings
        'width' => '900px',
        'height' => '500px',
        'animation' => 'fadeIn',
        'animation_duration' => '400',
        'overlay_color' => 'rgba(0, 0, 0, 0.7)',
        'position' => 'center',
        'position_x' => '50%',
        'position_y' => '50%',
        
        // Split-screen specific settings
        'split_layout' => 'image-left',
        'split_ratio' => '50-50',
        'split_content_bg' => '#5DBCD2',
        'split_content_padding' => '40px',
        'split_image' => '', // Will need to be set when template is used - user should upload their own image image
        'split_image_position' => 'center center',
        'split_image_size' => 'cover',
        
        // Mobile responsiveness
        'split_mobile_layout' => 'stacked',
        'split_mobile_breakpoint' => '768',
        'split_mobile_image_height' => '200px',
        'split_mobile_padding' => '20px',
        
        // Typography
        'font_size' => '16px',
        'font_weight' => '400',
        'line_height' => '1.6',
        'text_color' => '#ffffff',
        'text_align' => 'center',
        'heading_font_size' => '32px',
        'heading_font_weight' => '700',
        
        // Text formatting
        'enable_text_bold' => '1',
        'enable_text_italic' => '1',
        'enable_text_underline' => '1',
        'link_color' => '#ffffff',
        'link_hover_color' => '#f0f0f0',
        'link_underline' => '1',
        
        // Button styling
        'button_style_preset' => 'primary',
        'button_bg_color' => '#FF69B4',
        'button_text_color' => '#ffffff',
        'button_hover_bg_color' => '#FF1493',
        'button_border_radius' => '50px',
        'button_padding' => '16px 32px',
        'button_font_size' => '16px',
        'button_font_weight' => '600',
        'button_text_transform' => 'none',
        'button_shadow' => 'medium',
        
        // Button icons
        'button_show_icon' => '1',
        'button_icon' => 'arrow-right',
        'button_icon_position' => 'after',
        'button_icon_spacing' => '8px',
        
        // Button layout
        'button_layout' => 'stacked',
        'button_spacing' => '16px',
        'button_align' => 'center',
        
        // Form field styling
        'field_style' => 'modern',
        'field_border_color' => '#ffffff',
        'field_focus_color' => '#FF69B4',
        'field_bg_color' => 'rgba(255, 255, 255, 0.2)',
        'field_text_color' => '#ffffff',
        'field_padding' => '14px 20px',
        'field_border_radius' => '50px',
        
        // Close button
        'show_close_button' => true,
        'close_button_color' => '#ffffff',
        'close_on_overlay_click' => true,
        'close_on_esc_key' => true,
        
        // Multi-step transitions
        'step_transition' => 'fade',
        'step_transition_duration' => '300',
        'show_step_progress' => false,
    ),
    
    'default_content' => array(
        'type' => 'multi-step',
        'steps' => array(
            // Step 1: Email capture with discount offer
            array(
                'id' => 'step-1',
                'headline' => 'Get an additional <strong>5% discount</strong> on your order!',
                'subheadline' => 'Enter your email below to instantly receive your 5% discount code, and join our newsletter for more coupons and updates.',
                'form_fields' => array(
                    array(
                        'type' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'Your email address',
                        'required' => true,
                        'name' => 'email',
                        'custom_classes' => 'dcf-field-email'
                    )
                ),
                'buttons' => array(
                    array(
                        'text' => 'Submit',
                        'type' => 'submit',
                        'class' => 'dcf-button-primary',
                        'next_step' => 'step-2'
                    ),
                    array(
                        'text' => 'No thanks',
                        'type' => 'button',
                        'class' => 'dcf-button-text-link',
                        'action' => 'close'
                    )
                )
            ),
            // Step 2: Thank you message
            array(
                'id' => 'step-2',
                'headline' => 'Thank You!',
                'subheadline' => 'Your 5% discount code has been sent to your email. Check your inbox and start saving today!',
                'content' => '<p style="margin-top: 20px;">Your discount code: <strong style="font-size: 24px; color: #FF69B4;">SAVE5NOW</strong></p>',
                'buttons' => array(
                    array(
                        'text' => 'Start Shopping',
                        'type' => 'button',
                        'class' => 'dcf-button-primary',
                        'action' => 'close'
                    )
                )
            )
        )
    ),
    
    'trigger_settings' => array(
        'type' => 'exit_intent',
        'delay' => 0,
        'scroll_percentage' => 0,
        'show_frequency' => 'once_per_session',
        'cookie_duration' => 7
    ),
    
    'targeting_rules' => array(
        'pages' => array('mode' => 'all'),
        'users' => array(),
        'devices' => array('types' => array('desktop', 'mobile')),
        'schedule' => array()
    ),
    
    'css_template' => '
        /* Custom styles for discount offer template */
        [data-popup-id="{{POPUP_ID}}"] {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-popup-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-popup-headline {
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #ffffff;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-popup-subheadline {
            font-size: 18px;
            font-weight: 400;
            line-height: 1.5;
            margin-bottom: 30px;
            color: #ffffff;
            opacity: 0.95;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-form-field input[type="email"] {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            color: #ffffff;
            font-size: 16px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            display: block;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-form-field input[type="email"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-form-field input[type="email"]:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-button-text-link {
            background: transparent !important;
            border: none !important;
            color: #ffffff !important;
            text-decoration: underline !important;
            padding: 8px 16px !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            box-shadow: none !important;
            margin-top: 10px;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-button-text-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
            transform: none !important;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-split-screen-popup .dcf-close-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: #ffffff;
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        [data-popup-id="{{POPUP_ID}}"] .dcf-split-screen-popup .dcf-close-button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        /* Ensure form is centered */
        [data-popup-id="{{POPUP_ID}}"] .dcf-form-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            [data-popup-id="{{POPUP_ID}}"] .dcf-popup-headline {
                font-size: 24px;
            }
            
            [data-popup-id="{{POPUP_ID}}"] .dcf-popup-subheadline {
                font-size: 16px;
            }
            
            [data-popup-id="{{POPUP_ID}}"] .dcf-split-content-section {
                padding: 30px 20px !important;
            }
        }
    '
);