<?php
/**
 * School Setup Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default School module
 *
 * @since 4.0
 *
 * @return string Dashboard module HTML.
 */
function DashboardDefaultSchoolSetup()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	/*if ( SchoolInfo( 'SCHOOLS_NB' ) > 1 )
	{
	$html .= '<p>' . NoInput( $schools_nb, _( 'Schools' ) ) . '</p>';
	}*/

	if ( $profile === 'admin' )
	{
		$data = DashboardSchoolSetupAdmin();
	}

	return DashboardModule( 'School_Setup', $data );
}

if ( ! function_exists( 'DashboardSchoolSetupAdmin' ) )
{
	/**
	 * Dashboard data
	 * School Setup module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardSchoolSetupAdmin()
	{
		$start_date = date( 'Y-m-d', time() - 60 * 60 * 24 );

		$access_log_RET = DBGet( "SELECT
		COUNT(USERNAME) AS LOGIN_RECORDS,
		SUM(CASE WHEN PROFILE='admin' THEN 1 END) AS LOGIN_ADMIN,
		SUM(CASE WHEN PROFILE='teacher' THEN 1 END) AS LOGIN_TEACHER,
		SUM(CASE WHEN PROFILE='parent' THEN 1 END) AS LOGIN_PARENT,
		SUM(CASE WHEN PROFILE='student' THEN 1 END) AS LOGIN_STUDENT,
		SUM(CASE WHEN STATUS IS NULL OR STATUS='B' THEN 1 END) AS LOGIN_FAIL
		FROM access_log
		WHERE CREATED_AT >='" . $start_date . "'
		AND CREATED_AT <='" . DBDate() . ' 23:59:59' . "'" );

		$login_records = (int) $access_log_RET[1]['LOGIN_RECORDS'];

		$data = [
			// Login records for today and yesterday.
			ngettext( 'Login record', 'Login records', $login_records ) => $login_records,
			// Login records per profile.
			_( 'Administrator' ) => (int) $access_log_RET[1]['LOGIN_ADMIN'],
			_( 'Teacher' ) => $access_log_RET[1]['LOGIN_TEACHER'],
			_( 'Parent' ) => $access_log_RET[1]['LOGIN_PARENT'],
			_( 'Student' ) => $access_log_RET[1]['LOGIN_STUDENT'],
			// Failed login records.
			_( 'Fail' ) => (int) $access_log_RET[1]['LOGIN_FAIL'],
		];

		return $data;
	}
}
