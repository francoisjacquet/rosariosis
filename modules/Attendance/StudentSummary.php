<?php
DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

//if (User( 'PROFILE' ) === 'teacher')
//	$_REQUEST['period_id'] = UserPeriod();

//FJ bugfix bug when Back to Student Search

if ( $_REQUEST['search_modfunc'] || $_REQUEST['student_id'] || User( 'PROFILE' ) === 'parent' || User( 'PROFILE' ) === 'student' )
{
	$period_select = '';

	if ( ! UserStudentID() && ! $_REQUEST['student_id'] )
	{
		//FJ multiple school periods for a course period
		//$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE FROM SCHOOL_PERIODS sp WHERE sp.SYEAR='".UserSyear()."' AND sp.SCHOOL_ID='".UserSchool()."' AND EXISTS(SELECT '' FROM COURSE_PERIODS cp WHERE cp.PERIOD_ID=sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0".(User( 'PROFILE' ) === 'teacher'?" AND cp.PERIOD_ID='".UserPeriod()."'":'').") ORDER BY sp.SORT_ORDER" );
		$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE
		FROM SCHOOL_PERIODS sp
		WHERE sp.SYEAR='" . UserSyear() . "'
		AND sp.SCHOOL_ID='" . UserSchool() . "'
		AND EXISTS
			(SELECT ''
			FROM COURSE_PERIODS cp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
			WHERE  cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND cpsp.PERIOD_ID=sp.PERIOD_ID
			AND position(',0,' IN cp.DOES_ATTENDANCE)>0
			" . ( User( 'PROFILE' ) === 'teacher' ? " AND cp.COURSE_PERIOD_ID IN (SELECT COURSE_PERIOD_ID FROM COURSE_PERIOD_SCHOOL_PERIODS WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='" . UserCoursePeriodSchoolPeriod() . "')" : '' ) . ")
		ORDER BY sp.SORT_ORDER" );

		$period_select = '<select name="period_id" id="period_id" onchange="ajaxPostForm(this.form,true);">
			<option value="">' . _( 'Daily' ) . '</option>';

		if ( ! empty( $periods_RET ) )
		{
			//FJ All periods

			if ( count( $periods_RET ) > 1 )
			{
				$period_select .= '<option value="all"' . (  ( $_REQUEST['period_id'] == 'all' ) ? ' selected' : '' ) . '>' . _( 'All Periods' ) . '</option>';
			}

			foreach ( (array) $periods_RET as $period )
			{
				$period_select .= '<option value="' . $period['PERIOD_ID'] . '"' . (  ( $_REQUEST['period_id'] == $period['PERIOD_ID'] ) ? ' selected' : '' ) . '>' . $period['TITLE'] . '</option>';
			}
		}

		$period_select .= '</select>
			<label for="period_id" class="a11y-hidden">' . _( 'Periods' ) . '</label>';
	}

	echo '<form action="' . PreparePHP_SELF() . '" method="GET">';

	DrawHeader( _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) .
		( $period_select ? ' : ' . $period_select : '' ) .
		' : ' . Buttons( _( 'Go' ) )
	);

	echo '</form>';
}

if ( ! empty( $_REQUEST['period_id'] ) )
{
	//FJ All periods

	if ( $_REQUEST['period_id'] == 'all' )
	{
		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$period_ids_RET = DBGet( "SELECT PERIOD_ID
				FROM COURSE_PERIOD_SCHOOL_PERIODS
				WHERE COURSE_PERIOD_ID IN (SELECT COURSE_PERIOD_ID
					FROM COURSE_PERIOD_SCHOOL_PERIODS
					WHERE COURSE_PERIOD_SCHOOL_PERIODS_ID='" . UserCoursePeriodSchoolPeriod() . "')" );
		}
		else
		{
			$period_ids_RET = DBGet( "SELECT PERIOD_ID
				FROM SCHOOL_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" );
		}

		$period_ids_list = array();

		foreach ( (array) $period_ids_RET as $period_id )
		{
			$period_ids_list[] = $period_id['PERIOD_ID'];
		}

		$period_ids_list = implode( ',', $period_ids_list );
	}
	else
	{
		$period_ids_list = $_REQUEST['period_id'];
	}

	$extra['SELECT'] .= ",(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
						WHERE ac.ID=ap.ATTENDANCE_CODE AND (ac.STATE_CODE='A' OR ac.STATE_CODE='H') AND ap.STUDENT_ID=ssm.STUDENT_ID
						AND ap.PERIOD_ID IN (" . $period_ids_list . ")
						AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND ac.SYEAR=ssm.SYEAR) AS STATE_ABS";

	$extra['columns_after']['STATE_ABS'] = _( 'State Abs' );
	$codes_RET = DBGet( "SELECT ID,TITLE FROM ATTENDANCE_CODES WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "' AND TABLE_NAME='0' AND (DEFAULT_CODE!='Y' OR DEFAULT_CODE IS NULL)" );

	if ( ! empty( $codes_RET ) && count( $codes_RET ) > 1 )
	{
		foreach ( (array) $codes_RET as $code )
		{
			$extra['SELECT'] .= ",(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
						WHERE ac.ID=ap.ATTENDANCE_CODE
						AND ac.ID='" . $code['ID'] . "'
						AND ap.PERIOD_ID IN (" . $period_ids_list . ")
						AND ap.STUDENT_ID=ssm.STUDENT_ID
						AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "') AS ABS_" . $code['ID'];

			$extra['columns_after']['ABS_' . $code['ID']] = $code['TITLE'];
		}
	}
}
else
{
	$extra['SELECT'] .= ",(SELECT COALESCE((sum(STATE_VALUE-1)*-1),0.0) FROM ATTENDANCE_DAY ad
						WHERE ad.STUDENT_ID=ssm.STUDENT_ID
						AND ad.SCHOOL_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND ad.SYEAR=ssm.SYEAR) AS STATE_ABS";
//FJ add translation
	$extra['columns_after']['STATE_ABS'] = _( 'Days Absent' );
}

