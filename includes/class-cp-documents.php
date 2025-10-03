<?php
/**
 * Document Management Helper
 * File: includes/class-cp-documents.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class CP_Documents {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_cp_document', array($this, 'save_meta_boxes'));
        add_filter('manage_cp_document_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cp_document_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cp_document_details',
            __('Document Details', 'client-portal'),
            array($this, 'render_meta_box'),
            'cp_document',
            'normal',
            'high'
        );
    }
    
    /**
     * Render Meta Box
     */
    public function render_meta_box($post) {
        wp_nonce_field('cp_document_meta_box', 'cp_document_meta_box_nonce');
        
        $filename = get_post_meta($post->ID, '_cp_filename', true);
        $mime_type = get_post_meta($post->ID, '_cp_mime_type', true);
        $file_size = get_post_meta($post->ID, '_cp_file_size', true);
        $file_url = get_post_meta($post->ID, '_cp_file_url', true);
        $file_path = get_post_meta($post->ID, '_cp_file_path', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('File Name', 'client-portal'); ?></label></th>
                <td><strong><?php echo esc_html($filename); ?></strong></td>
            </tr>
            <tr>
                <th><label><?php _e('MIME Type', 'client-portal'); ?></label></th>
                <td><?php echo esc_html($mime_type); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('File Size', 'client-portal'); ?></label></th>
                <td><?php echo esc_html(size_format($file_size, 2)); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('File Path', 'client-portal'); ?></label></th>
                <td><code><?php echo esc_html($file_path); ?></code></td>
            </tr>
            <?php if ($file_url): ?>
            <tr>
                <th><label><?php _e('Actions', 'client-portal'); ?></label></th>
                <td>
                    <a href="<?php echo esc_url($file_url); ?>" class="button button-primary" download>
                        <?php _e('Download File', 'client-portal'); ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <p class="description">
            <?php _e('This document was uploaded via the Client Portal. To replace it, delete this document and have the client upload a new one.', 'client-portal'); ?>
        </p>
        <?php
    }
    
    /**
     * Save Meta Boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['cp_document_meta_box_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['cp_document_meta_box_nonce'], 'cp_document_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Meta is saved via API, this is just for manual admin edits
    }
    
    /**
     * Set Custom Admin Columns
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['file_name'] = __('File Name', 'client-portal');
        $new_columns['file_type'] = __('Type', 'client-portal');
        $new_columns['file_size'] = __('Size', 'client-portal');
        $new_columns['author'] = $columns['author'];
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Custom Column Content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'file_name':
                $filename = get_post_meta($post_id, '_cp_filename', true);
                echo '<strong>' . esc_html($filename) . '</strong>';
                break;
                
            case 'file_type':
                $mime = get_post_meta($post_id, '_cp_mime_type', true);
                $extension = $this->get_file_extension($mime);
                echo '<span style="padding: 3px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px; font-weight: 600;">' . esc_html(strtoupper($extension)) . '</span>';
                break;
                
            case 'file_size':
                $size = get_post_meta($post_id, '_cp_file_size', true);
                echo esc_html(size_format($size, 2));
                break;
        }
    }
    
    /**
     * Get File Extension from MIME Type
     */
    private function get_file_extension($mime) {
        $mime_map = array(
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
        );
        
        return isset($mime_map[$mime]) ? $mime_map[$mime] : 'file';
    }
}

// Initialize the class
new CP_Documents();