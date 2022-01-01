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
	 *
	 * @param  string $module Module.
	 * @param  string $data   Icon image path.
	 * @return string Module Title and data HTML.
	 */
	function DashboardModule( $module, $data )
	{
		global $_ROSARIO;

		// @todo export data.
		$export = ! empty( $_ROSARIO['Dashboard']['export'] );

		$html = '';

		if ( ! empty( $data ) )
		{
			$html .= DashboardModuleData( $data );
		}

		if ( $html )
		{
			$html = DashboardModuleTitle( $module ) . $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'DashboardModuleTitle' ) )
{
	/**
	 * Dashboard Module Title HTML
	 *
	 * @since 4.0
	 *
	 * @param  string $module Module.
	 * @param  string $icon   Icon image path.
	 * @return string Module Title HTML.
	 */
	function DashboardModuleTitle( $module, $icon = '' )
	{
		global $_ROSARIO;

		if ( ! empty( $_ROSARIO['Menu'][$module]['title'] ) )
		{
			$module_title = $_ROSARIO['Menu'][$module]['title'];
		}
		else
		{
			$module_title = _( str_replace( '_', ' ', $module ) );
		}

		ob_start();
		?>
		<h3 class="dashboard-module-title">
			<span class="module-icon <?php echo $module; ?>"></span>
			<?php echo $module_title; ?>
		</h3>
		<?php

		return ob_get_clean();
	}
}

if ( ! function_exists( 'DashboardModuleData' ) )
{
	/**
	 * Dashboard Module Data HTML
	 * Will skip `null` data values.
	 *
	 * @since 4.0
	 *
	 * @param  array  $data    Array containing values and their title as key.
	 * @param  int    $columns Number of columns to display. Optional. Defaults to 1 and 2 if data > 10.
	 * @return string Module data HTML
	 */
	function DashboardModuleData( $data, $columns = 0 )
	{
		require_once 'ProgramFunctions/TipMessage.fnc.php';

		if ( empty( $data ) )
		{
			return '';
		}

		$first_value = reset( $data );

		$first_key = key( $data );

		unset( $data[$first_key] );

		// Detail by Profile & Fail.
		$cell = 0;

		$message = '';

		$data = array_filter( $data, function( $value ) {
			return ! is_null( $value );
		});

		if ( $columns < 1 )
		{
			$columns = 1;

			if ( count( $data ) >= 10 )
			{
				$columns = 2;
			}
		}

		foreach ( $data as $title => $value )
		{
			$message .= '<td><span class="legend-gray">' .
				$title . '</span></td><td>' . $value . '</td>';

			if ( ++$cell % $columns === 0 )
			{
				$message .= '</tr><tr>';
			}
		}

		if ( ! $message )
		{
			return '<div class="dashboard-module-data">' . NoInput( $first_value, $first_key ) . '</div>';
		}

		$message = '<table class="dashboard-module-data-tipmsg widefat col1-align-right"><tr>' .
			$message . '</tr></table>';

		return '<div class="dashboard-module-data">' .
		MakeTipMessage( $message, $first_key, NoInput( $first_value, $first_key ) ) . '</div>';
	}
}

if ( ! function_exists( 'DashboardSchool_Setup' ) )
{
	/**
	 * Dashboard School module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardSchool_Setup()
	{
		require_once 'modules/School_Setup/includes/Dashboard.inc.php';

		return DashboardDefaultSchoolSetup();
	}
}

if ( ! function_exists( 'DashboardStudents' ) )
{
	/**
	 * Dashboard Students module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardStudents()
	{
		require_once 'modules/Students/includes/Dashboard.inc.php';

		return DashboardDefaultStudents();
	}
}

if ( ! function_exists( 'DashboardUsers' ) )
{
	/**
	 * Dashboard Users module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardUsers()
	{
		require_once 'modules/Users/includes/Dashboard.inc.php';

		return DashboardDefaultUsers();
	}
}

if ( ! function_exists( 'DashboardScheduling' ) )
{
	/**
	 * Dashboard Schedulin module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardScheduling()
	{
		require_once 'modules/Scheduling/includes/Dashboard.inc.php';

		return DashboardDefaultScheduling();
	}
}

if ( ! function_exists( 'DashboardGrades' ) )
{
	/**
	 * Dashboard Grades module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardGrades()
	{
		require_once 'modules/Grades/includes/Dashboard.inc.php';

		return DashboardDefaultGrades();
	}
}

if ( ! function_exists( 'DashboardAttendance' ) )
{
	/**
	 * Dashboard Attendance module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardAttendance()
	{
		require_once 'modules/Attendance/includes/Dashboard.inc.php';

		return DashboardDefaultAttendance();
	}
}

if ( ! function_exists( 'DashboardEligibility' ) )
{
	/**
	 * Dashboard Activities module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardEligibility()
	{
		require_once 'modules/Eligibility/includes/Dashboard.inc.php';

		return DashboardDefaultEligibility();
	}
}

if ( ! function_exists( 'DashboardDiscipline' ) )
{
	/**
	 * Dashboard Disciplin module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardDiscipline()
	{
		require_once 'modules/Discipline/includes/Dashboard.inc.php';

		return DashboardDefaultDiscipline();
	}
}

if ( ! function_exists( 'DashboardAccounting' ) )
{
	/**
	 * Dashboard Accounting module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardAccounting()
	{
		require_once 'modules/Accounting/includes/Dashboard.inc.php';

		return DashboardDefaultAccounting();
	}
}

if ( ! function_exists( 'DashboardStudent_Billing' ) )
{
	/**
	 * Dashboard Student Billing module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardStudent_Billing()
	{
		require_once 'modules/Student_Billing/includes/Dashboard.inc.php';

		return DashboardDefaultStudentBilling();
	}
}

if ( ! function_exists( 'DashboardFood_Service' ) )
{
	/**
	 * Dashboard Food Service module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardFood_Service()
	{
		require_once 'modules/Food_Service/includes/Dashboard.inc.php';

		return DashboardDefaultFoodService();
	}
}
