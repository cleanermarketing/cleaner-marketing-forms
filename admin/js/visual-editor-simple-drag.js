/**
 * Simple Drag and Drop for Visual Editor
 * Alternative implementation with better browser compatibility
 */

(function($) {
    'use strict';
    
    window.DCF_SimpleDrag = {
        draggedBlockType: null,
        
        init: function() {
            var self = this;
            
            // Make sidebar blocks draggable using jQuery UI
            $('.dcf-block-item').draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 10000,
                connectToSortable: '.dcf-popup-content',
                start: function(event, ui) {
                    var blockType = $(this).data('block-type');
                    self.draggedBlockType = blockType;
                    console.log('Started dragging block type:', blockType);
                    ui.helper.css('opacity', '0.8');
                },
                stop: function(event, ui) {
                    console.log('Stopped dragging');
                }
            });
            
            // Make the popup content area droppable
            $('.dcf-popup-content').droppable({
                accept: '.dcf-block-item',
                hoverClass: 'dcf-drag-over',
                drop: function(event, ui) {
                    console.log('Dropped block type:', self.draggedBlockType);
                    
                    if (!self.draggedBlockType) {
                        console.error('No block type found');
                        return;
                    }
                    
                    // Create the block
                    var block = DCF_BlockRegistry.create(self.draggedBlockType);
                    if (!block) {
                        console.error('Failed to create block of type:', self.draggedBlockType);
                        return;
                    }
                    
                    // Render and add the block
                    var blockHtml = $(block.render());
                    $(this).append(blockHtml);
                    
                    // Store block instance
                    if (window.DCF_VisualEditor) {
                        window.DCF_VisualEditor.blockInstances[block.id] = block;
                        
                        // Make content editable
                        window.DCF_VisualEditor.makeContentEditable();
                        
                        // Save state
                        window.DCF_VisualEditor.saveState();
                        
                        console.log('Block added successfully:', block.id);
                    }
                    
                    // Reset
                    self.draggedBlockType = null;
                }
            });
            
            // Make existing blocks sortable
            $('.dcf-popup-content').sortable({
                items: '[data-block-id]',
                placeholder: 'dcf-block-placeholder',
                handle: false,
                tolerance: 'pointer',
                start: function(event, ui) {
                    ui.placeholder.height(ui.item.height());
                },
                update: function(event, ui) {
                    // Save state after reordering
                    if (window.DCF_VisualEditor) {
                        window.DCF_VisualEditor.saveState();
                    }
                }
            });
            
            console.log('Simple drag and drop initialized');
        }
    };
    
    // Initialize when ready
    $(document).ready(function() {
        // Check if native drag and drop is already initialized
        if (window.DCF_DragDrop && window.DCF_DragDrop.init) {
            console.log('Native drag and drop is available, skipping jQuery UI implementation');
            return;
        }
        
        // Wait a bit for visual editor to initialize
        setTimeout(function() {
            if ($('.dcf-visual-editor-wrapper').length) {
                DCF_SimpleDrag.init();
                
                // Re-initialize when new blocks are added
                $(document).on('dcf:blocks-updated', function() {
                    $('.dcf-block-item').draggable('destroy');
                    DCF_SimpleDrag.init();
                });
            }
        }, 500);
    });
    
})(jQuery);