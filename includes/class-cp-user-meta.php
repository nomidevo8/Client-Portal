<?php
/**
 * User Meta Fields for Client Portal
 * File: includes/class-cp-user-meta.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_User_Meta {
    
    public function __construct() {
        add_action('show_user_profile', array($this, 'add_custom_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_custom_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_custom_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_custom_user_profile_fields'));
        add_action('init', array($this, 'register_client_role'));
    }
    
    /**
     * Register Client Role
     */
    public function register_client_role() {
        if (!get_role('client')) {
            add_role('client', __('Client', 'client-portal'), array(
                'read' => true,
            ));
        }
    }
    
    /**
     * Add Custom Fields to User Profile
     */
    public function add_custom_user_profile_fields($user) {
        ?>
        <h3><?php _e('Client Portal Information', 'client-portal'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="cp_full_name"><?php _e('Full Name', 'client-portal'); ?></label></th>
                <td>
                    <input type="text" name="cp_full_name" id="cp_full_name" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'cp_full_name', true)); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Client\'s full name for display', 'client-portal'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cp_phone"><?php _e('Phone Number', 'client-portal'); ?></label></th>
                <td>
                    <input type="text" name="cp_phone" id="cp_phone" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'cp_phone', true)); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Contact phone number', 'client-portal'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cp_company"><?php _e('Company', 'client-portal'); ?></label></th>
                <td>
                    <input type="text" name="cp_company" id="cp_company" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'cp_company', true)); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Company or organization name', 'client-portal'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cp_address"><?php _e('Address', 'client-portal'); ?></label></th>
                <td>
                    <textarea name="cp_address" id="cp_address" rows="5" 
                              class="large-text"><?php echo esc_textarea(get_user_meta($user->ID, 'cp_address', true)); ?></textarea>
                    <p class="description"><?php _e('Full mailing address', 'client-portal'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save Custom User Profile Fields
     */
    public function save_custom_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['cp_full_name'])) {
            update_user_meta($user_id, 'cp_full_name', sanitize_text_field($_POST['cp_full_name']));
        }
        
        if (isset($_POST['cp_phone'])) {
            update_user_meta($user_id, 'cp_phone', sanitize_text_field($_POST['cp_phone']));
        }
        
        if (isset($_POST['cp_company'])) {
            update_user_meta($user_id, 'cp_company', sanitize_text_field($_POST['cp_company']));
        }
        
        if (isset($_POST['cp_address'])) {
            update_user_meta($user_id, 'cp_address', sanitize_textarea_field($_POST['cp_address']));
        }

        // After saving, send only changed fields to Airtable (if integration exists)
        if (class_exists('CP_Airtable')) {
            $update_fields = array();
            if (isset($_POST['cp_full_name'])) {
                $update_fields['First Name'] = get_user_meta($user_id, 'first_name', true);
                $update_fields['Last Name'] = get_user_meta($user_id, 'last_name', true);
            }
            if (isset($_POST['cp_company'])) {
                $update_fields['Company'] = sanitize_text_field($_POST['cp_company']);
            }
            if (isset($_POST['cp_address'])) {
                $update_fields['Address'] = sanitize_textarea_field($_POST['cp_address']);
            }

            if (!empty($update_fields)) {
                $res = CP_Airtable::update_user_fields($user_id, $update_fields);
                // if (is_wp_error($res)) {
                //     error_log('[CP] Airtable profile update error for user ' . $user_id . ': ' . $res->get_error_message());
                // } else {
                //     error_log('[CP] Airtable profile update OK for user ' . $user_id);
                // }
            }
        }
    }
}

// Initialize the class
new CP_User_Meta();