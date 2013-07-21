<?php
if($_REQUEST['values'] && $_POST['values'])
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if($id!='new')
			{
				$sql = "UPDATE SCHOOL_GRADELEVELS SET ";
								
				foreach($columns as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
				DBQuery($sql);
			}
			else
			{
				$sql = "INSERT INTO SCHOOL_GRADELEVELS ";

				$fields = 'ID,SCHOOL_ID,';
				$values = db_seq_nextval('SCHOOL_GRADELEVELS_SEQ').",'".UserSchool()."',";

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
				
				if($go)
					DBQuery($sql);
			}
		}
		else
			$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
	}
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove')
{
	if(DeletePrompt(_('Grade Level')))
	{
		DBQuery("DELETE FROM SCHOOL_GRADELEVELS WHERE ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER";
	$QI = DBQuery($sql);
	$grades_RET = DBGet($QI,array('TITLE'=>'makeTextInput','SHORT_NAME'=>'makeTextInput','SORT_ORDER'=>'makeTextInput','NEXT_GRADE_ID'=>'makeGradeInput'));
	
	$columns = array('TITLE'=>_('Title'),'SHORT_NAME'=>_('Short Name'),'SORT_ORDER'=>_('Sort Order'),'NEXT_GRADE_ID'=>_('Next Grade'));
	$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'SHORT_NAME'=>makeTextInput('','SHORT_NAME'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'),'NEXT_GRADE_ID'=>makeGradeInput('','NEXT_GRADE_ID'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'ID');
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	ListOutput($grades_RET,$columns,'Grade Level','Grade Levels',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if($name!='TITLE')
		$extra = 'size=5 maxlength=2';
	if($name=='SORT_ORDER')
		$comment = '<!-- '.$value.' -->';

	return $comment.TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function makeGradeInput($value,$name)
{	global $THIS_RET,$grades;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
		
	if(!$grades)
	{
		$grades_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		if(count($grades_RET))
		{
			foreach($grades_RET as $grade)
				$grades[$grade['ID']] = $grade['TITLE'];
		}
	}
	
	return SelectInput($value,'values['.$id.']['.$name.']','',$grades,_('N/A'));
}
?>