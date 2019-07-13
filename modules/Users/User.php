<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';

if ( User( 'PROFILE' ) !== 'admin' && User( 'PROFILE' ) !== 'teacher' && $_REQUEST['staff_id'] && $_REQUEST['staff_id'] != User( 'STAFF_ID' ) && $_REQUEST['staff_id'] !== 'new' )
{
	if ( User( 'USERNAME' ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}

	exit;
}

$categories = array( '1' => 'General_Info', '2' => 'Schedule', 'Other_Info' => 'Other_Info' );

if ( ! isset( $_REQUEST['category_id'] ) )
{
	$category_id = '1';
	$include = 'General_Info';
}
else
{
	$category_id = $_REQUEST['category_id'];

	if ( in_array( $_REQUEST['category_id'], array_keys( $categories ) ) )
	{
		$include = $categories[$category_id];
	}
	else
	{
		$category_include = DBGet( "SELECT INCLUDE FROM STAFF_FIELD_CATEGORIES WHERE ID='" . $_REQUEST['category_id'] . "'" );

		if ( ! empty( $category_include ) )
		{
			$include = $category_include[1]['INCLUDE'];

			if ( empty( $include ) )
			{
				$include = $categories['Other_Info'];
			}
		}

		//FJ Prevent $_REQUEST['category_id'] hacking
		else
		{
			$category_id = '1';
			$include = 'General_Info';
		}
	}
}

// Allow update for Parents & Teachers if have Edit permissions.

if ( User( 'PROFILE' ) !== 'admin' )
{
	$can_edit_from_where = " FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

	if ( ! User( 'PROFILE_ID' ) )
	{
		$can_edit_from_where = " FROM STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
	}

	$can_edit_RET = DBGet( "SELECT MODNAME " . $can_edit_from_where .
		" AND MODNAME='Users/User.php&category_id=" . $category_id . "'
		AND CAN_EDIT='Y'" );

	if ( $can_edit_RET )
	{
		$_ROSARIO['allow_edit'] = true;
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['staff'].
	AddRequestedDates( 'staff', 'post' );

	if ( isset( $_REQUEST['staff']['SCHOOLS'] ) )
	{
		$staff_schools = '';

		// FJ remove empty schools.

		foreach ( (array) $_REQUEST['staff']['SCHOOLS'] as $school_id => $yes )
		{
			if ( $yes )
			{
				$staff_schools .= $school_id . ',';
			}
		}

		// Build schools format: ,1,2,
		$_REQUEST['staff']['SCHOOLS'] = $staff_schools ? ',' . $staff_schools : '';

		// FJ remove Schools for Parents.

		if ( isset( $_REQUEST['staff']['PROFILE'] )
			&& $_REQUEST['staff']['PROFILE'] == 'parent' )
		{
			$_REQUEST['staff']['SCHOOLS'] = '';
		}

		// FJ reset current school if updating self schools.

		if ( User( 'STAFF_ID' ) == UserStaffID() )
		{
			unset( $_SESSION['UserSchool'] );
		}
	}

	// Admin Schools restriction.

	if (  ( User( 'PROFILE' ) === 'admin'
		&& ! AllowEdit( 'Users/User.php&category_id=1&schools' ) )
		|| User( 'PROFILE' ) !== 'admin' )
	{
		if ( UserStaffID() )
		{
			// Restricted!
			unset( $_REQUEST['staff']['SCHOOLS'] );
		}
		elseif ( UserSchool() ) // No set if "Create User Account".
		{
			// Assign new user to current school only.
			$_REQUEST['staff']['SCHOOLS'] = ',' . UserSchool() . ',';
		}
	}

	// Admin Profile restriction.

	if (  ( User( 'PROFILE' ) === 'admin'
		&& ! AllowEdit( 'Users/User.php&category_id=1&user_profile' )
		&& isset( $_REQUEST['staff']['PROFILE'] ) )
		|| User( 'PROFILE' ) !== 'admin' )
	{
		if ( UserStaffID() )
		{
			// Restricted!
			unset( $_REQUEST['staff']['PROFILE'] );
			unset( $_REQUEST['staff']['PROFILE_ID'] );
		}

		// New User.
		elseif ( $_REQUEST['staff']['PROFILE'] === 'admin' )
		{
			// Remove Administrator from profile options.
			$_REQUEST['staff']['PROFILE'] = 'teacher';
		}
	}

	if ( ! empty( $_POST['staff'] )
		|| ! empty( $_FILES ) )
	{
		$required_error = false;

		// FJ fix SQL bug FIRST_NAME, LAST_NAME is null.

		if (  ( isset( $_REQUEST['staff']['FIRST_NAME'] ) && empty( $_REQUEST['staff']['FIRST_NAME'] ) ) || ( isset( $_REQUEST['staff']['LAST_NAME'] ) && empty( $_REQUEST['staff']['LAST_NAME'] ) ) )
		{
			$required_error = true;
		}

		// FJ other fields required.
		$required_error = $required_error || CheckRequiredCustomFields( 'STAFF_FIELDS', $_REQUEST['staff'] );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['staff'] = FilterCustomFieldsMarkdown( 'STAFF_FIELDS', 'staff' );

		// FJ create account.

		if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
		{
			// Check Captcha.

			if ( ! CheckCaptcha() )
			{
				$error[] = _( 'Captcha' );
			}

			// Username & password required.

			if ( empty( $_REQUEST['staff']['USERNAME'] )
				|| empty( $_REQUEST['staff']['PASSWORD'] ) )
			{
				$required_error = true;
			}

			// Check if trying to hack profile (would result in an SQL error).

			if ( isset( $_REQUEST['staff']['PROFILE'] ) )
			{
				require_once 'ProgramFunctions/HackingLog.fnc.php';
				HackingLog();
			}
		}

		if ( $required_error )
		{
			$error[] = _( 'Please fill in the required fields' );
		}

		//check username unicity
		$existing_username = DBGet( "SELECT 'exists'
			FROM STAFF
			WHERE USERNAME='" . $_REQUEST['staff']['USERNAME'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND STAFF_ID!='" . UserStaffID() . "'
			UNION SELECT 'exists'
			FROM STUDENTS
			WHERE USERNAME='" . $_REQUEST['staff']['USERNAME'] . "'" );

		if ( ! empty( $existing_username ) )
		{
			$error[] = _( 'A user with that username already exists. Choose a different username and try again.' );
		}

		if ( UserStaffID() && ! $error )
		{
			//hook
			do_action( 'Users/User.php|update_user_checks' );

			$profile_RET = DBGet( "SELECT PROFILE,PROFILE_ID,USERNAME FROM STAFF WHERE STAFF_ID='" . UserStaffID() . "'" );

			if ( isset( $_REQUEST['staff']['PROFILE'] ) && $_REQUEST['staff']['PROFILE'] != $profile_RET[1]['PROFILE_ID'] )
			{
				if ( $_REQUEST['staff']['PROFILE'] == 'admin' )
				{
					$_REQUEST['staff']['PROFILE_ID'] = '1';
				}
				elseif ( $_REQUEST['staff']['PROFILE'] == 'teacher' )
				{
					$_REQUEST['staff']['PROFILE_ID'] = '2';
				}
				elseif ( $_REQUEST['staff']['PROFILE'] == 'parent' )
				{
					$_REQUEST['staff']['PROFILE_ID'] = '3';
				}
			}

			if ( ! empty( $_REQUEST['staff']['PROFILE_ID'] ) )
			{
				DBQuery( "DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='" . UserStaffID() . "'" );
			}
			elseif ( isset( $_REQUEST['staff']['PROFILE_ID'] ) && $profile_RET[1]['PROFILE_ID'] )
			{
				DBQuery( "DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='" . UserStaffID() . "'" );
				DBQuery( "INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT) SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT FROM STAFF s,PROFILE_EXCEPTIONS e WHERE s.STAFF_ID='" . UserStaffID() . "' AND s.PROFILE_ID=e.PROFILE_ID" );
			}

			if ( ! $error )
			{
				$sql = "UPDATE STAFF SET ";
				$fields_RET = DBGet( "SELECT ID,TYPE FROM STAFF_FIELDS ORDER BY SORT_ORDER", array(), array( 'ID' ) );
				$go = false;

				foreach ( (array) $_REQUEST['staff'] as $column_name => $value )
				{
					if ( ! is_array( $value ) )
					{
						//FJ check numeric fields

						if ( $fields_RET[str_replace( 'CUSTOM_', '', $column_name )][1]['TYPE'] == 'numeric' && $value != '' && ! is_numeric( $value ) )
						{
							$error[] = _( 'Please enter valid Numeric data.' );
							continue;
						}

						//FJ add password encryption

						if ( $column_name !== 'PASSWORD' )
						{
							$sql .= "$column_name='" . $value . "',";
							$go = true;
						}

						if ( $column_name == 'PASSWORD' && ! empty( $value ) && $value !== str_repeat( '*', 8 ) )
						{
							$value = str_replace( "''", "'", $value );
							$sql .= "$column_name='" . encrypt_password( $value ) . "',";
							$go = true;
						}
					}
					else
					{
						// Select multiple from options.
						// FJ fix bug none selected not saved.
						$sql_multiple_input = '';

						foreach ( (array) $value as $val )
						{
							if ( $val )
							{
								$sql_multiple_input .= $val . '||';
							}
						}

						if ( $sql_multiple_input )
						{
							$sql_multiple_input = "||" . $sql_multiple_input;
						}

						$sql .= $column_name . "='" . $sql_multiple_input . "',";

						$go = true;
					}
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "'";

				if ( $go )
				{
					DBQuery( $sql );

					//hook
					do_action( 'Users/User.php|update_user' );
				}
			}
		}
		elseif ( ! $error ) //new user
		{
			//hook
			do_action( 'Users/User.php|create_user_checks' );

			if ( $_REQUEST['staff']['PROFILE'] == 'admin' )
			{
				$_REQUEST['staff']['PROFILE_ID'] = '1';
			}
			elseif ( $_REQUEST['staff']['PROFILE'] == 'teacher' )
			{
				$_REQUEST['staff']['PROFILE_ID'] = '2';
			}
			elseif ( $_REQUEST['staff']['PROFILE'] == 'parent' )
			{
				$_REQUEST['staff']['PROFILE_ID'] = '3';
			}

			if ( ! $error )
			{
				$staff_id = DBSeqNextID( 'STAFF_SEQ' );

				$sql = "INSERT INTO STAFF ";
				$fields = 'SYEAR,STAFF_ID,';
				$values = "'" . UserSyear() . "','" . $staff_id . "',";

				if ( basename( $_SERVER['PHP_SELF'] ) == 'index.php' )
				{
					$fields .= 'PROFILE,';
					$values = "'" . Config( 'SYEAR' ) . "'" . mb_substr( $values, mb_strpos( $values, ',' ) ) . "'none',";
				}

				$fields_RET = DBGet( "SELECT ID,TYPE FROM STAFF_FIELDS ORDER BY SORT_ORDER", array(), array( 'ID' ) );

				foreach ( (array) $_REQUEST['staff'] as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						//FJ check numeric fields

						if ( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric' && $value != '' && ! is_numeric( $value ) )
						{
							$error[] = _( 'Please enter valid Numeric data.' );
							break;
						}

						$fields .= DBEscapeIdentifier( $column ) . ',';

						//FJ add password encryption

						if ( $column !== 'PASSWORD' )
						{
							$values .= "'" . $value . "',";
						}
						else
						{
							$value = str_replace( "''", "'", $value );
							$values .= "'" . encrypt_password( $value ) . "',";
						}
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				DBQuery( $sql );

				SetUserStaffID( $_REQUEST['staff_id'] = $staff_id );

				//hook
				do_action( 'Users/User.php|create_user' );

				// Notify the network admin that a new admin has been created.

				if ( $_REQUEST['staff']['PROFILE_ID'] == 1
					&& filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
				{
					//FJ add SendEmail function
					require_once 'ProgramFunctions/SendEmail.fnc.php';

					$to = $RosarioNotifyAddress;

					$admin_name = $_REQUEST['staff']['FIRST_NAME'] . ' ' . $_REQUEST['staff']['LAST_NAME'];
					$subject = sprintf( 'New Admin Added: %s', $admin_name );

					$admin_username = empty( $_REQUEST['staff']['USERNAME'] ) ? '[no username]' : $_REQUEST['staff']['USERNAME'];

					if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
					{
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					}
					else
					{
						$ip = $_SERVER['REMOTE_ADDR'];
					}

					$message = sprintf( 'New User: %s
Added by: %s
Remote IP: %s', $admin_username, User( 'NAME' ), $ip );

					SendEmail( $to, $subject, $message );
				}
			}
		}

		if ( UserStaffID()
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'STAFF',
				'staff',
				$FileUploadsPath . 'User/' . UserStaffID() . '/'
			);
		}

		if ( UserStaffID()
			&& ! empty( $_FILES['photo'] ) )
		{
			// $new_photo_file = FileUpload('photo', $UserPicturesPath.UserSyear().'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', UserStaffID());

			$new_photo_file = ImageUpload(
				'photo',
				array( 'width' => 150, 'height' => '150' ),
				$UserPicturesPath . UserSyear() . '/',
				array(),
				'.jpg',
				UserStaffID()
			);

			// Hook.
			do_action( 'Users/User.php|upload_user_photo' );
		}
	}

	if ( ! in_array( $include, $categories ) )
	{
		if ( ! mb_strpos( $include, '/' ) )
		{
			require 'modules/Users/includes/' . $include . '.inc.php';
		}
		else // ex.: Food Service, custom module or plugin.
		{
			if ( file_exists( 'plugins/' . $include . '.inc.php' ) )
			{
				// @since 4.5 Include Student/User Info tab from custom plugin.
				require 'plugins/' . $include . '.inc.php';
			}
			else
			{
				require 'modules/' . $include . '.inc.php';
			}
		}
	}

	if ( $error && ! UserStaffID() )
	{
		$_REQUEST['staff_id'] = 'new';
	}

	// Unset modfunc & staff & redirect URL.
	RedirectURL( array( 'modfunc', 'staff' ) );

	if ( User( 'STAFF_ID' ) == $_REQUEST['staff_id'] )
	{
		unset( $_ROSARIO['User'] );
	}
}

if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
{
	if ( isset( $_REQUEST['staff_id'] )
		&& $_REQUEST['staff_id'] === 'new' )
	{
		$_ROSARIO['HeaderIcon'] = 'Users';

		DrawHeader( _( 'Add a User' ) );
	}
	else
	{
		DrawHeader( ProgramTitle() );
	}
}
elseif ( ! UserStaffID() )
{
	// FJ create account.
	$_ROSARIO['HeaderIcon'] = 'Users';

	DrawHeader( _( 'Create User Account' ) );
}
else
{
	// Account created, return to index.
	?>
	<script>window.location.href = "index.php?modfunc=logout&reason=account_created";</script>
<?php
exit;
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php'
	&& UserStaffID() !== User( 'STAFF_ID' )
	&& User( 'PROFILE' ) === 'admin'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'User' ) ) )
	{
		$delete_sql = "DELETE FROM PROGRAM_USER_CONFIG
			WHERE USER_ID='" . UserStaffID() . "';";

		$delete_sql .= "DELETE FROM STAFF_EXCEPTIONS
			WHERE USER_ID='" . UserStaffID() . "';";

		$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS
			WHERE STAFF_ID='" . UserStaffID() . "';";

		$delete_sql .= "DELETE FROM STAFF
			WHERE STAFF_ID='" . UserStaffID() . "';";

		DBQuery( $delete_sql );

		// Hook.
		do_action( 'Users/User.php|delete_user' );

		unset( $_SESSION['staff_id'] );

		// Unset modfunc & staff_id & redirect URL.
		RedirectURL( array( 'modfunc', 'staff_id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'remove_file'
	&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['id'] );

		$file = $FileUploadsPath . 'User/' . UserStaffID() . '/' . $_REQUEST['filename'];

		DBQuery( "UPDATE STAFF SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE STAFF_ID='" . UserStaffID() . "'" );

		if ( file_exists( $file ) )
		{
			unset( $file );
		}

		// Unset modfunc, id, filename & redirect URL.
		RedirectURL( array( 'modfunc', 'id', 'filename' ) );
	}
}

echo ErrorMessage( $error );

Search( 'staff_id', ( isset( $extra ) ? $extra : array() ) );

if (  ( UserStaffID()
	|| ( isset( $_REQUEST['staff_id'] )
		&& $_REQUEST['staff_id'] === 'new' ) )
	&& $_REQUEST['modfunc'] !== 'delete'
	&& $_REQUEST['modfunc'] !== 'remove_file' )
{
	if ( $_REQUEST['staff_id'] !== 'new' )
	{
		$sql = "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		s.TITLE,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
		s.USERNAME,s.PASSWORD,s.SCHOOLS,s.PROFILE,s.PROFILE_ID,s.PHONE,s.EMAIL,
		s.LAST_LOGIN,s.SYEAR,s.ROLLOVER_ID
		FROM STAFF s
		WHERE s.STAFF_ID='" . UserStaffID() . "'";

		$staff = DBGet( $sql );

		$staff = $staff[1];
	}

	if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
	{
		$form_action = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $category_id . '&staff_id=' . UserStaffID() . '&modfunc=update';
	}
	else
	{
		// FJ create account.
		$form_action = 'index.php?create_account=user&staff_id=new&modfunc=update';
	}

	echo '<form name="staff" id="staff"	action="' . $form_action . '"
		method="POST" enctype="multipart/form-data">';

	if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
	{
		if ( UserStaffID()
			&& UserStaffID() !== User( 'STAFF_ID' )
			&& User( 'PROFILE' ) === 'admin'
			&& AllowEdit() )
		{
			$delete_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
				"&modfunc=delete'";

			$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_URL . ');" />';
		}
	}

	if ( $_REQUEST['staff_id'] !== 'new' )
	{
		$name = $staff['FULL_NAME'] . ' - ' . $staff['STAFF_ID'];
	}

	DrawHeader( $name, $delete_button . SubmitButton() );

	// Hook.
	do_action( 'Users/User.php|header' );

	if ( User( 'PROFILE_ID' ) )
	{
		$can_use_RET = DBGet( "SELECT MODNAME
			FROM PROFILE_EXCEPTIONS
			WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
			AND CAN_USE='Y'", array(), array( 'MODNAME' ) );
	}
	else
	{
		$can_use_RET = DBGet( "SELECT MODNAME
			FROM STAFF_EXCEPTIONS
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND CAN_USE='Y'", array(), array( 'MODNAME' ) );
	}

	//FJ create account

	if ( basename( $_SERVER['PHP_SELF'] ) == 'index.php' )
	{
		$can_use_RET['Users/User.php&category_id=1'] = true;
	}

	$profile = DBGetOne( "SELECT PROFILE
		FROM STAFF WHERE
		STAFF_ID='" . UserStaffID() . "'" );

	$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE FROM STAFF_FIELD_CATEGORIES WHERE " . ( $profile ? mb_strtoupper( $profile ) . '=\'Y\'' : 'ID=\'1\'' ) . " ORDER BY SORT_ORDER,TITLE" );

	foreach ( (array) $categories_RET as $category )
	{
		if ( $can_use_RET['Users/User.php&category_id=' . $category['ID']] )
		{
			//FJ Remove $_REQUEST['include']
			/*if ( $category['ID']=='1')
			$include = 'General_Info';
			elseif ( $category['ID']=='2')
			$include = 'Schedule';
			elseif ( $category['INCLUDE'])
			$include = $category['INCLUDE'];
			else
			$include = 'Other_Info';*/

			$tabs[] = array(
				'title' => $category['TITLE'],
				'link' => ( $_REQUEST['staff_id'] !== 'new' ?
					'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category['ID'] . '&staff_id=' . UserStaffID() :
					'' ),
			);
		}
	}

	$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category_id . '&staff_id=' . UserStaffID();

	echo '<br />';
	PopTable( 'header', $tabs, 'width="100%"' );
	$PopTable_opened = true;

	if ( $can_use_RET['Users/User.php&category_id=' . $category_id] )
	{
		if ( ! mb_strpos( $include, '/' ) )
		{
			require 'modules/Users/includes/' . $include . '.inc.php';
		}
		else
		{
			if ( file_exists( 'plugins/' . $include . '.inc.php' ) )
			{
				// @since 4.5 Include Student/User Info tab from custom plugin.
				require 'plugins/' . $include . '.inc.php';
			}
			else
			{
				require 'modules/' . $include . '.inc.php';
			}

			$separator = '<hr />';

			require_once 'modules/Users/includes/Other_Info.inc.php';
		}
	}

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
