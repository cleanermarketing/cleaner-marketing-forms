/**
 * Popup Admin JavaScript for Dry Cleaning Forms
 *
 * @package DryCleaningForms
 */

(function($) {
    'use strict';

    // Global popup admin object
    window.DCF_PopupAdmin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Tab navigation
            this.bindTabEvents();
            
            // Popup form events
            this.bindPopupFormEvents();
            
            // Preview events
            this.bindPreviewEvents();
            
            // A/B testing events
            this.bindABTestingEvents();
            
            // Template events
            this.bindTemplateEvents();
            
            // Design tab events
            this.bindDesignTabEvents();
        },

        bindTabEvents: function() {
            // Tab switching
            $(document).on('click', '.nav-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var target = $tab.attr('href');
                
                // Update active tab
                $tab.siblings().removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');
                
                // Update active content
                $('.dcf-tab-content').removeClass('dcf-tab-active');
                $(target).addClass('dcf-tab-active');
                
                // Update hidden field with active tab
                var tabName = target.replace('#', '');
                $('#active_tab').val(tabName);
                
                // Update URL without reloading
                var url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
                
                // Initialize design preview when switching to design tab
                if (target === '#design') {
                    // Always reinitialize to ensure proper display
                    DCF_PopupAdmin.designPreviewInitialized = false;
                    DCF_PopupAdmin.initializeDesignPreview();
                }
            });
        },

        bindPopupFormEvents: function() {
            console.log('DCF Popup: Binding popup form events...');
            
            // Check if form exists
            var $form = $('#popup-edit-form');
            console.log('DCF Popup: Form element found:', $form.length > 0);
            if ($form.length > 0) {
                console.log('DCF Popup: Form element:', $form[0]);
            }
            
            // Popup type change
            $(document).on('change', '#popup_type', function() {
                DCF_PopupAdmin.togglePopupTypeSettings($(this).val());
            });

            // Auto close toggle
            $(document).on('change', 'input[name="popup_config[auto_close]"]', function() {
                $('.dcf-auto-close-delay').toggle($(this).is(':checked'));
            });

            // Trigger type change
            $(document).on('change', 'select[name="trigger_settings[type]"]', function() {
                DCF_PopupAdmin.toggleTriggerSettings($(this).val());
            });

            // Color picker initialization
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.dcf-color-picker').wpColorPicker();
            }

            // Image upload functionality
            DCF_PopupAdmin.bindImageUploadEvents();

            // Form validation
            $('#popup-edit-form').on('submit', function(e) {
                console.log('DCF Popup: Form submit event triggered');
                console.log('DCF Popup: Form element:', this);
                console.log('DCF Popup: Form action:', $(this).attr('action'));
                console.log('DCF Popup: Form method:', $(this).attr('method'));
                
                var isValid = DCF_PopupAdmin.validatePopupForm();
                if (!isValid) {
                    console.log('DCF Popup: Form submission prevented due to validation failure');
                    e.preventDefault();
                    return false;
                }
                console.log('DCF Popup: Form validation passed, allowing submission');
                
                // Log form data
                var formData = $(this).serializeArray();
                console.log('DCF Popup: Form data being submitted:', formData);
                
                // Check for any other event handlers that might prevent submission
                setTimeout(function() {
                    console.log('DCF Popup: Form should have submitted by now');
                }, 100);
                
                return true;
            });
            
            console.log('DCF Popup: Form submit handler bound');

            // Real-time preview updates
            $(document).on('input change', '.dcf-preview-trigger', function() {
                DCF_PopupAdmin.updatePreview();
            });
            
            // Real-time design preview updates
            $(document).on('input change', '#design input, #design select, #design textarea', function() {
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Color picker change events
            $(document).on('change', '.dcf-color-picker', function() {
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Form change handler for preview
            $(document).on('change', '#form_id', function() {
                DCF_PopupAdmin.updateFormPreview();
                // Also update design preview if visible
                if ($('#design').hasClass('dcf-tab-active')) {
                    DCF_PopupAdmin.updateDesignPreview();
                }
            });
            
            // Refresh preview button
            $(document).on('click', '#refresh-preview', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.updateFormPreview();
            });

            // Template selection
            $(document).on('click', '.dcf-template-option', function() {
                DCF_PopupAdmin.selectTemplate($(this));
            });

            // Targeting rule management
            $(document).on('click', '.dcf-add-targeting-rule', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.addTargetingRule($(this));
            });

            $(document).on('click', '.dcf-remove-targeting-rule', function(e) {
                e.preventDefault();
                $(this).closest('.dcf-targeting-rule').remove();
            });
        },

        bindPreviewEvents: function() {
            // Preview popup
            $(document).on('click', '.dcf-preview-popup', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.previewPopup();
            });

            // Test popup triggers
            $(document).on('click', '.dcf-test-trigger', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.testTrigger($(this).data('trigger'));
            });
        },

        bindABTestingEvents: function() {
            // Create A/B test
            $(document).on('click', '.dcf-create-ab-test', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.createABTest();
            });

            // View A/B test results
            $(document).on('click', '.dcf-view-ab-results', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.viewABResults($(this).data('test-id'));
            });
        },

        bindTemplateEvents: function() {
            // Import template
            $(document).on('click', '.dcf-import-template', function(e) {
                e.preventDefault();
                $('#dcf-template-import').click();
            });

            $(document).on('change', '#dcf-template-import', function() {
                DCF_PopupAdmin.importTemplate(this.files[0]);
            });

            // Export template
            $(document).on('click', '.dcf-export-template', function(e) {
                e.preventDefault();
                DCF_PopupAdmin.exportTemplate($(this).data('popup-id'));
            });
        },
        
        bindDesignTabEvents: function() {
            // Collapsible sections
            $(document).on('click', '.dcf-design-section .dcf-section-header', function(e) {
                e.preventDefault();
                var $section = $(this).closest('.dcf-design-section');
                $section.toggleClass('collapsed');
                
                // Animate the toggle arrow
                var $toggle = $section.find('.dcf-section-toggle');
                if ($section.hasClass('collapsed')) {
                    $toggle.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
                } else {
                    $toggle.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
                }
                
                // Smooth animation for content
                var $content = $section.find('.dcf-section-content');
                if ($section.hasClass('collapsed')) {
                    $content.slideUp(300);
                } else {
                    $content.slideDown(300);
                }
            });
            
            // Color swatch clicks
            $(document).on('click', '.dcf-color-swatch', function() {
                var color = $(this).data('color');
                var $group = $(this).closest('.dcf-color-input-group');
                var $colorInput = $group.find('input[type="color"]');
                var $textInput = $group.find('.dcf-color-text');
                
                $colorInput.val(color).trigger('change');
                if ($textInput.length) {
                    $textInput.val(color);
                }
                
                // Update preview
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Sync color text input with color picker
            $(document).on('input', '.dcf-color-text', function() {
                var $textInput = $(this);
                var syncTarget = $textInput.data('sync');
                if (syncTarget) {
                    $('#' + syncTarget).val($textInput.val()).trigger('change');
                }
            });
            
            // Range slider sync with text input
            $(document).on('input', '.dcf-range-slider', function() {
                var $slider = $(this);
                var value = $slider.val();
                var $container = $slider.closest('.dcf-font-size-control, .dcf-line-height-control, .dcf-duration-control');
                var $textInput = $container.find('input[type="text"], input[type="number"]');
                
                if ($textInput.length) {
                    // Update text input with units
                    var currentVal = $textInput.val();
                    var unit = currentVal.replace(/[0-9.]/g, '') || 'px';
                    $textInput.val(value + unit);
                }
                
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Text input sync with range slider
            $(document).on('input', '.dcf-font-size-control input[type="text"], .dcf-line-height-control input[type="text"], .dcf-duration-control input[type="number"]', function() {
                var $textInput = $(this);
                var value = parseFloat($textInput.val());
                var $container = $textInput.closest('.dcf-font-size-control, .dcf-line-height-control, .dcf-duration-control');
                var $slider = $container.find('.dcf-range-slider');
                
                if ($slider.length && !isNaN(value)) {
                    $slider.val(value);
                }
                
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button alignment selection (for text)
            $(document).on('click', '.dcf-align-button[data-align]', function() {
                var $button = $(this);
                var align = $button.data('align');
                var $group = $button.closest('.dcf-button-group');
                
                // Check if this is for text alignment or button alignment
                var isTextAlign = $group.siblings('#text_align').length > 0;
                var targetInput = isTextAlign ? '#text_align' : '#button_align';
                
                // Update active state
                $group.find('.dcf-align-button').removeAttr('data-active');
                $button.attr('data-active', 'true');
                
                // Update hidden input
                $(targetInput).val(align).trigger('change');
                
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button spacing slider sync
            $(document).on('input', '#button_spacing_slider', function() {
                var value = $(this).val();
                $('#button_spacing').val(value + 'px');
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button spacing text input sync
            $(document).on('input', '#button_spacing', function() {
                var value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    $('#button_spacing_slider').val(value);
                }
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button layout change
            $(document).on('change', 'input[name="design_settings[button_layout]"]', function() {
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Preset buttons
            $(document).on('click', '.dcf-preset-btn', function() {
                var $button = $(this);
                var radius = $button.data('radius');
                $('#button_border_radius').val(radius).trigger('change');
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Overlay presets
            $(document).on('click', '.dcf-overlay-presets .dcf-preset', function() {
                var value = $(this).data('value');
                $('#overlay_color').val(value).trigger('change');
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Form layout change
            $(document).on('change', 'input[name="design_settings[form_layout]"]', function() {
                var layout = $(this).val();
                if (layout === 'single') {
                    $('.dcf-layout-options').hide();
                } else {
                    $('.dcf-layout-options').show();
                }
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Background image change
            $(document).on('change', '#background_image', function() {
                if ($(this).val()) {
                    $('.dcf-background-image-options').show();
                } else {
                    $('.dcf-background-image-options').hide();
                }
            });
            
            // Device preview selector
            $(document).on('click', '.dcf-device-btn', function() {
                var $button = $(this);
                var device = $button.data('device');
                
                // Update active state
                $('.dcf-device-btn').removeClass('active');
                $button.addClass('active');
                
                // Update preview container
                $('.dcf-popup-preview-container').attr('data-device', device);
                
                // Trigger preview update to adjust sizing
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button style preset handler
            $(document).on('change', 'input[name="design_settings[button_style_preset]"]', function() {
                var preset = $(this).val();
                DCF_PopupAdmin.applyButtonPreset(preset);
                
                // Show/hide custom settings
                if (preset === 'custom') {
                    $('.dcf-custom-button-settings').show();
                } else {
                    $('.dcf-custom-button-settings').hide();
                }
                
                // Update preview
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button icon toggle
            $(document).on('change', '#button_show_icon', function() {
                if ($(this).is(':checked')) {
                    $('.dcf-button-icon-settings').show();
                } else {
                    $('.dcf-button-icon-settings').hide();
                }
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Button icon selection
            $(document).on('change', 'input[name="design_settings[button_icon]"], #button_icon_position, #button_icon_spacing', function() {
                DCF_PopupAdmin.updateButtonPreviewIcons();
                DCF_PopupAdmin.updateDesignPreview();
            });
        },

        initComponents: function() {
            // Initialize on page load
            this.togglePopupTypeSettings($('#popup_type').val());
            this.toggleTriggerSettings($('select[name="trigger_settings[type]"]').val());
            
            // Initialize auto close visibility
            $('.dcf-auto-close-delay').toggle($('input[name="popup_config[auto_close]"]').is(':checked'));

            // Initialize Select2 for targeting
            if (typeof $.fn.select2 !== 'undefined') {
                $('.dcf-select2').select2({
                    width: '100%'
                });
            }

            // Initialize sortable for targeting rules
            if (typeof $.fn.sortable !== 'undefined') {
                $('.dcf-targeting-rules').sortable({
                    handle: '.dcf-rule-handle',
                    placeholder: 'dcf-rule-placeholder'
                });
            }
            
            // Initialize gradient controls
            this.initGradientControls();
            
            // Initialize design tab components
            this.initDesignTabComponents();
            
            // Initialize button preset visibility
            var selectedPreset = $('input[name="design_settings[button_style_preset]"]:checked').val();
            if (selectedPreset === 'custom') {
                $('.dcf-custom-button-settings').show();
            } else {
                $('.dcf-custom-button-settings').hide();
            }
            
            // Initialize button icon visibility
            if ($('#button_show_icon').is(':checked')) {
                $('.dcf-button-icon-settings').show();
            }
            
            // Initialize button icon preview
            this.updateButtonPreviewIcons();
            
            // Initialize real-time preview if on design tab
            if ($('#design').hasClass('dcf-tab-active')) {
                this.initializeDesignPreview();
            }
            
            // Also check for nav-tab-active class (WordPress default)
            if ($('a[href="#design"]').hasClass('nav-tab-active')) {
                setTimeout(function() {
                    DCF_PopupAdmin.initializeDesignPreview();
                }, 100);
            }
        },
        
        initGradientControls: function() {
            // Handle gradient background toggle
            $('#use_gradient').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#gradient_settings').slideDown();
                } else {
                    $('#gradient_settings').slideUp();
                }
            });
            
            // Handle third color toggle
            $('#gradient_add_third').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#gradient_third_color').slideDown();
                } else {
                    $('#gradient_third_color').slideUp();
                }
            });
            
            // Update gradient angle field visibility based on gradient type
            $('#gradient_type').on('change', function() {
                if ($(this).val() === 'linear') {
                    $('#gradient_angle').closest('div').show();
                } else {
                    $('#gradient_angle').closest('div').hide();
                }
            }).trigger('change');
        },
        
        initDesignTabComponents: function() {
            // Initialize collapsible sections
            $('.dcf-design-section').each(function() {
                var $section = $(this);
                var isCollapsed = $section.hasClass('collapsed');
                
                // Set initial arrow state
                var $toggle = $section.find('.dcf-section-toggle');
                if (isCollapsed) {
                    $toggle.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
                    $section.find('.dcf-section-content').hide();
                }
            });
            
            // Initialize form layout visibility
            var currentLayout = $('input[name="design_settings[form_layout]"]:checked').val();
            if (currentLayout === 'single') {
                $('.dcf-layout-options').hide();
            }
            
            // Initialize background image options visibility
            if (!$('#background_image').val()) {
                $('.dcf-background-image-options').hide();
            }
            
            // Initialize range sliders with current values
            $('.dcf-range-slider').each(function() {
                var $slider = $(this);
                var $container = $slider.closest('.dcf-font-size-control, .dcf-line-height-control, .dcf-duration-control');
                var $textInput = $container.find('input[type="text"], input[type="number"]');
                
                if ($textInput.length) {
                    var value = parseFloat($textInput.val());
                    if (!isNaN(value)) {
                        $slider.val(value);
                    }
                }
            });
            
            // Initialize gradient preview
            this.updateGradientPreview();
            
            // Add gradient change handlers
            $('#gradient_type, #gradient_color_1, #gradient_color_2, #gradient_color_3, #gradient_angle, #gradient_add_third').on('change input', function() {
                DCF_PopupAdmin.updateGradientPreview();
            });
            
            // Gradient preset handler
            $(document).on('click', '.dcf-gradient-preset', function(e) {
                e.preventDefault();
                var $preset = $(this);
                var colors = JSON.parse($preset.attr('data-colors'));
                var angle = $preset.attr('data-angle') || '135';
                
                // Set gradient colors
                $('#gradient_color_1').val(colors[0]).trigger('change');
                $('#gradient_color_2').val(colors[1]).trigger('change');
                
                // Set angle if linear
                if ($('#gradient_type').val() === 'linear') {
                    $('#gradient_angle').val(angle).trigger('change');
                }
                
                // Update preview
                DCF_PopupAdmin.updateGradientPreview();
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Opacity range sync
            $('#background_opacity').on('input', function() {
                $(this).siblings('.dcf-range-value').text($(this).val() + '%');
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Step transition duration slider
            $('#step_transition_duration_slider').on('input', function() {
                $('#step_transition_duration').val($(this).val());
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Step transition duration input
            $('#step_transition_duration').on('input', function() {
                var value = parseInt($(this).val());
                if (!isNaN(value)) {
                    $('#step_transition_duration_slider').val(value);
                }
                DCF_PopupAdmin.updateDesignPreview();
            });
            
            // Progress indicator toggle
            $('input[name="design_settings[show_step_progress]"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.dcf-progress-options').show();
                } else {
                    $('.dcf-progress-options').hide();
                }
            });
        },
        
        updateGradientPreview: function() {
            var $preview = $('.dcf-gradient-preview');
            if (!$preview.length) return;
            
            var type = $('#gradient_type').val() || 'linear';
            var color1 = $('#gradient_color_1').val() || '#667eea';
            var color2 = $('#gradient_color_2').val() || '#764ba2';
            var useThird = $('#gradient_add_third').is(':checked');
            var color3 = $('#gradient_color_3').val() || '#f093fb';
            var angle = $('#gradient_angle').val() || '135';
            
            var gradient;
            if (type === 'radial') {
                gradient = 'radial-gradient(circle, ' + color1 + ', ' + color2;
                if (useThird) {
                    gradient += ', ' + color3;
                }
                gradient += ')';
            } else {
                gradient = 'linear-gradient(' + angle + 'deg, ' + color1 + ', ' + color2;
                if (useThird) {
                    gradient += ', ' + color3;
                }
                gradient += ')';
            }
            
            $preview.css('background', gradient);
        },

        togglePopupTypeSettings: function(popupType) {
            // Hide all type-specific settings
            $('.dcf-modal-only, .dcf-sidebar-only, .dcf-bar-only, .dcf-multi-step-only, .dcf-split-screen-only').hide();
            $('.dcf-modal-sidebar-only').hide();

            // Show relevant settings
            switch(popupType) {
                case 'modal':
                    $('.dcf-modal-only, .dcf-modal-sidebar-only').show();
                    break;
                case 'sidebar':
                    $('.dcf-sidebar-only, .dcf-modal-sidebar-only').show();
                    break;
                case 'bar':
                    $('.dcf-bar-only').show();
                    break;
                case 'multi-step':
                    $('.dcf-multi-step-only').show();
                    break;
                case 'split-screen':
                    $('.dcf-split-screen-only, .dcf-modal-only').show();
                    break;
            }
        },

        toggleTriggerSettings: function(triggerType) {
            // Hide all trigger-specific settings
            $('.dcf-trigger-setting').hide();

            // Show relevant settings
            $('.dcf-trigger-' + triggerType).show();
            
            // Update trigger settings dynamically
            this.updateTriggerSettings(triggerType);
        },
        
        updateTriggerSettings: function(triggerType) {
            var triggerTypes = dcf_popup_admin.trigger_types;
            var settings = triggerTypes[triggerType];
            
            if (!settings) return;
            
            // Update description
            $('.dcf-trigger-description').text(settings.description);
            
            // Clear existing settings
            $('#trigger-specific-settings').empty();
            
            // Add new settings
            if (settings.settings) {
                var self = this;
                $.each(settings.settings, function(key, setting) {
                    var row = $('<tr>');
                    var label = $('<th scope="row">').html('<label for="trigger_' + key + '">' + setting.label + '</label>');
                    var cell = $('<td>');
                    
                    var input;
                    switch (setting.type) {
                        case 'number':
                            input = $('<input type="number" class="small-text">');
                            if (setting.min !== undefined) input.attr('min', setting.min);
                            if (setting.max !== undefined) input.attr('max', setting.max);
                            break;
                        case 'text':
                            input = $('<input type="text" class="regular-text">');
                            break;
                        case 'select':
                            input = $('<select class="regular-text">');
                            $.each(setting.options, function(value, text) {
                                input.append($('<option>').val(value).text(text));
                            });
                            break;
                        case 'checkbox':
                            input = $('<input type="checkbox" value="1">');
                            break;
                    }
                    
                    input.attr('name', 'trigger_settings[' + key + ']');
                    input.attr('id', 'trigger_' + key);
                    input.val(setting.default);
                    
                    cell.append(input);
                    
                    if (setting.description) {
                        cell.append($('<p class="description">').text(setting.description));
                    }
                    
                    row.append(label).append(cell);
                    $('#trigger-specific-settings').append(row);
                });
            }
        },

        validatePopupForm: function() {
            console.log('DCF Popup: Form validation started');
            var isValid = true;
            var errors = [];

            // Check required fields
            var popupName = $('input[name="popup_name"]').val().trim();
            console.log('DCF Popup: Popup name:', popupName);
            if (!popupName) {
                errors.push('Popup name is required.');
                isValid = false;
            }

            var formId = $('select[name="popup_config[form_id]"]').val();
            console.log('DCF Popup: Form ID:', formId);
            if (!formId) {
                errors.push('Please select a form for the popup.');
                isValid = false;
            }

            // Validate trigger settings
            var triggerType = $('select[name="trigger_settings[type]"]').val();
            console.log('DCF Popup: Trigger type:', triggerType);
            if (triggerType === 'time_delay') {
                var delay = parseInt($('input[name="trigger_settings[delay]"]').val());
                console.log('DCF Popup: Time delay:', delay);
                if (!delay || delay < 1) {
                    errors.push('Time delay must be at least 1 second.');
                    isValid = false;
                }
            }

            // Show errors if any
            if (!isValid) {
                console.log('DCF Popup: Validation failed with errors:', errors);
                DCF_PopupAdmin.showValidationErrors(errors);
            } else {
                console.log('DCF Popup: Validation passed');
            }

            return isValid;
        },

        showValidationErrors: function(errors) {
            var errorHtml = '<div class="notice notice-error"><ul>';
            errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul></div>';

            $('.dcf-popup-header').after(errorHtml);
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.dcf-popup-edit').offset().top
            }, 500);

            // Remove error after 5 seconds
            setTimeout(function() {
                $('.notice-error').fadeOut();
            }, 5000);
        },

        previewPopup: function() {
            var formData = $('#popup-edit-form').serialize();
            
            // Open preview in new window
            var previewWindow = window.open('', 'popup-preview', 'width=800,height=600');
            
            // Send preview data via POST
            var form = $('<form>', {
                method: 'POST',
                action: dcf_popup_admin.preview_url,
                target: 'popup-preview'
            });

            // Add form data
            formData.split('&').forEach(function(pair) {
                var parts = pair.split('=');
                if (parts.length === 2) {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: decodeURIComponent(parts[0]),
                        value: decodeURIComponent(parts[1])
                    }));
                }
            });

            // Add preview flag
            form.append($('<input>', {
                type: 'hidden',
                name: 'dcf_preview',
                value: '1'
            }));

            $('body').append(form);
            form.submit();
            form.remove();
        },

        testTrigger: function(triggerType) {
            // Simulate trigger for testing
            var testData = {
                action: 'dcf_test_popup_trigger',
                trigger_type: triggerType,
                popup_id: $('input[name="popup_id"]').val(),
                nonce: dcf_popup_admin.nonce
            };

            $.post(dcf_popup_admin.ajax_url, testData, function(response) {
                if (response.success) {
                    DCF_PopupAdmin.showNotice('Trigger test successful!', 'success');
                } else {
                    DCF_PopupAdmin.showNotice('Trigger test failed: ' + response.data, 'error');
                }
            });
        },

        updatePreview: function() {
            // Debounced preview update
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(function() {
                // Update live preview if available
                var previewFrame = $('#dcf-popup-preview-frame');
                if (previewFrame.length) {
                    // Send updated settings to preview frame
                    var settings = DCF_PopupAdmin.getPopupSettings();
                    previewFrame[0].contentWindow.postMessage({
                        type: 'updatePopup',
                        settings: settings
                    }, '*');
                } else {
                    // Update form preview
                    DCF_PopupAdmin.updateFormPreview();
                }
            }, 500);
        },
        
        updateFormPreview: function() {
            var formId = $('#form_id').val();
            
            if (!formId) {
                $('#popup-preview').html('Select a form to see preview...');
                return;
            }
            
            // Make AJAX request to get form preview
            $.post(dcf_popup_admin.ajax_url, {
                action: 'dcf_admin_action',
                dcf_action: 'get_form_preview',
                form_id: formId,
                nonce: dcf_popup_admin.nonce
            }, function(response) {
                if (response.success) {
                    $('#popup-preview').html(response.data);
                } else {
                    $('#popup-preview').html('Error loading preview.');
                }
            });
        },

        getPopupSettings: function() {
            var settings = {};
            
            // Collect all form data
            $('#popup-edit-form').serializeArray().forEach(function(field) {
                settings[field.name] = field.value;
            });

            return settings;
        },

        selectTemplate: function($template) {
            // Remove active class from all templates
            $('.dcf-template-option').removeClass('active');
            
            // Add active class to selected template
            $template.addClass('active');
            
            // Load template data
            var templateId = $template.data('template-id');
            this.loadTemplate(templateId);
        },

        loadTemplate: function(templateId) {
            var data = {
                action: 'dcf_load_popup_template',
                template_id: templateId,
                nonce: dcf_popup_admin.nonce
            };

            $.post(dcf_popup_admin.ajax_url, data, function(response) {
                if (response.success) {
                    // Populate form with template data
                    DCF_PopupAdmin.populateFormWithTemplate(response.data);
                    DCF_PopupAdmin.showNotice('Template loaded successfully!', 'success');
                } else {
                    DCF_PopupAdmin.showNotice('Failed to load template: ' + response.data, 'error');
                }
            });
        },

        populateFormWithTemplate: function(templateData) {
            // Populate form fields with template data
            Object.keys(templateData).forEach(function(key) {
                var $field = $('[name="' + key + '"]');
                if ($field.length) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', templateData[key]);
                    } else {
                        $field.val(templateData[key]);
                    }
                }
            });

            // Trigger change events to update UI
            $('[name]').trigger('change');
        },

        addTargetingRule: function($button) {
            var ruleType = $button.data('rule-type');
            var template = $('#dcf-targeting-rule-template-' + ruleType).html();
            
            if (template) {
                var $container = $button.closest('.dcf-targeting-section').find('.dcf-targeting-rules');
                $container.append(template);
                
                // Initialize any new components
                $container.find('.dcf-select2:not(.select2-hidden-accessible)').select2({
                    width: '100%'
                });
            }
        },

        createABTest: function() {
            var popupId = $('input[name="popup_id"]').val();
            if (!popupId) {
                DCF_PopupAdmin.showNotice('Please save the popup first before creating an A/B test.', 'warning');
                return;
            }

            // Redirect to A/B test creation page
            window.location.href = dcf_popup_admin.ab_test_url + '&popup_id=' + popupId;
        },

        viewABResults: function(testId) {
            // Redirect to A/B test results page
            window.location.href = dcf_popup_admin.ab_results_url + '&test_id=' + testId;
        },

        importTemplate: function(file) {
            if (!file) return;

            var formData = new FormData();
            formData.append('action', 'dcf_import_popup_template');
            formData.append('template_file', file);
            formData.append('nonce', dcf_popup_admin.nonce);

            $.ajax({
                url: dcf_popup_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        DCF_PopupAdmin.showNotice('Template imported successfully!', 'success');
                        // Refresh template list
                        location.reload();
                    } else {
                        DCF_PopupAdmin.showNotice('Import failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    DCF_PopupAdmin.showNotice('Import failed due to server error.', 'error');
                }
            });
        },

        exportTemplate: function(popupId) {
            var data = {
                action: 'dcf_export_popup_template',
                popup_id: popupId,
                nonce: dcf_popup_admin.nonce
            };

            // Create download link
            var form = $('<form>', {
                method: 'POST',
                action: dcf_popup_admin.ajax_url
            });

            Object.keys(data).forEach(function(key) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: data[key]
                }));
            });

            $('body').append(form);
            form.submit();
            form.remove();
        },

        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.dcf-popup-header').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        bindImageUploadEvents: function() {
            var self = this;
            
            // Upload image button
            $(document).on('click', '.dcf-upload-image', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var targetField = $button.data('target');
                var imageUploader;
                
                // If the media frame already exists, reopen it
                if (imageUploader) {
                    imageUploader.open();
                    return;
                }
                
                // Create the media frame
                imageUploader = wp.media({
                    title: 'Select Background Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                // When an image is selected, run a callback
                imageUploader.on('select', function() {
                    var attachment = imageUploader.state().get('selection').first().toJSON();
                    var imageUrl = attachment.url;
                    
                    // Set the image URL in the hidden field
                    $('#' + targetField).val(imageUrl).trigger('change');
                    
                    // Update the preview
                    var $preview = $('#' + targetField + '_preview');
                    if ($preview.length) {
                        $preview.html('<img src="' + imageUrl + '" alt="">');
                    }
                    
                    // Show the remove button
                    $button.siblings('.dcf-remove-image').show();
                });
                
                // Open the media uploader
                imageUploader.open();
            });
            
            // Remove image button
            $(document).on('click', '.dcf-remove-image', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var targetField = $button.data('target');
                
                // Clear the hidden field
                $('#' + targetField).val('').trigger('change');
                
                // Clear the preview
                var $preview = $('#' + targetField + '_preview');
                if ($preview.length) {
                    $preview.html('');
                }
                
                // Hide the remove button
                $button.hide();
            });
        },
        
        // Design preview functionality
        designPreviewInitialized: false,
        designPreviewTimeout: null,
        
        initializeDesignPreview: function() {
            if (this.designPreviewInitialized) return;
            
            // Load initial preview
            this.loadDesignPreview();
            this.designPreviewInitialized = true;
        },
        
        loadDesignPreview: function() {
            var $preview = $('#design-popup-preview');
            var formId = $('#form_id').val();
            var popupId = $('input[name="popup_id"]').val();
            var templateId = $('input[name="template_id"]').val() || $('#template_id').val();
            
            // Check if we have either a form or template
            if (!formId && !templateId) {
                $preview.html('<div class="dcf-preview-loading">Select a form or template to see preview...</div>');
                return;
            }
            
            // Show loading state
            $preview.html('<div class="dcf-preview-loading">Loading preview...</div>');
            
            // Get current design settings
            var designSettings = this.getDesignSettings();
            
            // Make AJAX request to get popup preview
            $.post(dcf_popup_admin.ajax_url, {
                action: 'dcf_admin_action',
                dcf_action: 'get_popup_preview',
                form_id: formId || 0,
                popup_id: popupId || 0,
                template_id: templateId || '',
                popup_type: $('#popup_type').val(),
                design_settings: designSettings,
                nonce: dcf_popup_admin.nonce
            })
            .done(function(response) {
                if (response && response.success) {
                    $preview.html(response.data);
                    // Apply styles after preview loads
                    DCF_PopupAdmin.applyPreviewStyles();
                    // Initialize multi-step navigation if present - with small delay to ensure DOM is ready
                    setTimeout(function() {
                        DCF_PopupAdmin.initPreviewMultiStep();
                        
                        // Double-check after another delay in case of slow rendering
                        setTimeout(function() {
                            var $multiStep = $('#design-popup-preview').find('.dcf-multi-step-popup');
                            if ($multiStep.length > 0 && !$multiStep.find('.dcf-step-active').is(':visible')) {
                                console.log('DCF: First step not visible after init, forcing display');
                                DCF_PopupAdmin.initPreviewMultiStep();
                            }
                        }, 300);
                    }, 100);
                } else {
                    var errorMsg = 'Error loading preview.';
                    if (response && response.data) {
                        errorMsg += ' Error: ' + response.data;
                    }
                    console.error('DCF Preview: Error in response', response);
                    $preview.html('<div class="dcf-preview-loading">' + errorMsg + '</div>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('DCF Preview: AJAX request failed', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    formId: formId,
                    popupId: popupId,
                    templateId: templateId
                });
                $preview.html('<div class="dcf-preview-loading">Error loading preview. Check console for details.</div>');
            });
        },
        
        updateDesignPreview: function() {
            // Debounce updates
            clearTimeout(this.designPreviewTimeout);
            this.designPreviewTimeout = setTimeout(function() {
                DCF_PopupAdmin.applyPreviewStyles();
            }, 300);
        },
        
        getDesignSettings: function() {
            var settings = {};
            
            try {
                // Collect all design settings
                $('#design input, #design select, #design textarea').each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    if (name && name.indexOf('design_settings') !== -1) {
                        var key = name.replace('design_settings[', '').replace(']', '');
                        if ($field.is(':checkbox')) {
                            settings[key] = $field.is(':checked') ? '1' : '';
                        } else {
                            settings[key] = $field.val();
                        }
                    }
                });
                
            } catch (error) {
                console.error('DCF Preview: Error collecting design settings', error);
            }
            
            return settings;
        },
        
        applyPreviewStyles: function() {
            var $previewPopup = $('#design-popup-preview .dcf-popup');
            if (!$previewPopup.length) return;
            
            var settings = this.getDesignSettings();
            var popupId = 'preview';
            
            // Remove existing dynamic styles for preview
            $('#dcf-preview-dynamic-styles').remove();
            
            // Generate comprehensive dynamic styles (matching frontend popup-engine.js)
            var dynamicStyles = this.generatePreviewDynamicStyles(popupId, settings);
            
            // Add the dynamic styles to the document
            if (dynamicStyles) {
                $('head').append('<style id="dcf-preview-dynamic-styles">' + dynamicStyles + '</style>');
            }
            
            // Apply basic popup styles
            if (settings.width) {
                $previewPopup.css('width', settings.width);
            }
            if (settings.height && settings.height !== 'auto') {
                $previewPopup.css('height', settings.height);
            }
            
            // Background styles
            var opacity = (settings.background_opacity || '100') / 100;
            
            if (settings.use_gradient === '1' && settings.gradient_color_1 && settings.gradient_color_2) {
                var gradient;
                if (settings.gradient_type === 'radial') {
                    gradient = 'radial-gradient(circle, ' + settings.gradient_color_1 + ', ' + settings.gradient_color_2;
                    if (settings.gradient_add_third === '1' && settings.gradient_color_3) {
                        gradient = 'radial-gradient(circle, ' + settings.gradient_color_1 + ', ' + settings.gradient_color_2 + ', ' + settings.gradient_color_3;
                    }
                } else {
                    var angle = settings.gradient_angle || '135';
                    gradient = 'linear-gradient(' + angle + 'deg, ' + settings.gradient_color_1 + ', ' + settings.gradient_color_2;
                    if (settings.gradient_add_third === '1' && settings.gradient_color_3) {
                        gradient = 'linear-gradient(' + angle + 'deg, ' + settings.gradient_color_1 + ', ' + settings.gradient_color_2 + ', ' + settings.gradient_color_3;
                    }
                }
                gradient += ')';
                
                // Apply gradient with opacity
                if (opacity < 1) {
                    $previewPopup.css({
                        'background': gradient,
                        'position': 'relative'
                    });
                    // Add pseudo element for opacity
                    var styleId = 'dcf-preview-opacity-style';
                    $('#' + styleId).remove();
                    var opacityStyle = '<style id="' + styleId + '">' +
                        '#design-popup-preview .dcf-popup::before {' +
                            'content: "";' +
                            'position: absolute;' +
                            'top: 0; left: 0; right: 0; bottom: 0;' +
                            'background: white;' +
                            'opacity: ' + (1 - opacity) + ';' +
                            'z-index: 0;' +
                        '}' +
                        '#design-popup-preview .dcf-popup > * {' +
                            'position: relative;' +
                            'z-index: 1;' +
                        '}' +
                    '</style>';
                    $('head').append(opacityStyle);
                } else {
                    $previewPopup.css('background', gradient);
                }
            } else {
                var bgColor = settings.background_color || '#ffffff';
                if (opacity < 1) {
                    // Convert hex to rgba with opacity
                    var rgb = this.hexToRgb(bgColor);
                    if (rgb) {
                        bgColor = 'rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + opacity + ')';
                    }
                }
                $previewPopup.css('background-color', bgColor);
                
                if (settings.background_image) {
                    $previewPopup.css({
                        'background-image': 'url(' + settings.background_image + ')',
                        'background-position': settings.background_position || 'center center',
                        'background-size': settings.background_size || 'cover',
                        'background-repeat': settings.background_repeat || 'no-repeat'
                    });
                }
            }
            
            // Typography
            $previewPopup.css({
                'color': settings.text_color || '#333333',
                'font-size': settings.font_size || '16px',
                'font-weight': settings.font_weight || '400',
                'line-height': settings.line_height || '1.6',
                'text-align': settings.text_align || 'left',
                'border-radius': settings.border_radius || '8px',
                'padding': settings.padding || '30px'
            });
        },
        
        generatePreviewDynamicStyles: function(popupId, design) {
            var styles = [];
            
            // Add layout styles
            if (design.form_layout && design.form_layout !== 'single') {
                var columnGap = design.column_gap || '20px';
                var fieldSpacing = design.field_spacing || '20px';
                
                if (design.form_layout === 'two-column') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-form-fields,' +
                        '[data-popup-id="' + popupId + '"] form {' +
                            'display: grid;' +
                            'grid-template-columns: 1fr 1fr;' +
                            'gap: ' + fieldSpacing + ' ' + columnGap + ';' +
                            'align-items: start;' +
                        '}' +
                        
                        '/* Full width fields */' +
                        '[data-popup-id="' + popupId + '"] .dcf-field-type-textarea,' +
                        '[data-popup-id="' + popupId + '"] .dcf-field-type-message,' +
                        '[data-popup-id="' + popupId + '"] .dcf-field-type-comments,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper,' +
                        '[data-popup-id="' + popupId + '"] .dcf-form-submit {' +
                            'grid-column: 1 / -1;' +
                        '}'
                    );
                    
                    // Apply full width settings
                    var fullWidthFields = design.full_width_fields || ['message', 'submit'];
                    if (fullWidthFields.includes && fullWidthFields.includes('email')) {
                        styles.push(
                            '[data-popup-id="' + popupId + '"] .dcf-field-type-email,' +
                            '[data-popup-id="' + popupId + '"] .dcf-email-field {' +
                                'grid-column: 1 / -1;' +
                            '}'
                        );
                    }
                } else if (design.form_layout === 'inline') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-form-fields,' +
                        '[data-popup-id="' + popupId + '"] form {' +
                            'display: flex;' +
                            'flex-wrap: wrap;' +
                            'gap: ' + fieldSpacing + ' ' + columnGap + ';' +
                            'align-items: flex-end;' +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"] .dcf-field {' +
                            'flex: 1 1 200px;' +
                        '}' +
                        
                        '/* Full width fields in inline layout */' +
                        '[data-popup-id="' + popupId + '"] .dcf-field-type-textarea,' +
                        '[data-popup-id="' + popupId + '"] .dcf-field-type-message,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper {' +
                            'flex: 1 1 100%;' +
                        '}'
                    );
                }
                
                // Mobile responsiveness for layouts
                styles.push(
                    '@media (max-width: 600px) {' +
                        '[data-popup-id="' + popupId + '"] .dcf-form-fields,' +
                        '[data-popup-id="' + popupId + '"] form {' +
                            'display: block !important;' +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"] .dcf-field {' +
                            'margin-bottom: ' + fieldSpacing + ' !important;' +
                        '}' +
                    '}'
                );
            }
            
            // Add heading styles if specified
            if (design.heading_font_size || design.heading_font_weight) {
                styles.push(
                    '[data-popup-id="' + popupId + '"] h1,' +
                    '[data-popup-id="' + popupId + '"] h2,' +
                    '[data-popup-id="' + popupId + '"] h3,' +
                    '[data-popup-id="' + popupId + '"] h4,' +
                    '[data-popup-id="' + popupId + '"] h5,' +
                    '[data-popup-id="' + popupId + '"] h6 {' +
                        (design.heading_font_size ? 'font-size: ' + design.heading_font_size + ' !important;' : '') +
                        (design.heading_font_weight ? 'font-weight: ' + design.heading_font_weight + ' !important;' : '') +
                        (design.text_align ? 'text-align: ' + design.text_align + ' !important;' : '') +
                        'line-height: 1.2 !important;' +
                        'margin-bottom: 0.5em !important;' +
                    '}'
                );
            }
            
            // Add paragraph and list styles
            if (design.font_size || design.line_height) {
                styles.push(
                    '[data-popup-id="' + popupId + '"] p,' +
                    '[data-popup-id="' + popupId + '"] li {' +
                        (design.font_size ? 'font-size: ' + design.font_size + ' !important;' : '') +
                        (design.line_height ? 'line-height: ' + design.line_height + ' !important;' : '') +
                    '}'
                );
            }
            
            // Add button styles
            var buttonShadows = {
                'none': 'none',
                'small': '0 2px 4px rgba(0,0,0,0.1)',
                'medium': '0 4px 8px rgba(0,0,0,0.15)',
                'large': '0 8px 16px rgba(0,0,0,0.2)'
            };
            
            var buttonShadow = buttonShadows[design.button_shadow] || buttonShadows['small'];
            
            styles.push(
                '[data-popup-id="' + popupId + '"] button,' +
                '[data-popup-id="' + popupId + '"] .button,' +
                '[data-popup-id="' + popupId + '"] input[type="submit"] {' +
                    (design.button_bg_color ? 'background-color: ' + design.button_bg_color + ' !important;' : '') +
                    (design.button_text_color ? 'color: ' + design.button_text_color + ' !important;' : '') +
                    (design.button_border_radius ? 'border-radius: ' + design.button_border_radius + ' !important;' : '') +
                    (design.button_padding ? 'padding: ' + design.button_padding + ' !important;' : '') +
                    (design.button_font_size ? 'font-size: ' + design.button_font_size + ' !important;' : '') +
                    (design.button_font_weight ? 'font-weight: ' + design.button_font_weight + ' !important;' : '') +
                    (design.button_text_transform ? 'text-transform: ' + design.button_text_transform + ' !important;' : '') +
                    'box-shadow: ' + buttonShadow + ' !important;' +
                    'border: none !important;' +
                    'transition: all 0.3s ease !important;' +
                    'cursor: pointer !important;' +
                    'display: inline-block !important;' +
                    'text-decoration: none !important;' +
                    'line-height: 1.5 !important;' +
                    'position: relative !important;' +
                '}' +
                
                '[data-popup-id="' + popupId + '"] button:hover,' +
                '[data-popup-id="' + popupId + '"] .button:hover,' +
                '[data-popup-id="' + popupId + '"] input[type="submit"]:hover {' +
                    (design.button_hover_bg_color ? 'background-color: ' + design.button_hover_bg_color + ' !important;' : '') +
                    'transform: translateY(-2px) !important;' +
                    'box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;' +
                '}' +
                
                '[data-popup-id="' + popupId + '"] button:active,' +
                '[data-popup-id="' + popupId + '"] .button:active,' +
                '[data-popup-id="' + popupId + '"] input[type="submit"]:active {' +
                    'transform: translateY(0) !important;' +
                    'box-shadow: ' + buttonShadow + ' !important;' +
                '}'
            );
            
            // Add button layout styles
            if (design.button_layout || design.button_spacing || design.button_align) {
                var buttonLayout = design.button_layout || 'inline';
                var buttonSpacing = design.button_spacing || '12px';
                var buttonAlign = design.button_align || 'center';
                
                // Button container styles
                styles.push(
                    '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper,' +
                    '[data-popup-id="' + popupId + '"] .dcf-button-wrapper,' +
                    '[data-popup-id="' + popupId + '"] .dcf-step-buttons {' +
                        'display: flex !important;' +
                        'gap: ' + buttonSpacing + ' !important;' +
                        'margin-top: 20px !important;' +
                        (buttonLayout === 'stacked' ? 'flex-direction: column !important;' : 'flex-direction: row !important;') +
                        (buttonLayout === 'inline' ? 'flex-wrap: wrap !important;' : '') +
                        (buttonAlign === 'left' ? 'justify-content: flex-start !important;' : '') +
                        (buttonAlign === 'center' ? 'justify-content: center !important;' : '') +
                        (buttonAlign === 'right' ? 'justify-content: flex-end !important;' : '') +
                    '}'
                );
                
                // Individual button styles based on layout
                if (buttonLayout === 'full-width') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper input[type="submit"],' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons .button {' +
                            'width: 100% !important;' +
                            'flex: 1 1 100% !important;' +
                        '}'
                    );
                } else if (buttonLayout === 'stacked') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper input[type="submit"],' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons .button {' +
                            'width: auto !important;' +
                            'min-width: 200px !important;' +
                        '}'
                    );
                } else {
                    // Inline layout
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-submit-wrapper input[type="submit"],' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-button-wrapper .button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons button,' +
                        '[data-popup-id="' + popupId + '"] .dcf-step-buttons .button {' +
                            'width: auto !important;' +
                            'flex: 0 1 auto !important;' +
                        '}'
                    );
                }
            }
            
            // Add button icon styles if enabled
            if (design.button_show_icon === '1' && design.button_icon) {
                var iconSpacing = design.button_icon_spacing || '8px';
                var iconPosition = design.button_icon_position || 'after';
                
                // Define icon content based on selected icon
                var iconMap = {
                    'arrow-right': '→',
                    'arrow-right-long': '→',
                    'chevron-right': '›',
                    'arrow-circle': '➜'
                };
                
                var iconContent = iconMap[design.button_icon] || '→';
                
                if (iconPosition === 'before') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] button::before,' +
                        '[data-popup-id="' + popupId + '"] .button::before,' +
                        '[data-popup-id="' + popupId + '"] input[type="submit"]::before {' +
                            'content: "' + iconContent + '" !important;' +
                            'margin-right: ' + iconSpacing + ' !important;' +
                            'display: inline-block !important;' +
                            'font-size: 1.1em !important;' +
                            'vertical-align: middle !important;' +
                            'transition: transform 0.3s ease !important;' +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"] button:hover::before,' +
                        '[data-popup-id="' + popupId + '"] .button:hover::before,' +
                        '[data-popup-id="' + popupId + '"] input[type="submit"]:hover::before {' +
                            'transform: translateX(-2px) !important;' +
                        '}'
                    );
                } else {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] button::after,' +
                        '[data-popup-id="' + popupId + '"] .button::after,' +
                        '[data-popup-id="' + popupId + '"] input[type="submit"]::after {' +
                            'content: "' + iconContent + '" !important;' +
                            'margin-left: ' + iconSpacing + ' !important;' +
                            'display: inline-block !important;' +
                            'font-size: 1.1em !important;' +
                            'vertical-align: middle !important;' +
                            'transition: transform 0.3s ease !important;' +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"] button:hover::after,' +
                        '[data-popup-id="' + popupId + '"] .button:hover::after,' +
                        '[data-popup-id="' + popupId + '"] input[type="submit"]:hover::after {' +
                            'transform: translateX(2px) !important;' +
                        '}'
                    );
                }
            }
            
            // Add form field styles if customized
            if (design.field_style || design.field_border_color || design.field_focus_color || design.field_bg_color) {
                styles.push(
                    '[data-popup-id="' + popupId + '"] input[type="text"],' +
                    '[data-popup-id="' + popupId + '"] input[type="email"],' +
                    '[data-popup-id="' + popupId + '"] input[type="tel"],' +
                    '[data-popup-id="' + popupId + '"] input[type="number"],' +
                    '[data-popup-id="' + popupId + '"] input[type="password"],' +
                    '[data-popup-id="' + popupId + '"] textarea,' +
                    '[data-popup-id="' + popupId + '"] select {' +
                        (design.field_border_color ? 'border-color: ' + design.field_border_color + ' !important;' : '') +
                        (design.field_bg_color ? 'background-color: ' + design.field_bg_color + ' !important;' : '') +
                        (design.field_text_color ? 'color: ' + design.field_text_color + ' !important;' : '') +
                        (design.field_padding ? 'padding: ' + design.field_padding + ' !important;' : '') +
                        (design.field_border_radius ? 'border-radius: ' + design.field_border_radius + ' !important;' : '') +
                    '}' +
                    
                    '[data-popup-id="' + popupId + '"] input[type="text"]:focus,' +
                    '[data-popup-id="' + popupId + '"] input[type="email"]:focus,' +
                    '[data-popup-id="' + popupId + '"] input[type="tel"]:focus,' +
                    '[data-popup-id="' + popupId + '"] input[type="number"]:focus,' +
                    '[data-popup-id="' + popupId + '"] input[type="password"]:focus,' +
                    '[data-popup-id="' + popupId + '"] textarea:focus,' +
                    '[data-popup-id="' + popupId + '"] select:focus {' +
                        (design.field_focus_color ? 'border-color: ' + design.field_focus_color + ' !important;' : '') +
                        (design.field_focus_color ? 'box-shadow: 0 0 0 4px ' + design.field_focus_color + '33 !important;' : '') +
                    '}'
                );
                
                // Apply field style variations
                if (design.field_style === 'underline') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] input[type="text"],' +
                        '[data-popup-id="' + popupId + '"] input[type="email"],' +
                        '[data-popup-id="' + popupId + '"] input[type="tel"],' +
                        '[data-popup-id="' + popupId + '"] input[type="number"],' +
                        '[data-popup-id="' + popupId + '"] input[type="password"],' +
                        '[data-popup-id="' + popupId + '"] textarea,' +
                        '[data-popup-id="' + popupId + '"] select {' +
                            'border: none !important;' +
                            'border-bottom: 2px solid ' + (design.field_border_color || '#e1e8ed') + ' !important;' +
                            'border-radius: 0 !important;' +
                            'background: transparent !important;' +
                            'padding-left: 0 !important;' +
                            'padding-right: 0 !important;' +
                        '}'
                    );
                } else if (design.field_style === 'classic') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] input[type="text"],' +
                        '[data-popup-id="' + popupId + '"] input[type="email"],' +
                        '[data-popup-id="' + popupId + '"] input[type="tel"],' +
                        '[data-popup-id="' + popupId + '"] input[type="number"],' +
                        '[data-popup-id="' + popupId + '"] input[type="password"],' +
                        '[data-popup-id="' + popupId + '"] textarea,' +
                        '[data-popup-id="' + popupId + '"] select {' +
                            'border-radius: 0 !important;' +
                        '}'
                    );
                } else if (design.field_style === 'floating') {
                    styles.push(
                        '[data-popup-id="' + popupId + '"] .dcf-field {' +
                            'position: relative;' +
                            'margin-top: 20px;' +
                        '}' +
                        '[data-popup-id="' + popupId + '"] .dcf-field label {' +
                            'position: absolute;' +
                            'top: 18px;' +
                            'left: 20px;' +
                            'transition: all 0.3s ease;' +
                            'background: ' + (design.background_color || '#ffffff') + ';' +
                            'padding: 0 5px;' +
                        '}' +
                        '[data-popup-id="' + popupId + '"] .dcf-field input:focus + label,' +
                        '[data-popup-id="' + popupId + '"] .dcf-field input:not(:placeholder-shown) + label {' +
                            'top: -10px;' +
                            'font-size: 12px;' +
                            'color: ' + (design.field_focus_color || '#3498db') + ';' +
                        '}'
                    );
                }
            }
            
            // Add text formatting styles if enabled
            if (design.enable_text_bold === '1') {
                styles.push(
                    '[data-popup-id="' + popupId + '"] strong,' +
                    '[data-popup-id="' + popupId + '"] b {' +
                        'font-weight: 700 !important;' +
                    '}'
                );
            }
            
            if (design.enable_text_italic === '1') {
                styles.push(
                    '[data-popup-id="' + popupId + '"] em,' +
                    '[data-popup-id="' + popupId + '"] i {' +
                        'font-style: italic !important;' +
                    '}'
                );
            }
            
            if (design.enable_text_underline === '1') {
                styles.push(
                    '[data-popup-id="' + popupId + '"] u {' +
                        'text-decoration: underline !important;' +
                    '}'
                );
            }
            
            // Add link styles
            if (design.link_color || design.link_hover_color || design.link_underline) {
                styles.push(
                    '[data-popup-id="' + popupId + '"] a {' +
                        (design.link_color ? 'color: ' + design.link_color + ' !important;' : '') +
                        (design.link_underline === '1' ? 'text-decoration: underline !important;' : 'text-decoration: none !important;') +
                        'transition: color 0.3s ease !important;' +
                    '}' +
                    
                    '[data-popup-id="' + popupId + '"] a:hover {' +
                        (design.link_hover_color ? 'color: ' + design.link_hover_color + ' !important;' : '') +
                    '}'
                );
            }
            
            // Add icon styles if enabled
            if (design.use_field_icons === '1') {
                styles.push(
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-email-field input,' +
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-phone-field input,' +
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-name-field input {' +
                        'padding-left: 50px !important;' +
                    '}' +
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-email-field::before {' +
                        'content: "✉";' +
                        'position: absolute;' +
                        'left: 20px;' +
                        'top: 50%;' +
                        'transform: translateY(-50%);' +
                        'font-size: 18px;' +
                        'opacity: 0.5;' +
                    '}' +
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-phone-field::before {' +
                        'content: "☎";' +
                        'position: absolute;' +
                        'left: 20px;' +
                        'top: 50%;' +
                        'transform: translateY(-50%);' +
                        'font-size: 18px;' +
                        'opacity: 0.5;' +
                    '}' +
                    '[data-popup-id="' + popupId + '"] .dcf-field.dcf-name-field::before {' +
                        'content: "👤";' +
                        'position: absolute;' +
                        'left: 20px;' +
                        'top: 50%;' +
                        'transform: translateY(-50%);' +
                        'font-size: 18px;' +
                        'opacity: 0.5;' +
                    '}'
                );
            }
            
            // Add split-screen mobile responsiveness
            if (design.split_mobile_layout && design.split_mobile_breakpoint) {
                var mobileLayout = design.split_mobile_layout || 'stacked';
                var breakpoint = design.split_mobile_breakpoint || '768';
                var mobileImageHeight = design.split_mobile_image_height || '200px';
                var mobilePadding = design.split_mobile_padding || '20px';
                
                styles.push(
                    '@media (max-width: ' + breakpoint + 'px) {' +
                        '[data-popup-id="' + popupId + '"].dcf-split-screen-popup {' +
                            (mobileLayout === 'stacked' ? 'flex-direction: column !important;' : '') +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"].dcf-split-screen-popup .dcf-split-image-section,' +
                        '[data-popup-id="' + popupId + '"].dcf-split-screen-popup .dcf-split-content-section {' +
                            (mobileLayout === 'stacked' ? 'flex: none !important; width: 100% !important;' : '') +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"].dcf-split-screen-popup .dcf-split-image-section {' +
                            (mobileLayout === 'stacked' ? 'height: ' + mobileImageHeight + ' !important;' : '') +
                            (mobileLayout === 'hide-image' ? 'display: none !important;' : '') +
                        '}' +
                        
                        '[data-popup-id="' + popupId + '"].dcf-split-screen-popup .dcf-split-content-section {' +
                            'padding: ' + mobilePadding + ' !important;' +
                            (mobileLayout === 'hide-image' ? 'flex: 1 1 100% !important; width: 100% !important;' : '') +
                        '}' +
                    '}'
                );
            }
            
            return styles.length > 0 ? styles.join('') : '';
        },
        
        /**
         * Initialize multi-step navigation in preview
         */
        initPreviewMultiStep: function() {
            var $preview = $('#design-popup-preview');
            var $multiStep = $preview.find('.dcf-multi-step-popup');
            
            if ($multiStep.length === 0) return;
            
            // Add preview controls for multi-step navigation
            if (!$preview.find('.dcf-preview-step-nav').length) {
                var navHtml = '<div class="dcf-preview-step-nav">' +
                    '<button class="dcf-preview-nav-btn dcf-prev-step" disabled>Previous</button>' +
                    '<span class="dcf-step-indicator">Step <span class="current-step">1</span> of <span class="total-steps">2</span></span>' +
                    '<button class="dcf-preview-nav-btn dcf-next-step">Next</button>' +
                    '</div>';
                $preview.append(navHtml);
            }
            
            // Update total steps count
            var totalSteps = $multiStep.find('.dcf-popup-step').length;
            $preview.find('.total-steps').text(totalSteps);
            
            // Don't interfere with CSS - let CSS handle initial display
            // Just set up the navigation
            
            // Handle preview navigation buttons
            $preview.off('click', '.dcf-prev-step, .dcf-next-step');
            $preview.on('click', '.dcf-prev-step', function() {
                DCF_PopupAdmin.navigatePreviewStepByIndex(-1);
            });
            $preview.on('click', '.dcf-next-step', function() {
                DCF_PopupAdmin.navigatePreviewStepByIndex(1);
            });
            
            // Remove the automatic JS manipulation that conflicts with CSS
            // The CSS will handle showing the first step
        },
        
        /**
         * Navigate preview by index
         */
        navigatePreviewStepByIndex: function(direction) {
            var $preview = $('#design-popup-preview');
            var $multiStep = $preview.find('.dcf-multi-step-popup');
            var $steps = $multiStep.find('.dcf-popup-step');
            
            // Find current visible step
            var currentIndex = 0;
            $steps.each(function(index) {
                if ($(this).is(':visible')) {
                    currentIndex = index;
                    return false;
                }
            });
            
            var newIndex = currentIndex + direction;
            
            // Bounds check
            if (newIndex < 0 || newIndex >= $steps.length) return;
            
            // Hide all steps
            $steps.hide().css({
                'display': 'none',
                'visibility': 'hidden'
            });
            
            // Show new step
            $steps.eq(newIndex).show().css({
                'display': 'block !important',
                'visibility': 'visible !important',
                'opacity': '1 !important',
                'transform': 'none !important'
            });
            
            // Update navigation
            $preview.find('.current-step').text(newIndex + 1);
            $preview.find('.dcf-prev-step').prop('disabled', newIndex === 0);
            $preview.find('.dcf-next-step').prop('disabled', newIndex === $steps.length - 1);
        },
        
        /**
         * Navigate to a specific step in preview
         */
        navigatePreviewStep: function($multiStep, stepId) {
            var $targetStep = $multiStep.find('[data-step-id="' + stepId + '"]');
            
            if ($targetStep.length === 0) return;
            
            var $currentStep = $multiStep.find('.dcf-step-active');
            var currentIndex = $currentStep.index();
            var targetIndex = $targetStep.index();
            
            // Apply transition settings
            var transition = $('#step_transition').val() || 'fade';
            var duration = $('#step_transition_duration').val() || '300';
            
            $multiStep.attr('data-transition', transition);
            $multiStep.css('--transition-duration', duration + 'ms');
            
            // Handle transition direction for slide
            if (transition === 'slide-horizontal' && currentIndex < targetIndex) {
                $currentStep.addClass('dcf-step-prev');
            }
            
            // Hide all steps with transition
            $multiStep.find('.dcf-popup-step').removeClass('dcf-step-active dcf-step-prev');
            
            // Show target step with transition
            setTimeout(function() {
                $multiStep.find('.dcf-popup-step').hide();
                $targetStep.show().addClass('dcf-step-active');
                
                // Update progress indicators
                DCF_PopupAdmin.updateStepProgress($multiStep, targetIndex);
            }, 50);
        },
        
        /**
         * Update step progress indicators
         */
        updateStepProgress: function($multiStep, stepIndex) {
            var totalSteps = $multiStep.find('.dcf-popup-step').length;
            var progress = ((stepIndex + 1) / totalSteps) * 100;
            
            // Update progress bar
            $multiStep.find('.dcf-progress-fill').css('width', progress + '%');
            
            // Update progress dots
            $multiStep.find('.dcf-progress-dot').removeClass('active');
            $multiStep.find('.dcf-progress-dot').eq(stepIndex).addClass('active');
            
            // Update progress numbers
            $multiStep.find('.dcf-progress-number').removeClass('active completed');
            $multiStep.find('.dcf-progress-number').eq(stepIndex).addClass('active');
            $multiStep.find('.dcf-progress-number:lt(' + stepIndex + ')').addClass('completed');
        },
        
        /**
         * Update button preview icons
         */
        updateButtonPreviewIcons: function() {
            var showIcon = $('#button_show_icon').is(':checked');
            var iconType = $('input[name="design_settings[button_icon]"]:checked').val();
            var iconPosition = $('#button_icon_position').val();
            
            var iconMap = {
                'arrow-right': '→',
                'arrow-right-long': '→',
                'chevron-right': '›',
                'arrow-circle': '➜'
            };
            
            $('.dcf-button-sample').each(function() {
                var $button = $(this);
                
                if (showIcon && iconType) {
                    $button.attr('data-icon', iconMap[iconType] || '→');
                    $button.attr('data-icon-position', iconPosition);
                } else {
                    $button.removeAttr('data-icon');
                    $button.removeAttr('data-icon-position');
                }
            });
        },
        
        /**
         * Convert hex color to RGB
         */
        hexToRgb: function(hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },
        
        /**
         * Apply button style preset
         */
        applyButtonPreset: function(preset) {
            var presets = {
                'primary': {
                    'button_bg_color': '#FF69B4',
                    'button_text_color': '#ffffff',
                    'button_hover_bg_color': '#FF1493',
                    'button_border_radius': '50px',
                    'button_padding': '16px 32px',
                    'button_font_size': '16px',
                    'button_font_weight': '600',
                    'button_text_transform': 'none',
                    'button_shadow': 'medium'
                },
                'secondary': {
                    'button_bg_color': '#6c757d',
                    'button_text_color': '#ffffff',
                    'button_hover_bg_color': '#5a6268',
                    'button_border_radius': '4px',
                    'button_padding': '12px 24px',
                    'button_font_size': '14px',
                    'button_font_weight': '500',
                    'button_text_transform': 'none',
                    'button_shadow': 'small'
                },
                'outline': {
                    'button_bg_color': 'transparent',
                    'button_text_color': '#2271b1',
                    'button_hover_bg_color': '#2271b1',
                    'button_border_radius': '4px',
                    'button_padding': '10px 20px',
                    'button_font_size': '14px',
                    'button_font_weight': '500',
                    'button_text_transform': 'none',
                    'button_shadow': 'none',
                    'button_border': '2px solid #2271b1'
                },
                'text-link': {
                    'button_bg_color': 'transparent',
                    'button_text_color': '#2271b1',
                    'button_hover_bg_color': 'transparent',
                    'button_border_radius': '0',
                    'button_padding': '0',
                    'button_font_size': '14px',
                    'button_font_weight': '400',
                    'button_text_transform': 'none',
                    'button_shadow': 'none',
                    'button_text_decoration': 'underline'
                }
            };
            
            if (preset === 'custom') {
                // Don't apply any preset for custom
                return;
            }
            
            if (presets[preset]) {
                var settings = presets[preset];
                for (var key in settings) {
                    var $field = $('[name="design_settings[' + key + ']"]');
                    if ($field.length) {
                        $field.val(settings[key]).trigger('change');
                    }
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('DCF Popup: Document ready, initializing...');
        
        // Add class to body for CSS targeting
        if ($('.dcf-popup-edit, .dcf-popup-list, .dcf-popup-templates').length) {
            $('body').addClass('dcf-popup-page');
        }
        
        // Add global error handler
        window.addEventListener('error', function(e) {
            console.error('DCF Popup: JavaScript error detected:', e.error);
            console.error('DCF Popup: Error message:', e.message);
            console.error('DCF Popup: Error source:', e.filename + ':' + e.lineno);
        });
        
        if ($('.dcf-popup-edit, .dcf-popup-list, .dcf-popup-templates').length) {
            console.log('DCF Popup: Found popup admin elements, initializing DCF_PopupAdmin');
            DCF_PopupAdmin.init();
            
            // Activate tab based on URL parameter
            var urlParams = new URLSearchParams(window.location.search);
            var activeTab = urlParams.get('tab') || 'general';
            
            // Update hidden field
            $('#active_tab').val(activeTab);
            
            // Activate the tab
            $('.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab[href="#' + activeTab + '"]').addClass('nav-tab-active');
            $('.dcf-tab-content').removeClass('dcf-tab-active');
            $('#' + activeTab).addClass('dcf-tab-active');
            
            // Initialize design preview if on design tab
            if (activeTab === 'design') {
                setTimeout(function() {
                    DCF_PopupAdmin.initializeDesignPreview();
                }, 100);
            }
        } else {
            console.log('DCF Popup: No popup admin elements found');
        }
    });

})(jQuery); 