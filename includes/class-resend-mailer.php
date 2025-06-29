<?php
/**
 * Resend Email Integration
 *
 * @package    Dry_Cleaning_Forms
 * @subpackage Dry_Cleaning_Forms/includes
 */

/**
 * Resend Mailer class
 * 
 * Handles sending emails through Resend API
 */
class DCF_Resend_Mailer {
    
    /**
     * Resend API key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Resend API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://api.resend.com/emails';
    
    /**
     * Constructor
     *
     * @param string $api_key Resend API key
     */
    public function __construct($api_key = null) {
        $this->api_key = $api_key ?: DCF_Plugin_Core::get_setting('resend_api_key');
    }
    
    /**
     * Send email through Resend API
     *
     * @param string       $to      Recipient email
     * @param string       $subject Email subject
     * @param string       $message Email body (HTML)
     * @param array|string $headers Email headers
     * @param array        $attachments File attachments
     * @return bool Success status
     */
    public function send($to, $subject, $message, $headers = '', $attachments = array()) {
        if (!$this->api_key) {
            error_log('DCF Resend: API key not configured');
            return false;
        }
        
        // Parse headers
        $parsed_headers = $this->parse_headers($headers);
        
        // Prepare email data
        $email_data = array(
            'from' => $this->get_from_address($parsed_headers),
            'to' => is_array($to) ? $to : array($to),
            'subject' => $subject,
            'html' => $message
        );
        
        // Add reply-to if set
        if (!empty($parsed_headers['reply-to'])) {
            $email_data['reply_to'] = $parsed_headers['reply-to'];
        }
        
        // Add CC if set
        if (!empty($parsed_headers['cc'])) {
            $email_data['cc'] = is_array($parsed_headers['cc']) ? $parsed_headers['cc'] : array($parsed_headers['cc']);
        }
        
        // Add BCC if set
        if (!empty($parsed_headers['bcc'])) {
            $email_data['bcc'] = is_array($parsed_headers['bcc']) ? $parsed_headers['bcc'] : array($parsed_headers['bcc']);
        }
        
        // Add attachments if any
        if (!empty($attachments)) {
            $email_data['attachments'] = $this->prepare_attachments($attachments);
        }
        
        // Send the email
        $response = $this->make_api_request($email_data);
        
        if (is_wp_error($response)) {
            error_log('DCF Resend: Failed to send email - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code !== 200) {
            error_log('DCF Resend: API error - Code: ' . $code . ', Body: ' . $body);
            return false;
        }
        
        $result = json_decode($body, true);
        if (isset($result['id'])) {
            error_log('DCF Resend: Email sent successfully - ID: ' . $result['id']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Make API request to Resend
     *
     * @param array $data Email data
     * @return array|WP_Error Response or error
     */
    private function make_api_request($data) {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30
        );
        
        return wp_remote_post($this->api_endpoint, $args);
    }
    
    /**
     * Parse email headers
     *
     * @param array|string $headers Headers
     * @return array Parsed headers
     */
    private function parse_headers($headers) {
        $parsed = array();
        
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if (strpos($header, ':') !== false) {
                    list($key, $value) = explode(':', $header, 2);
                    $key = strtolower(trim($key));
                    $value = trim($value);
                    
                    if ($key === 'from') {
                        $parsed['from'] = $value;
                    } elseif ($key === 'reply-to') {
                        $parsed['reply-to'] = $value;
                    } elseif ($key === 'cc') {
                        $parsed['cc'] = $value;
                    } elseif ($key === 'bcc') {
                        $parsed['bcc'] = $value;
                    }
                }
            }
        } elseif (is_string($headers)) {
            $header_lines = explode("\n", $headers);
            foreach ($header_lines as $header) {
                if (strpos($header, ':') !== false) {
                    list($key, $value) = explode(':', $header, 2);
                    $key = strtolower(trim($key));
                    $value = trim($value);
                    
                    if ($key === 'from') {
                        $parsed['from'] = $value;
                    } elseif ($key === 'reply-to') {
                        $parsed['reply-to'] = $value;
                    } elseif ($key === 'cc') {
                        $parsed['cc'] = $value;
                    } elseif ($key === 'bcc') {
                        $parsed['bcc'] = $value;
                    }
                }
            }
        }
        
        return $parsed;
    }
    
    /**
     * Get from address
     *
     * @param array $headers Parsed headers
     * @return string From address
     */
    private function get_from_address($headers) {
        if (!empty($headers['from'])) {
            return $headers['from'];
        }
        
        // Use settings
        $from_email = DCF_Plugin_Core::get_setting('from_email');
        $from_name = DCF_Plugin_Core::get_setting('from_name');
        
        if (!$from_email) {
            $from_email = get_option('admin_email');
        }
        
        if (!$from_name) {
            $from_name = get_bloginfo('name');
        }
        
        return $from_name . ' <' . $from_email . '>';
    }
    
    /**
     * Prepare attachments for Resend API
     *
     * @param array $attachments WordPress attachment paths
     * @return array Formatted attachments
     */
    private function prepare_attachments($attachments) {
        $formatted = array();
        
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $content = file_get_contents($attachment);
                if ($content !== false) {
                    $formatted[] = array(
                        'filename' => basename($attachment),
                        'content' => base64_encode($content)
                    );
                }
            }
        }
        
        return $formatted;
    }
}

/**
 * Hook into WordPress mail system
 */
class DCF_Mail_Integration {
    
    /**
     * Initialize mail integration
     */
    public static function init() {
        // Hook into phpmailer_init to configure Resend when needed
        add_action('phpmailer_init', array(__CLASS__, 'configure_phpmailer'), 10, 1);
        
        // Alternative: completely replace wp_mail
        if (DCF_Plugin_Core::get_setting('use_resend_api') && DCF_Plugin_Core::get_setting('resend_api_key')) {
            add_filter('pre_wp_mail', array(__CLASS__, 'send_mail_via_resend'), 10, 2);
        }
    }
    
    /**
     * Send mail via Resend API
     *
     * @param null|bool $return Short-circuit return value
     * @param array $atts Mail attributes
     * @return bool|null
     */
    public static function send_mail_via_resend($return, $atts) {
        if (!DCF_Plugin_Core::get_setting('use_resend_api') || !DCF_Plugin_Core::get_setting('resend_api_key')) {
            return $return; // Let WordPress handle it
        }
        
        $mailer = new DCF_Resend_Mailer();
        $result = $mailer->send(
            $atts['to'],
            $atts['subject'],
            $atts['message'],
            $atts['headers'],
            $atts['attachments']
        );
        
        // Return the result to short-circuit wp_mail
        return $result;
    }
    
    /**
     * Configure PHPMailer for Resend
     *
     * @param PHPMailer $phpmailer
     */
    public static function configure_phpmailer($phpmailer) {
        // This method is kept for compatibility but not used with Resend API
    }
}

// Initialize mail integration
add_action('init', array('DCF_Mail_Integration', 'init'));