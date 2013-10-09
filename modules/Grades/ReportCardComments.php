<?php
include 'modules/Grades/DeletePromptX.fnc.php';

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	if($_REQUEST['values'] && $_POST['values'])
	{
		if($_REQUEST['tab_id']!='')
		{
			if($_REQUEST['tab_id']=='new')
				$table = 'REPORT_CARD_COMMENT_CATEGORIES';
			else
				$table = 'REPORT_CARD_COMMENTS';
			foreach($_REQUEST['values'] as $id=>$columns)
			{
		//modif Francois: fix SQL bug invalid sort order
				if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
				{
					if($id!='new')
					{
						$sql = "UPDATE $table SET ";
						foreach($columns as $column=>$value)
							$sql .= $column."='".$value."',";

						$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
						DBQuery($sql);
					}
					else
					{
						$sql = "INSERT INTO $table ";
						$fields = "ID,SCHOOL_ID,SYEAR,COURSE_ID,".($_REQUEST['tab_id']=='new'?'':"CATEGORY_ID,");
						$values = db_seq_nextval($table.'_SEQ').",'".UserSchool()."','".UserSyear()."',".($_REQUEST['tab_id']=='new'?"$_REQUEST[course_id]":($_REQUEST['tab_id']=='-1'?"NULL,NULL":($_REQUEST['tab_id']=='0'?"'0',NULL":"'$_REQUEST[course_id]','$_REQUEST[tab_id]'"))).",";

						$go = false;
						foreach($columns as $column=>$value)
							if($value)
							{
								$fields .= $column.',';
								$values .= "'".$value."',";
								$go = true;
							}
						$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

						if($go)
							DBQuery($sql);
					}
				}
				else
					$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
			}
		}
	}
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if($_REQUEST['tab_id']=='new')
	{
//modif Francois: add translation
		if(DeletePromptX(_('Report Card Comment Category')))
		{
			DBQuery("DELETE FROM REPORT_CARD_COMMENTS WHERE CATEGORY_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM REPORT_CARD_COMMENT_CATEGORIES WHERE ID='$_REQUEST[id]'");
		}
	}
	elseif($_REQUEST['tab_id']=='-1')
	{
		if(DeletePromptX(_('Report Card Comment')))
		{
			DBQuery("DELETE FROM REPORT_CARD_COMMENTS WHERE ID='$_REQUEST[id]'");
		}
	}
	else
	{
		if(DeletePromptX(_('Report Card Comment')))
		{
			DBQuery("DELETE FROM REPORT_CARD_COMMENTS WHERE ID='$_REQUEST[id]'");
		}
	}
}

if(empty($_REQUEST['modfunc']))

