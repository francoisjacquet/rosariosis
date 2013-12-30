<?php
include('ProgramFunctions/PortalPollsNotes.fnc.php');

if($_REQUEST['day_values'] && $_POST['day_values'])
{
	foreach($_REQUEST['day_values'] as $id=>$values)
	{
		if($_REQUEST['day_values'][$id]['START_DATE'] && $_REQUEST['month_values'][$id]['START_DATE'] && $_REQUEST['year_values'][$id]['START_DATE'])
			$_REQUEST['values'][$id]['START_DATE'] = $_REQUEST['day_values'][$id]['START_DATE'].'-'.$_REQUEST['month_values'][$id]['START_DATE'].'-'.$_REQUEST['year_values'][$id]['START_DATE'];
		elseif(isset($_REQUEST['day_values'][$id]['START_DATE']) && isset($_REQUEST['month_values'][$id]['START_DATE']) && isset($_REQUEST['year_values'][$id]['START_DATE']))
			$_REQUEST['values'][$id]['START_DATE'] = '';

		if($_REQUEST['day_values'][$id]['END_DATE'] && $_REQUEST['month_values'][$id]['END_DATE'] && $_REQUEST['year_values'][$id]['END_DATE'])
			$_REQUEST['values'][$id]['END_DATE'] = $_REQUEST['day_values'][$id]['END_DATE'].'-'.$_REQUEST['month_values'][$id]['END_DATE'].'-'.$_REQUEST['year_values'][$id]['END_DATE'];
		elseif(isset($_REQUEST['day_values'][$id]['END_DATE']) && isset($_REQUEST['month_values'][$id]['END_DATE']) && isset($_REQUEST['year_values'][$id]['END_DATE']))
			$_REQUEST['values'][$id]['END_DATE'] = '';
	}
	if(!$_POST['values'])
		$_POST['values'] = $_REQUEST['values'];
}

