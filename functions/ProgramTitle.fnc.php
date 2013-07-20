<?php

function ProgramTitle($modname='')
{	global $_ROSARIO;

	if(!$modname)
		$modname = $_REQUEST['modname'];
	if(!$_ROSARIO['Menu'])
	{
		global $RosarioModules;
		include 'Menu.php';
	}
	foreach($_ROSARIO['Menu'] as $modcat=>$programs)
	{
		if(count($programs))
		{
			foreach($programs as $program=>$title)
			{
				if($modname==$program)
				{
					if($_ROSARIO['HeaderIcon']!==false)
						if(mb_substr($modname,0,25)=='Users/TeacherPrograms.php')
//							$_ROSARIO['HeaderIcon'] = mb_substr($modname,34,mb_strpos($modname,'/',34)-34).'.gif';
							$_ROSARIO['HeaderIcon'] = mb_substr($modname,34,mb_strpos($modname,'/',34)-34).'.png'; //modif Francois: icons
						else
//							$_ROSARIO['HeaderIcon'] = $modcat.'.gif';
							$_ROSARIO['HeaderIcon'] = $modcat.'.png'; //modif Francois: icones
					return $title;
				}
			}
		}
	}
	if($_ROSARIO['HeaderIcon']!==false)
		unset($_ROSARIO['HeaderIcon']);
	return 'RosarioSIS';
}
?>