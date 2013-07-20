<?php
/*
	-- The list of available fields matched to a student ID - $fields
	The field array ($fields) is a multidimensional array where the first key is the
	name of the table that holds the field, and the second key is the column name itself.
	$fields['TABLE_NAME']['COLUMN_NAME'] should equal the displayable name of the column.

	Take Note that the column list can be limited by profile, simply by leaving them out of
	the array.

	Also, notice the columm names can contain db_case and concatanation (||).
	If you use this, you must use TABLE_NAME.COLUMN_NAME, ex:
		$fields['TABLE_NAME']['TABLE_NAME.COL1||TABLE_NAME.COL2 as COL']

	-- The organization of the fields by tables is done in the $org array
	Simply add your table to the $org array (using the category name as the key to $org),
	and columns in that table will be listed in that category. Ex:
		$org['Category'] = array('TABLE1','TABLE2')
	Every table must be in a category to be listed
	For the sake of making things easier, keep the STUDENTS table the first one listed
	in the first org category.

	-- For tables that have more than one record per student, you must use the multidimensional $limit
	array which uses the table name as the first key, column name to limit on as the second, and
	value to limit by as the value. Ex:
		$limit[TABLE_NAME][SYEAR] = '03'
	You can limit by as many columns on a table as you choose.

	-- DBGet Functions array $functions
	Defines a function for each column to replace using GetCapWords() for each item returned in DBReturn
	It is defined in the same way that it always is for DBGet, so it should be familiar.

	While not required for any column, it is highly reccommended that this be used. Any field left out of this
	array will be returned as it is in the DB.

	if you want to define a custom array, place it after the definitions of the setup arrays, but before the main body
	of the program.  It would probably be better, though, to put it in the functions dir
*/
$sysyear = UserSyear();

$fields['STUDENTS'] = array('FIRST_NAME'=>_('First Name'),'LAST_NAME'=>_('Last Name'),'MIDDLE_NAME'=>_('Middle Name'),
							'CURRENT_SCHOOL'=>_('Current School'),'PREVIOUS_SCHOOL'=>_('Previous School'),'NEXT_SCHOOL'=>_('Next School'),
							'BIRTH_DATE'=>_('Birthdate'),'BIRTH_PLACE'=>_('Birth Place')
						 );

/*
$fields[AS_ISAT] = array('READ_SCALE_SCORE'=>'Scaled Reading Score','MATH_SCALE_SCORE'=>'Scaled Math Score');
$fields[AS_ITBS] = array('READ_SCALE_SCORE'=>'Scaled Reading Score','MATH_SCALE_SCORE'=>'Scaled Math Score');
*/

$cust_RET = DBGet(DBQuery("SELECT TITLE,'CUSTOM_'||ID as COLUMN_NAME FROM CUSTOM_FIELDS"),array('TITLE'=>'GetCapWords'));
if(count($cust_RET))
{
	foreach($cust_RET as $cust)
		$fields['CUSTOM'][$cust['COLUMN_NAME']] = $cust['TITLE'];
}

$org['Student Info'] = array('STUDENTS');
//$org['Custom Info'] = array('CUSTOM');
//$org['Assesment Info'] = array('AS_ISAT','AS_ITBS');

//$limit[AS_ISAT][YEAR] = '2001';
//$limit[AS_ISAT][YEAR] = '00';
$limit['STUDENT_ENROLLMENT']['SYEAR'] = $sysyear;
$functions = array('FIRST_NAME'=>'GetCapWords','LAST_NAME'=>'GetCapWords','MIDDLE_NAME'=>'GetCapWords',
					'GRADE_ID'=>'GetGrade',
					'SCHOOL'=>'GetSchool','PREVIOUS_SCHOOL'=>'GetSchool','NEXT_SCHOOL'=>'GetSchool','CURRENT_SCHOOL'=>'GetSchool',
					'ENROLL_DATE'=>'DBDateConv','BIRTH_DATE'=>'DBDateConv');

