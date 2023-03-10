<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

// Get all the MP's associated with the current MP
$all_mp_ids = explode( "','", trim( GetAllMP( 'PRO', UserMP() ), "'" ) );

if ( ! empty( $_REQUEST['mp'] )
	&& ! in_array( $_REQUEST['mp'], $all_mp_ids ) )
{
	// Requested MP not found, reset.
	RedirectURL( 'mp' );
}

if ( empty( $_REQUEST['mp'] ) )
{
	$_REQUEST['mp'] = UserMP();
}

$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE,COALESCE(sp.SHORT_NAME,sp.TITLE) AS SHORT_TITLE
	FROM school_periods sp
	WHERE sp.SCHOOL_ID='" . UserSchool() . "'
	AND sp.SYEAR='" . UserSyear() . "'
	AND EXISTS (SELECT 1
		FROM course_periods cp,course_period_school_periods cpsp
		WHERE cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SYEAR='" . UserSyear() . "')
	ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER,sp.TITLE", [], [ 'PERIOD_ID' ] );

$period_select = '<select name="school_period" id="school_period" onchange="ajaxPostForm(this.form,true);">
	<option value="">' . _( 'All' ) . '</option>';

$_REQUEST['school_period'] = issetVal( $_REQUEST['school_period'], false );

foreach ( (array) $periods_RET as $id => $period )
{
	$period_select .= '<option value="' . AttrEscape( $id ) . '"' .
		(  ( $_REQUEST['school_period'] == $id ) ? ' selected' : '' ) . '>' .
		$period[1]['TITLE'] . '</option>';
}

$period_select .= '</select>
	<label for="school_period" class="a11y-hidden">' . _( 'Period' ) . '</label>';

$mp_select = '<select name="mp" id="mp-select" onchange="ajaxPostForm(this.form,true);">';

foreach ( (array) $all_mp_ids as $mp_id )
{
	if ( GetMP( $mp_id, 'DOES_GRADES' ) == 'Y' || $mp_id == UserMP() )
	{
		$mp_select .= '<option value="' . AttrEscape( $mp_id ) . '"' .
			( $mp_id == $_REQUEST['mp'] ? ' selected' : '' ) . '>' . GetMP( $mp_id ) . '</option>';

		if ( $mp_id === $_REQUEST['mp'] )
		{
			$user_mp_title = GetMP( $mp_id );
		}
	}
}

$mp_select .= '</select>
	<label for="mp-select" class="a11y-hidden">' . _( 'Marking Period' ) . '</label>';

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="GET">';
DrawHeader( $mp_select . ' &mdash; ' . $period_select );
echo '</form>';

//FJ multiple school periods for a course period
/*$sql = "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sp.TITLE,cp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
(SELECT 'Y' FROM grades_completed ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
FROM staff s,course_periods cp,school_periods sp
WHERE
sp.PERIOD_ID = cp.PERIOD_ID AND cp.GRADE_SCALE_ID IS NOT NULL
AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
".(($_REQUEST['school_period'])?" AND cp.PERIOD_ID='".$_REQUEST['school_period']."'":'')."
ORDER BY FULL_NAME";*/

$RET = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,s.ROLLOVER_ID,
	sp.TITLE,cpsp.PERIOD_ID,cp.TITLE AS CP_TITLE,c.TITLE AS COURSE_TITLE,
	(SELECT 'Y'
		FROM grades_completed ac
		WHERE ac.STAFF_ID=cp.TEACHER_ID
		AND ac.MARKING_PERIOD_ID='" . (int) $_REQUEST['mp'] . "'
		AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
	FROM staff s,course_periods cp,school_periods sp,course_period_school_periods cpsp,courses c
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND cp.GRADE_SCALE_ID IS NOT NULL
	AND cp.TEACHER_ID=s.STAFF_ID
	AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
	AND cp.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID='" . UserSchool() . "'
	AND c.COURSE_ID=cp.COURSE_ID
	AND s.PROFILE='teacher'" .
	( $_REQUEST['school_period'] ? " AND cpsp.PERIOD_ID='" . (int) $_REQUEST['school_period'] . "'" : '' ) .
	" ORDER BY FULL_NAME", [ 'FULL_NAME' => 'makePhotoTipMessage' ], [ 'STAFF_ID' ] );

if ( empty( $_REQUEST['school_period'] ) )
{
	$i = 0;

	foreach ( (array) $RET as $staff_id => $periods )
	{
		$i++;

		$staff_RET[$i]['FULL_NAME'] = $periods[1]['FULL_NAME'];

		foreach ( (array) $periods as $period )
		{
			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				if ( ! isset( $staff_RET[$i][$period['PERIOD_ID']] ) )
				{
					$staff_RET[$i][$period['PERIOD_ID']] = '';
				}

				$staff_RET[$i][$period['PERIOD_ID']] .= MakeTipMessage(
					$period['CP_TITLE'],
					$period['COURSE_TITLE'],
					button( $period['COMPLETED'] === 'Y' ? 'check' : 'x' )
				) . ' ';
			}
			else
			{
				$staff_RET[$i][$period['PERIOD_ID']] = $period['COMPLETED'] === 'Y' ?
				_( 'Yes' ) . ' ' :
				_( 'No' ) . ' ';
			}
		}
	}

	$columns = [ 'FULL_NAME' => _( 'Teacher' ) ];

	$period_title_column = 'TITLE';

	if ( count( $periods_RET ) > 10 )
	{
		// Use Period's Short Name when > 10 columns in the list.
		$period_title_column = 'SHORT_TITLE';
	}

	foreach ( (array) $periods_RET as $id => $period )
	{
		$columns[$id] = $period[1][$period_title_column];
	}

	ListOutput( $staff_RET, $columns, 'Teacher who enters grades', 'Teachers who enter grades' );
}
else
{
	$period_title = $periods_RET[$_REQUEST['school_period']][1]['TITLE'];

	foreach ( (array) $RET as $staff_id => $periods )
	{
		foreach ( (array) $periods as $period_id => $period )
		{
			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$RET[$staff_id][$period_id]['COMPLETED'] = button( $period['COMPLETED'] == 'Y' ? 'check' : 'x', '', '' ) . ' ';
			}
			else
			{
				$RET[$staff_id][$period_id]['COMPLETED'] = $period['COMPLETED'] == 'Y' ? _( 'Yes' ) . ' ' : _( 'No' ) . ' ';
			}
		}
	}

	ListOutput(
		$RET,
		[ 'FULL_NAME' => _( 'Teacher' ), 'CP_TITLE' => _( 'Course Period' ), 'COMPLETED' => _( 'Completed' ) ],
		sprintf( _( 'Teacher who enters grades for %s' ), $period_title ),
		sprintf( _( 'Teachers who enter grades for %s' ), $period_title ),
		false,
		[ 'STAFF_ID' ]
	);
}
