<?php

// Modules configuration, included in Configuration.php

// Core modules (packaged with RosarioSIS):
// Core modules cannot be deleted
/* var defined in Warehouse.php
$RosarioCoreModules = array(
	'School_Setup',
	'Students',
	'Users',
	'Scheduling',
	'Grades',
	'Attendance',
	'Eligibility',
	'Discipline',
	'Accounting',
	'Student_Billing',
	'Food_Service',
	'State_Reports',
	'Resources',
	'Custom'
);*/

// Core modules that will generate errors if deactivated
$always_activated = array(
	'School_Setup'
);

$directories_bypass = array(
	'modules/misc'
);

//hacking protections
if (isset($_REQUEST['module']) && strpos($_REQUEST['module'], '..') !== false)
{
	include('ProgramFunctions/HackingLog.fnc.php');
	HackingLog();
}


if ( $_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if (DeletePrompt(_('Module')))
	{
		//verify if not in $always_activated & not in $RosarioCoreModules but in $RosarioModules
		if ( !in_array($_REQUEST['module'], $always_activated) && !in_array($_REQUEST['module'], $RosarioCoreModules) && in_array($_REQUEST['module'], array_keys($RosarioModules)) && $RosarioModules[$_REQUEST['module']] == false)
		{
			//delete module: execute delete.sql script
			if (file_exists('modules/'.$_REQUEST['module'].'/delete.sql'))
			{
				$delete_sql = file_get_contents('modules/'.$_REQUEST['module'].'/delete.sql');
				DBQuery($delete_sql);
			}

			//update $RosarioModules
			unset($RosarioModules[$_REQUEST['module']]);

			//save $RosarioModules
			_saveRosarioModules();

			if (is_dir('modules/'.$_REQUEST['module']))
			{
				//remove files & dir
				if ( !_delTree('modules/'.$_REQUEST['module']))
					$error[] = _('Files not eraseable.');
			}
		}
		
		unset($_REQUEST['modfunc']);
		unset($_REQUEST['module']);
	}
}

if ( $_REQUEST['modfunc']=='deactivate' && AllowEdit())
{
	if (DeletePrompt(_('Module'),_('Deactivate')))
	{
		//verify if not in $always_activated  & activated
		if ( !in_array($_REQUEST['module'], $always_activated) && in_array($_REQUEST['module'], array_keys($RosarioModules)) && $RosarioModules[$_REQUEST['module']] == true)
		{
			//update $RosarioModules
			$RosarioModules[$_REQUEST['module']] = false;
			
			//save $RosarioModules
			_saveRosarioModules();

			//reload menu
			_reloadMenu();
		}
		
		//verify module dir exists
		if ( !is_dir('modules/'.$_REQUEST['module']) || !file_exists('modules/'.$_REQUEST['module'].'/Menu.php'))
		{
			$error[] = _('Incomplete or inexistant module.');
		}

		unset($_REQUEST['modfunc']);
		unset($_REQUEST['module']);
	}
}

if ( $_REQUEST['modfunc']=='activate' && AllowEdit())
{
	$update_RosarioModules = false;
	
	//verify not already in $RosarioModules
	if ( !in_array($_REQUEST['module'], array_keys($RosarioModules)))
	{
		//verify directory exists
		if (is_dir('modules/'.$_REQUEST['module']) && file_exists('modules/'.$_REQUEST['module'].'/Menu.php'))
		{
			//install module: execute install.sql script
			if (file_exists('modules/'.$_REQUEST['module'].'/install.sql'))
			{
				$install_sql = file_get_contents('modules/'.$_REQUEST['module'].'/install.sql');
				DBQuery($install_sql);
			}
			
			$update_RosarioModules = true;
		}
		else
			$error[] = _('Incomplete or inexistant module.');
	}
	//verify in $RosarioModules
	elseif ( $RosarioModules[$_REQUEST['module']] == false && is_dir('modules/'.$_REQUEST['module']))
	{
		$update_RosarioModules = true;
	}
	//no module dir
	elseif ( !is_dir('modules/'.$_REQUEST['module']) || !file_exists('modules/'.$_REQUEST['module'].'/Menu.php'))
	{
		$error[] = _('Incomplete or inexistant module.');
	}

	if ( $update_RosarioModules)
	{
		//update $RosarioModules
		$RosarioModules[$_REQUEST['module']] = true;
		
		//save $RosarioModules
		_saveRosarioModules();

		//reload menu
		_reloadMenu();
	}
	
	unset($_REQUEST['modfunc']);
	unset($_REQUEST['module']);
}


