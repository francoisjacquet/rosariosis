<?php

/***************************************************************
 Setup.php file
 Optional
 - Modify the Config values present in the program_config table
****************************************************************/

DrawHeader(ProgramTitle()); //display main header with Module icon and Program title

if($_REQUEST['modfunc']=='update' && $_REQUEST['values'] && $_POST['values'] && AllowEdit()) //AllowEdit must be verified before inserting, updating, deleting data
{

	//verify value is numeric
	if (empty($_REQUEST['values']['EXAMPLE_CONFIG']) || is_numeric($_REQUEST['values']['EXAMPLE_CONFIG']))
	{
		$sql = '';
		foreach($_REQUEST['values'] as $column=>$value)
		{
			$sql .= "UPDATE PROGRAM_CONFIG SET ";
			$sql .= "VALUE='".$value."' WHERE TITLE='".$column."'";
			$sql .= " AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."';";
		}
		
		DBQuery($sql);
		$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'.dgettext('Example', 'The configuration value has been modified.');
	}
	else
	{
		$error[] = _('Please enter valid Numeric data.');
	}

	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if(empty($_REQUEST['modfunc'])) //display Setup value
{
	//display note if any
	if (!empty($note))
		echo ErrorMessage($note, 'note');
	//display errors if any
	if (!empty($error))
		echo ErrorMessage($error, 'error');
	
	//form used to send the updated Config to be processed by the same script (see at the top)
	echo '<FORM ACTION="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" METHOD="POST">';

	//display secondary header with Save button (aligned right)
	DrawHeader('',SubmitButton(_('Save'))); //SubmitButton is diplayed only if AllowEdit
	
	echo '<BR />';
	
	//encapsulate content in PopTable
	PopTable('header',dgettext('Example', 'Example module Setup'));

	//get the program config options
	$program_config = DBGet(DBQuery("SELECT TITLE, VALUE FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND program='example'"),array(),array('TITLE')); //the returned array will be indexed by TITLE field

	//display the program config options
	echo '<FIELDSET><legend><b>'.dgettext('Example', 'Example').'</b></legend><TABLE>';
	echo '<TR style="text-align:left;"><TD>'.TextInput($program_config['EXAMPLE_CONFIG'][1]['VALUE'],'values[EXAMPLE_CONFIG]','<span class="legend-gray" title="'.dgettext('Example', 'Try to enter a non-numeric value').'">'.dgettext('Example', 'Example config value label').' *</span>','maxlength=2 size=2 min=0').'</TD></TR>';
	echo '</TABLE></FIELDSET>';

	//close PopTable
	PopTable('footer');

	echo '<span class="center">'.SubmitButton(_('Save')).'</span>'; //SubmitButton is diplayed only if AllowEdit
	echo '</FORM>';

}
?>
