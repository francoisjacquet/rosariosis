<?php
include 'modules/Grades/DeletePromptX.fnc.php';
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	if($_REQUEST['values'] && $_POST['values'])
	{
		if($_REQUEST['tab_id'])
		{
			foreach($_REQUEST['values'] as $id=>$columns)
			{
		//modif Francois: fix SQL bug invalid numeric data
				if ((empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER'])) && (empty($columns['BREAK_OFF']) || is_numeric($columns['BREAK_OFF'])) && (empty($columns['GPA_VALUE']) || is_numeric($columns['GPA_VALUE'])) && (empty($columns['UNWEIGHTED_GP']) || is_numeric($columns['UNWEIGHTED_GP'])))
				{
					if($id!='new')
					{
						if($_REQUEST['tab_id']!='new')
							$sql = "UPDATE REPORT_CARD_GRADES SET ";
						else
							$sql = "UPDATE REPORT_CARD_GRADE_SCALES SET ";

						foreach($columns as $column=>$value)
							$sql .= $column."='".$value."',";

						if($_REQUEST['tab_id']!='new')
							$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
						else
							$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
						DBQuery($sql);
					}
					else
					{
						if($_REQUEST['tab_id']!='new')
						{
							$sql = 'INSERT INTO REPORT_CARD_GRADES ';
							$fields = 'ID,SCHOOL_ID,SYEAR,GRADE_SCALE_ID,';
							$values = db_seq_nextval('REPORT_CARD_GRADES_SEQ').',\''.UserSchool().'\',\''.UserSyear().'\',\''.$_REQUEST['tab_id'].'\',';
						}
						else
						{
							$sql = 'INSERT INTO REPORT_CARD_GRADE_SCALES ';
							$fields = 'ID,SCHOOL_ID,SYEAR,';
							$values = db_seq_nextval('REPORT_CARD_GRADE_SCALES_SEQ').',\''.UserSchool().'\',\''.UserSyear().'\',';
						}

						$go = false;
						foreach($columns as $column=>$value)
							if($value)
							{
								$fields .= $column.',';
								$values .= '\''.$value.'\',';
								$go = true;
							}
						$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

						if($go)
							DBQuery($sql);
					}
				}
				else
					$error = ErrorMessage(array(_('Please enter valid Numeric data.')));
			}
		}
	}
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if($_REQUEST['tab_id']!='new')
	{
//modif Francois: add translation
		if(DeletePromptX(_('Report Card Grade')))
		{
			DBQuery("DELETE FROM REPORT_CARD_GRADES WHERE ID='$_REQUEST[id]'");
		}
	}
	else
		if(DeletePromptX(_('Report Card Grading Scale')))
		{
			DBQuery("DELETE FROM REPORT_CARD_GRADES WHERE GRADE_SCALE_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM REPORT_CARD_GRADE_SCALES WHERE ID='$_REQUEST[id]'");
		}
}

if(empty($_REQUEST['modfunc']))

{
	if(User('PROFILE')=='admin')
	{
		$grade_scales_RET = DBGet(DBQuery('SELECT ID,TITLE FROM REPORT_CARD_GRADE_SCALES WHERE SCHOOL_ID=\''.UserSchool().'\' AND SYEAR=\''.UserSyear().'\' ORDER BY SORT_ORDER'),array(),array('ID'));
		if($_REQUEST['tab_id']=='' || $_REQUEST['tab_id']!='new' && !$grade_scales_RET[$_REQUEST['tab_id']])
			if(count($grade_scales_RET))
				$_REQUEST['tab_id'] = key($grade_scales_RET).'';
			else
				$_REQUEST['tab_id'] = 'new';
	}
	else
	{
		$course_period_RET = DBGet(DBQuery('SELECT GRADE_SCALE_ID,DOES_BREAKOFF,TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=\''.UserCoursePeriod().'\''));
		if(!$course_period_RET[1]['GRADE_SCALE_ID'])
			ErrorMessage(array(_('This course is not graded.')),'fatal');
		$grade_scales_RET = DBGet(DBQuery('SELECT ID,TITLE FROM REPORT_CARD_GRADE_SCALES WHERE ID=\''.$course_period_RET[1]['GRADE_SCALE_ID'].'\''),array(),array('ID'));
		if($course_period_RET[1]['DOES_BREAKOFF']=='Y')
		{
			$teacher_id = $course_period_RET[1]['TEACHER_ID'];
			$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='$teacher_id' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
		}
		$_REQUEST['tab_id'] = key($grade_scales_RET).'';
	}

	$tabs = array();
	$grade_scale_select = array();
	foreach($grade_scales_RET as $id=>$grade_scale)
	{
		$tabs[] = array('title'=>$grade_scale[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=$id");
		$grade_scale_select[$id] = $grade_scale[1]['TITLE'];
	}

	if($_REQUEST['tab_id']!='new')
	{
		$sql = 'SELECT * FROM REPORT_CARD_GRADES WHERE GRADE_SCALE_ID=\''.$_REQUEST['tab_id'].'\' AND SYEAR=\''.UserSyear().'\' AND SCHOOL_ID=\''.UserSchool().'\' ORDER BY BREAK_OFF IS NOT NULL DESC,BREAK_OFF DESC, SORT_ORDER';
		$functions = array('TITLE'=>'makeGradesInput',
                            'BREAK_OFF'=>'makeGradesInput',
                            'SORT_ORDER'=>'makeGradesInput',
                            'GPA_VALUE'=>'makeGradesInput',
                            'UNWEIGHTED_GP'=>'makeGradesInput',
                            'COMMENT'=>'makeGradesInput');
		$LO_columns = array('TITLE'=>_('Title'),
                            'BREAK_OFF'=>_('Breakoff'),
                            'GPA_VALUE'=>_('GPA Value'),
                            'UNWEIGHTED_GP'=>_('Unweighted GP Value'),
                            'SORT_ORDER'=>_('Order'),
                            'COMMENT'=>_('Comment'));

		if(User('PROFILE')=='admin' && AllowEdit())
		{
			$functions += array('GRADE_SCALE_ID'=>'makeGradesInput');
			$LO_columns += array('GRADE_SCALE_ID'=>_('Grade Scale'));
		}

		$link['add']['html'] = array('TITLE'=>makeGradesInput('','TITLE'),'BREAK_OFF'=>makeGradesInput('','BREAK_OFF'),'GPA_VALUE'=>makeGradesInput('','GPA_VALUE'),'UNWEIGHTED_GP'=>makeGradesInput('','UNWEIGHTED_GP'),'SORT_ORDER'=>makeGradesInput('','SORT_ORDER'),'COMMENT'=>makeGradesInput('','COMMENT'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=$_REQUEST[tab_id]";
		$link['remove']['variables'] = array('id'=>'ID');
		$link['add']['html']['remove'] = button('add');

		if(User('PROFILE')=='admin')
			$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new");
		$singular = 'Grade';
		$plural = 'Grades';
	}
	else
	{
		$sql = 'SELECT * FROM REPORT_CARD_GRADE_SCALES WHERE SCHOOL_ID=\''.UserSchool().'\' AND SYEAR=\''.UserSyear().'\' ORDER BY SORT_ORDER,ID';
		$functions = array('TITLE'=>'makeTextInput','GP_SCALE'=>'makeTextInput','COMMENT'=>'makeTextInput','HHR_GPA_VALUE'=>'makeGradesInput','HR_GPA_VALUE'=>'makeGradesInput','HRS_GPA_VALUE'=>'makeGradesInput','SORT_ORDER'=>'makeTextInput');
		$LO_columns = array('TITLE'=>_('Gradescale'),'GP_SCALE'=>_('Scale Value'),'COMMENT'=>_('Comment'),'HHR_GPA_VALUE'=>_('High Honor Roll GPA Min'),'HR_GPA_VALUE'=>_('Honor Roll GPA Min'),'HRS_GPA_VALUE'=>_('Honor Roll by Subject GPA Min'),'SORT_ORDER'=>_('Sort Order'));

		$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'GP_SCALE'=>makeTextInput('', 'GP_SCALE'),'COMMENT'=>makeTextInput('','COMMENT'),'HHR_GPA_VALUE'=>makeGradesInput('','HHR_GPA_VALUE'),'HR_GPA_VALUE'=>makeGradesInput('','HR_GPA_VALUE'),'HRS_GPA_VALUE'=>makeGradesInput('','HRS_GPA_VALUE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=new";
		$link['remove']['variables'] = array('id'=>'ID');
		$link['add']['html']['remove'] = button('add');

		$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new");
		$singular = 'Grade Scale';
		$plural = 'Grade Scales';
	}
	$LO_ret = DBGet(DBQuery($sql),$functions);

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&tab_id='.$_REQUEST['tab_id'].'" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
	echo '<BR />';
//modif Francois: fix SQL bug invalid numeric data
	if(isset($error)) echo $error;

	$LO_options = array('save'=>false,'search'=>false,
		'header'=>WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&tab_id=$_REQUEST[tab_id]"));
	ListOutput($LO_ret,$LO_columns,$singular,$plural,$link,array(),$LO_options);

	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function makeGradesInput($value,$name)
{	global $THIS_RET,$grade_scale_select,$teacher_id,$config_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='GRADE_SCALE_ID')
		return SelectInput($value,"values[$id][$name]",'',$grade_scale_select,false);
	elseif($name=='COMMENT')
		$extra = 'size=15 maxlength=100';
//modif Francois: Honor Roll by Subject
	elseif($name=='GPA_VALUE' || $name=='HHR_GPA_VALUE' || $name=='HR_GPA_VALUE' || $name=='HRS_GPA_VALUE')
		$extra = 'size=5 maxlength=5';
	elseif($name=='SORT_ORDER')
		$extra = 'size=5 maxlength=5';
	elseif($name=='BREAK_OFF' && $teacher_id && $config_RET[UserCoursePeriod().'-'.$THIS_RET['ID']][1]['VALUE']!='')
		return '<span style="color:blue">'.$config_RET[UserCoursePeriod().'-'.$THIS_RET['ID']][1]['VALUE'].'</span>';
	else
		$extra = 'size=5 maxlength=5';

	return TextInput($value,"values[$id][$name]",'',$extra);
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
    //bjj adding 'GP_SCALE'
	if($name=='TITLE')
		$extra = 'size=15 maxlength=25';
    elseif($name=='GP_SCALE')
        $extra = 'size=5 maxlength=5';
	elseif($name=='COMMENT')
		$extra = 'size=15 maxlength=100';
	else
		$extra = 'size=5 maxlength=5';

	return TextInput($value,"values[$id][$name]",'',$extra);
}
?>
