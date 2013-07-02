<?php
/*
	Send it an SQL Select Query Identifier and an optional functions array where the key is the
	column in the database that the function (contained in the value of the array) is applied.

	Use the second parameter (an array of functions indexed by the column to apply them to)
	if you need to do complicated formatting and don't want to loop through the
	array before sending it to ListOutput.  Use especially when expecting a large result.

	$THIS_RET is a useful variable for the functions in the second parameter.  It is the current row of the
	query result.

	Furthermore, the third parameter can be used to change the array index to a column in the
	result.  For instance, if you selected student_id from students, and chose to index by student_id,
	you would get a result similar to this :
	$array[1031806][1] = array('STUDENT_ID'=>'1031806');

	The third parameter should be an array -- ordered by the importance of the index.  So, if you select
	COURSE_ID,COURSE_PERIOD_ID from COURSE_PERIODS, and choose to index by
	array('COURSE_ID','COURSE_PERIOD_ID') then you will be returned an array formatted like this:
	$array[10101][402345][1] = array('COURSE_ID'=>'10101','COURSE_PERIOD_ID'=>'402345')
*/

function DBGet($QI,$functions=array(),$index=array())
{	global $THIS_RET;

	$index_count = count($index);
	$tmp_THIS_RET = $THIS_RET;

	$results = array();
	while($RET=db_fetch_row($QI))
	{
		$THIS_RET = $RET;

		if($index_count)
		{
			$ind = '';
			foreach($index as $col)
				$ind .= "['".str_replace("'","\'",$THIS_RET[$col])."']";
//			eval('$s'.$ind.'++;$this_ind=$s'.$ind.';');
			if (!eval('return isset($s'.$ind.');'))
				eval('$s'.$ind.'=1;');
			else
				eval('$s'.$ind.'++;');
			eval('$this_ind=$s'.$ind.';');
		}
		elseif (!isset($s))
			$s = 1;
		else
			$s++; // 1-based if no index specified
		foreach($RET as $key=>$value)
		{
//			if($functions[$key] && function_exists($functions[$key]))
			if(array_key_exists($key, $functions) && function_exists($functions[$key]))
			{
				if($index_count)
					eval('$results'.$ind.'[$this_ind][$key] = $functions[$key]($value,$key);');
				else
					$results[$s][$key] = $functions[$key]($value,$key);
			}
			else
			{
				if($index_count)
					eval('$results'.$ind.'[$this_ind][$key] = $value;');
				else
					$results[$s][$key] = $value;
			}
		}
	}

	$THIS_RET = $tmp_THIS_RET;

	return $results;
}
?>