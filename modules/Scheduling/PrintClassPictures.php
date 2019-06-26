<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['cp_arr'] ) )
	{
		$cp_list = "'" . implode( "','", $_REQUEST['cp_arr'] ) . "'";

		//FJ multiple school periods for a course period
		//$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID,cp.MARKING_PERIOD_ID,cp.MP FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID IN ($cp_list) ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)" );
		$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,TEACHER_ID,cp.MARKING_PERIOD_ID,cp.MP
			FROM COURSE_PERIODS cp
			WHERE cp.COURSE_PERIOD_ID IN (" . $cp_list . ")
			ORDER BY cp.SHORT_NAME,cp.TITLE" );
		//echo '<pre>'; var_dump($course_periods_RET); echo '</pre>';

		if ( $_REQUEST['include_teacher'] == 'Y' )
		{
			$teachers_RET = DBGet( "SELECT STAFF_ID,LAST_NAME,FIRST_NAME,ROLLOVER_ID
				FROM STAFF
				WHERE STAFF_ID IN (SELECT TEACHER_ID
					FROM COURSE_PERIODS
					WHERE COURSE_PERIOD_ID IN (" . $cp_list . "))", array(), array( 'STAFF_ID' ) );
		}

		//echo '<pre>'; var_dump($teachers_RET); echo '</pre>';

		$handle = PDFStart();

		$PCP_UserCoursePeriod = UserCoursePeriod(); // save/restore for teachers

		$no_students_backprompt = true;

		foreach ( (array) $course_periods_RET as $course_period )
		{
			$course_period_id = $course_period['COURSE_PERIOD_ID'];
			$teacher_id = $course_period['TEACHER_ID'];

			if ( $teacher_id )
			{
				$_SESSION['UserCoursePeriod'] = $course_period_id;

				$extra = array( 'SELECT_ONLY' => 's.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME', 'ORDER_BY' => 's.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME', 'MP' => $course_period['MARKING_PERIOD_ID'], 'MPTable' => $course_period['MP'] );

				if ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' )
				{
					// FJ prevent course period ID hacking.
					$extra['WHERE'] .= " AND '" . UserStudentID() . "' IN
					(SELECT STUDENT_ID
					FROM SCHEDULE
					WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
					AND '" . DBDate() . "'>=START_DATE
					AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";

					// Limit to UserCoursePeriod().
					$extra['FROM'] = "JOIN SCHEDULE ss ON (ss.COURSE_PERIOD_ID='" . $course_period_id . "'
						AND s.STUDENT_ID=ss.STUDENT_ID)";

					// Do NOT use GetStuList() otherwise limited to UserStudentID().
					$RET = DBGet( "SELECT s.STUDENT_ID,s.LAST_NAME,s.FIRST_NAME
						FROM STUDENTS s
						JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
							AND ssm.SYEAR='" . UserSyear() . "'
							AND ssm.SCHOOL_ID='" . UserSchool() . "'
							AND ('" . DBDate() . "'>=ssm.START_DATE
								AND (ssm.END_DATE IS NULL OR '" . DBDate() . "'<=ssm.END_DATE)))" .
						$extra['FROM'] .
						"WHERE TRUE" . $extra['WHERE'] .
						" ORDER BY " . $extra['ORDER_BY'] );
				}
				elseif ( User( 'PROFILE' ) === 'teacher' )
				{
					$extra['WHERE'] .= " AND '" . User( 'STAFF_ID' ) . "'=(SELECT TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')";
				}
				elseif ( User( 'PROFILE' ) === 'admin' )
				{
					$extra['WHERE'] .= " AND s.STUDENT_ID IN
					(SELECT STUDENT_ID
					FROM SCHEDULE
					WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
					AND '" . DBDate() . "'>=START_DATE
					AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";
				}

				if ( ! isset( $RET ) )
				{
					$RET = GetStuList( $extra );
				}

				//echo '<pre>'; var_dump($RET); echo '</pre>';

				if ( ! empty( $RET ) )
				{
					$no_students_backprompt = false;

					echo '<table class="width-100p">';
					//FJ school year over one/two calendar years format
					echo '<tr><td colspan="5" class="center"><h3>' . FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . ' - ' . $course_period['TITLE'] . '</h3></td></tr>';

					$i = 0;

					if ( $_REQUEST['include_teacher'] == 'Y' )
					{
						$teacher = $teachers_RET[$teacher_id][1];

						echo '<tr><td style="vertical-align:bottom;"><table>';

						if ( $UserPicturesPath && (  ( $size = @getimagesize( $picture_path = $UserPicturesPath . UserSyear() . '/' . $teacher_id . '.JPG' ) ) || $_REQUEST['last_year'] == 'Y' && $staff['ROLLOVER_ID'] && ( $size = @getimagesize( $picture_path = $UserPicturesPath . ( UserSyear() - 1 ) . '/' . $staff['ROLLOVER_ID'] . '.JPG' ) ) ) )
						{
							if ( $size[1] / $size[0] > 172 / 130 )
							{
								echo '<tr><td style="width:130px;"><img src="' . $picture_path . '" height="172"></td></tr>';
							}
							else
							{
								echo '<tr><td style="width:130px;"><img src="' . $picture_path . '" width="130"></td></tr>';
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

						if ( $StudentPicturesPath && (  ( $size = @getimagesize( $picture_path = $StudentPicturesPath . UserSyear() . '/' . $student_id . '.jpg' ) ) || $_REQUEST['last_year'] == 'Y' && ( $size = @getimagesize( $picture_path = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . $student_id . '.jpg' ) ) ) )
						{
							if ( $size[1] / $size[0] > 172 / 130 )
							{
								echo '<tr><td style="width:130px;"><img src="' . $picture_path . '" height="172"></td></tr>';
							}
							else
							{
								echo '<tr><td style="width:130px;"><img src="' . $picture_path . '" width="130"></td></tr>';
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
							echo '</tr><!-- NEED 2in -->';
						}
					}

					if ( $i % 5 != 0 )
					{
						echo '</tr>';
					}

					echo '</table><div style="page-break-after: always;"></div>';
				}
			}
		}

		$_SESSION['UserCoursePeriod'] = $PCP_UserCoursePeriod;

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

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$_REQUEST['search_modfunc'] = 'list';
	}

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Class Pictures for Selected Course Periods' ) );

		$extra['extra_header_left'] = '<table>';

		//FJ add <label> on checkbox
		$extra['extra_header_left'] .= '<tr class="st"><td><label><input type="checkbox" name="include_teacher" value="Y" checked /> ' . _( 'Include Teacher' ) . '</label></td>';
		$extra['extra_header_left'] .= '<td><label><input type="checkbox" name="last_year" value="Y"> ' . _( 'Use Last Year\'s if Missing' ) . '</label></td></tr>';

		if ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
		{
			$extra['extra_header_left'] .= '<tr><td colspan="3"><label><input type="checkbox" name="include_inactive" value="Y"> ' . _( 'Include Inactive Students' ) . '</label></td></tr>';
		}

		$extra['extra_header_left'] .= '</table>';
	}

	mySearch( 'course_period', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Class Pictures for Selected Course Periods' ) ) . '</div>';
		echo '</form>';
	}
}

/**
 * @param $type
 * @param $extra
 */
function mySearch( $type, $extra = '' )
{
	global $extra;

	if ( empty( $_REQUEST['search_modfunc'] ) )
	{
		$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], array( 'bottom_back' ) );

		if ( $_SESSION['Back_PHP_SELF'] != 'course' )
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset( $_SESSION['List_PHP_SELF'] );
		}

		echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';

		echo '<br />';

		PopTable( 'header', _( 'Find a Course' ) );

		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&search_modfunc=list&next_modname=' . $_REQUEST['next_modname'] . '" method="POST">';

		echo '<table>';

		$RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
			FROM STAFF
			WHERE PROFILE='teacher'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
			AND SYEAR='" . UserSyear() . "'
			ORDER BY FULL_NAME" );

		echo '<tr class="st"><td>' . _( 'Teacher' ) . '</td><td>';

		echo '<select name="teacher_id"><option value="">' . _( 'N/A' ) . '</option>';

		foreach ( (array) $RET as $teacher )
		{
			echo '<option value="' . $teacher['STAFF_ID'] . '">' . $teacher['FULL_NAME'] . '</option>';
		}

		echo '</select></td></tr>';

		$RET = DBGet( "SELECT SUBJECT_ID,TITLE
			FROM COURSE_SUBJECTS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TITLE" );

		echo '<tr class="st"><td>' . _( 'Subject' ) . '</td><td>';

		echo '<select name="subject_id"><option value="">' . _( 'N/A' ) . '</option>';

		foreach ( (array) $RET as $subject )
		{
			echo '<option value="' . $subject['SUBJECT_ID'] . '">' . $subject['TITLE'] . '</option>';
		}

		echo '</select></td></tr>';

		$RET = DBGet( "SELECT PERIOD_ID,TITLE
			FROM SCHOOL_PERIODS
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER" );

		echo '<tr class="st"><td>' . _( 'Period' ) . '</td><td>';

		echo '<select name="period_id"><option value="">' . _( 'N/A' ) . '</option>';

		foreach ( (array) $RET as $period )
		{
			echo '<option value="' . $period['PERIOD_ID'] . '">' . $period['TITLE'] . '</option>';
		}

		echo '</select></td></tr>';

		Widgets( 'course' );
		echo $extra['search'];

		echo '<tr><td colspan="2" class="center">';
		echo '<br />';
		echo Buttons( _( 'Submit' ), _( 'Reset' ) );

		echo '</td></tr></table></form>';

		PopTable( 'footer' );
	}
	else
	{
		DrawHeader( '', $extra['header_right'] );
		DrawHeader( $extra['extra_header_left'], $extra['extra_header_right'] );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			if ( ! empty( $_REQUEST['teacher_id'] ) )
			{
				$where .= " AND cp.TEACHER_ID='" . $_REQUEST['teacher_id'] . "'";
			}

			if ( ! empty( $_REQUEST['first'] ) )
			{
				$where .= " AND UPPER(s.FIRST_NAME) LIKE '" . mb_strtoupper( $_REQUEST['first'] ) . "%'";
			}

			if ( ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				if ( $_REQUEST['w_course_period_id_which'] == 'course' )
				{
					$where .= " AND cp.COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "')";
				}
				else
				{
					$where .= " AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'";
				}
			}

			if ( ! empty( $_REQUEST['subject_id'] ) )
			{
				$from .= ",COURSES c";
				$where .= " AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID='" . $_REQUEST['subject_id'] . "'";
			}

			//FJ multiple school periods for a course period

			if ( ! empty( $_REQUEST['period_id'] ) )
			{
				//$where .= " AND cp.PERIOD_ID='".$_REQUEST['period_id']."'";
				$where .= " AND cpsp.PERIOD_ID='" . $_REQUEST['period_id'] . "' AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";
				$from .= ",COURSE_PERIOD_SCHOOL_PERIODS cpsp";
			}

			//$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp$from WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND sp.PERIOD_ID=cp.PERIOD_ID$where";
			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE FROM COURSE_PERIODS cp$from WHERE cp.SCHOOL_ID='" . UserSchool() . "' AND cp.SYEAR='" . UserSyear() . "'$where";
		}
		elseif ( User( 'PROFILE' ) === 'teacher' )
		{
			//FJ multiple school periods for a course period
			//$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND sp.PERIOD_ID=cp.PERIOD_ID";
			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE FROM COURSE_PERIODS cp WHERE cp.SCHOOL_ID='" . UserSchool() . "' AND cp.SYEAR='" . UserSyear() . "' AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'";
		}
		else
		{
			//FJ multiple school periods for a course period
			//$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,sp.ATTENDANCE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHEDULE ss WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.SYEAR='".UserSyear()."' AND ss.STUDENT_ID='".UserStudentID()."' AND (CURRENT_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR CURRENT_DATE<=ss.END_DATE)) AND sp.PERIOD_ID=cp.PERIOD_ID";
			$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE FROM COURSE_PERIODS cp,SCHEDULE ss WHERE cp.SCHOOL_ID='" . UserSchool() . "' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.SYEAR='" . UserSyear() . "' AND ss.STUDENT_ID='" . UserStudentID() . "' AND (CURRENT_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR CURRENT_DATE<=ss.END_DATE))";
		}

		//$sql .= ' ORDER BY sp.PERIOD_ID';

		$LO_columns = array( 'COURSE_PERIOD_ID' => MakeChooseCheckbox( 'Y', '', 'cp_arr' ), 'TITLE' => _( 'Course Period' ) );

		$course_periods_RET = DBGet( $sql, array( 'COURSE_PERIOD_ID' => 'MakeChooseCheckbox' ) );

		if ( empty( $_REQUEST['LO_save'] ) && ! $extra['suppress_save'] )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], array( 'bottom_back' ) );

			if ( $_SESSION['Back_PHP_SELF'] != 'course' )
			{
				$_SESSION['Back_PHP_SELF'] = 'course';
				unset( $_SESSION['Search_PHP_SELF'] );
			}

			if ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
			{
				echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
			}
		}

		echo '<input type="hidden" name="relation">';
		ListOutput( $course_periods_RET, $LO_columns, 'Course Period', 'Course Periods' );
	}
}
