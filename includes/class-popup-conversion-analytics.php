<?php
/**
 * Popup Conversion Analytics Class
 *
 * Focuses on conversion metrics for popup analytics as requested by the user,
 * rather than heatmaps or session recordings.
 *
 * @package CleanerMarketingForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class DCF_Popup_Conversion_Analytics {
    
    /**
     * Initialize conversion analytics
     */
    public function __construct() {
        add_action('wp_ajax_dcf_track_popup_event', array($this, 'track_popup_event'));
        add_action('wp_ajax_nopriv_dcf_track_popup_event', array($this, 'track_popup_event'));
        add_action('wp_ajax_dcf_get_conversion_data', array($this, 'get_conversion_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tracking_scripts'));
        add_action('init', array($this, 'create_analytics_tables'));
    }

    /**
     * Enqueue tracking scripts
     */
    public function enqueue_tracking_scripts() {
        wp_enqueue_script(
            'dcf-conversion-tracking',
            CMF_PLUGIN_URL . 'public/js/conversion-tracking.js',
            array('jquery'),
            CMF_PLUGIN_VERSION,
            true
        );

        wp_localize_script('dcf-conversion-tracking', 'dcf_analytics', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dcf_analytics_nonce'),
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id(),
            'page_url' => get_permalink(),
            'referrer' => wp_get_referer()
        ));
    }

    /**
     * Track popup events
     */
    public function track_popup_event() {
        check_ajax_referer('dcf_analytics_nonce', 'nonce');

        $event_data = array(
            'popup_id' => intval($_POST['popup_id']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'user_id' => get_current_user_id(),
            'session_id' => sanitize_text_field($_POST['session_id']),
            'page_url' => esc_url_raw($_POST['page_url']),
            'referrer' => esc_url_raw($_POST['referrer']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'ip_address' => $this->get_user_ip(),
            'timestamp' => current_time('mysql'),
            'additional_data' => json_encode($_POST['additional_data'] ?? array())
        );

        $this->store_event($event_data);
        
        wp_send_json_success(array(
            'message' => 'Event tracked successfully'
        ));
    }

    /**
     * Store event in database
     */
    private function store_event($event_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dcf_popup_events';
        
        $wpdb->insert($table_name, $event_data, array(
            '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ));
    }

    /**
     * Get conversion data
     */
    public function get_conversion_data() {
        check_ajax_referer('dcf_analytics_nonce', 'nonce');

        $popup_id = intval($_POST['popup_id']);
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30');
        
        $conversion_data = $this->calculate_conversion_metrics($popup_id, $date_range);
        
        wp_send_json_success($conversion_data);
    }

    /**
     * Calculate conversion metrics
     */
    public function calculate_conversion_metrics($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        // Basic metrics
        $metrics = array(
            'impressions' => $this->get_event_count('impression', $popup_id, $days),
            'views' => $this->get_event_count('view', $popup_id, $days),
            'interactions' => $this->get_event_count('interaction', $popup_id, $days),
            'submissions' => $this->get_event_count('submission', $popup_id, $days),
            'conversions' => $this->get_event_count('conversion', $popup_id, $days),
            'closes' => $this->get_event_count('close', $popup_id, $days)
        );

        // Calculate conversion rates
        $metrics['view_rate'] = $metrics['impressions'] > 0 ? 
            round(($metrics['views'] / $metrics['impressions']) * 100, 2) : 0;
        
        $metrics['interaction_rate'] = $metrics['views'] > 0 ? 
            round(($metrics['interactions'] / $metrics['views']) * 100, 2) : 0;
        
        $metrics['conversion_rate'] = $metrics['views'] > 0 ? 
            round(($metrics['conversions'] / $metrics['views']) * 100, 2) : 0;
        
        $metrics['submission_rate'] = $metrics['views'] > 0 ? 
            round(($metrics['submissions'] / $metrics['views']) * 100, 2) : 0;

        // Advanced metrics
        $metrics['bounce_rate'] = $this->calculate_bounce_rate($popup_id, $days);
        $metrics['time_to_conversion'] = $this->calculate_average_time_to_conversion($popup_id, $days);
        $metrics['revenue_per_visitor'] = $this->calculate_revenue_per_visitor($popup_id, $days);
        $metrics['lifetime_value'] = $this->calculate_customer_lifetime_value($popup_id, $days);

        // Funnel analysis
        $metrics['funnel'] = $this->get_conversion_funnel($popup_id, $days);
        
        // Time-based analysis
        $metrics['hourly_performance'] = $this->get_hourly_performance($popup_id, $days);
        $metrics['daily_performance'] = $this->get_daily_performance($popup_id, $days);
        
        // Device and source analysis
        $metrics['device_breakdown'] = $this->get_device_breakdown($popup_id, $days);
        $metrics['source_breakdown'] = $this->get_source_breakdown($popup_id, $days);
        
        // A/B testing metrics
        if ($popup_id) {
            $metrics['ab_test_results'] = $this->get_ab_test_results($popup_id, $days);
        }

        return $metrics;
    }

    /**
     * Get event count
     */
    private function get_event_count($event_type, $popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$events_table} 
             WHERE event_type = %s 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}",
            $event_type,
            $days
        )));
    }

    /**
     * Calculate bounce rate
     */
    private function calculate_bounce_rate($popup_id = null, $days = 30) {
        // For popup analytics, bounce rate is simply the inverse of conversion rate
        // If 50% convert, then 50% bounce
        $conversions = $this->get_event_count('conversion', $popup_id, $days);
        $views = $this->get_event_count('view', $popup_id, $days);
        
        if ($views > 0) {
            $conversion_rate_percent = ($conversions / $views) * 100;
            return round(100 - $conversion_rate_percent, 2);
        }
        
        return 0;
    }

    /**
     * Calculate average time to conversion
     */
    private function calculate_average_time_to_conversion($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        $result = $wpdb->get_results($wpdb->prepare(
            "SELECT session_id, 
                    MIN(CASE WHEN event_type = 'view' THEN timestamp END) as first_view,
                    MAX(CASE WHEN event_type = 'conversion' THEN timestamp END) as conversion_time
             FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             AND event_type IN ('view', 'conversion')
             GROUP BY session_id 
             HAVING first_view IS NOT NULL AND conversion_time IS NOT NULL",
            $days
        ));

        if (empty($result)) {
            return 0;
        }

        $total_time = 0;
        $conversion_count = 0;

        foreach ($result as $session) {
            $time_diff = strtotime($session->conversion_time) - strtotime($session->first_view);
            $total_time += $time_diff;
            $conversion_count++;
        }

        return $conversion_count > 0 ? round($total_time / $conversion_count, 2) : 0;
    }

    /**
     * Calculate revenue per visitor
     */
    private function calculate_revenue_per_visitor($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        // Get total revenue from conversion events
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(JSON_EXTRACT(additional_data, '$.revenue') AS DECIMAL(10,2))) 
             FROM {$events_table} 
             WHERE event_type = 'conversion' 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             AND JSON_EXTRACT(additional_data, '$.revenue') IS NOT NULL",
            $days
        ));

        // Get unique visitors
        $unique_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}",
            $days
        ));

        return $unique_visitors > 0 ? round($total_revenue / $unique_visitors, 2) : 0;
    }

    /**
     * Calculate customer lifetime value
     */
    private function calculate_customer_lifetime_value($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        // This is a simplified CLV calculation
        // In practice, you'd want to track customers over longer periods
        $avg_order_value = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(CAST(JSON_EXTRACT(additional_data, '$.revenue') AS DECIMAL(10,2))) 
             FROM {$events_table} 
             WHERE event_type = 'conversion' 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             AND JSON_EXTRACT(additional_data, '$.revenue') IS NOT NULL",
            $days
        ));

        // Assume average customer makes 3 purchases per year
        $purchase_frequency = 3;
        $customer_lifespan = 2; // years

        return round($avg_order_value * $purchase_frequency * $customer_lifespan, 2);
    }

    /**
     * Get conversion funnel
     */
    private function get_conversion_funnel($popup_id = null, $days = 30) {
        $funnel_steps = array(
            'impression' => 'Popup Shown',
            'view' => 'Popup Viewed',
            'interaction' => 'User Interacted',
            'submission' => 'Form Submitted',
            'conversion' => 'Converted'
        );

        $funnel_data = array();
        $previous_count = null;

        foreach ($funnel_steps as $step => $label) {
            $count = $this->get_event_count($step, $popup_id, $days);
            $drop_off_rate = $previous_count ? 
                round((($previous_count - $count) / $previous_count) * 100, 2) : 0;

            $funnel_data[] = array(
                'step' => $step,
                'label' => $label,
                'count' => $count,
                'drop_off_rate' => $drop_off_rate
            );

            $previous_count = $count;
        }

        return $funnel_data;
    }

    /**
     * Get hourly performance
     */
    private function get_hourly_performance($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(timestamp) as hour,
                    COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                    COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                    ROUND((COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) / 
                           COUNT(CASE WHEN event_type = 'view' THEN 1 END)) * 100, 2) as conversion_rate
             FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             GROUP BY HOUR(timestamp) 
             ORDER BY hour",
            $days
        ), ARRAY_A);
    }

    /**
     * Get daily performance
     */
    private function get_daily_performance($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date,
                    COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                    COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                    ROUND((COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) / 
                           COUNT(CASE WHEN event_type = 'view' THEN 1 END)) * 100, 2) as conversion_rate
             FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             GROUP BY DATE(timestamp) 
             ORDER BY date DESC",
            $days
        ), ARRAY_A);
    }

    /**
     * Get device breakdown
     */
    private function get_device_breakdown($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                    WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type,
                COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                ROUND((COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) / 
                       COUNT(CASE WHEN event_type = 'view' THEN 1 END)) * 100, 2) as conversion_rate
             FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             GROUP BY device_type 
             ORDER BY views DESC",
            $days
        ), ARRAY_A);
    }

    /**
     * Get source breakdown
     */
    private function get_source_breakdown($popup_id = null, $days = 30) {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        $popup_condition = $popup_id ? "AND popup_id = {$popup_id}" : "";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN referrer LIKE '%google%' THEN 'Google'
                    WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                    WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                    WHEN referrer = '' THEN 'Direct'
                    ELSE 'Other'
                END as source,
                COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                ROUND((COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) / 
                       COUNT(CASE WHEN event_type = 'view' THEN 1 END)) * 100, 2) as conversion_rate
             FROM {$events_table} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
             {$popup_condition}
             GROUP BY source 
             ORDER BY views DESC",
            $days
        ), ARRAY_A);
    }

    /**
     * Get A/B test results
     */
    private function get_ab_test_results($popup_id, $days = 30) {
        global $wpdb;

        $ab_table = $wpdb->prefix . 'dcf_ab_tests';
        $events_table = $wpdb->prefix . 'dcf_popup_events';

        // Get active A/B tests for this popup
        $ab_tests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$ab_table} 
             WHERE popup_id = %d 
             AND status = 'active'
             AND start_date <= NOW() 
             AND (end_date IS NULL OR end_date >= NOW())",
            $popup_id
        ), ARRAY_A);

        $results = array();

        foreach ($ab_tests as $test) {
            $variants = json_decode($test['variants'], true);
            $variant_results = array();

            foreach ($variants as $variant) {
                $views = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$events_table} 
                     WHERE popup_id = %d 
                     AND event_type = 'view'
                     AND JSON_EXTRACT(additional_data, '$.variant') = %s
                     AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $popup_id,
                    $variant['id'],
                    $days
                ));

                $conversions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$events_table} 
                     WHERE popup_id = %d 
                     AND event_type = 'conversion'
                     AND JSON_EXTRACT(additional_data, '$.variant') = %s
                     AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $popup_id,
                    $variant['id'],
                    $days
                ));

                $conversion_rate = $views > 0 ? round(($conversions / $views) * 100, 2) : 0;

                $variant_results[] = array(
                    'variant_id' => $variant['id'],
                    'variant_name' => $variant['name'],
                    'views' => $views,
                    'conversions' => $conversions,
                    'conversion_rate' => $conversion_rate
                );
            }

            $results[] = array(
                'test_id' => $test['id'],
                'test_name' => $test['test_name'],
                'variants' => $variant_results,
                'statistical_significance' => $this->calculate_statistical_significance($variant_results)
            );
        }

        return $results;
    }

    /**
     * Calculate statistical significance for A/B tests
     */
    private function calculate_statistical_significance($variants) {
        if (count($variants) < 2) {
            return null;
        }

        // Simple z-test for two proportions
        $control = $variants[0];
        $variant = $variants[1];

        $p1 = $control['conversions'] / max($control['views'], 1);
        $p2 = $variant['conversions'] / max($variant['views'], 1);
        
        $n1 = $control['views'];
        $n2 = $variant['views'];

        if ($n1 < 30 || $n2 < 30) {
            return array('significant' => false, 'confidence' => 0, 'note' => 'Insufficient sample size');
        }

        $p_pool = ($control['conversions'] + $variant['conversions']) / ($n1 + $n2);
        $se = sqrt($p_pool * (1 - $p_pool) * (1/$n1 + 1/$n2));
        
        if ($se == 0) {
            return array('significant' => false, 'confidence' => 0, 'note' => 'No variance');
        }

        $z = abs($p2 - $p1) / $se;
        
        // Convert z-score to confidence level
        $confidence = (1 - 2 * (1 - $this->normal_cdf($z))) * 100;
        $significant = $confidence >= 95;

        return array(
            'significant' => $significant,
            'confidence' => round($confidence, 2),
            'z_score' => round($z, 4),
            'note' => $significant ? 'Statistically significant' : 'Not statistically significant'
        );
    }

    /**
     * Normal cumulative distribution function
     */
    private function normal_cdf($x) {
        return 0.5 * (1 + $this->erf($x / sqrt(2)));
    }

    /**
     * Error function approximation
     */
    private function erf($x) {
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * Get session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }

    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Create analytics tables
     */
    public function create_analytics_tables() {
        global $wpdb;

        $events_table = $wpdb->prefix . 'dcf_popup_events';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$events_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            popup_id int(11) NOT NULL,
            event_type varchar(50) NOT NULL,
            user_id int(11) DEFAULT 0,
            session_id varchar(100) NOT NULL,
            page_url text NOT NULL,
            referrer text,
            user_agent text,
            ip_address varchar(45),
            timestamp datetime NOT NULL,
            additional_data longtext,
            PRIMARY KEY (id),
            KEY popup_id (popup_id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 