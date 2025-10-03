<?php
/**
 * Plugin Name: Client Portal
 * Description: Complete client portal system with profile management, documents, tickets, and invoices
 * Version: 1.0.0
 * Author: SERVE TECH
 * Author URI: https://servetechglobal.com/
 * License: GPL v2 or later
 * Text Domain: client-portal
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CP_VERSION', '1.0.0');
define('CP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CP_PLUGIN_FILE', __FILE__);

define('CP_AIRTABLE_BASE_URL', 'https://api.airtable.com/v0');
define('CP_AIRTABLE_BASE', 'appqXZW9gTA6m99T8');
define('CP_AIRTABLE_API_KEY', 'patTsdQjUhAvTk7VI.2413d63954d5753f7f65d927b262e7c13351487db22a0f4001cbb7205611c4f7');
define('CP_AIRTABLE_TABLE', 'tblDfxIyyHmMliZtA');

/**
 * Main Client Portal Class
 */
class Client_Portal
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies()
    {
        require_once CP_PLUGIN_DIR . 'includes/class-cp-post-types.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-api.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-user-meta.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-documents.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-tickets.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-invoices.php';
        require_once CP_PLUGIN_DIR . 'includes/class-cp-shortcode.php';
        // Airtable integration (optional). Sends new client users to Airtable when configured.
        require_once CP_PLUGIN_DIR . 'includes/class-cp-airtable.php';
    }

    private function init_hooks()
    {
        register_activation_hook(CP_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(CP_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'client-portal',
            __('Pending Approvals', 'client-portal'),
            __('Pending Approvals', 'client-portal'),
            'manage_options',
            'cp-pending-approvals',
            array($this, 'render_pending_approvals_page')
        );
    }

    public function render_pending_approvals_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        // Handle Approve/Reject actions
        if (isset($_GET['cp_action'], $_GET['user_id']) && check_admin_referer('cp_approve_user_' . intval($_GET['user_id']))) {
            $user_id = intval($_GET['user_id']);
            if ($_GET['cp_action'] === 'approve') {
                update_user_meta($user_id, 'cp_approved', '1');
                echo '<div class="notice notice-success is-dismissible"><p>User approved.</p></div>';
                // Send/update record in Airtable when admin approves
                if (class_exists('CP_Airtable')) {
                    $res = CP_Airtable::update_user_fields($user_id, array('Status' => 'Registered'));
                }
            } elseif ($_GET['cp_action'] === 'reject') {
                update_user_meta($user_id, 'cp_approved', '-1');
                echo '<div class="notice notice-error is-dismissible"><p>User rejected.</p></div>';
                // Update Airtable record status to Deleted when admin rejects
                if (class_exists('CP_Airtable')) {
                    $res = CP_Airtable::update_user_fields($user_id, array('Status' => 'Rejected'));
                }
            }
        }
        // List pending users
        $args = array(
            'meta_key' => 'cp_approved',
            'meta_value' => '0',
            'number' => 100,
            'fields' => array('ID', 'user_login', 'user_email', 'user_registered'),
        );
        $pending_users = get_users($args);
        echo '<div class="wrap"><h1>Pending User Approvals</h1>';
        if (empty($pending_users)) {
            echo '<p>No pending users.</p></div>';
            return;
        }
        echo '<table class="widefat"><thead><tr><th>Username</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead><tbody>';
        foreach ($pending_users as $user) {
            $approve_url = wp_nonce_url(admin_url('admin.php?page=cp-pending-approvals&cp_action=approve&user_id=' . $user->ID), 'cp_approve_user_' . $user->ID);
            $reject_url = wp_nonce_url(admin_url('admin.php?page=cp-pending-approvals&cp_action=reject&user_id=' . $user->ID), 'cp_approve_user_' . $user->ID);
            echo '<tr>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($user->user_registered) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($approve_url) . '" class="button button-primary" style="margin-right:8px;">Approve</a>';
            echo '<a href="' . esc_url($reject_url) . '" class="button">Reject</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function activate()
    {
        CP_Post_Types::register_post_types();
        flush_rewrite_rules();

        // Create upload directory for documents
        $upload_dir = wp_upload_dir();
        $client_docs_dir = $upload_dir['basedir'] . '/client-portal-docs';
        if (!file_exists($client_docs_dir)) {
            wp_mkdir_p($client_docs_dir);
            // Add .htaccess for security
            file_put_contents($client_docs_dir . '/.htaccess', 'deny from all');
        }
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function init()
    {
        CP_Post_Types::register_post_types();
        load_plugin_textdomain('client-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_rest_routes()
    {
        CP_API::register_routes();
    }
}

// Initialize plugin
function cp_init()
{
    return Client_Portal::get_instance();
}
add_action('plugins_loaded', 'cp_init');

/**
 * Helper function to get current client user ID
 */
function cp_get_current_client_id()
{
    if (!is_user_logged_in()) {
        return 0;
    }

    $user = wp_get_current_user();
    if (in_array('client', $user->roles) || in_array('administrator', $user->roles)) {
        return $user->ID;
    }

    return 0;
}

/**
 * Check if user has client portal access
 */
function cp_user_can_access()
{
    return cp_get_current_client_id() > 0;
}

/**
 * Auto-approve administrators
 */
// function cp_auto_approve_admin_on_register($user_id)
// {
//     $user = get_userdata($user_id);
//     if ($user && in_array('administrator', (array) $user->roles, true)) {
//         update_user_meta($user_id, 'cp_approved', '1');
//     }
// }
// add_action('user_register', 'cp_auto_approve_admin_on_register');

/**
 * Send newly registered client users to Airtable (if configured).
 *
 * This will only run when the created user has the 'client' role.
 */
function cp_send_client_to_airtable_on_register($user_id)
{

    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }

    $roles = (array) $user->roles;
    $first_name = isset($_POST['cp_full_name']) ? sanitize_text_field($_POST['cp_full_name']) : '';
    $cp_password = isset($_POST['cp_password']) ? sanitize_text_field($_POST['cp_password']) : '';

    $result = CP_Airtable::send_user_to_airtable($user_id, $first_name, $cp_password);

    // Log result for debugging
    // if (is_wp_error($result)) {
    //     error_log('[CP] Airtable send error for user ' . $user_id . ': ' . $result->get_error_message());
    // } else {
    //     error_log('[CP] Airtable send OK for user ' . $user_id);
    // }

    // Allow developers to act on result via action
    do_action('cp_airtable_after_send', $user_id, $result);
}
add_action('user_register', 'cp_send_client_to_airtable_on_register');

function cp_auto_approve_admin_on_login($user_login, $user)
{
    if ($user && in_array('administrator', (array) $user->roles, true)) {
        $approved = get_user_meta($user->ID, 'cp_approved', true);
        if ($approved !== '1') {
            update_user_meta($user->ID, 'cp_approved', '1');
        }
    }
}
add_action('wp_login', 'cp_auto_approve_admin_on_login', 10, 2);