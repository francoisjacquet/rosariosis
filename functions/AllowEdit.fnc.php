<?php
/**
 * User edit / usage rights check functions
 * Determined by profiles / user permissions
 *
 * @see Users > User Profiles & User Permissions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Can Edit program check
 *
 * Always perform `AllowEdit()` check:
 * before displaying fields / options to edit data
 * AND before saving or updating data
 *
 * @since 12.0 Add smart cache: up to 8ms faster
 * Tip: if you call AllowEdit() > 5 times, each for a different program, set $cache_all to true.
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['allow_edit'] & $_ROSARIO['AllowEdit'] (cache)
 *
 * @param  string $modname Specify program name (optional) defaults to current program.
 * @param  bool   $cache_all Cache Can Edit for all programs. Defaults to false.
 *
 * @return boolean false if not allowed, true if allowed
 */
function AllowEdit( $modname = false, $cache_all = false )
{
	global $_ROSARIO;

	// Build cache only once.
	static $cached_all = false;

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		return ! empty( $_ROSARIO['allow_edit'] );
	}

	if ( ! $modname )
	{
		if ( isset( $_ROSARIO['allow_edit'] ) )
		{
			return $_ROSARIO['allow_edit'];
		}

		if ( ! isset( $_REQUEST['modname'] ) )
		{
			return false;
		}

		$modname = $_REQUEST['modname'];
	}

	if ( isset( $_REQUEST['modname'] ) && $modname === $_REQUEST['modname'] )
	{
		if ( $modname === 'Students/Student.php'
			&& isset( $_REQUEST['student_id'] )
			&& $_REQUEST['student_id'] === 'new' )
		{
			// Add a Student.
			$modname .= '&include=General_Info&student_id=new';
		}
		elseif ( $modname === 'Users/User.php'
			&& isset( $_REQUEST['staff_id'] )
			&& $_REQUEST['staff_id'] === 'new' )
		{
			// Add a User.
			$modname .= '&staff_id=new';
		}
		elseif ( ( $modname === 'Students/Student.php' || $modname === 'Users/User.php' )
			&& isset( $_REQUEST['category_id'] ) )
		{
			// Student / User Info tabs.
			$modname .= '&category_id=' . $_REQUEST['category_id'];
		}
	}

	if ( isset( $_ROSARIO['AllowEdit'][ $modname ] ) )
	{
		return ! empty( $_ROSARIO['AllowEdit'][ $modname ] );
	}

	$from_where_sql = User( 'PROFILE_ID' ) ?
		"FROM profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
		"FROM staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";

	if ( $cache_all && ! $cached_all )
	{
		// Get all CAN_EDIT programs from database
		$_ROSARIO['AllowEdit'] = DBGet( "SELECT MODNAME " . $from_where_sql .
			" AND CAN_EDIT='Y'", [], [ 'MODNAME' ] );

		$cached_all = true;
	}
	elseif ( ! $cached_all )
	{
		// SQL query only 1 program: up to 8ms gain compared to all.
		$can_edit = DBGetOne( "SELECT 1 " . $from_where_sql .
			" AND CAN_EDIT='Y' AND MODNAME='" . $modname . "'" );

		$_ROSARIO['AllowEdit'][ $modname ] = $can_edit ? $modname : false;
	}

	return ! empty( $_ROSARIO['AllowEdit'][ $modname ] );
}


/**
 * Can Use program check
 *
 * @since 12.0 Add smart cache: up to 8ms faster
 * Tip: if you call AllowUse() > 5 times, each for a different program, set $cache_all to true.
 *
 * @global array  $_ROSARIO Set $_ROSARIO['ProgramLoaded']
 *
 * @param  string $modname Specify program name (optional) defaults to current program.
 * @param  bool   $cache_all Cache Can Use for all programs. Defaults to false.
 *
 * @return boolean false if not allowed, true if allowed
 */
