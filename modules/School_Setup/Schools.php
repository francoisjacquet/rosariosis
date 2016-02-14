<?php

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

if ( isset( $_POST['day_values'], $_POST['month_values'], $_POST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['day_values'],
		$_REQUEST['month_values'],
		$_REQUEST['year_values']
	);

	$_REQUEST['values'] = array_replace_recursive( $_REQUEST['values'], $requested_dates );

	$_POST['values'] = array_replace_recursive( $_POST['values'], $requested_dates );
}

if ( $_REQUEST['modfunc']=='update')
{
	if ( $_REQUEST['button']==_('Save') && AllowEdit())
	{
		if ( $_REQUEST['values'] && $_POST['values'])
		{
			// FJ other fields required.
			$required_error = CheckRequiredCustomFields( 'PEOPLE_FIELDS', $_REQUEST['values']['PEOPLE'] );

			if ( $required_error )
			{
				$error[] = _( 'Please fill in the required fields' );
			}

			// FJ textarea fields MarkDown sanitize.
			$_REQUEST['values'] = FilterCustomFieldsMarkdown( 'SCHOOL_FIELDS', $_REQUEST['values'] );

			if ( ( empty( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] )
					|| is_numeric( $_REQUEST['values']['NUMBER_DAYS_ROTATION'] ) )
				&& ( empty( $_REQUEST['values']['REPORTING_GP_SCALE'] )
					|| is_numeric( $_REQUEST['values']['REPORTING_GP_SCALE'] ) ) )
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}

			if ( ! $error )
			{
				if ( $_REQUEST['new_school']!='true')
				{
					$sql = "UPDATE SCHOOLS SET ";

					$fields_RET = DBGet(DBQuery("SELECT ID,TYPE FROM SCHOOL_FIELDS ORDER BY SORT_ORDER"), array(), array('ID'));

					$go = 0;
				
					foreach ( (array) $_REQUEST['values'] as $column => $value)
					{
						if (1)//!empty($value) || $value=='0')
						{
							//FJ check numeric fields
							if ( $fields_RET[str_replace('CUSTOM_','',$column)][1]['TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
							{
								$error[] = _('Please enter valid Numeric data.');
								continue;
							}
						
							$sql .= $column."='".$value."',";
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
							$fields .= ','.$column;
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
					
						unset($_REQUEST['new_school']);

						//set new current school
						$_SESSION['UserSchool'] = $id;
					}
				}
				UpdateSchoolArray(UserSchool());
			}
		}
		
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['values']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
	}
	elseif ( $_REQUEST['button']==_('Delete') && User('PROFILE')=='admin' && AllowEdit())
	{
		if (DeletePrompt(_('School')))
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

			unset($_REQUEST['modfunc']);

			//set current school to one of the remaining schools
			$first_remaining_school = DBGet(DBQuery("SELECT ID FROM SCHOOLS WHERE SYEAR = '".UserSyear()."' LIMIT 1"));
			$_SESSION['UserSchool'] = $first_remaining_school[1]['ID'];

			UpdateSchoolArray(UserSchool());
		}
	}
	else
		unset($_REQUEST['modfunc']);
}

if (empty($_REQUEST['modfunc']))
{
	if ( !empty($note))
		echo ErrorMessage($note, 'note');
	if ( !empty($error))
		echo ErrorMessage($error, 'error');

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
	echo '<tr><td colspan="3">'.TextInput($schooldata['TITLE'],'values[TITLE]',(! $schooldata['TITLE']?'<span class="legend-red">':'')._('School Name').(! $schooldata['TITLE']?'</span>':''),'required maxlength=100').'</td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['ADDRESS'],'values[ADDRESS]',_('Address'),'maxlength=100').'</td></tr>';

	echo '<tr><td>'.TextInput($schooldata['CITY'],'values[CITY]',_('City'),'maxlength=100').'</td><td>'.TextInput($schooldata['STATE'],'values[STATE]',_('State'),'maxlength=10').'</td>';

	echo '<td>'.TextInput($schooldata['ZIPCODE'],'values[ZIPCODE]',_('Zip'),'maxlength=10').'</td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['PHONE'],'values[PHONE]',_('Phone'),'maxlength=30').'</td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['PRINCIPAL'],'values[PRINCIPAL]',_('Principal of School'),'maxlength=100').'</td></tr>';

	if (AllowEdit() || ! $schooldata['WWW_ADDRESS'])
		echo '<tr><td colspan="3">'.TextInput($schooldata['WWW_ADDRESS'],'values[WWW_ADDRESS]',_('Website'),'maxlength=100').'</td></tr>';
	else
		echo '<tr><td colspan="3"><a href="http://'.$schooldata['WWW_ADDRESS'].'" target="_blank">'.$schooldata['WWW_ADDRESS'].'</a><br /><span class="legend-gray">'._('Website').'</span></td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['SHORT_NAME'],'values[SHORT_NAME]',_('Short Name'),'maxlength=25').'</td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['SCHOOL_NUMBER'],'values[SCHOOL_NUMBER]',_('School Number'),'maxlength=100').'</td></tr>';

	echo '<tr><td colspan="3">'.TextInput($schooldata['REPORTING_GP_SCALE'],'values[REPORTING_GP_SCALE]',(! $schooldata['REPORTING_GP_SCALE']?'<span class="legend-red">':'')._('Base Grading Scale').(! $schooldata['TITLE']?'</span>':''),'maxlength=10 required').'</td></tr>';

	if ( AllowEdit() )
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['NUMBER_DAYS_ROTATION'],
			'values[NUMBER_DAYS_ROTATION]',
			_('Number of Days for the Rotation' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank if the school does not use a Rotation of Numbered Days' ) .
				'</i></div>',
			'maxlength=1 size=1 min=1'
		) . '</td></tr>';
	}
	elseif ( !empty( $schooldata['NUMBER_DAYS_ROTATION'] ) ) //do not show if no rotation set
	{
		echo '<tr><td colspan="3">' . TextInput(
			$schooldata['NUMBER_DAYS_ROTATION'],
			'values[NUMBER_DAYS_ROTATION]',
			_( 'Number of Days for the Rotation' ),
			'maxlength=1 size=1 min=1'
		) . '</td></tr>';
	}

	//FJ add School Fields
	$fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE,DEFAULT_SELECTION,REQUIRED FROM SCHOOL_FIELDS ORDER BY SORT_ORDER,TITLE"));
	$fields_RET = ParseMLArray($fields_RET,'TITLE');
	
	if ( count( $fields_RET ) )
		echo '<tr><td colspan="3"><hr /></td></tr>';
		
	foreach ( (array) $fields_RET as $field )
	{
		$value_custom = '';

		if ( $_REQUEST['new_school'] != 'true' )
		{
			$value_custom = DBGet( DBQuery( "SELECT CUSTOM_" . $field['ID'] . "
				FROM SCHOOLS
				WHERE ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" ) );

			$value_custom = $value_custom[1]['CUSTOM_' . $field['ID']];

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
					( $field['REQUIRED'] ? ' required' : '' ),
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
