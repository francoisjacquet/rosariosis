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

//modif Francois: add TinyMCE to the textarea (see modules/Students/Letters.php & modules/Grades/HonorRollSubject.php & modules/Grades/HonorRoll.php)
if (($_REQUEST['modname']=='Students/Letters.php' && isset($_REQUEST['letter_text'])) || (($_REQUEST['modname']=='Grades/HonorRollSubject.php' || $_REQUEST['modname']=='Grades/HonorRoll.php') && isset($_REQUEST['honor_roll_text'])))
{
	$REQUEST_letter_text = $_REQUEST['letter_text'];
	$REQUEST_honor_roll_text = $_REQUEST['honor_roll_text'];
}
/*if ((!$_REQUEST['modname']=='Students/Letters.php' || !isset($_REQUEST['letter_text'])) && (!$_REQUEST['modname']=='Grades/HonorRollSubject.php' || !isset($_REQUEST['honor_roll_text'])) && (!$_REQUEST['modname']=='Grades/HonorRoll.php' || !isset($_REQUEST['honor_roll_text'])))
	if(!get_magic_quotes_gpc())
		array_rwalk($_REQUEST,'addslashes');*/

array_rwalk($_REQUEST,'strip_tags');

if(!isset($_REQUEST['_ROSARIO_PDF']))
{
	//Warehouse('header');

	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(mb_strpos($_REQUEST['modname'],'misc/')===false)
	/*if (!in_array($_REQUEST['modname'], array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')))
		echo '<script type="text/javascript">if(window == top  && (!window.opener || window.opener.location.href.substring(0,(window.opener.location.href.indexOf("&")!=-1?window.opener.location.href.indexOf("&"):window.opener.location.href.replace("#","").length))!=window.location.href.substring(0,(window.location.href.indexOf("&")!=-1?window.location.href.indexOf("&"):window.location.href.replace("#","").length)))) window.location.href = "index.php";</script>';*/
	if (in_array($_REQUEST['modname'], array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($_REQUEST['modname'] == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($_REQUEST['modname'], array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php')) && $_REQUEST['modfunc'] == 'choose_course')) //popups
	{
		Warehouse('header');
		echo '<script type="text/javascript">if(window == top  && (!window.opener)) window.location.href = "index.php";</script>';
		echo '<div id="body" tabindex="0" role="main" class="mod">';
	}
	elseif ($_REQUEST['modname'] !== 'misc/Portal.php' && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) //AJAX check
		HackingLog(); //no direct access		
}

if($_REQUEST['modname'])
{
	if(isset($_REQUEST['_ROSARIO_PDF']) && $_REQUEST['_ROSARIO_PDF']=='true')
		ob_start();
	//modif Francois: replaced ? with & in modname
	/*if(mb_strpos($_REQUEST['modname'],'?')!==false)
	{
		$modname = mb_substr($_REQUEST['modname'],0,mb_strpos($_REQUEST['modname'],'?'));
		$vars = mb_substr($_REQUEST['modname'],(mb_strpos($_REQUEST['modname'],'?')+1));

		$vars = explode('?',$vars);
		foreach($vars as $code)
		{
			$code = explode('=',$code);
			$_REQUEST[$code[0]] = $code[1];
		}
	}
	else*/
		$modname = $_REQUEST['modname'];

	if(empty($_REQUEST['LO_save']) && !isset($_REQUEST['_ROSARIO_PDF']) && (mb_strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
		$_SESSION['_REQUEST_vars'] = $_REQUEST;

	$allowed = false;
	include 'Menu.php';
	foreach($_ROSARIO['Menu'] as $modcat=>$programs)
	{
		if($_REQUEST['modname']==$modcat.'/Search.php')
		{
			$allowed = true;
			break;
		}
		foreach($programs as $program=>$title)
		{
//modif Francois: fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF
			if($_REQUEST['modname']==$program || (mb_strpos($program, $_REQUEST['modname'])=== 0 && mb_strpos($_SERVER['QUERY_STRING'], $program)=== 8))
			{
				$allowed = true;
				$program_loaded = $program;
				break;
			}
		}
	}
	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(mb_substr($_REQUEST['modname'],0,5)=='misc/')
	if (!$allowed && in_array($_REQUEST['modname'], array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/Portal.php', 'misc/ViewContact.php')))
		$allowed = true;

	if($allowed)
	{
		if(Preferences('SEARCH')!='Y')
			$_REQUEST['search_modfunc'] = 'list';
//modif Francois: remove languages/English/
//		include('languages/English/'.$modname);
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
}

if(!isset($_REQUEST['_ROSARIO_PDF']))
{
	Warehouse('footer');
	if (in_array($_REQUEST['modname'], array('misc/ChooseRequest.php', 'misc/ChooseCourse.php', 'misc/ViewContact.php')) || ($_REQUEST['modname'] == 'School_Setup/Calendar.php' && $_REQUEST['modfunc'] == 'detail') || (in_array($_REQUEST['modname'], array('Scheduling/MassDrops.php', 'Scheduling/Schedule.php', 'Scheduling/MassSchedule.php', 'Scheduling/MassRequests.php')) && $_REQUEST['modfunc'] == 'choose_course'))
	{
		echo '</div>';//#body
		Warehouse('footer_plain');
	}
}
?>