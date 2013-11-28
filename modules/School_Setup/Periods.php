<?php
if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach($_REQUEST['values'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid numeric data
		if ((empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER'])) && (empty($columns['LENGTH']) || is_numeric($columns['LENGTH'])))
		{
			if($columns['START_TIME_HOUR']!='' && $columns['START_TIME_MINUTE'] && $columns['START_TIME_M'])
			{
				$columns['START_TIME'] = $columns['START_TIME_HOUR'].':'.$columns['START_TIME_MINUTE'].' '.$columns['START_TIME_M'];
			}
			unset($columns['START_TIME_HOUR']);unset($columns['START_TIME_MINUTE']);unset($columns['START_TIME_M']);
			if($columns['END_TIME_HOUR']!='' && $columns['END_TIME_MINUTE'] && $columns['END_TIME_M'])
			{
				$columns['END_TIME'] = $columns['END_TIME_HOUR'].':'.$columns['END_TIME_MINUTE'].' '.$columns['END_TIME_M'];
			}
			unset($columns['END_TIME_HOUR']);unset($columns['END_TIME_MINUTE']);unset($columns['END_TIME_M']);

			if($id!='new')
			{
				$sql = "UPDATE SCHOOL_PERIODS SET ";

				foreach($columns as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE PERIOD_ID='$id'";
				DBQuery($sql);
			}
			else
			{
				$sql = "INSERT INTO SCHOOL_PERIODS ";

				$fields = 'PERIOD_ID,SCHOOL_ID,SYEAR,';
				$values = db_seq_nextval('SCHOOL_PERIODS_SEQ').",'".UserSchool()."','".UserSyear()."',";

				$go = false;
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
			$error = ErrorMessage(array(_('Please enter valid Numeric data.')));
	}
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('Period')))
	{
		DBQuery("DELETE FROM SCHOOL_PERIODS WHERE PERIOD_ID='$_REQUEST[id]'");
		unset($_REQUEST['modfunc']);
	}
}

if($_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT PERIOD_ID,TITLE,SHORT_NAME,SORT_ORDER,LENGTH,START_TIME,END_TIME,BLOCK,ATTENDANCE FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER";
	$QI = DBQuery($sql);
	$periods_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','SHORT_NAME'=>'_makeTextInput','SORT_ORDER'=>'_makeTextInput','BLOCK'=>'_makeTextInput','LENGTH'=>'_makeTextInput','START_TIME'=>'_makeTimeInput','END_TIME'=>'_makeTimeInput','ATTENDANCE'=>'_makeCheckboxInput'));

	$columns = array('TITLE'=>_('Title'),'SHORT_NAME'=>_('Short Name'),'SORT_ORDER'=>_('Sort Order'),'LENGTH'=>_('Length (minutes)'),'BLOCK'=>_('Block'),'ATTENDANCE'=>_('Used for Attendance')); //,'START_TIME'=>_('Start Time'),'END_TIME'=>_('End Time'));
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'SHORT_NAME'=>_makeTextInput('','SHORT_NAME'),'LENGTH'=>_makeTextInput('','LENGTH'),'SORT_ORDER'=>_makeTextInput('','SORT_ORDER'),'BLOCK'=>_makeTextInput('','BLOCK'),'START_TIME'=>_makeTimeInput('','START_TIME'),'END_TIME'=>_makeTimeInput('','END_TIME'),'ATTENDANCE'=>_makeCheckboxInput('','ATTENDANCE'));
	$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
	$link['remove']['variables'] = array('id'=>'PERIOD_ID');

	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
//modif Francois: fix SQL bug invalid numeric data
	if(isset($error)) echo $error;
	ListOutput($periods_RET,$columns,'Period','Periods',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['PERIOD_ID'])
		$id = $THIS_RET['PERIOD_ID'];
	else
		$id = 'new';

	if($name!='TITLE')
		$extra = 'size=5 maxlength=10';

	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makeCheckboxInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['PERIOD_ID'])
		$id = $THIS_RET['PERIOD_ID'];
	else
		$id = 'new';

	return CheckboxInput($value,'values['.$id.']['.$name.']','','',($id=='new'),'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">');
}

function _makeTimeInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['PERIOD_ID'])
		$id = $THIS_RET['PERIOD_ID'];
	else
		$id = 'new';

	$hour = mb_substr($value,0,mb_strpos($value,':'));
	$minute = mb_substr($value,mb_strpos($value,':'),mb_strpos($value,' '));
	$m = mb_substr($value,mb_strpos($value,' '));

	for($i=1;$i<=11;$i++)
		$hour_options[$i] = ''.$i;
	$hour_options['0'] = '12';

	for($i=0;$i<=9;$i++)
		$minute_options['0'.$i] = '0'.$i;
	for($i=10;$i<=59;$i++)
		$minute_options[$i] = ''.$i;

	$m_options = array('AM'=>'AM','PM'=>'PM');

    if($id!='new' && $value)
	{
		$return = '<DIV id='.$name.$id.'><div class="onclick" onclick=\'addHTML("';
		
        $toEscape = '<TABLE><TR><TD>'.SelectInput($hour,'values['.$id.']['.$name.'_HOUR]','',$hour_options,_('N/A'),'',false).':</TD><TD>'.SelectInput($minute,'values['.$id.']['.$name.'_MINUTE]','',$minute_options,_('N/A'),'',false).'</TD><TD>'.SelectInput($m,'values['.$id.']['.$name.'_M]','',$m_options,_('N/A'),'',false).'</TD></TR></TABLE>';
		$return .= str_replace('"','\"',$toEscape);
		
		$return .= '","'.$name.$id.'",true);\'>'.'<span class="underline-dots">'.$value.'</span></div></DIV>';
		return $return;
	}
    else
        return '<TABLE><TR><TD>'.SelectInput($hour,'values['.$id.']['.$name.'_HOUR]','',$hour_options,_('N/A'),'',false).':</TD><TD>'.SelectInput($minute,'values['.$id.']['.$name.'_MINUTE]','',$minute_options,_('N/A'),'',false).'</TD><TD>'.SelectInput($m,'values['.$id.']['.$name.'_M]','',$m_options,_('N/A'),'',false).'</TD></TR></TABLE>';
}
?>