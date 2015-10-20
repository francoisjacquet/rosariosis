<?php
// This script will automatically create parent accounts and associate students based on an email address which is part of the student record.

DrawHeader(ProgramTitle());

// remove current student
if ( UserStudentID() )
{
	unset($_SESSION['student_id']);

	//remove student_id from URL
	unset($_SESSION['_REQUEST_vars']['student_id']);
}

// The $email_column corresponds to a student field or an address field which is created for the email address.  The COLUMN_# is the column in the
// students table or the address table which holds the student contact email address.  You will need to create the column and inspect rosario database
// to determine the email column and assign it here.
// Making the email address an address field is useful when using 'ganged' address (address record is shared by multiple students by using the 'add
// existing address feature).
// The column name should start with 's.' if a student field or 'a.' if an address field.
//FJ Moodle Integrator: the "family" email field must be different from the student email field in the Moodle/config.inc.php
$email_column = ''; //example: 'a.CUSTOM_2'

//save $email_column var in SESSION
if (isset($_SESSION['email_column']) && empty($email_column))
	$email_column = $_SESSION['email_column'];
elseif (isset($_POST['email_column']))
	$email_column = $_SESSION['email_column'] = $_POST['email_column'];

if (empty($email_column))
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';

	//get Student / Address fields
	$student_columns = DBGet(DBQuery("SELECT 's.CUSTOM_' || f.ID AS COLUMN, f.TITLE, c.TITLE AS CATEGORY FROM CUSTOM_FIELDS f, STUDENT_FIELD_CATEGORIES c WHERE f.TYPE='text' AND c.ID=f.CATEGORY_ID ORDER BY f.CATEGORY_ID, f.SORT_ORDER"));
	$address_columns = DBGet(DBQuery("SELECT 'a.CUSTOM_' || f.ID AS COLUMN, f.TITLE, c.TITLE AS CATEGORY FROM ADDRESS_FIELDS f, ADDRESS_FIELD_CATEGORIES c WHERE f.TYPE='text' AND c.ID=f.CATEGORY_ID ORDER BY f.CATEGORY_ID, f.SORT_ORDER"));

	//display SELECT input
	$select_html = _('Select Parents email field').': <SELECT id="email_column" name="email_column">';

	$select_html .= '<OPTGROUP label="'.htmlspecialchars(_('Student Fields')).'">';
	foreach ( (array)$student_columns as $student_column)
	{
		$select_html .= '<OPTION value="'.$student_column['COLUMN'].'">'.ParseMLField($student_column['CATEGORY']).' - '.ParseMLField($student_column['TITLE']).'</OPTION>';
	}

	$select_html .= '</OPTGROUP><OPTGROUP label="'.htmlspecialchars(_('Address Fields')).'">';
	foreach ( (array)$address_columns as $address_column)
	{
		$select_html .= '<OPTION value="'.$address_column['COLUMN'].'">'.ParseMLField($address_column['CATEGORY']).' - '.ParseMLField($address_column['TITLE']).'</OPTION>';
	}

	$select_html .= '</OPTGROUP></SELECT>';

	DrawHeader('','',$select_html);

	echo '<BR /><div class="center">' . SubmitButton(_('Select Parents email field')) . '</div>';
	echo '</FORM>';
}

// A list of potential users is obtained from the student contacts with an address.  The student must have at least one such contact.  Students which
// have the same email will be associated to the same user and grouped together in the list and even though each will have contacts listed for selection
// only that of the first student selected in the group will be used in the creation of the account.

// Parent users are created with the following profile id. '3' is the default 'parent' profile.
$profile_id = '3';


// end of user configuration


