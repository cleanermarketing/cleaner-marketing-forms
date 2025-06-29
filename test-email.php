<?php
/**
 * Test email functionality
 * 
 * Access this file directly to test if WordPress email is working
 */

// Load WordPress
// Go up from plugins/cleaner-marketing-forms to public root
// __FILE__ is in /wp-content/plugins/cleaner-marketing-forms/
// Need to go up 3 levels: cleaner-marketing-forms -> plugins -> wp-content -> root
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('Error: Could not find wp-load.php at ' . $wp_load_path);
}
require_once($wp_load_path);

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

$test_email = isset($_GET['email']) ? sanitize_email($_GET['email']) : get_option('admin_email');
$sent = false;
$error = '';

if (isset($_GET['send'])) {
    // Test basic WordPress mail
    $subject = 'Test Email from Dry Cleaning Forms Plugin';
    $message = "This is a test email to verify that WordPress email functionality is working.\n\n";
    $message .= "Sent at: " . current_time('mysql') . "\n";
    $message .= "Site: " . get_bloginfo('name') . "\n";
    $message .= "URL: " . home_url() . "\n";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    $result = wp_mail($test_email, $subject, $message, $headers);
    
    if ($result) {
        $sent = true;
    } else {
        $error = 'wp_mail() returned false. Check your WordPress email configuration.';
        
        // Check if mail() is disabled
        if (!function_exists('mail')) {
            $error .= ' The PHP mail() function is not available.';
        }
    }
}

// Get email settings
$enable_admin = DCF_Plugin_Core::get_setting('enable_admin_notifications');
$enable_customer = DCF_Plugin_Core::get_setting('enable_customer_confirmations');
$admin_email = DCF_Plugin_Core::get_setting('admin_notification_email') ?: get_option('admin_email');
$from_email = DCF_Plugin_Core::get_setting('from_email') ?: get_option('admin_email');
$from_name = DCF_Plugin_Core::get_setting('from_name') ?: get_bloginfo('name');
$use_resend = DCF_Plugin_Core::get_setting('use_resend_api');
$resend_api_key = DCF_Plugin_Core::get_setting('resend_api_key');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test - Dry Cleaning Forms</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .test-form {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 3px;
        }
        input[type="email"] {
            width: 300px;
            padding: 8px;
            margin-right: 10px;
        }
        button {
            padding: 8px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background: #005a87;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Test - Dry Cleaning Forms Plugin</h1>
        
        <?php if ($sent): ?>
            <div class="status success">
                ✓ Test email sent successfully to <?php echo esc_html($test_email); ?>
            </div>
        <?php elseif ($error): ?>
            <div class="status error">
                ✗ Failed to send test email: <?php echo esc_html($error); ?>
            </div>
        <?php endif; ?>
        
        <h2>Current Email Settings</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Email Provider</td>
                <td><?php echo $use_resend ? 'Resend API' : 'WordPress Default (PHP mail)'; ?></td>
                <td><?php echo $use_resend ? '✓' : '⚠'; ?></td>
            </tr>
            <?php if ($use_resend): ?>
            <tr>
                <td>Resend API Key</td>
                <td><?php echo $resend_api_key ? substr($resend_api_key, 0, 10) . '...' : 'Not configured'; ?></td>
                <td><?php echo $resend_api_key ? '✓' : '✗'; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Admin Notifications Enabled</td>
                <td><?php echo $enable_admin ? 'Yes' : 'No'; ?></td>
                <td><?php echo $enable_admin ? '✓' : '✗'; ?></td>
            </tr>
            <tr>
                <td>Customer Confirmations Enabled</td>
                <td><?php echo $enable_customer ? 'Yes' : 'No'; ?></td>
                <td><?php echo $enable_customer ? '✓' : '✗'; ?></td>
            </tr>
            <tr>
                <td>Admin Email</td>
                <td><?php echo esc_html($admin_email); ?></td>
                <td>✓</td>
            </tr>
            <tr>
                <td>From Email</td>
                <td><?php echo esc_html($from_email); ?></td>
                <td>✓</td>
            </tr>
            <tr>
                <td>From Name</td>
                <td><?php echo esc_html($from_name); ?></td>
                <td>✓</td>
            </tr>
        </table>
        
        <?php if (!$enable_admin && !$enable_customer): ?>
            <div class="status warning">
                ⚠ Both admin notifications and customer confirmations are disabled. 
                <a href="<?php echo admin_url('admin.php?page=dcf-settings&tab=email'); ?>">Enable them in settings</a>
            </div>
        <?php endif; ?>
        
        <?php if ($use_resend && !$resend_api_key): ?>
            <div class="status error">
                ✗ Resend API is enabled but no API key is configured. 
                <a href="<?php echo admin_url('admin.php?page=dcf-settings&tab=email'); ?>">Add your API key</a>
            </div>
        <?php elseif (!$use_resend): ?>
            <div class="status info">
                ℹ Using default WordPress mail. For better deliverability, consider enabling Resend API in 
                <a href="<?php echo admin_url('admin.php?page=dcf-settings&tab=email'); ?>">email settings</a>
            </div>
        <?php endif; ?>
        
        <h2>Send Test Email</h2>
        <div class="test-form">
            <form method="get">
                <label>
                    Send test email to:
                    <input type="email" name="email" value="<?php echo esc_attr($test_email); ?>" required>
                </label>
                <button type="submit" name="send" value="1">Send Test Email</button>
            </form>
        </div>
        
        <h2>PHP Mail Configuration</h2>
        <table>
            <tr>
                <td>PHP mail() function</td>
                <td><?php echo function_exists('mail') ? 'Available' : 'Not Available'; ?></td>
                <td><?php echo function_exists('mail') ? '✓' : '✗'; ?></td>
            </tr>
            <tr>
                <td>sendmail_path</td>
                <td><?php echo ini_get('sendmail_path') ?: 'Not set'; ?></td>
                <td><?php echo ini_get('sendmail_path') ? '✓' : '⚠'; ?></td>
            </tr>
            <tr>
                <td>SMTP</td>
                <td><?php echo ini_get('SMTP') ?: 'localhost'; ?></td>
                <td>✓</td>
            </tr>
            <tr>
                <td>smtp_port</td>
                <td><?php echo ini_get('smtp_port') ?: '25'; ?></td>
                <td>✓</td>
            </tr>
        </table>
        
        <div class="status info">
            <strong>Note:</strong> If emails are not being received, you may need to:
            <ul>
                <li>Configure an SMTP plugin (like WP Mail SMTP)</li>
                <li>Check your hosting provider's email settings</li>
                <li>Verify that emails are not going to spam</li>
                <li>Ensure your server has a mail transfer agent installed</li>
            </ul>
        </div>
        
        <p><a href="<?php echo admin_url('admin.php?page=dcf-settings&tab=email'); ?>">← Back to Email Settings</a></p>
    </div>
</body>
</html>