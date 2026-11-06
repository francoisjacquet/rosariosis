<?php
/**
 * Dashboard module
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

if ( ! function_exists( 'DashboardModule' ) )
{
	/**
	 * Dashboard Module Title HTML
	 *
	 * @uses DashboardModuleData, DashboardModuleTitle
	 * @example return DashboardModule( 'School_Setup', $data );
	 * @since 4.0
	 * @deprecated since 13.0 Use (new RosarioSIS\Functions\DashboardModule)->add( $module, $data ) instead
	 *
	 * @param  string $module Module.
	 * @param  string $data   Icon image path.
	 * @return string Module Title and data HTML.
	 */
	function DashboardModule( $module, $data )
	{
		RosarioSIS\Functions\Deprecation::trigger(
			'RosarioSIS',
			'13.0',
			'DashboardModule() function is deprecated, use (new RosarioSIS\Functions\DashboardModule)->add( $module, $data ) instead.'
		);

		return $data;
	}
}

if ( ! function_exists( 'DashboardModuleTitle' ) )
{
	/**
	 * Dashboard Module Title HTML
	 *
	 * @since 4.0
	 * @since 12.5 Module is addon, set custom module icon & remove $icon param
	 * @deprecated since 13.0 Use (new RosarioSIS\Functions\DashboardModule)->module( $module ) instead
	 *
	 * @param  string $module Module.
	 *
	 * @return string Module Title HTML.
	 */
	function DashboardModuleTitle( $module )
	{
		RosarioSIS\Functions\Deprecation::trigger(
			'RosarioSIS',
			'13.0',
			'DashboardModuleTitle() function is deprecated, use (new RosarioSIS\Functions\DashboardModule)->title() instead.'
		);

		return (new RosarioSIS\Functions\DashboardModule)->title( $module );
	}
}

if ( ! function_exists( 'DashboardModuleData' ) )
{
	/**
	 * Dashboard Module Data HTML
	 * Will skip `null` data values.
	 *
	 * @since 4.0
	 * @deprecated since 13.0 Use (new RosarioSIS\Functions\DashboardModule)->data( $data, $columns ) instead
	 *
	 * @param  array  $data    Array containing values and their title as key.
	 * @param  int    $columns Number of columns to display. Optional. Defaults to 1 and 2 if data > 10.
	 * @return string Module data HTML
	 */
	function DashboardModuleData( $data, $columns = 0 )
	{
		RosarioSIS\Functions\Deprecation::trigger(
			'RosarioSIS',
			'13.0',
			'DashboardModuleData() function is deprecated, use (new RosarioSIS\Functions\DashboardModule)->data() instead.'
		);

		return (new RosarioSIS\Functions\DashboardModule)->data( $data, $columns );
	}
}
