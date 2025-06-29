<?php
/**
 * Standalone migration script to update DCF shortcodes to CMF
 * Run this file directly or include it to perform the migration
 */

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. Please run this script from within WordPress.');
    }
}

// Only allow admin users to run this
if (!current_user_can('manage_options')) {
    die('Insufficient permissions.');
}

// Perform the migration
echo "<h2>Starting DCF to CMF Shortcode Migration</h2>\n";

global $wpdb;

// Define shortcode mappings
$shortcode_mappings = array(
    '[dcf_signup_form' => '[cmf_signup_form',
    '[dcf_contact_form' => '[cmf_contact_form',
    '[dcf_optin_form' => '[cmf_optin_form',
    '[dcf_form' => '[cmf_form'
);

// Get all posts/pages with old shortcodes
$posts_query = "
    SELECT ID, post_content, post_title, post_type 
    FROM {$wpdb->posts} 
    WHERE post_status IN ('publish', 'draft', 'pending', 'private') 
    AND (
        post_content LIKE '%[dcf_signup_form%' 
        OR post_content LIKE '%[dcf_contact_form%'
        OR post_content LIKE '%[dcf_optin_form%'
        OR post_content LIKE '%[dcf_form%'
    )
";

$posts = $wpdb->get_results($posts_query);
$updated_count = 0;

echo "<p>Found " . count($posts) . " posts/pages with old shortcodes.</p>\n";

foreach ($posts as $post) {
    $original_content = $post->post_content;
    $updated_content = $original_content;
    $changes_made = false;
    
    // Replace each old shortcode with new one
    foreach ($shortcode_mappings as $old => $new) {
        if (strpos($updated_content, $old) !== false) {
            $updated_content = str_replace($old, $new, $updated_content);
            $changes_made = true;
        }
    }
    
    // Update the post if changes were made
    if ($changes_made) {
        $update_result = $wpdb->update(
            $wpdb->posts,
            array('post_content' => $updated_content),
            array('ID' => $post->ID),
            array('%s'),
            array('%d')
        );
        
        if ($update_result !== false) {
            $updated_count++;
            echo "<p>✓ Updated {$post->post_type}: {$post->post_title} (ID: {$post->ID})</p>\n";
            
            // Clear post cache
            clean_post_cache($post->ID);
        } else {
            echo "<p>✗ Failed to update {$post->post_type}: {$post->post_title} (ID: {$post->ID})</p>\n";
        }
    }
}

// Clear the migration notice
delete_option('cmf_migration_notice_dismissed');

echo "<h3>Migration Complete!</h3>\n";
echo "<p>Successfully updated {$updated_count} posts/pages.</p>\n";
echo "<p>The old shortcodes will continue to work due to backwards compatibility, but your content now uses the new CMF shortcodes.</p>\n";