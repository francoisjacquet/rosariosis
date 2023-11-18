<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';
require_once 'modules/School_Setup/includes/Schools.fnc.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( $_REQUEST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		// Add eventual Dates to $_REQUEST['values'].
		AddRequestedDates( 'values', 'post' );

		if ( ! empty( $_REQUEST['values'] )
			&& ! empty( $_POST['values'] )
			|| ! empty( $_FILES ) )
		{
			// FJ other fields required.
			$required_error = CheckRequiredCustomFields( 'school_fields', $_REQUEST['values'] );

			if ( $required_error )
			{
				$error[] = _( 'Please fill in the required fields' );
			}

			// FJ textarea fields MarkDown sanitize.
			$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'school_fields', 'values' );

			if ( ( ! empty( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] )
					&& ! is_numeric( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] ) )
				|| ( ! empty( $_REQUEST['values']['REPORTING_GP_SCALE'] )
					&& ! is_numeric( $_REQUEST['values']['REPORTING_GP_SCALE'] ) ) )
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}

			if ( ! $error )
			{
				$sql = "UPDATE schools SET ";

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM school_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				$go = 0;

				foreach ( (array) $_REQUEST['values'] as $column => $value )
				{
					if ( ! empty( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
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

					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					$go = true;
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'";

				if ( $go )
				{
					DBQuery( $sql );
				}

				$uploaded = FilesUploadUpdate(
					'schools',
					'values',
					$FileUploadsPath . 'Schools/' . UserSchool() . '/'
				);

				if ( $go || $uploaded )
				{
					$note[] = button( 'check' ) . '&nbsp;' . _( 'This school has been modified.' );
				}

				UpdateSchoolArray( UserSchool() );

				// @since 5.8 Hook.
				do_action( 'School_Setup/Schools.php|update_school' );
			}
		}

		// Unset modfunc, values & redirect URL.
		RedirectURL( [ 'modfunc', 'values' ] );
	}
	elseif ( ( $_REQUEST['button'] === _( 'Delete' )
		|| isset( $_POST['delete_ok'] ) )
		&& User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		if ( DeletePrompt( _( 'School' ) ) )
		{
			$delete_sql = SchoolDeleteSQL( UserSchool() );

			DBQuery( $delete_sql );

			// @since 5.8 Hook.
			do_action( 'School_Setup/Schools.php|delete_school' );

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );

			// Set current school to one of the remaining schools.
			$_SESSION['UserSchool'] = DBGetOne( "SELECT ID
				FROM schools
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

		// Security: sanitize filename with no_accents().
		$filename = no_accents( $_GET['filename'] );

		$file = $FileUploadsPath . 'Schools/' . UserSchool() . '/' . $filename;

		DBQuery( "UPDATE schools SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		if ( file_exists( $file ) )
		{
			unlink( $file );
		}

		// Unset modfunc, id, filename & redirect URL.
		RedirectURL( [ 'modfunc', 'id', 'filename' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	$schooldata = DBGet( "SELECT ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,
		SCHOOL_NUMBER,REPORTING_GP_SCALE,SHORT_NAME,NUMBER_DAYS_ROTATION
		FROM schools
		WHERE ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$schooldata = $schooldata[1];
	$school_name = SchoolInfo( 'TITLE' );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST" enctype="multipart/form-data">';

	$delete_button = '';

	// FJ delete school only if more than one school.
	if ( $_SESSION['SchoolData']['SCHOOLS_NB'] > 1 )
	{
		// Delete school only if has NO students enrolled in all school years.
		$has_students_enrolled = DBGetOne( "SELECT 1 AS ENROLLED
			FROM student_enrollment
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL )" );

		$can_delete = DBTransDryRun( SchoolDeleteSQL( UserSchool() ) );

		$delete_button = $can_delete ? SubmitButton( _( 'Delete' ), 'button', '' ) : '';
	}

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
		_( 'Zip Code' ),
		'maxlength=10 size=5'
	) . '</td></tr>';

	if ( ! AllowEdit() )
	{
		echo '<tr><td colspan="3">' . NoInput(
			makePhone( $schooldata['PHONE'] ),
			_( 'Phone' )
		) . '</td></tr>';
	}
	else
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['PHONE'],
			'values[PHONE]',
			_( 'Phone' ),
			'maxlength=30'
		) . '</td></tr>';
	}

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

		echo '<tr><td colspan="3">' . NoInput(
			'<a href="' . URLEscape( $school_link ) . '" target="_blank">' . $schooldata['WWW_ADDRESS'] . '</a>',
			_( 'Website' )
		) . '</td></tr>';
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
		'maxlength=50'
	) . '</td></tr>';

	echo '<tr><td colspan="3">' . TextInput(
		(float) $schooldata['REPORTING_GP_SCALE'],
		'values[REPORTING_GP_SCALE]',
		_( 'Base Grading Scale' ),
		'type="number" min="1" max="10000" required'
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
			'type=number size=1 min=2 max=7'
		) . '</td></tr>';
	}
	elseif ( ! empty( $schooldata['NUMBER_DAYS_ROTATION'] ) ) //do not show if no rotation set
	{
		echo '<tr><td colspan="3">' . NoInput(
			$schooldata['NUMBER_DAYS_ROTATION'],
			_( 'Number of Days for the Rotation' )
		) . '</td></tr>';
	}

	// FJ add School Fields.
	$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
		FROM school_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

	if ( ! empty( $fields_RET ) )
	{
		echo '<tr><td colspan="3"><hr></td></tr>';
	}

	$custom_RET = DBGet( "SELECT *
		FROM schools
		WHERE ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$value = $custom_RET[1];

	foreach ( (array) $fields_RET as $field )
	{
		$value_custom = issetVal( $value['CUSTOM_' . $field['ID']], '' );

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

				$col_name = DBEscapeIdentifier( 'CUSTOM_' . $field['ID'] );

				$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
					FROM schools s
					WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
					AND s." . $col_name . " IS NOT NULL
					AND s." . $col_name . "<>''
					AND s." . $col_name . "<>'---'
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
