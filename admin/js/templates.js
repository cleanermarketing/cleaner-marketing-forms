/**
 * Templates Page JavaScript
 */
(function($) {
    'use strict';
    
    var DCF_Templates = {
        
        init: function() {
            this.bindEvents();
            this.initFilters();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Template type selection
            $('.dcf-type-card').on('click', function(e) {
                if (!$(e.target).is('a')) {
                    $(this).find('a')[0].click();
                }
            });
            
            // Use template button
            $(document).on('click', '.dcf-use-template-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleUseTemplate.call(this, e);
            });
            
            // Preview button
            $(document).on('click', '.dcf-preview-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handlePreview.call(this, e);
            });
            
            // Modal controls
            $(document).on('click', '.dcf-modal-close, .dcf-modal-cancel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.closeModal();
            });
            
            $(document).on('click', '.dcf-modal-create', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.createFromTemplate.call(this);
            });
            
            // Search functionality
            $('.dcf-search-input').on('input', this.debounce(this.handleSearch, 300));
            
            // Filter toggles
            $('.dcf-filter-toggle').on('click', this.toggleFilterDropdown);
            
            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dcf-filter-group').length) {
                    $('.dcf-filter-dropdown').removeClass('show');
                    $('.dcf-filter-toggle').removeClass('active');
                }
            });
            
            // Handle escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeModal();
                }
            });
            
            // Close modal when clicking overlay (not content)
            $(document).on('click', '.dcf-modal', function(e) {
                if ($(e.target).hasClass('dcf-modal')) {
                    self.closeModal();
                }
            });
            
            // Prevent modal content clicks from closing modal
            $(document).on('click', '.dcf-modal-content', function(e) {
                e.stopPropagation();
            });
        },
        
        initFilters: function() {
            // Load filter options dynamically based on available templates
            var categories = {};
            $('.dcf-template-card').each(function() {
                var category = $(this).data('category');
                if (category) {
                    if (!categories[category]) {
                        categories[category] = 0;
                    }
                    categories[category]++;
                }
            });
            
            // Populate filter dropdowns
            Object.keys(categories).forEach(function(category) {
                var $dropdown = $('#filter-' + category);
                var $option = $('<div class="dcf-filter-option">')
                    .html('<label><input type="checkbox" value="' + category + '"> ' + category + ' (' + categories[category] + ')</label>');
                $dropdown.append($option);
            });
        },
        
        handleUseTemplate: function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var templateId = $(this).data('template-id');
            var templateName = $(this).closest('.dcf-template-card').find('h3').text();
            
            $('#dcf-template-id').val(templateId);
            $('#dcf-template-name').val('').attr('placeholder', templateName + ' Copy');
            $('#dcf-template-modal').addClass('show');
            $('#dcf-template-name').focus();
        },
        
        handlePreview: function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var templateId = $(this).data('template-id');
            
            // Show loading state
            $('#dcf-preview-modal .dcf-preview-container').html('<div class="dcf-loading"><span class="spinner is-active"></span></div>');
            $('#dcf-preview-modal').addClass('show');
            
            // Load preview via AJAX
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_preview_template',
                    template_id: templateId,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#dcf-preview-modal .dcf-preview-container').html(response.data.preview);
                    } else {
                        $('#dcf-preview-modal .dcf-preview-container').html(
                            '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                        );
                    }
                },
                error: function() {
                    $('#dcf-preview-modal .dcf-preview-container').html(
                        '<div class="notice notice-error"><p>Failed to load preview.</p></div>'
                    );
                }
            });
        },
        
        closeModal: function() {
            $('.dcf-modal').removeClass('show');
            $('#dcf-template-form')[0].reset();
        },
        
        createFromTemplate: function() {
            var templateId = $('#dcf-template-id').val();
            var name = $('#dcf-template-name').val();
            
            if (!name.trim()) {
                $('#dcf-template-name').focus();
                return;
            }
            
            // Disable button and show loading
            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).text('Creating...');
            
            $.ajax({
                url: dcf_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_create_from_template',
                    template_id: templateId,
                    name: name,
                    nonce: dcf_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to edit page
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            DCF_Templates.closeModal();
                            DCF_Templates.showNotice('Created successfully!', 'success');
                        }
                    } else {
                        DCF_Templates.showNotice(response.data.message || 'Failed to create from template.', 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    DCF_Templates.showNotice('An error occurred. Please try again.', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        handleSearch: function() {
            var query = $(this).val().toLowerCase();
            
            if (query) {
                $('.dcf-template-card').each(function() {
                    var $card = $(this);
                    var title = $card.find('h3').text().toLowerCase();
                    var description = $card.find('p').text().toLowerCase();
                    
                    if (title.includes(query) || description.includes(query)) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
                
                // Update no results message
                if ($('.dcf-template-card:visible').length === 0) {
                    if (!$('.dcf-no-results').length) {
                        $('.dcf-templates-grid').append(
                            '<div class="dcf-no-results dcf-no-templates">' +
                            '<p>No templates found matching "' + query + '"</p>' +
                            '</div>'
                        );
                    }
                } else {
                    $('.dcf-no-results').remove();
                }
            } else {
                $('.dcf-template-card').show();
                $('.dcf-no-results').remove();
            }
        },
        
        toggleFilterDropdown: function(e) {
            e.stopPropagation();
            var $toggle = $(this);
            var $dropdown = $toggle.siblings('.dcf-filter-dropdown');
            
            // Close other dropdowns
            $('.dcf-filter-dropdown').not($dropdown).removeClass('show');
            $('.dcf-filter-toggle').not($toggle).removeClass('active');
            
            // Toggle this dropdown
            $dropdown.toggleClass('show');
            $toggle.toggleClass('active');
        },
        
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
                          '<p>' + message + '</p>' +
                          '<button type="button" class="notice-dismiss"></button>' +
                          '</div>');
            
            $('.dcf-templates-page h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        DCF_Templates.init();
    });
    
})(jQuery);