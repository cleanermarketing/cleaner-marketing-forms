<?php
/**
 * Time-Delay Popup Template
 *
 * Optimized for time-based triggers with high conversion focus
 * as prioritized by the user.
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_config = array(
    'name' => __('Time-Delay Popup', 'dry-cleaning-forms'),
    'description' => __('A popup that appears after a specified time delay, perfect for engaging visitors who are browsing your content.', 'dry-cleaning-forms'),
    'type' => 'time-delay',
    'category' => 'engagement',
    'preview_image' => 'time-delay-preview.png',
    'features' => array('Timed Display', 'Engagement Tracking', 'Social Proof Elements'),
    'default_settings' => array(
        'popup_type' => 'modal',
        'width' => 550,
        'height' => 'auto',
        'position' => 'center',
        'overlay' => true,
        'overlay_color' => 'rgba(0,0,0,0.7)',
        'background_color' => '#ffffff',
        'border_radius' => 8,
        'padding' => 35,
        'close_button' => true,
        'close_on_overlay' => true,
        'animation' => 'fadeInUp',
        'animation_duration' => 400,
        'trigger_delay' => 15, // 15 seconds default
        'auto_close' => false,
        'auto_close_delay' => 0
    ),
    'default_content' => array(
        'headline' => __('Still Looking for the Perfect Dry Cleaning Service?', 'dry-cleaning-forms'),
        'subheadline' => __('Get 20% OFF your first order + FREE pickup & delivery', 'dry-cleaning-forms'),
        'description' => __('Join thousands of satisfied customers who trust us with their garments. Professional cleaning, convenient service, unbeatable prices.', 'dry-cleaning-forms'),
        'benefits' => array(
            __('✓ Expert stain removal', 'dry-cleaning-forms'),
            __('✓ Same-day service available', 'dry-cleaning-forms'),
            __('✓ Eco-friendly cleaning process', 'dry-cleaning-forms'),
            __('✓ 100% satisfaction guarantee', 'dry-cleaning-forms')
        ),
        'form_fields' => array(
            array(
                'type' => 'email',
                'label' => __('Email Address', 'dry-cleaning-forms'),
                'placeholder' => __('Enter your email for instant discount...', 'dry-cleaning-forms'),
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => __('ZIP Code', 'dry-cleaning-forms'),
                'placeholder' => __('ZIP code for delivery area...', 'dry-cleaning-forms'),
                'required' => false
            )
        ),
        'submit_button' => array(
            'text' => __('Get My 20% Discount Now', 'dry-cleaning-forms'),
            'background_color' => '#28a745',
            'text_color' => '#ffffff',
            'border_radius' => 5
        ),
        'secondary_button' => array(
            'text' => __('Maybe later', 'dry-cleaning-forms'),
            'style' => 'link',
            'color' => '#6c757d'
        ),
        'privacy_text' => __('Your information is secure. Unsubscribe anytime.', 'dry-cleaning-forms'),
        'social_proof' => array(
            'rating' => '4.9',
            'reviews_count' => '2,847',
            'customers_count' => '15,000+'
        )
    ),
    'css_template' => '
        .dcf-popup-time-delay {
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
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            border: 1px solid #e0e0e0;
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
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 5px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .dcf-popup-close:hover {
            background: #f5f5f5;
            color: #666;
        }
        
        .dcf-popup-content {
            text-align: center;
        }
        
        .dcf-popup-headline {
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 12px 0;
            color: #333;
            line-height: 1.3;
        }
        
        .dcf-popup-subheadline {
            font-size: 20px;
            margin: 0 0 18px 0;
            color: #28a745;
            font-weight: 600;
        }
        
        .dcf-popup-description {
            font-size: 15px;
            margin: 0 0 20px 0;
            color: #666;
            line-height: 1.5;
        }
        
        .dcf-popup-benefits {
            text-align: left;
            margin: 0 0 25px 0;
            padding: 0;
            list-style: none;
        }
        
        .dcf-popup-benefits li {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #555;
            padding-left: 0;
        }
        
        .dcf-popup-form {
            margin-bottom: 20px;
        }
        
        .dcf-popup-field {
            margin-bottom: 15px;
        }
        
        .dcf-popup-field input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .dcf-popup-field input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .dcf-popup-submit {
            background: {{submit_button.background_color}};
            color: {{submit_button.text_color}};
            border: none;
            padding: 14px 25px;
            border-radius: {{submit_button.border_radius}}px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dcf-popup-submit:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .dcf-popup-secondary {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 13px;
            cursor: pointer;
            margin-top: 12px;
            text-decoration: underline;
            transition: color 0.3s ease;
        }
        
        .dcf-popup-secondary:hover {
            color: #495057;
        }
        
        .dcf-popup-privacy {
            font-size: 11px;
            color: #999;
            margin-top: 15px;
            line-height: 1.4;
        }
        
        .dcf-popup-social-proof {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: #666;
        }
        
        .dcf-popup-rating {
            display: inline-flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .dcf-popup-stars {
            color: #ffc107;
            margin-right: 8px;
            font-size: 16px;
        }
        
        .dcf-popup-rating-text {
            font-weight: 600;
            color: #333;
        }
        
        .dcf-popup-stats {
            font-size: 12px;
            color: #888;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .dcf-popup-time-delay {
                width: 95%;
                padding: 25px;
                margin: 20px;
                max-height: 85vh;
            }
            
            .dcf-popup-headline {
                font-size: 24px;
            }
            
            .dcf-popup-subheadline {
                font-size: 18px;
            }
            
            .dcf-popup-field input {
                padding: 14px 15px;
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            .dcf-popup-submit {
                padding: 16px 25px;
                font-size: 16px;
            }
        }
        
        /* Animation classes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .dcf-popup-time-delay.dcf-animate-fadeInUp {
            animation: fadeInUp {{animation_duration}}ms ease-out;
        }
        
        /* Performance optimizations */
        .dcf-popup-time-delay {
            will-change: transform, opacity;
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Accessibility improvements */
        .dcf-popup-time-delay:focus-within {
            outline: 2px solid #28a745;
            outline-offset: 2px;
        }
        
        .dcf-popup-field input:invalid {
            border-color: #dc3545;
        }
        
        .dcf-popup-field input:valid {
            border-color: #28a745;
        }
    ',
    'js_template' => '
        // Time-delay specific JavaScript
        (function($) {
            var timeDelayPopup = {
                init: function() {
                    this.bindEvents();
                    this.trackEngagement();
                },
                
                bindEvents: function() {
                    // Track form interactions
                    $(".dcf-popup-time-delay input").on("focus", function() {
                        dcf_analytics.trackEvent("interaction", {
                            type: "field_focus",
                            field: $(this).attr("name")
                        });
                    });
                    
                    // Track secondary button clicks
                    $(".dcf-popup-secondary").on("click", function() {
                        dcf_analytics.trackEvent("close", {
                            type: "secondary_button",
                            reason: "maybe_later"
                        });
                    });
                },
                
                trackEngagement: function() {
                    var startTime = Date.now();
                    var engaged = false;
                    
                    // Track time spent
                    setInterval(function() {
                        var timeSpent = (Date.now() - startTime) / 1000;
                        if (timeSpent > 5 && !engaged) {
                            engaged = true;
                            dcf_analytics.trackEvent("engagement", {
                                time_spent: timeSpent,
                                type: "time_threshold"
                            });
                        }
                    }, 1000);
                    
                    // Track scroll within popup
                    $(".dcf-popup-time-delay").on("scroll", function() {
                        if (!engaged) {
                            engaged = true;
                            dcf_analytics.trackEvent("engagement", {
                                type: "scroll"
                            });
                        }
                    });
                }
            };
            
            $(document).ready(function() {
                timeDelayPopup.init();
            });
        })(jQuery);
    '
);

return $template_config; 