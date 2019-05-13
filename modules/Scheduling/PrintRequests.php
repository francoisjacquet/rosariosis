<?php
Widgets( 'request' );
Widgets( 'mailing_labels' );

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	DrawHeader( ProgramTitle() );

	$extra['new'] = true;
	$extra['action'] .= "&_ROSARIO_PDF=true";
	Search( 'student_id', $extra );
}
else
{
	$columns = array(
		'COURSE_TITLE' => _( 'Course' ),
		'WITH_FULL_NAME' => '<span class="a11y-hidden">' . _( 'Request' ) . '</span>',
	);

	$extra['SELECT'] .= ",c.TITLE AS COURSE_TITLE,srp.PRIORITY,srp.MARKING_PERIOD_ID,srp.WITH_TEACHER_ID,srp.NOT_TEACHER_ID,srp.WITH_PERIOD_ID,srp.NOT_PERIOD_ID,'' AS WITH_FULL_NAME";
	$extra['FROM'] .= ',COURSES c,SCHEDULE_REQUESTS srp';
	$extra['WHERE'] .= ' AND ssm.STUDENT_ID=srp.STUDENT_ID AND ssm.SYEAR=srp.SYEAR AND srp.COURSE_ID = c.COURSE_ID';

//FJ add subject areas
	$extra['functions'] += array( 'WITH_FULL_NAME' => '_makeExtra' );
	$extra['group'] = array( 'STUDENT_ID' );
	//FJ add ORDER BY COURSE_TITLE
	$extra['ORDER_BY'] = 'COURSE_TITLE';

	if ( $_REQUEST['mailing_labels'] == 'Y' )
	{
		$extra['group'][] = 'ADDRESS_ID';
	}

	$RET = GetStuList( $extra );

	if ( ! empty( $RET ) )
	{
		$__DBINC_NO_SQLSHOW = true;
		$handle = PDFStart();

		foreach ( (array) $RET as $student_id => $courses )
		{
			if ( $_REQUEST['mailing_labels'] == 'Y' )
			{
				foreach ( (array) $courses as $address )
				{
					echo '<br /><br /><br />';
					unset( $_ROSARIO['DrawHeader'] );
					DrawHeader( _( 'Student Requests' ) );
					DrawHeader( $address[1]['FULL_NAME'], $address[1]['STUDENT_ID'] );
					DrawHeader( $address[1]['GRADE_ID'] );
					DrawHeader( SchoolInfo( 'TITLE' ) );
					DrawHeader( ProperDate( DBDate() ) );

					echo '<br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $address[1]['MAILING_LABEL'] . '</td></tr></table><br />';

					ListOutput( $address, $columns, 'Request', 'Requests', array(), array(), array( 'center' => false, 'print' => false ) );
					echo '<div style="page-break-after: always;"></div>';
				}
			}
			else
			{
				unset( $_ROSARIO['DrawHeader'] );
				DrawHeader( _( 'Student Requests' ) );
				DrawHeader( $courses[1]['FULL_NAME'], $courses[1]['STUDENT_ID'] );
				DrawHeader( $courses[1]['GRADE_ID'] );
				DrawHeader( SchoolInfo( 'TITLE' ) );
				DrawHeader( ProperDate( DBDate() ) );

				ListOutput( $courses, $columns, 'Request', 'Requests', array(), array(), array( 'center' => false, 'print' => false ) );
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

/**
 * @param $value
 * @param $title
 * @return mixed
 */
function _makeExtra( $value, $title = '' )
{
	global $THIS_RET;

	$return = array();

	if ( $THIS_RET['WITH_TEACHER_ID'] )
	{
		$return[] = _( 'With' ) . ':&nbsp;' . GetTeacher( $THIS_RET['WITH_TEACHER_ID'] );
	}

	if ( $THIS_RET['NOT_TEACHER_ID'] )
	{
		$return[] = _( 'Not With' ) . ':&nbsp;' . GetTeacher( $THIS_RET['NOT_TEACHER_ID'] );
	}

	if ( $THIS_RET['WITH_PERIOD_ID'] )
	{
		$return[] = _( 'On' ) . ':&nbsp;' . _getPeriod( $THIS_RET['WITH_PERIOD_ID'] );
	}

	if ( $THIS_RET['NOT_PERIOD_ID'] )
	{
		$return[] = _( 'Not on' ) . ':&nbsp;' . _getPeriod( $THIS_RET['NOT_PERIOD_ID'] );
	}

	if ( $THIS_RET['PRIORITY'] )
	{
		$return[] = _( 'Priority' ) . ':&nbsp;' . $THIS_RET['PRIORITY'];
	}

	if ( $THIS_RET['MARKING_PERIOD_ID'] )
	{
		$return[] = _( 'Marking Period' ) . ':&nbsp;' . GetMP( $THIS_RET['MARKING_PERIOD_ID'] );
	}

	$return = implode( '&nbsp;-&nbsp;', $return );

	return $return;
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
		$sql = "SELECT TITLE, PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='" . UserSyear() . "'";
		$periods_RET = DBGet( $sql, array(), array( 'PERIOD_ID' ) );
	}

	return $periods_RET[$period_id][1]['TITLE'];
}
