<?php

require_once 'ProgramFunctions/Charts.fnc.php';

DrawHeader( ProgramTitle() );

// Set Marking Period
if ( empty( $_REQUEST['mp_id'] ) )
{
	$_REQUEST['mp_id'] = UserMP();
}

$chart_types = array( 'line', 'list' );

// set Chart Type
if ( !isset( $_REQUEST['chart_type'] )
	|| !in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'line';
}

// Get all the mp's associated with the current mp
$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,0,SORT_ORDER
	FROM SCHOOL_MARKING_PERIODS
	WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID
		FROM SCHOOL_MARKING_PERIODS
		WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='" . UserMP() . "'))
	AND MP='FY'
	UNION
	SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,1,SORT_ORDER
	FROM SCHOOL_MARKING_PERIODS
	WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID
		FROM SCHOOL_MARKING_PERIODS
		WHERE MARKING_PERIOD_ID='" . UserMP() . "')
	AND MP='SEM'
	UNION
	SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,2,SORT_ORDER
	FROM SCHOOL_MARKING_PERIODS
	WHERE MARKING_PERIOD_ID='" . UserMP() . "'
	UNION
	SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,3,SORT_ORDER
	FROM SCHOOL_MARKING_PERIODS
	WHERE PARENT_ID='" . UserMP() . "'
	AND MP='PRO'
	ORDER BY 5,SORT_ORDER" );

echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="GET">';

$mp_select = '<select name="mp_id" id="mp_id" onchange="ajaxPostForm(this.form,true);">';

foreach ( (array) $mps_RET as $mp )
{
    if ( $mp['DOES_GRADES'] === 'Y'
    	|| $mp['MARKING_PERIOD_ID'] === UserMP() )
    {
        $mp_select .= '<option value="' . $mp['MARKING_PERIOD_ID'] . '"' .
        	( $mp['MARKING_PERIOD_ID'] === $_REQUEST['mp_id'] ? ' selected' : '' ) . '>' .
        	( $UserMPTitle = $mp['TITLE'] ) . '</option>';
	}
}

$mp_select .= '</select>
	<label for="mp_id" class="a11y-hidden">' . _( 'Marking Periods' ) . '</label>';

DrawHeader( $mp_select );

echo '</form>';

$grouped_SQL = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,s.STAFF_ID,g.REPORT_CARD_GRADE_ID
	FROM STUDENT_REPORT_CARD_GRADES g,STAFF s,COURSE_PERIODS cp
	WHERE g.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
	AND cp.TEACHER_ID=s.STAFF_ID
	AND cp.SYEAR=s.SYEAR
	AND cp.SYEAR=g.SYEAR
	AND cp.SYEAR='" . UserSyear() . "'
	AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp_id'] . "'";

$grouped_RET = DBGet( $grouped_SQL, array(), array( 'STAFF_ID', 'REPORT_CARD_GRADE_ID' ) );

$grades_RET = DBGet( "SELECT rg.ID,rg.TITLE,rg.GPA_VALUE
	FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs
	WHERE rg.SCHOOL_ID='" . UserSchool() . "'
	AND rg.SYEAR='" . UserSyear() . "'
	AND rs.ID=rg.GRADE_SCALE_ID
	ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER" );

//FJ jqplot charts
if ( $grouped_RET )
{
	echo '<br />';

	$tabs = array(
		array(
			'title' => _( 'Line' ),
			'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'line' ) ),
		)
	);

	// Allow Column chart only if grades count <=20
	if ( empty( $grades_RET ) || count( $grades_RET ) <= 20 )
	{
		$tabs[] = array(
			'title' => _( 'Column' ),
			'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'column' ) ),
		);
	}

	$tabs[] = array(
		'title' => _( 'List' ),
		'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'list' ) ),
	);

	$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

	PopTable( 'header', $tabs );

	// List.
	if ( $_REQUEST['chart_type'] === 'list' )
	{
		$LO_columns = array( 'GRADES' => _( 'Grades' ) );

		$i = $j = 0;

		foreach ( (array) $grades_RET as $grade )
		{
			$i++;

			$teachers_RET[ $i ]['GRADES'] = $grade['TITLE'];
		}

		foreach ( (array) $grouped_RET as $staff_id => $grades )
		{
			$j = 0;

			$LO_columns[ $staff_id ] = $grades[key( $grades )][1]['FULL_NAME'];

			foreach ( (array) $grades_RET as $grade )
			{
				$j++;

				$teachers_RET[ $j ][ $staff_id ] = empty( $grades[$grade['ID']] ) ?
					0 : count( $grades[$grade['ID']] );
			}
		}

		$LO_options['responsive'] = false;

		ListOutput( $teachers_RET, $LO_columns, 'Grade', 'Grades', array(), array(), $LO_options );
	}
	//FJ jqplot charts
	else
	{

		foreach ( (array) $grouped_RET as $staff_id => $grades )
		{
			$chartData = array();

			$chartTitle = $grades[key($grades)][1]['FULL_NAME'] . ' - ' . $UserMPTitle . ' - ' . _( 'Grade Breakdown' );

			foreach ( (array) $grades_RET as $grade )
			{
				if ( $_REQUEST['chart_type'] === 'column' )
				{
					$chartData[0][] = $grade['TITLE'];
				}
				else
					$chartData[0][] = $grade['GPA_VALUE'];

				$chartData[1][] = empty( $grades[$grade['ID']] ) ?
					0 : count( $grades[$grade['ID']] );
			}

			if ( $_REQUEST['chart_type'] === 'column' )
			{
				echo jqPlotChart( 'column', $chartData, $chartTitle );
			}
			else
				echo jqPlotChart( 'line', $chartData, $chartTitle );

			echo '<br />';
		}
	}

	PopTable( 'footer' );
}
else
{

	echo '<br /><div class="center"><b>' . sprintf( _( 'No %s were found.' ), _( 'Teacher' ) ) . '</div></b>';

}
