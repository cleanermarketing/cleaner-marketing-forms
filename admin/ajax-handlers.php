<?php
/**
 * AJAX Handlers for missing methods
 * 
 * This is a temporary file to add missing AJAX handlers.
 * These should be integrated into the main class later.
 */

// Handle get all forms
add_action('wp_ajax_dcf_get_all_forms', 'dcf_handle_get_all_forms');
function dcf_handle_get_all_forms() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    global $wpdb;
    $forms_table = $wpdb->prefix . 'dcf_forms';
    
    $forms = $wpdb->get_results("SELECT id, form_name, form_type FROM $forms_table ORDER BY form_name ASC");
    
    wp_send_json_success($forms);
}

// Handle get form data
add_action('wp_ajax_dcf_get_form_data', 'dcf_handle_get_form_data');
function dcf_handle_get_form_data() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dcf_admin_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $form_id = intval($_POST['form_id'] ?? 0);
    
    if (!$form_id) {
        wp_send_json_error('Invalid form ID');
    }
    
    // Use the form builder to get form data
    $form_builder = new DCF_Form_Builder();
    $form_data = $form_builder->get_form($form_id);
    
    if ($form_data) {
        // Parse the form config if it's a string
        if (isset($form_data->form_config) && is_string($form_data->form_config)) {
            $form_data->form_config = json_decode($form_data->form_config, true);
        }
        
        wp_send_json_success($form_data);
    } else {
        wp_send_json_error('Form not found');
    }
}

// Load this file
require_once __FILE__;