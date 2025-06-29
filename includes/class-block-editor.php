<?php
/**
 * Block Editor Integration
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block Editor class
 */
class DCF_Block_Editor {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register blocks
     */
    public function register_blocks() {
        // Register block for form shortcode
        register_block_type('dry-cleaning-forms/form', array(
            'render_callback' => array($this, 'render_form_block'),
            'attributes' => array(
                'formId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDescription' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'ajax' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));
    }
    
    /**
     * Render form block
     */
    public function render_form_block($attributes) {
        $form_id = isset($attributes['formId']) ? intval($attributes['formId']) : 0;
        
        if (!$form_id) {
            return '<p>' . __('Please select a form.', 'dry-cleaning-forms') . '</p>';
        }
        
        // Generate shortcode
        $shortcode = sprintf(
            '[dcf_form id="%d" show_title="%s" show_description="%s" ajax="%s"]',
            $form_id,
            $attributes['showTitle'] ? 'true' : 'false',
            $attributes['showDescription'] ? 'true' : 'false',
            $attributes['ajax'] ? 'true' : 'false'
        );
        
        return do_shortcode($shortcode);
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Get all forms for the dropdown
        $form_builder = new DCF_Form_Builder();
        $forms = $form_builder->get_forms(array('limit' => 100));
        
        $forms_array = array();
        foreach ($forms as $form) {
            $forms_array[] = array(
                'value' => $form->id,
                'label' => $form->form_name
            );
        }
        
        // Enqueue block editor script
        wp_enqueue_script(
            'dcf-block-editor',
            CMF_PLUGIN_URL . 'admin/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            CMF_PLUGIN_VERSION
        );
        
        // Localize script
        wp_localize_script('dcf-block-editor', 'dcf_block_editor', array(
            'forms' => $forms_array,
            'preview_nonce' => wp_create_nonce('dcf_preview_form')
        ));
    }
}