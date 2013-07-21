<?php
if($_REQUEST['month_values'] && $_POST['month_values'])
{
	foreach($_REQUEST['month_values'] as $id=>$columns)
	{
		foreach($columns as $column=>$value)
		{
			$_REQUEST['values'][$id][$column] = $_REQUEST['day_values'][$id][$column].'-'.$value.'-'.$_REQUEST['year_values'][$id][$column];
			//modif Francois: bugfix SQL bug when incomplete or non-existent date
			//if($_REQUEST['values'][$id][$column]=='--')
			if(mb_strlen($_REQUEST['values'][$id][$column]) < 11)
				$_REQUEST['values'][$id][$column] = '';
			else
			{
				while(!VerifyDate($_REQUEST['values'][$id][$column]))
				{
					$_REQUEST['day_values'][$id][$column]--;
					$_REQUEST['values'][$id][$column] = $_REQUEST['day_values'][$id][$column].'-'.$value.'-'.$_REQUEST['year_values'][$id][$column];
				}
			}
		}
	}
	$_POST['values'] = $_REQUEST['values'];
}

if($_REQUEST['values'] && $_POST['values'])
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{	
		if($id!='new')
		{
			$sql = "UPDATE ELIGIBILITY_ACTIVITIES SET ";
							
			foreach($columns as $column=>$value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
			DBQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO ELIGIBILITY_ACTIVITIES ";

			$fields = 'ID,SCHOOL_ID,SYEAR,';
			$values = db_seq_nextval('ELIGIBILITY_ACTIVITIES_SEQ').",'".UserSchool()."','".UserSyear()."',";

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
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove')
{
	if(DeletePrompt(_('Activity')))
	{
		DBQuery("DELETE FROM ELIGIBILITY_ACTIVITIES WHERE ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,TITLE,START_DATE,END_DATE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY TITLE";
	$QI = DBQuery($sql);
	$activities_RET = DBGet($QI,array('TITLE'=>'makeTextInput','START_DATE'=>'makeDateInput','END_DATE'=>'makeDateInput'));
	
	$columns = array('TITLE'=>_('Title'),'START_DATE'=>_('Begins'),'END_DATE'=>_('Ends'));
	$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'START_DATE'=>makeDateInput('','START_DATE'),'END_DATE'=>makeDateInput('','END_DATE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'ID');
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
	ListOutput($activities_RET,$columns,'Activity','Activities',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	return TextInput($value,'values['.$id.']['.$name.']');
}

function makeDateInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	return DateInput($value,'values['.$id.']['.$name.']');
}

?>