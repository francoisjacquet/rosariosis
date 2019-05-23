<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$date = RequestedDate( 'include_active_date', '' );

		if ( $date )
		{
			$date_extra = "OR ('" . $date . "'>=sr.START_DATE
			AND sr.END_DATE IS NULL)";
		}
		else
		{
			$date = DBDate();

			$date_extra = 'OR sr.END_DATE IS NULL';
		}

		// FJ multiple school periods for a course period.
		//$columns = array('PERIOD_TITLE' => _('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'),'MARKING_PERIOD_ID' => _('Term'),'DAYS' => _('Days'),'ROOM' => _('Room'),'COURSE_TITLE' => _('Course'));
		$columns = array(
			'PERIOD_TITLE' => _( 'Period' ) . ' ' . _( 'Days' ) . ' - ' . _( 'Short Name' ) . ' - ' . _( 'Teacher' ),
			'MARKING_PERIOD_ID' => _( 'Term' ),
			'ROOM' => _( 'Room' ),
			'COURSE_TITLE' => _( 'Course' ),
		);

		/*	$extra['SELECT'] .= ',c.TITLE AS COURSE_TITLE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID,p_cp.DAYS,p_cp.ROOM';
		$extra['FROM'] .= ' LEFT OUTER JOIN SCHEDULE sr ON (sr.STUDENT_ID=ssm.STUDENT_ID),COURSES c,COURSE_PERIODS p_cp,SCHOOL_PERIODS sp ';
		$extra['WHERE'] .= " AND p_cp.PERIOD_ID=sp.PERIOD_ID AND ssm.SYEAR=sr.SYEAR AND sr.COURSE_ID=c.COURSE_ID AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID  AND ('".$date."' BETWEEN sr.START_DATE AND sr.END_DATE $date_extra)";*/
		$extra['SELECT'] .= ',c.TITLE AS COURSE_TITLE,p_cp.TITLE AS PERIOD_TITLE,sr.MARKING_PERIOD_ID,p_cp.ROOM';
		$extra['FROM'] .= ' LEFT OUTER JOIN SCHEDULE sr ON (sr.STUDENT_ID=ssm.STUDENT_ID),COURSES c,COURSE_PERIODS p_cp ';
		$extra['WHERE'] .= " AND ssm.SYEAR=sr.SYEAR
		AND sr.COURSE_ID=c.COURSE_ID
		AND sr.COURSE_PERIOD_ID=p_cp.COURSE_PERIOD_ID
		AND ('" . $date . "' BETWEEN sr.START_DATE AND sr.END_DATE " . $date_extra . ")";

		if ( ! empty( $_REQUEST['mp_id'] ) )
		{
			$extra['WHERE'] .= ' AND sr.MARKING_PERIOD_ID IN (' . GetAllMP(
				GetMP( $_REQUEST['mp_id'], 'MP' ),
				$_REQUEST['mp_id']
			) . ')';
		}

//	$extra['functions'] = array('MARKING_PERIOD_ID' => 'GetMP','DAYS' => '_makeDays');
		//FJ add subject areas
		$extra['functions'] = array( 'MARKING_PERIOD_ID' => 'GetMP' );
		$extra['group'] = array( 'STUDENT_ID' );
