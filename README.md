# Sensei Lesson Grid Editor

A powerful WordPress plugin that provides a visual grid editor for organizing and displaying Sensei LMS lessons in customizable, responsive grids with module support.

## Features

### Core Functionality
- **Visual Grid Editor**: Drag-and-drop interface for creating lesson grids
- **Module Organization**: Group lessons into modules for better structure
- **Flexible Columns**: Choose between 3, 4, 5, or 6 columns per module
- **Sensei Integration**: Automatic sync with Sensei lessons, progress tracking, and access control
- **Responsive Design**: Automatic adaptation for desktop, tablet, and mobile devices
- **Custom Post Type**: Dedicated management system for multiple grids

### Advanced Features
- **Lesson Status Indicators**: Visual markers for completed, incomplete, and locked lessons
- **Progress Tracking**: Integration with Sensei's user progress system
- **Access Control**: Respects Sensei's prerequisites and permissions
- **Custom Images**: Override lesson thumbnails with custom images
- **Mixed Content**: Combine Sensei lessons with custom tiles/links

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Sensei LMS plugin (for full functionality)
- jQuery (included with WordPress)

## Installation

### Via GitHub

1. Download the latest release from the [releases page](https://github.com/ottenharoldpatrick-gif/sensei-lesson-map-editor/releases)
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin panel

### Manual Installation

```bash
cd wp-content/plugins
git clone https://github.com/ottenharoldpatrick-gif/sensei-lesson-map-editor.git sensei-lesson-grid
```

## Usage

### Creating Your First Grid

1. Navigate to **Lesson Grids** in the WordPress admin menu
2. Click **Add New Grid**
3. Give your grid a title (this creates the slug for the shortcode)
4. Add modules and tiles using the visual editor
5. Save your grid

### Using Shortcodes

Display a grid anywhere using the shortcode:

```
[lesson_grid slug="your-grid-slug"]
```

In PHP templates:

```php
<?php echo do_shortcode('[lesson_grid slug="your-grid-slug"]'); ?>
```

Or using the helper function:

```php
<?php echo slge_grid_by_slug('your-grid-slug'); ?>
```

### Module Configuration

Each module can have:
- **Custom name**: Displayed as a heading above the grid
- **Column count**: 3, 4, 5, or 6 columns
- **Multiple tiles**: Each linking to lessons or custom URLs

### Tile Types

1. **Sensei Lesson Tiles**
   - Link directly to Sensei lessons
   - Automatic title and thumbnail sync
   - Progress and lock status display

2. **Custom Tiles**
   - Manual title and URL entry
   - Custom image selection
   - No progress tracking

## Styling & Customization

### CSS Classes

The plugin uses BEM-style CSS classes for easy customization:

```css
.slge-lesson-grid-container /* Main container */
.slge-module-section /* Module wrapper */
.slge-module-title /* Module heading */
.slge-lesson-grid /* Grid container */
.slge-lesson-card /* Individual tile */
.slge-lesson-image /* Tile image */
.slge-lesson-title /* Tile title */
```

### Responsive Breakpoints

The plugin automatically adjusts columns at these breakpoints:

| Columns Set | Desktop | Tablet (≤1024px) | Mobile (≤768px) | Small (≤480px) |
|------------|---------|------------------|------------------|----------------|
| 6 columns  | 6       | 4                | 2                | 1              |
| 5 columns  | 5       | 3                | 2                | 1              |
| 4 columns  | 4       | 3                | 2                | 1              |
| 3 columns  | 3       | 2                | 2                | 1              |

### Custom Styling

Add custom styles to your theme:

```css
/* Example: Custom module title */
.slge-module-title {
    font-family: 'Your Font', sans-serif;
    color: #333;
    border-bottom: 2px solid #0073aa;
}

/* Example: Custom card hover effect */
.slge-lesson-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}
```

## Advanced Configuration

### Filters

The plugin provides several filters for customization:

```php
// Modify lesson search query
add_filter('slge_lesson_search_args', function($args, $search_term) {
    $args['meta_key'] = 'custom_field';
    return $args;
}, 10, 2);

// After grid update
add_action('slg_grid_updated', function($post_id) {
    // Clear custom cache, etc.
});
```

### Functions

Utility functions available for developers:

```php
// Get all grids
$grids = SLGE_Plugin::get_instance()->get_all_grids();

// Get grid structure
$structure = SLGE_Plugin::get_instance()->get_grid_structure($grid_id);
```

## 🗂️ File Structure

```
sensei-lesson-grid/
├── sensei-lesson-grid.php      # Main plugin file
├── assets/
│   ├── admin.css               # Admin interface styles
│   ├── admin.js                # Admin interface JavaScript
│   ├── frontend.css            # Frontend grid styles
│   └── placeholder.png         # Default tile image
├── grid-columns.php            # Helper functions
└── README.md                   # Documentation
```

## Troubleshooting

### Common Issues

**Grid not displaying:**
- Check if the slug in the shortcode matches your grid's slug
- Ensure the grid is published (not draft)
- Clear any caching plugins

**Sensei lessons not showing:**
- Verify Sensei LMS is installed and activated
- Check that lessons are published
- Ensure lesson post type exists

**Columns not responsive:**
- Clear browser cache
- Check for CSS conflicts with your theme
- Verify frontend.css is loading

### Debug Mode

Add debug parameter to see grid information:

```
[lesson_grid slug="your-grid" debug="1"]
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Changelog

### Version 1.1.0
- Added per-module column selection (3-6 columns)
- Improved responsive design
- Enhanced backward compatibility
- Bug fixes and performance improvements

### Version 1.0.0
- Initial release
- Core grid editor functionality
- Sensei LMS integration
- Module and tile system
- Responsive 5-column grid

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Credits

**Author:** Harold Otten  
**Website:** [Is Digitaal](https://eco.isdigitaal.nl)  
**Requires:** WordPress 6.0+, PHP 7.4+  
**Tested up to:** WordPress 6.8.2  

## Support

For support, please create an issue on the [GitHub repository](https://github.com/ottenharoldpatrick-gif/sensei-lesson-map-editor/issues).

## Donations

If you find this plugin useful, consider buying me a coffee! ☕

---

Made with ❤️ for the WordPress and Sensei LMS community
