<?php

require_once 'modules/Scheduling/includes/ClassSearchWidget.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['cp_arr'] ) )
	{
		$cp_list = "'" . implode( "','", $_REQUEST['cp_arr'] ) . "'";

		$extra['DATE'] = DBGetOne( "SELECT min(SCHOOL_DATE) AS START_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( ! $extra['DATE']
			|| DBDate() > $extra['DATE'] )
		{
			$extra['DATE'] = DBDate();
		}

		$fy_id = GetFullYearMP();

		//FJ multiple school periods for a course period
		//FJ add subject areas
		$course_periods_RET = DBGet( DBQuery( "SELECT cp.TITLE,cp.COURSE_PERIOD_ID,cp.TITLE,
		cp.MARKING_PERIOD_ID,cp.MP,c.TITLE AS COURSE_TITLE,cp.TEACHER_ID,
		(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=cp.TEACHER_ID) AS TEACHER
		FROM COURSE_PERIODS cp,COURSES c
		WHERE c.COURSE_ID=cp.COURSE_ID
		AND cp.COURSE_PERIOD_ID IN (" . $cp_list . ")
		ORDER BY TEACHER" ) );

		$first_extra = $extra;
		$handle = PDFStart();

		$PCL_UserCoursePeriod = UserCoursePeriod(); // save/restore for teachers

		$no_students_backprompt = true;

		foreach ( (array) $course_periods_RET as $teacher_id => $course_period )
		{
			$_SESSION['UserCoursePeriod'] = $course_period['COURSE_PERIOD_ID'];

			$extra = array( 'SELECT_ONLY' => '1' );

			//FJ prevent course period ID hacking

			if ( User( 'PROFILE' ) === 'teacher' )
			{
				$extra['WHERE'] = " AND '" . User( 'STAFF_ID' ) . "'=(SELECT TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')";
			}
			elseif ( User( 'PROFILE' ) === 'admin' )
			{
				$extra['WHERE'] = $extraWHERE = " AND s.STUDENT_ID IN
				(SELECT STUDENT_ID
				FROM SCHEDULE
				WHERE COURSE_PERIOD_ID='" . $course_period['COURSE_PERIOD_ID'] . "'
				AND '" . DBDate() . "'>=START_DATE
				AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";
			}

			$RET = GetStuList( $extra );
			//echo '<pre>'; var_dump($RET); echo '</pre>';

			if ( ! empty( $RET ) )
			{
				$no_students_backprompt = false;

				unset( $_ROSARIO['DrawHeader'] );
				DrawHeader( _( 'Class List' ) );

				DrawHeader( $course_period['COURSE_TITLE'], $course_period['TITLE'] );
				DrawHeader( SchoolInfo( 'TITLE' ), ProperDate( DBDate() ) );

				$extra = $first_extra;
				$extra['MP'] = $course_period['MARKING_PERIOD_ID'];
				$extra['MPTable'] = $course_period['MP'];
				$extra['suppress_save'] = true;

				$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

				if ( User( 'PROFILE' ) === 'admin' )
				{
					$extra['WHERE'] .= $extraWHERE;
				}

				require_once 'modules/misc/Export.php';

				echo '<div style="page-break-after: always;"></div>';
			}
		}

		$_SESSION['UserCoursePeriod'] = $PCL_UserCoursePeriod;

		if ( $no_students_backprompt )
		{
			BackPrompt( _( 'No Students were found.' ) );
		}

		PDFStop( $handle );
	}
	else
	{
		BackPrompt( _( 'You must choose at least one course period.' ) );
	}
}

if ( ! $_REQUEST['modfunc']
	|| $_REQUEST['modfunc'] === 'list' )
{
	DrawHeader( ProgramTitle() );

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$_REQUEST['modfunc'] = 'list';
	}

	if ( $_REQUEST['modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&search_modfunc=list&_ROSARIO_PDF=true' .
			issetVal( $extra['action'], '' ) . '" method="POST" name="search">';

		$submit_button = Buttons( _( 'Create Class Lists for Selected Course Periods' ) );

		DrawHeader(
			'<label><input type="checkbox" name="include_inactive" value="Y" /> ' .
			_( 'Include Inactive Students' ) . '</label>',
			$submit_button
		);

		$Search = 'ClassSearchWidget';

		require_once 'modules/misc/Export.php';

		echo '<br style="clear: both;" /><div class="center">' . $submit_button . '</div>';
		echo '</form>';
	}
	else
	{
		ClassSearchWidget( '' );
	}
}
