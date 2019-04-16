<?php
/**
 * @param $item
 */
function MyWidgets( $item )
{
	global $extra, $_ROSARIO;

	switch ( $item )
	{
		case 'ly_course':
			if ( ! empty( $_REQUEST['w_ly_course_period_id'] ) )
			{
				if ( $_REQUEST['w_ly_course_period_id_which'] == 'course' )
				{
					$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_ly_course_period_id'] . "'" );
					$extra['WHERE'] .= " AND exists(SELECT '' FROM SCHEDULE WHERE STUDENT_ID=ssm.STUDENT_ID AND COURSE_ID='" . $course[1]['COURSE_ID'] . "')";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Last Year Course' ) . ': </b>' . $course[1]['COURSE_TITLE'] . '<br />';
					}
				}
				else
				{
					$extra['WHERE'] .= " AND exists(SELECT '' FROM SCHEDULE WHERE STUDENT_ID=ssm.STUDENT_ID AND COURSE_PERIOD_ID='" . $_REQUEST['w_ly_course_period_id'] . "')";
					$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_ly_course_period_id'] . "'" );

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Last Year Course Period' ) . ': </b>' . $course[1]['COURSE_TITLE'] . ' - ' . $course[1]['TITLE'] . '<br />';
					}
				}
			}

			$extra['search'] .= '<tr><td>' . _( 'Last Year Course' ) . '</td>
				<td><div id="ly_course_div"></div>
				<a href="#" onclick=\'popups.open(
					"Modules.php?modname=misc/ChooseCourse.php&last_year=true"
				); return false;\'>' . _( 'Choose' ) . '</a></td></tr>';
			break;
	}
}
