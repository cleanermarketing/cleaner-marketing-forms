/**
 * Dry Cleaning Forms - Popup Privacy Compliance
 * 
 * Handles GDPR consent and privacy compliance for popups
 */

(function($) {
    'use strict';
    
    var DCFPopupPrivacy = {
        
        /**
         * Initialize privacy compliance
         */
        init: function() {
            this.bindEvents();
            this.checkConsent();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle consent buttons
            $(document).on('click', '.dcf-consent-accept', this.handleAcceptConsent);
            $(document).on('click', '.dcf-consent-decline', this.handleDeclineConsent);
            
            // Handle opt-out
            $(document).on('click', '.dcf-privacy-opt-out', this.handleOptOut);
        },
        
        /**
         * Check if user has given consent
         */
        checkConsent: function() {
            // Check cookie for consent status
            var consent = this.getCookie('dcf_privacy_consent');
            
            if (!consent && dcf_privacy.consent_required) {
                // Show consent notice if required
                this.showConsentNotice();
            }
        },
        
        /**
         * Show consent notice
         */
        showConsentNotice: function() {
            // Only show on popups that require consent
            $('.dcf-popup[data-requires-consent="true"]').each(function() {
                var $popup = $(this);
                if (!$popup.find('.dcf-consent-banner').length) {
                    // Consent banner is already added by PHP
                    $popup.find('.dcf-consent-banner').show();
                }
            });
        },
        
        /**
         * Handle accept consent
         */
        handleAcceptConsent: function(e) {
            e.preventDefault();
            
            // Set consent cookie
            DCFPopupPrivacy.setCookie('dcf_privacy_consent', 'accepted', 365);
            
            // Hide consent banner
            $('.dcf-consent-banner').fadeOut();
            
            // Track consent
            DCFPopupPrivacy.trackConsent('accepted');
            
            // Enable popup functionality
            $(document).trigger('dcf:privacy:consent_granted');
        },
        
        /**
         * Handle decline consent
         */
        handleDeclineConsent: function(e) {
            e.preventDefault();
            
            // Set consent cookie
            DCFPopupPrivacy.setCookie('dcf_privacy_consent', 'declined', 365);
            
            // Hide popup
            $(this).closest('.dcf-popup').fadeOut();
            
            // Track consent
            DCFPopupPrivacy.trackConsent('declined');
            
            // Trigger event
            $(document).trigger('dcf:privacy:consent_declined');
        },
        
        /**
         * Handle opt-out
         */
        handleOptOut: function(e) {
            e.preventDefault();
            
            if (confirm(dcf_privacy.messages.opt_out)) {
                // Clear consent cookie
                DCFPopupPrivacy.setCookie('dcf_privacy_consent', '', -1);
                
                // Clear any stored data
                DCFPopupPrivacy.clearStoredData();
                
                // Reload page
                window.location.reload();
            }
        },
        
        /**
         * Track consent action
         */
        trackConsent: function(action) {
            $.ajax({
                url: dcf_privacy.ajax_url,
                type: 'POST',
                data: {
                    action: 'dcf_track_privacy_consent',
                    consent_action: action,
                    nonce: dcf_privacy.nonce
                }
            });
        },
        
        /**
         * Clear stored data
         */
        clearStoredData: function() {
            // Clear localStorage
            if (typeof(Storage) !== "undefined") {
                for (var key in localStorage) {
                    if (key.indexOf('dcf_') === 0) {
                        localStorage.removeItem(key);
                    }
                }
            }
            
            // Clear sessionStorage
            if (typeof(Storage) !== "undefined") {
                for (var key in sessionStorage) {
                    if (key.indexOf('dcf_') === 0) {
                        sessionStorage.removeItem(key);
                    }
                }
            }
            
            // Clear cookies
            document.cookie.split(";").forEach(function(c) {
                var cookie = c.trim();
                var eqPos = cookie.indexOf("=");
                var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                if (name.indexOf('dcf_') === 0) {
                    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
                }
            });
        },
        
        /**
         * Get cookie value
         */
        getCookie: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },
        
        /**
         * Set cookie
         */
        setCookie: function(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if privacy object exists
        if (typeof dcf_privacy !== 'undefined') {
            DCFPopupPrivacy.init();
        }
    });
    
    // Make available globally
    window.DCFPopupPrivacy = DCFPopupPrivacy;
    
})(jQuery);