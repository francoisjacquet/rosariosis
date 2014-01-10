<?php
// OTHER INFO
function _makeTextInput($column,$name,$size,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<span class="legend-red">','</span>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<span class="legend-red">','</span>') : array('','');
	}

	if($field['TYPE']=='numeric')
		$value[$column] = str_replace('.00','',$value[$column]);

//modif Francois: text field is required
	//return TextInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size,$div);
//modif Francois: text field maxlength=255
	return TextInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size.' maxlength=255'.($field['REQUIRED']=='Y' ? ' required': ''),$div);
}

function _makeDateInput($column,$name,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<span class="legend-red">','</span>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<span class="legend-red">','</span>') : array('','');
	}

	return DateInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$div);
}

//modif Francois: display age next to birthdate
function _makeStudentAge($column,$name)
{	global $value;

	if($_REQUEST['student_id']!='new' && date_create($value[$column]))
	{
		$datetime1 = date_create($value[$column]);
		$datetime2 = date_create('now');
		$interval = date_diff($datetime1, $datetime2);
		return '</TD><TD>'.$interval->format('%Y&nbsp;'._('Years').'&nbsp;%m&nbsp;'._('Months').'&nbsp;%d&nbsp;'._('Days')).'<BR /><span class="legend-gray">'.$name.'</span>';
	}
	else
		return '';
}

function _makeSelectInput($column,$name,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<span class="legend-red">','</span>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && $value[$column]=='' ? array('<span class="legend-red">','</span>') : array('','');
	}

	$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
	$select_options = explode("\r",$field['SELECT_OPTIONS']);
	if(count($select_options))
	{
		foreach($select_options as $option)
			if($field['TYPE']=='codeds')
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			elseif($field['TYPE']=='exports')
			{
				$option = explode('|',$option);
				if($option[0]!='')
					$options[$option[0]] = $option[0];
			}
			else
				$options[$option] = $option;
	}

	$extra = 'style="max-width:250;"';
	return SelectInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$options,_('N/A'),$extra,$div);
}

function _makeAutoSelectInput($column,$name,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
		$req = $field['REQUIRED']=='Y' ? array('<span class="legend-red">','</span>') : array('','');
	}
	else
	{
		$div = true;
		$req = $field['REQUIRED']=='Y' && ($value[$column]=='' || $value[$column]=='---') ? array('<span class="legend-red">','</span>') : array('','');
	}

	// build the select list...
	// get the standard selects
	if($field['SELECT_OPTIONS'])
	{
		$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
		$select_options = explode("\r",$field['SELECT_OPTIONS']);
	}
	else
		$select_options = array();
	if(count($select_options))
	{
		foreach($select_options as $option)
			if($option!='')
				$options[$option] = $option;
	}
	// add the 'new' option, is also the separator
//modif Francois: new option
//	$options['---'] = '---';
	$options['---'] = '-'. _('Edit') .'-';

	if($field['TYPE']=='autos' && AllowEdit()) // we don't really need the select list if we can't edit anyway
	{
		// add values found in current and previous year
		if($request=='values[ADDRESS]')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT a.CUSTOM_$field[ID],upper(a.CUSTOM_$field[ID]) AS SORT_KEY FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS s,STUDENT_ENROLLMENT sse WHERE a.ADDRESS_ID=sja.ADDRESS_ID AND s.STUDENT_ID=sja.STUDENT_ID AND sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND a.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		elseif($request=='values[PEOPLE]')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT p.CUSTOM_$field[ID],upper(p.CUSTOM_$field[ID]) AS SORT_KEY FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp,STUDENTS s,STUDENT_ENROLLMENT sse WHERE p.PERSON_ID=sjp.PERSON_ID AND s.STUDENT_ID=sjp.STUDENT_ID AND sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND p.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		elseif($request=='students')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT s.CUSTOM_$field[ID],upper(s.CUSTOM_$field[ID]) AS SORT_KEY FROM STUDENTS s,STUDENT_ENROLLMENT sse WHERE sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND s.CUSTOM_$field[ID] IS NOT NULL ORDER BY SORT_KEY"));
		elseif($request=='staff')
			$options_RET = DBGet(DBQuery("SELECT DISTINCT s.CUSTOM_$field[ID],upper(s.CUSTOM_$field[ID]) AS KEY FROM STAFF s WHERE (s.SYEAR='".UserSyear()."' OR s.SYEAR='".(UserSyear()-1)."') AND s.CUSTOM_$field[ID] IS NOT NULL ORDER BY KEY"));
		if(count($options_RET))
		{
			foreach($options_RET as $option)
				if($option['CUSTOM_'.$field['ID']]!='' && !$options[$option['CUSTOM_'.$field['ID']]])
					$options[$option['CUSTOM_'.$field['ID']]] = array($option['CUSTOM_'.$field['ID']],'<span style="color:blue">'.$option['CUSTOM_'.$field['ID']].'</span>');
		}
	}
	// make sure the current value is in the list
	if($value[$column]!='' && !$options[$value[$column]])
		$options[$value[$column]] = array($value[$column],'<span style="color:'.($field['TYPE']=='autos'?'blue':'green').'">'.$value[$column].'</span>');

	if($value[$column]!='---' && count($options)>1)
	{
		$extra = 'style="max-width:250;"';
		return SelectInput($value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$options,_('N/A'),$extra,$div);
	}
	else
