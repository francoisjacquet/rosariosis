<?php

$max_cols = 3;
$max_rows = 15;

if ( $_REQUEST['modfunc'] === 'save' )
{
	if (count($_REQUEST['st_arr']))
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		if ( User( 'PROFILE' ) === 'admin' )
		{
			if ( $_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				if ( $_REQUEST['teacher'] )
				{
					$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
					FROM STAFF st,COURSE_PERIODS cp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "') AS TEACHER";
				}

				if ( $_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."') AS ROOM";
			}
			else
			{
				if ( $_REQUEST['teacher'] )
				{
					// FJ multiple school periods for a course period.
					$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
					FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss, COURSE_PERIOD_SCHOOL_PERIODS cpsp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cpsp.PERIOD_id=p.PERIOD_ID
					AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
					AND p.ATTENDANCE='Y'
					AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
					AND ss.STUDENT_ID=s.STUDENT_ID
					AND ss.SYEAR='" . UserSyear() . "'
					AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ). ")
					AND (ss.START_DATE<='" . DBDate() . "'
						AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
					ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				}

				if ( $_REQUEST['room'])
					$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss, COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cpsp.PERIOD_id=p.PERIOD_ID AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
			}
		}
		else
		{
			if ( $_REQUEST['teacher'] )
			{
				$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . " AS FULL_NAME
					FROM STAFF st,COURSE_PERIODS cp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS TEACHER";
			}

			if ( $_REQUEST['room'])
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS ROOM";
		}
		$RET = GetStuList($extra);

		if (count($RET))
		{
			$skipRET = array();
			for ( $i=($_REQUEST['start_row']-1)*$max_cols+$_REQUEST['start_col']; $i>1; $i--)
				$skipRET[-$i] = array('LAST_NAME' => '&nbsp;');

			$handle = PDFstart();
			echo '<table style="height: 100%" class="width-100p cellspacing-0">';

			$cols = $rows = 0;

			foreach ( (array) $skipRET + $RET as $i => $student )
			{
				if ( $cols < 1 )
				{
					echo '<tr>';
				}

				echo '<td class="center" style="width:33%; vertical-align: middle;">';

				echo '<b>' . $student['FULL_NAME'] . '</b>';

				if ( $_REQUEST['teacher'] )
				{
					if ( $student['TEACHER'] )
					{
						echo '<br />' . _( 'Teacher' ) . ':&nbsp;' . $student['TEACHER'];
					}
					else
					{
						echo '<br />&nbsp;';
					}
				}

				if ( $_REQUEST['room'] )
				{
					if ( $student['ROOM'] )
					{
						echo '<br />' . _( 'Room' ) . ':&nbsp;' . $student['ROOM'];
					}
					else
					{
						echo '<br />&nbsp;';
					}
				}

				echo '</td>';

				$cols++;

				if ( $cols==$max_cols)
				{
					echo '</tr><tr><td clospan="'.$max_cols.'">&nbsp;</td></tr>';
					$rows++;
					$cols = 0;
				}

				if ( $rows==$max_rows)
				{
					echo '</table>';
					echo '<div style="page-break-after: always;"></div>';
					echo '<table style="height: 100%" class="width-100p cellspacing-0">';
					$rows = 0;
				}
			}

			if ( $cols==0 && $rows==0)
			{}
			else
			{
				while ($cols!=0 && $cols<$max_cols)
				{
					echo '<td class="center" style="width:33%; vertical-align: middle; padding-bottom: 8px;">&nbsp;</td>';
					$cols++;
				}
				if ( $cols==$max_cols)
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

if ( ! $_REQUEST['modfunc'] )

{
	DrawHeader(ProgramTitle());

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_search_all_schools='.$_REQUEST['_search_all_schools'].(User('PROFILE')=='admin'?'&w_course_period_id_which='.$_REQUEST['w_course_period_id_which'].'&w_course_period_id='.$_REQUEST['w_course_period_id']:'').'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = '<input type="submit" value="'._('Create Labels for Selected Students').'" />';

		$extra['extra_header_left'] = '<table>';

		$extra['extra_header_left'] .= '<tr><td colspan="4"><b>'._('Include On Labels').':</b></td></tr>';
		$extra['extra_header_left'] .= '<tr class="st">';

		if (User('PROFILE')=='admin')
		{
			if ( $_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				$course_RET = DBGet( DBQuery( "SELECT " . DisplayNameSQL( 's' ) . " AS TEACHER,cp.ROOM
				FROM STAFF s,COURSE_PERIODS cp
				WHERE s.STAFF_ID=cp.TEACHER_ID
				AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'" ) );

				$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="teacher" value="Y"> '._('Teacher').' ('.$course_RET[1]['TEACHER'].')</label></td></tr>';
				$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="room" value="Y"> '._('Room').' ('.$course_RET[1]['ROOM'].')</label></td></tr>';
			}
			else
			{
				$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="teacher" value="Y"> '._('Attendance Teacher').'</label></td></tr>';
				$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="room" value="Y"> '._('Attendance Room').'</label></td></tr>';
			}
		}
		else
		{
			$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="teacher" value="Y"> '._('Teacher').'</label></td></tr>';
			$extra['extra_header_left'] .= '<tr><td colspan="4"><label><input type="checkbox" name="room" value="Y"> '._('Room').'</label></td></tr>';
		}

		$extra['extra_header_left'] .= '</table>';

		$extra['extra_header_right'] = '<table class="col1-align-right">';

		$extra['extra_header_right'] .= '<tr class="st"><td>'._('Starting row').'</td><td><select name="start_row">';
		for ( $row=1; $row<=$max_rows; $row++)
			$extra['extra_header_right'] .=  '<option value="'.$row.'">'.$row;
		$extra['extra_header_right'] .=  '</select></td></tr>';
		$extra['extra_header_right'] .= '<tr class="st"><td>'._('Starting column').'</td><td><select name="start_col">';
		for ( $col=1; $col<=$max_cols; $col++)
			$extra['extra_header_right'] .=  '<option value="'.$col.'">'.$col;
		$extra['extra_header_right'] .= '</select></td></tr>';

		$extra['extra_header_right'] .= '</table>';
	}

	Widgets('course');
	//Widgets('request');
	//Widgets('activity');
	//Widgets('absences');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');
	//Widgets('eligibility');

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center"><input type="submit" value="'._('Create Labels for Selected Students').'" /></div>';
		echo '</form>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<input type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}
