<?php

function User($item)
{	global $_ROSARIO;

	if(!UserSyear())
		$_SESSION['UserSyear'] = Config('SYEAR');

	if(!isset($_ROSARIO['User']) || UserSyear()!=$_ROSARIO['User'][1]['SYEAR'])
	{
		if(!empty($_SESSION['STAFF_ID']))
		{
			$sql = "SELECT STAFF_ID,USERNAME,FIRST_NAME||' '||LAST_NAME AS NAME,PROFILE,PROFILE_ID,SCHOOLS,CURRENT_SCHOOL_ID,EMAIL,SYEAR,LAST_LOGIN 
			FROM STAFF 
			WHERE SYEAR='".UserSyear()."' 
			AND USERNAME=(SELECT USERNAME FROM STAFF WHERE SYEAR='".Config('SYEAR')."' AND STAFF_ID='".$_SESSION['STAFF_ID']."')";

			$_ROSARIO['User'] = DBGet(DBQuery($sql));
		}
		elseif(!empty($_SESSION['STUDENT_ID']))
		{
			$sql = "SELECT '0' AS STAFF_ID,s.USERNAME,s.FIRST_NAME||' '||s.LAST_NAME AS NAME,'student' AS PROFILE,'0' AS PROFILE_ID,','||se.SCHOOL_ID||',' AS SCHOOLS,se.SYEAR,se.SCHOOL_ID 
			FROM STUDENTS s,STUDENT_ENROLLMENT se 
			WHERE s.STUDENT_ID='".$_SESSION['STUDENT_ID']."' 
			AND se.SYEAR='".UserSyear()."' 
			AND se.STUDENT_ID=s.STUDENT_ID 
			ORDER BY se.END_DATE DESC LIMIT 1";

			$_ROSARIO['User'] = DBGet(DBQuery($sql));

			if($_ROSARIO['User'][1]['SCHOOL_ID']!=UserSchool())
				$_SESSION['UserSchool'] = $_ROSARIO['User'][1]['SCHOOL_ID'];
		}
		//FJ create account
		elseif(basename($_SERVER['PHP_SELF'])=='index.php')
			return false;
		else
		{
			$error[] = 'User not logged in!';//should never be displayed, so do not translate

			return ErrorMessage($error, 'fatal');
		}
	}

	return $_ROSARIO['User'][1][$item];
}

function Preferences($item='',$program='Preferences')
{	global $_ROSARIO;

	if($_SESSION['STAFF_ID'] && !isset($_ROSARIO['Preferences'][$program]))
	{
        $QI = DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".$_SESSION['STAFF_ID']."' AND PROGRAM='".$program."'");
		$_ROSARIO['Preferences'][$program] = DBGet($QI,array(),array('TITLE'));
	}
	if($item=='')
		return;

	//FJ add Default Theme to Configuration
	$default_theme = Config('THEME');

	$defaults = array('SORT'=>'Name',
				'SEARCH'=>'Y',
				'DELIMITER'=>'Tab',
				'HEADER'=>'#333366',
				'HIGHLIGHT'=>'#FFFFFF',
				'THEME'=>$default_theme,
				'MONTH'=>'%B',
				'DAY'=>'%d',
				'YEAR'=>'%Y',
				'DEFAULT_ALL_SCHOOLS'=>'N',
				'ASSIGNMENT_SORTING'=>'ASSIGNMENT_ID',
				'ANOMALOUS_MAX'=>'100',
				'SCROLL_TOP'=>'Y',
				'PAGE_SIZE'=>'A4'
				);

	if(!isset($_ROSARIO['Preferences'][$program][$item][1]['VALUE']))
		$_ROSARIO['Preferences'][$program][$item][1]['VALUE'] = $defaults[$item];

	if(!empty($_SESSION['STAFF_ID']) && User('PROFILE')=='parent' || !empty($_SESSION['STUDENT_ID']))
		$_ROSARIO['Preferences'][$program]['SEARCH'][1]['VALUE'] = 'N';

	return $_ROSARIO['Preferences'][$program][$item][1]['VALUE'];
}
?>