//	$extra['ORDER'] = ',sp.SORT_ORDER';

		if ( $_REQUEST['mailing_labels'] == 'Y' )
		{
			$extra['group'][] = 'ADDRESS_ID';
		}

		Widgets( 'mailing_labels' );

		// Parent: associated students.
		$extra['ASSOCIATED'] = User( 'STAFF_ID' );

		$RET = GetStuList( $extra );

		// FJ add schedule table.
		$schedule_table_days = array(
			'U' => false,
			'M' => false,
			'T' => false,
			'W' => false,
			'H' => false,
			'F' => false,
			'S' => false,
		);

		// FJ days display to locale.
		$days_convert = array(
			'U' => _( 'Sunday' ),
			'M' => _( 'Monday' ),
			'T' => _( 'Tuesday' ),
			'W' => _( 'Wednesday' ),
			'H' => _( 'Thursday' ),
			'F' => _( 'Friday' ),
			'S' => _( 'Saturday' ),
		);

		// FJ days numbered.

		if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
		{
			$days_convert = array(
				'U' => _( 'Day' ) . ' 7',
				'M' => _( 'Day' ) . ' 1',
				'T' => _( 'Day' ) . ' 2',
				'W' => _( 'Day' ) . ' 3',
				'H' => _( 'Day' ) . ' 4',
				'F' => _( 'Day' ) . ' 5',
				'S' => _( 'Day' ) . ' 6',
			);
		}

		$schedule_table_RET = DBGet( "SELECT cp.ROOM,cs.TITLE,sp.TITLE AS SCHOOL_PERIOD,
		cpsp.DAYS,stu.STUDENT_ID," . DisplayNameSQL( 'sta' ) . " AS FULL_NAME
	FROM COURSE_PERIODS cp,COURSES c,SCHOOLS s,SCHOOL_PERIODS sp,
		COURSE_PERIOD_SCHOOL_PERIODS cpsp,STUDENTS stu,SCHEDULE sr,STAFF sta,COURSE_SUBJECTS cs
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND c.SUBJECT_ID=cs.SUBJECT_ID
	AND cp.SYEAR='" . UserSyear() . "'
	AND s.ID=cp.SCHOOL_ID
	AND s.ID='" . UserSchool() . "'
	AND s.SYEAR=cp.SYEAR
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND sr.MARKING_PERIOD_ID IN (" . GetAllMP( GetMP( $_REQUEST['mp_id'], 'MP' ), $_REQUEST['mp_id'] ) . ")
	AND stu.STUDENT_ID IN (" . $st_list . ")
	AND stu.STUDENT_ID=sr.STUDENT_ID
	AND cp.SCHOOL_ID=sr.SCHOOL_ID
	AND cp.TEACHER_ID=sta.STAFF_ID
	AND sr.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND ('" . $date . "' BETWEEN sr.START_DATE AND sr.END_DATE " . $date_extra . ")
	AND sp.LENGTH <= " . ( Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) / 2 ) ."
	ORDER BY sp.SORT_ORDER", array( 'DAYS' => '_GetDays' ), array( 'STUDENT_ID', 'SCHOOL_PERIOD' ) );
		// FJ note the "sp.LENGTH <= (Config('ATTENDANCE_FULL_DAY_MINUTES') / 2)" condition
		// to remove Full Day and Half Day school periods from the schedule table!

		$columns_table = array( 'SCHOOL_PERIOD' => _( 'Periods' ) );

		foreach ( $schedule_table_days as $day => $true )
		{
			if ( $true )
			{
				$columns_table[$day] = $days_convert[$day];
			}
		}

		if ( ! empty( $RET ) )
		{
			$handle = PDFStart();

			if ( $_REQUEST['schedule_table'] === 'No' )
			{
				foreach ( (array) $RET as $student_id => $courses )
				{
					if ( $_REQUEST['mailing_labels'] == 'Y' )
					{
						foreach ( (array) $courses as $address )
						{
							echo '<br /><br /><br />';
							unset( $_ROSARIO['DrawHeader'] );
							DrawHeader( _( 'Student Schedule' ) );
							DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( $date ) );
							DrawHeader( $address[1]['FULL_NAME'], $address[1]['STUDENT_ID'] );
							DrawHeader( $address[1]['GRADE_ID'], $_REQUEST['mp_id'] ? GetMP( $_REQUEST['mp_id'] ) : '' );

							echo '<br /><br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $address[1]['MAILING_LABEL'] . '</td></tr></table><br />';

							ListOutput( $address, $columns, 'Course', 'Courses', array(), array(), array( 'center' => false, 'print' => false ) );
							echo '<div style="page-break-after: always;"></div>';
						}
					}
					else
					{
						//FJ add Horizontal format option

						if ( isset( $_REQUEST['horizontalFormat'] ) )
						{
							//echo '<!-- MEDIA SIZE 8.5x11in -->';
							$_SESSION['orientation'] = 'landscape';
						}

						unset( $_ROSARIO['DrawHeader'] );
						DrawHeader( _( 'Student Schedule' ) );
						DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( $date ) );
						DrawHeader( $courses[1]['FULL_NAME'], $courses[1]['STUDENT_ID'] );
						DrawHeader( $courses[1]['GRADE_ID'], $_REQUEST['mp_id'] ? GetMP( $_REQUEST['mp_id'] ) : '' );

						ListOutput( $courses, $columns, 'Course', 'Courses', array(), array(), array( 'center' => false, 'print' => false ) );
						echo '<div style="page-break-after: always;"></div>';
					}
				}
			}

			// FJ add schedule table.

			if ( $_REQUEST['schedule_table'] == 'Yes' )
			{
				if ( ! $schedule_table_RET )
				{
					$error[] = sprintf( _( 'No %s were found.' ), ngettext( 'Course Period', 'Course Periods', 0 ) );

					echo ErrorMessage( $error );

					$note[] = sprintf(
						_( 'Only course periods with a length <= %d minutes (full school day in minutes divided by 2) are shown in the table schedule.' ),
						Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) / 2
					);

					echo ErrorMessage( $note, 'note' );
				}

				foreach ( (array) $schedule_table_RET as $student_id => $schedule_table )
				{
					/*foreach ( (array) $schedule_table as $period => $course_periods)
					{
					$schedule_table_body .= '<tr><td>'.$period.'</td>';

					$schedule_table_TDs = $schedule_table_TDs_empty;
					foreach ($course_periods as $course_period)
					{
					foreach ($course_period['DAYS'] as $course_period_day)
					{
					if ( !is_array($schedule_table_TDs[ $course_period_day ]))
					$schedule_table_TDs[ $course_period_day ] = array();
					$schedule_table_TDs[ $course_period_day ][] = '<td>'.$course_period['TITLE'].'<br />'.$course_period['FULL_NAME'].(empty($course_period['ROOM'])?'':'<br />'._('Room').': '.$course_period['ROOM']).'</td>';
					}
					}
					foreach ($schedule_table_TDs as $schedule_table_TD)
					{
					if (is_array($schedule_table_TD))
					if (count($schedule_table_TD) == 1)
					$schedule_table_body .= $schedule_table_TD[0];
					else
					$schedule_table_body .= '<td><table><tr>'.implode($schedule_table_TD).'</tr></table></td>';
					else
					$schedule_table_body .= $schedule_table_TD;
					}
					$schedule_table_body .= '</tr>';
					}
					$schedule_table_body .= '</table>';*/

					if ( $_REQUEST['mailing_labels'] == 'Y' && isset( $RET[$student_id] ) )
					{
						foreach ( (array) $RET[$student_id] as $address )
						{
							echo '<br /><br /><br />';
							unset( $_ROSARIO['DrawHeader'] );
							DrawHeader( _( 'Student Schedule' ) );
							DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( $date ) );
							DrawHeader( $address[1]['FULL_NAME'], $address[1]['STUDENT_ID'] );
							DrawHeader( $address[1]['GRADE_ID'], $_REQUEST['mp_id'] ? GetMP( $_REQUEST['mp_id'] ) : '' );

							echo '<br /><br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $address[1]['MAILING_LABEL'] . '</td></tr></table><br />';

							$schedule_table = _schedule_table_RET( $schedule_table );

							ListOutput( $schedule_table, $columns_table, 'Period', 'Periods', false, array() );
						}
					}
					else
					{
						//FJ add Horizontal format option

						if ( isset( $_REQUEST['horizontalFormat'] ) )
						{
							//echo '<!-- MEDIA SIZE 8.5x11in -->';
							$_SESSION['orientation'] = 'landscape';
						}

						unset( $_ROSARIO['DrawHeader'] );
						DrawHeader( _( 'Student Schedule' ) );
						DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( $date ) );
						DrawHeader( $RET[$student_id][1]['FULL_NAME'], $RET[$student_id][1]['STUDENT_ID'] );
						DrawHeader( $RET[$student_id][1]['GRADE_ID'], $_REQUEST['mp_id'] ? GetMP( $_REQUEST['mp_id'] ) : '' );

						$schedule_table = _schedule_table_RET( $schedule_table );

						ListOutput( $schedule_table, $columns_table, 'Period', 'Periods', false, array() );
					}

					echo '<div style="page-break-after: always;"></div>';
				}
			}

			PDFStop( $handle );
		}
		else
		{
			BackPrompt( _( 'No Students were found.' ) );
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE," . db_case( array( 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ) ) . " AS TBL
			FROM SCHOOL_MARKING_PERIODS
			WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TBL,SORT_ORDER" );

		$mp_select = '<select name="mp_id" id="mp_id"><option value="">' . _( 'N/A' );

		foreach ( (array) $mp_RET as $mp )
		{
			$mp_select .= '<option value="' . $mp['MARKING_PERIOD_ID'] . '">' . $mp['TITLE'];
		}

		$mp_select .= '</select>';

		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '&_ROSARIO_PDF=true" method="POST" id="printSchedulesForm">';

		$extra['header_right'] = Buttons( _( 'Create Schedules for Selected Students' ) );

		$extra['extra_header_left'] = '<table class="cellpadding-5 col1-align-right">';
		$extra['extra_header_left'] .= '<tr class="st"><td><label for="mp_id">' . _( 'Marking Period' ) .
			'</label></td><td>' . $mp_select . '</td></tr>';
		$extra['extra_header_left'] .= '<tr class="st"><td>' . _( 'Include only courses active as of' ) .
		'</td><td>' . PrepareDate( '', '_include_active_date' ) . '</td></tr>';

		//FJ add Horizontal format option
		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		'<label for="horizontalFormat" class="nobr">' . _( 'Horizontal Format' ) . '</label></td>
			<td><input type="checkbox" id="horizontalFormat" name="horizontalFormat" value="Y" />' .
			'</td></tr>';

		//FJ add schedule table
		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		'<label><input name="schedule_table" type="radio" value="Yes" checked />&nbsp;' . _( 'Table' ) . '</label></td>
			<td><label><input name="schedule_table" type="radio" value="No" />&nbsp;' . _( 'List' ) . '</label>' .
			'</td></tr>';

		Widgets( 'mailing_labels' );
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '</table>';
	}

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );
	$extra['options']['search'] = false;
	$extra['new'] = true;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'request' );
	Widgets( 'course' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Schedules for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

//FJ add schedule table
/**
 * @param $value
 * @param $column
 * @return mixed
 */
function _GetDays( $value, $column )
{
	global $schedule_table_days;

	$days_array = str_split( $value );

	foreach ( $days_array as $index => $day )
	{
		$schedule_table_days[$day] = true;
	}

	return $days_array;
}

/**
 * @param $schedule_table_RET
 * @return mixed
 */
function _schedule_table_RET( $schedule_table_RET )
{
	$schedule_table_body = array();
	$i = 1;

	foreach ( (array) $schedule_table_RET as $period => $course_periods )
	{
		$schedule_table_body[$i]['SCHOOL_PERIOD'] = $period;

		foreach ( $course_periods as $course_period )
		{
			foreach ( $course_period['DAYS'] as $course_period_day )
			{
				if ( ! isset( $schedule_table_body[$i][$course_period_day] ) || ! is_array( $schedule_table_body[$i][$course_period_day] ) )
				{
					$schedule_table_body[$i][$course_period_day] = array();
				}

				$schedule_table_body[$i][$course_period_day][] = '<td>' . $course_period['TITLE'] . '<br />' . $course_period['FULL_NAME'] . ( empty( $course_period['ROOM'] ) ? '' : '<br />' . _( 'Room' ) . ': ' . $course_period['ROOM'] ) . '</td>';
			}
		}

		$j = 0;

		foreach ( $schedule_table_body[$i] as $day_key => $schedule_table_day )
		{
			$j++;

			if ( $j == 1 ) // skip SCHOOL_PERIOD column
			{
				continue;
			}

			if ( count( (array) $schedule_table_day ) == 1 )
			{
				$schedule_table_body[$i][$day_key] = str_replace( array( '<td>', '</td>' ), '', $schedule_table_day[0] );
			}
			else
			{
				$schedule_table_body[$i][$day_key] = '<table><tr>' . implode( $schedule_table_day ) . '</tr></table>';
			}
		}

		$i++;
	}

	return $schedule_table_body;
}
