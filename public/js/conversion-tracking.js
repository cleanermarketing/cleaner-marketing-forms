/**
 * Dry Cleaning Forms - Conversion Tracking
 * 
 * Tracks popup conversions and analytics
 */

(function($) {
    'use strict';
    
    var DCFConversionTracking = {
        
        /**
         * Initialize conversion tracking
         */
        init: function() {
            this.bindEvents();
            this.trackPageView();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Track popup events
            $(document).on('dcf:popup:show', this.trackPopupShow);
            $(document).on('dcf:popup:close', this.trackPopupClose);
            $(document).on('dcf:popup:conversion', this.trackPopupConversion);
            $(document).on('dcf:popup:interaction', this.trackPopupInteraction);
            
            // Track form submissions
            $(document).on('submit', '.dcf-popup-form', this.trackFormSubmit);
            
            // Track clicks within popup as interactions
            $(document).on('click', '.dcf-popup', function(e) {
                var $popup = $(this);
                var popupId = $popup.data('popup-id');
                if (popupId && !$(e.target).is('button, input[type="submit"]')) {
                    $(document).trigger('dcf:popup:interaction', {
                        popup_id: popupId,
                        interaction_type: 'click'
                    });
                }
            });
        },
        
        /**
         * Track page view
         */
        trackPageView: function() {
            this.sendEvent('page_view', {
                page_url: dcf_analytics.page_url,
                referrer: dcf_analytics.referrer
            });
        },
        
        /**
         * Track popup show event
         */
        trackPopupShow: function(e, data) {
            // Track as 'view' event for analytics
            DCFConversionTracking.sendEvent('view', {
                popup_id: data.popup_id,
                popup_type: data.popup_type,
                trigger_type: data.trigger_type
            });
        },
        
        /**
         * Track popup close event
         */
        trackPopupClose: function(e, data) {
            DCFConversionTracking.sendEvent('close', {
                popup_id: data.popup_id,
                interaction_time: data.interaction_time
            });
        },
        
        /**
         * Track popup conversion event
         */
        trackPopupConversion: function(e, data) {
            DCFConversionTracking.sendEvent('conversion', {
                popup_id: data.popup_id,
                form_id: data.form_id,
                conversion_value: data.conversion_value || 0
            });
        },
        
        /**
         * Track form submission
         */
        trackFormSubmit: function(e) {
            var $form = $(this);
            var popupId = $form.closest('.dcf-popup').data('popup-id');
            
            if (popupId) {
                DCFConversionTracking.sendEvent('submission', {
                    popup_id: popupId,
                    form_id: $form.data('form-id')
                });
            }
        },
        
        /**
         * Track popup interaction
         */
        trackPopupInteraction: function(e, data) {
            DCFConversionTracking.sendEvent('interaction', {
                popup_id: data.popup_id,
                interaction_type: data.interaction_type || 'click'
            });
        },
        
        /**
         * Send event to server
         */
        sendEvent: function(event_type, event_data) {
            // Prepare data in the format expected by the backend
            var data = {
                action: 'dcf_track_popup_event',
                nonce: dcf_analytics.nonce,
                event_type: event_type,
                popup_id: event_data.popup_id || 0,
                session_id: dcf_analytics.session_id,
                page_url: dcf_analytics.page_url || window.location.href,
                referrer: dcf_analytics.referrer || document.referrer,
                additional_data: event_data
            };
            
            // Send via AJAX
            $.ajax({
                url: dcf_analytics.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                error: function(xhr, status, error) {
                    console.error('DCF Analytics Error:', error);
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        DCFConversionTracking.init();
    });
    
    // Make available globally for debugging
    window.DCFConversionTracking = DCFConversionTracking;
    
})(jQuery);