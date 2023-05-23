<?php
// Note: The 'active assignments' feature is not fully correct.  If a student has dropped and re-enrolled there can be multiple timespans for
// which the  assignemnts are 'active' for that student.  However, only the timespan of current enrollment is used for 'active' assignment
// determination.  It would be possible to include all enrollment timespans but only the current is used for simplicity.  This is not a bug
// but an accepted limitaion.

require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

if ( ! empty( $_REQUEST['period'] ) )
{
	// @since 10.9 Set current User Course Period before Secondary Teacher logic.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

if ( ! empty( $_SESSION['is_secondary_teacher'] ) )
{
	// @since 6.9 Add Secondary Teacher: set User to main teacher.
	UserImpersonateTeacher();
}

$_REQUEST['include_inactive'] = issetVal( $_REQUEST['include_inactive'] );

$_REQUEST['include_all'] = issetVal( $_REQUEST['include_all'] );

$_REQUEST['type_id'] = issetVal( $_REQUEST['type_id'] );

$_REQUEST['assignment_id'] = issetVal( $_REQUEST['assignment_id'] );

DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() . ' - ' . GetMP( UserMP() ) );

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions

if ( ! isset( $_ROSARIO['allow_edit'] )
	// Do not allow edit past quarter grades for Teachers according to Program Config.
	&& ( ProgramConfig( 'grades', 'GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT' )
		|| GetCurrentMP( 'QTR', DBDate(), false ) == UserMP()
		|| GetMP( 'END_DATE' ) > DBDate() ) )
{
	$_ROSARIO['allow_edit'] = true;
}

$gradebook_config = ProgramUserConfig( 'Gradebook' );

//$max_allowed = Preferences('ANOMALOUS_MAX','Gradebook')/100;
$max_allowed = ( isset( $gradebook_config['ANOMALOUS_MAX'] ) && $gradebook_config['ANOMALOUS_MAX'] ?
	$gradebook_config['ANOMALOUS_MAX'] / 100 :
	1 );

if ( ! empty( $_REQUEST['student_id'] ) )
{
	if ( $_REQUEST['student_id'] !== UserStudentID() )
	{
		SetUserStudentID( $_REQUEST['student_id'] );
	}
}
elseif ( UserStudentID() )
{
	unset( $_SESSION['student_id'] );
}

$types_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID,TITLE,FINAL_GRADE_PERCENT,COLOR
FROM gradebook_assignment_types gt
WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
AND COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND (SELECT count(1) FROM gradebook_assignments WHERE STAFF_ID=gt.STAFF_ID
AND ((COURSE_ID=gt.COURSE_ID AND STAFF_ID=gt.STAFF_ID) OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND MARKING_PERIOD_ID='" . UserMP() . "'
AND ASSIGNMENT_TYPE_ID=gt.ASSIGNMENT_TYPE_ID)>0
ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [ 'TITLE' => '_makeTitle' ], [ 'ASSIGNMENT_TYPE_ID' ] );
//echo '<pre>'; var_dump($types_RET); echo '</pre>';

if ( $_REQUEST['type_id']
	&& empty( $types_RET[$_REQUEST['type_id']] ) )
{
	// Unset type ID & redirect URL.
	RedirectURL( 'type_id' );
}

$assignments_RET = DBGet( "SELECT ga.ASSIGNMENT_ID,ga.ASSIGNMENT_TYPE_ID,ga.TITLE,ga.POINTS,ga.ASSIGNED_DATE,
ga.DUE_DATE,ga.DEFAULT_POINTS," . _SQLUnixTimestamp( 'DUE_DATE' ) . " AS DUE_EPOCH,ga.WEIGHT,
CASE WHEN (ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ASSIGNED_DATE)
	AND (DUE_DATE IS NULL OR CURRENT_DATE>=DUE_DATE)
	OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)
THEN 'Y' ELSE NULL END AS DUE
FROM gradebook_assignments ga,gradebook_assignment_types gat
WHERE ga.STAFF_ID='" . User( 'STAFF_ID' ) . "'
AND ((ga.COURSE_ID=(SELECT cp.COURSE_ID
		FROM course_periods cp
		WHERE cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
	AND ga.STAFF_ID='" . User( 'STAFF_ID' ) . "')
	OR ga.COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND ga.MARKING_PERIOD_ID='" . UserMP() . "'" .
( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['type_id'] . "'" : '' ) .
" AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
ORDER BY gat.TITLE,ga." .
// SQL ORDER BY Assignment Type first, then order Assignments.
DBEscapeIdentifier( Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) ) .
" DESC,ga.ASSIGNMENT_ID DESC,ga.TITLE", [], [ 'ASSIGNMENT_ID' ] );
//echo '<pre>'; var_dump($assignments_RET); echo '</pre>';

// when changing course periods the assignment_id will be wrong except for '' (totals) and 'all'

if ( $_REQUEST['assignment_id']
	&& $_REQUEST['assignment_id'] !== 'all'
	&& empty( $assignments_RET[$_REQUEST['assignment_id']] ) )
{
	// Unset assignment ID & redirect URL.
	RedirectURL( 'assignment_id' );
}

//else
//	$_REQUEST['type_id'] = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID'];

if ( UserStudentID()
	&& ! $_REQUEST['assignment_id'] )
{
	$_REQUEST['assignment_id'] = 'all';
}

