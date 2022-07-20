<?php
/**
 * Class Search Widget functions
 *
 * Primarily used in PrintClassLists.php & PrintClassPictures.php programs.
 */

/**
 * Class Search Widget
 * Find a Course PopTable + Course Periods ListOutput
 *
 * @since 5.6
 *
 * @uses _classSearchWidgetFindCourse()
 * @uses _classSearchWidgetCoursePeriodsListOutput()
 *
 * @param string $type  'course_period'.
 * @param array  $extra Search or Header Extra.
 */
function ClassSearchWidget( $extra = '' )
{
	if ( empty( $_REQUEST['modfunc'] ) )
	{
		_classSearchWidgetFindCourse( $extra );
	}
	else
	{
		_classSearchWidgetCoursePeriodsListOutput( $extra );
	}
}

/**
 * Find Course pop table for Class Search Widget
 * Local function
 *
 * @since 5.6
 *
 * @param  array $extra Search or Header Extra.
 */
function _classSearchWidgetFindCourse( $extra )
{
	$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'bottom_back' ] );

	if ( empty( $_SESSION['Back_PHP_SELF'] )
		|| $_SESSION['Back_PHP_SELF'] !== 'course' )
	{
		$_SESSION['Back_PHP_SELF'] = 'course';
		unset( $_SESSION['List_PHP_SELF'] );
	}

	echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';

	echo '<br />';

	PopTable( 'header', _( 'Find a Course' ) );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=' . $_REQUEST['modfunc'] . '&modfunc=list&next_modname=' .
		issetVal( $_REQUEST['next_modname'], '' )  ) . '" method="POST">';

	echo '<table>';

	$RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
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
		FROM course_subjects
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY TITLE" );

	echo '<tr class="st"><td>' . _( 'Subject' ) . '</td><td>';

	echo '<select name="subject_id"><option value="">' . _( 'N/A' ) . '</option>';

	foreach ( (array) $RET as $subject )
	{
		echo '<option value="' . AttrEscape( $subject['SUBJECT_ID'] ) . '">' . $subject['TITLE'] . '</option>';
	}

	echo '</select></td></tr>';

	$RET = DBGet( "SELECT PERIOD_ID,TITLE
		FROM school_periods
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	echo '<tr class="st"><td>' . _( 'Period' ) . '</td><td>';

	echo '<select name="period_id"><option value="">' . _( 'N/A' ) . '</option>';

	foreach ( (array) $RET as $period )
	{
		echo '<option value="' . AttrEscape( $period['PERIOD_ID'] ) . '">' . $period['TITLE'] . '</option>';
	}

	echo '</select></td></tr>';

	Widgets( 'course', $extra );

	echo issetVal( $extra['search'] );

	echo '<tr><td colspan="2" class="center">';
	echo '<br />';
	echo Buttons( _( 'Submit' ), _( 'Reset' ) );

	echo '</td></tr></table></form>';

	PopTable( 'footer' );
}

/**
 * Course Periods ListOutput for Class Search Widget
 * Local function
 *
 * @since 5.6
 *
 * @param  array $extra Search or Header Extra.
 */
function _classSearchWidgetCoursePeriodsListOutput( $extra = '' )
{
	if ( ! empty( $extra['extra_search'] ) )
	{
		// Print Class Lists misc/Export.php Fields table.
		echo '<table>' . $extra['extra_search'] . '</table>';
	}

	if ( ! empty( $extra['header_right'] ) )
	{
		// Print class Pictures options & button headers.
		DrawHeader( '', $extra['header_right'] );
		DrawHeader(
			( ! empty( $extra['extra_header_left'] ) ? $extra['extra_header_left'] : '' ),
			( ! empty( $extra['extra_header_right'] ) ? $extra['extra_header_right'] : '' )
		);
	}

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$where = $from = '';

		if ( ! empty( $_REQUEST['teacher_id'] ) )
		{
			$where .= " AND cp.TEACHER_ID='" . (int) $_REQUEST['teacher_id'] . "'";
		}

		if ( ! empty( $_REQUEST['first'] ) )
		{
			$where .= " AND UPPER(s.FIRST_NAME) LIKE '" . mb_strtoupper( $_REQUEST['first'] ) . "%'";
		}

		if ( ! empty( $_REQUEST['w_course_period_id'] ) )
		{
			if ( $_REQUEST['w_course_period_id_which'] == 'course' )
			{
				$where .= " AND cp.COURSE_ID=(SELECT COURSE_ID FROM course_periods WHERE COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "')";
			}
			else
			{
				$where .= " AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "'";
			}
		}

		if ( ! empty( $_REQUEST['subject_id'] ) )
		{
			$from .= ",courses c";
			$where .= " AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'";
		}

		if ( ! empty( $_REQUEST['period_id'] ) )
		{
			// FJ multiple school periods for a course period.
			$where .= " AND cpsp.PERIOD_ID='" . (int) $_REQUEST['period_id'] . "'
				AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID";

			$from .= ",course_period_school_periods cpsp";
		}

		$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE
			FROM course_periods cp" . $from . "
			WHERE cp.SCHOOL_ID='" . UserSchool() . "'
			AND cp.SYEAR='" . UserSyear() . "'" . $where;
	}
	elseif ( User( 'PROFILE' ) === 'teacher' )
	{
		// @since 6.9 Add Secondary Teacher.
		// FJ multiple school periods for a course period.
		$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE
			FROM course_periods cp
			WHERE cp.SCHOOL_ID='" . UserSchool() . "'
			AND cp.SYEAR='" . UserSyear() . "'
			AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
				OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')";
	}
	else
	{
		// FJ multiple school periods for a course period.
		$sql = "SELECT cp.COURSE_PERIOD_ID,cp.TITLE
		FROM course_periods cp,schedule ss
		WHERE cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND ss.SYEAR='" . UserSyear() . "'
		AND ss.STUDENT_ID='" . UserStudentID() . "'
		AND (CURRENT_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR CURRENT_DATE<=ss.END_DATE)
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";
	}

	$LO_columns = [ 'COURSE_PERIOD_ID' => MakeChooseCheckbox( 'Y', '', 'cp_arr' ), 'TITLE' => _( 'Course Period' ) ];

	$course_periods_RET = DBGet( $sql, [ 'COURSE_PERIOD_ID' => 'MakeChooseCheckbox' ] );

	if ( empty( $_REQUEST['LO_save'] ) && empty( $extra['suppress_save'] ) )
	{
		$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'bottom_back' ] );

		if ( empty( $_SESSION['Back_PHP_SELF'] )
			|| $_SESSION['Back_PHP_SELF'] !== 'course' )
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

	ListOutput(
		$course_periods_RET,
		$LO_columns,
		'Course Period',
		'Course Periods',
		[],
		[],
		[
			'save' => '0',
		]
	);
}
