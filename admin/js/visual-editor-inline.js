/**
 * Enhanced Inline Editing for Visual Editor
 *
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    window.DCF_InlineEditor = {
        toolbar: null,
        currentElement: null,
        savedSelection: null,

        init: function() {
            this.createToolbar();
            this.bindEvents();
        },

        createToolbar: function() {
            this.toolbar = $(`
                <div class="dcf-inline-toolbar">
                    <div class="dcf-toolbar-group">
                        <button class="dcf-toolbar-btn" data-command="bold" title="Bold">
                            <span class="dashicons dashicons-editor-bold"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="italic" title="Italic">
                            <span class="dashicons dashicons-editor-italic"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="underline" title="Underline">
                            <span class="dashicons dashicons-editor-underline"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="strikethrough" title="Strikethrough">
                            <span class="dashicons dashicons-editor-strikethrough"></span>
                        </button>
                    </div>
                    <div class="dcf-toolbar-separator"></div>
                    <div class="dcf-toolbar-group">
                        <button class="dcf-toolbar-btn" data-command="alignLeft" title="Align Left">
                            <span class="dashicons dashicons-editor-alignleft"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="alignCenter" title="Align Center">
                            <span class="dashicons dashicons-editor-aligncenter"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="alignRight" title="Align Right">
                            <span class="dashicons dashicons-editor-alignright"></span>
                        </button>
                    </div>
                    <div class="dcf-toolbar-separator"></div>
                    <div class="dcf-toolbar-group">
                        <button class="dcf-toolbar-btn" data-command="link" title="Insert Link">
                            <span class="dashicons dashicons-admin-links"></span>
                        </button>
                        <button class="dcf-toolbar-btn" data-command="removeFormat" title="Clear Formatting">
                            <span class="dashicons dashicons-editor-removeformatting"></span>
                        </button>
                    </div>
                    <div class="dcf-toolbar-group dcf-text-size-group">
                        <select class="dcf-text-size-select">
                            <option value="">Size</option>
                            <option value="1">Small</option>
                            <option value="3">Normal</option>
                            <option value="5">Large</option>
                            <option value="7">Huge</option>
                        </select>
                    </div>
                </div>
            `);
            
            $('body').append(this.toolbar);
        },

        bindEvents: function() {
            var self = this;

            // Handle text selection
            $(document).on('mouseup keyup', '.dcf-editable', function(e) {
                self.handleSelection(e, this);
            });

            // Handle toolbar commands
            $(document).on('click', '.dcf-toolbar-btn', function(e) {
                e.preventDefault();
                var command = $(this).data('command');
                self.executeCommand(command);
            });

            // Handle text size change
            $(document).on('change', '.dcf-text-size-select', function() {
                var size = $(this).val();
                if (size) {
                    self.executeCommand('fontSize', size);
                }
                $(this).val(''); // Reset select
            });

            // Handle focus and blur
            $(document).on('focus', '.dcf-editable', function() {
                self.currentElement = this;
                self.syncWithBlockSettings(this);
            });

            $(document).on('blur', '.dcf-editable', function() {
                setTimeout(function() {
                    if (!self.toolbar.is(':hover')) {
                        self.hideToolbar();
                    }
                }, 200);
                self.updateBlockContent(this);
            });

            // Handle input changes
            $(document).on('input', '.dcf-editable', function() {
                self.handleContentChange(this);
            });

            // Prevent toolbar from losing focus
            this.toolbar.on('mousedown', function(e) {
                e.preventDefault();
            });

            // Hide toolbar on document click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dcf-editable, .dcf-inline-toolbar').length) {
                    self.hideToolbar();
                }
            });

            // Handle paste events
            $(document).on('paste', '.dcf-editable', function(e) {
                self.handlePaste(e);
            });
        },

        handleSelection: function(e, element) {
            var selection = window.getSelection();
            
            if (selection.rangeCount > 0 && !selection.isCollapsed) {
                this.showToolbar(selection, element);
                this.updateToolbarState();
            } else {
                this.hideToolbar();
            }
        },

        showToolbar: function(selection, element) {
            var range = selection.getRangeAt(0);
            var rect = range.getBoundingClientRect();
            
            // Position toolbar above selection
            var top = rect.top - this.toolbar.outerHeight() - 10;
            var left = rect.left + (rect.width / 2) - (this.toolbar.outerWidth() / 2);
            
            // Ensure toolbar stays within viewport
            if (top < 10) {
                top = rect.bottom + 10;
            }
            if (left < 10) {
                left = 10;
            }
            if (left + this.toolbar.outerWidth() > $(window).width() - 10) {
                left = $(window).width() - this.toolbar.outerWidth() - 10;
            }
            
            this.toolbar.css({
                top: top + 'px',
                left: left + 'px',
                display: 'block'
            });
            
            this.currentElement = element;
            this.savedSelection = this.saveSelection();
        },

        hideToolbar: function() {
            this.toolbar.hide();
        },

        executeCommand: function(command, value) {
            // Restore selection
            if (this.savedSelection) {
                this.restoreSelection(this.savedSelection);
            }
            
            switch(command) {
                case 'bold':
                    document.execCommand('bold', false, null);
                    break;
                case 'italic':
                    document.execCommand('italic', false, null);
                    break;
                case 'underline':
                    document.execCommand('underline', false, null);
                    break;
                case 'strikethrough':
                    document.execCommand('strikeThrough', false, null);
                    break;
                case 'alignLeft':
                    document.execCommand('justifyLeft', false, null);
                    break;
                case 'alignCenter':
                    document.execCommand('justifyCenter', false, null);
                    break;
                case 'alignRight':
                    document.execCommand('justifyRight', false, null);
                    break;
                case 'link':
                    this.insertLink();
                    break;
                case 'removeFormat':
                    document.execCommand('removeFormat', false, null);
                    break;
                case 'fontSize':
                    document.execCommand('fontSize', false, value);
                    break;
            }
            
            this.updateToolbarState();
            this.handleContentChange(this.currentElement);
        },

        insertLink: function() {
            var url = prompt('Enter URL:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
            }
        },

        updateToolbarState: function() {
            // Update button states based on current selection
            $('.dcf-toolbar-btn').removeClass('active');
            
            if (document.queryCommandState('bold')) {
                $('.dcf-toolbar-btn[data-command="bold"]').addClass('active');
            }
            if (document.queryCommandState('italic')) {
                $('.dcf-toolbar-btn[data-command="italic"]').addClass('active');
            }
            if (document.queryCommandState('underline')) {
                $('.dcf-toolbar-btn[data-command="underline"]').addClass('active');
            }
            if (document.queryCommandState('strikeThrough')) {
                $('.dcf-toolbar-btn[data-command="strikethrough"]').addClass('active');
            }
            if (document.queryCommandState('justifyLeft')) {
                $('.dcf-toolbar-btn[data-command="alignLeft"]').addClass('active');
            }
            if (document.queryCommandState('justifyCenter')) {
                $('.dcf-toolbar-btn[data-command="alignCenter"]').addClass('active');
            }
            if (document.queryCommandState('justifyRight')) {
                $('.dcf-toolbar-btn[data-command="alignRight"]').addClass('active');
            }
        },

        handleContentChange: function(element) {
            var $element = $(element);
            var blockId = $element.attr('data-block-id');
            
            // Update placeholder visibility
            if ($element.text().trim() === '') {
                $element.addClass('dcf-empty');
            } else {
                $element.removeClass('dcf-empty');
            }
            
            // Sync with block instance
            if (window.DCF_VisualEditor && window.DCF_VisualEditor.blockInstances && blockId) {
                var block = window.DCF_VisualEditor.blockInstances[blockId];
                if (block && block.settings) {
                    block.settings.text = $element.html();
                }
            }
            
            // Trigger save state
            if (window.DCF_VisualEditor && window.DCF_VisualEditor.saveState) {
                clearTimeout(this.saveTimeout);
                this.saveTimeout = setTimeout(function() {
                    window.DCF_VisualEditor.saveState();
                }, 500);
            }
        },

        handlePaste: function(e) {
            e.preventDefault();
            
            var text = '';
            if (e.originalEvent.clipboardData) {
                text = e.originalEvent.clipboardData.getData('text/plain');
            } else if (window.clipboardData) {
                text = window.clipboardData.getData('Text');
            }
            
            // Insert plain text
            document.execCommand('insertText', false, text);
        },

        syncWithBlockSettings: function(element) {
            var $element = $(element);
            var blockId = $element.attr('data-block-id');
            
            if (window.DCF_VisualEditor && window.DCF_VisualEditor.blockInstances && blockId) {
                var block = window.DCF_VisualEditor.blockInstances[blockId];
                if (block && block.settings) {
                    // Apply block settings to element
                    if (block.settings.fontSize) {
                        $element.css('font-size', block.settings.fontSize);
                    }
                    if (block.settings.fontWeight) {
                        $element.css('font-weight', block.settings.fontWeight);
                    }
                    if (block.settings.color) {
                        $element.css('color', block.settings.color);
                    }
                    if (block.settings.textAlign) {
                        $element.css('text-align', block.settings.textAlign);
                    }
                }
            }
        },

        updateBlockContent: function(element) {
            var $element = $(element);
            var blockId = $element.attr('data-block-id');
            var blockType = $element.attr('data-block-type');
            
            if (window.DCF_VisualEditor && window.DCF_VisualEditor.blockInstances && blockId) {
                var block = window.DCF_VisualEditor.blockInstances[blockId];
                if (block) {
                    // Update block content based on type
                    switch(blockType) {
                        case 'text':
                            if (block.settings) {
                                block.settings.text = $element.html();
                            }
                            break;
                        case 'button':
                            if (block.settings) {
                                block.settings.text = $element.text();
                            }
                            break;
                        // Add more block types as needed
                    }
                }
            }
        },

        // Selection management utilities
        saveSelection: function() {
            var selection = window.getSelection();
            if (selection.rangeCount > 0) {
                return selection.getRangeAt(0);
            }
            return null;
        },

        restoreSelection: function(range) {
            if (range) {
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            }
        },

        // Get clean HTML without artifacts
        getCleanHTML: function(element) {
            var $clone = $(element).clone();
            
            // Remove toolbar and other UI elements
            $clone.find('.dcf-block-toolbar, .dcf-inline-toolbar').remove();
            
            // Remove contenteditable attributes
            $clone.removeAttr('contenteditable');
            $clone.find('[contenteditable]').removeAttr('contenteditable');
            
            return $clone.html();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DCF_InlineEditor.init();
    });

})(jQuery);