if ( ! empty( $_REQUEST['values'] )
	&& ! empty( $_POST['values'] )
	&& $_REQUEST['assignment_id'] )
{
	include 'ProgramFunctions/_makePercentGrade.fnc.php';

	if ( UserStudentID() )
	{
		$current_RET[UserStudentID()] = DBGet( "SELECT g.ASSIGNMENT_ID
			FROM gradebook_grades g,gradebook_assignments a
			WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
			AND a.MARKING_PERIOD_ID='" . UserMP() . "'
			AND g.STUDENT_ID='" . UserStudentID() . "'
			AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" .
			( $_REQUEST['assignment_id'] === 'all' ? '' :
				" AND g.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" ),
			[],
			[ 'ASSIGNMENT_ID' ]
		);
	}
	elseif ( $_REQUEST['assignment_id'] === 'all' )
	{
		$current_RET = DBGet( "SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS
			FROM gradebook_grades g,gradebook_assignments a
			WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
			AND a.MARKING_PERIOD_ID='" . UserMP() . "'
			AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'",
			[],
			[ 'STUDENT_ID', 'ASSIGNMENT_ID' ]
		);
	}
	else
	{
		$current_RET = DBGet( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID
			FROM gradebook_grades
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
			AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'",
			[],
			[ 'STUDENT_ID', 'ASSIGNMENT_ID' ]
		);
	}

	foreach ( (array) $_REQUEST['values'] as $student_id => $assignments )
	{
		foreach ( (array) $assignments as $assignment_id => $columns )
		{
			if ( isset( $columns['POINTS'] )
				&& $columns['POINTS'] != '' )
			{
				if ( $columns['POINTS'] == '*' )
				{
					$columns['POINTS'] = '-1';
				}
				else
				{
					// Handle Points decimal with comma instead of point, ie "10,5"
					$columns['POINTS'] = str_replace( ',', '.', $columns['POINTS'] );

					if ( mb_substr( $columns['POINTS'], -1 ) == '%' )
					{
						$columns['POINTS'] = mb_substr( $columns['POINTS'], 0, -1 ) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					}
					elseif ( ! is_numeric( $columns['POINTS'] ) )
					{
						$columns['POINTS'] = _makePercentGrade( $columns['POINTS'], UserCoursePeriod() ) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					}

					if ( $columns['POINTS'] < 0 )
					{
						$columns['POINTS'] = '0';
					}
					elseif ( $columns['POINTS'] > 9999.99 )
					{
						$columns['POINTS'] = '9999.99';
					}
				}
			}

			if ( ! empty( $current_RET[$student_id][$assignment_id] ) )
			{
				DBUpdate(
					'gradebook_grades',
					$columns,
					[
						'STUDENT_ID' => (int) $student_id,
						'COURSE_PERIOD_ID' => UserCoursePeriod(),
						'ASSIGNMENT_ID' => (int) $assignment_id,
					]
				);
			}
			elseif ( $columns['POINTS'] != ''
				|| ( isset( $columns['COMMENT'] ) && $columns['COMMENT'] != '' ) )
			{
				// @deprecated since 6.9 SQL gradebook_grades column PERIOD_ID.
				DBInsert(
					'gradebook_grades',
					[
						'STUDENT_ID' => (int) $student_id,
						'COURSE_PERIOD_ID' => UserCoursePeriod(),
						'ASSIGNMENT_ID' => (int) $assignment_id,
						'POINTS' => $columns['POINTS'],
						'COMMENT' => issetVal( $columns['COMMENT'], '' ),
					]
				);
			}
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );

	unset( $current_RET );
}

$LO_options = [ 'search' => false ];

if ( UserStudentID() )
{
	$extra['WHERE'] = " AND s.STUDENT_ID='" . UserStudentID() . "'";

	if ( empty( $_REQUEST['type_id'] ) )
	{
		$LO_columns = [ 'TYPE_TITLE' => _( 'Category' ) ];
	}
	else
	{
		$LO_columns = [];
	}

	$LO_columns += [
		'TITLE' => _( 'Assignment' ),
		'POINTS' => _( 'Points' ),
		'COMMENT' => _( 'Comment' ),
		'SUBMISSION' => _( 'Submission' ),
	];

	// modif Francois: display percent grade according to Configuration.

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
	{
		$LO_columns['PERCENT_GRADE'] = _( 'Percent' );
	}

	// modif Francois: display letter grade according to Configuration.

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
	{
		if ( empty( $gradebook_config['LETTER_GRADE_ALL'] ) )
		{
			$LO_columns['LETTER_GRADE'] = _( 'Letter' );
		}
	}

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'];

	$link['TITLE']['variables'] = [
		'type_id' => 'ASSIGNMENT_TYPE_ID',
		'assignment_id' => 'ASSIGNMENT_ID',
	];

	$current_RET[UserStudentID()] = DBGet( "SELECT g.ASSIGNMENT_ID
	FROM gradebook_grades g,gradebook_assignments a
	WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
	AND a.MARKING_PERIOD_ID='" . UserMP() . "'
	AND g.STUDENT_ID='" . UserStudentID() . "'
	AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" .
		( $_REQUEST['assignment_id'] == 'all' ? '' : " AND g.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" ), [], [ 'ASSIGNMENT_ID' ] );

	$count_assignments = count( (array) $assignments_RET );

	$extra['SELECT'] = ",ga.ASSIGNMENT_TYPE_ID,ga.ASSIGNMENT_ID,ga.TITLE,ga.POINTS AS TOTAL_POINTS,
		ga.SUBMISSION,'' AS PERCENT_GRADE,'' AS LETTER_GRADE,ga.WEIGHT,
		CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
			AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
			OR CURRENT_DATE>(SELECT END_DATE FROM school_marking_periods WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)
			THEN 'Y' ELSE NULL END AS DUE";

	$extra['SELECT'] .= ',gg.POINTS,gg.COMMENT';

	if ( empty( $_REQUEST['type_id'] ) )
	{
		$extra['SELECT'] .= ',(SELECT TITLE FROM gradebook_assignment_types WHERE ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID) AS TYPE_TITLE';

		$link['TYPE_TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'];

		$link['TYPE_TITLE']['variables'] = [ 'type_id' => 'ASSIGNMENT_TYPE_ID' ];
	}

	$extra['FROM'] = " JOIN gradebook_assignments ga ON
	(ga.STAFF_ID=cp.TEACHER_ID
	AND ((ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID)
		OR ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)
	AND ga.MARKING_PERIOD_ID='" . UserMP() . "'" .
	( $_REQUEST['assignment_id'] == 'all' ? '' : " AND ga.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'" ) .
	( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['type_id'] . "'" : '' ) . ")
	LEFT OUTER JOIN gradebook_grades gg ON
	(gg.STUDENT_ID=s.STUDENT_ID
	AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
	AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)";

	if ( empty( $_REQUEST['include_all'] ) )
	{
		$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
		OR (ga.DUE_DATE IS NULL
			OR (GREATEST(ssm.START_DATE,ss.START_DATE)<=ga.DUE_DATE)
			AND (LEAST(ssm.END_DATE,ss.END_DATE) IS NULL
			OR LEAST(ssm.END_DATE,ss.END_DATE)>=ga.DUE_DATE)))" .
		( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['type_id'] . "'" : '' );
	}

	$extra['ORDER_BY'] = DBEscapeIdentifier( Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) ) . " DESC";

	$extra['functions'] = [
		'TYPE_TITLE' => '_makeTitle',
		'POINTS' => '_makeExtraStuCols',
		'PERCENT_GRADE' => '_makeExtraStuCols',
		'LETTER_GRADE' => '_makeExtraStuCols',
		'COMMENT' => '_makeExtraStuCols',
		'SUBMISSION' => 'MakeStudentAssignmentSubmissionView',
	];
}
else
{
	$LO_columns = [ 'FULL_NAME' => _( 'Student' ) ];

	// Gain 1 column: replace it with "Submission".
	/*if ( $_REQUEST['assignment_id'] != 'all' )
	{
	$LO_columns += array( 'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ) );
	}*/

	if ( $_REQUEST['include_inactive'] == 'Y' )
	{
		$LO_columns += [
			'ACTIVE' => _( 'School Status' ),
			'ACTIVE_SCHEDULE' => _( 'Course Status' ) ];
	}

	$link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&include_inactive=' . $_REQUEST['include_inactive'] .
		'&include_all=' . $_REQUEST['include_all'] . '&type_id=' . $_REQUEST['type_id'] . '&assignment_id=all';

	$link['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

	$sql_start_end_epoch = "," . _SQLUnixTimestamp( 'GREATEST(ssm.START_DATE, ss.START_DATE)' ) . " AS START_EPOCH," .
		_SQLUnixTimestamp( 'LEAST(ssm.END_DATE, ss.END_DATE)' ) . " AS END_EPOCH";

	if ( $_REQUEST['assignment_id'] == 'all' )
	{
		$current_RET = DBGet( "SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS
			FROM gradebook_grades g,gradebook_assignments a
			WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
			AND a.MARKING_PERIOD_ID='" . UserMP() . "'
			AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" .
			( $_REQUEST['type_id'] ? " AND a.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['type_id'] . "'" : '' ),
			[],
			[ 'STUDENT_ID', 'ASSIGNMENT_ID' ]
		);

		// Fix PHP Fatal error: Cannot pass parameter 1 by reference.
		$count_extra = [ 'SELECT_ONLY' => 'ssm.STUDENT_ID' ];

		$count_students = GetStuList( $count_extra );
		$count_students = count( (array) $count_students );

		$extra['SELECT'] = $sql_start_end_epoch;

		$extra['functions'] = [];

		foreach ( (array) $assignments_RET as $id => $assignment )
		{
			$assignment = $assignment[1];

			$extra['SELECT'] .= ",'" . $id . "' AS G" . $id;

			$extra['functions'] += [ 'G' . $id => '_makeExtraCols' ];

			// @since 10.4 Truncate Assignment title to 36 chars.
			$title = mb_strlen( $assignment['TITLE'] ) <= 36 ?
				$assignment['TITLE'] :
				'<span title="' . AttrEscape( $assignment['TITLE'] ) . '">' . mb_substr( $assignment['TITLE'], 0, 33 ) . '...</span>';

			$column_title = $title;

			if ( empty( $_REQUEST['type_id'] ) )
			{
				$column_title = $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['TITLE'] . '<br />' . $column_title;
			}

			if ( ! $_REQUEST['type_id']
				&& $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'] )
			{
				$column_title = '<span style="background-color: ' .
					$types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'] . ';">&nbsp;</span>&nbsp;' .
					$column_title;
			}

			$LO_columns['G' . $id] = $column_title;
		}
	}
	elseif ( ! empty( $_REQUEST['assignment_id'] ) )
	{
		$extra['SELECT'] = ",'" . $_REQUEST['assignment_id'] . "' AS POINTS,
			'" . $_REQUEST['assignment_id'] . "' AS PERCENT_GRADE,
			'" . $_REQUEST['assignment_id'] . "' AS LETTER_GRADE,
			'" . $_REQUEST['assignment_id'] . "' AS COMMENT,
			(SELECT 'Y' FROM gradebook_assignments ga
				WHERE ga.ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
				AND ga.SUBMISSION='Y') AS SUBMISSION,
			'" . $_REQUEST['assignment_id'] . "' AS ASSIGNMENT_ID";

		$extra['SELECT'] .= $sql_start_end_epoch;

		$extra['functions'] = [
			'POINTS' => '_makeExtraAssnCols',
			'PERCENT_GRADE' => '_makeExtraAssnCols',
			'LETTER_GRADE' => '_makeExtraAssnCols',
			'COMMENT' => '_makeExtraAssnCols',
			'SUBMISSION' => 'MakeStudentAssignmentSubmissionView',
		];

		$LO_columns += [
			'POINTS' => _( 'Points' ),
			'COMMENT' => _( 'Comment' ),
			'SUBMISSION' => _( 'Submission' ),
		];

		$current_RET = DBGet( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID
			FROM gradebook_grades
			WHERE ASSIGNMENT_ID='" . (int) $_REQUEST['assignment_id'] . "'
			AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'", [], [ 'STUDENT_ID', 'ASSIGNMENT_ID' ] );
	}
	else
	{
		if ( ! empty( $assignments_RET ) )
		{
			//FJ default points
			$extra['SELECT_ONLY'] = "s.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,
				sum(" . db_case( [ 'gg.POINTS', "'-1'", "'0'", "''", db_case( [ 'ga.DEFAULT_POINTS', "'-1'", "'0'", 'ga.DEFAULT_POINTS' ] ), 'gg.POINTS' ] ) . ") AS PARTIAL_POINTS,
				sum(" . db_case( [ 'gg.POINTS', "'-1'", "'0'", "''", db_case( [ 'ga.DEFAULT_POINTS', "'-1'", "'0'", 'ga.POINTS' ] ), 'ga.POINTS' ] ) . ") AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";

			if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
			{
				// @since 11.0 Add Weight Assignments option
				$extra['SELECT_ONLY'] .= ",sum(" . db_case( [ 'ga.WEIGHT', "''", "'0'", "ga.WEIGHT" ] ) . ") AS PARTIAL_WEIGHT,
					sum((gg.POINTS/ga.POINTS)*ga.WEIGHT) AS PARTIAL_WEIGHTED_GRADE";
			}

			$extra['FROM'] = " JOIN gradebook_assignments ga ON (((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID) AND ga.STAFF_ID=cp.TEACHER_ID)
				AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
			LEFT OUTER JOIN gradebook_grades gg ON (gg.STUDENT_ID=s.STUDENT_ID
				AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
				AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),gradebook_assignment_types gt";

			$extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
			AND gt.COURSE_ID=cp.COURSE_ID
			AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
				AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
				OR CURRENT_DATE>(SELECT END_DATE
					FROM school_marking_periods
					WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))" .
			( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . (int) $_REQUEST['type_id'] . "'" : '' );

			if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
			{
				// @since 11.0 Add Weight Assignments option
				// Exclude Extra Credit assignments.
				$extra['WHERE'] .= " AND ga.POINTS>0";
			}

			if ( empty( $_REQUEST['include_all'] ) )
			{
				$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
					OR ga.DUE_DATE IS NULL
					OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
						AND (ga.DUE_DATE>=ssm.START_DATE
							AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";
			}

			$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";
			$extra['group'] = [ 'STUDENT_ID' ];

			$points_RET = GetStuList( $extra );
			//echo '<pre>'; var_dump($points_RET); echo '</pre>';

			unset( $extra );
			$extra['SELECT'] = $sql_start_end_epoch .
				",'' AS POINTS,'' AS PERCENT_GRADE,'' AS LETTER_GRADE";

			$extra['functions'] = [
				'POINTS' => '_makeExtraAssnCols',
				'PERCENT_GRADE' => '_makeExtraAssnCols',
				'LETTER_GRADE' => '_makeExtraAssnCols',
			];

			$LO_columns['POINTS'] = _( 'Points' );
		}
	}

	if ( $_REQUEST['assignment_id'] != 'all' )
	{
		// modif Francois: display percent grade according to Configuration.

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
		{
			$LO_columns['PERCENT_GRADE'] = _( 'Percent' );
		}

		// modif Francois: display letter grade according to Configuration.

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
		{
			if ( empty( $_REQUEST['assignment_id'] )
				|| empty( $gradebook_config['LETTER_GRADE_ALL'] ) )
			{
				$LO_columns['LETTER_GRADE'] = _( 'Letter' );
			}
		}
	}

	$extra['functions']['FULL_NAME'] = 'makePhotoTipMessage';
}

$stu_RET = GetStuList( $extra );
//echo '<pre>'; var_dump($stu_RET); echo '</pre>';

$type_onchange_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'] .
	'&include_all=' . $_REQUEST['include_all'] .
	( $_REQUEST['assignment_id'] === 'all' ? '&assignment_id=all' : '' ) .
	( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) .
	"&type_id=" );

$type_select = '<select name="type_id" id="type_id" autocomplete="off" onchange="' .
	AttrEscape( 'ajaxLink(' . json_encode( $type_onchange_URL ) . ' + this.value);' ) . '">';

$type_select .= '<option value=""' . ( ! $_REQUEST['type_id'] ? ' selected' : '' ) . '>' .
_( 'All' ) .
	'</option>';

foreach ( (array) $types_RET as $id => $type )
{
	$type_select .= '<option value="' . AttrEscape( $id ) . '"' . ( $_REQUEST['type_id'] == $id ? ' selected' : '' ) . '>' .
		$type[1]['TITLE'] .
		'</option>';
}

$type_select .= '</select><label for="type_id" class="a11y-hidden">' . _( 'Assignment Types' ) . '</label>';

$assignment_onchange_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'] .
	'&include_all=' . $_REQUEST['include_all'] .
	'&type_id=' . $_REQUEST['type_id'] .
	"&assignment_id=" );

$assignment_select = '<select name="assignment_id" id="assignment_id" autocomplete="off" onchange="' .
	AttrEscape( 'ajaxLink(' . json_encode( $assignment_onchange_URL ) . ' + this.value);' ) . '">';

$assignment_select .= '<option value="">' . _( 'Totals' ) . '</option>';

$assignment_select .= '<option value="all"' . (  ( $_REQUEST['assignment_id'] === 'all' && ! UserStudentID() ) ? ' selected' : '' ) . '>' .
_( 'All' ) .
	'</option>';

if ( UserStudentID() && $_REQUEST['assignment_id'] === 'all' )
{
	$assignment_select .= '<option value="all" selected>' .
		( isset( $stu_RET[1]['FULL_NAME'] ) ? $stu_RET[1]['FULL_NAME'] : '' ) . '</option>';
}

$optgroup = '';

foreach ( (array) $assignments_RET as $id => $assignment )
{
	if ( empty( $_REQUEST['type_id'] )
		&& $optgroup !== $types_RET[$assignment[1]['ASSIGNMENT_TYPE_ID']][1]['TITLE'] )
	{
		if ( $optgroup )
		{
			$assignment_select .= '</optgroup>';
		}

		$optgroup = $types_RET[$assignment[1]['ASSIGNMENT_TYPE_ID']][1]['TITLE'];

		$assignment_select .= '<optgroup label="' . AttrEscape( strip_tags( $optgroup ) ) . '">';
	}

	$assignment_select .= '<option value="' . AttrEscape( $id ) . '"' .
		( $_REQUEST['assignment_id'] == $id ? ' selected' : '' ) . '>' .
		$assignment[1]['TITLE'] . '</option>';
}

if ( $assignments_RET )
{
	$assignment_select .= '</optgroup>';
}

$assignment_select .= '</select>
	<label for="assignment_id" class="a11y-hidden">' . _( 'Assignments' ) . '</label>';

// echo '<form action="' . URLEscape( 'Modules.php?modname='.$_REQUEST['modname'].'&student_id='.UserStudentID().'' ) . '" method="POST">';

/**
 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
 * Then teacher submits the form, data would be saved for CP B...
 *
 * Must be used in combination with
 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
 */
echo '<form action="' . PreparePHP_SELF( [], [ 'values' ], [ 'period' => UserCoursePeriod() ] ) . '" method="POST">';

$tabs = [ [
	'title' => _( 'All' ),
	'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' . ( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) . ( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'],
] ];

foreach ( (array) $types_RET as $id => $type )
{
	$color = '';

	if ( $type[1]['COLOR'] )
	{
		$color = '<span style="background-color: ' . $type[1]['COLOR'] . ';">&nbsp;</span>&nbsp;';
	}

	$tabs[] = [
		'title' => $color . $type[1]['TITLE'] .
			( ! empty( $gradebook_config['WEIGHT'] ) ?
				'|' . number_format( 100 * $type[1]['FINAL_GRADE_PERCENT'], 0 ) . '%' :
				'' ),
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' . $id .
			( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) .
			( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) .
			'&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'],
	];
}

DrawHeader(
	$type_select . $assignment_select,
	$_REQUEST['assignment_id'] ? SubmitButton() : ''
);

DrawHeader(
	CheckBoxOnclick(
		'include_inactive',
		_( 'Include Inactive Students' )
	) . ' &nbsp;' .
	CheckBoxOnclick(
		'include_all',
		_( 'Include Inactive Assignments' )
	)
);

if ( $_REQUEST['assignment_id'] && $_REQUEST['assignment_id'] != 'all' )
{
	$assigned_date = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNED_DATE'];
	$due_date = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE_DATE'];
	$due = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE'];

	DrawHeader( _( 'Assigned Date' ) . ': ' . ( $assigned_date ? ProperDate( $assigned_date ) : _( 'N/A' ) ) .
		' &mdash; ' . _( 'Due Date' ) . ': ' . ( $due_date ? ProperDate( $due_date ) : _( 'N/A' ) ) .
		( $due ? ' &mdash; <b>' . _( 'Assignment is Due' ) . '</b>' : '' ) );
}

if ( empty( $_ROSARIO['allow_edit'] )
	&& ( ! empty( $_REQUEST['student_id'] )
		|| ! empty( $_REQUEST['assignment_id'] ) ) )
{
	DrawHeader( '<span style="color:red">' . _( 'You can not edit these grades.' ) . '</span>' );
}

$LO_options['header'] = WrapTabs(
	$tabs,
	'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' .
	( $_REQUEST['type_id'] ?
		$_REQUEST['type_id'] :
		( $_REQUEST['assignment_id'] && $_REQUEST['assignment_id'] != 'all' ?
			$assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID'] :
			'' )
	) .
	( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) .
	( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) .
	'&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all']
);

echo '<br />';

if ( UserStudentID() )
{
	ListOutput(
		$stu_RET,
		$LO_columns,
		'Assignment',
		'Assignments',
		$link,
		[],
		$LO_options
	);
}
else
{
	ListOutput(
		$stu_RET,
		$LO_columns,
		'Student',
		'Students',
		$link,
		[],
		$LO_options
	);
}

// @since 4.6 Navigate form inputs vertically using tab key.
// @link https://stackoverflow.com/questions/38575817/set-tabindex-in-vertical-order-of-columns
?>
<script>
	function fixVerticalTabindex(selector) {
		var tabindex = 1;
		$(selector).each(function(i, tbl) {
			$(tbl).find('tr').first().find('td').each(function(clmn, el) {
				$(tbl).find('tr td:nth-child(' + (clmn + 1) + ') input').each(function(j, input) {
					$(input).attr('tabindex', tabindex++);
				});
			});
		});
	}

	fixVerticalTabindex('.list-wrapper .list tbody');
</script>
<?php

echo $_REQUEST['assignment_id'] ? '<br /><div class="center">' . SubmitButton() . '</div>' : '';
echo '</form>';

/**
 * @param  $assignment_id
 * @param  $column
 * @return mixed
 */
function _makeExtraAssnCols( $assignment_id, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$current_RET,
	$points_RET,
	$max_allowed,
	$total,
		$gradebook_config;

	switch ( $column )
	{
		case 'POINTS':
			if ( ! $assignment_id )
			{
				$total = $total_points = 0;
				//FJ default points
				$total_use_default_points = false;

				if ( ! empty( $points_RET[$THIS_RET['STUDENT_ID']] ) )
				{
					foreach ( (array) $points_RET[$THIS_RET['STUDENT_ID']] as $partial_points )
					{
						/**
						 * Do not include Extra Credit assignments
						 * when Total Points is 0 for the Type
						 * if the Gradebook is configured to Weight Grades:
						 * Division by zero is impossible.
						 */
						if ( $partial_points['PARTIAL_TOTAL'] != 0
							|| empty( $gradebook_config['WEIGHT'] ) )
						{
							$total += $partial_points['PARTIAL_POINTS'];
							$total_points += $partial_points['PARTIAL_TOTAL'];
						}
					}
				}

//				return '<table cellspacing=0 cellpadding=0><tr><td>'.$total.'</td><td>&nbsp;/&nbsp;</td><td>'.$total_points.'</td></tr></table>';

				return $total . '&nbsp;/&nbsp;' . $total_points;
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( ( isset( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] )
						&& $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != '' )
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];

					//FJ default points
					$points = issetVal( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] );
					$div = true;

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
						$div = false;
					}

					if ( $points == '-1' )
					{
						$points = '*';
					}
					elseif ( mb_strpos( (string) $points, '.' ) )
					{
						$points = rtrim( rtrim( $points, '0' ), '.' );
					}

//					return '<table cellspacing=0 cellpadding=1><tr><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</td><td>&nbsp;/&nbsp;</td><td>'.$total_points.'</td></tr></table>';

					$name = 'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]';

					$id = GetInputID( $name );

					return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
					TextInput(
						$points,
						$name,
						'',
						' size=2 maxlength=7',
						$div
					) . '</span>
						<label for="' . $id . '">&nbsp;/&nbsp;' . $total_points . '</label>';
				}
			}

			break;

		case 'PERCENT_GRADE':
			if ( ! $assignment_id )
			{
				$total = $total_percent = $total_weighted_grade = $total_weights = 0;

				if ( ! empty( $points_RET[$THIS_RET['STUDENT_ID']] ) )
				{
					foreach ( (array) $points_RET[$THIS_RET['STUDENT_ID']] as $partial_points )
					{
						/**
						 * Do not include Extra Credit assignments
						 * when Total Points is 0 for the Type
						 * if the Gradebook is configured to Weight Grades:
						 * Division by zero is impossible.
						 */
						if ( $partial_points['PARTIAL_TOTAL'] != 0
							|| empty( $gradebook_config['WEIGHT'] ) )
						{
							$total += $partial_points['PARTIAL_POINTS'] *
								( ! empty( $gradebook_config['WEIGHT'] ) ?
									$partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
									1 );

							$total_percent += ( ! empty( $gradebook_config['WEIGHT'] ) ?
								$partial_points['FINAL_GRADE_PERCENT'] :
								$partial_points['PARTIAL_TOTAL'] );

							if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] ) )
							{
								// @since 11.0 Add Weight Assignments option
								$total_weighted_grade += ( ! empty( $gradebook_config['WEIGHT'] ) ?
									$partial_points['FINAL_GRADE_PERCENT'] * $partial_points['PARTIAL_WEIGHTED_GRADE'] :
									$partial_points['PARTIAL_WEIGHTED_GRADE'] );

								$total_weights += ( ! empty( $gradebook_config['WEIGHT'] ) ?
									$partial_points['FINAL_GRADE_PERCENT'] * $partial_points['PARTIAL_WEIGHT'] :
									$partial_points['PARTIAL_WEIGHT'] );
							}
						}
					}

					if ( $total_percent != 0 )
					{
						$total /= $total_percent;
					}

					if ( ! empty( $gradebook_config['WEIGHT_ASSIGNMENTS'] )
						&& $total_weights > 0 )
					{
						// @since 11.0 Add Weight Assignments option
						$total = $total_weighted_grade / $total_weights;
					}
				}

				$red_span = $total > $max_allowed;

				$percent = _makeLetterGrade( $total, 0, 0, '%' );

				return _Percent( $percent, 2, $red_span );
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( ( isset( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] )
							&& $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != '' )
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					//FJ default points
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
					}

					if ( $total_points != 0 )
					{
						if ( $points != '-1' )
						{
							$red_span = ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' )
								&& ( $points > $total_points * $max_allowed );

							$percent = _makeLetterGrade( $points / $total_points, 0, 0, '%' );

							return _Percent( $percent, 2, $red_span );
						}
						else
						{
							return _( 'N/A' );
						}
					}
					else
					{
						return _( 'E/C' );
					}
				}
			}

			break;

		case 'LETTER_GRADE':
			if ( ! $assignment_id )
			{
				return '<b>' . _makeLetterGrade( $total ) . '</b>';
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( ( isset( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] )
							&& $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != '' )
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					//FJ default points
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
					}

					if ( $total_points != 0 )
					{
						if ( $points != '-1' )
						{
							return ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ? '' : '<span style="color:gray">' ) . '<b>' . _makeLetterGrade( $points / $total_points ) . '</b>' . ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ? '' : '</span>' );
						}
						else
						{
							return _( 'N/A' );
						}
					}
					else
					{
						return _( 'N/A' );
					}
				}
			}

			break;

		case 'COMMENT':
			if ( ! $assignment_id )
			{
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( ( isset( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] )
							&& $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != '' )
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$return = TextInput(
						issetVal( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['COMMENT'] ),
						'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][COMMENT]',
						'',
						'size=20 maxlength=500'
					);

					if ( mb_strlen( (string) $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['COMMENT'] ) > 60
						&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
					{
						// Comments length > 60 chars, responsive table ColorBox.
						$return = '<div id="divGradesComment' . $THIS_RET['STUDENT_ID'] . '" class="rt2colorBox">' .
							$return . '</div>';
					}

					return $return;
				}
			}

			break;
	}
}

