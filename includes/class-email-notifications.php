<?php
/**
 * Email Notifications Handler
 *
 * @package    Dry_Cleaning_Forms
 * @subpackage Dry_Cleaning_Forms/includes
 */

/**
 * Email Notifications class
 */
class DCF_Email_Notifications {
    
    /**
     * Send form submission notifications
     *
     * @param string $form_type Type of form (contact, opt-in, etc.)
     * @param array  $form_data Form submission data
     * @param int    $submission_id Submission ID
     * @param int    $form_id Form ID (for form builder forms)
     * @return bool Success status
     */
    public static function send_form_submission_notifications($form_type, $form_data, $submission_id, $form_id = null) {
        $success = true;
        
        // Send admin notification if enabled
        if (DCF_Plugin_Core::get_setting('enable_admin_notifications')) {
            $admin_result = self::send_admin_notification($form_type, $form_data, $submission_id, $form_id);
            if (!$admin_result) {
                $success = false;
            }
        }
        
        // Send customer confirmation if enabled and email is available
        if (DCF_Plugin_Core::get_setting('enable_customer_confirmations')) {
            $email = self::get_email_from_form_data($form_data);
            if ($email) {
                $customer_result = self::send_customer_confirmation($email, $form_type, $form_data, $submission_id, $form_id);
                if (!$customer_result) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Send admin notification email
     *
     * @param string $form_type Type of form
     * @param array  $form_data Form submission data
     * @param int    $submission_id Submission ID
     * @param int    $form_id Form ID (for form builder forms)
     * @return bool Success status
     */
    private static function send_admin_notification($form_type, $form_data, $submission_id, $form_id = null) {
        $to = DCF_Plugin_Core::get_setting('admin_notification_email');
        if (!$to) {
            $to = get_option('admin_email');
        }
        
        $subject = self::get_admin_subject($form_type, $form_id);
        $message = self::get_admin_message($form_type, $form_data, $submission_id, $form_id);
        $headers = self::get_email_headers();
        
        $result = wp_mail($to, $subject, $message, $headers);
        
        if (!$result) {
            error_log('DCF: Failed to send admin notification email to ' . $to);
        }
        
        return $result;
    }
    
    /**
     * Send customer confirmation email
     *
     * @param string $email Customer email
     * @param string $form_type Type of form
     * @param array  $form_data Form submission data
     * @param int    $submission_id Submission ID
     * @param int    $form_id Form ID (for form builder forms)
     * @return bool Success status
     */
    private static function send_customer_confirmation($email, $form_type, $form_data, $submission_id, $form_id = null) {
        $subject = self::get_customer_subject($form_type, $form_id);
        $message = self::get_customer_message($form_type, $form_data, $submission_id, $form_id);
        $headers = self::get_email_headers();
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        if (!$result) {
            error_log('DCF: Failed to send customer confirmation email to ' . $email);
        }
        
        return $result;
    }
    
    /**
     * Get email from form data
     *
     * @param array $form_data Form submission data
     * @return string|null Email address or null
     */
    private static function get_email_from_form_data($form_data) {
        // Check common email field names
        $email_fields = array('email', 'Email', 'customer_email', 'user_email');
        
        foreach ($email_fields as $field) {
            if (!empty($form_data[$field]) && is_email($form_data[$field])) {
                return $form_data[$field];
            }
        }
        
        return null;
    }
    
    /**
     * Get email headers
     *
     * @return array Email headers
     */
    private static function get_email_headers() {
        $from_email = DCF_Plugin_Core::get_setting('from_email');
        if (!$from_email) {
            $from_email = get_option('admin_email');
        }
        
        $from_name = DCF_Plugin_Core::get_setting('from_name');
        if (!$from_name) {
            $from_name = get_bloginfo('name');
        }
        
        $headers = array(
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Content-Type: text/html; charset=UTF-8'
        );
        
        return $headers;
    }
    
    /**
     * Get admin email subject
     *
     * @param string $form_type Type of form
     * @param int    $form_id Form ID (for form builder forms)
     * @return string Email subject
     */
    private static function get_admin_subject($form_type, $form_id = null) {
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            if ($form) {
                return sprintf(__('New Form Submission: %s', 'dry-cleaning-forms'), $form->form_name);
            }
        }
        
        switch ($form_type) {
            case 'contact':
                return __('New Contact Form Submission', 'dry-cleaning-forms');
            case 'opt-in':
                return __('New Opt-In Form Submission', 'dry-cleaning-forms');
            case 'customer_signup':
                return __('New Customer Signup', 'dry-cleaning-forms');
            default:
                return __('New Form Submission', 'dry-cleaning-forms');
        }
    }
    
    /**
     * Get customer email subject
     *
     * @param string $form_type Type of form
     * @param int    $form_id Form ID (for form builder forms)
     * @return string Email subject
     */
    private static function get_customer_subject($form_type, $form_id = null) {
        return __('Thank you for your submission', 'dry-cleaning-forms');
    }
    
    /**
     * Get admin email message
     *
     * @param string $form_type Type of form
     * @param array  $form_data Form submission data
     * @param int    $submission_id Submission ID
     * @param int    $form_id Form ID (for form builder forms)
     * @return string Email message
     */
    private static function get_admin_message($form_type, $form_data, $submission_id, $form_id = null) {
        // Get field labels if this is a form builder form
        $field_labels = array();
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            if ($form && isset($form->form_config['fields'])) {
                foreach ($form->form_config['fields'] as $field) {
                    if (isset($field['id']) && isset($field['label'])) {
                        $field_labels[$field['id']] = $field['label'];
                    }
                }
            }
        }
        
        ob_start();
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
                .field { margin-bottom: 15px; }
                .field-label { font-weight: bold; color: #555; }
                .field-value { margin-left: 10px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2><?php echo self::get_admin_subject($form_type, $form_id); ?></h2>
                
                <p><?php _e('A new form submission has been received:', 'dry-cleaning-forms'); ?></p>
                
                <?php foreach ($form_data as $field_name => $field_value): ?>
                    <?php 
                    if (is_array($field_value)) continue; // Skip complex fields for now
                    if (empty($field_value) || trim($field_value) === '') continue; // Skip empty fields
                    
                    // Determine the display label
                    $display_label = $field_name;
                    
                    // Check if we have a label for this field ID
                    if (isset($field_labels[$field_name])) {
                        $display_label = $field_labels[$field_name];
                    } else {
                        // Try to format common field names
                        $common_fields = array(
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'name' => 'Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'address' => 'Address',
                            'street' => 'Street Address',
                            'street2' => 'Address Line 2',
                            'city' => 'City',
                            'state' => 'State',
                            'zip' => 'ZIP Code',
                            'postal_code' => 'Postal Code',
                            'message' => 'Message',
                            'subject' => 'Subject',
                            'comments' => 'Comments',
                            'company' => 'Company',
                            'website' => 'Website'
                        );
                        
                        if (isset($common_fields[strtolower($field_name)])) {
                            $display_label = $common_fields[strtolower($field_name)];
                        } elseif (preg_match('/^\d+$/', $field_name)) {
                            // If it's just numbers (timestamp ID), try to skip or show as "Field"
                            $display_label = 'Field ' . substr($field_name, -4);
                        } else {
                            // Format the field name nicely
                            $display_label = ucwords(str_replace(array('_', '-'), ' ', $field_name));
                        }
                    }
                    ?>
                    <div class="field">
                        <span class="field-label"><?php echo esc_html($display_label); ?>:</span>
                        <span class="field-value"><?php echo esc_html($field_value); ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="footer">
                    <p><?php printf(__('Submission ID: %d', 'dry-cleaning-forms'), $submission_id); ?></p>
                    <p><?php printf(__('Submitted on: %s', 'dry-cleaning-forms'), current_time('F j, Y g:i a')); ?></p>
                    <?php if ($form_id): ?>
                        <p><?php printf(__('Form ID: %d', 'dry-cleaning-forms'), $form_id); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get customer email message
     *
     * @param string $form_type Type of form
     * @param array  $form_data Form submission data
     * @param int    $submission_id Submission ID
     * @param int    $form_id Form ID (for form builder forms)
     * @return string Email message
     */
    private static function get_customer_message($form_type, $form_data, $submission_id, $form_id = null) {
        // Get field labels if this is a form builder form
        $field_labels = array();
        if ($form_id) {
            $form_builder = new DCF_Form_Builder();
            $form = $form_builder->get_form($form_id);
            if ($form && isset($form->form_config['fields'])) {
                foreach ($form->form_config['fields'] as $field) {
                    if (isset($field['id']) && isset($field['label'])) {
                        $field_labels[$field['id']] = $field['label'];
                    }
                }
            }
        }
        
        ob_start();
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #333; }
                .message { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .summary { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .summary h3 { margin-top: 0; color: #555; }
                .field { margin-bottom: 10px; }
                .field-label { font-weight: bold; color: #555; }
                .field-value { color: #333; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2><?php _e('Thank you for your submission!', 'dry-cleaning-forms'); ?></h2>
                
                <div class="message">
                    <p><?php _e('We have received your information and will get back to you soon.', 'dry-cleaning-forms'); ?></p>
                    <p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'dry-cleaning-forms'); ?></p>
                </div>
                
                <div class="summary">
                    <h3><?php _e('Your Submission Details:', 'dry-cleaning-forms'); ?></h3>
                    <?php 
                    $has_content = false;
                    foreach ($form_data as $field_name => $field_value): 
                        if (is_array($field_value) || empty($field_value) || trim($field_value) === '') continue;
                        
                        // Use same label logic as admin email
                        $display_label = $field_name;
                        if (isset($field_labels[$field_name])) {
                            $display_label = $field_labels[$field_name];
                        } else {
                            $common_fields = array(
                                'first_name' => 'First Name',
                                'last_name' => 'Last Name',
                                'name' => 'Name',
                                'email' => 'Email',
                                'phone' => 'Phone',
                                'address' => 'Address',
                                'street' => 'Street Address',
                                'street2' => 'Address Line 2',
                                'city' => 'City',
                                'state' => 'State',
                                'zip' => 'ZIP Code',
                                'postal_code' => 'Postal Code',
                                'message' => 'Message',
                                'subject' => 'Subject',
                                'comments' => 'Comments',
                                'company' => 'Company',
                                'website' => 'Website'
                            );
                            
                            if (isset($common_fields[strtolower($field_name)])) {
                                $display_label = $common_fields[strtolower($field_name)];
                            } elseif (!preg_match('/^\d+$/', $field_name)) {
                                $display_label = ucwords(str_replace(array('_', '-'), ' ', $field_name));
                            } else {
                                continue; // Skip timestamp-only field names in customer email
                            }
                        }
                        $has_content = true;
                    ?>
                        <div class="field">
                            <span class="field-label"><?php echo esc_html($display_label); ?>:</span>
                            <span class="field-value"><?php echo esc_html($field_value); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$has_content): ?>
                        <p><?php _e('Your submission has been recorded.', 'dry-cleaning-forms'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="footer">
                    <p><?php echo get_bloginfo('name'); ?></p>
                    <?php if ($phone = get_option('dcf_business_phone')): ?>
                        <p><?php printf(__('Phone: %s', 'dry-cleaning-forms'), $phone); ?></p>
                    <?php endif; ?>
                    <?php if ($email = get_option('dcf_business_email')): ?>
                        <p><?php printf(__('Email: %s', 'dry-cleaning-forms'), $email); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send signup completion notification
     *
     * @param array $user_data User data from signup
     * @param int   $submission_id Submission ID
     * @return bool Success status
     */
    public static function send_signup_completion_notifications($user_data, $submission_id) {
        $form_data = array(
            'first_name' => isset($user_data['first_name']) ? $user_data['first_name'] : '',
            'last_name' => isset($user_data['last_name']) ? $user_data['last_name'] : '',
            'email' => isset($user_data['email']) ? $user_data['email'] : '',
            'phone' => isset($user_data['phone']) ? $user_data['phone'] : '',
            'service_preference' => isset($user_data['service_preference']) ? $user_data['service_preference'] : '',
        );
        
        if (isset($user_data['street'])) {
            $form_data['address'] = $user_data['street'];
            if (!empty($user_data['street2'])) {
                $form_data['address'] .= ', ' . $user_data['street2'];
            }
            $form_data['city'] = $user_data['city'];
            $form_data['state'] = $user_data['state'];
            $form_data['zip'] = $user_data['zip'];
        }
        
        return self::send_form_submission_notifications('customer_signup', $form_data, $submission_id);
    }
}