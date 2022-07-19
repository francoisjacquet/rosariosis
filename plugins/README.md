# PLUGINS

Place here the Plugins you want to add to RosarioSIS.

Note: if you wish to add menu entries, please create a [module](https://gitlab.com/francoisjacquet/rosariosis/-/tree/mobile/modules) instead.

## Activate

Activate them via:
_School > Configuration > Plugins_

## Files

- `functions.php`: required. Contains the functions to be automatically loaded by RosarioSIS.
- `install.sql`: optional. Contains the PostgreSQL queries run on plugin activation: configuration, plugin tables, data, etc.
- `install_mysql.sql`: optional. Contains the MySQL queries run on plugin activation: configuration, plugin tables, data, etc.
- `install_[2 letters locale code].sql`: optional. Contains the SQL queries run on plugin activation to translate texts: templates, etc. For example, to translate to French: `install_fr.sql`. Since RosarioSIS 7.3.
- `config.inc.php`: optional. Included by the `modules/School_Setup/includes/Plugins.inc.php` file when the _Configuration_ link in the plugin listing is clicked.

## Example

You can base your work or reuse any existing plugin. The list of available plugins can be found at https://www.rosariosis.org/add-ons/

## Action hooks

You typically want to register your functions to be hooked on certain actions. The list of actions is available in the [`functions/Actions.php`](https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/functions/Actions.php) file.

For example, to load a specific JS or CSS file in the HTML `<head>` use this code:
```php
// Add our MyPluginHeadLoadJSCSS() function to the Warehouse.php|header_head action.
add_action( 'Warehouse.php|header_head', 'MyPluginHeadLoadJSCSS' );

/**
 * Load JS and CSS in HTML head
 * Load mystylesheet.css & myjavascriptfile.js files.
 *
 * @uses Warehouse.php|header_head action hook
 */
function MyPluginHeadLoadJSCSS()
{
	echo '<link rel="stylesheet" href="plugins/MyPlugin/css/mystylesheet.css" />';
	echo '<script src="plugins/MyPlugin/js/myjavascriptfile.js"></script>';
}
```
