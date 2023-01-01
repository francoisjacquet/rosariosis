<?php
/**
 * Users Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Users module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultUsers()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardUsersAdmin();
	}

	return DashboardModule( 'Users', $data );
}

if ( ! function_exists( 'DashboardUsersAdmin' ) )
{
	/**
	 * Dashboard data
	 * Users module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardUsersAdmin()
	{
		$users_nb = 0;

		$users_RET = DBGet( "SELECT PROFILE,COUNT(STAFF_ID) AS USERS_NB
		FROM staff
		WHERE SYEAR='" . UserSyear() . "'
		AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
		GROUP BY PROFILE" );

		$users_profile_data = [];

		$profiles = [
			'admin' => _( 'Administrator' ),
			'teacher' => _( 'Teacher' ),
			'parent' => _( 'Parent' ),
			'none' => _( 'No Access' ),
		];

		foreach ( $users_RET as $users )
		{
			$profile = $profiles[$users['PROFILE']];

			$users_profile_data[$profile] = $users['USERS_NB'];

			$users_nb += (int) $users['USERS_NB'];
		}

		if ( ! $users_nb )
		{
			return [];
		}

		$data = [];

		// Users in school.
		$data[_( 'Users' )] = (int) $users_nb;

		$users_nb = (int) $users_nb;

		$data += $users_profile_data;

		return $data;
	}
}