function AllowUse( $modname = false, $cache_all = false )
{
	global $_ROSARIO;

	// Build cache only once.
	static $cached_all = false,
		$allow_use = [];

	$query_string = '';

	if ( ! $modname )
	{
		$modname = $_ROSARIO['ProgramLoaded'] = $_REQUEST['modname'];

		$query_string = urldecode( $_SERVER['QUERY_STRING'] );

		if ( isset( $_REQUEST['bottomfunc'] )
			&& $_REQUEST['bottomfunc'] === 'print' )
		{
			$query_string = str_replace( 'bottomfunc=print&', '', $query_string );
		}
	}

	/**
	 * Security fix allow PHP scripts in misc/ one by one in place of the whole folder.
	 *
	 * @link http://www.securiteam.com/securitynews/6S02U1P6BI.html
	 */
	$allow_misc = [
		'misc/ChooseRequest.php',
		'misc/ChooseCourse.php',
		'misc/Portal.php',
		'misc/ViewContact.php',
	];

	if ( in_array( $modname, $allow_misc ) )
	{
		return true;
	}

	if ( isset( $_REQUEST['modname'] ) && $modname === $_REQUEST['modname'] )
	{
		if ( $modname === 'Students/Student.php'
			&& isset( $_REQUEST['student_id'] )
			&& $_REQUEST['student_id'] === 'new' )
		{
			// Add a Student.
			$add_query = '&include=General_Info&student_id=new';
		}
		elseif ( $modname === 'Users/User.php'
			&& isset( $_REQUEST['staff_id'] )
			&& $_REQUEST['staff_id'] === 'new' )
		{
			// Add a User.
			$add_query = '&staff_id=new';
		}
		elseif ( ( $modname === 'Students/Student.php' || $modname === 'Users/User.php' )
			&& isset( $_REQUEST['category_id'] ) )
		{
			// Student / User Info tabs.
			$add_query = '&category_id=' . $_REQUEST['category_id'];
		}
		elseif ( $modname === 'Users/TeacherPrograms.php'
			&& isset( $_REQUEST['include'] ) )
		{
			// Teacher Programs.
			$add_query = '&include=' . $_REQUEST['include'];
		}
		elseif ( ( $modname === 'Accounting/Statements.php' || $modname === 'Student_Billing/Statements.php' )
			&& isset( $_REQUEST['_ROSARIO_PDF'] )
			&& User( 'PROFILE' ) !== 'admin' )
		{
			// Print Statements.
			$add_query = '&_ROSARIO_PDF';
		}

		$modname = $_ROSARIO['ProgramLoaded'] = $modname . issetVal( $add_query );
	}

	if ( ! empty( $allow_use[ $modname ] ) )
	{
		return true;
	}

	$from_where_sql = User( 'PROFILE_ID' ) != '' ? // Beware, '0' is student!
		"FROM profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
		"FROM staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";

	if ( $cache_all && ! $cached_all )
	{
		// Get all CAN_USE programs from database.
		$allow_use = DBGet( "SELECT MODNAME " . $from_where_sql .
			" AND CAN_USE='Y'", [], [ 'MODNAME' ] );

		$cached_all = true;
	}
	elseif ( ! $cached_all
		// SQL query only 1 program: up to 8ms gain compared to all.
		&& DBGetOne( "SELECT 1 " . $from_where_sql .
			" AND CAN_USE='Y' AND MODNAME='" . $modname . "'" ) )
	{
		$allow_use[ $modname ] = $modname;
	}

	if ( ! empty( $allow_use[ $modname ] ) )
	{
		return true;
	}

	if ( ! $query_string )
	{
		return false;
	}

	if ( ! $cached_all
		&& ( $allow_use_like = DBGet( "SELECT MODNAME " . $from_where_sql .
			" AND CAN_USE='Y'
			AND MODNAME LIKE '" . $modname . "%'", [], [ 'MODNAME' ] ) ) )
	{
		// Fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF.
		$allow_use += $allow_use_like;
	}

	$allow_modnames = array_keys( $allow_use );

	foreach ( $allow_modnames as $allow_modname )
	{
		if ( mb_strpos( $allow_modname, $modname ) === 0
			&& mb_strpos( $query_string, $allow_modname ) === 8 )
		{
			// Fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF.
			$_ROSARIO['ProgramLoaded'] = $allow_modname;

			return true;
		}
	}

	return false;
}
