<?php
/**
 * Ticket Management Helper
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Tickets {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_cp_ticket_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cp_ticket_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('admin_head', array($this, 'admin_styles'));
        add_action('admin_notices', array($this, 'debug_admin_notices'));
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        global $post;
        
        // Only add meta boxes for cp_ticket post type
        if (!$post || $post->post_type !== 'cp_ticket') {
            return;
        }
        
        // Check if user has permission to manage tickets
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        add_meta_box(
            'cp_ticket_messages',
            __('Ticket Messages', 'client-portal'),
            array($this, 'render_messages_meta_box'),
            'cp_ticket',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cp_ticket_reply',
            __('Add Reply', 'client-portal'),
            array($this, 'render_reply_meta_box'),
            'cp_ticket',
            'side',
            'default'
        );
    }
    
    /**
     * Render Messages Meta Box
     */
    public function render_messages_meta_box($post) {
        $messages = get_comments(array(
            'post_id' => $post->ID,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'ASC',
        ));
        
        if (empty($messages)) {
            echo '<p>' . __('No messages yet.', 'client-portal') . '</p>';
            return;
        }
        
        echo '<div class="cp-ticket-messages">';
        foreach ($messages as $message) {
            $user = get_userdata($message->user_id);
            $is_admin = $user && in_array('administrator', $user->roles);
            $message_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->comment_date));
            
            ?>
            <div class="cp-message <?php echo $is_admin ? 'admin-message' : 'client-message'; ?>" style="margin-bottom: 20px; padding: 15px; background: <?php echo $is_admin ? '#e7f3ff' : '#f8f9fa'; ?>; border-left: 4px solid <?php echo $is_admin ? '#0073aa' : '#6c757d'; ?>; border-radius: 5px; position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center;">
                        <span class="dashicons <?php echo $is_admin ? 'dashicons-admin-users' : 'dashicons-businessman'; ?>" style="margin-right: 8px; color: <?php echo $is_admin ? '#0073aa' : '#6c757d'; ?>;"></span>
                        <strong style="color: <?php echo $is_admin ? '#0073aa' : '#495057'; ?>;"><?php echo esc_html($message->comment_author); ?></strong>
                        <?php if ($is_admin): ?>
                            <span style="margin-left: 8px; padding: 2px 6px; background: #0073aa; color: white; border-radius: 3px; font-size: 0.75em; font-weight: normal;">Admin</span>
                        <?php else: ?>
                            <span style="margin-left: 8px; padding: 2px 6px; background: #6c757d; color: white; border-radius: 3px; font-size: 0.75em; font-weight: normal;">Client</span>
                        <?php endif; ?>
                    </div>
                    <span style="color: #666; font-size: 0.85em;">
                        <span class="dashicons dashicons-calendar-alt" style="margin-right: 4px;"></span>
                        <?php echo esc_html($message_date); ?>
                    </span>
                </div>
                <div style="line-height: 1.6; color: #333;"><?php echo nl2br(esc_html($message->comment_content)); ?></div>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    /**
     * Render Reply Meta Box
     */
    public function render_reply_meta_box($post) {
        // Get current ticket status
        $status_terms = wp_get_post_terms($post->ID, 'cp_ticket_status');
        $current_status = !empty($status_terms) ? $status_terms[0]->slug : 'open';
        
        ?>
        <div class="cp-ticket-reply-form">
            <div style="margin-bottom: 15px;">
                <label for="cp_ticket_reply_text" style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?php _e('Reply Message:', 'client-portal'); ?>
                </label>
                <textarea id="cp_ticket_reply_text" rows="6" style="width: 100%; resize: vertical;" placeholder="<?php _e('Type your reply to the client...', 'client-portal'); ?>"></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                    <?php _e('Ticket Status:', 'client-portal'); ?>
                </label>
                <select id="cp_ticket_status_change" style="width: 100%;">
                    <option value="open" <?php selected($current_status, 'open'); ?>><?php _e('Open', 'client-portal'); ?></option>
                    <option value="pending" <?php selected($current_status, 'pending'); ?>><?php _e('Pending', 'client-portal'); ?></option>
                    <option value="closed" <?php selected($current_status, 'closed'); ?>><?php _e('Closed', 'client-portal'); ?></option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" id="cp_send_email_notification" checked>
                    <?php _e('Send email notification to client', 'client-portal'); ?>
                </label>
            </div>
            
            <p>
                <button type="button" class="button button-primary" id="cp_send_reply">
                    <span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Send Reply', 'client-portal'); ?>
                </button>
                <button type="button" class="button" id="cp_save_status_only" style="margin-left: 10px;">
                    <span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Update Status Only', 'client-portal'); ?>
                </button>
            </p>
            
            <div id="cp_reply_status" style="margin-top: 10px; display: none;"></div>
            
            <p class="description">
                <?php _e('Your reply will be sent to the client. The ticket status will be updated accordingly.', 'client-portal'); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Send reply function
            function sendReply(updateStatus = true) {
                var message = $('#cp_ticket_reply_text').val().trim();
                var newStatus = $('#cp_ticket_status_change').val();
                var sendEmail = $('#cp_send_email_notification').is(':checked');
                
                if (updateStatus && !message) {
                    showStatus('Please enter a message', 'error');
                    return;
                }
                
                var button = $('#cp_send_reply');
                var statusButton = $('#cp_save_status_only');
                button.prop('disabled', true).find('span').removeClass('dashicons-email-alt').addClass('dashicons-update').css('animation', 'spin 1s linear infinite');
                statusButton.prop('disabled', true);
                
                var requestData = {
                    message: message,
                    status: newStatus,
                    send_email: sendEmail,
                    update_status_only: !updateStatus
                };
                
                $.ajax({
                    url: '<?php echo rest_url('client-portal/v1/tickets/' . $post->ID . '/reply'); ?>',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    data: JSON.stringify(requestData),
                    contentType: 'application/json',
                    success: function(response) {
                        showStatus('Operation completed successfully!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Unknown error occurred';
                        showStatus('Error: ' + errorMsg, 'error');
                        resetButtons();
                    }
                });
            }
            
            // Status only update
            $('#cp_save_status_only').on('click', function() {
                sendReply(false);
            });
            
            // Send reply
            $('#cp_send_reply').on('click', function() {
                sendReply(true);
            });
            
            // Helper functions
            function showStatus(message, type) {
                var statusDiv = $('#cp_reply_status');
                var className = type === 'success' ? 'notice notice-success' : 'notice notice-error';
                statusDiv.removeClass('notice notice-success notice-error')
                        .addClass(className)
                        .html('<p>' + message + '</p>')
                        .show();
                
                if (type === 'success') {
                    setTimeout(function() {
                        statusDiv.fadeOut();
                    }, 3000);
                }
            }
            
            function resetButtons() {
                $('#cp_send_reply').prop('disabled', false)
                    .find('span').removeClass('dashicons-update').addClass('dashicons-email-alt')
                    .css('animation', 'none');
                $('#cp_save_status_only').prop('disabled', false);
            }
            
            // Auto-resize textarea
            $('#cp_ticket_reply_text').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
        </script>
        <?php
    }
    
    /**
     * Set Custom Admin Columns
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['priority'] = __('Priority', 'client-portal');
        $new_columns['status'] = __('Status', 'client-portal');
        $new_columns['messages'] = __('Messages', 'client-portal');
        $new_columns['author'] = $columns['author'];
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Custom Column Content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'priority':
                $terms = wp_get_post_terms($post_id, 'cp_ticket_priority');
                if (!empty($terms)) {
                    $priority = $terms[0]->name;
                    $color = $this->get_priority_color($terms[0]->slug);
                    echo '<span style="padding: 3px 8px; border-radius: 3px; background: ' . esc_attr($color) . '; color: white; font-size: 0.85em;">' . esc_html($priority) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'status':
                $terms = wp_get_post_terms($post_id, 'cp_ticket_status');
                if (!empty($terms)) {
                    $status = $terms[0]->name;
                    $color = $this->get_status_color($terms[0]->slug);
                    echo '<span style="padding: 3px 8px; border-radius: 3px; background: ' . esc_attr($color) . '; color: white; font-size: 0.85em;">' . esc_html($status) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'messages':
                $count = get_comments_number($post_id);
                echo '<strong>' . esc_html($count) . '</strong>';
                break;
        }
    }
    
    /**
     * Get Priority Color
     */
    private function get_priority_color($priority) {
        $colors = array(
            'low' => '#10b981',
            'normal' => '#3b82f6',
            'high' => '#ef4444',
        );
        
        return isset($colors[$priority]) ? $colors[$priority] : '#6b7280';
    }
    
    /**
     * Get Status Color
     */
    private function get_status_color($status) {
        $colors = array(
            'open' => '#3b82f6',
            'closed' => '#6b7280',
            'pending' => '#f59e0b',
        );
        
        return isset($colors[$status]) ? $colors[$status] : '#6b7280';
    }
    
    /**
     * Admin Styles
     */
    public function admin_styles() {
        global $post_type;
        if ($post_type === 'cp_ticket') {
            ?>
            <style>
                .cp-ticket-messages {
                    max-height: 600px;
                    overflow-y: auto;
                    padding: 10px;
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                
                .cp-message {
                    animation: fadeIn 0.3s ease-in;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    transition: all 0.2s ease;
                }
                
                .cp-message:hover {
                    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                }
                
                .cp-message.admin-message {
                    border-left-color: #0073aa !important;
                }
                
                .cp-message.client-message {
                    border-left-color: #6c757d !important;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                .cp-ticket-reply-form {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 5px;
                    border: 1px solid #ddd;
                }
                
                .cp-ticket-reply-form textarea {
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    padding: 8px;
                    font-family: inherit;
                    transition: border-color 0.2s ease;
                }
                
                .cp-ticket-reply-form textarea:focus {
                    border-color: #0073aa;
                    box-shadow: 0 0 0 1px #0073aa;
                    outline: none;
                }
                
                .cp-ticket-reply-form select {
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    padding: 5px;
                    background: white;
                }
                
                .cp-ticket-reply-form select:focus {
                    border-color: #0073aa;
                    box-shadow: 0 0 0 1px #0073aa;
                    outline: none;
                }
                
                #cp_reply_status {
                    margin-top: 10px;
                }
                
                #cp_reply_status.notice {
                    padding: 10px 15px;
                    border-radius: 3px;
                    border-left-width: 4px;
                    border-left-style: solid;
                }
                
                #cp_reply_status.notice-success {
                    background: #d4edda;
                    border-left-color: #28a745;
                    color: #155724;
                }
                
                #cp_reply_status.notice-error {
                    background: #f8d7da;
                    border-left-color: #dc3545;
                    color: #721c24;
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
            <?php
        }
    }
    
    /**
     * Debug admin notices to help troubleshoot
     */
    public function debug_admin_notices() {
        global $post, $post_type;
        
        // Only show debug info on ticket pages for admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if ($post_type === 'cp_ticket' && isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            $post = get_post($post_id);
            
            if ($post && $post->post_type === 'cp_ticket') {
                echo '<div class="notice notice-info"><p><strong>Debug Info:</strong> ';
                echo 'Post ID: ' . $post_id . ' | ';
                echo 'Post Type: ' . $post->post_type . ' | ';
                echo 'User Can Edit: ' . (current_user_can('edit_posts') ? 'Yes' : 'No') . ' | ';
                echo 'Meta Boxes Registered: Yes';
                echo '</p></div>';
            }
        }
    }
}

new CP_Tickets();