if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save' && AllowEdit())
{
	// If $test email is set then this script will only 'go through the motions' and email the results to the $test_email address instead of parents
	// no accounts are created and no associations are made.  Use this to verify the behavior and email operation before actual use.
	$test_email = $_REQUEST['test_email'];

	// Set the from and cc emails here - the emails can be comma separated list of emails.
	$cc = '';
	if (User('EMAIL'))
		$cc = User('EMAIL');
	elseif ( !filter_var($test_email, FILTER_VALIDATE_EMAIL))
		ErrorMessage(array(_('You must set the <b>test mode email</b> or have a user email address to use this script.')),'fatal');


	// new for when parent account was created new
	// old for when parent account was existing
	$subject['new'] = _('New Parent Account');
	$subject['old'] = _('Updated Parent Account');

	//FJ add Template
	$createparentstext = $_REQUEST['inputcreateparentstext_new'].'__BLOCK2__'.$_REQUEST['inputcreateparentstext_old'];
	$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = 'Custom/CreateParents.php' AND STAFF_ID = '".User('STAFF_ID')."'"));
	if ( !$template_update)
		DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('Custom/CreateParents.php', '".User('STAFF_ID')."', '".$createparentstext."')");
	else
		DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$createparentstext."' WHERE MODNAME = 'Custom/CreateParents.php' AND STAFF_ID = '".User('STAFF_ID')."'");

	$message['new'] = str_replace("''", "'", $_REQUEST['inputcreateparentstext_new']);
	$message['old'] = str_replace("''", "'", $_REQUEST['inputcreateparentstext_old']);

	if (count($_REQUEST['student']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['student']).'\'';

		$extra['SELECT'] = ",lower(".$email_column.") AS EMAIL";
		$extra['SELECT'] .= ",(SELECT STAFF_ID FROM STAFF WHERE lower(EMAIL)=lower(".$email_column.") AND PROFILE='parent' AND SYEAR=ssm.SYEAR) AS STAFF_ID";
		$extra['WHERE'] = " AND s.STUDENT_ID IN (".$st_list.")";
		$extra['group'] = array('EMAIL');
		$extra['addr'] = true;
		$extra['STUDENTS_JOIN_ADDRESS'] = "AND sam.RESIDENCE='Y'";

		$RET = GetStuList($extra);
		//echo '<pre>'; var_dump($RET); echo '</pre>';

		foreach ( (array)$RET as $email=>$students)
		{
			unset($id);
			$student_id = $students[1]['STUDENT_ID'];

			if ( !$students[1]['STAFF_ID'])
			{
				if ( $_REQUEST['contact'][$student_id])
				{
					//username = user part of the email
					$tmp_username = $username = trim(mb_strpos($students[1]['EMAIL'],'@')!==false?mb_substr($students[1]['EMAIL'],0,mb_strpos($students[1]['EMAIL'],'@')):$students[1]['EMAIL']);

					$i = 1;

					//if username already exists
					while(DBGet(DBQuery("SELECT STAFF_ID FROM STAFF WHERE upper(USERNAME)=upper('".$username."') AND SYEAR='".UserSyear()."'")))
						$username = $tmp_username.$i++;

					$user = DBGet(DBQuery("SELECT FIRST_NAME,MIDDLE_NAME,LAST_NAME FROM PEOPLE WHERE PERSON_ID='".$_REQUEST['contact'][$student_id]."'"));
					$user = $user[1];

					//FJ change parent password generation
					//$password = $passwords[rand(0,count($passwords)-1)];
					$password = $username . rand(100,999);

					//FJ Moodle integrator / password
					$password = UCFirst($password). '*';

					if ( !$test_email)
					{
						// get staff id
						$id = DBGet(DBQuery('SELECT '.db_seq_nextval('STAFF_SEQ').' AS SEQ_ID '.FROM_DUAL));
						$id = $id[1]['SEQ_ID'];

						//FJ add password encryption
						$password_encrypted = encrypt_password($password);

						$sql = "INSERT INTO STAFF (STAFF_ID,SYEAR,PROFILE,PROFILE_ID,FIRST_NAME,MIDDLE_NAME,LAST_NAME,USERNAME,PASSWORD,EMAIL) values ('".$id."','".UserSyear()."','parent','".$profile_id."','".$user['FIRST_NAME']."','".$user['MIDDLE_NAME']."','".$user['LAST_NAME']."','".$username."','".$password_encrypted."','".$students[1]['EMAIL']."')";
						DBQuery($sql);

						//hook
						do_action('Custom/CreateParents.php|create_user');

						$staff = DBGet(DBquery("SELECT FIRST_NAME||' '||LAST_NAME AS NAME,USERNAME,PASSWORD FROM STAFF WHERE STAFF_ID='".$id."'"));
					}
					else
					{
						$id = true;
						$staff = array(1=>array('NAME'=>$user['FIRST_NAME'].' '.$user['LAST_NAME'],'USERNAME'=>$username,'PASSWORD'=>$password));
					}

					$account = 'new';
				}
			}
			else //if user already exists
			{
				$id = $students[1]['STAFF_ID'];
				$staff = DBGet(DBquery("SELECT FIRST_NAME||' '||LAST_NAME AS NAME,USERNAME,PASSWORD FROM STAFF WHERE STAFF_ID='".$id."'"));

				$account = 'old';
			}

			if ( $id)
			{
				$staff = $staff[1];
				$student_list = '';
				foreach ( (array)$students as $student)
				{
					//join users to students
					if ( !$test_email)
					{
						$sql = "INSERT INTO STUDENTS_JOIN_USERS (STAFF_ID,STUDENT_ID) values ('".$id."','".$student['STUDENT_ID']."')";
						DBQuery($sql);

						//hook
						do_action('Custom/CreateParents.php|user_assign_role');
					}
					$student_list .= str_replace('&nbsp;',' ',$student['FULL_NAME'])."\r";
				}

				$msg = str_replace('__ASSOCIATED_STUDENTS__',$student_list,$message[$account]);
				$msg = str_replace('__SCHOOL_ID__',SchoolInfo('TITLE'),$msg);
				$msg = str_replace('__PARENT_NAME__',$staff['NAME'],$msg);
				$msg = str_replace('__USERNAME__',$staff['USERNAME'],$msg);
				$msg = str_replace('__PASSWORD__',$password,$msg);
				
				//FJ add SendEmail function
				include_once('ProgramFunctions/SendEmail.fnc.php');
				
				$to = empty($test_email) ? $students[1]['EMAIL'] : $test_email;
				
				//FJ send email from rosariosis@[domain]
				$result = SendEmail($to, $subject[$account], $msg, null, $cc);

				$RET[$email][1]['PARENT'] = $staff['NAME'];
				$RET[$email][1]['USERNAME'] = $staff['USERNAME'];
				$RET[$email][1]['PASSWORD'] = (empty($password)?'':$password);

				if ( $result)
					$RET[$email][1]['RESULT'] = _('Success');
				else
					$RET[$email][1]['RESULT'] = _('Fail');
			}
			else
				$RET[$email][1]['RESULT'] = _('Fail');
		}

		$columns = array('FULL_NAME'=>_('Student'),'PARENT'=>_('Parent'),'USERNAME'=>_('Username'),'PASSWORD'=>_('Password'),'EMAIL'=>_('Email'),'RESULT'=>_('Result'));
		ListOutput($RET,$columns,'Creation Result','Creation Results',false,array('EMAIL'));
	}
	else
	{
		$error[] = _('You must choose at least one student.');
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['modfunc']);
	}

	//reset $email_column var
	unset($_SESSION['email_column'], $email_column);
}

