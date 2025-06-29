/**
 * Admin JavaScript for Dry Cleaning Forms
 *
 * @package DryCleaningForms
 */

(function($) {
    'use strict';

    // Global admin object
    window.DCF_Admin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Dashboard events
            this.bindDashboardEvents();
            
            // Form builder events
            this.bindFormBuilderEvents();
            
            // Settings events
            this.bindSettingsEvents();
            
            // General events
            this.bindGeneralEvents();
        },

        bindDashboardEvents: function() {
            // Test integration connections
            $(document).on('click', '.dcf-test-integration', function(e) {
                e.preventDefault();
                DCF_Admin.testIntegration($(this));
            });

            // Refresh dashboard stats
            $(document).on('click', '.dcf-refresh-stats', function(e) {
                e.preventDefault();
                DCF_Admin.refreshDashboardStats();
            });
        },

        bindFormBuilderEvents: function() {
            // Form builder drag and drop - initialize when jQuery UI is available
            this.initFormBuilderWhenReady();
            
            // Collapsible sections
            $(document).on('click', '.dcf-sidebar-section h3', function(e) {
                e.preventDefault();
                var $section = $(this).parent('.dcf-sidebar-section');
                $section.toggleClass('collapsed');
                
                // Save state to localStorage
                var sectionId = $(this).text().trim();
                var isCollapsed = $section.hasClass('collapsed');
                localStorage.setItem('dcf_section_' + sectionId, isCollapsed ? 'collapsed' : 'expanded');
            });
            
            // Style controls
            this.bindStyleControls();
            
            // Restore collapsed state on page load
            $('.dcf-sidebar-section h3').each(function() {
                var sectionId = $(this).text().trim();
                var state = localStorage.getItem('dcf_section_' + sectionId);
                if (state === 'collapsed') {
                    $(this).parent('.dcf-sidebar-section').addClass('collapsed');
                }
            });
            
            // POS integration toggle
            $(document).on('change', '#pos-sync-enabled', function() {
                $('.dcf-pos-options').toggle($(this).is(':checked'));
                $('.dcf-test-features').toggle($(this).is(':checked'));
            });
            
            // UTM parameters toggle
            $(document).on('change', '#include-utm-parameters', function() {
                DCF_Admin.toggleUTMFields($(this).is(':checked'));
            });
            
            // Webhook enabled toggle
            $(document).on('change', '#webhook-enabled', function() {
                $('#webhook-url-group').toggle($(this).is(':checked'));
            });
            
            // Test POS integration features
            $(document).on('click', '.dcf-test-feature', function(e) {
                e.preventDefault();
                DCF_Admin.testPOSFeature($(this));
            });

            // Form field editing
            $(document).on('click', '.dcf-edit-field', function(e) {
                e.preventDefault();
                DCF_Admin.editFormField($(this));
            });

            // Form field deletion
            $(document).on('click', '.dcf-delete-field', function(e) {
                e.preventDefault();
                var $button = $(this);
                DCF_Admin.showSweetAlert({
                    title: dcf_admin.messages.confirm_delete_title || 'Delete Field?',
                    text: dcf_admin.messages.confirm_delete || 'Are you sure you want to delete this field?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        DCF_Admin.deleteFormField($button);
                    }
                });
            });

            // Form field duplication
            $(document).on('click', '.dcf-duplicate-field', function(e) {
                e.preventDefault();
                DCF_Admin.duplicateFormField($(this));
            });

            // Save form
            $(document).on('click', '#dcf-save-form', function(e) {
                e.preventDefault();
                console.log('Save form button clicked!');
                DCF_Admin.saveForm();
            });

            // Test AJAX
            $(document).on('click', '#dcf-test-ajax', function(e) {
                e.preventDefault();
                console.log('Test AJAX button clicked!');
                $.ajax({
                    url: dcf_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dcf_test_ajax',
                        nonce: dcf_admin.nonce
                    },
                    success: function(response) {
                        console.log('Test AJAX success:', response);
                        alert('Test AJAX worked! Response: ' + JSON.stringify(response));
                    },
                    error: function(xhr, status, error) {
                        console.log('Test AJAX error:', {xhr: xhr, status: status, error: error});
                        alert('Test AJAX failed: ' + error);
                    }
                });
            });

            // Preview form
            $(document).on('click', '#dcf-preview-form', function(e) {
                e.preventDefault();
                DCF_Admin.previewForm();
            });

            // Template handling
            $(document).on('click', '#apply-template', function(e) {
                e.preventDefault();
                DCF_Admin.applyFormTemplate();
            });
            
            // Template modal handling
            $(document).on('click', '.dcf-select-template', function(e) {
                e.preventDefault();
                DCF_Admin.selectTemplate($(this));
            });
            
            // Close template modal when clicking close button
            $(document).on('click', '#dcf-template-modal .dcf-modal-close', function() {
                $('#dcf-template-modal').css('display', 'none');
            });

            // Real-time form title and description updates
            $(document).on('input', '#form-title', function() {
                var title = $(this).val() || 'Untitled Form';
                $('#canvas-form-title').text(title);
            });

            $(document).on('input', '#form-description', function() {
                $('#canvas-form-description').text($(this).val());
            });

            // Modal handling for field editor
            $(document).on('click', '.dcf-modal-close, #dcf-cancel-field', function() {
                $('#dcf-field-modal').hide();
            });

            $(document).on('click', '#dcf-save-field', function() {
                DCF_Admin.saveFieldChanges();
            });
        },

        bindSettingsEvents: function() {
            // Settings form submission
            $('.dcf-settings-form').on('submit', function(e) {
                DCF_Admin.saveSettings($(this));
            });

            // POS system selection
            $('input[name="dcf_settings[pos_system]"]').on('change', function() {
                DCF_Admin.togglePOSSettings($(this).val());
            });

            // Import/Export settings
            $(document).on('click', '.dcf-export-settings', function(e) {
                e.preventDefault();
                DCF_Admin.exportSettings();
            });

            $(document).on('click', '.dcf-import-settings', function(e) {
                e.preventDefault();
                $('#dcf-import-file').click();
            });

            $(document).on('change', '#dcf-import-file', function() {
                DCF_Admin.importSettings(this.files[0]);
            });
        },

        bindGeneralEvents: function() {
            // Modal handling
            $(document).on('click', '.dcf-modal-close', function() {
                $(this).closest('.dcf-modal').hide();
            });

            $(document).on('click', '.dcf-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Tooltips
            if (typeof $.fn.tooltip !== 'undefined') {
                $('[data-tooltip]').tooltip();
            }

            // Confirmation dialogs
            $(document).on('click', '[data-confirm]', function(e) {
                var message = $(this).data('confirm') || dcf_admin.messages.confirm_action;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });

            // Copy to clipboard
            $(document).on('click', '.dcf-copy-shortcode', function(e) {
                e.preventDefault();
                DCF_Admin.copyToClipboard($(this));
            });
        },

        initComponents: function() {
            // Initialize template modal for new forms
            if ($('#dcf-template-modal').length > 0 && !$('#dcf-save-form').data('form-id')) {
                // Show template modal with flex display
                $('#dcf-template-modal').css('display', 'flex');
            }
            
            // Store existing form config if editing
            if ($('#dcf-save-form').data('form-id') && window.dcfFormConfig) {
                this.currentFormConfig = window.dcfFormConfig;
                
                // Check if UTM parameters should be displayed
                if (this.currentFormConfig.include_utm_parameters) {
                    // Trigger the checkbox to be checked and show UTM fields
                    $('#include-utm-parameters').prop('checked', true);
                    DCF_Admin.toggleUTMFields(true);
                }
                
                // Initialize style controls based on existing config
                if (this.currentFormConfig.styles) {
                    var layoutType = this.currentFormConfig.styles.layout_type || 'single-column';
                    if (layoutType === 'two-column' || layoutType === 'single-line') {
                        $('#label-width-group').show();
                    }
                }
            }
            
            // Initialize date pickers
            if (typeof $.fn.datepicker !== 'undefined') {
                $('.dcf-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
            }

            // Initialize select2 if available, otherwise load it
            this.initSelect2();

            // Initialize SweetAlert2 if available, otherwise load it
            this.initSweetAlert2();

            // Initialize color pickers
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.dcf-color-picker').wpColorPicker();
            }

            // Initialize code editors
            if (typeof wp !== 'undefined' && wp.codeEditor) {
                $('.dcf-code-editor').each(function() {
                    wp.codeEditor.initialize(this, {
                        codemirror: {
                            mode: $(this).data('mode') || 'javascript',
                            lineNumbers: true,
                            theme: 'default'
                        }
                    });
                });
            }
        },

        initSelect2: function() {
            if (typeof $.fn.select2 !== 'undefined') {
                this.applySelect2();
            } else {
                // Load Select2 CSS
                var cssLink = document.createElement('link');
                cssLink.rel = 'stylesheet';
                cssLink.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
                document.head.appendChild(cssLink);
                
                // Load Select2 JS
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
                script.onload = function() {
                    DCF_Admin.applySelect2();
                };
                document.head.appendChild(script);
            }
        },

        applySelect2: function() {
            $('.dcf-select2').select2({
                width: '100%',
                theme: 'default'
            });
            
            // Enhanced form type selector
            $('#form-type').select2({
                width: '100%',
                minimumResultsForSearch: Infinity
            });
            
            // Enhanced integration selector
            $('select[name*="pos_system"]').select2({
                width: '100%',
                minimumResultsForSearch: Infinity
            });
            
            // Enhanced filter selectors
            $('#form_filter, #status_filter, #date_range').select2({
                width: '100%'
            });
        },

        initSweetAlert2: function() {
            if (typeof Swal !== 'undefined') {
                this.sweetAlertReady = true;
            } else {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                script.onload = function() {
                    DCF_Admin.sweetAlertReady = true;
                };
                document.head.appendChild(script);
            }
        },

        showSweetAlert: function(options) {
            if (this.sweetAlertReady && typeof Swal !== 'undefined') {
                return Swal.fire(options);
            } else {
                // Fallback to regular alert/confirm
                if (options.showCancelButton) {
                    return Promise.resolve({ isConfirmed: confirm(options.text || options.title) });
                } else {
                    alert(options.text || options.title);
                    return Promise.resolve({ isConfirmed: true });
                }
            }
        },

        initFormBuilderWhenReady: function() {
            var self = this;
            var attempts = 0;
            var maxAttempts = 50; // 5 seconds max wait
            
            var checkJQueryUI = function() {
                attempts++;
                
                if (typeof $.fn.draggable !== 'undefined' && typeof $.fn.droppable !== 'undefined' && typeof $.fn.sortable !== 'undefined') {
                    self.initFormBuilder();
                } else if (attempts < maxAttempts) {
                    // Check again in 100ms
                    setTimeout(checkJQueryUI, 100);
                } else {
                    console.error('jQuery UI failed to load after', maxAttempts, 'attempts');
                }
            };
            checkJQueryUI();
        },

        initFormBuilder: function() {
            // Only initialize if we're on the form editor page
            if ($('.dcf-form-editor').length === 0) {
                return;
            }

            // Make field library items draggable
            $('.dcf-field-item').draggable({
                helper: 'clone',
                revert: 'invalid',
                zIndex: 1000,
                start: function() {
                    $('.dcf-form-fields').addClass('dcf-drop-zone-active');
                },
                stop: function() {
                    $('.dcf-form-fields').removeClass('dcf-drop-zone-active');
                }
            });

            // Make form fields area droppable
            $('.dcf-form-fields').droppable({
                accept: '.dcf-field-item',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    var fieldType = ui.draggable.data('field-type');
                    var fieldSubtype = ui.draggable.data('field-subtype');
                    DCF_Admin.addFormField(fieldType, $(this), fieldSubtype);
                }
            });

            // Make form fields sortable
            $('.dcf-form-fields').sortable({
                items: '.dcf-form-field',
                placeholder: 'dcf-field-placeholder',
                tolerance: 'pointer',
                handle: '.dcf-field-handle'
            });
        },

        testIntegration: function($button) {
            var integration = $button.data('integration');
            var originalText = $button.text();

            $button.prop('disabled', true).text(dcf_admin.messages.testing);

            // Remove existing status indicators
            $button.siblings('.dcf-connection-status').remove();

            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'test_integration',
                    integration: integration,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    var statusClass = response.success ? 'dcf-connection-success' : 'dcf-connection-error';
                    var statusText = response.success ? dcf_admin.messages.connection_success : dcf_admin.messages.connection_error;
                    
                    $button.after('<span class="dcf-connection-status ' + statusClass + '">' + statusText + '</span>');
                    
                    if (!response.success && response.data && response.data.message) {
                        $button.parent().append('<br><small class="dcf-error-message">' + response.data.message + '</small>');
                    }
                },
                error: function() {
                    $button.after('<span class="dcf-connection-status dcf-connection-error">' + dcf_admin.messages.connection_error + '</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        testPOSFeature: function($button) {
            var feature = $button.data('feature');
            var $container = $button.closest('.dcf-test-features');
            var $results = $container.find('.dcf-test-results');
            var $output = $container.find('.dcf-test-output');
            
            $button.prop('disabled', true);
            $results.show();
            $output.html('<div style="color: #666;">Testing ' + feature + '...</div>');
            
            // Get sample test data based on feature
            var testData = this.getTestDataForFeature(feature);
            
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'test_pos_feature',
                    feature: feature,
                    test_data: testData,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    var output = '<div style="margin-bottom: 10px;">';
                    output += '<strong>Feature:</strong> ' + feature + '<br>';
                    output += '<strong>Status:</strong> ';
                    
                    if (response.success) {
                        output += '<span style="color: #0f5132;">✓ Success</span><br>';
                        
                        if (response.data) {
                            output += '<strong>Response:</strong><br>';
                            output += '<pre style="margin: 5px 0; background: white; padding: 8px; border-radius: 3px;">';
                            output += JSON.stringify(response.data, null, 2);
                            output += '</pre>';
                        }
                    } else {
                        output += '<span style="color: #721c24;">✗ Failed</span><br>';
                        if (response.data && response.data.message) {
                            output += '<strong>Error:</strong> ' + response.data.message + '<br>';
                        }
                        if (response.data && response.data.details) {
                            output += '<strong>Details:</strong><br>';
                            output += '<pre style="margin: 5px 0; background: white; padding: 8px; border-radius: 3px;">';
                            output += JSON.stringify(response.data.details, null, 2);
                            output += '</pre>';
                        }
                    }
                    
                    output += '</div>';
                    $output.html(output);
                },
                error: function(xhr, status, error) {
                    $output.html('<div style="color: #721c24;">Error: ' + error + '</div>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        getTestDataForFeature: function(feature) {
            var timestamp = Date.now();
            
            // Get values from input fields
            var testEmail = $('#test-email').val().trim();
            var testPhone = $('#test-phone').val().trim();
            var testCustomerId = $('#test-customer-id').val().trim();
            
            switch(feature) {
                case 'check_customer':
                    var data = {};
                    if (testEmail) {
                        data.email = testEmail;
                    }
                    if (testPhone) {
                        data.phone = testPhone;
                    }
                    // If no custom data provided, use defaults
                    if (!testEmail && !testPhone) {
                        data.email = 'test@example.com';
                        data.phone = '(555) 123-4567';
                    }
                    return data;
                    
                case 'create_customer':
                    var createData = {
                        first_name: 'Test',
                        last_name: 'Customer ' + timestamp
                    };
                    
                    // Use custom email if provided, otherwise generate unique one
                    if (testEmail) {
                        createData.email = testEmail;
                    } else {
                        createData.email = 'test' + timestamp + '@example.com';
                    }
                    
                    // Only add phone if provided
                    if (testPhone) {
                        createData.phone = testPhone;
                    } else if (!testEmail) {
                        // Only add dummy phone if no custom data at all was provided
                        createData.phone = '(555) ' + timestamp.toString().substr(-7, 3) + '-' + timestamp.toString().substr(-4);
                    }
                    
                    return createData;
                    
                case 'update_customer':
                    var updateData = {
                        customer_id: testCustomerId || 'test-customer-id'
                    };
                    
                    // Add email if provided
                    if (testEmail) {
                        updateData.email = testEmail;
                    }
                    
                    // Add phone if provided
                    if (testPhone) {
                        updateData.phone = testPhone;
                    }
                    
                    // If no email or phone provided, add a test email
                    if (!testEmail && !testPhone) {
                        updateData.email = 'updated-email@example.com';
                    }
                    
                    return updateData;
                    
                default:
                    return {};
            }
        },

        refreshDashboardStats: function() {
            var $button = $('.dcf-refresh-stats');
            var originalText = $button.text();

            $button.prop('disabled', true).text(dcf_admin.messages.refreshing);

            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'refresh_dashboard_stats',
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        DCF_Admin.showNotice(response.data.message || dcf_admin.messages.error, 'error');
                    }
                },
                error: function() {
                    DCF_Admin.showNotice(dcf_admin.messages.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        addFormField: function(fieldType, $container, fieldSubtype) {
            var fieldId = this.generateFieldId(fieldType, $container);
            var fieldHtml = this.generateFieldHtml(fieldType, fieldId, fieldSubtype);
            
            $container.append(fieldHtml);
            $container.find('.dcf-empty-form').hide();
            
            // Trigger field added event
            $(document).trigger('dcf:field_added', [fieldType, fieldId]);
        },

        generateFieldId: function(fieldType, $container) {
            // Get base name from field type
            var baseName = fieldType;
            
            // Special cases for more intuitive names
            switch (fieldType) {
                case 'tel':
                    baseName = 'phone';
                    break;
                case 'textarea':
                    baseName = 'message';
                    break;
                case 'text':
                    baseName = 'text_field';
                    break;
            }
            
            // Count existing fields of this type to add a number if needed
            var existingFields = $container.find('.dcf-form-field[data-field-type="' + fieldType + '"]').length;
            
            if (existingFields > 0) {
                return baseName + '_' + (existingFields + 1);
            }
            
            return baseName;
        },

        generateFieldHtml: function(fieldType, fieldId, fieldSubtype) {
            var fieldName = this.getFieldLabel(fieldType, fieldSubtype);
            var placeholder = this.getDefaultPlaceholder(fieldType, fieldSubtype, fieldName);
            
            var html = '<div class="dcf-form-field" data-field-id="' + fieldId + '" data-field-type="' + fieldType + '">';
            html += '<div class="dcf-field-handle"><span class="dashicons dashicons-menu"></span></div>';
            html += '<div class="dcf-field-preview">';
            html += '<label>' + fieldName + '</label>';
            
            switch (fieldType) {
                case 'name':
                    html += '<div class="dcf-name-field">';
                    html += '<div class="dcf-name-row">';
                    html += '<div class="dcf-name-first"><input type="text" name="' + fieldId + '_first" placeholder="First Name" disabled></div>';
                    html += '<div class="dcf-name-last"><input type="text" name="' + fieldId + '_last" placeholder="Last Name" disabled></div>';
                    html += '</div></div>';
                    break;
                case 'terms':
                    html += '<div class="dcf-terms-field">';
                    html += '<label><input type="checkbox" name="' + fieldId + '" disabled> I have read and agree to the Terms and Conditions and Privacy Policy</label>';
                    html += '</div>';
                    break;
                case 'address':
                    html += '<div class="dcf-address-field">';
                    html += '<div class="dcf-address-row">';
                    html += '<div class="dcf-address-line1"><input type="text" name="' + fieldId + '_line1" placeholder="Street Address" disabled></div>';
                    html += '<div class="dcf-address-line2"><input type="text" name="' + fieldId + '_line2" placeholder="Apartment, suite, etc." disabled></div>';
                    html += '</div>';
                    html += '<div class="dcf-address-row">';
                    html += '<div class="dcf-address-city"><input type="text" name="' + fieldId + '_city" placeholder="City" disabled></div>';
                    html += '<div class="dcf-address-state"><input type="text" name="' + fieldId + '_state" placeholder="State" disabled></div>';
                    html += '<div class="dcf-address-zip"><input type="text" name="' + fieldId + '_zip" placeholder="ZIP Code" disabled></div>';
                    html += '</div></div>';
                    break;
                case 'textarea':
                    html += '<textarea name="' + fieldId + '" placeholder="' + placeholder + '" disabled></textarea>';
                    break;
                case 'select':
                    html += '<select name="' + fieldId + '" disabled><option>Option 1</option><option>Option 2</option></select>';
                    break;
                case 'radio':
                    html += '<div><label><input type="radio" name="' + fieldId + '" disabled> Option 1</label></div>';
                    html += '<div><label><input type="radio" name="' + fieldId + '" disabled> Option 2</label></div>';
                    break;
                case 'checkbox':
                    html += '<label><input type="checkbox" disabled> Checkbox option</label>';
                    break;
                case 'hidden':
                    html += '<input type="hidden" name="' + fieldId + '" value="hidden_value"><em>Hidden field (not visible to users)</em>';
                    break;
                case 'image':
                    html += '<div class="dcf-image-preview" style="border: 2px dashed #ccc; padding: 20px; text-align: center;">';
                    html += '<span class="dashicons dashicons-format-image" style="font-size: 48px; color: #999;"></span>';
                    html += '<p style="margin: 10px 0 0 0; color: #666;">Image will be displayed here</p>';
                    html += '</div>';
                    break;
                case 'submit':
                    const buttonText = 'Submit';
                    const buttonSize = 'medium';
                    const alignment = 'center';
                    const bgColor = '#2271b1';
                    const textColor = '#ffffff';
                    const borderColor = '#2271b1';
                    const borderRadius = '4';
                    const minWidth = '';
                    
                    // Size styles
                    const sizeStyles = {
                        'small': 'padding: 8px 16px; font-size: 14px;',
                        'medium': 'padding: 12px 24px; font-size: 16px;',
                        'large': 'padding: 16px 32px; font-size: 18px;'
                    };
                    
                    let buttonStyle = sizeStyles[buttonSize] || sizeStyles['medium'];
                    buttonStyle += `background-color: ${bgColor}; color: ${textColor}; border: 1px solid ${borderColor}; border-radius: ${borderRadius}px; cursor: pointer;`;
                    
                    if (minWidth && minWidth !== '0') {
                        buttonStyle += ` min-width: ${minWidth}px;`;
                    }
                    
                    html += '<div style="text-align: ' + alignment + '; margin-top: 10px;">';
                    html += '<button type="button" style="' + buttonStyle + '" disabled>';
                    html += buttonText;
                    html += '</button>';
                    html += '<br><small style="color: #666; font-style: italic;">Custom submit button preview</small>';
                    html += '</div>';
                    break;
                default:
                    // Handle standard input fields (text, email, phone, number, date)
                    var inputType = fieldType;
                    if (fieldType === 'tel') {
                        inputType = 'tel';
                    }
                    html += '<input type="' + inputType + '" name="' + fieldId + '" placeholder="' + placeholder + '" disabled>';
                    break;
            }
            
            html += '</div>';
            html += '<div class="dcf-field-actions">';
            html += '<button type="button" class="dcf-edit-field" title="Edit Field"><span class="dashicons dashicons-edit"></span></button>';
            html += '<button type="button" class="dcf-duplicate-field" title="Duplicate Field"><span class="dashicons dashicons-admin-page"></span></button>';
            html += '<button type="button" class="dcf-delete-field" title="Delete Field"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        getFieldLabel: function(fieldType, fieldSubtype) {
            // Handle specific subtypes
            if (fieldSubtype) {
                switch (fieldSubtype) {
                    case 'city':
                        return 'City';
                    case 'state':
                        return 'State';
                    case 'country':
                        return 'Country';
                    case 'postal':
                        return 'Postal Code';
                    case 'multiple':
                        return 'Multi Select';
                }
            }
            
            // Default field labels
            switch (fieldType) {
                case 'name':
                    return 'Full Name';
                case 'email':
                    return 'Email';
                case 'tel':
                    return 'Phone';
                case 'address':
                    return 'Address';
                case 'text':
                    return 'Text Field';
                case 'textarea':
                    return 'Text Area';
                case 'select':
                    return 'Select Dropdown';
                case 'radio':
                    return 'Radio Buttons';
                case 'checkbox':
                    return 'Checkboxes';
                case 'date':
                    return 'Date';
                case 'number':
                    return 'Number';
                case 'hidden':
                    return 'Hidden Field';
                case 'submit':
                    return 'Submit Button';
                case 'terms':
                    return 'Terms & Conditions';
                case 'image':
                    return 'Image';
                default:
                    return fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + ' Field';
            }
        },

        getDefaultPlaceholder: function(fieldType, fieldSubtype, fieldLabel) {
            // Special placeholder text for specific field types
            switch (fieldType) {
                case 'email':
                    return 'your@email.com';
                case 'tel':
                case 'phone':
                    return '(555) 555-5555';
                case 'date':
                    return 'mm/dd/yyyy';
                case 'number':
                    return 'Enter number';
                case 'text':
                    if (fieldLabel.toLowerCase().includes('name')) {
                        return 'Enter your name';
                    } else if (fieldLabel.toLowerCase().includes('company')) {
                        return 'Enter company name';
                    }
                    return 'Enter ' + fieldLabel.toLowerCase();
                case 'textarea':
                    return 'Enter your message here...';
                default:
                    // For other fields, use the label as placeholder
                    return 'Enter ' + fieldLabel.toLowerCase();
            }
        },

        editFormField: function($button) {
            var $field = $button.closest('.dcf-form-field');
            var fieldData = this.getFieldData($field);
            
            this.openFieldModal(fieldData, $field);
        },

        deleteFormField: function($button) {
            var $field = $button.closest('.dcf-form-field');
            var $container = $field.closest('.dcf-form-fields');
            
            $field.fadeOut(function() {
                $(this).remove();
                
                if ($container.find('.dcf-form-field').length === 0) {
                    $container.find('.dcf-empty-form').show();
                }
            });
        },

        duplicateFormField: function($button) {
            var $field = $button.closest('.dcf-form-field');
            var $clone = $field.clone();
            var fieldType = $field.attr('data-field-type');
            var $container = $field.closest('.dcf-form-fields');
            var newId = this.generateFieldId(fieldType, $container);
            
            $clone.attr('data-field-id', newId);
            $clone.find('input[type="radio"]').attr('name', newId);
            
            $field.after($clone);
        },

        getFieldData: function($field) {
            var $label = $field.find('.dcf-field-preview label').first();
            var labelText = $label.text().trim();
            
            // Remove existing asterisk to get clean label
            var cleanLabel = labelText.replace(/\s*\*\s*$/, '');
            
            // Get the actual field name from the input element's name attribute
            var $input = $field.find('.dcf-field-preview input, .dcf-field-preview textarea, .dcf-field-preview select').first();
            var fieldName = $field.attr('data-field-id') || $field.data('field-id') || $input.attr('name');
            
            // For complex fields with suffixed names, extract the base name
            if (!$field.attr('data-field-id') && !$field.data('field-id') && $input.attr('name')) {
                var inputName = $input.attr('name');
                // Remove suffixes like _first, _last, _line1, etc.
                fieldName = inputName.replace(/_(first|last|line1|line2|city|state|zip)$/, '');
            }
            
            // Debug logging
            console.log('getFieldData debug:', {
                fieldElement: $field[0],
                inputElement: $input[0],
                inputName: $input.attr('name'),
                dataFieldId: $field.attr('data-field-id'),
                dataFieldIdFromData: $field.data('field-id'),
                finalFieldName: fieldName,
                fieldType: $field.data('field-type')
            });
            
            // Extract custom CSS classes (exclude dcf-form-field and dcf-field-required)
            var allClasses = $field.attr('class') ? $field.attr('class').split(/\s+/) : [];
            var customClasses = allClasses.filter(function(className) {
                return className !== 'dcf-form-field' && className !== 'dcf-field-required' && className.trim() !== '';
            });
            var cssClass = customClasses.join(' ');
            
            var fieldData = {
                id: fieldName,
                type: $field.data('field-type'),
                label: cleanLabel,
                placeholder: $input.attr('placeholder') || '',
                required: $field.hasClass('dcf-field-required'),
                css_class: cssClass, // Use css_class to match the database field name
                options: this.getFieldOptions($field)
            };
            
            // Add field-specific data based on type
            var fieldType = $field.data('field-type');
            switch (fieldType) {
                case 'name':
                    var $firstInput = $field.find('input[name$="_first"]');
                    var $lastInput = $field.find('input[name$="_last"]');
                    fieldData.first_placeholder = $firstInput.attr('placeholder') || '';
                    fieldData.last_placeholder = $lastInput.attr('placeholder') || '';
                    break;
                case 'terms':
                    var $termsLabel = $field.find('.dcf-terms-field label');
                    var labelHtml = $termsLabel.html();
                    if (labelHtml) {
                        // Extract text and URLs from the label
                        var tempDiv = $('<div>').html(labelHtml);
                        fieldData.terms_text = tempDiv.text().trim();
                        var $termsLink = tempDiv.find('a:contains("Terms and Conditions")');
                        var $privacyLink = tempDiv.find('a:contains("Privacy Policy")');
                        fieldData.terms_url = $termsLink.length ? $termsLink.attr('href') : '';
                        fieldData.privacy_url = $privacyLink.length ? $privacyLink.attr('href') : '';
                    }
                    break;
                case 'address':
                    var $line1Input = $field.find('input[name$="_line1"]');
                    var $line2Input = $field.find('input[name$="_line2"]');
                    var $cityInput = $field.find('input[name$="_city"]');
                    var $stateInput = $field.find('input[name$="_state"]');
                    var $zipInput = $field.find('input[name$="_zip"]');
                    fieldData.line1_placeholder = $line1Input.attr('placeholder') || '';
                    fieldData.line2_placeholder = $line2Input.attr('placeholder') || '';
                    fieldData.city_placeholder = $cityInput.attr('placeholder') || '';
                    fieldData.state_placeholder = $stateInput.attr('placeholder') || '';
                    fieldData.zip_placeholder = $zipInput.attr('placeholder') || '';
                    break;
                case 'hidden':
                    var $hiddenInput = $field.find('input[type="hidden"]');
                    fieldData.default_value = $hiddenInput.attr('value') || '';
                    break;
                case 'submit':
                    // Extract submit button properties from the preview
                    var $button = $field.find('button[type="button"]').first(); // Get only the submit button, not action buttons
                    if ($button.length) {
                        // Get button text, but exclude any child elements (like action buttons)
                        var buttonTextNode = $button.contents().filter(function() {
                            return this.nodeType === 3; // Text nodes only
                        }).text().trim();
                        fieldData.button_text = buttonTextNode || $button.clone().children().remove().end().text().trim() || 'Submit';
                        
                        // Extract styles from the button's style attribute
                        var buttonStyle = $button.attr('style') || '';
                        
                        // Parse background color
                        var bgColorMatch = buttonStyle.match(/background-color:\s*([^;]+)/);
                        fieldData.bg_color = bgColorMatch ? bgColorMatch[1].trim() : '#2271b1';
                        
                        // Parse text color - look for color property that's not background-color or border-color
                        var textColorMatch = buttonStyle.match(/(?:^|;)\s*color:\s*([^;]+)/);
                        fieldData.text_color = textColorMatch ? textColorMatch[1].trim() : '#ffffff';
                        
                        // Parse border color - match the specific format: border: 1px solid #color
                        var borderColorMatch = buttonStyle.match(/border:\s*1px\s+solid\s+([^;]+)/) ||
                                             buttonStyle.match(/border-color:\s*([^;]+)/);
                        fieldData.border_color = borderColorMatch ? borderColorMatch[1].trim() : '#2271b1';
                        
                        // Temporary debugging for border color
                        console.log('Button style for border extraction:', buttonStyle);
                        console.log('Border color match result:', borderColorMatch);
                        console.log('Final border color:', fieldData.border_color);
                        
                        // Parse border radius
                        var borderRadiusMatch = buttonStyle.match(/border-radius:\s*([^;]+)/);
                        fieldData.border_radius = borderRadiusMatch ? borderRadiusMatch[1].replace('px', '').trim() : '4';
                        
                        // Parse min width
                        var minWidthMatch = buttonStyle.match(/min-width:\s*([^;]+)/);
                        fieldData.min_width = minWidthMatch ? minWidthMatch[1].replace('px', '').trim() : '';
                        
                        // Determine size based on padding
                        var paddingMatch = buttonStyle.match(/padding:\s*([^;]+)/);
                        if (paddingMatch) {
                            var padding = paddingMatch[1].trim();
                            if (padding.includes('8px 16px')) {
                                fieldData.button_size = 'small';
                            } else if (padding.includes('16px 32px')) {
                                fieldData.button_size = 'large';
                            } else {
                                fieldData.button_size = 'medium';
                            }
                        } else {
                            fieldData.button_size = 'medium';
                        }
                        
                        // Determine alignment from parent div
                        var $buttonContainer = $button.closest('div[style*="text-align"]');
                        if ($buttonContainer.length) {
                            var containerStyle = $buttonContainer.attr('style') || '';
                            var alignMatch = containerStyle.match(/text-align:\s*([^;]+)/);
                            fieldData.alignment = alignMatch ? alignMatch[1].trim() : 'center';
                        } else {
                            fieldData.alignment = 'center';
                        }
                    } else {
                        // Default values if button not found
                        fieldData.button_text = 'Submit';
                        fieldData.button_size = 'medium';
                        fieldData.alignment = 'center';
                        fieldData.bg_color = '#2271b1';
                        fieldData.text_color = '#ffffff';
                        fieldData.border_color = '#2271b1';
                        fieldData.border_radius = '4';
                        fieldData.min_width = '';
                    }
                    break;
            }
            
            return fieldData;
        },

        getFieldOptions: function($field) {
            var options = [];
            var fieldType = $field.data('field-type');
            
            if (fieldType === 'select') {
                $field.find('.dcf-field-preview option').each(function() {
                    if ($(this).val() !== '') { // Skip placeholder option
                        options.push($(this).text());
                    }
                });
            } else if (fieldType === 'radio' || fieldType === 'checkbox') {
                $field.find('.dcf-field-preview input[type="' + fieldType + '"]').each(function() {
                    options.push($(this).closest('label').text().trim());
                });
            }
            
            return options;
        },

        openFieldModal: function(fieldData, $field) {
            var $modal = $('#dcf-field-modal');
            var fieldType = $field.data('field-type');
            
            // Populate modal with field data
            $modal.find('#field-label').val(fieldData.label);
            $modal.find('#field-name').val(fieldData.id);
            
            // Set placeholder with default if not already set
            var placeholder = fieldData.placeholder || this.getDefaultPlaceholder(fieldType, null, fieldData.label);
            $modal.find('#field-placeholder').val(placeholder);
            
            $modal.find('#field-required').prop('checked', fieldData.required);
            $modal.find('#field-css-class').val(fieldData.css_class || '');
            
            // Hide all field-specific options first
            $modal.find('#field-options-group, #name-field-options, #terms-field-options, #hidden-field-options, #address-field-options').hide();
            
            // Show field-specific options based on field type
            var fieldType = $field.data('field-type');
            switch (fieldType) {
                case 'name':
                    $modal.find('#name-field-options').show();
                    $modal.find('#field-first-placeholder').val(fieldData.first_placeholder || 'First Name');
                    $modal.find('#field-last-placeholder').val(fieldData.last_placeholder || 'Last Name');
                    break;
                case 'terms':
                    $modal.find('#terms-field-options').show();
                    $modal.find('#field-terms-text').val(fieldData.terms_text || 'I have read and agree to the Terms and Conditions and Privacy Policy');
                    $modal.find('#field-terms-url').val(fieldData.terms_url || '');
                    $modal.find('#field-privacy-url').val(fieldData.privacy_url || '');
                    break;
                case 'address':
                    $modal.find('#address-field-options').show();
                    $modal.find('#field-line1-placeholder').val(fieldData.line1_placeholder || 'Street Address');
                    $modal.find('#field-line2-placeholder').val(fieldData.line2_placeholder || 'Apartment, suite, etc.');
                    $modal.find('#field-city-placeholder').val(fieldData.city_placeholder || 'City');
                    $modal.find('#field-state-placeholder').val(fieldData.state_placeholder || 'State');
                    $modal.find('#field-zip-placeholder').val(fieldData.zip_placeholder || 'ZIP Code');
                    break;
                case 'hidden':
                    $modal.find('#hidden-field-options').show();
                    $modal.find('#field-default-value').val(fieldData.default_value || '');
                    break;
                case 'select':
                case 'radio':
                case 'checkbox':
                    $modal.find('#field-options-group').show();
                    if (fieldData.options.length > 0) {
                        $modal.find('#field-options').val(fieldData.options.join('\n'));
                    }
                    break;
                case 'submit':
                    $modal.find('#submit-field-options').show();
                    break;
            }
            
            // Address field specific data
            if (fieldData.line1_placeholder) $modal.find('#field-line1-placeholder').val(fieldData.line1_placeholder);
            if (fieldData.line2_placeholder) $modal.find('#field-line2-placeholder').val(fieldData.line2_placeholder);
            if (fieldData.city_placeholder) $modal.find('#field-city-placeholder').val(fieldData.city_placeholder);
            if (fieldData.state_placeholder) $modal.find('#field-state-placeholder').val(fieldData.state_placeholder);
            if (fieldData.zip_placeholder) $modal.find('#field-zip-placeholder').val(fieldData.zip_placeholder);
            
            // Submit button specific data
            if (fieldData.button_text) $modal.find('#field-button-text').val(fieldData.button_text);
            if (fieldData.button_size) $modal.find('#field-button-size').val(fieldData.button_size);
            if (fieldData.alignment) $modal.find('#field-alignment').val(fieldData.alignment);
            if (fieldData.bg_color) $modal.find('#field-bg-color').val(fieldData.bg_color);
            if (fieldData.text_color) $modal.find('#field-text-color').val(fieldData.text_color);
            if (fieldData.border_color) $modal.find('#field-border-color').val(fieldData.border_color);
            if (fieldData.border_radius) $modal.find('#field-border-radius').val(fieldData.border_radius);
            if (fieldData.min_width) $modal.find('#field-min-width').val(fieldData.min_width);
            
            // Store reference to field being edited
            $modal.data('editing-field', $field);
            
            $modal.show();
        },

        saveFieldChanges: function() {
            var $modal = $('#dcf-field-modal');
            var $field = $modal.data('editing-field');
            
            // Get updated values from modal
            var newLabel = $modal.find('#field-label').val().trim();
            var newName = $modal.find('#field-name').val().trim();
            var newPlaceholder = $modal.find('#field-placeholder').val().trim();
            var isRequired = $modal.find('#field-required').is(':checked');
            var newCssClass = $modal.find('#field-css-class').val().trim();
            var newOptions = $modal.find('#field-options').val().split('\n').filter(function(option) {
                return option.trim() !== '';
            });
            
            // Get field-specific data
            var fieldType = $field.data('field-type');
            var fieldSpecificData = {};
            
            switch (fieldType) {
                case 'name':
                    fieldSpecificData.first_placeholder = $modal.find('#field-first-placeholder').val().trim();
                    fieldSpecificData.last_placeholder = $modal.find('#field-last-placeholder').val().trim();
                    break;
                case 'terms':
                    fieldSpecificData.terms_text = $modal.find('#field-terms-text').val().trim();
                    fieldSpecificData.terms_url = $modal.find('#field-terms-url').val().trim();
                    fieldSpecificData.privacy_url = $modal.find('#field-privacy-url').val().trim();
                    break;
                case 'address':
                    fieldSpecificData.line1_placeholder = $modal.find('#field-line1-placeholder').val().trim();
                    fieldSpecificData.line2_placeholder = $modal.find('#field-line2-placeholder').val().trim();
                    fieldSpecificData.city_placeholder = $modal.find('#field-city-placeholder').val().trim();
                    fieldSpecificData.state_placeholder = $modal.find('#field-state-placeholder').val().trim();
                    fieldSpecificData.zip_placeholder = $modal.find('#field-zip-placeholder').val().trim();
                    break;
                case 'hidden':
                    fieldSpecificData.default_value = $modal.find('#field-default-value').val().trim();
                    break;
                case 'submit':
                    fieldSpecificData.button_text = $modal.find('#field-button-text').val().trim() || 'Submit';
                    fieldSpecificData.button_size = $modal.find('#field-button-size').val() || 'medium';
                    fieldSpecificData.alignment = $modal.find('#field-alignment').val() || 'center';
                    fieldSpecificData.bg_color = $modal.find('#field-bg-color').val() || '#2271b1';
                    fieldSpecificData.text_color = $modal.find('#field-text-color').val() || '#ffffff';
                    fieldSpecificData.border_color = $modal.find('#field-border-color').val() || '#2271b1';
                    fieldSpecificData.border_radius = $modal.find('#field-border-radius').val() || '4';
                    fieldSpecificData.min_width = $modal.find('#field-min-width').val() || '';
                    
                    console.log('saveFieldChanges - border color from modal:', $modal.find('#field-border-color').val());
                    console.log('saveFieldChanges - collected submit button data:', fieldSpecificData);
                    break;
            }
            
            // Update field data attributes
            $field.attr('data-field-id', newName);
            $field.attr('data-last-updated', Date.now()); // Force browser to recognize changes
            
            // Handle required class
            if (isRequired) {
                $field.addClass('dcf-field-required');
            } else {
                $field.removeClass('dcf-field-required');
            }
            
            // Handle custom CSS classes
            // First remove any existing custom classes (keep only dcf-form-field and dcf-field-required)
            $field.attr('class', function(i, className) {
                return className.split(/\s+/).filter(function(cls) {
                    return cls === 'dcf-form-field' || cls === 'dcf-field-required';
                }).join(' ');
            });
            
            // Add new custom CSS classes if provided
            if (newCssClass) {
                $field.addClass(newCssClass);
            }
            
            // Update all input elements with the new name
            $field.find('input, textarea, select').each(function() {
                $(this).attr('name', newName);
                $(this).attr('id', newName);
            });
            
            // Force a complete re-render of the field preview
            this.refreshFieldPreview($field, $.extend({
                label: newLabel,
                name: newName,
                placeholder: newPlaceholder,
                required: isRequired,
                type: $field.data('field-type'),
                options: newOptions
            }, fieldSpecificData));
            
            // Hide modal
            $modal.hide();
            
            // Force a visual refresh
            $field.hide().show();
            
            // Show success message
            this.showNotice('Field updated successfully', 'success');
        },

        updateFieldPreview: function($field) {
            var fieldData = this.getFieldData($field);
            var $preview = $field.find('.dcf-field-preview');
            
            if ($preview.length) {
                var previewText = fieldData.label;
                if (fieldData.type) {
                    previewText += ' (' + fieldData.type + ')';
                }
                if (fieldData.required) {
                    previewText += ' *';
                }
                $preview.text(previewText);
            }
        },

        refreshFieldPreview: function($field, fieldData) {
            var $preview = $field.find('.dcf-field-preview');
            if (!$preview.length) return;
            
            // Completely rebuild the preview HTML
            var previewHtml = '';
            
            // Only add label for non-submit fields
            if (fieldData.type !== 'submit') {
                previewHtml += '<label>' + fieldData.label + (fieldData.required ? ' *' : '') + '</label>';
            }
            
            switch (fieldData.type) {
                case 'name':
                    previewHtml += '<div class="dcf-name-field">';
                    previewHtml += '<div class="dcf-name-row">';
                    previewHtml += '<div class="dcf-name-first"><input type="text" name="' + fieldData.name + '_first" placeholder="' + (fieldData.first_placeholder || 'First Name') + '" disabled></div>';
                    previewHtml += '<div class="dcf-name-last"><input type="text" name="' + fieldData.name + '_last" placeholder="' + (fieldData.last_placeholder || 'Last Name') + '" disabled></div>';
                    previewHtml += '</div></div>';
                    break;
                case 'terms':
                    var termsText = fieldData.terms_text || 'I have read and agree to the Terms and Conditions and Privacy Policy';
                    var termsUrl = fieldData.terms_url || '';
                    var privacyUrl = fieldData.privacy_url || '';
                    
                    if (termsUrl || privacyUrl) {
                        if (termsUrl) {
                            termsText = termsText.replace('Terms and Conditions', '<a href="' + termsUrl + '" target="_blank">Terms and Conditions</a>');
                        }
                        if (privacyUrl) {
                            termsText = termsText.replace('Privacy Policy', '<a href="' + privacyUrl + '" target="_blank">Privacy Policy</a>');
                        }
                    }
                    
                    previewHtml += '<div class="dcf-terms-field">';
                    previewHtml += '<label><input type="checkbox" name="' + fieldData.name + '" disabled> ' + termsText + '</label>';
                    previewHtml += '</div>';
                    break;
                case 'address':
                    previewHtml += '<div class="dcf-address-field">';
                    previewHtml += '<div class="dcf-address-row">';
                    previewHtml += '<div class="dcf-address-line1"><input type="text" name="' + fieldData.name + '_line1" placeholder="' + (fieldData.line1_placeholder || 'Address Line 1') + '" disabled></div>';
                    previewHtml += '<div class="dcf-address-line2"><input type="text" name="' + fieldData.name + '_line2" placeholder="' + (fieldData.line2_placeholder || 'Address Line 2') + '" disabled></div>';
                    previewHtml += '</div>';
                    previewHtml += '<div class="dcf-address-row">';
                    previewHtml += '<div class="dcf-address-city"><input type="text" name="' + fieldData.name + '_city" placeholder="' + (fieldData.city_placeholder || 'City') + '" disabled></div>';
                    previewHtml += '<div class="dcf-address-state"><input type="text" name="' + fieldData.name + '_state" placeholder="' + (fieldData.state_placeholder || 'State') + '" disabled></div>';
                    previewHtml += '<div class="dcf-address-zip"><input type="text" name="' + fieldData.name + '_zip" placeholder="' + (fieldData.zip_placeholder || 'Zip Code') + '" disabled></div>';
                    previewHtml += '</div></div>';
                    break;
                case 'textarea':
                    previewHtml += '<textarea name="' + fieldData.name + '" placeholder="' + (fieldData.placeholder || 'Enter your message') + '" disabled></textarea>';
                    break;
                case 'select':
                    previewHtml += '<select name="' + fieldData.name + '" disabled>';
                    previewHtml += '<option value="">' + (fieldData.placeholder || 'Select an option') + '</option>';
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option) {
                            previewHtml += '<option value="' + option + '">' + option + '</option>';
                        });
                    } else {
                        previewHtml += '<option>Option 1</option><option>Option 2</option>';
                    }
                    previewHtml += '</select>';
                    break;
                case 'radio':
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option, index) {
                            var optionId = fieldData.name + '_' + index;
                            previewHtml += '<div><label for="' + optionId + '">';
                            previewHtml += '<input type="radio" id="' + optionId + '" name="' + fieldData.name + '" value="' + option + '" disabled>';
                            previewHtml += ' ' + option + '</label></div>';
                        });
                    } else {
                        previewHtml += '<div><label><input type="radio" name="' + fieldData.name + '" disabled> Option 1</label></div>';
                        previewHtml += '<div><label><input type="radio" name="' + fieldData.name + '" disabled> Option 2</label></div>';
                    }
                    break;
                case 'checkbox':
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option, index) {
                            var optionId = fieldData.name + '_' + index;
                            previewHtml += '<div><label for="' + optionId + '">';
                            previewHtml += '<input type="checkbox" id="' + optionId + '" name="' + fieldData.name + '" value="' + option + '" disabled>';
                            previewHtml += ' ' + option + '</label></div>';
                        });
                    } else {
                        previewHtml += '<label><input type="checkbox" disabled> Checkbox option</label>';
                    }
                    break;
                case 'hidden':
                    previewHtml += '<input type="hidden" name="' + fieldData.name + '" value="hidden_value"><em>Hidden field (not visible to users)</em>';
                    break;
                case 'submit':
                    const buttonText = fieldData.button_text || 'Submit';
                    const buttonSize = fieldData.button_size || 'medium';
                    const alignment = fieldData.alignment || 'center';
                    const bgColor = fieldData.bg_color || '#2271b1';
                    const textColor = fieldData.text_color || '#ffffff';
                    const borderColor = fieldData.border_color || '#2271b1';
                    const borderRadius = fieldData.border_radius || '4';
                    const minWidth = fieldData.min_width || '';
                    
                    console.log('refreshFieldPreview submit button colors:', {
                        bgColor: bgColor,
                        textColor: textColor,
                        borderColor: borderColor
                    });
                    
                    // Size styles
                    const sizeStyles = {
                        'small': 'padding: 8px 16px; font-size: 14px;',
                        'medium': 'padding: 12px 24px; font-size: 16px;',
                        'large': 'padding: 16px 32px; font-size: 18px;'
                    };
                    
                    let buttonStyle = sizeStyles[buttonSize] || sizeStyles['medium'];
                    buttonStyle += `background-color: ${bgColor}; color: ${textColor}; border: 1px solid ${borderColor}; border-radius: ${borderRadius}px; cursor: pointer;`;
                    
                    console.log('Generated button style:', buttonStyle);
                    
                    if (minWidth && minWidth !== '0') {
                        buttonStyle += ` min-width: ${minWidth}px;`;
                    }
                    
                    previewHtml += '<div style="text-align: ' + alignment + '; margin-top: 10px;">';
                    previewHtml += '<button type="button" style="' + buttonStyle + '" disabled>';
                    previewHtml += buttonText;
                    previewHtml += '</button>';
                    previewHtml += '<br><small style="color: #666; font-style: italic;">Custom submit button preview</small>';
                    previewHtml += '</div>';
                    break;
                default:
                    // Handle standard input types (text, email, phone, etc.)
                    var inputType = fieldData.type === 'phone' ? 'tel' : fieldData.type;
                    previewHtml += '<input type="' + inputType + '" name="' + fieldData.name + '" placeholder="' + (fieldData.placeholder || '') + '" disabled>';
                    break;
            }
            
            // Replace the entire preview content
            $preview.html(previewHtml);
            
            // Add a visual flash to show the update
            $preview.css('background-color', '#e6f3ff');
            setTimeout(function() {
                $preview.css('background-color', '');
            }, 1500);
        },

        toggleUTMFields: function(includeUTM) {
            var utmFields = [
                { id: 'utm_source', label: 'UTM Source' },
                { id: 'utm_medium', label: 'UTM Medium' },
                { id: 'utm_campaign', label: 'UTM Campaign' },
                { id: 'utm_content', label: 'UTM Content' },
                { id: 'utm_keyword', label: 'UTM Keyword' },
                { id: 'utm_matchtype', label: 'UTM Match Type' },
                { id: 'campaign_id', label: 'Campaign ID' },
                { id: 'ad_group_id', label: 'Ad Group ID' },
                { id: 'ad_id', label: 'Ad ID' },
                { id: 'gclid', label: 'Google Click ID' }
            ];
            
            var $formFields = $('.dcf-form-fields');
            
            if (includeUTM) {
                // Add UTM fields if they don't exist
                utmFields.forEach(function(field) {
                    if ($formFields.find('[data-field-id="' + field.id + '"]').length === 0) {
                        // Create hidden field HTML with UTM marker
                        var fieldHtml = '<div class="dcf-form-field dcf-utm-field" data-field-id="' + field.id + '" data-field-type="hidden">';
                        fieldHtml += '<div class="dcf-field-handle"><span class="dashicons dashicons-menu"></span></div>';
                        fieldHtml += '<div class="dcf-field-preview">';
                        fieldHtml += '<label>' + field.label + ' (Hidden UTM Field)</label>';
                        fieldHtml += '<input type="hidden" name="' + field.id + '" value="" disabled>';
                        fieldHtml += '</div>';
                        fieldHtml += '<div class="dcf-field-actions">';
                        fieldHtml += '<button type="button" class="dcf-edit-field" title="Edit Field"><span class="dashicons dashicons-edit"></span></button>';
                        fieldHtml += '<button type="button" class="dcf-delete-field" title="Delete Field"><span class="dashicons dashicons-trash"></span></button>';
                        fieldHtml += '</div>';
                        fieldHtml += '</div>';
                        
                        $formFields.append(fieldHtml);
                    }
                });
                
                // Hide empty form message if it exists
                $formFields.find('.dcf-empty-form').hide();
            } else {
                // Remove UTM fields
                $formFields.find('.dcf-utm-field').remove();
                
                // Show empty form message if no fields remain
                if ($formFields.find('.dcf-form-field').length === 0) {
                    $formFields.find('.dcf-empty-form').show();
                }
            }
        },
        
        bindStyleControls: function() {
            // Layout selector
            $(document).on('click', '.dcf-layout-option', function() {
                $('.dcf-layout-option').removeClass('active');
                $(this).addClass('active');
                var layout = $(this).data('layout');
                $('#form-layout-type').val(layout);
                
                // Show/hide label width for two-column layout
                if (layout === 'two-column' || layout === 'single-line') {
                    $('#label-width-group').show();
                } else {
                    $('#label-width-group').hide();
                }
            });
            
            // Alignment selector
            $(document).on('click', '.dcf-alignment-option', function() {
                $('.dcf-alignment-option').removeClass('active');
                $(this).addClass('active');
                var alignment = $(this).data('align');
                $('#form-label-alignment').val(alignment);
            });
        },
        
        saveForm: function() {
            var $button = $('#dcf-save-form');
            var originalText = $button.text();
            var formId = $button.data('form-id') || 0;
            
            // Get form values
            var formName = $('#form-name').val();
            var formType = $('#form-type').val();
            var formTitle = $('#form-title').val();
            var formDescription = $('#form-description').val();
            var webhookUrl = $('#webhook-url').val();
            var formFields = this.getFormFields();
            
            // Get POS integration settings
            var posIntegration = {
                enabled: $('#pos-sync-enabled').is(':checked'),
                check_existing_customer: $('#pos-check-existing').is(':checked'),
                create_customer: $('#pos-create-customer').is(':checked'),
                update_customer: $('#pos-update-customer').is(':checked'),
                create_route: $('#pos-create-route').is(':checked'),
                process_payment: $('#pos-process-payment').is(':checked')
            };
            
            // Get style settings
            var formStyles = {
                layout_type: $('#form-layout-type').val(),
                input_style: $('#form-input-style').val(),
                width: $('#form-width').val(),
                width_unit: $('#form-width-unit').val(),
                field_spacing: $('#form-field-spacing').val(),
                label_width: $('#form-label-width').val(),
                label_alignment: $('#form-label-alignment').val(),
                padding_top: $('#form-padding-top').val(),
                padding_right: $('#form-padding-right').val(),
                padding_bottom: $('#form-padding-bottom').val(),
                padding_left: $('#form-padding-left').val(),
                show_labels: $('#form-show-labels').is(':checked')
            };
            
            console.log('saveForm: Starting save process', {
                formId: formId,
                formName: formName,
                formType: formType,
                formTitle: formTitle,
                formDescription: formDescription,
                webhookUrl: webhookUrl,
                formFields: formFields
            });
            
            // Validate required fields
            if (!formName || formName.trim() === '') {
                DCF_Admin.showNotice('Form name is required', 'error');
                return;
            }
            
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt dcf-spin"></span> ' + dcf_admin.messages.saving);
            
            // Build form configuration
            var formConfig = {
                title: formTitle,
                description: formDescription,
                fields: formFields,
                pos_integration: posIntegration,
                include_utm_parameters: $('#include-utm-parameters').is(':checked'),
                webhook_enabled: $('#webhook-enabled').is(':checked'),
                success_message: $('#success-message').val() || '',
                styles: formStyles
            };
            
            // Include multi-step configuration if it exists from template or existing form
            if (this.currentTemplateData && this.currentTemplateData.config.multi_step) {
                formConfig.multi_step = this.currentTemplateData.config.multi_step;
                formConfig.steps = this.currentTemplateData.config.steps;
            } else if (this.currentFormConfig && this.currentFormConfig.multi_step) {
                formConfig.multi_step = this.currentFormConfig.multi_step;
                formConfig.steps = this.currentFormConfig.steps;
            }
            
            var formData = {
                action: 'dcf_unique_form_save_12345', // Use unique handler to test
                form_id: formId,
                form_name: formName,
                form_type: formType,
                form_config: JSON.stringify(formConfig),
                webhook_url: webhookUrl,
                nonce: dcf_admin.nonce
            };
            
            console.log('saveForm: Sending AJAX request', formData);
            
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('saveForm: AJAX success response', response);
                    if (response.success) {
                        if (!formId) {
                            // Redirect to edit page for new forms
                            var adminUrl = dcf_admin.admin_url || window.location.origin + '/wp-admin/';
                            console.log('saveForm: Redirecting to edit page for new form');
                            window.location.href = adminUrl + 'admin.php?page=cmf-form-builder&action=edit&form_id=' + response.data.form_id;
                        } else {
                            console.log('saveForm: Form updated successfully');
                            DCF_Admin.showNotice(dcf_admin.messages.form_saved, 'success');
                        }
                    } else {
                        console.log('saveForm: Server returned error', response.data);
                        DCF_Admin.showNotice(response.data.message || dcf_admin.messages.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('saveForm: AJAX error', {xhr: xhr, status: status, error: error});
                    DCF_Admin.showNotice(dcf_admin.messages.error, 'error');
                },
                complete: function() {
                    console.log('saveForm: AJAX request completed');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        previewForm: function() {
            var formId = $('#dcf-save-form').data('form-id') || 0;
            
            if (!formId) {
                // For new forms, show a message that they need to save first
                DCF_Admin.showNotice('Please save the form first before previewing.', 'info');
                return;
            }
            
            // Open preview in new window/tab
            var previewUrl = window.location.origin + '/?dcf_preview=1&form_id=' + formId;
            var previewWindow = window.open(previewUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
            
            if (!previewWindow) {
                // Fallback if popup was blocked
                DCF_Admin.showNotice('Please allow popups to preview the form, or visit: ' + previewUrl, 'info');
            }
        },

        selectTemplate: function($button) {
            var templateKey = $button.data('template');
            
            if (!templateKey) {
                // User selected blank template
                $('#dcf-template-modal').css('display', 'none');
                return;
            }
            
            var originalText = $button.text();
            $button.prop('disabled', true).text('Loading...');
            
            // Get template data via AJAX
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'get_form_template',
                    template_key: templateKey,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.template) {
                        DCF_Admin.loadTemplateData(response.data.template);
                        $('#dcf-template-modal').css('display', 'none');
                        DCF_Admin.showNotice('Template loaded successfully!', 'success');
                    } else {
                        DCF_Admin.showNotice(response.data.message || 'Failed to load template', 'error');
                    }
                },
                error: function() {
                    DCF_Admin.showNotice('Error loading template', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        applyFormTemplate: function() {
            var templateKey = $('#form-template-selector').val();
            
            if (!templateKey) {
                this.showNotice('Please select a template first.', 'error');
                return;
            }
            
            var $button = $('#apply-template');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Applying Template...');
            
            // Get template data via AJAX
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'get_form_template',
                    template_key: templateKey,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.template) {
                        DCF_Admin.loadTemplateData(response.data.template);
                        DCF_Admin.showNotice('Template applied successfully!', 'success');
                    } else {
                        DCF_Admin.showNotice(response.data.message || 'Failed to load template', 'error');
                    }
                },
                error: function() {
                    DCF_Admin.showNotice('Error loading template', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        loadTemplateData: function(template) {
            console.log('Loading template data:', template);
            
            // Store template data for later use (including multi-step config)
            this.currentTemplateData = template;
            
            // Update form settings
            $('#form-name').val(template.name);
            $('#form-type').val(template.form_type);
            $('#form-title').val(template.config.title || template.name);
            $('#form-description').val(template.config.description || template.description);
            
            // Update canvas header
            $('#canvas-form-title').text(template.config.title || template.name);
            $('#canvas-form-description').text(template.config.description || template.description);
            
            // Clear existing fields
            $('.dcf-form-fields').empty();
            
            // Add template fields
            if (template.config.fields && template.config.fields.length > 0) {
                template.config.fields.forEach(function(field) {
                    DCF_Admin.addTemplateField(field);
                });
                $('.dcf-empty-form').hide();
            } else {
                $('.dcf-empty-form').show();
            }
            
            // Update POS integration settings if available
            if (template.config.pos_integration) {
                // Enable POS sync if any integration feature is enabled
                var anyEnabled = template.config.pos_integration.check_existing_customer || 
                               template.config.pos_integration.create_customer || 
                               template.config.pos_integration.update_customer || 
                               template.config.pos_integration.create_route || 
                               template.config.pos_integration.process_payment;
                
                $('#pos-sync-enabled').prop('checked', anyEnabled);
                $('#pos-check-existing').prop('checked', template.config.pos_integration.check_existing_customer || false);
                $('#pos-create-customer').prop('checked', template.config.pos_integration.create_customer || false);
                $('#pos-update-customer').prop('checked', template.config.pos_integration.update_customer || false);
                $('#pos-create-route').prop('checked', template.config.pos_integration.create_route || false);
                $('#pos-process-payment').prop('checked', template.config.pos_integration.process_payment || false);
                
                // Show/hide POS options based on enabled state
                $('.dcf-pos-options').toggle(anyEnabled);
                $('.dcf-test-features').toggle(anyEnabled);
            }
        },
        
        addTemplateField: function(fieldData) {
            var $container = $('.dcf-form-fields');
            var fieldId = fieldData.id || this.generateFieldId(fieldData.type, $container);
            var fieldHtml = this.generateTemplateFieldHtml(fieldData, fieldId);
            
            $container.append(fieldHtml);
        },
        
        generateTemplateFieldHtml: function(fieldData, fieldId) {
            var fieldName = fieldData.label || (fieldData.type.charAt(0).toUpperCase() + fieldData.type.slice(1) + ' Field');
            var fieldClasses = ['dcf-form-field'];
            
            if (fieldData.required) {
                fieldClasses.push('dcf-field-required');
            }
            if (fieldData.css_class) {
                fieldClasses.push(fieldData.css_class);
            }
            
            var html = '<div class="' + fieldClasses.join(' ') + '" data-field-id="' + fieldId + '" data-field-type="' + fieldData.type + '">';
            html += '<div class="dcf-field-handle"><span class="dashicons dashicons-menu"></span></div>';
            html += '<div class="dcf-field-preview">';
            
            // Add label for non-submit fields
            if (fieldData.type !== 'submit') {
                html += '<label>' + fieldName;
                if (fieldData.required) {
                    html += ' <span class="dcf-required">*</span>';
                }
                html += '</label>';
            }
            
            // Generate field-specific HTML
            switch (fieldData.type) {
                case 'text':
                case 'email':
                    var inputType = fieldData.type === 'phone' ? 'tel' : fieldData.type;
                    html += '<input type="' + inputType + '" name="' + fieldId + '" placeholder="' + (fieldData.placeholder || '') + '" disabled>';
                    break;
                case 'phone':
                    html += '<input type="tel" name="' + fieldId + '" placeholder="' + (fieldData.placeholder || '') + '" disabled>';
                    break;
                case 'textarea':
                    html += '<textarea name="' + fieldId + '" placeholder="' + (fieldData.placeholder || '') + '" disabled></textarea>';
                    break;
                case 'select':
                    html += '<select name="' + fieldId + '" disabled>';
                    if (fieldData.placeholder) {
                        html += '<option value="">' + fieldData.placeholder + '</option>';
                    }
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option) {
                            var optionLabel = typeof option === 'object' ? option.label : option;
                            html += '<option>' + optionLabel + '</option>';
                        });
                    } else {
                        html += '<option>Option 1</option><option>Option 2</option>';
                    }
                    html += '</select>';
                    break;
                case 'radio':
                    html += '<div class="dcf-radio-preview">';
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option) {
                            var optionLabel = typeof option === 'object' ? option.label : option;
                            html += '<div><label><input type="radio" name="' + fieldId + '" disabled> ' + optionLabel + '</label></div>';
                        });
                    } else {
                        html += '<div><label><input type="radio" name="' + fieldId + '" disabled> Option 1</label></div>';
                        html += '<div><label><input type="radio" name="' + fieldId + '" disabled> Option 2</label></div>';
                    }
                    html += '</div>';
                    break;
                case 'checkbox':
                    html += '<div class="dcf-checkbox-preview">';
                    if (fieldData.options && fieldData.options.length > 0) {
                        fieldData.options.forEach(function(option) {
                            var optionLabel = typeof option === 'object' ? option.label : option;
                            html += '<div><label><input type="checkbox" name="' + fieldId + '" disabled> ' + optionLabel + '</label></div>';
                        });
                    } else {
                        html += '<div><label><input type="checkbox" disabled> Checkbox option</label></div>';
                    }
                    html += '</div>';
                    break;
                case 'submit':
                    var buttonText = fieldData.button_text || 'Submit';
                    var buttonSize = fieldData.button_size || 'medium';
                    var alignment = fieldData.alignment || 'center';
                    var bgColor = fieldData.bg_color || '#2271b1';
                    var textColor = fieldData.text_color || '#ffffff';
                    var borderColor = fieldData.border_color || '#2271b1';
                    var borderRadius = fieldData.border_radius || '4';
                    var minWidth = fieldData.min_width || '';
                    
                    var sizeStyles = {
                        'small': 'padding: 8px 16px; font-size: 14px;',
                        'medium': 'padding: 12px 24px; font-size: 16px;',
                        'large': 'padding: 16px 32px; font-size: 18px;'
                    };
                    
                    var buttonStyle = sizeStyles[buttonSize] || sizeStyles['medium'];
                    buttonStyle += 'background-color: ' + bgColor + '; color: ' + textColor + '; border: 1px solid ' + borderColor + '; border-radius: ' + borderRadius + 'px; cursor: pointer;';
                    
                    if (minWidth && minWidth !== '0') {
                        buttonStyle += ' min-width: ' + minWidth + 'px;';
                    }
                    
                    html += '<div style="text-align: ' + alignment + '; margin-top: 10px;">';
                    html += '<button type="button" style="' + buttonStyle + '" disabled>' + buttonText + '</button>';
                    html += '<br><small style="color: #666; font-style: italic;">Custom submit button preview</small>';
                    html += '</div>';
                    break;
                default:
                    html += '<input type="' + fieldData.type + '" name="' + fieldId + '" placeholder="' + (fieldData.placeholder || '') + '" disabled>';
                    break;
            }
            
            html += '</div>';
            html += '<div class="dcf-field-actions">';
            html += '<button type="button" class="dcf-edit-field" title="Edit Field"><span class="dashicons dashicons-edit"></span></button>';
            html += '<button type="button" class="dcf-duplicate-field" title="Duplicate Field"><span class="dashicons dashicons-admin-page"></span></button>';
            html += '<button type="button" class="dcf-delete-field" title="Delete Field"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },

        getFormFields: function() {
            var fields = [];
            
            console.log('getFormFields: Found', $('.dcf-form-field').length, 'field elements');
            
            $('.dcf-form-field').each(function() {
                var $field = $(this);
                var fieldData = DCF_Admin.getFieldData($field);
                console.log('getFormFields: Collected field data:', fieldData);
                fields.push(fieldData);
            });
            
            console.log('getFormFields: Final fields array:', fields);
            return fields;
        },

        saveSettings: function($form) {
            var $button = $form.find('[type="submit"]');
            var originalText = $button.val();
            
            $button.prop('disabled', true).val(dcf_admin.messages.saving);
            
            // Form will submit normally, but we can add validation here
            setTimeout(function() {
                $button.prop('disabled', false).val(originalText);
            }, 2000);
        },

        togglePOSSettings: function(posSystem) {
            $('.dcf-integration-settings').hide();
            if (posSystem) {
                $('#' + posSystem + '-settings').show();
            }
        },

        exportSettings: function() {
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'export_settings',
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var blob = new Blob([JSON.stringify(response.data.settings, null, 2)], { type: 'application/json' });
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'dcf-settings-' + new Date().toISOString().split('T')[0] + '.json';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        DCF_Admin.showNotice(response.data.message || dcf_admin.messages.error, 'error');
                    }
                },
                error: function() {
                    DCF_Admin.showNotice(dcf_admin.messages.error, 'error');
                }
            });
        },

        importSettings: function(file) {
            if (!file) return;
            
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    DCF_Admin.processImportedSettings(settings);
                } catch (error) {
                    DCF_Admin.showNotice(dcf_admin.messages.invalid_file, 'error');
                }
            };
            reader.readAsText(file);
        },

        processImportedSettings: function(settings) {
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_admin_action',
                    dcf_action: 'import_settings',
                    settings: JSON.stringify(settings),
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DCF_Admin.showNotice(dcf_admin.messages.settings_imported, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        DCF_Admin.showNotice(response.data.message || dcf_admin.messages.error, 'error');
                    }
                },
                error: function() {
                    DCF_Admin.showNotice(dcf_admin.messages.error, 'error');
                }
            });
        },

        copyToClipboard: function($button) {
            var text = $button.data('copy') || $button.prev('input').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    DCF_Admin.showCopyFeedback($button);
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                DCF_Admin.showCopyFeedback($button);
            }
        },

        showCopyFeedback: function($button) {
            var originalText = $button.text();
            $button.text(dcf_admin.messages.copied);
            
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        },

        showNotice: function(message, type) {
            type = type || 'info';
            
            if (this.sweetAlertReady && typeof Swal !== 'undefined') {
                var icon = type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info');
                Swal.fire({
                    title: type === 'error' ? 'Error' : (type === 'success' ? 'Success' : 'Notice'),
                    text: message,
                    icon: icon,
                    timer: type === 'success' ? 3000 : undefined,
                    showConfirmButton: type === 'error',
                    toast: true,
                    position: 'top-end'
                });
            } else {
                // Fallback to WordPress notices
                var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
                $('.wrap h1').after($notice);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $notice.fadeOut();
                }, 5000);
            }
        },

        // Utility functions
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() { inThrottle = false; }, limit);
                }
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        DCF_Admin.init();
    });

    // Add CSS for spinning animation
    $('<style>')
        .prop('type', 'text/css')
        .html('.dcf-spin { animation: dcf-spin 1s linear infinite; } @keyframes dcf-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery); 