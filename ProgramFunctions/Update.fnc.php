<?php
/**
 * Update functions
 *
 * Incremental updates
 *
 * Update() function called if ROSARIO_VERSION != version in DB
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Update manager function
 *
 * Call the specific versions functions
 *
 * @since 2.9
 *
 * @return boolean false if wrong version or update failed, else true
 */
function Update()
{
	$from_version = Config( 'VERSION' );

	$to_version = ROSARIO_VERSION;

	/**
	 * Check if Update() version < ROSARIO_VERSION.
	 *
	 * Prevent DB version update if new Update.fnc.php file has NOT been uploaded YET.
	 * Update must be run once both new Warehouse.php & Update.fnc.php files are uploaded.
	 */
	if ( version_compare( '6.6', ROSARIO_VERSION, '<' ) )
	{
		return false;
	}

	// Check if version in DB >= ROSARIO_VERSION.
	if ( version_compare( $from_version, $to_version, '>=' ) )
	{
		return false;
	}

	require_once 'ProgramFunctions/UpdateV2_3.fnc.php';
	require_once 'ProgramFunctions/UpdateV4_5.fnc.php';

	$return = true;

	switch ( true )
	{
		case version_compare( $from_version, '2.9-alpha', '<' ) :

			$return = _update29alpha();

		case version_compare( $from_version, '2.9.2', '<' ) :

			$return = _update292();

		case version_compare( $from_version, '2.9.5', '<' ) :

			$return = _update295();

		case version_compare( $from_version, '2.9.12', '<' ) :

			$return = _update2912();

		case version_compare( $from_version, '2.9.13', '<' ) :

			$return = _update2913();

		case version_compare( $from_version, '2.9.14', '<' ) :

			$return = _update2914();

		case version_compare( $from_version, '3.0', '<' ) :

			$return = _update30();

		case version_compare( $from_version, '3.1', '<' ) :

			$return = _update31();

		case version_compare( $from_version, '3.5', '<' ) :

			$return = _update35();

		case version_compare( $from_version, '3.7-beta', '<' ) :

			$return = _update37beta();

		case version_compare( $from_version, '3.9', '<' ) :

			$return = _update39();

		case version_compare( $from_version, '4.0-beta', '<' ) :

			$return = _update40beta();

		case version_compare( $from_version, '4.2-beta', '<' ) :

			$return = _update42beta();

		case version_compare( $from_version, '4.3-beta', '<' ) :

			$return = _update43beta();

		case version_compare( $from_version, '4.4-beta', '<' ) :

			$return = _update44beta();

		case version_compare( $from_version, '4.4-beta2', '<' ) :

			$return = _update44beta2();

		case version_compare( $from_version, '4.5-beta2', '<' ) :

			$return = _update45beta2();

		case version_compare( $from_version, '4.6-beta', '<' ) :

			$return = _update46beta();

		case version_compare( $from_version, '4.7-beta', '<' ) :

			$return = _update47beta();

		case version_compare( $from_version, '4.7-beta2', '<' ) :

			$return = _update47beta2();

		case version_compare( $from_version, '4.9-beta', '<' ) :

			$return = _update49beta();

		case version_compare( $from_version, '5.0-beta', '<' ) :

			$return = _update50beta();

		case version_compare( $from_version, '5.0.1', '<' ) :

			$return = _update501();

		case version_compare( $from_version, '5.2-beta', '<' ) :

			$return = _update52beta();

		case version_compare( $from_version, '5.3-beta', '<' ) :

			$return = _update53beta();

		case version_compare( $from_version, '5.4.1', '<' ) :

			$return = _update541();

		case version_compare( $from_version, '5.4.2', '<' ) :

			$return = _update542();

		case version_compare( $from_version, '5.5-beta3', '<' ) :

			$return = _update55beta3();

		case version_compare( $from_version, '5.7', '<' ) :

			$return = _update57();

		case version_compare( $from_version, '5.8-beta5', '<' ) :

			$return = _update58beta5();

		case version_compare( $from_version, '5.9-beta', '<' ) :

			$return = _update59beta();

		case version_compare( $from_version, '5.9-beta2', '<' ) :

			$return = _update59beta2();

		case version_compare( $from_version, '5.9', '<' ) :

			$return = _update59();

		case version_compare( $from_version, '5.9.1', '<' ) :

			$return = _update591();

		case version_compare( $from_version, '6.3', '<' ) :

			$return = _update63();

		case version_compare( $from_version, '6.6', '<' ) :

			$return = _update66();
	}

	// Update version in DB CONFIG table.
	Config( 'VERSION', ROSARIO_VERSION );

	return $return;
}


/**
 * Is function called by Update()?
 *
 * Local function
 *
 * @example _isCallerUpdate( debug_backtrace() );
 *
 * @since 2.9.13
 *
 * @param  array   $callers debug_backtrace().
 *
 * @return boolean          Exit with error message if not called by Update().
 */
function _isCallerUpdate( $callers )
{
	if ( ! isset( $callers[1]['function'] )
		|| $callers[1]['function'] !== 'Update' )
	{
		exit( 'Error: the update functions must be called by Update() only!' );
	}

	return true;
}


/**
 * Update to version 6.3
 *
 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to CONFIG table.
 *
 * Local function
 *
 * @since 6.3
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update63()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL to CONFIG table.
	 */
	$default_school_added = DBGetOne( "SELECT 1 FROM CONFIG
		WHERE TITLE='CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL'" );

	if ( ! $default_school_added )
	{
		DBQuery( "INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL', NULL);" );
	}

	return $return;
}

/**
 * Update to version 6.6
 *
 * Add Registration program for Administrators.
 * 1. Add Custom/Registration.php to profile_exceptions table.
 *
 * Local function
 *
 * @since 6.6
 *
 * @return boolean false if update failed or if not called by Update(), else true
 */
function _update66()
{
	_isCallerUpdate( debug_backtrace() );

	$return = true;

	/**
	 * 1. Add Custom/Registration.php to profile_exceptions table.
	 */
	$admin_profiles_RET = DBGet( "SELECT id
		FROM user_profiles
		WHERE profile='admin'" );

	foreach ( (array) $admin_profiles_RET as $admin_profile )
	{
		$profile_id = $admin_profile['ID'];

		$registration_profile_exceptions_exists = DBGet( "SELECT 1
			FROM profile_exceptions
			WHERE profile_id='" . $profile_id . "'
			AND modname='Custom/Registration.php'" );

		if ( ! $registration_profile_exceptions_exists )
		{
			DBQuery( "INSERT INTO profile_exceptions
				VALUES ('" . $profile_id . "', 'Custom/Registration.php', 'Y', 'Y');" );
		}
	}

	return $return;
}
