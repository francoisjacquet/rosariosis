<?php

if ( isset( $_POST['day_values'] )
	&& isset( $_POST['month_values'] )
	&& isset( $_POST['year_values'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['day_values'],
		$_REQUEST['month_values'],
		$_REQUEST['year_values']
	);

	$_REQUEST['values'] = array_replace_recursive( $_REQUEST['values'], $requested_dates );

	$_POST['values'] = array_replace_recursive( $_POST['values'], $requested_dates );
}

if ( isset( $_POST['values'] )
	&& count( $_POST['values'] )
	&& AllowEdit() )
{
	foreach ( (array)$_REQUEST['values'] as $id => $columns)
	{	
		if ( $id!='new')
		{
			$sql = "UPDATE ELIGIBILITY_ACTIVITIES SET ";
							
			foreach ( (array)$columns as $column => $value)
			{
				$sql .= $column."='".$value."',";
			}
			$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
			DBQuery($sql);
		}
		else
		{
			$sql = "INSERT INTO ELIGIBILITY_ACTIVITIES ";

			$fields = 'ID,SCHOOL_ID,SYEAR,';
			$values = db_seq_nextval('ELIGIBILITY_ACTIVITIES_SEQ').",'".UserSchool()."','".UserSyear()."',";

			$go = 0;
			foreach ( (array)$columns as $column => $value)
			{
				if ( !empty($value) || $value=='0')
				{
					$fields .= $column.',';
					$values .= "'".$value."',";
					$go = true;
				}
			}
			$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			
			if ( $go)
				DBQuery($sql);
		}
	}
}

DrawHeader(ProgramTitle());

if ( $_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if (DeletePrompt(_('Activity')))
	{
		DBQuery("DELETE FROM ELIGIBILITY_ACTIVITIES WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if ( $_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,TITLE,START_DATE,END_DATE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY TITLE";
	$QI = DBQuery($sql);
	$activities_RET = DBGet($QI,array('TITLE' => 'makeTextInput','START_DATE' => 'makeDateInput','END_DATE' => 'makeDateInput'));
	
	$columns = array('TITLE' => _('Title'),'START_DATE' => _('Begins'),'END_DATE' => _('Ends'));
	$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'START_DATE'=>makeDateInput('','START_DATE'),'END_DATE'=>makeDateInput('','END_DATE'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
	$link['remove']['variables'] = array('id' => 'ID');
	
	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
	ListOutput($activities_RET,$columns,'Activity','Activities',$link);
	echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '</form>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;
	
	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	return TextInput($value,'values['.$id.']['.$name.']');
}

function makeDateInput($value,$name)
{	global $THIS_RET;
	
	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	return DateInput($value,'values['.$id.']['.$name.']');
}

