<?php
/**
 * Fullscreen Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Fullscreen Takeover', 'dry-cleaning-forms'),
    'description' => __('A full-page overlay that captures maximum attention for important announcements.', 'dry-cleaning-forms'),
    'type' => 'fullscreen',
    'category' => 'engagement',
    'preview_image' => 'fullscreen-preview.png',
    'features' => array('Maximum Impact', 'Mobile Friendly', 'High Conversion'),
    'default_settings' => array(
        'popup_type' => 'fullscreen',
        'position' => 'center',
        'width' => '100%',
        'height' => '100%',
        'background_color' => '#f8f9fa',
        'background_image' => '',
        'overlay' => false,
        'padding' => 40,
        'close_button' => true,
        'close_on_escape' => true,
        'animation' => 'fadeIn',
        'animation_duration' => 500
    ),
    'default_content' => array(
        'headline' => __('Welcome to Premium Dry Cleaning', 'dry-cleaning-forms'),
        'subheadline' => __('Experience the difference with our eco-friendly cleaning process', 'dry-cleaning-forms'),
        'description' => __('Join over 10,000 satisfied customers who trust us with their garments. Get started with a special welcome offer just for you!', 'dry-cleaning-forms'),
        'form_fields' => array(
            array(
                'type' => 'text',
                'label' => __('Full Name', 'dry-cleaning-forms'),
                'placeholder' => __('John Doe', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'email',
                'label' => __('Email Address', 'dry-cleaning-forms'),
                'placeholder' => __('john@example.com', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'tel',
                'label' => __('Phone Number', 'dry-cleaning-forms'),
                'placeholder' => __('(555) 123-4567', 'dry-cleaning-forms'),
                'required' => true
            )
        ),
        'submit_button' => array(
            'text' => __('Get Your Welcome Offer', 'dry-cleaning-forms'),
            'background_color' => '#28a745',
            'text_color' => '#ffffff',
            'border_radius' => 50
        ),
        'benefits' => array(
            __('✓ Free pickup & delivery', 'dry-cleaning-forms'),
            __('✓ 24-hour turnaround', 'dry-cleaning-forms'),
            __('✓ Eco-friendly cleaning', 'dry-cleaning-forms'),
            __('✓ 100% satisfaction guarantee', 'dry-cleaning-forms')
        )
    ),
    'css_template' => '
        .dcf-popup-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: {{background_color}};
            {{#if background_image}}
            background-image: url({{background_image}});
            background-size: cover;
            background-position: center;
            {{/if}}
            z-index: 999999;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dcf-popup-fullscreen-container {
            max-width: 800px;
            width: 90%;
            padding: {{padding}}px;
            position: relative;
        }
        
        .dcf-popup-fullscreen-content {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 60px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .dcf-popup-fullscreen-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #333;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .dcf-popup-fullscreen-close:hover {
            background: #fff;
            transform: scale(1.1);
        }
        
        .dcf-popup-fullscreen-headline {
            font-size: 42px;
            font-weight: bold;
            margin: 0 0 15px 0;
            color: #1d2327;
            line-height: 1.2;
        }
        
        .dcf-popup-fullscreen-subheadline {
            font-size: 24px;
            margin: 0 0 20px 0;
            color: #646970;
            font-weight: 300;
        }
        
        .dcf-popup-fullscreen-description {
            font-size: 16px;
            margin: 0 0 40px 0;
            color: #646970;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .dcf-popup-fullscreen-form {
            max-width: 400px;
            margin: 0 auto 30px;
        }
        
        .dcf-popup-fullscreen-field {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .dcf-popup-fullscreen-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d2327;
            font-size: 14px;
        }
        
        .dcf-popup-fullscreen-field input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-fullscreen-field input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
        }
        
        .dcf-popup-fullscreen-submit {
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
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        
        .dcf-popup-fullscreen-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        
        .dcf-popup-fullscreen-benefits {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        
        .dcf-popup-fullscreen-benefit {
            font-size: 14px;
            color: #646970;
            text-align: left;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-fullscreen-content {
                padding: 40px 20px;
            }
            
            .dcf-popup-fullscreen-headline {
                font-size: 32px;
            }
            
            .dcf-popup-fullscreen-subheadline {
                font-size: 20px;
            }
            
            .dcf-popup-fullscreen-benefits {
                grid-template-columns: 1fr;
            }
        }
    '
);

return $template_config;