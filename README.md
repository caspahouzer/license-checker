# License Manager Module

License module with hook-based admin menu integration for WordPress plugins.

## Namespace

`SLK\LicenseChecker`

## Classes

-   **LicenseChecker** - Main singleton controller for license management
-   **LicenseHelper** - HTTP API client for license server communication
-   **LicenseAdminPage** - Admin interface renderer for license page

## Features

-   ✅ License activation/deactivation/validation
-   ✅ **Automatic validation every 12 hours** (transient-based)
-   ✅ REST API integration with license server
-   ✅ WordPress admin UI integration
-   ✅ Secure form handling with nonces
-   ✅ Data storage via WordPress Options API
-   ✅ **Hook-based menu registration** (new architecture)
-   ✅ **Separate licenses per plugin** with unique option names

## API Configuration

-   **Base URL:** `https://slk-communications.de/`
-   **Endpoints:**
    -   `POST /activate` - Activate license
    -   `POST /deactivate` - Deactivate license
    -   `POST /validate` - Validate license

## Installation & Setup

### 1. Instantiating LicenseChecker

In your plugin main file or bootstrap:

```php
// In your plugin file (e.g., slk-my-plugin.php)
if (file_exists(__DIR__ . '/modules/LicenseChecker/LicenseChecker.php')) {
    require_once __DIR__ . '/modules/LicenseChecker/LicenseChecker.php';

    if (class_exists('\SLK\LicenseChecker\LicenseChecker')) {
        // Instantiate with your own text domain
        \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
    }
}
```

### 2. Hook-based Menu Registration

The module automatically triggers the `TEXTDOMAIN_WITH_UNDERSCORES_admin_menu` hook. Register a handler in your admin setup:

```php
// In the admin_menu hook or later
do_action('slk_content_bitch_admin_menu', $parent_slug);
```

**Or simpler:** Let the fallback system do it automatically - if no handler is registered, it will handle it itself!

## Usage

### Check License Status via PHP

```php
// Check license for your plugin
if (\SLK\LicenseChecker\LicenseChecker::is_active('my-plugin-domain')) {
    // License is active for your plugin
} else {
    // License is not activated or expired
}

// Or use the instance directly
$license = \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
if ($license->get_license_status() === 'active') {
    // Your plugin is licensed
}
```

### Access Admin Page

The license admin page is automatically registered as a submenu under the parent menu:

```
Admin Menu
└── Your Menu
    ├── Menu Item 1
    ├── Menu Item 2
    └── License (at the end)  ← Automatically positioned
```

### Manual Registration (optional)

If you need more control:

```php
$license_manager = \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
$license_manager->register_submenu('edit.php?post_type=my_cpt');
```

## Option Names

Each plugin stores its license data with a unique prefix:

```
slk_{plugin_slug}_license_checker_license_key
slk_{plugin_slug}_license_checker_license_status
slk_{plugin_slug}_license_checker_activation_hash
slk_{plugin_slug}_license_checker_license_counts
```

**Example for `slk-content-bitch`:**
```
slk_content_bitch_license_checker_license_key
slk_content_bitch_license_checker_license_status
slk_content_bitch_license_checker_activation_hash
slk_content_bitch_license_checker_license_counts
```

## Hooks & Actions

### `slk_register_license_submenu`

Triggers license submenu registration:

```php
do_action('slk_register_license_submenu', $parent_slug, $license_manager);
```

**Parameters:**
- `$parent_slug` (string) - Parent menu slug (e.g., 'edit.php?post_type=my_cpt')
- `$license_manager` (LicenseChecker) - LicenseChecker instance

**Example:**
```php
add_action('slk_register_license_submenu', function($parent_slug, $license_manager) {
    $license_manager->register_submenu($parent_slug);
}, 10, 2);
```

### `slk_register_license_submenu_done_{option_prefix}`

Triggers after successful submenu registration:

```php
do_action('slk_register_license_submenu_done_slk_content_bitch_license_checker');
```

## AJAX Actions

JavaScript communication uses the following AJAX actions (varies per plugin):

- **slk-license-manager:** `slk_license_manager_manage_license`
- **slk-content-bitch:** `slk_content_bitch_manage_license`

These are automatically registered by the module based on the plugin context.

## LicenseChecker Class Methods

### Public Methods

```php
// Get singleton instance
$instance = LicenseChecker::instance($text_domain = 'slk-license-checker');

// Activate license
$result = $instance->activate_license('license-key-here');

// Deactivate license
$result = $instance->deactivate_license($license_key, $activation_hash);

// Validate license
$result = $instance->validate_license($license_key);

// Get license status ('active', 'inactive', 'invalid')
$status = $instance->get_license_status();

// Get activation counts
$counts = $instance->get_license_counts(); // ['activated' => 1, 'limit' => 5]

// Register admin menu (orchestrates hook-based registration)
$instance->add_admin_menu($parent_slug);

// Register submenu directly
$instance->register_submenu($parent_slug);

// Render license page (used internally)
$instance->render_license_form();
```

### Static Methods

```php
// Check if license is active (with plugin support)
if (LicenseChecker::is_active('my-plugin-domain')) {
    // ...
}

// Check for current plugin (uses default)
if (LicenseChecker::is_active()) {
    // ...
}
```

## Menu Registration Architecture

### Hook-based Design

The new architecture uses a clean hook-based structure:

```
1. Plugin calls do_action('slk_license_manager_admin_menu', $parent_slug)
   ↓
2. LicenseChecker triggers do_action('slk_register_license_submenu', $parent_slug, $this)
   ↓
3. Plugin registers hook handler for 'slk_register_license_submenu'
   ↓
4. Hook handler calls $license_manager->register_submenu($parent_slug)
   ↓
5. License submenu is registered
   ↓
6. Menu is moved to the end (reorder_submenu_to_end)
   ↓
7. Active menu item is highlighted (register_active_menu_handler)
```

## Separate Licenses per Plugin (Multi-Plugin Setup)

Both plugins can be active simultaneously with **separate and independent licenses:**

```php
// In slk-license-manager/slk-license-manager.php
\SLK\LicenseChecker\LicenseChecker::instance('slk-license-manager');

// In slk-content-bitch/slk-content-bitch.php
\SLK\LicenseChecker\LicenseChecker::instance('slk-content-bitch');

// Each plugin has its own license
if (\SLK\LicenseChecker\LicenseChecker::is_active('slk-license-manager')) {
    // License Manager is licensed
}

if (\SLK\LicenseChecker\LicenseChecker::is_active('slk-content-bitch')) {
    // Content Bitch is licensed
}
```

## Debugging

Enable the `SLK_DEBUG` constant for logging:

```php
define('SLK_DEBUG', true);
```

Logs are output via `error_log()` (check wp-content/debug.log).

## Auto-loading

Classes are automatically loaded via PSR-4 autoloader. No manual `require` needed, except when initially loading the module.
