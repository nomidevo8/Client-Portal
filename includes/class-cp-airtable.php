<?php
/**
 * Airtable helper for Client Portal
 * File: includes/class-cp-airtable.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Airtable {

    /**
     * Cached config values
     */
    protected static $api_key = null;
    protected static $base = null;
    protected static $table = null;
    protected static $base_url = null;

    /**
     * Ensure config values are loaded into the static properties.
     */
    protected static function ensure_config() {
        if (null !== self::$api_key && null !== self::$base && null !== self::$table && null !== self::$base_url) {
            return;
        }

        self::$api_key = defined('CP_AIRTABLE_API_KEY') ? CP_AIRTABLE_API_KEY : get_option('cp_airtable_api_key');
        self::$base = defined('CP_AIRTABLE_BASE') ? CP_AIRTABLE_BASE : get_option('cp_airtable_base');
        self::$table = defined('CP_AIRTABLE_TABLE') ? CP_AIRTABLE_TABLE : get_option('cp_airtable_table');
        self::$base_url = defined('CP_AIRTABLE_BASE_URL') ? CP_AIRTABLE_BASE_URL : get_option('cp_airtable_base_url');
    }

    /**
     * Send a WP user to Airtable.
     *
     * @param int $user_id
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_user_to_airtable($user_id, $first_name = 'Unknown', $cp_password = '') {
        //   error_log('In send_user_to_airtable');
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('no_user', 'User not found');
        }

        // Only send clients by default. Caller should check role if needed.

        // Build fields mapping. Allow filter to customize.
        $fields = apply_filters('cp_airtable_user_fields', array(
            'Username' => $user->user_login,
            'First Name' => $first_name,
            'Last Name' => $user->last_name,
            'E-mail Address' => $user->user_email,
            'Password' => $cp_password,
            'Confirm Password' => $cp_password,
            "Type of Entity" =>"Client",
            'Status' => 'Registered',
            'Notes' => 'Created via WP Client Portal',
        ), $user_id, $user);

        $body = json_encode(array('fields' => $fields));

        // Determine status based on approval or role
        $approved = get_user_meta($user_id, 'cp_approved', true);
        $roles = (array) $user->roles;
        if ($approved === '1' || in_array('client', $roles, true)) {
            $fields['Status'] = 'Registered';
        } else {
            $fields['Status'] = 'Pending';
        }

        $body = json_encode(array('fields' => $fields));

        // Load config once
        self::ensure_config();
        $base = self::$base;
        $table = self::$table;
        $base_url = self::$base_url;
        $api_key = self::$api_key;

        if (empty($api_key) || empty($base) || empty($table)) {
            return new WP_Error('missing_config', 'Airtable configuration is missing');
        }

        // Build request URL. If a base URL is provided, prefer it (handle trailing slashes and whether it already contains the base id).
        if (!empty($base_url)) {
            $base_url = rtrim($base_url, '/');
            // If the base URL does not already include the base id, append /v0/{base}/{table}
            if (false === strpos($base_url, rawurlencode($base))) {
                $url = $base_url . '/' . rawurlencode($base) . '/' . rawurlencode($table);
            } else {
                // Base URL already points to the base; just append the table
                $url = $base_url . '/' . rawurlencode($table);
            }
        } else {
            $url = sprintf('https://api.airtable.com/v0/%s/%s', rawurlencode($base), rawurlencode($table));
        }

  
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => $body,
            'timeout' => 15,
        );
      
        // No saved record id: create a new record
        // error_log('[CP] Creating new Airtable record at ' . $url);
        $response = wp_remote_post($url, $args);

        // error_log('[CP] Airtable raw response: ' . print_r($response, true));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // error_log('[CP] Airtable response code: ' . $code . ' body: ' . $response_body);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('airtable_error', sprintf('Airtable API returned %s: %s', $code, $response_body));
        }

        // Parse response and store returned record ID for future updates
        $data = json_decode($response_body, true);
        if (is_array($data) && !empty($data['id'])) {
            update_user_meta($user_id, 'cp_airtable_record_id', $data['id']);
            // error_log('[CP] Saved cp_airtable_record_id=' . $data['id'] . ' for user ' . $user_id);
        }

        return true;
    }

    /**
     * Update specific fields for an existing Airtable record for a user.
     * If there's no saved record id this will create a full record instead.
     *
     * @param int $user_id
     * @param array $fields   Associative array of Airtable field name => value
     * @return bool|WP_Error
     */
    public static function update_user_fields($user_id, $fields = array()) {
        if (empty($fields) || !is_array($fields)) {
            return new WP_Error('no_fields', 'No fields provided to update');
        }

        // If no Airtable record id exists yet, call send_user_to_airtable to create a full record
        $saved_record_id = get_user_meta($user_id, 'cp_airtable_record_id', true);
        if (empty($saved_record_id)) {
            // Fallback to full send (will save record id)
            return self::send_user_to_airtable($user_id);
        }

        // Build minimal payload
        $payload = array('fields' => $fields);

        // Load config once
        self::ensure_config();
        $base = self::$base;
        $table = self::$table;
        $base_url = self::$base_url;
        $api_key = self::$api_key;

        if (empty($api_key) || empty($base) || empty($table)) {
            return new WP_Error('missing_config', 'Airtable configuration is missing');
        }

        // Build base URL as in send_user_to_airtable
        if (!empty($base_url)) {
            $base_url = rtrim($base_url, '/');
            if (strpos($base_url, rawurlencode($base)) !== false) {
                $url = $base_url . '/' . rawurlencode($table);
            } elseif (strpos($base_url, '/v0') !== false) {
                $url = $base_url . '/' . rawurlencode($base) . '/' . rawurlencode($table);
            } else {
                $url = $base_url . '/v0/' . rawurlencode($base) . '/' . rawurlencode($table);
            }
        } else {
            $url = sprintf('https://api.airtable.com/v0/%s/%s', rawurlencode($base), rawurlencode($table));
        }

        $request_url = rtrim($url, '/') . '/' . rawurlencode($saved_record_id);

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode($payload),
            'method' => 'PATCH',
            'timeout' => 15,
        );

        // error_log('[CP] update_user_fields request_url: ' . $request_url);
        // error_log('[CP] update_user_fields payload: ' . json_encode($payload));

        $response = wp_remote_request($request_url, $args);
        // if (is_wp_error($response)) {
        //     error_log('[CP] update_user_fields wp_remote_request error: ' . $response->get_error_message());
        //     return $response;
        // }

        $code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        // error_log('[CP] update_user_fields response: ' . $code . ' ' . $response_body);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('airtable_error', sprintf('Airtable API returned %s: %s', $code, $response_body));
        }

        return true;
    }
}

// Backwards compatibility: allow procedural helper
function cp_airtable_send_user($user_id) {
    return CP_Airtable::send_user_to_airtable($user_id);
}
