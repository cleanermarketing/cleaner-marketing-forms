/**
 * Enhanced Step Navigation for Visual Editor
 *
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.DCF_StepManager = {
        currentStep: 1,
        steps: [],
        isDraggingStep: false,
        
        init: function() {
            this.bindEvents();
            this.initializeSteps();
        },

        bindEvents: function() {
            var self = this;

            // Step navigation
            $(document).on('click', '.dcf-step-tab', function(e) {
                if (!$(e.target).hasClass('dcf-step-actions-btn')) {
                    var stepId = $(this).data('step');
                    self.switchToStep(stepId);
                }
            });

            // Previous/Next buttons
            $(document).on('click', '#prev-step', function() {
                self.navigateToPreviousStep();
            });

            $(document).on('click', '#next-step', function() {
                self.navigateToNextStep();
            });

            // Add new step
            $(document).on('click', '.dcf-add-step', function() {
                self.addNewStep();
            });

            // Step actions menu
            $(document).on('click', '.dcf-step-actions-btn', function(e) {
                e.stopPropagation();
                self.showStepActions($(this).closest('.dcf-step-tab'));
            });

            // Handle step actions
            $(document).on('click', '.dcf-step-action', function() {
                var action = $(this).data('action');
                var stepId = $(this).data('step-id');
                self.handleStepAction(action, stepId);
            });

            // Make steps draggable for reordering
            this.initStepDragging();

            // Handle rename inline
            $(document).on('dblclick', '.dcf-step-label', function() {
                self.startStepRename($(this));
            });

            // Close menus on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dcf-step-actions-menu').length) {
                    $('.dcf-step-actions-menu').remove();
                }
            });
        },

        initializeSteps: function() {
            // Initialize from existing popup data if available
            if (window.DCF_VisualEditor && window.DCF_VisualEditor.popupData && window.DCF_VisualEditor.popupData.steps) {
                this.steps = window.DCF_VisualEditor.popupData.steps;
            } else {
                // Default single step
                this.steps = [{
                    id: 1,
                    name: 'Optin',
                    blocks: [],
                    settings: {
                        transition: 'fade',
                        duration: 300,
                        validation: true
                    }
                }];
            }
            
            this.renderStepTabs();
            this.updateNavigationState();
        },

        initStepDragging: function() {
            var self = this;
            
            $('.dcf-step-tabs').sortable({
                items: '.dcf-step-tab',
                axis: 'x',
                containment: 'parent',
                helper: 'clone',
                tolerance: 'pointer',
                start: function(e, ui) {
                    self.isDraggingStep = true;
                    ui.helper.addClass('dcf-step-dragging');
                },
                stop: function(e, ui) {
                    self.isDraggingStep = false;
                    self.reorderSteps();
                },
                update: function(e, ui) {
                    // Update step order
                }
            });
        },

        switchToStep: function(stepId) {
            if (this.currentStep === stepId) return;
            
            // Validate current step before switching
            if (this.validateCurrentStep()) {
                // Save current step
                if (window.DCF_VisualEditor) {
                    window.DCF_VisualEditor.saveCurrentStep();
                }
                
                // Animate transition
                this.animateStepTransition(this.currentStep, stepId);
                
                // Update current step
                this.currentStep = stepId;
                
                // Load new step
                if (window.DCF_VisualEditor) {
                    window.DCF_VisualEditor.loadStepContent(stepId);
                }
                
                // Update UI
                this.updateStepTabs();
                this.updateNavigationState();
            }
        },

        navigateToPreviousStep: function() {
            if (this.currentStep > 1) {
                this.switchToStep(this.currentStep - 1);
            }
        },

        navigateToNextStep: function() {
            if (this.currentStep < this.steps.length) {
                this.switchToStep(this.currentStep + 1);
            }
        },

        addNewStep: function() {
            var newStep = {
                id: this.steps.length + 1,
                name: 'Step ' + (this.steps.length + 1),
                blocks: [],
                settings: {
                    transition: 'fade',
                    duration: 300,
                    validation: true
                }
            };
            
            this.steps.push(newStep);
            
            // Update visual editor data
            if (window.DCF_VisualEditor) {
                window.DCF_VisualEditor.popupData.steps = this.steps;
            }
            
            this.renderStepTabs();
            this.switchToStep(newStep.id);
            
            // Show success message
            this.showNotification('New step added', 'success');
        },

        showStepActions: function($stepTab) {
            $('.dcf-step-actions-menu').remove();
            
            var stepId = $stepTab.data('step');
            var step = this.getStep(stepId);
            
            var menu = $(`
                <div class="dcf-step-actions-menu">
                    <div class="dcf-step-action" data-action="rename" data-step-id="${stepId}">
                        <span class="dashicons dashicons-edit"></span> Rename
                    </div>
                    <div class="dcf-step-action" data-action="duplicate" data-step-id="${stepId}">
                        <span class="dashicons dashicons-admin-page"></span> Duplicate
                    </div>
                    <div class="dcf-step-action" data-action="settings" data-step-id="${stepId}">
                        <span class="dashicons dashicons-admin-generic"></span> Settings
                    </div>
                    ${this.steps.length > 1 ? `
                        <div class="dcf-step-actions-separator"></div>
                        <div class="dcf-step-action dcf-delete-action" data-action="delete" data-step-id="${stepId}">
                            <span class="dashicons dashicons-trash"></span> Delete
                        </div>
                    ` : ''}
                </div>
            `);
            
            var offset = $stepTab.offset();
            menu.css({
                top: offset.top + $stepTab.outerHeight() + 5,
                left: offset.left
            });
            
            $('body').append(menu);
        },

        handleStepAction: function(action, stepId) {
            switch(action) {
                case 'rename':
                    this.renameStep(stepId);
                    break;
                case 'duplicate':
                    this.duplicateStep(stepId);
                    break;
                case 'settings':
                    this.showStepSettings(stepId);
                    break;
                case 'delete':
                    this.deleteStep(stepId);
                    break;
            }
            
            $('.dcf-step-actions-menu').remove();
        },

        renameStep: function(stepId) {
            var $tab = $('.dcf-step-tab[data-step="' + stepId + '"]');
            var $label = $tab.find('.dcf-step-label');
            this.startStepRename($label);
        },

        startStepRename: function($label) {
            var currentName = $label.text();
            var stepId = $label.closest('.dcf-step-tab').data('step');
            
            var $input = $('<input type="text" class="dcf-step-rename-input">');
            $input.val(currentName);
            
            $label.html($input);
            $input.focus().select();
            
            var self = this;
            
            var finishRename = function() {
                var newName = $input.val().trim();
                if (newName && newName !== currentName) {
                    var step = self.getStep(stepId);
                    if (step) {
                        step.name = newName;
                        $label.text(newName);
                        
                        // Save state
                        if (window.DCF_VisualEditor) {
                            window.DCF_VisualEditor.saveState();
                        }
                    }
                } else {
                    $label.text(currentName);
                }
            };
            
            $input.on('blur', finishRename);
            $input.on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    finishRename();
                }
            });
        },

        duplicateStep: function(stepId) {
            var step = this.getStep(stepId);
            if (!step) return;
            
            var newStep = JSON.parse(JSON.stringify(step));
            newStep.id = this.steps.length + 1;
            newStep.name = step.name + ' (Copy)';
            
            this.steps.push(newStep);
            
            // Update visual editor data
            if (window.DCF_VisualEditor) {
                window.DCF_VisualEditor.popupData.steps = this.steps;
            }
            
            this.renderStepTabs();
            this.switchToStep(newStep.id);
            
            this.showNotification('Step duplicated', 'success');
        },

        deleteStep: function(stepId) {
            if (this.steps.length <= 1) {
                this.showNotification('Cannot delete the last step', 'error');
                return;
            }
            
            if (confirm('Are you sure you want to delete this step?')) {
                var stepIndex = this.steps.findIndex(s => s.id === stepId);
                if (stepIndex !== -1) {
                    this.steps.splice(stepIndex, 1);
                    
                    // Reindex steps
                    this.steps.forEach((step, index) => {
                        step.id = index + 1;
                    });
                    
                    // Update visual editor data
                    if (window.DCF_VisualEditor) {
                        window.DCF_VisualEditor.popupData.steps = this.steps;
                    }
                    
                    // Switch to first step if current was deleted
                    if (this.currentStep === stepId) {
                        this.currentStep = 1;
                        if (window.DCF_VisualEditor) {
                            window.DCF_VisualEditor.loadStepContent(1);
                        }
                    }
                    
                    this.renderStepTabs();
                    this.updateNavigationState();
                    
                    this.showNotification('Step deleted', 'success');
                }
            }
        },

        showStepSettings: function(stepId) {
            var step = this.getStep(stepId);
            if (!step) return;
            
            // Create settings modal
            var modal = $(`
                <div class="dcf-modal-overlay">
                    <div class="dcf-modal dcf-step-settings-modal">
                        <div class="dcf-modal-header">
                            <h3>Step Settings: ${step.name}</h3>
                            <button class="dcf-modal-close"><span class="dashicons dashicons-no"></span></button>
                        </div>
                        <div class="dcf-modal-content">
                            <div class="dcf-field-group">
                                <label class="dcf-field-label">Transition Effect</label>
                                <select class="dcf-field-select" id="step-transition">
                                    <option value="fade" ${step.settings.transition === 'fade' ? 'selected' : ''}>Fade</option>
                                    <option value="slide-horizontal" ${step.settings.transition === 'slide-horizontal' ? 'selected' : ''}>Slide Horizontal</option>
                                    <option value="slide-vertical" ${step.settings.transition === 'slide-vertical' ? 'selected' : ''}>Slide Vertical</option>
                                    <option value="zoom" ${step.settings.transition === 'zoom' ? 'selected' : ''}>Zoom</option>
                                    <option value="flip" ${step.settings.transition === 'flip' ? 'selected' : ''}>Flip</option>
                                </select>
                            </div>
                            <div class="dcf-field-group">
                                <label class="dcf-field-label">Transition Duration (ms)</label>
                                <input type="number" class="dcf-field-input" id="step-duration" value="${step.settings.duration || 300}" min="0" max="2000" step="100">
                            </div>
                            <div class="dcf-field-group">
                                <label class="dcf-field-label">
                                    <input type="checkbox" id="step-validation" ${step.settings.validation ? 'checked' : ''}>
                                    Validate form fields before next step
                                </label>
                            </div>
                            <div class="dcf-field-group">
                                <label class="dcf-field-label">
                                    <input type="checkbox" id="step-skip-allowed" ${step.settings.skipAllowed ? 'checked' : ''}>
                                    Allow users to skip this step
                                </label>
                            </div>
                        </div>
                        <div class="dcf-modal-footer">
                            <button class="dcf-btn dcf-btn-primary" id="save-step-settings">Save Settings</button>
                            <button class="dcf-btn dcf-btn-secondary dcf-modal-cancel">Cancel</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            var self = this;
            
            // Handle save
            modal.find('#save-step-settings').on('click', function() {
                step.settings.transition = modal.find('#step-transition').val();
                step.settings.duration = parseInt(modal.find('#step-duration').val());
                step.settings.validation = modal.find('#step-validation').is(':checked');
                step.settings.skipAllowed = modal.find('#step-skip-allowed').is(':checked');
                
                // Save state
                if (window.DCF_VisualEditor) {
                    window.DCF_VisualEditor.saveState();
                }
                
                modal.remove();
                self.showNotification('Step settings saved', 'success');
            });
            
            // Handle close
            modal.find('.dcf-modal-close, .dcf-modal-cancel').on('click', function() {
                modal.remove();
            });
            
            // Close on overlay click
            modal.on('click', function(e) {
                if ($(e.target).hasClass('dcf-modal-overlay')) {
                    modal.remove();
                }
            });
        },

        reorderSteps: function() {
            var newOrder = [];
            $('.dcf-step-tab').each(function(index) {
                var stepId = $(this).data('step');
                var step = this.getStep(stepId);
                if (step) {
                    step.id = index + 1;
                    newOrder.push(step);
                }
            }.bind(this));
            
            this.steps = newOrder;
            
            // Update visual editor data
            if (window.DCF_VisualEditor) {
                window.DCF_VisualEditor.popupData.steps = this.steps;
                window.DCF_VisualEditor.saveState();
            }
            
            this.renderStepTabs();
        },

        renderStepTabs: function() {
            var $tabs = $('.dcf-step-tabs');
            $tabs.empty();
            
            this.steps.forEach(function(step) {
                var $tab = $(`
                    <div class="dcf-step-tab ${step.id === this.currentStep ? 'active' : ''}" data-step="${step.id}">
                        <span class="dcf-step-number">${step.id}</span>
                        <span class="dcf-step-label">${step.name}</span>
                        <button class="dcf-step-actions-btn" title="Step actions">
                            <span class="dashicons dashicons-ellipsis"></span>
                        </button>
                    </div>
                `);
                $tabs.append($tab);
            }.bind(this));
            
            // Reinitialize dragging
            this.initStepDragging();
        },

        updateStepTabs: function() {
            $('.dcf-step-tab').removeClass('active');
            $('.dcf-step-tab[data-step="' + this.currentStep + '"]').addClass('active');
        },

        updateNavigationState: function() {
            $('#prev-step').prop('disabled', this.currentStep === 1);
            $('#next-step').prop('disabled', this.currentStep === this.steps.length);
            
            // Update step counter
            $('.dcf-step-counter').text(this.currentStep + ' / ' + this.steps.length);
        },

        validateCurrentStep: function() {
            var step = this.getStep(this.currentStep);
            if (!step || !step.settings.validation) return true;
            
            // Check for required form fields
            var $requiredFields = $('.dcf-popup-content').find('input[required], textarea[required], select[required]');
            var isValid = true;
            
            $requiredFields.each(function() {
                var $field = $(this);
                if (!$field.val() || $field.val().trim() === '') {
                    $field.addClass('dcf-field-error');
                    isValid = false;
                } else {
                    $field.removeClass('dcf-field-error');
                }
            });
            
            if (!isValid) {
                this.showNotification('Please fill in all required fields', 'error');
            }
            
            return isValid;
        },

        animateStepTransition: function(fromStep, toStep) {
            var step = this.getStep(toStep);
            var transition = step && step.settings ? step.settings.transition : 'fade';
            var duration = step && step.settings ? step.settings.duration : 300;
            
            var $content = $('.dcf-popup-content');
            
            // Add transition class
            $content.addClass('dcf-step-transitioning dcf-transition-' + transition);
            
            setTimeout(function() {
                $content.removeClass('dcf-step-transitioning dcf-transition-' + transition);
            }, duration);
        },

        getStep: function(stepId) {
            return this.steps.find(s => s.id === stepId);
        },

        showNotification: function(message, type) {
            var $notification = $(`
                <div class="dcf-notification dcf-notification-${type}">
                    <span class="dashicons dashicons-${type === 'success' ? 'yes' : 'warning'}"></span>
                    ${message}
                </div>
            `);
            
            $('.dcf-visual-editor-wrapper').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.dcf-visual-editor-wrapper').length) {
            DCF_StepManager.init();
        }
    });

})(jQuery);