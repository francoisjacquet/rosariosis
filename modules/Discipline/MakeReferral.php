<?php

DrawHeader( ProgramTitle() );

// set start date
if ( isset( $_REQUEST['day_start'] )
	&& isset( $_REQUEST['month_start'] )
	&& isset( $_REQUEST['year_start'] ) )
{
	$start_date = RequestedDate(
		$_REQUEST['day_start'],
		$_REQUEST['month_start'],
		$_REQUEST['year_start']
	);
}

if ( empty( $start_date ) )
	$start_date = '01-' . mb_strtoupper( date( 'M-Y' ) );

// set end date
if ( isset( $_REQUEST['day_end'] )
	&& isset( $_REQUEST['month_end'] )
	&& isset( $_REQUEST['year_end'] ) )
{
	$end_date = RequestedDate(
		$_REQUEST['day_end'],
		$_REQUEST['month_end'],
		$_REQUEST['year_end']
	);
}

if ( empty( $end_date ) )
	$end_date = DBDate();

if ( isset( $_POST['day_values'] )
	&& isset( $_POST['month_values'] )
	&& isset( $_POST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['day_values'],
		$_REQUEST['month_values'],
		$_REQUEST['year_values']
	);

	$_REQUEST['values'] = array_replace_recursive( $_REQUEST['values'], $requested_dates );

	$_POST['values'] = array_replace_recursive( $_POST['values'], $requested_dates );
}

if ( isset( $_POST['values'] )
	&& count( $_POST['values'] ) )
{
	$sql = "INSERT INTO DISCIPLINE_REFERRALS ";
	
	$referral_id_RET = DBGet( DBQuery( "SELECT " . db_seq_nextval( 'DISCIPLINE_REFERRALS_SEQ' ) . " AS ID;" ) );

	$referral_id = $referral_id_RET[1]['ID'];

	$fields = "ID,SYEAR,SCHOOL_ID,STUDENT_ID,";
	$values = $referral_id . ",'" . UserSyear() . "','" . UserSchool() . "','" . UserStudentID() . "',";

	$go = 0;

	$categories_RET = DBGet(DBQuery("SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"), array(), array('ID'));
	
	foreach ( (array) $_REQUEST['values'] as $column => $value)
	{
		if ( !empty($value) || $value=='0')
		{
			//FJ check numeric fields
			if ( $categories_RET[str_replace('CATEGORY_','',$column)][1]['DATA_TYPE'] == 'numeric' && $value!='' && !is_numeric($value))
			{
				$error[] = _('Please enter valid Numeric data.');
				$go = 0;
				break;
			}

			$fields .= $column.',';
			if ( !is_array($value))
				$values .= "'".str_replace('&quot;','"',$value)."',";
			else
			{
				$values .= "'||";
				foreach ( (array) $value as $val)
				{
					if ( $val)
						$values .= str_replace('&quot;','"',$val).'||';
				}
				$values .= "',";
			}
			$go = true;
		}
	}

	$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

	if ( $go)
	{
		DBQuery($sql);

		// FJ email Discipline Referral feature
		if ( isset( $_REQUEST['emails'] ) )
		{
			require_once 'modules/Discipline/includes/EmailReferral.fnc.php';

			if ( EmailReferral( $referral_id, $_REQUEST['emails'] ) )
			{
				$note[] = _( 'That discipline incident has been emailed.' );
			} else var_dump($referral_id);
		}

		$note[] = _('That discipline incident has been referred to an administrator.');
	}

	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_REQUEST['student_id']);
	unset($_SESSION['student_id']);
}

if (isset($error))
	echo ErrorMessage($error);

if (isset($note))
	echo ErrorMessage($note,'note');

//if ( ! $_REQUEST['student_id'])
	$extra['new'] = true;


if ( $_REQUEST['student_id'])
	echo '<br />';

Search('student_id',$extra);