if (isset($error))
	echo ErrorMessage($error);

if (empty($_REQUEST['modfunc']) && !empty($email_column))
{
	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		$extra['header_right'] = SubmitButton(_('Create Parent Accounts for Selected Students'));
		
		$extra['extra_header_left'] = '<TABLE>';

		//FJ add Template
		$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		list($template_new, $template_old) = explode('__BLOCK2__', $templates[(isset($templates[User('STAFF_ID')]) ? User('STAFF_ID') : 0)][1]['TEMPLATE']);
		
		$extra['extra_header_left'] .= '<TR class="st"><TD>&nbsp;</TD><TD>'.'<label><TEXTAREA name="inputcreateparentstext_new" cols="100" rows="5">'.$template_new.'</TEXTAREA><BR /><span class="legend-gray">'._('New Parent Account').' - '._('Email Text').'</span></label></TD></TR>';
		
		$extra['extra_header_left'] .= '<TR class="st"><TD>&nbsp;</TD><TD>'.'<label><TEXTAREA name="inputcreateparentstext_old" cols="100" rows="5">'.$template_old.'</TEXTAREA><BR /><span class="legend-gray">'._('Updated Parent Account').' - '._('Email Text').'</span></label></TD></TR>';
		
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'._('Substitutions').':</TD><TD><TABLE><TR class="st">';
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

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX,lower($email_column) AS EMAIL,s.STUDENT_ID AS CONTACT";
	$extra['SELECT'] .= ",(SELECT STAFF_ID FROM STAFF WHERE lower(EMAIL)=lower($email_column) AND PROFILE='parent' AND SYEAR=ssm.SYEAR) AS STAFF_ID";
	//$extra['WHERE'] = " AND $email_column IS NOT NULL";
	$extra['WHERE'] .= " AND NOT EXISTS (SELECT '' FROM STUDENTS_JOIN_USERS sju,STAFF st WHERE sju.STUDENT_ID=s.STUDENT_ID AND st.STAFF_ID=sju.STAFF_ID AND SYEAR='".UserSyear()."')";

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox','CONTACT'=>'_makeContactSelect');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');" /><A>');
	$extra['columns_after'] = array('EMAIL'=>_('Email'),'CONTACT'=>_('Contact'));
	$extra['LO_group'] = $extra['group'] = array('EMAIL');
	$extra['addr'] = true;
	$extra['SELECT'] .= ",a.ADDRESS_ID";
	$extra['STUDENTS_JOIN_ADDRESS'] .= " AND sam.RESIDENCE='Y'";

	Search('student_id',$extra);

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><div class="center">' . SubmitButton(_('Create Parent Accounts for Selected Students')) . '</div>';
		echo '</FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	if ( empty( $THIS_RET['STAFF_ID'] ) )
	{
		$has_parents = DBGet( DBQuery( "SELECT 1 
			FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p 
			WHERE p.PERSON_ID=sjp.PERSON_ID 
			AND sjp.STUDENT_ID='" . $value . "' 
			AND sjp.ADDRESS_ID='" . $THIS_RET['ADDRESS_ID'] . "' 
			ORDER BY sjp.STUDENT_RELATION" ) );
	}
	else
		$has_parents = true;

	if ( filter_var( $THIS_RET['EMAIL'], FILTER_VALIDATE_EMAIL )
		&& $has_parents )
		return '<INPUT type="checkbox" name="student['.$value.']" value="'.$value.'" />';
	else
		return '';
}

function _makeContactSelect($value,$column)
{	global $THIS_RET;

	if ( !$THIS_RET['STAFF_ID'])
		$RET = DBGet(DBQuery("SELECT sjp.PERSON_ID,sjp.STUDENT_RELATION,p.FIRST_NAME||' '||p.LAST_NAME AS CONTACT 
		FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p 
		WHERE p.PERSON_ID=sjp.PERSON_ID 
		AND sjp.STUDENT_ID='".$value."' 
		AND sjp.ADDRESS_ID='".$THIS_RET['ADDRESS_ID']."' 
		ORDER BY sjp.STUDENT_RELATION"));
	else
		$RET = DBGet(DBQuery("SELECT '' AS PERSON_ID,STAFF_ID AS STUDENT_RELATION,FIRST_NAME||' '||LAST_NAME AS CONTACT FROM STAFF WHERE STAFF_ID='".$THIS_RET['STAFF_ID']."'"));

	if (count($RET))
	{
		$checked = ' checked';
		$return = '<TABLE class="cellspacing-0">';
		foreach ( (array)$RET as $contact)
		{
			$return .= '<TR><TD>'.($contact['PERSON_ID']?'<INPUT type="radio" name="contact['.$value.']" value='.$contact['PERSON_ID'].$checked.' />':'&nbsp;').'</TD>';
			$return .= '<TD>'.$contact['CONTACT'].'</TD>';
			$return .= '<TD>('.$contact['STUDENT_RELATION'].')</TD></TR>';
			$checked = '';
		}
		$return .= '</TABLE>';
	}
	return $return;
}
