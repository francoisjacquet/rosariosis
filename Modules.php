<?php
error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ERROR);
include 'Warehouse.php';
//modif Francois: add TinyMCE to the textarea (see modules/Students/Letters.php & modules/Grades/HonorRollSubject.php & modules/Grades/HonorRoll.php)
if ((!$_REQUEST['modname']=='Students/Letters.php' || !isset($_REQUEST['letter_text'])) && (!$_REQUEST['modname']=='Grades/HonorRollSubject.php' || !isset($_REQUEST['honor_roll_text'])) && (!$_REQUEST['modname']=='Grades/HonorRoll.php' || !isset($_REQUEST['honor_roll_text'])))
{
	if(!get_magic_quotes_gpc())
		array_rwalk($_REQUEST,'addslashes');
	array_rwalk($_REQUEST,'strip_tags');
}

if(!isset($_REQUEST['_ROSARIO_PDF']))
{
	Warehouse('header');

	//if(strpos($_REQUEST['modname'],'misc/')===false && $_REQUEST['modname']!='Students/Student.php' && $_REQUEST['modname']!='School_Setup/Calendar.php' && $_REQUEST['modname']!='Scheduling/Schedule.php' && $_REQUEST['modname']!='Attendance/Percent.php' && $_REQUEST['modname']!='Attendance/Percent.php&list_by_day=true' && $_REQUEST['modname']!='Scheduling/MassRequests.php' && $_REQUEST['modname']!='Scheduling/MassSchedule.php' && $_REQUEST['modname']!='Student_Billing/Fees.php')
	//if(strpos($_REQUEST['modname'],'misc/')===false)
		echo '<script type="text/javascript">if(window == top  && (!window.opener || window.opener.location.href.substring(0,(window.opener.location.href.indexOf("&")!=-1?window.opener.location.href.indexOf("&"):window.opener.location.href.replace("#","").length))!=window.location.href.substring(0,(window.location.href.indexOf("&")!=-1?window.location.href.indexOf("&"):window.location.href.replace("#","").length)))) window.location.href = "index.php";</script>';
	echo '</HEAD><BODY id="modulesBody">';
	echo '<DIV id="Migoicons" style="visibility:hidden;position:absolute;z-index:1000;top:-100px"></DIV>';
}

if($_REQUEST['modname'])
{
	if(isset($_REQUEST['_ROSARIO_PDF']) && $_REQUEST['_ROSARIO_PDF']=='true')
		ob_start();
	//modif Francois: replaced ? with & in modname
	/*if(strpos($_REQUEST['modname'],'?')!==false)
	{
		$modname = substr($_REQUEST['modname'],0,strpos($_REQUEST['modname'],'?'));
		$vars = substr($_REQUEST['modname'],(strpos($_REQUEST['modname'],'?')+1));

		$vars = explode('?',$vars);
		foreach($vars as $code)
		{
			$code = explode('=',$code);
			$_REQUEST[$code[0]] = $code[1];
		}
	}
	else*/
		$modname = $_REQUEST['modname'];

//	if(!$_REQUEST['LO_save'] && !isset($_REQUEST['_ROSARIO_PDF']) && (strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
	if(empty($_REQUEST['LO_save']) && !isset($_REQUEST['_ROSARIO_PDF']) && (strpos($modname,'misc/')===false || $modname=='misc/Registration.php' || $modname=='misc/Export.php' || $modname=='misc/Portal.php'))
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
			if (strpos($program, '&') !== false)
				$program = substr($program, 0, strpos($program, '&'));
			if($_REQUEST['modname']==$program)
			{
				$allowed = true;
				break;
			}
		}
	}
	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//allow PHP scripts in misc/ one by one in place of the whole folder
	//if(substr($_REQUEST['modname'],0,5)=='misc/')
	if (!$allowed && in_array($_REQUEST['modname'], array('misc/ChooseCourse.php', 'misc/ChooseCourse.php', 'misc/Portal.php', 'misc/ViewContact.php')))
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
}
?>