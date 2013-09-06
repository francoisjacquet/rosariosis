<?php

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
	$notes_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','CONTENT'=>'_makeContentInput','SORT_ORDER'=>'_makeTextInput','FILE_ATTACHED'=>'_makeFileAttached','START_DATE'=>'_makePublishing'));

	$columns = array('TITLE'=>_('Title'),'CONTENT'=>_('Note'),'SORT_ORDER'=>_('Sort Order'),'FILE_ATTACHED'=>_('File Attached'),'START_DATE'=>_('Publishing Options'));
	//,'START_TIME'=>'Start Time','END_TIME'=>'End Time'
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'CONTENT'=>_makeContentInput('','CONTENT'),'SHORT_NAME'=>_makeTextInput('','SHORT_NAME'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'FILE_ATTACHED'=>_makeFileAttached('','FILE_ATTACHED'),'START_DATE'=>_makePublishing('','START_DATE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'ID');

	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST" enctype="multipart/form-data">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	
//modif Francois: Moodle integrator
	echo $moodleError;

	if (!empty($PortalNotesFilesError)) echo ErrorMessage(array($PortalNotesFilesError));
	//modif Francois: no responsive table
	$options = array('responsive' => false);
	ListOutput($notes_RET,$columns,'Note','Notes',$link,array(),$options);

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

	return TextareaInput($value,"values[$id][$name]",'','rows=5');
}

//modif Francois: file attached to portal notes
function _makeFileAttached($value,$name)
{	global $THIS_RET, $PortalNotesFilesPath;
	static $filesAttachedCount = 0;
	static $js_included = false;

	$loadColorBox = false;
	
	if($THIS_RET['ID'])
	{
		
		$id = $THIS_RET['ID'];
		if (empty($value))
		{
			$return = '&nbsp;';
		}			
		else
		{
			$filesAttachedCount ++;
			
			//modif Francois: colorbox
			//colorbox extensions list
			$colorbox_list = array('.jpg', '.jpeg', '.png', '.gif', '.mp3', '.wav', '.avi', '.mp4', '.ogg');
			if (in_array( mb_strtolower(mb_strrchr($value, '.')), $colorbox_list ) )
			{

				if (($finfo = finfo_open(FILEINFO_MIME_TYPE)) !== false)
				{
					//modif Francois: detects if an audio or video embedded in HTML5 will be rendered
					//https://developer.mozilla.org/en-US/docs/Media_formats_supported_by_the_audio_and_video_elements
					//Checked on 2012.11.16
					$browser = $_SERVER['HTTP_USER_AGENT'];
					$return = '<a href="'.$value.'" title="'.str_replace($PortalNotesFilesPath, '', $value).'"><img src="assets/download.png" class="alignImg" /> '._('Download').'</a>';
					if (in_array( mb_strtolower(mb_strrchr($value, '.')), array('.mp3', '.mp4') ) && (mb_stripos($browser, 'Firefox') || mb_stripos($browser, 'Opera'))) //MP3 or MP4 file & not supported in Firefox and Opera
					{
						return $return;
					}
					elseif (in_array( mb_strtolower(mb_strrchr($value, '.')), array('.ogg') ) && mb_stripos($browser, 'MSIE')) //OGG file & not supported in Internet Explorer
					{
						return $return;
					}
					elseif (in_array( mb_strtolower(mb_strrchr($value, '.')), array('.wav') ) && (mb_stripos($browser, 'MSIE') || mb_stripos($browser, 'Opera'))) //WAV file & not supported in Internet Explorer and Opera
					{
						return $return;
					}
					
					if (mb_strpos(finfo_file($finfo, $value), 'audio') !== false ) //media audio files
					{
						$return .= '<div><div style="display:none"><audio src="'.$value.'" preload="auto" controls class="audioHtml5" id="colorboxinline'.$filesAttachedCount.'"><p>Your browser does not support the audio element</p></audio></div>';
						$return .= '<a class="colorboxinline" href="#colorboxinline'.$filesAttachedCount.'" title="'.str_replace($PortalNotesFilesPath, '', $value).'"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a></div>';
						$loadColorBox = true;
					}
					elseif (mb_strpos(finfo_file($finfo, $value), 'video') !== false ) //media video files
					{
						$return .= '<div><div style="display:none"><video src="'.$value.'" preload="auto" controls class="videoHtml5" id="colorboxinline'.$filesAttachedCount.'"><p>Your browser does not support the audio element</p></video></div>';
						$return .= '<a class="colorboxinline" href="#colorboxinline'.$filesAttachedCount.'" title="'.str_replace($PortalNotesFilesPath, '', $value).'"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a></div>';
						$loadColorBox = true;
						
					} 
					else //image files
					{
						$return = '<a href="'.$value.'" class="colorbox" title="'.str_replace($PortalNotesFilesPath, '', $value).'"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a>';
						$loadColorBox = true;
					}
				}
			}
			elseif (filter_var($value, FILTER_VALIDATE_URL) !== false) //embed link
			{
				$return = '<a href="'.$value.'" title="'.$value.'" class="colorboxiframe"><img src="assets/visualize.png" class="alignImg" /> '._('View Online').'</a>';
				$loadColorBox = true;
			}
			else
			{
				$return = '<a href="'.$value.'" title="'.str_replace($PortalNotesFilesPath, '', $value).'"><img src="assets/download.png" class="alignImg" /> '._('Download').'</a>';
			}
		}
	}
	else
	{
		$id = 'new';
		
		$return = '<label><input type="radio" name="values[new][FILE_OR_EMBED]" value="FILE">&nbsp;<input type="file" id="'.$name.'_FILE" name="'.$name.'_FILE" size="14" /></label><br /><br />';
		$return .= '<label><input type="radio" name="values[new][FILE_OR_EMBED]" value="EMBED" onclick="javascript:document.getElementById(\'values[new]['.$name.'_EMBED]\').focus();">&nbsp;'._('Embed Link').': <input type="text" id="values[new]['.$name.'_EMBED]" name="values[new]['.$name.'_EMBED]" size="14"></label>';
	}
		
	if ($loadColorBox && !$js_included)
	{
		$return .= includeOnceJquery();
		$return .= '<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
		<script type="text/javascript" src="assets/js/colorbox/jquery.colorbox-min.js"></script>
		<script type="text/javascript">
			$(document).ready(function(){
				$(\'.colorbox\').colorbox();
				$(\'.colorboxinline\').colorbox({inline:true});
				$(\'.colorboxiframe\').colorbox({iframe:true, innerWidth:425, innerHeight:344});
			});
		</script>';
		
		$js_included = true;
	}
	return $return;
}

function _makePublishing($value,$name)
{	global $THIS_RET,$profiles_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

//modif Francois: remove LO_field
	$return = '<TABLE class="cellpadding-0 cellspacing-0"><TR class="st"><TD><b>'.Localize('colon',_('Visible Between')).'</b></TD><TD style="text-align:right">';
	$return .= DateInput($value,"values[$id][$name]").'</TD><TD> '._('to').' </TD><TD>';
	$return .= DateInput($THIS_RET['END_DATE'],"values[$id][END_DATE]").'</TD></TR>';
//modif Francois: css WPadmin
	$return .= '<TR><TD colspan="4" style="padding:0">';

	if(!$profiles_RET)
		$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID"));

	$return .= '<TABLE class="width-100p cellspacing-0 cellpadding-0"><TR><TD colspan="4"><b>'.Localize('colon',_('Visible To')).'</b></TD></TR><TR class="st">';
	foreach(array('admin'=>_('Administrator w/Custom'),'teacher'=>_('Teacher w/Custom'),'parent'=>_('Parent w/Custom')) as $profile_id=>$profile)
//modif Francois: add <label> on checkbox
		$return .= '<TD><label><INPUT type="checkbox" name="profiles[$id]['.$profile_id.']" value="Y"'.(mb_strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile_id,")!==false?' checked':'').'> '.$profile.'</label></TD>';
	$i = 3;
	foreach($profiles_RET as $profile)
	{
		$i++;
		$return .= '<TD><label><INPUT type="checkbox" name="profiles['.$id.']['.$profile['ID'].']" value="Y"'.(mb_strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile[ID],")!==false?' checked':'')."> "._($profile['TITLE'])."</label></TD>";
		if($i%4==0 && $i!=count($profile))
			$return .= '</TR><TR class="st">';
	}
	for(;$i%4!=0;$i++)
		$return .= '<TD>&nbsp;</TD>';
	$return .= '</TR>';
	
//modif Francois: Moodle integrator
	if (MOODLE_INTEGRATOR && $id == 'new')
		$return .= '<TR><TD colspan="4"><B>'._('Publish Note in Moodle?').'</B> <label><INPUT type="checkbox" name="MOODLE_PUBLISH_NOTE" value="Y" /> '._('Yes').'</label></TD></TR>';
		
	$return .= '</TABLE></TD></TR></TABLE>';
	return $return;
}
?>