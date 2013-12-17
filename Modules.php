<?php
error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ERROR);
include 'Warehouse.php';

function array_rwalk(&$array, $function)
{
	foreach($array as $key => $value)
	{
		if(is_array($value))
		{
			array_rwalk($value, $function);
			$array[$key] = $value;
		}
		else
			$array[$key] = $function($value);
	}
}

array_rwalk($_REQUEST,'DBEscapeString');

if(isset($_REQUEST['modname']))
{
	$modname = $_REQUEST['modname'];
	
	//modif Francois: add TinyMCE to the textarea (see modules/Students/Letters.php & modules/Grades/HonorRollSubject.php & modules/Grades/HonorRoll.php)
	if (($modname=='Students/Letters.php' && isset($_REQUEST['letter_text'])) || (($modname=='Grades/HonorRollSubject.php' || $modname=='Grades/HonorRoll.php') && isset($_REQUEST['honor_roll_text'])))
	{
		$REQUEST_letter_text = $_REQUEST['letter_text'];
		$REQUEST_honor_roll_text = $_REQUEST['honor_roll_text'];
	}
	array_rwalk($_REQUEST,'strip_tags');

	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
		if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($modname == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($modname, array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php', 'Scheduling/Courses.php')) && $_REQUEST['modfunc'] == 'choose_course')) //popups
		{
			Warehouse('header');
			echo '<script type="text/javascript">if(window == top  && (!window.opener)) window.location.href = "index.php";</script>';
			echo '<div id="body" tabindex="0" role="main" class="mod">';
		}
		elseif ($modname !== 'misc/Portal.php' && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) //AJAX check
			HackingLog(); //no direct access		
	}

	if(isset($_REQUEST['_ROSARIO_PDF']) && $_REQUEST['_ROSARIO_PDF']=='true')
		ob_start();

	if(empty($_REQUEST['LO_save']) && !isset($_REQUEST['_ROSARIO_PDF']) && (mb_strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
		$_SESSION['_REQUEST_vars'] = $_REQUEST;

	$allowed = false;
	include 'Menu.php';
	foreach($_ROSARIO['Menu'] as $modcat=>$programs)
	{
		if($modname==$modcat.'/Search.php')
		{
			$allowed = true;
			break;
		}
		foreach($programs as $program=>$title)
		{
//modif Francois: fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
			if($modname==$program || (mb_strpos($program, $modname)=== 0 && mb_strpos($_SERVER['QUERY_STRING'], $program)=== 8))
			{
				$allowed = true;
				$program_loaded = $program; //eg: "Student_Billing/Statements.php&_ROSARIO_PDF"
				break;
			}
		}
	}
	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(mb_substr($_REQUEST['modname'],0,5)=='misc/')
	if (!$allowed && in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/Portal.php', 'misc/ViewContact.php')))
		$allowed = true;

	if($allowed)
	{
		if(Preferences('SEARCH')!='Y')
			$_REQUEST['search_modfunc'] = 'list';
		include('modules/'.$modname);
	}
	else
	{
		if(User('USERNAME'))
			//modif Francois: create HackingLog function to centralize code
			HackingLog();
			
		exit;
	}

	if(isset($_SESSION['unset_student']))
	{
		unset($_SESSION['unset_student']);
		//unset($_SESSION['staff_id']); // mab 070704 why is this here
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		Warehouse('footer');
		if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($modname == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($modname, array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php', 'Scheduling/Courses.php')) && $_REQUEST['modfunc'] == 'choose_course')) //popups
		{
			echo '</div>';//#body
			Warehouse('footer_plain');
		}
	}
}
?>