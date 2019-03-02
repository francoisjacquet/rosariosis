<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

$sem = GetParentMP( 'SEM', UserMP() );
$fy = GetParentMP( 'FY', $sem );
$pros = GetChildrenMP( 'PRO', UserMP() );

$all_mp = GetAllMP( 'PRO', UserMP() );

// If the UserMP has been changed, the REQUESTed MP may not work.

if ( empty( $_REQUEST['mp'] )
	|| mb_strpos( $all_mp, "'" . $_REQUEST['mp'] . "'" ) === false )
{
	$_REQUEST['mp'] = UserMP();
}

$periods_RET = DBGet( "SELECT sp.PERIOD_ID,sp.TITLE
	FROM SCHOOL_PERIODS sp
	WHERE sp.SCHOOL_ID='" . UserSchool() . "'
	AND sp.SYEAR='" . UserSyear() . "'
	AND EXISTS (SELECT 1
		FROM COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SYEAR='" . UserSyear() . "')
	ORDER BY sp.SORT_ORDER", array(), array( 'PERIOD_ID' ) );

$period_select = '<select name="period" onChange="ajaxPostForm(this.form,true);"><option value="">' . _( 'All' ) . '</option>';

$_REQUEST['period'] = isset( $_REQUEST['period'] ) ? $_REQUEST['period'] : false;

foreach ( (array) $periods_RET as $id => $period )
{
	$period_select .= '<option value="' . $id . '"' .
		(  ( $_REQUEST['period'] == $id ) ? ' selected' : '' ) . '>' .
		$period[1]['TITLE'] . '</option>';
}

$period_select .= "</select>";

$mp_select = '<select name="mp" onChange="ajaxPostForm(this.form,true);">';

if ( $pros != '' )
{
	foreach ( explode( ',', str_replace( "'", '', $pros ) ) as $pro )
	{
		if ( GetMP( $pro, 'DOES_GRADES' ) == 'Y' )
		{
			$mp_select .= '<option value="' . $pro . '"' . (  ( $pro == $_REQUEST['mp'] ) ? ' selected' : '' ) . '>' .
				GetMP( $pro ) . '</option>';
		}
	}
}

$mp_select .= '<option value="' . UserMP() . '"' . (  ( UserMP() == $_REQUEST['mp'] ) ? ' selected' : '' ) . '>' .
	GetMP( UserMP() ) . '</option>';

if ( GetMP( $sem, 'DOES_GRADES' ) == 'Y' )
{
	$mp_select .= '<option value="' . $sem . '"' . (  ( $sem == $_REQUEST['mp'] ) ? ' selected' : '' ) . '>' .
		GetMP( $sem ) . '</option>';
}

if ( GetMP( $fy, 'DOES_GRADES' ) == 'Y' )
{
	$mp_select .= '<option value="' . $fy . '"' . (  ( $fy == $_REQUEST['mp'] ) ? ' selected' : '' ) . '>' .
		GetMP( $fy ) . '</option>';
}

$mp_select .= '</select>';

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="GET">';
DrawHeader( $mp_select . ' - ' . $period_select );
echo '</form>';

//FJ multiple school periods for a course period
/*$sql = "SELECT s.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,sp.TITLE,cp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
(SELECT 'Y' FROM GRADES_COMPLETED ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp
WHERE
sp.PERIOD_ID = cp.PERIOD_ID AND cp.GRADE_SCALE_ID IS NOT NULL
AND cp.TEACHER_ID=s.STAFF_ID AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).")
AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND s.PROFILE='teacher'
".(($_REQUEST['period'])?" AND cp.PERIOD_ID='".$_REQUEST['period']."'":'')."
ORDER BY FULL_NAME";*/

$RET = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,sp.TITLE,cpsp.PERIOD_ID,cp.TITLE AS COURSE_TITLE,
	(SELECT 'Y'
		FROM GRADES_COMPLETED ac
		WHERE ac.STAFF_ID=cp.TEACHER_ID
		AND ac.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
		AND ac.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AS COMPLETED
	FROM STAFF s,COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND sp.PERIOD_ID=cpsp.PERIOD_ID
	AND cp.GRADE_SCALE_ID IS NOT NULL
	AND cp.TEACHER_ID=s.STAFF_ID
	AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
	AND cp.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID='" . UserSchool() . "'
	AND s.PROFILE='teacher'" .
	( $_REQUEST['period'] ? " AND cpsp.PERIOD_ID='" . $_REQUEST['period'] . "'" : '' ) .
	" ORDER BY FULL_NAME", array(), array( 'STAFF_ID' ) );

if ( empty( $_REQUEST['period'] ) )
{
	foreach ( (array) $RET as $staff_id => $periods )
	{
		$i++;

		$staff_RET[$i]['FULL_NAME'] = $periods[1]['FULL_NAME'];

		foreach ( (array) $periods as $period )
		{
			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$staff_RET[$i][$period['PERIOD_ID']] .= makeTipMessage(
					$period['COURSE_TITLE'],
					_( 'Course Title' ),
					button( $period['COMPLETED'] === 'Y' ? 'check' : 'x' )
				);
			}
			else
			{
				$staff_RET[$i][$period['PERIOD_ID']] = $period['COMPLETED'] === 'Y' ?
				_( 'Yes' ) . ' ' :
				_( 'No' ) . ' ';
			}
		}
	}

	$columns = array( 'FULL_NAME' => _( 'Teacher' ) );

	foreach ( (array) $periods_RET as $id => $period )
	{
		$columns[$id] = $period[1]['TITLE'];
	}

	ListOutput( $staff_RET, $columns, 'Teacher who enters grades', 'Teachers who enter grades' );
}
else
{
	$period_title = $periods_RET[$_REQUEST['period']][1]['TITLE'];

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
		array( 'FULL_NAME' => _( 'Teacher' ), 'COURSE_TITLE' => _( 'Course' ), 'COMPLETED' => _( 'Completed' ) ),
		sprintf( _( 'Teacher who enters grades for %s' ), $period_title ),
		sprintf( _( 'Teachers who enter grades for %s' ), $period_title ),
		false,
		array( 'STAFF_ID' )
	);
}
