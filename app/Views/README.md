# View System Documentation

## Overview

The enhanced View system provides layout inheritance, dynamic CSS/JS loading, and organized directory structure for better maintainability.

## Directory Structure

```
app/Views/
├── layouts/           # Layout templates
│   ├── app.php       # Main application layout
│   ├── simple.php    # Simple layout for basic pages
│   ├── header.php    # Header component
│   ├── sidebar.php   # Sidebar component
│   ├── footer.php    # Footer component
│   └── functions.php # Layout helper functions
├── pages/            # Page-specific views organized by feature
│   ├── employees/    # Employee management views
│   ├── holidays/     # Holiday management views
│   ├── leaves/       # Leave management views
│   ├── littering/    # Littering report views
│   ├── waste/        # Waste collection views
│   ├── admin/        # Admin panel views
│   └── demo/         # Demo and example views
└── auth/             # Authentication views
    └── login.php
```

## Usage

### Basic View Rendering

```php
// In Controller
return $this->render('pages/employees/index', $data);
```

### Layout Inheritance

```php
// In Controller - specify layout as third parameter
return $this->render('pages/employees/index', $data, 'app');
```

### Dynamic CSS Loading

```php
// In View file
<?php
use App\Core\View;

View::startSection('css');
?>
<link href="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<style>
    .custom-style { color: red; }
</style>
<?php
View::endSection();
?>
```

### Dynamic JavaScript Loading

```php
// In View file
<?php
View::startSection('js');
?>
<script src="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script>
    // Page-specific JavaScript
    console.log('Page loaded');
</script>
<?php
View::endSection();
?>
```

### Helper Methods

```php
// Add CSS file programmatically
View::addCss(BASE_ASSETS_URL . '/assets/css/custom.css');

// Add JS file programmatically
View::addJs(BASE_ASSETS_URL . '/assets/js/custom.js');

// Check if section exists
if (View::hasSection('custom-section')) {
    // Do something
}

// Output section with default
echo View::yieldSection('custom-section', 'Default content');
```

## Layout Components

### Main Layout (app.php)
- Full application layout with header, sidebar, footer
- Includes all default CSS/JS libraries
- Supports dynamic CSS/JS sections
- Used for authenticated pages

### Simple Layout (simple.php)
- Minimal layout for basic pages
- Includes only essential CSS/JS
- Used for login, status pages, etc.

### Components
- **header.php**: Top navigation bar with user menu
- **sidebar.php**: Left navigation menu
- **footer.php**: Bottom navigation menu

## Migration from Old System

### Before (Old System)
```php
// Old way - manual includes
include_once ROOT_PATH . '/layouts/header.php';
// Now handled by EmployeeController@index with proper MVC structure
include_once ROOT_PATH . '/layouts/footer.php';
```

### After (New System)
```php
// New way - in Controller
return $this->render('pages/employees/index', $data, 'app');
```

## Benefits

1. **Layout Inheritance**: Consistent layout across pages
2. **Dynamic Assets**: Page-specific CSS/JS loading
3. **Organized Structure**: Logical directory organization
4. **Maintainability**: Centralized layout management
5. **Flexibility**: Multiple layout options
6. **Performance**: Only load required assets per page

## Examples

See `app/Views/pages/demo/layout-example.php` for a complete example demonstrating all features.