<?php

DrawHeader( ProgramTitle() );

if ( ! $_REQUEST['modfunc']
	&& $_REQUEST['search_modfunc'] !== 'list' )
{
	unset( $_SESSION['MassDrops.php'] );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( $_SESSION['MassDrops.php'] )
	{
		if ( isset( $_REQUEST['student'] )
			&& is_array( $_REQUEST['student'] ) )
		{
			$drop_date = RequestedDate( 'drop', '' );

			if ( $drop_date )
			{
				$course_mp = DBGetOne( "SELECT MARKING_PERIOD_ID
					FROM COURSE_PERIODS
					WHERE COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'" );

				$course_mp_table = GetMP( $course_mp, 'MP' );

				if ( $course_mp_table == 'FY' || $course_mp == $_REQUEST['marking_period_id'] || mb_strpos( GetChildrenMP( $course_mp_table, $course_mp ), "'" . $_REQUEST['marking_period_id'] . "'" ) !== false )
				{
					$mp_table = GetMP( $_REQUEST['marking_period_id'], 'MP' );
					//$current_RET = DBGet( "SELECT STUDENT_ID FROM SCHEDULE WHERE COURSE_PERIOD_ID='".$_SESSION['MassDrops.php']['course_period_id']."' AND SYEAR='".UserSyear()."' AND (('".$start_date."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL) AND '".$start_date."'>=START_DATE)",array(),array('STUDENT_ID'));
					$current_RET = DBGet( "SELECT STUDENT_ID
						FROM SCHEDULE
						WHERE COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "' " );

					foreach ( (array) $_REQUEST['student'] as $student_id )
					{
						if ( $current_RET[$student_id]
							&& empty( $schedule_deletion_pending ) )
						{
							DBQuery( "UPDATE SCHEDULE
								SET END_DATE='" . $drop_date . "'
								WHERE STUDENT_ID='" . $student_id . "'
								AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'" );

							//$start_end_RET = DBGet( "SELECT START_DATE,END_DATE FROM SCHEDULE WHERE STUDENT_ID='".UserStudentID()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND END_DATE<START_DATE" );
							$start_end_RET = DBGet( "SELECT START_DATE,END_DATE
								FROM SCHEDULE
								WHERE STUDENT_ID='" . $student_id . "'
								AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'
								AND END_DATE<START_DATE" );

							//User is asked if he wants absences and grades to be deleted

							if ( ! empty( $start_end_RET ) )
							{
								//if user clicked Cancel or OK or Display Prompt

								if ( isset( $_REQUEST['delete_ok'] )
									|| DeletePrompt( _( 'Students\' Absences and Grades' ), 'Delete', false ) )
								{
									// Group SQL deletes.
									$delete_sql = '';

									//if user clicked OK

									if ( ! isset( $_REQUEST['delete_cancel'] ) )
									{
										$delete_sql .= "DELETE FROM GRADEBOOK_GRADES
											WHERE STUDENT_ID='" . $student_id . "'
											AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM STUDENT_REPORT_CARD_GRADES
											WHERE STUDENT_ID='" . $student_id . "'
											AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM STUDENT_REPORT_CARD_COMMENTS
											WHERE STUDENT_ID='" . $student_id . "'
											AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "';";

										$delete_sql .= "DELETE FROM ATTENDANCE_PERIOD
											WHERE STUDENT_ID='" . $student_id . "'
											AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "';";
									}

									//else simply delete schedule entry

									$delete_sql .= "DELETE FROM SCHEDULE
										WHERE STUDENT_ID='" . $student_id . "'
										AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "';";

									DBQuery( $delete_sql );

									//hook
									do_action( 'Scheduling/MassDrops.php|drop_student' );
								}
								else
								{
									$schedule_deletion_pending = true;
								}
							}
							else
							{
								DBQuery( "DELETE FROM ATTENDANCE_PERIOD
									WHERE STUDENT_ID='" . $student_id . "'
									AND COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'
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
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';
		DrawHeader( '', SubmitButton( _( 'Drop Course for Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Course to Drop' ) );

		echo '<table><tr><td colspan="2"><div id=course_div>';

		if ( $_SESSION['MassDrops.php'] )
		{
			$course_title = DBGetOne( "SELECT TITLE
				FROM COURSES
				WHERE COURSE_ID='" . $_SESSION['MassDrops.php']['course_id'] . "'" );

			$period_title = DBGetOne( "SELECT TITLE
				FROM COURSE_PERIODS
				WHERE COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'" );

			echo $course_title . '<br />' . $period_title;
		}

		echo '</div>' . '<a href="#" onclick=\'popups.open(
				"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=choose_course"
			); return false;\'>' . _( 'Choose a Course' ) . '</a></td></tr>';

		echo '<tr class="st"><td>' . _( 'Drop Date' ) . '</td>
			<td>' . DateInput( DBDate(), 'drop', '', false, false ) . '</td></tr>';

		echo '<tr class="st"><td>' . _( 'Marking Period' ) . '</td><td>';
		echo '<select name=marking_period_id>';

		$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE," .
			db_case( array( 'MP', "'FY'", "'0'", "'SEM'", "'1'", "'QTR'", "'2'" ) ) . " AS TBL
			FROM SCHOOL_MARKING_PERIODS
			WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY TBL,SORT_ORDER" );

		foreach ( (array) $mp_RET as $mp )
		{
			echo '<option value="' . $mp['MARKING_PERIOD_ID'] . '">' . $mp['TITLE'] . '</option>';
		}

		echo '</select>';
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

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
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
		$_SESSION['MassDrops.php']['subject_id'] = isset( $_REQUEST['subject_id'] ) ? $_REQUEST['subject_id'] : null;
		$_SESSION['MassDrops.php']['course_id'] = isset( $_REQUEST['course_id'] ) ? $_REQUEST['course_id'] : null;
		$_SESSION['MassDrops.php']['course_period_id'] = isset( $_REQUEST['course_period_id'] ) ? $_REQUEST['course_period_id'] : null;

		$course_title = DBGetOne( "SELECT TITLE
			FROM COURSES
			WHERE COURSE_ID='" . $_SESSION['MassDrops.php']['course_id'] . "'" );

		$period_title = DBGetOne( "SELECT TITLE
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $_SESSION['MassDrops.php']['course_period_id'] . "'" );

		echo '<script>opener.document.getElementById("course_div").innerHTML = ' .
			json_encode( $course_title . '<br />' . $period_title ) . '; window.close();</script>';
	}
}
