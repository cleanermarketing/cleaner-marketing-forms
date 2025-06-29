/**
 * Visual Popup Editor JavaScript
 *
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.DCF_VisualEditor = {
        // Editor state
        isDragging: false,
        selectedBlock: null,
        currentStep: 1,
        popupData: {
            steps: [
                {
                    id: 1,
                    name: 'Optin',
                    blocks: []
                }
            ]
        },
        undoStack: [],
        redoStack: [],
        blockInstances: {},

        init: function() {
            this.bindEvents();
            this.initializeEditor();
            this.updateBlockCategories();
            this.loadExistingData();
            this.initialized = true;
        },

        bindEvents: function() {
            // Tab navigation
            $(document).on('click', '.dcf-editor-tab', this.handleTabClick.bind(this));
            
            // Block dragging - disabled in favor of simple drag
            // this.initDragAndDrop();
            
            // Inline editing
            this.initInlineEditing();
            
            // Step navigation
            $(document).on('click', '.dcf-step-tab-vertical', this.handleStepClick.bind(this));
            $(document).on('click', '.dcf-add-step', this.handleAddStep.bind(this));
            $(document).on('click', '.dcf-delete-step', this.handleDeleteStep.bind(this));
            
            // Save functionality
            $(document).on('submit', '#visual-popup-form', this.handleSave.bind(this));
            
            // Toolbar actions
            $(document).on('click', '.dcf-editor-close', this.handleClose.bind(this));
            $(document).on('click', '.dcf-fullscreen-btn', this.toggleFullscreen.bind(this));
            
            // Undo/Redo
            $(document).on('click', '.dcf-sidebar-tool[title="Undo"]', this.undo.bind(this));
            $(document).on('click', '.dcf-sidebar-tool[title="Redo"]', this.redo.bind(this));
            
            // Block selection
            $(document).on('click', '.dcf-popup-content [data-block-id]', this.selectBlock.bind(this));
            
            // Keyboard shortcuts
            this.bindKeyboardShortcuts();
            
            // Debug button for testing
            $(document).on('click', '.dcf-editor-support', this.debugCurrentState.bind(this));
            
            // Collapsible panels
            $(document).on('click', '.dcf-panel-header', this.handlePanelToggle.bind(this));
            
            // Trigger type change
            $(document).on('change', '#ve_trigger_type', this.handleTriggerTypeChange.bind(this));
            
            // Status change listener
            $(document).on('change', '#popup_status', this.handleStatusChange.bind(this));
            
            // Device preview buttons
            $(document).on('click', '.dcf-device-btn', this.handleDevicePreview.bind(this));
            
            // Mobile preview tool button
            $(document).on('click', '.dcf-sidebar-tool[title="Mobile Preview"]', this.toggleMobilePreview.bind(this));
        },

        initializeEditor: function() {
            // Add editor class to body
            $('body').addClass('dcf-visual-editor-active');
            
            // Initialize content editable areas
            this.makeContentEditable();
            
            // Update block categories
            this.updateBlockCategories();
            
            // Set initial step
            this.showStep(1);
            
            // Prevent form validation on preview fields
            this.disablePreviewFormValidation();
            
            // Initialize trigger settings
            $('#ve_trigger_type').trigger('change');
            
            // Initialize panels (first panel open by default)
            $('.dcf-settings-panel').first().removeClass('dcf-collapsed');
        },

        initDragAndDrop: function() {
            var self = this;
            
            // Make blocks draggable
            $('.dcf-block-item').attr('draggable', true);
            
            // Drag start
            $(document).on('dragstart', '.dcf-block-item', function(e) {
                self.isDragging = true;
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('blockType', $(this).data('block-type'));
                $(this).addClass('dragging');
            });
            
            // Drag end
            $(document).on('dragend', '.dcf-block-item', function(e) {
                self.isDragging = false;
                $(this).removeClass('dragging');
                $('.dcf-drop-zone').remove();
            });
            
            // Drag over preview area
            $(document).on('dragover', '.dcf-popup-content', function(e) {
                e.preventDefault();
                if (self.isDragging) {
                    self.showDropZones(e);
                }
            });
            
            // Drop on preview area
            $(document).on('drop', '.dcf-popup-content', function(e) {
                e.preventDefault();
                var blockType = e.originalEvent.dataTransfer.getData('blockType');
                self.addBlock(blockType, e);
                $('.dcf-drop-zone').remove();
            });
        },

        initInlineEditing: function() {
            var self = this;
            
            // Track changes for undo/redo
            $(document).on('input', '.dcf-editable', function() {
                self.saveState();
            });
            
            // Format toolbar for text selection
            $(document).on('mouseup', '.dcf-editable', function(e) {
                var selection = window.getSelection();
                if (selection.toString().length > 0) {
                    self.showFormatToolbar(e);
                } else {
                    self.hideFormatToolbar();
                }
            });
        },

        makeContentEditable: function() {
            // Add unique IDs to blocks
            $('.dcf-popup-content > *').each(function(index) {
                if (!$(this).attr('data-block-id')) {
                    $(this).attr('data-block-id', 'block-' + Date.now() + '-' + index);
                }
            });
        },

        showDropZones: function(e) {
            $('.dcf-drop-zone').remove();
            
            var $content = $('.dcf-popup-content');
            var blocks = $content.children('[data-block-id]');
            
            if (blocks.length === 0) {
                $content.append('<div class="dcf-drop-zone"></div>');
                return;
            }
            
            blocks.each(function(index) {
                var $block = $(this);
                var rect = this.getBoundingClientRect();
                var mouseY = e.originalEvent.clientY;
                
                if (index === 0 && mouseY < rect.top + rect.height / 2) {
                    $block.before('<div class="dcf-drop-zone"></div>');
                } else if (mouseY > rect.top + rect.height / 2) {
                    $block.after('<div class="dcf-drop-zone"></div>');
                }
            });
            
            // Remove duplicate drop zones
            $('.dcf-drop-zone + .dcf-drop-zone').remove();
        },

        addBlock: function(blockType, e) {
            // This method is now primarily handled by DCF_DragDrop
            // Keep it for backward compatibility
            if (window.DCF_DragDrop && e) {
                var position = {
                    type: 'append',
                    container: $('.dcf-popup-content')[0]
                };
                window.DCF_DragDrop.dropNewBlock(blockType, position);
            } else {
                // Fallback for direct calls
                var block = DCF_BlockRegistry.create(blockType);
                if (!block) return;
                
                var blockHtml = $(block.render());
                $('.dcf-popup-content').append(blockHtml);
                
                // Store block instance
                this.blockInstances[block.id] = block;
                
                this.makeContentEditable();
                this.saveState();
            }
        },

        generateBlockHtml: function(blockType) {
            // DEPRECATED: Use DCF_BlockRegistry.create() directly
            console.warn('generateBlockHtml is deprecated. Use DCF_BlockRegistry.create() directly.');
            var block = DCF_BlockRegistry.create(blockType);
            return block ? block.render() : '<div>Unknown block type</div>';
        },

        selectBlock: function(e) {
            e.stopPropagation();
            
            $('.dcf-block-selected').removeClass('dcf-block-selected');
            $('.dcf-block-toolbar').remove();
            
            var $block = $(e.currentTarget);
            $block.addClass('dcf-block-selected');
            
            this.showBlockToolbar($block);
            this.selectedBlock = $block;
            
            // Show block settings panel
            this.showBlockSettings($block);
        },

        showBlockToolbar: function($block) {
            var toolbar = '<div class="dcf-block-toolbar">';
            toolbar += '<button class="dcf-block-tool" data-action="move-up" title="Move up"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
            toolbar += '<button class="dcf-block-tool" data-action="move-down" title="Move down"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
            toolbar += '<button class="dcf-block-tool" data-action="duplicate" title="Duplicate"><span class="dashicons dashicons-admin-page"></span></button>';
            toolbar += '<button class="dcf-block-tool" data-action="settings" title="Settings"><span class="dashicons dashicons-admin-generic"></span></button>';
            toolbar += '<button class="dcf-block-tool" data-action="delete" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
            toolbar += '</div>';
            
            $block.append(toolbar);
            
            // Bind toolbar actions
            $('.dcf-block-tool').on('click', this.handleBlockAction.bind(this));
        },

        handleBlockAction: function(e) {
            e.stopPropagation();
            
            var action = $(e.currentTarget).data('action');
            var $block = this.selectedBlock;
            
            switch(action) {
                case 'move-up':
                    var $prev = $block.prev('[data-block-id]');
                    if ($prev.length) {
                        $block.insertBefore($prev);
                    }
                    break;
                    
                case 'move-down':
                    var $next = $block.next('[data-block-id]');
                    if ($next.length) {
                        $block.insertAfter($next);
                    }
                    break;
                    
                case 'duplicate':
                    var blockId = $block.attr('data-block-id');
                    var blockType = $block.attr('data-block-type');
                    var originalBlock = this.blocks && this.blocks[blockId];
                    
                    if (originalBlock && typeof originalBlock.toJSON === 'function') {
                        // Clone using block system
                        var blockData = originalBlock.toJSON();
                        blockData.id = 'block-' + Date.now();
                        
                        var newBlock = DCF_BlockRegistry.create(blockType, blockData);
                        if (newBlock && typeof newBlock.fromJSON === 'function') {
                            newBlock.fromJSON(blockData);
                        }
                        
                        this.blocks = this.blocks || {};
                        this.blocks[newBlock.id] = newBlock;
                        
                        var $newBlock = $(newBlock.render());
                        $block.after($newBlock);
                    } else {
                        // Fallback clone method
                        var $clone = $block.clone();
                        $clone.attr('data-block-id', 'block-' + Date.now());
                        $clone.removeClass('dcf-block-selected');
                        $clone.find('.dcf-block-toolbar').remove();
                        $block.after($clone);
                    }
                    break;
                    
                case 'delete':
                    if (confirm('Are you sure you want to delete this block?')) {
                        $block.remove();
                        this.selectedBlock = null;
                        this.hideBlockSettings();
                    }
                    break;
                    
                case 'settings':
                    this.toggleBlockSettings($block);
                    break;
            }
            
            this.saveState();
        },

        showFormatToolbar: function(e) {
            // TODO: Implement text formatting toolbar
        },

        hideFormatToolbar: function() {
            $('.dcf-format-toolbar').remove();
        },

        handleTabClick: function(e) {
            e.preventDefault();
            
            var $clickedTab = $(e.currentTarget);
            var tab = $clickedTab.data('tab');
            
            // Update active tab
            $('.dcf-editor-tab').removeClass('active');
            $clickedTab.addClass('active');
            
            // Hide all tab contents
            $('.dcf-tab-content').removeClass('active');
            
            // Show selected tab content
            $('.dcf-tab-content-' + tab).addClass('active');
            
            // Special handling for design tab
            if (tab === 'design') {
                // Make sure the editor is visible
                $('.dcf-editor-main').show();
            } else {
                // For other tabs, we might want to hide the editor
                // but for now let's keep it visible
            }
            
            // Update the current status in publish tab if needed
            if (tab === 'publish') {
                var currentStatus = $('#popup_status').val();
                $('.dcf-status-draft, .dcf-status-active').removeClass('dcf-status-draft dcf-status-active');
                $('.dcf-current-status strong').addClass('dcf-status-' + currentStatus);
                $('.dcf-current-status strong').text(currentStatus === 'active' ? 'Live' : 'Draft');
            }
        },

        handleStepClick: function(e) {
            var stepId = $(e.currentTarget).data('step');
            this.showStep(stepId);
        },

        handlePrevStep: function() {
            if (this.currentStep > 1) {
                this.showStep(this.currentStep - 1);
            }
        },

        handleNextStep: function() {
            var totalSteps = this.popupData.steps.length;
            if (this.currentStep < totalSteps) {
                this.showStep(this.currentStep + 1);
            }
        },

        handleAddStep: function() {
            var newStep = {
                id: this.popupData.steps.length + 1,
                name: 'Step ' + (this.popupData.steps.length + 1),
                blocks: []
            };
            
            this.popupData.steps.push(newStep);
            this.updateStepTabs();
            this.showStep(newStep.id);
        },

        handleDeleteStep: function(e) {
            e.stopPropagation(); // Prevent triggering step click
            
            if (this.popupData.steps.length <= 1) {
                alert('You must have at least one step in your popup.');
                return;
            }
            
            var stepId = parseInt($(e.currentTarget).data('step-id'));
            
            if (!confirm('Are you sure you want to delete this step? This action cannot be undone.')) {
                return;
            }
            
            // Find the step index
            var stepIndex = this.popupData.steps.findIndex(function(s) {
                return s.id === stepId;
            });
            
            if (stepIndex === -1) return;
            
            // Save current step before deletion
            if (this.currentStep === stepId) {
                // If we're deleting the current step, switch to another step first
                var newStep = stepIndex > 0 ? this.popupData.steps[stepIndex - 1].id : this.popupData.steps[1].id;
                this.showStep(newStep);
            }
            
            // Remove the step
            this.popupData.steps.splice(stepIndex, 1);
            
            // Re-index remaining steps
            this.popupData.steps.forEach(function(step, index) {
                step.id = index + 1;
                step.name = step.name.replace(/Step \d+/, 'Step ' + (index + 1));
            });
            
            // Update current step reference if needed
            if (this.currentStep > this.popupData.steps.length) {
                this.currentStep = this.popupData.steps.length;
            }
            
            // Update the UI
            this.updateStepTabs();
            this.saveState();
        },

        showStep: function(stepId) {
            // Only save current step if we're not in initial load
            if (this.initialized) {
                this.saveCurrentStep();
            }
            
            // Update UI
            $('.dcf-step-tab-vertical').removeClass('active');
            $('.dcf-step-tab-vertical[data-step="' + stepId + '"]').addClass('active');
            
            // Load step content
            this.currentStep = stepId;
            this.loadStepContent(stepId);
            
            // Update navigation buttons
            this.updateStepNavigation();
            
            // Update step tabs to reflect current step
            this.updateStepTabs();
        },

        saveCurrentStep: function() {
            var blocks = [];
            var self = this;
            
            console.log('Saving current step, blockInstances:', this.blockInstances);
            
            $('.dcf-popup-content [data-block-id]').each(function() {
                var $block = $(this);
                var blockId = $block.attr('data-block-id');
                var blockType = $block.attr('data-block-type');
                
                console.log('Processing block:', blockId, blockType);
                
                // Get block instance or create it
                var block = self.blockInstances && self.blockInstances[blockId];
                if (!block && blockType && window.DCF_BlockRegistry) {
                    block = DCF_BlockRegistry.create(blockType, { id: blockId });
                    if (block) {
                        self.blockInstances = self.blockInstances || {};
                        self.blockInstances[blockId] = block;
                    }
                }
                
                // Save block data
                var blockData;
                if (block && typeof block.toJSON === 'function') {
                    blockData = block.toJSON();
                    // Update content from the DOM for editable blocks
                    if ($block.attr('contenteditable') === 'true') {
                        blockData.content = $block.html();
                    }
                } else {
                    // Fallback for blocks without instances (like default content)
                    blockData = {
                        id: blockId,
                        type: blockType || 'text',
                        content: $block.html().trim(),
                        attributes: {
                            class: $block.attr('class') || '',
                            style: $block.attr('style') || '',
                            placeholder: $block.attr('data-placeholder') || ''
                        },
                        settings: {}
                    };
                    
                    // Extract settings from attributes for better compatibility
                    if (blockData.attributes.style) {
                        var styles = blockData.attributes.style.split(';').filter(s => s.trim());
                        styles.forEach(style => {
                            var [prop, value] = style.split(':').map(s => s.trim());
                            if (prop === 'font-size') {
                                blockData.settings.fontSize = value;
                            } else if (prop === 'color') {
                                blockData.settings.color = value;
                            } else if (prop === 'text-align') {
                                blockData.settings.textAlign = value;
                            }
                        });
                    }
                }
                
                console.log('Block data:', blockData);
                blocks.push(blockData);
            });
            
            console.log('Collected blocks for step:', blocks);
            
            var stepIndex = this.popupData.steps.findIndex(s => s.id === this.currentStep);
            if (stepIndex !== -1) {
                this.popupData.steps[stepIndex].blocks = blocks;
                console.log('Updated step ' + this.currentStep + ' with blocks:', this.popupData.steps[stepIndex].blocks);
            } else {
                console.error('Step ' + this.currentStep + ' not found in popupData.steps');
            }
        },

        loadStepContent: function(stepId) {
            var step = this.popupData.steps.find(s => s.id === stepId);
            if (!step) return;
            
            var $content = $('.dcf-popup-content');
            
            console.log('Loading step content for step:', stepId);
            console.log('Step data:', step);
            
            // Clear any loading placeholders or existing content
            $content.find('.dcf-loading-placeholder').remove();
            $content.empty();
            
            // Clear block instances for this step
            this.blockInstances = {};
            
            if (step.blocks && step.blocks.length > 0) {
                var self = this;
                step.blocks.forEach(function(blockData) {
                    var block;
                    var blockHtml;
                    
                    // Create block instance from saved data
                    if (blockData.type && window.DCF_BlockRegistry && DCF_BlockRegistry.blocks[blockData.type]) {
                        console.log('Creating block from registry:', blockData.type, blockData);
                        console.log('Available block types:', Object.keys(DCF_BlockRegistry.blocks));
                        
                        // Create with empty options first
                        block = DCF_BlockRegistry.create(blockData.type, {});
                        
                        // Then load the saved data
                        if (block && typeof block.fromJSON === 'function') {
                            block.fromJSON(blockData);
                            console.log('Block after fromJSON:', block);
                        }
                        
                        if (block && block.id) {
                            self.blockInstances[block.id] = block;
                        }
                        
                        // Render block
                        if (block && typeof block.render === 'function') {
                            try {
                                blockHtml = block.render();
                                $content.append(blockHtml);
                                console.log('Rendered block successfully:', block.type);
                            } catch (e) {
                                console.error('Error rendering block:', block.type, e);
                                console.error('Block state:', block);
                                // Fallback to simple render
                                $content.append(self.renderFallbackBlock(blockData));
                            }
                        }
                    } else {
                        console.log('Block type not in registry, using fallback:', blockData.type);
                        console.log('Available types:', window.DCF_BlockRegistry ? Object.keys(DCF_BlockRegistry.blocks) : 'Registry not loaded');
                        // Fallback for blocks without registry (like default content blocks)
                        var elementType = 'div';
                        var elementClass = blockData.attributes && blockData.attributes.class || '';
                        
                        // Determine element type based on block type
                        if (blockData.type === 'heading') {
                            elementType = 'h2';
                        } else if (blockData.type === 'text') {
                            elementType = 'p';
                        }
                        
                        var $block = $('<' + elementType + '>');
                        $block.html(blockData.content);
                        $block.attr('data-block-id', blockData.id);
                        $block.attr('data-block-type', blockData.type);
                        
                        // Add classes
                        if (elementClass) {
                            $block.addClass(elementClass);
                        }
                        if (blockData.type !== 'fields') {
                            $block.addClass('dcf-editable');
                            $block.attr('contenteditable', 'true');
                        }
                        
                        // Add placeholder if available
                        if (blockData.attributes && blockData.attributes.placeholder) {
                            $block.attr('data-placeholder', blockData.attributes.placeholder);
                        }
                        
                        // Add style if available
                        if (blockData.attributes && blockData.attributes.style) {
                            $block.attr('style', blockData.attributes.style);
                        }
                        
                        $content.append($block);
                    }
                });
            } else {
                // Default content for new steps
                $content.html(this.getDefaultStepContent());
            }
            
            this.makeContentEditable();
        },

        getDefaultStepContent: function() {
            return `
                <h2 class="dcf-editable" contenteditable="true" data-placeholder="Enter your headline...">
                    Enter your headline here
                </h2>
                <p class="dcf-editable" contenteditable="true" data-placeholder="Enter your description...">
                    Enter your description here
                </p>
            `;
        },
        
        renderFallbackBlock: function(blockData) {
            var elementType = 'div';
            var content = blockData.content || '';
            
            if (blockData.type === 'heading') {
                elementType = 'h2';
            } else if (blockData.type === 'text') {
                elementType = 'p';
            }
            
            var $element = $('<' + elementType + '>');
            $element.html(content);
            $element.attr('data-block-id', blockData.id);
            $element.attr('data-block-type', blockData.type);
            $element.addClass('dcf-editable');
            $element.attr('contenteditable', 'true');
            
            if (blockData.attributes) {
                if (blockData.attributes.style) {
                    $element.attr('style', blockData.attributes.style);
                }
                if (blockData.attributes.placeholder) {
                    $element.attr('data-placeholder', blockData.attributes.placeholder);
                }
            }
            
            return $element[0].outerHTML;
        },

        updateStepTabs: function() {
            var $tabs = $('.dcf-step-tabs-vertical');
            $tabs.empty();
            
            var self = this;
            this.popupData.steps.forEach(function(step, index) {
                var $tab = $('<div class="dcf-step-tab-vertical" data-step="' + step.id + '">');
                var tabHtml = '<span class="dcf-step-number">' + (index + 1) + '</span>' +
                              '<span class="dcf-step-label">' + step.name + '</span>';
                
                // Add delete button if more than one step
                if (self.popupData.steps.length > 1) {
                    tabHtml += '<button type="button" class="dcf-delete-step" data-step-id="' + step.id + '" title="Delete this step">' +
                              '<span class="dashicons dashicons-trash"></span>' +
                              '</button>';
                }
                
                $tab.html(tabHtml);
                if (step.id === self.currentStep) {
                    $tab.addClass('active');
                }
                $tabs.append($tab);
            });
            
            // Update any step counter if it exists
            $('.dcf-step-counter').text(this.currentStep + ' / ' + this.popupData.steps.length);
        },

        updateStepNavigation: function() {
            var totalSteps = this.popupData.steps.length;
            
            $('#prev-step').prop('disabled', this.currentStep === 1);
            $('#next-step').prop('disabled', this.currentStep === totalSteps);
        },

        saveState: function() {
            this.undoStack.push(JSON.stringify(this.popupData));
            this.redoStack = [];
            
            // Limit undo stack size
            if (this.undoStack.length > 50) {
                this.undoStack.shift();
            }
        },

        undo: function() {
            if (this.undoStack.length > 0) {
                this.redoStack.push(JSON.stringify(this.popupData));
                var previousState = this.undoStack.pop();
                this.popupData = JSON.parse(previousState);
                this.loadStepContent(this.currentStep);
            }
        },

        redo: function() {
            if (this.redoStack.length > 0) {
                this.undoStack.push(JSON.stringify(this.popupData));
                var nextState = this.redoStack.pop();
                this.popupData = JSON.parse(nextState);
                this.loadStepContent(this.currentStep);
            }
        },

        handleSave: function(e) {
            e.preventDefault();
            
            // Save current step
            this.saveCurrentStep();
            
            console.log('=== SAVE PROCESS STARTED ===');
            console.log('Current popupData state:', this.popupData);
            console.log('Current popupData.steps:', this.popupData.steps);
            console.log('Number of blocks in step 1:', this.popupData.steps[0] ? this.popupData.steps[0].blocks.length : 0);
            
            // Get popup name from visible input first, then hidden fields
            var popupName = $('input[name="popup_name_visible"]').val() || 
                           $('.dcf-popup-name-input').val() || 
                           $('.dcf-popup-name').val() || 
                           $('#popup_name').val() || 
                           $('input[name="popup_name"]').val() || 
                           'Untitled Popup';
            
            console.log('Popup name found:', popupName);
            console.log('From visible field:', $('input[name="popup_name_visible"]').val());
            console.log('All popup name inputs:');
            console.log('  .dcf-popup-name-input:', $('.dcf-popup-name-input').length, $('.dcf-popup-name-input').val());
            console.log('  input[name="popup_name_visible"]:', $('input[name="popup_name_visible"]').length, $('input[name="popup_name_visible"]').val());
            console.log('  #popup_name:', $('#popup_name').length, $('#popup_name').val());
            
            // Update hidden popup_name field
            $('#popup_name').val(popupName);
            
            // Get popup type
            var popupType = $('.dcf-popup').data('popup-type') || 'modal';
            
            // Get status from the dropdown
            var status = $('#popup_status').val() || 'draft';
            
            // Collect trigger settings from the Display Rules tab
            var triggerSettings = {
                type: $('#ve_trigger_type').val() || 'time_delay',
                max_displays: parseInt($('#ve_max_displays').val()) || 3
            };
            
            // Add trigger-specific settings
            var triggerType = triggerSettings.type;
            if (triggerType === 'time_delay') {
                triggerSettings.delay = parseInt($('#ve_delay_seconds').val()) || 5;
            } else if (triggerType === 'scroll_depth') {
                triggerSettings.scroll_percentage = parseInt($('#ve_scroll_percentage').val()) || 50;
            } else if (triggerType === 'element_visibility') {
                triggerSettings.element_selector = $('#ve_element_selector').val() || '';
            } else if (triggerType === 'inactivity') {
                triggerSettings.inactivity_time = parseInt($('#ve_inactivity_time').val()) || 30;
            }
            
            // Collect targeting rules
            var targetingRules = {
                pages: {
                    mode: $('input[name="targeting_rules[pages][mode]"]:checked').val() || 'all'
                },
                users: {
                    login_status: $('#ve_login_status').val() || '',
                    visitor_type: $('#ve_visitor_type').val() || ''
                },
                devices: {
                    types: []
                }
            };
            
            // Collect device types
            $('input[name="targeting_rules[devices][types][]"]:checked').each(function() {
                targetingRules.devices.types.push($(this).val());
            });
            
            // Prepare the complete popup data structure
            var completePopupData = {
                popup_name: popupName,
                popup_type: popupType,
                status: status,
                visual_editor: true,
                steps: this.popupData.steps || [],
                settings: this.popupData.settings || {},
                trigger_settings: triggerSettings,
                targeting_rules: targetingRules
            };
            
            console.log('Complete popup data to save:', completePopupData);
            console.log('Total steps:', completePopupData.steps.length);
            if (completePopupData.steps[0]) {
                console.log('Step 1 blocks:', completePopupData.steps[0].blocks.length);
                completePopupData.steps[0].blocks.forEach(function(block, i) {
                    console.log('  Block ' + i + ':', block.type, '- ID:', block.id);
                });
            }
            
            // Update hidden field with stringified data
            var jsonData = JSON.stringify(completePopupData);
            console.log('JSON stringified data length:', jsonData.length);
            console.log('First 500 chars:', jsonData.substring(0, 500));
            $('#popup_data').val(jsonData);
            
            // Verify the data was set
            console.log('Hidden field value set successfully:', $('#popup_data').val().length > 0);
            
            // Also set individual form fields if they exist
            if ($('input[name="popup_name"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'popup_name',
                    value: popupName
                }).appendTo('#visual-popup-form');
            }
            
            if ($('input[name="popup_type"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'popup_type',
                    value: popupType
                }).appendTo('#visual-popup-form');
            }
            
            // Don't add a hidden status field - we already have the select field
            
            // Disable preview fields before submission
            $('.dcf-popup-content input, .dcf-popup-content select, .dcf-popup-content textarea').each(function() {
                $(this).prop('disabled', true);
            });
            
            // Log form data before submission
            console.log('Form data before submission:');
            console.log('Form action:', $('#visual-popup-form').attr('action'));
            console.log('Form method:', $('#visual-popup-form').attr('method'));
            console.log('All form inputs:');
            $('#visual-popup-form').find('input:not(:disabled), select:not(:disabled), textarea:not(:disabled)').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                var type = $(this).attr('type');
                
                if (name === 'popup_data' && value) {
                    console.log(name + ' (' + type + '): [JSON with ' + value.length + ' characters]');
                    try {
                        var parsed = JSON.parse(value);
                        console.log('popup_data parsed:', parsed);
                    } catch (e) {
                        console.log('Failed to parse popup_data:', e);
                    }
                } else {
                    console.log(name + ' (' + type + '):', value);
                }
            });
            
            // Add a flag to track submission
            $('<input>').attr({
                type: 'hidden',
                name: 'visual_editor_submission',
                value: '1'
            }).appendTo('#visual-popup-form');
            
            console.log('=== SUBMITTING FORM ===');
            console.log('Form will submit to:', $('#visual-popup-form').attr('action'));
            
            // Submit form
            $('#visual-popup-form')[0].submit();
        },

        handleClose: function() {
            if (confirm('Are you sure you want to close the editor? Any unsaved changes will be lost.')) {
                $('body').removeClass('dcf-visual-editor-active');
                window.location.href = dcf_visual_editor.admin_url;
            }
        },

        toggleFullscreen: function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        },

        showBlockSettings: function($block) {
            var blockId = $block.attr('data-block-id');
            var blockType = $block.attr('data-block-type');
            
            if (!this.blockInstances || !this.blockInstances[blockId]) {
                // Create block instance if it doesn't exist
                var block = DCF_BlockRegistry.create(blockType, { id: blockId });
                this.blockInstances = this.blockInstances || {};
                this.blockInstances[blockId] = block;
            }
            
            var block = this.blockInstances[blockId];
            if (!block) return;
            
            // Create settings panel if it doesn't exist
            var $settingsPanel = $('.dcf-block-settings-panel');
            if ($settingsPanel.length === 0) {
                $settingsPanel = $('<div class="dcf-block-settings-panel"></div>');
                $('.dcf-visual-editor-wrapper').append($settingsPanel);
            }
            
            // Render block settings
            var settingsHtml = '<div class="dcf-settings-header">';
            settingsHtml += '<h3>' + block.getName() + ' Settings</h3>';
            settingsHtml += '<button class="dcf-close-settings" title="Close"><span class="dashicons dashicons-no"></span></button>';
            settingsHtml += '</div>';
            settingsHtml += '<div class="dcf-settings-content">';
            settingsHtml += block.renderSettings();
            settingsHtml += '</div>';
            
            $settingsPanel.html(settingsHtml).addClass('active');
            
            // Bind settings events
            this.bindSettingsEvents($block, block);
        },
        
        hideBlockSettings: function() {
            $('.dcf-block-settings-panel').removeClass('active');
        },
        
        toggleBlockSettings: function($block) {
            var $panel = $('.dcf-block-settings-panel');
            if ($panel.hasClass('active')) {
                this.hideBlockSettings();
            } else {
                this.showBlockSettings($block);
            }
        },
        
        bindSettingsEvents: function($block, block) {
            var self = this;
            
            // Close button
            $('.dcf-close-settings').on('click', function() {
                self.hideBlockSettings();
            });
            
            // Handle settings changes
            $('.dcf-block-settings-panel').on('change', 'input, select, textarea', function() {
                var $input = $(this);
                var setting = $input.attr('data-setting');
                var value = $input.val();
                
                if ($input.attr('type') === 'checkbox') {
                    value = $input.is(':checked');
                }
                
                // Update block settings
                block.settings[setting] = value;
                
                // Re-render block
                var newHtml = block.render();
                var $newBlock = $(newHtml);
                $block.replaceWith($newBlock);
                
                // Update reference and re-select
                self.selectedBlock = $newBlock;
                $newBlock.addClass('dcf-block-selected');
                self.showBlockToolbar($newBlock);
                
                // Update blocks reference
                self.blockInstances[block.id] = block;
                
                self.saveState();
            });
            
            // Handle button group selections
            $('.dcf-button-group button').on('click', function() {
                var $btn = $(this);
                var setting = $btn.attr('data-setting');
                var value = $btn.attr('data-value');
                
                // Update active state
                $btn.siblings().removeAttr('data-active');
                $btn.attr('data-active', 'true');
                
                // Update block settings
                block.settings[setting] = value;
                
                // Re-render block
                var newHtml = block.render();
                var $newBlock = $(newHtml);
                $block.replaceWith($newBlock);
                
                // Update reference and re-select
                self.selectedBlock = $newBlock;
                $newBlock.addClass('dcf-block-selected');
                self.showBlockToolbar($newBlock);
                
                self.blockInstances[block.id] = block;
                self.saveState();
            });
        },
        
        disablePreviewFormValidation: function() {
            var self = this;
            
            // Function to disable validation on form fields
            function disableValidation(element) {
                $(element).removeAttr('required');
                $(element).prop('required', false);
                $(element).attr('data-preview', 'true'); // Mark as preview field
                
                // Clear validation state
                if (element.setCustomValidity) {
                    element.setCustomValidity('');
                }
                
                // Prevent default validation
                if (element.checkValidity) {
                    element.checkValidity = function() { return true; };
                }
                
                // Prevent form submission on enter
                $(element).on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Remove validation from preview form fields
            $('.dcf-popup-content').on('input change', 'input, select, textarea', function(e) {
                disableValidation(this);
            });
            
            // Use MutationObserver instead of DOMNodeInserted
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            $(node).find('input, select, textarea').each(function() {
                                disableValidation(this);
                            });
                            if ($(node).is('input, select, textarea')) {
                                disableValidation(node);
                            }
                        }
                    });
                });
            });
            
            // Start observing
            var popupContent = document.querySelector('.dcf-popup-content');
            if (popupContent) {
                observer.observe(popupContent, {
                    childList: true,
                    subtree: true
                });
            }
            
            // Disable any existing fields
            $('.dcf-popup-content input, .dcf-popup-content select, .dcf-popup-content textarea').each(function() {
                disableValidation(this);
            });
        },
        
        updateBlockCategories: function() {
            if (typeof DCF_BlockRegistry === 'undefined') return;
            
            var categories = DCF_BlockRegistry.getCategories();
            var $blockList = $('.dcf-blocks-list');
            
            $blockList.empty();
            
            // Default category order
            var categoryOrder = ['standard', 'advanced'];
            var categoryNames = {
                'standard': 'Standard Blocks',
                'advanced': 'Advanced Blocks'
            };
            
            categoryOrder.forEach(function(categoryKey) {
                if (categories[categoryKey]) {
                    var $category = $('<div class="dcf-block-category">');
                    $category.append('<h4>' + (categoryNames[categoryKey] || categoryKey) + '</h4>');
                    
                    var $items = $('<div class="dcf-blocks-grid">');
                    categories[categoryKey].forEach(function(block) {
                        var $item = $('<div class="dcf-block-item" draggable="true" data-block-type="' + block.type + '">');
                        $item.append('<span class="dashicons ' + block.icon + '"></span>');
                        $item.append('<span>' + block.name + '</span>');
                        $items.append($item);
                    });
                    
                    $category.append($items);
                    $blockList.append($category);
                }
            });
            
            // Trigger event to reinitialize drag and drop
            $(document).trigger('dcf:blocks-updated');
        },
        
        bindKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S = Save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    $('#visual-popup-form').submit();
                }
                
                // Ctrl/Cmd + Z = Undo
                if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                }
                
                // Ctrl/Cmd + Shift + Z = Redo
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') {
                    e.preventDefault();
                    this.redo();
                }
                
                // Delete key when block is selected
                if (e.key === 'Delete' && this.selectedBlock) {
                    e.preventDefault();
                    this.selectedBlock.remove();
                    this.selectedBlock = null;
                    this.saveState();
                }
            }.bind(this));
        },

        loadExistingData: function() {
            // Load existing popup data if editing
            var existingData = $('#popup_data').val();
            console.log('Loading existing data from hidden field:', existingData);
            console.log('Current URL:', window.location.href);
            console.log('Popup ID from form:', $('input[name="popup_id"]').val());
            
            if (existingData) {
                try {
                    var parsedData = JSON.parse(existingData);
                    console.log('Parsed existing data:', parsedData);
                    
                    // The data should already be in the correct format from PHP
                    if (parsedData.steps && parsedData.steps.length > 0) {
                        this.popupData = parsedData;
                        console.log('Loaded visual editor data with steps');
                    } else {
                        console.log('No steps found in parsed data, using defaults');
                        // Keep existing default structure
                    }
                    
                    console.log('Final popupData structure:', this.popupData);
                    this.updateStepTabs();
                    this.showStep(1);
                } catch (e) {
                    console.error('Error loading popup data:', e);
                    console.error('Raw data that failed to parse:', existingData);
                }
            } else {
                console.log('No existing data found in hidden field');
            }
        },
        
        debugCurrentState: function() {
            console.log('=== DEBUG: Current Visual Editor State ===');
            console.log('Popup ID:', $('input[name="popup_id"]').val());
            console.log('Current popupData:', this.popupData);
            console.log('Block instances:', this.blockInstances);
            console.log('Current step:', this.currentStep);
            console.log('Hidden field value:', $('#popup_data').val());
            
            // Save current step and show the data
            this.saveCurrentStep();
            console.log('After saveCurrentStep, popupData:', this.popupData);
            
            // Test database directly
            if ($('input[name="popup_id"]').val()) {
                console.log('Use this URL to check database state:');
                console.log(window.location.origin + '/wp-content/plugins/dry-cleaning-forms/test-popup-visual-data.php?popup_id=' + $('input[name="popup_id"]').val());
            }
        },
        
        handlePanelToggle: function(e) {
            e.preventDefault();
            var $panel = $(e.currentTarget).closest('.dcf-settings-panel');
            $panel.toggleClass('dcf-collapsed');
        },
        
        handleTriggerTypeChange: function(e) {
            var triggerType = $(e.target).val();
            var $specificSettings = $('#ve_trigger_specific_settings');
            var $description = $('#ve_trigger_description');
            
            // Clear existing settings
            $specificSettings.empty();
            
            // Update description based on trigger type
            var triggerDescriptions = {
                'time_delay': 'Show popup after a specified delay',
                'exit_intent': 'Show when user tries to leave the page',
                'scroll_depth': 'Show after scrolling to a certain percentage',
                'element_visibility': 'Show when a specific element becomes visible',
                'inactivity': 'Show after user is inactive for a period'
            };
            
            $description.text(triggerDescriptions[triggerType] || '');
            
            // Add trigger-specific settings
            switch(triggerType) {
                case 'time_delay':
                    $specificSettings.html(`
                        <div class="dcf-setting-group">
                            <label for="ve_delay_seconds">Delay (seconds)</label>
                            <input type="number" name="trigger_settings[delay]" id="ve_delay_seconds" 
                                   value="5" min="1" max="300" class="dcf-setting-control">
                            <p class="dcf-setting-description">Time to wait before showing popup</p>
                        </div>
                    `);
                    break;
                    
                case 'scroll_depth':
                    $specificSettings.html(`
                        <div class="dcf-setting-group">
                            <label for="ve_scroll_percentage">Scroll Percentage</label>
                            <input type="number" name="trigger_settings[scroll_percentage]" id="ve_scroll_percentage" 
                                   value="50" min="10" max="100" step="10" class="dcf-setting-control">
                            <p class="dcf-setting-description">Show popup after scrolling this percentage</p>
                        </div>
                    `);
                    break;
                    
                case 'element_visibility':
                    $specificSettings.html(`
                        <div class="dcf-setting-group">
                            <label for="ve_element_selector">Element Selector</label>
                            <input type="text" name="trigger_settings[element_selector]" id="ve_element_selector" 
                                   placeholder="#my-element or .my-class" class="dcf-setting-control">
                            <p class="dcf-setting-description">CSS selector for the element to watch</p>
                        </div>
                    `);
                    break;
                    
                case 'inactivity':
                    $specificSettings.html(`
                        <div class="dcf-setting-group">
                            <label for="ve_inactivity_time">Inactivity Time (seconds)</label>
                            <input type="number" name="trigger_settings[inactivity_time]" id="ve_inactivity_time" 
                                   value="30" min="5" max="600" class="dcf-setting-control">
                            <p class="dcf-setting-description">Time of no mouse/keyboard activity</p>
                        </div>
                    `);
                    break;
            }
        },
        
        handleStatusChange: function(e) {
            var status = $(e.target).val();
            console.log('Status changed to:', status);
            
            // Update any UI elements that show status
            $('.dcf-status-draft, .dcf-status-active').removeClass('dcf-status-draft dcf-status-active');
            $('.dcf-current-status strong').addClass('dcf-status-' + status);
            $('.dcf-current-status strong').text(status === 'active' ? 'Live' : 'Draft');
            
            // If on publish tab, update immediately
            if ($('.dcf-editor-tab[data-tab="publish"]').hasClass('active')) {
                this.handleTabClick({ 
                    currentTarget: $('.dcf-editor-tab[data-tab="publish"]')[0],
                    preventDefault: function() {}
                });
            }
        },
        
        /**
         * Handle device preview button clicks
         */
        handleDevicePreview: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var device = $button.data('device');
            
            // Update active button state
            $('.dcf-device-btn').removeClass('active');
            $button.addClass('active');
            
            // Update preview container
            var $previewContainer = $('.dcf-preview-container');
            $previewContainer.attr('data-device', device);
            
            // Apply device-specific styles to the preview
            var $popupPreview = $('.dcf-popup-preview');
            
            // Remove all device classes
            $popupPreview.removeClass('dcf-preview-desktop dcf-preview-tablet dcf-preview-mobile');
            
            // Add new device class
            $popupPreview.addClass('dcf-preview-' + device);
            
            // Update preview dimensions
            switch(device) {
                case 'mobile':
                    $popupPreview.css({
                        'max-width': '375px',
                        'margin': '0 auto',
                        'padding': '10px',
                        'background': '#f5f5f5',
                        'border': 'none',
                        'border-radius': '0',
                        'box-shadow': 'none',
                        'min-height': '667px' // iPhone 6/7/8 height
                    });
                    
                    // Adjust popup styles to match mobile reality
                    $('.dcf-popup', $popupPreview).css({
                        'width': 'calc(100% - 20px)',
                        'max-width': 'none',
                        'margin': '10px'
                    });
                    break;
                case 'tablet':
                    $popupPreview.css({
                        'max-width': '768px',
                        'margin': '0 auto',
                        'padding': '20px',
                        'background': '#f5f5f5',
                        'border': 'none',
                        'border-radius': '0',
                        'box-shadow': 'none',
                        'min-height': '1024px' // iPad height
                    });
                    
                    // Adjust popup styles to match tablet reality
                    $('.dcf-popup', $popupPreview).css({
                        'width': 'calc(100% - 40px)',
                        'max-width': 'none',
                        'margin': '20px'
                    });
                    break;
                case 'desktop':
                default:
                    $popupPreview.css({
                        'max-width': '100%',
                        'margin': '0',
                        'padding': '0',
                        'background': 'transparent',
                        'border': 'none',
                        'border-radius': '0',
                        'box-shadow': 'none',
                        'min-height': 'auto'
                    });
                    
                    // Reset popup styles
                    $('.dcf-popup', $popupPreview).css({
                        'width': '',
                        'max-width': '',
                        'margin': ''
                    });
                    break;
            }
            
            console.log('Device preview changed to:', device);
        },
        
        /**
         * Toggle mobile preview mode
         */
        toggleMobilePreview: function(e) {
            e.preventDefault();
            
            // Find the mobile device button and click it
            var $mobileBtn = $('.dcf-device-btn[data-device="mobile"]');
            if ($mobileBtn.hasClass('active')) {
                // If mobile is active, switch to desktop
                $('.dcf-device-btn[data-device="desktop"]').click();
            } else {
                // Switch to mobile
                $mobileBtn.click();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.dcf-visual-editor-wrapper').length) {
            DCF_VisualEditor.init();
        }
    });

})(jQuery);