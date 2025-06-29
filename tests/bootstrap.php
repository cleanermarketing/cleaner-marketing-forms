<?php
/**
 * PHPUnit Bootstrap for Dry Cleaning Forms Plugin
 *
 * @package CleanerMarketingForms
 */

// Define test environment
define('DCF_TESTING', true);

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/dry-cleaning-forms.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test utilities
require_once dirname(__FILE__) . '/includes/class-dcf-test-case.php';
require_once dirname(__FILE__) . '/includes/class-dcf-test-factory.php';
require_once dirname(__FILE__) . '/includes/class-dcf-mock-integrations.php'; 