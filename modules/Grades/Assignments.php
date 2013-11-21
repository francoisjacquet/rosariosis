<?php
DrawHeader(_('Gradebook').' - '.ProgramTitle());
/*
$course_id = DBGet(DBQuery("SELECT COURSE_ID,COURSE_PERIOD_ID FROM COURSE_PERIODS WHERE TEACHER_ID='".User('STAFF_ID')."' AND PERIOD_ID='".UserPeriod()."' AND MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).')'));
$course_period_id = $course_id[1]['COURSE_PERIOD_ID'];
$course_id = $course_id[1]['COURSE_ID'];
*/
$course_id = DBGet(DBQuery("SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
$course_id = $course_id[1]['COURSE_ID'];

$_ROSARIO['allow_edit'] = true;
unset($_SESSION['_REQUEST_vars']['assignment_type_id']);
unset($_SESSION['_REQUEST_vars']['assignment_id']);

if($_REQUEST['day_tables'] && $_POST['day_tables'])
{
	foreach($_REQUEST['day_tables'] as $id=>$values)
	{
		if($_REQUEST['day_tables'][$id]['DUE_DATE'] && $_REQUEST['month_tables'][$id]['DUE_DATE'] && $_REQUEST['year_tables'][$id]['DUE_DATE'])
			$_REQUEST['tables'][$id]['DUE_DATE'] = $_REQUEST['day_tables'][$id]['DUE_DATE'].'-'.$_REQUEST['month_tables'][$id]['DUE_DATE'].'-'.$_REQUEST['year_tables'][$id]['DUE_DATE'];
		if($_REQUEST['day_tables'][$id]['ASSIGNED_DATE'] && $_REQUEST['month_tables'][$id]['ASSIGNED_DATE'] && $_REQUEST['year_tables'][$id]['ASSIGNED_DATE'])
			$_REQUEST['tables'][$id]['ASSIGNED_DATE'] = $_REQUEST['day_tables'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['month_tables'][$id]['ASSIGNED_DATE'].'-'.$_REQUEST['year_tables'][$id]['ASSIGNED_DATE'];
	}
	$_POST['tables'] = $_REQUEST['tables'];
}

if($_REQUEST['tables'] && $_POST['tables'])
{
	$table = $_REQUEST['table'];
	foreach($_REQUEST['tables'] as $id=>$columns)
	{
		//modif Francois: added SQL constraint TITLE & POINTS are not null
		if ((isset($columns['TITLE']) && empty($columns['TITLE'])) || (isset($columns['POINTS']) && empty($columns['POINTS'])))
			BackPrompt(_('Please fill in the required fields'));

		//modif Francois: fix SQL bug invalid numeric data
        if (!isset($columns['POINTS']) || (is_numeric($columns['POINTS']) && intval($columns['POINTS'])>=0))
		{
			//modif Francois: fix SQL bug invalid sort order
			if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
			{
				if($id!='new')
				{
					if($columns['ASSIGNMENT_TYPE_ID'] && $columns['ASSIGNMENT_TYPE_ID']!=$_REQUEST['assignment_type_id'])
						$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];

					$sql = "UPDATE $table SET ";

					//if(!$columns['COURSE_ID'] && $table=='GRADEBOOK_ASSIGNMENTS')
					//	$columns['COURSE_ID'] = 'N';

					foreach($columns as $column=>$value)
					{
						if($column=='DUE_DATE' || $column=='ASSIGNED_DATE')
						{
							if(!VerifyDate($value))
								BackPrompt(_('Some dates were not entered correctly.'));
						}
						elseif($column=='COURSE_ID' && $value=='Y' && $table=='GRADEBOOK_ASSIGNMENTS')
						{
							$value = $course_id;
							$sql .= 'COURSE_PERIOD_ID=NULL,';
						}
						elseif($column=='COURSE_ID' && $table=='GRADEBOOK_ASSIGNMENTS')
						{
							$column = 'COURSE_PERIOD_ID';
							$value = UserCoursePeriod();
							$sql .= 'COURSE_ID=NULL,';
						}
						elseif($column=='FINAL_GRADE_PERCENT' && $table=='GRADEBOOK_ASSIGNMENT_TYPES')
							$value = preg_replace('/[^0-9.]/','',$value) / 100;


						$sql .= $column."='".$value."',";
					}
					$sql = mb_substr($sql,0,-1) . " WHERE ".mb_substr($table,10,-1)."_ID='$id'";
					$go = true;
					$gradebook_assignment_update = true;
				}
				else
				{
					$sql = "INSERT INTO $table ";

					if($table=='GRADEBOOK_ASSIGNMENTS')
					{
						if($columns['ASSIGNMENT_TYPE_ID'])
						{
							$_REQUEST['assignment_type_id'] = $columns['ASSIGNMENT_TYPE_ID'];
							unset($columns['ASSIGNMENT_TYPE_ID']);
						}
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('GRADEBOOK_ASSIGNMENTS_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ASSIGNMENT_ID,ASSIGNMENT_TYPE_ID,STAFF_ID,MARKING_PERIOD_ID,";
						$values = $id.",'".$_REQUEST['assignment_type_id']."','".User('STAFF_ID')."','".UserMP()."',";
						$_REQUEST['assignment_id'] = $id;
					}
					elseif($table=='GRADEBOOK_ASSIGNMENT_TYPES')
					{
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('GRADEBOOK_ASSIGNMENT_TYPES_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_ID,";
						$values = $id.",'".User('STAFF_ID')."','$course_id',";
						$_REQUEST['assignment_type_id'] = $id;
					}

					$go = false;

					if(!$columns['COURSE_ID'] && $_REQUEST['table']=='GRADEBOOK_ASSIGNMENTS')
						$columns['COURSE_ID'] = 'N';

					foreach($columns as $column=>$value)
					{
						if($column=='DUE_DATE' || $column=='ASSIGNED_DATE')
						{
							if(!VerifyDate($value))
								BackPrompt(_('Some dates were not entered correctly.'));
						}
						elseif($column=='COURSE_ID' && $value=='Y')
							$value = $course_id;
						elseif($column=='COURSE_ID')
						{
							$column = 'COURSE_PERIOD_ID';
							$value = UserCoursePeriod();
						}
						elseif($column=='FINAL_GRADE_PERCENT' && $table=='GRADEBOOK_ASSIGNMENT_TYPES')
							$value = preg_replace('/[^0-9.]/','',$value) / 100;

						if($value!='')
						{
							$fields .= $column.',';
							$values .= "'".$value."',";
							$go = true;
						}
					}
					$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
				}
			}
			else
				echo ErrorMessage(array(_('Please enter a valid Sort Order.')));
		}
		else
			echo ErrorMessage(array(_('Please enter valid Numeric data.')));
			
		if($go)
		{
			DBQuery($sql);
			
//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR && $table=='GRADEBOOK_ASSIGNMENTS') //add course event to the Moodle calendar
			{
				if ($gradebook_assignment_update) //delete event then recreate it!
				{
					echo Moodle($_REQUEST['modname'], 'core_calendar_delete_calendar_events');
					echo $moodleError;
				}
				if (isset($columns['DUE_DATE']))
				{
					$moodleError = Moodle($_REQUEST['modname'], 'core_calendar_create_calendar_events');
					echo $moodleError; 
				}
			}
		}
	}
	unset($_REQUEST['tables']);
	unset($_SESSION['_REQUEST_vars']['tables']);
}

if($_REQUEST['modfunc']=='delete')
{
	if($_REQUEST['assignment_id'])
	{
		$table = 'assignment';
		$sql = "DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'";
	}
	else
	{
		$table = 'assignment type';
		$sql = "DELETE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'";
	}

	if(DeletePrompt($table))
	{
		DBQuery($sql);
		if(!$_REQUEST['assignment_id'])
		{
			$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'"));
			if(count($assignments_RET))
			{
				foreach($assignments_RET as $assignment_id)
				{
					DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='".$assignment_id['ASSIGNMENT_ID']."'");

//modif Francois: Moodle integrator
					if (MOODLE_INTEGRATOR) //add course event to the Moodle calendar
					{
						$_REQUEST['assignment_id'] = $assignment_id['ASSIGNMENT_ID'];
						echo Moodle($_REQUEST['modname'], 'core_calendar_delete_calendar_events');
						unset($_REQUEST['assignment_id']);
					}
				}
			}
			DBQuery("DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'");
			unset($_REQUEST['assignment_type_id']);
		}
		else
		{
			DBQuery("DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'");
//modif Francois: Moodle integrator
			if (MOODLE_INTEGRATOR) //add course event to the Moodle calendar
			{
				$moodleError = Moodle($_REQUEST['modname'], 'core_calendar_delete_calendar_events');
				echo $moodleError;
			}
			unset($_REQUEST['assignment_id']);
		}
		unset($_REQUEST['modfunc']);
	}
}

if(empty($_REQUEST['modfunc']))

{
	// ASSIGNMENT TYPES
	$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,SORT_ORDER FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$types_RET = DBGet($QI);

	if($_REQUEST['assignment_id']!='new' && $_REQUEST['assignment_type_id']!='new')
	{
		$delete_button = '<script type="text/javascript">var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&assignment_type_id='.$_REQUEST['assignment_type_id'].'&assignment_id='.$_REQUEST['assignment_id'].'"; delete_link.target = "body";</script>';
		$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
	}

	// ADDING & EDITING FORM
	if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='new')
	{
		$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,ASSIGNED_DATE,DUE_DATE,POINTS,COURSE_ID,DESCRIPTION,
				CASE WHEN DUE_DATE<ASSIGNED_DATE THEN 'Y' ELSE NULL END AS DATE_ERROR,
				CASE WHEN ASSIGNED_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') THEN 'Y' ELSE NULL END AS ASSIGNED_ERROR,
				CASE WHEN DUE_DATE>(SELECT END_DATE+1 FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') THEN 'Y' ELSE NULL END AS DUE_ERROR
				FROM GRADEBOOK_ASSIGNMENTS
				WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI);
		$RET = $RET[1];
		$title = $RET['TITLE'];
	}
	elseif($_REQUEST['assignment_type_id'] && $_REQUEST['assignment_type_id']!='new' && $_REQUEST['assignment_id']!='new')
	{
		$sql = "SELECT at.TITLE,at.FINAL_GRADE_PERCENT,SORT_ORDER,COLOR,
				(SELECT sum(FINAL_GRADE_PERCENT) FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND STAFF_ID='".User('STAFF_ID')."') AS TOTAL_PERCENT
				FROM GRADEBOOK_ASSIGNMENT_TYPES at
				WHERE at.ASSIGNMENT_TYPE_ID='$_REQUEST[assignment_type_id]'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI,array('FINAL_GRADE_PERCENT'=>'_makePercent'));
		$RET = $RET[1];
		$title = $RET['TITLE'];
	}
	elseif($_REQUEST['assignment_id']=='new')
	{
		$title = _('New Assignment');
		$new = true;
	}
	elseif($_REQUEST['assignment_type_id']=='new')
	{
		$sql = "SELECT sum(FINAL_GRADE_PERCENT) AS TOTAL_PERCENT FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') AND STAFF_ID='".User('STAFF_ID')."'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI,array('FINAL_GRADE_PERCENT'=>'_makePercent'));
		$RET = $RET[1];
		$title = _('New Assignment Type');
	}

	if($_REQUEST['assignment_id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&assignment_type_id='.$_REQUEST['assignment_type_id'];
		if($_REQUEST['assignment_id']!='new')
			echo '&assignment_id='.$_REQUEST['assignment_id'];
		echo '&table=GRADEBOOK_ASSIGNMENTS" method="POST">';

		DrawHeader($title,$delete_button.SubmitButton(_('Save')));
		$header .= '<TABLE class="width-100p cellpadding-3">';
		$header .= '<TR class="st">';

//modif Francois: title & points are required
		$header .= '<TD>' . TextInput($RET['TITLE'],'tables['.$_REQUEST['assignment_id'].'][TITLE]',($RET['TITLE']?'':'<span style="color:red">')._('Title').($RET['TITLE']?'':'</span>'),'required') . '</TD>';
		$header .= '<TD>' . TextInput($RET['POINTS'],'tables['.$_REQUEST['assignment_id'].'][POINTS]',($RET['POINTS']!=''?'':'<span style="color:red">')._('Points').($RET['POINTS']?'':'</span>'),' size=4 maxlength=4 required min=0') . '</TD>';
		$header .= '<TD>' . CheckboxInput($RET['COURSE_ID'],'tables['.$_REQUEST['assignment_id'].'][COURSE_ID]',_('Apply to all Periods for this Course'),'',$_REQUEST['assignment_id']=='new') . '</TD>';
		foreach($types_RET as $type)
			$assignment_type_options[$type['ASSIGNMENT_TYPE_ID']] = $type['TITLE'];

		$header .= '<TD>' . SelectInput($RET['ASSIGNMENT_TYPE_ID']?$RET['ASSIGNMENT_TYPE_ID']:$_REQUEST['assignment_type_id'],'tables['.$_REQUEST['assignment_id'].'][ASSIGNMENT_TYPE_ID]',_('Assignment Type'),$assignment_type_options,false) . '</TD>';
		$header .= '</TR><TR class="st">';
		$header .= '<TD class="valign-top">' . DateInput($new && Preferences('DEFAULT_ASSIGNED','Gradebook')=='Y'?DBDate():$RET['ASSIGNED_DATE'],'tables['.$_REQUEST['assignment_id'].'][ASSIGNED_DATE]',_('Assigned'),!$new) . '</TD>';
		$header .= '<TD class="valign-top">' . DateInput($new && Preferences('DEFAULT_DUE','Gradebook')=='Y'?DBDate():$RET['DUE_DATE'],'tables['.$_REQUEST['assignment_id'].'][DUE_DATE]',_('Due'),!$new) . '</TD>';
		$header .= '<TD rowspan="2" colspan="2">' . TextareaInput($RET['DESCRIPTION'],'tables['.$_REQUEST['assignment_id'].'][DESCRIPTION]',_('Description')) . '</TD>';
		$header .= '</TR>';
		$errors = ($RET['DATE_ERROR']=='Y'?'<span style="color:red">'._('Due date is before assigned date!').'</span><BR />':'');
		$errors .= ($RET['ASSIGNED_ERROR']=='Y'?'<span style="color:red">'._('Assigned date is after end of quarter!').'</span><BR />':'');
		$errors .= ($RET['DUE_ERROR']=='Y'?'<span style="color:red">'._('Due date is after end of quarter!').'</span><BR />':'');
		$header .= '<TR><TD class="valign-top" colspan="2">'.mb_substr($errors,0,-6).'</TD></TR>';
		$header .= '</TABLE>';
	}
	elseif($_REQUEST['assignment_type_id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table=GRADEBOOK_ASSIGNMENT_TYPES';
		if($_REQUEST['assignment_type_id']!='new')
			echo '&assignment_type_id='.$_REQUEST['assignment_type_id'];
		echo '" method="POST">';
		DrawHeader($title,$delete_button.SubmitButton(_('Save')));
		$header .= '<TABLE class="width-100p cellpadding-3">';
		$header .= '<TR class="st">';

//modif Francois: title is required
		$header .= '<TD>' . TextInput($RET['TITLE'],'tables['.$_REQUEST['assignment_type_id'].'][TITLE]',($RET['TITLE']?'':'<span style="color:red">')._('Title').($RET['TITLE']?'':'</span>'),'required') . '</TD>';
		if(Preferences('WEIGHT','Gradebook')=='Y')
		{
			$header .= '<TD>' . TextInput($RET['FINAL_GRADE_PERCENT'],'tables['.$_REQUEST['assignment_type_id'].'][FINAL_GRADE_PERCENT]',($RET['FINAL_GRADE_PERCENT']!=0?'':'<span style="color:red">')._('Percent of Final Grade').($RET['FINAL_GRADE_PERCENT']!=0?'':'</span>')) . '</TD>';
			$header .= '<TD>' . NoInput($RET['TOTAL_PERCENT']==1?'100%':'<span style="color:red">'.(100*$RET['TOTAL_PERCENT']).'%</span>',_('Percent Total')) . '</TD>';
		}
		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['assignment_type_id'].'][SORT_ORDER]',_('Sort Order')) . '</TD>';
		$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666','#333366','#336633','purple','teal','firebrick','tan');
		foreach($colors as $color)
		{
			$color_select[$color] = array('<span style="background-color:'.$color.';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>','<span style="background-color:'.$color.';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>');
		}
//modif Francois: add translation
		$header .= '<TD>' .  RadioInput($RET['COLOR'],'tables['.$_REQUEST['assignment_type_id'].'][COLOR]',_('Color'),$color_select) . '</TD>';

		$header .= '</TR></TABLE>';
	}
	else
		$header = false;

	if($header)
	{
		DrawHeader($header);
		echo '</FORM>';
	}

	// DISPLAY THE MENU
	$LO_options = array('save'=>false,'search'=>false,'add'=>true,'responsive'=>false);

	if(count($types_RET))
	{
		if($_REQUEST['assignment_type_id'])
		{
			foreach($types_RET as $key=>$value)
			{
				if($value['ASSIGNMENT_TYPE_ID']==$_REQUEST['assignment_type_id'])
					$types_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	echo '<div class="st">';
	$columns = array('TITLE'=>_('Assignment Type'),'SORT_ORDER'=>_('Order'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
	$link['TITLE']['variables'] = array('assignment_type_id'=>'ASSIGNMENT_TYPE_ID');
	$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=new";
	$link['add']['first'] = 5; // number before add link moves to top

	ListOutput($types_RET,$columns,'Assignment Type','Assignment Types',$link,array(),$LO_options);
	echo '</div>';


	// ASSIGNMENTS
	if($_REQUEST['assignment_type_id'] && $_REQUEST['assignment_type_id']!='new' && count($types_RET))
	{
		$sql = "SELECT ASSIGNMENT_ID,TITLE,POINTS FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID='".User('STAFF_ID')."' AND (COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."') OR COURSE_PERIOD_ID='".UserCoursePeriod()."') AND ASSIGNMENT_TYPE_ID='".$_REQUEST['assignment_type_id']."' AND MARKING_PERIOD_ID='".UserMP()."' ORDER BY ".Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC";
		$QI = DBQuery($sql);
		$assn_RET = DBGet($QI);

		if(count($assn_RET))
		{
			if($_REQUEST['assignment_id'] && $_REQUEST['assignment_id']!='new')
			{
				foreach($assn_RET as $key=>$value)
				{
					if($value['ASSIGNMENT_ID']==$_REQUEST['assignment_id'])
						$assn_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
				}
			}
		}

		echo '<div class="st">';
		$columns = array('TITLE'=>_('Assignment'),'POINTS'=>_('Points'));
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=$_REQUEST[assignment_type_id]";
		$link['TITLE']['variables'] = array('assignment_id'=>'ASSIGNMENT_ID');
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&assignment_type_id=$_REQUEST[assignment_type_id]&assignment_id=new";
		$link['add']['first'] = 5; // number before add link moves to top

		ListOutput($assn_RET,$columns,'Assignment','Assignments',$link,array(),$LO_options);

		echo '</div>';
	}
}

function _makePercent($value,$column)
{
	return Percent($value,2);
}
?>