if (empty($_REQUEST['modfunc']))
{
	
	if ( $error)
		echo ErrorMessage($error);

	$modules_RET = array('');
	foreach ( (array)$RosarioModules as $module_title => $activated)
	{
		$THIS_RET = array();
		$THIS_RET['DELETE'] = _makeDelete($module_title,$activated);
		$THIS_RET['TITLE'] = _makeReadMe($module_title,$activated);
		$THIS_RET['ACTIVATED'] = _makeActivated($activated);
		
		$modules_RET[] = $THIS_RET;

		$directories_bypass[] = 'modules/'.$module_title;
	}		
	
	// scan plugins/ folder for uninstalled plugins
	$modules = array_diff(glob('modules/*', GLOB_ONLYDIR), $directories_bypass);

	foreach ($modules as $module)
	{
		$module_title = str_replace('modules/', '', $module);

		$THIS_RET = array();
		$THIS_RET['DELETE'] = _makeDelete($module_title);
		$THIS_RET['TITLE'] = _makeReadMe($module_title);
		$THIS_RET['ACTIVATED'] = _makeActivated(false);

		$modules_RET[] = $THIS_RET;
	}

	$columns = array('DELETE' => '','TITLE' => _('Title'),'ACTIVATED' => _('Activated'));
	
	unset($modules_RET[0]);
	
	ListOutput($modules_RET,$columns,'Module','Modules');
}

function _makeActivated($activated)
{
	if ( $activated)
		$return = button('check');
	else
		$return = button('x');

	if (isset($_REQUEST['LO_save']))
	{
		if ( $activated)
			$return = _('Yes');
		else
			$return = _('No');
	}

	return $return;
}

function _makeDelete($module_title,$activated=null)
{	
	global $RosarioModules, $always_activated, $RosarioCoreModules;
	
	$return = '';
	if (AllowEdit())
	{
		if ( $activated)
		{
			if ( !in_array($module_title, $always_activated))
			{
				$return = button('remove',_('Deactivate'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=modules&modfunc=deactivate&module='.$module_title.'"');
			}
		}
		else
		{
			if (file_exists('modules/'.$module_title.'/Menu.php'))
				$return = button('add',_('Activate'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=modules&modfunc=activate&module='.$module_title.'"');
			else
				$return = '<span style="color:red">'.sprintf(_('%s file missing or wrong permissions.'),'Menu.php').'</span>';

			//if not core module & already installed, delete link
			if ( !in_array($module_title, $always_activated) && !in_array($module_title, $RosarioCoreModules) && in_array($module_title, array_keys($RosarioModules)))
				$return .= '&nbsp;'.button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=modules&modfunc=delete&module='.$module_title.'"');
		}
	}
	return $return;
}

function _makeReadMe($module_title,$activated=null)
{
	global $RosarioCoreModules;

	//format & translate module title
	if ( !in_array($module_title, $RosarioCoreModules) && $activated)
		$module_title_echo = dgettext($module_title, str_replace('_', ' ', $module_title));
	else
		$module_title_echo = _(str_replace('_', ' ', $module_title));

	//if README.md file, display in Colorbox
	if ( !isset($_REQUEST['_ROSARIO_PDF']) && file_exists('modules/'.$module_title.'/README.md'))
	{
		//get README.md content
		$readme_content = file_get_contents('modules/'.$module_title.'/README.md');
		
		// convert MarkDown text to HTML
		$readme_content = '<div class="markdown-to-html">' . $readme_content . '</div>';

		$return .= '<div style="display:none;"><div id="README_'.$module_title.'">'.$readme_content.'</div></div>';

		$return .= '<a class="colorboxinline" href="#README_'.$module_title.'">'.$module_title_echo.'</a>';
	}
	else
		$return = $module_title_echo;

	return $return;
}

function _saveRosarioModules()
{
	global $RosarioModules;

	$MODULES = DBEscapeString(serialize($RosarioModules));
	
	DBQuery("UPDATE config SET config_value='".$MODULES."' WHERE title='MODULES'");
	
	return true;
}

function _reloadMenu()
{
	?>
	<script>ajaxLink('Side.php');</script>
	<?php

	return true;
}

function _delTree($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		if (is_dir("$dir/$file"))
			delTree("$dir/$file");
		elseif (is_writable("$dir/$file"))
			unlink("$dir/$file");
		else
			return false;
	}
	return rmdir($dir);
}
