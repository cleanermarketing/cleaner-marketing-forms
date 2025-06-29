/**
 * Test script to verify UTM fields in visual editor
 * 
 * Add this to the browser console when in the visual editor
 */

(function() {
    console.log('=== Testing Form Data with UTM Fields ===');
    
    // Test 1: Check if form block exists
    console.log('Test 1: Checking for Form Block instances...');
    if (window.DCF_VisualEditor && window.DCF_VisualEditor.blockInstances) {
        var formBlocks = Object.values(window.DCF_VisualEditor.blockInstances).filter(function(block) {
            return block.type === 'form';
        });
        
        console.log('Form blocks found:', formBlocks.length);
        
        formBlocks.forEach(function(block, index) {
            console.log('Form Block ' + (index + 1) + ':');
            console.log('- Form ID:', block.settings.formId);
            
            if (block.formData) {
                console.log('- Form Data loaded: Yes');
                console.log('- Form Name:', block.formData.form_name);
                
                if (block.formData.form_config) {
                    var config = block.formData.form_config;
                    console.log('- Include UTM Parameters:', config.include_utm_parameters);
                    
                    if (config.fields) {
                        var utmFields = config.fields.filter(function(field) {
                            return field.id.indexOf('utm_') === 0;
                        });
                        
                        console.log('- Total Fields:', config.fields.length);
                        console.log('- UTM Fields:', utmFields.length);
                        
                        if (utmFields.length > 0) {
                            console.log('- UTM Field Details:');
                            utmFields.forEach(function(field) {
                                console.log('  • ' + field.id + ' (' + field.type + ')');
                            });
                        }
                    }
                } else {
                    console.log('- Form Config: Not found');
                }
            } else {
                console.log('- Form Data: Not loaded yet');
            }
        });
    } else {
        console.log('Visual Editor or block instances not found');
    }
    
    // Test 2: Direct AJAX call
    console.log('\nTest 2: Making direct AJAX call for form data...');
    
    // Get a form ID to test (you'll need to set this)
    var testFormId = 2; // Change this to your form ID
    
    jQuery.ajax({
        url: dcf_visual_editor.ajax_url,
        type: 'POST',
        data: {
            action: 'dcf_get_form_data',
            form_id: testFormId,
            nonce: dcf_visual_editor.nonce
        },
        success: function(response) {
            console.log('AJAX Response:', response);
            
            if (response.success && response.data) {
                var formData = response.data;
                console.log('Form Name:', formData.form_name);
                
                if (formData.form_config) {
                    console.log('Include UTM Parameters:', formData.form_config.include_utm_parameters);
                    
                    if (formData.form_config.fields) {
                        var utmFields = formData.form_config.fields.filter(function(field) {
                            return field.id.indexOf('utm_') === 0;
                        });
                        
                        console.log('UTM Fields in response:', utmFields.length);
                        if (utmFields.length > 0) {
                            console.log('UTM Fields:', utmFields);
                        }
                    }
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
    
    // Test 3: Check if FormBlock class adds UTM fields
    console.log('\nTest 3: Checking FormBlock render method...');
    if (window.DCF_BlockRegistry && window.DCF_BlockRegistry.blocks.form) {
        var FormBlockClass = window.DCF_BlockRegistry.blocks.form;
        var testBlock = new FormBlockClass({ formId: testFormId });
        
        // Check if loadFormData exists
        if (typeof testBlock.loadFormData === 'function') {
            console.log('FormBlock has loadFormData method');
            
            // Watch for form data loading
            var originalUpdate = testBlock.updatePreview;
            testBlock.updatePreview = function() {
                console.log('Form data loaded in test block:', testBlock.formData);
                if (testBlock.formData && testBlock.formData.form_config && testBlock.formData.form_config.fields) {
                    var utmFields = testBlock.formData.form_config.fields.filter(function(field) {
                        return field.id.indexOf('utm_') === 0;
                    });
                    console.log('UTM fields in loaded data:', utmFields.length);
                }
                originalUpdate.call(testBlock);
            };
            
            testBlock.loadFormData();
        }
    }
    
    console.log('=== End of UTM Field Tests ===');
})();

// Helper function to manually trigger form data reload
window.testReloadFormData = function(formId) {
    console.log('Manually reloading form data for form ID:', formId);
    
    jQuery.ajax({
        url: dcf_visual_editor.ajax_url,
        type: 'POST',
        data: {
            action: 'dcf_get_form_data',
            form_id: formId,
            nonce: dcf_visual_editor.nonce
        },
        success: function(response) {
            console.log('Manual reload response:', response);
            
            if (response.success && response.data && response.data.form_config) {
                var utmFields = response.data.form_config.fields.filter(function(field) {
                    return field.id.indexOf('utm_') === 0;
                });
                
                console.log('Form has', utmFields.length, 'UTM fields');
                
                // Update any existing form blocks
                if (window.DCF_VisualEditor && window.DCF_VisualEditor.blockInstances) {
                    Object.values(window.DCF_VisualEditor.blockInstances).forEach(function(block) {
                        if (block.type === 'form' && block.settings.formId == formId) {
                            block.formData = response.data;
                            block.updatePreview();
                            console.log('Updated form block with new data');
                        }
                    });
                }
            }
        }
    });
};