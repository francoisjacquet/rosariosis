<?php

require_once 'modules/Scheduling/includes/ClassSearchWidget.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['cp_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one course period.' ) );
	}

	$cp_list = "'" . implode( "','", $_REQUEST['cp_arr'] ) . "'";

	//FJ multiple school periods for a course period
	//$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID,cp.MARKING_PERIOD_ID,cp.MP FROM course_periods cp WHERE cp.COURSE_PERIOD_ID IN ($cp_list) ORDER BY (SELECT SORT_ORDER FROM school_periods WHERE PERIOD_ID=cp.PERIOD_ID)" );
	$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID,cp.MARKING_PERIOD_ID,cp.MP
		FROM course_periods cp
		WHERE cp.COURSE_PERIOD_ID IN (" . $cp_list . ")
		ORDER BY cp.SHORT_NAME,cp.TITLE" );
	//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';

	if ( isset( $_REQUEST['include_teacher'] )
		&& $_REQUEST['include_teacher'] === 'Y' )
	{
		$teachers_RET = DBGet( "SELECT STAFF_ID,LAST_NAME,FIRST_NAME,ROLLOVER_ID
			FROM staff
			WHERE STAFF_ID IN (SELECT TEACHER_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID IN (" . $cp_list . "))", [], [ 'STAFF_ID' ] );
	}

	//echo '<pre>'; var_dump($teachers_RET); echo '</pre>';

	$handle = PDFStart();

	$PCP_UserCoursePeriod = UserCoursePeriod(); // Save/restore for teachers.

	$is_include_inactive = isset( $_REQUEST['include_inactive'] ) && $_REQUEST['include_inactive'] === 'Y';

	$no_students_backprompt = true;

	foreach ( (array) $course_periods_RET as $course_period )
	{
		$course_period_id = $course_period['COURSE_PERIOD_ID'];
		$teacher_id = $course_period['TEACHER_ID'];

		if ( ! $teacher_id )
		{
			continue;
		}

		// Do NOT use SetUserCoursePeriod() here (even only for teachers) as CP may be in another Semester, Quarter, etc.
		$_SESSION['UserCoursePeriod'] = $course_period_id;

		$extra = [
			'SELECT_ONLY' => 's.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME',
			'ORDER_BY' => 's.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME',
			'MP' => $course_period['MARKING_PERIOD_ID'],
			'MPTable' => $course_period['MP'],
		];

		if ( User( 'PROFILE' ) === 'student'
			|| User( 'PROFILE' ) === 'parent' )
		{
			// Prevent course period ID hacking.
			$extra['WHERE'] = " AND '" . UserStudentID() . "' IN
			(SELECT STUDENT_ID
			FROM schedule
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'
			AND '" . DBDate() . "'>=START_DATE
			AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)
			AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";

			// Limit to UserCoursePeriod().
			$extra['FROM'] = "JOIN schedule ss ON (ss.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
				AND s.STUDENT_ID=ss.STUDENT_ID)";

			// Do NOT use GetStuList() otherwise limited to UserStudentID().
			$RET = DBGet( "SELECT s.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME
				FROM students s
				JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
					AND ssm.SYEAR='" . UserSyear() . "'
					AND ssm.SCHOOL_ID='" . UserSchool() . "'
					AND ('" . DBDate() . "'>=ssm.START_DATE
						AND (ssm.END_DATE IS NULL OR '" . DBDate() . "'<=ssm.END_DATE)))" .
				$extra['FROM'] .
				"WHERE TRUE" . $extra['WHERE'] .
				" ORDER BY " . $extra['ORDER_BY'] );
		}

		if ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		{
			$extra['WHERE'] = '';

			if ( User( 'PROFILE' ) === 'teacher' )
			{
				// Prevent course period ID hacking.
				$extra['WHERE'] .= " AND '" . User( 'STAFF_ID' ) . "'=(SELECT TEACHER_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')";
			}

			$extra['WHERE'] .= " AND s.STUDENT_ID IN
			(SELECT STUDENT_ID
			FROM schedule
			WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "'";

			if ( $is_include_inactive )
			{
				// Include Inactive Students.
				$extra['WHERE'] .= ")";
			}
			else
			{
				// Active / Scheduled Students.
				$extra['WHERE'] .= " AND '" . DBDate() . "'>=START_DATE
					AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)
					AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";
			}

			$RET = GetStuList( $extra );
		}

		//echo '<pre>'; var_dump($RET); echo '</pre>';

		if ( empty( $RET ) )
		{
			continue;
		}

		$no_students_backprompt = false;

		echo '<table class="width-100p">';
		//FJ school year over one/two calendar years format
		echo '<tr><td colspan="5" class="center"><h3>' . FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' - ' . $course_period['TITLE'] . '</h3></td></tr>';

		$i = 0;

		if ( isset( $_REQUEST['include_teacher'] )
			&& $_REQUEST['include_teacher'] === 'Y' )
		{
			$teacher = $teachers_RET[$teacher_id][1];

			echo '<tr><td style="vertical-align:bottom;"><table>';

			// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
			$picture_path = (array) glob( $UserPicturesPath . UserSyear() . '/' . $teacher_id . '.*jpg' );

			$picture_path = end( $picture_path );

			if ( ! $picture_path
				&& $teacher['ROLLOVER_ID'] )
			{
				// Use Last Year's if Missing.
				// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
				$picture_path = (array) glob( $UserPicturesPath . ( UserSyear() - 1 ) . '/' . $teacher['ROLLOVER_ID'] . '.*jpg' );

				$picture_path = end( $picture_path );
			}

			if ( $picture_path )
			{
				$size = getimagesize( $picture_path );

				if ( $size[1] / $size[0] > 172 / 130 )
				{
					echo '<tr><td style="width:130px;"><img src="' . URLEscape( $picture_path ) . '" height="172"></td></tr>';
				}
				else
				{
					echo '<tr><td style="width:130px;"><img src="' . URLEscape( $picture_path ) . '" width="130"></td></tr>';
				}
			}
			else
			{
				echo '<tr><td style="width:130px; height:172px;"></td></tr>';
			}

			echo '<tr><td><span class="size-1"><b>' . $teacher['LAST_NAME'] . '</b><br />' . $teacher['FIRST_NAME'] . '</span></td></tr>';
			echo '</table></td>';
			$i++;
		}

		foreach ( (array) $RET as $student )
		{
			$student_id = $student['STUDENT_ID'];

			if ( $i++ % 5 == 0 )
			{
				echo '<tr>';
			}

			echo '<td style="vertical-align:bottom;"><table>';

			// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
			$picture_path = (array) glob( $StudentPicturesPath . '*/' . $student_id . '.*jpg' );

			$picture_path = end( $picture_path );

			if ( $picture_path )
			{
				$size = getimagesize( $picture_path );

				if ( $size[1] / $size[0] > 172 / 130 )
				{
					echo '<tr><td style="width:130px;"><img src="' . URLEscape( $picture_path ) . '" height="172"></td></tr>';
				}
				else
				{
					echo '<tr><td style="width:130px;"><img src="' . URLEscape( $picture_path ) . '" width="130"></td></tr>';
				}
			}
			else
			{
				echo '<tr><td style="width:130px; height:172px;"></td></tr>';
			}

			echo '<tr><td><span class="size-1"><b>' . $student['LAST_NAME'] . '</b><br />' . $student['FIRST_NAME'] . '</span></td></tr>';
			echo '</table></td>';

			if ( $i % 5 == 0 )
			{
				echo '</tr>';
			}
		}

		if ( $i % 5 != 0 )
		{
			echo '</tr>';
		}

		echo '</table><div style="page-break-after: always;"></div>';
	}

	// Do NOT use SetUserCoursePeriod() here (even only for teachers) as CP may be in another Semester, Quarter, etc.
	$_SESSION['UserCoursePeriod'] = $PCP_UserCoursePeriod;

	if ( $no_students_backprompt )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	PDFStop( $handle );
}

if ( ! $_REQUEST['modfunc']
	|| $_REQUEST['modfunc'] === 'list' )
{
	DrawHeader( ProgramTitle() );

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$_REQUEST['modfunc'] = 'list';
	}

	$extra = issetVal( $extra, [] );

	if ( $_REQUEST['modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Class Pictures for Selected Course Periods' ) );

		$extra['extra_header_left'] = '<table>';

		//FJ add <label> on checkbox
		$extra['extra_header_left'] .= '<tr class="st"><td><label><input type="checkbox" name="include_teacher" value="Y" checked /> ' . _( 'Include Teacher' ) . '</label></td>';

		if ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
		{
			$extra['extra_header_left'] .= '<tr><td colspan="3"><label><input type="checkbox" name="include_inactive" value="Y"> ' . _( 'Include Inactive Students' ) . '</label></td></tr>';
		}

		$extra['extra_header_left'] .= '</table>';
	}

	ClassSearchWidget( $extra );

	if ( $_REQUEST['modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Class Pictures for Selected Course Periods' ) ) . '</div>';
		echo '</form>';
	}
}

