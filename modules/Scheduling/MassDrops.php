<?php

DrawHeader( ProgramTitle() );

if ( ! $_REQUEST['modfunc']
	&& $_REQUEST['search_modfunc'] !== 'list' )
{
	unset( $_SESSION['MassDrops.php'] );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_SESSION['MassDrops.php'] ) )
	{
		if ( isset( $_REQUEST['student'] )
			&& is_array( $_REQUEST['student'] ) )
		{
			$drop_date = RequestedDate( 'drop', '' );

			if ( $drop_date )
			{
				$course_mp = DBGetOne( "SELECT MARKING_PERIOD_ID
					FROM course_periods
					WHERE COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'
					AND SYEAR='" . UserSyear() . "'" );

				$course_mp_table = GetMP( $course_mp, 'MP' );

				if ( $course_mp_table == 'FY' || $course_mp == $_REQUEST['marking_period_id'] || mb_strpos( GetChildrenMP( $course_mp_table, $course_mp ), "'" . $_REQUEST['marking_period_id'] . "'" ) !== false )
				{
					$mp_table = GetMP( $_REQUEST['marking_period_id'], 'MP' );
					//$current_RET = DBGet( "SELECT STUDENT_ID FROM schedule WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' AND SYEAR='".UserSyear()."' AND (('".$start_date."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL) AND '".$start_date."'>=START_DATE)",array(),array('STUDENT_ID'));
					$current_RET = DBGet( "SELECT STUDENT_ID
						FROM schedule
						WHERE COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'", [], [ 'STUDENT_ID' ] );

					foreach ( (array) $_REQUEST['student'] as $student_id )
					{
						if ( ! empty( $current_RET[$student_id] )
							&& empty( $schedule_deletion_pending ) )
						{
							DBQuery( "UPDATE schedule
								SET END_DATE='" . $drop_date . "'
								WHERE STUDENT_ID='" . (int) $student_id . "'
								AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'" );

							//$start_end_RET = DBGet( "SELECT START_DATE,END_DATE FROM schedule WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND END_DATE<START_DATE" );
							$start_end_RET = DBGet( "SELECT START_DATE,END_DATE
								FROM schedule
								WHERE STUDENT_ID='" . (int) $student_id . "'
								AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'
								AND END_DATE<START_DATE" );

							if ( ! empty( $start_end_RET ) )
							{
								$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . "
									FROM students
									WHERE STUDENT_ID='" . $student_id . "'" );

								// User is asked if he wants absences and grades to be deleted.
								$delete_ok = DeletePrompt(
									_( 'Student\'s Absences and Grades' ) . ' (' . $student_name . ')',
									_( 'also delete' ),
									false
								);

								if ( $delete_ok )
								{
									// If user clicked Cancel or OK or Display Prompt.
									// Group SQL deletes.
									$delete_sql = '';

									if ( ! isset( $_REQUEST['delete_cancel'] ) )
									{
										// If user clicked OK.
										$delete_sql .= "DELETE FROM gradebook_grades
											WHERE STUDENT_ID='" . (int) $student_id . "'
											AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM student_report_card_grades
											WHERE STUDENT_ID='" . (int) $student_id . "'
											AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM student_report_card_comments
											WHERE STUDENT_ID='" . (int) $student_id . "'
											AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM attendance_period
											WHERE STUDENT_ID='" . (int) $student_id . "'
											AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "';";
									}

									// Else simply delete schedule entry.
									$delete_sql .= "DELETE FROM schedule
										WHERE STUDENT_ID='" . (int) $student_id . "'
										AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "';";

									DBQuery( $delete_sql );

									// Hook.
									do_action( 'Scheduling/MassDrops.php|drop_student' );
								}
								else
								{
									$schedule_deletion_pending = true;
								}
							}
							else
							{
								DBQuery( "DELETE FROM attendance_period
									WHERE STUDENT_ID='" . (int) $student_id . "'
									AND COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'
									AND SCHOOL_DATE>'" . $drop_date . "'" );
							}
						}
					}

					if ( empty( $schedule_deletion_pending ) )
					{
						$note[] = button( 'check' ) . '&nbsp;' . _( 'This course has been dropped for the selected students\' schedules.' );
					}
				}
				else
				{
					$error[] = _( 'You cannot schedule a student into that course during this marking period.' ) . ' ' . sprintf( _( 'This course meets on %s.' ), GetMP( $course_mp ) );
				}
			}
			else
			{
				$error[] = _( 'The date you entered is not valid' );
			}
		}
		else
		{
			$error[] = _( 'You must choose at least one student.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose a course.' );
	}

	if ( empty( $schedule_deletion_pending ) )
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );

		unset( $_SESSION['MassDrops.php'] );
	}
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

if ( $_REQUEST['modfunc'] != 'choose_course' )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';
		DrawHeader( '', SubmitButton( _( 'Drop Course for Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Course to Drop' ) );

		echo '<table><tr><td><div id="course_div">';

		if ( ! empty( $_SESSION['MassDrops.php'] ) )
		{
			$course_title = DBGetOne( "SELECT TITLE
				FROM courses
				WHERE COURSE_ID='" . (int) $_SESSION['MassDrops.php']['course_id'] . "'" );

			$period_title = DBGetOne( "SELECT TITLE
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'" );

			echo $course_title . '<br />' . $period_title . '<br /><br />';
		}

		$popup_url = URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=choose_course' );

		echo '</div><a href="#" onclick="' . AttrEscape( 'popups.open(
			' . json_encode( $popup_url ) . '
			); return false;' ) . '">' . _( 'Choose a Course' ) . '</a></td></tr>';

		echo '<tr><td><br />' . DateInput(
			DBDate(),
			'drop',
			_( 'Drop Date' ),
			false,
			false
		) . '</td></tr>';

		echo '<tr><td><select name="marking_period_id" id="marking_period_id">';

		// @since 11.1 SQL Use GetFullYearMP() & GetChildrenMP() functions to limit Marking Periods
		$fy_and_children_mp = "'" . GetFullYearMP() . "'";

		if ( GetChildrenMP( 'FY' ) )
		{
			$fy_and_children_mp .= "," . GetChildrenMP( 'FY' );
		}

		$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE," .
			db_case( [ 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ] ) . " AS TBL
			FROM school_marking_periods
			WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
			AND MARKING_PERIOD_ID IN(" . $fy_and_children_mp . ")
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TBL,SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

		foreach ( (array) $mp_RET as $mp )
		{
			echo '<option value="' . AttrEscape( $mp['MARKING_PERIOD_ID'] ) . '">' . $mp['TITLE'] . '</option>';
		}

		echo '</select>';

		echo FormatInputTitle( _( 'Marking Period' ), 'marking_period_id' );

		echo '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br />';
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] !== 'list' )
	{
		unset( $_SESSION['MassDrops.php'] );
	}

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) ];
	$extra['new'] = true;

	Widgets( 'course' );
	Widgets( 'request' );
	Widgets( 'activity' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Drop Course for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

if ( $_REQUEST['modfunc'] === 'choose_course' )
{
	if ( empty( $_REQUEST['course_period_id'] ) )
	{
		include 'modules/Scheduling/Courses.php';
	}
	else
	{
		$_SESSION['MassDrops.php']['subject_id'] = issetVal( $_REQUEST['subject_id'] );
		$_SESSION['MassDrops.php']['course_id'] = issetVal( $_REQUEST['course_id'] );
		$_SESSION['MassDrops.php']['course_period_id'] = issetVal( $_REQUEST['course_period_id'] );

		$course_title = DBGetOne( "SELECT TITLE
			FROM courses
			WHERE COURSE_ID='" . (int) $_SESSION['MassDrops.php']['course_id'] . "'" );

		$period_title = DBGetOne( "SELECT TITLE
			FROM course_periods
			WHERE COURSE_PERIOD_ID='" . (int) $_SESSION['MassDrops.php']['course_period_id'] . "'" );

		echo '<script>opener.document.getElementById("course_div").innerHTML = ' .
			json_encode( $course_title . '<br />' . $period_title ) . '; window.close();</script>';
	}
}
