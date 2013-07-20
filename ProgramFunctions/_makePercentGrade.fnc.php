<?php
function _makePercentGrade($grade_id,$course_period_id=0,$staff_id=0)
{	global $programconfig,$_ROSARIO;

	if(!$course_period_id)
		$course_period_id = UserCoursePeriod();

	if(!$staff_id)
		$staff_id = User('STAFF_ID');

	if(!$programconfig[$staff_id])
	{
        $config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".$staff_id."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
		if(count($config_RET))
			foreach($config_RET as $title=>$value)
				$programconfig[$staff_id][$title] = $value[1]['VALUE'];
		else
			$programconfig[$staff_id] = true;
	}
	if(!$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id])
		$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet(DBQuery("SELECT DOES_BREAKOFF,GRADE_SCALE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$course_period_id."'"));
	$does_breakoff = $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id][1]['DOES_BREAKOFF'];
	$grade_scale_id = $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id][1]['GRADE_SCALE_ID'];

	if(!$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id])
		$_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] = DBGet(DBQuery("SELECT TITLE,ID,BREAK_OFF FROM REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND GRADE_SCALE_ID='$grade_scale_id' ORDER BY BREAK_OFF IS NOT NULL DESC,BREAK_OFF DESC,SORT_ORDER"));
	//$grades = array('A+','A','A-','B+','B','B-','C+','C','C-','D+','D','D-','F');

	foreach($_ROSARIO['_makeLetterGrade']['grades'][$grade_scale_id] as $grade)
	{
		$prev = $crnt;
		$crnt = ($does_breakoff=='Y' ? $programconfig[$staff_id][$course_period_id.'-'.$grade['ID']] : $grade['BREAK_OFF']);
		if(is_numeric($grade_id) ? $grade_id==$grade['ID'] : mb_strtoupper($grade_id)==mb_strtoupper($grade['TITLE']))
			return ($crnt + ($crnt>$prev ? 100 : $prev)) / 2;
	}
	return 0;
}
?>
