# License Manager Module

Lizenzierungsmodul mit Hook-basierter Admin-Menü-Integration für WordPress Plugins.

## Namespace

`SLK\LicenseChecker`

## Classes

-   **LicenseChecker** - Main singleton controller für Lizenzmanagement
-   **LicenseHelper** - API HTTP client für License-Server-Kommunikation
-   **LicenseAdminPage** - Admin interface renderer für License-Seite

## Features

-   ✅ Lizenzaktivierung/Deaktivierung/Validierung
-   ✅ **Automatische Validierung alle 12 Stunden** (transient-basiert)
-   ✅ REST API Integration mit dem License-Server
-   ✅ WordPress Admin UI Integration
-   ✅ Sichere Formularbearbeitung mit Nonces
-   ✅ Datenspeicherung über WordPress Options API
-   ✅ **Hook-basierte Menü-Registrierung** (neue Architektur)
-   ✅ **Separate Lizenzen pro Plugin** mit eindeutigen Option-Namen

## API Configuration

-   **Base URL:** `https://slk-communications.de/`
-   **Endpoints:**
    -   `POST /activate` - Lizenz aktivieren
    -   `POST /deactivate` - Lizenz deaktivieren
    -   `POST /validate` - Lizenz validieren

## Installation & Setup

### 1. LicenseChecker Instanzieren

Im Plugin-Main-File oder Bootstrap:

```php
// In deinem Plugin-Datei (z.B. slk-my-plugin.php)
if (file_exists(__DIR__ . '/modules/LicenseChecker/LicenseChecker.php')) {
    require_once __DIR__ . '/modules/LicenseChecker/LicenseChecker.php';

    if (class_exists('\SLK\LicenseChecker\LicenseChecker')) {
        // Instanziere mit deinem eigenen Text-Domain
        \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
    }
}
```

### 2. Hook-basierte Menü-Registrierung

Das Modul triggert automatisch den `slk_register_license_submenu` Hook. Registriere einen Handler in deinem Admin-Setup:

```php
// Im admin_menu Hook oder später
add_action('slk_register_license_submenu', function($parent_slug, $license_manager) {
    // Dein Plugin registriert seine License-Seite selbst
    $license_manager->register_submenu($parent_slug);
}, 10, 2);
```

**Oder einfacher:** Lasse das Fallback-System es automatisch machen - wenn kein Handler registriert wurde, macht es sich selbst!

## Verwendung

### Lizenzstatus per PHP prüfen

```php
// Prüfe Lizenz für dein Plugin
if (\SLK\LicenseChecker\LicenseChecker::is_active('my-plugin-domain')) {
    // Lizenz ist für dein Plugin aktiv
} else {
    // Lizenz ist nicht aktiviert oder abgelaufen
}

// Oder nutze die Instanz direkt
$license = \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
if ($license->get_license_status() === 'active') {
    // Dein Plugin ist lizenziert
}
```

### Admin-Seite aufrufen

Die License-Admin-Seite wird automatisch als Submenü unter dem Parent-Menü registriert:

```
Admin-Menü
└── Dein Menü
    ├── Menü-Item 1
    ├── Menü-Item 2
    └── License (am Ende)  ← Wird automatisch positioniert
```

### Manuelle Registrierung (optional)

Falls du mehr Kontrolle brauchst:

```php
$license_manager = \SLK\LicenseChecker\LicenseChecker::instance('my-plugin-domain');
$license_manager->register_submenu('edit.php?post_type=my_cpt');
```

## Option-Namen

Jedes Plugin speichert seine Lizenzdaten mit einem eindeutigen Präfix:

```
slk_{plugin_slug}_license_checker_license_key
slk_{plugin_slug}_license_checker_license_status
slk_{plugin_slug}_license_checker_activation_hash
slk_{plugin_slug}_license_checker_license_counts
```

**Beispiel für `slk-content-bitch`:**
```
slk_content_bitch_license_checker_license_key
slk_content_bitch_license_checker_license_status
slk_content_bitch_license_checker_activation_hash
slk_content_bitch_license_checker_license_counts
```

## Hooks & Aktionen

### `slk_register_license_submenu`

Triggert die Registrierung des License-Submenüs:

```php
do_action('slk_register_license_submenu', $parent_slug, $license_manager);
```

