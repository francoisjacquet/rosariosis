<?php

require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

DrawHeader( ProgramTitle() );

if ( isset( $_POST['day_values'], $_POST['month_values'], $_POST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['year_values'],
		$_REQUEST['month_values'],
		$_REQUEST['day_values']
	);

	$_REQUEST['values'] = array_replace_recursive( (array) $_REQUEST['values'], $requested_dates );

	$_POST['values'] = array_replace_recursive( (array) $_POST['values'], $requested_dates );
}

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( $_REQUEST['button'] === _( 'Save' )
		&& AllowEdit() )
	{
		if ( $_REQUEST['values']
			&& $_POST['values'] )
		{
			// FJ other fields required.
			$required_error = CheckRequiredCustomFields( 'SCHOOL_FIELDS', $_REQUEST['values'] );

			if ( $required_error )
			{
				$error[] = _( 'Please fill in the required fields' );
			}

			// FJ textarea fields MarkDown sanitize.
			$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'SCHOOL_FIELDS', 'values' );

			if ( ( ! empty( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] )
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
				if ( $_REQUEST['new_school'] !== 'true' )
				{
					$sql = "UPDATE SCHOOLS SET ";

					$fields_RET = DBGet( DBQuery( "SELECT ID,TYPE
						FROM SCHOOL_FIELDS
						ORDER BY SORT_ORDER" ), array(), array( 'ID' ) );

					$go = 0;

					foreach ( (array) $_REQUEST['values'] as $column => $value )
					{
						if ( ! is_array( $value ) )
						{
							//FJ check numeric fields
							if ( $fields_RET[str_replace('CUSTOM_','',$column)][1]['TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
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
					$sql = mb_substr($sql,0,-1) . " WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'";
					if ( $go)
					{
						DBQuery($sql);
						$note[] = button('check') .'&nbsp;'._('This school has been modified.');
					}
				}
				else
				{
					$fields = $values = '';

					foreach ( (array) $_REQUEST['values'] as $column => $value)
						if ( $column!='ID' && $value)
						{
							$fields .= ',' . DBEscapeIdentifier( $column );
							$values .= ",'".$value."'";
						}

					if ( $fields && $values)
					{
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('SCHOOLS_SEQ')." AS ID"));
						$id = $id[1]['ID'];
						$sql = "INSERT INTO SCHOOLS (ID,SYEAR$fields) values('".$id."','".UserSyear()."'$values)";
						DBQuery($sql);
						DBQuery("UPDATE STAFF SET SCHOOLS=rtrim(SCHOOLS,',')||',$id,' WHERE STAFF_ID='".User('STAFF_ID')."' AND SCHOOLS IS NOT NULL");

						//FJ copy School Configuration
						$sql = "INSERT INTO CONFIG (SCHOOL_ID,CONFIG_VALUE,TITLE) SELECT '".$id."' AS SCHOOL_ID,CONFIG_VALUE,TITLE FROM CONFIG WHERE SCHOOL_ID='".UserSchool()."';";
						DBQuery($sql);
						$sql = "INSERT INTO PROGRAM_CONFIG (SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE) SELECT '".$id."' AS SCHOOL_ID,SYEAR,PROGRAM,VALUE,TITLE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
						DBQuery($sql);

						// Unset new school & redirect URL.
						RedirectURL( 'new_school' );

						// Set new current school.
						$_SESSION['UserSchool'] = $id;

						// Unset current student.
						unset( $_SESSION['student_id'] );
					}
				}
				UpdateSchoolArray(UserSchool());
			}
		}

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
	elseif ( ( $_REQUEST['button'] === _( 'Delete' )
			|| isset( $_POST['delete_ok'] ) )
		&& User( 'PROFILE' ) === 'admin'
		&& AllowEdit() )
	{
		if ( DeletePrompt( _( 'School' ) ) )
		{
			DBQuery("DELETE FROM SCHOOLS WHERE ID='".UserSchool()."'");
			DBQuery("DELETE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM ATTENDANCE_CALENDAR WHERE SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM SCHOOL_MARKING_PERIODS WHERE SCHOOL_ID='".UserSchool()."'");
			DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID=NULL WHERE CURRENT_SCHOOL_ID='".UserSchool()."'");
			DBQuery("UPDATE STAFF SET SCHOOLS=replace(SCHOOLS,',".UserSchool().",',',')");
			//FJ add School Configuration
			DBQuery("DELETE FROM CONFIG WHERE SCHOOL_ID='".UserSchool()."'");
			DBQuery("DELETE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."'");

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );

			//set current school to one of the remaining schools
			$first_remaining_school = DBGet(DBQuery("SELECT ID FROM SCHOOLS WHERE SYEAR = '".UserSyear()."' LIMIT 1"));
			$_SESSION['UserSchool'] = $first_remaining_school[1]['ID'];

			UpdateSchoolArray(UserSchool());
		}
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( ! $_REQUEST['modfunc'] )
{

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	if ( ! $_REQUEST['new_school'])
	{
		$schooldata = DBGet(DBQuery("SELECT ID,TITLE,ADDRESS,CITY,STATE,ZIPCODE,PHONE,PRINCIPAL,WWW_ADDRESS,SCHOOL_NUMBER,REPORTING_GP_SCALE,SHORT_NAME,NUMBER_DAYS_ROTATION FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
		$schooldata = $schooldata[1];
		$school_name = SchoolInfo('TITLE');
	}
	else
		$school_name = _('Add a School');

	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&new_school='.$_REQUEST['new_school'].'" method="POST">';

	//FJ delete school only if more than one school
	$delete_button = false;
	if ( $_REQUEST['new_school']!='true' && $_SESSION['SchoolData']['SCHOOLS_NB'] > 1)
		$delete_button = true;

	//FJ fix bug: no save button if no admin
	if (User('PROFILE')=='admin' && AllowEdit())
		DrawHeader('',SubmitButton(_('Save'), 'button').($delete_button?SubmitButton(_('Delete'), 'button'):''));

	echo '<br />';

	PopTable('header',$school_name);

	echo '<table>';

	if ( $_REQUEST['new_school']!='true')
		echo '<tr><td colspan="3">'.(file_exists('assets/school_logo_'.UserSchool().'.jpg') ? '<img src="assets/school_logo_'.UserSchool().'.jpg" style="max-width:225px; max-height:225px;" /><br /><span class="legend-gray">'._('School logo').'</span>' : '').'</td></tr>';

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
		'maxlength=100'
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
		'maxlength=10'
	) . '</td><td>' .
	TextInput(
		$schooldata['ZIPCODE'],
		'values[ZIPCODE]',
		_( 'Zip' ),
		'maxlength=10'
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
			'maxlength=100'
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
	$fields_RET = DBGet( DBQuery( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
		FROM SCHOOL_FIELDS
		ORDER BY SORT_ORDER,TITLE" ) );

	$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

	if ( count( $fields_RET ) )
	{
		echo '<tr><td colspan="3"><hr /></td></tr>';
	}

	foreach ( (array) $fields_RET as $field )
	{
		$value_custom = '';

		if ( $_REQUEST['new_school'] !== 'true' )
		{
			$value_custom = DBGet( DBQuery( "SELECT CUSTOM_" . $field['ID'] . "
				FROM SCHOOLS
				WHERE ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" ) );

			$value_custom = $value_custom[1][ 'CUSTOM_' . $field['ID'] ];

			$div = true;
		}
		elseif ( $field['DEFAULT_SELECTION'] )
		{
			$value_custom = $field['DEFAULT_SELECTION'];

			$div = false;
		}

		$title_custom = AllowEdit() && ! $value_custom && $field['REQUIRED'] ?
			'<span class="legend-red">' . $field['TITLE'] . '</span>' :
			$field['TITLE'];

		echo '<tr><td colspan="3">';

		switch ( $field['TYPE'] )
		{
			case 'text':
				echo TextInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					'maxlength=255' . ( $field['REQUIRED'] ? ' required' : '' ),
					$div
				);

				break;

			case 'numeric':
				echo TextInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					'size=9 maxlength=18' . ( $field['REQUIRED'] ? ' required' : '' ),
					$div
				);

				break;

			case 'date':
				echo DateInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					$div,
					true,
					$field['REQUIRED']
				);

				break;

			case 'textarea':
				echo TextAreaInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					'maxlength=5000' . ( $field['REQUIRED'] ? ' required' : '' ),
					$div
				);

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
				// Global.
				$value = array( 'CUSTOM_' . $field['ID'] => $value_custom );

				echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $title_custom, 'values' );

				break;

			case 'select':
			case 'autos':
			case 'edits':
			case 'codeds':
			case 'exports':
				$options = $select_options = array();

				$col_name = 'CUSTOM_' . $field['ID'];

				if ( $field['SELECT_OPTIONS'] )
				{
					$options = explode(
						"\r",
						str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] )
					);
				}

				foreach ( (array) $options as $option )
				{
					$value = $option;

					// Exports specificities.
					if ( $field['TYPE'] === 'exports' )
					{
						$option = explode( '|', $option );

						$option = $value = $option[0];
					}
					// Codeds specificities.
					elseif ( $field['TYPE'] === 'codeds' )
					{
						list( $value, $option ) = explode( '|', $option );
					}

					if ( $value !== ''
						&& $option !== '' )
					{
						$select_options[ $value ] = $option;
					}
				}

				// Get autos / edits pull-down edited options.
				if ( $field['TYPE'] === 'autos'
					|| $field['TYPE'] === 'edits' )
				{
					if ( $value_custom === '---'
						|| count( $select_options ) <= 1 )
					{
						// FJ new option.
						echo TextInput(
							$value_custom === '---' ?
								array( '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ) :
								$value_custom,
							'values[CUSTOM_' . $field['ID'] . ']',
							$title_custom,
							( $field['REQUIRED'] === 'Y' ? 'required' : '' ),
							$div
						);

						break;
					}

					$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
						FROM SCHOOLS s
						WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
						AND s." . $col_name . " IS NOT NULL
						AND s." . $col_name . " != ''
						ORDER BY SORT_KEY";

					$options_RET = DBGet( DBQuery( $sql_options ) );

					// Add the 'new' option, is also the separator.
					$select_options['---'] = '-' . _( 'Edit' ) . '-';

					foreach ( (array) $options_RET as $option )
					{
						$option_value = $option[ $col_name ];

						if ( ! isset( $select_options[ $option_value ] ) )
						{
							$select_options[ $option_value ] = '<span style="color:blue">' .
								$option_value . '</span>';
						}
					}

					// Make sure the current value is in the list.
					if ( $value_custom != ''
						&& ! isset( $select_options[ $value_custom ] ) )
					{
						$select_options[ $value_custom ] = array(
							$value_custom,
							'<span style="color:' . ( $field['TYPE'] === 'autos' ? 'blue' : 'green' ) . '">' .
								$value_custom . '</span>'
						);
					}
				}

				echo SelectInput(
					$value_custom,
					'values[CUSTOM_' . $field['ID'] . ']',
					$title_custom,
					$select_options,
					'N/A',
					( $field['REQUIRED'] ? ' required' : '' ),
					$div
				);

				break;
		}

		echo '</td></tr>';
	}

	echo '</table>';

	PopTable( 'footer' );

	if ( User('PROFILE') === 'admin'
		&& AllowEdit() )
		echo '<br /><div class="center">' . SubmitButton( _( 'Save' ), 'button' ) . '</div>';

	echo '</form>';
}
