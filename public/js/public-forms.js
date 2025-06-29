/**
 * Dry Cleaning Forms - Public JavaScript
 */

(function($) {
    'use strict';
    
    var DCF = {
        
        // Current submission data for multi-step forms
        currentSubmission: {
            id: null,
            step: 1,
            data: {}
        },
        
        // Initialize the plugin
        init: function() {
            // Prevent multiple initializations
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            
            this.bindEvents();
            this.initSignupForm();
            this.initFormValidation();
            this.initMultiStepForms();
            this.initUTMTracking();
        },
        
        // Bind event handlers
        bindEvents: function() {
            console.log('[DCF Debug] bindEvents called - removing and re-adding event handlers');
            
            // Use namespaced events to prevent duplicate handlers
            $(document).off('submit.dcf').on('submit.dcf', '.dcf-form[data-ajax="true"], .dcf-contact-form[data-ajax="true"], .dcf-optin-form[data-ajax="true"]', this.handleFormSubmit);
            
            // Signup form steps
            $(document).off('submit.dcf-signup').on('submit.dcf-signup', '.dcf-signup-form', this.handleSignupStep);
            
            // Service option selection
            $(document).off('change.dcf-service').on('change.dcf-service', '.dcf-service-option input[type="radio"]', this.handleServiceSelection);
            
            // Pickup date selection (only for forms that don't have embedded handlers)
            $(document).off('change.dcf-pickup').on('change.dcf-pickup', '#dcf_pickup_date', function() {
                // Don't handle if this is in a signup form container (has embedded handler)
                if ($(this).closest('.dcf-signup-form-container').length > 0) {
                    return;
                }
                DCF.handlePickupDateChange.call(this);
            });
            
            // Phone number formatting
            $(document).off('input.dcf-phone').on('input.dcf-phone', 'input[type="tel"], input[name*="phone"], input[id*="phone"]', this.formatPhoneNumber);
            $(document).off('paste.dcf-phone').on('paste.dcf-phone', 'input[type="tel"], input[name*="phone"], input[id*="phone"]', this.handlePhonePaste);
            
            // Real-time validation
            $(document).off('blur.dcf-validate').on('blur.dcf-validate', '.dcf-input, .dcf-textarea, .dcf-select', this.validateField);
            
            // Multi-step form navigation
            $(document).off('click.dcf-nav').on('click.dcf-nav', '.dcf-next-step', this.handleNextStep);
            $(document).off('click.dcf-nav-prev').on('click.dcf-nav-prev', '.dcf-prev-step', this.handlePrevStep);
        },
        
        // Initialize signup form
        initSignupForm: function() {
            var $container = $('.dcf-signup-form-container');
            if ($container.length === 0) return;
            
            // Show first step
            this.showSignupStep(1);
        },
        
        // Initialize form validation
        initFormValidation: function() {
            // Add validation classes
            $('.dcf-field input[required], .dcf-field textarea[required], .dcf-field select[required]')
                .closest('.dcf-field')
                .addClass('dcf-field-required');
            
            // Format any pre-filled phone numbers
            $('input[type="tel"], input[name*="phone"], input[id*="phone"]').each(function() {
                var $input = $(this);
                if ($input.val()) {
                    $input.trigger('input');
                }
            });
        },
        
        // Initialize multi-step forms
        initMultiStepForms: function() {
            $('.dcf-multi-step-form').each(function() {
                var $container = $(this);
                var $form = $container.find('form.dcf-form');
                var currentStep = 0;
                var $steps = $container.find('.dcf-step-content');
                var totalSteps = $steps.length;
                
                // Store step data on form
                $form.data('currentStep', currentStep);
                $form.data('totalSteps', totalSteps);
                $form.data('container', $container);
                
                // Initialize first step
                DCF.showFormStep($container, 0);
            });
        },
        
        // Handle next step button
        handleNextStep: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.dcf-form');
            var $container = $form.closest('.dcf-multi-step-form');
            var currentStep = $form.data('currentStep');
            var totalSteps = $form.data('totalSteps');
            var formId = $container.data('form-id');
            
            // Validate current step
            if (!DCF.validateStep($container, currentStep)) {
                return false;
            }
            
            // Get all form data collected so far
            var allFormData = DCF.getAllFormData($form);
            
            // Only handle multi-step forms from form builder, not old signup forms
            if (!$container.hasClass('dcf-signup-form-container')) {
                // Check for POS integration on first step (Personal Information)
                if (currentStep === 0 && $form.data('multi-step')) {
                    var formData = DCF.getStepData($container, currentStep);
                    
                    // Check if we need to verify existing customer
                    if (formData.email || formData.phone) {
                        $button.prop('disabled', true).text('Checking...');
                        DCF.checkExistingCustomer($form, formData, function(exists) {
                            if (exists) {
                                $button.prop('disabled', false).text('Next');
                                DCF.showCustomerExistsAlert($form);
                            } else {
                                // Create customer account first
                                DCF.currentSubmission.data = $.extend({}, DCF.currentSubmission.data, formData);
                                DCF.createCustomerAccount($form, function(success) {
                                    $button.prop('disabled', false).text('Next');
                                    if (success) {
                                        // Proceed to service selection
                                        DCF.showFormStep($container, currentStep + 1);
                                    } else {
                                        alert('Error creating customer account. Please try again.');
                                    }
                                });
                            }
                        });
                        return;
                    }
                }
            }
            
            // Only handle multi-step forms from form builder, not old signup forms
            if (!$container.hasClass('dcf-signup-form-container')) {
                // Handle service selection step (step 1)
                if (currentStep === 1) {
                    var serviceType = $container.find('input[name="dcf_field[service_type]"]:checked').val();
                    
                    if (!serviceType) {
                        alert('Please select a service option.');
                        return;
                    }
                    
                    // Store service selection
                    DCF.currentSubmission.data = $.extend({}, DCF.currentSubmission.data, {service_type: serviceType});
                    
                    if (serviceType === 'retail_store' || serviceType === 'not_sure') {
                        // Show success message and redirect to login
                        $button.prop('disabled', true);
                        // For retail store, show completion message with the form container
                        var $actualContainer = $form.closest('.dcf-form-container');
                        DCF.showAccountCreatedMessage($actualContainer, serviceType);
                        return;
                    } else if (serviceType === 'pickup_delivery') {
                        // Continue to address step
                        DCF.showFormStep($container, currentStep + 1);
                        return;
                    }
                }
                
                // Handle address information step (step 2)
                if (currentStep === 2) {
                    // Get address data from current step
                    var addressData = DCF.getStepData($container, currentStep);
                    
                    // Validate address fields
                    if (!addressData.address || !addressData.city || !addressData.state || !addressData.zip) {
                        alert('Please fill in all required address fields.');
                        return;
                    }
                    
                    // Merge with existing data
                    DCF.currentSubmission.data = $.extend({}, DCF.currentSubmission.data, addressData);
                    
                    // Update customer with address
                    $button.prop('disabled', true).text('Processing...');
                    DCF.updateCustomerAddress($form, DCF.currentSubmission.data, function(success, pickupDates) {
                        $button.prop('disabled', false).text('Next');
                        if (success) {
                            if (pickupDates && pickupDates.length > 0) {
                                console.log('Got pickup dates:', pickupDates);
                                // Show pickup date selection
                                DCF.showPickupDateSelection($container, pickupDates);
                            } else {
                                // Check if we should try to get pickup dates directly
                                console.log('No pickup dates returned with address update. Address ID:', DCF.currentSubmission.address_id);
                                // For now, continue to payment without pickup scheduling
                                DCF.showMessage($container, 'Address saved. Please add your payment information.', 'info');
                                setTimeout(function() {
                                    DCF.showPaymentStep($container);
                                }, 1000);
                            }
                        } else {
                            alert('Error updating address. Please try again.');
                        }
                    });
                    return;
                }
            }
            
            // Move to next step by default
            if (currentStep < totalSteps - 1) {
                DCF.currentSubmission.data = $.extend({}, DCF.currentSubmission.data, allFormData);
                DCF.showFormStep($container, currentStep + 1);
            }
        },
        
        // Handle previous step button
        handlePrevStep: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $button.closest('.dcf-form');
            var $container = $form.closest('.dcf-multi-step-form');
            var currentStep = $form.data('currentStep');
            
            // Move to previous step
            if (currentStep > 0) {
                DCF.showFormStep($container, currentStep - 1);
            }
        },
        
        // Show form step
        showFormStep: function($container, step) {
            var $form = $container.find('form.dcf-form');
            var $steps = $container.find('.dcf-step-content');
            var $stepIndicators = $container.find('.dcf-step');
            var totalSteps = $form.data('totalSteps');
            
            // Hide all steps
            $steps.hide();
            
            // Show current step
            $steps.eq(step).fadeIn();
            
            // Update step indicators
            $stepIndicators.removeClass('active completed');
            $stepIndicators.eq(step).addClass('active');
            for (var i = 0; i < step; i++) {
                $stepIndicators.eq(i).addClass('completed');
            }
            
            // Update navigation buttons
            var $prevButton = $container.find('.dcf-prev-step');
            var $nextButton = $container.find('.dcf-next-step');
            var $submitButton = $container.find('.dcf-submit-button');
            
            $prevButton.toggle(step > 0);
            $nextButton.toggle(step < totalSteps - 1);
            $submitButton.toggle(step === totalSteps - 1);
            
            // Update current step
            $form.data('currentStep', step);
            
            // Apply conditional logic
            DCF.applyConditionalLogic($container);
        },
        
        // Validate step
        validateStep: function($container, step) {
            var $step = $container.find('.dcf-step-content').eq(step);
            var isValid = true;
            
            $step.find('.dcf-input, .dcf-textarea, .dcf-select').each(function() {
                if ($(this).is(':visible') && !DCF.validateField.call(this)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        // Get step data
        getStepData: function($container, step) {
            var $step = $container.find('.dcf-step-content').eq(step);
            var data = {};
            
            $step.find('.dcf-input, .dcf-textarea, .dcf-select, .dcf-radio:checked, .dcf-checkbox:checked').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name) {
                    var fieldName = name.replace('dcf_field[', '').replace(']', '').replace('[]', '');
                    if ($field.attr('type') === 'checkbox') {
                        if (!data[fieldName]) data[fieldName] = [];
                        data[fieldName].push($field.val());
                    } else {
                        // Check if this is a phone field
                        if ($field.attr('type') === 'tel' || fieldName.indexOf('phone') !== -1) {
                            // Use raw phone number if available
                            var rawPhone = $field.data('raw-phone');
                            data[fieldName] = rawPhone || $field.val().replace(/\D/g, '');
                        } else {
                            data[fieldName] = $field.val();
                        }
                    }
                }
            });
            
            return data;
        },
        
        // Get all form data
        getAllFormData: function($form) {
            var data = {};
            
            $form.find('.dcf-input, .dcf-textarea, .dcf-select, .dcf-radio:checked, .dcf-checkbox:checked').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name) {
                    var fieldName = name.replace('dcf_field[', '').replace(']', '').replace('[]', '');
                    if ($field.attr('type') === 'checkbox') {
                        if (!data[fieldName]) data[fieldName] = [];
                        data[fieldName].push($field.val());
                    } else {
                        // Check if this is a phone field
                        if ($field.attr('type') === 'tel' || fieldName.indexOf('phone') !== -1) {
                            // Use raw phone number if available
                            var rawPhone = $field.data('raw-phone');
                            data[fieldName] = rawPhone || $field.val().replace(/\D/g, '');
                        } else {
                            data[fieldName] = $field.val();
                        }
                    }
                }
            });
            
            return data;
        },
        
        // Check existing customer
        checkExistingCustomer: function($form, data, callback) {
            var formId = $form.closest('.dcf-form-container').data('form-id');
            
            if (!formId) {
                callback(false);
                return;
            }
            
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_check_existing_customer',
                    email: data.email,
                    phone: data.phone,
                    form_id: formId,
                    nonce: $form.find('[name="dcf_nonce"]').val()
                },
                dataType: 'json',
                success: function(response) {
                    callback(response.success && response.data.exists);
                },
                error: function() {
                    callback(false);
                }
            });
        },
        
        // Show customer exists alert
        showCustomerExistsAlert: function($form) {
            var message = dcf_ajax.messages.customer_exists || 'You already have an account. Please login instead.';
            var loginUrl = dcf_ajax.login_url;
            
            // Show alert
            alert(message);
            
            // Redirect to login page if URL is available
            if (loginUrl) {
                window.location.href = loginUrl;
            }
        },
        
        // Apply conditional logic
        applyConditionalLogic: function($form) {
            // This would implement conditional field visibility
            // based on the form configuration
        },
        
        // Handle form submission
        handleFormSubmit: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent event from bubbling and being handled multiple times
            
            var $form = $(this);
            var $container = $form.closest('.dcf-form-container, .dcf-contact-form-container, .dcf-optin-form-container');
            
            // If no container found, use the form itself as container (for popup mode)
            if ($container.length === 0) {
                $container = $form;
                console.log('[DCF Debug] No container found, using form as container');
            }
            
            console.log('[DCF Debug] Form submission triggered:', {
                formId: $form.data('form-id'),
                containerId: $container.attr('class'),
                containerLength: $container.length
            });
            
            // Prevent multiple simultaneous submissions
            if ($form.data('submitting')) {
                console.log('[DCF Debug] Form already submitting, ignoring duplicate submission');
                return false;
            }
            $form.data('submitting', true);
            
            // Add a unique submission ID to track this specific submission
            var submissionId = 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            $form.data('submission-id', submissionId);
            console.log('[DCF Debug] Starting form submission with ID:', submissionId);
            
            // Validate form
            if (!DCF.validateForm($form)) {
                $form.data('submitting', false);
                return false;
            }
            
            // Show loading state
            DCF.setLoadingState($container, true);
            
            // Process phone fields before submission
            $form.find('input[type="tel"], input[name*="phone"], input[id*="phone"]').each(function() {
                var $phoneField = $(this);
                var rawPhone = $phoneField.data('raw-phone') || $phoneField.val().replace(/\D/g, '');
                
                // Create a hidden field with the raw phone number
                var fieldName = $phoneField.attr('name');
                if (fieldName) {
                    var $hiddenPhone = $form.find('input[type="hidden"][name="' + fieldName + '_raw"]');
                    if ($hiddenPhone.length === 0) {
                        $hiddenPhone = $('<input type="hidden" name="' + fieldName + '_raw" />');
                        $phoneField.after($hiddenPhone);
                    }
                    $hiddenPhone.val(rawPhone);
                    
                    // Also update the actual field to send raw number
                    $phoneField.val(rawPhone);
                }
            });
            
            // Submit form
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&submission_id=' + submissionId,
                dataType: 'json',
                success: function(response) {
                    // Verify this is the response for our submission
                    var currentSubmissionId = $form.data('submission-id');
                    if (currentSubmissionId !== submissionId) {
                        console.log('[DCF Debug] Ignoring stale response for submission:', submissionId, 'current:', currentSubmissionId);
                        return;
                    }
                    
                    // Reset submitting flag
                    $form.data('submitting', false);
                    
                    // Restore formatted phone numbers after submission
                    $form.find('input[type="tel"], input[name*="phone"], input[id*="phone"]').each(function() {
                        $(this).trigger('input');
                    });
                    
                    if (response.success) {
                        DCF.showMessage($container, response.data.message, 'success');
                        $form[0].reset();
                        
                        // Trigger conversion event if form is in a popup
                        var $popup = $form.closest('.dcf-popup');
                        if ($popup.length > 0) {
                            var popupId = $popup.data('popup-id');
                            $(document).trigger('dcf:popup:conversion', {
                                popup_id: popupId,
                                form_id: $form.data('form-id') || 0,
                                conversion_value: 0
                            });
                        }
                    } else {
                        DCF.showMessage($container, response.data.message, 'error');
                    }
                },
                error: function() {
                    // Verify this is the response for our submission
                    var currentSubmissionId = $form.data('submission-id');
                    if (currentSubmissionId !== submissionId) {
                        console.log('[DCF Debug] Ignoring stale error for submission:', submissionId, 'current:', currentSubmissionId);
                        return;
                    }
                    
                    // Reset submitting flag
                    $form.data('submitting', false);
                    
                    // Restore formatted phone numbers after submission
                    $form.find('input[type="tel"], input[name*="phone"], input[id*="phone"]').each(function() {
                        $(this).trigger('input');
                    });
                    
                    DCF.showMessage($container, dcf_ajax.messages.error, 'error');
                },
                complete: function() {
                    DCF.setLoadingState($container, false);
                }
            });
        },
        
        // Handle signup step submission
        handleSignupStep: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $container = $form.closest('.dcf-signup-form-container');
            var step = parseInt($form.data('step'));
            
            // Validate form
            if (!DCF.validateForm($form)) {
                return false;
            }
            
            // Show loading state
            DCF.setLoadingState($container, true);
            
            // Prepare form data
            var formData = $form.serialize();
            if (DCF.currentSubmission.id) {
                formData += '&submission_id=' + DCF.currentSubmission.id;
            }
            if (DCF.currentSubmission.customer_id) {
                formData += '&customer_id=' + DCF.currentSubmission.customer_id;
            }
            
            // Submit step
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update current submission data
                        if (response.data.submission_id) {
                            DCF.currentSubmission.id = response.data.submission_id;
                        }
                        if (response.data.customer_id) {
                            DCF.currentSubmission.customer_id = response.data.customer_id;
                        }
                        if (response.data.address_id) {
                            DCF.currentSubmission.address_id = response.data.address_id;
                            console.log('DCF: Stored address_id:', response.data.address_id);
                        }
                        if (response.data.phone) {
                            DCF.currentSubmission.phone = response.data.phone;
                            console.log('DCF: Stored phone:', response.data.phone);
                        }
                        
                        // Check if form is completed
                        if (response.data.completed) {
                            if (response.data.service_completion) {
                                // Handle retail store / not sure completion with POS-specific instructions
                                DCF.showServiceCompletionMessage($container, response.data);
                            } else {
                                // Handle regular completion (pickup/delivery completed)
                                DCF.showCompletionMessage($container, response.data.message, response.data.redirect_url);
                            }
                        } else if (response.data.customer_exists) {
                            DCF.showCustomerExistsMessage($container, response.data.message, response.data.redirect_url);
                        } else {
                            // Move to next step
                            DCF.moveToNextStep($container, step + 1, response.data.next_step);
                            DCF.showMessage($container, response.data.message, 'info');
                        }
                    } else {
                        DCF.showMessage($container, response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    DCF.showMessage($container, dcf_ajax.messages.error, 'error');
                },
                complete: function() {
                    DCF.setLoadingState($container, false);
                }
            });
        },
        
        // Handle service selection
        handleServiceSelection: function() {
            var $option = $(this).closest('.dcf-service-option');
            var $options = $option.siblings('.dcf-service-option');
            
            // Update visual state
            $options.removeClass('selected');
            $option.addClass('selected');
        },
        
        // Handle pickup date change
        handlePickupDateChange: function() {
            console.log('DCF: External handlePickupDateChange called');
            var selectedDate = $(this).val();
            var $timeSlot = $('#dcf_time_slot');
            var $scheduleButton = $('#dcf_schedule_pickup');
            console.log('DCF: Selected date:', selectedDate);
            
            if (!selectedDate) {
                $timeSlot.empty().append('<option value="">Select Time</option>');
                if ($scheduleButton.length) {
                    $scheduleButton.prop('disabled', true);
                }
                return;
            }
            
            // Get pickup dates data - try both variable names for compatibility
            var pickupDates = window.dcfPickupDates || window.dcf_pickup_dates || [];
            console.log('DCF: Available pickup dates:', pickupDates);
            var dateInfo = pickupDates.find(function(date) {
                return date.date === selectedDate;
            });
            console.log('DCF: Found dateInfo:', dateInfo);
            
            $timeSlot.empty().append('<option value="">Select Time</option>');
            
            if (dateInfo && dateInfo.timeSlots) {
                dateInfo.timeSlots.forEach(function(slot) {
                    if (slot.available) {
                        // Format slot ID for display - handle SMRT data structure
                        var slotLabel = slot.id;
                        if (slot.id && slot.id.startsWith('anytime_')) {
                            slotLabel = 'Anytime';
                        } else if (slot.startTime && slot.endTime) {
                            // Handle different data structures
                            slotLabel = slot.startTime + ' - ' + slot.endTime;
                        } else if (slot.id && slot.id.includes('_')) {
                            // Try to extract time from ID
                            var parts = slot.id.split('_');
                            slotLabel = parts[parts.length - 1] || slot.id;
                        }
                        
                        var slotValue = slot.id || (slot.startTime + '-' + slot.endTime);
                        $timeSlot.append('<option value="' + slotValue + '">' + slotLabel + '</option>');
                    }
                });
                
                // Enable schedule button when time slots are available
                if ($scheduleButton.length) {
                    $scheduleButton.prop('disabled', !$timeSlot.find('option').length > 1);
                }
            }
        },
        
        // Handle pickup date selection (new method)
        handlePickupDateSelection: function() {
            var $select = $(this);
            var selectedDateId = $select.val();
            var $timeSlotField = $('#dcf_time_slot_field');
            var $timeSlotSelect = $('#dcf_time_slot');
            var $scheduleButton = $('#dcf_schedule_pickup');
            
            if (!selectedDateId) {
                $timeSlotField.hide();
                $scheduleButton.prop('disabled', true);
                return;
            }
            
            // Find the selected date info from our stored data
            var selectedDateInfo = null;
            if (window.dcfPickupDates) {
                selectedDateInfo = window.dcfPickupDates.find(function(date) {
                    return date.id === selectedDateId;
                });
            }
            
            
            if (selectedDateInfo && selectedDateInfo.timeSlots && selectedDateInfo.timeSlots.length > 0) {
                // Show time slot field - try multiple methods to ensure visibility
                $timeSlotField.show();
                $timeSlotField.css('display', 'block');
                $timeSlotField.removeClass('hidden');
                $timeSlotField.addClass('show');
                
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    // Clear and populate time slots
                    $timeSlotSelect.empty().append('<option value="">Choose a time...</option>');
                    
                    selectedDateInfo.timeSlots.forEach(function(slot) {
                        // Format the slot label
                        var label = slot.id;
                        
                        // Check if it's an "anytime" slot
                        if (slot.id && slot.id.toLowerCase().includes('anytime')) {
                            label = 'Anytime';
                        } else if (slot.label) {
                            label = slot.label;
                        } else if (slot.id && slot.id.includes('T')) {
                            // If ID contains a timestamp, try to extract time
                            try {
                                var parts = slot.id.split('T');
                                if (parts.length > 1) {
                                    var timePart = parts[1].split(':');
                                    if (timePart.length >= 2) {
                                        var hours = parseInt(timePart[0]);
                                        var minutes = parseInt(timePart[1]);
                                        var ampm = hours >= 12 ? 'PM' : 'AM';
                                        hours = hours % 12;
                                        hours = hours ? hours : 12;
                                        label = hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ' ' + ampm;
                                    }
                                }
                            } catch (e) {
                                console.log('Error parsing time slot:', e);
                            }
                        }
                        
                        var $option = $('<option></option>').attr('value', slot.id).text(label);
                        $timeSlotSelect.append($option);
                    });
                    
                    // Force the select to update
                    $timeSlotSelect.trigger('change');
                }, 100);
                
                // Reset button state
                $scheduleButton.prop('disabled', true);
            } else {
                console.log('No time slots found for selected date');
                $timeSlotField.hide();
                $scheduleButton.prop('disabled', true);
            }
        },
        
        // Handle time slot selection
        handleTimeSlotSelection: function() {
            var dateSelected = $('#dcf_pickup_date').val();
            var timeSelected = $(this).val();
            var $scheduleButton = $('#dcf_schedule_pickup');
            
            // Enable button only if both date and time are selected
            $scheduleButton.prop('disabled', !(dateSelected && timeSelected));
        },
        
        // Handle schedule pickup
        handleSchedulePickup: function($container) {
            console.log('handleSchedulePickup called');
            
            // Ensure we have a valid container
            if (!$container || !$container.length) {
                $container = $('.dcf-signup-form-container');
            }
            
            var $dateSelect = $('#dcf_pickup_date');
            var selectedDate = $dateSelect.val(); // This is the date value (YYYY-MM-DD)
            var timeSlotId = $('#dcf_time_slot').val();
            
            console.log('Selected date:', selectedDate);
            console.log('Selected time slot:', timeSlotId);
            
            if (!selectedDate || !timeSlotId) {
                alert('Please select both a date and time slot.');
                return;
            }
            
            // Find the date info from our stored data using the date value
            var selectedDateInfo = null;
            if (window.dcfPickupDates) {
                selectedDateInfo = window.dcfPickupDates.find(function(date) {
                    return date.date === selectedDate;
                });
            }
            
            console.log('Found date info:', selectedDateInfo);
            
            if (!selectedDateInfo) {
                alert('Invalid date selection. Please refresh and try again.');
                return;
            }
            
            // Extract just the date portion (YYYY-MM-DD) from the ISO date string
            var formattedDate = selectedDate;
            if (selectedDate.includes('T')) {
                // If it's an ISO date string, extract just the date part
                formattedDate = selectedDate.split('T')[0];
            }
            
            console.log('Formatted date for appointment:', formattedDate);
            
            // Prepare appointment data
            var appointmentData = {
                date_id: formattedDate,  // Use the formatted date (YYYY-MM-DD)
                time_slot_id: timeSlotId,
                address_id: DCF.currentSubmission.address_id,
                customer_id: DCF.currentSubmission.customer_id
            };
            
            console.log('Appointment data:', appointmentData);
            console.log('Current submission:', DCF.currentSubmission);
            
            // Check if we have required data
            if (!appointmentData.address_id || !appointmentData.customer_id) {
                alert('Missing required data. Please refresh and try again.');
                $('#dcf_schedule_pickup').prop('disabled', false).text('Schedule Pickup');
                return;
            }
            
            // Show loading
            $('#dcf_schedule_pickup').prop('disabled', true).text('Scheduling...');
            
            // Get nonce - try form-specific first, then fallback to public nonce
            var formId = $container.data('form-id') || 0;
            var $form = $container.find('form.dcf-form');
            var nonce = $form.find('[name="dcf_nonce"]').val() || dcf_ajax.nonce;
            
            // Call schedule pickup
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_schedule_pickup',
                    form_id: formId,
                    appointment_data: appointmentData,
                    nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        DCF.showPickupConfirmation($container, appointmentData);
                    } else {
                        alert(response.data.message || 'Error scheduling pickup. Please try again.');
                        $('#dcf_schedule_pickup').prop('disabled', false).text('Schedule Pickup');
                    }
                },
                error: function() {
                    alert('Error scheduling pickup. Please try again.');
                    $('#dcf_schedule_pickup').prop('disabled', false).text('Schedule Pickup');
                }
            });
        },
        
        // Show pickup confirmation
        showPickupConfirmation: function($container, appointmentData) {
            console.log('showPickupConfirmation called');
            
            // Store appointment data
            DCF.currentSubmission.appointment = appointmentData;
            
            // Show brief success message then show completion
            DCF.showMessage($container, 'Pickup scheduled successfully!', 'success');
            
            // Show final completion message after a brief delay
            setTimeout(function() {
                console.log('Showing final completion message...');
                DCF.showFinalCompletionMessage($container);
            }, 2000);
        },
        
        
        // Show final completion message
        showFinalCompletionMessage: function($container) {
            // Trigger conversion event if in popup
            var $popup = $container.closest('.dcf-popup');
            if ($popup.length > 0) {
                var popupId = $popup.data('popup-id');
                $(document).trigger('dcf:popup:conversion', {
                    popup_id: popupId,
                    form_id: $container.find('form').data('form-id') || 0,
                    conversion_value: 0
                });
            }
            
            var html = '<div class="dcf-completion-message">';
            html += '<div class="dcf-message dcf-message-success">';
            html += '<h3>🎉 Account Setup Complete!</h3>';
            html += '<p>Your account has been created successfully with:</p>';
            html += '<ul style="text-align: left; margin: 20px 0;">';
            html += '<li>✓ Personal information saved</li>';
            html += '<li>✓ Pickup & delivery service selected</li>';
            html += '<li>✓ Address verified</li>';
            html += '<li>✓ Pickup scheduled</li>';
            html += '</ul>';
            html += '<p>You\'re all set to use our services!</p>';
            html += '</div>';
            
            if (dcf_ajax.login_url) {
                html += '<p style="text-align: center; margin-top: 20px;">Redirecting to your account in 3 seconds...</p>';
                html += '<p style="text-align: center;"><a href="' + dcf_ajax.login_url + '" class="dcf-submit-button" style="display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px;">Go to Your Account Now</a></p>';
            }
            
            html += '</div>';
            
            // Find the correct content area to replace
            var $contentArea = $container.find('.dcf-step-content:visible');
            if (!$contentArea.length) {
                $contentArea = $container.find('.dcf-signup-form-content');
            }
            if (!$contentArea.length) {
                $contentArea = $container.find('.dcf-form-content');
            }
            if (!$contentArea.length) {
                // Try to find the pickup scheduler parent
                var $pickupScheduler = $container.find('.dcf-pickup-scheduler');
                if ($pickupScheduler.length) {
                    $contentArea = $pickupScheduler.parent();
                }
            }
            
            if ($contentArea.length) {
                $contentArea.html(html);
                
                // Hide any navigation buttons
                $container.find('.dcf-step-navigation').hide();
                $container.find('.dcf-form-navigation').hide();
                $container.find('.dcf-form-submit').hide();
            } else {
                // Fallback: replace entire container content
                $container.html(html);
            }
            
            // Update progress to show all steps completed
            $container.find('.dcf-progress-step').removeClass('active').addClass('completed');
            $container.find('.dcf-step').removeClass('active').addClass('completed');
            
            // Redirect after delay
            if (dcf_ajax.login_url) {
                setTimeout(function() {
                    window.location.href = dcf_ajax.login_url;
                }, 3000);
            }
        },
        
        
        // Format phone number
        formatPhoneNumber: function(e) {
            var $input = $(this);
            var value = $input.val();
            
            // Remove all non-digit characters
            var cleaned = value.replace(/\D/g, '');
            
            // Limit to 10 digits
            cleaned = cleaned.substring(0, 10);
            
            // Format the number
            var formatted = '';
            if (cleaned.length > 0) {
                if (cleaned.length <= 3) {
                    formatted = '(' + cleaned;
                } else if (cleaned.length <= 6) {
                    formatted = '(' + cleaned.substring(0, 3) + ') ' + cleaned.substring(3);
                } else {
                    formatted = '(' + cleaned.substring(0, 3) + ') ' + cleaned.substring(3, 6) + '-' + cleaned.substring(6);
                }
            }
            
            // Store the raw number in a data attribute
            $input.data('raw-phone', cleaned);
            
            // Update the input value with formatted number
            $input.val(formatted);
            
            // Maintain cursor position
            var cursorPosition = $input[0].selectionStart;
            var oldLength = value.length;
            var newLength = formatted.length;
            var diff = newLength - oldLength;
            
            // Adjust cursor position based on formatting changes
            if (diff > 0 && cursorPosition >= 1 && cursorPosition <= 4) {
                cursorPosition += 1; // Account for opening parenthesis
            } else if (diff > 0 && cursorPosition >= 5 && cursorPosition <= 8) {
                cursorPosition += 2; // Account for ") "
            } else if (diff > 0 && cursorPosition >= 9) {
                cursorPosition += 3; // Account for "(" + ") " + "-"
            }
            
            setTimeout(function() {
                $input[0].setSelectionRange(cursorPosition, cursorPosition);
            }, 0);
        },
        
        // Handle phone paste
        handlePhonePaste: function(e) {
            var $input = $(this);
            e.preventDefault();
            
            // Get pasted data
            var pastedData = '';
            if (e.originalEvent.clipboardData && e.originalEvent.clipboardData.getData) {
                pastedData = e.originalEvent.clipboardData.getData('text/plain');
            } else if (window.clipboardData && window.clipboardData.getData) {
                pastedData = window.clipboardData.getData('Text');
            }
            
            // Clean and insert the pasted data
            var cleaned = pastedData.replace(/\D/g, '').substring(0, 10);
            $input.val(cleaned);
            
            // Trigger formatting
            $input.trigger('input');
        },
        
        // Validate individual field
        validateField: function() {
            var $field = $(this);
            var $container = $field.closest('.dcf-field');
            var value = $field.val().trim();
            var isRequired = $field.prop('required');
            var fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            
            // Remove existing error state
            $container.removeClass('dcf-field-error');
            $container.find('.dcf-field-error-message').remove();
            
            // Check required fields
            if (isRequired && !value) {
                DCF.showFieldError($container, dcf_ajax.messages.required_field);
                return false;
            }
            
            // Validate specific field types
            if (value) {
                switch (fieldType) {
                    case 'email':
                        if (!DCF.isValidEmail(value)) {
                            DCF.showFieldError($container, dcf_ajax.messages.invalid_email);
                            return false;
                        }
                        break;
                    case 'tel':
                        if (!DCF.isValidPhone(value)) {
                            DCF.showFieldError($container, dcf_ajax.messages.invalid_phone);
                            return false;
                        }
                        break;
                }
            }
            
            return true;
        },
        
        // Validate entire form
        validateForm: function($form) {
            var isValid = true;
            
            $form.find('.dcf-input, .dcf-textarea, .dcf-select').each(function() {
                if (!DCF.validateField.call(this)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        // Show field error
        showFieldError: function($container, message) {
            $container.addClass('dcf-field-error');
            $container.find('.dcf-field-input').append(
                '<div class="dcf-field-error-message">' + message + '</div>'
            );
        },
        
        // Show signup step
        showSignupStep: function(step) {
            var $container = $('.dcf-signup-form-container');
            
            // Update progress indicators
            $container.find('.dcf-progress-step').each(function() {
                var $step = $(this);
                var stepNumber = parseInt($step.data('step'));
                
                $step.removeClass('active completed');
                
                if (stepNumber === step) {
                    $step.addClass('active');
                } else if (stepNumber < step) {
                    $step.addClass('completed');
                }
            });
            
            // Show current step content
            $container.find('.dcf-signup-step').removeClass('active');
            $container.find('.dcf-signup-step[data-step="' + step + '"]').addClass('active');
            
            // Update current submission step
            DCF.currentSubmission.step = step;
        },
        
        // Move to next step
        moveToNextStep: function($container, step, stepContent) {
            if (stepContent) {
                // Replace step content
                $container.find('.dcf-signup-form-content').html(stepContent);
                
                // Update form data with submission ID
                if (DCF.currentSubmission.id) {
                    $container.find('input[name="submission_id"]').val(DCF.currentSubmission.id);
                }
                if (DCF.currentSubmission.customer_id) {
                    $container.find('input[name="customer_id"]').val(DCF.currentSubmission.customer_id);
                }
                
                // Log what we have in currentSubmission for debugging
                console.log('DCF: Moving to step', step, 'with submission data:', DCF.currentSubmission);
            }
            
            // Show the step
            DCF.showSignupStep(step);
            
            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $container.offset().top - 50
            }, 500);
        },
        
        // Show completion message
        showCompletionMessage: function($container, message, redirectUrl) {
            var html = '<div class="dcf-completion-message">' +
                '<div class="dcf-message dcf-message-success">' +
                '<h3>🎉 ' + message + '</h3>' +
                '</div>';
            
            if (redirectUrl) {
                html += '<p><a href="' + redirectUrl + '" class="dcf-submit-button">Continue to Login</a></p>';
            }
            
            html += '</div>';
            
            $container.find('.dcf-signup-form-content').html(html);
            
            // Update progress to show completion
            $container.find('.dcf-progress-step').addClass('completed');
            
            // Redirect after delay if URL provided
            if (redirectUrl) {
                setTimeout(function() {
                    window.location.href = redirectUrl;
                }, 3000);
            }
        },
        
        // Show service completion message (retail store / not sure)
        showServiceCompletionMessage: function($container, data) {
            var serviceTypeText = '';
            if (data.service_type === 'retail_store') {
                serviceTypeText = 'Retail Store Service';
            } else if (data.service_type === 'not_sure') {
                serviceTypeText = 'We\'ll Help You Decide';
            }
            
            var html = '<div class="dcf-service-completion-message">';
            html += '<div class="dcf-message dcf-message-success">';
            html += '<h3>🎉 ' + data.message + '</h3>';
            html += '</div>';
            
            if (serviceTypeText) {
                html += '<div class="dcf-service-type-info" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">';
                html += '<h4>Service Selection: ' + serviceTypeText + '</h4>';
                html += '</div>';
            }
            
            // Add POS-specific login instructions
            if (data.login_instructions) {
                html += '<div class="dcf-login-instructions" style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #2271b1;">';
                html += '<h4>Login Instructions:</h4>';
                html += '<p>' + data.login_instructions + '</p>';
                html += '</div>';
            }
            
            if (data.redirect_url) {
                html += '<p style="text-align: center; margin-top: 30px;">Redirecting to login page in 3 seconds...</p>';
                html += '<p style="text-align: center;">';
                html += '<a href="' + data.redirect_url + '" class="dcf-submit-button" style="display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px;">';
                html += 'Go to Login Now</a>';
                html += '</p>';
            }
            
            html += '</div>';
            
            $container.find('.dcf-signup-form-content').html(html);
            
            // Update progress to show completion
            $container.find('.dcf-progress-step').removeClass('active').addClass('completed');
            
            // Redirect after 3 seconds if login URL is available
            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 3000);
            }
        },
        
        // Show customer exists message
        showCustomerExistsMessage: function($container, message, redirectUrl) {
            var html = '<div class="dcf-customer-exists-message">' +
                '<div class="dcf-message dcf-message-info">' +
                '<h3>ℹ️ ' + message + '</h3>' +
                '</div>';
            
            if (redirectUrl) {
                html += '<p><a href="' + redirectUrl + '" class="dcf-submit-button">Go to Login</a></p>';
            }
            
            html += '</div>';
            
            $container.find('.dcf-signup-form-content').html(html);
        },
        
        // Show message
        showMessage: function($container, message, type) {
            type = type || 'info';
            
            // Generate a unique message ID to prevent duplicates
            var messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            console.log('[DCF Debug] showMessage called:', { 
                message: message, 
                type: type, 
                container: $container,
                messageId: messageId,
                containerClasses: $container.attr('class')
            });
            
            // Remove ALL existing messages, including in parent containers for popups
            var $popup = $container.closest('.dcf-popup');
            if ($popup.length > 0) {
                // If in a popup, remove all messages in the entire popup
                $popup.find('.dcf-message').remove();
                console.log('[DCF Debug] Removed messages from popup');
            }
            
            // Also remove from the container itself
            $container.find('.dcf-message').remove();
            
            // For extra safety, remove any messages with the same text
            $('.dcf-message').each(function() {
                if ($(this).text() === message) {
                    $(this).remove();
                    console.log('[DCF Debug] Removed duplicate message with same text');
                }
            });
            
            // Add new message with unique ID
            var $message = $('<div class="dcf-message dcf-message-' + type + '" data-message-id="' + messageId + '">' + message + '</div>');
            $container.prepend($message);
            
            // Auto-hide success and info messages
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $message.remove();
                    });
                }, 5000);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 50
            }, 300);
        },
        
        // Set loading state
        setLoadingState: function($container, loading) {
            if (loading) {
                $container.addClass('dcf-loading');
                $container.find('.dcf-submit-button').prop('disabled', true);
            } else {
                $container.removeClass('dcf-loading');
                $container.find('.dcf-submit-button').prop('disabled', false);
            }
        },
        
        // Email validation
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },
        
        // Phone validation
        isValidPhone: function(phone) {
            var cleaned = phone.replace(/[^0-9]/g, '');
            return cleaned.length >= 10;
        },
        
        // Create customer account
        createCustomerAccount: function($form, callback) {
            var formData = DCF.currentSubmission.data;
            var formId = $form.closest('.dcf-form-container').data('form-id') || 0;
            
            // Get nonce - try form-specific first, then fallback to public nonce
            var nonce = $form.find('[name="dcf_nonce"]').val() || dcf_ajax.nonce;
            
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_create_customer_account',
                    form_id: formId,
                    customer_data: formData,
                    nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store customer ID if returned
                        if (response.data && response.data.customer_id) {
                            DCF.currentSubmission.customer_id = response.data.customer_id;
                            DCF.currentSubmission.data.pos_customer_id = response.data.customer_id;
                        }
                        if (response.data && response.data.submission_id) {
                            DCF.currentSubmission.id = response.data.submission_id;
                        }
                    }
                    if (callback) callback(response.success);
                },
                error: function() {
                    if (callback) callback(false);
                }
            });
        },
        
        // Show account created message
        showAccountCreatedMessage: function($container, serviceType) {
            // Trigger conversion event if in popup
            var $popup = $container.closest('.dcf-popup');
            if ($popup.length > 0) {
                var popupId = $popup.data('popup-id');
                $(document).trigger('dcf:popup:conversion', {
                    popup_id: popupId,
                    form_id: $container.find('form').data('form-id') || 0,
                    conversion_value: 0
                });
            }
            
            var message = '<div class="dcf-completion-message">';
            message += '<div class="dcf-message dcf-message-success">';
            message += '<h3>🎉 Account Created Successfully!</h3>';
            
            if (serviceType === 'retail_store') {
                message += '<p>Your account has been created. You can now visit any of our retail locations.</p>';
            } else {
                message += '<p>Your account has been created. We\'ll contact you soon with more information.</p>';
            }
            
            // Add POS-specific login instructions
            var posSystem = dcf_ajax.pos_system || '';
            if (posSystem) {
                message += '<div class="dcf-login-instructions" style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
                message += '<h4>Login Instructions:</h4>';
                
                switch(posSystem) {
                    case 'smrt':
                        message += '<p>You can now log in to the SMRT customer portal using your email and the password sent to your email.</p>';
                        break;
                    case 'spot':
                        message += '<p>You can now log in to your SPOT account using your email address.</p>';
                        break;
                    case 'cleancloud':
                        message += '<p>You can now log in to CleanCloud using your phone number or email.</p>';
                        break;
                    default:
                        message += '<p>You can now log in using your email address.</p>';
                }
                
                message += '</div>';
            }
            
            message += '</div>';
            
            if (dcf_ajax.login_url) {
                message += '<p style="text-align: center; margin-top: 20px;">Redirecting to login page in 3 seconds...</p>';
                message += '<p style="text-align: center;"><a href="' + dcf_ajax.login_url + '" class="dcf-submit-button" style="display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px;">Go to Login Now</a></p>';
            }
            
            message += '</div>';
            
            // Replace form content with success message
            var $contentArea = $container.find('.dcf-multi-step-form-content');
            if (!$contentArea.length) {
                $contentArea = $container.find('.dcf-signup-form-content');
            }
            if (!$contentArea.length) {
                $contentArea = $container.find('.dcf-form-content');
            }
            if ($contentArea.length) {
                $contentArea.html(message);
            } else {
                // Fallback: replace entire container content
                $container.html(message);
            }
            
            // Update progress to show completion
            $container.find('.dcf-progress-step').removeClass('active').addClass('completed');
            
            // Redirect after 3 seconds if login URL is available
            if (dcf_ajax.login_url) {
                setTimeout(function() {
                    window.location.href = dcf_ajax.login_url;
                }, 3000);
            }
        },
        
        // Update customer address
        updateCustomerAddress: function($form, formData, callback) {
            var formId = $form.closest('.dcf-form-container').data('form-id') || 0;
            
            // Get nonce - try form-specific first, then fallback to public nonce
            var nonce = $form.find('[name="dcf_nonce"]').val() || dcf_ajax.nonce;
            
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_update_customer_address',
                    form_id: formId,
                    customer_data: formData,
                    nonce: nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Store address ID if returned
                        if (response.data.address_id) {
                            DCF.currentSubmission.address_id = response.data.address_id;
                            DCF.currentSubmission.data.address_id = response.data.address_id;
                        }
                        
                        if (response.data.pickup_dates) {
                            callback(true, response.data.pickup_dates);
                        } else {
                            callback(true, null);
                        }
                    } else {
                        callback(false, null);
                    }
                },
                error: function() {
                    callback(false, null);
                }
            });
        },
        
        // Show pickup date selection
        showPickupDateSelection: function($container, pickupDates) {
            console.log('showPickupDateSelection called with dates:', pickupDates);
            console.log('Container:', $container);
            
            // Ensure we have a valid container
            if (!$container || !$container.length) {
                $container = $('.dcf-multi-step-form');
            }
            
            // For multi-step forms, we need to add a new step dynamically
            var html = '<h3 class="dcf-step-title">Schedule Your Pickup</h3>';
            html += '<div class="dcf-pickup-scheduler">';
            
            if (pickupDates && pickupDates.length > 0) {
                html += '<div class="dcf-field">';
                html += '<label class="dcf-field-label">Select Pickup Date <span class="dcf-required">*</span></label>';
                html += '<select id="dcf_pickup_date" class="dcf-select" required>';
                html += '<option value="">Choose a date...</option>';
                
                pickupDates.forEach(function(dateInfo) {
                    var dateObj = new Date(dateInfo.date);
                    var dateLabel = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    html += '<option value="' + dateInfo.id + '">' + dateLabel + '</option>';
                });
                
                // Store pickup dates globally for access in event handler
                window.dcfPickupDates = pickupDates;
                
                html += '</select>';
                html += '</div>';
                
                html += '<div class="dcf-field" id="dcf_time_slot_field" style="display: none;">';
                html += '<label class="dcf-field-label">Select Time Slot <span class="dcf-required">*</span></label>';
                html += '<select id="dcf_time_slot" class="dcf-select" required>';
                html += '<option value="">Choose a time...</option>';
                html += '</select>';
                html += '</div>';
                
                // Don't add navigation buttons - we'll use the existing ones
                html += '<div class="dcf-form-actions" style="margin-top: 20px;">';
                html += '<button type="button" class="dcf-submit-button" id="dcf_schedule_pickup" disabled>Schedule Pickup</button>';
                html += '</div>';
            } else {
                html += '<p>No pickup dates are currently available. Please contact us for assistance.</p>';
                html += '<div class="dcf-form-actions" style="margin-top: 20px;">';
                html += '<button type="button" class="dcf-submit-button" id="dcf_continue_to_payment">Continue to Payment</button>';
                html += '</div>';
            }
            
            html += '</div>';
            
            // For form builder multi-step forms, we need to handle this differently
            var $form = $container.find('form.dcf-form');
            var $currentStep = $container.find('.dcf-step-content:visible');
            var currentStepNum = parseInt($currentStep.data('step'));
            
            if ($currentStep.length) {
                // Replace the current step content
                console.log('Replacing current step content');
                console.log('Current step data-step:', $currentStep.attr('data-step'));
                $currentStep.html(html);
                
                // Make sure the step remains visible
                $currentStep.show();
                
                // Store reference to this step for later use
                $container.data('pickup-step', $currentStep);
                
                // Update navigation buttons
                var $prevButton = $container.find('.dcf-prev-step');
                var $nextButton = $container.find('.dcf-next-step');
                var $submitButton = $container.find('.dcf-submit-button');
                
                // Hide next button, show custom schedule button
                $nextButton.hide();
                $prevButton.show();
                
                // Update step indicators if they exist
                var $steps = $container.find('.dcf-step');
                if ($steps.length) {
                    $steps.removeClass('active');
                    // Mark current step as active (pickup scheduling)
                    if ($steps.eq(currentStepNum + 1).length) {
                        $steps.eq(currentStepNum + 1).addClass('active');
                    }
                }
            } else {
                console.error('Could not find current step to replace');
                
                // Fallback: Show pickup dates in a modal
                var modalHtml = '<div class="dcf-modal dcf-pickup-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; align-items: center; justify-content: center;">';
                modalHtml += '<div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">';
                modalHtml += '<h3 class="dcf-step-title">Schedule Your Pickup</h3>';
                modalHtml += '<div class="dcf-pickup-scheduler">' + html + '</div>';
                modalHtml += '</div></div>';
                
                $('body').append(modalHtml);
                
                // Close modal on background click
                $('.dcf-pickup-modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).remove();
                    }
                });
            }
            
            // Bind events using event delegation to ensure they work after content replacement
            setTimeout(function() {
                console.log('Binding events - pickup date select found:', $('#dcf_pickup_date').length);
                console.log('Binding events - time slot select found:', $('#dcf_time_slot').length);
                console.log('Initial time slot field state:', $('#dcf_time_slot_field').css('display'));
                
                // Use event delegation on document to ensure events work
                $(document).off('change', '#dcf_pickup_date').on('change', '#dcf_pickup_date', DCF.handlePickupDateSelection);
                $(document).off('change', '#dcf_time_slot').on('change', '#dcf_time_slot', DCF.handleTimeSlotSelection);
                $(document).off('click', '#dcf_schedule_pickup').on('click', '#dcf_schedule_pickup', function() {
                    DCF.handleSchedulePickup($container);
                });
                $(document).off('click', '#dcf_continue_to_payment').on('click', '#dcf_continue_to_payment', function() {
                    DCF.showPaymentStep($container);
                });
                
                // Debug: Check if the time slot field HTML is correct
                var $timeSlotField = $('#dcf_time_slot_field');
                if ($timeSlotField.length) {
                    console.log('Time slot field HTML:', $timeSlotField[0].outerHTML);
                    console.log('Time slot field parent:', $timeSlotField.parent().attr('class'));
                }
            }, 200);
        },
        
        // Confirm pickup date
        confirmPickupDate: function($form, pickupDate, callback) {
            var formId = $form.closest('.dcf-form-container').data('form-id');
            
            $.ajax({
                url: dcf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_schedule_pickup',
                    form_id: formId,
                    pickup_date: pickupDate,
                    customer_data: DCF.currentSubmission.data,
                    nonce: $form.find('[name="dcf_nonce"]').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (callback) callback(response.success);
                },
                error: function() {
                    if (callback) callback(false);
                }
            });
        },
        
        // Initialize UTM tracking
        initUTMTracking: function() {
            // Get URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            
            // List of UTM parameters to track
            var utmParams = [
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_keyword',
                'utm_matchtype',
                'campaign_id',
                'ad_group_id',
                'ad_id',
                'gclid'
            ];
            
            // Update hidden fields with UTM parameters from URL
            utmParams.forEach(function(param) {
                var value = urlParams.get(param);
                if (value) {
                    // Update any existing hidden fields
                    $('input[name="' + param + '"]').val(value);
                    $('#dcf_' + param).val(value);
                    // Also update form builder fields with dcf_field[name] format
                    $('input[name="dcf_field[' + param + ']"]').val(value);
                    $('#dcf_field_' + param).val(value);
                }
            });
            
            // Store UTM parameters in session storage for persistence across steps
            utmParams.forEach(function(param) {
                var value = urlParams.get(param);
                if (value) {
                    sessionStorage.setItem('dcf_' + param, value);
                } else {
                    // Check if we have it in session storage
                    var storedValue = sessionStorage.getItem('dcf_' + param);
                    if (storedValue) {
                        // Update hidden fields with stored value
                        $('input[name="' + param + '"]').val(storedValue);
                        $('#dcf_' + param).val(storedValue);
                        // Also update form builder fields with dcf_field[name] format
                        $('input[name="dcf_field[' + param + ']"]').val(storedValue);
                        $('#dcf_field_' + param).val(storedValue);
                    }
                }
            });
        }
        
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        DCF.init();
    });
    
    // Make DCF available globally for debugging
    window.DCF = DCF;
    
})(jQuery); 