/**
 * @param $value
 * @param $column
 */
function _makeExtraStuCols( $value, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$assignment_count,
	$count_assignments,
		$max_allowed;

	//FJ default points

	if ( is_null( $THIS_RET['POINTS'] ) )
	{
		$THIS_RET['POINTS'] = $assignments_RET[$THIS_RET['ASSIGNMENT_ID']][1]['DEFAULT_POINTS'];
	}

	switch ( $column )
	{
		case 'POINTS':
			$assignment_count++;

			//FJ default points
			$div = true;

			if ( is_null( $value ) )
			{
				$value = $assignments_RET[$THIS_RET['ASSIGNMENT_ID']][1]['DEFAULT_POINTS'];
				$div = false;
			}

			if ( $value == '-1' )
			{
				$value = '*';
			}
			elseif ( mb_strpos( (string) $value, '.' ) )
			{
				$value = rtrim( rtrim( $value, '0' ), '.' );
			}

//			return '<table cellspacing=0 cellpadding=1><tr><td>'.TextInput($value,'values['.$THIS_RET['STUDENT_ID'].']['.$THIS_RET['ASSIGNMENT_ID'].'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</td><td>&nbsp;/&nbsp;</td><td>'.$THIS_RET['TOTAL_POINTS'].'</td></tr></table>';

			$name = 'values[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['ASSIGNMENT_ID'] . '][POINTS]';

			$id = GetInputID( $name );

			return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
			TextInput(
				$value,
				$name,
				'',
				' size=2 maxlength=7',
				$div
			) . '</span>
				<label for="' . $id . '">&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'] . '</label>';
			break;

		case 'PERCENT_GRADE':
			if ( $THIS_RET['TOTAL_POINTS'] != 0 )
			{
				if ( $THIS_RET['POINTS'] != '-1' )
				{
					$red_span = ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' )
						&& ( $THIS_RET['POINTS'] > $THIS_RET['TOTAL_POINTS'] * $max_allowed );

					$percent = _makeLetterGrade( $THIS_RET['POINTS'] / $THIS_RET['TOTAL_POINTS'], 0, 0, '%' );

					return _Percent(
						$percent,
						2,
						$red_span
					);
				}

				return _( 'N/A' );
			}

			return _( 'E/C' );

			break;

		case 'LETTER_GRADE':
			if ( $THIS_RET['TOTAL_POINTS'] != 0 )
			{
				if ( $THIS_RET['POINTS'] != '-1' )
				{
					return ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' ? '' : '<span style="color:gray">' ) . '<b>' . _makeLetterGrade( $THIS_RET['POINTS'] / $THIS_RET['TOTAL_POINTS'] ) . '</b>' . ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' ? '' : '</span>' );
				}
			}

			return _( 'N/A' );

			break;

		case 'COMMENT':
			$return = TextInput(
				$value,
				'values[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['ASSIGNMENT_ID'] . '][COMMENT]',
				'',
				'size=20 maxlength=500'
			);

			if ( mb_strlen( (string) $value ) > 60
				&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				// Comments length > 60 chars, responsive table ColorBox.
				$return = '<div id="divGradesComment' . $THIS_RET['STUDENT_ID'] . '" class="rt2colorBox">' .
					$return . '</div>';
			}

			return $return;

			break;
	}
}

