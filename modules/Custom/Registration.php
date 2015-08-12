<?php
if(!UserStudentID())
{
	$_SESSION['UserSyear'] = Config('SYEAR');
	$RET = DBGet(DBQuery("SELECT sju.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,se.SCHOOL_ID 
	FROM STUDENTS s,STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se 
	WHERE s.STUDENT_ID=sju.STUDENT_ID 
	AND sju.STAFF_ID='".User('STAFF_ID')."' 
	AND se.SYEAR='".UserSyear()."' 
	AND se.STUDENT_ID=sju.STUDENT_ID 
	AND (('".DBDate()."' BETWEEN se.START_DATE AND se.END_DATE OR se.END_DATE IS NULL) 
	AND '".DBDate()."'>=se.START_DATE)"));

	//note: do not use SetUserStudentID() here as this is safe
	$_SESSION['student_id'] = $RET[1]['STUDENT_ID'];
}

$_ROSARIO['allow_edit'] = true;

$_REQUEST['values']['STUDENTS']['CUSTOM_200000004'] = $_REQUEST['day_birth_date'].'-'.$_REQUEST['month_birth_date'].'-'.$_REQUEST['year_birth_date'];
unset($_REQUEST['day_birth_date']); unset($_REQUEST['month_birth_date']); unset($_REQUEST['year_birth_date']);
if(!VerifyDate($_REQUEST['values']['STUDENTS']['CUSTOM_200000004']))
	unset($_REQUEST['values']['STUDENTS']['CUSTOM_200000004']);


if($_REQUEST['values'])
{
	if($_REQUEST['values']['ADDRESS'])
	{
		foreach($_REQUEST['values']['ADDRESS'] as $key=>$columns)
		{
			if($columns['ADDRESS'] && !$inserted_addresses[preg_replace('/[^0-9A-Za-z]+/','',mb_strtolower($columns['ADDRESS']))])
			{
				$address_RET = DBGet(DBQuery("SELECT ".db_seq_nextval('ADDRESS_SEQ').' AS ADDRESS_ID '.FROM_DUAL));
				$address_id[$key] = $address_RET[1]['ADDRESS_ID'];
				if($key==1)
					$address_id[2] = $address_RET[1]['ADDRESS_ID'];
				$sql = "INSERT INTO ADDRESS ";

				$fields = 'ADDRESS_ID,';
				$values = $address_id[$key].',';

				if($columns['ADDRESS'])
					$columns += PrepareAddress($columns['ADDRESS']);
				$columns['PHONE'] = mb_substr(preg_replace('/[^0-9]+/','',$columns['PHONE']),0,7);

				unset($address['ADDRESS']);
				$go = 0;
				foreach($columns as $column=>$value)
				{
					if(!empty($value) || $value=='0')
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if($go)
				{
					DBQuery($sql);
					DBQuery("INSERT INTO STUDENTS_JOIN_ADDRESS (ID,STUDENT_ID,ADDRESS_ID) values(".db_seq_nextval('STUDENTS_JOIN_ADDRESS_SEQ').",'".UserStudentID()."','".$address_id[$key]."')");
				}
				$inserted_addresses[preg_replace('/[^0-9A-Za-z]+/','',mb_strtolower($columns['ADDRESS']))] = $address_id[$key];
			}
			else
				$address_id[$key] = $inserted_addresses[preg_replace('/[^0-9A-Za-z]+/','',mb_strtolower($columns['ADDRESS']))];
		}
	}

	if($_REQUEST['values']['PEOPLE'])
	{
		foreach($_REQUEST['values']['PEOPLE'] as $key=>$person)
		{
			if($person['FIRST_NAME'] && $person['LAST_NAME'])
			{
				$person_id = DBGet(DBQuery("SELECT ".db_seq_nextval('PEOPLE_SEQ').' AS PERSON_ID '.FROM_DUAL));
				$person_id = $person_id[1]['PERSON_ID'];

				if($key==1 || $key==2)
					$person['extra']['Relation'] = 'Parent';
				elseif($key>=3 && $key<=6)
					$person['extra']['Relation'] = 'Grandparent';

				foreach($person['extra'] as $column=>$value)
				{
					if(!empty($value) || $value=='0')
					{
						$value = $value;
						$sql = "INSERT INTO PEOPLE_JOIN_CONTACTS ";
						$fields = 'ID,PERSON_ID,TITLE,VALUE,';
						$values = db_seq_nextval('PEOPLE_SEQ').",'".$person_id."','".$column."','".$value."',";
						$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
						DBQuery($sql);
					}
				}

				unset($person['extra']);

				$sql = "INSERT INTO PEOPLE ";
				$fields = 'PERSON_ID,';
				$values = "'".$person_id."',";
				$go = 0;
				foreach($person as $column=>$value)
				{
					if(!empty($value) || $value=='0')
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if($go)
				{
					DBQuery($sql);
					if($key==1 || $key==2)
						DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY) values(".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'".UserStudentID()."','".$person_id."','".$address_id[$key]."','Y')");
					elseif($address_id[$key])
						DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID) values(".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'".UserStudentID()."','".$person_id."','".$address_id[$key]."')");
					else
						DBQuery("INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,EMERGENCY) values(".db_seq_nextval('STUDENTS_JOIN_PEOPLE_SEQ').",'".UserStudentID()."','".$person_id."','".$address_id[1]."','Y')");
				}
			}
		}
	}

	if($_REQUEST['values']['STUDENTS'])
	{
		$sql = "UPDATE STUDENTS SET ";
		foreach($_REQUEST['values']['STUDENTS'] as $column_name=>$value)
		{
			$sql .= "$column_name='".$value."',";
		}

		$sql = mb_substr($sql,0,-1) . " WHERE STUDENT_ID='".UserStudentID()."'";
		DBQuery($sql);
	}

	$student = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
	
	if($RosarioNotifyAddress)
	{
		//FJ add SendEmail function
		include_once('ProgramFunctions/SendEmail.fnc.php');
		
		$student_name = $student[1]['FIRST_NAME'].' '.$student[1]['LAST_NAME'];
	
		$message = sprintf('New Registration %s (%d) has been registered by %s.', $student_name, UserStudentID(), User('NAME'));
	
		SendEmail($RosarioNotifyAddress, 'New Registration', $message);
	}
	
	unset($_SESSION['_REQUEST_vars']['values']);
}
echo '<H4>Welcome, '.User('NAME').', to the '.ParseMLField(Config('TITLE')).'</H4>';

$addresses = DBGet(DBQuery("SELECT COUNT(*) AS COUNT FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID='".UserStudentID()."'"));
echo ''._('We would appreciate it if you would enter just a little bit of information about you and your child to help us out this school year. Thanks!').'';

if($addresses[1]['COUNT']!=0)
	echo '<BR /><BR />'. button('check', '', '', 'bigger') .'<b>'._('Your child has been registered.').'</b>';

echo '<BR /><BR /><TABLE><TR><TD class="valign-top">';
echo '<B>'._('Information about you').':</B><BR /><BR />';
echo '<TABLE><TR><TD>';
echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST" onsubmit=\'for(i=0;i<document.forms[0].elements.length;i++){if(document.forms[0].elements[i].style.color=="rgb(187, 187, 187)" || document.forms[0].elements[i].style.color=="#bbbbbb") document.forms[0].elements[i].value="";}\'>';
echo _makeInput('values[PEOPLE][1][FIRST_NAME]',_('First Name'));
echo _makeInput('values[PEOPLE][1][LAST_NAME]',_('Last Name'));
echo '<BR />'._makeInput('values[PEOPLE][1][extra][Cell]',_('Cell Phone'),'','size=30');
echo '<BR />'._makeInput('values[PEOPLE][1][extra][Workplace]',_('Workplace'),'','size=30');
echo '</TD></TR></TABLE>';
echo '</TD><TD>';
echo '<B>'._('Information about your spouse or significant other residing with you').':</B><BR />'._('Leave this section blank if you are separated.').'';
echo '<TABLE><TR><TD class="valign-top">';
echo _makeInput('values[PEOPLE][2][FIRST_NAME]',_('First Name'));
echo _makeInput('values[PEOPLE][2][LAST_NAME]',_('Last Name'));
echo '<BR />'._makeInput('values[PEOPLE][2][extra][Cell]',_('Cell Phone'),'','size=30');
echo '<BR />'._makeInput('values[PEOPLE][2][extra][Workplace]',_('Workplace'),'','size=30');
echo '</TD></TR></TABLE>';
echo '</TD></TR><TR><TD colspan="2">';

echo '<B>'._('Your Address').':</B>';
echo '<TABLE><TR><TD>';
echo _makeInput('values[ADDRESS][1][ADDRESS]',_('Address'),'','size=40');
echo '<BR />'._makeInput('values[ADDRESS][1][CITY]',_('City'),'','size=35');
echo ' '._makeInput('values[ADDRESS][1][STATE]',_('State'),'','size=3 maxlength=2');
echo '<BR />'._makeInput('values[ADDRESS][1][ZIPCODE]',_('Zip'),'','size=6');
echo '<BR /><BR /> '._makeInput('values[ADDRESS][1][PHONE]',_('Phone'),'','size=9 maxlength=30');
echo '<BR /><BR />';
echo '</TD></TR>';

for($i=3;$i<=6;$i++)
{
	if($i==3 || $i==5)
		echo '<TR>';
	echo '<TD class="valign-top">';
	echo '<B>'._('Grandparent Information').':</B>';
	echo '<BR />'._makeInput('values[PEOPLE]['.$i.'][FIRST_NAME]',_('First Name'));
	echo _makeInput('values[PEOPLE]['.$i.'][LAST_NAME]',_('Last Name'));
	echo '<BR />';
	echo _makeInput('values[PEOPLE]['.$i.'][extra][Cell]',_('Cell Phone'),'','size=30');
	echo '<BR />';
	echo _makeInput('values[ADDRESS]['.$i.'][ADDRESS]',_('Address'),'','size=40');
	echo '<BR />'._makeInput('values[ADDRESS]['.$i.'][CITY]',_('City'),'','size=35');
	echo ' '._makeInput('values[ADDRESS]['.$i.'][STATE]',_('State'),'','size=3 maxlength=2');
	echo '<BR />'._makeInput('values[ADDRESS]['.$i.'][ZIPCODE]',_('Zip'),'','size=6 maxlength=10');
	echo '<BR /><BR />'._makeInput('values[ADDRESS]['.$i.'][PHONE]',_('Phone'),'','size=9 maxlength=30');
	if($i==4)
		echo '<BR /><BR />';
	echo '</TD>';
	if($i==4 || $i==6)
		echo '</TR>';
}

echo '<TR><TD colspan="2">';
echo '<BR /><B>'._('Other Contacts').':</B><BR />';

echo _makeInput('values[PEOPLE][7][FIRST_NAME]',_('First Name'));
echo _makeInput('values[PEOPLE][7][LAST_NAME]',_('Last Name'));
echo _makeInput('values[PEOPLE][7][extra][Relation]',_('Relation to Student'),'','size=30');
echo _makeInput('values[PEOPLE][7][extra][Cell]',_('Cell Phone'),'','size=30');

echo '<BR />'._makeInput('values[PEOPLE][8][FIRST_NAME]',_('First Name'));
echo _makeInput('values[PEOPLE][8][LAST_NAME]',_('Last Name'));
echo _makeInput('values[PEOPLE][8][extra][Relation]',_('Relation to Student'),'','size=30');
echo _makeInput('values[PEOPLE][8][extra][Cell]',_('Cell Phone'),'','size=30');

echo '</TD></TR></TABLE>';
echo '</TD></TR></TABLE>';
echo '<HR>';
$custom_fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE,SELECT_OPTIONS FROM CUSTOM_FIELDS WHERE ID"),array(),array('ID'));
$student = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'"));
echo '<B>'.sprintf(_('Information about %s %s'),$student[1]['FIRST_NAME'],$student[1]['LAST_NAME']).':</B>';
echo '<TABLE>';
echo '<TR>';
echo '<TD>';
if ($custom_fields_RET['200000004'] && $custom_fields_RET['200000004'][1]['TYPE'] == 'date')
	echo DateInput($student['CUSTOM_200000004'],'birth_date',ParseMLField($custom_fields_RET['200000004'][1]['TITLE']));
echo '</TD>';
echo '<TD>';
if ($custom_fields_RET['200000003'])
	echo _makeInput('values[STUDENTS][CUSTOM_200000003]',ParseMLField($custom_fields_RET['200000003'][1]['TITLE']));
echo '</TD>';
echo '</TR>';
echo '<TR>';
echo '<TD>';
if ($custom_fields_RET['200000001'] && $custom_fields_RET['200000001'][1]['TYPE'] == 'select')
{
	$select_options = array();
	$select_options_array = explode('<br />', nl2br($custom_fields_RET['200000001'][1]['SELECT_OPTIONS']));
	foreach ($select_options_array as $select_option)
		$select_options[$select_option] = $select_option;
	echo SelectInput($student['CUSTOM_200000001'],'values[STUDENTS][CUSTOM_200000001]',ParseMLField($custom_fields_RET['200000001'][1]['TITLE']),$select_options);
}
echo '</TD>';
echo '<TD>';

if ($custom_fields_RET['200000005'] && $custom_fields_RET['200000005'][1]['TYPE'] == 'select')
{
	$select_options = array();
	$select_options_array = explode('<br />', nl2br($custom_fields_RET['200000005'][1]['SELECT_OPTIONS']));
	foreach ($select_options_array as $select_option)
		$select_options[$select_option] = $select_option;
	echo SelectInput($student['CUSTOM_200000005'],'values[STUDENTS][CUSTOM_200000005]',ParseMLField($custom_fields_RET['200000005'][1]['TITLE']),$select_options,_('N/A'),'style="width:200"');
}
echo '</TD>';
echo '</TR>';

echo '<TR>';
echo '<TD>';
if ($custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select')
{
	$select_options = array();
	$select_options_array = explode('<br />', nl2br($custom_fields_RET['200000000'][1]['SELECT_OPTIONS']));
	foreach ($select_options_array as $select_option)
		$select_options[$select_option] = $select_option;
	echo SelectInput($student['CUSTOM_200000000'],'values[STUDENTS][CUSTOM_200000000]',ParseMLField($custom_fields_RET['200000000'][1]['TITLE']),$select_options);
}
echo '</TD>';
echo '</TR>';
echo '<TR><TD colspan="2" class="center">';
if ($custom_fields_RET['200000006'])
	echo '<BR />'._makeInput('values[STUDENTS][CUSTOM_200000006]',ParseMLField($custom_fields_RET['200000006'][1]['TITLE']),'','size=30');
if ($custom_fields_RET['200000007'])
	echo '<BR />'._makeInput('values[STUDENTS][CUSTOM_200000007]',ParseMLField($custom_fields_RET['200000007'][1]['TITLE']),'','size=30');
if ($custom_fields_RET['200000008'])
	echo '<BR />'._makeInput('values[STUDENTS][CUSTOM_200000008]',ParseMLField($custom_fields_RET['200000008'][1]['TITLE']),'','size=30');
if ($custom_fields_RET['200000009'])
	echo '<BR /><TEXTAREA name=values[STUDENTS][CUSTOM_200000009] cols=26 rows=5 style="color: BBBBBB;" onfocus=\'if(this.value=="Medical Comments") this.value=""; this.style.color="000000";\' onblur=\'if(this.value=="") {this.value="Medical Comments"; this.style.color="BBBBBB";}\'">'.ParseMLField($custom_fields_RET['200000009'][1]['TITLE']).'</TEXTAREA>';
echo '</TD></TR>';
echo '</TABLE>';
echo '<BR />';
$_ROSARIO['DrawHeader'] = 'E8E8E9';
DrawHeader('','',SubmitButton(_('Save')));
echo '</form>';

function _makeInput($name,$title,$value='',$extra='')
{
	return '<INPUT type="text" name="'.$name.'" value="'.$title.'" style="color:
	BBBBBB" onfocus=\'if(this.value=="'.$title.'") this.value=""; this.style.color="000000"\' onsubmit=\'if(this.value=="'.$title.'") this.value=""; this.style.color="000000"\' onblur=\'if(this.value=="") {this.value="'.$title.'"; this.style.color="BBBBBB"}\' '.$extra.' />';
}

function PrepareAddress($temp)
{
	$address = array();
	preg_match('/^[0-9]+/',$temp,$regs);$temp = preg_replace('^[0-9]+ ','',$temp);
	if($regs[0])
		$address['HOUSE_NO'] = $regs[0];

	$temp_dir = mb_strtoupper(str_replace('.',' ',mb_substr($temp,0,2)));
	if($temp_dir=='W ' || $temp_dir=='E ' || $temp_dir=='N ' || $temp_dir=='S ')
	{
		$address['DIRECTION'] = mb_substr($temp,0,1);
		$address['STREET'] = mb_substr($temp,2);
	}
	elseif($temp_dir=='NO' || $temp_dir=='SO' || $temp_dir=='WE' || $temp_dir=='EA')
	{
		$temp_dir = str_replace('.','',mb_strtoupper(mb_substr($temp,0,mb_strpos($temp,' '))));
		switch($temp_dir)
		{
			case 'NORTH':
				$address['DIRECTION'] = 'N';
				$address['STREET'] = mb_substr($temp,mb_strpos($temp,' '));
			break;

			case 'SOUTH':
				$address['DIRECTION'] = 'S';
				$address['STREET'] = mb_substr($temp,mb_strpos($temp,' '));
			break;

			case 'EAST':
				$address['DIRECTION'] = 'E';
				$address['STREET'] = mb_substr($temp,mb_strpos($temp,' '));
			break;

			case 'WEST':
				$address['DIRECTION'] = 'W';
				$address['STREET'] = mb_substr($temp,mb_strpos($temp,' '));
			break;

			default:
				$address['STREET'] = $temp;
			break;
		}
		$address['STREET'] = trim($address['STREET']);
	}
	else
		$address['STREET'] = $temp;
	return $address;
}
?>
