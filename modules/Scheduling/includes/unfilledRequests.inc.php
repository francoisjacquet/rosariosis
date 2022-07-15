<?php

// Regroup functions for UnfilledRequests.php & Unfilled Requests display in Schedule.php.
function calcSeats()
{
	global $THIS_RET;

	$periods_RET = DBGet( "SELECT COURSE_PERIOD_ID,MARKING_PERIOD_ID,CALENDAR_ID,TOTAL_SEATS
	FROM course_periods cp
	WHERE COURSE_ID='" . (int) $THIS_RET['COURSE_ID'] . "'
	AND (GENDER_RESTRICTION='N' OR GENDER_RESTRICTION='" . mb_substr( (string) $THIS_RET['CUSTOM_200000000'], 0, 1 ) . "')" .
		( $THIS_RET['WITH_TEACHER_ID'] ? " AND TEACHER_ID='" . (int) $THIS_RET['WITH_TEACHER_ID'] . "'" : '' ) .
		( $THIS_RET['NOT_TEACHER_ID'] ? " AND TEACHER_ID!='" . (int) $THIS_RET['NOT_TEACHER_ID'] . "'" : '' ) .
		//FJ bugfix SQL error column "period_id" does not exist
		( $THIS_RET['WITH_PERIOD_ID'] ? " AND '" . $THIS_RET['WITH_PERIOD_ID'] . "' IN(SELECT cpsp.PERIOD_ID FROM course_period_school_periods cpsp WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)" : '' ) .
		( $THIS_RET['NOT_PERIOD_ID'] ? " AND '" . $THIS_RET['NOT_PERIOD_ID'] . "' NOT IN(SELECT cpsp.PERIOD_ID FROM course_period_school_periods cpsp WHERE cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)" : '' ) );
	//echo '<pre>'; var_dump($periods_RET); echo '</pre>';

	$total_seats = $filled_seats = null;

	foreach ( (array) $periods_RET as $period )
	{
		$seats = calcSeats0( $period );

		if ( $total_seats !== false )
		{
			if ( $period['TOTAL_SEATS'] )
			{
				$total_seats += $period['TOTAL_SEATS'];
			}
			else
			{
				$total_seats = false;
			}
		}

		if ( $filled_seats !== false )
		{
			if ( $seats != '' )
			{
				$filled_seats += $seats;
			}
			else
			{
				$filled_seats = false;
			}
		}
	}

	return ( $total_seats !== false ? ( $filled_seats !== false ? $total_seats - $filled_seats : '' ) : _( 'N/A' ) );
}

/**
 * @param $value
 * @param $column
 */
function _makeRequestTeacher( $value, $column )
{
	global $THIS_RET;

	return ( $value ? _( 'With' ) . '&nbsp;' . GetTeacher( $value ) : '' ) .
		( $THIS_RET['NOT_TEACHER_ID'] ? ( $value ? ' &mdash; ' : '' ) . _( 'Without' ) . '&nbsp;' . GetTeacher( $THIS_RET['NOT_TEACHER_ID'] ) : '' );
}

/**
 * @param $value
 * @param $column
 */
function _makeRequestPeriod( $value, $column )
{
	global $THIS_RET;

	return ( $value ? _( 'On' ) . '&nbsp;' . _getPeriod( $value ) : '' ) .
		( $THIS_RET['NOT_PERIOD_ID'] ? ( $value ? ' &mdash; ' : '' ) . _( 'Not on' ) . '&nbsp;' . _getPeriod( $THIS_RET['NOT_PERIOD_ID'] ) : '' );
}

/**
 * @param $period_id
 * @return mixed
 */
function _getPeriod( $period_id )
{
	static $periods_RET;

	if ( empty( $periods_RET ) )
	{
		$sql = "SELECT TITLE, PERIOD_ID FROM school_periods WHERE SYEAR='" . UserSyear() . "'";
		$periods_RET = DBGet( $sql, [], [ 'PERIOD_ID' ] );
	}

	return $periods_RET[$period_id][1]['TITLE'];
}
