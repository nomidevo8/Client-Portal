<?php
/**
 * Shortcode for Client Portal Frontend
 * Add this file to includes/ folder and require it in main plugin file
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Shortcode {
    
    public function __construct() {
        add_shortcode('client_portal', array($this, 'render_portal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue Scripts and Styles
     */
    public function enqueue_scripts() {
        if (is_singular() && has_shortcode(get_post()->post_content, 'client_portal')) {
            // Enqueue the frontend HTML file or inline it
            wp_localize_script('cp-frontend', 'cpApiSettings', array(
                'root' => esc_url_raw(rest_url('client-portal/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentUser' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
                'userId' => get_current_user_id(),
            ));
            // Adjust path and version as needed
            wp_enqueue_script(
                'client-portal-js',                                
                CP_PLUGIN_URL . 'assets/js/client-portal.js',
                [ 'jquery' ],                                  
                CP_VERSION,                                     
                true                                        
            );

            // Localize PHP data for JS
            wp_localize_script( 'client-portal-js', 'ClientPortal', [
                'api_root' => esc_url_raw( rest_url( 'client-portal/v1/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
            ] );

            wp_enqueue_style(
                'client-portal-css', 
                CP_PLUGIN_URL . 'assets/css/client-portal.css',
                [],
                CP_VERSION
            );
        }
    }
    
    /**
     * Render Portal Shortcode
     */
    public function render_portal($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            // Show login and registration forms
            return $this->render_auth_forms();
        }
        
		// Check if user is approved (admins bypass approval)
		$user_id = get_current_user_id();
		$approved = get_user_meta($user_id, 'cp_approved', true);
		if ($approved != '1' && !current_user_can('manage_options')) {
            return '<div class="cp-error">Your account is pending admin approval. Please wait for approval before accessing the portal.</div>';
        }
        
        // Check if user has access
        if (!cp_user_can_access()) {
            return '<div class="cp-error">You do not have permission to access the client portal.</div>';
        }
        
        // Include the frontend HTML
        ob_start();
        $this->render_portal_html();
        return ob_get_clean();
    }
    
    /**
     * Render Login & Register Forms
     */
    private function render_auth_forms() {
        ob_start();
        ?>
        <style>
        .cp-login-wrapper {
            max-width: 420px;
            margin: 50px auto;
            padding: 35px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
        }
        .cp-login-wrapper h2 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        .cp-btn {
            background: #0073aa;
            border: none;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .cp-btn:hover {
            background: #005f8d;
        }
        .cp-btn-ghost {
            background: transparent;
            border: 2px solid #0073aa;
            color: #0073aa;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
        }
        .cp-btn-ghost.active {
            background: #0073aa;
            color: #fff;
        }
        .cp-form-row {
            margin-bottom: 15px;
        }
        .cp-input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .cp-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 5px rgba(0,115,170,0.3);
            outline: none;
        }
        .cp-small {
            font-size: 14px;
            margin-top: 10px;
        }
        .cp-auth-tab {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(5px);}
            to {opacity: 1; transform: translateY(0);}
        }
        .cp-login-wrapper p a {
            color: #0073aa;
            text-decoration: none;
        }
        .cp-login-wrapper p a:hover {
            text-decoration: underline;
        }
        </style>

        <div class="cp-login-wrapper">
            <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 25px;">
                <button id="cp-login-tab" class="cp-btn cp-btn-ghost active" onclick="cpShowTab('login')">Login</button>
                <button id="cp-register-tab" class="cp-btn cp-btn-ghost" onclick="cpShowTab('register')">Register</button>
            </div>
            <div id="cp-login-form" class="cp-auth-tab">
                <h2 style="text-align: center;">Client Portal Login</h2>
                <?php wp_login_form(array(
                    'redirect' => get_permalink(),
                    'form_id' => 'cp-loginform',
                    'label_username' => __('Username or Email', 'client-portal'),
                    'label_password' => __('Password', 'client-portal'),
                    'label_remember' => __('Remember Me', 'client-portal'),
                    'label_log_in' => __('Log In', 'client-portal'),
                )); ?>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>">Lost your password?</a>
                </p>
            </div>
            <div id="cp-register-form" class="cp-auth-tab" style="display:none;">
                <h2 style="text-align: center;">Register</h2>
                <form method="post">
                    <input type="hidden" name="cp_register" value="1" />
                    <div class="cp-form-row">
                        <input class="cp-input" name="cp_full_name" placeholder="Full Name" required />
                    </div>
                    <div class="cp-form-row">
                        <input class="cp-input" name="cp_username" placeholder="Username" required />
                    </div>
                    <div class="cp-form-row">
                        <input class="cp-input" name="cp_email" type="email" placeholder="Email" required />
                    </div>
                    <div class="cp-form-row">
                        <input class="cp-input" name="cp_password" type="password" placeholder="Password" required />
                    </div>
                    <button type="submit" class="cp-btn" style="width:100%;">Register</button>
                </form>
            </div>
        </div>
        <script>
        function cpShowTab(tab) {
            document.getElementById('cp-login-form').style.display = tab === 'login' ? '' : 'none';
            document.getElementById('cp-register-form').style.display = tab === 'register' ? '' : 'none';
            document.getElementById('cp-login-tab').classList.toggle('active', tab === 'login');
            document.getElementById('cp-register-tab').classList.toggle('active', tab === 'register');
        }
        </script>
        <?php
        // keep your registration PHP code unchanged...
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cp_register'])) {
            $username = sanitize_user($_POST['cp_username']);
            $email = sanitize_email($_POST['cp_email']);
            $password = $_POST['cp_password'];
            $full_name = sanitize_text_field($_POST['cp_full_name']);
            $errors = array();
            if (username_exists($username) || email_exists($email)) {
                $errors[] = 'Username or email already exists.';
            }
            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                $errors[] = 'All fields are required.';
            }
            if (!is_email($email)) {
                $errors[] = 'Invalid email address.';
            }
            if (empty($errors)) {
                $user_id = wp_create_user($username, $password, $email);
                if (is_wp_error($user_id)) {
                    $errors[] = $user_id->get_error_message();
                } else {
                    update_user_meta($user_id, 'cp_approved', '0');
                    update_user_meta($user_id, 'first_name', $full_name);
                    $user = new WP_User($user_id);
                    $user->set_role('client');
                    echo '<div class="cp-small" style="color:green;text-align:center;margin-top:10px;">Registration successful! Please wait for admin approval.</div>';
                }
            }
            if (!empty($errors)) {
                echo '<div class="cp-small" style="color:red;text-align:center;margin-top:10px;">'.implode('<br>', $errors).'</div>';
            }
        }
        return ob_get_clean();
    }

    
    /**
     * Render Portal HTML
     */
    private function render_portal_html() {
        $user      = wp_get_current_user();
        $nonce     = wp_create_nonce( 'wp_rest' );
        $api_root  = rest_url( 'client-portal/v1/' );
    
        // Make variables available inside the template
        $current_user_name = esc_html( $user->display_name );
        $logout_url        = wp_logout_url( get_permalink() );
    
        // ✅ Load the partial
        include CP_PLUGIN_DIR . 'includes/partials/portal.php';
    }
}

new CP_Shortcode();