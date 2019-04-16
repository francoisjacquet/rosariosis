<?php

DrawHeader( ProgramTitle() );

Widgets( 'activity' );
Widgets( 'course' );
Widgets( 'eligibility' );

Search( 'student_id', $extra );

if ( $_REQUEST['modfunc'] === 'add'
	&& $_REQUEST['new_activity']
	&& AllowEdit() )
{
	// FJ fix bug add the same activity more than once.
	$activity_RET = DBGet( "SELECT ACTIVITY_ID
		FROM STUDENT_ELIGIBILITY_ACTIVITIES
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND ACTIVITY_ID='" . $_REQUEST['new_activity'] . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( ! empty( $activity_RET ) )
	{
		echo ErrorMessage( array( _( 'The activity you selected is already assigned to this student!' ) ) );
	}
	else
	{
		DBQuery( "INSERT INTO STUDENT_ELIGIBILITY_ACTIVITIES (STUDENT_ID,ACTIVITY_ID,SYEAR) values('" . UserStudentID() . "','" . $_REQUEST['new_activity'] . "','" . UserSyear() . "')" );
	}

	// Unset modfunc & new activity & redirect URL.
	RedirectURL( array( 'modfunc', 'new_activity' ) );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit()
	&& UserStudentID() )
{
	if ( DeletePrompt( _( 'Activity' ) ) )
	{
		DBQuery( "DELETE FROM STUDENT_ELIGIBILITY_ACTIVITIES
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND ACTIVITY_ID='" . $_REQUEST['activity_id'] . "'
			AND SYEAR='" . UserSyear() . "'" );

		// Unset modfunc & activity ID & redirect URL.
		RedirectURL( array( 'modfunc', 'activity_id' ) );
	}
}

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
	$eligibility_config = ProgramConfig( 'eligibility' );

	foreach ( (array) $eligibility_config as $value )
	{
		${$value[1]['TITLE']} = $value[1]['VALUE'];
	}

	switch ( date( 'D' ) )
	{
		case 'Mon':
			$today = 1;
			break;
		case 'Tue':
			$today = 2;
			break;
		case 'Wed':
			$today = 3;
			break;
		case 'Thu':
			$today = 4;
			break;
		case 'Fri':
			$today = 5;
			break;
		case 'Sat':
			$today = 6;
			break;
		case 'Sun':
			$today = 7;
			break;
	}

	$start = time() - ( $today - $START_DAY ) * 60 * 60 * 24;

	if ( empty( $_REQUEST['start_date'] ) )
	{
		$start_time = $start;

		$start_date = date( 'Y-m-d', $start_time );

		$end_date = DBDate();
	}
	else
	{
		$start_time = $_REQUEST['start_date'];

		$start_date = date( 'Y-m-d', $start_time );

		$end_date = date( 'Y-m-d', $start_time + 60 * 60 * 24 * 6 );
	}

	$begin_year = DBGetOne( "SELECT min(date_part('epoch',SCHOOL_DATE)) AS SCHOOL_DATE
		FROM ATTENDANCE_CALENDAR
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( is_null( $begin_year ) )
	{
		ErrorMessage( array( _( 'There are no calendars yet setup.' ) ), 'fatal' );
	}

//FJ display locale with strftime()
	//	$date_select = "<option value=$start>".date('M d, Y',$start).' - '.date('M d, Y',$end).'</option>';
	$date_select = '<option value="' . $start . '">' . ProperDate( date( 'Y-m-d', $start ) ) . ' - ' . ProperDate( DBDate() ) . '</option>';
	//exit(var_dump($begin_year));

	for ( $i = $start - ( 60 * 60 * 24 * 7 ); $i >= $begin_year; $i -= ( 60 * 60 * 24 * 7 ) )
//		$date_select .= "<option value=$i".(($i+86400>=$start_time && $i-86400<=$start_time)?' selected':'').">".date('M d, Y',$i).' - '.date('M d, Y',($i+1+(($END_DAY-$START_DAY))*60*60*24)).'</option>';
	{
		$date_select .= '<option value="' . $i . '"' . (  ( $i + 86400 >= $start_time && $i - 86400 <= $start_time ) ? ' selected' : '' ) . ">" . ProperDate( date( 'Y-m-d', $i ) ) . ' - ' . ProperDate( date( 'Y-m-d', ( $i + 1 + (  ( $END_DAY - $START_DAY ) ) * 60 * 60 * 24 ) ) ) . '</option>';
	}

	$date_select = '<select name="start_date" autocomplete="off">' . $date_select . '</select>';

	echo '<form action="' . PreparePHP_SELF( $_REQUEST, array( 'start_date' ) ) . '" method="GET">';

	DrawHeader( $date_select . ' ' . SubmitButton( _( 'Go' ) ) );

	echo '</form>';

	$RET = DBGet( "SELECT em.STUDENT_ID,em.ACTIVITY_ID,ea.TITLE,ea.START_DATE,ea.END_DATE
	FROM ELIGIBILITY_ACTIVITIES ea,STUDENT_ELIGIBILITY_ACTIVITIES em
	WHERE em.SYEAR='" . UserSyear() . "'
	AND em.STUDENT_ID='" . UserStudentID() . "'
	AND em.SYEAR=ea.SYEAR
	AND em.ACTIVITY_ID=ea.ID
	ORDER BY ea.START_DATE", array( 'START_DATE' => 'ProperDate', 'END_DATE' => 'ProperDate' ) );

	$activities_RET = DBGet( "SELECT ID,TITLE FROM ELIGIBILITY_ACTIVITIES WHERE SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "'" );

	foreach ( (array) $activities_RET as $value )
	{
		$activities[$value['ID']] = $value['TITLE'];
	}

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&start_date=' . $_REQUEST['start_date'];
	$link['remove']['variables'] = array( 'activity_id' => 'ACTIVITY_ID' );
//FJ css WPadmin
	//	$link['add']['html']['TITLE'] = '<table class="cellspacing-0"><tr><td>'.SelectInput('','new_activity','',$activities).'</td><td><input type=submit value="'._('Add').'"></td></tr></table>';
	//	$link['add']['html']['remove'] = button('add');
	$link['add']['html'] = array( 'remove' => button( 'add' ), 'TITLE' => SelectInput( '', 'new_activity', '', $activities ) . SubmitButton( _( 'Add' ) ), 'START_DATE' => '&nbsp;', 'END_DATE' => '&nbsp;' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=add&start_date=' . $_REQUEST['start_date'] . '" method="POST">';
	$columns = array( 'TITLE' => _( 'Activity' ), 'START_DATE' => _( 'Starts' ), 'END_DATE' => _( 'Ends' ) );
	ListOutput( $RET, $columns, 'Activity', 'Activities', $link );
	echo '</form>';

	$RET = DBGet( "SELECT e.ELIGIBILITY_CODE,c.TITLE as COURSE_TITLE
	FROM ELIGIBILITY e,COURSES c,COURSE_PERIODS cp
	WHERE e.STUDENT_ID='" . UserStudentID() . "'
	AND e.SYEAR='" . UserSyear() . "'
	AND e.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.COURSE_ID=c.COURSE_ID
	AND e.SCHOOL_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'", array( 'ELIGIBILITY_CODE' => '_makeLower' ) );
	$columns = array( 'COURSE_TITLE' => _( 'Course' ), 'ELIGIBILITY_CODE' => _( 'Grade' ) );
	ListOutput( $RET, $columns, 'Course', 'Courses' );
}

/**
 * @param $word
 */
function _makeLower( $word )
{
	return ucwords( mb_strtolower( $word ) );
}
