<?php

function DeCodeds($value,$column)
{	global $_ROSARIO;

	$field = explode('_',$column);

	if(!$_ROSARIO['DeCodeds'][$column])
	{
		$RET = DBGet(DBQuery("SELECT TYPE,SELECT_OPTIONS FROM $field[0]_FIELDS WHERE ID='$field[1]'"));
		if($RET[1]['TYPE']=='codeds' || $RET[1]['TYPE']=='exports')
		{
			$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$RET[1]['SELECT_OPTIONS']));
			$select_options = explode("\r",$select_options);
			foreach($select_options as $option)
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			$RET[1]['SELECT_OPTIONS'] = $options;
			$_ROSARIO['DeCodeds'][$column] = $RET[1];
		}
		else
			$_ROSARIO['DeCodeds'][$column] = true;
	}

	if($_ROSARIO['DeCodeds'][$column]['TYPE']=='codeds')
	{
	if($value!='')
		if($_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_ROSARIO_PDF'] && $_REQUEST['LO_save'] && Preferences('E_CODEDS')=='Y')
				return $value;
			else
				return $_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
		else
			return '<span style="color:red">'.$value.'</span>';
	else
		return '';
	}
	elseif($_ROSARIO['DeCodeds'][$column]['TYPE']=='exports')
	{
	if($value!='')
	{
		if($_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_ROSARIO_PDF'] && $_REQUEST['LO_save'] && Preferences('E_EXPORTS')!='Y')
				return $_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
			else
				return $value;
		else
			return '<span style="color:red">'.$value.'</span>';
	}
	else
		return '';
	}
}

function StaffDeCodeds($value,$column)
{	global $_ROSARIO;

	$field = explode('_',$column);

	if(!$_ROSARIO['DeCodeds'][$column])
	{
		$RET = DBGet(DBQuery("SELECT TYPE,SELECT_OPTIONS FROM STAFF_FIELDS WHERE ID='$field[1]'"));
		if($RET[1]['TYPE']=='codeds' || $RET[1]['TYPE']=='exports')
		{
			$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$RET[1]['SELECT_OPTIONS']));
			$select_options = explode("\r",$select_options);
			foreach($select_options as $option)
			{
				$option = explode('|',$option);
				if($option[0]!='' && $option[1]!='')
					$options[$option[0]] = $option[1];
			}
			$RET[1]['SELECT_OPTIONS'] = $options;
			$_ROSARIO['DeCodeds'][$column] = $RET[1];
		}
		else
			$_ROSARIO['DeCodeds'][$column] = true;
	}

	if($_ROSARIO['DeCodeds'][$column]['TYPE']=='codeds')
	{
	if($value!='')
		if($_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			return $_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
		else
			return '<span style="color:red">'.$value.'</span>';
	else
		return '';
	}
	elseif($_ROSARIO['DeCodeds'][$column]['TYPE']=='exports')
	{
	if($value!='')
	{
		if($_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value]!='')
			if($_REQUEST['_ROSARIO_PDF'] && $_REQUEST['LO_save'] && Preferences('E_EXPORTS')!='Y')
				return $_ROSARIO['DeCodeds'][$column]['SELECT_OPTIONS'][$value];
			else
				return $value;
		else
			return '<span style="color:red">'.$value.'</span>';
	}
	else
		return '';
	}
}
?>
