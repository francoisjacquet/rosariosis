<?php
/**
 * (Student) Widgets function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Widgets
 * Essentially used in the Find a Student form
 *
 * @todo  Fill $extra['search'] only if required (see Search.inc.php, if !search_modfunc?)
 *
 * @global array   $_ROSARIO       Sets $_ROSARIO['Widgets']
 * @global array   $RosarioModules
 * @global array   $extra
 *
 * @param  string  $item           widget name or 'all' widgets.
 * @param  array   &$myextra       Search.inc.php extra (HTML, functions...) (optional). Defaults to global $extra.
 *
 * @return boolean true if Widget loaded, false if insufficient rights or already saved widget
 */
function Widgets( $item, &$myextra = null )
{
	global $extra,
		$_ROSARIO,
		$RosarioModules;

	// Do not use `! empty()` here.
	if ( isset( $myextra ) )
	{
		$extra =& $myextra;
	}

	$extra_defaults = array(
		'functions' => array(),
		'search' => '',
		'NoSearchTerms' => '',
		'SELECT' => '',
		'FROM' => '',
		'WHERE' => '',
	);

	$extra = array_replace_recursive( $extra_defaults, (array) $extra );

	// Save current widgets list inside $_ROSARIO['Widgets'] global var.
	if ( ! isset( $_ROSARIO['Widgets'] )
		|| ! is_array( $_ROSARIO['Widgets'] ) )
	{
		$_ROSARIO['Widgets'] = array();
	}

	if ( ! isset( $_ROSARIO['SearchTerms'] ) )
	{
		$_ROSARIO['SearchTerms'] = '';
	}

	// If insufficient rights or already saved widget, exit.
	if ( ( User( 'PROFILE' ) !== 'admin'
			&& User( 'PROFILE' ) !== 'teacher' )
		|| ! empty( $_ROSARIO['Widgets'][ $item ] ) )
	{
		return false;
	}

	switch ( $item )
	{
		// All Widgets (or almost).
		case 'all':

			// FJ regroup widgets wrap.
			$widget_wrap_header =
			function( $title )
			{
				return '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu">
					<b>' . $title . '</b></a>
					<br />
					<table class="widefat width-100p col1-align-right hide">';
			};

			$widget_wrap_footer = '</table>';

			// Enrollment.
			if ( $RosarioModules['Students']
				&& ( empty( $_ROSARIO['Widgets']['calendar'] )
					|| empty( $_ROSARIO['Widgets']['next_year'] )
					|| empty( $_ROSARIO['Widgets']['enrolled'] )
					|| empty( $_ROSARIO['Widgets']['rolled'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Enrollment' ) );

				Widgets( 'calendar', $extra );
				Widgets( 'next_year', $extra );
				Widgets( 'enrolled', $extra );
				Widgets( 'rolled', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Scheduling.
			if ( $RosarioModules['Scheduling']
				&& empty( $_ROSARIO['Widgets']['course'] )
				&& User('PROFILE') == 'admin' )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Scheduling' ) );

				Widgets( 'course', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Attendance.
			if ( $RosarioModules['Attendance']
				&& ( empty( $_ROSARIO['Widgets']['absences'] )
					|| empty( $_ROSARIO['Widgets']['cp_absences'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Attendance' ) );

				Widgets( 'absences', $extra );

				Widgets( 'cp_absences', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Grades.
			if ( $RosarioModules['Grades']
				&& ( empty( $_ROSARIO['Widgets']['gpa'] )
					|| empty( $_ROSARIO['Widgets']['class_rank'] )
					|| empty( $_ROSARIO['Widgets']['letter_grade'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Grades' ) );

				Widgets( 'gpa', $extra );
				Widgets( 'class_rank', $extra );
				Widgets( 'letter_grade', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Eligibility.
			if ( $RosarioModules['Eligibility']
				&& ( empty( $_ROSARIO['Widgets']['eligibility'] )
					|| empty( $_ROSARIO['Widgets']['activity'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Eligibility' ) );

				Widgets( 'eligibility', $extra );
				Widgets( 'activity', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Food Service.
			if ( $RosarioModules['Food_Service']
				&& ( empty( $_ROSARIO['Widgets']['fsa_balance'] )
					|| empty( $_ROSARIO['Widgets']['fsa_discount'] )
					|| empty( $_ROSARIO['Widgets']['fsa_status'] )
					|| empty( $_ROSARIO['Widgets']['fsa_barcode'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Food Service' ) );

				Widgets( 'fsa_balance', $extra );
				Widgets( 'fsa_discount', $extra );
				Widgets( 'fsa_status', $extra );
				Widgets( 'fsa_barcode', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Discipline.
			if ( $RosarioModules['Discipline']
				&& ( empty( $_ROSARIO['Widgets']['reporter'] )
					|| empty( $_ROSARIO['Widgets']['incident_date'] )
					|| empty( $_ROSARIO['Widgets']['discipline_fields'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Discipline' ) );

				Widgets( 'reporter', $extra );
				Widgets( 'incident_date', $extra );
				Widgets( 'discipline_fields', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Student Billing.
			if ( $RosarioModules['Student_Billing']
				&& ( empty( $_ROSARIO['Widgets']['balance'] ) )
				&& AllowUse( 'Student_Billing/StudentFees.php' ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Student Billing' ) );

				Widgets( 'balance', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

		break;

		// User Widgets (configured in My Preferences).
		case 'user':

			/*$widgets_RET = DBGet( "SELECT TITLE
				FROM PROGRAM_USER_CONFIG
				WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
				AND PROGRAM='WidgetsSearch'" .
				( count( $_ROSARIO['Widgets'] ) ?
					" AND TITLE NOT IN ('" .
						implode( "','", array_keys( $_ROSARIO['Widgets'] ) ) .
					"')" :
					'' )
				);*/

			$user_widgets = ProgramUserConfig( 'WidgetsSearch' );

			$saved_widget_titles = array_keys( $_ROSARIO['Widgets'] );

			foreach ( (array) $user_widgets as $user_widget_title => $value )
			{
				if ( ! in_array( $user_widget_title, $saved_widget_titles ) )
				{
					Widgets( $user_widget_title, $extra );
				}
			}

		break;

		// Course Widget.
		case 'course':

			if ( ! $RosarioModules['Scheduling']
				|| User( 'PROFILE' ) !== 'admin' )
			{
				break;
			}

			if ( ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				// Course.
				if ( $_REQUEST['w_course_period_id_which'] == 'course' )
				{
					$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID
						FROM COURSE_PERIODS cp,COURSES c
						WHERE c.COURSE_ID=cp.COURSE_ID
						AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'" );

					$extra['FROM'] .= ",SCHEDULE w_ss";

					$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID
						AND w_ss.SYEAR=ssm.SYEAR
						AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID
						AND w_ss.COURSE_ID='" . $course[1]['COURSE_ID'] . "'
						AND ('" . DBDate() . "'
							BETWEEN w_ss.START_DATE
							AND w_ss.END_DATE
							OR w_ss.END_DATE IS NULL)";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Course' ) . ': </b>'.
							$course[1]['COURSE_TITLE'] . '<br />';
					}
				}
				// Course Period.
				else
				{
					$extra['FROM'] .= ",SCHEDULE w_ss";

					$extra['WHERE'] .= " AND w_ss.STUDENT_ID=s.STUDENT_ID
						AND w_ss.SYEAR=ssm.SYEAR
						AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID
						AND w_ss.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'
						AND ('" . DBDate() . "'
							BETWEEN w_ss.START_DATE
							AND w_ss.END_DATE
							OR w_ss.END_DATE IS NULL)";

					$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID
						FROM COURSE_PERIODS cp,COURSES c
						WHERE c.COURSE_ID=cp.COURSE_ID
						AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'" );

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Course Period' ) . ': </b>' .
							$course[1]['COURSE_TITLE'] . ': ' . $course[1]['TITLE'] . '<br />';
					}
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Course' ) . '</td><td>
			<div id="course_div"></div>
			<a href="#" onclick=\'popups.open(
					"Modules.php?modname=misc/ChooseCourse.php"
				); return false;\'>' .
				_( 'Choose' ) .
			'</a>
			</td></tr>';

		break;

		// Request Widget.
		case 'request':
			if ( ! $RosarioModules['Scheduling']
				|| User( 'PROFILE' ) !== 'admin' )
			{
				break;
			}

			// PART OF THIS IS DUPLICATED IN PrintRequests.php.
			if ( ! empty( $_REQUEST['request_course_id'] ) )
			{
				$course = DBGet( "SELECT c.TITLE
					FROM COURSES c
					WHERE c.COURSE_ID='" . $_REQUEST['request_course_id'] . "'" );

				// Request.
				if ( ! isset( $_REQUEST['missing_request_course'] )
					|| ! $_REQUEST['missing_request_course'] )
				{
					$extra['FROM'] .= ",SCHEDULE_REQUESTS sr";

					$extra['WHERE'] .= " AND sr.STUDENT_ID=s.STUDENT_ID
						AND sr.SYEAR=ssm.SYEAR
						AND sr.SCHOOL_ID=ssm.SCHOOL_ID
						AND sr.COURSE_ID='" . $_REQUEST['request_course_id'] . "' ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Request' ) . ': </b>' .
							$course[1]['TITLE'] . '<br />';
					}
				}
				// Missing Request.
				else
				{
					$extra['WHERE'] .= " AND NOT EXISTS
						(SELECT '' FROM
							SCHEDULE_REQUESTS sr
							WHERE sr.STUDENT_ID=ssm.STUDENT_ID
							AND sr.SYEAR=ssm.SYEAR
							AND sr.COURSE_ID='" . $_REQUEST['request_course_id'] . "' ) ";

					if ( ! $extra['NoSearchTerms'] )
					{
						$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Missing Request' ) . ': </b>' .
							$course[1]['TITLE'] . '<br />';
					}
				}
			}

			$extra['search'] .= '<tr class="st"><td>
			'. _( 'Request' ) . '
			</td><td>
			<div id="request_div"></div>
			<a href="#" onclick=\'popups.open(
					"Modules.php?modname=misc/ChooseRequest.php"
				); return false;\'>' .
				_( 'Choose' ) .
			'</a>
			</td></tr>';

		break;

		// Days Absent Widget.
		case 'absences':

			if ( ! $RosarioModules['Attendance'] )
			{
				break;
			}

			if ( isset( $_REQUEST['absences_low'] )
				&& is_numeric( $_REQUEST['absences_low'] )
				&& isset( $_REQUEST['absences_high'] )
				&& is_numeric( $_REQUEST['absences_high'] ) )
			{
				if ( $_REQUEST['absences_low'] > $_REQUEST['absences_high'] )
				{
					$temp = $_REQUEST['absences_high'];

					$_REQUEST['absences_high'] = $_REQUEST['absences_low'];

					$_REQUEST['absences_low'] = $temp;
				}

				// Set Absences number SQL condition.
				if ( $_REQUEST['absences_low'] == $_REQUEST['absences_high'] )
				{
					$absences_sql = " = '" . $_REQUEST['absences_low'] . "'";
				}
				else
				{
					$absences_sql = " BETWEEN '" . $_REQUEST['absences_low'] . "'
						AND '" . $_REQUEST['absences_high'] . "'";
				}

				$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE
					FROM ATTENDANCE_DAY ad
					WHERE ssm.STUDENT_ID=ad.STUDENT_ID
					AND ad.SYEAR=ssm.SYEAR
					AND ad.MARKING_PERIOD_ID IN (" . GetChildrenMP( $_REQUEST['absences_term'], UserMP() ) . "))" .
					$absences_sql;

				switch ( $_REQUEST['absences_term'] )
				{
					case 'FY':
						$term = _( 'this school year to date' );
					break;

					case 'SEM':
						$term = _( 'this semester to date' );
					break;

					case 'QTR':
						$term = _( 'this marking period to date' );
					break;
				}

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Days Absent' ) . ' ' . $term . ': </b>' .
						_( 'Between' ) . ' ' .
						$_REQUEST['absences_low'] . ' &amp; ' . $_REQUEST['absences_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' .	_( 'Days Absent' ) .
			'<br />
			<label>
				<input type="radio" name="absences_term" value="FY" checked />&nbsp;' .
				_( 'YTD' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" name="absences_term" value="SEM" />&nbsp;' .
				GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" name="absences_term" value="QTR" />&nbsp;' .
				GetMP( UserMP(), 'SHORT_NAME' ) .
			'</label>
			</td><td><label>' . _( 'Between' ) .
			' <input type="text" name="absences_low" size="3" maxlength="3" /></label>' .
			' <label>&amp; ' .
			'<input type="text" name="absences_high" size="3" maxlength="3" /><label>
			</td></tr>';

		break;

		// Course Period Absences Widget
		// for admins only (relies on the Course widget).
		case 'cp_absences':

			if ( ! $RosarioModules['Attendance']
				|| User( 'PROFILE' ) !== 'admin' )
			{
				break;
			}

			if ( isset( $_REQUEST['cp_absences_low'] )
				&& is_numeric( $_REQUEST['cp_absences_low'] )
				&& isset( $_REQUEST['cp_absences_high'] )
				&& is_numeric( $_REQUEST['cp_absences_high'] )
				&& isset( $_REQUEST['w_course_period_id'] )
				&& is_numeric( $_REQUEST['w_course_period_id'] ) )
			{
				if ( $_REQUEST['cp_absences_low'] > $_REQUEST['cp_absences_high'] )
				{
					$temp = $_REQUEST['cp_absences_high'];

					$_REQUEST['cp_absences_high'] = $_REQUEST['cp_absences_low'];

					$_REQUEST['cp_absences_low'] = $temp;
				}


				// Set Term SQL condition, if not Full Year.
				$term_sql = '';

				if ( $_REQUEST['cp_absences_term'] !== 'FY' )
				{
					$term_sql = " AND cast(ap.MARKING_PERIOD_ID as text)
						IN(" . GetChildrenMP( $_REQUEST['cp_absences_term'], UserMP() ) . ")";
				}

				// Set Absences number SQL condition.
				if ( $_REQUEST['cp_absences_low'] == $_REQUEST['cp_absences_high'] )
				{
					$absences_sql = " = '" . $_REQUEST['cp_absences_low'] . "'";
				}
				else
				{
					$absences_sql = " BETWEEN '" . $_REQUEST['cp_absences_low'] . "'
						AND '" . $_REQUEST['cp_absences_high'] . "'";
				}

				$extra['WHERE'] .= " AND (SELECT count(*)
					FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE
					AND ac.STATE_CODE='A'
					AND ap.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'" .
					$term_sql .
					" AND ap.STUDENT_ID=ssm.STUDENT_ID)" .
					$absences_sql;

				switch ( $_REQUEST['cp_absences_term'] )
				{
					case 'FY':

						$term = _( 'this school year to date' );

					break;

					case 'SEM':

						$term = _( 'this semester to date' );

					break;

					case 'QTR':

						$term = _( 'this marking period to date' );

					break;
				}

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Course Period Absences' ) . ' ' .
						$term . ': </b>' . _( 'Between' ) . ' ' .
						$_REQUEST['cp_absences_low'] . ' &amp; ' .
						$_REQUEST['cp_absences_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' .	_( 'Course Period Absences' ) .
			'<div class="tooltip"><i>' .
				_( 'Use the Choose link of the Course widget (under Scheduling) to select a Course Period.' ) .
			'</i></div>' .
			'<br />
			<label>
				<input type="radio" name="cp_absences_term" value="FY" checked />&nbsp;' .
				_( 'YTD' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" name="cp_absences_term" value="SEM" />&nbsp;' .
				GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" name="cp_absences_term" value="QTR" />&nbsp;' .
				GetMP( UserMP(), 'SHORT_NAME' ) .
			'</label>
			</td><td><label>' . _( 'Between' ) .
			' <input type="text" name="cp_absences_low" size="3" maxlength="3" /></label>' .
			' <label>&amp;' .
			' <input type="text" name="cp_absences_high" size="3" maxlength="3" /></label>
			</td></tr>';

		break;

		// GPA Widget.
		case 'gpa':

			if ( ! $RosarioModules['Grades'] )
			{
				break;
			}

			if ( isset( $_REQUEST['gpa_low'] )
				&& is_numeric( $_REQUEST['gpa_low'] )
				&& isset( $_REQUEST['gpa_high'] )
				&& is_numeric( $_REQUEST['gpa_high'] ) )
			{
				if ( $_REQUEST['gpa_low'] > $_REQUEST['gpa_high'] )
				{
					$temp = $_REQUEST['gpa_high'];
					$_REQUEST['gpa_high'] = $_REQUEST['gpa_low'];
					$_REQUEST['gpa_low'] = $temp;
				}

				if ( ! empty( $_REQUEST['list_gpa'] ) )
				{
					$extra['SELECT'] .= ',sms.CUM_WEIGHTED_FACTOR,sms.CUM_UNWEIGHTED_FACTOR';

					$extra['columns_after']['CUM_WEIGHTED_FACTOR'] = _( 'Weighted GPA' );
					$extra['columns_after']['CUM_UNWEIGHTED_FACTOR'] = _( 'Unweighted GPA' );
				}

				if ( mb_strpos( $extra['FROM'], 'STUDENT_MP_STATS sms' ) === false )
				{
					$extra['FROM'] .= ",STUDENT_MP_STATS sms";

					$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID
						AND sms.MARKING_PERIOD_ID='" . $_REQUEST['gpa_term'] . "'";
				}

				$extra['WHERE'] .= " AND sms.CUM_" . ( ($_REQUEST['gpa_weighted'] == 'Y' ) ? '' : 'UN' ) . "WEIGHTED_FACTOR *
					" . SchoolInfo( 'REPORTING_GP_SCALE' ) . "
					BETWEEN '" . $_REQUEST['gpa_low'] . "' AND '" . $_REQUEST['gpa_high'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' .
						( ( $_REQUEST['gpa_weighted'] == 'Y' ) ?
							_( 'Weighted GPA' ) :
							_( 'Unweighted GPA' ) ) .
						' ' . _( 'Between' ) . ': </b>' .
						$_REQUEST['gpa_low'] . ' &amp; ' . $_REQUEST['gpa_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'GPA' ) . '<br />
			<label>
				<input type="checkbox" name="gpa_weighted" value="Y">&nbsp;' . _( 'Weighted' ) .
			'</label>
			<br />';

			if ( GetMP( $MPfy = GetParentMP( 'FY', GetParentMP( 'SEM', UserMP() ) ), 'DOES_GRADES') == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="gpa_term" value="' . $MPfy . '" checked />&nbsp;' .
						GetMP( $MPfy, 'SHORT_NAME' ) .
					'</label>&nbsp; ';
			}

			if ( GetMP( $MPsem = GetParentMP( 'SEM', UserMP() ), 'DOES_GRADES' ) == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="gpa_term" value="' . $MPsem . '">&nbsp;' .
						GetMP( $MPsem, 'SHORT_NAME' ) .
					'</label> &nbsp;';
			}

			if ( GetMP( $MPtrim = UserMP(), 'DOES_GRADES' ) == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="gpa_term" value="' . $MPtrim . '" checked />&nbsp;' .
						GetMP( $MPtrim, 'SHORT_NAME' ) .
					'</label>';
			}

			$extra['search'] .= '</td><td><label>' . _( 'Between' ) .
			' <input type="text" name="gpa_low" size="3" maxlength="5" /></label>' .
			' <label>&amp;' .
			' <input type="text" name="gpa_high" size="3" maxlength="5" /></label>
			</td></tr>';

		break;

		// Class Rank Widget.
		case 'class_rank':

			if ( ! $RosarioModules['Grades'] )
			{
				break;
			}

			if ( isset( $_REQUEST['class_rank_low'] )
				&& is_numeric( $_REQUEST['class_rank_low'] )
				&& isset( $_REQUEST['class_rank_high'] )
				&& is_numeric( $_REQUEST['class_rank_high'] ) )
			{
				if ( $_REQUEST['class_rank_low'] > $_REQUEST['class_rank_high'] )
				{
					$temp = $_REQUEST['class_rank_high'];
					$_REQUEST['class_rank_high'] = $_REQUEST['class_rank_low'];
					$_REQUEST['class_rank_low'] = $temp;
				}

				if ( mb_strpos( $extra['FROM'], 'STUDENT_MP_STATS sms' ) === false )
				{
					$extra['FROM'] .= ",STUDENT_MP_STATS sms";

					$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID
						AND sms.MARKING_PERIOD_ID='" . $_REQUEST['class_rank_term'] . "'";
				}

				$extra['WHERE'] .= " AND sms.CUM_RANK BETWEEN
					'" . $_REQUEST['class_rank_low'] . "'
					AND '" . $_REQUEST['class_rank_high'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Class Rank' ) . ' ' . _( 'Between' ) . ': </b>' .
						$_REQUEST['class_rank_low'] . ' &amp; ' . $_REQUEST['class_rank_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Class Rank' ) . '<br />';

			if ( GetMP( $MPfy = GetParentMP( 'FY', GetParentMP( 'SEM', UserMP() ) ), 'DOES_GRADES' ) == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="class_rank_term" value="' . $MPfy . '">&nbsp;' .
						GetMP( $MPfy, 'SHORT_NAME' ) .
					'</label>&nbsp; ';
			}

			if ( GetMP( $MPsem = GetParentMP( 'SEM', UserMP() ), 'DOES_GRADES' ) == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="class_rank_term" value="' . $MPsem . '">&nbsp;' .
						GetMP( $MPsem, 'SHORT_NAME' ) .
					'</label> &nbsp; ';
			}

			if ( GetMP( $MPtrim = UserMP(), 'DOES_GRADES' ) == 'Y' )
			{
				$extra['search'] .= '<label>
						<input type="radio" name="class_rank_term" value="' . $MPtrim . '" checked />&nbsp;' .
						GetMP( $MPtrim, 'SHORT_NAME' ) .
					'</label>';
			}

			if ( mb_strlen( $pros = GetChildrenMP( 'PRO', UserMP() ) ) )
			{
				$pros = explode( ',', str_replace( "'", '', $pros ) );

				foreach ( (array) $pros as $pro )
				{
					$extra['search'] .= '<label>
							<input type="radio" name="class_rank_term" value="' . $pro . '">&nbsp;' .
							GetMP( $pro, 'SHORT_NAME' ) .
						'</label> &nbsp;';
				}
			}

			$extra['search'] .= '</td><td><label>' . _( 'Between' ) .
			' <input type="text" name="class_rank_low" size="3" maxlength="5" /></label>' .
			' <label>&amp;' .
			' <input type="text" name="class_rank_high" size="3" maxlength="5" /></label>
			</td></tr>';

		break;

		// Report Card Grade Widget.
		case 'letter_grade':

			if ( ! $RosarioModules['Grades'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['letter_grade'] ) )
			{
				$LetterGradeSearchTerms = '<b>' . ( $_REQUEST['letter_grade_exclude'] == 'Y' ?
						_( 'Without' ) :
						_( 'With' ) ) .
					' ' . _( 'Report Card Grade' ) . ': </b>';

				$letter_grades_RET = DBGet( "SELECT ID,TITLE
					FROM REPORT_CARD_GRADES
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "'", array(), array( 'ID' ) );

				foreach ( (array) $_REQUEST['letter_grade'] as $grade => $yes )
				{
					if ( ! $yes )
					{
						continue;
					}

					$letter_grades .= ",'" . $grade . "'";

					$LetterGradeSearchTerms .= $letter_grades_RET[ $grade ][1]['TITLE'].', ';
				}

				$LetterGradeSearchTerms = mb_substr( $LetterGradeSearchTerms, 0, -2 ) . '<br />';

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= $LetterGradeSearchTerms;
				}

				$extra['WHERE'] .= " AND " . ( $_REQUEST['letter_grade_exclude'] == 'Y' ? 'NOT ' : '' ) . "EXISTS
					(SELECT ''
						FROM STUDENT_REPORT_CARD_GRADES sg3
						WHERE sg3.STUDENT_ID=ssm.STUDENT_ID
						AND sg3.SYEAR=ssm.SYEAR
						AND sg3.REPORT_CARD_GRADE_ID IN (" . mb_substr( $letter_grades, 1 ) . ")
						AND sg3.MARKING_PERIOD_ID='" . $_REQUEST['letter_grade_term'] . "' )";
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Grade' ) . '<br />
			<label>
				<input type="checkbox" name="letter_grade_exclude" value="Y" />&nbsp;' . _( 'Did not receive' ) .
			'</label>
			<br />
			<label class="nobr">
				<input type="radio" name="letter_grade_term" value="' . GetParentMP( 'SEM', UserMP() ) . '" />&nbsp;' .
				GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
			'</label> &nbsp;
			<label class="nobr">
				<input type="radio" name="letter_grade_term" value="' . UserMP() . '" checked />&nbsp;' .
				GetMP( UserMP(), 'SHORT_NAME' ) .
			'</label>';

			if ( mb_strlen( $pros = GetChildrenMP( 'PRO', UserMP() ) ) )
			{
				$pros = explode( ',', str_replace( "'", '', $pros ) );

				foreach ( (array) $pros as $pro )
				{
					$extra['search'] .= '<label class="nobr">
							<input type="radio" name="letter_grade_term" value="' . $pro . '" />&nbsp;' .
							GetMP( $pro, 'SHORT_NAME' ) .
						'</label> &nbsp;';
				}
			}

			$extra['search'] .= '</td><td>';

			// FJ fix error Invalid argument supplied for foreach().
			if ( empty( $_REQUEST['search_modfunc'] ) )
			{
				$letter_grades_RET = DBGet( "SELECT rg.ID,rg.TITLE,rg.GRADE_SCALE_ID
					FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs
					WHERE rg.SCHOOL_ID='" . UserSchool() . "'
					AND rg.SYEAR='" . UserSyear() . "'
					AND rs.ID=rg.GRADE_SCALE_ID" .
					( User( 'PROFILE' ) === 'teacher' ?
					" AND rg.GRADE_SCALE_ID=
						(SELECT GRADE_SCALE_ID
							FROM COURSE_PERIODS
							WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')" :
					'' ) .
					" ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER",
					array(), array( 'GRADE_SCALE_ID' ) );

				$j = 0;

				foreach ( (array) $letter_grades_RET as $grades )
				{
					$i = 0;

					if ( $j++ > 0 )
					{
						$extra['search'] .= '<br /><br />';
					}

					foreach ( (array) $grades as $grade )
					{
						$extra['search'] .= '<label>
								<input type="checkbox" value="Y" name="letter_grade[' . $grade['ID'] . ']" />&nbsp;' .
								$grade['TITLE'] .
							'</label> &nbsp; ';

						if ( ++$i%6 === 0 )
						{
							$extra['search'] .= '<br /><br />';
						}
					}
				}
			}

			$extra['search'] .= '</td></tr>';

		break;

		// Eligibility (Ineligible) Widget.
		case 'eligibility':

			if ( ! $RosarioModules['Eligibility'] )
			{
				break;
			}

			if ( isset( $_REQUEST['ineligible'] )
				&& $_REQUEST['ineligible'] == 'Y' )
			{
				switch ( date( 'D' ) )
				{
					case 'Mon':

						$today = 1;

					break;

					case 'Tue':

						$today = 2;

					break;

					case 'Wed':

						$today = 3;

					break;

					case 'Thu':

						$today = 4;

					break;

					case 'Fri':

						$today = 5;

					break;

					case 'Sat':

						$today = 6;

					break;

					case 'Sun':

						$today = 7;

					break;
				}

				$start_date = date(
					'Y-m-d',
					time() - ( $today - ProgramConfig( 'eligibility', 'START_DAY' ) ) * 60 * 60 * 24
				);

				$end_date = DBDate();

				$extra['WHERE'] .= " AND (SELECT count(*)
					FROM ELIGIBILITY e
					WHERE ssm.STUDENT_ID=e.STUDENT_ID
					AND e.SYEAR=ssm.SYEAR
					AND e.SCHOOL_DATE BETWEEN '" . $start_date . "'
					AND '" . $end_date . "'
					AND e.ELIGIBILITY_CODE='FAILING') > '0'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Eligibility' ) . ': </b>' .
						_( 'Ineligible' ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>
			</td><td>
			<label>
				<input type="checkbox" name="ineligible" value="Y" />&nbsp;' . _( 'Ineligible' ) .
			'</label>
			</td></tr>';

		break;

		// Activity (Eligibility) Widget.
		case 'activity':

			if ( ! $RosarioModules['Eligibility'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['activity_id'] ) )
			{
				$extra['FROM'] .= ",STUDENT_ELIGIBILITY_ACTIVITIES sea";

				$extra['WHERE'] .= " AND sea.STUDENT_ID=s.STUDENT_ID
					AND sea.SYEAR=ssm.SYEAR
					AND sea.ACTIVITY_ID='" . $_REQUEST['activity_id'] . "'";

				$activity_title = DBGetOne( "SELECT TITLE
					FROM ELIGIBILITY_ACTIVITIES
					WHERE ID='" . $_REQUEST['activity_id'] . "'" );

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Activity' ) . ': </b>' .
						$activity_title . '<br />';
				}
			}

			$activities_RET = array();

			if ( empty( $_REQUEST['search_modfunc'] ) )
			{
				$activities_RET = DBGet( "SELECT ID,TITLE
					FROM ELIGIBILITY_ACTIVITIES
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "'" );
			}

			$select = '<select name="activity_id" id="activity_id">
				<option value="">' . _( 'Not Specified' ) . '</option>';

			foreach ( (array) $activities_RET as $activity )
			{
				$select .= '<option value="' . $activity['ID'] . '">' . $activity['TITLE'] . '</option>';
			}

			$select .= '</select>';

			$extra['search'] .= '<tr class="st"><td><label for="activity_id">' .
			_( 'Activity' ) .
			'</label></td><td>' .
			$select .
			'</td></tr>';

		break;

		// Mailing Labels Widget.
		case 'mailing_labels':

			if ( $_REQUEST['mailing_labels'] == 'Y' )
			{
				require_once 'ProgramFunctions/MailingLabel.fnc.php';

				$extra['SELECT'] .= ',coalesce(sam.ADDRESS_ID,-ssm.STUDENT_ID) AS ADDRESS_ID,
					sam.ADDRESS_ID AS MAILING_LABEL';

				$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam
					ON (sam.STUDENT_ID=ssm.STUDENT_ID
						AND sam.MAILING='Y'" . ( $_REQUEST['residence'] == 'Y' ? " AND sam.RESIDENCE='Y'" : '' ) . ")" .
					$extra['FROM'];

				$extra['functions'] += array( 'MAILING_LABEL' => 'MailingLabel' );
			}

			$extra['search'] .= '<tr class="st"><td>' .
				'<label for="mailing_labels">' . _( 'Mailing Labels' ) . '</label>' .
				'</td><td>' .
				'<input type="checkbox" id="mailing_labels" name="mailing_labels" value="Y" />' .
				'</td>';

		break;

		// Student Billing Balance Widget.
		case 'balance':

			if ( ! $RosarioModules['Student_Billing']
				|| !AllowUse( 'Student_Billing/StudentFees.php' ) )
			{
				break;
			}

			if ( isset( $_REQUEST['balance_low'] )
				&& is_numeric( $_REQUEST['balance_low'] )
				&& isset( $_REQUEST['balance_high'] )
				&& is_numeric( $_REQUEST['balance_high'] ) )
			{
				if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'] )
				{
					$temp = $_REQUEST['balance_high'];
					$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
					$_REQUEST['balance_low'] = $temp;
				}

				$extra['WHERE'] .= " AND (
					coalesce((SELECT sum(p.AMOUNT)
						FROM BILLING_PAYMENTS p
						WHERE p.STUDENT_ID=ssm.STUDENT_ID
						AND p.SYEAR=ssm.SYEAR),0) -
					coalesce((SELECT sum(f.AMOUNT)
						FROM BILLING_FEES f
						WHERE f.STUDENT_ID=ssm.STUDENT_ID
						AND f.SYEAR=ssm.SYEAR),0))
					BETWEEN '" . $_REQUEST['balance_low'] . "'
					AND '" . $_REQUEST['balance_high'] . "' ";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Student Billing Balance' ) . ' ' . _( 'Between' ) .': </b>' .
						$_REQUEST['balance_low'] . ' &amp; ' .
						$_REQUEST['balance_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Balance' ) . '</td><td><label>' . _( 'Between' ) .
			' <input type="text" name="balance_low" size="5" maxlength="10" /></label>' .
			' <label>&amp;' .
			' <input type="text" name="balance_high" size="5" maxlength="10" /></label>
			</td></tr>';

		break;

		// Discipline Reporter Widget.
		case 'reporter':

			if ( ! $RosarioModules['Discipline'] )
			{
				break;
			}

			$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
				FROM STAFF
				WHERE SYEAR='" . UserSyear() . "'
				AND (SCHOOLS IS NULL OR SCHOOLS LIKE '%," . UserSchool() . ",%')
				AND (PROFILE='admin' OR PROFILE='teacher')
				ORDER BY LAST_NAME,FIRST_NAME,MIDDLE_NAME", array(), array( 'STAFF_ID' ) );

			if ( ! empty( $_REQUEST['discipline_reporter'] ) )
			{
				if ( mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS' ) === false )
				{
					$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID
						AND dr.SYEAR=ssm.SYEAR
						AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';

					$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
				}

				$extra['WHERE'] .= " AND dr.STAFF_ID='" . $_REQUEST['discipline_reporter'] . "' ";

				$reporter = $users_RET[ $_REQUEST['discipline_reporter'] ][1];

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Reporter' ) . ': </b>' .
						$reporter['FULL_NAME'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="discipline_reporter">' .
			_( 'Reporter' ) . '</label></td><td>
			<select name="discipline_reporter" id="discipline_reporter">
				<option value="">' . _( 'Not Specified' ) . '</option>';

			foreach ( (array) $users_RET as $id => $user )
			{
				$extra['search'] .= '<option value="' . $id . '">' .
						$user[1]['FULL_NAME'] .
					'</option>';
			}

			$extra['search'] .= '</select>';

			$extra['search'] .= '</td></tr>';

		break;

		// Discipline Incident Date Widget.
		case 'incident_date':

			if ( ! $RosarioModules['Discipline'] )
			{
				break;
			}

			$discipline_entry_begin = RequestedDate( 'discipline_entry_begin', $_REQUEST['discipline_entry_begin'] );

			$discipline_entry_end = RequestedDate( 'discipline_entry_end', $_REQUEST['discipline_entry_end'] );

			if ( ( $discipline_entry_begin
					|| $discipline_entry_end )
				&& mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS' ) === false  )
			{
				$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID
					AND dr.SYEAR=ssm.SYEAR
					AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';

				$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
			}

			if ( $discipline_entry_begin
				&& $discipline_entry_end )
			{
				$extra['WHERE'] .= " AND dr.ENTRY_DATE
					BETWEEN '" . $discipline_entry_begin .
					"' AND '" . $discipline_entry_end . "' ";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'Between' ) . ': </b>' .
						ProperDate( $discipline_entry_begin ) . ' &amp; ' .
						ProperDate( $discipline_entry_end ) . '<br />';
				}
			}
			elseif ( $discipline_entry_begin )
			{
				$extra['WHERE'] .= " AND dr.ENTRY_DATE>='" . $discipline_entry_begin . "' ";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'On or After' ) . ' </b>' .
						ProperDate( $discipline_entry_begin ) . '<br />';
				}
			}
			elseif ( $discipline_entry_end )
			{
				$extra['WHERE'] .= " AND dr.ENTRY_DATE<='" . $discipline_entry_end . "' ";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'On or Before' ) . ' </b>' .
						ProperDate( $discipline_entry_end ) . '<br />';
				}
			}

			$discipline_entry_begin_default = '';

			if ( $_REQUEST['modname'] === 'Discipline/Referrals.php' )
			{
				// Set default Incident Date for Referrals program only.
				$discipline_entry_begin_default = date( 'Y-m' ) . '-01';
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Incident Date' ) . '</td><td>
			<table class="cellspacing-0"><tr><td>
			<span class="sizep2">&ge;</span>&nbsp;
			</td><td>
			' . PrepareDate( $discipline_entry_begin_default, '_discipline_entry_begin', true, array( 'short' => true ) ).'
			</td></tr><tr><td>
			<span class="sizep2">&le;</span>&nbsp;
			</td><td>
			' . PrepareDate( '', '_discipline_entry_end', true, array( 'short' => true ) ).'
			</td></tr></table>
			</td></tr>';

		break;

		// Discipline Fields Widgets.
		case 'discipline_fields':

			if ( ! $RosarioModules['Discipline'] )
			{
				break;
			}

			if ( isset( $_REQUEST['discipline'] )
				&& is_array( $_REQUEST['discipline'] ) )
			{
				//modify loop: use for instead of foreach
				$key = array_keys( $_REQUEST['discipline'] );
				$size = count( $key );

				for ( $i = 0; $i < $size; $i++ )
				{
					if ( ! ( $_REQUEST['discipline'][ $key[ $i ] ] ) )
					{
						unset( $_REQUEST['discipline'][ $key[ $i ] ] );
					}
				}

				/*foreach ( (array) $_REQUEST['discipline'] as $key => $value)
				{
					if(! $value)
						unset($_REQUEST['discipline'][ $key ]);
				}*/
			}

			// FJ bugfix wrong advanced student search results, due to discipline numeric fields.
			if ( isset( $_REQUEST['discipline_begin'] )
				&& is_array( $_REQUEST['discipline_begin'] ) )
			{
				// Modify loop: use for instead of foreach.
				$key = array_keys( $_REQUEST['discipline_begin'] );
				$size = count( $key );

				for ( $i = 0; $i < $size; $i++ )
				{
					if ( !( $_REQUEST['discipline_begin'][ $key[ $i ] ] )
						|| !is_numeric( $_REQUEST['discipline_begin'][ $key[ $i ] ] ) )
					{
						unset( $_REQUEST['discipline_begin'][ $key[ $i ] ] );
					}
				}

				/*foreach ( (array) $_REQUEST['discipline_begin'] as $key => $value)
				{
					if(! $value)
						unset($_REQUEST['discipline_begin'][ $key ]);
				}*/
			}

			if ( isset( $_REQUEST['discipline_end'] )
				&& is_array( $_REQUEST['discipline_end'] ) )
			{
				// Modify loop: use for instead of foreach.
				$key = array_keys( $_REQUEST['discipline_end'] );
				$size = count( $key );

				for ( $i = 0; $i < $size; $i++ )
				{
					if ( ! ( $_REQUEST['discipline_end'][ $key[ $i ] ] )
						|| ! is_numeric( $_REQUEST['discipline_end'][ $key[ $i ] ] ) )
					{
						unset( $_REQUEST['discipline_end'][ $key[ $i ] ] );
					}
				}

				/*foreach ( (array) $_REQUEST['discipline_end'] as $key => $value)
				{
					if(! $value)
						unset($_REQUEST['discipline_end'][ $key ]);
				}*/
			}

			if ( ( ! empty( $_REQUEST['discipline'] )
					|| ! empty( $_REQUEST['discipline_begin'] )
					|| ! empty( $_REQUEST['discipline_end'] ) )
				&& mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS' ) === false )
			{
				$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID
					AND dr.SYEAR=ssm.SYEAR
					AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';

				$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
			}

			$categories_RET = DBGet( "SELECT f.ID,u.TITLE,f.DATA_TYPE,u.SELECT_OPTIONS
				FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u
				WHERE u.DISCIPLINE_FIELD_ID=f.ID
				AND u.SYEAR='" . UserSyear() . "'
				AND u.SCHOOL_ID='" . UserSchool() . "'
				AND f.DATA_TYPE!='textarea'
				AND f.DATA_TYPE!='date'" );

			foreach ( (array) $categories_RET as $category )
			{
				$input_name = 'discipline[' . $category['ID'] . ']';

				if ( $category['DATA_TYPE'] !== 'numeric' )
				{
					$input_id = GetInputID( $input_name );

					$extra['search'] .= '<tr class="st"><td><label for="' . $input_id . '">' .
						$category['TITLE'] . '</label></td><td>';
				}
				else
				{
					$extra['search'] .= '<tr class="st"><td>' . $category['TITLE'] . '</td><td>';
				}

				switch ( $category['DATA_TYPE'] )
				{
					case 'text':

						$extra['search'] .= '<input type="text" name="' . $input_name .
							'" id="' . $input_id . '" size="24" maxlength="255" />';

						if ( ! empty( $_REQUEST['discipline'][ $category['ID'] ] ) )
						{
							$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
								" LIKE '" . $_REQUEST['discipline'][ $category['ID'] ] . "%' ";

							if ( ! $extra['NoSearchTerms'] )
							{
								$_ROSARIO['SearchTerms'] .= '<b>' . $category['TITLE'] . ': </b> ' .
									$_REQUEST['discipline'][ $category['ID'] ] . '<br />';
							}
						}

					break;

					case 'checkbox':

						$extra['search'] .= '<input type="checkbox" name="' . $input_name .
							'" id="' . $input_id . '" value="Y" />';

						if ( ! empty( $_REQUEST['discipline'][ $category['ID'] ] ) )
						{
							$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] . "='Y' ";

							if ( ! $extra['NoSearchTerms'] )
							{
								$_ROSARIO['SearchTerms'] .= '<b>' . $category['TITLE'] . '</b><br />';
							}

						}

					break;

					case 'numeric':

						$extra['search'] .= '<label>' . _( 'Between' ) .
							' <input type="text" name="discipline_begin[' . $category['ID'] .
								']" size="3" maxlength="11" /></label>' .
							' <label>&amp;' .
							' <input type="text" name="discipline_end[' . $category['ID'] .
								']" size="3" maxlength="11" /></label>';

						if ( $_REQUEST['discipline_begin'][ $category['ID'] ]
							&& $_REQUEST['discipline_end'][ $category['ID'] ] )
						{
							$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
								" BETWEEN '" . $_REQUEST['discipline_begin'][ $category['ID'] ] .
								"' AND '" . $_REQUEST['discipline_end'][ $category['ID'] ] . "' ";

							if ( ! $extra['NoSearchTerms'] )
							{
								$_ROSARIO['SearchTerms'] .= '<b>' . $category['TITLE'] . ' ' . _( 'Between' ) . ': </b>' .
									$_REQUEST['discipline_begin'][ $category['ID'] ] . ' &amp; ' .
									$_REQUEST['discipline_end'][ $category['ID'] ] . '<br />';
							}
						}

					break;

					case 'multiple_checkbox':
					case 'multiple_radio':
					case 'select':

						$category['SELECT_OPTIONS'] = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS'] ) );

						$extra['search'] .= '<select name="' . $input_name . '" id="' . $input_id . '">
							<option value="">' . _( 'N/A' ) . '</option>';

						foreach ( (array) $category['SELECT_OPTIONS'] as $option )
						{
							$extra['search'] .= '<option value="' . $option . '">' . $option . '</option>';
						}

						$extra['search'] .= '</select>';

						if ( ! empty( $_REQUEST['discipline'][ $category['ID'] ] ) )
						{
							if ( $category['DATA_TYPE'] == 'multiple_radio'
								|| $category['DATA_TYPE'] == 'select' )
							{
								$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
									" = '" . $_REQUEST['discipline'][ $category['ID'] ] . "' ";
							}
							elseif ( $category['DATA_TYPE'] == 'multiple_checkbox' )
							{
								$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
									" LIKE '%||" . $_REQUEST['discipline'][ $category['ID'] ] . "||%' ";
							}

							if ( ! $extra['NoSearchTerms'] )
							{
								$_ROSARIO['SearchTerms'] .= '<b>' . $category['TITLE'] . ': </b>' .
									$_REQUEST['discipline'][ $category['ID'] ] . '<br />';
							}
						}

					break;
				}

				$extra['search'] .= '</td></tr>';
			}

		break;

		// Next Year (Enrollment) Widget.
		case 'next_year':

			if ( ! $RosarioModules['Students'] )
			{
				break;
			}

			$schools_RET = DBGet( "SELECT ID,TITLE
				FROM SCHOOLS
				WHERE ID!='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			$next_year_options = array(
				'' => _( 'N/A' ),
				'!' => _( 'No Value' ),
				UserSchool() => _( 'Next grade at current school' ),
				'0' => _( 'Retain' ),
				'-1' => _( 'Do not enroll after this school year' ),
			);

			foreach ( (array) $schools_RET as $school )
			{
				$next_year_options[ $school['ID'] ] = $school['TITLE'];
			}

			if ( ! empty( $_REQUEST['next_year'] ) )
			{
				if ( $_REQUEST['next_year'] == '!' )
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL IS NULL";
				}
				else
				{
					$extra['WHERE'] .= " AND ssm.NEXT_SCHOOL='" . $_REQUEST['next_year'] . "'";
				}

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Next Year' ) . ': </b>' .
						$next_year_options[$_REQUEST['next_year']] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="next_year">' . _( 'Next Year' ) . '</label></td><td>
			<select name="next_year" id="next_year">';

			foreach ( (array) $next_year_options as $id => $option )
			{
				$extra['search'] .= '<option value="' . $id . '">' . $option . '</option>';
			}

			$extra['search'] .= '</select></td></tr>';

		break;

		// Calendar (Enrollment) Widget.
		case 'calendar':

			if ( ! $RosarioModules['Students'] )
			{
				break;
			}

			$calendars_RET = DBGet( "SELECT CALENDAR_ID,TITLE
				FROM ATTENDANCE_CALENDARS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY DEFAULT_CALENDAR ASC" );

			if ( ! empty( $_REQUEST['calendar'] ) )
			{
				if ( $_REQUEST['calendar'] === '!' )
				{
					$where_not = ( $_REQUEST['calendar_not'] === 'Y' ? 'NOT ' : '' );

					$extra['WHERE'] .= " AND ssm.CALENDAR_ID IS " . $where_not . "NULL";

					$text_not = ( $_REQUEST['calendar_not'] === 'Y' ? _( 'Any Value' ) : _( 'No Value' ) );
				}
				else
				{
					$where_not = ( $_REQUEST['calendar_not'] === 'Y' ? '!' : '' );

					$extra['WHERE'] .= " AND ssm.CALENDAR_ID" . $where_not . "='" . $_REQUEST['calendar'] . "'";

					foreach ( (array) $calendars_RET as $calendar )
					{
						if ( $_REQUEST['calendar'] === $calendar['CALENDAR_ID'] )
						{
							$calendar_title = $calendar['TITLE'];

							break;
						}
					}

					$text_not = ( $_REQUEST['calendar_not'] == 'Y' ? _( 'Not' ) . ' ' : '' ) .
						$calendar_title;
				}

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Calendar' ) . ': </b>' . $text_not . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="calendar_input">' . _( 'Calendar' ) . '</label></td><td>
			<label>
				<input type="checkbox" name="calendar_not" value="Y" /> ' . _( 'Not' ) .
			'</label>
			<select name="calendar" id="calendar_input">
				<option value="">' . _( 'N/A' ) . '</option>
				<option value="!">' . _( 'No Value' ) . '</option>';

			foreach ( (array) $calendars_RET as $calendar )
			{
				$extra['search'] .= '<option value="' . $calendar['CALENDAR_ID'] . '">' .
					$calendar['TITLE'] . '</option>';
			}

			$extra['search'] .= '</select></td></tr>';

		break;

		// Attendance Start / Enrolled Widget.
		case 'enrolled':

			if ( ! $RosarioModules['Students'] )
			{
				break;
			}

			// Verify enrolled begin date.
			if ( ! empty( $_REQUEST['month_enrolled_begin'] )
				&& ! empty( $_REQUEST['day_enrolled_begin'] )
				&& ! empty( $_REQUEST['year_enrolled_begin'] ) )
			{
				$_REQUEST['enrolled_begin'] = RequestedDate(
					$_REQUEST['year_enrolled_begin'],
					$_REQUEST['month_enrolled_begin'],
					$_REQUEST['day_enrolled_begin']
				);
			}

			// Verify enrolled end date.
			if ( ! empty( $_REQUEST['month_enrolled_end'] )
				&& ! empty( $_REQUEST['day_enrolled_end'] )
				&& ! empty( $_REQUEST['year_enrolled_end'] ) )
			{
				$_REQUEST['enrolled_end'] = RequestedDate(
					$_REQUEST['year_enrolled_end'],
					$_REQUEST['month_enrolled_end'],
					$_REQUEST['day_enrolled_end']
				);
			}

			if ( ! empty( $_REQUEST['enrolled_begin'] )
				&& ! empty( $_REQUEST['enrolled_end'] ) )
			{
				$extra['WHERE'] .= " AND ssm.START_DATE
					BETWEEN '" . $_REQUEST['enrolled_begin'] .
					"' AND '" . $_REQUEST['enrolled_end'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'Between' ) . ': </b>' .
						ProperDate( $_REQUEST['enrolled_begin'] ) . ' &amp; ' .
						ProperDate( $_REQUEST['enrolled_end'] ) . '<br />';
				}
			}
			elseif ( ! empty( $_REQUEST['enrolled_begin'] ) )
			{
				$extra['WHERE'] .= " AND ssm.START_DATE>='" . $_REQUEST['enrolled_begin'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'On or After' ) . ': </b>' .
						ProperDate( $_REQUEST['enrolled_begin'] ) . '<br />';
				}
			}
			elseif ( ! empty( $_REQUEST['enrolled_end'] ) )
			{
				$extra['WHERE'] .= " AND ssm.START_DATE<='" . $_REQUEST['enrolled_end'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'On or Before' ) . ': </b>' .
						ProperDate( $_REQUEST['enrolled_end'] ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Attendance Start' ) . '</td><td>
			<table class="cellspacing-0"><tr><td class="sizep2">
			&ge;
			</td><td>
			' . PrepareDate( '', '_enrolled_begin', true, array( 'short' => true ) ) . '
			</td></tr><tr><td class="sizep2">
			&le;
			</td><td>
			' . PrepareDate( '', '_enrolled_end', true, array( 'short' => true ) ) . '
			</td></tr></table>
			</td></tr>';

		break;

		// Previously Enrolled Widget.
		case 'rolled':

			if ( ! $RosarioModules['Students'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['rolled'] ) )
			{
				$extra['WHERE'] .= " AND " . ( $_REQUEST['rolled'] == 'Y' ? '' : 'NOT ' ) . "exists
					(SELECT ''
						FROM STUDENT_ENROLLMENT
						WHERE STUDENT_ID=ssm.STUDENT_ID
						AND SYEAR<ssm.SYEAR)";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Previously Enrolled' ) . ': </b>' .
						( $_REQUEST['rolled'] == 'Y' ? _( 'Yes' ) : _( 'No' ) ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Previously Enrolled' ) . '</td><td>
			<label>
				<input type="radio" value="" name="rolled" checked />&nbsp;' . _( 'N/A' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" value="Y" name="rolled" />&nbsp;' . _( 'Yes' ) .
			'</label> &nbsp;
			<label>
				<input type="radio" value="N" name="rolled" />&nbsp;' . _( 'No' ) .
			'</label>
			</td></tr>';

		break;

		// Food Service Balance Warning Widget.
		case 'fsa_balance_warning':

			$value = $GLOBALS['warning'];
			$item = 'fsa_balance';

		// Food Service Balance Widget.
		case 'fsa_balance':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( isset( $_REQUEST['fsa_balance'] )
				&& is_numeric( $_REQUEST['fsa_balance'] ) )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';

					$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID';
				}

				$extra['FROM'] .= ",FOOD_SERVICE_ACCOUNTS fsa";
				$extra['WHERE'] .= " AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
					AND fsa.BALANCE" . ( empty( $_REQUEST['fsa_bal_ge'] ) ? '<' : '>=' ) .
					"'" . round(  $_REQUEST['fsa_balance'], 2 ) . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Balance' ) . ' </b> ' .
						'<span class="sizep2">' . ( empty( $_REQUEST['fsa_bal_ge'] ) ? '&lt;' : '&ge;' ) . '</span>' .
						number_format( $_REQUEST['fsa_balance'], 2 ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_balance">' . _( 'Balance' ) . '</label></td><td>
			<label class="sizep2">
				<input type="radio" name="fsa_bal_ge" value="" checked /> &lt;</label>&nbsp;
			<label  class="sizep2">
				<input type="radio" name="fsa_bal_ge" value="Y" /> &ge;</label>
			<input type="text" name="fsa_balance" id="fsa_balance" size="7" maxlength="9"' .
				( isset( $value ) ? ' value="' . $value . '"' : '') . ' />
			</td></tr>';

		break;

		// Food Service Discount Widget.
		case 'fsa_discount':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_discount'] ) )
			{
				if ( ! mb_strpos($extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";

					$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
				}

				if ( $_REQUEST['fsa_discount'] == 'Full' )
				{
					$extra['WHERE'] .= " AND fssa.DISCOUNT IS NULL";
				}
				else
					$extra['WHERE'] .= " AND fssa.DISCOUNT='" . $_REQUEST['fsa_discount'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Discount' ) . ': </b>' .
						_( $_REQUEST['fsa_discount'] ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_discount">' . _( 'Discount' ) . '</label></td><td>
			<select name="fsa_discount" id="fsa_discount">
			<option value="">' . _( 'Not Specified' ) . '</option>
			<option value="Full">' . _( 'Full' ) . '</option>
			<option value="Reduced">' . _( 'Reduced' ) . '</option>
			<option value="Free">' . _( 'Free' ) . '</option>
			</select>
			</td></tr>';

		break;

		// Food Service Active Account Status Widget.
		case 'fsa_status_active':

			$value = 'active';
			$item = 'fsa_status';

		// Food Service Account Status Widget.
		case 'fsa_status':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_status'] ) )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";

					$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
				}

				if ( $_REQUEST['fsa_status'] == 'Active' )
				{
					$extra['WHERE'] .= " AND fssa.STATUS IS NULL";
				}
				else
					$extra['WHERE'] .= " AND fssa.STATUS='" . $_REQUEST['fsa_status'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Account Status' ) . ': </b>' .
						_( $_REQUEST['fsa_status'] ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_status">' . _( 'Account Status' ) . '</label></td><td>
			<select name="fsa_status" id="fsa_status">
			<option value="">' . _( 'Not Specified' ) . '</option>
			<option value="Active"' . ( isset( $value ) == 'active' ? ' selected' : '' ) . '>' . _( 'Active' ) . '</option>
			<option value="Inactive">' . _( 'Inactive' ) . '</option>
			<option value="Disabled">' . _( 'Disabled' ) . '</option>
			<option value="Closed">' . _( 'Closed' ) . '</option>
			</select>
			</td></tr>';

		break;

		// Food Service Barcode Widget.
		case 'fsa_barcode':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_barcode'] ) )
			{
				if ( !mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";

					$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
				}

				$extra['WHERE'] .= " AND fssa.BARCODE='" . $_REQUEST['fsa_barcode'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Barcode' ) . ': </b>' .
						$_REQUEST['fsa_barcode'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_barcode">' . _( 'Barcode' ) .
			'</label></td><td>
			<input type="text" name="fsa_barcode" id="fsa_barcode" size="15" maxlength="50" />
			</td></tr>';

		break;

		// Food Service Account ID Widget.
		case 'fsa_account_id':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( is_numeric( $_REQUEST['fsa_account_id'] ) )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ",FOOD_SERVICE_STUDENT_ACCOUNTS fssa";

					$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
				}

				$extra['WHERE'] .= " AND fssa.ACCOUNT_ID='" . (int) $_REQUEST['fsa_account_id'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Account ID' ) . ': </b>' .
						(int) $_REQUEST['fsa_account_id'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_account_id">' . _( 'Account ID' ) . '</label></td><td>
			<input type="text" name="fsa_account_id" id="fsa_account_id" size="4" maxlength="10" />
			</td></tr>';

		break;
	}

	$_ROSARIO['Widgets'][ $item ] = true;

	return true;
}
