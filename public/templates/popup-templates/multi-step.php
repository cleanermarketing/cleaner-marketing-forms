<?php
/**
 * Multi-Step Popup Template
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Multi-Step Popup', 'dry-cleaning-forms'),
    'description' => __('A progressive popup that guides users through multiple steps, increasing completion rates.', 'dry-cleaning-forms'),
    'type' => 'multi-step',
    'category' => 'conversion',
    'preview_image' => 'multi-step-preview.png',
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 550,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.7)',
        'background_color' => '#ffffff',
        'border_radius' => 10,
        'padding' => 35,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeIn',
        'animation_duration' => 300,
        'progress_bar' => true
    ),
    'default_content' => array(
        'steps' => array(
            array(
                'step_number' => 1,
                'headline' => __('What service do you need?', 'dry-cleaning-forms'),
                'description' => __('Select the dry cleaning service that best fits your needs.', 'dry-cleaning-forms'),
                'fields' => array(
                    array(
                        'type' => 'radio',
                        'name' => 'service_type',
                        'label' => __('Service Type', 'dry-cleaning-forms'),
                        'options' => array(
                            'dry_cleaning' => __('Dry Cleaning', 'dry-cleaning-forms'),
                            'laundry' => __('Wash & Fold Laundry', 'dry-cleaning-forms'),
                            'alterations' => __('Alterations & Repairs', 'dry-cleaning-forms'),
                            'specialty' => __('Specialty Items (Wedding dress, etc.)', 'dry-cleaning-forms')
                        ),
                        'required' => true
                    )
                ),
                'button_text' => __('Next Step', 'dry-cleaning-forms')
            ),
            array(
                'step_number' => 2,
                'headline' => __('How would you like to get your items to us?', 'dry-cleaning-forms'),
                'description' => __('Choose the most convenient option for you.', 'dry-cleaning-forms'),
                'fields' => array(
                    array(
                        'type' => 'radio',
                        'name' => 'delivery_method',
                        'label' => __('Delivery Method', 'dry-cleaning-forms'),
                        'options' => array(
                            'pickup' => __('Free Pickup & Delivery', 'dry-cleaning-forms'),
                            'drop_off' => __('Drop off at store', 'dry-cleaning-forms'),
                            'locker' => __('24/7 Smart Locker', 'dry-cleaning-forms')
                        ),
                        'required' => true
                    )
                ),
                'button_text' => __('Continue', 'dry-cleaning-forms')
            ),
            array(
                'step_number' => 3,
                'headline' => __('Get your personalized quote!', 'dry-cleaning-forms'),
                'description' => __('Enter your details and we\'ll send you a custom quote within 30 minutes.', 'dry-cleaning-forms'),
                'fields' => array(
                    array(
                        'type' => 'text',
                        'name' => 'first_name',
                        'label' => __('First Name', 'dry-cleaning-forms'),
                        'placeholder' => __('Your first name...', 'dry-cleaning-forms'),
                        'required' => true
                    ),
                    array(
                        'type' => 'email',
                        'name' => 'email',
                        'label' => __('Email Address', 'dry-cleaning-forms'),
                        'placeholder' => __('your@email.com', 'dry-cleaning-forms'),
                        'required' => true
                    ),
                    array(
                        'type' => 'tel',
                        'name' => 'phone',
                        'label' => __('Phone Number', 'dry-cleaning-forms'),
                        'placeholder' => __('(555) 123-4567', 'dry-cleaning-forms'),
                        'required' => false
                    )
                ),
                'button_text' => __('Get My Quote', 'dry-cleaning-forms')
            )
        ),
        'success_message' => array(
            'headline' => __('Thank you!', 'dry-cleaning-forms'),
            'description' => __('We\'ve received your request and will send you a personalized quote within 30 minutes. Check your email for details!', 'dry-cleaning-forms'),
            'features' => array(
                __('✓ Personalized quote in 30 minutes', 'dry-cleaning-forms'),
                __('✓ No obligation or commitment', 'dry-cleaning-forms'),
                __('✓ Special new customer discount included', 'dry-cleaning-forms')
            )
        ),
        'privacy_text' => __('Your information is secure and will never be shared.', 'dry-cleaning-forms')
    ),
    'css_template' => '
        .dcf-popup-multi-step {
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
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
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
            color: #999;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dcf-popup-close:hover {
            color: #666;
        }
        
        .dcf-popup-progress {
            margin-bottom: 30px;
        }
        
        .dcf-popup-progress-bar {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .dcf-popup-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2271b1, #72aee6);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .dcf-popup-progress-text {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .dcf-popup-step {
            display: none;
        }
        
        .dcf-popup-step.active {
            display: block;
        }
        
        .dcf-popup-step-content {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .dcf-popup-headline {
            font-size: 26px;
            font-weight: bold;
            margin: 0 0 15px 0;
            color: #333;
            line-height: 1.3;
        }
        
        .dcf-popup-description {
            font-size: 16px;
            margin: 0 0 25px 0;
            color: #666;
            line-height: 1.5;
        }
        
        .dcf-popup-field {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .dcf-popup-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .dcf-popup-field input[type="text"],
        .dcf-popup-field input[type="email"],
        .dcf-popup-field input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
        }
        
        .dcf-popup-radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .dcf-popup-radio-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-radio-option:hover {
            border-color: #2271b1;
            background: #f8f9fa;
        }
        
        .dcf-popup-radio-option.selected {
            border-color: #2271b1;
            background: #e7f3ff;
        }
        
        .dcf-popup-radio-option input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
        }
        
        .dcf-popup-radio-option label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }
        
        .dcf-popup-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        
        .dcf-popup-btn-back {
            background: none;
            border: 1px solid #ddd;
            color: #666;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-btn-back:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }
        
        .dcf-popup-btn-next {
            background: #2271b1;
            color: #ffffff;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dcf-popup-btn-next:hover {
            background: #1e5a8a;
            transform: translateY(-1px);
        }
        
        .dcf-popup-btn-next:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .dcf-popup-success {
            text-align: center;
            display: none;
        }
        
        .dcf-popup-success.active {
            display: block;
        }
        
        .dcf-popup-success-icon {
            font-size: 48px;
            color: #00a32a;
            margin-bottom: 20px;
        }
        
        .dcf-popup-features {
            margin: 25px 0;
            text-align: left;
        }
        
        .dcf-popup-feature {
            font-size: 14px;
            color: #00a32a;
            margin-bottom: 8px;
            display: block;
        }
        
        .dcf-popup-privacy {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .dcf-popup-multi-step {
                width: 95%;
                padding: 25px;
            }
            
            .dcf-popup-headline {
                font-size: 22px;
            }
            
            .dcf-popup-description {
                font-size: 14px;
            }
            
            .dcf-popup-radio-option {
                padding: 12px;
            }
            
            .dcf-popup-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .dcf-popup-btn-back,
            .dcf-popup-btn-next {
                width: 100%;
            }
        }
    '
);

return $template_config; 