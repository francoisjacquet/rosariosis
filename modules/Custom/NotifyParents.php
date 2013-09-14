<?php

// This was a quick hack to email parents who were assigned accounts but had never logged in
// Warning: the passwords associated to the accounts will be reset

DrawHeader(ProgramTitle());


if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	// If $test email is set then this script will only 'go through the motions' and email the results to the $test_email address instead of parents
	$test_email = $_REQUEST['test_email'];

	// Set the from and cc emails here - the emails can be comma separated list of emails.
	if(!empty($test_email))
		$from = $test_email;
	elseif (User('EMAIL'))
		$from = $cc = User('EMAIL');
	else
		ErrorMessage(array(_('You must set the <b>test mode email</b> or have a user email address to use this script.')),'fatal');
		
	$headers = "From:".$from."\r\nCc:".(isset($cc) ? $cc.',' : '').$RosarioNotifyAddress."\r\n";

	//modif Francois: add email headers
	$headers .= 'Return-Path:'.$from."\r\n"; 
	$headers .= 'Reply-To:'.$from . "\r\n" . 'X-Mailer:PHP/' . phpversion();
	$params = '-f '.$from;

	$subject = ParseMLField(Config('TITLE')).' - '._('New Parent Account');

	//modif Francois: add Template
	$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = 'Custom/NotifyParents.php' AND STAFF_ID = '".User('STAFF_ID')."'"));
	if (!$template_update)
		DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('Custom/NotifyParents.php', '".User('STAFF_ID')."', '".$_REQUEST['inputnotifyparentstext']."')");
	else
		DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$_REQUEST['inputnotifyparentstext']."' WHERE MODNAME = 'Custom/NotifyParents.php' AND STAFF_ID = '".User('STAFF_ID')."'");

	$message = $_REQUEST['inputnotifyparentstext'];

	if(count($_REQUEST['staff']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['staff']).'\'';

		$extra['SELECT'] = ",s.FIRST_NAME||' '||s.LAST_NAME AS NAME,s.USERNAME,s.PASSWORD,s.EMAIL";
		$extra['WHERE'] = " AND s.STAFF_ID IN ($st_list)";

		$RET = GetStaffList($extra);
		//echo '<pre>'; var_dump($RET); echo '</pre>';

		$RESULT = array(0=>array());
		$i = 0;
		foreach($RET as $staff)
		{
			$staff_id = $staff['STAFF_ID'];

	//modif Francois: change parent password generation
			$password = $staff['USERNAME'] . rand(1000,9999);
	//modif Francois: add password encryption
			$password_encrypted = encrypt_password($password);		
			DBQuery("UPDATE STAFF SET PASSWORD='$password_encrypted' WHERE STAFF_ID='$staff_id'");
			
			$students_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,STUDENT_ENROLLMENT sse,STUDENTS_JOIN_USERS sju WHERE sju.STAFF_ID='$staff_id' AND s.STUDENT_ID=sju.STUDENT_ID AND sse.STUDENT_ID=sju.STUDENT_ID AND sse.SYEAR='".UserSyear()."' AND sse.END_DATE IS NULL"));
			//echo '<pre>'; var_dump($students_RET); echo '</pre>';

			$student_list = '';
			foreach($students_RET as $student)
				$student_list .= str_replace('&nbsp;',' ',$student['FULL_NAME'])."\r";

			$msg = str_replace('__ASSOCIATED_STUDENTS__',$student_list,$message);
			$msg = str_replace('__SCHOOL_ID__',SchoolInfo('TITLE'),$msg);
			$msg = str_replace('__PARENT_NAME__',$staff['NAME'],$msg);
			$msg = str_replace('__USERNAME__',$staff['USERNAME'],$msg);
	//modif Francois: add password encryption
	//		$msg = str_replace('__PASSWORD__',$staff['PASSWORD'],$msg);
			$msg = str_replace('__PASSWORD__',$password,$msg);
			$result = @mail(empty($test_email)?$staff['EMAIL']:$test_email,utf8_decode($subject),utf8_decode($msg),$headers,$params);

			$RESULT[] = array('PARENT'=>$staff['FULL_NAME'],'USERNAME'=>$staff['USERNAME'],'EMAIL'=>!$test_email?$staff['EMAIL']:$test_email,'RESULT'=>$result?_('Success'):_('Fail'));
			$i++;
		}
		unset($RESULT[0]);
		$columns = array('PARENT'=>_('Parent'),'USERNAME'=>_('Username'),'EMAIL'=>_('Email'),'RESULT'=>_('Result'));
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

if(empty($_REQUEST['modfunc']) || $_REQUEST['search_modfunc']=='list')
{
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		$extra['header_right'] = SubmitButton(_('Notify Selected Parents'));
		
		$extra['extra_header_left'] = '<TABLE>';

		//modif Francois: add Template
		$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		
		$template = $templates[(isset($templates[User('STAFF_ID')]) ? User('STAFF_ID') : 0)][1]['TEMPLATE'];

		$extra['extra_header_left'] .= '<TR class="st"><TD>&nbsp;</TD><TD>'.'<label><TEXTAREA name="inputnotifyparentstext" cols="97" rows="5">'.str_replace(array("'",'"'),array('&#39;','&rdquo;',''),$template).'</TEXTAREA><BR /><span class="legend-gray">'.str_replace(array("'",'"'),array('&#39;','\"'),_('New Parent Account').' - '._('Email Text')).'</span></label></TD></TR>';
		
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'.Localize('colon',_('Substitutions')).'</TD><TD><TABLE><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__PARENT_NAME__</TD><TD>= '._('Parent Name').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__ASSOCIATED_STUDENTS__</TD><TD>= '._('Associated Students').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__USERNAME__</TD><TD>= '._('Username').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__PASSWORD__</TD><TD>= '._('Password').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__SCHOOL_ID__</TD><TD>= '._('School').'</TD><TD colspan="3">&nbsp;</TD>';
		$extra['extra_header_left'] .= '</TR></TABLE></TD></TR>';
		
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'._('Test Mode').':'.'</TD><TD><label><input name="test_email" type="text" /><BR /><span class="legend-gray">'._('Email').'</span></label></TD></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
	}

	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX,s.USERNAME,s.EMAIL";

	$extra['SELECT'] .= ",(SELECT count(st.STUDENT_ID) FROM STUDENTS st,STUDENT_ENROLLMENT sse,STUDENTS_JOIN_USERS sju WHERE sju.STAFF_ID=s.STAFF_ID AND st.STUDENT_ID=sju.STUDENT_ID AND sse.STUDENT_ID=sju.STUDENT_ID AND sse.SYEAR='".UserSyear()."' AND sse.END_DATE IS NULL) AS ASSOCIATED";

	$extra['WHERE'] = " AND s.LAST_LOGIN IS NULL";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'staff\');" /><A>');
	$extra['columns_after'] = array('ASSOCIATED'=>_('Associated Students'),'USERNAME'=>_('Username'),'EMAIL'=>_('Email'));
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['profile'] = 'parent';
	$extra['new'] = true;

	Search('staff_id',$extra);

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Notify Selected Parents')).'</span>';
		echo '</FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	if($THIS_RET['USERNAME'] && $THIS_RET['EMAIL'] && $THIS_RET['ASSOCIATED']>0)
		return '&nbsp;&nbsp;<INPUT type="checkbox" name="staff['.$value.']" value="'.$value.'" />';
	else
		return '';
}
?>