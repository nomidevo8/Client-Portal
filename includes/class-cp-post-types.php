<?php
/**
 * Register Custom Post Types for Client Portal
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Post_Types {
    
    public static function register_post_types() {
        self::register_document_post_type();
        self::register_ticket_post_type();
        self::register_invoice_post_type();
    }
    
    /**
     * Register Document Post Type
     */
    private static function register_document_post_type() {
        $labels = array(
            'name'                  => _x('Documents', 'Post Type General Name', 'client-portal'),
            'singular_name'         => _x('Document', 'Post Type Singular Name', 'client-portal'),
            'menu_name'             => __('Client Documents', 'client-portal'),
            'name_admin_bar'        => __('Document', 'client-portal'),
            'archives'              => __('Document Archives', 'client-portal'),
            'attributes'            => __('Document Attributes', 'client-portal'),
            'all_items'             => __('All Documents', 'client-portal'),
            'add_new_item'          => __('Add New Document', 'client-portal'),
            'add_new'               => __('Add New', 'client-portal'),
            'new_item'              => __('New Document', 'client-portal'),
            'edit_item'             => __('Edit Document', 'client-portal'),
            'update_item'           => __('Update Document', 'client-portal'),
            'view_item'             => __('View Document', 'client-portal'),
            'view_items'            => __('View Documents', 'client-portal'),
            'search_items'          => __('Search Document', 'client-portal'),
        );
        
        $args = array(
            'label'                 => __('Document', 'client-portal'),
            'labels'                => $labels,
            'supports'              => array('title', 'author', 'custom-fields'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'client-portal',
            'menu_position'         => 20,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'cp-documents',
        );
        
        register_post_type('cp_document', $args);
    }
    
    /**
     * Register Ticket Post Type
     */
    private static function register_ticket_post_type() {
        $labels = array(
            'name'                  => _x('Tickets', 'Post Type General Name', 'client-portal'),
            'singular_name'         => _x('Ticket', 'Post Type Singular Name', 'client-portal'),
            'menu_name'             => __('Support Tickets', 'client-portal'),
            'name_admin_bar'        => __('Ticket', 'client-portal'),
            'archives'              => __('Ticket Archives', 'client-portal'),
            'attributes'            => __('Ticket Attributes', 'client-portal'),
            'all_items'             => __('All Tickets', 'client-portal'),
            'add_new_item'          => __('Add New Ticket', 'client-portal'),
            'add_new'               => __('Add New', 'client-portal'),
            'new_item'              => __('New Ticket', 'client-portal'),
            'edit_item'             => __('Edit Ticket', 'client-portal'),
            'update_item'           => __('Update Ticket', 'client-portal'),
            'view_item'             => __('View Ticket', 'client-portal'),
            'view_items'            => __('View Tickets', 'client-portal'),
            'search_items'          => __('Search Ticket', 'client-portal'),
        );
        
        $args = array(
            'label'                 => __('Ticket', 'client-portal'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'author', 'custom-fields', 'comments'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'client-portal',
            'menu_position'         => 21,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'cp-tickets',
        );
        
        register_post_type('cp_ticket', $args);
        
        // Register ticket status taxonomy
        register_taxonomy('cp_ticket_status', 'cp_ticket', array(
            'label'                 => __('Ticket Status', 'client-portal'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'hierarchical'          => false,
            'show_admin_column'     => true,
        ));
        
        // Register ticket priority taxonomy
        register_taxonomy('cp_ticket_priority', 'cp_ticket', array(
            'label'                 => __('Priority', 'client-portal'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'hierarchical'          => false,
            'show_admin_column'     => true,
        ));
        
        // Insert default terms
        if (!term_exists('open', 'cp_ticket_status')) {
            wp_insert_term('open', 'cp_ticket_status');
            wp_insert_term('closed', 'cp_ticket_status');
            wp_insert_term('pending', 'cp_ticket_status');
        }
        
        if (!term_exists('low', 'cp_ticket_priority')) {
            wp_insert_term('low', 'cp_ticket_priority');
            wp_insert_term('normal', 'cp_ticket_priority');
            wp_insert_term('high', 'cp_ticket_priority');
        }
    }
    
    /**
     * Register Invoice Post Type
     */
    private static function register_invoice_post_type() {
        $labels = array(
            'name'                  => _x('Invoices', 'Post Type General Name', 'client-portal'),
            'singular_name'         => _x('Invoice', 'Post Type Singular Name', 'client-portal'),
            'menu_name'             => __('Invoices', 'client-portal'),
            'name_admin_bar'        => __('Invoice', 'client-portal'),
            'archives'              => __('Invoice Archives', 'client-portal'),
            'attributes'            => __('Invoice Attributes', 'client-portal'),
            'all_items'             => __('All Invoices', 'client-portal'),
            'add_new_item'          => __('Add New Invoice', 'client-portal'),
            'add_new'               => __('Add New', 'client-portal'),
            'new_item'              => __('New Invoice', 'client-portal'),
            'edit_item'             => __('Edit Invoice', 'client-portal'),
            'update_item'           => __('Update Invoice', 'client-portal'),
            'view_item'             => __('View Invoice', 'client-portal'),
            'view_items'            => __('View Invoices', 'client-portal'),
            'search_items'          => __('Search Invoice', 'client-portal'),
        );
        
        $args = array(
            'label'                 => __('Invoice', 'client-portal'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'author', 'custom-fields'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'client-portal',
            'menu_position'         => 22,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'cp-invoices',
        );
        
        register_post_type('cp_invoice', $args);
        
        // Register invoice status taxonomy
        register_taxonomy('cp_invoice_status', 'cp_invoice', array(
            'label'                 => __('Invoice Status', 'client-portal'),
            'public'                => false,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'hierarchical'          => false,
            'show_admin_column'     => true,
        ));
        
        // Insert default terms
        if (!term_exists('unpaid', 'cp_invoice_status')) {
            wp_insert_term('unpaid', 'cp_invoice_status');
            wp_insert_term('paid', 'cp_invoice_status');
            wp_insert_term('overdue', 'cp_invoice_status');
            wp_insert_term('cancelled', 'cp_invoice_status');
        }
    }
}

// Add admin menu
add_action('admin_menu', function() {
    add_menu_page(
        __('Client Portal', 'client-portal'),
        __('Client Portal', 'client-portal'),
        'manage_options',
        'client-portal',
        'cp_dashboard_page',
        'dashicons-groups',
        6
    );
    
    add_submenu_page(
        'client-portal',
        __('Dashboard', 'client-portal'),
        __('Dashboard', 'client-portal'),
        'manage_options',
        'client-portal',
        'cp_dashboard_page'
    );
    
    add_submenu_page(
        'client-portal',
        __('Settings', 'client-portal'),
        __('Settings', 'client-portal'),
        'manage_options',
        'client-portal-settings',
        'cp_settings_page'
    );
});

function cp_dashboard_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="card">
            <h2>Client Portal Dashboard</h2>
            <p>Manage your client portal from here.</p>
            
            <?php
            $doc_count = wp_count_posts('cp_document');
            $ticket_count = wp_count_posts('cp_ticket');
            $invoice_count = wp_count_posts('cp_invoice');
            ?>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Documents</td>
                        <td><?php echo esc_html($doc_count->publish); ?></td>
                        <td><a href="edit.php?post_type=cp_document" class="button">View All</a></td>
                    </tr>
                    <tr>
                        <td>Tickets</td>
                        <td><?php echo esc_html($ticket_count->publish); ?></td>
                        <td><a href="edit.php?post_type=cp_ticket" class="button">View All</a></td>
                    </tr>
                    <tr>
                        <td>Invoices</td>
                        <td><?php echo esc_html($invoice_count->publish); ?></td>
                        <td><a href="edit.php?post_type=cp_invoice" class="button">View All</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Invoices Section -->
        <div class="card" style="margin-top: 20px;">
            <h2>Recent Invoices</h2>
            <?php
            $recent_invoices = get_posts(array(
                'post_type' => 'cp_invoice',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if ($recent_invoices) {
                echo '<table class="widefat">';
                echo '<thead><tr><th>Invoice</th><th>Amount</th><th>Status</th><th>Client</th><th>Due Date</th><th>Actions</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($recent_invoices as $invoice) {
                    $status_terms = wp_get_post_terms($invoice->ID, 'cp_invoice_status');
                    $author = get_userdata($invoice->post_author);
                    $amount = get_post_meta($invoice->ID, '_cp_invoice_amount', true);
                    $due_date = get_post_meta($invoice->ID, '_cp_invoice_due_date', true);
                    $has_file = !empty(get_post_meta($invoice->ID, '_cp_invoice_file_url', true));
                    
                    $status = !empty($status_terms) ? $status_terms[0]->name : 'Unpaid';
                    $status_color = !empty($status_terms) ? $status_terms[0]->slug : 'unpaid';
                    $author_name = $author ? $author->display_name : 'Unknown';
                    
                    // Status colors
                    $status_colors = array(
                        'unpaid' => '#f0b429',
                        'paid' => '#46b450',
                        'overdue' => '#dc3232',
                        'cancelled' => '#999',
                    );
                    $color = isset($status_colors[$status_color]) ? $status_colors[$status_color] : '#999';
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($invoice->post_title) . '</strong>';
                    if ($has_file) {
                        echo ' <span class="dashicons dashicons-paperclip" style="color: #0073aa; margin-left: 5px;" title="Has attached file"></span>';
                    }
                    echo '</td>';
                    echo '<td><strong style="color: #2271b1;">$' . number_format($amount, 2) . '</strong></td>';
                    echo '<td><span style="padding: 3px 8px; border-radius: 3px; background: ' . esc_attr($color) . '; color: white; font-size: 0.85em;">' . esc_html($status) . '</span></td>';
                    echo '<td>' . esc_html($author_name) . '</td>';
                    echo '<td>' . ($due_date ? esc_html(date_i18n('M j, Y', strtotime($due_date))) : '—') . '</td>';
                    echo '<td>';
                    echo '<a href="post.php?post=' . $invoice->ID . '&action=edit" class="button button-small">Manage Invoice</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p>No invoices found.</p>';
            }
            ?>
        </div>
        
        <!-- Recent Tickets Section -->
        <div class="card" style="margin-top: 20px;">
            <h2>Recent Support Tickets</h2>
            <?php
            $recent_tickets = get_posts(array(
                'post_type' => 'cp_ticket',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if ($recent_tickets) {
                echo '<table class="widefat">';
                echo '<thead><tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Author</th><th>Date</th><th>Actions</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($recent_tickets as $ticket) {
                    $status_terms = wp_get_post_terms($ticket->ID, 'cp_ticket_status');
                    $priority_terms = wp_get_post_terms($ticket->ID, 'cp_ticket_priority');
                    $author = get_userdata($ticket->post_author);
                    
                    $status = !empty($status_terms) ? $status_terms[0]->name : 'Open';
                    $priority = !empty($priority_terms) ? $priority_terms[0]->name : 'Normal';
                    $author_name = $author ? $author->display_name : 'Unknown';
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($ticket->post_title) . '</strong></td>';
                    echo '<td><span style="padding: 3px 8px; border-radius: 3px; background: #0073aa; color: white; font-size: 0.85em;">' . esc_html($status) . '</span></td>';
                    echo '<td><span style="padding: 3px 8px; border-radius: 3px; background: #666; color: white; font-size: 0.85em;">' . esc_html($priority) . '</span></td>';
                    echo '<td>' . esc_html($author_name) . '</td>';
                    echo '<td>' . esc_html(date_i18n('M j, Y', strtotime($ticket->post_date))) . '</td>';
                    echo '<td>';
                    echo '<a href="post.php?post=' . $ticket->ID . '&action=edit" class="button button-primary">Reply to Ticket</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p>No tickets found.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

function cp_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cp_settings');
            do_settings_sections('cp_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}