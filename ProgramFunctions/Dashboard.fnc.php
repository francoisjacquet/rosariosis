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
 */
function Dashboard()
{
	global $RosarioModules,
		$_ROSARIO;

	require_once 'Menu.php';
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	if ( ! isset( $_ROSARIO['Dashboard'] ) )
	{
		$_ROSARIO['Dashboard'] = [];
	}

	if ( ! empty( $_REQUEST['_ROSARIO_DASHBOARD'] ) )
	{
		$_ROSARIO['Dashboard'] = array_merge_recursive( $_ROSARIO['Dashboard'], $_REQUEST['_ROSARIO_DASHBOARD'] );
	}

	foreach ( $RosarioModules as $module => $activated )
	{
		if ( ! $activated )
		{
			// Module not activated.
			continue;
		}

		if ( ! function_exists( 'Dashboard' . $module ) )
		{
			// No Dashboard function for module.
			continue;
		}

		if ( empty( $_ROSARIO['Menu'][$module] ) )
		{
			// User profile has no access to module.
			continue;
		}

		$dashboard_html = call_user_func( 'Dashboard' . $module );

		DashboardAdd( $module, $dashboard_html, true );
	}
}

/**
 * Dashboard Output HTML
 * Modules HTML inside PopTable
 *
 * @global $_ROSARIO
 * @since 4.0
 * @since 7.7 Move Dashboard() call outside.
 *
 * @param integer $rows Number of modules per row, defaults to 4. Optional.
 */
function DashboardOutput( $rows = 4 )
{
	global $_ROSARIO;

	if ( empty( $_ROSARIO['Dashboard'] ) )
	{
		return;
	}

	echo '<br>';

	PopTable( 'header', _( 'Dashboard' ), 'width="100%"' );

	?>
	<table class="dashboard width-100p valign-top fixed-col"><tr class="st">
	<?php

	if ( $rows < 1 )
	{
		$rows = 4;
	}

	$row = 0;

	// Output Dashboard modules, 4 per row.

	foreach ( $_ROSARIO['Dashboard'] as $html ): ?>

		<td><?php echo $html; ?></td>

		<?php

	if ( ++$row % $rows === 0 ): ?>

			</tr><tr class="st">

		<?php endif;
	endforeach;

	?>
	</tr></table>
	<?php

	PopTable( 'footer' );
}

/**
 * Add module HTML to Dashboard
 *
 * @global $_ROSARIO Add module HTML to $_ROSARIO['Dashboard'][ $module ]
 * @since 4.0
 *
 * @param string  $module Module.
 * @param string  $html   Dashboard HTML.
 * @param boolean $append Append HTML.
 */
function DashboardAdd( $module, $html, $append = true )
{
	global $_ROSARIO;

	if ( empty( $html ) )
	{
		return;
	}

	if ( $append
		&& ! empty( $_ROSARIO['Dashboard'][$module] ) )
	{
		$_ROSARIO['Dashboard'][$module] .= $html;
	}
	else
	{
		$_ROSARIO['Dashboard'][$module] = $html;
	}
}
