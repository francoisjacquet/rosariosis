<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'modules/Students/includes/Student.fnc.php';
require_once 'modules/Students/includes/SaveEnrollment.fnc.php';
require_once 'modules/Students/includes/Enrollment.fnc.php';

$_REQUEST['student_id'] = issetVal( $_REQUEST['student_id'] );

if ( User( 'PROFILE' ) !== 'admin'
	&& User( 'PROFILE' ) !== 'teacher'
	&& $_REQUEST['student_id']
	&& $_REQUEST['student_id'] != UserStudentID()
	&& $_REQUEST['student_id'] !== 'new' )
{
	if ( User( 'PROFILE' ) === 'parent' )
	{
		// Parent switching tab with Sibling: set Current Student ID.
		SetUserStudentID( $_REQUEST['student_id'] );
	}
	elseif ( User( 'USERNAME' ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}
	else
	{
		exit;
	}
}

$categories = [
	'1' => 'General_Info',
	'2' => 'Medical',
	'3' => 'Address',
	'4' => 'Comments',
	'Other_Info' => 'Other_Info',
];

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
			FROM student_field_categories
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

// Allow update for Parents, Students & Teachers if have Edit permissions.

if ( User( 'PROFILE' ) !== 'admin' )
{
	$can_edit_from_where = " FROM profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

	if ( User( 'PROFILE' ) !== 'student'
		&& ! User( 'PROFILE_ID' ) )
	{
		$can_edit_from_where = " FROM staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
	}

	$can_edit_RET = DBGet( "SELECT MODNAME " . $can_edit_from_where .
		" AND MODNAME='Students/Student.php&category_id=" . $category_id . "'
		AND CAN_EDIT='Y'" );

	if ( $can_edit_RET )
	{
		$_ROSARIO['allow_edit'] = true;
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['students'].
	AddRequestedDates( 'students', 'post' );

	if ( ! empty( $_POST['students'] )
		|| ! empty( $_POST['values'] )
		|| ! empty( $_FILES ) )
	{
		if ( ! $_REQUEST['student_id']
			&& UserStudentID() )
		{
			// Fix saving new student when current Student ID set (in other browser tab).
			unset( $_SESSION['student_id'] );
		}
		elseif ( $_REQUEST['student_id'] !== 'new'
			&& $_REQUEST['student_id'] != UserStudentID() )
		{
			// Fix SQL error on save when current Student ID was lost (in other browser tab).
			SetUserStudentID( $_REQUEST['student_id'] );
		}

		$required_error = false;

		if (  ( isset( $_REQUEST['students']['FIRST_NAME'] )
				&& empty( $_REQUEST['students']['FIRST_NAME'] ) )
			|| ( isset( $_REQUEST['students']['LAST_NAME'] )
				&& empty( $_REQUEST['students']['LAST_NAME'] ) ) )
		{
			// Check FIRST_NAME, LAST_NAME is not null.
			$required_error = true;
		}

		// FJ other fields required.
		$required_error = $required_error
			|| ( isset( $_REQUEST['students'] ) && CheckRequiredCustomFields( 'custom_fields', $_REQUEST['students'] ) );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['students'] = FilterCustomFieldsMarkdown( 'custom_fields', 'students' );

		// FJ create account.

		if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
		{
			// Check Captcha.

			if ( ! CheckCaptcha() )
			{
				$error[] = _( 'Captcha' );
			}

			// Username & password required.

			if ( empty( $_REQUEST['students']['USERNAME'] )
				|| empty( $_REQUEST['students']['PASSWORD'] ) )
			{
				$required_error = true;
			}

			// Check if trying to hack enrollment.

			if ( isset( $_REQUEST['month_values']['student_enrollment'] )
				|| count( (array) $_REQUEST['values']['student_enrollment'] ) > 2 )
			{
				require_once 'ProgramFunctions/HackingLog.fnc.php';

				HackingLog();
			}

			if ( Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' )
				&& ! empty( $_REQUEST['values']['student_enrollment']['new']['GRADE_ID'] ) )
			{
				// @since 5.9 Automatic Student Account Activation.
				// Enroll student on the same day (even if it is before first school day).
				list(
					$_REQUEST['year_values']['student_enrollment']['new']['START_DATE'],
					$_REQUEST['month_values']['student_enrollment']['new']['START_DATE'],
					$_REQUEST['day_values']['student_enrollment']['new']['START_DATE']
				) = explode( '-', DBDate() );

				// Enroll student with default Rolling / Retention Options (Next grade at current school).
				$_REQUEST['values']['student_enrollment']['new']['NEXT_SCHOOL'] = UserSchool();

				// Enroll student in Default Calendar.
				$_REQUEST['values']['student_enrollment']['new']['CALENDAR_ID'] = DBGetOne( "SELECT CALENDAR_ID
					FROM attendance_calendars
					WHERE SYEAR='" . UserSyear() . "'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND DEFAULT_CALENDAR='Y'" );
			}
		}

		if ( $required_error )
		{
			$error[] = _( 'Please fill in the required fields' );
		}

		if ( ! empty( $_REQUEST['students']['USERNAME'] ) )
		{
			// Check username uniqueness.
			$existing_username = DBGet( "SELECT 'exists'
				FROM staff
				WHERE USERNAME='" . $_REQUEST['students']['USERNAME'] . "'
				AND SYEAR='" . UserSyear() . "'
				UNION SELECT 'exists'
				FROM students
				WHERE USERNAME='" . $_REQUEST['students']['USERNAME'] . "'
				AND STUDENT_ID!='" . (int) UserStudentID() . "'" );

			if ( ! empty( $existing_username ) )
			{
				$error[] = _( 'A user with that username already exists. Choose a different username and try again.' );
			}
		}

		// Add Enrollment / Drop dates.
		AddRequestedDates( 'values' );

		if ( UserStudentID() && ! $error )
		{
			// Hook.
			do_action( 'Students/Student.php|update_student_checks' );

			// Update enrollment.

			if ( ! empty( $_REQUEST['values'] ) && ! $error )
			{
				$old_enrollment_RET = DBGet( "SELECT GRADE_ID,START_DATE,END_DATE
					FROM student_enrollment
					WHERE STUDENT_ID='" . UserStudentID() . "'
					AND SYEAR='" . Config( 'SYEAR' ) . "'" );

				SaveEnrollment();

				if ( count( $old_enrollment_RET ) === 1
					&& ( ! $old_enrollment_RET[1]['START_DATE']
						|| $old_enrollment_RET[1]['START_DATE'] > DBDate() ) )
				{
					// Student was Inactive and is enrolled as of today, in Default School Year: Account Activation.
					$student_account_activated = DBGetOne( "SELECT 1
						FROM student_enrollment
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . Config( 'SYEAR' ) . "'
						AND START_DATE IS NOT NULL
						AND CURRENT_DATE>=START_DATE
						AND (CURRENT_DATE<=END_DATE OR END_DATE IS NULL)" );
				}
			}

			if ( ! empty( $_REQUEST['students'] ) && ! $error )
			{
				$sql = "UPDATE students SET ";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM custom_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				$go = false;

				foreach ( (array) $_REQUEST['students'] as $column => $value )
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

						$value = encrypt_password( $_POST['students']['PASSWORD'] );
					}

					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					$go = true;
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

				if ( $go )
				{
					DBQuery( $sql );

					$note[] = button( 'check' ) . ' ' . _( 'Your changes were saved.' );

					// Hook.
					do_action( 'Students/Student.php|update_student' );
				}
			}
			elseif ( ! $error
				&& ( ! empty( $_POST['values'] )
					|| ! empty( $_FILES ) ) )
			{
				$note[] = button( 'check' ) . ' ' . _( 'Your changes were saved.' );
			}
		}
		elseif ( ! $error ) // New student.
		{
			if ( isset( $_REQUEST['assign_student_id'] )
				&& $_REQUEST['assign_student_id'] !== '' )
			{
				if ( strlen( $_REQUEST['assign_student_id'] ) <= 9
					&& ( $student_id = (int) $_REQUEST['assign_student_id'] ) > 0 )
				{
					if ( DBGetOne( "SELECT STUDENT_ID
						FROM students
						WHERE STUDENT_ID='" . (int) $student_id . "'" ) )
					{
						$error[] = sprintf( _( 'That %s ID is already taken. Please select a different one.' ), Config( 'NAME' ) );
					}
				}
				else
				{
					$error[] = _( 'Please enter valid Numeric data.' );
				}
			}

			// Hook.
			do_action( 'Students/Student.php|create_student_checks' );

			if ( ! $error )
			{
				if ( ! isset( $student_id ) )
				{
					do
					{
						// @since 9.3 Still use DBSeqNextID() for student ID, adapt for MySQL
						$student_id = DBSeqNextID( $DatabaseType === 'mysql' ? 'students' : 'students_student_id_seq' );
					}
					while ( DBGetOne( "SELECT STUDENT_ID
						FROM students
						WHERE STUDENT_ID='" . (int) $student_id . "'" ) );
				}

				$sql = "INSERT INTO students ";
				$fields = 'STUDENT_ID,';
				$values = "'" . $student_id . "',";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM custom_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				foreach ( (array) $_REQUEST['students'] as $column => $value )
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
						$value = encrypt_password( $_POST['students']['PASSWORD'] );
					}

					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';

						$values .= "'" . $value . "',";
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				DBQuery( $sql );

				// Create default food service account for this student.
				// Associate with default food service account and assign other defaults.
				DBQuery( "INSERT INTO food_service_student_accounts (STUDENT_ID,DISCOUNT,BARCODE,ACCOUNT_ID)
					VALUES('" . $student_id . "','','','" . $student_id . "')" );

				// Fix SQL error, Check if Account ID already exists
				$fs_account_id_exists = DBGetOne( "SELECT 1
					FROM food_service_accounts
					WHERE ACCOUNT_ID='" . (int) $student_id . "'" );

				if ( ! $fs_account_id_exists )
				{
					DBQuery( "INSERT INTO food_service_accounts (ACCOUNT_ID,BALANCE,TRANSACTION_ID)
						VALUES('" . $student_id . "','0.00','0');" );
				}

				// Create enrollment.
				SaveEnrollment();

				SetUserStudentID( $_REQUEST['student_id'] = $student_id );

				// Hook.
				do_action( 'Students/Student.php|create_student' );
			}
		}

		if ( UserStudentID()
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'students',
				'students',
				$FileUploadsPath . 'Student/' . UserStudentID() . '/'
			);

			if ( ! empty( $_REQUEST['person_id'] ) )
			{
				$uploaded = FilesUploadUpdate(
					'people',
					'valuespeople',
					$FileUploadsPath . 'Contact/' . $_REQUEST['person_id'] . '/'
				);
			}

			if ( ! empty( $_REQUEST['address_id'] ) )
			{
				$uploaded = FilesUploadUpdate(
					'address',
					'valuesaddress',
					$FileUploadsPath . 'Address/' . $_REQUEST['address_id'] . '/'
				);
			}
		}

		if ( UserStudentID()
			&& ! empty( $_FILES['photo'] ) )
		{
			$new_photo_file = ImageUpload(
				'photo',
				[ 'width' => 150, 'height' => 150 ],
				$StudentPicturesPath . UserSyear() . '/',
				[],
				'.jpg',
				// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
				// @since 11.0 Fix PHP fatal error if openssl PHP extension is missing
				UserStudentID() . '.' . bin2hex( function_exists( 'openssl_random_pseudo_bytes' ) ?
					openssl_random_pseudo_bytes( 16 ) :
					( function_exists( 'random_bytes' ) ? random_bytes( 16 ) :
						mb_substr( sha1( rand( 999999999, 9999999999 ), true ), 0, 16 ) ) )
			);

			if ( $new_photo_file )
			{
				// Remove old photos.
				$old_photo_files = glob( $StudentPicturesPath . UserSyear() . '/' . UserStudentID() . '.*jpg' );

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
			do_action( 'Students/Student.php|upload_student_photo', $new_photo_file );
		}

		if ( UserStudentID() )
		{
			require_once 'ProgramFunctions/SendNotification.fnc.php';

			if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
			{
				/**
				 * Send Create Student Account email to System Notify address.
				 *
				 * @since 5.7
				 */
				SendNotificationCreateStudentAccount( UserStudentID(), $RosarioNotifyAddress );
			}

			if ( ! empty( $student_account_activated ) )
			{
				// @since 5.9 Send Account Activation email notification to Student.
				SendNotificationActivateStudentAccount( UserStudentID() );
			}
		}
	}

	if ( ! in_array( $include, $categories ) )
	{
		if ( ! mb_strpos( $include, '/' ) )
		{
			require 'modules/Students/includes/' . $include . '.inc.php';
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

	if ( $error
		&& ! UserStudentID() )
	{
		$_REQUEST['student_id'] = 'new';
	}

	// Unset modfunc, students (& values if no current Student).
	$unset_request = [ 'modfunc', 'students' ];

	if ( ! UserStudentID() )
	{
		$unset_request[] = 'values';
	}

	// Unset & redirect URL.
	RedirectURL( $unset_request );

	// SHOULD THIS BE HERE???
	/*if ( !UserStudentID() )
unset( $_REQUEST['values'] );

$_SESSION['_REQUEST_vars']['modfunc'] = false;
unset( $_SESSION['_REQUEST_vars']['students'] );
unset( $_SESSION['_REQUEST_vars']['values'] );*/
}

if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
{
	if ( isset( $_REQUEST['student_id'] )
		&& $_REQUEST['student_id'] === 'new' )
	{
		$_ROSARIO['HeaderIcon'] = 'Students';

		DrawHeader( _( 'Add a Student' ) );
	}
	else
	{
		DrawHeader( ProgramTitle() );
	}
}
elseif ( ! UserStudentID() )
{
	// FJ create account.
	$_ROSARIO['HeaderIcon'] = 'Students';

	DrawHeader( _( 'Create Student Account' ) );
}
else
{
	// Account created.
	// Hook.
	do_action( 'Students/Student.php|account_created' );

	// Return to index.
	// @since 5.9 Automatic Student Account Activation.
	$reason = Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) ?
		'account_activated' : 'account_created';
	?>
	<script>window.location.href = "index.php?modfunc=logout&reason=" + <?php echo json_encode( $reason ); ?> + "&token=" + <?php echo json_encode( $_SESSION['token'] ); ?>;</script>
	<?php
	exit;
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php'
	&& User( 'PROFILE' ) === 'admin'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Student' ) ) )
	{
		$delete_sql = StudentDeleteSQL( $_REQUEST['student_id'] );

		DBQuery( $delete_sql );

		// Remove photos on delete.
		$old_photo_files = glob( $StudentPicturesPath . '*/' . $_REQUEST['student_id'] . '.*jpg' );

		foreach ( $old_photo_files as $old_photo_file )
		{
			unlink( $old_photo_file );
		}

		// Hook.
		do_action( 'Students/Student.php|delete_student' );

		unset( $_SESSION['student_id'] );

		// Unset modfunc & student_id & redirect URL.
		RedirectURL( [ 'modfunc', 'student_id' ] );
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

		if ( ! empty( $_REQUEST['person_id'] ) )
		{
			$file = $FileUploadsPath . 'Contact/' . $_REQUEST['person_id'] . '/' . $filename;

			DBQuery( "UPDATE people
				SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" );
		}
		elseif ( ! empty( $_REQUEST['address_id'] ) )
		{
			$file = $FileUploadsPath . 'Address/' . $_REQUEST['address_id'] . '/' . $filename;

			DBQuery( "UPDATE address
				SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
		}
		else
		{
			$file = $FileUploadsPath . 'Student/' . UserStudentID() . '/' . $filename;

			DBQuery( "UPDATE students
				SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE STUDENT_ID='" . UserStudentID() . "'" );
		}

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

Search( 'student_id' );

if (  ( UserStudentID()
	|| isset( $_REQUEST['student_id'] ) && $_REQUEST['student_id'] === 'new' )
	&& $_REQUEST['modfunc'] !== 'delete'
	&& $_REQUEST['modfunc'] !== 'remove_file' )
{
	// MODNAME LIKE 'Students/Student.php%'.

	if ( User( 'PROFILE_ID' )
		|| User( 'PROFILE' ) === 'student' )
	{
		$can_use_sql = "SELECT MODNAME
			FROM profile_exceptions
			WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
			AND CAN_USE='Y'
			AND MODNAME LIKE 'Students/Student.php%'";
	}
	else
	{
		$can_use_sql = "SELECT MODNAME
			FROM staff_exceptions
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND CAN_USE='Y'
			AND MODNAME LIKE 'Students/Student.php%'";
	}

	$can_use_RET = DBGet( $can_use_sql, [], [ 'MODNAME' ] );

	if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
	{
		// Create account.
		$can_use_RET['Students/Student.php&category_id=1'] = true;
	}

	//FJ General_Info only for new student
	//$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE FROM student_field_categories ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );
	$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE
		FROM student_field_categories
		WHERE " . ( $_REQUEST['student_id'] !== 'new' ? 'TRUE' : "ID='1'" ) .
		" ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	if ( mb_strpos( $_REQUEST['modfunc'], 'delete_' ) !== 0
		|| ! empty( $_REQUEST['delete_ok'] ) )
	{
		if ( $_REQUEST['student_id'] !== 'new' )
		{
			$sql = "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
			s.USERNAME,s.PASSWORD,s.LAST_LOGIN,
			(SELECT ID
				FROM student_enrollment
				WHERE SYEAR='" . UserSyear() . "'
				AND STUDENT_ID=s.STUDENT_ID
				ORDER BY START_DATE DESC,END_DATE DESC
				LIMIT 1) AS ENROLLMENT_ID
			FROM students s
			WHERE s.STUDENT_ID='" . UserStudentID() . "'";

			$student = DBGet( $sql );
			$student = $student[1];

			$school = DBGet( "SELECT SCHOOL_ID,GRADE_ID
				FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND ('" . DBDate() . "' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL)" );
		}

		$delete_button = '';

		if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
		{
			$form_action = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $category_id . '&student_id=' . UserStudentID() . '&modfunc=update';

			if ( UserStudentID()
				&& User( 'PROFILE' ) === 'admin'
				&& AllowEdit() )
			{
				// Can't delete Student if has Schedule, Attendance, Grades,
				// Discipline, Billing records, etc.
				$can_delete = DBTransDryRun( StudentDeleteSQL( UserStudentID() ) );

				if ( $can_delete )
				{
					$delete_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
						'&student_id=' . UserStudentID() . "&modfunc=delete" );

					$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
						'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_URL ) . ');' ) . '" />';
				}
			}
		}
		else
		{
			// @since 6.0 Create Student Account: add school_id param to URL.
			$form_action = 'index.php?create_account=student&student_id=new&school_id=' . UserSchool() . '&modfunc=update';
		}

		echo '<form name="student" id="student"	action="' . URLEscape( $form_action ) . '"
			method="POST" enctype="multipart/form-data">';

		$name = $_REQUEST['student_id'] !== 'new' ? $student['FULL_NAME'] . ' - ' . $student['STUDENT_ID'] : '';

		DrawHeader( $name, $delete_button . SubmitButton() );

		// Hook.
		do_action( 'Students/Student.php|header' );

		$tabs = [];

		foreach ( (array) $categories_RET as $category )
		{
			if ( isset( $can_use_RET['Students/Student.php&category_id=' . $category['ID']] ) )
			{
				//FJ Remove $_REQUEST['include']
				/*if ( $category['ID']=='1')
				$include = 'General_Info';
				elseif ( $category['ID']=='3')
				$include = 'Address';
				elseif ( $category['ID']=='2')
				$include = 'Medical';
				elseif ( $category['ID']=='4')
				$include = 'Comments';
				elseif ( $category['INCLUDE'])
				$include = $category['INCLUDE'];
				else
				$include = 'Other_Info';*/

				$tabs[] = [
					'title' => $category['TITLE'],
					'link' => ( $_REQUEST['student_id'] !== 'new' ?
						'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category['ID'] . '&student_id=' . UserStudentID() :
						'' ),
				];
			}
		}

		$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $category_id . '&student_id=' . UserStudentID();

		echo '<br />';

		echo PopTable( 'header', $tabs, 'width="100%"' );

		$PopTable_opened = true;

		if ( ! empty( $can_use_RET['Students/Student.php&category_id=' . $category_id] ) )
		{
			if ( ! mb_strpos( $include, '/' ) )
			{
				require 'modules/Students/includes/' . $include . '.inc.php';
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

				require_once 'modules/Students/includes/Other_Info.inc.php';
			}
		}

		echo PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div>';
		echo '</form>';
	}
	elseif ( ! empty( $can_use_RET['Students/Student.php&category_id=' . $category_id] ) )
	{
		// Is Deleting from Other tab.
		if ( ! mb_strpos( $include, '/' ) )
		{
			require 'modules/Students/includes/' . $include . '.inc.php';
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
