/**
 * Visual Editor Fix
 * Patches for block management
 */

(function($) {
    'use strict';
    
    // Wait for visual editor to be initialized
    $(document).ready(function() {
        if (window.DCF_VisualEditor) {
            console.log('Applying Visual Editor fixes...');
            
            // Override the makeContentEditable function to ensure blocks are tracked
            var originalMakeContentEditable = window.DCF_VisualEditor.makeContentEditable;
            window.DCF_VisualEditor.makeContentEditable = function() {
                // Call original
                if (originalMakeContentEditable) {
                    originalMakeContentEditable.call(this);
                }
                
                // Ensure all blocks have IDs and are tracked
                $('.dcf-popup-content [data-block-type]').each(function() {
                    var $block = $(this);
                    var blockId = $block.attr('data-block-id');
                    var blockType = $block.attr('data-block-type');
                    
                    // Generate ID if missing
                    if (!blockId) {
                        blockId = 'block-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                        $block.attr('data-block-id', blockId);
                    }
                    
                    // Ensure block instance exists
                    if (blockType && !window.DCF_VisualEditor.blockInstances[blockId]) {
                        var block = DCF_BlockRegistry.create(blockType, { id: blockId });
                        if (block) {
                            window.DCF_VisualEditor.blockInstances[blockId] = block;
                            console.log('Created missing block instance:', blockType, blockId);
                        }
                    }
                });
            };
            
            // Fix for drag and drop
            if (window.DCF_DragDrop) {
                var originalDropNewBlock = window.DCF_DragDrop.dropNewBlock;
                window.DCF_DragDrop.dropNewBlock = function(blockType, position) {
                    console.log('Dropping new block:', blockType, position);
                    
                    // Call original
                    originalDropNewBlock.call(this, blockType, position);
                    
                    // Ensure state is saved
                    setTimeout(function() {
                        if (window.DCF_VisualEditor && window.DCF_VisualEditor.saveCurrentStep) {
                            window.DCF_VisualEditor.saveCurrentStep();
                            console.log('Step saved after drop');
                        }
                    }, 100);
                };
            }
            
            console.log('Visual Editor fixes applied');
        }
    });
    
})(jQuery);