$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID"));
if((($_REQUEST['profiles'] && $_POST['profiles']) || ($_REQUEST['values'] && $_POST['values'])) && AllowEdit())
{
	$notes_RET = DBGet(DBQuery("SELECT ID FROM PORTAL_NOTES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

	foreach($notes_RET as $note_id)
	{
		$note_id = $note_id['ID'];
		$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] = '';
		foreach(array('admin','teacher','parent') as $profile_id)
			if($_REQUEST['profiles'][$note_id][$profile_id])
				$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
		if(count($_REQUEST['profiles'][$note_id]))
		{
			foreach($profiles_RET as $profile)
			{
				$profile_id = $profile['ID'];

				if($_REQUEST['profiles'][$note_id][$profile_id])
					$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
			}
		}
		if($_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'])
			$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ',';
	}
}

if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	//modif Francois: file attached to portal notes
	include('modules/School_Setup/includes/PortalNotesFiles.inc.php');
	
	foreach($_REQUEST['values'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if($id!='new')
			{
				$sql = "UPDATE PORTAL_NOTES SET ";

				foreach($columns as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
				DBQuery($sql);
//modif Francois: Moodle integrator
				if (isset($columns['TITLE']) || isset($columns['CONTENT'])) //update note if title or content modified
					$moodleError = Moodle($_REQUEST['modname'], 'core_notes_update_notes');
			}
			else
			{
				if(count($_REQUEST['profiles']['new']))
				{
					foreach(array('admin','teacher','parent') as $profile_id)
					{
						if($_REQUEST['profiles']['new'][$profile_id])
							$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id.',';
						$columns['PUBLISHED_PROFILES'] = ','.$_REQUEST['values']['new']['PUBLISHED_PROFILES'];
					}
					foreach($profiles_RET as $profile)
					{
						$profile_id = $profile['ID'];

						if($_REQUEST['profiles']['new'][$profile_id])
							$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id.',';
						$columns['PUBLISHED_PROFILES'] = ','.$_REQUEST['values']['new']['PUBLISHED_PROFILES'];
					}
				}
				else
					$_REQUEST['values']['new']['PUBLISHED_PROFILES'] = '';

				$sql = "INSERT INTO PORTAL_NOTES ";

				//modif Francois: file attached to portal notes
				$fields = 'ID,SCHOOL_ID,SYEAR,PUBLISHED_DATE,PUBLISHED_USER,';
//modif Francois: Moodle integrator
				$portal_note_RET = DBGet(DBQuery("SELECT ".db_seq_nextval('PORTAL_NOTES_SEQ').' AS PORTAL_NOTE_ID '.FROM_DUAL));
				$portal_note_id = $portal_note_RET[1]['PORTAL_NOTE_ID'];
				//$values = db_seq_nextval('PORTAL_NOTES_SEQ').",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";
				$values = $portal_note_id.",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";

				$PortalNotesFilesError = '';
				if ($columns['FILE_OR_EMBED'] == 'FILE')
					$columns['FILE_ATTACHED'] = PortalNotesFiles($_FILES['FILE_ATTACHED_FILE'], $PortalNotesFilesError);
				elseif ($columns['FILE_OR_EMBED'] == 'EMBED')
					if (filter_var($columns['FILE_ATTACHED_EMBED'], FILTER_VALIDATE_URL) !== false)
						$columns['FILE_ATTACHED'] = $columns['FILE_ATTACHED_EMBED'];
						
				unset($columns['FILE_ATTACHED_EMBED'], $columns['FILE_OR_EMBED']);
				
				$go = 0;
				foreach($columns as $column=>$value)
				{
					if($value)
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if($go && empty($PortalNotesFilesError))
				{
					DBQuery($sql);
					
//modif Francois: Moodle integrator
					if ($_REQUEST['MOODLE_PUBLISH_NOTE'])
						$moodleError = Moodle($_REQUEST['modname'], 'core_notes_create_notes');
				}
			}
		}
		else
			$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
	}
	unset($_REQUEST['values']);
	unset($_SESSION['_REQUEST_vars']['values']);
	unset($_REQUEST['profiles']);
	unset($_SESSION['_REQUEST_vars']['profiles']);
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('Note')))
	{
//modif Francois: file attached to portal notes
		$file_to_remove = DBGet(DBQuery("SELECT FILE_ATTACHED FROM PORTAL_NOTES WHERE ID='$_REQUEST[id]'"));
		@unlink($file_to_remove[1]['FILE_ATTACHED']);
		DBQuery("DELETE FROM PORTAL_NOTES WHERE ID='$_REQUEST[id]'");

//modif Francois: Moodle integrator
		if (MOODLE_INTEGRATOR)
			$moodleError = Moodle($_REQUEST['modname'], 'core_notes_delete_notes');
			
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
//modif Francois: file attached to portal notes
	$sql = "SELECT ID,SORT_ORDER,TITLE,CONTENT,START_DATE,END_DATE,PUBLISHED_PROFILES,FILE_ATTACHED,CASE WHEN END_DATE IS NOT NULL AND END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED FROM PORTAL_NOTES WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY EXPIRED DESC,SORT_ORDER,PUBLISHED_DATE DESC";
	$QI = DBQuery($sql);
	$notes_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','CONTENT'=>'_makeContentInput','SORT_ORDER'=>'_makeTextInput','FILE_ATTACHED'=>'makeFileAttached','START_DATE'=>'makePublishing'));

	$columns = array('TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SORT_ORDER'=>_('Sort Order'),'FILE_ATTACHED'=>_('File Attached'),'START_DATE'=>_('Publishing Options'));
	//,'START_TIME'=>'Start Time','END_TIME'=>'End Time'
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'CONTENT'=>_makeContentInput('','CONTENT'),'SHORT_NAME'=>_makeTextInput('','SHORT_NAME'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'FILE_ATTACHED'=>makeFileAttached('','FILE_ATTACHED'),'START_DATE'=>makePublishing('','START_DATE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'ID');

	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST" enctype="multipart/form-data">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	
//modif Francois: Moodle integrator
	echo $moodleError;

	if (!empty($PortalNotesFilesError)) echo ErrorMessage(array($PortalNotesFilesError));
	
	ListOutput($notes_RET,$columns,'Note','Notes',$link);

	echo '<BR /><span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name!='TITLE')
		$extra = 'size=5 maxlength=10';
//modif Francois: title field required
	if($name=='TITLE' && $id != 'new')
		$extra = 'required';

	return TextInput($name=='TITLE' && $THIS_RET['EXPIRED']?array($value,'<span style="color:red">'.$value.'</span>'):$value,"values[$id][$name]",'',$extra);
}

function _makeContentInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$return = includeOnceColorBox('divNoteContent'.$id);
	$return .= '<DIV id="divNoteContent'.$id.'" class="rt2colorBox">'.TextareaInput($value,"values[$id][$name]",'','rows=5').'</DIV>';
	
	return $return;
}
?>