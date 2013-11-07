<?php

$max_cols = 3;
$max_rows = 26;

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$extra['SELECT'] .= ",s.FIRST_NAME AS NICK_NAME";
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				if($_REQUEST['teacher'])
					$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS TEACHER";
				if($_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS ROOM";
			}
			else
			{
				if($_REQUEST['teacher'])
//modif Francois: multiple school periods for a course period
					$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE st.STAFF_ID=cp.TEACHER_ID AND cpsp.PERIOD_id=p.PERIOD_ID AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				if($_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cpsp.PERIOD_id=p.PERIOD_ID AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
			}
		}
		else
		{
			if($_REQUEST['teacher'])
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS TEACHER";
			if($_REQUEST['room'])
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS ROOM";
		}
		$RET = GetStuList($extra);

		if(count($RET))
		{
			$skipRET = array();
			for($i=($_REQUEST['start_row']-1)*$max_cols+$_REQUEST['start_col']; $i>1; $i--)
				$skipRET[-$i] = array('LAST_NAME'=>' ');

			$handle = PDFstart();
			echo '<table style="height: 100%" class="width-100p cellspacing-0 cellpadding-0">';

			$cols = 0;
			$rows = 0;
			foreach($skipRET+$RET as $i=>$student)
			{
				if($cols<1)
					echo '<tr>';
				echo '<td style="text-align:center; width:33%; vertical-align: middle;">';
				if($_REQUEST['full_name']=='given')
					$name = $student['LAST_NAME'].', '.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'];
				elseif($_REQUEST['full_name']=='given_natural')
					$name = $student['FIRST_NAME'].' '.$student['LAST_NAME'];
				else
					$name = $student['FULL_NAME'];
				echo '<B>'.$name.'</B>';
				if($_REQUEST['teacher'])
					echo '<BR />'.Localize('colon',_('Teacher')).'&nbsp;'.$student['TEACHER'];
				if($_REQUEST['room'])
					echo '<BR />'.Localize('colon',_('Room')).'&nbsp;'.$student['ROOM'];
				echo '</td>';

				$cols++;

				if($cols==$max_cols)
				{
					echo '</tr>';
					$rows++;
					$cols = 0;
				}

				if($rows==$max_rows)
				{
					echo '</table>';
					echo '<div style="page-break-after: always;"></div>';
					echo '<table style="height: 100%" class="width-100p cellspacing-0 cellpadding-0">';
					$rows = 0;
				}
			}

			if ($cols==0 && $rows==0)
			{}
			else
			{
				while ($cols!=0 && $cols<$max_cols)
				{
					echo '<td style="text-align:center; width:33%; vertical-align: middle; padding-bottom: 8px;">&nbsp;</td>';
					$cols++;
				}
				if ($cols==$max_cols)
					echo '</tr>';
				echo '</table>';
			}
			PDFstop($handle);
		}
		else
			BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_search_all_schools='.$_REQUEST['_search_all_schools'].(User('PROFILE')=='admin'?'&w_course_period_id_which='.$_REQUEST['w_course_period_id_which'].'&w_course_period_id='.$_REQUEST['w_course_period_id']:'').'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Labels for Selected Students').'" />';

		$extra['extra_header_left'] = '<TABLE>';

		$extra['extra_header_left'] .= '<TR><TD colspan="4"><b>'.Localize('colon',_('Include On Labels')).'</b></TD></TR>';
		$extra['extra_header_left'] .= '<TR class="st">';
//modif Francois: add <label> on radio
		$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="full_name" value="given" checked /> '._('Last, Given Middle').'</label></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="full_name" value="given_natural"> '._('Given Last').'</label></TD>';
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				$course_RET = DBGet(DBQuery("SELECT s.FIRST_NAME||' '||s.LAST_NAME AS TEACHER,cp.ROOM FROM STAFF s,COURSE_PERIODS cp WHERE s.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]'"));
//modif Francois: add <label> on checkbox
				$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="teacher" value="Y"> '._('Teacher').' ('.$course_RET[1]['TEACHER'].')</label></TD></TR>';
				$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="room" value="Y"> '._('Room').' ('.$course_RET[1]['ROOM'].')</label></TD></TR>';
			}
			else
			{
				$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="teacher" value="Y"> '._('Attendance Teacher').'</label></TD></TR>';
				$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="room" value="Y"> '._('Attendance Room').'</label></TD></TR>';
			}
		}
		else
		{
			$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="teacher" value="Y"> '._('Teacher').'</label></TD></TR>';
			$extra['extra_header_left'] .= '<TR><TD colspan="4"><label><INPUT type="checkbox" name="room" value="Y"> '._('Room').'</label></TD></TR>';
		}

		$extra['extra_header_left'] .= '</TABLE>';
		$extra['extra_header_right'] = '<TABLE>';

		$extra['extra_header_right'] .= '<TR class="st"><TD style="text-align:right">'._('Starting row').'</TD><TD><SELECT name="start_row">';
		for($row=1; $row<=$max_rows; $row++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$row.'">'.$row;
		$extra['extra_header_right'] .=  '</SELECT></TD></TR>';
		$extra['extra_header_right'] .= '<TR class="st"><TD style="text-align:right">'._('Starting column').'</TD><TD><SELECT name="start_col">';
		for($col=1; $col<=$max_cols; $col++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$col.'">'.$col;
		$extra['extra_header_right'] .= '</SELECT></TD></TR>';

		$extra['extra_header_right'] .= '</TABLE>';
	}

	Widgets('course');
	//Widgets('request');
	//Widgets('activity');
	//Widgets('absences');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');
	//Widgets('eligibility');
	//$extra['force_search'] = true;

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center"><INPUT type="submit" value="'._('Create Labels for Selected Students').'" /></span>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}
?>