// -------------------------------- END SETUP --------------------------------- \\
$modfunc = $_REQUEST['modfunc'];
if($modfunc=='')
	$modfunc = 'find';

if($modfunc=='list')
{
	$field_list = $_REQUEST['field_list'];

	$i=2;
	if(count($field_list))
	{
		foreach($field_list as $table_name=>$column_list)
		{
			// PRODUCE FROM AND WHERE LISTS
			if($table_name!='STUDENTS')
				$from .= ",$table_name a$i";
			else
				$i=1;
			$tables[$i] = 'a'.$i;
			for($j=1;$j<$i;$j++)
				$where .= "AND a$j.STUDENT_ID=a$i.STUDENT_ID ";
			if(count($limit[$table_name]))
			{
				foreach($limit[$table_name] as $column_name=>$value)
					$where .= "AND a$i.$column_name='$value' ";
			}

			// PRODUCE SELECT LIST
			if(count($column_list))
			{
				foreach($column_list as $column_name=>$on)
				{
					$select .= ",a$i.$column_name";
					$LO_columns[$column_name] = $fields[$table_name][$column_name];
					$LO_functions[$column_name] = $functions[$column_name];
				}
			}

			$i++;
		}
	}
	$select = 'a1.STUDENT_ID'.$select;
	$where = mb_substr($where,4);
	$from = 'STUDENTS a1'.$from;

	if(trim($where)=='')
		$where .= ' 1=1 ';
	if($_REQUEST['last'])
		$where .= "AND a1.LAST_NAME LIKE '".mb_strtoupper($_REQUEST['last'])."%'";
	if($_REQUEST['first'])
		$where .= "AND a1.FIRST_NAME LIKE '".mb_strtoupper($_REQUEST['first'])."%'";
	if($_REQUEST['stuid'])
		$where .= "AND a1.STUDENT_ID = '".$_REQUEST['stuid']."'";


	// CONSTRUCT SQL
	$sql = "SELECT $select FROM $from WHERE $where ORDER BY a1.STUDENT_ID";
	$QI = DBQuery($sql);
	$RET = DBGet($QI,$LO_functions);

	$_REQUEST['modfunc'] = 'list';
	ListOutput($RET,$LO_columns,'Student','Students');
}

if($modfunc=='find')
{
	PopTable('header',_('Find a Student'));
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=list" METHOD="POST">';
	echo '<b>'.Localize('colon',_('Search Criteria')).'</b>';
	Warehouse('searchstu');
	echo '</TABLE>';
	echo '<HR>';
	echo '<b>'.Localize('colon',_('List')).'</B>';
	echo '<TABLE><TR><TD>';

	foreach($org as $cat_name=>$tables)
	{
		echo '<TABLE class="cellpadding-5"><TR><TD colspan="7" style="text-align:left;"><b>'.$cat_name.'</b></TD></TR>';
		echo '<TR><TD></TD>';
		$col = 1;
		foreach($tables as $table_name)
		{
			if(count($fields[$table_name]))
			{
				foreach($fields[$table_name] as $column_name=>$column_disp)
				{
//modif Francois: add <label> on checkbox
					echo '<TD><label><INPUT type="checkbox" name="field_list['.$table_name.']['.$column_name.']"></TD><TD>'.$column_disp.'</label></TD>';
					$col++;
					if($col==4)
					{
						echo '</TR><TR><TD style="width:10px;">&nbsp;</TD>';
						$col=1;
					}
				}
			}
		}
		if($col==1)
			echo '<TD></TD><TD></TD><TD></TD><TD></TD><TD></TD><TD></TD></TR></TABLE>';
		elseif($col==2)
			echo '<TD></TD><TD></TD><TD></TD><TD></TD></TR></TABLE>';
		elseif($col==3)
			echo '<TD></TD><TD></TD></TR></TABLE>';
	}
	echo '</TD></TR></TABLE>';
	echo '<span class="center">';
	echo Buttons(_('Submit'),_('Reset'));
	echo '</span>';
	echo '</FORM>';
	PopTable('footer');
}

?>
