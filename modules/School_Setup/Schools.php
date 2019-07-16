<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( $_REQUEST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		if ( ! empty( $_REQUEST['values'] )
			&& ! empty( $_POST['values'] )
			|| ! empty( $_FILES ) )
		{
			// FJ other fields required.
			$required_error = CheckRequiredCustomFields( 'SCHOOL_FIELDS', $_REQUEST['values'] );

			if ( $required_error )
			{
				$error[] = _( 'Please fill in the required fields' );
			}

			// FJ textarea fields MarkDown sanitize.
			$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'SCHOOL_FIELDS', 'values' );

			if (  ( ! empty( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] )
				&& ! is_numeric( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] ) )
				|| ( ! empty( $_REQUEST['values']['REPORTING_GP_SCALE'] )
					&& ! is_numeric( $_REQUEST['values']['REPORTING_GP_SCALE'] )
					// Fix DB error with REPORTING_GP_SCALE field numeric(10,3) type.
					 || $_REQUEST['values']['REPORTING_GP_SCALE'] >= 10000000 ) )
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}

			if ( ! $error )
			{
				$sql = "UPDATE SCHOOLS SET ";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM SCHOOL_FIELDS
					ORDER BY SORT_ORDER", array(), array( 'ID' ) );

				$go = 0;

				foreach ( (array) $_REQUEST['values'] as $column => $value )
				{
					if ( ! is_array( $value ) )
					{
						//FJ check numeric fields
						if ( ! empty( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
							&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
							&& $value != ''
							&& ! is_numeric( $value ) )
						{
							$error[] = _( 'Please enter valid Numeric data.' );
							continue;
						}

						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
						$go = true;
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

						$sql .= DBEscapeIdentifier( $column ) . "='" . $sql_multiple_input . "',";

						$go = true;
					}
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'";

				if ( $go )
				{
					DBQuery( $sql );
					$note[] = button( 'check' ) . '&nbsp;' . _( 'This school has been modified.' );
				}

				$uploaded = FilesUploadUpdate(
					'SCHOOLS',
					'values',
					$FileUploadsPath . 'Schools/' . UserSchool() . '/'
				);

				if ( ! $go && $uploaded )
				{
					$note[] = button( 'check' ) . '&nbsp;' . _( 'This school has been modified.' );
				}

				UpdateSchoolArray( UserSchool() );
			}
		}

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
	elseif (  ( $_REQUEST['button'] === _( 'Delete' )
		|| isset( $_POST['delete_ok'] ) )
		&& User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		if ( DeletePrompt( _( 'School' ) ) )
		{
			$delete_sql = "DELETE FROM SCHOOLS WHERE ID='" . UserSchool() . "';";
			$delete_sql .= "DELETE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "DELETE FROM SCHOOL_MARKING_PERIODS WHERE SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "UPDATE STAFF SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "UPDATE STAFF SET SCHOOLS=replace(SCHOOLS,'," . UserSchool() . ",',',');";
			//FJ add School Configuration
			$delete_sql .= "DELETE FROM CONFIG WHERE SCHOOL_ID='" . UserSchool() . "';";
			$delete_sql .= "DELETE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='" . UserSchool() . "';";
			// Fix SQL error when Parent have students enrolled in deleted school.
			$delete_sql .= "DELETE FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID IN(SELECT STUDENT_ID
				FROM STUDENT_ENROLLMENT
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL ) );";

			DBQuery( $delete_sql );

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );

			// Set current school to one of the remaining schools.
			$_SESSION['UserSchool'] = DBGetOne( "SELECT ID
				FROM SCHOOLS
				WHERE SYEAR = '" . UserSyear() . "' LIMIT 1" );

			UpdateSchoolArray( UserSchool() );
		}
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( $_REQUEST['modfunc'] === 'remove_file'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['id'] );

		$file = $FileUploadsPath . 'Schools/' . UserSchool() . '/' . $_REQUEST['filename'];

		DBQuery( "UPDATE SCHOOLS SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		if ( file_exists( $file ) )
		{
			unset( $file );
		}

		// Unset modfunc, id, filename & redirect URL.
		RedirectURL( array( 'modfunc', 'id', 'filename' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	$schooldata = DBGet( "SELECT ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,
		SCHOOL_NUMBER,REPORTING_GP_SCALE,SHORT_NAME,NUMBER_DAYS_ROTATION
		FROM SCHOOLS
		WHERE ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$schooldata = $schooldata[1];
	$school_name = SchoolInfo( 'TITLE' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST" enctype="multipart/form-data">';

	//FJ delete school only if more than one school
	$delete_button = $_SESSION['SchoolData']['SCHOOLS_NB'] > 1 ?
		SubmitButton( _( 'Delete' ), 'button', '' ) :
		'';

	// FJ fix bug: no save button if not admin.
	if ( User( 'PROFILE' ) === 'admin' && AllowEdit() )
	{
		DrawHeader(
			'',
			// Leave Delete button AFTER the Save one so info are saved on Enter keypress.
			SubmitButton( _( 'Save' ), 'button' ) . $delete_button
		);
	}

	echo '<br />';

	PopTable( 'header', $school_name );

	echo '<table><tr><td colspan="3">' . ( file_exists( 'assets/school_logo_' . UserSchool() . '.jpg' ) ?
		'<img src="assets/school_logo_' . UserSchool() . '.jpg" style="max-width:225px; max-height:225px;" /><br />
		<span class="legend-gray">' . _( 'School logo' ) . '</span>' :
		'' ) . '</td></tr>';

	//FJ school name field required
	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['TITLE'],
		'values[TITLE]',
		_( 'School Name' ),
		'required maxlength=100'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['ADDRESS'],
		'values[ADDRESS]',
		_( 'Address' ),
		( 'maxlength=100' . ( empty( $schooldata['ADDRESS'] ) ? ' size=26' : '' ) )
	) . '</td></tr>';

	echo '<tr><td>' . TextInput(
		$schooldata['CITY'],
		'values[CITY]',
		_( 'City' ),
		'maxlength=100'
	) . '</td><td>' .
	TextInput(
		$schooldata['STATE'],
		'values[STATE]',
		_( 'State' ),
		'maxlength=10 size=5'
	) . '</td><td>' .
	TextInput(
		$schooldata['ZIPCODE'],
		'values[ZIPCODE]',
		_( 'Zip' ),
		'maxlength=10 size=5'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['PHONE'],
		'values[PHONE]',
		_( 'Phone' ),
		'maxlength=30'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['PRINCIPAL'],
		'values[PRINCIPAL]',
		_( 'Principal of School' ),
		'maxlength=100'
	) . '</td></tr>';

	if ( AllowEdit()
		|| ! $schooldata['WWW_ADDRESS'] )
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['WWW_ADDRESS'],
			'values[WWW_ADDRESS]',
			_( 'Website' ),
			( 'maxlength=100' . ( empty( $schooldata['WWW_ADDRESS'] ) ? ' size=26' : '' ) )
		) . '</td></tr>';
	}
	else
	{
		$school_link = mb_strpos( $schooldata['WWW_ADDRESS'], 'http' ) === 0 ?
		$schooldata['WWW_ADDRESS'] :
		'http://' . $schooldata['WWW_ADDRESS'];

		echo '<tr><td colspan="3">
			<a href="' . $school_link . '" target="_blank">' .
		$schooldata['WWW_ADDRESS'] .
		'</a><br />
			<span class="legend-gray">' . _( 'Website' ) . '</span></td></tr>';
	}

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['SHORT_NAME'],
		'values[SHORT_NAME]',
		_( 'Short Name' ),
		'maxlength=25'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['SCHOOL_NUMBER'],
		'values[SCHOOL_NUMBER]',
		_( 'School Number' ),
		'maxlength=100'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		$schooldata['REPORTING_GP_SCALE'],
		'values[REPORTING_GP_SCALE]',
		_( 'Base Grading Scale' ),
		'maxlength=10 required'
	) . '</td></tr>';

	if ( AllowEdit() )
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['NUMBER_DAYS_ROTATION'],
			'values[NUMBER_DAYS_ROTATION]',
			_( 'Number of Days for the Rotation' ) .
			'<div class="tooltip"><i>' .
			_( 'Leave the field blank if the school does not use a Rotation of Numbered Days' ) .
			'</i></div>',
			'type=number size=1 min=1 max=9'
		) . '</td></tr>';
	}
	elseif ( ! empty( $schooldata['NUMBER_DAYS_ROTATION'] ) ) //do not show if no rotation set
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['NUMBER_DAYS_ROTATION'],
			'values[NUMBER_DAYS_ROTATION]',
			_( 'Number of Days for the Rotation' ),
			'maxlength=1 size=1 min=1'
		) . '</td></tr>';
	}

	// FJ add School Fields.
	$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
		FROM SCHOOL_FIELDS
		ORDER BY SORT_ORDER,TITLE" );

	$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

	if ( ! empty( $fields_RET ) )
	{
		echo '<tr><td colspan="3"><hr /></td></tr>';
	}

	$custom_RET = DBGet( "SELECT *
		FROM SCHOOLS
		WHERE ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$value = $custom_RET[1];

	foreach ( (array) $fields_RET as $field )
	{
		$value_custom = isset( $value['CUSTOM_' . $field['ID']] ) ? $value['CUSTOM_' . $field['ID']] : '';

		$div = true;

		$title_custom = AllowEdit() && ! $value_custom && $field['REQUIRED'] ?
		'<span class="legend-red">' . $field['TITLE'] . '</span>' :
		$field['TITLE'];

		echo '<tr><td colspan="3">';

		switch ( $field['TYPE'] )
		{
			case 'text':
			case 'numeric':

				echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'values' );

				break;

			case 'date':

				echo _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'values' );

				break;

			case 'textarea':

				echo _makeTextAreaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'values' );

				break;

			// Add School Field types.
			case 'radio':
				echo CheckboxInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					'',
					false,
					'Yes',
					'No',
					$div,
					( $field['REQUIRED'] ? ' required' : '' )
				);

				break;

			case 'multiple':

				echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $title_custom, 'values' );

				break;

			case 'autos':

				$sql_options = "SELECT DISTINCT s.CUSTOM_" . $field['ID'] . ",upper(s.CUSTOM_" . $field['ID'] . ") AS SORT_KEY
					FROM SCHOOLS s
					WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
					AND s.CUSTOM_" . $field['ID'] . " IS NOT NULL
					AND s.CUSTOM_" . $field['ID'] . " != ''
					ORDER BY SORT_KEY";

				$options_RET = DBGet( $sql_options );

				echo _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'values', $options_RET );

				break;

			case 'exports':
			case 'select':

				echo _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'values' );

				break;

			case 'files':

				echo _makeFilesInput(
					'CUSTOM_' . $field['ID'],
					$field['TITLE'],
					'values',
					'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove_file&id=' . $field['ID'] . '&filename='
				);

				break;
		}

		echo '</td></tr>';
	}

	echo '</table>';

	PopTable( 'footer' );

	if ( User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Save' ), 'button' ) . '</div>';
	}

	echo '</form>';
}
