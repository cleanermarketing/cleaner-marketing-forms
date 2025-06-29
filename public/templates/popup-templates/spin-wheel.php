<?php
/**
 * Spin Wheel Gamified Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Spin to Win', 'dry-cleaning-forms'),
    'description' => __('An interactive spin wheel that gamifies the signup process and increases engagement.', 'dry-cleaning-forms'),
    'type' => 'spin-wheel',
    'category' => 'gamification',
    'preview_image' => 'spin-wheel-preview.png',
    'features' => array('Interactive', 'High Engagement', 'Fun Experience'),
    'is_featured' => true,
    'default_settings' => array(
        'popup_type' => 'spin-wheel',
        'width' => 700,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.8)',
        'background_color' => '#ffffff',
        'border_radius' => 12,
        'padding' => 40,
        'close_button' => true,
        'close_on_overlay' => false,
        'animation' => 'bounceIn',
        'animation_duration' => 500
    ),
    'default_content' => array(
        'headline' => __('Spin the Wheel & Win!', 'dry-cleaning-forms'),
        'subheadline' => __('Try your luck for exclusive discounts!', 'dry-cleaning-forms'),
        'description' => __('Enter your email to spin the wheel and unlock your special offer.', 'dry-cleaning-forms'),
        'prizes' => array(
            array('text' => '10% OFF', 'probability' => 30, 'color' => '#FF6B6B'),
            array('text' => '15% OFF', 'probability' => 25, 'color' => '#4ECDC4'),
            array('text' => '20% OFF', 'probability' => 20, 'color' => '#45B7D1'),
            array('text' => 'FREE Delivery', 'probability' => 15, 'color' => '#96CEB4'),
            array('text' => '25% OFF', 'probability' => 8, 'color' => '#FECA57'),
            array('text' => '30% OFF', 'probability' => 2, 'color' => '#FF9FF3')
        ),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Your Email', 'dry-cleaning-forms'),
                'placeholder' => __('Enter your email to spin...', 'dry-cleaning-forms'),
                'required' => true
            )
        ),
        'submit_button' => array(
            'text' => __('SPIN NOW!', 'dry-cleaning-forms'),
            'background_color' => '#FF6B6B',
            'text_color' => '#ffffff',
            'border_radius' => 50
        ),
        'terms_text' => __('*One spin per customer. Offer valid for new customers only.', 'dry-cleaning-forms')
    ),
    'css_template' => '
        .dcf-popup-spin-wheel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: {{background_color}};
            border-radius: {{border_radius}}px;
            padding: {{padding}}px;
            max-width: {{width}}px;
            width: 90%;
            z-index: 999999;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .dcf-popup-spin-wheel-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        
        .dcf-popup-spin-wheel-left {
            text-align: center;
        }
        
        .dcf-popup-spin-wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }
        
        .dcf-wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
        }
        
        .dcf-wheel-slice {
            position: absolute;
            width: 50%;
            height: 50%;
            transform-origin: right bottom;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dcf-wheel-text {
            position: absolute;
            font-weight: bold;
            font-size: 14px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            transform: rotate(-60deg) translateX(50%);
        }
        
        .dcf-wheel-pointer {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 40px solid #333;
            z-index: 10;
        }
        
        .dcf-popup-spin-wheel-right {
            text-align: center;
        }
        
        .dcf-popup-spin-wheel-headline {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #1d2327;
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .dcf-popup-spin-wheel-subheadline {
            font-size: 20px;
            margin: 0 0 15px 0;
            color: #646970;
        }
        
        .dcf-popup-spin-wheel-description {
            font-size: 14px;
            margin: 0 0 25px 0;
            color: #777;
            line-height: 1.5;
        }
        
        .dcf-popup-spin-wheel-form {
            margin-bottom: 20px;
        }
        
        .dcf-popup-spin-wheel-field {
            margin-bottom: 15px;
        }
        
        .dcf-popup-spin-wheel-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .dcf-popup-spin-wheel-field input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e1e1;
            border-radius: 50px;
            font-size: 16px;
            text-align: center;
        }
        
        .dcf-popup-spin-wheel-field input:focus {
            outline: none;
            border-color: #FF6B6B;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .dcf-popup-spin-wheel-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 16px 40px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .dcf-popup-spin-wheel-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }
        
        .dcf-popup-spin-wheel-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .dcf-popup-spin-wheel-terms {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
        }
        
        .dcf-popup-spin-wheel-result {
            display: none;
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .dcf-popup-spin-wheel-result.show {
            display: block;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dcf-result-prize {
            font-size: 36px;
            font-weight: bold;
            color: #FF6B6B;
            margin-bottom: 10px;
        }
        
        .dcf-result-code {
            font-size: 24px;
            font-family: monospace;
            background: #e9ecef;
            padding: 10px 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-spin-wheel-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .dcf-popup-spin-wheel-container {
                width: 250px;
                height: 250px;
            }
            
            .dcf-popup-spin-wheel-headline {
                font-size: 28px;
            }
        }
    '
);

return $template_config;