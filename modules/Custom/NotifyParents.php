<?php

// This was a quick hack to email parents who were assigned accounts but had never logged in
// Warning: the passwords associated to the accounts will be reset

DrawHeader(ProgramTitle());


if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	// If $test email is set then this script will only 'go through the motions' and email the results to the $test_email address instead of parents
	$test_email = $_REQUEST['test_email'];

	// Set the from and cc emails here - the emails can be comma separated list of emails.
	$cc = '';
	if (User('EMAIL'))
		$cc = User('EMAIL');
	elseif ( !filter_var($test_email, FILTER_VALIDATE_EMAIL))
		ErrorMessage(array(_('You must set the <b>test mode email</b> or have a user email address to use this script.')),'fatal');
		
	$subject = _('New Parent Account');

	//FJ add Template
	$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = 'Custom/NotifyParents.php' AND STAFF_ID = '".User('STAFF_ID')."'"));
	if ( ! $template_update)
		DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('Custom/NotifyParents.php', '".User('STAFF_ID')."', '".$_REQUEST['inputnotifyparentstext']."')");
	else
		DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$_REQUEST['inputnotifyparentstext']."' WHERE MODNAME = 'Custom/NotifyParents.php' AND STAFF_ID = '".User('STAFF_ID')."'");

	$message = str_replace("''", "'", $_REQUEST['inputnotifyparentstext']);

	if (count($_REQUEST['staff']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['staff']).'\'';

		$extra['SELECT'] = ",s.FIRST_NAME||' '||s.LAST_NAME AS NAME,s.USERNAME,s.PASSWORD,s.EMAIL";
		$extra['WHERE'] = " AND s.STAFF_ID IN ($st_list)";

		$RET = GetStaffList($extra);
		//echo '<pre>'; var_dump($RET); echo '</pre>';

		$RESULT = array(0 => array());
		$i = 0;
		foreach ( (array) $RET as $staff)
		{
			$staff_id = $staff['STAFF_ID'];

	//FJ change parent password generation
			$password = $staff['USERNAME'] . rand(1000,9999);
	//FJ add password encryption
			$password_encrypted = encrypt_password($password);		
			DBQuery("UPDATE STAFF SET PASSWORD='".$password_encrypted."' WHERE STAFF_ID='".$staff_id."'");
			
			$students_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME 
			FROM STUDENTS s,STUDENT_ENROLLMENT sse,STUDENTS_JOIN_USERS sju 
			WHERE sju.STAFF_ID='".$staff_id."' 
			AND s.STUDENT_ID=sju.STUDENT_ID 
			AND sse.STUDENT_ID=sju.STUDENT_ID 
			AND sse.SYEAR='".UserSyear()."' 
			AND sse.END_DATE IS NULL"));
			//echo '<pre>'; var_dump($students_RET); echo '</pre>';

			$student_list = '';
			foreach ( (array) $students_RET as $student)
				$student_list .= str_replace('&nbsp;',' ',$student['FULL_NAME'])."\r";

			$msg = str_replace('__ASSOCIATED_STUDENTS__',$student_list,$message);
			$msg = str_replace('__SCHOOL_ID__',SchoolInfo('TITLE'),$msg);
			$msg = str_replace('__PARENT_NAME__',$staff['NAME'],$msg);
			$msg = str_replace('__USERNAME__',$staff['USERNAME'],$msg);
	//FJ add password encryption
	//		$msg = str_replace('__PASSWORD__',$staff['PASSWORD'],$msg);
			$msg = str_replace('__PASSWORD__',$password,$msg);
			
			//FJ add SendEmail function
			require_once 'ProgramFunctions/SendEmail.fnc.php';
			
			$to = empty($test_email)?$staff['EMAIL']:$test_email;
			
			//FJ send email from rosariosis@[domain]
			$result = SendEmail($to, $subject, $msg, null, $cc);

			$RESULT[] = array('PARENT' => $staff['FULL_NAME'],'USERNAME' => $staff['USERNAME'],'EMAIL'=>! $test_email?$staff['EMAIL']:$test_email,'RESULT' => $result?_('Success'):_('Fail'));
			$i++;
		}
		unset($RESULT[0]);
		$columns = array('PARENT' => _('Parent'),'USERNAME' => _('Username'),'EMAIL' => _('Email'),'RESULT' => _('Result'));
		ListOutput($RESULT,$columns,'Notification Result','Notification Results');
	}
	else
	{
		$error[] = _('You must choose at least one user');
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['modfunc']);
	}
}

