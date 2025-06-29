/**
 * DCF Popup Engine
 * 
 * Handles popup display logic, triggers, and user interactions
 * 
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    window.DCF_PopupEngine = {
        
        // Configuration
        config: {
            popups: [],
            activePopups: [],
            sessionData: {},
            debug: false
        },
        
        // State tracking
        state: {
            pageLoadTime: Date.now(),
            scrollPercentage: 0,
            pageViews: 0,
            sessionStartTime: Date.now(),
            exitIntentTriggered: false,
            popupsShown: [],
            popupDisplayCounts: {}, // Track how many times each popup has been shown
            userInteracted: false
        },
        
        /**
         * Initialize the popup engine
         */
        init: function() {
            // Prevent multiple initializations
            if (this.initialized) {
                console.log('[DCF Popup Engine] Already initialized, skipping');
                return;
            }
            
            if (typeof dcf_popup_data === 'undefined') {
                return;
            }
            
            this.initialized = true;
            
            this.config = $.extend(this.config, dcf_popup_data);
            this.state.debug = true; // Force debug mode for troubleshooting
            
            // Check for preview mode
            const urlParams = new URLSearchParams(window.location.search);
            this.state.isPreviewMode = urlParams.has('dcf_preview');
            this.state.previewPopupId = urlParams.get('dcf_preview');
            
            // Store UTM parameters from URL for later use
            this.captureUTMParameters(urlParams);
            
            // Clear session data if in preview mode
            if (this.state.isPreviewMode) {
                localStorage.removeItem('dcf_popup_session');
                this.state.popupsShown = [];
                this.state.popupDisplayCounts = {};
                console.log('[DCF Popup Engine] Preview mode enabled - session cleared');
                if (this.state.previewPopupId) {
                    console.log('[DCF Popup Engine] Preview popup ID:', this.state.previewPopupId);
                }
            }
            
            // Add debug methods
            const self = this;
            
            // Add method to clear session for debugging
            window.dcfClearPopupSession = function() {
                localStorage.removeItem('dcf_popup_session');
                console.log('[DCF Popup Engine] Session cleared. Reload page to test popups again.');
            };
            
            // Add method to force show popup for debugging
            window.dcfShowPopup = function(popupId) {
                self.log('Manually showing popup:', popupId);
                self.showPopup(popupId || '15');
            };
            
            // Add debug method to check popup visibility
            window.dcfDebugPopup = function(popupId) {
                self.debugPopupVisibility(popupId || '1');
            };
            
            // Add debug method to check UTM parameters
            window.dcfDebugUTM = function() {
                console.log('[DCF Debug] Current UTM parameters:', self.state.utmParameters);
                console.log('[DCF Debug] SessionStorage UTM values:');
                ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_keyword', 'gclid'].forEach(param => {
                    const value = sessionStorage.getItem('dcf_' + param);
                    if (value) {
                        console.log(`  ${param}: ${value}`);
                    }
                });
                
                // Check current popup forms for UTM fields
                $('.dcf-popup form').each(function(index) {
                    const $form = $(this);
                    console.log(`[DCF Debug] Form ${index + 1} UTM fields:`);
                    $form.find('input[type="hidden"]').each(function() {
                        const name = $(this).attr('name');
                        const value = $(this).val();
                        if (name && (name.includes('utm_') || name.includes('gclid') || name.includes('campaign_'))) {
                            console.log(`  ${name}: ${value || '(empty)'}`);
                        }
                    });
                });
            };
            
            // Initialize multi-step handlers
            this.initMultiStepHandlers();
            
            this.log('Popup Engine initialized', this.config);
            this.log('Loaded popups:', this.config.popups);
            this.log('Preview mode:', this.state.isPreviewMode);
            
            // Log each popup's details
            if (this.config.popups && this.config.popups.length > 0) {
                this.config.popups.forEach(function(popup) {
                    console.log('[DCF Popup Engine] Popup details:', {
                        id: popup.id,
                        name: popup.name,
                        trigger_type: popup.triggers ? popup.triggers.type : 'unknown',
                        status: popup.status,
                        mobile_enabled: popup.mobile_enabled !== undefined ? popup.mobile_enabled : (popup.triggers ? popup.triggers.mobile_enabled : false)
                    });
                });
            }
            
            // Load session data (unless in preview mode)
            if (!this.state.isPreviewMode) {
                this.loadSessionData();
            }
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Process popups
            this.processPopups();
            
            // If preview mode with specific popup ID, show it immediately
            if (this.state.isPreviewMode && this.state.previewPopupId) {
                setTimeout(() => {
                    this.log('Showing preview popup:', this.state.previewPopupId);
                    this.showPopup(this.state.previewPopupId);
                }, 500); // Small delay to ensure everything is loaded
            }
        },
        
        /**
         * Load session data from localStorage
         */
        loadSessionData: function() {
            try {
                const stored = localStorage.getItem('dcf_popup_session');
                if (stored) {
                    this.state = $.extend(this.state, JSON.parse(stored));
                }
            } catch (e) {
                this.log('Error loading session data:', e);
            }
            
            // Update page views
            this.state.pageViews++;
            this.saveSessionData();
        },
        
        /**
         * Save session data to localStorage
         */
        saveSessionData: function() {
            // Skip saving in preview mode
            if (this.state.isPreviewMode) {
                return;
            }
            
            try {
                localStorage.setItem('dcf_popup_session', JSON.stringify({
                    pageViews: this.state.pageViews,
                    sessionStartTime: this.state.sessionStartTime,
                    popupsShown: this.state.popupsShown,
                    popupDisplayCounts: this.state.popupDisplayCounts
                }));
            } catch (e) {
                this.log('Error saving session data:', e);
            }
        },
        
        /**
         * Set up event listeners
         */
        setupEventListeners: function() {
            const self = this;
            
            // Scroll tracking
            $(window).on('scroll', $.throttle(100, function() {
                self.updateScrollPercentage();
                self.checkScrollTriggers();
            }));
            
            // Exit intent detection
            $(document).on('mouseleave', function(e) {
                if (e.clientY <= 0) {
                    self.triggerExitIntent();
                }
            });
            
            // Mobile exit intent (back button simulation)
            if (this.config.is_mobile) {
                let touchStartY = 0;
                $(document).on('touchstart', function(e) {
                    touchStartY = e.touches[0].clientY;
                });
                
                $(document).on('touchmove', function(e) {
                    const touchY = e.touches[0].clientY;
                    const touchDiff = touchY - touchStartY;
                    
                    // If user swipes down from top of screen
                    if (touchStartY < 50 && touchDiff > 50) {
                        self.triggerExitIntent();
                    }
                });
            }
            
            // User interaction tracking
            $(document).on('click scroll keydown', function() {
                self.state.userInteracted = true;
            });
            
            // Popup close handlers
            $(document).on('click', '.dcf-popup-close', function(e) {
                e.preventDefault();
                const popupId = $(this).closest('.dcf-popup').data('popup-id');
                self.closePopup(popupId, 'dismissed');
            });
            
            // Popup overlay close
            $(document).on('click', '.dcf-popup-overlay', function(e) {
                if (e.target === this) {
                    const popupId = $(this).find('.dcf-popup').data('popup-id');
                    const popup = self.getPopupConfig(popupId);
                    
                    if (popup && popup.design.close_on_overlay !== false) {
                        self.closePopup(popupId, 'dismissed');
                    }
                }
            });
            
            // Form submission handling with animations
            $(document).on('submit', '.dcf-popup form', function(e) {
                const $form = $(this);
                const $popup = $form.closest('.dcf-popup');
                const popupId = $popup.data('popup-id');
                const popup = self.getPopupConfig(popupId);
                
                // Add submitting state
                $form.addClass('dcf-form-submitting');
                
                // Track interaction
                self.trackInteraction(popupId, 'form_submitted');
                
                // Handle AJAX forms
                if ($form.hasClass('dcf-ajax-form') || $form.closest('.dcf-form').length) {
                    e.preventDefault();
                    
                    // Simulate form submission (actual AJAX handled elsewhere)
                    setTimeout(() => {
                        $form.removeClass('dcf-form-submitting');
                        
                        // Check if confetti is enabled
                        if (popup?.design?.confetti_on_success) {
                            self.showConfetti($popup);
                        }
                        
                        // Show success message
                        self.showFormSuccess($form);
                    }, 1500);
                }
            });
            
            // Form field validation with shake animation
            $(document).on('blur', '.dcf-popup input[required], .dcf-popup textarea[required]', function() {
                const $field = $(this);
                const $popup = $field.closest('.dcf-popup');
                const popup = self.getPopupConfig($popup.data('popup-id'));
                
                if (!$field.val().trim() && popup?.design?.shake_on_error !== false) {
                    $field.parent().addClass('dcf-shake-error dcf-error');
                    setTimeout(() => {
                        $field.parent().removeClass('dcf-shake-error');
                    }, 820);
                } else {
                    $field.parent().removeClass('dcf-error');
                }
            });
            
            // Dynamic click triggers
            this.setupClickTriggers();
        },
        
        /**
         * Set up click triggers for popups
         */
        setupClickTriggers: function() {
            const self = this;
            
            this.config.popups.forEach(function(popup) {
                if (popup.triggers && popup.triggers.type === 'click_trigger' && popup.triggers.selector) {
                    $(document).on('click', popup.triggers.selector, function(e) {
                        e.preventDefault();
                        self.showPopup(popup.id);
                    });
                }
            });
        },
        
        /**
         * Process all popups and set up triggers
         */
        processPopups: function() {
            const self = this;
            
            this.config.popups.forEach(function(popup) {
                self.setupPopupTrigger(popup);
            });
        },
        
        /**
         * Set up trigger for individual popup
         */
        setupPopupTrigger: function(popup) {
            const self = this;
            const triggers = popup.triggers || popup.trigger_settings || {};
            
            // If no triggers at all, log warning
            if (!popup.triggers && !popup.trigger_settings) {
                this.log('Warning: Popup has no trigger configuration:', popup.id, popup.name);
                // Popup can still be shown manually with dcfShowPopup()
                return;
            }
            
            // Skip if popup already shown (unless in preview mode)
            if (!this.state.isPreviewMode && this.state.popupsShown.includes(String(popup.id))) {
                this.log('Popup already shown in session, skipping:', popup.id);
                return;
            }
            
            switch (triggers.type) {
                case 'time_delay':
                    const delay = (triggers.delay || 5) * 1000;
                    this.log('Setting up time delay trigger for popup:', popup.id, 'Delay:', delay + 'ms');
                    setTimeout(function() {
                        self.log('Time delay reached, showing popup:', popup.id);
                        self.showPopup(popup.id);
                    }, delay);
                    break;
                    
                case 'scroll_percentage':
                    // Will be checked in scroll handler
                    break;
                    
                case 'exit_intent':
                    // Will be triggered by exit intent handler
                    break;
                    
                case 'page_views':
                    if (this.state.pageViews >= (triggers.count || 3)) {
                        this.showPopup(popup.id);
                    }
                    break;
                    
                case 'session_time':
                    const sessionMinutes = (Date.now() - this.state.sessionStartTime) / (1000 * 60);
                    const requiredMinutes = triggers.minutes || 2;
                    
                    if (sessionMinutes >= requiredMinutes) {
                        this.showPopup(popup.id);
                    } else {
                        setTimeout(function() {
                            self.showPopup(popup.id);
                        }, (requiredMinutes - sessionMinutes) * 60 * 1000);
                    }
                    break;
                    
                case 'click_trigger':
                    // Already handled in setupClickTriggers
                    break;
                    
                default:
                    this.log('Unknown trigger type:', triggers.type);
            }
        },
        
        /**
         * Update scroll percentage
         */
        updateScrollPercentage: function() {
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height();
            const winHeight = $(window).height();
            const scrollPercent = Math.round(scrollTop / (docHeight - winHeight) * 100);
            
            this.state.scrollPercentage = Math.min(100, Math.max(0, scrollPercent));
        },
        
        /**
         * Check scroll-based triggers
         */
        checkScrollTriggers: function() {
            const self = this;
            
            this.config.popups.forEach(function(popup) {
                if (popup.triggers.type === 'scroll_percentage' && 
                    !self.state.popupsShown.includes(popup.id)) {
                    
                    const requiredPercent = popup.triggers.percentage || 50;
                    if (self.state.scrollPercentage >= requiredPercent) {
                        self.showPopup(popup.id);
                    }
                }
            });
        },
        
        /**
         * Trigger exit intent
         */
        triggerExitIntent: function() {
            this.log('Exit intent triggered');
            
            if (this.state.exitIntentTriggered) {
                this.log('Exit intent already triggered, skipping');
                return;
            }
            
            this.state.exitIntentTriggered = true;
            const self = this;
            
            this.log('Checking popups for exit intent trigger');
            this.config.popups.forEach(function(popup) {
                if (!popup.triggers || !popup.triggers.type) {
                    self.log('Popup has no triggers or trigger type:', popup.id, popup.triggers);
                    return;
                }
                
                self.log('Checking popup:', popup.id, popup.name, 'Trigger type:', popup.triggers.type);
                
                self.log('Popup triggers:', popup.triggers);
                self.log('Popups already shown:', self.state.popupsShown);
                self.log('Is mobile:', self.config.is_mobile);
                
                if (popup.triggers.type === 'exit_intent' && 
                    !self.state.popupsShown.includes(String(popup.id))) {
                    
                    // Check if mobile is enabled for this trigger
                    const mobileEnabled = popup.mobile_enabled !== undefined ? popup.mobile_enabled : 
                                         (popup.triggers && popup.triggers.mobile_enabled);
                    if (self.config.is_mobile && !mobileEnabled) {
                        self.log('Popup skipped - mobile not enabled for this trigger');
                        return;
                    }
                    
                    self.log('Exit intent popup will be shown:', popup.id);
                    const delay = popup.triggers.delay || 0;
                    setTimeout(function() {
                        self.showPopup(popup.id);
                    }, delay * 1000);
                } else {
                    self.log('Popup not shown. Reasons:');
                    self.log('- Trigger type match:', popup.triggers.type === 'exit_intent');
                    self.log('- Not already shown:', !self.state.popupsShown.includes(popup.id));
                }
            });
        },
        
        /**
         * Show popup
         */
        showPopup: function(popupId) {
            this.log('showPopup called for ID:', popupId);
            
            const popup = this.getPopupConfig(popupId);
            if (!popup) {
                this.log('Popup not found:', popupId);
                return;
            }
            
            this.log('Popup config found:', popup);
            this.log('Popup content:', popup.content ? popup.content.substring(0, 100) + '...' : 'No content');
            
            // Check if popup is already active
            if (this.config.activePopups.includes(String(popupId))) {
                this.log('Popup already active, skipping');
                return;
            }
            
            // Check if popup was already shown (unless in preview mode)
            if (!this.state.isPreviewMode && this.state.popupsShown.includes(String(popupId))) {
                this.log('Popup already shown in this session, skipping');
                return;
            }
            
            this.log('Showing popup:', popupId);
            
            // Remove any existing popups with the same ID to avoid duplicates
            $(`[data-popup-id="${popupId}"]`).closest('.dcf-popup-overlay').remove();
            $(`[data-popup-id="${popupId}"]`).remove();
            $(`.dcf-popup-overlay[data-overlay-id="${popupId}"]`).remove();
            
            // Create popup HTML
            const popupHtml = this.createPopupHtml(popup);
            
            this.log('Created popup HTML (first 500 chars):', popupHtml.substring(0, 500));
            this.log('Popup type:', popup.type);
            this.log('Popup design:', popup.design);
            
            // For modal, multi-step, and split-screen popups, append directly to body for proper positioning
            if (popup.type === 'modal' || popup.type === 'multi-step' || popup.type === 'split-screen') {
                $('body').append(popupHtml);
                this.log('Added popup with overlay directly to body');
            } else {
                // For other popup types (sidebar, bar), use container
                if ($('#dcf-popup-container').length === 0) {
                    $('body').append('<div id="dcf-popup-container" style="position: fixed; top: 0; left: 0; width: 0; height: 0; z-index: 999998;"></div>');
                    this.log('Created popup container');
                }
                $('#dcf-popup-container').append(popupHtml);
                this.log('Added popup HTML to container');
            }
            
            // Verify popup was added
            const $allPopups = $(`[data-popup-id="${popupId}"]`);
            this.log('Number of popups found with this ID:', $allPopups.length);
            
            // Get the popup we just added (should be the last one)
            const $addedPopup = $allPopups.last();
            if ($addedPopup.length === 0) {
                this.log('ERROR: Popup not found after adding to DOM!');
                return;
            }
            
            // Check if popup is inside overlay
            const $overlayParent = $addedPopup.closest('.dcf-popup-overlay');
            this.log('Popup element found:', $addedPopup[0]);
            this.log('Popup has overlay parent:', $overlayParent.length > 0);
            this.log('Initial popup state:', {
                display: $addedPopup.css('display'),
                opacity: $addedPopup.css('opacity'),
                visibility: $addedPopup.css('visibility')
            });
            
            // Add to active popups
            this.config.activePopups.push(String(popupId));
            this.state.popupsShown.push(String(popupId));
            
            // Save session data
            this.saveSessionData();
            
            // Show with animation
            this.animatePopupIn(popupId);
            
            // Store popup open time for tracking
            this.state.popupOpenTime = Date.now();
            
            // Trigger analytics event for popup show
            $(document).trigger('dcf:popup:show', {
                popup_id: popupId,
                popup_type: popup.type,
                trigger_type: popup.trigger || 'manual'
            });
            
            // Initialize countdown timers if any
            setTimeout(() => {
                this.initCountdownTimers();
            }, 100);
            
            // Load form content if there are placeholders
            setTimeout(() => {
                const $popup = $(`[data-popup-id="${popupId}"]`);
                this.loadFormPlaceholders($popup);
            }, 100);
            
            // Populate UTM fields if form exists in popup
            setTimeout(() => {
                const $popup = $(`[data-popup-id="${popupId}"]`);
                if ($popup.find('form').length > 0) {
                    this.populateUTMFields($popup);
                    
                    // Also trigger the DCF initialization for form functionality
                    if (window.DCF && typeof window.DCF.initUTMTracking === 'function') {
                        window.DCF.initUTMTracking();
                    }
                }
            }, 200);
            
            // Track display
            this.trackDisplay(popupId);
            
            // Set up auto-close if configured
            this.setupAutoClose(popup);
        },
        
        /**
         * Ensure CSS value has units (add 'px' if it's just a number)
         */
        ensureUnits: function(value, defaultValue) {
            if (!value) return defaultValue;
            // If value is just a number, add 'px'
            if (/^\d+$/.test(value.toString())) {
                return value + 'px';
            }
            return value;
        },
        
        /**
         * Create popup HTML
         */
        createPopupHtml: function(popup) {
            const design = popup.design || {};
            const config = popup.config || {};
            
            let popupClass = 'dcf-popup dcf-popup-' + popup.type;
            let overlayClass = 'dcf-popup-overlay';
            
            // Add mobile class if needed
            if (this.config.is_mobile) {
                popupClass += ' dcf-popup-mobile';
            }
            
            // Create popup structure based on type
            let popupHtml = '';
            
            switch (popup.type) {
                case 'modal':
                    popupHtml = this.createModalHtml(popup, popupClass, overlayClass);
                    break;
                case 'sidebar':
                    popupHtml = this.createSidebarHtml(popup, popupClass);
                    break;
                case 'bar':
                    popupHtml = this.createBarHtml(popup, popupClass);
                    break;
                case 'multi-step':
                    popupHtml = this.createMultiStepHtml(popup, popupClass, overlayClass);
                    break;
                case 'split-screen':
                    popupHtml = this.createSplitScreenHtml(popup, popupClass, overlayClass);
                    break;
                default:
                    popupHtml = this.createModalHtml(popup, popupClass, overlayClass);
            }
            
            return popupHtml;
        },
        
        /**
         * Create split-screen popup HTML
         */
        createSplitScreenHtml: function(popup, popupClass, overlayClass) {
            const design = popup.design || {};
            const config = popup.config || {};
            
            // Responsive width based on device
            const defaultWidth = this.config.is_mobile ? '95%' : this.ensureUnits(design.width, '900px');
            const defaultHeight = this.config.is_mobile ? 'auto' : this.ensureUnits(design.height, '500px');
            
            // Build styles for the popup container
            const popupStyles = this.buildPopupStyles(design, {
                width: defaultWidth,
                height: defaultHeight,
                maxWidth: '90vw',
                maxHeight: '90vh',
                backgroundColor: '#ffffff',
                borderRadius: this.ensureUnits(design.border_radius, '8px'),
                position: 'relative',
                margin: '0 auto',
                overflow: 'hidden',
                display: 'flex',
                flexDirection: design.split_layout === 'image-right' ? 'row-reverse' : 'row',
                boxShadow: '0 10px 40px rgba(0, 0, 0, 0.2)'
            });
            
            // Build overlay styles
            const overlayStyles = this.buildPopupStyles(design, {
                position: 'fixed !important',
                top: '0 !important',
                left: '0 !important',
                right: '0 !important',
                bottom: '0 !important',
                width: '100%',
                height: '100%',
                backgroundColor: design.overlay_color || 'rgba(0,0,0,0.7)',
                zIndex: '999999',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });
            
            // Calculate split ratio
            const splitRatio = design.split_ratio || '50-50';
            const [imageWidth, contentWidth] = splitRatio.split('-');
            
            // Build image section styles
            const imageStyles = {
                flex: `0 0 ${imageWidth}%`,
                backgroundImage: design.split_image ? `url(${design.split_image})` : '',
                backgroundPosition: design.split_image_position || 'center center',
                backgroundSize: design.split_image_size || 'cover',
                backgroundRepeat: 'no-repeat',
                backgroundColor: design.split_image_bg || '#e8e8e8',
                minHeight: '100%',
                position: 'relative'
            };
            
            // Build content section styles
            const contentStyles = {
                flex: `0 0 ${contentWidth}%`,
                backgroundColor: design.split_content_bg || '#5DBCD2',
                padding: design.split_content_padding || '40px',
                overflowY: 'auto',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center'
            };
            
            // Generate dynamic styles
            const dynamicStyles = this.generateDynamicStyles(popup.id, design);
            
            // Get content HTML dynamically
            let contentHtml = this.getPopupContentHtml(popup);
            
            // Debug log
            this.log('Split-screen popup content:', contentHtml ? contentHtml.substring(0, 200) + '...' : 'No content');
            this.log('Content section background:', design.split_content_bg);
            
            return `
                ${dynamicStyles}
                <div class="${overlayClass}" data-overlay-id="${popup.id}" style="${overlayStyles}">
                    <div class="${popupClass} dcf-split-screen-popup" data-popup-id="${popup.id}" style="${popupStyles}">
                        ${this.getCloseButton(design)}
                        <div class="dcf-split-image-section" style="${this.buildPopupStyles(design, imageStyles)}"></div>
                        <div class="dcf-split-content-section" style="${this.buildPopupStyles(design, contentStyles)}">
                            <div class="dcf-popup-content">
                                ${contentHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Create modal popup HTML
         */
        createModalHtml: function(popup, popupClass, overlayClass) {
            const design = popup.design || {};
            
            // Responsive width based on device
            const defaultWidth = this.config.is_mobile ? '95%' : this.ensureUnits(design.width, '500px');
            const defaultPadding = this.config.is_mobile ? '20px' : this.ensureUnits(design.padding, '30px');
            
            // Build background styles
            const backgroundStyles = {};
            if (design.use_gradient && design.gradient_color_1 && design.gradient_color_2) {
                // Build gradient background
                let gradient;
                if (design.gradient_type === 'radial') {
                    gradient = `radial-gradient(circle, ${design.gradient_color_1}, ${design.gradient_color_2}`;
                    if (design.gradient_add_third && design.gradient_color_3) {
                        gradient = `radial-gradient(circle, ${design.gradient_color_1}, ${design.gradient_color_2}, ${design.gradient_color_3}`;
                    }
                    gradient += ')';
                } else {
                    // Linear gradient
                    const angle = design.gradient_angle || 135;
                    gradient = `linear-gradient(${angle}deg, ${design.gradient_color_1}, ${design.gradient_color_2}`;
                    if (design.gradient_add_third && design.gradient_color_3) {
                        gradient = `linear-gradient(${angle}deg, ${design.gradient_color_1}, ${design.gradient_color_2}, ${design.gradient_color_3}`;
                    }
                    gradient += ')';
                }
                backgroundStyles.background = gradient;
            } else if (design.background_image) {
                backgroundStyles.backgroundImage = `url(${design.background_image})`;
                backgroundStyles.backgroundPosition = design.background_position || 'center center';
                backgroundStyles.backgroundSize = design.background_size || 'cover';
                backgroundStyles.backgroundRepeat = design.background_repeat || 'no-repeat';
            }
            
            const styles = this.buildPopupStyles(design, {
                width: defaultWidth,
                maxWidth: '90vw',
                backgroundColor: design.background_color || '#ffffff',
                borderRadius: this.ensureUnits(design.border_radius, '8px'),
                padding: defaultPadding,
                position: 'relative',
                margin: '20px auto',  // Small vertical margin for breathing room
                maxHeight: 'calc(90vh - 40px)',  // Leave space at top and bottom
                overflowY: 'auto',
                overflowX: 'hidden',
                boxShadow: '0 4px 20px rgba(0,0,0,0.15)',  // Added shadow for better visibility
                fontSize: design.font_size || '16px',
                fontWeight: design.font_weight || '400',
                lineHeight: design.line_height || '1.6',
                textAlign: design.text_align || 'center',
                color: design.text_color || '#333333',
                ...backgroundStyles
            });
            
            const overlayStyles = this.buildPopupStyles(design, {
                position: 'fixed !important',
                top: '0 !important',
                left: '0 !important',
                right: '0 !important',
                bottom: '0 !important',
                width: '100%',
                height: '100%',
                backgroundColor: design.overlay_color || 'rgba(0,0,0,0.7)',
                zIndex: '999999',
                display: 'none',
                alignItems: 'center',
                justifyContent: 'center',
                overflow: 'auto'
            });
            
            // Generate dynamic styles for headings and other elements
            const dynamicStyles = this.generateDynamicStyles(popup.id, design);
            
            return `
                ${dynamicStyles}
                <div class="${overlayClass}" data-overlay-id="${popup.id}" style="${overlayStyles}">
                    <div class="${popupClass}" data-popup-id="${popup.id}" style="${styles}">
                        ${this.getCloseButton(design)}
                        <div class="dcf-popup-content">
                            ${this.getPopupContentHtml(popup)}
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Create sidebar popup HTML
         */
        createSidebarHtml: function(popup, popupClass) {
            const design = popup.design || {};
            const position = design.position || 'right';
            
            const width = this.ensureUnits(design.width, '300px');
            const height = this.ensureUnits(design.height, 'auto');
            
            // Build background styles
            const backgroundStyles = {};
            if (design.use_gradient && design.gradient_color_1 && design.gradient_color_2) {
                // Build gradient background
                let gradient;
                if (design.gradient_type === 'radial') {
                    gradient = `radial-gradient(circle, ${design.gradient_color_1}, ${design.gradient_color_2}`;
                    if (design.gradient_add_third && design.gradient_color_3) {
                        gradient = `radial-gradient(circle, ${design.gradient_color_1}, ${design.gradient_color_2}, ${design.gradient_color_3}`;
                    }
                    gradient += ')';
                } else {
                    // Linear gradient
                    const angle = design.gradient_angle || 135;
                    gradient = `linear-gradient(${angle}deg, ${design.gradient_color_1}, ${design.gradient_color_2}`;
                    if (design.gradient_add_third && design.gradient_color_3) {
                        gradient = `linear-gradient(${angle}deg, ${design.gradient_color_1}, ${design.gradient_color_2}, ${design.gradient_color_3}`;
                    }
                    gradient += ')';
                }
                backgroundStyles.background = gradient;
            } else if (design.background_image) {
                backgroundStyles.backgroundImage = `url(${design.background_image})`;
                backgroundStyles.backgroundPosition = design.background_position || 'center center';
                backgroundStyles.backgroundSize = design.background_size || 'cover';
                backgroundStyles.backgroundRepeat = design.background_repeat || 'no-repeat';
            }
            
            const styles = this.buildPopupStyles(design, {
                position: 'fixed',
                top: this.ensureUnits(design.top, '20px'),
                [position]: '-' + width,
                width: width,
                height: height,
                maxHeight: '80vh',
                backgroundColor: design.background_color || '#f8f9fa',
                borderRadius: this.ensureUnits(design.border_radius, '8px 0 0 8px'),
                padding: this.ensureUnits(design.padding, '20px'),
                zIndex: '999999',
                overflow: 'auto',
                boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
                ...backgroundStyles
            });
            
            // Generate dynamic styles for headings and other elements
            const dynamicStyles = this.generateDynamicStyles(popup.id, design);
            
            return `
                ${dynamicStyles}
                <div class="${popupClass}" data-popup-id="${popup.id}" style="${styles}">
                    ${this.getCloseButton(design)}
                    <div class="dcf-popup-content">
                        ${this.getPopupContentHtml(popup)}
                    </div>
                </div>
            `;
        },
        
        /**
         * Create bar popup HTML
         */
        createBarHtml: function(popup, popupClass) {
            const design = popup.design || {};
            const position = design.position || 'bottom';
            
            const styles = this.buildPopupStyles(design, {
                position: 'fixed',
                [position]: '0',
                left: '0',
                width: '100%',
                height: this.ensureUnits(design.height, '80px'),
                backgroundColor: design.background_color || '#2271b1',
                color: design.text_color || '#ffffff',
                padding: this.ensureUnits(design.padding, '15px 20px'),
                zIndex: '999999',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                transform: position === 'bottom' ? 'translateY(100%)' : 'translateY(-100%)'
            });
            
            return `
                <div class="${popupClass}" data-popup-id="${popup.id}" style="${styles}">
                    <div class="dcf-popup-content" style="flex: 1;">
                        ${this.getPopupContentHtml(popup)}
                    </div>
                    ${this.getCloseButton(design, true)}
                </div>
            `;
        },
        
        /**
         * Get popup content HTML
         */
        getPopupContentHtml: function(popup) {
            const config = popup.config || {};
            
            // Debug log the config to see what we're working with
            console.log('[DCF Debug] Popup config:', config);
            console.log('[DCF Debug] Popup full data:', popup);
            
            // Check if visual editor content
            if (config.visual_editor && config.steps) {
                return this.renderVisualEditorContent({
                    steps: config.steps,
                    settings: config.settings || {}
                });
            }
            
            // Check if blocks directly in config
            if (config.blocks && Array.isArray(config.blocks)) {
                return this.renderVisualEditorContent(config);
            }
            
            // Check for template content
            if (config.template_content && config.template_content.steps) {
                return this.renderMultiStepContent(config, config.template_content);
            }
            
            // Check for raw content that might contain shortcodes
            if (config.content && typeof config.content === 'string') {
                // Check if content contains a form shortcode
                const formShortcodeMatch = config.content.match(/\[dcf_form\s+id="(\d+)"\]/);
                if (formShortcodeMatch) {
                    const formId = formShortcodeMatch[1];
                    return this.renderFormContent(formId);
                }
                // Return raw content if no shortcode found
                return config.content;
            }
            
            // Also check if content is directly on the popup object (not in config)
            if (popup.content && typeof popup.content === 'string') {
                // Check if content contains a form shortcode
                const formShortcodeMatch = popup.content.match(/\[dcf_form\s+id="(\d+)"\]/);
                if (formShortcodeMatch) {
                    const formId = formShortcodeMatch[1];
                    console.log('[DCF Debug] Found form shortcode in popup.content, form ID:', formId);
                    return this.renderFormContent(formId);
                }
                // Return raw content if no shortcode found
                return popup.content;
            }
            
            // Default to form-based content
            if (config.form_id) {
                return this.renderFormContent(config.form_id);
            }
            
            return '<p>No content configured for this popup.</p>';
        },
        
        /**
         * Render form content
         */
        renderFormContent: function(formId) {
            // Create a placeholder that will be replaced with actual form content
            return `<div class="dcf-form-placeholder" data-form-id="${formId}">
                <div class="dcf-loading">
                    <span class="dcf-spinner"></span>
                    <p>Loading form...</p>
                </div>
            </div>`;
        },
        
        /**
         * Render visual editor content
         */
        renderVisualEditorContent: function(visualEditorData) {
            console.log('[DCF Debug] renderVisualEditorContent called with:', visualEditorData);
            
            if (!visualEditorData || !Array.isArray(visualEditorData.steps)) {
                return '';
            }
            
            let html = '<div class="dcf-multi-step-popup" data-total-steps="' + visualEditorData.steps.length + '">';
            
            visualEditorData.steps.forEach((step, index) => {
                console.log('[DCF Debug] Processing step:', index, step);
                
                const stepIndex = index + 1;
                const stepId = 'step-' + stepIndex;
                
                html += '<div class="dcf-popup-step" data-step-id="' + stepId + '" data-step-index="' + index + '"';
                html += index === 0 ? ' style="display: block;">' : ' style="display: none;">';
                
                if (step.blocks && Array.isArray(step.blocks)) {
                    step.blocks.forEach((block, blockIndex) => {
                        console.log('[DCF Debug] Processing block:', blockIndex, block);
                        html += this.renderBlock(block);
                    });
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            return html;
        },
        
        /**
         * Render a single block
         */
        renderBlock: function(block) {
            if (!block || !block.type) return '';
            
            const settings = block.settings || {};
            
            switch (block.type) {
                case 'heading':
                    const level = settings.level || 'h2';
                    const text = settings.text || '';
                    let styles = [];
                    if (settings.fontSize) styles.push('font-size: ' + settings.fontSize);
                    if (settings.fontWeight) styles.push('font-weight: ' + settings.fontWeight);
                    if (settings.textAlign) styles.push('text-align: ' + settings.textAlign);
                    if (settings.color) styles.push('color: ' + settings.color);
                    const styleAttr = styles.length ? ' style="' + styles.join('; ') + '"' : '';
                    return '<' + level + ' class="dcf-block dcf-block-heading"' + styleAttr + '>' + text + '</' + level + '>';
                    
                case 'text':
                    const content = settings.content || settings.text || block.content || '';
                    console.log('[DCF Debug] Text block content:', content);
                    
                    // Check if content contains a form shortcode
                    const formShortcodeMatch = content.match(/\[dcf_form\s+id="(\d+)"\]/);
                    if (formShortcodeMatch) {
                        const formId = formShortcodeMatch[1];
                        console.log('[DCF Debug] Found form shortcode in text block, form ID:', formId);
                        return this.renderFormContent(formId);
                    }
                    
                    let textStyles = [];
                    if (settings.fontSize) textStyles.push('font-size: ' + settings.fontSize);
                    if (settings.textAlign) textStyles.push('text-align: ' + settings.textAlign);
                    if (settings.color) textStyles.push('color: ' + settings.color);
                    const textStyleAttr = textStyles.length ? ' style="' + textStyles.join('; ') + '"' : '';
                    return '<div class="dcf-block dcf-block-text"' + textStyleAttr + '>' + content + '</div>';
                    
                case 'form':
                    console.log('[DCF Debug] Form block settings:', settings);
                    console.log('[DCF Debug] Form block full data:', block);
                    
                    // Check if the form ID is in the content as a shortcode
                    if (block.content && typeof block.content === 'string') {
                        const formShortcodeMatch = block.content.match(/\[dcf_form\s+id="(\d+)"\]/);
                        if (formShortcodeMatch) {
                            const formId = formShortcodeMatch[1];
                            console.log('[DCF Debug] Found form shortcode in form block content, form ID:', formId);
                            return this.renderFormContent(formId);
                        }
                    }
                    
                    let formId = settings.formId || 0;
                    
                    // Convert string to number if needed
                    if (typeof formId === 'string' && formId) {
                        formId = parseInt(formId, 10);
                    }
                    
                    if (!formId) {
                        console.log('[DCF Debug] No form ID found in form block');
                        return '<p class="dcf-block dcf-block-form">No form selected</p>';
                    }
                    console.log('[DCF Debug] Rendering form with ID:', formId);
                    return this.renderFormContent(formId);
                    
                case 'button':
                    const buttonText = settings.text || 'Click Me';
                    const action = settings.action || 'close';
                    let buttonClasses = 'dcf-block dcf-block-button dcf-button';
                    let dataAttrs = '';
                    
                    if (action === 'close') {
                        buttonClasses += ' dcf-popup-close';
                        dataAttrs = ' data-action="close"';
                    } else if (action === 'next' && settings.nextStep) {
                        buttonClasses += ' dcf-next-button';
                        dataAttrs = ' data-action="next" data-next-step="' + settings.nextStep + '"';
                    }
                    
                    let buttonStyles = [];
                    if (settings.bgColor) buttonStyles.push('background-color: ' + settings.bgColor);
                    if (settings.textColor) buttonStyles.push('color: ' + settings.textColor);
                    if (settings.borderRadius) buttonStyles.push('border-radius: ' + settings.borderRadius);
                    if (settings.padding) buttonStyles.push('padding: ' + settings.padding);
                    if (settings.fontSize) buttonStyles.push('font-size: ' + settings.fontSize);
                    const buttonStyleAttr = buttonStyles.length ? ' style="' + buttonStyles.join('; ') + '"' : '';
                    
                    return '<button class="' + buttonClasses + '"' + buttonStyleAttr + dataAttrs + '>' + buttonText + '</button>';
                    
                default:
                    return '';
            }
        },
        
        /**
         * Create multi-step popup HTML
         */
        createMultiStepHtml: function(popup, popupClass, overlayClass) {
            const design = popup.design || {};
            
            const styles = this.buildPopupStyles(design, {
                width: this.ensureUnits(design.width, '600px'),
                maxWidth: '90vw',
                backgroundColor: design.background_color || '#ffffff',
                borderRadius: this.ensureUnits(design.border_radius, '12px'),
                padding: '0',
                position: 'relative',
                margin: '50px auto',
                maxHeight: '80vh',
                overflow: 'hidden'
            });
            
            const overlayStyles = this.buildPopupStyles(design, {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                backgroundColor: design.overlay_color || 'rgba(0,0,0,0.7)',
                zIndex: '999999',
                display: 'none'
            });
            
            return `
                <div class="${overlayClass}" style="${overlayStyles}">
                    <div class="${popupClass} dcf-popup-multi-step" data-popup-id="${popup.id}" style="${styles}">
                        ${this.getCloseButton(design)}
                        ${design.progress_bar ? '<div class="dcf-popup-progress"><div class="dcf-popup-progress-bar"></div></div>' : ''}
                        <div class="dcf-popup-content" style="padding: 30px;">
                            ${this.getPopupContentHtml(popup)}
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Get close button HTML
         */
        getCloseButton: function(design, inline = false) {
            if (design.close_button === false) {
                return '';
            }
            
            const buttonStyle = inline ? 
                'background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; padding: 5px;' :
                'position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #666; z-index: 1;';
            
            return `<button class="dcf-popup-close" style="${buttonStyle}" aria-label="Close popup">&times;</button>`;
        },
        
        /**
         * Load form placeholders via AJAX
         */
        loadFormPlaceholders: function($popup) {
            const self = this;
            console.log('[DCF Debug] loadFormPlaceholders called, looking for placeholders in:', $popup);
            
            const placeholders = $popup.find('.dcf-form-placeholder');
            console.log('[DCF Debug] Found placeholders:', placeholders.length);
            
            placeholders.each(function() {
                const $placeholder = $(this);
                const formId = $placeholder.data('form-id');
                console.log('[DCF Debug] Processing placeholder with form ID:', formId);
                
                if (!formId) return;
                
                // Load form via AJAX
                console.log('[DCF Debug] Making AJAX request with:', {
                    url: self.config.ajax_url,
                    action: 'dcf_get_form_html',
                    form_id: formId,
                    nonce: self.config.nonce
                });
                
                $.ajax({
                    url: self.config.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dcf_get_form_html',
                        form_id: formId,
                        popup_mode: true,
                        nonce: self.config.nonce
                    },
                    success: function(response) {
                        console.log('[DCF Debug] AJAX success response:', response);
                        
                        if (response.success && response.data.html) {
                            console.log('[DCF Debug] Replacing placeholder with form HTML');
                            $placeholder.replaceWith(response.data.html);
                            
                            // Reinitialize form functionality if DCF is available
                            if (window.DCF && typeof window.DCF.initFormValidation === 'function') {
                                window.DCF.initFormValidation();
                            }
                            
                            // Ensure events are not double-bound by triggering a custom event
                            $placeholder.trigger('dcf:form:loaded', { formId: formId });
                        } else {
                            console.log('[DCF Debug] AJAX response error:', response);
                            $placeholder.html('<p class="dcf-error">Error loading form: ' + (response.data?.message || 'Unknown error') + '</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('[DCF Debug] AJAX error:', {xhr: xhr, status: status, error: error});
                        $placeholder.html('<p class="dcf-error">Error loading form: ' + error + '</p>');
                    }
                });
            });
        },
        
        /**
         * Build popup styles string
         */
        buildPopupStyles: function(design, baseStyles) {
            const styles = $.extend({}, baseStyles);
            
            // Apply custom styles from design
            if (design.custom_css) {
                // Parse and apply custom CSS
                const customStyles = this.parseCustomCSS(design.custom_css);
                $.extend(styles, customStyles);
            }
            
            // Convert styles object to CSS string
            return Object.keys(styles).map(key => {
                const cssKey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
                return `${cssKey}: ${styles[key]}`;
            }).join('; ');
        },
        
        /**
         * Parse custom CSS
         */
        parseCustomCSS: function(css) {
            const styles = {};
            const rules = css.split(';');
            
            rules.forEach(rule => {
                const [property, value] = rule.split(':').map(s => s.trim());
                if (property && value) {
                    const camelProperty = property.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
                    styles[camelProperty] = value;
                }
            });
            
            return styles;
        },
        
        /**
         * Animate popup in
         */
        animatePopupIn: function(popupId) {
            // Find the overlay first for modal popups
            let $overlay = $(`.dcf-popup-overlay[data-overlay-id="${popupId}"]`);
            let $popup;
            
            if ($overlay.length > 0) {
                // Get the popup inside the overlay
                $popup = $overlay.find(`[data-popup-id="${popupId}"]`);
                this.log('Found overlay with popup inside');
            } else {
                // For non-modal popups, find the popup directly
                $popup = $(`[data-popup-id="${popupId}"]`).last();
                this.log('No overlay found, using popup directly');
            }
            
            const popup = this.getPopupConfig(popupId);
            const design = popup.design || {};
            const animation = design.animation || 'fadeIn';
            const duration = design.animation_duration || 500;
            const delay = design.animation_delay || 0;
            
            // Add hover effects class if enabled
            if (design.hover_effects !== false) {
                $popup.addClass('dcf-hover-effects');
            }
            
            // Add pulse effect to CTA if enabled
            if (design.pulse_cta) {
                $popup.find('button[type="submit"], .dcf-submit-button, .dcf-yes-button').addClass('dcf-pulse-cta');
            }
            
            this.log('Animating popup in:', popupId, 'Animation:', animation, 'Duration:', duration, 'Delay:', delay);
            
            // Show overlay if it exists
            if ($overlay.length) {
                // Ensure overlay is properly positioned and visible
                $overlay.css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'right': '0',
                    'bottom': '0',
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center',
                    'visibility': 'visible',
                    'opacity': '0'
                });
                
                // Fade in the overlay
                $overlay.animate({
                    opacity: 1
                }, 300);
                
                this.log('Overlay shown with forced positioning');
                
                // Check if body has transform that might break fixed positioning
                const bodyTransform = $('body').css('transform');
                if (bodyTransform && bodyTransform !== 'none') {
                    this.log('Warning: Body has transform that may affect fixed positioning:', bodyTransform);
                }
                
                // Scroll to top to ensure popup is visible
                window.scrollTo(0, 0);
            } else {
                this.log('Warning: No overlay found for popup!');
                // For debugging, let's check what we actually have
                this.log('Popup element classes:', $popup.attr('class'));
                this.log('Popup parent:', $popup.parent()[0]);
            }
            
            // Ensure popup starts with proper state for animation
            $popup.css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '0' // Start with 0 opacity for fade in
            });
            
            // Apply animation after delay
            setTimeout(() => {
                // Add animation classes and animate opacity
                $popup.addClass('dcf-animated').css({
                    'animation-duration': duration + 'ms',
                    'visibility': 'visible',
                    'display': 'block'
                });
                
                // Manually animate opacity since CSS animation might be overridden
                $popup.animate({
                    opacity: 1
                }, duration);
                
                // Add specific animation class
                switch (animation) {
                    case 'fadeIn':
                        $popup.addClass('dcf-fadeIn');
                        break;
                    case 'slideInUp':
                        $popup.addClass('dcf-slideInUp');
                        break;
                    case 'slideInDown':
                        $popup.addClass('dcf-slideInDown');
                        break;
                    case 'slideInLeft':
                        $popup.addClass('dcf-slideInLeft');
                        break;
                    case 'slideInRight':
                        $popup.addClass('dcf-slideInRight');
                        break;
                    case 'bounceIn':
                        $popup.addClass('dcf-bounceIn');
                        break;
                    case 'zoomIn':
                        $popup.addClass('dcf-zoomIn');
                        break;
                    case 'rotateIn':
                        $popup.addClass('dcf-rotateIn');
                        break;
                    case 'flipIn':
                        $popup.addClass('dcf-flipIn');
                        break;
                    case 'rubberBand':
                        $popup.addClass('dcf-rubberBand');
                        break;
                    default:
                        $popup.addClass('dcf-fadeIn');
                }
            }, delay);
            
            // Log final state and ensure visibility
            setTimeout(() => {
                const finalDisplay = $popup.css('display');
                const finalOpacity = $popup.css('opacity');
                const finalVisibility = $popup.css('visibility');
                
                this.log('Popup animation complete. Display:', finalDisplay, 'Opacity:', finalOpacity, 'Visibility:', finalVisibility);
                
                // Force visibility if still hidden
                if (finalVisibility === 'hidden' || finalOpacity === '0') {
                    $popup.css({
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    this.log('Forced visibility on popup');
                }
                
                // Also check overlay visibility
                const $overlay = $popup.closest('.dcf-popup-overlay');
                if ($overlay.length > 0) {
                    const overlayDisplay = $overlay.css('display');
                    const overlayVisibility = $overlay.css('visibility');
                    const overlayOpacity = $overlay.css('opacity');
                    this.log('Overlay state:', {
                        display: overlayDisplay,
                        visibility: overlayVisibility,
                        opacity: overlayOpacity
                    });
                    
                    // Force overlay visibility
                    if (overlayDisplay === 'none' || overlayVisibility === 'hidden' || overlayOpacity === '0') {
                        $overlay.css({
                            'display': 'flex',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                        this.log('Forced visibility on overlay');
                    }
                    
                    // Check popup computed position
                    const popupPosition = $popup.css('position');
                    const popupOffset = $popup.offset();
                    this.log('Popup computed position:', popupPosition);
                    this.log('Popup offset:', popupOffset);
                    
                    // Check for conflicting styles
                    if (popupPosition === 'fixed') {
                        this.log('WARNING: Popup has fixed position - should be relative!');
                        $popup.css('position', 'relative');
                    }
                }
                
                // After popup is fully visible, populate any UTM fields
                if ($popup.find('form').length > 0) {
                    this.populateUTMFields($popup);
                }
            }, 350); // Wait for animation to complete
        },
        
        /**
         * Close popup
         */
        closePopup: function(popupId, reason = 'closed') {
            const $popup = $(`[data-popup-id="${popupId}"]`);
            const $overlay = $popup.closest('.dcf-popup-overlay');
            const popup = this.getPopupConfig(popupId);
            const exitAnimation = popup?.design?.exit_animation || 'fadeOut';
            const duration = popup?.design?.animation_duration || 500;
            
            // Clean up countdown timers
            this.cleanupCountdownTimers(popupId);
            
            // Remove entrance animation classes
            $popup.removeClass('dcf-fadeIn dcf-slideInUp dcf-slideInDown dcf-slideInLeft dcf-slideInRight dcf-bounceIn dcf-zoomIn dcf-rotateIn dcf-flipIn dcf-rubberBand');
            
            // Add exit animation class
            switch (exitAnimation) {
                case 'slideOutUp':
                    $popup.addClass('dcf-slideOutUp');
                    break;
                case 'slideOutDown':
                    $popup.addClass('dcf-slideOutDown');
                    break;
                case 'zoomOut':
                    $popup.addClass('dcf-zoomOut');
                    break;
                case 'bounceOut':
                    $popup.addClass('dcf-bounceOut');
                    break;
                default:
                    $popup.addClass('dcf-fadeOut');
            }
            
            // Wait for animation to complete before removing
            setTimeout(() => {
                if ($overlay.length) {
                    $overlay.remove();
                } else {
                    $popup.remove();
                }
            }, duration);
            
            // Remove from active popups
            const index = this.config.activePopups.indexOf(popupId);
            if (index > -1) {
                this.config.activePopups.splice(index, 1);
            }
            
            // Track interaction
            this.trackInteraction(popupId, reason);
            
            // Trigger analytics event for popup close
            $(document).trigger('dcf:popup:close', {
                popup_id: popupId,
                interaction_time: Date.now() - (this.state.popupOpenTime || Date.now())
            });
            
            this.log('Popup closed:', popupId, reason);
        },
        
        /**
         * Set up auto-close
         */
        setupAutoClose: function(popup) {
            if (popup.config.auto_close && popup.config.auto_close_delay) {
                const self = this;
                setTimeout(function() {
                    self.closePopup(popup.id, 'auto_closed');
                }, popup.config.auto_close_delay * 1000);
            }
        },
        
        /**
         * Get popup configuration
         */
        getPopupConfig: function(popupId) {
            return this.config.popups.find(p => p.id == popupId);
        },
        
        /**
         * Track popup display
         */
        trackDisplay: function(popupId) {
            this.sendTrackingRequest('track_display', {
                popup_id: popupId
            });
        },
        
        /**
         * Track popup interaction
         */
        trackInteraction: function(popupId, interactionType, data = {}) {
            this.sendTrackingRequest('track_interaction', {
                popup_id: popupId,
                interaction_type: interactionType,
                interaction_data: data
            });
        },
        
        /**
         * Send tracking request
         */
        sendTrackingRequest: function(action, data) {
            $.post(this.config.ajax_url, {
                action: 'dcf_popup_action',
                dcf_popup_action: action,
                nonce: this.config.nonce,
                ...data
            }).done((response) => {
                this.log('Tracking response:', response);
            }).fail((xhr, status, error) => {
                this.log('Tracking error:', error);
            });
        },
        
        /**
         * Log debug messages
         */
        log: function(...args) {
            if (this.state.debug) {
                console.log('[DCF Popup Engine]', ...args);
            }
        },
        
        /**
         * Capture UTM parameters from URL
         */
        captureUTMParameters: function(urlParams) {
            console.log('[DCF Debug] captureUTMParameters called');
            console.log('[DCF Debug] URL params:', urlParams.toString());
            
            const utmParams = [
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
            
            this.state.utmParameters = {};
            
            // First check URL parameters
            utmParams.forEach(param => {
                const value = urlParams.get(param);
                if (value) {
                    this.state.utmParameters[param] = value;
                    // Also store in sessionStorage for persistence
                    sessionStorage.setItem('dcf_' + param, value);
                    console.log(`[DCF Debug] Captured ${param} = ${value} from URL`);
                } else {
                    // Check sessionStorage for previously stored values
                    const storedValue = sessionStorage.getItem('dcf_' + param);
                    if (storedValue) {
                        this.state.utmParameters[param] = storedValue;
                        console.log(`[DCF Debug] Retrieved ${param} = ${storedValue} from sessionStorage`);
                    }
                }
            });
            
            console.log('[DCF Debug] Final captured UTM parameters:', this.state.utmParameters);
        },
        
        /**
         * Populate UTM fields in a form
         */
        populateUTMFields: function($container) {
            console.log('[DCF Debug] populateUTMFields called');
            console.log('[DCF Debug] UTM parameters state:', this.state.utmParameters);
            
            if (!this.state.utmParameters || Object.keys(this.state.utmParameters).length === 0) {
                console.log('[DCF Debug] No UTM parameters to populate');
                return;
            }
            
            console.log('[DCF Debug] Populating UTM fields with:', this.state.utmParameters);
            
            // Populate hidden UTM fields
            Object.keys(this.state.utmParameters).forEach(param => {
                const value = this.state.utmParameters[param];
                if (value) {
                    // Try multiple selector formats that might be used
                    const selectors = [
                        `input[name="${param}"]`,
                        `#dcf_${param}`,
                        `input[name="dcf_field[${param}]"]`,
                        `#dcf_field_${param}`
                    ];
                    
                    selectors.forEach(selector => {
                        const $field = $container.find(selector);
                        if ($field.length > 0) {
                            $field.val(value);
                            console.log(`[DCF Debug] Found and set ${selector} to ${value}`);
                        } else {
                            console.log(`[DCF Debug] Field not found: ${selector}`);
                        }
                    });
                }
            });
        },
        
        /**
         * Generate dynamic styles for typography
         */
        generateDynamicStyles: function(popupId, design) {
            const styles = [];
            
            // Add layout styles
            if (design.form_layout && design.form_layout !== 'single') {
                const columnGap = design.column_gap || '20px';
                const fieldSpacing = design.field_spacing || '20px';
                
                if (design.form_layout === 'two-column') {
                    styles.push(`
                        [data-popup-id="${popupId}"] .dcf-form-fields,
                        [data-popup-id="${popupId}"] form {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: ${fieldSpacing} ${columnGap};
                            align-items: start;
                        }
                        
                        /* Full width fields */
                        [data-popup-id="${popupId}"] .dcf-field-type-textarea,
                        [data-popup-id="${popupId}"] .dcf-field-type-message,
                        [data-popup-id="${popupId}"] .dcf-field-type-comments,
                        [data-popup-id="${popupId}"] .dcf-submit-wrapper,
                        [data-popup-id="${popupId}"] .dcf-form-submit {
                            grid-column: 1 / -1;
                        }
                    `);
                    
                    // Apply full width settings
                    const fullWidthFields = design.full_width_fields || ['message', 'submit'];
                    if (fullWidthFields.includes('email')) {
                        styles.push(`
                            [data-popup-id="${popupId}"] .dcf-field-type-email,
                            [data-popup-id="${popupId}"] .dcf-email-field {
                                grid-column: 1 / -1;
                            }
                        `);
                    }
                } else if (design.form_layout === 'inline') {
                    styles.push(`
                        [data-popup-id="${popupId}"] .dcf-form-fields,
                        [data-popup-id="${popupId}"] form {
                            display: flex;
                            flex-wrap: wrap;
                            gap: ${fieldSpacing} ${columnGap};
                            align-items: flex-end;
                        }
                        
                        [data-popup-id="${popupId}"] .dcf-field {
                            flex: 1 1 200px;
                        }
                        
                        /* Full width fields in inline layout */
                        [data-popup-id="${popupId}"] .dcf-field-type-textarea,
                        [data-popup-id="${popupId}"] .dcf-field-type-message,
                        [data-popup-id="${popupId}"] .dcf-submit-wrapper {
                            flex: 1 1 100%;
                        }
                    `);
                }
                
                // Mobile responsiveness for layouts
                styles.push(`
                    @media (max-width: 600px) {
                        [data-popup-id="${popupId}"] .dcf-form-fields,
                        [data-popup-id="${popupId}"] form {
                            display: block !important;
                        }
                        
                        [data-popup-id="${popupId}"] .dcf-field {
                            margin-bottom: ${fieldSpacing} !important;
                        }
                    }
                `);
            }
            
            // Add heading styles if specified
            if (design.heading_font_size || design.heading_font_weight) {
                styles.push(`
                    [data-popup-id="${popupId}"] h1,
                    [data-popup-id="${popupId}"] h2,
                    [data-popup-id="${popupId}"] h3,
                    [data-popup-id="${popupId}"] h4,
                    [data-popup-id="${popupId}"] h5,
                    [data-popup-id="${popupId}"] h6 {
                        ${design.heading_font_size ? `font-size: ${design.heading_font_size} !important;` : ''}
                        ${design.heading_font_weight ? `font-weight: ${design.heading_font_weight} !important;` : ''}
                        ${design.text_align ? `text-align: ${design.text_align} !important;` : ''}
                        line-height: 1.2 !important;
                        margin-bottom: 0.5em !important;
                    }
                `);
            }
            
            // Add paragraph and list styles
            if (design.font_size || design.line_height) {
                styles.push(`
                    [data-popup-id="${popupId}"] p,
                    [data-popup-id="${popupId}"] li {
                        ${design.font_size ? `font-size: ${design.font_size} !important;` : ''}
                        ${design.line_height ? `line-height: ${design.line_height} !important;` : ''}
                    }
                `);
            }
            
            // Add button styles
            const buttonShadows = {
                'none': 'none',
                'small': '0 2px 4px rgba(0,0,0,0.1)',
                'medium': '0 4px 8px rgba(0,0,0,0.15)',
                'large': '0 8px 16px rgba(0,0,0,0.2)'
            };
            
            const buttonShadow = buttonShadows[design.button_shadow] || buttonShadows['small'];
            
            styles.push(`
                [data-popup-id="${popupId}"] button,
                [data-popup-id="${popupId}"] .button,
                [data-popup-id="${popupId}"] input[type="submit"] {
                    ${design.button_bg_color ? `background-color: ${design.button_bg_color} !important;` : ''}
                    ${design.button_text_color ? `color: ${design.button_text_color} !important;` : ''}
                    ${design.button_border_radius ? `border-radius: ${design.button_border_radius} !important;` : ''}
                    ${design.button_padding ? `padding: ${design.button_padding} !important;` : ''}
                    ${design.button_font_size ? `font-size: ${design.button_font_size} !important;` : ''}
                    ${design.button_font_weight ? `font-weight: ${design.button_font_weight} !important;` : ''}
                    ${design.button_text_transform ? `text-transform: ${design.button_text_transform} !important;` : ''}
                    box-shadow: ${buttonShadow} !important;
                    border: none !important;
                    transition: all 0.3s ease !important;
                    cursor: pointer !important;
                    display: inline-block !important;
                    text-decoration: none !important;
                    line-height: 1.5 !important;
                }
                
                [data-popup-id="${popupId}"] button:hover,
                [data-popup-id="${popupId}"] .button:hover,
                [data-popup-id="${popupId}"] input[type="submit"]:hover {
                    ${design.button_hover_bg_color ? `background-color: ${design.button_hover_bg_color} !important;` : ''}
                    transform: translateY(-2px) !important;
                    box-shadow: 0 6px 20px rgba(0,0,0,0.2) !important;
                }
                
                [data-popup-id="${popupId}"] button:active,
                [data-popup-id="${popupId}"] .button:active,
                [data-popup-id="${popupId}"] input[type="submit"]:active {
                    transform: translateY(0) !important;
                    box-shadow: ${buttonShadow} !important;
                }
            `);
            
            // Add form field styles if customized
            if (design.field_style || design.field_border_color || design.field_focus_color || design.field_bg_color) {
                styles.push(`
                    [data-popup-id="${popupId}"] input[type="text"],
                    [data-popup-id="${popupId}"] input[type="email"],
                    [data-popup-id="${popupId}"] input[type="tel"],
                    [data-popup-id="${popupId}"] input[type="number"],
                    [data-popup-id="${popupId}"] input[type="password"],
                    [data-popup-id="${popupId}"] textarea,
                    [data-popup-id="${popupId}"] select {
                        ${design.field_border_color ? `border-color: ${design.field_border_color} !important;` : ''}
                        ${design.field_bg_color ? `background-color: ${design.field_bg_color} !important;` : ''}
                        ${design.field_text_color ? `color: ${design.field_text_color} !important;` : ''}
                        ${design.field_padding ? `padding: ${design.field_padding} !important;` : ''}
                        ${design.field_border_radius ? `border-radius: ${design.field_border_radius} !important;` : ''}
                    }
                    
                    [data-popup-id="${popupId}"] input[type="text"]:focus,
                    [data-popup-id="${popupId}"] input[type="email"]:focus,
                    [data-popup-id="${popupId}"] input[type="tel"]:focus,
                    [data-popup-id="${popupId}"] input[type="number"]:focus,
                    [data-popup-id="${popupId}"] input[type="password"]:focus,
                    [data-popup-id="${popupId}"] textarea:focus,
                    [data-popup-id="${popupId}"] select:focus {
                        ${design.field_focus_color ? `border-color: ${design.field_focus_color} !important;` : ''}
                        ${design.field_focus_color ? `box-shadow: 0 0 0 4px ${design.field_focus_color}33 !important;` : ''}
                    }
                `);
                
                // Apply field style variations
                if (design.field_style === 'underline') {
                    styles.push(`
                        [data-popup-id="${popupId}"] input[type="text"],
                        [data-popup-id="${popupId}"] input[type="email"],
                        [data-popup-id="${popupId}"] input[type="tel"],
                        [data-popup-id="${popupId}"] input[type="number"],
                        [data-popup-id="${popupId}"] input[type="password"],
                        [data-popup-id="${popupId}"] textarea,
                        [data-popup-id="${popupId}"] select {
                            border: none !important;
                            border-bottom: 2px solid ${design.field_border_color || '#e1e8ed'} !important;
                            border-radius: 0 !important;
                            background: transparent !important;
                            padding-left: 0 !important;
                            padding-right: 0 !important;
                        }
                    `);
                } else if (design.field_style === 'classic') {
                    styles.push(`
                        [data-popup-id="${popupId}"] input[type="text"],
                        [data-popup-id="${popupId}"] input[type="email"],
                        [data-popup-id="${popupId}"] input[type="tel"],
                        [data-popup-id="${popupId}"] input[type="number"],
                        [data-popup-id="${popupId}"] input[type="password"],
                        [data-popup-id="${popupId}"] textarea,
                        [data-popup-id="${popupId}"] select {
                            border-radius: 0 !important;
                        }
                    `);
                } else if (design.field_style === 'floating') {
                    styles.push(`
                        [data-popup-id="${popupId}"] .dcf-field {
                            position: relative;
                            margin-top: 20px;
                        }
                        [data-popup-id="${popupId}"] .dcf-field label {
                            position: absolute;
                            top: 18px;
                            left: 20px;
                            transition: all 0.3s ease;
                            background: ${design.background_color || '#ffffff'};
                            padding: 0 5px;
                        }
                        [data-popup-id="${popupId}"] .dcf-field input:focus + label,
                        [data-popup-id="${popupId}"] .dcf-field input:not(:placeholder-shown) + label {
                            top: -10px;
                            font-size: 12px;
                            color: ${design.field_focus_color || '#3498db'};
                        }
                    `);
                }
            }
            
            // Add icon styles if enabled
            if (design.use_field_icons) {
                styles.push(`
                    [data-popup-id="${popupId}"] .dcf-field.dcf-email-field input,
                    [data-popup-id="${popupId}"] .dcf-field.dcf-phone-field input,
                    [data-popup-id="${popupId}"] .dcf-field.dcf-name-field input {
                        padding-left: 50px !important;
                    }
                    [data-popup-id="${popupId}"] .dcf-field.dcf-email-field::before {
                        content: "✉";
                        position: absolute;
                        left: 20px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 18px;
                        opacity: 0.5;
                    }
                    [data-popup-id="${popupId}"] .dcf-field.dcf-phone-field::before {
                        content: "☎";
                        position: absolute;
                        left: 20px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 18px;
                        opacity: 0.5;
                    }
                    [data-popup-id="${popupId}"] .dcf-field.dcf-name-field::before {
                        content: "👤";
                        position: absolute;
                        left: 20px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 18px;
                        opacity: 0.5;
                    }
                `);
            }
            
            return styles.length > 0 ? `<style>${styles.join('')}</style>` : '';
        },
        
        /**
         * Initialize countdown timers
         */
        initCountdownTimers: function() {
            $('.dcf-popup-countdown').each(function() {
                const $countdown = $(this);
                const endTime = $countdown.data('end-time');
                
                if (!endTime) return;
                
                const countdownInterval = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = new Date(endTime).getTime() - now;
                    
                    if (distance < 0) {
                        clearInterval(countdownInterval);
                        $countdown.html('<div class="dcf-countdown-expired">Offer Expired</div>');
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    $countdown.find('.dcf-countdown-days').text(String(days).padStart(2, '0'));
                    $countdown.find('.dcf-countdown-hours').text(String(hours).padStart(2, '0'));
                    $countdown.find('.dcf-countdown-minutes').text(String(minutes).padStart(2, '0'));
                    $countdown.find('.dcf-countdown-seconds').text(String(seconds).padStart(2, '0'));
                }, 1000);
                
                // Store interval ID for cleanup
                $countdown.data('countdown-interval', countdownInterval);
            });
        },
        
        /**
         * Clean up countdown timers
         */
        cleanupCountdownTimers: function(popupId) {
            const $popup = $(`[data-popup-id="${popupId}"]`);
            $popup.find('.dcf-popup-countdown').each(function() {
                const intervalId = $(this).data('countdown-interval');
                if (intervalId) {
                    clearInterval(intervalId);
                }
            });
        },
        
        /**
         * Initialize multi-step handlers
         */
        initMultiStepHandlers: function() {
            const self = this;
            
            // Handle Yes/No button clicks
            $(document).on('click', '.dcf-yes-button, .dcf-no-button', function(e) {
                e.preventDefault();
                const $button = $(this);
                const $popup = $button.closest('.dcf-popup');
                const popupId = $popup.data('popup-id');
                
                if ($button.data('action') === 'close') {
                    self.closePopup(popupId, 'user_closed');
                } else if ($button.data('next-step')) {
                    self.navigateToStep($popup, $button.data('next-step'));
                }
            });
            
            // Handle next button clicks
            $(document).on('click', '.dcf-next-button', function(e) {
                e.preventDefault();
                const $button = $(this);
                const $popup = $button.closest('.dcf-popup');
                const popupId = $popup.data('popup-id');
                
                if ($button.data('action') === 'close') {
                    self.closePopup(popupId, 'user_closed');
                } else if ($button.data('next-step')) {
                    self.navigateToStep($popup, $button.data('next-step'));
                }
            });
            
            // Handle multi-step form submissions
            $(document).on('submit', '.dcf-multi-step-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $popup = $form.closest('.dcf-popup');
                const popupId = $popup.data('popup-id');
                
                // Collect form data
                const formData = $form.serialize();
                
                // Track form submission
                self.trackInteraction(popupId, 'form_submitted', { form_data: formData });
                
                // Show success message or close popup
                const $currentStep = $form.closest('.dcf-popup-step');
                const nextStep = $currentStep.data('next-step');
                
                if (nextStep) {
                    self.navigateToStep($popup, nextStep);
                } else {
                    // Show success message
                    $currentStep.html('<div class="dcf-success-message"><h3>Thank you!</h3><p>Your information has been submitted successfully.</p></div>');
                    
                    // Close popup after delay
                    setTimeout(function() {
                        self.closePopup(popupId, 'form_completed');
                    }, 3000);
                }
            });
        },
        
        /**
         * Navigate to a specific step in multi-step popup
         */
        navigateToStep: function($popup, stepId) {
            const $multiStep = $popup.find('.dcf-multi-step-popup');
            const $targetStep = $multiStep.find('[data-step-id="' + stepId + '"]');
            
            if ($targetStep.length === 0) {
                this.log('Step not found:', stepId);
                return;
            }
            
            // Hide all steps
            $multiStep.find('.dcf-popup-step').hide();
            
            // Show target step
            $targetStep.fadeIn(300);
            
            // Update current step
            const stepIndex = $targetStep.data('step-index');
            $multiStep.data('current-step', stepIndex);
            
            // Reinitialize countdown timers if any
            this.initCountdownTimers();
        },
        
        /**
         * Show confetti animation
         */
        showConfetti: function($popup) {
            const confettiCount = 150;
            const $container = $('<div class="dcf-confetti-container"></div>').css({
                position: 'fixed',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                pointerEvents: 'none',
                zIndex: 999999,
                overflow: 'hidden'
            });
            
            // Create confetti pieces
            for (let i = 0; i < confettiCount; i++) {
                const $confetti = $('<div class="dcf-confetti"></div>');
                const startPosX = Math.random() * 100;
                const endPosX = startPosX + (Math.random() - 0.5) * 30;
                const rotation = Math.random() * 360;
                const delay = Math.random() * 3;
                const duration = 3 + Math.random() * 3;
                const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                $confetti.css({
                    position: 'absolute',
                    width: '10px',
                    height: '10px',
                    backgroundColor: color,
                    left: startPosX + '%',
                    top: '-20px',
                    opacity: 1,
                    transform: `rotate(${rotation}deg)`,
                    animation: `dcf-confetti-fall ${duration}s ${delay}s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards`
                });
                
                $container.append($confetti);
            }
            
            // Add styles for confetti animation
            if ($('#dcf-confetti-styles').length === 0) {
                $('head').append(`
                    <style id="dcf-confetti-styles">
                        @keyframes dcf-confetti-fall {
                            to {
                                transform: translateY(110vh) rotate(720deg);
                                opacity: 0;
                            }
                        }
                        .dcf-confetti {
                            border-radius: 50%;
                        }
                    </style>
                `);
            }
            
            // Add container to body
            $('body').append($container);
            
            // Remove after animation completes
            setTimeout(() => {
                $container.remove();
            }, 9000);
        },
        
        /**
         * Show form success message
         */
        showFormSuccess: function($form) {
            const successHtml = `
                <div class="dcf-form-success">
                    <div class="dcf-success-icon">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="32" r="31" stroke="#4CAF50" stroke-width="2"/>
                            <path d="M20 32L28 40L44 24" stroke="#4CAF50" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Success!</h3>
                    <p>Your form has been submitted successfully.</p>
                </div>
            `;
            
            // Add success styles if not already present
            if ($('#dcf-success-styles').length === 0) {
                $('head').append(`
                    <style id="dcf-success-styles">
                        .dcf-form-success {
                            text-align: center;
                            padding: 40px;
                            animation: dcf-zoomIn 0.5s ease;
                        }
                        .dcf-success-icon svg {
                            animation: dcf-success-checkmark 0.8s ease;
                        }
                        .dcf-success-icon svg path {
                            stroke-dasharray: 60;
                            stroke-dashoffset: 60;
                            animation: dcf-success-draw 0.5s 0.3s ease forwards;
                        }
                        .dcf-form-success h3 {
                            color: #4CAF50;
                            margin: 20px 0 10px;
                            font-size: 24px;
                        }
                        .dcf-form-success p {
                            color: #666;
                            margin: 0;
                        }
                        @keyframes dcf-success-checkmark {
                            0% {
                                transform: scale(0) rotate(-45deg);
                            }
                            50% {
                                transform: scale(1.2) rotate(-45deg);
                            }
                            100% {
                                transform: scale(1) rotate(0deg);
                            }
                        }
                        @keyframes dcf-success-draw {
                            to {
                                stroke-dashoffset: 0;
                            }
                        }
                    </style>
                `);
            }
            
            // Replace form content with success message
            $form.hide().after(successHtml);
            
            // Close popup after 3 seconds
            const $popup = $form.closest('.dcf-popup');
            const popupId = $popup.data('popup-id');
            
            setTimeout(() => {
                this.closePopup(popupId, 'form_success');
            }, 3000);
        },
        
        /**
         * Debug helper to check popup visibility
         */
        debugPopupVisibility: function(popupId) {
            const $popup = $(`[data-popup-id="${popupId}"]`);
            const $overlay = $popup.closest('.dcf-popup-overlay');
            
            if ($popup.length === 0) {
                console.error('[DCF Debug] Popup not found in DOM:', popupId);
                return;
            }
            
            console.group('[DCF Debug] Popup Visibility Check - ID:', popupId);
            console.log('Popup element:', $popup[0]);
            console.log('Popup display:', $popup.css('display'));
            console.log('Popup visibility:', $popup.css('visibility'));
            console.log('Popup opacity:', $popup.css('opacity'));
            console.log('Popup z-index:', $popup.css('z-index'));
            console.log('Popup position:', $popup.css('position'));
            
            // Check overlay positioning
            if ($overlay.length) {
                console.log('Overlay element:', $overlay[0]);
                console.log('Overlay position:', $overlay.css('position'));
                console.log('Overlay top:', $overlay.css('top'));
                console.log('Overlay left:', $overlay.css('left'));
                console.log('Overlay display:', $overlay.css('display'));
                console.log('Overlay offset:', $overlay.offset());
                console.log('Overlay parent:', $overlay.parent()[0]);
            }
            
            // Check viewport position
            const rect = $popup[0].getBoundingClientRect();
            console.log('Popup viewport position:', {
                top: rect.top,
                left: rect.left,
                bottom: rect.bottom,
                right: rect.right,
                inViewport: rect.top >= 0 && rect.left >= 0 && 
                           rect.bottom <= window.innerHeight && 
                           rect.right <= window.innerWidth
            });
            console.log('Popup dimensions:', {
                width: $popup.outerWidth(),
                height: $popup.outerHeight()
            });
            console.log('Popup offset:', $popup.offset());
            
            if ($overlay.length) {
                console.log('Overlay element:', $overlay[0]);
                console.log('Overlay display:', $overlay.css('display'));
                console.log('Overlay opacity:', $overlay.css('opacity'));
                console.log('Overlay z-index:', $overlay.css('z-index'));
            }
            
            // Check if popup is hidden by parent elements
            let $parent = $popup.parent();
            while ($parent.length && !$parent.is('body')) {
                if ($parent.css('display') === 'none' || $parent.css('visibility') === 'hidden') {
                    console.warn('Parent element is hidden:', $parent[0]);
                }
                $parent = $parent.parent();
            }
            
            // Check computed styles
            const computedStyles = window.getComputedStyle($popup[0]);
            console.log('Computed display:', computedStyles.display);
            console.log('Computed visibility:', computedStyles.visibility);
            console.log('Computed opacity:', computedStyles.opacity);
            
            console.groupEnd();
        }
    };
    
    // jQuery throttle function
    $.throttle = function(delay, fn) {
        let timeoutId;
        let lastExec = 0;
        
        return function() {
            const context = this;
            const args = arguments;
            const elapsed = Date.now() - lastExec;
            
            function exec() {
                lastExec = Date.now();
                fn.apply(context, args);
            }
            
            clearTimeout(timeoutId);
            
            if (elapsed > delay) {
                exec();
            } else {
                timeoutId = setTimeout(exec, delay - elapsed);
            }
        };
    };
    
    // Initialize popup engine on document ready
    $(document).ready(function() {
        // Initialize popup engine if data is available
        if (typeof dcf_popup_data !== 'undefined') {
            window.DCF_PopupEngine.init();
        }
        
        // Remove any click handlers from spacer blocks
        $(document).on('click', '.dcf-block-spacer', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        // Ensure spacer blocks are non-interactive
        $('.dcf-block-spacer').css({
            'pointer-events': 'none',
            'cursor': 'default'
        });
    });
    
})(jQuery); 