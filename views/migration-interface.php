<?php
/**
 * Migration Export Interface
 */
?>
<div class="wrap">
    <h1><?php _e('Site Migration', 'wp-site-migration'); ?></h1>
    
    <?php if (isset($_GET['status'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php 
                switch($_GET['status']) {
                    case 'export_created':
                        printf(__('Export file created successfully! <a href="%s">Download Export</a>', 'wp-site-migration'), esc_url($_GET['download_url']));
                        break;
                    case 'urls_updated':
                        _e('URLs updated successfully!', 'wp-site-migration');
                        break;
                }
            ?></p>
        </div>
    <?php endif; ?>
    
    <nav class="nav-tab-wrapper">
        <a href="#export" class="nav-tab nav-tab-active" data-tab="tab-export"><?php _e('Export Site', 'wp-site-migration'); ?></a>
        <a href="#import" class="nav-tab" data-tab="tab-import"><?php _e('Import Data', 'wp-site-migration'); ?></a>
        <a href="#url-updates" class="nav-tab" data-tab="tab-url-updates"><?php _e('URL Updates', 'wp-site-migration'); ?></a>
    </nav>
    
    <!-- Export Tab -->
    <div id="tab-export" class="migration-tab-content">
        <div class="card">
            <h2><?php _e('Create Migration Export', 'wp-site-migration'); ?></h2>
            <p><?php _e('Generate a complete backup of your site content and configuration. Files are stored permanently in wp-content/migration-exports/', 'wp-site-migration'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('site-migration-action', 'wp_site_migration_nonce'); ?>
                
                <div class="form-group">
                    <label><strong><?php _e('Export Type:', 'wp-site-migration'); ?></strong></label>
                    <select name="export_type" id="export_type">
                        <option value="full"><?php _e('Full Site Export (Content + Configuration)', 'wp-site-migration'); ?></option>
                        <option value="content_only"><?php _e('Content Only (Posts, Pages, Media)', 'wp-site-migration'); ?></option>
                        <option value="configuration_only"><?php _e('Configuration Only (Options, Themes)', 'wp-site-migration'); ?></option>
                    </select>
                </div>
                
                <?php submit_button(__('Create Export File', 'wp-site-migration'), 'button-primary', 'create_export'); ?>
            </form>
            
            <hr>
            
            <h3><?php _e('Export Storage Location:', 'wp-site-migration'); ?></h3>
            <p><code><?php echo MIGRATION_EXPORTS_DIR; ?></code></p>
            <p class="description">
                <?php _e('All export files are saved to this directory and can be managed from the "Export History" tab.', 'wp-site-migration'); ?>
            </p>
        </div>
    </div>
    
    <!-- Import Tab -->
    <div id="tab-import" class="migration-tab-content" style="display: none;">
        <div class="card">
            <h2><?php _e('Import Migration Data', 'wp-site-migration'); ?></h2>
            <p><?php _e('Restore site content and configuration from a previously exported migration file.', 'wp-site-migration'); ?></p>
            
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('site-migration-action', 'wp_site_migration_nonce'); ?>
                
                <div class="form-group">
                    <label for="migration_file"><?php _e('Select Migration File:', 'wp-site-migration'); ?></label>
                    <input type="file" name="migration_file" id="migration_file" accept=".json" required>
                </div>
                
                <?php submit_button(__('Import Migration Data', 'wp-site-migration'), 'button-primary'); ?>
            </form>
            
            <div class="migration-warning">
                <div class="notice notice-warning inline">
                    <p><strong><?php _e('Warning:', 'wp-site-migration'); ?></strong> <?php _e('Importing will overwrite existing data. Always backup your site before importing.', 'wp-site-migration'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- URL Updates Tab -->
    <div id="tab-url-updates" class="migration-tab-content" style="display: none;">
        <div class="card">
            <h2><?php _e('Update Site URLs', 'wp-site-migration'); ?></h2>
            <p><?php _e('Replace all occurrences of an old URL with a new one throughout your site content.', 'wp-site-migration'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('site-migration-action', 'wp_site_migration_nonce'); ?>
                
                <div class="form-group">
                    <label for="old_url"><?php _e('Old URL:', 'wp-site-migration'); ?></label>
                    <input type="url" name="old_url" id="old_url" required placeholder="https://olddomain.com">
                </div>
                
                <div class="form-group">
                    <label for="new_url"><?php _e('New URL:', 'wp-site-migration'); ?></label>
                    <input type="url" name="new_url" id="new_url" required placeholder="https://newdomain.com">
                </div>
                
                <?php submit_button(__('Update URLs', 'wp-site-migration'), 'button-primary'); ?>
            </form>
        </div>
    </div>
</div>

<style>
.migration-tab-content { display: block; }
.card { margin-top: 20px; padding: 20px; border-radius: 8px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.form-group { margin-bottom: 15px; }
.migration-warning .notice { margin: 20px 0; }
.code-block { background-color: #f6f7f7; padding: 15px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
</style>

<script>
jQuery(document).ready(function($){
    $('.nav-tab').click(function(e){
        e.preventDefault();
        var tab = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.migration-tab-content').hide();
        $(tab).show();
    });
});
</script>
