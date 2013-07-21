<?php

// INSERT/UPDATE,[fields][values]/WHERE,[]fields/,Propernames
function SaveData($iu_extra,$fields_done=false,$field_names=false)
{
	if(!$fields_done)
		$fields_done = array();
	if(!$field_names)
		$field_names = array();

	if($_REQUEST['month_values'])
	{
		foreach($_REQUEST['month_values'] as $table=>$values)
		{
			foreach($values as $id=>$columns)
			{
				foreach($columns as $column=>$value)
				{
					$_REQUEST['values'][$table][$id][$column] = $_REQUEST['day_values'][$table][$id][$column].'-'.$value.'-'.$_REQUEST['year_values'][$table][$id][$column];
					//modif Francois: bugfix SQL bug when incomplete or non-existent date
					//if($_REQUEST['values'][$table][$id][$column]=='--')
					if(mb_strlen($_REQUEST['values'][$table][$id][$column]) < 11)
						$_REQUEST['values'][$table][$id][$column] = '';
					else
					{
						while(!VerifyDate($_REQUEST['values'][$table][$id][$column]))
						{
							$_REQUEST['day_values'][$table][$id][$column]--;
							$_REQUEST['values'][$table][$id][$column] = $_REQUEST['day_values'][$table][$id][$column].'-'.$value.'-'.$_REQUEST['year_values'][$table][$id][$column];
						}
					}
				}
			}
		}
	}
	foreach($_REQUEST['values'] as $table=>$values)
	{
		$table_properties = db_properties($table);
		foreach($values as $id=>$columns)
		{
			foreach($columns as $column=>$value)
			{
				if($field_names[$table][$column])
					$name = sprintf(_('The value for %s'),$field_names[$table][$column]);
				else
					$name = sprintf(_('The value for %s'),ucwords(mb_strtolower(str_replace('_',' ',$column))));

				// COLUMN DOESN'T EXIST
				if(!$table_properties[$column])
				{
					$error[] = sprintf(_('There is no column for %s. This value was not saved.'), $name);
					continue;
				}

				// VALUE IS TOO LONG
				if($table_properties[$column]['TYPE']=='VARCHAR' && mb_strlen($value) > $table_properties[$column]['SIZE'])
				{
					$value = mb_substr($value,0,$table_properties[$column]['SIZE']);
					$error[] = sprintf(_('%s was too long. It was truncated to fit in the field.'), $name);
				}

				// FIELD IS NUMERIC, VALUE CONTAINS NON-NUMERICAL CHARACTERS
				if($table_properties[$column]['TYPE']=='NUMERIC' && preg_match('/[^0-9-]/',$value))
				{
					$value = preg_replace('/[^0-9]/','',$value);
					$error[] = sprintf(_('%s, a numerical field, contained non-numerical characters. These characters were removed.'), $name);
				}

				// FIELD IS DATE, DATE IS WRONG
				if($table_properties[$column]['TYPE']=='DATE' && $value && !VerifyDate($value))
				{
					$error[] = sprintf(_('%s, a date field, was not a valid date. This value could not be saved.'), $name);
					continue;
				}
				if($id=='new')
				{
					if($value)
					{
						$ins_fields[$table] .= $column.',';
						$ins_values[$table] .= "'".$value."',";
						$go = true;
					}
				}
				else
					$sql[$table] .= "$column='".str_replace('&#39;',"''",$value)."',";
			}
			if($id=='new')
				$sql[$table] = 'INSERT INTO '.$table.' (' . $iu_extra['fields'][$table].mb_substr($ins_fields[$table],0,-1) . ') values(' . $iu_extra['values'][$table].mb_substr($ins_values[$table],0,-1) . ')';
			else
				$sql[$table] = 'UPDATE '.$table.' SET '.mb_substr($sql[$table],0,-1).' WHERE '.str_replace('__ID__',$id,$iu_extra[$table]);

			echo ErrorMessage($error);
			if($id!='new' || $go==true)
				DBQuery($sql[$table]);
			$error = $ins_fields = $ins_values = $sql = $go = '';
		}
	}
}
?>