{
	if(User('PROFILE')=='admin')
	{
		$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND (SELECT count(1) FROM COURSE_PERIODS WHERE SUBJECT_ID=COURSE_SUBJECTS.SUBJECT_ID AND GRADE_SCALE_ID IS NOT NULL)>0 ORDER BY SORT_ORDER,TITLE"),array(),array('SUBJECT_ID'));
		if(!$_REQUEST['subject_id'] || !$subjects_RET[$_REQUEST['subject_id']])
			$_REQUEST['subject_id'] = key($subjects_RET).'';
		$courses_RET = DBGet(DBQuery("SELECT COURSE_ID,TITLE FROM COURSES WHERE SUBJECT_ID='$_REQUEST[subject_id]' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND (SELECT count(1) FROM COURSE_PERIODS WHERE COURSE_ID=COURSES.COURSE_ID AND GRADE_SCALE_ID IS NOT NULL)>0 ORDER BY TITLE"),array(),array('COURSE_ID'));
		if(!$_REQUEST['course_id'] || !$courses_RET[$_REQUEST['course_id']])
			$_REQUEST['course_id'] = key($courses_RET).'';

		$subject_select = '<script type="text/javascript">var subject_idonchange = document.createElement("a"); subject_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&subject_id="; subject_idonchange.target = "body";</script>';
		$subject_select .= '<SELECT name="subject_id" onchange="subject_idonchange.href += this.options[selectedIndex].value; ajaxLink(subject_idonchange);">';
		foreach($subjects_RET as $id=>$subject)
			$subject_select .= '<OPTION value="'.$id.'"'.($_REQUEST['subject_id']==$id?' SELECTED="SELECTED"':'').'>'.$subject[1]['TITLE'].'</OPTION>';
		$subject_select .= '</SELECT>';
		$course_select = '<script type="text/javascript">var course_idonchange = document.createElement("a"); course_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id="; course_idonchange.target = "body";</script>';
		$course_select .= '<SELECT name="course_id" onchange="course_idonchange.href += this.options[selectedIndex].value; ajaxLink(course_idonchange);">';
		foreach($courses_RET as $id=>$course)
			$course_select .= '<OPTION value="'.$id.'"'.($_REQUEST['course_id']==$id?' SELECTED="SELECTED"':'').'>'.$course[1]['TITLE'].'</OPTION>';
		$course_select .= '</SELECT>';
	}
	else
	{
		$course_period_RET = DBGet(DBQuery('SELECT GRADE_SCALE_ID,DOES_BREAKOFF,TEACHER_ID,COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=\''.UserCoursePeriod().'\''));
		if(!$course_period_RET[1]['GRADE_SCALE_ID'])
			ErrorMessage(array(_('This course is not graded.')),'fatal');
		$subjects_RET = DBGet(DBQuery("SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='".$course_period_RET[1]['SUBJECT_ID']."'"));
		//modif Francois: add subject areas
		$courses_RET = DBGet(DBQuery("SELECT TITLE,SUBJECT_ID,(SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID=COURSES.SUBJECT_ID) AS SUBJECT FROM COURSES WHERE COURSE_ID='".$course_period_RET[1]['COURSE_ID']."'"), array('TITLE'=>'CourseTitle'));
		$_REQUEST['subject_id'] = $courses_RET[1]['SUBJECT_ID'];
		$_REQUEST['course_id'] = $course_period_RET[1]['COURSE_ID'];
		$subject_select = $courses_RET[1]['SUBJECT'];
		$course_select = $courses_RET[1]['TITLE'];
	}

	$categories_RET = DBGet(DBQuery("SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER,(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE COURSE_ID=rc.COURSE_ID AND CATEGORY_ID=rc.ID) AS COUNT FROM REPORT_CARD_COMMENT_CATEGORIES rc WHERE rc.COURSE_ID='$_REQUEST[course_id]'".
				" UNION SELECT 0,'"._('All Courses')."',NULL,2,NULL,(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND COURSE_ID='0' AND SYEAR='".UserSyear()."')".
				" UNION SELECT -1,'"._('General')."',NULL,3,NULL,(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND COURSE_ID IS NULL AND SYEAR='".UserSyear()."')".
				" ORDER BY 4,SORT_ORDER"),array(),array('ID'));
	if($_REQUEST['tab_id']=='' || $_REQUEST['tab_id']!='new' && !$categories_RET[$_REQUEST['tab_id']])
		//$_REQUEST['tab_id'] = key($categories_RET).'';
		$_REQUEST['tab_id'] = '-1'; //modif Francois: default to -1 (General)

	$tabs = array();
	foreach($categories_RET as $id=>$category)
	{
		if($category[1]['COUNT'] || AllowEdit())
		{
			$tabs[] = array('title'=>$category[1]['TITLE'],'link'=>'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id='.$id)+($category[1]['COLOR']?array('color'=>$category[1]['COLOR']):array());
			if($id>0)
				$category_select[$id] = $category[1]['TITLE'];
		}
	}

	if($_REQUEST['tab_id']=='new')
	{
		$sql = "SELECT * FROM REPORT_CARD_COMMENT_CATEGORIES WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER";
		$functions = array('TITLE'=>'makeTextInput','SORT_ORDER'=>'makeTextInput','COLOR'=>'makeColorInput');
		$LO_columns = array('TITLE'=>_('Comment Category'),'SORT_ORDER'=>_('Sort Order'),'COLOR'=>_('Color'));

		$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'),'COLOR'=>makeColorInput('','COLOR'));
		$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove&course_id='.$_REQUEST['course_id'].'&tab_id=new';
		$link['remove']['variables'] = array('id'=>'ID');
		$link['add']['html']['remove'] = button('add');

		$tabs[] = array('title'=>button('add','','',14),'link'=>'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id=new');
	}
	elseif($_REQUEST['tab_id']=='-1')
	{
		$sql = "SELECT * FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NULL ORDER BY SORT_ORDER";
		$functions = array('TITLE'=>'makeTextInput','SORT_ORDER'=>'makeTextInput');
		$LO_columns = array('TITLE'=>_('Comment'),'SORT_ORDER'=>_('Sort Order'));

		$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'));
		$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id=-1';
		$link['remove']['variables'] = array('id'=>'ID');
		$link['add']['html']['remove'] = button('add');

		if(User('PROFILE')=='admin')
			$tabs[] = array('title'=>button('add','','',14),'link'=>'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id=new');
	}
	else
	{
        $codes_RET = DBGet(DBQuery("SELECT ID,TITLE FROM REPORT_CARD_COMMENT_CODE_SCALES WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,TITLE"));
        $code_select = array(''=>_('N/A'));
        foreach($codes_RET as $code)
            $code_select[$code['ID']] = $code['TITLE'];

        $functions = array('TITLE'=>'makeCommentsInput','SCALE_ID'=>'makeCommentsInput','SORT_ORDER'=>'makeCommentsInput');
        $LO_columns = array('TITLE'=>_('Comment'),'SCALE_ID'=>_('Code Scale'),'SORT_ORDER'=>_('Sort Order'));
		if($_REQUEST['tab_id']=='0')
		{
			// need to be more specific since course_id=0 is not unique
            $sql = "SELECT * FROM REPORT_CARD_COMMENTS WHERE COURSE_ID='0' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,TITLE";
		}
		else
		{
            $sql = "SELECT * FROM REPORT_CARD_COMMENTS WHERE CATEGORY_ID='".$_REQUEST['tab_id']."' ORDER BY SORT_ORDER,TITLE";
			if(User('PROFILE')=='admin' && AllowEdit())
			{
				$functions += array('CATEGORY_ID'=>'makeCommentsInput');
				$LO_columns += array('CATEGORY_ID'=>_('Category'));
			}
		}
        
        $link['add']['html'] = array('TITLE'=>makeCommentsInput('','TITLE'),'SCALE_ID'=>makeCommentsInput('','SCALE_ID'),'SORT_ORDER'=>makeCommentsInput('','SORT_ORDER'));
		$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id='.$_REQUEST['tab_id'];
		$link['remove']['variables'] = array('id'=>'ID');
		$link['add']['html']['remove'] = button('add');

		if(User('PROFILE')=='admin')
			$tabs[] = array('title'=>button('add','','',14),'link'=>'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id=new');
	}
	$LO_ret = DBGet(DBQuery($sql),$functions);

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&course_id='.$_REQUEST['course_id'].'&tab_id='.$_REQUEST['tab_id'].'" method="POST">';
	DrawHeader($subject_select.' : '.$course_select,SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;

	$LO_options = array('save'=>false,'search'=>false,'header_color'=>$categories_RET[$_REQUEST['tab_id']][1]['COLOR'],
		'header'=>WrapTabs($tabs,'Modules.php?modname='.$_REQUEST['modname'].'&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&tab_id='.$_REQUEST['tab_id']));
	//ListOutput($LO_ret,$LO_columns,$singular,$plural,$link,array(),$LO_options);
	if($_REQUEST['tab_id']=='new')
		ListOutput($LO_ret,$LO_columns,'Category','Categories',$link,array(),$LO_options);
	else
		ListOutput($LO_ret,$LO_columns,'Comment','Comments',$link,array(),$LO_options);

	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	return TextInput($value,"values[$id][$name]",'',$extra);
}

function makeCommentsInput($value,$name)
{	global $THIS_RET,$category_select,$code_select;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='CATEGORY_ID')
		return SelectInput($value,"values[$id][$name]",'',$category_select,false);
    elseif($name=='SCALE_ID')
        return SelectInput($value,"values[$id][$name]",'',$code_select,false);
	elseif($name=='SORT_ORDER')
		$extra = 'size=5 maxlength=5';

	return TextInput($value,"values[$id][$name]",'',$extra);
}

function makeColorInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	$colors = array('#330099','#3366FF','#003333','#FF3300','#660000','#666666','#333366','#336633','purple','teal','firebrick','tan');
	foreach($colors as $color)
	{
		$color_select[$color] = array('<span style="background-color:'.$color.'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>','<span style="background-color:'.$color.';">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>');
	}
	return RadioInput($value,"values[$id][$name]",'',$color_select);
}
?>
