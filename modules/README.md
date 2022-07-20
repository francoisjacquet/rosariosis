# MODULES

Place here the Modules you want to add to RosarioSIS.

## Activate

Activate them via:
_School > Configuration > Modules_

## Files

- `Menu.php`: required.
- `install.sql`: optional. Contains the PostgreSQL queries run on module activation: Profile exceptions, module tables, data, etc.
- `install_mysql.sql`: optional. Contains the MySQL queries run on module activation: Profile exceptions, module tables, data, etc.
- `install_[2 letters locale code].sql`: optional. Contains the SQL queries run on module activation to translate texts: templates, etc. For example, to translate to French: `install_fr.sql`. Since RosarioSIS 7.3.
- `functions.php`: optional. Contains the functions to be automatically loaded by RosarioSIS.

## Example

To create a custom module, or add a program to an existing module, please refer to https://gitlab.com/francoisjacquet/Example

## Action hooks

You typically want to register your `functions.php` file functions to be hooked on certain actions. The list of actions is available in the [`functions/Actions.php`](https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/functions/Actions.php) file.

For example, to run a daily CRON like system:
```php
// Add our MyModuleCronDo() function to the Warehouse.php|header action.
add_action( 'Warehouse.php|header', 'MyModuleCronDo' );

/**
 * Run daily CRON on page load.
 * Do my CRON logic.
 *
 * @uses Warehouse.php|header_head action hook
 */
function MyModuleCronDo()
{
	$cron_day = ProgramConfig( 'my_module', 'MY_MODULE_CRON_DAY' );

	if ( DBDate() <= $cron_day
		|| ! UserSchool() )
	{
		// CRON already ran today or not logged in.
		return -1;
	}

	// Save CRON day: ran today.
	ProgramConfig( 'my_module', 'MY_MODULE_CRON_DAY', DBDate() );

	// Do my CRON logic...

	return $my_return_value;
}
```
