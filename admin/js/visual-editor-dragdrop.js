/**
 * Enhanced Drag and Drop functionality for Visual Editor
 *
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.DCF_DragDrop = {
        isDragging: false,
        draggedElement: null,
        draggedBlock: null,
        dropIndicator: null,
        originalIndex: -1,
        placeholderElement: null,
        dragCounter: 0,

        init: function() {
            this.createDropIndicator();
            this.bindEvents();
        },

        createDropIndicator: function() {
            this.dropIndicator = $('<div class="dcf-drop-indicator"><div class="dcf-drop-line"></div></div>');
            $('body').append(this.dropIndicator);
        },

        bindEvents: function() {
            var self = this;
            
            // Prevent default drag behavior on document to allow drops
            $(document).on('dragover', function(e) {
                e.preventDefault();
            });
            
            $(document).on('drop', function(e) {
                e.preventDefault();
            });

            // Make sidebar blocks draggable
            $(document).on('dragstart', '.dcf-block-item', function(e) {
                self.handleBlockDragStart(e, this, 'new');
            });

            // Make existing blocks in preview draggable
            $(document).on('mouseenter', '.dcf-popup-content [data-block-id]', function() {
                if (!$(this).attr('draggable')) {
                    $(this).attr('draggable', true);
                    $(this).addClass('dcf-draggable-block');
                }
            });

            $(document).on('dragstart', '.dcf-popup-content [data-block-id]', function(e) {
                self.handleBlockDragStart(e, this, 'existing');
            });

            // Handle drag events
            $(document).on('dragend', '.dcf-block-item, .dcf-popup-content [data-block-id]', function(e) {
                self.handleDragEnd(e);
            });

            // Bind to both the content area and its children
            $(document).on('dragenter', '.dcf-popup-content, .dcf-popup-content *', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(e.target).closest('.dcf-popup-content').length) {
                    self.dragCounter++;
                    console.log('dragenter event, counter:', self.dragCounter);
                    $('.dcf-popup-content').addClass('dcf-drag-over');
                }
            });
            
            $(document).on('dragover', '.dcf-popup-content, .dcf-popup-content *', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(e.target).closest('.dcf-popup-content').length) {
                    self.handleDragOver(e);
                }
            });

            $(document).on('drop', '.dcf-popup-content, .dcf-popup-content *', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(e.target).closest('.dcf-popup-content').length) {
                    console.log('drop event on element within .dcf-popup-content');
                    self.dragCounter = 0;
                    self.handleDrop(e);
                }
            });

            $(document).on('dragleave', '.dcf-popup-content, .dcf-popup-content *', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($(e.relatedTarget).closest('.dcf-popup-content').length === 0) {
                    self.dragCounter = 0;
                    console.log('Actually leaving popup content area');
                    $('.dcf-popup-content').removeClass('dcf-drag-over');
                    self.handleDragLeave(e);
                }
            });
            
            // Also bind to document to catch any drops
            $(document).on('drop', function(e) {
                console.log('drop event on document, target:', e.target);
                if ($(e.target).closest('.dcf-popup-content').length) {
                    console.log('Drop is within .dcf-popup-content');
                }
            });

            // Visual feedback for draggable blocks
            $(document).on('mouseenter', '.dcf-draggable-block', function() {
                $(this).addClass('dcf-draggable-hover');
            });

            $(document).on('mouseleave', '.dcf-draggable-block', function() {
                $(this).removeClass('dcf-draggable-hover');
            });
        },

        handleBlockDragStart: function(e, element, type) {
            this.isDragging = true;
            this.draggedElement = $(element);
            
            // Check if dataTransfer exists (native drag event)
            var dataTransfer = e.originalEvent ? e.originalEvent.dataTransfer : e.dataTransfer;
            if (!dataTransfer) {
                console.error('No dataTransfer object found - this might be a jQuery UI drag event');
                return;
            }
            
            dataTransfer.effectAllowed = 'all'; // Allow all effects for better compatibility
            
            // Set drag image if possible
            if (dataTransfer.setDragImage && e.originalEvent) {
                dataTransfer.setDragImage(element, e.originalEvent.offsetX || 0, e.originalEvent.offsetY || 0);
            }
            
            if (type === 'new') {
                var blockType = $(element).data('block-type') || $(element).attr('data-block-type');
                if (!blockType) {
                    console.error('Block type not found on element:', element);
                    return;
                }
                console.log('Dragging new block of type:', blockType);
                
                // Set data in multiple formats for compatibility
                // Use text/plain as the primary format for better browser compatibility
                try {
                    dataTransfer.setData('text/plain', blockType + '|new');
                    // Try to set custom types if supported
                    try {
                        dataTransfer.setData('blockType', blockType);
                        dataTransfer.setData('dragType', 'new');
                    } catch (customErr) {
                        console.log('Browser does not support custom dataTransfer types');
                    }
                } catch (err) {
                    console.error('Failed to set any drag data:', err);
                }
            } else {
                var blockId = $(element).data('block-id');
                try {
                    dataTransfer.setData('blockId', blockId);
                    dataTransfer.setData('dragType', 'existing');
                } catch (err) {
                    console.error('Failed to set drag data for existing block:', err);
                }
                
                // Store original position
                this.originalIndex = $(element).index();
                
                // Create placeholder
                this.createPlaceholder(element);
                
                // Add dragging class
                $(element).addClass('dcf-block-dragging');
            }

            $(element).addClass('dragging');
            $('.dcf-popup-content').addClass('dcf-drag-active');
        },

        handleDragEnd: function(e) {
            console.log('handleDragEnd called');
            this.isDragging = false;
            this.dragCounter = 0; // Reset counter
            
            $('.dragging').removeClass('dragging');
            $('.dcf-block-dragging').removeClass('dcf-block-dragging');
            $('.dcf-drag-active').removeClass('dcf-drag-active');
            $('.dcf-drag-over').removeClass('dcf-drag-over');
            
            this.dropIndicator.hide();
            
            if (this.placeholderElement) {
                this.placeholderElement.remove();
                this.placeholderElement = null;
            }
            
            this.draggedElement = null;
            this.originalIndex = -1;
        },

        handleDragOver: function(e) {
            if (!this.isDragging) return;
            
            e.preventDefault();
            
            // Set drop effect if dataTransfer is available
            var dataTransfer = e.originalEvent ? e.originalEvent.dataTransfer : e.dataTransfer;
            if (dataTransfer) {
                dataTransfer.dropEffect = 'copy'; // Use 'copy' for new blocks
            }
            
            // Calculate drop position
            var dropPosition = this.calculateDropPosition(e);
            
            if (dropPosition) {
                this.showDropIndicator(dropPosition);
            }
        },

        handleDrop: function(e) {
            console.log('handleDrop called, isDragging:', this.isDragging);
            if (!this.isDragging) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            var dataTransfer = e.originalEvent ? e.originalEvent.dataTransfer : e.dataTransfer;
            if (!dataTransfer) {
                console.error('No dataTransfer in drop event');
                return;
            }
            
            // Try to get text/plain data first (most compatible)
            var textData = dataTransfer.getData('text/plain') || dataTransfer.getData('text');
            console.log('Text data from dataTransfer:', textData);
            
            var dragType = '';
            var blockType = '';
            
            // Parse text data if available
            if (textData && textData.includes('|')) {
                var parts = textData.split('|');
                blockType = parts[0];
                dragType = parts[1];
            } else if (textData) {
                blockType = textData;
                dragType = 'new';
            }
            
            // Try custom data types as fallback
            if (!dragType) {
                dragType = dataTransfer.getData('dragType');
            }
            if (!blockType) {
                blockType = dataTransfer.getData('blockType');
            }
            
            console.log('Parsed values - dragType:', dragType, 'blockType:', blockType);
            
            var dropPosition = this.calculateDropPosition(e);
            console.log('Drop position:', dropPosition);
            
            console.log('Final values - dragType:', dragType, 'blockType:', blockType);
            
            if (!dropPosition) {
                console.log('No drop position found');
                return;
            }
            
            // If no dragType but we have blockType, assume it's a new block
            if (!dragType && blockType) {
                dragType = 'new';
            }
            
            if (dragType === 'new' && blockType) {
                console.log('Dropping new block of type:', blockType);
                this.dropNewBlock(blockType, dropPosition);
            } else if (dragType === 'existing') {
                // Handle existing block reorder
                var blockId = dataTransfer.getData('blockId');
                this.reorderBlock(blockId, dropPosition);
            }
            
            this.handleDragEnd(e);
        },

        handleDragLeave: function(e) {
            // Only hide indicator if leaving the content area entirely
            if (e.target === $('.dcf-popup-content')[0]) {
                this.dropIndicator.hide();
            }
        },

        calculateDropPosition: function(e) {
            var $content = $('.dcf-popup-content');
            var blocks = $content.children('[data-block-id]').not('.dcf-block-dragging');
            
            if (blocks.length === 0) {
                return {
                    type: 'append',
                    container: $content[0]
                };
            }
            
            var mouseY = e.originalEvent.clientY;
            var closestBlock = null;
            var position = 'after';
            var minDistance = Infinity;
            
            blocks.each(function() {
                var rect = this.getBoundingClientRect();
                var blockMiddle = rect.top + rect.height / 2;
                var distance = Math.abs(mouseY - blockMiddle);
                
                if (distance < minDistance) {
                    minDistance = distance;
                    closestBlock = this;
                    position = mouseY < blockMiddle ? 'before' : 'after';
                }
            });
            
            return {
                type: position,
                element: closestBlock
            };
        },

        showDropIndicator: function(position) {
            if (position.type === 'append') {
                var $container = $(position.container);
                var rect = position.container.getBoundingClientRect();
                this.dropIndicator.css({
                    top: rect.bottom - 2,
                    left: rect.left,
                    width: rect.width,
                    display: 'block'
                });
            } else {
                var $element = $(position.element);
                var rect = position.element.getBoundingClientRect();
                var top = position.type === 'before' ? rect.top - 2 : rect.bottom - 2;
                
                this.dropIndicator.css({
                    top: top,
                    left: rect.left,
                    width: rect.width,
                    display: 'block'
                });
            }
        },

        createPlaceholder: function(element) {
            var $element = $(element);
            var height = $element.outerHeight();
            
            this.placeholderElement = $('<div class="dcf-block-placeholder"></div>');
            this.placeholderElement.css({
                height: height,
                marginTop: $element.css('margin-top'),
                marginBottom: $element.css('margin-bottom')
            });
            
            $element.after(this.placeholderElement);
        },

        dropNewBlock: function(blockType, position) {
            console.log('dropNewBlock called with blockType:', blockType, 'position:', position);
            
            // Validate block type
            if (!blockType || blockType === 'undefined') {
                console.error('Invalid block type:', blockType);
                return;
            }
            
            // Check if DCF_BlockRegistry exists
            if (typeof DCF_BlockRegistry === 'undefined') {
                console.error('DCF_BlockRegistry is not defined');
                return;
            }
            
            console.log('Available block types:', Object.keys(DCF_BlockRegistry.blocks));
            
            // Create new block
            var block = DCF_BlockRegistry.create(blockType);
            if (!block) {
                console.error('Failed to create block of type:', blockType);
                console.log('DCF_BlockRegistry.blocks:', DCF_BlockRegistry.blocks);
                return;
            }
            
            console.log('Block created successfully:', block);
            
            var blockHtml = $(block.render());
            
            // Insert at position
            if (position.type === 'append') {
                $(position.container).append(blockHtml);
            } else if (position.type === 'before') {
                $(position.element).before(blockHtml);
            } else {
                $(position.element).after(blockHtml);
            }
            
            // Store block instance
            if (window.DCF_VisualEditor) {
                if (!window.DCF_VisualEditor.blockInstances) {
                    window.DCF_VisualEditor.blockInstances = {};
                }
                window.DCF_VisualEditor.blockInstances[block.id] = block;
                
                // Make content editable
                window.DCF_VisualEditor.makeContentEditable();
                
                // Save state
                window.DCF_VisualEditor.saveState();
                
                // Add entrance animation
                blockHtml.addClass('dcf-block-entrance');
                setTimeout(function() {
                    blockHtml.removeClass('dcf-block-entrance');
                }, 300);
                
                // If it's a form block, automatically show settings
                if (blockType === 'form') {
                    setTimeout(function() {
                        blockHtml.trigger('click');
                    }, 100);
                }
            }
        },

        reorderBlock: function(blockId, position) {
            var $block = $('[data-block-id="' + blockId + '"]');
            if (!$block.length) return;
            
            // Remove placeholder
            if (this.placeholderElement) {
                this.placeholderElement.remove();
            }
            
            // Move block to new position
            if (position.type === 'append') {
                $(position.container).append($block);
            } else if (position.type === 'before') {
                $(position.element).before($block);
            } else {
                $(position.element).after($block);
            }
            
            // Add reorder animation
            $block.addClass('dcf-block-reorder');
            setTimeout(function() {
                $block.removeClass('dcf-block-reorder');
            }, 300);
            
            // Save state
            if (window.DCF_VisualEditor) {
                window.DCF_VisualEditor.saveState();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DCF_DragDrop.init();
        console.log('DCF_DragDrop initialized');
        
        // Debug - check if registry is available
        setTimeout(function() {
            if (typeof DCF_BlockRegistry !== 'undefined') {
                console.log('DCF_BlockRegistry is available with blocks:', Object.keys(DCF_BlockRegistry.blocks));
            } else {
                console.error('DCF_BlockRegistry is NOT available!');
            }
        }, 1000);
    });

})(jQuery);