/**
 * @param $assignment_id
 * @param $column
 */
function _makeExtraCols( $assignment_id, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$current_RET,
	$old_student_id,
	$student_count,
	$count_students,
		$max_allowed;

	if ( $THIS_RET['STUDENT_ID'] != $old_student_id )
	{
		$student_count++;

		$old_student_id = $THIS_RET['STUDENT_ID'];
	}

	$total_points = $assignments_RET[$assignment_id][1]['POINTS'];

	$current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] = issetVal( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] );

	if ( ! empty( $_REQUEST['include_all'] )
		|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
			|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
			|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
			&& ( ! $THIS_RET['END_EPOCH']
				|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
	{
		//FJ default points
		$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];
		$div = true;

		if ( is_null( $points ) )
		{
			$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
			$div = false;
		}

		if ( $points == '-1' )
		{
			$points = '*';
		}
		elseif ( mb_strpos( (string) $points, '.' ) )
		{
			$points = rtrim( rtrim( $points, '0' ), '.' );
		}

		$name = 'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]';

		$id = GetInputID( $name );

		if ( $total_points != 0 )
		{
			if ( $points != '*' )
			{
				$percent_red_span = ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' )
					&& ( $points > $total_points * $max_allowed );

				$percent = _makeLetterGrade( $points / $total_points, 0, 0, '%' );

				// modif Francois: display letter grade according to Configuration
				return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
				TextInput(
					$points,
					$name,
					'',
					' size=2 maxlength=7',
					$div
				) . '</span>
				<label for="' . $id . '">&nbsp;/&nbsp;' . $total_points . '</label><span>' .
				( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 ?
					'&nbsp;&minus;&nbsp;' . _Percent( $percent, 2, $percent_red_span ) :
					'' ) .
				( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 ?
					'&nbsp;&minus;&nbsp;<b>' . _makeLetterGrade( $points / $total_points ) . '</b>' :
					'' ) .
				'</span>';
			}

			//return '<table cellspacing=0 cellpadding=1><tr align=center><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<hr />'.$total_points.'</td><td>&nbsp;'._('N/A').'<br />&nbsp;'._('N/A').'</td></tr></table>';

			return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
			TextInput(
				$points,
				$name,
				'',
				' size=2 maxlength=7',
				$div
			) . '</span>
			<label for="' . $id . '">&nbsp;/&nbsp;' . $total_points . '</label>
			<span>&nbsp;&minus;&nbsp;' . _( 'N/A' ) . '</span>';
		}

		//return '<table class="cellspacing-0"><tr class="center"><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<hr />'.$total_points.'</td><td>&nbsp;E/C</td></tr></table>';

		return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
		TextInput(
			$points,
			$name,
			'',
			' size=2 maxlength=7',
			$div
		) . '</span>
		<label for="' . $id . '">&nbsp;/&nbsp;' . $total_points . '</label>
		<span>&nbsp;&minus;&nbsp;' . _( 'E/C' ) . '</span>';
	}
}

