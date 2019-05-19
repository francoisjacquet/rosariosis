<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';

if ( User( 'PROFILE' ) !== 'admin' && User( 'PROFILE' ) !== 'teacher' && $_REQUEST['student_id'] && $_REQUEST['student_id'] != UserStudentID() && $_REQUEST['student_id'] !== 'new' )
{
	if ( User( 'USERNAME' ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}

	exit;
}

$categories = array(
	'1' => 'General_Info',
	'2' => 'Medical',
	'3' => 'Address',
	'4' => 'Comments',
	'Other_Info' => 'Other_Info',
);

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
			FROM STUDENT_FIELD_CATEGORIES
			WHERE ID='" . $_REQUEST['category_id'] . "'" );

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

// Allow update for Parents, Students & Teachers if have Edit permissions.

if ( User( 'PROFILE' ) !== 'admin' )
{
	$can_edit_from_where = " FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'";

	if ( User( 'PROFILE' ) !== 'student'
		&& ! User( 'PROFILE_ID' ) )
	{
		$can_edit_from_where = " FROM STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'";
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
		$required_error = false;

		//FJ fix SQL bug FIRST_NAME, LAST_NAME is null

		if (  ( isset( $_REQUEST['students']['FIRST_NAME'] ) && empty( $_REQUEST['students']['FIRST_NAME'] ) ) || ( isset( $_REQUEST['students']['LAST_NAME'] ) && empty( $_REQUEST['students']['LAST_NAME'] ) ) )
		{
			$required_error = true;
		}

		// FJ other fields required.
		$required_error = $required_error || CheckRequiredCustomFields( 'CUSTOM_FIELDS', $_REQUEST['students'] );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['students'] = FilterCustomFieldsMarkdown( 'CUSTOM_FIELDS', 'students' );

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

			if ( isset( $_REQUEST['month_values']['STUDENT_ENROLLMENT'] )
				|| count( (array) $_REQUEST['values']['STUDENT_ENROLLMENT'] ) > 1 )
			{
				require_once 'ProgramFunctions/HackingLog.fnc.php';

				HackingLog();
			}
		}

		if ( $required_error )
		{
			$error[] = _( 'Please fill in the required fields' );
		}

		// Check username unicity.
		$existing_username = DBGet( "SELECT 'exists'
			FROM STAFF
			WHERE USERNAME='" . $_REQUEST['students']['USERNAME'] . "'
			AND SYEAR='" . UserSyear() . "'
			UNION SELECT 'exists'
			FROM STUDENTS
			WHERE USERNAME='" . $_REQUEST['students']['USERNAME'] . "'
			AND STUDENT_ID!='" . UserStudentID() . "'" );

		if ( ! empty( $existing_username ) )
		{
			$error[] = _( 'A user with that username already exists. Choose a different username and try again.' );
		}

		if ( UserStudentID() && ! $error )
		{
			//hook
			do_action( 'Students/Student.php|update_student_checks' );

			// update enrollment

			if ( ! empty( $_REQUEST['values'] ) && ! $error )
			{
				require_once 'modules/Students/includes/SaveEnrollment.fnc.php';
				SaveEnrollment();
			}

			if ( ! empty( $_REQUEST['students'] ) && ! $error )
			{
				$sql = "UPDATE STUDENTS SET ";
				$fields_RET = DBGet( "SELECT ID,TYPE FROM CUSTOM_FIELDS ORDER BY SORT_ORDER", array(), array( 'ID' ) );
				$go = false;

				foreach ( (array) $_REQUEST['students'] as $column => $value )
				{
					if ( 1 ) //!empty($value) || $value=='0')
					{
						//FJ check numeric fields

						if ( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric' && $value != '' && ! is_numeric( $value ) )
						{
							$error[] = _( 'Please enter valid Numeric data.' );
							continue;
						}

						if ( ! is_array( $value ) )
						{
							//FJ add password encryption

							if ( $column !== 'PASSWORD' )
							{
								$sql .= $column . "='" . str_replace( '&#39;', "''", $value ) . "',";
								$go = true;
							}

							if ( $column == 'PASSWORD' && ! empty( $value ) && $value !== str_repeat( '*', 8 ) )
							{
								$value = str_replace( "''", "'", $value );
								$sql .= $column . "='" . encrypt_password( $value ) . "',";
								$go = true;
							}
						}
						else
						{
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

							$sql .= $column . "='" . $sql_multiple_input . "',";

							$go = true;
						}
					}
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

				if ( $go )
				{
					DBQuery( $sql );

					//hook
					do_action( 'Students/Student.php|update_student' );
				}
			}
		}
		elseif ( ! $error ) //new student
		{
			if ( isset( $_REQUEST['assign_student_id'] )
				&& $_REQUEST['assign_student_id'] !== '' )
			{
				if (  ( $student_id = (int) $_REQUEST['assign_student_id'] ) > 0 )
				{
					if ( DBGetOne( "SELECT STUDENT_ID
						FROM STUDENTS
						WHERE STUDENT_ID='" . $student_id . "'" ) )
					{
						$error[] = sprintf( _( 'That %s ID is already taken. Please select a different one.' ), Config( 'NAME' ) );
					}
				}
				else
				{
					$error[] = _( 'Please enter valid Numeric data.' );
				}
			}

			//hook
			do_action( 'Students/Student.php|create_student_checks' );

			if ( ! $error )
			{
				if ( ! isset( $student_id ) )
				{
					do
					{
						$student_id = DBSeqNextID( 'STUDENTS_SEQ' );
					}
					while ( DBGetOne( "SELECT STUDENT_ID
						FROM STUDENTS
						WHERE STUDENT_ID='" . $student_id . "'" ) );
				}

				$sql = "INSERT INTO STUDENTS ";
				$fields = 'STUDENT_ID,';
				$values = "'" . $student_id . "',";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM CUSTOM_FIELDS
					ORDER BY SORT_ORDER", array(), array( 'ID' ) );

				foreach ( (array) $_REQUEST['students'] as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						//FJ check numeric fields

						if ( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric' && $value != '' && ! is_numeric( $value ) )
						{
							$error[] = _( 'Please enter valid Numeric data.' );
							continue;
						}

						$fields .= DBEscapeIdentifier( $column ) . ',';

						if ( ! is_array( $value ) )
						{
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
						else
						{
							$values .= "'||";

							foreach ( (array) $value as $val )
							{
								if ( $val )
								{
									$values .= $val . '||';
								}
							}

							$values .= "',";
						}
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				DBQuery( $sql );

				// create default food service account for this student
				$sql = "INSERT INTO FOOD_SERVICE_ACCOUNTS (ACCOUNT_ID,BALANCE,TRANSACTION_ID) values('" . $student_id . "','0.00','0')";
				DBQuery( $sql );

				// associate with default food service account and assign other defaults
				$sql = "INSERT INTO FOOD_SERVICE_STUDENT_ACCOUNTS (STUDENT_ID,DISCOUNT,BARCODE,ACCOUNT_ID) values('" . $student_id . "','','','" . $student_id . "')";
				DBQuery( $sql );

				// create enrollment
				require_once 'modules/Students/includes/SaveEnrollment.fnc.php';
				SaveEnrollment();

				SetUserStudentID( $_REQUEST['student_id'] = $student_id );

				//hook
				do_action( 'Students/Student.php|create_student' );
			}
		}

		if ( UserStudentID()
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'STUDENTS',
				'students',
				$FileUploadsPath . 'Student/' . UserStudentID() . '/'
			);

			if ( ! empty( $_REQUEST['person_id'] ) )
			{
				$uploaded = FilesUploadUpdate(
					'PEOPLE',
					'valuesPEOPLE',
					$FileUploadsPath . 'Contact/' . $_REQUEST['person_id'] . '/'
				);
			}

			if ( ! empty( $_REQUEST['address_id'] ) )
			{
				$uploaded = FilesUploadUpdate(
					'ADDRESS',
					'valuesADDRESS',
					$FileUploadsPath . 'Address/' . $_REQUEST['address_id'] . '/'
				);
			}
		}

		if ( UserStudentID()
			&& ! empty( $_FILES['photo'] ) )
		{
			// $new_photo_file = FileUpload('photo', $StudentPicturesPath.UserSyear().'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', UserStudentID());

			$new_photo_file = ImageUpload(
				'photo',
				array( 'width' => 150, 'height' => '150' ),
				$StudentPicturesPath . UserSyear() . '/',
				array(),
				'.jpg',
				UserStudentID()
			);

			// Hook.
			do_action( 'Students/Student.php|upload_student_photo' );
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
	$unset_request = array( 'modfunc', 'students' );

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
	?>
	<script>window.location.href = "index.php?modfunc=logout&reason=account_created";</script>
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
		// Do not try to delete Grades, Attendance, or Schedule records
		// in case records exist, we must keep them.
		$delete_sql = "DELETE FROM STUDENTS_JOIN_ADDRESS
			WHERE STUDENT_ID='" . UserStudentID() . "';";

		$delete_sql .= "DELETE FROM STUDENTS_JOIN_PEOPLE
			WHERE STUDENT_ID='" . UserStudentID() . "';";

		$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS
			WHERE STUDENT_ID='" . UserStudentID() . "';";

		$delete_sql .= "DELETE FROM STUDENT_ENROLLMENT
			WHERE STUDENT_ID='" . UserStudentID() . "';";

		$delete_sql .= "DELETE FROM STUDENTS
			WHERE STUDENT_ID='" . UserStudentID() . "';";

		$delete_sql .= "DELETE FROM FOOD_SERVICE_ACCOUNTS
			WHERE ACCOUNT_ID='" . UserStudentID() . "';";

		DBQuery( $delete_sql );

		// Hook.
		do_action( 'Students/Student.php|delete_student' );

		unset( $_SESSION['student_id'] );

		// Unset modfunc & student_id & redirect URL.
		RedirectURL( array( 'modfunc', 'student_id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'remove_file'
	&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['id'] );

		if ( ! empty( $_REQUEST['person_id'] ) )
		{
			$file = $FileUploadsPath . 'People/' . $_REQUEST['person_id'] . '/' . $_REQUEST['filename'];

			DBQuery( "UPDATE PEOPLE SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE PERSON_ID='" . $_REQUEST['person_id'] . "'" );
		}
		elseif ( ! empty( $_REQUEST['address_id'] ) )
		{
			$file = $FileUploadsPath . 'Address/' . $_REQUEST['address_id'] . '/' . $_REQUEST['filename'];

			DBQuery( "UPDATE ADDRESS SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE ADDRESS_ID='" . $_REQUEST['address_id'] . "'" );
		}
		else
		{
			$file = $FileUploadsPath . 'Student/' . UserStudentID() . '/' . $_REQUEST['filename'];

			DBQuery( "UPDATE STUDENTS SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
				WHERE STUDENT_ID='" . UserStudentID() . "'" );
		}

		if ( file_exists( $file ) )
		{
			unset( $file );
		}

		// Unset modfunc, id, filename & redirect URL.
		RedirectURL( array( 'modfunc', 'id', 'filename' ) );
	}
}

echo ErrorMessage( $error );

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
			FROM PROFILE_EXCEPTIONS
			WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
			AND CAN_USE='Y'
			AND MODNAME LIKE 'Students/Student.php%'";
	}
	else
	{
		$can_use_sql = "SELECT MODNAME
			FROM STAFF_EXCEPTIONS
			WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
			AND CAN_USE='Y'
			AND MODNAME LIKE 'Students/Student.php%'";
	}

	$can_use_RET = DBGet( $can_use_sql, array(), array( 'MODNAME' ) );

	// FJ create account.

	if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
	{
		$can_use_RET['Students/Student.php&category_id=1'] = true;
	}

	//FJ General_Info only for new student
	//$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE" );
	$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE
		FROM STUDENT_FIELD_CATEGORIES
		WHERE " . ( $_REQUEST['student_id'] !== 'new' ? 'TRUE' : "ID='1'" ) .
		" ORDER BY SORT_ORDER,TITLE" );

	if ( $_REQUEST['modfunc'] !== 'delete_medical'
		&& $_REQUEST['modfunc'] !== 'delete_address'
		|| $_REQUEST['delete_ok'] )
	{
		if ( $_REQUEST['student_id'] !== 'new' )
		{
			$sql = "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.FIRST_NAME,s.LAST_NAME,s.MIDDLE_NAME,s.NAME_SUFFIX,
			s.USERNAME,s.PASSWORD,s.LAST_LOGIN,
			(SELECT ID
				FROM STUDENT_ENROLLMENT
				WHERE SYEAR='" . UserSyear() . "'
				AND STUDENT_ID=s.STUDENT_ID
				ORDER BY START_DATE DESC,END_DATE DESC
				LIMIT 1) AS ENROLLMENT_ID
			FROM STUDENTS s
			WHERE s.STUDENT_ID='" . UserStudentID() . "'";

			$student = DBGet( $sql );
			$student = $student[1];

			$school = DBGet( "SELECT SCHOOL_ID,GRADE_ID
				FROM STUDENT_ENROLLMENT
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
				// Can't delete Student if has Schedule, Attendance, or Grades records.
				$student_records_RET = DBGet( "SELECT (SELECT 1
						FROM SCHEDULE
						WHERE STUDENT_ID='" . UserStudentID() . "' LIMIT 1) AS HAS_SCHEDULE,
					(SELECT 1
						FROM ATTENDANCE_PERIOD
						WHERE STUDENT_ID='" . UserStudentID() . "' LIMIT 1) AS HAS_ATTENDANCE,
					(SELECT 1
						FROM STUDENT_REPORT_CARD_GRADES
						WHERE STUDENT_ID='" . UserStudentID() . "' LIMIT 1) AS HAS_GRADES" );

				if ( ! $student_records_RET
					|| ( ! $student_records_RET[1]['HAS_SCHEDULE']
						&& ! $student_records_RET[1]['HAS_ATTENDANCE']
						&& ! $student_records_RET[1]['HAS_GRADES'] ) )
				{
					$delete_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
						"&modfunc=delete'";

					$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_URL . ');" />';
				}
			}
		}
		else
		{
			// FJ create account.
			$form_action = 'index.php?create_account=student&student_id=new&modfunc=update';
		}

		echo '<form name="student" id="student"	action="' . $form_action . '"
			method="POST" enctype="multipart/form-data">';

		if ( $_REQUEST['student_id'] !== 'new' )
		{
			$name = $student['FULL_NAME'] . ' - ' . $student['STUDENT_ID'];
		}

		DrawHeader( $name, $delete_button . SubmitButton() );

		// Hook.
		do_action( 'Students/Student.php|header' );

		foreach ( (array) $categories_RET as $category )
		{
			if ( $can_use_RET['Students/Student.php&category_id=' . $category['ID']] )
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

				$tabs[] = array(
					'title' => $category['TITLE'],
					'link' => ( $_REQUEST['student_id'] !== 'new' ?
						'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $category['ID'] . '&student_id=' . UserStudentID() :
						'' ),
				);
			}
		}

		$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&category_id=' . $category_id . '&student_id=' . UserStudentID();

		echo '<br />';

		echo PopTable( 'header', $tabs, 'width="100%"' );

		$PopTable_opened = true;

		if ( $can_use_RET['Students/Student.php&category_id=' . $category_id] )
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

				$separator = '<hr />';

				require_once 'modules/Students/includes/Other_Info.inc.php';
			}
		}

		echo PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div>';
		echo '</form>';
	}
	elseif ( $can_use_RET['Students/Student.php&category_id=' . $category_id] )
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

			$separator = '<hr />';

			require 'modules/Students/includes/Other_Info.inc.php';
		}
	}
}
