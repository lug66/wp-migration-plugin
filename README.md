# WordPress Site Migration Plugin

A comprehensive WordPress plugin for migrating websites with persistent export storage.

## Overview

This plugin provides complete site migration functionality including:

- **Export**: Create full site backups (content + configuration)
- **Import**: Restore sites from previously exported data
- **URL Updates**: Bulk update URLs across your entire site
- **Persistent Storage**: Export files are saved to `wp-content/migration-exports/` for later use

## Installation

1. Upload the plugin folder to `/wp-content/plugins/wp-migration-plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the migration tools under **Tools → Migration**

## Usage

### Creating an Export

1. Navigate to **Tools → Migration**
2. Click on the "Export Site" tab
3. Choose your export type:
   - **Full Site Export**: Complete backup (content + configuration)
   - **Content Only**: Posts, pages, media only
   - **Configuration Only**: Options and themes only
4. Click "Create Export File"
5. Your export will be saved to `wp-content/migration-exports/` with a unique filename

### Managing Exports

1. Navigate to **Tools → Export History**
2. View all existing export files with their:
   - Filenames and storage paths
   - Creation dates
   - File sizes
3. Actions available for each export:
   - Download individual files
   - Delete specific exports
   - Bulk operations (delete all, download all as ZIP)

### Importing Data

1. Navigate to the "Import Data" tab
2. Select a previously exported migration file
3. Review import options
4. Click "Import Migration Data"
5. **Warning**: This will overwrite existing data

### URL Updates

1. Use the "URL Updates" tab for domain migrations or HTTPS transitions
2. Enter your old URL and new URL
3. Click "Update URLs" to replace all occurrences throughout your site

## Storage Location

All export files are stored in:
```
wp-content/migration-exports/
```

This directory is automatically created when the plugin needs it, and contains all generated migration files with timestamps and unique identifiers.

### File Naming Convention
Files follow this pattern:
```
migration-YYYY-MM-DD-HH-MM-SS-randomstring.json
```

Example:
```
migration-2024-03-15-14-30-22-abcd1234efgh.json
```

## Features

### Export Capabilities
- Complete content export (posts, pages, media)
- User and role migration
- Site configuration backup (options, themes)
- Metadata preservation
- Automatic timestamping

### Import Capabilities  
- JSON-based import system
- Conflict resolution for existing data
- Transaction support for data integrity
- User mapping between sites

### URL Management
- Comprehensive URL replacement in content
- Support for HTTP to HTTPS migrations
- Configuration updates (home/siteurl)
- Safe search and replace operations

## Technical Details

### File Permissions
The plugin requires write permissions to `wp-content/` directory. The export folder will be created automatically.

### Security Features
- Nonce verification on all actions
- Capability checks (`manage_options`)
- Sanitization of user inputs
- Transaction support for database consistency

### REST API Endpoints

#### Create Persistent Export
```
POST /wp-json/migration/v1/export/persistent
```

#### List All Exports  
```
GET /wp-json/migration/v1/exports/list
```

## Troubleshooting

### "Failed to save migration file"
- Check that `wp-content/` directory is writable
- Verify disk space is available

### "File not found" during download
- Ensure the export file exists in `wp-content/migration-exports/`
- Check file permissions on the exported files

### Import conflicts
- The plugin will skip existing users with same email/login
- Posts are matched by slug; existing posts will be updated

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- JSON extension enabled
- Write permissions to `wp-content/` directory

## Changelog

### Version 1.0.0
- Initial release
- Complete site export/import functionality
- Persistent storage in wp-content/migration-exports/
- URL update tools
- REST API integration
- Export history management

## License

This plugin is licensed under the GPL2 license.

## Support

For issues, feature requests, or contributions:
[GitHub Repository](https://github.com/yourusername/wp-migration-plugin)
