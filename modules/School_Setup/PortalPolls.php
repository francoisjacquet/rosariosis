<?php
//modif Francois: Portal Polls inspired by Portal Notes

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
	$polls_RET = DBGet(DBQuery("SELECT ID FROM PORTAL_POLLS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

	foreach($polls_RET as $poll_id)
	{
		$poll_id = $poll_id['ID'];
		$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] = '';
		foreach(array('admin','teacher','parent') as $profile_id)
			if($_REQUEST['profiles'][$poll_id][$profile_id])
				$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
		if(count($_REQUEST['profiles'][$poll_id]))
		{
			foreach($profiles_RET as $profile)
			{
				$profile_id = $profile['ID'];

				if($_REQUEST['profiles'][$poll_id][$profile_id])
					$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ','.$profile_id;
			}
		}
		if($_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'])
			$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ',';
	}
}

if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	
	foreach($_REQUEST['values'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if($id!='new')
			{
				$sql = "UPDATE PORTAL_POLLS SET ";
				$sql_question = "UPDATE PORTAL_POLL_QUESTIONS SET ";

				$sql_questions = array();
				$id_questions = array();
				foreach($columns as $column=>$value)
				{
					if (is_array($value))
					{
						$id_questions[] = $column;
						$sql_question_cols = '';
						foreach($value as $col=>$val)
						{
							$sql_question_cols .= $col."='".$val."',";
						}
						$sql_questions[] = $sql_question.$sql_question_cols;
					}
					else
						$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
				DBQuery($sql);
				
				$q = 0;
				foreach($sql_questions as $sql_question)
				{
					$sql_question = mb_substr($sql_question,0,-1) . " WHERE ID='$id_questions[$q]'";
					DBQuery($sql_question);
					$q++;
				}
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

				$sql = "INSERT INTO PORTAL_POLLS ";
				$sql_question = "INSERT INTO PORTAL_POLL_QUESTIONS ";
				$fields = 'ID,SCHOOL_ID,SYEAR,PUBLISHED_DATE,PUBLISHED_USER,';
				$portal_poll_RET = DBGet(DBQuery("SELECT ".db_seq_nextval('PORTAL_POLLS_SEQ').' AS PORTAL_POLL_ID '.FROM_DUAL));
				$portal_poll_id = $portal_poll_RET[1]['PORTAL_POLL_ID'];
				//$values = db_seq_nextval('PORTAL_POLLS_SEQ').",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";
				$values = $portal_poll_id.",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";
				
				$go = 0;
				$sql_questions = array();
				foreach($columns as $column=>$value)
				{
					if($value)
					{
						if (mb_strpos($column, 'new') !== false)
						{
							$go_question = 0;
							$fields_question = 'ID,PORTAL_POLL_ID,';
							$portal_poll_question_RET = DBGet(DBQuery("SELECT ".db_seq_nextval('PORTAL_POLL_QUESTIONS_SEQ').' AS PORTAL_POLL_QUESTION_ID '.FROM_DUAL));
							$portal_poll_question_id = $portal_poll_question_RET[1]['PORTAL_POLL_QUESTION_ID'];
							$values_question = $portal_poll_question_id.",".$portal_poll_id.",";
							foreach($value as $col=>$val)
							{
								if ($val)
								{
									$fields_question .= $col.',';
									$values_question .= "'".$val."',";
									$go_question = true;
								}
							}
							if ($go_question)
								$sql_questions[] = $sql_question.'(' . mb_substr($fields_question,0,-1) . ') values(' . mb_substr($values_question,0,-1) . ')';
						}
						else
						{
							$fields .= $column.',';
							$values .= "'".$value."',";
							$go = true;
						}
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if($go)
				{
					DBQuery($sql);
					foreach($sql_questions as $sql_question)
						DBQuery($sql_question);
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
	if(DeletePrompt(_('Poll')))
	{
		DBQuery("DELETE FROM PORTAL_POLLS WHERE ID='$_REQUEST[id]'");
		DBQuery("DELETE FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql_questions = "SELECT ppq.ID,ppq.PORTAL_POLL_ID,ppq.OPTIONS,ppq.VOTES,ppq.QUESTION,ppq.TYPE FROM PORTAL_POLL_QUESTIONS ppq, PORTAL_POLLS pp WHERE pp.SCHOOL_ID='".UserSchool()."' AND pp.SYEAR='".UserSyear()."' AND pp.ID=ppq.PORTAL_POLL_ID ORDER BY ppq.ID";
	$QI_questions = DBQuery($sql_questions);
	$questions_RET = DBGet($QI_questions,array('OPTIONS'=>'_makeOptionsInput'));	

	$sql = "SELECT pp.ID,pp.SORT_ORDER,pp.TITLE,'See_PORTAL_POLL_QUESTIONS' AS OPTIONS, pp.VOTES_NUMBER,pp.START_DATE,pp.END_DATE,pp.PUBLISHED_PROFILES,pp.STUDENTS_TEACHER_ID,CASE WHEN pp.END_DATE IS NOT NULL AND pp.END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED FROM PORTAL_POLLS pp WHERE pp.SCHOOL_ID='".UserSchool()."' AND pp.SYEAR='".UserSyear()."' ORDER BY EXPIRED DESC,pp.SORT_ORDER,pp.PUBLISHED_DATE DESC";
	$QI = DBQuery($sql);
	$polls_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','OPTIONS'=>'_makeOptionsInputs','VOTES_NUMBER'=>'_makePollVotes','SORT_ORDER'=>'_makeTextInput','START_DATE'=>'_makePublishing'));
	
	$columns = array('TITLE'=>_('Title'),'OPTIONS'=>_('Poll'),'VOTES_NUMBER'=>_('Results'),'SORT_ORDER'=>_('Sort Order'),'START_DATE'=>_('Publishing Options'));
	//,'START_TIME'=>'Start Time','END_TIME'=>'End Time'
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'OPTIONS'=>_makeOptionsInputs('','OPTIONS'),'VOTES_NUMBER'=>_makePollVotes('','VOTES_NUMBER'),'SHORT_NAME'=>_makeTextInput('','SHORT_NAME'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'START_DATE'=>_makePublishing('','START_DATE'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
	$link['remove']['variables'] = array('id'=>'ID');

	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	
	//modif Francois: no responsive table
	$options = array('responsive' => false);
	ListOutput($polls_RET,$columns,'Poll','Polls',$link,array(),$options);

	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
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

function _makeOptionsInput($value,$name)
{	global $THIS_RET,$portal_poll_id;
	static $OptionNb = 0;
	
	if($THIS_RET['ID'])
	{
		$id = $THIS_RET['ID'];
		$portal_poll_id = $THIS_RET['PORTAL_POLL_ID'];
	}
	else
	{
		$portal_poll_id = 'new';
		$id = 'new'.$OptionNb;
	}
	if ($portal_poll_id == $old_portal_poll_id)
		$OptionNb++;
	$old_portal_poll_id = $portal_poll_id;
	
	$type_options = array('multiple_radio'=>_('Select One from Options'),'multiple'=>_('Select Multiple from Options'));
	
	return '<TR'.($portal_poll_id == 'new' ? ' id="newOption_0"' : '').'><TD>'.TextInput($THIS_RET['QUESTION'],"values[$portal_poll_id][$id][QUESTION]",'','maxlength=255 size=20').'</TD><TD>'.TextareaInput($value,"values[$portal_poll_id][$id][$name]",'','rows=3 cols=20').($portal_poll_id == 'new' ? '<BR />'._('* one per line') : '').'</TD><TD>'.SelectInput($THIS_RET['TYPE'],"values[$portal_poll_id][$id][TYPE]",'',$type_options,false).'</TD></TR>';
}

function _makeOptionsInputs($value,$name)
{	global $THIS_RET,$questions_RET;
	static $js_included = false;

	$value = '';
	if($THIS_RET['ID'])
	{
		$id = $THIS_RET['ID'];
		foreach ($questions_RET as $question)
		{
			if ($question['PORTAL_POLL_ID'] == $id)
				$value .= $question['OPTIONS'];
		}
	}
	else
	{
		$id = 'new';
		$value = _makeOptionsInput('','OPTIONS');
	}

	if ($id == 'new')
	{
		$return = '<script type="text/javascript">
			function newOption()
			{
				var table = document.getElementById(\'newOptionsTable\');
				var nbOptions = (table.rows.length - 3);
				row = table.insertRow(2+nbOptions);
				// insert table cells to the new row
				var tr = document.getElementById(\'newOption_\'+nbOptions);
				row.setAttribute(\'id\', \'newOption_\'+(nbOptions+1));
				for (i = 0; i < 3; i++) {
					createCell(row.insertCell(i), tr, i, nbOptions+1);
				}
			}
			// fill the cells
			function createCell(cell, tr, i, newId) {
				cell.innerHTML = tr.cells[i].innerHTML;
				reg = new RegExp(\'new\' + (newId-1),\'g\'); //g for global string
				cell.innerHTML = cell.innerHTML.replace(reg, \'new\'+newId);
			}
		</script>';
		$return .= '<DIV style="max-height: 350px; overflow-y: auto;"><TABLE class="cellspacing-0" id="newOptionsTable"><TR><TD><b>'._('Question').'</b></TD><TD><b>'._('Options').'</b></TD><TD><b>'._('Data Type').'</b></TD></TR>'.$value.'<TR><TD>&nbsp;</TD><TD>&nbsp;</TD><TD><a href="#" onclick="newOption();return false;"><img src="assets/add_button.gif" width="18" style="vetical-align:middle" /> '._('New Question').'</a></TR></TABLE></DIV>';
	}
	else
	{
		$return = '<DIV style="max-height: 350px; overflow-y: auto;"><TABLE class="cellspacing-0"><TR><TD><b>'._('Question').'</b></TD><TD><b>'._('Options').'</b></TD><TD><b>'._('Data Type').'</b></TD></TR>'.$value.'</TABLE></DIV>';
	}
		
	return $return;
}

function _makeQuestionVotes($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'] && $THIS_RET[$name])
	{
		$poll_id = $THIS_RET['PORTAL_POLL_ID'];
	}
	else
		return '&nbsp;'; //new poll

}

function _makePollVotes($value,$name)
{	global $THIS_RET,$questions_RET;

	if($THIS_RET['ID'])
	{
		$poll_id = $THIS_RET['ID'];
		$poll_questions_RET = DBGet(DBQuery("SELECT QUESTION, VOTES, OPTIONS FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='".$poll_id."'"));
		$votes_display_RET = DBGet(DBQuery("SELECT DISPLAY_VOTES FROM PORTAL_POLLS WHERE ID='".$poll_id."'"));
		if (empty($value))
			return CheckboxInput($votes_display_RET[1]['DISPLAY_VOTES'],"values[".$THIS_RET['ID']."][DISPLAY_VOTES]",_('Results Display'));
			
		include_once('ProgramFunctions/PortalPolls.fnc.php');
		return CheckboxInput($votes_display_RET[1]['DISPLAY_VOTES'],"values[".$poll_id."][DISPLAY_VOTES]",_('Results Display')).PortalPollsVotesDisplay($poll_id, true, $poll_questions_RET,$value);
	}
	else
		return CheckboxInput('',"values[new][DISPLAY_VOTES]",_('Results Display'),'',true);

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
		$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID WHERE"));

	$return .= '<TABLE class="width-100p cellspacing-0 cellpadding-0"><TR><TD colspan="4"><b>'.Localize('colon',_('Visible To')).'</b></TD></TR><TR class="st">';
	foreach(array('admin'=>_('Administrator w/Custom'),'teacher'=>_('Teacher w/Custom'),'parent'=>_('Parent w/Custom')) as $profile_id=>$profile)
//modif Francois: add <label> on checkbox
		$return .= '<TD><label><INPUT type="checkbox" name="profiles[$id]['.$profile_id.']" value="Y"'.(mb_strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile_id,")!==false?' checked':'').' /> '.$profile.'</label></TD>';
		
	//modif Francois: Portal Polls add students teacher
	$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM STAFF WHERE (SCHOOLS IS NULL OR STRPOS(SCHOOLS,',".UserSchool().",')>0) AND SYEAR='".UserSyear()."' AND PROFILE='teacher' ORDER BY LAST_NAME,FIRST_NAME"));
	if(count($teachers_RET))
	{
		foreach($teachers_RET as $teacher)
			$teachers[$teacher['STAFF_ID']] = $teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'];
	}
	
	$i = 0;
	foreach($profiles_RET as $profile)
	{
		$i++;
		if ($profile['ID'] == 0) //student
			$return .= '</TR><TR class="st">';

		$return .= '<TD><label><INPUT type="checkbox" name="profiles['.$id.']['.$profile['ID'].']" value="Y"'.(mb_strpos($THIS_RET['PUBLISHED_PROFILES'],",$profile[ID],")!==false?' checked':'').' /> '._($profile['TITLE']);
		//modif Francois: Portal Polls add students teacher
		if ($profile['ID'] == 0) //student
		{
			$return .= ': </label></TD>';
			$return .= '<TD colspan="2">'.SelectInput($THIS_RET['STUDENTS_TEACHER_ID'],'values['.$id.'][STUDENTS_TEACHER_ID]',_('Limit to Teacher'),$teachers, true, '', true).'</TD>';
			$i = $i +2;
		}
		else
			$return .= '</label></TD>';
			
		if($i%3==0 && $i!=count($profile))
			$return .= '</TR><TR class="st">';
	}
	for(;$i%3!=0;$i++)
		$return .= '<TD>&nbsp;</TD>';
	$return .= '</TR>';
	
	$return .= '</TABLE>';
		
	$return .= '</TD></TR></TABLE>';
	return $return;
}
?>