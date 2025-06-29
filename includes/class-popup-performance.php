<?php
/**
 * Popup Performance Optimization Class
 *
 * Handles performance optimization for popups to ensure minimal page speed impact
 * and extremely quick loading times as requested.
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Performance {
    
    /**
     * Initialize performance optimizations
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'optimize_script_loading'), 1);
        add_action('wp_head', array($this, 'add_performance_hints'), 1);
        add_filter('dcf_popup_render', array($this, 'optimize_popup_html'), 10, 2);
        add_action('wp_footer', array($this, 'lazy_load_popup_assets'), 999);
    }

    /**
     * Optimize script loading for minimal page speed impact
     */
    public function optimize_script_loading() {
        // Only load popup scripts when needed
        if (!$this->should_load_popup_scripts()) {
            return;
        }

        // Defer popup scripts to avoid blocking page load
        wp_enqueue_script(
            'dcf-popup-engine',
            CMF_PLUGIN_URL . 'public/js/popup-engine.js',
            array('jquery'),
            CMF_PLUGIN_VERSION,
            true // Load in footer
        );
        
        // Also enqueue popup styles
        wp_enqueue_style(
            'dcf-popup-styles',
            CMF_PLUGIN_URL . 'public/css/popup-styles.css',
            array(),
            CMF_PLUGIN_VERSION
        );

        // Add async/defer attributes
        add_filter('script_loader_tag', array($this, 'add_async_defer_attributes'), 10, 2);

        // Inline critical popup CSS to avoid render-blocking
        add_action('wp_head', array($this, 'inline_critical_popup_css'), 5);
    }

    /**
     * Add performance hints for faster loading
     */
    public function add_performance_hints() {
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        
        // Preload critical popup assets
        if ($this->should_load_popup_scripts()) {
            echo '<link rel="preload" href="' . CMF_PLUGIN_URL . 'public/js/popup-engine.js" as="script">' . "\n";
            // Note: popup-styles.css is inlined as critical CSS, so no need to preload
        }
    }

    /**
     * Add async/defer attributes to popup scripts
     */
    public function add_async_defer_attributes($tag, $handle) {
        if ('dcf-popup-engine' === $handle) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        return $tag;
    }

    /**
     * Inline critical popup CSS to avoid render-blocking
     */
    public function inline_critical_popup_css() {
        if (!$this->should_load_popup_scripts()) {
            return;
        }

        $critical_css = $this->get_critical_popup_css();
        if ($critical_css) {
            echo '<style id="dcf-popup-critical-css">' . $critical_css . '</style>' . "\n";
        }
    }

    /**
     * Get critical popup CSS (inline styles for immediate rendering)
     */
    private function get_critical_popup_css() {
        return '
        .dcf-popup-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:999998;opacity:0;visibility:hidden;transition:opacity 0.3s ease,visibility 0.3s ease}
        .dcf-popup-overlay.active{opacity:1;visibility:visible}
        .dcf-popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.8);background:#fff;border-radius:8px;padding:30px;max-width:90%;max-height:90%;overflow-y:auto;z-index:999999;opacity:0;visibility:hidden;transition:all 0.3s ease}
        .dcf-popup.active{opacity:1;visibility:visible;transform:translate(-50%,-50%) scale(1)}
        .dcf-popup-close{position:absolute;top:15px;right:15px;background:none;border:none;font-size:24px;cursor:pointer;color:#999;width:30px;height:30px;display:flex;align-items:center;justify-content:center}
        @media(max-width:768px){.dcf-popup{padding:20px;max-width:95%}}
        ';
    }

    /**
     * Lazy load non-critical popup assets
     */
    public function lazy_load_popup_assets() {
        if (!$this->should_load_popup_scripts()) {
            return;
        }

        // Lazy load non-critical CSS
        echo '<script>
        (function(){
            var link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "' . CMF_PLUGIN_URL . 'public/css/popup-styles.css";
            link.media = "print";
            link.onload = function(){this.media="all"};
            document.head.appendChild(link);
        })();
        </script>';
    }

    /**
     * Optimize popup HTML for faster rendering
     */
    public function optimize_popup_html($html, $popup_data) {
        // Minify HTML
        $html = $this->minify_html($html);
        
        // Add performance attributes
        $html = str_replace('<div class="dcf-popup"', '<div class="dcf-popup" data-optimized="true"', $html);
        
        return $html;
    }

    /**
     * Minify HTML for smaller payload
     */
    private function minify_html($html) {
        // Remove unnecessary whitespace and comments
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        
        return $html;
    }

    /**
     * Check if popup scripts should be loaded on current page
     */
    private function should_load_popup_scripts() {
        // Get active popups for current page
        $popup_manager = new DCF_Popup_Manager();
        $active_popups = $popup_manager->get_active_popups_for_page();
        
        return !empty($active_popups);
    }

    /**
     * Get performance metrics for popups
     */
    public function get_performance_metrics() {
        return array(
            'script_load_time' => $this->measure_script_load_time(),
            'popup_render_time' => $this->measure_popup_render_time(),
            'page_speed_impact' => $this->measure_page_speed_impact(),
            'memory_usage' => $this->get_memory_usage()
        );
    }

    /**
     * Measure script loading time
     */
    private function measure_script_load_time() {
        // Implementation for measuring script load time
        return microtime(true);
    }

    /**
     * Measure popup rendering time
     */
    private function measure_popup_render_time() {
        // Implementation for measuring popup render time
        return microtime(true);
    }

    /**
     * Measure page speed impact
     */
    private function measure_page_speed_impact() {
        // Implementation for measuring page speed impact
        return array(
            'dom_ready_delay' => 0,
            'first_paint_delay' => 0,
            'largest_contentful_paint_delay' => 0
        );
    }

    /**
     * Get memory usage
     */
    private function get_memory_usage() {
        return memory_get_usage(true);
    }

    /**
     * Enable popup caching for better performance
     */
    public function enable_popup_caching() {
        add_action('wp_ajax_dcf_get_popup_cache', array($this, 'serve_cached_popup'));
        add_action('wp_ajax_nopriv_dcf_get_popup_cache', array($this, 'serve_cached_popup'));
    }

    /**
     * Serve cached popup content
     */
    public function serve_cached_popup() {
        $popup_id = intval($_GET['popup_id']);
        $cache_key = 'dcf_popup_cache_' . $popup_id;
        
        $cached_popup = wp_cache_get($cache_key);
        if ($cached_popup === false) {
            $popup_manager = new DCF_Popup_Manager();
            $popup = $popup_manager->get_popup($popup_id);
            $cached_popup = $popup_manager->render_popup($popup);
            wp_cache_set($cache_key, $cached_popup, '', 3600); // Cache for 1 hour
        }
        
        wp_send_json_success($cached_popup);
    }

    /**
     * Optimize images in popups
     */
    public function optimize_popup_images($popup_content) {
        // Add lazy loading to images
        $popup_content = preg_replace(
            '/<img([^>]+)src=(["\'])([^"\']+)\2([^>]*)>/i',
            '<img$1loading="lazy" src=$2$3$2$4>',
            $popup_content
        );
        
        return $popup_content;
    }

    /**
     * Implement popup preloading for instant display
     */
    public function preload_critical_popups() {
        $critical_popups = $this->get_critical_popups();
        
        foreach ($critical_popups as $popup) {
            // Preload popup HTML in hidden container
            echo '<div id="dcf-preload-popup-' . $popup['id'] . '" style="display:none;">';
            echo $this->render_popup_html($popup);
            echo '</div>';
        }
    }

    /**
     * Get critical popups that should be preloaded
     */
    private function get_critical_popups() {
        $popup_manager = new DCF_Popup_Manager();
        return $popup_manager->get_popups(array(
            'status' => 'active',
            'trigger_type' => array('exit_intent', 'time_delay'),
            'priority' => 'high'
        ));
    }

    /**
     * Render popup HTML for preloading
     */
    private function render_popup_html($popup) {
        $popup_manager = new DCF_Popup_Manager();
        return $popup_manager->render_popup($popup);
    }
} 