if (UserStudentID() && $_REQUEST['student_id'])
{
	//FJ teachers need AllowEdit (to edit the input fields)
	$_ROSARIO['allow_edit'] = true;
	
	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
	echo '<br />';
	PopTable('header',ProgramTitle());

	$categories_RET = DBGet(DBQuery("SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"));
	
	echo '<table class="width-100p col1-align-right">';

	echo '<tr class="st"><td><span class="legend-gray">'._('Student').'</span></td><td>';
	$name = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
	echo $name[1]['FIRST_NAME'].'&nbsp;'.($name[1]['MIDDLE_NAME']?$name[1]['MIDDLE_NAME'].' ':'').$name[1]['LAST_NAME'].'&nbsp;'.$name[1]['NAME_SUFFIX'];
	echo '</td></tr>';

	echo '<tr class="st"><td><span class="legend-gray">'._('Reporter').'</span></td><td>';
	$users_RET = DBGet(DBQuery("SELECT STAFF_ID,FIRST_NAME,LAST_NAME,MIDDLE_NAME,EMAIL,PROFILE FROM STAFF WHERE SYEAR='".UserSyear()."' AND SCHOOLS LIKE '%,".UserSchool().",%' AND PROFILE IN ('admin','teacher') ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME"));
	echo '<select name="values[STAFF_ID]">';
	foreach ( (array) $users_RET as $user)
		echo '<option value="'.$user['STAFF_ID'].'"'.(User('STAFF_ID')==$user['STAFF_ID']?' selected':'').'>'.$user['LAST_NAME'].', '.$user['FIRST_NAME'].' '.$user['MIDDLE_NAME'].'</option>';
	echo '</select>';
	echo '</td></tr>';

	echo '<tr class="st"><td><span class="legend-gray">'._('Incident Date').'</span></td><td>';
	echo PrepareDate(DBDate(),'_values[ENTRY_DATE]');
	echo '</td></tr>';

	// FJ email Discipline Referral feature
	// email Referral to: Administrators and/or Teachers
	// get Administrators & Teachers with valid emails:
	foreach ( (array) $users_RET as $user )
	{
		if ( filter_var( $user['EMAIL'], FILTER_VALIDATE_EMAIL ) )
		{
			if ( $user['PROFILE'] === 'admin' )
			{
				$emailadmin_options[$user['EMAIL']] = $user['LAST_NAME'].', '.$user['FIRST_NAME'].' '.$user['MIDDLE_NAME'];
			}
			elseif ( $user['PROFILE'] === 'teacher' )
			{
				$emailteacher_options[$user['EMAIL']] = $user['LAST_NAME'].', '.$user['FIRST_NAME'].' '.$user['MIDDLE_NAME'];
			}
		}
	}

	echo '<tr class="st"><td><span class="legend-gray">'._('Email Referral to').'</span></td><td>';

	$value = $allow_na = $div = false;

	// multiple select input
	$extra = 'multiple title="' . _( 'Hold the CTRL key down to select multiple options' ) . '"';

	echo '<table><tr class="st"><td>';

	echo SelectInput( $value, 'emails[]', _( 'Administrators' ), $emailadmin_options, $allow_na, $extra, $div );

	echo '</td><td>';

	echo SelectInput( $value, 'emails[]', _( 'Teachers' ), $emailteacher_options, $allow_na, $extra, $div );

	echo '</td></tr></table>';

	echo '</td></tr>';

	foreach ( (array) $categories_RET as $category)
	{
		echo '<tr class="st"><td><span class="legend-gray">'.$category['TITLE'].'</span></td><td>';
		switch ( $category['DATA_TYPE'])
		{
			case 'text':
				echo TextInput('','values[CATEGORY_'.$category['ID'].']','','maxlength=255');
				//echo '<input type="TEXT" name="values[CATEGORY_'.$category['ID'].']" maxlength="255" />';
			break;
	
			case 'numeric':
				echo TextInput('','values[CATEGORY_'.$category['ID'].']','','size=9 maxlength=18');
				//echo '<input type="TEXT" name="values[CATEGORY_'.$category['ID'].']" size="4" maxlength="10" />';
			break;
	
			case 'textarea':
				echo TextAreaInput('','values[CATEGORY_'.$category['ID'].']','','maxlength=5000 rows=4 cols=30');
				//echo '<textarea name="values[CATEGORY_'.$category['ID'].']" rows="4" cols="30"></textarea>';
			break;
	
			case 'checkbox':
				echo CheckboxInput('','values[CATEGORY_'.$category['ID'].']','','',true);
				//echo '<input type="CHECKBOX" name="values[CATEGORY_'.$category['ID'].']" value="Y" />';
			break;
			
			case 'date':
				echo DateInput(DBDate(),'_values[CATEGORY_'.$category['ID'].']');
				//echo PrepareDate(DBDate(),'_values[CATEGORY_'.$category['ID'].']');
			break;
			
			case 'multiple_checkbox':
				$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS']) );
				
				echo '<table class="cellpadding-5"><tr class="st">';
				$i = 0;
				foreach ( (array) $options as $option )
				{
					$i++;
					if ( $i%3==0)
						echo '</tr><tr class="st">';
					echo '<td><label><input type="checkbox" name="values[CATEGORY_'.$category['ID'].'][]" value="'.str_replace('"','&quot;',$option).'" />&nbsp;'.$option.'</label></td>';
				}
				echo '</tr></table>';
			break;
			
			case 'multiple_radio':
				$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));
				$options = explode("\r",$category['SELECT_OPTIONS']);
				
				echo '<table class="cellpadding-5"><tr class="st">';
				$i = 0;
				foreach ( (array) $options as $option)
				{
					$i++;
					if ( $i%3==0)
						echo '</tr><tr class="st">';
					echo '<td><label><input type="radio" name="values[CATEGORY_'.$category['ID'].']" value="'.str_replace('"','&quot;',$option).'">&nbsp;'.$option.'</label></td>';
				}
				echo '</tr></table>';
			break;

			case 'select':
				$options = array();
				$category['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category['SELECT_OPTIONS']));

				$select_options = explode("\r",$category['SELECT_OPTIONS']);

				foreach ( (array) $select_options as $option)
					$options[ $option ] = $option;

				echo SelectInput('','values[CATEGORY_'.$category['ID'].']','',$options,'N/A');
				/*echo '<select name="values[CATEGORY_'.$category['ID'].']"><option value="">'._('N/A').'</option>';
				foreach ( (array) $options as $option)
				{
					echo '<option value="'.str_replace('"','&quot;',$option).'">'.$option.'</option>';
				}
				echo '</select>';*/
			break;
		}
		echo '</td></tr>';
	}
	echo '</table>';

	PopTable('footer');

	echo '<br /><div class="center">' . SubmitButton( _( 'Submit' ) ) . '</div>';

	echo '</form>';
}