if (isset($error))
	echo ErrorMessage($error);

if (empty($_REQUEST['modfunc']) || $_REQUEST['search_modfunc']=='list')
{
	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		$extra['header_right'] = SubmitButton(_('Notify Selected Parents'));
		
		$extra['extra_header_left'] = '<table>';

		//FJ add Template
		$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		
		$template = $templates[(isset($templates[User('STAFF_ID')]) ? User('STAFF_ID') : 0)][1]['TEMPLATE'];

		$extra['extra_header_left'] .= '<tr class="st"><td>&nbsp;</td><td>' .
			'<textarea name="inputnotifyparentstext" cols="97" rows="5">'
			. $template . '</textarea>' .
			FormatInputTitle(
				_( 'New Parent Account' ) . ' - ' . _( 'Email Text' ),
				'inputnotifyparentstext'
			) . '</td></tr>';
		
		$extra['extra_header_left'] .= '<tr class="st"><td style="vertical-align: top;">'._('Substitutions').':</td><td><table><tr class="st">';
		$extra['extra_header_left'] .= '<td>__PARENT_NAME__</td><td>= '._('Parent Name').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__ASSOCIATED_STUDENTS__</td><td>= '._('Associated Students').'</td>';
		$extra['extra_header_left'] .= '</tr><tr class="st">';
		$extra['extra_header_left'] .= '<td>__USERNAME__</td><td>= '._('Username').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__PASSWORD__</td><td>= '._('Password').'</td>';
		$extra['extra_header_left'] .= '</tr><tr class="st">';
		$extra['extra_header_left'] .= '<td>__SCHOOL_ID__</td><td>= '._('School').'</td><td colspan="3">&nbsp;</td>';
		$extra['extra_header_left'] .= '</tr></table></td></tr>';
		
		$extra['extra_header_left'] .= '<tr class="st"><td style="vertical-align: top;">' .
			_( 'Test Mode' ) . ':' . '</td><td>' .
			TextInput(
				'',
				'test_email',
				_( 'Email' ),
				'',
				false
			) . '</td></tr>';

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX,s.USERNAME,s.EMAIL";

	$extra['SELECT'] .= ",(SELECT count(st.STUDENT_ID) FROM STUDENTS st,STUDENT_ENROLLMENT sse,STUDENTS_JOIN_USERS sju WHERE sju.STAFF_ID=s.STAFF_ID AND st.STUDENT_ID=sju.STUDENT_ID AND sse.STUDENT_ID=sju.STUDENT_ID AND sse.SYEAR='".UserSyear()."' AND sse.END_DATE IS NULL) AS ASSOCIATED";

	$extra['WHERE'] = " AND s.LAST_LOGIN IS NULL";
	$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'staff\');" /><A>');
	$extra['columns_after'] = array('ASSOCIATED' => _('Associated Students'),'USERNAME' => _('Username'),'EMAIL' => _('Email'));
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['profile'] = 'parent';
	$extra['new'] = true;

	Search('staff_id',$extra);

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton(_('Notify Selected Parents')) . '</div>';
		echo '</form>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	if ( $THIS_RET['USERNAME'] && $THIS_RET['EMAIL'] && $THIS_RET['ASSOCIATED']>0)
		return '<input type="checkbox" name="staff['.$value.']" value="'.$value.'" />';
	else
		return '';
}
