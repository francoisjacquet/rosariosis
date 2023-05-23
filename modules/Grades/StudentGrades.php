<?php

require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

$do_stats = ProgramConfig( 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS' ) == 'Y'
	|| (  ( User( 'PROFILE' ) === 'teacher'
	|| User( 'PROFILE' ) === 'admin' )
	&& ProgramConfig( 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS' ) == 'Y' );

$_REQUEST['include_inactive'] = issetVal( $_REQUEST['include_inactive'], '' );
$_REQUEST['do_stats'] = issetVal( $_REQUEST['do_stats'], '' );

$_ROSARIO['allow_edit'] = false;

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

// @since 10.9.1 Show Gradebook Grades of Inactive Students (School status)
// Maintain "Include Inactive Students" user choice in URL.
$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'];

Search( 'student_id', $extra );

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	//FJ multiple school periods for a course period
	/*$courses_RET = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,courses c WHERE s.SYEAR='".UserSyear()."' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND s.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") AND ('".DBDate()."'>=s.START_DATE AND (s.END_DATE IS NULL OR '".DBDate()."'<=s.END_DATE)) AND s.STUDENT_ID='".UserStudentID()."' AND cp.GRADE_SCALE_ID IS NOT NULL".(User( 'PROFILE' ) === 'teacher'?' AND cp.TEACHER_ID=\''.User('STAFF_ID').'\'':'')." AND c.COURSE_ID=cp.COURSE_ID ORDER BY (SELECT SORT_ORDER FROM school_periods WHERE PERIOD_ID=cp.PERIOD_ID)",array(),array('COURSE_PERIOD_ID'));*/

	// @since 10.9.1 SQL Show Gradebook Grades of Inactive Students (Course status, maybe dropped as of today) (Only if has grades)
	$courses_RET = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID
	FROM schedule s,course_periods cp,courses c
	WHERE s.SYEAR='" . UserSyear() . "'
	AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID
	AND s.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
	AND '" . DBDate() . "'>=s.START_DATE
	AND ((s.END_DATE IS NULL OR '" . DBDate() . "'<=s.END_DATE)
		OR EXISTS(SELECT 1 FROM gradebook_grades gg
		WHERE gg.STUDENT_ID=s.STUDENT_ID
		AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
		LIMIT 1))
	AND s.STUDENT_ID='" . UserStudentID() . "'
	AND cp.GRADE_SCALE_ID IS NOT NULL" .
		( User( 'PROFILE' ) === 'teacher' ? ' AND cp.TEACHER_ID=\'' . User( 'STAFF_ID' ) . '\'' : '' ) . "
	AND c.COURSE_ID=cp.COURSE_ID
	ORDER BY cp.SHORT_NAME, cp.TITLE", [], [ 'COURSE_PERIOD_ID' ] );
//echo '<pre>'; var_dump($courses_RET); echo '</pre>';

	if ( isset( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'all'
		&& empty( $courses_RET[$_REQUEST['id']] ) )
	{
		// Unset ID & redirect URL.
		RedirectURL( 'id' );
	}

	if ( empty( $_REQUEST['id'] ) )
	{
		DrawHeader( _( 'Totals' ), '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&id=all' . ( $do_stats ? '&do_stats=' . $_REQUEST['do_stats'] : '' ) ) . '">' .
			_( 'Expand All' ) . '</a>' );

		if ( $do_stats )
		{
			DrawHeader(
				'',
				CheckBoxOnclick( 'do_stats', _( 'Include Anonymous Statistics' ) )
			);
		}

		$LO_columns = [ 'TITLE' => _( 'Course Title' ), 'TEACHER' => _( 'Teacher' ), 'UNGRADED' => _( 'Ungraded' ) ];

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
		{
			$LO_columns['PERCENT'] = _( 'Percent' );
		}

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
		{
			$LO_columns['GRADE'] = _( 'Letter' );
		}

		if ( $do_stats && $_REQUEST['do_stats'] )
		{
			$LO_columns += [ 'BAR1' => _( 'Grade Range' ), 'BAR2' => _( 'Class Rank' ) ];
		}

		if ( ! empty( $courses_RET ) )
		{
			$LO_ret = [ 0 => [] ];

			foreach ( (array) $courses_RET as $course_period_id => $course )
			{
				$course = $course[1];
				$staff_id = $course['STAFF_ID'];
				$course_id = $course['COURSE_ID'];
				$course_title = $course['TITLE'];
				//echo $staff_id.'+'.$course_id.'+'.$course_period_id.'+'.$course_title.'|';
				$assignments_RET = DBGet( "SELECT ASSIGNMENT_ID,TITLE,POINTS
					FROM gradebook_assignments
					WHERE STAFF_ID='" . (int) $staff_id . "'
					AND (COURSE_ID='" . (int) $course_id . "' OR COURSE_PERIOD_ID='" . (int) $course_period_id . "')
					AND MARKING_PERIOD_ID='" . UserMP() . "'
					ORDER BY DUE_DATE DESC,ASSIGNMENT_ID" );
				//echo '<pre>'; var_dump($assignments_RET); echo '</pre>';

				$gradebook_config[$staff_id] = ProgramUserConfig( 'Gradebook', $staff_id );

				$gradebook_config[$staff_id]['LATENCY'] = issetVal( $gradebook_config[$staff_id]['LATENCY'] );

				$gradebook_config[$staff_id]['WEIGHT'] = issetVal( $gradebook_config[$staff_id]['WEIGHT'] );

				// @since 8.8 Exclude 0 points assignments from Ungraded count.
				$sql = "SELECT s.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,
				sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'gg.POINTS' ) ) . ") AS PARTIAL_POINTS,
				sum(" . db_case( array( 'gg.POINTS', "'-1'", "'0'", 'ga.POINTS' ) ) . ") AS PARTIAL_TOTAL,
				gt.FINAL_GRADE_PERCENT,sum(CASE WHEN gg.POINTS IS NULL AND ga.POINTS>0 THEN 1 ELSE 0 END) AS UNGRADED
				FROM students s
				JOIN schedule ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='" . UserSyear() . "'";

				// @since 10.9.1 SQL Show Gradebook Grades of Inactive Students (Course status, maybe dropped as of today)
				$sql .= " AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ") AND CURRENT_DATE>=ss.START_DATE";

				$sql .= ") JOIN course_periods cp ON (cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='" . (int) $course_period_id . "')
				JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID AND ssm.SYEAR=ss.SYEAR AND ssm.SCHOOL_ID='" . UserSchool() . "'";

				if ( $_REQUEST['include_inactive'] == 'Y' )
				{
					$sql .= " AND ssm.ID=(SELECT ID FROM student_enrollment WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR=ssm.SYEAR ORDER BY START_DATE DESC LIMIT 1)";
				}
				else
				{
					$sql .= " AND (CURRENT_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR CURRENT_DATE<=ssm.END_DATE))";
				}

				$sql .= ") JOIN gradebook_assignments ga ON (((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID) AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='" . UserMP() . "') LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),gradebook_assignment_types gt";

				$sql .= " WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=(ga.DUE_DATE + INTERVAL " .
					( $DatabaseType === 'mysql' ? (int) $gradebook_config[$staff_id]['LATENCY'] . ' DAY' :
						"'" . (int) $gradebook_config[$staff_id]['LATENCY'] . " DAY'" ) . "))
					OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";

				$sql .= " AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))" . ( $do_stats && $_REQUEST['do_stats'] ? '' : " AND s.STUDENT_ID='" . UserStudentID() . "'" );

				$sql .= " GROUP BY gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

				if ( $do_stats
					&& $_REQUEST['do_stats'] )
				{
					$all_RET = DBGet( $sql, [], [ 'STUDENT_ID' ] );

					$points_RET = issetVal( $all_RET[UserStudentID()] );
				}
				else
				{
					$points_RET = DBGet( $sql );
				}

				//echo '<pre>'; var_dump($points_RET); echo '</pre>';
				//echo '<pre>'; var_dump($all_RET); echo '</pre>';

				if ( ! empty( $points_RET ) )
				{
					$total = $total_percent = 0;
					$ungraded = 0;

					foreach ( (array) $points_RET as $partial_points )
					{
						if ( $partial_points['PARTIAL_TOTAL'] != 0 || $gradebook_config[$staff_id]['WEIGHT'] != 'Y' )
						{
							$total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config[$staff_id]['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] : 1 );
							$total_percent += ( $gradebook_config[$staff_id]['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] : $partial_points['PARTIAL_TOTAL'] );
						}

						$ungraded += $partial_points['UNGRADED'];
					}

					if ( $total_percent != 0 )
					{
						$percent = $total / $total_percent;
					}
					else
					{
						$percent = false;
					}

					if ( $do_stats && $_REQUEST['do_stats'] )
					{
						$min_percent = $max_percent = $percent;
						$avg_percent = 0;
						$lower = $higher = 0;

						foreach ( (array) $all_RET as $xstudent_id => $student )
						{
							$total = $total_percent = 0;

							foreach ( (array) $student as $partial_points )
							{
								if ( $partial_points['PARTIAL_TOTAL'] != 0 || $gradebook_config[$staff_id]['WEIGHT'] != 'Y' )
								{
									$total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config[$staff_id]['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] : 1 );
									$total_percent += ( $gradebook_config[$staff_id]['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] : $partial_points['PARTIAL_TOTAL'] );
								}
							}

							if ( $total_percent != 0 )
							{
								$total /= $total_percent;

								if ( $min_percent === false || $total < $min_percent )
								{
									$min_percent = $total;
								}

								if ( $max_percent === false || $total > $max_percent )
								{
									$max_percent = $total;
								}

								$avg_percent += $total;

								if ( $xstudent_id != UserStudentID() && $percent !== false )
								{
									if ( $total > $percent )
									{
										$higher++;
									}
									else
									{
										$lower++;
									}
								}
							}
						}

						$avg_percent /= count( $all_RET );

						// FJ bargraph with rounded percent.
						//$bargraph1 = bargraph1($percent===false?true:$percent,$min_percent,$avg_percent,$max_percent,1);
						$bargraph1 = bargraph1(
							$percent === false ?
							true : _makeLetterGrade( $percent, $course_period_id, $staff_id, '%' ),
							_makeLetterGrade( $min_percent, $course_period_id, $staff_id, '%' ),
							_makeLetterGrade( $avg_percent, $course_period_id, $staff_id, '%' ),
							_makeLetterGrade( $max_percent, $course_period_id, $staff_id, '%' ),
							100
						);

						$bargraph2 = bargraph2( $percent === false ? true : 0, $lower, $higher );
					}

					if ( ! $ungraded )
					{
						$ungraded = '';
					}

					$LO_ret[] = [
						'ID' => $course_period_id,
						'TITLE' => $course['COURSE_TITLE'],
						'TEACHER' => mb_substr( $course_title, mb_strrpos( str_replace( ' - ', ' ^ ', $course_title ), '^' ) + 2 ),
						'PERCENT' => ( $percent !== false ?
							(float) number_format( 100 * $percent, 2, '.', '' ) . '%' :
							_( 'N/A' ) ),
						'GRADE' => $percent !== false ?
						'<b>' . _makeLetterGrade( $percent, $course_period_id, $staff_id ) . '</b>' :
						_( 'N/A' ),
						'UNGRADED' => $ungraded,
					]
					 	+ ( $do_stats && $_REQUEST['do_stats'] ?
						[ 'BAR1' => $bargraph1, 'BAR2' => $bargraph2 ] :
						[]
					);
				}

				//else
				//$LO_ret[] = array('ID' => $course_period_id,'TITLE' => $course['COURSE_TITLE'],'TEACHER'=>mb_substr($course_title,mb_strrpos(str_replace(' - ',' ^ ',$course_title),'^')+2));
			}

			unset( $LO_ret[0] );

			$link = [
				'TITLE' => [
					'link' => 'Modules.php?modname=' . $_REQUEST['modname'] .
					( $do_stats ? '&do_stats=' . $_REQUEST['do_stats'] : '' ) .
					// @since 10.9.1 Show Gradebook Grades of Inactive Students (School status)
					// Maintain "Include Inactive Students" user choice in URL.
					'&include_inactive=' . $_REQUEST['include_inactive'],
					'variables' => [ 'id' => 'ID' ],
				],
			];

			ListOutput(
				$LO_ret,
				$LO_columns,
				'Course',
				'Courses',
				$link,
				[],
				[ 'center' => false, 'save' => false, 'search' => false ]
			);
		}
		else
		{
			$warning[] = _( 'There are no grades available for this student.' );

			echo ErrorMessage( $warning, 'warning' );
		}
	}
	else
	{
		if ( $_REQUEST['id'] === 'all' )
		{
			DrawHeader( _( 'All Courses' ), '' );
		}
		else
		{
			$courses_RET = [ $_REQUEST['id'] => $courses_RET[$_REQUEST['id']] ];

			$req_course_title = $courses_RET[$_REQUEST['id']][1]['COURSE_TITLE'];

			DrawHeader(
				'<b>' . $req_course_title . '</b> - ' .
				mb_substr(
					$req_course_title,
					mb_strrpos( str_replace( ' - ', ' ^ ', $req_course_title ), ' ^' )
				),
				'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				( $do_stats ? '&do_stats=' . $_REQUEST['do_stats'] : '' ) .
				// @since 10.9.1 Show Gradebook Grades of Inactive Students (School status)
				// Maintain "Include Inactive Students" user choice in URL.
				'&include_inactive=' . $_REQUEST['include_inactive'] ) . '">' .
				_( 'Back to Totals' ) . '</a>'
			);
		}

		if ( $do_stats )
		{
			DrawHeader(
				'',
				CheckBoxOnclick( 'do_stats', _( 'Include Anonymous Statistics' ) )
			);
		}

		//echo '<pre>'; var_dump($courses_RET); echo '</pre>';

		foreach ( (array) $courses_RET as $course_period_id => $course )
		{
			$course = $course[1];
			$staff_id = $course['STAFF_ID'];

			if ( ! isset( $gradebook_config[$staff_id] ) )
			{
				$gradebook_config[$staff_id] = ProgramUserConfig( 'Gradebook', $staff_id );

				$gradebook_config[$staff_id]['LATENCY'] = issetVal( $gradebook_config[$staff_id]['LATENCY'] );

				$gradebook_config[$staff_id]['WEIGHT'] = issetVal( $gradebook_config[$staff_id]['WEIGHT'] );
			}

			//FJ assigments appear after assigned date and not due date
			$assignments_RET = DBGet( "SELECT ga.ASSIGNMENT_ID,gg.POINTS,gg.COMMENT,ga.TITLE,
				ga.DESCRIPTION,ga.ASSIGNED_DATE,ga.DUE_DATE,ga.POINTS AS POINTS_POSSIBLE,ga.WEIGHT,
				at.TITLE AS CATEGORY,at.COLOR AS ASSIGNMENT_TYPE_COLOR,ga.STAFF_ID,ga.FILE,
				ga.COURSE_ID,'" . DBEscapeString( $course['COURSE_TITLE'] ) . "' AS COURSE_TITLE
			FROM gradebook_assignments ga
			LEFT OUTER JOIN gradebook_grades gg ON (gg.COURSE_PERIOD_ID='" . (int) $course['COURSE_PERIOD_ID'] . "' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.STUDENT_ID='" . UserStudentID() . "'),
			gradebook_assignment_types at
			WHERE (ga.COURSE_PERIOD_ID='" . (int) $course['COURSE_PERIOD_ID'] . "' OR ga.COURSE_ID='" . (int) $course['COURSE_ID'] . "' AND ga.STAFF_ID='" . (int) $staff_id . "')
			AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
			AND at.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
			AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
				AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=(ga.DUE_DATE + INTERVAL " .
				( $DatabaseType === 'mysql' ? (int) $gradebook_config[$staff_id]['LATENCY'] . ' DAY' :
					"'" . (int) $gradebook_config[$staff_id]['LATENCY'] . " DAY'" ) . "))
				OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)
				OR gg.POINTS IS NOT NULL)
			AND (ga.POINTS!='0' OR gg.POINTS IS NOT NULL AND gg.POINTS!='-1')
			ORDER BY at.TITLE,ga.ASSIGNMENT_ID DESC", [ 'TITLE' => '_makeTipAssignment', 'CATEGORY' => '_makeCategory' ] );
			//echo '<pre>'; var_dump($assignments_RET); echo '</pre>';

			if ( ! empty( $assignments_RET ) )
			{
				if ( $do_stats && $_REQUEST['do_stats'] )
				//FJ bugfix broken statistics, MIN calculation when gg.POINTS is NULL
				{
					$all_RET = DBGet( "SELECT ga.ASSIGNMENT_ID,
					min(" . db_case( [ 'gg.POINTS', "'-1'", 'ga.POINTS', db_case( [ 'gg.POINTS', "''", '0', 'gg.POINTS' ] ) ] ) . ") AS MIN,
					max(" . db_case( [ 'gg.POINTS', "'-1'", '0', 'gg.POINTS' ] ) . ") AS MAX,
					" . db_case( [ "sum(" . db_case( [ 'gg.POINTS', "'-1'", '0', '1' ] ) . ")", "'0'", "'0'", "sum(" . db_case( [ 'gg.POINTS', "'-1'", '0', 'gg.POINTS' ] ) . ") / sum(" . db_case( [ 'gg.POINTS', "'-1'", '0', '1' ] ) . ")" ] ) . " AS AVG,
					sum(CASE WHEN gg.POINTS!='-1' AND gg.POINTS<=g.POINTS AND gg.STUDENT_ID!=g.STUDENT_ID THEN 1 ELSE 0 END) AS LOWER,
					sum(CASE WHEN gg.POINTS!='-1' AND gg.POINTS>g.POINTS THEN 1 ELSE 0 END) AS HIGHER
					FROM gradebook_grades gg,gradebook_assignments ga
					LEFT OUTER JOIN gradebook_grades g ON (g.COURSE_PERIOD_ID='" . (int) $course['COURSE_PERIOD_ID'] . "' AND g.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND g.STUDENT_ID='" . UserStudentID() . "'),
					gradebook_assignment_types at
					WHERE (ga.COURSE_PERIOD_ID='" . (int) $course['COURSE_PERIOD_ID'] . "' OR ga.COURSE_ID='" . (int) $course['COURSE_ID'] . "' AND ga.STAFF_ID='" . (int) $staff_id . "')
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND at.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
					AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=(ga.DUE_DATE + INTERVAL " .
						( $DatabaseType === 'mysql' ? (int) $gradebook_config[$staff_id]['LATENCY'] . ' DAY' :
						"'" . (int) $gradebook_config[$staff_id]['LATENCY'] . " DAY'" ) . "))
						OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)
						OR g.POINTS IS NOT NULL)
					AND ga.POINTS!='0'
					GROUP BY ga.ASSIGNMENT_ID", [], [ 'ASSIGNMENT_ID' ] );
				}

				//echo '<pre>'; var_dump($all_RET); echo '</pre>';

				$LO_columns = [
					'TITLE' => _( 'Title' ),
					'CATEGORY' => _( 'Category' ),
					'POINTS' => _( 'Points / Possible' ),
				];

				if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
				{
					$LO_columns['PERCENT'] = _( 'Percent' );
				}

				if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
				{
					if ( empty( $gradebook_config[$staff_id]['LETTER_GRADE_ALL'] ) )
					{
						$LO_columns['LETTER'] = _( 'Letter' );
					}
				}

				$LO_columns += [ 'COMMENT' => _( 'Comment' ) ];

				if ( $do_stats && $_REQUEST['do_stats'] )
				{
					$LO_columns += [ 'BAR1' => _( 'Grade Range' ), 'BAR2' => _( 'Class Rank' ) ];
				}

				$LO_ret = [ 0 => [] ];

				foreach ( (array) $assignments_RET as $i => $assignment )
				{
					if ( $do_stats && $_REQUEST['do_stats'] )
					{
						if ( ! empty( $all_RET[$assignment['ASSIGNMENT_ID']] ) )
						{
							$all = $all_RET[$assignment['ASSIGNMENT_ID']][1];

							if ( $assignment['POINTS'] != '-1' && $assignment['POINTS'] != '' )
							{
								$bargraph1 = bargraph1(
									$assignment['POINTS'],
									$all['MIN'],
									$all['AVG'],
									$all['MAX'],
									$assignment['POINTS_POSSIBLE']
								);

								$bargraph2 = bargraph2( 0, $all['LOWER'], $all['HIGHER'] );
							}
							else
							{
								$bargraph1 = bargraph1(
									true,
									$all['MIN'],
									$all['AVG'],
									$all['MAX'],
									$assignment['POINTS_POSSIBLE']
								);

								$bargraph2 = bargraph2( true );
							}
						}
						else
						{
							$bargraph1 = bargraph1( false );
							$bargraph2 = bargraph2( false );
						}
					}

					$comment = $assignment['COMMENT'] . ( $assignment['POINTS'] == '' ?
						( $assignment['COMMENT'] ? '<br />' : '' ) .
						'<span style="color:red">' . _( 'No Grade' ) . '</span>' :
						'' );

					if ( mb_strlen( $comment ) > 60
						&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
					{
						// Comments length > 60 chars, responsive table ColorBox.
						$comment = '<div id="divStudentGradesComment' . $course_period_id . $i . '" class="rt2colorBox">' .
							$comment . '</div>';
					}

					$LO_ret[] = [
						'TITLE' => $assignment['TITLE'],
						'CATEGORY' => $assignment['CATEGORY'],
						'POINTS' => ( $assignment['POINTS'] == '-1' ?
							'*' :
							( $assignment['POINTS'] == '' ?
								'<span style="color:red">0</span>' :
								rtrim( rtrim( $assignment['POINTS'], '0' ), '.' ) ) )
						. ' / ' . $assignment['POINTS_POSSIBLE'],
						'PERCENT' => ( $assignment['POINTS_POSSIBLE'] == '0' ?
							_( 'E/C' ) :
							( $assignment['POINTS'] == '-1' ?
								'*' :
								(float) number_format( 100 * $assignment['POINTS'] / $assignment['POINTS_POSSIBLE'], 2, '.', '' ) . '%' ) ),
						'LETTER' => ( $assignment['POINTS_POSSIBLE'] == '0' ?
							_( 'N/A' ) :
							( $assignment['POINTS'] == '-1' ?
								_( 'N/A' ) :
								'<b>' . _makeLetterGrade(
									$assignment['POINTS'] / $assignment['POINTS_POSSIBLE'],
									$course['COURSE_PERIOD_ID'],
									$staff_id
								) . '</b>' ) ),
						'COMMENT' => $comment,
					] +
						( $do_stats && $_REQUEST['do_stats'] ?
						[ 'BAR1' => $bargraph1, 'BAR2' => $bargraph2 ] :
						[]
					);
				}

				if ( $_REQUEST['id'] == 'all' )
				{
					//echo '<br />';
					DrawHeader( '<b>' . mb_substr(
						$course['TITLE'],
						0,
						mb_strpos( str_replace( ' - ', ' ^ ', $course['TITLE'] ), '^' )
					) .
						'</b> - ' . mb_substr(
							$course['TITLE'],
							mb_strrpos( str_replace( ' - ', ' ^ ', $course['TITLE'] ), '^' ) + 2
						),
						'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
						( $do_stats ? '&do_stats=' . $_REQUEST['do_stats'] : '' ) ) . '">' .
						_( 'Back to Totals' ) . '</a>' );
				}

				unset( $LO_ret[0] );

				ListOutput(
					$LO_ret,
					$LO_columns,
					'Assignment',
					'Assignments',
					[],
					[],
					[ 'center' => false, 'save' => $_REQUEST['id'] != 'all', 'search' => false ]
				);
			}
			elseif ( $_REQUEST['id'] !== 'all' )
			{
				$warning[] = _( 'There are no grades available for this student.' );

				echo ErrorMessage( $warning, 'warning' );
			}
		}
	}
}

/**
 * Make Assignment Details
 *
 * @uses StudentAssignmentDrawHeaders()
 * @since 4.5 Move assignment details from Tip message to Colorbox popup
 * @since 10.4 Truncate Assignment title to 36 chars
 *
 * @param  string $value     Assignment Title
 * @param  string $column    'TITLE'
 * @return string Assignment Details inside Popup
 */
function _makeTipAssignment( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	if ( ! function_exists( 'StudentAssignmentDrawHeaders' ) )
	{
		// Include Student Assignments functions.
		require_once 'modules/Grades/includes/StudentAssignments.fnc.php';
	}

	// Truncate title to 36 chars.
	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	if (  ( $THIS_RET['DESCRIPTION']
		|| $THIS_RET['ASSIGNED_DATE']
		|| $THIS_RET['DUE_DATE'] )
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& empty( $_REQUEST['LO_save'] ) )
	{
		$colorbox_id = 'ASSIGNMENT_' . $THIS_RET['ASSIGNMENT_ID'];

		$colorbox = '<div class="hide"><div id="' . $colorbox_id . '" style="min-width: 50vw;">';

		// Format Assignment columns for StudentAssignmentDrawHeaders function.
		$assignment = $THIS_RET;

		$assignment['POINTS'] = $assignment['POINTS_POSSIBLE'];

		ob_start();

		StudentAssignmentDrawHeaders( $assignment );

		$colorbox .= ob_get_clean();

		$colorbox .= '</div></div>';

		$colorbox .= '<a class="colorboxinline" href="#' . $colorbox_id . '">' . $title . '</a>';
	}
	else
	{
		$colorbox = $title;
	}

	return $colorbox;
}

/**
 * Make Assignment Category
 * Truncate Assignment Category to 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.6
 *
 * @param  string $value  Category value.
 * @param  string $column Column. Defaults to 'CATEGORY'.
 *
 * @return string         Assignment Category truncated to 36 chars.
 */
function _makeCategory( $value, $column = 'TITLE' )
{
	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}


//FJ fix error Missing argument 2 & 3 & 4 & 5
//function bargraph1($x,$lo,$avg,$hi,$max)
/**
 * @param $x
 * @param $lo
 * @param $avg
 * @param $hi
 * @param $max
 */
function bargraph1( $x, $lo = 0, $avg = 0, $hi = 0, $max = 0 )
{
	if ( $x !== false )
	{
		$scale = $hi > $max ? $hi : $max;
		$w1 = round( 100 * $lo / $scale );
		$w5 = round( 100 * ( 1.0 - $hi / $scale ) );

		if ( $x !== true )
		{
			//FJ add grades legends on the graph

			if ( $x < $avg )
			{
				$w2 = round( 100 * ( $x - $lo ) / $scale );
				$c2 = '#ff0000';
				$legendc2 = $x;
				$w4 = round( 100 * ( $hi - $avg ) / $scale );
				$c4 = '#00ff00';
				$legendc4 = round( $avg, 2 ) . ' (' . _( 'Average' ) . ')';
			}
			else
			{
				$w2 = round( 100 * ( $avg - $lo ) / $scale );
				$c2 = '#00ff00';
				$legendc2 = round( $avg, 2 ) . ' (' . _( 'Average' ) . ')';
				$w4 = round( 100 * ( $hi - $x ) / $scale );
				$c4 = '#ff0000';
				$legendc4 = $x;
			}

			$w3 = 100 - $w1 - $w2 - $w4 - $w5;

			$correction = 2;

			return '<div style="float:left; width:150px; border: #333 1px solid;">' .
				( $w1 > 0 ? '<div style="width:' . ( $w1 - $correction ) . '%;float:left; background-color:#fff;">&nbsp;</div>' : '' ) .
				( $w2 > 0 ? '<div style="width:' . max( $w2 - $correction, 1 ) . '%; background-color:#00a000;float:left;">&nbsp;</div>' : '' ) .
				'<div style="width:2%; background-color:' . $c2 . '; cursor:pointer;float:left;" title="' . AttrEscape( $legendc2 ) . '" >&nbsp;</div>' .
				( $w3 > 0 ? '<div style="width:' . ( $w3 - $correction ) . '%; background-color:#00a000;float:left;">&nbsp;</div>' : '' ) .
				'<div style="width:2%; background-color:' . $c4 . '; cursor:pointer;float:left;" title="' . AttrEscape( $legendc4 ) . '">&nbsp;</div>' .
				( $w4 > 0 ? '<div style="width:' . ( $w4 - $correction ) . '%; background-color:#00a000;float:left;">&nbsp;</div>' : '' ) .
				( $w5 > 0 ? '<div style="width:' . ( $w5 - $correction ) . '%;float:left;background-color:#fff;">&nbsp;</div>' : '' ) .
				'</div>';
		}
		else
		{
			$w2 = round( 100 * ( $avg - $lo ) / $scale );
			$w4 = round( 100 * ( $hi - $avg ) / $scale );

			$correction = 2;

			return '<div style="float:left; width:150px; border: #333 1px solid;">' .
				( $w1 > 0 ? '<div style="width:' . ( $w1 - $correction ) . '%;float:left; background-color:#fff;">&nbsp;</div>' : '' ) .
				( $w2 > 0 ? '<div style="width:' . $w2 . '%; background-color:#00a000;float:left;">&nbsp;</div>' : '' ) .
				'<div style="width:2%; background-color:#00a000; float:left;">&nbsp;</div>' .
				( $w4 > 0 ? '<div style="width:' . $w4 . '%; background-color:#00a000;float:left;">&nbsp;</div>' : '' ) .
				( $w5 > 0 ? '<div style="width:' . ( $w5 - $correction ) . '%;float:left;background-color:#fff;">&nbsp;</div>' : '' ) .
				'</div>';
		}
	}
	else
	{
		return '<div style="float:left;">&nbsp;</div>';
	}
}

//FJ fix error Missing argument 3 & 2
//function bargraph2($x,$lo,$hi)
/**
 * @param $x
 * @param $lo
 * @param $hi
 */
function bargraph2( $x, $lo = 0, $hi = 0 )
{
	if ( $x !== false && $x !== true )
	{
		$scale = $lo + $hi + 1;
		$w1 = round( 100 * $lo / $scale );
		$w3 = round( 100 * $hi / $scale );
		$w2 = 100 - $w1 - $w3;

		return '<div style="float:left; width:150px; border: #333 1px solid;">' . ( $w1 > 0 || $lo > 0 ? '<div style="width:' . $w1 . '%; background-color:#fff;float:left;">&nbsp;</div>' : '' ) . '<div style="width:' . $w2 . '%; background-color:#ff0000;float:left;">&nbsp;</div>' . ( $w3 > 0 || $hi > 0 ? '<div style="width:' . $w3 . '%; background-color:#fff;float:left;">&nbsp;</div>' : '' ) . '</div>';
	}
	else
	{
		return '<div style="float:left;">&nbsp;</div>';
	}
}
