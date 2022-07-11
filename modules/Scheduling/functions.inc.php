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
				// @since 6.5 Course Widget: add Subject and Not options.
				$extra['WHERE'] .= ! empty( $_REQUEST['w_ly_course_period_id_not'] ) ?
					" AND NOT " : " AND ";

				if ( $_REQUEST['w_ly_course_period_id_which'] === 'subject' )
				{
					$extra['WHERE'] .= " EXISTS(SELECT 1 FROM schedule
						WHERE STUDENT_ID=ssm.STUDENT_ID
						AND COURSE_ID IN(SELECT COURSE_ID
							FROM courses
							WHERE SUBJECT_ID='" . (int) $_REQUEST['w_ly_subject_id'] . "'))";

					$subject_title = DBGetOne( "SELECT TITLE
						FROM course_subjects
						WHERE SUBJECT_ID='" . (int) $_REQUEST['w_ly_subject_id'] . "'" );

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Last Year Course' ) . ': </b>' .
							( ! empty( $_REQUEST['w_ly_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
							$subject_title . '<br />';
					}
				}
				// Course.
				elseif ( $_REQUEST['w_ly_course_period_id_which'] === 'course' )
				{
					$extra['WHERE'] .= " EXISTS(SELECT 1 FROM schedule
						WHERE STUDENT_ID=ssm.STUDENT_ID
						AND COURSE_ID='" . (int) $_REQUEST['w_ly_course_id'] . "')";

					$course_title = DBGetOne( "SELECT TITLE
						FROM courses
						WHERE COURSE_ID='" . (int) $_REQUEST['w_ly_course_id'] . "'" );

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Last Year Course' ) . ': </b>' .
							( ! empty( $_REQUEST['w_ly_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
							$course_title . '<br />';
					}
				}
				// Course Period.
				else
				{
					$extra['WHERE'] .= " EXISTS(SELECT 1 FROM schedule
						WHERE STUDENT_ID=ssm.STUDENT_ID
						AND COURSE_PERIOD_ID='" . (int) $_REQUEST['w_ly_course_period_id'] . "')";

					$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID
						FROM course_periods cp,courses c
						WHERE c.COURSE_ID=cp.COURSE_ID
						AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_ly_course_period_id'] . "'" );

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Last Year Course Period' ) . ': </b>' .
							( ! empty( $_REQUEST['w_ly_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
							$course[1]['COURSE_TITLE'] . ': ' . $course[1]['TITLE'] . '<br />';
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
