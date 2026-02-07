<?php
/**
 * Export History Management Interface
 */
?>
<div class="wrap">
    <h1><?php _e('Migration Export History', 'wp-site-migration'); ?></h1>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'deleted'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Export file deleted successfully.', 'wp-site-migration'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php
    $exports_dir = MIGRATION_EXPORTS_DIR;
    $export_files = array();
    
    if (file_exists($exports_dir)) {
        $files = glob($exports_dir . '/*.json');
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            
            // Extract timestamp from filename format: migration-YYYY-MM-DD-HH-MM-SS-random.json
            $parts = explode('-', substr($filename, 10)); // Skip "migration-" prefix
            $timestamp = '';
            if (count($parts) >= 2) {
                $timestamp = substr($parts[0], 0, 4) . '-' . substr($parts[0], 4, 2) . '-' . substr($parts[0], 6, 2) . ' ' . 
                            substr($parts[1], 0, 2) . ':' . substr($parts[1], 2, 2) . ':' . substr($parts[1], 4, 2);
            }
            
            $export_files[] = array(
                'filename' => $filename,
                'filepath' => $filepath,
                'fileurl' => content_url(basename($exports_dir) . '/' . $filename),
                'filesize' => filesize($filepath),
                'created' => $timestamp
            );
        }
    }
    
    usort($export_files, function($a, $b) {
        return strcmp($b['filename'], $a['filename']); // Newest first
    });
    ?>
    
    <div class="card">
        <h2><?php _e('Existing Export Files', 'wp-site-migration'); ?></h2>
        
        <?php if (empty($export_files)): ?>
            <p class="description"><?php _e('No export files found. Create your first export using the Migration tab.', 'wp-site-migration'); ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Filename', 'wp-site-migration'); ?></th>
                            <th><?php _e('Date Created', 'wp-site-migration'); ?></th>
                            <th><?php _e('File Size', 'wp-site-migration'); ?></th>
                            <th><?php _e('Actions', 'wp-site-migration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($export_files as $file): ?>
                            <tr>
                                <td>
                                    <code><?php echo esc_html($file['filename']); ?></code><br>
                                    <small class="description">
                                        <?php _e('Path:', 'wp-site-migration'); ?> 
                                        <code><?php echo esc_html($file['filepath']); ?></code>
                                    </small>
                                </td>
                                <td><?php echo !empty($file['created']) ? esc_html($file['created']) : '-'; ?></td>
                                <td><?php echo size_format($file['filesize'], 2); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($file['fileurl']); ?>" class="button button-small" target="_blank">
                                        <?php _e('Download', 'wp-site-migration'); ?>
                                    </a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this export file?', 'wp-site-migration'); ?>');">
                                        <?php wp_nonce_field('delete_migration_export', '_wpnonce'); ?>
                                        <input type="hidden" name="file" value="<?php echo esc_attr($file['filename']); ?>">
                                        <button type="submit" class="button button-small delete">
                                            <?php _e('Delete', 'wp-site-migration'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <h3><?php _e('Storage Directory Information', 'wp-site-migration'); ?></h3>
        
        <p><strong><?php _e('Export Storage Location:', 'wp-site-migration'); ?></strong> <code><?php echo $exports_dir; ?></code></p>
        
        <?php if (file_exists($exports_dir)): ?>
            <p class="success">
                <strong><?php _e('Status:', 'wp-site-migration'); ?></strong> 
                <?php _e('Directory exists and is writable', 'wp-site-migration'); ?>
            </p>
            
            <?php
            $directory_size = 0;
            if ($handle = opendir($exports_dir)) {
                while (($entry = readdir($handle)) !== false) {
                    if ($entry != '.' && $entry != '..') {
                        if (is_file($exports_dir . '/' . $entry)) {
                            $directory_size += filesize($exports_dir . '/' . $entry);
                        }
                    }
                }
                closedir($handle);
            }
            ?>
            
            <p><strong><?php _e('Total Export Storage Used:', 'wp-site-migration'); ?></strong> <?php echo size_format($directory_size, 2); ?> 
               (<?php echo count($export_files); ?> <?php _e('files', 'wp-site-migration'); ?>)</p>
        <?php else: ?>
            <p class="error">
                <strong><?php _e('Warning:', 'wp-site-migration'); ?></strong> 
                <?php _e('Export directory does not exist or is not writable.', 'wp-site-migration'); ?>
            </p>
        <?php endif; ?>
        
        <hr>
        
        <h3><?php _e('Management Actions', 'wp-site-migration'); ?></h3>
        
        <div class="button-group">
            <a href="<?php echo admin_url('tools.php?page=site-migration'); ?>" class="button button-secondary">
                <?php _e('Create New Export', 'wp-site-migration'); ?>
            </a>
            
            <?php if (!empty($export_files)): ?>
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('bulk_delete_exports', '_wpnonce'); ?>
                    <button type="submit" class="button button-danger" onclick="return confirm('<?php _e('Are you sure you want to delete ALL export files? This cannot be undone!', 'wp-site-migration'); ?>');">
                        <?php _e('Delete All Exports', 'wp-site-migration'); ?>
                    </button>
                </form>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=bulk_download_exports'), 'bulk_download_exports'); ?>" class="button button-secondary">
                    <?php _e('Download All Exports (ZIP)', 'wp-site-migration'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table-responsive { overflow-x: auto; }
.card { margin-top: 20px; padding: 20px; border-radius: 8px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.code-block { background-color: #f6f7f7; padding: 15px; border-radius: 4px; font-family: monospace; overflow-x: auto; margin: 10px 0; }
.button-group { margin-top: 20px; }
</style>
