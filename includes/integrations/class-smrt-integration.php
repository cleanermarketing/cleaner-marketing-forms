<?php
/**
 * SMRT Integration
 *
 * @package CleanerMarketingForms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SMRT Integration class
 */
class DCF_SMRT_Integration {
    
    /**
     * Integration name
     */
    private $name = 'SMRT';
    
    /**
     * GraphQL URL
     */
    private $graphql_url;
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $graphql_url = DCF_Plugin_Core::get_setting('smrt_graphql_url');
        $api_key = DCF_Plugin_Core::get_setting('smrt_api_key');
        
        // Handle both encrypted and non-encrypted values
        $this->graphql_url = $graphql_url;
        $this->api_key = $api_key;
        
        // Try to decrypt if they look encrypted (base64 with special chars)
        if ($api_key && preg_match('/^[a-zA-Z0-9\/\+=]+$/', $api_key) && strlen($api_key) > 50) {
            $decrypted = DCF_Plugin_Core::decrypt($api_key);
            if ($decrypted) {
                $this->api_key = $decrypted;
            }
        }
    }
    
    /**
     * Get integration name
     *
     * @return string Integration name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Check if integration is configured
     *
     * @return bool True if configured
     */
    public function is_configured() {
        return !empty($this->graphql_url) && !empty($this->api_key);
    }
    
    /**
     * Test connection to SMRT API
     *
     * @return array|WP_Error Test result or error
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('SMRT integration is not configured', 'dry-cleaning-forms'));
        }
        
        // Try a simple introspection query that requires authentication
        $query = '
            query TestConnection {
                __schema {
                    queryType {
                        name
                    }
                }
            }
        ';
        
        $start_time = microtime(true);
        $response = $this->make_graphql_request($query);
        $response_time = round((microtime(true) - $start_time) * 1000, 2); // in milliseconds
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
                'details' => array(
                    'endpoint' => $this->graphql_url,
                    'response_time' => $response_time . 'ms',
                    'error_code' => $response->get_error_code()
                )
            );
        }
        
        // Check if we got valid data back
        $has_valid_response = isset($response['data']) && isset($response['data']['__schema']);
        
        if (!$has_valid_response) {
            return array(
                'success' => false,
                'message' => __('Invalid response from SMRT API - authentication may have failed', 'dry-cleaning-forms'),
                'details' => array(
                    'endpoint' => $this->graphql_url,
                    'response_time' => $response_time . 'ms',
                    'response' => $response
                )
            );
        }
        
        // Also check CustomerFieldValueInput type structure
        $this->inspect_custom_field_type();
        
        return array(
            'success' => true,
            'message' => __('Successfully connected to SMRT API', 'dry-cleaning-forms'),
            'details' => array(
                'endpoint' => $this->graphql_url,
                'response_time' => $response_time . 'ms',
                'api_version' => 'GraphQL',
                'query_type' => isset($response['data']['__schema']['queryType']['name']) ? 
                               $response['data']['__schema']['queryType']['name'] : 'Unknown'
            )
        );
    }
    
    /**
     * Inspect the CustomerFieldValueInput type structure
     *
     * @return void
     */
    private function inspect_custom_field_type() {
        $query = '
            query InspectCustomFieldType {
                __type(name: "CustomerFieldValueInput") {
                    name
                    kind
                    inputFields {
                        name
                        type {
                            name
                            kind
                        }
                    }
                }
            }
        ';
        
        $response = $this->make_graphql_request($query);
        
        if (!is_wp_error($response) && isset($response['data']['__type'])) {
            $type_info = $response['data']['__type'];
            error_log('DCF SMRT: CustomerFieldValueInput structure: ' . json_encode($type_info));
            
            // Log integration for debugging
            DCF_Plugin_Core::log_integration('smrt', 'inspect_custom_field_type', 
                array('query' => 'CustomerFieldValueInput introspection'), 
                $type_info, 'info');
        }
    }
    
    /**
     * Check if customer exists
     *
     * @param string $email Customer email
     * @param string $phone Customer phone
     * @return array|WP_Error Customer data or error
     */
    public function customer_exists($email, $phone = '') {
        // Validate inputs
        if (empty($email) && empty($phone)) {
            return new WP_Error('missing_params', 'Either email or phone number is required for customer lookup');
        }
        
        // Use phone number for lookup if provided, otherwise use email
        $search_term = !empty($phone) ? $phone : $email;
        $search_by = !empty($phone) ? 'phone' : 'email';
        
        // Format phone number (remove country code prefix if exists)
        if ($search_by === 'phone') {
            $search_term = preg_replace('/^\+1/', '', $search_term);
        }
        
        $query = '
            query GetCustomer($term: String!) {
                business {
                    getCustomer(by: ' . $search_by . ', term: $term, includeInactive: true) {
                        id
                        localId
                        name
                        email
                        cellPhone
                        firstName
                        lastName
                        addresses {
                            id
                            localId
                            name
                            streetAddress
                            streetAddress2
                            city
                            state
                            zip
                        }
                        defaultAddress {
                            id
                            localId
                            name
                            streetAddress
                            streetAddress2
                            city
                            state
                            zip
                        }
                        customerRelationship
                    }
                }
            }
        ';
        
        $variables = array(
            'term' => $search_term
        );
        
        $response = $this->make_graphql_request($query, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'customer_exists', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        // Log the full response for debugging
        DCF_Plugin_Core::log_integration('smrt', 'customer_exists_debug', array(
            'search_by' => $search_by,
            'search_term' => $search_term,
            'query' => $query,
            'variables' => $variables
        ), $response, 'info');
        
        // Check for GraphQL errors
        if (isset($response['errors']) && !empty($response['errors'])) {
            $error_message = '';
            foreach ($response['errors'] as $error) {
                $error_message .= $error['message'] . ' ';
            }
            DCF_Plugin_Core::log_integration('smrt', 'customer_exists', $variables, array('graphql_errors' => $response['errors']), 'error');
            return new WP_Error('graphql_error', trim($error_message));
        }
        
        // Check if customer was found
        if (isset($response['data']['business']['getCustomer']) && $response['data']['business']['getCustomer']) {
            $customer = $response['data']['business']['getCustomer'];
            
            // Map SMRT response to expected format
            $mapped_customer = array(
                'id' => $customer['id'],
                'email' => $customer['email'],
                'phone' => $customer['cellPhone'],
                'firstName' => $customer['firstName'],
                'lastName' => $customer['lastName'],
                'addresses' => array()
            );
            
            // Map addresses to expected format
            if (isset($customer['addresses']) && is_array($customer['addresses'])) {
                foreach ($customer['addresses'] as $address) {
                    $mapped_customer['addresses'][] = array(
                        'id' => $address['id'],
                        'street' => $address['streetAddress'],
                        'city' => $address['city'],
                        'state' => $address['state'],
                        'zipCode' => $address['zip']
                    );
                }
            }
            
            DCF_Plugin_Core::log_integration('smrt', 'customer_exists', $variables, $mapped_customer, 'success');
            return array(
                'exists' => true,
                'customer' => $mapped_customer
            );
        }
        
        DCF_Plugin_Core::log_integration('smrt', 'customer_exists', $variables, array('exists' => false), 'success');
        return array('exists' => false);
    }
    
    /**
     * Create customer
     *
     * @param array $customer_data Customer data
     * @return array|WP_Error Customer data or error
     */
    public function create_customer($customer_data) {
        // Build custom fields array if UTM parameters are present
        $custom_fields = array();
        if (isset($customer_data['utm_parameters']) && !empty(array_filter($customer_data['utm_parameters']))) {
            $custom_fields = $this->build_utm_custom_fields($customer_data['utm_parameters']);
            
            // Debug log the custom fields structure
            if (!empty($custom_fields)) {
                DCF_Plugin_Core::log_integration('smrt', 'create_customer_custom_fields_debug', 
                    array('utm_params' => $customer_data['utm_parameters'], 'custom_fields' => $custom_fields), 
                    'Debug: Custom fields structure', 'info');
            }
        }
        
        // Build the input object
        $input = array(
            'lastName' => $customer_data['last_name'],
            'firstName' => $customer_data['first_name'],
            'phone' => $customer_data['phone'],
            'email' => isset($customer_data['email']) ? $customer_data['email'] : null
        );
        
        // Add custom fields if we have any
        if (!empty($custom_fields)) {
            $input['customFields'] = $custom_fields;
            
            // Log the full input being sent
            DCF_Plugin_Core::log_integration('smrt', 'create_customer_input_debug', 
                array('input' => $input), 
                'Debug: Full customer input with custom fields', 'info');
        }
        
        $mutation = '
            mutation CreateCustomer($input: CustomerInput!, $agentId: String!) {
                createCustomer(input: $input, agentId: $agentId) {
                    id
                    localId
                    name
                    email
                    cellPhone
                    firstName
                    lastName
                }
            }
        ';
        
        // Get the default agent ID from settings or use a default
        $agent_id = DCF_Plugin_Core::get_setting('smrt_agent_id');
        if (empty($agent_id)) {
            $agent_id = DCF_Plugin_Core::get_setting('smrt_store_id');
        }
        
        if (empty($agent_id)) {
            return new WP_Error('missing_agent_id', __('SMRT agent ID is required for customer creation', 'dry-cleaning-forms'));
        }
        
        $variables = array(
            'input' => $input,
            'agentId' => $agent_id
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'create_customer', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['createCustomer'])) {
            $customer = $response['data']['createCustomer'];
            
            // Map response to expected format
            $mapped_customer = array(
                'id' => $customer['id'],
                'email' => $customer['email'],
                'phone' => $customer['cellPhone'],
                'firstName' => $customer['firstName'],
                'lastName' => $customer['lastName'],
                'addresses' => array()
            );
            
            DCF_Plugin_Core::log_integration('smrt', 'create_customer', $variables, $mapped_customer, 'success');
            
            // Apply promo code if provided
            if (!empty($customer_data['promo_code'])) {
                // Pass the customer data we already have to avoid needing to re-fetch
                $promo_customer_data = array(
                    'firstName' => $customer['firstName'],
                    'lastName' => $customer['lastName'],
                    'phone' => $customer['cellPhone'],
                    'email' => $customer['email']
                );
                $promo_result = $this->apply_promo_code($customer['id'], $customer_data['promo_code'], $promo_customer_data);
                if (is_wp_error($promo_result)) {
                    // Log the error but don't fail the customer creation
                    DCF_Plugin_Core::log_integration('smrt', 'apply_promo_code', 
                        array('customer_id' => $customer['id'], 'promo_code' => $customer_data['promo_code']), 
                        array('error' => $promo_result->get_error_message()), 'error');
                } else {
                    // Log successful promo code application
                    DCF_Plugin_Core::log_integration('smrt', 'apply_promo_code', 
                        array('customer_id' => $customer['id'], 'promo_code' => $customer_data['promo_code']), 
                        $promo_result, 'success');
                }
            }
            
            return $mapped_customer;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error creating customer', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'create_customer', $variables, $response, 'error');
        return new WP_Error('create_customer_failed', $error_message);
    }
    
    /**
     * Get customer by ID
     *
     * @param string $customer_id Customer ID
     * @return array|WP_Error Customer data or error
     */
    public function get_customer_by_id($customer_id) {
        $query = '
            query GetCustomer($customerId: ID!) {
                business {
                    getCustomer(customerId: $customerId) {
                        id
                        localId
                        name
                        email
                        cellPhone
                        firstName
                        lastName
                    }
                }
            }
        ';
        
        $variables = array(
            'customerId' => $customer_id
        );
        
        $response = $this->make_graphql_request($query, $variables);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if (isset($response['data']['business']['getCustomer'])) {
            return $response['data']['business']['getCustomer'];
        }
        
        return new WP_Error('customer_not_found', __('Customer not found', 'dry-cleaning-forms'));
    }
    
    /**
     * Apply promo code to customer
     *
     * @param string $customer_id Customer ID
     * @param string $promo_code Promo code to apply
     * @param array $customer_data Optional customer data to include required fields
     * @return array|WP_Error Result or error
     */
    public function apply_promo_code($customer_id, $promo_code, $customer_data = array()) {
        if (empty($promo_code)) {
            return array('success' => false, 'message' => 'No promo code provided');
        }
        
        // If we don't have customer data, try to get it from the ID
        // But for efficiency, we'll build it from the data we have during customer creation
        if (empty($customer_data['firstName']) || empty($customer_data['lastName'])) {
            $customer_lookup = $this->get_customer_by_id($customer_id);
            if (is_wp_error($customer_lookup)) {
                // If we can't get customer data, log the error but try with minimal data
                DCF_Plugin_Core::log_integration('smrt', 'get_customer_for_promo', 
                    array('customer_id' => $customer_id), 
                    array('error' => $customer_lookup->get_error_message()), 'error');
                return $customer_lookup;
            }
            $customer_data = array_merge($customer_data, $customer_lookup);
        }
        
        $mutation = '
            mutation ApplyPromoCode($customerId: ID!, $input: CustomerInput!, $promotionToApply: String!) {
                putCustomer(customerId: $customerId, input: $input, promotionToApply: $promotionToApply) {
                    id
                    firstName
                    lastName
                }
            }
        ';
        
        // Build customer input WITHOUT the id field (CustomerInput doesn't accept id)
        $customer_input = array(
            'firstName' => isset($customer_data['firstName']) ? $customer_data['firstName'] : (isset($customer_data['first_name']) ? $customer_data['first_name'] : ''),
            'lastName' => isset($customer_data['lastName']) ? $customer_data['lastName'] : (isset($customer_data['last_name']) ? $customer_data['last_name'] : '')
        );
        
        // Add optional fields if available
        if (isset($customer_data['phone']) || isset($customer_data['cellPhone'])) {
            $customer_input['phone'] = isset($customer_data['phone']) ? $customer_data['phone'] : $customer_data['cellPhone'];
        }
        if (isset($customer_data['email'])) {
            $customer_input['email'] = $customer_data['email'];
        }
        
        // Debug log the customer input being built
        DCF_Plugin_Core::log_integration('smrt', 'promo_customer_input_debug', 
            array('customer_data' => $customer_data, 'customer_input' => $customer_input), 
            'Building customer input for promo code', 'info');
        
        $variables = array(
            'customerId' => $customer_id,
            'input' => $customer_input,
            'promotionToApply' => trim($promo_code)
        );
        
        // Log the actual variables being sent to GraphQL
        DCF_Plugin_Core::log_integration('smrt', 'apply_promo_code_request', 
            array('variables' => $variables, 'mutation' => $mutation), 
            'Sending promo code request with full customer data', 'info');
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'apply_promo_code_error', 
                array('variables' => $variables), 
                array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['putCustomer'])) {
            return array('success' => true, 'message' => 'Promo code applied successfully');
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error applying promo code', 'dry-cleaning-forms');
        return new WP_Error('apply_promo_failed', $error_message);
    }
    
    /**
     * Update customer
     *
     * @param string $customer_id Customer ID (can be empty for email/phone updates)
     * @param array $customer_data Customer data
     * @return array|WP_Error Updated customer data or error
     */
    public function update_customer($customer_id, $customer_data) {
        // Check if this is a full address update (with street, city, state, zip)
        if (isset($customer_data['address']) && is_array($customer_data['address']) && 
            isset($customer_data['address']['street']) && isset($customer_data['address']['city'])) {
            return $this->update_customer_address($customer_id, $customer_data);
        } elseif (isset($customer_data['street']) && isset($customer_data['city']) && 
                  isset($customer_data['state']) && isset($customer_data['zip'])) {
            return $this->update_customer_address($customer_id, $customer_data);
        }
        
        // For email/phone updates, we need to check if customer exists first
        if (isset($customer_data['phone']) && isset($customer_data['email'])) {
            // First, check if customer exists by phone
            $phone = preg_replace('/[^0-9]/', '', $customer_data['phone']);
            $check_result = $this->customer_exists('', $phone);
            
            if (is_wp_error($check_result)) {
                return $check_result;
            }
            
            if (!$check_result['exists']) {
                // Customer doesn't exist, nothing to update
                return array(
                    'updated' => false,
                    'reason' => 'Customer not found',
                    'message' => 'No customer found with phone number ' . $phone
                );
            }
            
            // Customer exists, get their data
            $existing_customer = $check_result['customer'];
            $customer_id = $existing_customer['id'];
            
            // Check if email already exists and matches
            if (!empty($existing_customer['email']) && 
                strcasecmp($existing_customer['email'], $customer_data['email']) === 0) {
                // Email already exists and matches
                return array(
                    'updated' => false,
                    'reason' => 'Email already exists',
                    'message' => 'Customer already has this email address',
                    'customer' => $existing_customer
                );
            }
            
            // Email needs to be updated
            $mutation = '
                mutation PutCustomer($customerId: ID!, $input: CustomerInput!) {
                    putCustomer(customerId: $customerId, input: $input) {
                        id
                        firstName
                        lastName
                        email
                        cellPhone
                    }
                }
            ';
            
            // Build customer input with required fields
            $customer_input = array(
                'firstName' => $existing_customer['firstName'],
                'lastName' => $existing_customer['lastName'],
                'phone' => $existing_customer['cellPhone'],
                'email' => $customer_data['email']
            );
            
            $variables = array(
                'customerId' => $customer_id,
                'input' => $customer_input
            );
            
            $response = $this->make_graphql_request($mutation, $variables);
            
            if (is_wp_error($response)) {
                DCF_Plugin_Core::log_integration('smrt', 'update_customer', $variables, array('error' => $response->get_error_message()), 'error');
                return $response;
            }
            
            if (isset($response['data']['putCustomer'])) {
                $updated_customer = $response['data']['putCustomer'];
                
                // Map response to expected format
                $result = array(
                    'updated' => true,
                    'reason' => 'Email updated',
                    'message' => 'Customer email successfully updated',
                    'customer' => array(
                        'id' => $updated_customer['id'],
                        'firstName' => $updated_customer['firstName'],
                        'lastName' => $updated_customer['lastName'],
                        'email' => isset($updated_customer['email']) ? $updated_customer['email'] : '',
                        'phone' => isset($updated_customer['cellPhone']) ? $updated_customer['cellPhone'] : ''
                    )
                );
                
                DCF_Plugin_Core::log_integration('smrt', 'update_customer', $variables, $result, 'success');
                return $result;
            }
            
            $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error updating customer', 'dry-cleaning-forms');
            DCF_Plugin_Core::log_integration('smrt', 'update_customer', $variables, $response, 'error');
            return new WP_Error('update_customer_failed', $error_message);
        } else {
            // For other types of updates (not email/phone), return error for now
            return new WP_Error('unsupported_update', 'This update type is not supported in this context');
        }
    }
    
    /**
     * Add customer address
     *
     * @param string $customer_id Customer ID
     * @param array $address_data Address data
     * @return array|WP_Error Address data or error
     */
    public function add_customer_address($customer_id, $address_data) {
        $mutation = '
            mutation PutCustomerAddresses($customerId: ID!, $defaultName: String!, $addresses: [CustomerAddressInput!]!, $updateDeliveries: Boolean!) {
                putCustomerAddresses(
                    customerId: $customerId
                    defaultName: $defaultName
                    addresses: $addresses
                    updateDeliveries: $updateDeliveries
                ) {
                    id
                    addresses {
                        id
                        localId
                        name
                        streetAddress
                        streetAddress2
                        city
                        state
                        zip
                    }
                }
            }
        ';
        
        // Prepare address input in the correct format
        $address_input = array(
            'name' => isset($address_data['name']) ? $address_data['name'] : 'Home',
            'streetAddress' => $address_data['street'],
            'city' => $address_data['city'],
            'state' => $address_data['state'],
            'zip' => $address_data['zipCode']
        );
        
        // Add optional fields
        if (isset($address_data['streetAddress2'])) {
            $address_input['streetAddress2'] = $address_data['streetAddress2'];
        }
        
        $variables = array(
            'customerId' => $customer_id,
            'defaultName' => $address_input['name'],
            'addresses' => array($address_input),
            'updateDeliveries' => true
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'add_customer_address', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['putCustomerAddresses']) && 
            isset($response['data']['putCustomerAddresses']['addresses']) &&
            count($response['data']['putCustomerAddresses']['addresses']) > 0) {
            
            $address = $response['data']['putCustomerAddresses']['addresses'][0];
            
            // Map response to expected format
            $mapped_address = array(
                'id' => $address['id'],
                'street' => $address['streetAddress'],
                'city' => $address['city'],
                'state' => $address['state'],
                'zipCode' => $address['zip']
            );
            
            DCF_Plugin_Core::log_integration('smrt', 'add_customer_address', $variables, $mapped_address, 'success');
            return $mapped_address;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error adding customer address', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'add_customer_address', $variables, $response, 'error');
        return new WP_Error('add_customer_address_failed', $error_message);
    }
    
    /**
     * Update customer address
     *
     * @param string $customer_id Customer ID
     * @param array $customer_data Customer data including address fields
     * @return array|WP_Error Updated customer data or error
     */
    private function update_customer_address($customer_id, $customer_data) {
        $mutation = '
            mutation PutCustomerAddresses($customerId: ID!, $defaultName: String!, $addresses: [CustomerAddressInput!]!, $updateDeliveries: Boolean!) {
                putCustomerAddresses(
                    customerId: $customerId
                    defaultName: $defaultName
                    addresses: $addresses
                    updateDeliveries: $updateDeliveries
                ) {
                    id
                    addresses {
                        id
                        localId
                        name
                        streetAddress
                        streetAddress2
                        city
                        state
                        zip
                        latitude
                        longitude
                        skipVerification
                        manualLocation
                    }
                }
            }
        ';
        
        // Build address array
        $address = array(
            'name' => 'Home',
            'streetAddress' => isset($customer_data['street']) ? $customer_data['street'] : (isset($customer_data['address']) ? $customer_data['address'] : ''),
            'city' => isset($customer_data['city']) ? $customer_data['city'] : '',
            'state' => isset($customer_data['state']) ? $customer_data['state'] : '',
            'zip' => isset($customer_data['zip']) ? $customer_data['zip'] : ''
        );
        
        // Add apartment/suite if provided
        if (!empty($customer_data['street2'])) {
            $address['streetAddress2'] = $customer_data['street2'];
        } elseif (!empty($customer_data['apartment'])) {
            $address['streetAddress2'] = $customer_data['apartment'];
        }
        
        $variables = array(
            'customerId' => $customer_id,
            'defaultName' => 'Home',
            'addresses' => array($address),
            'updateDeliveries' => true
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'update_customer_address', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['putCustomerAddresses'])) {
            $result = $response['data']['putCustomerAddresses'];
            
            // Map response to expected format
            $mapped_result = array(
                'id' => $result['id'] ?: $customer_id,
                'addresses' => array(),
                'address_id' => null // Store the first address ID for scheduling
            );
            
            if (isset($result['addresses']) && is_array($result['addresses'])) {
                foreach ($result['addresses'] as $addr) {
                    // Store the first address ID
                    if (!$mapped_result['address_id']) {
                        $mapped_result['address_id'] = $addr['id'];
                    }
                    
                    $mapped_result['addresses'][] = array(
                        'id' => $addr['id'],
                        'name' => $addr['name'],
                        'street' => $addr['streetAddress'],
                        'street2' => isset($addr['streetAddress2']) ? $addr['streetAddress2'] : '',
                        'city' => $addr['city'],
                        'state' => $addr['state'],
                        'zip' => $addr['zip']
                    );
                }
            }
            
            DCF_Plugin_Core::log_integration('smrt', 'update_customer_address', $variables, $mapped_result, 'success');
            return $mapped_result;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error updating customer address', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'update_customer_address', $variables, $response, 'error');
        return new WP_Error('update_customer_address_failed', $error_message);
    }
    
    /**
     * Get available pickup dates
     *
     * @param string $customer_id Customer ID
     * @param array $address_data Address data containing address_id
     * @return array|WP_Error Available dates or error
     */
    public function get_pickup_dates($customer_id, $address_data) {
        // Extract address ID and phone from address data
        $address_id = null;
        $phone = null;
        
        if (is_array($address_data)) {
            if (isset($address_data['address_id'])) {
                $address_id = $address_data['address_id'];
            }
            if (isset($address_data['phone'])) {
                $phone = $address_data['phone'];
            }
        }
        
        if (!$address_id || !$phone) {
            return new WP_Error('missing_data', __('Address ID and phone number are required for scheduling', 'dry-cleaning-forms'));
        }
        
        // Get current timestamp in ISO 8601 format
        $current_timestamp = gmdate('Y-m-d\TH:i:s\Z');
        
        $query = '
            query GetDatesForScheduling($phone: String!) {
                business {
                    getCustomer(by: phone, term: $phone, includeInactive: true) {
                        id
                        localId
                        name
                        datesForSchedulingAppointment(
                            addressId: "' . $address_id . '"
                            forDelivery: true
                            fromTimestamp: "' . $current_timestamp . '"
                        ) {
                            id
                            date
                            timeSlots {
                                id
                            }
                        }
                    }
                }
            }
        ';
        
        $variables = array(
            'phone' => $phone
        );
        
        $response = $this->make_graphql_request($query, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'get_pickup_dates', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['business']['getCustomer']['datesForSchedulingAppointment'])) {
            $dates = $response['data']['business']['getCustomer']['datesForSchedulingAppointment'];
            
            // Map response to expected format
            $mapped_dates = array();
            foreach ($dates as $date) {
                $time_slots = array();
                if (isset($date['timeSlots']) && is_array($date['timeSlots'])) {
                    foreach ($date['timeSlots'] as $slot) {
                        $time_slots[] = array(
                            'id' => $slot['id'],
                            'available' => true // Assume available if returned
                        );
                    }
                }
                
                $mapped_dates[] = array(
                    'id' => $date['id'],
                    'date' => $date['date'],
                    'timeSlots' => $time_slots
                );
            }
            
            DCF_Plugin_Core::log_integration('smrt', 'get_pickup_dates', array('phone' => $phone, 'address_id' => $address_id), $mapped_dates, 'success');
            return $mapped_dates;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error getting pickup dates', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'get_pickup_dates', $variables, $response, 'error');
        return new WP_Error('get_pickup_dates_failed', $error_message);
    }
    
    /**
     * Schedule pickup appointment
     *
     * @param string $customer_id Customer ID
     * @param array $appointment_data Appointment data including date_id, time_slot_id, address_id
     * @return array|WP_Error Appointment data or error
     */
    public function schedule_pickup($customer_id, $appointment_data) {
        // Extract date and time slot info
        if (!isset($appointment_data['date_id']) || !isset($appointment_data['time_slot_id'])) {
            return new WP_Error('missing_data', __('Date ID and time slot ID are required for scheduling', 'dry-cleaning-forms'));
        }
        
        // Parse the date from date_id (format: YYYY-MM-DD)
        $pickup_date = $appointment_data['date_id'];
        $date_parts = explode('-', $pickup_date);
        
        if (count($date_parts) !== 3) {
            return new WP_Error('invalid_date', __('Invalid date format', 'dry-cleaning-forms'));
        }
        
        $selected_year = (int) $date_parts[0];
        $selected_month = (int) $date_parts[1];
        $selected_date = (int) $date_parts[2];
        
        // Get route ID from settings
        $route_id = DCF_Plugin_Core::get_setting('smrt_delivery_route_id');
        if (empty($route_id)) {
            return new WP_Error('missing_route_id', __('Delivery Route ID is not configured. Please configure it in the SMRT settings.', 'dry-cleaning-forms'));
        }
        
        $mutation = '
            mutation PutAppointment($input: AppointmentInput!, $isPickup: Boolean!) {
                putAppointment(
                    input: $input
                    isPickup: $isPickup
                ) {
                    id
                }
            }
        ';
        
        $variables = array(
            'input' => array(
                'addressId' => $appointment_data['address_id'],
                'customerId' => $customer_id,
                'selectedDate' => $selected_date,
                'selectedMonth' => $selected_month,
                'selectedYear' => $selected_year,
                'timeSlotId' => $appointment_data['time_slot_id'],
                'routeId' => $route_id
            ),
            'isPickup' => true
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'schedule_pickup', $variables, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['putAppointment'])) {
            $appointment = $response['data']['putAppointment'];
            
            // Map response to expected format
            $mapped_appointment = array(
                'id' => $appointment['id'],
                'customerId' => $customer_id,
                'date' => $pickup_date,
                'status' => 'scheduled',
                'confirmationNumber' => $appointment['id'] // Use appointment ID as confirmation
            );
            
            DCF_Plugin_Core::log_integration('smrt', 'schedule_pickup', $variables, $mapped_appointment, 'success');
            return $mapped_appointment;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error scheduling pickup', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'schedule_pickup', $variables, $response, 'error');
        return new WP_Error('schedule_pickup_failed', $error_message);
    }
    
    /**
     * Put credit card
     *
     * @param string $customer_id Customer ID
     * @param array $card_data Credit card data
     * @return array|WP_Error Result or error
     */
    public function put_credit_card($customer_id, $card_data) {
        $mutation = '
            mutation PutCreditCard(
                $customerId: ID!,
                $id: String!,
                $type: CreditCardTypeEnum,
                $last4: Int,
                $expiryMonth: Int,
                $expiryYear: Int,
                $addedBy: CreditCardAddedByEnum
            ) {
                putCreditCard(
                    customerId: $customerId,
                    id: $id,
                    type: $type,
                    last4: $last4,
                    expiryMonth: $expiryMonth,
                    expiryYear: $expiryYear,
                    addedBy: $addedBy
                )
            }
        ';
        
        // Extract last 4 digits from card number
        $card_number = preg_replace('/[^0-9]/', '', $card_data['card_number']);
        $last4 = (int) substr($card_number, -4);
        
        // Determine card type based on first digit
        $card_type = $this->detect_card_type($card_number);
        
        // Generate a unique ID for this card
        // SMRT expects a simpler format - just use a unique identifier
        $card_id = uniqid('cc_', true);
        
        $variables = array(
            'customerId' => $customer_id,
            'id' => $card_id,
            'type' => $card_type,
            'last4' => $last4,
            'expiryMonth' => (int) $card_data['expiry_month'],
            'expiryYear' => (int) $card_data['expiry_year'],
            'addedBy' => 'customer'
        );
        
        // Log the request without sensitive data
        $log_data = array(
            'customerId' => $customer_id,
            'type' => $card_type,
            'last4' => $last4,
            'expiryMonth' => $variables['expiryMonth'],
            'expiryYear' => $variables['expiryYear']
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'put_credit_card', $log_data, array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        // Log the full response for debugging
        DCF_Plugin_Core::log_integration('smrt', 'put_credit_card_response', array(
            'variables' => $variables,
            'response' => $response
        ), $response, 'info');
        
        if (isset($response['data']['putCreditCard'])) {
            $result = array(
                'card_id' => $response['data']['putCreditCard'],
                'last4' => $last4,
                'type' => $card_type,
                'status' => 'added'
            );
            
            DCF_Plugin_Core::log_integration('smrt', 'put_credit_card', $log_data, $result, 'success');
            return $result;
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error adding credit card', 'dry-cleaning-forms');
        
        // If the error is about invalid card id, it might be because SMRT expects a different approach
        if (strpos($error_message, 'Invalid card id') !== false) {
            $error_message = __('Credit card processing is not available at this time. Your account has been created successfully and you can add payment information later.', 'dry-cleaning-forms');
        }
        
        DCF_Plugin_Core::log_integration('smrt', 'put_credit_card', $log_data, $response, 'error');
        return new WP_Error('put_credit_card_failed', $error_message);
    }
    
    /**
     * Detect card type from card number
     *
     * @param string $card_number Card number
     * @return string Card type
     */
    private function detect_card_type($card_number) {
        $first_digit = substr($card_number, 0, 1);
        $first_two = substr($card_number, 0, 2);
        $first_four = substr($card_number, 0, 4);
        
        if ($first_digit == '4') {
            return 'VISA';
        } elseif (in_array($first_two, array('51', '52', '53', '54', '55'))) {
            return 'MASTERCARD';
        } elseif (in_array($first_two, array('34', '37'))) {
            return 'AMEX';
        } elseif ($first_two == '60' || $first_four == '6011' || in_array($first_two, array('64', '65'))) {
            return 'DISCOVER';
        } else {
            return 'OTHER';
        }
    }
    
    /**
     * Build UTM custom fields for SMRT
     *
     * @param array $utm_params UTM parameters
     * @return array Custom fields array
     */
    private function build_utm_custom_fields($utm_params) {
        $custom_fields = array();
        
        // Map UTM parameters to SMRT custom field unique IDs
        // These must match exactly the Unique IDs configured in SMRT
        $utm_field_mapping = array(
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'utm_content' => 'utm_content',
            'utm_keyword' => 'utm_keyword',
            'utm_matchtype' => 'utm_matchtype',
            'campaign_id' => 'campaign_id',
            'ad_group_id' => 'ad_group_id',
            'ad_id' => 'ad_id'
        );
        
        foreach ($utm_field_mapping as $param_key => $field_id) {
            if (isset($utm_params[$param_key]) && !empty($utm_params[$param_key])) {
                // Use 'id' not 'fieldId' - this matches the Python example
                $custom_fields[] = array(
                    'id' => $field_id,
                    'value' => $utm_params[$param_key]
                );
            }
        }
        
        return $custom_fields;
    }
    
    /**
     * Process payment
     *
     * @param string $customer_id Customer ID
     * @param array $payment_data Payment data
     * @return array|WP_Error Payment result or error
     */
    public function process_payment($customer_id, $payment_data) {
        $mutation = '
            mutation ProcessPayment($input: PaymentInput!) {
                processPayment(input: $input) {
                    id
                    status
                    transactionId
                    amount
                    currency
                    paymentMethod {
                        type
                        last4
                    }
                }
            }
        ';
        
        $variables = array(
            'input' => array(
                'customerId' => $customer_id,
                'amount' => $payment_data['amount'],
                'currency' => isset($payment_data['currency']) ? $payment_data['currency'] : 'USD',
                'paymentMethod' => array(
                    'type' => 'card',
                    'cardNumber' => $payment_data['card_number'],
                    'expiryMonth' => $payment_data['expiry_month'],
                    'expiryYear' => $payment_data['expiry_year'],
                    'securityCode' => $payment_data['security_code'],
                    'billingZip' => $payment_data['billing_zip']
                )
            )
        );
        
        $response = $this->make_graphql_request($mutation, $variables);
        
        if (is_wp_error($response)) {
            DCF_Plugin_Core::log_integration('smrt', 'process_payment', array('customerId' => $customer_id, 'amount' => $payment_data['amount']), array('error' => $response->get_error_message()), 'error');
            return $response;
        }
        
        if (isset($response['data']['processPayment'])) {
            DCF_Plugin_Core::log_integration('smrt', 'process_payment', array('customerId' => $customer_id, 'amount' => $payment_data['amount']), $response['data']['processPayment'], 'success');
            return $response['data']['processPayment'];
        }
        
        $error_message = isset($response['errors']) ? $response['errors'][0]['message'] : __('Unknown error processing payment', 'dry-cleaning-forms');
        DCF_Plugin_Core::log_integration('smrt', 'process_payment', array('customerId' => $customer_id, 'amount' => $payment_data['amount']), $response, 'error');
        return new WP_Error('process_payment_failed', $error_message);
    }
    
    /**
     * Make GraphQL request to SMRT API
     *
     * @param string $query GraphQL query
     * @param array $variables Query variables
     * @return array|WP_Error Response data or error
     */
    private function make_graphql_request($query, $variables = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('SMRT integration is not configured', 'dry-cleaning-forms'));
        }
        
        $body = array(
            'query' => $query,
            'variables' => $variables
        );
        
        $args = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
                'User-Agent' => 'DryCleaningForms/' . CMF_PLUGIN_VERSION
            ),
            'timeout' => 60
        );
        
        $response = wp_remote_post($this->graphql_url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', sprintf(__('SMRT API returned status %d: %s', 'dry-cleaning-forms'), $response_code, $response_body));
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response from SMRT API', 'dry-cleaning-forms'));
        }
        
        // Handle GraphQL errors, but allow "Customer not found" to pass through
        if (isset($data['errors']) && !empty($data['errors'])) {
            $error_message = $data['errors'][0]['message'];
            
            // Special handling for customer not found - this is not an error for customer lookup
            if (strpos($error_message, 'Customer not found') !== false) {
                // Return data with null customer result
                return array(
                    'data' => array(
                        'business' => array(
                            'getCustomer' => null
                        )
                    )
                );
            }
            
            return new WP_Error('graphql_error', $error_message);
        }
        
        return $data;
    }
} 