//modif Francois: new option
//		return TextInput($value[$column]=='---'?array('---','<span style="color:red">---</span>'):''.$value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size,$div);
		return TextInput($value[$column]=='---'?array('---','<span style="color:red">-'. _('Edit') .'-</span>'):''.$value[$column],$request.'['.$column.']',$req[0].$name.$req[1],$size,$div);
}

function _makeCheckboxInput($column,$name,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
	}
	else
		$div = true;

	return CheckboxInput($value[$column],$request.'['.$column.']',$name,'',($_REQUEST['student_id']=='new'));
}

function _makeTextareaInput($column,$name,$request)
{	global $value,$field;

	if($_REQUEST['student_id']=='new' && $field['DEFAULT_SELECTION'])
	{
		$value[$column] = $field['DEFAULT_SELECTION'];
		$div = false;
	}
	else
		$div = true;

//modif Francois: text area is required
//modif Francois: textarea field maxlength=5000
	return TextAreaInput($value[$column],$request.'['.$column.']',$name,'maxlength=5000'.($field['REQUIRED']=='Y' ? ' required': ''),$div);
}

function _makeMultipleInput($column,$name,$request)
{	global $value,$field,$_ROSARIO;

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		$field['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$field['SELECT_OPTIONS']));
		$select_options = explode("\r",$field['SELECT_OPTIONS']);
		if(count($select_options))
		{
			foreach($select_options as $option)
				$options[$option] = $option;
		}

		$escape_array = array('&#39;','&quot;');
		if($value[$column]!='')
		{
			$return = '<DIV id="div'.$request.'['.$column.']"><div class="onclick" onclick=\'javascript:addHTML(html'.$request.$column;
			$escape_array = array('&#39;','');
		}
		
		$table = '<TABLE class="cellpadding-3">';
		if(count($options)>12)
		{
			$table .= '<TR><TD colspan="2">';
			$table .= '<span class="legend-gray">'.($value[$column]!=''?str_replace("'",'&#39;',$name):$name).'</span>';
			$table .= '<TABLE class="width-100p" style="height: 7px; border:1;border-style: solid solid none solid;"><TR><TD></TD></TR></TABLE>';
			$table .= '</TD></TR>';
		}
		$table .= '<TR>';
		$i = 0;
		foreach($options as $option)
		{
			if($i%2==0)
				$table .= '</TR><TR>';
//modif Francois: add <label> on checkbox
			$table .= '<TD><label><INPUT type="checkbox" name="'.$request.'['.$column.'][]" value="'.htmlspecialchars($option).'"'.(mb_strpos($value[$column],'||'.$option.'||')!==false?' checked':'').'> '.str_replace("'",'&#39;',$option).'</label></TD>';
			$i++;
		}
		$table .= '</TR><TR><TD colspan="2">';
//modif Francois: fix bug none selected not saved
		$table .= '<INPUT type="hidden" name="'.$request.'['.$column.'][none]" value="">';
		$table .= '<TABLE class="width-100p" style="height:7px; border:1; border-style:none solid solid solid;"><TR><TD></TD></TR></TABLE>';

		$table .= '</TD></TR></TABLE>';
		if($value[$column]!='')
		{
			echo '<script type="text/javascript">var html'.$request.$column.'=\''.$table.'\';</script>'.$return;
			echo ',"div'.$request.'['.$column.']",true);\' >';
			echo '<span class="underline-dots">'.($value[$column]!=''?str_replace('||',', ',mb_substr($value[$column],2,-2)):'-').'</span>';
			echo '</div></DIV>';
		}
		else
			echo $table;
	}
	else
		echo ($value[$column]!=''?str_replace('||',', ',mb_substr($value[$column],2,-2)):'-').'<BR />';

	echo '<span class="legend-gray">'.$name.'</span>';
}

