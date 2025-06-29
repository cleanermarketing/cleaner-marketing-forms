/**
 * Visual Editor Block System
 *
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Block Registry
    window.DCF_BlockRegistry = {
        blocks: {},
        
        register: function(type, blockClass) {
            this.blocks[type] = blockClass;
        },
        
        create: function(type, options) {
            if (!this.blocks[type]) {
                console.error('Unknown block type:', type);
                return null;
            }
            
            try {
                var block = new this.blocks[type](options || {});
                return block;
            } catch (e) {
                console.error('Error creating block type ' + type + ':', e);
                console.error('Options provided:', options);
                
                // Try creating with empty options
                try {
                    return new this.blocks[type]({});
                } catch (e2) {
                    console.error('Failed to create block with empty options:', e2);
                    return null;
                }
            }
        },
        
        getAll: function() {
            return this.blocks;
        },
        
        getCategories: function() {
            var categories = {};
            Object.keys(this.blocks).forEach(function(type) {
                var block = new this.blocks[type]();
                var category = block.getCategory();
                if (!categories[category]) {
                    categories[category] = [];
                }
                categories[category].push({
                    type: type,
                    name: block.getName(),
                    icon: block.getIcon()
                });
            }.bind(this));
            return categories;
        }
    };

    // Base Block Class
    class DCF_Block {
        constructor(options = {}) {
            this.id = options.id || 'block-' + Date.now();
            this.type = options.type || 'unknown';
            this.content = options.content || '';
            this.attributes = options.attributes || {};
            this.settings = options.settings || {};
        }

        // Methods to be overridden by child classes
        getName() {
            return 'Unknown Block';
        }

        getIcon() {
            return 'dashicons-block-default';
        }

        getCategory() {
            return 'standard';
        }

        getDefaultContent() {
            return '';
        }

        render() {
            return '<div data-block-id="' + this.id + '" data-block-type="' + this.type + '">Block content</div>';
        }

        renderSettings() {
            return '<p>No settings available for this block.</p>';
        }

        getEditableElements() {
            return [];
        }

        update(data) {
            Object.assign(this.settings, data);
        }

        toJSON() {
            return {
                id: this.id,
                type: this.type,
                content: this.content,
                attributes: this.attributes,
                settings: this.settings
            };
        }

        fromJSON(data) {
            this.id = data.id || this.id;
            this.type = data.type || this.type;
            this.content = data.content || this.content;
            this.attributes = data.attributes || this.attributes;
            
            // Merge settings to preserve defaults
            if (data.settings) {
                this.settings = Object.assign({}, this.settings, data.settings);
            }
            
            // Handle content field for text-based blocks
            if (data.content !== undefined) {
                // Set text content for blocks that use it
                if (this.type === 'text' || this.type === 'heading' || this.type === 'button') {
                    this.settings.text = data.content;
                }
            }
            
            // Parse style attributes if present
            if (data.attributes && data.attributes.style && typeof data.attributes.style === 'string') {
                var styles = data.attributes.style.split(';').filter(s => s.trim());
                styles.forEach(style => {
                    var parts = style.split(':');
                    if (parts.length === 2) {
                        var prop = parts[0].trim();
                        var value = parts[1].trim();
                        
                        if (prop === 'font-size' && this.settings.hasOwnProperty('fontSize')) {
                            this.settings.fontSize = value;
                        } else if (prop === 'color' && this.settings.hasOwnProperty('color')) {
                            this.settings.color = value;
                        } else if (prop === 'text-align' && this.settings.hasOwnProperty('textAlign')) {
                            this.settings.textAlign = value;
                        } else if (prop === 'font-weight' && this.settings.hasOwnProperty('fontWeight')) {
                            this.settings.fontWeight = value;
                        }
                    }
                });
            }
            
            return this;
        }
    }

    // Text Block
    class DCF_TextBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'text';
            this.settings = Object.assign({
                text: 'New text block',
                fontSize: '16px',
                fontWeight: 'normal',
                textAlign: 'left',
                color: '#333333',
                placeholder: 'Enter your text here...'
            }, this.settings);
        }

        getName() { return 'Text'; }
        getIcon() { return 'dashicons-text'; }

        render() {
            var styles = [
                'font-size: ' + this.settings.fontSize,
                'font-weight: ' + this.settings.fontWeight,
                'text-align: ' + this.settings.textAlign,
                'color: ' + this.settings.color
            ].join('; ');

            return '<p class="dcf-editable" contenteditable="true" data-block-id="' + this.id + '" data-block-type="text" data-placeholder="' + this.settings.placeholder + '" style="' + styles + '">' + this.settings.text + '</p>';
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Text Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Font Size</label>
                        <input type="text" class="dcf-field-input" data-setting="fontSize" value="${this.settings.fontSize}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Font Weight</label>
                        <select class="dcf-field-select" data-setting="fontWeight">
                            <option value="normal" ${this.settings.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                            <option value="bold" ${this.settings.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                            <option value="100" ${this.settings.fontWeight === '100' ? 'selected' : ''}>Thin</option>
                            <option value="300" ${this.settings.fontWeight === '300' ? 'selected' : ''}>Light</option>
                            <option value="500" ${this.settings.fontWeight === '500' ? 'selected' : ''}>Medium</option>
                            <option value="700" ${this.settings.fontWeight === '700' ? 'selected' : ''}>Bold</option>
                            <option value="900" ${this.settings.fontWeight === '900' ? 'selected' : ''}>Black</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Text Align</label>
                        <div class="dcf-button-group">
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="left" ${this.settings.textAlign === 'left' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-alignleft"></span>
                            </button>
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="center" ${this.settings.textAlign === 'center' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-aligncenter"></span>
                            </button>
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="right" ${this.settings.textAlign === 'right' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-alignright"></span>
                            </button>
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="justify" ${this.settings.textAlign === 'justify' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-justify"></span>
                            </button>
                        </div>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Text Color</label>
                        <input type="color" class="dcf-color-input" data-setting="color" value="${this.settings.color}">
                    </div>
                </div>
            `;
        }
    }

    // Button Block
    class DCF_ButtonBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'button';
            this.settings = Object.assign({
                text: 'BUTTON TEXT',
                backgroundColor: '#28a745',
                textColor: '#ffffff',
                borderRadius: '4px',
                padding: '18px 30px',
                fontSize: '18px',
                fontWeight: '600',
                textTransform: 'uppercase',
                action: 'submit',
                url: '',
                target: '_self'
            }, this.settings);
        }

        getName() { return 'Button'; }
        getIcon() { return 'dashicons-button'; }

        render() {
            var styles = [
                'background-color: ' + this.settings.backgroundColor,
                'color: ' + this.settings.textColor,
                'border-radius: ' + this.settings.borderRadius,
                'padding: ' + this.settings.padding,
                'font-size: ' + this.settings.fontSize,
                'font-weight: ' + this.settings.fontWeight,
                'text-transform: ' + this.settings.textTransform,
                'border: none',
                'cursor: pointer',
                'transition: all 0.3s ease'
            ].join('; ');

            var tag = this.settings.action === 'link' ? 'a' : 'button';
            var href = this.settings.action === 'link' ? ' href="' + this.settings.url + '" target="' + this.settings.target + '"' : '';
            var type = this.settings.action !== 'link' ? ' type="' + this.settings.action + '"' : '';

            return '<' + tag + ' class="dcf-button dcf-editable" contenteditable="true" data-block-id="' + this.id + '" data-block-type="button" style="' + styles + '"' + href + type + '>' + this.settings.text + '</' + tag + '>';
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Button Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Button Action</label>
                        <select class="dcf-field-select" data-setting="action">
                            <option value="submit" ${this.settings.action === 'submit' ? 'selected' : ''}>Submit Form</option>
                            <option value="next-step" ${this.settings.action === 'next-step' ? 'selected' : ''}>Next Step</option>
                            <option value="link" ${this.settings.action === 'link' ? 'selected' : ''}>Go to URL</option>
                            <option value="close" ${this.settings.action === 'close' ? 'selected' : ''}>Close Popup</option>
                        </select>
                    </div>
                    <div class="dcf-field-group dcf-url-settings" style="${this.settings.action === 'link' ? '' : 'display:none'}">
                        <label class="dcf-field-label">URL</label>
                        <input type="text" class="dcf-field-input" data-setting="url" value="${this.settings.url}" placeholder="https://example.com">
                        <label class="dcf-field-label">Target</label>
                        <select class="dcf-field-select" data-setting="target">
                            <option value="_self" ${this.settings.target === '_self' ? 'selected' : ''}>Same Window</option>
                            <option value="_blank" ${this.settings.target === '_blank' ? 'selected' : ''}>New Window</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Background Color</label>
                        <input type="color" class="dcf-color-input" data-setting="backgroundColor" value="${this.settings.backgroundColor}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Text Color</label>
                        <input type="color" class="dcf-color-input" data-setting="textColor" value="${this.settings.textColor}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Border Radius</label>
                        <input type="text" class="dcf-field-input" data-setting="borderRadius" value="${this.settings.borderRadius}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Padding</label>
                        <input type="text" class="dcf-field-input" data-setting="padding" value="${this.settings.padding}">
                    </div>
                </div>
            `;
        }
    }

    // Image Block
    class DCF_ImageBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'image';
            this.settings = Object.assign({
                src: dcf_admin.placeholder_image || '',
                alt: 'Image',
                width: '100%',
                height: 'auto',
                alignment: 'center',
                borderRadius: '0px',
                clickAction: 'none',
                linkUrl: ''
            }, this.settings);
        }

        getName() { return 'Image'; }
        getIcon() { return 'dashicons-format-image'; }

        render() {
            var containerStyles = 'text-align: ' + this.settings.alignment + ';';
            var imageStyles = [
                'width: ' + this.settings.width,
                'height: ' + this.settings.height,
                'border-radius: ' + this.settings.borderRadius,
                'display: inline-block'
            ].join('; ');

            var html = '<div class="dcf-image-block" data-block-id="' + this.id + '" data-block-type="image" style="' + containerStyles + '">';
            
            if (this.settings.clickAction === 'link' && this.settings.linkUrl) {
                html += '<a href="' + this.settings.linkUrl + '" target="_blank">';
            }
            
            html += '<img src="' + this.settings.src + '" alt="' + this.settings.alt + '" style="' + imageStyles + '">';
            
            if (this.settings.clickAction === 'link' && this.settings.linkUrl) {
                html += '</a>';
            }
            
            html += '<div class="dcf-image-upload-overlay">Click to upload image</div>';
            html += '</div>';
            
            return html;
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Image Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Image</label>
                        <button type="button" class="button dcf-upload-image" data-setting="src">Choose Image</button>
                        <div class="dcf-image-preview" style="margin-top: 10px;">
                            ${this.settings.src ? '<img src="' + this.settings.src + '" style="max-width: 200px;">' : ''}
                        </div>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Alt Text</label>
                        <input type="text" class="dcf-field-input" data-setting="alt" value="${this.settings.alt}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Width</label>
                        <input type="text" class="dcf-field-input" data-setting="width" value="${this.settings.width}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Height</label>
                        <input type="text" class="dcf-field-input" data-setting="height" value="${this.settings.height}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Alignment</label>
                        <select class="dcf-field-select" data-setting="alignment">
                            <option value="left" ${this.settings.alignment === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${this.settings.alignment === 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${this.settings.alignment === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Click Action</label>
                        <select class="dcf-field-select" data-setting="clickAction">
                            <option value="none" ${this.settings.clickAction === 'none' ? 'selected' : ''}>None</option>
                            <option value="link" ${this.settings.clickAction === 'link' ? 'selected' : ''}>Open Link</option>
                            <option value="lightbox" ${this.settings.clickAction === 'lightbox' ? 'selected' : ''}>Open in Lightbox</option>
                        </select>
                    </div>
                    <div class="dcf-field-group dcf-link-settings" style="${this.settings.clickAction === 'link' ? '' : 'display:none'}">
                        <label class="dcf-field-label">Link URL</label>
                        <input type="text" class="dcf-field-input" data-setting="linkUrl" value="${this.settings.linkUrl}" placeholder="https://example.com">
                    </div>
                </div>
            `;
        }
    }

    // Form Block - Integrates with Form Builder
    class DCF_FormBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'form';
            this.settings = Object.assign({
                formId: '',
                showTitle: false,
                showDescription: false,
                alignment: 'left'
            }, this.settings);
            
            // Cache for form data
            this.formData = null;
            this.loadingForm = false;
            this.availableForms = [];
            
            // Load available forms on creation
            console.log('FormBlock constructor - loading forms');
            this.loadAvailableForms();
            
            // Load form data if form ID is set
            if (this.settings.formId) {
                this.loadFormData();
            }
        }

        getName() { return 'Form'; }
        getIcon() { return 'dashicons-forms'; }
        getCategory() { return 'standard'; }

        render() {
            var html = '<div class="dcf-form-block-container" data-block-id="' + this.id + '" data-block-type="form" style="text-align: ' + this.settings.alignment + ';">';
            
            if (!this.settings.formId) {
                html += '<div class="dcf-form-placeholder">';
                html += '<span class="dashicons dashicons-forms"></span>';
                html += '<p>Select a form from the settings panel</p>';
                html += '</div>';
            } else if (this.loadingForm) {
                html += '<div class="dcf-form-loading">';
                html += '<span class="dashicons dashicons-update spin"></span>';
                html += '<p>Loading form...</p>';
                html += '</div>';
            } else if (this.formData) {
                // Render form preview
                html += '<div class="dcf-form-preview" data-form-id="' + this.settings.formId + '">';
                
                if (this.settings.showTitle && this.formData.form_config && this.formData.form_config.title) {
                    html += '<h3 class="dcf-form-title">' + this.formData.form_config.title + '</h3>';
                }
                
                if (this.settings.showDescription && this.formData.form_config && this.formData.form_config.description) {
                    html += '<div class="dcf-form-description">' + this.formData.form_config.description + '</div>';
                }
                
                // Show form name and field count
                html += '<div class="dcf-form-info">';
                html += '<strong>' + (this.formData.form_name || 'Untitled Form') + '</strong>';
                if (this.formData.form_config && this.formData.form_config.fields) {
                    var totalFieldCount = this.formData.form_config.fields.length;
                    var visibleFieldCount = this.formData.form_config.fields.filter(function(f) { return f.type !== 'hidden'; }).length;
                    var hiddenFieldCount = totalFieldCount - visibleFieldCount;
                    
                    // Show total field count
                    html += ' <span class="dcf-field-count">(' + totalFieldCount + ' field' + (totalFieldCount !== 1 ? 's' : '');
                    
                    // If there are hidden fields, show the breakdown
                    if (hiddenFieldCount > 0) {
                        html += ' - ' + visibleFieldCount + ' visible, ' + hiddenFieldCount + ' hidden';
                    }
                    
                    html += ')</span>';
                }
                html += '</div>';
                
                // Add form preview notice
                html += '<div class="dcf-form-preview-notice">';
                html += '<em>Form will be rendered on the frontend</em>';
                html += '</div>';
                
                html += '</div>';
            } else {
                html += '<div class="dcf-form-error">';
                html += '<span class="dashicons dashicons-warning"></span>';
                html += '<p>Unable to load form</p>';
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        }

        renderSettings() {
            var self = this;
            
            // Generate form options HTML
            var formOptionsHtml = '<option value="">Select a form...</option>';
            if (this.availableForms) {
                this.availableForms.forEach(function(form) {
                    var selected = form.id == self.settings.formId ? 'selected' : '';
                    formOptionsHtml += '<option value="' + form.id + '" ' + selected + '>' + form.form_name + '</option>';
                });
            }
            
            return `
                <div class="dcf-block-settings">
                    <h4>Form Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Select Form</label>
                        <select class="dcf-field-select dcf-form-selector" data-setting="formId">
                            ${formOptionsHtml}
                        </select>
                        <button type="button" class="button button-small dcf-refresh-forms" style="margin-top: 5px;">
                            <span class="dashicons dashicons-update"></span> Refresh Forms
                        </button>
                    </div>
                    <div class="dcf-field-group">
                        <label>
                            <input type="checkbox" data-setting="showTitle" ${this.settings.showTitle ? 'checked' : ''}>
                            Show Form Title
                        </label>
                    </div>
                    <div class="dcf-field-group">
                        <label>
                            <input type="checkbox" data-setting="showDescription" ${this.settings.showDescription ? 'checked' : ''}>
                            Show Form Description
                        </label>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Alignment</label>
                        <select class="dcf-field-select" data-setting="alignment">
                            <option value="left" ${this.settings.alignment === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${this.settings.alignment === 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${this.settings.alignment === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    ${this.settings.formId ? `
                        <div class="dcf-field-group">
                            <a href="${dcf_admin.admin_url.replace('admin.php?page=cmf-popup-manager', '')}admin.php?page=cmf-form-builder&action=edit&form_id=${this.settings.formId}" target="_blank" class="button button-small">
                                <span class="dashicons dashicons-edit"></span> Edit Form
                            </a>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        // Load available forms
        loadAvailableForms() {
            var self = this;
            
            console.log('Loading available forms...');
            console.log('AJAX URL:', dcf_admin.ajax_url);
            console.log('Nonce:', dcf_admin.nonce);
            
            jQuery.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_get_all_forms',
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    console.log('Forms loaded:', response);
                    if (response.success && response.data) {
                        self.availableForms = response.data;
                        // Re-render settings if panel is open
                        if (jQuery('.dcf-block-settings .dcf-form-selector').length) {
                            var currentSettings = self.renderSettings();
                            jQuery('.dcf-settings-content').html(currentSettings);
                            self.bindSettingsEvents();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load forms:', error);
                    console.error('XHR:', xhr);
                }
            });
        }
        
        // Load form data when form ID changes
        loadFormData() {
            var self = this;
            
            if (!this.settings.formId) {
                this.formData = null;
                this.updatePreview();
                return;
            }
            
            this.loadingForm = true;
            this.updatePreview();
            
            jQuery.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_get_form_data',
                    form_id: this.settings.formId,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    self.loadingForm = false;
                    if (response.success && response.data) {
                        self.formData = response.data;
                    } else {
                        self.formData = null;
                    }
                    self.updatePreview();
                },
                error: function() {
                    self.loadingForm = false;
                    self.formData = null;
                    self.updatePreview();
                }
            });
        }
        
        // Update preview
        updatePreview() {
            var newHtml = this.render();
            jQuery('[data-block-id="' + this.id + '"]').replaceWith(newHtml);
        }
        
        // Bind settings events
        bindSettingsEvents() {
            var self = this;
            
            // Form selector change
            jQuery(document).off('change.formblock-' + this.id).on('change.formblock-' + this.id, '.dcf-form-selector', function() {
                var newFormId = jQuery(this).val();
                if (newFormId !== self.settings.formId) {
                    self.settings.formId = newFormId;
                    self.loadFormData();
                }
            });
            
            // Refresh forms button
            jQuery(document).off('click.refreshforms-' + this.id).on('click.refreshforms-' + this.id, '.dcf-refresh-forms', function(e) {
                e.preventDefault();
                self.loadAvailableForms();
            });
        }
        
        // Override update method to handle form changes
        update(data) {
            var formChanged = data.formId && data.formId !== this.settings.formId;
            super.update(data);
            
            if (formChanged) {
                this.loadFormData();
            } else {
                this.updatePreview();
            }
        }
        
        // Initialize on creation
        fromJSON(data) {
            super.fromJSON(data);
            if (this.settings.formId) {
                this.loadFormData();
            }
            this.loadAvailableForms();
            return this;
        }
    }

    // Divider Block
    class DCF_DividerBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'divider';
            this.settings = Object.assign({
                style: 'solid',
                color: '#e0e0e0',
                thickness: '1px',
                width: '100%',
                margin: '20px 0'
            }, this.settings);
        }

        getName() { return 'Divider'; }
        getIcon() { return 'dashicons-minus'; }
        getCategory() { return 'advanced'; }

        render() {
            var styles = [
                'border-top-style: ' + this.settings.style,
                'border-top-color: ' + this.settings.color,
                'border-top-width: ' + this.settings.thickness,
                'width: ' + this.settings.width,
                'margin: ' + this.settings.margin
            ].join('; ');

            return '<hr class="dcf-divider" data-block-id="' + this.id + '" data-block-type="divider" style="' + styles + '">';
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Divider Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Style</label>
                        <select class="dcf-field-select" data-setting="style">
                            <option value="solid" ${this.settings.style === 'solid' ? 'selected' : ''}>Solid</option>
                            <option value="dashed" ${this.settings.style === 'dashed' ? 'selected' : ''}>Dashed</option>
                            <option value="dotted" ${this.settings.style === 'dotted' ? 'selected' : ''}>Dotted</option>
                            <option value="double" ${this.settings.style === 'double' ? 'selected' : ''}>Double</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Color</label>
                        <input type="color" class="dcf-color-input" data-setting="color" value="${this.settings.color}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Thickness</label>
                        <input type="text" class="dcf-field-input" data-setting="thickness" value="${this.settings.thickness}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Width</label>
                        <input type="text" class="dcf-field-input" data-setting="width" value="${this.settings.width}">
                    </div>
                </div>
            `;
        }
    }

    // Spacer Block
    class DCF_SpacerBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'spacer';
            this.settings = Object.assign({
                height: '30px'
            }, this.settings);
        }

        getName() { return 'Spacer'; }
        getIcon() { return 'dashicons-minus'; }
        getCategory() { return 'advanced'; }

        render() {
            return '<div class="dcf-spacer" data-block-id="' + this.id + '" data-block-type="spacer" style="height: ' + this.settings.height + ';"></div>';
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Spacer Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Height</label>
                        <input type="text" class="dcf-field-input" data-setting="height" value="${this.settings.height}">
                    </div>
                </div>
            `;
        }
    }

    // Countdown Block
    class DCF_CountdownBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'countdown';
            this.settings = Object.assign({
                endDate: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                endTime: '23:59',
                format: 'DD:HH:MM:SS',
                fontSize: '24px',
                color: '#333333',
                labelDisplay: 'show'
            }, this.settings);
        }

        getName() { return 'Countdown'; }
        getIcon() { return 'dashicons-clock'; }
        getCategory() { return 'advanced'; }

        render() {
            var styles = [
                'font-size: ' + this.settings.fontSize,
                'color: ' + this.settings.color,
                'text-align: center'
            ].join('; ');

            return `
                <div class="dcf-countdown-block" data-block-id="${this.id}" data-block-type="countdown" data-end-date="${this.settings.endDate}" data-end-time="${this.settings.endTime}" style="${styles}">
                    <div class="dcf-countdown-timer">00:05:00</div>
                    ${this.settings.labelDisplay === 'show' ? '<div class="dcf-countdown-labels"><span>Days</span><span>Hours</span><span>Minutes</span><span>Seconds</span></div>' : ''}
                </div>
            `;
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Countdown Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">End Date</label>
                        <input type="date" class="dcf-field-input" data-setting="endDate" value="${this.settings.endDate}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">End Time</label>
                        <input type="time" class="dcf-field-input" data-setting="endTime" value="${this.settings.endTime}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Font Size</label>
                        <input type="text" class="dcf-field-input" data-setting="fontSize" value="${this.settings.fontSize}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Color</label>
                        <input type="color" class="dcf-color-input" data-setting="color" value="${this.settings.color}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Show Labels</label>
                        <select class="dcf-field-select" data-setting="labelDisplay">
                            <option value="show" ${this.settings.labelDisplay === 'show' ? 'selected' : ''}>Show</option>
                            <option value="hide" ${this.settings.labelDisplay === 'hide' ? 'selected' : ''}>Hide</option>
                        </select>
                    </div>
                </div>
            `;
        }
    }

    // Video Block
    class DCF_VideoBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'video';
            this.settings = Object.assign({
                source: 'youtube',
                videoId: '',
                url: '',
                autoplay: false,
                muted: false,
                controls: true,
                width: '100%',
                height: '315px'
            }, this.settings);
        }

        getName() { return 'Video'; }
        getIcon() { return 'dashicons-video-alt3'; }
        getCategory() { return 'advanced'; }

        render() {
            var html = '<div class="dcf-video-block" data-block-id="' + this.id + '" data-block-type="video" style="width: ' + this.settings.width + '; height: ' + this.settings.height + ';">';
            
            if (this.settings.source === 'youtube' && this.settings.videoId) {
                var params = [];
                if (this.settings.autoplay) params.push('autoplay=1');
                if (this.settings.muted) params.push('mute=1');
                if (!this.settings.controls) params.push('controls=0');
                
                html += '<iframe src="https://www.youtube.com/embed/' + this.settings.videoId + '?' + params.join('&') + '" frameborder="0" allowfullscreen style="width: 100%; height: 100%;"></iframe>';
            } else if (this.settings.source === 'url' && this.settings.url) {
                html += '<video style="width: 100%; height: 100%;" ' + (this.settings.controls ? 'controls' : '') + ' ' + (this.settings.autoplay ? 'autoplay' : '') + ' ' + (this.settings.muted ? 'muted' : '') + '>';
                html += '<source src="' + this.settings.url + '" type="video/mp4">';
                html += 'Your browser does not support the video tag.';
                html += '</video>';
            } else {
                html += '<div class="dcf-video-placeholder">Video placeholder - Click to configure</div>';
            }
            
            html += '</div>';
            return html;
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Video Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Video Source</label>
                        <select class="dcf-field-select" data-setting="source">
                            <option value="youtube" ${this.settings.source === 'youtube' ? 'selected' : ''}>YouTube</option>
                            <option value="url" ${this.settings.source === 'url' ? 'selected' : ''}>Direct URL</option>
                        </select>
                    </div>
                    <div class="dcf-field-group dcf-youtube-settings" style="${this.settings.source === 'youtube' ? '' : 'display:none'}">
                        <label class="dcf-field-label">YouTube Video ID</label>
                        <input type="text" class="dcf-field-input" data-setting="videoId" value="${this.settings.videoId}" placeholder="e.g., dQw4w9WgXcQ">
                    </div>
                    <div class="dcf-field-group dcf-url-settings" style="${this.settings.source === 'url' ? '' : 'display:none'}">
                        <label class="dcf-field-label">Video URL</label>
                        <input type="text" class="dcf-field-input" data-setting="url" value="${this.settings.url}" placeholder="https://example.com/video.mp4">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Width</label>
                        <input type="text" class="dcf-field-input" data-setting="width" value="${this.settings.width}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Height</label>
                        <input type="text" class="dcf-field-input" data-setting="height" value="${this.settings.height}">
                    </div>
                    <div class="dcf-field-group">
                        <label><input type="checkbox" data-setting="autoplay" ${this.settings.autoplay ? 'checked' : ''}> Autoplay</label>
                    </div>
                    <div class="dcf-field-group">
                        <label><input type="checkbox" data-setting="muted" ${this.settings.muted ? 'checked' : ''}> Muted</label>
                    </div>
                    <div class="dcf-field-group">
                        <label><input type="checkbox" data-setting="controls" ${this.settings.controls ? 'checked' : ''}> Show Controls</label>
                    </div>
                </div>
            `;
        }
    }

    // Yes/No Block
    class DCF_YesNoBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'yes-no';
            this.settings = Object.assign({
                yesText: 'YES',
                noText: 'NO',
                yesColor: '#28a745',
                noColor: '#dc3545',
                buttonStyle: 'filled',
                spacing: '10px',
                yesAction: 'next-step',
                noAction: 'close'
            }, this.settings);
        }

        getName() { return 'Yes/No Buttons'; }
        getIcon() { return 'dashicons-yes-alt'; }
        getCategory() { return 'advanced'; }

        render() {
            var baseStyles = 'padding: 12px 24px; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;';
            
            var yesStyles = baseStyles;
            var noStyles = baseStyles;
            
            if (this.settings.buttonStyle === 'filled') {
                yesStyles += ' background: ' + this.settings.yesColor + '; color: white; border: none;';
                noStyles += ' background: ' + this.settings.noColor + '; color: white; border: none;';
            } else {
                yesStyles += ' background: transparent; color: ' + this.settings.yesColor + '; border: 2px solid ' + this.settings.yesColor + ';';
                noStyles += ' background: transparent; color: ' + this.settings.noColor + '; border: 2px solid ' + this.settings.noColor + ';';
            }

            return `
                <div class="dcf-yes-no-block" data-block-id="${this.id}" data-block-type="yes-no" style="display: flex; gap: ${this.settings.spacing}; justify-content: center;">
                    <button class="dcf-yes-button" data-action="${this.settings.yesAction}" style="${yesStyles}">${this.settings.yesText}</button>
                    <button class="dcf-no-button" data-action="${this.settings.noAction}" style="${noStyles}">${this.settings.noText}</button>
                </div>
            `;
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Yes/No Button Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Yes Button Text</label>
                        <input type="text" class="dcf-field-input" data-setting="yesText" value="${this.settings.yesText}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">No Button Text</label>
                        <input type="text" class="dcf-field-input" data-setting="noText" value="${this.settings.noText}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Yes Button Color</label>
                        <input type="color" class="dcf-color-input" data-setting="yesColor" value="${this.settings.yesColor}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">No Button Color</label>
                        <input type="color" class="dcf-color-input" data-setting="noColor" value="${this.settings.noColor}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Button Style</label>
                        <select class="dcf-field-select" data-setting="buttonStyle">
                            <option value="filled" ${this.settings.buttonStyle === 'filled' ? 'selected' : ''}>Filled</option>
                            <option value="outline" ${this.settings.buttonStyle === 'outline' ? 'selected' : ''}>Outline</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Yes Button Action</label>
                        <select class="dcf-field-select" data-setting="yesAction">
                            <option value="next-step" ${this.settings.yesAction === 'next-step' ? 'selected' : ''}>Go to Next Step</option>
                            <option value="close" ${this.settings.yesAction === 'close' ? 'selected' : ''}>Close Popup</option>
                            <option value="submit" ${this.settings.yesAction === 'submit' ? 'selected' : ''}>Submit Form</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">No Button Action</label>
                        <select class="dcf-field-select" data-setting="noAction">
                            <option value="close" ${this.settings.noAction === 'close' ? 'selected' : ''}>Close Popup</option>
                            <option value="previous-step" ${this.settings.noAction === 'previous-step' ? 'selected' : ''}>Go to Previous Step</option>
                        </select>
                    </div>
                </div>
            `;
        }
    }

    // Heading Block
    class DCF_HeadingBlock extends DCF_Block {
        constructor(options = {}) {
            super(options);
            this.type = 'heading';
            this.settings = Object.assign({
                text: 'Enter your headline here',
                level: 'h2',
                fontSize: '36px',
                fontWeight: 'normal',
                textAlign: 'center',
                color: '#333333',
                placeholder: 'Enter your headline...'
            }, this.settings);
        }

        getName() { return 'Heading'; }
        getIcon() { return 'dashicons-heading'; }

        render() {
            var styles = [
                'font-size: ' + this.settings.fontSize,
                'font-weight: ' + this.settings.fontWeight,
                'text-align: ' + this.settings.textAlign,
                'color: ' + this.settings.color
            ].join('; ');

            var tag = this.settings.level || 'h2';
            return '<' + tag + ' class="dcf-editable" contenteditable="true" data-block-id="' + this.id + '" data-block-type="heading" data-placeholder="' + this.settings.placeholder + '" style="' + styles + '">' + this.settings.text + '</' + tag + '>';
        }

        renderSettings() {
            return `
                <div class="dcf-block-settings">
                    <h4>Heading Settings</h4>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Heading Level</label>
                        <select class="dcf-field-select" data-setting="level">
                            <option value="h1" ${this.settings.level === 'h1' ? 'selected' : ''}>H1</option>
                            <option value="h2" ${this.settings.level === 'h2' ? 'selected' : ''}>H2</option>
                            <option value="h3" ${this.settings.level === 'h3' ? 'selected' : ''}>H3</option>
                            <option value="h4" ${this.settings.level === 'h4' ? 'selected' : ''}>H4</option>
                            <option value="h5" ${this.settings.level === 'h5' ? 'selected' : ''}>H5</option>
                            <option value="h6" ${this.settings.level === 'h6' ? 'selected' : ''}>H6</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Font Size</label>
                        <input type="text" class="dcf-field-input" data-setting="fontSize" value="${this.settings.fontSize}">
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Font Weight</label>
                        <select class="dcf-field-select" data-setting="fontWeight">
                            <option value="normal" ${this.settings.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                            <option value="bold" ${this.settings.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                            <option value="300" ${this.settings.fontWeight === '300' ? 'selected' : ''}>Light</option>
                            <option value="500" ${this.settings.fontWeight === '500' ? 'selected' : ''}>Medium</option>
                            <option value="700" ${this.settings.fontWeight === '700' ? 'selected' : ''}>Bold</option>
                        </select>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Text Align</label>
                        <div class="dcf-button-group">
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="left" ${this.settings.textAlign === 'left' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-alignleft"></span>
                            </button>
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="center" ${this.settings.textAlign === 'center' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-aligncenter"></span>
                            </button>
                            <button type="button" class="dcf-align-button" data-setting="textAlign" data-value="right" ${this.settings.textAlign === 'right' ? 'data-active="true"' : ''}>
                                <span class="dashicons dashicons-editor-alignright"></span>
                            </button>
                        </div>
                    </div>
                    <div class="dcf-field-group">
                        <label class="dcf-field-label">Text Color</label>
                        <input type="color" class="dcf-color-input" data-setting="color" value="${this.settings.color}">
                    </div>
                </div>
            `;
        }
    }

    // Register all blocks
    DCF_BlockRegistry.register('heading', DCF_HeadingBlock);
    DCF_BlockRegistry.register('text', DCF_TextBlock);
    DCF_BlockRegistry.register('button', DCF_ButtonBlock);
    DCF_BlockRegistry.register('image', DCF_ImageBlock);
    DCF_BlockRegistry.register('form', DCF_FormBlock);
    DCF_BlockRegistry.register('divider', DCF_DividerBlock);
    DCF_BlockRegistry.register('spacer', DCF_SpacerBlock);
    DCF_BlockRegistry.register('countdown', DCF_CountdownBlock);
    DCF_BlockRegistry.register('video', DCF_VideoBlock);
    DCF_BlockRegistry.register('yes-no', DCF_YesNoBlock);

    // Expose to global scope
    window.DCF_Block = DCF_Block;
    window.DCF_HeadingBlock = DCF_HeadingBlock;
    window.DCF_TextBlock = DCF_TextBlock;
    window.DCF_ButtonBlock = DCF_ButtonBlock;
    window.DCF_ImageBlock = DCF_ImageBlock;
    window.DCF_FormBlock = DCF_FormBlock;
    window.DCF_DividerBlock = DCF_DividerBlock;
    window.DCF_SpacerBlock = DCF_SpacerBlock;
    window.DCF_CountdownBlock = DCF_CountdownBlock;
    window.DCF_VideoBlock = DCF_VideoBlock;
    window.DCF_YesNoBlock = DCF_YesNoBlock;

})(jQuery);