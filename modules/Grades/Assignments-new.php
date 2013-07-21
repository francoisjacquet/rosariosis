<?php
include 'modules/Grades/DeletePromptX.fnc.php';
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';
DrawHeader(ProgramTitle());
$_ROSARIO['allow_edit'] = ($_REQUEST['allow_edit']=='Y');

if($_REQUEST['day_values'] && $_POST['day_values'])
{
	foreach($_REQUEST['day_values'] as $id=>$values)
	{
		if($_REQUEST['day_values'][$id]['ASSIGNED_DATE'] && $_REQUEST['month_values'][$id]['ASSIGNED_DATE'] && $_REQUEST['year_values'][$id]['ASSIGNED_DATE'])
		{
			while(!VerifyDate($_REQUEST['day_values'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['month_values'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['year_values'][$id]['ASSIGNED_DATE']))
				$_REQUEST['day_values'][$id]['ASSIGNED_DATE']--;
			$_REQUEST['values'][$id]['ASSIGNED_DATE'] = $_REQUEST['day_values'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['month_values'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['year_values'][$id]['ASSIGNED_DATE'];
		}
		if($_REQUEST['day_values'][$id]['DUE_DATE'] && $_REQUEST['month_values'][$id]['DUE_DATE'] && $_REQUEST['year_values'][$id]['DUE_DATE'])
		{
			while(!VerifyDate($_REQUEST['day_values'][$id]['DUE_DATE'].'-'.$_REQUEST['month_values'][$id]['DUE_DATE'].'-'.$_REQUEST['year_values'][$id]['DUE_DATE']))
				$_REQUEST['day_values'][$id]['DUE_DATE']--;
			$_REQUEST['values'][$id]['DUE_DATE'] = $_REQUEST['day_values'][$id]['DUE_DATE'].'-'.$_REQUEST['month_values'][$id]['DUE_DATE'].'-'.$_REQUEST['year_values'][$id]['DUE_DATE'];
		}
	}
	$_POST['values'] = $_REQUEST['values'];
	unset($_REQUEST['day_values']); unset($_REQUEST['month_values']); unset($_REQUEST['year_values']);
	unset($_SESSION['_REQUEST_vars']['day_values']); unset($_SESSION['_REQUEST_vars']['month_values']); unset($_SESSION['_REQUEST_vars']['year_values']);
}

if($_REQUEST['modfunc']=='update')
{
	if($_REQUEST['values'] && $_POST['values'])
	{
		foreach($_REQUEST['values'] as $id=>$columns)
		{
			if($id!='new')
			{
				if($_REQUEST['tab_id']!='new')
				{
					$sql = "UPDATE GRADEBOOK_ASSIGNMENTS SET ";
					//if(!$columns['COURSE_ID'])
					//	$columns['COURSE_ID'] = 'N';
				}
				else
					$sql = "UPDATE GRADEBOOK_ASSIGNMENT_TYPES SET ";

				foreach($columns as $column=>$value)
				{
					if($column=='POINTS' && $value!='')
						$value += 0;
					elseif($column=='FINAL_GRADE_PERCENT' && $value!='')
						$value /= 100;
					elseif($column=='COURSE_ID')
					{
						if($value=='Y')
						{
							$column = 'COURSE_PERIOD_ID';
							$value = '';
							$sql .= "COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'),";
						}
						else
						{
							$value = '';
							$sql .= "COURSE_PERIOD_ID='".UserCoursePeriod()."',";
						}
					}
					$sql .= $column."='".$value."',";
				}

				if($_REQUEST['tab_id']!='new')
					$sql = mb_substr($sql,0,-1) . " WHERE ASSIGNMENT_ID='$id'";
				else
					$sql = mb_substr($sql,0,-1) . " WHERE ASSIGNMENT_TYPE_ID='$id'";
				DBQuery($sql);
			}
			else
			{
				if($_REQUEST['tab_id']!='new')
				{
					$sql = 'INSERT INTO GRADEBOOK_ASSIGNMENTS ';
					$fields = "ASSIGNMENT_ID,STAFF_ID,MARKING_PERIOD_ID,";
					$values = db_seq_nextval('GRADEBOOK_ASSIGNMENTS_SEQ').",'".User('STAFF_ID')."','".UserMP()."',";
					if($_REQUEST['tab_id'])
					{
						$fields .= "ASSIGNMENT_TYPE_ID,";
						$values .= "'".$_REQUEST['tab_id']."',";
					}
					if(!$columns['COURSE_ID'])
						$columns['COURSE_ID'] = 'N';
				}
				else
				{
					$sql = 'INSERT INTO GRADEBOOK_ASSIGNMENT_TYPES ';
					$fields = 'ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_ID,';
					$values = db_seq_nextval('GRADEBOOK_ASSIGNMENT_TYPES_SEQ').",'".User('STAFF_ID')."',(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'),";
				}

				$go = false;
				foreach($columns as $column=>$value)
				{
					if($column=='POINTS' && $value!='')
						$value = ($value+0).'';
					elseif($column=='FINAL_GRADE_PERCENT' && $value!='')
						$value = ($value/100).'';
					elseif($column=='COURSE_ID')
					{
						if($value=='Y')
						{
							$column = 'COURSE_PERIOD_ID';
							$value = '';
							$fields .= "COURSE_ID,";
							$values .= "(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'),";
						}
						else
						{
							$value = '';
							$fields .= 'COURSE_PERIOD_ID,';
							$values .= "'".UserCoursePeriod()."',";
						}
					}
					if($value!='')
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						if($column!='ASSIGNMENT_TYPE_ID' && $column!='ASSIGNED_DATE' && $column!='DUE_DATE')
							$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if($go)
					DBQuery($sql);
			}
		}
	}
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove')
{
	if(DeletePromptX($_REQUEST['tab_id']!='new'?'assignment':'assignment type'))
        {
		if($_REQUEST['tab_id']!='new')
		{
			DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='$_REQUEST[id]'");
		}
		else
		{
			$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[id]'"));
			if(count($assignments_RET))
			{
				foreach($assignments_RET as $assignment_id)
					DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='".$assignment_id['ASSIGNMENT_ID']."'");
			}
			DBQuery("DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[id]'");
		}
		unset($_REQUEST['id']);
		unset($_REQUEST['modfunc']);
	}
}

if(empty($_REQUEST['modfunc']))

{
	$types_RET = DBGet(DBQuery("SELECT ASSIGNMENT_TYPE_ID,TITLE,SORT_ORDER,COLOR FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') ORDER BY SORT_ORDER,TITLE"),array(),array('ASSIGNMENT_TYPE_ID'));
	if($_REQUEST['tab_id'])
	{
		if($_REQUEST['tab_id']!='new' && !$types_RET[$_REQUEST['tab_id']])
			if(count($types_RET))
				$_REQUEST['tab_id'] = key($types_RET).'';
			else
				$_REQUEST['tab_id'] = 'new';
	}
	else
		if(!count($types_RET))
			$_REQUEST['tab_id'] = 'new';

	if(count($types_RET))
		$tabs = array(array('title'=>_('All'),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=&allow_edit=$_REQUEST[allow_edit]"));
	foreach($types_RET as $id=>$type)
	{
		$tabs[] = array('title'=>$type[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=$id&allow_edit=$_REQUEST[allow_edit]",'color'=>$type[1]['COLOR']);
		$type_options[$id] = !$_REQUEST['tab_id']&&$type[1]['COLOR']?array($type[1]['TITLE'],'<span style="color:'.$type[1]['COLOR'].'">'.$type[1]['TITLE'].'</span>'):$type[1]['TITLE'];
	}

	if($_REQUEST['tab_id']!='new')
	{
		$sql = "SELECT ASSIGNMENT_ID,TITLE,ASSIGNED_DATE,DUE_DATE,POINTS,COURSE_ID,DESCRIPTION,ASSIGNMENT_TYPE_ID,".
			db_case(array('(DUE_DATE<ASSIGNED_DATE)','TRUE',"'Y'",'NULL'))." AS DATE_ERROR,".
			db_case(array('(ASSIGNED_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=\''.UserMP().'\'))','TRUE',"'Y'",'NULL'))." AS ASSIGNED_ERROR,".
			db_case(array('DUE_DATE>(SELECT END_DATE+1 FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=\''.UserMP().'\')','TRUE',"'Y'",'NULL'))." AS DUE_ERROR ".
			"FROM GRADEBOOK_ASSIGNMENTS ".
			"WHERE STAFF_ID='".User('STAFF_ID')."' AND (COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') OR COURSE_PERIOD_ID='".UserCoursePeriod()."')".($_REQUEST['tab_id']?" AND ASSIGNMENT_TYPE_ID='".$_REQUEST['tab_id']."'":'').
			" AND MARKING_PERIOD_ID='".UserMP()."' ORDER BY ".Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC,ASSIGNMENT_ID DESC,TITLE";
		$functions = array('TITLE'=>'_makeAssnInput','POINTS'=>'_makeAssnInput','ASSIGNED_DATE'=>'_makeAssnInput','DUE_DATE'=>'_makeAssnInput','COURSE_ID'=>'_makeAssnInput','DESCRIPTION'=>'_makeAssnInput');
		if($_REQUEST['allow_edit']=='Y' || !$_REQUEST['tab_id'])
			$functions['ASSIGNMENT_TYPE_ID'] = '_makeAssnInput';
		$LO_ret = DBGet(DBQuery($sql),$functions);

		$LO_columns = array('TITLE'=>_('Title'),'POINTS'=>_('Points'),'ASSIGNED_DATE'=>_('Assigned Date'),'DUE_DATE'=>_('Due Date'),'COURSE_ID'=>_('All'),'DESCRIPTION'=>_('Description'));
		if($_REQUEST['allow_edit']=='Y' || !$_REQUEST['tab_id'])
			$LO_columns += array('ASSIGNMENT_TYPE_ID'=>_('Type'));
		$link['add']['html'] = array('TITLE'=>_makeAssnInput('','TITLE'),'POINTS'=>_makeAssnInput('','POINTS'),'ASSIGNED_DATE'=>_makeAssnInput('','ASSIGNED_DATE'),'DUE_DATE'=>_makeAssnInput('','DUE_DATE'),'COURSE_ID'=>_makeAssnInput('','COURSE_ID'),'DESCRIPTION'=>_makeAssnInput('','DESCRIPTION'));
		if(!$_REQUEST['tab_id'])
			$link['add']['html'] += array('ASSIGNMENT_TYPE_ID'=>_makeAssnInput('','ASSIGNMENT_TYPE_ID'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=$_REQUEST[tab_id]&allow_edit=$_REQUEST[allow_edit]";
		$link['remove']['variables'] = array('id'=>'ASSIGNMENT_ID');
		$link['add']['html']['remove'] = button('add');
		$link['add']['first'] = 1; // number before add link moves to top

		$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new&allow_edit=$_REQUEST[allow_edit]");
		$subject = 'Assignments';
	}
	else
	{
		$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,FINAL_GRADE_PERCENT,SORT_ORDER,COLOR FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') ORDER BY SORT_ORDER,TITLE";
		$functions = array('TITLE'=>'_makeTypeInput','SORT_ORDER'=>'_makeTypeInput','COLOR'=>'_makeColorInput');
		if(Preferences('WEIGHT','Gradebook')=='Y')
			$functions['FINAL_GRADE_PERCENT'] = '_makeTypeInput';
		$LO_ret = DBGet(DBQuery($sql),$functions);

		$LO_columns = array('TITLE'=>_('Type'));
		if(Preferences('WEIGHT','Gradebook')=='Y')
			$LO_columns += array('FINAL_GRADE_PERCENT'=>_('Percent'));
		$LO_columns += array('SORT_ORDER'=>_('Sort Order'),'COLOR'=>_('Color'));
		$link['add']['html'] = array('TITLE'=>_makeTypeInput('','TITLE'),'SORT_ORDER'=>_makeTypeInput('','SORT_ORDER'),'COLOR'=>_makeColorInput('','COLOR'));
		if(Preferences('WEIGHT','Gradebook')=='Y')
			$link['add']['html']['FINAL_GRADE_PERCENT'] = _makeTypeInput('','FINAL_GRADE_PERCENT');
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=new&allow_edit=$_REQUEST[allow_edit]";
		$link['remove']['variables'] = array('id'=>'ASSIGNMENT_TYPE_ID');
		$link['add']['html']['remove'] = button('add');

		$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new&allow_edit=$_REQUEST[allow_edit]");
		$subject = 'Assignmemt Types';
	}

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&tab_id='.$_REQUEST['tab_id'].'" method="POST">';
	DrawHeader('<label>'.CheckBoxOnclick('allow_edit').' '._('Edit').'</label>',SubmitButton(_('Save')));
	echo '<BR />';

	$LO_options = array('save'=>false,'search'=>false,'header_color'=>$types_RET[$_REQUEST['tab_id']][1]['COLOR'],
		'header'=>WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&tab_id=$_REQUEST[tab_id]&allow_edit=$_REQUEST[allow_edit]"));
    if ($subject == 'Assignments')
	    ListOutput($LO_ret,$LO_columns,'Assignment','Assignments',$link,array(),$LO_options);
    else
        ListOutput($LO_ret,$LO_columns,'Assignment Type','Assignment Types',$link,array(),$LO_options);

	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeAssnInput($value,$name)
{	global $THIS_RET,$type_options;

	if($THIS_RET['ASSIGNMENT_ID'])
		$id = $THIS_RET['ASSIGNMENT_ID'];
	else
		$id = 'new';

	if($name=='TITLE')
	{
		if($id!='new' && !$value)
			$title = '<span style="color:red">'._('Title').'</span>';
		$extra = 'size=25 maxlength=100';
	}
	elseif($name=='POINTS')
	{
		if($id!='new' && $value=='')
			$title = '<span style="color:red">'._('Points').'</span>';
		$extra = 'size=5 maxlength=5';
	}
	elseif($name=='ASSIGNED_DATE')
		return DateInput($id=='new' && Preferences('DEFAULT_ASSIGNED','Gradebook')=='Y'?DBDate():$value,"values[$id][ASSIGNED_DATE]",($THIS_RET['ASSIGNED_ERROR']=='Y'?'<span style="color:red">'._('Assigned date is after end of quarter!').'</span>':($THIS_RET['DATE_ERROR']=='Y'?'<span style="color:red">'._('Assigned date is after due date!').'</span>':'')),$id!='new');
	elseif($name=='DUE_DATE')
		return DateInput($id=='new' && Preferences('DEFAULT_DUE','Gradebook')=='Y'?DBDate():$value,"values[$id][DUE_DATE]",($THIS_RET['DUE_ERROR']=='Y'?'<span style="color:red">'._('Due date is after end of quarter!').'</span>':($THIS_RET['DATE_ERROR']=='Y'?'<span style="color:red">'._('Due date is before assigned date!').'</span>':'')),$id!='new');
	elseif($name=='COURSE_ID')
		return CheckboxInput($value,"values[$id][COURSE_ID]",'','',$id=='new');
	elseif($name=='DESCRIPTION')
		$extra = 'size=25 maxlength=1000';
	elseif($name=='ASSIGNMENT_TYPE_ID')
		return SelectInput($value,"values[$id][ASSIGNMENT_TYPE_ID]",'',$type_options,false);

	return TextInput($value,"values[$id][$name]",$title,$extra);
}

function _makeTypeInput($value,$name)
{	global $THIS_RET,$total_percent;

	if($THIS_RET['ASSIGNMENT_TYPE_ID'])
		$id = $THIS_RET['ASSIGNMENT_TYPE_ID'];
	else
		$id = 'new';

	if($name=='TITLE')
		$extra = 'size=25 maxlength=100';
	elseif($name=='FINAL_GRADE_PERCENT')
	{
		if($id=='new')
		{
			$title = ($total_percent!=1?'<span style="color:red">':'')._('Total').' = '.($total_percent*100).'%'.($total_percent!=1?'</span>':'');
		}
		else
		{
			$total_percent += $value;
			$value = array($value*100,($value*100).'%');
		}
		$extra = 'size=5 maxlength=10';
	}
	elseif($name=='SORT_ORDER')
		$extra = 'size=5 maxlength=10';

	return TextInput($value,"values[$id][$name]",$title,$extra);
}

function _makeColorInput($value,$name)
{	global $THIS_RET,$color_select;

	if($THIS_RET['ASSIGNMENT_TYPE_ID'])
		$id = $THIS_RET['ASSIGNMENT_TYPE_ID'];
	else
		$id = 'new';

	if(!$color_select)
	{
		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666','#333366','#336633','purple','teal','firebrick','tan');
		foreach($colors as $color)
		{
			$color_select[$color] = array('<TABLE class="cellpadding-0 cellspacing-0"><TR><TD style="width:100%; background-color:'.$color.'">&nbsp;</TD></TR></TABLE>','<TABLE class="cellpadding-1 cellspacing-0"><TR><TD style="background-color:'.$color.'; width:30px">&nbsp;</TD></TR></TABLE>');
		}
	}
	return RadioInput($value,"values[$id][COLOR]",'',$color_select);
}
?>
