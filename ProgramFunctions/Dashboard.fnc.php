<?php
/**
 * Dashboard
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Dashboard build
 *
 * Calls, for each active and user module
 * the `Dashboard[Module_Name]` function.
 *
 * Place your add-on module `Dashboard[Module_Name]` function in the functions.php file.
 *
 * @uses $_REQUEST['_ROSARIO_DASHBOARD']
 *
 * @todo For example, set $_REQUEST['_ROSARIO_DASHBOARD']['export'] to 1 to export data.
 * In URL: &_ROSARIO_DASHBOARD[export]=1
 *
 * @global $RosarioModules
 * @global $_ROSARIO
 * @see DashboardModule.fnc.php for default core modules `Dashboard[Module_Name]` functions.
 * @since 4.0
 * @deprecated since 13.0
 */
function Dashboard()
{
	RosarioSIS\Functions\Deprecation::trigger(
		'RosarioSIS',
		'13.0',
		'Dashboard() function is deprecated.'
	);
}

/**
 * Dashboard Output HTML
 * Modules HTML inside PopTable
 *
 * @global $_ROSARIO
 * @since 4.0
 * @since 7.7 Move Dashboard() call outside.
 * @deprecated since 13.0 Use (new RosarioSIS\Functions\Dashboard)->output() instead
 *
 * @param integer $rows Number of modules per row, defaults to 4. Optional.
 */
function DashboardOutput( $rows = 4 )
{
	RosarioSIS\Functions\Deprecation::trigger(
		'RosarioSIS',
		'13.0',
		'DashboardOutput() function is deprecated, use (new RosarioSIS\Functions\Dashboard)->output() instead.'
	);

	(new RosarioSIS\Functions\Dashboard)->output( $rows );
}

/**
 * Add module HTML to Dashboard
 *
 * @global $_ROSARIO Add module HTML to $_ROSARIO['Dashboard'][ $module ]
 * @since 4.0
 * @deprecated since 13.0
 *
 * @param string  $module Module.
 * @param string  $html   Dashboard HTML.
 * @param boolean $append Append HTML.
 */
function DashboardAdd( $module, $html, $append = true )
{
	RosarioSIS\Functions\Deprecation::trigger(
		'RosarioSIS',
		'13.0',
		'DashboardAdd() function is deprecated.'
	);
}
