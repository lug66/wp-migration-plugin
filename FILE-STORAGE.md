# WordPress Migration Plugin - File Storage Information

## Where Files Are Saved

### Primary Export Location
All migration export files are saved to:
```
wp-content/migration-exports/
```

This directory is automatically created when the plugin needs it, and contains all generated migration files.

## Directory Structure

```
wp-content/
├── migration-exports/          ← Your migration files go here
│   ├── migration-2024-03-15-14-30-22-abcd1234efgh.json  ← Export #1
│   ├── migration-2024-03-16-09-15-45-wxyz5678ijkl.json  ← Export #2
│   └── migration-backup-2024-03-15.zip                  ← Bulk download archive
```

## File Naming Convention

Migration files follow this pattern:
```
migration-YYYY-MM-DD-HH-MM-SS-randomstring.json
```

Example:
```
migration-2024-03-15-14-30-22-abcd1234efgh.json
```

This format includes:
- **Date and time** when the export was created
- **Random string** to ensure uniqueness

## File Contents

Each `.json` file contains:

### 1. WordPress Content
- All posts, pages, and custom post types
- Users and their roles/permissions  
- Media attachments (metadata)
- Taxonomy terms and relationships

### 2. Configuration Data
- Site options (blogname, siteurl, etc.)
- Theme settings and customizer options
- Database version information
- WordPress version details

### 3. Metadata
- Export timestamp
- Export type (full/content_only/configuration_only)
- Exported by user information
- PHP version information

## File Storage Details

### Automatic Directory Creation
The plugin automatically creates the `wp-content/migration-exports/` directory when:
1. First export is created
2. Plugin needs to list existing exports
3. Bulk download functionality is used

### Permissions
- **Directory**: 755 (rwxr-xr-x)
- **Files**: 644 (rw-r--r--)
- **Owner**: WordPress process user (www-data, apache, etc.)

## How to Access Exported Files

### Via WordPress Admin
1. Navigate to **Tools → Migration**
2. Click on the **"Export Site"** tab
3. Create a new export - it will be saved to `wp-content/migration-exports/`
4. The file URL is displayed in the success message

### Via Export History
1. Go to **Tools → Export History**
2. View all existing exports with their paths
3. Download individual files or download all as ZIP

### Direct File Access
```bash
# Navigate to the export directory
cd /path/to/your/wordpress/wp-content/migration-exports/

# List exported files
ls -la migration-*.json

# View file contents (requires jq)
cat migration-2024-03-15-14-30-22-abcd1234efgh.json | jq '.'

# Download a specific export
scp user@server:/path/to/wordpress/wp-content/migration-exports/migration-file.json ./local-download.json
```

## Storage Management

### Manual File Cleanup
To remove old exports manually:
```bash
# Remove files older than 30 days
find wp-content/migration-exports/ -name "migration-*.json" -mtime +30 -delete

# List all export files with sizes
ls -lh wp-content/migration-exports/
```

### Storage Limits
The plugin includes:
- **Bulk operations** to manage multiple exports
- **File deletion** from the WordPress admin
- **ZIP archiving** for easy transfer

## Security Considerations

1. **Directory Permissions**: The export directory uses standard 755 permissions
2. **File Access**: Files are served through WordPress AJAX handlers with capability checks
3. **Nonce Protection**: All download/delete actions require valid nonces
4. **User Capabilities**: Only users with `manage_options` can access migration features

## Troubleshooting Storage Issues

### "Failed to save migration file" Error
1. Check if directory exists: `ls wp-content/migration-exports/`
2. Verify permissions: `chmod 755 wp-content/migration-exports/`
3. Check disk space: `df -h /path/to/wordpress/wp-content/`

### Files Not Visible in Admin
1. Confirm files exist in the directory
2. Check file permissions (should be 644)
3. Verify file naming follows pattern: `migration-*.json`

### Large File Handling
For very large exports:
- Use **"Content Only"** export type to exclude configuration
- Consider using bulk ZIP download for multiple files
- Monitor PHP memory limits and execution time

## Technical Implementation Details

### Directory Path Definition
```php
define('MIGRATION_EXPORTS_DIR', WP_CONTENT_DIR . '/migration-exports');
```

### Directory Creation Logic
```php
if (!file_exists(MIGRATION_EXPORTS_DIR)) {
    wp_mkdir_p(MIGRATION_EXPORTS_DIR);
}
@chmod(MIGRATION_EXPORTS_DIR, 0755);
```

### File Saving Process
1. Collect migration data (WordPress content + configuration)
2. Generate unique filename with timestamp
3. Save to `wp-content/migration-exports/`
4. Set appropriate file permissions
5. Return success with file path and download URL

## Backup Strategy

For production environments, consider:

1. **Regular exports**: Schedule automatic exports during low-traffic periods
2. **Remote storage**: Copy export files to external storage (S3, Google Cloud)
3. **Version control**: Keep last N exports for rollback capability
4. **Monitoring**: Set up alerts for storage usage thresholds

## Summary

**Primary Storage Location**: `wp-content/migration-exports/`

This directory contains:
- All migration export JSON files
- Bulk download ZIP archives  
- Export metadata and timestamps

The plugin automatically manages this storage, creating the directory when needed and saving files with descriptive names that include creation timestamps for easy identification.
