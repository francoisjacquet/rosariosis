<?php

// Plugins configuration, included in Configuration.php

// Core plugins (packaged with RosarioSIS):
// Core plugins cannot be deleted
/* var defined in Warehouse.php
$RosarioCorePlugins = array(
	'Moodle'
);*/

$directories_bypass = array(
	'.',
	'..'
);

//hacking protections
if(isset($_REQUEST['plugin']) && strpos($_REQUEST['plugin'], '..') !== false)
{
	include('ProgramFunctions/HackingLog.fnc.php');
	HackingLog();
}


if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(DeletePrompt(_('Plugin')))
	{
		//verify if not in $RosarioCorePlugins but in $RosarioPlugins
		if (!in_array($_REQUEST['plugin'], $RosarioCorePlugins) && in_array($_REQUEST['plugin'], array_keys($RosarioPlugins)) && $RosarioPlugins[$_REQUEST['plugin']] == false)
		{
			//delete plugin: execute delete.sql script
			if (file_exists('plugins/'.$_REQUEST['plugin'].'/delete.sql'))
			{
				$delete_sql = file_get_contents('plugins/'.$_REQUEST['plugin'].'/delete.sql');
				DBQuery($delete_sql);
			}

			//update $RosarioPlugins
			unset($RosarioPlugins[$_REQUEST['plugin']]);

			//save $RosarioPlugins
			_saveRosarioPlugins();

			if (is_dir('plugins/'.$_REQUEST['plugin']))
			{
				//remove files & dir
				if (!_delTree('plugins/'.$_REQUEST['plugin']))
					$error[] = _('Files not eraseable.');
			}
		}
		
		unset($_REQUEST['modfunc']);
		unset($_REQUEST['plugin']);
	}
}

if($_REQUEST['modfunc']=='deactivate' && AllowEdit())
{
	if(DeletePrompt(_('Plugin'),_('Deactivate')))
	{
		//verify if activated
		if (in_array($_REQUEST['plugin'], array_keys($RosarioPlugins)) && $RosarioPlugins[$_REQUEST['plugin']] == true)
		{
			//update $RosarioPlugins
			$RosarioPlugins[$_REQUEST['plugin']] = false;
			
			//save $RosarioPlugins
			_saveRosarioPlugins();
		}
		
		//verify plugin dir exists
		if (!is_dir('plugins/'.$_REQUEST['plugin']) || !file_exists('plugins/'.$_REQUEST['plugin'].'/functions.php'))
		{
			$error[] = _('Incomplete or inexistant plugin.');
		}

		unset($_REQUEST['modfunc']);
		unset($_REQUEST['plugin']);
	}
}

if($_REQUEST['modfunc']=='activate' && AllowEdit())
{
	$update_RosarioPlugins = false;
	
	//verify not already in $RosarioPlugins
	if (!in_array($_REQUEST['plugin'], array_keys($RosarioPlugins)))
	{
		//verify directory exists
		if (is_dir('plugins/'.$_REQUEST['plugin']) && file_exists('plugins/'.$_REQUEST['plugin'].'/functions.php'))
		{
			//install plugin: execute install.sql script
			if (file_exists('plugins/'.$_REQUEST['plugin'].'/install.sql'))
			{
				$install_sql = file_get_contents('plugins/'.$_REQUEST['plugin'].'/install.sql');
				DBQuery($install_sql);
			}
			
			$update_RosarioPlugins = true;
		}
		else
			$error[] = _('Incomplete or inexistant plugin.');
	}
	//verify in $RosarioPlugins
	elseif ($RosarioPlugins[$_REQUEST['plugin']] == false && is_dir('plugins/'.$_REQUEST['plugin']))
	{
		$update_RosarioPlugins = true;
	}
	//no plugin dir
	elseif (!is_dir('plugins/'.$_REQUEST['plugin']) || !file_exists('plugins/'.$_REQUEST['plugin'].'/functions.php'))
	{
		$error[] = _('Incomplete or inexistant plugin.');
	}

	if ($update_RosarioPlugins)
	{
		//update $RosarioPlugins
		$RosarioPlugins[$_REQUEST['plugin']] = true;
		
		//save $RosarioPlugins
		_saveRosarioPlugins();
	}
	
	unset($_REQUEST['modfunc']);
	unset($_REQUEST['plugin']);
}


if(empty($_REQUEST['modfunc']))
{
	
	if ($error)
		echo ErrorMessage($error);

	$plugins_RET = array('');
	foreach($RosarioPlugins as $plugin_title => $activated)
	{
		$THIS_RET = array();
		$THIS_RET['DELETE'] =  _makeDelete($plugin_title,$activated);
		$THIS_RET['TITLE'] = _(str_replace('_', ' ', $plugin_title));
		$THIS_RET['ACTIVATED'] = _makeActivated($activated);
		
		$plugins_RET[] = $THIS_RET;
	}		
	
	// scan plugins/ folder for uninstalled plugins
	$directories_bypass_complete = array_merge($directories_bypass, array_keys($RosarioPlugins));

	$plugins = scandir('plugins/');
	foreach ($plugins as $plugin_title)
	{
		//filter directories
		if (!in_array($plugin_title, $directories_bypass_complete) && is_dir('plugins/'.$plugin_title))
		{
			$THIS_RET = array();
			$THIS_RET['DELETE'] =  _makeDelete($plugin_title);
			$THIS_RET['TITLE'] = str_replace('_', ' ', $plugin_title);
			$THIS_RET['ACTIVATED'] = _makeActivated(false);
		
			$plugins_RET[] = $THIS_RET;
		}
	}

	$columns = array('DELETE'=>'','TITLE'=>_('Title'),'ACTIVATED'=>_('Activated'));
	
	unset($plugins_RET[0]);
	
	ListOutput($plugins_RET,$columns,'Plugin','Plugins');
}

function _makeActivated($activated)
{	global $THIS_RET;
	
	if ($activated)
		$return = '<img src="assets/check_button.png" height="16" />';
	else
		$return = '<img src="assets/x_button.png" height="16" />';

	return $return;
}

function _makeDelete($plugin_title,$activated=null)
{	
	global $RosarioPlugins, $RosarioCorePlugins;
	
	$return = '';
	if (AllowEdit())
	{
		if ($activated)
		{
			$return = button('remove',_('Deactivate'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=deactivate&plugin='.$plugin_title.'"');
		}
		else
		{
			if (file_exists('plugins/'.$plugin_title.'/functions.php'))
				$return = button('add',_('Activate'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=activate&plugin='.$plugin_title.'"');
			else
				$return = '<span style="color:red">'.sprintf(_('%s file missing or wrong permissions.'),'functions.php').'</span>';

			//if not core plugin & already installed, delete link
			if (!in_array($plugin_title, $RosarioCorePlugins) && in_array($plugin_title, array_keys($RosarioPlugins)))
				$return .= '&nbsp;'.button('remove',_('Delete'),'"Modules.php?modname='.$_REQUEST['modname'].'&tab=plugins&modfunc=delete&plugin='.$plugin_title.'"');
		}
	}
	return $return;
}

function _saveRosarioPlugins()
{
	global $RosarioPlugins;

	$MODULES = DBEscapeString(serialize($RosarioPlugins));
	
	DBQuery("UPDATE config SET config_value='".$MODULES."' WHERE title='MODULES'");
	
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
?>
