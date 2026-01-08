# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-08

### Added

#### Core License Management

-   License activation via LemonSqueezy public License API
-   License deactivation with activation hash tracking
-   License validation with automatic 12-hour refresh via WordPress transients
-   License status tracking (active, inactive, or invalid)
-   Activation usage monitoring with activation count display
-   Singleton pattern per text domain for multiple plugin support

#### LemonSqueezy Integration

-   LemonSqueezy HTTP API client (`LemonSqueezyClient`) with configurable timeout and rate limiting
-   License activation operation (`ActivateLicense`) for key validation and setup
-   License deactivation operation (`DeactivateLicense`) for license cleanup
-   License validation operation (`ValidateLicense`) for status checks
-   LemonSqueezyStrategy orchestrator for coordinating license operations
-   LemonSqueezy checkout shortcode (`[lemonsqueezy_checkout]`) with Lemon.js integration
-   Dynamic LemonSqueezy configuration management

#### WordPress Integration

-   Hook-based admin menu registration with dynamic naming (`{plugin_slug}_admin_menu`)
-   Submenu registration and positioning system
-   Post-registration hooks (`slk_register_license_submenu` and `slk_register_license_submenu_done_{option_prefix}`)
-   AJAX action handlers for activate/deactivate operations (`{plugin_slug}_manage_license`)
-   WordPress transient-based validation caching (12-hour interval)
-   Plugin-specific option key prefix system for multi-plugin environments

#### Admin Interface

-   Comprehensive admin license page with license key input field
-   License status indicator (Active/Inactive/Invalid)
-   Activation usage display (activated count / limit)
-   Activate/Deactivate button toggles based on current status
-   AJAX-powered license operations without page reloads
-   Loading spinner and user feedback messages
-   WordPress notice-style error and success messages

#### Plugins List Integration

-   Inline license management in WordPress plugins list table via `LicensePluginsListRenderer`
-   Status badges with color coding (active/inactive/invalid states)
-   Masked license key display (shows first 4 and last 4 characters when active)
-   Activation/deactivation action buttons in plugins list
-   Usage display (X / Y activations) in plugins list row
-   Dynamic status class assignment for styling

#### Data Management

-   Persistent license key storage via WordPress options
-   License status caching with configurable validation intervals
-   Activation hash storage for secure deactivation
-   License activation count and limit data tracking
-   License data cleanup utility (`LicenseHelper::delete_license_data()`)
-   Per-plugin isolated storage with dynamic option key prefixes

#### Frontend Assets

-   Responsive CSS styling (`license-checker.css`) with CSS custom properties for theming
-   Color-coded status badges and state indicators
-   Card-based UI design matching WordPress admin aesthetic
-   Form styling with proper spacing and alignment
-   JavaScript AJAX handler (`license-checker.js`) for interactive operations
-   jQuery-based event handling with loading state management
-   Dynamic UI updates based on license status
-   Error display and message clearing functionality

#### Architecture & Code Quality

-   PSR-4 autoloading with namespace `SLK\LicenseChecker\`
-   Modular class structure with clear separation of concerns
-   Strategy pattern for license operations (`LemonSqueezyStrategy`)
-   Helper utilities for common operations (`LicenseHelper`)
-   Comprehensive code organization with src/, assets/, and views/ directories

### Security

-   Nonce verification for all form submissions and AJAX requests (action: `slk_license_action`)
-   Capability checks requiring `manage_options` for license management operations
-   Key masking in frontend display (only first 4 and last 4 characters visible when active)
-   Output escaping using WordPress functions (`wp_kses`, `esc_html`, `esc_attr`)
-   AJAX security tokens passed via `slk_license_vars` object
-   Singleton pattern with private constructor preventing direct instantiation
-   Secure option storage for license data at the WordPress options level

### Technical Details

-   **PHP Requirement**: >= 8.0
-   **Dependencies**: None required (LemonSqueezy integration uses public API without authentication)
-   **Dev Dependencies**: PHPUnit (^9.5 || ^10.0), PHpStan (^1.0)
-   **License**: GPL-2.0-or-later
-   **Package Type**: WordPress library/module
-   **Repository**: https://github.com/caspahouzer/license-checker
-   **Author**: Sebastian Klaus (sebastian@slk-communications.de)

#### Configuration Constants

-   `VALIDATION_INTERVAL = 43200` (12 hours in seconds) for automatic license validation
-   Request timeout: 10 seconds for API calls
-   Rate limit: 60 requests per minute (LemonSqueezy API limit)

#### Storage Keys (per plugin with dynamic prefix)

-   `slk_{plugin_slug}_license_checker_license_key` - Stored license key
-   `slk_{plugin_slug}_license_checker_license_status` - Current license status
-   `slk_{plugin_slug}_license_checker_activation_hash` - Hash for deactivation
-   `slk_{plugin_slug}_license_checker_license_counts` - Activation usage data
-   `slk_{plugin_slug}_license_checker_license_validation` - Validation transient (12-hour cache)

#### Multi-Plugin Architecture

-   Independent license management per plugin via unique text domain instances
-   Separate WordPress options for each plugin to prevent conflicts
-   Dynamic hook names generated from plugin slug
-   Support for concurrent multi-plugin licensing in single WordPress installation

[Unreleased]: https://github.com/caspahouzer/license-checker/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/caspahouzer/license-checker/releases/tag/v1.0.1