// MEDICAL ----
function _makeType($value,$column)
{	global $THIS_RET;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return SelectInput($value,'values[STUDENT_MEDICAL]['.$THIS_RET['ID'].'][TYPE]','',array('Immunization'=>_('Immunization'),'Physical'=>_('Physical')));
}

function _makeDate($value,$column='MEDICAL_DATE')
{	global $THIS_RET,$table;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return DateInput($value,'values['.$table.']['.$THIS_RET['ID'].']['.$column.']');
}

function _makeComments($value,$column)
{	global $THIS_RET,$table;

	if(!$THIS_RET['ID'])
		$THIS_RET['ID'] = 'new';

	return TextInput($value,'values['.$table.']['.$THIS_RET['ID'].']['.$column.']');
}

// ENROLLMENT
function _makeStartInput($value,$column)
{	global $THIS_RET,$add_codes;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	elseif($_REQUEST['student_id']=='new')
	{
		$id = 'new';
		$default = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS START_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$default = $default[1]['START_DATE'];
		if(!$default || DBDate('postgres')>$default)
			$default = DBDate();
		$value = $default;
	}
	else
	{
		$add = button('add').' ';
		$id = 'new';
	}

	if(!$add_codes)
	{
		$options_RET = DBGet(DBQuery("SELECT ID,TITLE AS TITLE FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' AND TYPE='Add' ORDER BY SORT_ORDER"));

		if($options_RET)
		{
			foreach($options_RET as $option)
				$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	if($_REQUEST['student_id']=='new')
		$div = false;
	else
		$div = true;

//modif Francois: remove LO_field
	return '<div class="nobr">'.$add.DateInput($value,'values[STUDENT_ENROLLMENT]['.$id.']['.$column.']','',$div,true).' - '.SelectInput($THIS_RET['ENROLLMENT_CODE'],'values[STUDENT_ENROLLMENT]['.$id.'][ENROLLMENT_CODE]','',$add_codes,_('N/A'),'style="max-width:150px;"').'</div>';
}

function _makeEndInput($value,$column)
{	global $THIS_RET,$drop_codes;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if(!$drop_codes)
	{
		$options_RET = DBGet(DBQuery("SELECT ID,TITLE AS TITLE FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' AND TYPE='Drop' ORDER BY SORT_ORDER"));

		if($options_RET)
		{
			foreach($options_RET as $option)
				$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	return '<div class="nobr">'.DateInput($value,'values[STUDENT_ENROLLMENT]['.$id.']['.$column.']').' - '.SelectInput($THIS_RET['DROP_CODE'],'values[STUDENT_ENROLLMENT]['.$id.'][DROP_CODE]','',$drop_codes,_(_('N/A')),'style="max-width:150px;"').'</div>';
}

function _makeSchoolInput($value,$column)
{	global $THIS_RET,$schools;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if(!$schools)
		$schools = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."'"),array(),array('ID'));

	foreach($schools as $sid=>$school)
		$options[$sid] = $school[1]['TITLE'];

	// mab - allow school to be editted if illegal value
	if($_REQUEST['student_id']!='new')
		if($id!='new')
			if($schools[$value])
				return $schools[$value][1]['TITLE'];
			else
				return SelectInput($value,'values[STUDENT_ENROLLMENT]['.$id.'][SCHOOL_ID]','',$options);
		else
			return SelectInput(UserSchool(),'values[STUDENT_ENROLLMENT]['.$id.'][SCHOOL_ID]','',$options,false,'',false);
	else
		return $schools[UserSchool()][1]['TITLE'];
}
?>