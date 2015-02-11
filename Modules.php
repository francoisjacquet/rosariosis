<?php
error_reporting(E_ALL ^ E_NOTICE);
include('Warehouse.php');

if(isset($_REQUEST['modname']))
{
	$modname = $_REQUEST['modname'];
	
	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		if(empty($_REQUEST['LO_save']) && (mb_strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
			$_SESSION['_REQUEST_vars'] = $_REQUEST;

		$_ROSARIO['is_popup'] = $_ROSARIO['not_ajax'] = false;

		//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
		if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($modname == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($modname, array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php', 'Scheduling/Courses.php')) && $_REQUEST['modfunc'] == 'choose_course')) //popups
		{
			$_ROSARIO['is_popup'] = true;
		}
		elseif (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') //AJAX check
		{
			$_ROSARIO['not_ajax'] = true;
		}
		
		if ($_ROSARIO['is_popup'] || $_ROSARIO['not_ajax'])
			Warehouse('header');
	}
	else
		ob_start();

	$allowed = false;

	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(mb_substr($_REQUEST['modname'],0,5)=='misc/')
	if (in_array($modname, array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/Portal.php', 'misc/ViewContact.php')))
		$allowed = true;
	else
	{
		include 'Menu.php';
		foreach($_ROSARIO['Menu'] as $modcat=>$programs)
		{
			foreach($programs as $program=>$title)
			{
	//modif Francois: fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
				if($modname==$program || (mb_strpos($program, $modname)=== 0 && mb_strpos($_SERVER['QUERY_STRING'], $program)=== 8))
				{
					$allowed = true;
					$_ROSARIO['Program_loaded'] = $program; //eg: "Student_Billing/Statements.php&_ROSARIO_PDF"
				}
			}
			if ($allowed)
				break;
		}
	}

	if($allowed)
	{
		if(Preferences('SEARCH')!='Y')
			$_REQUEST['search_modfunc'] = 'list';
		include('modules/'.$modname);
	}
	elseif(User('USERNAME'))
	{
		include('ProgramFunctions/HackingLog.fnc.php');
		HackingLog();
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
		Warehouse('footer');
}
?>
