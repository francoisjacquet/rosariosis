<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'modules/Users/includes/User.fnc.php';

$_REQUEST['staff_id'] = issetVal( $_REQUEST['staff_id'] );

if ( User( 'PROFILE' ) !== 'admin'
	&& User( 'PROFILE' ) !== 'teacher'
	&& $_REQUEST['staff_id']
	&& $_REQUEST['staff_id'] != User( 'STAFF_ID' )
	&& $_REQUEST['staff_id'] !== 'new' )
{
	if ( User( 'USERNAME' ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}

	exit;
}

$categories = [ '1' => 'General_Info', '2' => 'Schedule', 'Other_Info' => 'Other_Info' ];

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
		$category_include = DBGet( "SELECT INCLUDE
			FROM staff_field_categories
			WHERE ID='" . (int) $_REQUEST['category_id'] . "'" );

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

if ( empty( $_REQUEST['category_id'] )
	|| $_REQUEST['category_id'] !== $category_id )
{
	// Fix incoherence with AllowEdit() when category_id present or not in URL.
	$_REQUEST['category_id'] = $category_id;
}

// Allow update for Parents & Teachers if have Edit permissions.

if ( User( 'PROFILE' ) !== 'admin' )
{
	$can_edit_from_where = " FROM profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

	if ( ! User( 'PROFILE_ID' ) )
	{
		$can_edit_from_where = " FROM staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
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
		elseif ( issetVal( $_REQUEST['staff']['PROFILE'] ) === 'admin' )
		{
			// Remove Administrator from profile options.
			$_REQUEST['staff']['PROFILE'] = 'teacher';
		}
	}

	if ( ! empty( $_POST['staff'] )
		|| ! empty( $_FILES ) )
	{
		if ( ! $_REQUEST['staff_id']
			&& UserStaffID() )
		{
			// Fix saving new student when current Staff ID set (in other browser tab).
			unset( $_SESSION['staff_id'] );
		}
		elseif ( $_REQUEST['staff_id'] !== 'new'
			&& $_REQUEST['staff_id'] != UserStaffID() )
		{
			// Fix SQL error on save when current Staff ID was lost (in other browser tab).
			SetUserStaffID( $_REQUEST['staff_id'] );
		}

		$required_error = false;

		if ( ( isset( $_REQUEST['staff']['FIRST_NAME'] )
				&& empty( $_REQUEST['staff']['FIRST_NAME'] ) )
			|| ( isset( $_REQUEST['staff']['LAST_NAME'] )
				&& empty( $_REQUEST['staff']['LAST_NAME'] ) ) )
		{
			// Check FIRST_NAME, LAST_NAME is not null.
			$required_error = true;
		}

		// FJ other fields required.
		$required_error = $required_error || CheckRequiredCustomFields( 'staff_fields', issetVal( $_REQUEST['staff'], [] ) );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['staff'] = FilterCustomFieldsMarkdown( 'staff_fields', 'staff' );

		if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
		{
			// Create account.
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

		if ( ! empty( $_REQUEST['staff']['USERNAME'] ) )
		{
			// Check username uniqueness.
			$existing_username = DBGet( "SELECT 'exists'
				FROM staff
				WHERE USERNAME='" . $_REQUEST['staff']['USERNAME'] . "'
				AND SYEAR='" . UserSyear() . "'
				AND STAFF_ID!='" . (int) UserStaffID() . "'
				UNION SELECT 'exists'
				FROM students
				WHERE USERNAME='" . $_REQUEST['staff']['USERNAME'] . "'" );

			if ( ! empty( $existing_username ) )
			{
				$error[] = _( 'A user with that username already exists. Choose a different username and try again.' );
			}
		}

		if ( UserStaffID() && ! $error )
		{
			// Hook.
			do_action( 'Users/User.php|update_user_checks' );

			$profile_RET = DBGet( "SELECT PROFILE,PROFILE_ID,USERNAME
				FROM staff
				WHERE STAFF_ID='" . UserStaffID() . "'" );

			if ( isset( $_REQUEST['staff']['PROFILE'] )
				&& $_REQUEST['staff']['PROFILE'] != $profile_RET[1]['PROFILE_ID'] )
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

				if ( $profile_RET[1]['PROFILE'] === 'none' )
				{
					// Old Profile was "No Access": Account Activation.
					$user_account_activated = true;
				}
			}

			if ( ! empty( $_REQUEST['staff']['PROFILE_ID'] ) )
			{
				DBQuery( "DELETE FROM staff_exceptions WHERE USER_ID='" . UserStaffID() . "'" );
			}
			elseif ( isset( $_REQUEST['staff']['PROFILE_ID'] ) && $profile_RET[1]['PROFILE_ID'] )
			{
				DBQuery( "DELETE FROM staff_exceptions WHERE USER_ID='" . UserStaffID() . "'" );
				DBQuery( "INSERT INTO staff_exceptions (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
					SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT
					FROM staff s,profile_exceptions e
					WHERE s.STAFF_ID='" . UserStaffID() . "'
					AND s.PROFILE_ID=e.PROFILE_ID" );
			}

			if ( ! $error )
			{
				$sql = "UPDATE staff SET ";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM staff_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				$go = false;

				foreach ( (array) $_REQUEST['staff'] as $column => $value )
				{
					if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
						&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
						&& $value != ''
						&& ! is_numeric( $value ) )
					{
						$error[] = _( 'Please enter valid Numeric data.' );
						continue;
					}

					if ( is_array( $value ) )
					{
						// Select Multiple from Options field type format.
						$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
					}
					elseif ( $column == 'PASSWORD' )
					{
						if ( empty( $value ) )
						{
							continue;
						}

						$value = encrypt_password( $_POST['staff']['PASSWORD'] );
					}

					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					$go = true;
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "'";

				if ( $go )
				{
					DBQuery( $sql );

					$note[] = button( 'check' ) . ' ' . _( 'Your changes were saved.' );

					// Hook.
					do_action( 'Users/User.php|update_user' );
				}
			}
			elseif ( ! $error
				&& ( ! empty( $_POST['values'] )
					|| ! empty( $_FILES ) ) )
			{
				$note[] = button( 'check' ) . ' ' . _( 'Your changes were saved.' );
			}
		}
		elseif ( ! $error ) // New user.
		{
			// Hook.
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
				$sql = "INSERT INTO staff ";
				$fields = 'SYEAR,';
				$values = "'" . UserSyear() . "',";

				if ( basename( $_SERVER['PHP_SELF'] ) == 'index.php' )
				{
					$fields .= 'PROFILE,';
					$values = "'" . Config( 'SYEAR' ) . "'" . mb_substr( $values, mb_strpos( $values, ',' ) ) . "'none',";
				}

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM staff_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				foreach ( (array) $_REQUEST['staff'] as $column => $value )
				{
					if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
						&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
						&& $value != ''
						&& ! is_numeric( $value ) )
					{
						$error[] = _( 'Please enter valid Numeric data.' );
						break;
					}

					if ( is_array( $value ) )
					{
						// Select Multiple from Options field type format.
						$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
					}
					elseif ( $column == 'PASSWORD' )
					{
						$value = encrypt_password( $_POST['staff']['PASSWORD'] );
					}

					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';

						$values .= "'" . $value . "',";
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				DBQuery( $sql );

				$staff_id = DBLastInsertID();

				SetUserStaffID( $_REQUEST['staff_id'] = $staff_id );

				// Hook.
				do_action( 'Users/User.php|create_user' );
			}
		}

		if ( UserStaffID()
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'staff',
				'staff',
				$FileUploadsPath . 'User/' . UserStaffID() . '/'
			);
		}

		if ( UserStaffID()
			&& ! empty( $_FILES['photo'] ) )
		{
			$new_photo_file = ImageUpload(
				'photo',
				[ 'width' => 150, 'height' => 150 ],
				$UserPicturesPath . UserSyear() . '/',
				[],
				'.jpg',
				// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
				// @since 11.0 Fix PHP fatal error if openssl PHP extension is missing
				UserStaffID() . '.' . bin2hex( function_exists( 'openssl_random_pseudo_bytes' ) ?
					openssl_random_pseudo_bytes( 16 ) :
					( function_exists( 'random_bytes' ) ? random_bytes( 16 ) :
						mb_substr( sha1( rand( 999999999, 9999999999 ), true ), 0, 16 ) ) )
			);

			if ( $new_photo_file )
			{
				// Remove old photos.
				$old_photo_files = glob( $UserPicturesPath . UserSyear() . '/' . UserStaffID() . '.*jpg' );

				foreach ( $old_photo_files as $old_photo_file )
				{
					if ( $old_photo_file !== $new_photo_file )
					{
						unlink( $old_photo_file );
					}
				}
			}

			// Hook.
			// @since 9.0 Add $new_photo_file argument to action hook.
			do_action( 'Users/User.php|upload_user_photo', $new_photo_file );
		}

		if ( UserStaffID() )
		{
			require_once 'ProgramFunctions/SendNotification.fnc.php';

			if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
			{
				/**
				 * Send Create User Account email to System Notify address.
				 *
				 * @since 5.7
				 */
				SendNotificationCreateUserAccount( UserStaffID(), $RosarioNotifyAddress );
			}

			if ( ! empty( $staff_id ) ) // $staff_id is set on new.
			{
				// Send New Administrator Account email to System Notify address.
				SendNotificationNewAdministrator( UserStaffID(), $RosarioNotifyAddress );
			}

			if ( ! empty( $user_account_activated ) )
			{
				// @since 5.9 Send Account Activation email notification to User.
				SendNotificationActivateUserAccount( UserStaffID() );
			}
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
	RedirectURL( [ 'modfunc', 'staff' ] );

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
	<script>window.location.href = "index.php?modfunc=logout&reason=account_created&token=" + <?php echo json_encode( $_SESSION['token'] ); ?>;</script>
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
		$delete_sql = UserDeleteSQL( $_REQUEST['staff_id'] );

		DBQuery( $delete_sql );

		// Remove photos on delete.
		$old_photo_files = glob( $UserPicturesPath . UserSyear() . '/' . $_REQUEST['staff_id'] . '.*jpg' );

		foreach ( $old_photo_files as $old_photo_file )
		{
			unlink( $old_photo_file );
		}

		// Hook.
		do_action( 'Users/User.php|delete_user' );

		unset( $_SESSION['staff_id'] );

		// Unset modfunc & staff_id & redirect URL.
		RedirectURL( [ 'modfunc', 'staff_id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'remove_file'
	&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['id'] );

		// Security: sanitize filename with no_accents().
		$filename = no_accents( $_GET['filename'] );

		$file = $FileUploadsPath . 'User/' . UserStaffID() . '/' . $filename;

		DBQuery( "UPDATE staff
			SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE STAFF_ID='" . UserStaffID() . "'" );

		if ( file_exists( $file ) )
		{
			unlink( $file );
		}

		// Unset modfunc, id, filename & redirect URL.
		RedirectURL( [ 'modfunc', 'id', 'filename' ] );
	}
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

Search( 'staff_id', issetVal( $extra, [] ) );

if (  ( UserStaffID()
	|| ( isset( $_REQUEST['staff_id'] )
		&& $_REQUEST['staff_id'] === 'new' ) )
	&& $_REQUEST['modfunc'] !== 'delete'
	&& $_REQUEST['modfunc'] !== 'remove_file' )
{
	if ( User( 'PROFILE_ID' ) )
	{
		$can_use_RET = DBGet( "SELECT MODNAME
			FROM profile_exceptions
			WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
			AND CAN_USE='Y'", [], [ 'MODNAME' ] );
	}
	else
	{
		$can_use_RET = DBGet( "SELECT MODNAME
			FROM staff_exceptions
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND CAN_USE='Y'", [], [ 'MODNAME' ] );
	}

	if ( basename( $_SERVER['PHP_SELF'] ) == 'index.php' )
	{
		$can_use_RET['Users/User.php&category_id=1'] = true;
	}

	if ( mb_strpos( $_REQUEST['modfunc'], 'delete_' ) !== 0
		|| ! empty( $_REQUEST['delete_ok'] ) )
	{
		if ( $_REQUEST['staff_id'] !== 'new' )
		{
			$sql = "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.TITLE,s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
			s.USERNAME,s.PASSWORD,s.SCHOOLS,s.PROFILE,s.PROFILE_ID,s.EMAIL,
			s.LAST_LOGIN,s.SYEAR,s.ROLLOVER_ID
			FROM staff s
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

		echo '<form name="staff" id="staff"	action="' . URLEscape( $form_action ) . '"
			method="POST" enctype="multipart/form-data">';

		$delete_button = '';

		if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
		{
			if ( UserStaffID()
				&& UserStaffID() !== User( 'STAFF_ID' )
				&& User( 'PROFILE' ) === 'admin'
				&& AllowEdit() )
			{
				// @since 5.0 Cannot delete teacher if has course periods.
				$can_delete = DBTransDryRun( UserDeleteSQL( UserStaffID() ) );

				if ( $can_delete )
				{
					$delete_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
						'&staff_id=' . UserStaffID() . "&modfunc=delete" );

					$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
						'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_URL ) . ');' ) . '" />';
				}
			}
		}

		$name = $_REQUEST['staff_id'] !== 'new' ? $staff['FULL_NAME'] . ' - ' . $staff['STAFF_ID'] : '';

		DrawHeader( $name, $delete_button . SubmitButton() );

		// Hook.
		do_action( 'Users/User.php|header' );

		$profile = '';

		if ( UserStaffID() )
		{
			$profile = DBGetOne( "SELECT PROFILE
				FROM staff WHERE
				STAFF_ID='" . UserStaffID() . "'" );
		}

		$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE
			FROM staff_field_categories
			WHERE " . ( $profile ? DBEscapeIdentifier( $profile ) . "='Y'" : "ID='1'" ) . "
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

		$tabs = [];

		foreach ( (array) $categories_RET as $category )
		{
			if ( ! empty( $can_use_RET['Users/User.php&category_id=' . $category['ID']] ) )
			{
				$tabs[] = [
					'title' => $category['TITLE'],
					'link' => ( $_REQUEST['staff_id'] !== 'new' ?
						'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category['ID'] . '&staff_id=' . UserStaffID() :
						'' ),
				];
			}
		}

		$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category_id . '&staff_id=' . UserStaffID();

		echo '<br />';
		PopTable( 'header', $tabs, 'width="100%"' );
		$PopTable_opened = true;

		if ( ! empty( $can_use_RET['Users/User.php&category_id=' . $category_id] ) )
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

				$separator = '<hr>';

				require_once 'modules/Users/includes/Other_Info.inc.php';
			}
		}

		PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div>';
		echo '</form>';
	}
	elseif ( ! empty( $can_use_RET['Users/User.php&category_id=' . $category_id] ) )
	{
		// Is Deleting from Other tab.
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
		}
	}
}