/**
 * Make Percent HTML
 *
 * @since 7.4 Put raw percent inside HTML comment for better sorting.
 * @since 7.4 Add $red_span parameter.
 *
 * @param string $num      Unformatted percent.
 * @param int    $decimals Percent decimals.
 * @param bool   $red_span Set to true to color percentage in red. Typically if over 100%.
 *
 * @return Percent HTML with raw value inside HTML comment for better sorting.
 */
function _Percent( $num, $decimals = 2, $red_span = false )
{
	// Raw value in comment so we can sort Percent column the right way.
	$percent_html = '<!-- ' . number_format( $num, $decimals, '.', '' ) . ' -->';

	$percent_html .= ( $red_span ? '<span style="color:red">' : '' );

	// Fix trim 0 (float) when percent > 1,000: do not use comma for thousand separator.
	$percent_html .= (float) number_format( $num, $decimals, '.', '' ) . '%';

	$percent_html .= ( $red_span ? '</span>' : '' );

	return $percent_html;
}

/**
 * SQL to extract Unix timestamp or epoch from date
 * Use UNIX_TIMESTAMP() for MySQL and extract(EPOCH) for PostgreSQL
 *
 * Local function
 *
 * @since 9.3
 *
 * @param  string $column Date column.
 *
 * @return string         MySQL or PostgreSQL function
 */
function _SQLUnixTimestamp( $column )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return "UNIX_TIMESTAMP(" . $column . ")";
	}

	return "extract(EPOCH FROM " . $column . ")";
}

/**
 * Make Assignment Title
 * Truncate Assignment title to 36 chars
 *
 * Local function.
 * GetStuList() DBGet() callback.
 *
 * @since 10.5.2
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to 'TITLE'.
 *
 * @return string         Assignment title truncated to 36 chars.
 */
function _makeTitle( $value, $column = 'TITLE' )
{
	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	$title = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . AttrEscape( $value ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return $title;
}
