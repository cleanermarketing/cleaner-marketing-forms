<?php
/**
 * Exit-Intent Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Exit-Intent Popup', 'dry-cleaning-forms'),
    'description' => __('A popup that appears when users are about to leave your site, perfect for last-chance offers.', 'dry-cleaning-forms'),
    'type' => 'exit-intent',
    'category' => 'conversion',
    'preview_image' => 'exit-intent-preview.png',
    'features' => array('Exit Detection', 'Reduce Abandonment', 'Last Chance Offers'),
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 600,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.8)',
        'background_color' => '#ffffff',
        'border_radius' => 12,
        'padding' => 40,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'bounceIn',
        'animation_duration' => 500,
        'trigger_delay' => 0,
        'exit_intent_sensitivity' => 20
    ),
    'default_content' => array(
        'headline' => __('Wait! Don\'t Leave Empty-Handed!', 'dry-cleaning-forms'),
        'subheadline' => __('Get 25% OFF your first dry cleaning order', 'dry-cleaning-forms'),
        'description' => __('You\'re just one step away from experiencing our premium dry cleaning service. Don\'t miss out on this exclusive offer for new customers!', 'dry-cleaning-forms'),
        'urgency_text' => __('⏰ This offer expires in 24 hours!', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email Address', 'dry-cleaning-forms'),
                'placeholder' => __('Enter your email to claim discount...', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => __('First Name', 'dry-cleaning-forms'),
                'placeholder' => __('Your first name...', 'dry-cleaning-forms'),
                'required' => false
            )
        ),
        'submit_button' => array(
            'text' => __('Claim My 25% Discount', 'dry-cleaning-forms'),
            'background_color' => '#ff6b35',
            'text_color' => '#ffffff',
            'border_radius' => 6
        ),
        'secondary_button' => array(
            'text' => __('No thanks, I\'ll pay full price', 'dry-cleaning-forms'),
            'style' => 'link',
            'color' => '#999999'
        ),
        'privacy_text' => __('We respect your privacy. No spam, unsubscribe anytime.', 'dry-cleaning-forms'),
        'trust_badges' => array(
            __('✓ 5-Star Rated Service', 'dry-cleaning-forms'),
            __('✓ Free Pickup & Delivery', 'dry-cleaning-forms'),
            __('✓ 100% Satisfaction Guarantee', 'dry-cleaning-forms')
        )
    ),
    'css_template' => '
        .dcf-popup-exit-intent {
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border: 3px solid #ff6b35;
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
            font-size: 28px;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 0;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-close:hover {
            background: #f0f0f0;
            color: #666;
        }
        
        .dcf-popup-content {
            text-align: center;
        }
        
        .dcf-popup-headline {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 15px 0;
            color: #333;
            line-height: 1.2;
        }
        
        .dcf-popup-subheadline {
            font-size: 24px;
            margin: 0 0 20px 0;
            color: #ff6b35;
            font-weight: 600;
        }
        
        .dcf-popup-description {
            font-size: 16px;
            margin: 0 0 20px 0;
            color: #666;
            line-height: 1.6;
        }
        
        .dcf-popup-urgency {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px;
            margin: 0 0 25px 0;
            font-size: 16px;
            font-weight: 600;
            color: #856404;
        }
        
        .dcf-popup-form {
            margin-bottom: 20px;
        }
        
        .dcf-popup-field {
            margin-bottom: 15px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .dcf-popup-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 15px 30px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dcf-popup-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        .dcf-popup-secondary {
            background: none;
            border: none;
            color: {{secondary_button.color}};
            font-size: 14px;
            cursor: pointer;
            margin-top: 15px;
            text-decoration: underline;
        }
        
        .dcf-popup-secondary:hover {
            color: #666;
        }
        
        .dcf-popup-trust-badges {
            display: flex;
            justify-content: space-around;
            margin: 25px 0 15px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .dcf-popup-trust-badge {
            font-size: 14px;
            color: #00a32a;
            font-weight: 500;
        }
        
        .dcf-popup-privacy {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
        }
        
        /* Animation classes */
        .dcf-popup-exit-intent.bounceIn {
            animation: dcfBounceIn 0.5s ease-out;
        }
        
        @keyframes dcfBounceIn {
            0% {
                transform: translate(-50%, -50%) scale(0.3);
                opacity: 0;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.05);
                opacity: 1;
            }
            70% {
                transform: translate(-50%, -50%) scale(0.9);
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
            }
        }
        
        @media (max-width: 768px) {
            .dcf-popup-exit-intent {
                width: 95%;
                padding: 25px;
                border-width: 2px;
            }
            
            .dcf-popup-headline {
                font-size: 24px;
            }
            
            .dcf-popup-subheadline {
                font-size: 20px;
            }
            
            .dcf-popup-description {
                font-size: 14px;
            }
            
            .dcf-popup-trust-badges {
                flex-direction: column;
                text-align: center;
            }
            
            .dcf-popup-submit {
                font-size: 16px;
                padding: 12px 25px;
            }
        }
    '
);

return $template_config; 