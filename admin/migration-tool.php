<?php
/**
 * Migration Tool for updating DCF shortcodes to CMF shortcodes
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle the migration of old DCF shortcodes to new CMF shortcodes
 */
function cmf_migrate_shortcodes() {
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
    $results = array();
    
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
                $results[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'status' => 'success'
                );
                
                // Clear post cache
                clean_post_cache($post->ID);
            } else {
                $results[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'status' => 'failed'
                );
            }
        }
    }
    
    // Clear the migration notice option
    delete_option('cmf_migration_notice_dismissed');
    
    return array(
        'total_found' => count($posts),
        'total_updated' => $updated_count,
        'details' => $results
    );
}

/**
 * Add migration tab to settings page
 */
function cmf_add_migration_tab($tabs) {
    $tabs['migration'] = __('Migration', 'cleaner-marketing-forms');
    return $tabs;
}
add_filter('dcf_settings_tabs', 'cmf_add_migration_tab');

/**
 * Render migration tab content
 */
function cmf_render_migration_tab() {
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'migration') {
        return;
    }
    
    $migration_results = null;
    
    // Handle migration request
    if (isset($_POST['run_migration']) && isset($_POST['_wpnonce_migration']) && wp_verify_nonce($_POST['_wpnonce_migration'], 'cmf_run_migration')) {
        $migration_results = cmf_migrate_shortcodes();
    }
    ?>
    <div class="cmf-migration-tool">
        <h2><?php _e('Shortcode Migration Tool', 'cleaner-marketing-forms'); ?></h2>
        
        <div class="cmf-migration-info">
            <p><?php _e('This tool will update all old DCF shortcodes to the new CMF format:', 'cleaner-marketing-forms'); ?></p>
            <ul>
                <li><code>[dcf_signup_form]</code> → <code>[cmf_signup_form]</code></li>
                <li><code>[dcf_contact_form]</code> → <code>[cmf_contact_form]</code></li>
                <li><code>[dcf_optin_form]</code> → <code>[cmf_optin_form]</code></li>
                <li><code>[dcf_form]</code> → <code>[cmf_form]</code></li>
            </ul>
            <p class="description"><?php _e('Note: This will update all published, draft, pending, and private posts/pages.', 'cleaner-marketing-forms'); ?></p>
        </div>
        
        <?php if ($migration_results): ?>
            <div class="notice notice-<?php echo $migration_results['total_updated'] > 0 ? 'success' : 'warning'; ?>">
                <p>
                    <?php 
                    printf(
                        __('Migration complete! Found %d posts/pages with old shortcodes. Successfully updated %d.', 'cleaner-marketing-forms'),
                        $migration_results['total_found'],
                        $migration_results['total_updated']
                    );
                    ?>
                </p>
                <?php if (!empty($migration_results['details'])): ?>
                    <details>
                        <summary><?php _e('View details', 'cleaner-marketing-forms'); ?></summary>
                        <ul>
                            <?php foreach ($migration_results['details'] as $detail): ?>
                                <li>
                                    <?php echo esc_html($detail['type'] . ': ' . $detail['title']); ?> 
                                    (ID: <?php echo $detail['id']; ?>) - 
                                    <?php echo $detail['status'] === 'success' ? '✓' : '✗'; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo admin_url('admin.php?page=cmf-settings&tab=migration'); ?>">
            <?php wp_nonce_field('cmf_run_migration', '_wpnonce_migration'); ?>
            <p>
                <input type="submit" name="run_migration" class="button button-primary" 
                       value="<?php _e('Run Migration', 'cleaner-marketing-forms'); ?>"
                       onclick="return confirm('<?php _e('This will update all shortcodes in your content. It is recommended to backup your database first. Continue?', 'cleaner-marketing-forms'); ?>');">
            </p>
        </form>
        
        <style>
            .cmf-migration-tool {
                max-width: 800px;
                margin-top: 20px;
            }
            .cmf-migration-info {
                background: #f1f1f1;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
            }
            .cmf-migration-info ul {
                list-style: disc;
                margin-left: 30px;
            }
            .cmf-migration-info code {
                background: #fff;
                padding: 2px 5px;
                border-radius: 3px;
            }
            details {
                margin-top: 10px;
            }
            details summary {
                cursor: pointer;
                color: #0073aa;
            }
            details ul {
                margin-top: 10px;
                list-style: none;
            }
        </style>
    </div>
    <?php
}
add_action('dcf_settings_migration_tab', 'cmf_render_migration_tab');