**Parameter:**
- `$parent_slug` (string) - Parent-Menü-Slug (z.B. 'edit.php?post_type=my_cpt')
- `$license_manager` (LicenseChecker) - LicenseChecker-Instanz

**Beispiel:**
```php
add_action('slk_register_license_submenu', function($parent_slug, $license_manager) {
    $license_manager->register_submenu($parent_slug);
}, 10, 2);
```

### `slk_register_license_submenu_done_{option_prefix}`

Triggert nach erfolgreicher Submenu-Registrierung:

```php
do_action('slk_register_license_submenu_done_slk_content_bitch_license_checker');
```

## AJAX Aktionen

Die JavaScript-Kommunikation nutzt folgende AJAX-Aktionen (pro Plugin unterschiedlich):

- **slk-license-manager:** `slk_license_manager_manage_license`
- **slk-content-bitch:** `slk_content_bitch_manage_license`

Diese werden automatisch vom Modul registriert basierend auf dem Plug-in-Kontext.

## Methoden der LicenseChecker Klasse

### Public Methoden

```php
// Singleton-Instanz abrufen
$instance = LicenseChecker::instance($text_domain = 'slk-license-checker');

// Lizenz aktivieren
$result = $instance->activate_license('license-key-here');

// Lizenz deaktivieren
$result = $instance->deactivate_license($license_key, $activation_hash);

// Lizenz validieren
$result = $instance->validate_license($license_key);

// Lizenzstatus abrufen ('active', 'inactive', 'invalid')
$status = $instance->get_license_status();

// Aktivierungszähler abrufen
$counts = $instance->get_license_counts(); // ['activated' => 1, 'limit' => 5]

// Admin-Menü registrieren (orchestriert Hook-basierte Registrierung)
$instance->add_admin_menu($parent_slug);

// Submenü direkt registrieren
$instance->register_submenu($parent_slug);

// Lizenz-Seite rendern (intern genutzt)
$instance->render_license_form();
```

### Statische Methoden

```php
// Prüfe ob Lizenz aktiv ist (mit Plugin-Support)
if (LicenseChecker::is_active('my-plugin-domain')) {
    // ...
}

// Prüfe für aktuelles Plugin (nutzt default)
if (LicenseChecker::is_active()) {
    // ...
}
```

## Architektur der Menü-Registrierung

### Hook-basiertes Design

Die neue Architektur nutzt eine saubere Hook-basierte Struktur:

```
1. Plugin ruft do_action('slk_license_manager_admin_menu', $parent_slug) auf
   ↓
2. LicenseChecker triggert do_action('slk_register_license_submenu', $parent_slug, $this)
   ↓
3. Plugin registriert Hook-Handler für 'slk_register_license_submenu'
   ↓
4. Hook-Handler ruft $license_manager->register_submenu($parent_slug) auf
   ↓
5. License-Submenü wird registriert
   ↓
6. Menu wird ans Ende verschoben (reorder_submenu_to_end)
   ↓
7. Aktiver Menüpunkt wird hervorgehoben (register_active_menu_handler)
```

## Separate Lizenzen pro Plugin (Multi-Plugin Setup)

Beide Plugins können gleichzeitig aktiv sein mit **separaten und unabhängigen Lizenzen:**

```php
// In slk-license-manager/slk-license-manager.php
\SLK\LicenseChecker\LicenseChecker::instance('slk-license-manager');

// In slk-content-bitch/slk-content-bitch.php
\SLK\LicenseChecker\LicenseChecker::instance('slk-content-bitch');

// Jedes Plugin hat seine eigene Lizenz
if (\SLK\LicenseChecker\LicenseChecker::is_active('slk-license-manager')) {
    // License Manager ist lizenziert
}

if (\SLK\LicenseChecker\LicenseChecker::is_active('slk-content-bitch')) {
    // Content Bitch ist lizenziert
}
```

## Debugging

Aktiviere `SLK_DEBUG` Konstante zum Loggen:

```php
define('SLK_DEBUG', true);
```

Logs werden über `error_log()` ausgegeben (check wp-content/debug.log).

## Auto-Loading

Classes werden automatisch über den PSR-4 Autoloader geladen. Kein manuelles `require` nötig, außer beim initialen Laden des Moduls.