$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&day_start=' . $_REQUEST['day_start'] . '&day_end=' . $_REQUEST['day_end'] . '&month_start=' . $_REQUEST['month_start'] . '&month_end=' . $_REQUEST['month_end'] . '&year_start=' . $_REQUEST['year_start'] . '&year_end=' . $_REQUEST['year_end'] . '&period_id=' . $_REQUEST['period_id'];
$extra['link']['FULL_NAME']['variables'] = array( 'student_id' => 'STUDENT_ID' );

Search( 'student_id', $extra );

if ( UserStudentID() )
{
	$full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM STUDENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	DrawHeader( $full_name );

	$absences_RET = DBGet( "SELECT ap.STUDENT_ID,ap.PERIOD_ID,ap.SCHOOL_DATE,ac.SHORT_NAME,
		ac.TITLE,ac.STATE_CODE,ad.STATE_VALUE,ad.COMMENT AS OFFICE_COMMENT,ap.COMMENT AS TEACHER_COMMENT
	FROM ATTENDANCE_PERIOD ap,ATTENDANCE_DAY ad,ATTENDANCE_CODES ac
	WHERE ap.STUDENT_ID=ad.STUDENT_ID
	AND ap.SCHOOL_DATE=ad.SCHOOL_DATE
	AND ap.ATTENDANCE_CODE=ac.ID
	AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL)
	AND ap.STUDENT_ID='" . UserStudentID() . "'
	AND ap.SCHOOL_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'
	AND ad.SYEAR='" . UserSyear() . "'
	ORDER BY ap.SCHOOL_DATE", array(), array( 'SCHOOL_DATE', 'PERIOD_ID' ) );

	foreach ( (array) $absences_RET as $school_date => $absences )
	{
		$i++;

		$days_RET[$i]['SCHOOL_DATE'] = ProperDate( $school_date );

		$days_RET[$i]['DAILY'] = _makeStateValue( $absences[key( $absences )][1]['STATE_VALUE'] );

		$days_RET[$i]['OFFICE_COMMENT'] = $absences[key( $absences )][1]['OFFICE_COMMENT'];

		foreach ( (array) $absences as $period_id => $absence )
		{
			//$days_RET[ $i ][ $period_id ] =            $absence[1]['SHORT_NAME'];
			$days_RET[$i][$period_id] = _makeColor(
				$absence[1]['SHORT_NAME'],
				$absence[1]['TITLE'],
				$absence[1]['STATE_CODE']
			);

			$days_RET[$i]['COMMENT_' . $period_id] = $absence[1]['TEACHER_COMMENT'];
		}
	}

	//FJ multiple school periods for a course period
	//$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.SHORT_NAME FROM SCHOOL_PERIODS sp,SCHEDULE s,COURSE_PERIODS cp WHERE sp.SCHOOL_ID='".UserSchool()."' AND sp.SYEAR='".UserSyear()."' AND s.STUDENT_ID='".UserStudentID()."' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND position(',0,' IN cp.DOES_ATTENDANCE)>0 ORDER BY sp.SORT_ORDER" );
	$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.SHORT_NAME
	FROM SCHOOL_PERIODS sp,SCHEDULE s,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND sp.SCHOOL_ID='" . UserSchool() . "'
	AND sp.SYEAR='" . UserSyear() . "'
	AND s.STUDENT_ID='" . UserStudentID() . "'
	AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID
	AND cpsp.PERIOD_ID=sp.PERIOD_ID
	AND position(',0,' IN cp.DOES_ATTENDANCE)>0
	ORDER BY sp.SORT_ORDER" );

	$columns['SCHOOL_DATE'] = _( 'Date' );

	$columns['DAILY'] = _( 'Present' );

	$columns['OFFICE_COMMENT'] = _( 'Office Comment' );

	foreach ( (array) $periods_RET as $period )
	{
		$columns[$period['PERIOD_ID']] = $period['SHORT_NAME'];
		$columns['COMMENT_' . $period['PERIOD_ID']] = $period['SHORT_NAME']
		. ' ' . _( 'Comment' );
	}

	ListOutput(
		$days_RET,
		$columns,
		'Day',
		'Days'
	);
}

/**
 * @param $value
 */
function _makeStateValue( $value )
{
	if ( $value == '0.0' )
	{
		return _( 'None' );
	}
	elseif ( $value == '.5' )
	{
		return _( 'Half Day' );
	}
	else
	{
		return _( 'Full Day' );
	}
}

/**
 * @param $value
 * @param $title
 * @param $state_code
 */
function _makeColor( $value, $title, $state_code )
{
	$colors = array( 'P' => '#FFCC00', 'A' => '#FF0000', 'H' => '#FFCC00', 'T' => '#6666FF' );

	return '<span style="float:left; padding:0 8px;' .
		( isset( $colors[$state_code] ) ? ' background-color:' . $colors[$state_code] . ';' : '' ) .
		'" title="' . $title . '">' . $value . '</span>';
}
