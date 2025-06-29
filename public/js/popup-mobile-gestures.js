/**
 * DCF Popup Mobile Gestures
 * Enhanced mobile interactions for popups
 * 
 * @package DryCleaningForms
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    window.DCF_MobileGestures = {
        
        // Touch tracking
        touchStart: null,
        touchCurrent: null,
        touchThreshold: 50, // Minimum distance for swipe
        
        /**
         * Initialize mobile gesture handlers
         */
        init: function() {
            if (!this.isMobile()) {
                return;
            }
            
            this.setupSwipeToClose();
            this.setupPullToRefresh();
            this.improveTouchTargets();
            this.handleOrientationChange();
        },
        
        /**
         * Check if device is mobile
         */
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                   window.matchMedia("(max-width: 768px)").matches;
        },
        
        /**
         * Setup swipe to close for popups
         */
        setupSwipeToClose: function() {
            const self = this;
            
            // Swipe down to close modal popups
            $(document).on('touchstart', '.dcf-popup-modal, .dcf-popup-multi-step', function(e) {
                const $popup = $(this);
                if ($popup.scrollTop() === 0) {
                    self.touchStart = {
                        x: e.touches[0].clientX,
                        y: e.touches[0].clientY,
                        time: Date.now()
                    };
                }
            });
            
            $(document).on('touchmove', '.dcf-popup-modal, .dcf-popup-multi-step', function(e) {
                if (!self.touchStart) return;
                
                const $popup = $(this);
                const touch = e.touches[0];
                const deltaY = touch.clientY - self.touchStart.y;
                
                // Only track downward swipes when at top of scroll
                if (deltaY > 0 && $popup.scrollTop() === 0) {
                    e.preventDefault();
                    
                    // Visual feedback - move popup down
                    const opacity = 1 - (deltaY / 300);
                    const transform = `translateY(${Math.min(deltaY * 0.5, 100)}px)`;
                    
                    $popup.css({
                        transform: transform,
                        opacity: Math.max(0.5, opacity)
                    });
                }
            });
            
            $(document).on('touchend', '.dcf-popup-modal, .dcf-popup-multi-step', function(e) {
                if (!self.touchStart) return;
                
                const $popup = $(this);
                const touch = e.changedTouches[0];
                const deltaY = touch.clientY - self.touchStart.y;
                const deltaTime = Date.now() - self.touchStart.time;
                const velocity = deltaY / deltaTime;
                
                // Reset styles
                $popup.css({
                    transform: '',
                    opacity: ''
                });
                
                // Close if swiped down enough or with enough velocity
                if ((deltaY > 150 || velocity > 0.5) && $popup.scrollTop() === 0) {
                    const popupId = $popup.data('popup-id');
                    if (window.DCF_PopupEngine) {
                        window.DCF_PopupEngine.closePopup(popupId, 'swipe_closed');
                    }
                }
                
                self.touchStart = null;
            });
            
            // Swipe left/right for sidebar popups
            $(document).on('touchstart', '.dcf-popup-sidebar', function(e) {
                self.touchStart = {
                    x: e.touches[0].clientX,
                    y: e.touches[0].clientY,
                    time: Date.now()
                };
            });
            
            $(document).on('touchmove', '.dcf-popup-sidebar', function(e) {
                if (!self.touchStart) return;
                
                const $popup = $(this);
                const touch = e.touches[0];
                const deltaX = touch.clientX - self.touchStart.x;
                const isRightSidebar = $popup.hasClass('dcf-popup-sidebar-right');
                
                // Track horizontal swipes
                if ((isRightSidebar && deltaX > 0) || (!isRightSidebar && deltaX < 0)) {
                    e.preventDefault();
                    
                    const transform = `translateX(${deltaX * 0.5}px)`;
                    $popup.css({
                        transform: transform
                    });
                }
            });
            
            $(document).on('touchend', '.dcf-popup-sidebar', function(e) {
                if (!self.touchStart) return;
                
                const $popup = $(this);
                const touch = e.changedTouches[0];
                const deltaX = touch.clientX - self.touchStart.x;
                const isRightSidebar = $popup.hasClass('dcf-popup-sidebar-right');
                
                // Reset styles
                $popup.css({
                    transform: ''
                });
                
                // Close if swiped enough in the right direction
                if ((isRightSidebar && deltaX > 100) || (!isRightSidebar && deltaX < -100)) {
                    const popupId = $popup.data('popup-id');
                    if (window.DCF_PopupEngine) {
                        window.DCF_PopupEngine.closePopup(popupId, 'swipe_closed');
                    }
                }
                
                self.touchStart = null;
            });
        },
        
        /**
         * Prevent pull-to-refresh inside popups
         */
        setupPullToRefresh: function() {
            let lastY = 0;
            
            $(document).on('touchstart', '.dcf-popup', function(e) {
                lastY = e.touches[0].clientY;
            });
            
            $(document).on('touchmove', '.dcf-popup', function(e) {
                const y = e.touches[0].clientY;
                const scrollTop = $(this).scrollTop();
                
                // Prevent overscroll when at the top
                if (scrollTop === 0 && y > lastY) {
                    e.preventDefault();
                }
                
                lastY = y;
            });
        },
        
        /**
         * Improve touch targets for better mobile UX
         */
        improveTouchTargets: function() {
            // Add touch feedback
            $(document).on('touchstart', '.dcf-popup button, .dcf-popup a, .dcf-popup input[type="submit"]', function() {
                $(this).addClass('dcf-touch-active');
            });
            
            $(document).on('touchend touchcancel', '.dcf-popup button, .dcf-popup a, .dcf-popup input[type="submit"]', function() {
                $(this).removeClass('dcf-touch-active');
            });
            
            // Improve radio/checkbox tap areas
            $(document).on('click', '.dcf-popup label', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const $input = $(this).find('input[type="radio"], input[type="checkbox"]');
                    if ($input.length) {
                        $input.prop('checked', !$input.prop('checked')).trigger('change');
                    }
                }
            });
        },
        
        /**
         * Handle orientation changes
         */
        handleOrientationChange: function() {
            let previousOrientation = window.orientation;
            
            $(window).on('orientationchange', function() {
                const newOrientation = window.orientation;
                
                // Adjust popup positioning after orientation change
                $('.dcf-popup-modal, .dcf-popup-multi-step').each(function() {
                    const $popup = $(this);
                    const $overlay = $popup.closest('.dcf-popup-overlay');
                    
                    // Force reflow
                    $overlay.hide().show(0);
                    
                    // Center popup
                    setTimeout(function() {
                        $overlay.css({
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        });
                    }, 100);
                });
                
                previousOrientation = newOrientation;
            });
        },
        
        /**
         * Add visual feedback for touches
         */
        addTouchFeedback: function() {
            const style = `
                <style id="dcf-touch-feedback">
                    .dcf-touch-active {
                        opacity: 0.7 !important;
                        transform: scale(0.98) !important;
                    }
                    
                    /* Improve tap highlight */
                    .dcf-popup * {
                        -webkit-tap-highlight-color: rgba(34, 113, 177, 0.1);
                    }
                    
                    /* Smooth transitions for touch */
                    .dcf-popup button,
                    .dcf-popup a,
                    .dcf-popup input[type="submit"] {
                        transition: opacity 0.1s ease, transform 0.1s ease;
                    }
                    
                    /* Prevent text selection on double tap */
                    .dcf-popup {
                        -webkit-user-select: none;
                        -ms-user-select: none;
                        user-select: none;
                    }
                    
                    .dcf-popup input,
                    .dcf-popup textarea {
                        -webkit-user-select: text;
                        -ms-user-select: text;
                        user-select: text;
                    }
                </style>
            `;
            
            if ($('#dcf-touch-feedback').length === 0) {
                $('head').append(style);
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        DCF_MobileGestures.init();
        DCF_MobileGestures.addTouchFeedback();
    });
    
})(jQuery);