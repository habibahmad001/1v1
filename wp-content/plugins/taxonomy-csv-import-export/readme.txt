=== Taxonomy CSV Import Export ===
Contributors: rlorakib
Author URI: https://rlorakib.com/
Plugin URI: https://wordpress.org/plugins/search/taxonomy-csv-import-export/
Tags: csv import, csv export, taxonomy, student export, student import
Requires at least: 5.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily import and export WordPress taxonomies and users using CSV files.

== Description ==  
**Taxonomy CSV Import Export** helps you manage your WordPress site's taxonomies (like categories, tags, and custom taxonomies) with ease by importing or exporting them as CSV files.

This is especially useful for developers or site administrators who need to migrate, back up, or bulk edit taxonomy terms efficiently.

Now includes a powerful User Import/Export system with role-based filtering, making user management even more flexible.

### Key Features:
- Import taxonomy terms from a CSV file
- Export existing taxonomy terms to a CSV
- Supports built-in and custom taxonomies
- Works with categories, tags, and more
- Simple and easy-to-use interface
- No setup or configuration needed

### User Import & Export (New):
- Import users from CSV files
- Export users to CSV
- Filter users by specific roles during export
- Multi-role selection support
- Export all users by default if no role is selected
- Update existing users during import

### Why Use This Plugin?
- Save time with bulk operations
- Easily migrate data between sites
- Backup taxonomy terms and users
- Manage large datasets efficiently
- Simple and user-friendly interface
- No complex setup required

**Plugin Documentation:**  
[https://wordpress.org/plugins/search/taxonomy-csv-import-export/](https://wordpress.org/plugins/search/taxonomy-csv-import-export/)

- [Live Demo](https://wordpress.org/plugins/search/taxonomy-csv-import-export/)  
- [About Author](https://github.com/rlorakib)  
- [Join with us](https://www.facebook.com/profile.php?id=61581703708966)

== Video Tutorial ==  
Coming soon!

You can make my day by submitting a positive review on  
<a href="https://wordpress.org/plugins/search/taxonomy-csv-import-export/" target="blank"><strong>WordPress.org!</strong></a>

== Installation ==  
1. Upload the plugin files to the `/wp-content/plugins/` directory  
2. Activate through the 'Plugins' screen  

== Screenshots ==  
1. Export data list
2. CSV export page
3. Import success data and message

== Upgrade Notice ==  
N/A

== Changelog ==  
= 0.1 =  
* Initial version

= 0.2 =
* Fixed: Text domain issues
* Fixed: short function naming issue.

= 1.0 =
* Fixed: Plugin Security issues.
* Fixed: Translate issues.

= 1.1 =
* Added: Display of all imported data in a structured table.
* Improved: Implementation of enhanced UI/UX design for a smoother experience.

= 1.2 =
* Fixed: Display of all imported data in a structured table not showing.
* Fixed: Not working Implementation of enhanced UI/UX design.

= 1.3 - 29/03/2026 =
* Added: Users data export/import feature.
* Improved: Plugin menu include for better experience and management.

= 1.4 - 26/05/2026 =
* Added: Register Taxonomy — create and persist custom taxonomies from the admin UI, choosing between Tags (non-hierarchical) or Categories (hierarchical) type and attaching to any post type.
* Added: Create Term — accordion list of all registered taxonomies; expand any row to add a new term with a title and optional description via AJAX (no page reload).
* Added: Export data preview — see all terms in a table before downloading the CSV.
* Added: Unified Import/Export UI with pill-style tab switcher; import and export panels toggle dynamically without a page reload.
* Improved: Full plugin UI reorganisation — consistent page header on every page, card layout with gradient header strip, and bordered/shadowed table containers.
* Improved: Users page redesigned with two side-by-side panels (Export / Import); role selection replaced with checkboxes.
* Improved: CSS rewritten with CSS custom properties for the color scheme; duplicate rules removed and responsive breakpoints consolidated.
* Fixed: `fputcsv()` and `str_getcsv()` deprecated escape-parameter warning (PHP 8.4) that was prepending error output to exported CSV files and corrupting imports.
* Fixed: `ImportLogger::log()` was called with the wrong class name (`Import_Logger`) and as a static method when it was an instance method — both now corrected.
* Fixed: Broken singleton `init()` method that silently created a second unused instance.
* Fixed: `taxocsie_admin_notice_remove()` parenthesis grouping — the third screen ID condition was outside the `isset()` guard, risking a PHP notice on screens without an ID.
* Fixed: `TAXOCSIE_VERSION` constant was mismatched with the plugin header.
* Removed: Facebook footer links from all admin pages.
* Removed: Debug `error_log()` calls left in `autoload.php` and `admin/preview.php`.