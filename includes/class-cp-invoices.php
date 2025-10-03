<?php
/**
 * Invoice Management Helper
 * File: includes/class-cp-invoices.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Invoices {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_cp_invoice', array($this, 'save_meta_boxes'));
        add_filter('manage_cp_invoice_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cp_invoice_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('admin_head', array($this, 'admin_styles'));
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cp_invoice_details',
            __('Invoice Details', 'client-portal'),
            array($this, 'render_meta_box'),
            'cp_invoice',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cp_invoice_file',
            __('Invoice File', 'client-portal'),
            array($this, 'render_file_meta_box'),
            'cp_invoice',
            'normal',
            'default'
        );
        
        add_meta_box(
            'cp_invoice_payments',
            __('Payment History', 'client-portal'),
            array($this, 'render_payments_meta_box'),
            'cp_invoice',
            'normal',
            'default'
        );
        
        add_meta_box(
            'cp_invoice_stripe',
            __('Stripe Information', 'client-portal'),
            array($this, 'render_stripe_meta_box'),
            'cp_invoice',
            'side',
            'default'
        );
    }
    
    /**
     * Render Meta Box
     */
    public function render_meta_box($post) {
        wp_nonce_field('cp_invoice_meta_box', 'cp_invoice_meta_box_nonce');
        
        $amount = get_post_meta($post->ID, '_cp_invoice_amount', true);
        $due_date = get_post_meta($post->ID, '_cp_invoice_due_date', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cp_invoice_amount"><?php _e('Amount', 'client-portal'); ?> *</label></th>
                <td>
                    <input type="number" step="0.01" name="cp_invoice_amount" id="cp_invoice_amount" 
                           value="<?php echo esc_attr($amount); ?>" class="regular-text" required />
                    <p class="description"><?php _e('Invoice amount in dollars (e.g., 99.99)', 'client-portal'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cp_invoice_due_date"><?php _e('Due Date', 'client-portal'); ?></label></th>
                <td>
                    <input type="date" name="cp_invoice_due_date" id="cp_invoice_due_date" 
                           value="<?php echo esc_attr($due_date); ?>" class="regular-text" />
                    <p class="description"><?php _e('When this invoice is due', 'client-portal'); ?></p>
                </td>
            </tr>
        </table>
        <p><strong><?php _e('Note:', 'client-portal'); ?></strong> <?php _e('Set the invoice status using the "Invoice Status" box on the right sidebar.', 'client-portal'); ?></p>
        <?php
    }
    
    /**
     * Render File Meta Box
     */
    public function render_file_meta_box($post) {
        $file_url = get_post_meta($post->ID, '_cp_invoice_file_url', true);
        $file_name = get_post_meta($post->ID, '_cp_invoice_file_name', true);
        $file_size = get_post_meta($post->ID, '_cp_invoice_file_size', true);
        
        ?>
        <div class="cp-invoice-file-manager">
            <?php if ($file_url): ?>
                <div class="existing-file" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <h4><?php _e('Current Invoice File:', 'client-portal'); ?></h4>
                    <p>
                        <strong><?php _e('File:', 'client-portal'); ?></strong> 
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="button button-small">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                            <?php echo esc_html($file_name); ?>
                        </a>
                        <?php if ($file_size): ?>
                            <span style="color: #666; margin-left: 10px;">
                                (<?php echo esc_html(size_format($file_size)); ?>)
                            </span>
                        <?php endif; ?>
                    </p>
                    <button type="button" class="button button-secondary" id="remove-current-file">
                        <?php _e('Remove Current File', 'client-portal'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="file-upload-area">
                <h4><?php _e('Upload New Invoice File:', 'client-portal'); ?></h4>
                <input type="file" id="cp_invoice_file_upload" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="margin-bottom: 10px;">
                <p class="description">
                    <?php _e('Supported formats: PDF, JPG, PNG, DOC, DOCX (Max: 10MB)', 'client-portal'); ?>
                </p>
                <button type="button" class="button button-primary" id="upload-invoice-file" <?php echo !$file_url ? 'style="display:none;"' : ''; ?>>
                    <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Upload File', 'client-portal'); ?>
                </button>
                <button type="button" class="button button-primary" id="upload-new-file" <?php echo $file_url ? 'style="display:none;"' : ''; ?>>
                    <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Upload File', 'client-portal'); ?>
                </button>
            </div>
            
            <div id="upload-status" style="margin-top: 10px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#upload-invoice-file, #upload-new-file').on('click', function() {
                var fileInput = $('#cp_invoice_file_upload')[0];
                var file = fileInput.files[0];
                
                if (!file) {
                    alert('Please select a file to upload');
                    return;
                }
                
                var formData = new FormData();
                formData.append('file', file);
                
                var button = $(this);
                button.prop('disabled', true).find('span').removeClass('dashicons-upload').addClass('dashicons-update').css('animation', 'spin 1s linear infinite');
                
                $.ajax({
                    url: '<?php echo rest_url('client-portal/v1/invoices/' . $post->ID . '/upload'); ?>',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#upload-status').html('<div class="notice notice-success"><p>File uploaded successfully!</p></div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Upload failed';
                        $('#upload-status').html('<div class="notice notice-error"><p>Error: ' + errorMsg + '</p></div>');
                        button.prop('disabled', false).find('span').removeClass('dashicons-update').addClass('dashicons-upload').css('animation', 'none');
                    }
                });
            });
            
            $('#remove-current-file').on('click', function() {
                if (confirm('Are you sure you want to remove the current file?')) {
                    // This would need a delete endpoint to be implemented
                    alert('File removal functionality can be added here');
                }
            });
        });
        </script>
        
        <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .cp-invoice-file-manager {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .existing-file {
            border-left: 4px solid #0073aa;
        }
        .file-upload-area {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .file-upload-area:hover {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        </style>
        <?php
    }
    
    /**
     * Render Payments Meta Box
     */
    public function render_payments_meta_box($post) {
        $payments = get_post_meta($post->ID, '_cp_payment_history', true);
        $payment_history = is_array($payments) ? $payments : array();
        $total_paid = array_sum(array_column($payment_history, 'amount'));
        $invoice_amount = floatval(get_post_meta($post->ID, '_cp_invoice_amount', true));
        $remaining_amount = $invoice_amount - $total_paid;
        
        ?>
        <div class="cp-payment-manager">
            <div class="payment-summary" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4><?php _e('Payment Summary', 'client-portal'); ?></h4>
                <table class="widefat" style="margin: 0;">
                    <tr>
                        <td><strong><?php _e('Invoice Amount:', 'client-portal'); ?></strong></td>
                        <td style="text-align: right;"><strong>$<?php echo number_format($invoice_amount, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Total Paid:', 'client-portal'); ?></td>
                        <td style="text-align: right; color: #46b450;">$<?php echo number_format($total_paid, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Remaining:', 'client-portal'); ?></strong></td>
                        <td style="text-align: right; color: <?php echo $remaining_amount > 0 ? '#dc3232' : '#46b450'; ?>;">
                            <strong>$<?php echo number_format($remaining_amount, 2); ?></strong>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($payment_history)): ?>
                <div class="payment-history">
                    <h4><?php _e('Payment History', 'client-portal'); ?></h4>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'client-portal'); ?></th>
                                <th><?php _e('Amount', 'client-portal'); ?></th>
                                <th><?php _e('Method', 'client-portal'); ?></th>
                                <th><?php _e('Notes', 'client-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_history as $payment): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment['date']))); ?></td>
                                    <td style="color: #46b450; font-weight: 600;">$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo esc_html($payment['method']); ?></td>
                                    <td><?php echo esc_html($payment['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?php _e('No payments recorded yet.', 'client-portal'); ?></p>
            <?php endif; ?>
            
            <div class="add-payment" style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
                <h4><?php _e('Record New Payment', 'client-portal'); ?></h4>
                <table class="form-table" style="margin: 0;">
                    <tr>
                        <th style="padding-left: 0; width: 120px;"><?php _e('Amount:', 'client-portal'); ?></th>
                        <td>
                            <input type="number" step="0.01" id="payment-amount" placeholder="0.00" style="width: 120px;">
                        </td>
                    </tr>
                    <tr>
                        <th style="padding-left: 0;"><?php _e('Method:', 'client-portal'); ?></th>
                        <td>
                            <select id="payment-method" style="width: 200px;">
                                <option value="Cash"><?php _e('Cash', 'client-portal'); ?></option>
                                <option value="Check"><?php _e('Check', 'client-portal'); ?></option>
                                <option value="Bank Transfer"><?php _e('Bank Transfer', 'client-portal'); ?></option>
                                <option value="Credit Card"><?php _e('Credit Card', 'client-portal'); ?></option>
                                <option value="PayPal"><?php _e('PayPal', 'client-portal'); ?></option>
                                <option value="Stripe"><?php _e('Stripe', 'client-portal'); ?></option>
                                <option value="Other"><?php _e('Other', 'client-portal'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding-left: 0;"><?php _e('Notes:', 'client-portal'); ?></th>
                        <td>
                            <input type="text" id="payment-notes" placeholder="Optional notes" style="width: 300px;">
                        </td>
                    </tr>
                </table>
                <p style="margin-top: 15px;">
                    <button type="button" class="button button-primary" id="record-payment">
                        <span class="dashicons dashicons-money" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php _e('Record Payment', 'client-portal'); ?>
                    </button>
                </p>
            </div>
            
            <div id="payment-status" style="margin-top: 10px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#record-payment').on('click', function() {
                var amount = parseFloat($('#payment-amount').val());
                var method = $('#payment-method').val();
                var notes = $('#payment-notes').val();
                
                if (!amount || amount <= 0) {
                    alert('Please enter a valid payment amount');
                    return;
                }
                
                var button = $(this);
                button.prop('disabled', true).find('span').removeClass('dashicons-money').addClass('dashicons-update').css('animation', 'spin 1s linear infinite');
                
                $.ajax({
                    url: '<?php echo rest_url('client-portal/v1/invoices/' . $post->ID . '/payment'); ?>',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                    },
                    data: JSON.stringify({
                        amount: amount,
                        method: method,
                        notes: notes
                    }),
                    contentType: 'application/json',
                    success: function(response) {
                        $('#payment-status').html('<div class="notice notice-success"><p>Payment recorded successfully!</p></div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON?.message || 'Payment recording failed';
                        $('#payment-status').html('<div class="notice notice-error"><p>Error: ' + errorMsg + '</p></div>');
                        button.prop('disabled', false).find('span').removeClass('dashicons-update').addClass('dashicons-money').css('animation', 'none');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Stripe Meta Box
     */
    public function render_stripe_meta_box($post) {
        $stripe_invoice_id = get_post_meta($post->ID, '_cp_stripe_invoice_id', true);
        $stripe_payment_intent = get_post_meta($post->ID, '_cp_stripe_payment_intent', true);
        $stripe_customer_id = get_post_meta($post->ID, '_cp_stripe_customer_id', true);
        
        ?>
        <div class="cp-stripe-info">
            <?php if ($stripe_invoice_id || $stripe_payment_intent || $stripe_customer_id): ?>
                <table class="form-table" style="margin: 0;">
                    <?php if ($stripe_invoice_id): ?>
                    <tr>
                        <th style="padding-left: 0;"><?php _e('Invoice ID:', 'client-portal'); ?></th>
                        <td><code style="font-size: 11px;"><?php echo esc_html($stripe_invoice_id); ?></code></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($stripe_payment_intent): ?>
                    <tr>
                        <th style="padding-left: 0;"><?php _e('Payment Intent:', 'client-portal'); ?></th>
                        <td><code style="font-size: 11px;"><?php echo esc_html($stripe_payment_intent); ?></code></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($stripe_customer_id): ?>
                    <tr>
                        <th style="padding-left: 0;"><?php _e('Customer ID:', 'client-portal'); ?></th>
                        <td><code style="font-size: 11px;"><?php echo esc_html($stripe_customer_id); ?></code></td>
                    </tr>
                    <?php endif; ?>
                </table>
            <?php else: ?>
                <p class="description"><?php _e('No Stripe information available. Connect Stripe integration to enable payment processing.', 'client-portal'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save Meta Boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['cp_invoice_meta_box_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['cp_invoice_meta_box_nonce'], 'cp_invoice_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save amount
        if (isset($_POST['cp_invoice_amount'])) {
            $amount = floatval($_POST['cp_invoice_amount']);
            update_post_meta($post_id, '_cp_invoice_amount', $amount);
        }
        
        // Save due date
        if (isset($_POST['cp_invoice_due_date'])) {
            update_post_meta($post_id, '_cp_invoice_due_date', sanitize_text_field($_POST['cp_invoice_due_date']));
        }
    }
    
    /**
     * Set Custom Admin Columns
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['client'] = __('Client', 'client-portal');
        $new_columns['amount'] = __('Amount', 'client-portal');
        $new_columns['status'] = __('Status', 'client-portal');
        $new_columns['due_date'] = __('Due Date', 'client-portal');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Custom Column Content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'client':
                $author_id = get_post_field('post_author', $post_id);
                $author = get_userdata($author_id);
                if ($author) {
                    echo '<a href="' . get_edit_user_link($author_id) . '">' . esc_html($author->display_name) . '</a>';
                    echo '<br><span style="color: #666; font-size: 12px;">' . esc_html($author->user_email) . '</span>';
                }
                break;
                
            case 'amount':
                $amount = get_post_meta($post_id, '_cp_invoice_amount', true);
                echo '<strong style="font-size: 14px; color: #2271b1;">$' . number_format($amount, 2) . '</strong>';
                break;
                
            case 'status':
                $terms = wp_get_post_terms($post_id, 'cp_invoice_status');
                if (!empty($terms)) {
                    $status = $terms[0]->name;
                    $slug = $terms[0]->slug;
                    $color = $this->get_status_color($slug);
                    echo '<span class="cp-status-badge" style="padding: 4px 10px; border-radius: 12px; background: ' . esc_attr($color) . '; color: white; font-size: 11px; font-weight: 600; text-transform: uppercase;">' . esc_html($status) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'due_date':
                $due_date = get_post_meta($post_id, '_cp_invoice_due_date', true);
                if ($due_date) {
                    $timestamp = strtotime($due_date);
                    $formatted = date_i18n(get_option('date_format'), $timestamp);
                    
                    // Check if overdue
                    if ($timestamp < time()) {
                        $terms = wp_get_post_terms($post_id, 'cp_invoice_status');
                        $status = !empty($terms) ? $terms[0]->slug : 'unpaid';
                        if ($status === 'unpaid') {
                            echo '<span style="color: #dc3232; font-weight: 600;">' . esc_html($formatted) . '</span>';
                            echo '<br><span style="color: #dc3232; font-size: 11px;">⚠ OVERDUE</span>';
                        } else {
                            echo esc_html($formatted);
                        }
                    } else {
                        echo esc_html($formatted);
                        $days_until = ceil(($timestamp - time()) / (60 * 60 * 24));
                        if ($days_until <= 7) {
                            echo '<br><span style="color: #f0b429; font-size: 11px;">Due in ' . $days_until . ' days</span>';
                        }
                    }
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
        }
    }
    
    /**
     * Get Status Color
     */
    private function get_status_color($status) {
        $colors = array(
            'unpaid' => '#f0b429',
            'paid' => '#46b450',
            'overdue' => '#dc3232',
            'cancelled' => '#999',
        );
        
        return isset($colors[$status]) ? $colors[$status] : '#999';
    }
    
    /**
     * Admin Styles
     */
    public function admin_styles() {
        global $post_type;
        if ($post_type === 'cp_invoice') {
            ?>
            <style>
                .cp-status-badge {
                    display: inline-block;
                    line-height: 1.2;
                }
                .cp-stripe-info code {
                    display: block;
                    padding: 4px;
                    background: #f5f5f5;
                    border-radius: 3px;
                    word-break: break-all;
                }
            </style>
            <?php
        }
    }
}

// Initialize the class
new CP_Invoices();