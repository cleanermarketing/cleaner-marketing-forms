<?php
/**
 * Plugin Updater Class
 *
 * Handles automatic updates from GitHub repository
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CMF_Updater class
 */
class CMF_Updater {
    
    /**
     * Update checker instance
     *
     * @var object
     */
    private $update_checker;
    
    /**
     * GitHub repository URL
     *
     * @var string
     */
    private $repository_url;
    
    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug = 'cleaner-marketing-forms';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set repository URL - can be overridden by constant
        $this->repository_url = defined('CMF_UPDATE_REPO_URL') 
            ? CMF_UPDATE_REPO_URL 
            : 'https://github.com/cleanermarketing/cleaner-marketing-forms/';
        
        // Initialize updater after plugins loaded
        add_action('init', array($this, 'init_updater'));
        
        // Add settings field for update configuration
        add_action('dcf_settings_updates_section', array($this, 'render_update_settings'));
        
        // Handle manual update check
        add_action('admin_init', array($this, 'handle_manual_update_check'));
    }
    
    /**
     * Initialize the update checker
     */
    public function init_updater() {
        // Check if plugin update checker is available
        if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            // Try to load via Composer autoload
            $autoload_file = CMF_PLUGIN_DIR . 'vendor/autoload.php';
            if (file_exists($autoload_file)) {
                require_once $autoload_file;
            } else {
                // Log error and return
                error_log('CMF Update Checker: Plugin Update Checker library not found');
                return;
            }
        }
        
        try {
            // Build update checker
            $this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                $this->repository_url,
                CMF_PLUGIN_FILE,
                $this->plugin_slug
            );
            
            // Set branch (default to main)
            $branch = defined('CMF_UPDATE_BRANCH') ? CMF_UPDATE_BRANCH : 'main';
            $this->update_checker->setBranch($branch);
            
            // Set authentication if using private repository
            if (defined('CMF_GITHUB_TOKEN') && CMF_GITHUB_TOKEN) {
                $this->update_checker->setAuthentication(CMF_GITHUB_TOKEN);
            }
            
            // Add custom release assets filter if needed
            add_filter('puc_request_info_result-' . $this->plugin_slug, array($this, 'filter_update_info'), 10, 2);
            
            // Enable debug mode if in development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                add_action('admin_notices', array($this, 'show_update_debug_info'));
            }
            
        } catch (Exception $e) {
            error_log('CMF Update Checker Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Filter update information
     *
     * @param object $update_info Update information
     * @param array $http_result HTTP result
     * @return object Modified update information
     */
    public function filter_update_info($update_info, $http_result = null) {
        if ($update_info && !empty($update_info->download_url)) {
            // Add custom changelog URL if needed
            if (empty($update_info->sections['changelog'])) {
                $update_info->sections['changelog'] = $this->get_changelog();
            }
            
            // Add other custom sections
            $update_info->sections['description'] = 'Comprehensive WordPress plugin for dry cleaning and laundry service businesses.';
            
            // Set icons if available
            $update_info->icons = array(
                '1x' => CMF_PLUGIN_URL . 'assets/icon-128x128.png',
                '2x' => CMF_PLUGIN_URL . 'assets/icon-256x256.png'
            );
        }
        
        return $update_info;
    }
    
    /**
     * Get changelog from repository
     *
     * @return string Changelog HTML
     */
    private function get_changelog() {
        $changelog_url = trailingslashit($this->repository_url) . 'raw/main/CHANGELOG.md';
        
        $response = wp_remote_get($changelog_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'text/plain'
            )
        ));
        
        if (is_wp_error($response)) {
            return '<p>Changelog not available.</p>';
        }
        
        $changelog = wp_remote_retrieve_body($response);
        
        if (empty($changelog)) {
            return '<p>Changelog not available.</p>';
        }
        
        // Convert markdown to HTML (basic conversion)
        $changelog = $this->markdown_to_html($changelog);
        
        return $changelog;
    }
    
    /**
     * Basic markdown to HTML conversion
     *
     * @param string $markdown Markdown text
     * @return string HTML
     */
    private function markdown_to_html($markdown) {
        // Convert headers
        $html = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $markdown);
        $html = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $html);
        
        // Convert lists
        $html = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)\n/s', '<ul>$1</ul>', $html);
        
        // Convert line breaks
        $html = nl2br($html);
        
        return $html;
    }
    
    /**
     * Render update settings
     */
    public function render_update_settings() {
        ?>
        <h3><?php _e('Automatic Updates', 'cleaner-marketing-forms'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Update Status', 'cleaner-marketing-forms'); ?></th>
                <td>
                    <?php if ($this->update_checker): ?>
                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                        <?php _e('Automatic updates are enabled', 'cleaner-marketing-forms'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-no" style="color: red;"></span>
                        <?php _e('Automatic updates are not configured', 'cleaner-marketing-forms'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Current Version', 'cleaner-marketing-forms'); ?></th>
                <td><?php echo CMF_PLUGIN_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Update Source', 'cleaner-marketing-forms'); ?></th>
                <td>
                    <code><?php echo esc_html($this->repository_url); ?></code>
                    <?php if (defined('CMF_UPDATE_BRANCH')): ?>
                        <br><small><?php printf(__('Branch: %s', 'cleaner-marketing-forms'), CMF_UPDATE_BRANCH); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Check for Updates', 'cleaner-marketing-forms'); ?></th>
                <td>
                    <form method="post" action="">
                        <?php wp_nonce_field('dcf_check_update', 'dcf_update_nonce'); ?>
                        <input type="hidden" name="dcf_check_update" value="1">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Check Now', 'cleaner-marketing-forms'); ?>
                        </button>
                    </form>
                    <?php if (get_transient('dcf_update_check_result')): ?>
                        <p class="description" style="color: green;">
                            <?php echo get_transient('dcf_update_check_result'); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Handle manual update check
     */
    public function handle_manual_update_check() {
        if (!isset($_POST['dcf_check_update']) || !$_POST['dcf_check_update']) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['dcf_update_nonce'], 'dcf_check_update')) {
            return;
        }
        
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        if ($this->update_checker) {
            // Force check for updates
            $update = $this->update_checker->checkForUpdates();
            
            if ($update && version_compare($update->version, CMF_PLUGIN_VERSION, '>')) {
                $message = sprintf(
                    __('New version %s is available!', 'cleaner-marketing-forms'),
                    $update->version
                );
            } else {
                $message = __('You have the latest version.', 'cleaner-marketing-forms');
            }
            
            set_transient('dcf_update_check_result', $message, 30);
            
            // Clear WordPress update cache
            delete_site_transient('update_plugins');
        }
    }
    
    /**
     * Show debug information
     */
    public function show_update_debug_info() {
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        if (!$this->update_checker || !isset($_GET['dcf_debug_updates'])) {
            return;
        }
        
        $state = $this->update_checker->getUpdateState();
        $latest_version = $state->getUpdate();
        
        ?>
        <div class="notice notice-info">
            <p><strong>CMF Update Debug Info:</strong></p>
            <ul>
                <li>Current Version: <?php echo CMF_PLUGIN_VERSION; ?></li>
                <li>Latest Version: <?php echo $latest_version ? $latest_version->version : 'Unknown'; ?></li>
                <li>Last Check: <?php echo $state->getLastCheck() ? date('Y-m-d H:i:s', $state->getLastCheck()) : 'Never'; ?></li>
                <li>Update Available: <?php echo $latest_version && version_compare($latest_version->version, CMF_PLUGIN_VERSION, '>') ? 'Yes' : 'No'; ?></li>
            </ul>
        </div>
        <?php
    }
}

// Initialize updater
new CMF_Updater();