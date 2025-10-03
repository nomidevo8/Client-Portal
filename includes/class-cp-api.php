<?php
/**
 * REST API Endpoints for Client Portal
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_API {
    
    private static $namespace = 'client-portal/v1';
    
    public static function register_routes() {
        // Profile endpoints
        register_rest_route(self::$namespace, '/profile', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_profile'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/profile', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'update_profile'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        // Document endpoints
        register_rest_route(self::$namespace, '/documents', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_documents'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/documents', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'upload_document'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/documents/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_document'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/documents/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'delete_document'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/documents/(?P<id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'download_document'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        // Ticket endpoints
        register_rest_route(self::$namespace, '/tickets', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_tickets'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/tickets', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_ticket'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/tickets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_ticket'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/tickets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array(__CLASS__, 'update_ticket'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/tickets/(?P<id>\d+)/reply', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'reply_ticket'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        // Invoice endpoints
        register_rest_route(self::$namespace, '/invoices', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_invoices'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_invoice'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_invoice'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array(__CLASS__, 'update_invoice'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices/(?P<id>\d+)/upload', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'upload_invoice_file'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices/(?P<id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'download_invoice_file'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        register_rest_route(self::$namespace, '/invoices/(?P<id>\d+)/payment', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'record_payment'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
        
        // Dashboard summary
        register_rest_route(self::$namespace, '/dashboard', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_dashboard'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
        ));
    }
    
    public static function check_permission() {
        return is_user_logged_in();
    }
    
    /**
     * Profile Methods
     */
    public static function get_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }
        
        return array(
            'fullName' => get_user_meta($user_id, 'cp_full_name', true) ?: $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, 'cp_phone', true),
            'company' => get_user_meta($user_id, 'cp_company', true),
            'address' => get_user_meta($user_id, 'cp_address', true),
        );
    }
    
    public static function update_profile($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        if (isset($params['fullName'])) {
            update_user_meta($user_id, 'cp_full_name', sanitize_text_field($params['fullName']));
            wp_update_user(array('ID' => $user_id, 'display_name' => sanitize_text_field($params['fullName'])));
        }
        
        if (isset($params['email'])) {
            wp_update_user(array('ID' => $user_id, 'user_email' => sanitize_email($params['email'])));
        }
        
        if (isset($params['phone'])) {
            update_user_meta($user_id, 'cp_phone', sanitize_text_field($params['phone']));
        }
        
        if (isset($params['company'])) {
            update_user_meta($user_id, 'cp_company', sanitize_text_field($params['company']));
        }
        
        if (isset($params['address'])) {
            update_user_meta($user_id, 'cp_address', sanitize_textarea_field($params['address']));
        }

           // After saving, send only changed fields to Airtable (if integration exists)
        if (class_exists('CP_Airtable')) {
            $update_fields = array();

            if (isset($params['fullName'])) {
                $update_fields['First Name'] = sanitize_text_field($params['fullName']);
                $update_fields['Last Name'] = get_user_meta($user_id, 'last_name', true);
            }
            
            if (isset($params['email'])) {
                 $update_fields['E-mail Address'] = sanitize_email($params['email']);
            }
            
            if (isset($params['company'])) {
                 $update_fields['Company'] = sanitize_text_field($params['company']);
            }
            
            if (isset($params['address'])) {
                 $update_fields['Address'] = sanitize_textarea_field($params['address']);
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
        
        return array('success' => true, 'message' => 'Profile updated successfully');
    }
    
    /**
     * Document Methods
     */
    public static function get_documents($request) {
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'cp_document',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Admin can see all documents
        if (current_user_can('manage_options')) {
            unset($args['author']);
        }
        
        $posts = get_posts($args);
        $documents = array();
        
        foreach ($posts as $post) {
            $documents[] = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                'filename' => get_post_meta($post->ID, '_cp_filename', true),
                'mime' => get_post_meta($post->ID, '_cp_mime_type', true),
                'uploadedAt' => $post->post_date,
                'size' => get_post_meta($post->ID, '_cp_file_size', true),
            );
        }
        
        return $documents;
    }
    
    public static function upload_document($request) {
        $user_id = get_current_user_id();
        $files = $request->get_file_params();
        $params = $request->get_params();
        
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
        }
        
        $file = $files['file'];
        
        // Handle file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'jpg|jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'txt' => 'text/plain',
                'zip' => 'application/zip',
            )
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if (isset($movefile['error'])) {
            return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
        }
        
        // Create document post
        $title = isset($params['title']) && !empty($params['title']) ? $params['title'] : $file['name'];
        
        $post_id = wp_insert_post(array(
            'post_type' => 'cp_document',
            'post_title' => sanitize_text_field($title),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Save file metadata
        update_post_meta($post_id, '_cp_file_path', $movefile['file']);
        update_post_meta($post_id, '_cp_file_url', $movefile['url']);
        update_post_meta($post_id, '_cp_filename', $file['name']);
        update_post_meta($post_id, '_cp_mime_type', $file['type']);
        update_post_meta($post_id, '_cp_file_size', $file['size']);
        
        return array(
            'success' => true,
            'id' => $post_id,
            'message' => 'Document uploaded successfully',
        );
    }
    
    public static function get_document($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_document') {
            return new WP_Error('not_found', 'Document not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        return array(
            'id' => $post->ID,
            'name' => $post->post_title,
            'filename' => get_post_meta($post->ID, '_cp_filename', true),
            'mime' => get_post_meta($post->ID, '_cp_mime_type', true),
            'uploadedAt' => $post->post_date,
            'size' => get_post_meta($post->ID, '_cp_file_size', true),
        );
    }
    
    public static function download_document($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_document') {
            return new WP_Error('not_found', 'Document not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        $file_path = get_post_meta($post->ID, '_cp_file_path', true);
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'File not found on server', array('status' => 404));
        }
        
        return array(
            'url' => get_post_meta($post->ID, '_cp_file_url', true),
            'filename' => get_post_meta($post->ID, '_cp_filename', true),
        );
    }
    
    public static function delete_document($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_document') {
            return new WP_Error('not_found', 'Document not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        // Delete physical file
        $file_path = get_post_meta($post->ID, '_cp_file_path', true);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        
        // Delete post
        wp_delete_post($id, true);
        
        return array('success' => true, 'message' => 'Document deleted successfully');
    }
    
    /**
     * Ticket Methods
     */
    public static function get_tickets($request) {
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'cp_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Admin can see all tickets
        if (current_user_can('manage_options')) {
            unset($args['author']);
        }
        
        $posts = get_posts($args);
        $tickets = array();
        
        foreach ($posts as $post) {
            $status_terms = wp_get_post_terms($post->ID, 'cp_ticket_status');
            $priority_terms = wp_get_post_terms($post->ID, 'cp_ticket_priority');
            
            $messages = get_comments(array(
                'post_id' => $post->ID,
                'status' => 'approve',
                'orderby' => 'comment_date',
                'order' => 'ASC',
            ));
            
            $message_list = array();
            foreach ($messages as $msg) {
                $message_list[] = array(
                    'author' => $msg->comment_author,
                    'text' => $msg->comment_content,
                    'date' => $msg->comment_date,
                );
            }
            
            $tickets[] = array(
                'id' => $post->ID,
                'subject' => $post->post_title,
                'priority' => !empty($priority_terms) ? $priority_terms[0]->slug : 'normal',
                'status' => !empty($status_terms) ? $status_terms[0]->slug : 'open',
                'createdAt' => $post->post_date,
                'messages' => $message_list,
            );
        }
        
        return $tickets;
    }
    
    public static function create_ticket($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        if (empty($params['subject']) || empty($params['message'])) {
            return new WP_Error('missing_fields', 'Subject and message are required', array('status' => 400));
        }
        
        $post_id = wp_insert_post(array(
            'post_type' => 'cp_ticket',
            'post_title' => sanitize_text_field($params['subject']),
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set priority
        $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : 'normal';
        wp_set_object_terms($post_id, $priority, 'cp_ticket_priority');
        
        // Set status to open
        wp_set_object_terms($post_id, 'open', 'cp_ticket_status');
        
        // Add first message as comment
        $user = wp_get_current_user();
        wp_insert_comment(array(
            'comment_post_ID' => $post_id,
            'comment_content' => sanitize_textarea_field($params['message']),
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'user_id' => $user_id,
            'comment_approved' => 1,
        ));
        
        return array(
            'success' => true,
            'id' => $post_id,
            'message' => 'Ticket created successfully',
        );
    }
    
    public static function get_ticket($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_ticket') {
            return new WP_Error('not_found', 'Ticket not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        $status_terms = wp_get_post_terms($post->ID, 'cp_ticket_status');
        $priority_terms = wp_get_post_terms($post->ID, 'cp_ticket_priority');
        
        $messages = get_comments(array(
            'post_id' => $post->ID,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'ASC',
        ));
        
        $message_list = array();
        foreach ($messages as $msg) {
            $message_list[] = array(
                'author' => $msg->comment_author,
                'text' => $msg->comment_content,
                'date' => $msg->comment_date,
            );
        }
        
        return array(
            'id' => $post->ID,
            'subject' => $post->post_title,
            'priority' => !empty($priority_terms) ? $priority_terms[0]->slug : 'normal',
            'status' => !empty($status_terms) ? $status_terms[0]->slug : 'open',
            'createdAt' => $post->post_date,
            'messages' => $message_list,
        );
    }
    
    public static function update_ticket($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_ticket') {
            return new WP_Error('not_found', 'Ticket not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        // Update status if provided
        if (isset($params['status'])) {
            wp_set_object_terms($id, sanitize_text_field($params['status']), 'cp_ticket_status');
        }
        
        // Update priority if provided (admin only)
        if (isset($params['priority']) && current_user_can('manage_options')) {
            wp_set_object_terms($id, sanitize_text_field($params['priority']), 'cp_ticket_priority');
        }
        
        return array('success' => true, 'message' => 'Ticket updated successfully');
    }
    
    public static function reply_ticket($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_ticket') {
            return new WP_Error('not_found', 'Ticket not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
        $new_status = isset($params['status']) ? sanitize_text_field($params['status']) : 'open';
        $send_email = isset($params['send_email']) ? (bool) $params['send_email'] : true;
        $update_status_only = isset($params['update_status_only']) ? (bool) $params['update_status_only'] : false;
        
        // If updating status only, don't require message
        if (!$update_status_only && empty($message)) {
            return new WP_Error('missing_message', 'Message is required', array('status' => 400));
        }
        
        // Add reply as comment if message is provided
        if (!empty($message)) {
            $user = wp_get_current_user();
            wp_insert_comment(array(
                'comment_post_ID' => $id,
                'comment_content' => $message,
                'comment_author' => $user->display_name,
                'comment_author_email' => $user->user_email,
                'user_id' => $user_id,
                'comment_approved' => 1,
            ));
        }
        
        // Update ticket status
        if ($new_status) {
            wp_set_object_terms($id, $new_status, 'cp_ticket_status');
        }
        
        // Send email notification if requested and message exists
        if ($send_email && !empty($message)) {
            self::send_ticket_notification_email($id, $message, $new_status);
        }
        
        $success_message = $update_status_only ? 'Ticket status updated successfully' : 'Reply added successfully';
        
        return array(
            'success' => true, 
            'message' => $success_message,
            'status_updated' => $new_status,
            'email_sent' => $send_email && !empty($message)
        );
    }
    
    /**
     * Send ticket notification email
     */
    private static function send_ticket_notification_email($ticket_id, $message, $status) {
        $ticket = get_post($ticket_id);
        if (!$ticket) {
            return false;
        }
        
        $client = get_userdata($ticket->post_author);
        if (!$client) {
            return false;
        }
        
        $admin = wp_get_current_user();
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        
        // Email subject
        $subject = sprintf('[%s] New Reply to Ticket: %s', $site_name, $ticket->post_title);
        
        // Email body
        $body = sprintf(
            "Hello %s,\n\n" .
            "You have received a new reply to your support ticket:\n\n" .
            "Ticket: %s\n" .
            "Status: %s\n\n" .
            "Reply from %s:\n" .
            "%s\n\n" .
            "You can view and reply to this ticket by visiting:\n" .
            "%s\n\n" .
            "Best regards,\n" .
            "%s Support Team",
            $client->display_name,
            $ticket->post_title,
            ucfirst($status),
            $admin->display_name,
            $message,
            $site_url . '/client-portal/', // Assuming client portal page
            $site_name
        );
        
        // Set headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $admin->display_name . ' <' . $admin->user_email . '>'
        );
        
        // Send email
        return wp_mail($client->user_email, $subject, $body, $headers);
    }
    
    /**
     * Invoice Methods
     */
    public static function get_invoices($request) {
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'cp_invoice',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Admin can see all invoices
        if (current_user_can('manage_options')) {
            unset($args['author']);
        }
        
        $posts = get_posts($args);
        $invoices = array();
        
        foreach ($posts as $post) {
            $status_terms = wp_get_post_terms($post->ID, 'cp_invoice_status');
            
            // Get payment history
            $payments = get_post_meta($post->ID, '_cp_payment_history', true);
            $payment_history = is_array($payments) ? $payments : array();
            
            $invoices[] = array(
                'id' => $post->ID,
                'amount' => get_post_meta($post->ID, '_cp_invoice_amount', true),
                'memo' => $post->post_title,
                'description' => $post->post_content,
                'status' => !empty($status_terms) ? $status_terms[0]->slug : 'unpaid',
                'dueAt' => get_post_meta($post->ID, '_cp_invoice_due_date', true),
                'createdAt' => $post->post_date,
                'hasFile' => !empty(get_post_meta($post->ID, '_cp_invoice_file_url', true)),
                'paymentHistory' => $payment_history,
                'totalPaid' => array_sum(array_column($payment_history, 'amount')),
            );
        }
        
        return $invoices;
    }
    
    public static function get_invoice($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_invoice') {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        $status_terms = wp_get_post_terms($post->ID, 'cp_invoice_status');
        
        // Get payment history
        $payments = get_post_meta($post->ID, '_cp_payment_history', true);
        $payment_history = is_array($payments) ? $payments : array();
        
        return array(
            'id' => $post->ID,
            'amount' => get_post_meta($post->ID, '_cp_invoice_amount', true),
            'memo' => $post->post_title,
            'description' => $post->post_content,
            'status' => !empty($status_terms) ? $status_terms[0]->slug : 'unpaid',
            'dueAt' => get_post_meta($post->ID, '_cp_invoice_due_date', true),
            'createdAt' => $post->post_date,
            'hasFile' => !empty(get_post_meta($post->ID, '_cp_invoice_file_url', true)),
            'fileUrl' => get_post_meta($post->ID, '_cp_invoice_file_url', true),
            'fileName' => get_post_meta($post->ID, '_cp_invoice_file_name', true),
            'paymentHistory' => $payment_history,
            'totalPaid' => array_sum(array_column($payment_history, 'amount')),
            'remainingAmount' => floatval(get_post_meta($post->ID, '_cp_invoice_amount', true)) - array_sum(array_column($payment_history, 'amount')),
        );
    }
    
    /**
     * Create Invoice
     */
    public static function create_invoice($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        if (empty($params['memo']) || empty($params['amount'])) {
            return new WP_Error('missing_fields', 'Memo and amount are required', array('status' => 400));
        }
        
        $post_id = wp_insert_post(array(
            'post_type' => 'cp_invoice',
            'post_title' => sanitize_text_field($params['memo']),
            'post_content' => isset($params['description']) ? sanitize_textarea_field($params['description']) : '',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Save invoice metadata
        update_post_meta($post_id, '_cp_invoice_amount', floatval($params['amount']));
        
        if (isset($params['dueDate'])) {
            update_post_meta($post_id, '_cp_invoice_due_date', sanitize_text_field($params['dueDate']));
        }
        
        // Set default status to unpaid
        wp_set_object_terms($post_id, 'unpaid', 'cp_invoice_status');
        
        // Initialize empty payment history
        update_post_meta($post_id, '_cp_payment_history', array());
        
        return array(
            'success' => true,
            'id' => $post_id,
            'message' => 'Invoice created successfully',
        );
    }
    
    /**
     * Update Invoice
     */
    public static function update_invoice($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_invoice') {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        // Update post fields
        if (isset($params['memo'])) {
            wp_update_post(array('ID' => $id, 'post_title' => sanitize_text_field($params['memo'])));
        }
        
        if (isset($params['description'])) {
            wp_update_post(array('ID' => $id, 'post_content' => sanitize_textarea_field($params['description'])));
        }
        
        // Update metadata
        if (isset($params['amount'])) {
            update_post_meta($id, '_cp_invoice_amount', floatval($params['amount']));
        }
        
        if (isset($params['dueDate'])) {
            update_post_meta($id, '_cp_invoice_due_date', sanitize_text_field($params['dueDate']));
        }
        
        // Update status if provided
        if (isset($params['status'])) {
            wp_set_object_terms($id, sanitize_text_field($params['status']), 'cp_invoice_status');
        }
        
        return array('success' => true, 'message' => 'Invoice updated successfully');
    }
    
    /**
     * Upload Invoice File
     */
    public static function upload_invoice_file($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        $files = $request->get_file_params();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_invoice') {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
        }
        
        $file = $files['file'];
        
        // Handle file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf' => 'application/pdf',
                'jpg|jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            )
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if (isset($movefile['error'])) {
            return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
        }
        
        // Save file metadata
        update_post_meta($id, '_cp_invoice_file_path', $movefile['file']);
        update_post_meta($id, '_cp_invoice_file_url', $movefile['url']);
        update_post_meta($id, '_cp_invoice_file_name', $file['name']);
        update_post_meta($id, '_cp_invoice_file_size', $file['size']);
        update_post_meta($id, '_cp_invoice_file_type', $file['type']);
        
        return array(
            'success' => true,
            'message' => 'Invoice file uploaded successfully',
            'fileName' => $file['name'],
            'fileUrl' => $movefile['url'],
        );
    }
    
    /**
     * Download Invoice File
     */
    public static function download_invoice_file($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_invoice') {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        $file_url = get_post_meta($id, '_cp_invoice_file_url', true);
        $file_name = get_post_meta($id, '_cp_invoice_file_name', true);
        
        if (empty($file_url)) {
            return new WP_Error('no_file', 'No file attached to this invoice', array('status' => 404));
        }
        
        return array(
            'url' => $file_url,
            'filename' => $file_name ?: 'invoice.pdf',
        );
    }
    
    /**
     * Record Payment
     */
    public static function record_payment($request) {
        $id = $request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        if (empty($params['amount'])) {
            return new WP_Error('missing_amount', 'Payment amount is required', array('status' => 400));
        }
        
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'cp_invoice') {
            return new WP_Error('not_found', 'Invoice not found', array('status' => 404));
        }
        
        // Check permission
        if ($post->post_author != $user_id && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
        }
        
        // Get current payment history
        $payments = get_post_meta($id, '_cp_payment_history', true);
        $payment_history = is_array($payments) ? $payments : array();
        
        // Add new payment
        $payment = array(
            'amount' => floatval($params['amount']),
            'date' => current_time('mysql'),
            'method' => isset($params['method']) ? sanitize_text_field($params['method']) : 'Unknown',
            'notes' => isset($params['notes']) ? sanitize_text_field($params['notes']) : '',
            'recorded_by' => $user_id,
        );
        
        $payment_history[] = $payment;
        update_post_meta($id, '_cp_payment_history', $payment_history);
        
        // Calculate total paid and update status
        $total_paid = array_sum(array_column($payment_history, 'amount'));
        $invoice_amount = floatval(get_post_meta($id, '_cp_invoice_amount', true));
        
        if ($total_paid >= $invoice_amount) {
            wp_set_object_terms($id, 'paid', 'cp_invoice_status');
        } else {
            wp_set_object_terms($id, 'unpaid', 'cp_invoice_status');
        }
        
        return array(
            'success' => true,
            'message' => 'Payment recorded successfully',
            'totalPaid' => $total_paid,
            'remainingAmount' => $invoice_amount - $total_paid,
            'newStatus' => $total_paid >= $invoice_amount ? 'paid' : 'unpaid',
        );
    }
    
    /**
     * Dashboard Summary
     */
    public static function get_dashboard($request) {
        $user_id = get_current_user_id();
        
        // Count documents
        $doc_count = count(get_posts(array(
            'post_type' => 'cp_document',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        )));
        
        // Count open tickets
        $tickets = get_posts(array(
            'post_type' => 'cp_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'cp_ticket_status',
                    'field' => 'slug',
                    'terms' => 'open',
                ),
            ),
        ));
        
        // Recent documents
        $recent_docs = get_posts(array(
            'post_type' => 'cp_document',
            'author' => $user_id,
            'posts_per_page' => 3,
        ));
        
        $recent_doc_names = array_map(function($post) {
            return get_post_meta($post->ID, '_cp_filename', true);
        }, $recent_docs);
        
        // Recent tickets
        $recent_tickets = get_posts(array(
            'post_type' => 'cp_ticket',
            'author' => $user_id,
            'posts_per_page' => 3,
        ));
        
        $recent_ticket_subjects = array_map(function($post) {
            return $post->post_title;
        }, $recent_tickets);
        
        return array(
            'docCount' => $doc_count,
            'openTickets' => count($tickets),
            'recentDocs' => $recent_doc_names,
            'recentTickets' => $recent_ticket_subjects,
        );
    }
}