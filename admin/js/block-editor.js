/**
 * Block Editor JavaScript
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { SelectControl, ToggleControl, PanelBody, Placeholder } = wp.components;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;
    
    registerBlockType('dry-cleaning-forms/form', {
        title: __('Dry Cleaning Form', 'dry-cleaning-forms'),
        icon: 'feedback',
        category: 'widgets',
        attributes: {
            formId: {
                type: 'number',
                default: 0
            },
            showTitle: {
                type: 'boolean',
                default: true
            },
            showDescription: {
                type: 'boolean',
                default: true
            },
            ajax: {
                type: 'boolean',
                default: true
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { formId, showTitle, showDescription, ajax } = attributes;
            
            const forms = window.dcf_block_editor.forms || [];
            const formOptions = [
                { value: 0, label: __('Select a form...', 'dry-cleaning-forms') },
                ...forms
            ];
            
            const selectedForm = forms.find(form => form.value === formId);
            
            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Form Settings', 'dry-cleaning-forms') },
                        el(SelectControl, {
                            label: __('Select Form', 'dry-cleaning-forms'),
                            value: formId,
                            options: formOptions,
                            onChange: (value) => setAttributes({ formId: parseInt(value) })
                        }),
                        el(ToggleControl, {
                            label: __('Show Title', 'dry-cleaning-forms'),
                            checked: showTitle,
                            onChange: (value) => setAttributes({ showTitle: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show Description', 'dry-cleaning-forms'),
                            checked: showDescription,
                            onChange: (value) => setAttributes({ showDescription: value })
                        }),
                        el(ToggleControl, {
                            label: __('Enable AJAX', 'dry-cleaning-forms'),
                            checked: ajax,
                            onChange: (value) => setAttributes({ ajax: value })
                        })
                    )
                ),
                formId ? 
                    el(
                        'div',
                        { 
                            className: 'dcf-block-preview',
                            style: {
                                padding: '20px',
                                border: '2px solid #0073aa',
                                borderRadius: '4px',
                                backgroundColor: '#f0f8ff',
                                textAlign: 'center'
                            }
                        },
                        el('div', { style: { marginBottom: '10px' } },
                            el('span', { 
                                className: 'dashicons dashicons-feedback',
                                style: { fontSize: '32px', color: '#0073aa' }
                            })
                        ),
                        el('h4', { style: { margin: '0 0 10px 0' } }, 
                            selectedForm ? selectedForm.label : __('Form', 'dry-cleaning-forms')
                        ),
                        el('p', { style: { margin: 0, color: '#666' } }, 
                            __('Form will be displayed here on the frontend', 'dry-cleaning-forms')
                        )
                    ) :
                    el(
                        Placeholder,
                        {
                            icon: 'feedback',
                            label: __('Dry Cleaning Form', 'dry-cleaning-forms'),
                            instructions: __('Select a form to display', 'dry-cleaning-forms')
                        },
                        el(SelectControl, {
                            value: formId,
                            options: formOptions,
                            onChange: (value) => setAttributes({ formId: parseInt(value) })
                        })
                    )
            );
        },
        
        save: function() {
            // Render on server side
            return null;
        }
    });
})(window.wp);