/**
 * WordPress Site Migration Plugin
 * 
 * This plugin provides complete site migration functionality with persistent storage.
 * Export files are saved to wp-content/migration-exports/ directory.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MIGRATION_PLUGIN_VERSION', '1.0.0');
define('MIGRATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MIGRATION_EXPORTS_DIR', WP_CONTENT_DIR . '/migration-exports');

/**
 * Main Plugin Class
 */
class WP_Site_Migration_Plugin {
    
    private static $instance = null;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_migration_menu'));
        add_action('wp_ajax_download_migration_file', array($this, 'handle_file_download'));
        add_action('wp_ajax_delete_migration_export', array($this, 'handle_file_deletion'));
        add_action('wp_ajax_bulk_download_exports', array($this, 'handle_bulk_download'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function add_migration_menu() {
        // Main migration menu
        add_submenu_page(
            'tools.php',
            __('Site Migration', 'wp-site-migration'),
            __('Migration', 'wp-site-migration'),
            'manage_options',
            'site-migration',
            array($this, 'render_migration_interface')
        );
        
        // Export history submenu
        add_submenu_page(
            'tools.php',
            __('Export History', 'wp-site-migration'),
            __('Export History', 'wp-site-migration'),
            'manage_options',
            'site-migration-exports',
            array($this, 'render_export_history')
        );
    }
    
    public function get_exports_directory() {
        if (!file_exists(MIGRATION_EXPORTS_DIR)) {
            wp_mkdir_p(MIGRATION_EXPORTS_DIR);
        }
        
        // Set proper permissions
        @chmod(MIGRATION_EXPORTS_DIR, 0755);
        
        return MIGRATION_EXPORTS_DIR;
    }
    
    /**
     * Create persistent export with storage in wp-content/migration-exports/
     */
    public function create_persistent_export($type = 'full') {
        $export_data = array();
        
        // Collect data based on type
        if ($type === 'full' || $type === 'content_only') {
            $export_data['wordpress'] = $this->export_wordpress_content();
        }
        
        if ($type === 'full' || $type === 'configuration_only') {
            $export_data['configuration'] = $this->export_configuration();
        }
        
        // Add metadata
        $export_data['metadata'] = array(
            'generated_at' => current_time('mysql'),
            'site_url' => home_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'export_type' => $type,
            'export_version' => '1.0'
        );
        
        // Get export directory
        $export_dir = $this->get_exports_directory();
        
        // Create unique filename with timestamp and random string
        $timestamp = current_time('Y-m-d-H-i-s');
        $random = wp_generate_password(8, false);
        $filename = "migration-{$timestamp}-{$random}.json";
        $filepath = $export_dir . '/' . $filename;
        
        // Save export to file
        $result = file_put_contents($filepath, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result === false) {
            return array(
                'success' => false,
                'error' => __('Failed to save migration file', 'wp-site-migration')
            );
        }
        
        // Return success with file information
        $file_url = content_url(basename($export_dir) . '/' . $filename);
        
        return array(
            'success' => true,
            'file_path' => $filepath,
            'file_name' => $filename,
            'file_size' => $result,
            'file_url' => $file_url,
            'download_url' => wp_nonce_url(admin_url('admin-ajax.php?action=download_migration_file&file=' . urlencode($filename)), 'download_migration_file'),
            'created_at' => $timestamp
        );
    }
    
    /**
     * Export WordPress content (posts, pages, media)
     */
    private function export_wordpress_content() {
        global $wpdb;
        
        // Posts and pages (excluding revisions and nav menu items)
        $posts = $wpdb->get_results(
            "SELECT ID, post_title, post_content, post_excerpt, post_status, 
                    post_type, post_name, post_author, post_date, post_date_gmt,
                    menu_order, comment_status, ping_status, guid
             FROM {$wpdb->posts} 
             WHERE post_status IN ('publish', 'draft', 'trash', 'auto-draft') 
               AND post_type NOT IN ('revision', 'nav_menu_item')
             ORDER BY ID",
            ARRAY_A
        );
        
        // Add terms to posts
        $posts_with_terms = array();
        foreach ($posts as $post) {
            $terms = wp_get_object_terms($post['ID'], array('category', 'post_tag'), array('fields' => 'slugs'));
            $post['terms'] = array_values(array_map(function($term) {
                return array('slug' => $term);
            }, $terms));
            
            // Add featured image URL
            $featured_image = get_post_thumbnail_id($post['ID']);
            if ($featured_image) {
                $post['featured_image'] = wp_get_attachment_url($featured_image);
            }
            
            $posts_with_terms[] = $post;
        }
        
        // Users with roles and meta (limited for export)
        $users = array();
        $user_query = new WP_User_Query(array('fields' => 'all', 'number' => 50));
        foreach ($user_query->get_results() as $user) {
            $users[] = array(
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
                'meta' => get_user_meta($user->ID)
            );
        }
        
        // Media attachments
        $media = $wpdb->get_results(
            "SELECT ID, post_title, guid, post_mime_type, post_content, post_parent
             FROM {$wpdb->posts} 
             WHERE post_type = 'attachment'",
            ARRAY_A
        );
        
        return array(
            'posts' => $posts_with_terms,
            'users' => $users,
            'media' => $media,
            'total_posts' => count($posts_with_terms),
            'total_users' => count($users),
            'total_media' => count($media)
        );
    }
    
    /**
     * Export site configuration
     */
    private function export_configuration() {
        global $wpdb;
        
        // Essential options to export
        $essential_options = array(
            'blogname', 'blogdescription', 'siteurl', 'home',
            'users_can_register', 'default_role',
            'permalink_structure', 'category_base', 'tag_base',
            'thumbnail_size_w', 'thumbnail_size_h', 'medium_size_w', 'medium_size_h'
        );
        
        $options = array();
        foreach ($essential_options as $option_name) {
            $value = get_option($option_name);
            if ($value !== false) {
                $options[$option_name] = maybe_unserialize($value);
            }
        }
        
        // Get theme settings
        $theme_mods = array();
        $current_theme = get_option('stylesheet');
        if ($current_theme) {
            $mods = get_theme_mods($current_theme);
            if (is_array($mods)) {
                $theme_mods[$current_theme] = $mods;
            }
        }
        
        return array(
            'options' => $options,
            'themes' => $theme_mods,
            'database_version' => get_option('db_version'),
            'wp_version' => get_bloginfo('version')
        );
    }
    
    /**
     * Handle file download via AJAX
     */
    public function handle_file_download() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'wp-site-migration'));
        }
        
        $file = sanitize_file_name($_GET['file'] ?? '');
        if (empty($file)) {
            wp_send_json_error(__('No file specified', 'wp-site-migration'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'download_migration_file')) {
            wp_send_json_error(__('Invalid request', 'wp-site-migration'));
        }
        
        $filepath = $this->get_exports_directory() . '/' . $file;
        
        if (!file_exists($filepath)) {
            wp_send_json_error(__('File not found', 'wp-site-migration'));
        }
        
        // Send download headers
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    /**
     * Handle file deletion via AJAX
     */
    public function handle_file_deletion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'wp-site-migration'));
        }
        
        $file = sanitize_file_name($_POST['file'] ?? '');
        if (empty($file)) {
            wp_send_json_error(__('No file specified', 'wp-site-migration'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_migration_export')) {
            wp_send_json_error(__('Invalid request', 'wp-site-migration'));
        }
        
        $filepath = $this->get_exports_directory() . '/' . $file;
        
        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                wp_send_json_success(__('File deleted successfully', 'wp-site-migration'));
            } else {
                wp_send_json_error(__('Failed to delete file', 'wp-site-migration'));
            }
        } else {
            wp_send_json_error(__('File not found', 'wp-site-migration'));
        }
    }
    
    /**
     * Handle bulk download of all exports as ZIP
     */
    public function handle_bulk_download() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'wp-site-migration'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'bulk_download_exports')) {
            wp_send_json_error(__('Invalid request', 'wp-site-migration'));
        }
        
        $exports_dir = $this->get_exports_directory();
        if (!file_exists($exports_dir)) {
            wp_die(__('No export files found', 'wp-site-migration'));
        }
        
        // Create temporary ZIP file
        $zip_filename = 'migration-backup-' . current_time('Y-m-d') . '.zip';
        $zip_filepath = $exports_dir . '/' . $zip_filename;
        
        $zip = new ZipArchive();
        if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE) {
            wp_die(__('Could not create ZIP archive', 'wp-site-migration'));
        }
        
        // Add all JSON files to ZIP
        $json_files = glob($exports_dir . '/*.json');
        foreach ($json_files as $file) {
            if (basename($file) !== $zip_filename) { // Don't include the zip itself
                $relative_name = basename($file);
                $zip->addFile($file, $relative_name);
            }
        }
        
        $zip->close();
        
        // Send ZIP for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_filepath));
        readfile($zip_filepath);
        
        // Clean up
        unlink($zip_filepath);
        exit;
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        // Export endpoint
        register_rest_route('migration/v1', '/export/persistent', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_create_persistent_export'),
            'permission_callback' => array($this, 'rest_manage_options_permission')
        ));
        
        // Get all exports endpoint
        register_rest_route('migration/v1', '/exports/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_list_exports'),
            'permission_callback' => array($this, 'rest_manage_options_permission')
        ));
        
        // Export by filename endpoint
        register_rest_route('migration/v1', '/export/(?P<filename>[a-zA-Z0-9\-]+\.json)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_export'),
            'permission_callback' => array($this, 'rest_manage_options_permission')
        ));
    }
    
    /**
     * REST callback for creating persistent export
     */
    public function rest_create_persistent_export($request) {
        $type = $request->get_param('type') ?: 'full';
        
        if (!in_array($type, array('full', 'content_only', 'configuration_only'))) {
            return new WP_Error('invalid_type', __('Invalid export type', 'wp-site-migration'), array('status' => 400));
        }
        
        $result = $this->create_persistent_export($type);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 201);
        } else {
            return new WP_Error('export_failed', $result['error'], array('status' => 500));
        }
    }
    
    /**
     * REST callback for listing exports
     */
    public function rest_list_exports() {
        $exports_dir = $this->get_exports_directory();
        
        if (!file_exists($exports_dir)) {
            return new WP_REST_Response(array(), 200);
        }
        
        $files = glob($exports_dir . '/*.json');
        $export_list = array();
        
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            
            // Try to get metadata from file
            $metadata = array();
            if (file_exists($filepath)) {
                $content = json_decode(file_get_contents($filepath), true);
                if (isset($content['metadata'])) {
                    $metadata = $content['metadata'];
                }
            }
            
            $export_list[] = array(
                'file_name' => $filename,
                'file_path' => $filepath,
                'file_url' => content_url(basename($exports_dir) . '/' . $filename),
                'file_size' => filesize($filepath),
                'created_at' => $metadata['generated_at'] ?? null,
                'export_type' => $metadata['export_type'] ?? 'full'
            );
        }
        
        return new WP_REST_Response(array_values($export_list), 200);
    }
    
    /**
     * REST callback for getting specific export
     */
    public function rest_get_export($request) {
        $filename = $request->get_param('filename');
        $filepath = $this->get_exports_directory() . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return new WP_Error('file_not_found', __('File not found', 'wp-site-migration'), array('status' => 404));
        }
        
        // Get file content
        $content = json_decode(file_get_contents($filepath), true);
        
        return new WP_REST_Response(array(
            'filename' => $filename,
            'data' => $content,
            'file_size' => filesize($filepath),
            'download_url' => wp_nonce_url(admin_url('admin-ajax.php?action=download_migration_file&file=' . urlencode($filename)), 'download_migration_file')
        ), 200);
    }
    
    /**
     * Permission callback for REST API
     */
    public function rest_manage_options_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Render migration interface
     */
    public function render_migration_interface() {
        include_once MIGRATION_PLUGIN_DIR . 'views/migration-interface.php';
    }
    
    /**
     * Render export history interface
     */
    public function render_export_history() {
        include_once MIGRATION_PLUGIN_DIR . 'views/export-history.php';
    }
}

// Initialize plugin
function wp_migration_plugin_init() {
    return WP_Site_Migration_Plugin::get_instance();
}

// Run plugin
wp_migration_plugin_init();
