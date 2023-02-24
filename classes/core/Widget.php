<?php
/**
 * (Student) Widget interface and individual Widget classes
 *
 * @since 8.6
 *
 * @package RosarioSIS
 * @subpackage classes/core
 */

namespace RosarioSIS;

// Widget interface.
// Implement this interface when creating a new Widget.
interface Widget
{
	/**
	 * Check whether Widget can be built:
	 * Usually check if the corresponding Module is active
	 * Maybe check if User is admin
	 * Maybe check if AllowUse() for corresponding modname
	 *
	 * @param  array  $modules $RosarioModules global.
	 *
	 * @return bool True if can build Widget, else false.
	 */
	public function canBuild( $modules );

	/**
	 * Build extra SQL, and search terms
	 *
	 * @param  array  $extra Extra array, see definition in Widgets class.
	 *
	 * @return array         $extra array with Widget extra added.
	 */
	public function extra( $extra );

	/**
	 * Build HTML form
	 *
	 * @return string HTML form.
	 */
	public function html();
}

// Course Widget.
class Widget_course implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Scheduling']
			&& User( 'PROFILE' ) === 'admin';
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['w_course_period_id'] ) )
		{
			return $extra;
		}

		// @since 6.5 Course Widget: add Subject and Not options.
		$extra['WHERE'] .= ! empty( $_REQUEST['w_course_period_id_not'] ) ?
			" AND NOT " : " AND ";

		if ( $_REQUEST['w_course_period_id_which'] === 'subject' )
		{
			$extra['WHERE'] .= " EXISTS(SELECT 1
				FROM schedule w_ss
				WHERE w_ss.STUDENT_ID=s.STUDENT_ID
				AND w_ss.SYEAR=ssm.SYEAR
				AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID
				AND w_ss.COURSE_ID IN(SELECT COURSE_ID
					FROM courses
					WHERE SUBJECT_ID='" . (int) $_REQUEST['w_subject_id'] . "'
					AND SYEAR=ssm.SYEAR
					AND SCHOOL_ID=ssm.SCHOOL_ID)";

			$subject_title = DBGetOne( "SELECT TITLE
				FROM course_subjects
				WHERE SUBJECT_ID='" . (int) $_REQUEST['w_subject_id'] . "'" );

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Subject' ) . ': </b>'.
					( ! empty( $_REQUEST['w_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
					$subject_title . '<br />';
			}
		}
		// Course.
		elseif ( $_REQUEST['w_course_period_id_which'] === 'course' )
		{
			$extra['WHERE'] .= " EXISTS(SELECT 1
				FROM schedule w_ss
				WHERE w_ss.STUDENT_ID=s.STUDENT_ID
				AND w_ss.SYEAR=ssm.SYEAR
				AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID
				AND w_ss.COURSE_ID='" . (int) $_REQUEST['w_course_id'] . "'";

			$course_title = DBGetOne( "SELECT TITLE
				FROM courses
				WHERE COURSE_ID='" . (int) $_REQUEST['w_course_id'] . "'" );

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Course' ) . ': </b>'.
					( ! empty( $_REQUEST['w_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
					$course_title . '<br />';
			}
		}
		// Course Period.
		else
		{
			$extra['WHERE'] .= " EXISTS(SELECT 1
				FROM schedule w_ss
				WHERE w_ss.STUDENT_ID=s.STUDENT_ID
				AND w_ss.SYEAR=ssm.SYEAR
				AND w_ss.SCHOOL_ID=ssm.SCHOOL_ID
				AND w_ss.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "'";

			$course = DBGet( "SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_ID
				FROM course_periods cp,courses c
				WHERE c.COURSE_ID=cp.COURSE_ID
				AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "'" );

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Course Period' ) . ': </b>' .
					( ! empty( $_REQUEST['w_course_period_id_not'] ) ? _( 'Not' ) . ' ' : '' ) .
					$course[1]['COURSE_TITLE'] . ': ' . $course[1]['TITLE'] . '<br />';
			}
		}

		$is_include_inactive = isset( $_REQUEST['include_inactive'] ) && $_REQUEST['include_inactive'] === 'Y';

		if ( ! $is_include_inactive )
		{
			// Fix check students Course Status.
			$extra['WHERE'] .= " AND '" . DBDate() . "'>=w_ss.START_DATE
				AND ('" . DBDate() . "'<=w_ss.END_DATE OR w_ss.END_DATE IS NULL)
				AND w_ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")";
		}

		$extra['WHERE'] .= ")";

		return $extra;
	}

	function html()
	{
		if ( ! Config( 'COURSE_WIDGET_METHOD' ) )
		{
			// Course Widget: Popup window.
			$html = '<tr class="st"><td>' . _( 'Course' ) . '</td><td>
			<div id="course_div"></div>
			<a href="#" onclick=\'popups.open(
					"Modules.php?modname=misc/ChooseCourse.php"
				); return false;\'>' .
				_( 'Choose' ) .
			'</a>
			</td></tr>';

			return $html;
		}

		// @since 7.4 Add Course Widget: select / Pull-Down.
		$course_periods_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.TITLE,
		c.COURSE_ID,cs.SUBJECT_ID,cs.TITLE AS SUBJECT_TITLE
		FROM course_periods cp,courses c,course_subjects cs
		WHERE cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.COURSE_ID=c.COURSE_ID
		AND cs.SUBJECT_ID=c.SUBJECT_ID
		ORDER BY cs.SORT_ORDER IS NULL,cs.SORT_ORDER,cs.TITLE,cp.SHORT_NAME" );

		$course_period_options = [];

		$subject_group = '';

		foreach ( $course_periods_RET as $course_period )
		{
			if ( $subject_group !== $course_period['SUBJECT_TITLE'] )
			{
				$subject_group = $course_period['SUBJECT_TITLE'];

				$course_period_options[ $subject_group ] = [];
			}

			// Fix 403 Forbidden error due to pipe "|" in URL when using Apache 5G rules.
			$course_period_value = $course_period['SUBJECT_ID'] . ',' .
				$course_period['COURSE_ID'] . ',' . $course_period['COURSE_PERIOD_ID'];

			$course_period_options[ $subject_group ][ $course_period_value ] = $course_period['TITLE'];
		}

		$course_period_chosen_select = SelectInput(
			'',
			'course_period_select',
			'',
			$course_period_options,
			'N/A',
			'group onchange="wCourseIdUpdate(this.value);" autocomplete="off"'
		);

		$html = '<tr class="st"><td>' . _( 'Course' ) . '</td><td>' .
		$course_period_chosen_select .
		'<div id="course_div" class="hide">
		<label><input type="checkbox" name="w_course_period_id_not" value="Y"> ' .
			_( 'Not' ) . '</label>
		<label><select name="w_course_period_id_which" autocomplete="off">
		<option value="course_period"> ' . _( 'Course Period' ) . '</option>
		<option value="course"> ' . _( 'Course' ) . '</option>
		<option value="subject"> ' . _( 'Subject' ) . '</option>
		</select></label>
		<input type="hidden" name="w_course_period_id" value="">
		<input type="hidden" name="w_course_id" value="">
		<input type="hidden" name="w_subject_id" value="">
		</div></td></tr>';

		return $html . '<script>var wCourseIdUpdate = function( val ) {
			if ( ! val ) {
				$("[name=w_course_period_id]").val( "" );
				$("[name=w_course_id]").val( "" );
				$("[name=w_subject_id]").val( "" );
				$("[name=course_div").hide();
				return;
			}
			$("#course_div").show();
			var values = val.split(",");
			$("[name=w_course_period_id]").val( values[2] );
			$("[name=w_course_id]").val( values[1] );
			$("[name=w_subject_id]").val( values[0] );
		};</script>';
	}
}


// Request Widget.
class Widget_request implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Scheduling']
			&& User( 'PROFILE' ) === 'admin';
	}

	function extra( $extra )
	{
		// PART OF THIS IS DUPLICATED IN PrintRequests.php.
		if ( empty( $_REQUEST['request_course_id'] ) )
		{
			return $extra;
		}

		$course_title = DBGetOne( "SELECT c.TITLE
			FROM courses c
			WHERE c.COURSE_ID='" . (int) $_REQUEST['request_course_id'] . "'" );

		// Request.
		if ( ! isset( $_REQUEST['missing_request_course'] )
			|| ! $_REQUEST['missing_request_course'] )
		{
			$extra['FROM'] .= ",schedule_requests sr";

			$extra['WHERE'] .= " AND sr.STUDENT_ID=s.STUDENT_ID
				AND sr.SYEAR=ssm.SYEAR
				AND sr.SCHOOL_ID=ssm.SCHOOL_ID
				AND sr.COURSE_ID='" . (int) $_REQUEST['request_course_id'] . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Request' ) . ': </b>' .
					$course_title . '<br />';
			}
		}
		// Missing Request.
		else
		{
			$extra['WHERE'] .= " AND NOT EXISTS
				(SELECT '' FROM
					schedule_requests sr
					WHERE sr.STUDENT_ID=ssm.STUDENT_ID
					AND sr.SYEAR=ssm.SYEAR
					AND sr.COURSE_ID='" . (int) $_REQUEST['request_course_id'] . "' ) ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Missing Request' ) . ': </b>' .
					$course_title . '<br />';
			}
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>'. _( 'Request' ) . '</td><td>
		<div id="request_div"></div>
		<a href="#" onclick=\'popups.open(
				"Modules.php?modname=misc/ChooseRequest.php"
			); return false;\'>' .
			_( 'Choose' ) .
		'</a>
		</td></tr>';
	}
}

// Days Absent Widget.
class Widget_absences implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Attendance'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['absences_low'] )
			|| ! is_numeric( $_REQUEST['absences_low'] )
			|| ! isset( $_REQUEST['absences_high'] )
			|| ! is_numeric( $_REQUEST['absences_high'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['absences_low'] > $_REQUEST['absences_high'] )
		{
			$temp = $_REQUEST['absences_high'];

			$_REQUEST['absences_high'] = $_REQUEST['absences_low'];

			$_REQUEST['absences_low'] = $temp;
		}

		// Set Absences number SQL condition.
		$absences_sql = $_REQUEST['absences_low'] == $_REQUEST['absences_high'] ?
			" = '" . $_REQUEST['absences_low'] . "'" :
			" BETWEEN '" . $_REQUEST['absences_low'] . "'
				AND '" . $_REQUEST['absences_high'] . "'";

		$extra['WHERE'] .= " AND (SELECT sum(1-STATE_VALUE) AS STATE_VALUE
			FROM attendance_day ad
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
			$extra['SearchTerms'] .= '<b>' . _( 'Days Absent' ) . ' ' . $term . ': </b>' .
				_( 'Between' ) . ' ' .
				$_REQUEST['absences_low'] . ' &amp; ' . $_REQUEST['absences_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' .	_( 'Days Absent' ) .
		'<br />
		<label title="' . AttrEscape( _( 'this school year to date' ) ) . '">
			<input type="radio" name="absences_term" value="FY" checked>&nbsp;' .
			_( 'YTD' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this semester to date' ) ) . '">
			<input type="radio" name="absences_term" value="SEM">&nbsp;' .
			GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this marking period to date' ) ) . '">
			<input type="radio" name="absences_term" value="QTR">&nbsp;' .
			GetMP( UserMP(), 'SHORT_NAME' ) .
		'</label>
		</td><td><label>' . _( 'Between' ) .
		' <input type="text" name="absences_low" size="3" maxlength="3"></label>' .
		' <label>&amp; ' .
		'<input type="text" name="absences_high" size="3" maxlength="3"><label>
		</td></tr>';
	}
}


// Course Period Absences Widget
// for admins only (relies on the Course widget).
class Widget_cp_absences implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Attendance']
			&& User( 'PROFILE' ) === 'admin';
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['cp_absences_low'] )
			|| ! is_numeric( $_REQUEST['cp_absences_low'] )
			|| ! isset( $_REQUEST['cp_absences_high'] )
			|| ! is_numeric( $_REQUEST['cp_absences_high'] )
			|| ! isset( $_REQUEST['w_course_period_id'] )
			|| ! is_numeric( $_REQUEST['w_course_period_id'] ) )
		{
			return $extra;
		}

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
			$term_sql = " AND ap.MARKING_PERIOD_ID
				IN(" . GetChildrenMP( $_REQUEST['cp_absences_term'], UserMP() ) . ")";
		}

		// Set Absences number SQL condition.
		$absences_sql = $_REQUEST['cp_absences_low'] == $_REQUEST['cp_absences_high'] ?
			" = '" . $_REQUEST['cp_absences_low'] . "'" :
			" BETWEEN '" . $_REQUEST['cp_absences_low'] . "'
				AND '" . $_REQUEST['cp_absences_high'] . "'";

		$extra['WHERE'] .= " AND (SELECT count(*)
			FROM attendance_period ap,attendance_codes ac
			WHERE ac.ID=ap.ATTENDANCE_CODE
			AND ac.STATE_CODE='A'
			AND ap.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "'" .
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
			$extra['SearchTerms'] .= '<b>' . _( 'Course Period Absences' ) . ' ' .
				$term . ': </b>' . _( 'Between' ) . ' ' .
				$_REQUEST['cp_absences_low'] . ' &amp; ' .
				$_REQUEST['cp_absences_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' .	_( 'Course Period Absences' ) .
		'<div class="tooltip"><i>' .
			_( 'Use the Choose link of the Course widget (under Scheduling) to select a Course Period.' ) .
		'</i></div>' .
		'<br />
		<label title="' . AttrEscape( _( 'this school year to date' ) ) . '">
			<input type="radio" name="cp_absences_term" value="FY" checked>&nbsp;' .
			_( 'YTD' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this semester to date' ) ) . '">
			<input type="radio" name="cp_absences_term" value="SEM">&nbsp;' .
			GetMP( GetParentMP( 'SEM', UserMP() ), 'SHORT_NAME' ) .
		'</label> &nbsp;
		<label title="' . AttrEscape( _( 'this marking period to date' ) ) . '">
			<input type="radio" name="cp_absences_term" value="QTR">&nbsp;' .
			GetMP( UserMP(), 'SHORT_NAME' ) .
		'</label>
		</td><td><label>' . _( 'Between' ) .
		' <input type="text" name="cp_absences_low" size="3" maxlength="3"></label>' .
		' <label>&amp;' .
		' <input type="text" name="cp_absences_high" size="3" maxlength="3"></label>
		</td></tr>';
	}
}


// GPA Widget
class Widget_gpa implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Grades'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['gpa_low'] )
			|| ! is_numeric( $_REQUEST['gpa_low'] )
			|| ! isset( $_REQUEST['gpa_high'] )
			|| ! is_numeric( $_REQUEST['gpa_high'] ) )
		{
			return $extra;
		}

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

		if ( mb_strpos( $extra['FROM'], 'student_mp_stats sms' ) === false )
		{
			$extra['FROM'] .= ",student_mp_stats sms";

			$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID
				AND sms.MARKING_PERIOD_ID='" . (int) $_REQUEST['gpa_term'] . "'";
		}

		$extra['WHERE'] .= " AND sms.CUM_" .
			( ( isset( $_REQUEST['gpa_weighted'] ) && $_REQUEST['gpa_weighted'] === 'Y' ) ? '' : 'UN' ) .
			"WEIGHTED_FACTOR *" . SchoolInfo( 'REPORTING_GP_SCALE' ) . "
			BETWEEN '" . $_REQUEST['gpa_low'] . "' AND '" . $_REQUEST['gpa_high'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . GetMP( $_REQUEST['gpa_term'], 'TITLE' ) . ' &mdash; ' .
				( ( isset( $_REQUEST['gpa_weighted'] ) && $_REQUEST['gpa_weighted'] === 'Y' ) ?
					_( 'Weighted GPA' ) :
					_( 'Unweighted GPA' ) ) .
				' ' . _( 'Between' ) . ': </b>' .
				$_REQUEST['gpa_low'] . ' &amp; ' . $_REQUEST['gpa_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		$html = '<tr class="st"><td>' . _( 'GPA' ) . '<br />
		<label>
			<input type="checkbox" name="gpa_weighted" value="Y" checked>&nbsp;' . _( 'Weighted' ) .
		'</label>
		<br>';

		if ( GetMP( $MPfy = GetParentMP( 'FY', GetParentMP( 'SEM', UserMP() ) ), 'DOES_GRADES') == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPfy, 'TITLE' ) ) . '">
					<input type="radio" name="gpa_term" value="' . AttrEscape( $MPfy ) . '" checked>&nbsp;' .
					GetMP( $MPfy, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPsem = GetParentMP( 'SEM', UserMP() ), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPsem, 'TITLE' ) ) . '">
					<input type="radio" name="gpa_term" value="' . AttrEscape( $MPsem ) . '">&nbsp;' .
					GetMP( $MPsem, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPtrim = UserMP(), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPtrim, 'TITLE' ) ) . '">
					<input type="radio" name="gpa_term" value="' . AttrEscape( $MPtrim ) . '" checked>&nbsp;' .
					GetMP( $MPtrim, 'SHORT_NAME' ) .
				'</label>';
		}

		return $html . '</td><td><label>' . _( 'Between' ) .
		' <input type="number" name="gpa_low" min="0" max="99999" step="0.01"></label>' .
		' <label>&amp;' .
		' <input type="number" name="gpa_high" min="0" max="99999" step="0.01"></label>
		</td></tr>';
	}
}


// Class Rank Widget
class Widget_class_rank implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Grades'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['class_rank_low'] )
			|| ! is_numeric( $_REQUEST['class_rank_low'] )
			|| ! isset( $_REQUEST['class_rank_high'] )
			|| ! is_numeric( $_REQUEST['class_rank_high'] ) )
		{
			return $extra;
		}

		$_REQUEST['class_rank_low'] = (int) $_REQUEST['class_rank_low'];
		$_REQUEST['class_rank_high'] = (int) $_REQUEST['class_rank_high'];

		if ( $_REQUEST['class_rank_low'] > $_REQUEST['class_rank_high'] )
		{
			$temp = $_REQUEST['class_rank_high'];
			$_REQUEST['class_rank_high'] = $_REQUEST['class_rank_low'];
			$_REQUEST['class_rank_low'] = $temp;
		}

		if ( mb_strpos( $extra['FROM'], 'student_mp_stats sms' ) === false )
		{
			$extra['FROM'] .= ",student_mp_stats sms";

			$extra['WHERE'] .= " AND sms.STUDENT_ID=s.STUDENT_ID
				AND sms.MARKING_PERIOD_ID='" . (int) $_REQUEST['class_rank_term'] . "'";
		}

		$extra['WHERE'] .= " AND sms.CUM_RANK BETWEEN
			'" . $_REQUEST['class_rank_low'] . "'
			AND '" . $_REQUEST['class_rank_high'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . GetMP( $_REQUEST['class_rank_term'], 'TITLE' ) . ' &mdash; ' .
				_( 'Class Rank' ) . ' ' . _( 'Between' ) . ': </b>' .
				$_REQUEST['class_rank_low'] . ' &amp; ' . $_REQUEST['class_rank_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		$html = '<tr class="st"><td>' . _( 'Class Rank' ) . '<br />';

		if ( GetMP( $MPfy = GetParentMP( 'FY', GetParentMP( 'SEM', UserMP() ) ), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPfy, 'TITLE' ) ) . '">
					<input type="radio" name="class_rank_term" value="' . AttrEscape( $MPfy ) . '">&nbsp;' .
					GetMP( $MPfy, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPsem = GetParentMP( 'SEM', UserMP() ), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPsem, 'TITLE' ) ) . '">
					<input type="radio" name="class_rank_term" value="' . AttrEscape( $MPsem ) . '">&nbsp;' .
					GetMP( $MPsem, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPtrim = UserMP(), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPtrim, 'TITLE' ) ) . '">
					<input type="radio" name="class_rank_term" value="' . AttrEscape( $MPtrim ) . '" checked>&nbsp;' .
					GetMP( $MPtrim, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( mb_strlen( $pros = GetChildrenMP( 'PRO', UserMP() ) ) )
		{
			$pros = explode( ',', str_replace( "'", '', $pros ) );

			foreach ( $pros as $pro )
			{
				if ( GetMP( $pro, 'DOES_GRADES' ) !== 'Y' )
				{
					continue;
				}

				$html .= '<label title="' . AttrEscape( GetMP( $pro, 'TITLE' ) ) . '">
						<input type="radio" name="class_rank_term" value="' . AttrEscape( $pro ) . '">&nbsp;' .
						GetMP( $pro, 'SHORT_NAME' ) .
					'</label> &nbsp; ';
			}
		}

		return $html . '</td><td><label>' . _( 'Between' ) .
		' <input type="number" name="class_rank_low" min="0" max="99999" step="1"></label>' .
		' <label>&amp;' .
		' <input type="number" name="class_rank_high" min="0" max="99999" step="1"></label>
		</td></tr>';
	}
}


// Report Card Grade Widget
class Widget_letter_grade implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Grades'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['letter_grade'] ) )
		{
			return $extra;
		}

		$letter_grades = '';

		foreach ( (array) $_REQUEST['letter_grade'] as $grades )
		{
			foreach ( $grades as $grade )
			{
				if ( ! $grade )
				{
					continue;
				}

				$letter_grades .= ",'" . $grade . "'";
			}
		}

		if ( empty( $letter_grades ) )
		{
			return $extra;
		}

		$LetterGradeSearchTerms = '<b>' . GetMP( $_REQUEST['letter_grade_term'], 'TITLE' ) . ' &mdash; ' .
			( isset( $_REQUEST['letter_grade_exclude'] )
			&& $_REQUEST['letter_grade_exclude'] == 'Y' ?
				_( 'Without' ) :
				_( 'With' ) ) .
			' ' . _( 'Report Card Grade' ) . ': </b>';

		$letter_grade_titles = DBGetOne( "SELECT " . DBSQLCommaSeparatedResult( 'TITLE', ', ' ) . "
			FROM report_card_grades
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND ID IN(" . mb_substr( $letter_grades, 1 ) . ")" );

		$LetterGradeSearchTerms .= $letter_grade_titles . '<br />';

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= $LetterGradeSearchTerms;
		}

		$extra['WHERE'] .= " AND " . ( isset( $_REQUEST['letter_grade_exclude'] )
			&& $_REQUEST['letter_grade_exclude'] == 'Y' ? 'NOT ' : '' ) . "EXISTS
			(SELECT ''
				FROM student_report_card_grades sg3
				WHERE sg3.STUDENT_ID=ssm.STUDENT_ID
				AND sg3.SYEAR=ssm.SYEAR
				AND sg3.REPORT_CARD_GRADE_ID IN (" . mb_substr( $letter_grades, 1 ) . ")
				AND sg3.MARKING_PERIOD_ID='" . (int) $_REQUEST['letter_grade_term'] . "' )";

		return $extra;
	}

	function html()
	{
		global $_ROSARIO;

		$html = '<tr class="st"><td>' . _( 'Grade' ) . '<br />
		<label>
			<input type="checkbox" name="letter_grade_exclude" value="Y">&nbsp;' . _( 'Did not receive' ) .
		'</label>
		<br>';

		if ( GetMP( $MPfy = GetParentMP( 'FY', GetParentMP( 'SEM', UserMP() ) ), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPfy, 'TITLE' ) ) . '">
					<input type="radio" name="letter_grade_term" value="' . AttrEscape( $MPfy ) . '">&nbsp;' .
					GetMP( $MPfy, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPsem = GetParentMP( 'SEM', UserMP() ), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPsem, 'TITLE' ) ) . '">
					<input type="radio" name="letter_grade_term" value="' . AttrEscape( $MPsem ) . '">&nbsp;' .
					GetMP( $MPsem, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( GetMP( $MPtrim = UserMP(), 'DOES_GRADES' ) == 'Y' )
		{
			$html .= '<label title="' . AttrEscape( GetMP( $MPtrim, 'TITLE' ) ) . '">
					<input type="radio" name="letter_grade_term" value="' . AttrEscape( $MPtrim ) . '" checked>&nbsp;' .
					GetMP( $MPtrim, 'SHORT_NAME' ) .
				'</label> &nbsp; ';
		}

		if ( mb_strlen( $pros = GetChildrenMP( 'PRO', UserMP() ) ) )
		{
			$pros = explode( ',', str_replace( "'", '', $pros ) );

			foreach ( $pros as $pro )
			{
				if ( GetMP( $pro, 'DOES_GRADES' ) !== 'Y' )
				{
					continue;
				}

				$html .= '<label title="' . AttrEscape( GetMP( $pro, 'TITLE' ) ) . '">
						<input type="radio" name="letter_grade_term" value="' . AttrEscape( $pro ) . '">&nbsp;' .
						GetMP( $pro, 'SHORT_NAME' ) .
					'</label> &nbsp; ';
			}
		}

		$html .= '</td><td>';

		// FJ fix error Invalid argument supplied for foreach().
		if ( empty( $_REQUEST['search_modfunc'] ) )
		{
			$letter_grades_RET = DBGet( "SELECT rg.ID,rg.TITLE,rg.GRADE_SCALE_ID,rs.TITLE AS SCALE_TITLE
				FROM report_card_grades rg,report_card_grade_scales rs
				WHERE rg.SCHOOL_ID='" . UserSchool() . "'
				AND rg.SYEAR='" . UserSyear() . "'
				AND rs.ID=rg.GRADE_SCALE_ID" .
				( User( 'PROFILE' ) === 'teacher' ?
				" AND rg.GRADE_SCALE_ID=
					(SELECT GRADE_SCALE_ID
						FROM course_periods
						WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')" :
				'' ) .
				" ORDER BY rs.SORT_ORDER IS NULL,rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER IS NULL,rg.SORT_ORDER",
				[], [ 'GRADE_SCALE_ID' ] );

			$j = 0;

			foreach ( (array) $letter_grades_RET as $grades )
			{
				if ( $j++ > 0 )
				{
					$html .= '<br />';
				}

				$grades_options = [];

				foreach ( (array) $grades as $grade )
				{
					$grades_options[ $grade['ID'] ] = $grade['TITLE'];
				}

				if ( ! AllowEdit() )
				{
					// @since 10.6.1 Fix Grades input not displaying for Teachers.
					$_ROSARIO['allow_edit'] = true;

					$allow_edit_tmp = true;
				}

				// @since 9.0 Use multiple select input for grades list to gain space.
				$html .= '<div style="max-width: 240px">' . Select2Input(
					'',
					'letter_grade[' . $grade['GRADE_SCALE_ID'] . '][]',
					$grade['SCALE_TITLE'],
					$grades_options,
					false,
					'multiple'
				) . '</div>';

				if ( ! empty( $allow_edit_tmp ) )
				{
					$_ROSARIO['allow_edit'] = false;
				}
			}
		}

		return $html . '</td></tr>';
	}
}


// Eligibility (Ineligible) Widget
class Widget_eligibility implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Eligibility'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['ineligible'] )
			|| $_REQUEST['ineligible'] !== 'Y' )
		{
			return $extra;
		}

		// Day of the week: 1 (for Monday) through 7 (for Sunday).
		$today = date( 'w' ) ? date( 'w' ) : 7;

		$start_date = date(
			'Y-m-d',
			time() - ( $today - ProgramConfig( 'eligibility', 'START_DAY' ) ) * 60 * 60 * 24
		);

		$end_date = DBDate();

		$extra['WHERE'] .= " AND (SELECT count(*)
			FROM eligibility e
			WHERE ssm.STUDENT_ID=e.STUDENT_ID
			AND e.SYEAR=ssm.SYEAR
			AND e.SCHOOL_DATE BETWEEN '" . $start_date . "'
			AND '" . $end_date . "'
			AND e.ELIGIBILITY_CODE='FAILING') > '0'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Eligibility' ) . ': </b>' .
				_( 'Ineligible' ) . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>
		</td><td>
		<label>
			<input type="checkbox" name="ineligible" value="Y">&nbsp;' . _( 'Ineligible' ) .
		'</label>
		</td></tr>';
	}
}


// Activity (Eligibility) Widget
class Widget_activity implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Eligibility'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['activity_id'] ) )
		{
			return $extra;
		}

		$extra['FROM'] .= ",student_eligibility_activities sea";

		$extra['WHERE'] .= " AND sea.STUDENT_ID=s.STUDENT_ID
			AND sea.SYEAR=ssm.SYEAR
			AND sea.ACTIVITY_ID='" . (int) $_REQUEST['activity_id'] . "'";

		$activity_title = DBGetOne( "SELECT TITLE
			FROM eligibility_activities
			WHERE ID='" . (int) $_REQUEST['activity_id'] . "'" );

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Activity' ) . ': </b>' .
				$activity_title . '<br />';
		}

		return $extra;
	}

	function html()
	{
		$activities_RET = [];

		if ( empty( $_REQUEST['search_modfunc'] ) )
		{
			$activities_RET = DBGet( "SELECT ID,TITLE
				FROM eligibility_activities
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );
		}

		$select = '<select name="activity_id" id="activity_id">
			<option value="">' . _( 'Not Specified' ) . '</option>';

		foreach ( (array) $activities_RET as $activity )
		{
			$select .= '<option value="' . AttrEscape( $activity['ID'] ) . '">' . $activity['TITLE'] . '</option>';
		}

		$select .= '</select>';

		return '<tr class="st"><td><label for="activity_id">' .
		_( 'Activity' ) .
		'</label></td><td>' .
		$select .
		'</td></tr>';
	}
}


// Mailing Labels Widget
class Widget_mailing_labels implements Widget
{
	function canBuild( $modules )
	{
		return true;
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['mailing_labels'] )
			|| $_REQUEST['mailing_labels'] !== 'Y' )
		{
			return $extra;
		}

		require_once 'ProgramFunctions/MailingLabel.fnc.php';

		$extra['SELECT'] .= ',coalesce(saml.ADDRESS_ID,-ssm.STUDENT_ID) AS ADDRESS_ID,
			saml.ADDRESS_ID AS MAILING_LABEL';

		$extra['FROM'] = " LEFT OUTER JOIN students_join_address saml
			ON (saml.STUDENT_ID=ssm.STUDENT_ID
				AND saml.MAILING='Y'" .
				( isset( $_REQUEST['residence'] ) && $_REQUEST['residence'] == 'Y' ? " AND saml.RESIDENCE='Y'" : '' ) . ")" .
			$extra['FROM'];

		$extra['functions'] += [ 'MAILING_LABEL' => 'MailingLabel' ];

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' .
			'<label for="mailing_labels">' . _( 'Mailing Labels' ) . '</label>' .
			'</td><td>' .
			'<input type="checkbox" id="mailing_labels" name="mailing_labels" value="Y">' .
			'</td>';
	}
}


// Student Billing Balance Widget
class Widget_balance implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Student_Billing']
			&& AllowUse( 'Student_Billing/StudentFees.php' );
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['balance_low'] )
			|| ! is_numeric( $_REQUEST['balance_low'] )
			|| ! isset( $_REQUEST['balance_high'] )
			|| ! is_numeric( $_REQUEST['balance_high'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'] )
		{
			$temp = $_REQUEST['balance_high'];
			$_REQUEST['balance_high'] = $_REQUEST['balance_low'];
			$_REQUEST['balance_low'] = $temp;
		}

		$extra['WHERE'] .= " AND (
			coalesce((SELECT sum(p.AMOUNT)
				FROM billing_payments p
				WHERE p.STUDENT_ID=ssm.STUDENT_ID
				AND p.SYEAR=ssm.SYEAR),0) -
			coalesce((SELECT sum(f.AMOUNT)
				FROM billing_fees f
				WHERE f.STUDENT_ID=ssm.STUDENT_ID
				AND f.SYEAR=ssm.SYEAR),0))
			BETWEEN '" . $_REQUEST['balance_low'] . "'
			AND '" . $_REQUEST['balance_high'] . "' ";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Student Billing Balance' ) . ' ' . _( 'Between' ) .': </b>' .
				$_REQUEST['balance_low'] . ' &amp; ' .
				$_REQUEST['balance_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' . _( 'Balance' ) . '</td><td><label>' . _( 'Between' ) .
		' <input type="number" name="balance_low" step="0.01" min="-999999999999999" max="999999999999999"></label>' .
		' <label>&amp;' .
		' <input type="number" name="balance_high" step="0.01" min="-999999999999999" max="999999999999999"></label>
		</td></tr>';
	}
}


// Discipline Reporter Widget
class Widget_reporter implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Discipline'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['discipline_reporter'] ) )
		{
			return $extra;
		}

		if ( mb_stripos( $extra['FROM'], 'discipline_referrals dr' ) === false )
		{
			// Fix SQL error invalid reference to FROM-clause entry for table "ssm"
			// Add JOIN just after JOIN student_enrollment ssm
			$extra['FROM'] = ' LEFT JOIN discipline_referrals dr
				ON (dr.STUDENT_ID=ssm.STUDENT_ID
				AND dr.SYEAR=ssm.SYEAR
				AND dr.SCHOOL_ID=ssm.SCHOOL_ID) ' . $extra['FROM'];
		}

		$extra['WHERE'] .= " AND dr.STAFF_ID='" . (int) $_REQUEST['discipline_reporter'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$reporter_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
				FROM staff
				WHERE SYEAR='" . UserSyear() . "'
				AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
				AND (PROFILE='admin' OR PROFILE='teacher')
				AND STAFF_ID='" . (int) $_REQUEST['discipline_reporter'] . "'" );

			$extra['SearchTerms'] .= '<b>' . _( 'Reporter' ) . ': </b>' .
				$reporter_name . '<br />';
		}

		return $extra;
	}

	function html()
	{
		$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
			FROM staff
			WHERE SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
			AND (PROFILE='admin' OR PROFILE='teacher')
			ORDER BY FULL_NAME", [], [ 'STAFF_ID' ] );

		$html = '<tr class="st"><td><label for="discipline_reporter">' .
		_( 'Reporter' ) . '</label></td><td>
		<select name="discipline_reporter" id="discipline_reporter">
			<option value="">' . _( 'Not Specified' ) . '</option>';

		foreach ( (array) $users_RET as $id => $user )
		{
			$html .= '<option value="' . AttrEscape( $id ) . '">' .
					$user[1]['FULL_NAME'] .
				'</option>';
		}

		return $html . '</select></td></tr>';
	}
}


// Discipline Incident Date Widget
class Widget_incident_date implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Discipline'];
	}

	function extra( $extra )
	{
		$discipline_entry_begin = RequestedDate(
			'discipline_entry_begin',
			( issetVal( $_REQUEST['discipline_entry_begin'], '' ) )
		);

		$discipline_entry_end = RequestedDate(
			'discipline_entry_end',
			( issetVal( $_REQUEST['discipline_entry_end'], '' ) )
		);

		if ( ! $discipline_entry_begin
			&& ! $discipline_entry_end )
		{
			return $extra;
		}

		if ( $discipline_entry_end
			&& $discipline_entry_begin > $discipline_entry_end )
		{
			// Begin date > end date, switch.
			$discipline_entry_begin_tmp = $discipline_entry_begin;

			$discipline_entry_begin = $discipline_entry_end;
			$discipline_entry_end = $discipline_entry_begin_tmp;
		}

		if ( $discipline_entry_begin
			|| $discipline_entry_end )
		{
			if ( mb_stripos( $extra['FROM'], 'discipline_referrals dr' ) === false )
			{
				// Fix SQL error invalid reference to FROM-clause entry for table "ssm"
				// Add JOIN just after JOIN student_enrollment ssm
				$extra['FROM'] = ' LEFT JOIN discipline_referrals dr
					ON (dr.STUDENT_ID=ssm.STUDENT_ID
					AND dr.SYEAR=ssm.SYEAR
					AND dr.SCHOOL_ID=ssm.SCHOOL_ID) ' . $extra['FROM'];
			}
		}

		if ( $discipline_entry_begin
			&& $discipline_entry_end )
		{
			$extra['WHERE'] .= " AND dr.ENTRY_DATE
				BETWEEN '" . $discipline_entry_begin .
				"' AND '" . $discipline_entry_end . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'Between' ) . ': </b>' .
					ProperDate( $discipline_entry_begin ) . ' &amp; ' .
					ProperDate( $discipline_entry_end ) . '<br />';
			}
		}
		elseif ( $discipline_entry_begin )
		{
			$extra['WHERE'] .= " AND dr.ENTRY_DATE>='" . $discipline_entry_begin . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'On or After' ) . ' </b>' .
					ProperDate( $discipline_entry_begin ) . '<br />';
			}
		}
		elseif ( $discipline_entry_end )
		{
			$extra['WHERE'] .= " AND dr.ENTRY_DATE<='" . $discipline_entry_end . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Incident Date' ) . ' ' . _( 'On or Before' ) . ' </b>' .
					ProperDate( $discipline_entry_end ) . '<br />';
			}
		}

		return $extra;
	}

	function html()
	{
		$discipline_entry_begin_default = '';

		if ( $_REQUEST['modname'] === 'Discipline/Referrals.php' )
		{
			// Set default Incident Date for Referrals program only.
			$discipline_entry_begin_default = date( 'Y-m' ) . '-01';
		}

		return '<tr class="st"><td>' . _( 'Incident Date' ) . '</td><td>
		<table class="cellspacing-0"><tr><td>
		<span class="sizep2">&ge;</span>&nbsp;
		</td><td>' .
		PrepareDate( $discipline_entry_begin_default, '_discipline_entry_begin', true, [ 'short' => true ] ) .
		'</td></tr><tr><td>
		<span class="sizep2">&le;</span>&nbsp;
		</td><td>' .
		PrepareDate( '', '_discipline_entry_end', true, [ 'short' => true ] ) .
		'</td></tr></table>
		</td></tr>';
	}
}


// Discipline Fields Widget
class Widget_discipline_fields implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Discipline'];
	}

	function extra( $extra )
	{
		if ( isset( $_REQUEST['discipline'] )
			&& is_array( $_REQUEST['discipline'] ) )
		{
			// Modify loop: use for instead of foreach.
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
			$key = array_keys( $_REQUEST['discipline_begin'] );
			$size = count( $key );

			for ( $i = 0; $i < $size; $i++ )
			{
				if ( ! $_REQUEST['discipline_begin'][ $key[ $i ] ]
					|| ! is_numeric( $_REQUEST['discipline_begin'][ $key[ $i ] ] ) )
				{
					unset( $_REQUEST['discipline_begin'][ $key[ $i ] ] );
				}
			}
		}

		if ( isset( $_REQUEST['discipline_end'] )
			&& is_array( $_REQUEST['discipline_end'] ) )
		{
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
		}

		if ( empty( $_REQUEST['discipline'] )
			&& empty( $_REQUEST['discipline_begin'] )
			&& empty( $_REQUEST['discipline_end'] ) )
		{
			return $extra;
		}

		if ( mb_stripos( $extra['FROM'], 'discipline_referrals dr' ) === false )
		{
			// Fix SQL error invalid reference to FROM-clause entry for table "ssm"
			// Add JOIN just after JOIN student_enrollment ssm
			$extra['FROM'] = ' LEFT JOIN discipline_referrals dr
				ON (dr.STUDENT_ID=ssm.STUDENT_ID
				AND dr.SYEAR=ssm.SYEAR
				AND dr.SCHOOL_ID=ssm.SCHOOL_ID) ' . $extra['FROM'];
		}

		$extra = $this->_discipline_fields_search( $extra );

		return $extra;
	}

	private function _discipline_fields_search( $extra )
	{
		$categories_RET = DBGet( "SELECT f.ID,u.TITLE,f.DATA_TYPE,u.SELECT_OPTIONS
			FROM discipline_fields f,discipline_field_usage u
			WHERE u.DISCIPLINE_FIELD_ID=f.ID
			AND u.SYEAR='" . UserSyear() . "'
			AND u.SCHOOL_ID='" . UserSchool() . "'
			AND f.DATA_TYPE!='textarea'
			AND f.DATA_TYPE!='date'" );

		foreach ( (array) $categories_RET as $category )
		{
			switch ( $category['DATA_TYPE'] )
			{
				case 'text':

					if ( ! empty( $_REQUEST['discipline'][ $category['ID'] ] ) )
					{
						$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
							" LIKE '" . $_REQUEST['discipline'][ $category['ID'] ] . "%' ";

						if ( ! $extra['NoSearchTerms'] )
						{
							$extra['SearchTerms'] .= '<b>' . $category['TITLE'] . ': </b> ' .
								$_REQUEST['discipline'][ $category['ID'] ] . '<br />';
						}
					}

				break;

				case 'checkbox':

					if ( ! empty( $_REQUEST['discipline'][ $category['ID'] ] ) )
					{
						$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] . "='Y' ";

						if ( ! $extra['NoSearchTerms'] )
						{
							$extra['SearchTerms'] .= '<b>' . $category['TITLE'] . '</b><br />';
						}
					}

				break;

				case 'numeric':

					if ( ! empty( $_REQUEST['discipline_begin'][ $category['ID'] ] )
						&& ! empty( $_REQUEST['discipline_end'][ $category['ID'] ] ) )
					{
						$discipline_begin = $_REQUEST['discipline_begin'][ $category['ID'] ];
						$discipline_end = $_REQUEST['discipline_end'][ $category['ID'] ];

						if ( $discipline_begin > $discipline_end )
						{
							// Numeric Discipline field: invert values so BETWEEN works.
							$discipline_begin = $_REQUEST['discipline_end'][ $category['ID'] ];

							$discipline_end = $_REQUEST['discipline_begin'][ $category['ID'] ];
						}

						$extra['WHERE'] .= " AND dr.CATEGORY_" . $category['ID'] .
							" BETWEEN '" . $discipline_begin .
							"' AND '" . $discipline_end . "' ";

						if ( ! $extra['NoSearchTerms'] )
						{
							$extra['SearchTerms'] .= '<b>' . $category['TITLE'] . ' ' . _( 'Between' ) . ': </b>' .
								$discipline_begin . ' &amp; ' .
								$discipline_end . '<br />';
						}
					}

				break;

				case 'multiple_checkbox':
				case 'multiple_radio':
				case 'select':

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
							$extra['SearchTerms'] .= '<b>' . $category['TITLE'] . ': </b>' .
								$_REQUEST['discipline'][ $category['ID'] ] . '<br />';
						}
					}

				break;
			}
		}

		return $extra;
	}

	function html()
	{
		$categories_RET = DBGet( "SELECT f.ID,u.TITLE,f.DATA_TYPE,u.SELECT_OPTIONS
			FROM discipline_fields f,discipline_field_usage u
			WHERE u.DISCIPLINE_FIELD_ID=f.ID
			AND u.SYEAR='" . UserSyear() . "'
			AND u.SCHOOL_ID='" . UserSchool() . "'
			AND f.DATA_TYPE!='textarea'
			AND f.DATA_TYPE!='date'" );

		$html = '';

		foreach ( (array) $categories_RET as $category )
		{
			$input_name = 'discipline[' . $category['ID'] . ']';

			if ( $category['DATA_TYPE'] !== 'numeric' )
			{
				$input_id = GetInputID( $input_name );

				$html .= '<tr class="st"><td><label for="' . $input_id . '">' .
					$category['TITLE'] . '</label></td><td>';
			}
			else
			{
				$html .= '<tr class="st"><td>' . $category['TITLE'] . '</td><td>';
			}

			switch ( $category['DATA_TYPE'] )
			{
				case 'text':

					$html .= '<input type="text" name="' . AttrEscape( $input_name ) .
						'" id="' . $input_id . '" size="24" maxlength="255">';

				break;

				case 'checkbox':

					$html .= '<input type="checkbox" name="' . AttrEscape( $input_name ) .
						'" id="' . $input_id . '" value="Y">';

				break;

				case 'numeric':

					$html .= '<label>' . _( 'Between' ) .
						' <input type="number" name="discipline_begin[' . $category['ID'] .
							']" min="-999999999999999999" max="999999999999999999"></label>' .
						' <label>&amp;' .
						' <input type="number" name="discipline_end[' . $category['ID'] .
							']" min="-999999999999999999" max="999999999999999999"></label>';

				break;

				case 'multiple_checkbox':
				case 'multiple_radio':
				case 'select':

					$category['SELECT_OPTIONS'] = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $category['SELECT_OPTIONS'] ) );

					$html .= '<select name="' . AttrEscape( $input_name ) . '" id="' . $input_id . '">
						<option value="">' . _( 'N/A' ) . '</option>';

					foreach ( (array) $category['SELECT_OPTIONS'] as $option )
					{
						$html .= '<option value="' . AttrEscape( $option ) . '">' . $option . '</option>';
					}

					$html .= '</select>';

				break;
			}

			$html .= '</td></tr>';
		}

		return $html;
	}
}


// Next Year (Enrollment) Widget
class Widget_next_year implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Students'];
	}

	function extra( $extra )
	{
		$schools_RET = DBGet( "SELECT ID,TITLE
			FROM schools
			WHERE ID!='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		$next_year_options = [
			'' => _( 'N/A' ),
			'!' => _( 'No Value' ),
			UserSchool() => _( 'Next grade at current school' ),
			'0' => _( 'Retain' ),
			'-1' => _( 'Do not enroll after this school year' ),
		];

		foreach ( (array) $schools_RET as $school )
		{
			$next_year_options[ $school['ID'] ] = $school['TITLE'];
		}

		if ( isset( $_REQUEST['next_year'] )
			&& $_REQUEST['next_year'] !== '' ) // Handle "Retain" case: value is '0'.
		{
			$extra['WHERE'] .= $_REQUEST['next_year'] == '!' ?
				" AND ssm.NEXT_SCHOOL IS NULL" :
				" AND ssm.NEXT_SCHOOL='" . $_REQUEST['next_year'] . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Next Year' ) . ': </b>' .
					$next_year_options[$_REQUEST['next_year']] . '<br />';
			}
		}

		return $extra;
	}

	function html()
	{
		$next_year_options = [
			'' => _( 'N/A' ),
			'!' => _( 'No Value' ),
			UserSchool() => _( 'Next grade at current school' ),
			'0' => _( 'Retain' ),
			'-1' => _( 'Do not enroll after this school year' ),
		];

		$schools_RET = DBGet( "SELECT ID,TITLE
			FROM schools
			WHERE ID!='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" );

		foreach ( (array) $schools_RET as $school )
		{
			$next_year_options[ $school['ID'] ] = $school['TITLE'];
		}

		$html = '<tr class="st"><td><label for="next_year">' . _( 'Next Year' ) . '</label></td><td>
		<select name="next_year" id="next_year">';

		foreach ( $next_year_options as $id => $option )
		{
			$html .= '<option value="' . AttrEscape( $id ) . '">' . $option . '</option>';
		}

		return $html . '</select></td></tr>';
	}
}


// Calendar (Enrollment) Widget
class Widget_calendar implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Students'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['calendar'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['calendar'] === '!' )
		{
			$where_not = ( isset( $_REQUEST['calendar_not'] ) && $_REQUEST['calendar_not'] === 'Y' ?
				'NOT ' : '' );

			$extra['WHERE'] .= " AND ssm.CALENDAR_ID IS " . $where_not . "NULL";

			$text_not = ( isset( $_REQUEST['calendar_not'] ) && $_REQUEST['calendar_not'] === 'Y' ?
				_( 'Any Value' ) : _( 'No Value' ) );
		}
		else
		{
			$where_not = ( isset( $_REQUEST['calendar_not'] ) && $_REQUEST['calendar_not'] === 'Y' ?
				'!' : '' );

			$extra['WHERE'] .= " AND ssm.CALENDAR_ID" . $where_not . "='" . (int) $_REQUEST['calendar'] . "'";

			$calendars_RET = DBGet( "SELECT CALENDAR_ID,TITLE
				FROM attendance_calendars
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY DEFAULT_CALENDAR ASC" );

			foreach ( (array) $calendars_RET as $calendar )
			{
				if ( $_REQUEST['calendar'] === $calendar['CALENDAR_ID'] )
				{
					$calendar_title = $calendar['TITLE'];

					break;
				}
			}

			$text_not = ( isset( $_REQUEST['calendar_not'] ) && $_REQUEST['calendar_not'] == 'Y' ?
				_( 'Not' ) . ' ' : '' ) . $calendar_title;
		}

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Calendar' ) . ': </b>' . $text_not . '<br />';
		}

		return $extra;
	}

	function html()
	{
		$calendars_RET = DBGet( "SELECT CALENDAR_ID,TITLE
			FROM attendance_calendars
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY DEFAULT_CALENDAR ASC" );

		$html = '<tr class="st"><td><label for="calendar_input">' . _( 'Calendar' ) . '</label></td><td>
		<label>
			<input type="checkbox" name="calendar_not" value="Y"> ' . _( 'Not' ) .
		'</label>
		<select name="calendar" id="calendar_input">
			<option value="">' . _( 'N/A' ) . '</option>
			<option value="!">' . _( 'No Value' ) . '</option>';

		foreach ( (array) $calendars_RET as $calendar )
		{
			$html .= '<option value="' . AttrEscape( $calendar['CALENDAR_ID'] ) . '">' .
				$calendar['TITLE'] . '</option>';
		}

		return $html . '</select></td></tr>';
	}
}


// Attendance Start / Enrolled Widget
class Widget_enrolled implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Students'];
	}

	function extra( $extra )
	{
		$enrolled_begin = RequestedDate(
			'enrolled_begin',
			( issetVal( $_REQUEST['enrolled_begin'], '' ) )
		);

		$enrolled_end = RequestedDate(
			'enrolled_end',
			( issetVal( $_REQUEST['enrolled_end'], '' ) )
		);

		if ( ! $enrolled_begin
			&& ! $enrolled_end )
		{
			return $extra;
		}

		if ( $enrolled_end
			&& $enrolled_begin > $enrolled_end )
		{
			// Begin date > end date, switch.
			$enrolled_begin_tmp = $enrolled_begin;

			$enrolled_begin = $enrolled_end;
			$enrolled_end = $enrolled_begin_tmp;
		}

		if ( $enrolled_begin
			&& $enrolled_end )
		{
			$extra['WHERE'] .= " AND ssm.START_DATE
				BETWEEN '" . $enrolled_begin .
				"' AND '" . $enrolled_end . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'Between' ) . ': </b>' .
					ProperDate( $enrolled_begin ) . ' &amp; ' .
					ProperDate( $enrolled_end ) . '<br />';
			}
		}
		elseif ( $enrolled_begin )
		{
			$extra['WHERE'] .= " AND ssm.START_DATE>='" . $enrolled_begin . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'On or After' ) . ': </b>' .
					ProperDate( $enrolled_begin ) . '<br />';
			}
		}
		elseif ( $enrolled_end )
		{
			$extra['WHERE'] .= " AND ssm.START_DATE<='" . $enrolled_end . "'";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . _( 'Enrolled' ) . ' ' . _( 'On or Before' ) . ': </b>' .
					ProperDate( $enrolled_end ) . '<br />';
			}
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' . _( 'Attendance Start' ) . '</td><td>
		<table class="cellspacing-0"><tr><td class="sizep2">
		&ge;
		</td><td>' .
		PrepareDate( '', '_enrolled_begin', true, [ 'short' => true ] ) .
		'</td></tr><tr><td class="sizep2">
		&le;
		</td><td>' .
		PrepareDate( '', '_enrolled_end', true, [ 'short' => true ] ) .
		'</td></tr></table>
		</td></tr>';
	}
}


// Previously Enrolled Widget
class Widget_rolled implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Students'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['rolled'] ) )
		{
			return $extra;
		}

		$extra['WHERE'] .= " AND " . ( $_REQUEST['rolled'] == 'Y' ? '' : 'NOT ' ) . "exists
			(SELECT ''
				FROM student_enrollment
				WHERE STUDENT_ID=ssm.STUDENT_ID
				AND SYEAR<ssm.SYEAR)";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Previously Enrolled' ) . ': </b>' .
				( $_REQUEST['rolled'] == 'Y' ? _( 'Yes' ) : _( 'No' ) ) . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' . _( 'Previously Enrolled' ) . '</td><td>
		<label>
			<input type="radio" value="" name="rolled" checked>&nbsp;' . _( 'N/A' ) .
		'</label> &nbsp;
		<label>
			<input type="radio" value="Y" name="rolled">&nbsp;' . _( 'Yes' ) .
		'</label> &nbsp;
		<label>
			<input type="radio" value="N" name="rolled">&nbsp;' . _( 'No' ) .
		'</label>
		</td></tr>';
	}
}


// Food Service Balance Widget
class Widget_fsa_balance implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['fsa_balance'] )
			|| ! is_numeric( $_REQUEST['fsa_balance'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ',food_service_student_accounts fssa';

			$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID';
		}

		$extra['FROM'] .= ",food_service_accounts fsa";
		$extra['WHERE'] .= " AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
			AND fsa.BALANCE" . ( empty( $_REQUEST['fsa_bal_ge'] ) ? '<' : '>=' ) .
			"'" . round(  $_REQUEST['fsa_balance'], 2 ) . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Balance' ) . ' </b> ' .
				'<span class="sizep2">' . ( empty( $_REQUEST['fsa_bal_ge'] ) ? '&lt;' : '&ge;' ) . '</span> ' .
				Currency( $_REQUEST['fsa_balance'] ) . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td><label for="fsa_balance">' . _( 'Balance' ) . '</label></td><td>
		<label class="sizep2">
			<input type="radio" name="fsa_bal_ge" value="" checked> &lt;</label>&nbsp;
		<label  class="sizep2">
			<input type="radio" name="fsa_bal_ge" value="Y"> &ge;</label>
		<input name="fsa_balance" id="fsa_balance" type="number" step="0.01"' .
			( $value ? ' value="' . AttrEscape( $value ) . '"' : '') . ' min="-999999999999999" max="999999999999999">
		</td></tr>';
	}
}


// Food Service Balance Warning Widget
class Widget_fsa_balance_warning extends Widget_fsa_balance
{
	function html( $value = '' )
	{
		$value = $GLOBALS['warning'];

		return parent::html( $value );
	}
}


// Food Service Discount Widget
class Widget_fsa_discount implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_discount'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos($extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ",food_service_student_accounts fssa";

			$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
		}

		$extra['WHERE'] .= $_REQUEST['fsa_discount'] == 'Full' ?
			" AND fssa.DISCOUNT IS NULL" :
			" AND fssa.DISCOUNT='" . $_REQUEST['fsa_discount'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Discount' ) . ': </b>' .
				_( $_REQUEST['fsa_discount'] ) . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td><label for="fsa_discount">' . _( 'Discount' ) . '</label></td><td>
		<select name="fsa_discount" id="fsa_discount">
		<option value="">' . _( 'Not Specified' ) . '</option>
		<option value="Full">' . _( 'Full' ) . '</option>
		<option value="Reduced">' . _( 'Reduced' ) . '</option>
		<option value="Free">' . _( 'Free' ) . '</option>
		</select>
		</td></tr>';
	}
}


// Food Service Account Status Widget
class Widget_fsa_status implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_status'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ",food_service_student_accounts fssa";

			$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
		}

		$extra['WHERE'] .= $_REQUEST['fsa_status'] == 'Active' ?
			" AND fssa.STATUS IS NULL" :
			" AND fssa.STATUS='" . $_REQUEST['fsa_status'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Account Status' ) . ': </b>' .
				_( $_REQUEST['fsa_status'] ) . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td><label for="fsa_status">' . _( 'Account Status' ) . '</label></td><td>
		<select name="fsa_status" id="fsa_status">
		<option value="">' . _( 'Not Specified' ) . '</option>
		<option value="Active"' . ( $value == 'active' ? ' selected' : '' ) . '>' . _( 'Active' ) . '</option>
		<option value="Inactive">' . _( 'Inactive' ) . '</option>
		<option value="Disabled">' . _( 'Disabled' ) . '</option>
		<option value="Closed">' . _( 'Closed' ) . '</option>
		</select>
		</td></tr>';
	}
}


// Food Service Active Account Status Widget
class Widget_fsa_status_active extends Widget_fsa_status
{
	function html( $value = 'active' )
	{
		return parent::html( $value );
	}
}


// Food Service Barcode Widget
class Widget_fsa_barcode implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_barcode'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ",food_service_student_accounts fssa";

			$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
		}

		$extra['WHERE'] .= " AND fssa.BARCODE='" . $_REQUEST['fsa_barcode'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Barcode' ) . ': </b>' .
				$_REQUEST['fsa_barcode'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td><label for="fsa_barcode">' . _( 'Barcode' ) .
		'</label></td><td>
		<input type="text" name="fsa_barcode" id="fsa_barcode" size="15" maxlength="50">
		</td></tr>';
	}
}


// Food Service Account ID Widget
class Widget_fsa_account_id implements Widget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['fsa_account_id'] )
			|| ! is_numeric( $_REQUEST['fsa_account_id'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ",food_service_student_accounts fssa";

			$extra['WHERE'] .= " AND fssa.STUDENT_ID=s.STUDENT_ID";
		}

		$extra['WHERE'] .= " AND fssa.ACCOUNT_ID='" . (int) $_REQUEST['fsa_account_id'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Account ID' ) . ': </b>' .
				(int) $_REQUEST['fsa_account_id'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td><label for="fsa_account_id">' . _( 'Account ID' ) . '</label></td><td>
		<input type="text" name="fsa_account_id" id="fsa_account_id" size="5" maxlength="9">
		</td></tr>';
	}
}


// @since 5.1 Medical Immunization or Physical Widget.
// Called in Search.fnc.php.
class Widget_medical_date implements Widget
{
	function canBuild( $modules )
	{
		return AllowUse( 'Students/Student.php&category_id=2' );
	}

	function extra( $extra )
	{
		$medical_begin = RequestedDate(
			'medical_begin',
			( issetVal( $_REQUEST['medical_begin'], '' ) )
		);

		$medical_end = RequestedDate(
			'medical_end',
			( issetVal( $_REQUEST['medical_end'], '' ) )
		);

		if ( ! $medical_begin
			&& ! $medical_end )
		{
			return $extra;
		}

		if ( $medical_begin || $medical_end )
		{
			$medical_type = ! empty( $_REQUEST['medical_type'] )
				&& $_REQUEST['medical_type'] === 'Physical' ?
				'Physical' : 'Immunization';

			$extra['WHERE'] .= " AND s.STUDENT_ID IN(SELECT STUDENT_ID
				FROM student_medical
				WHERE TYPE='" . $medical_type . "' ";

			$medical_type_label = $medical_type === 'Physical' ?
				_( 'Physical' ) : _( 'Immunization' );
		}

		if ( $medical_begin
			&& $medical_end )
		{
			$extra['WHERE'] .= " AND MEDICAL_DATE
				BETWEEN '" . $medical_begin .
				"' AND '" . $medical_end . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . $medical_type_label . ' ' . _( 'Between' ) . ': </b>' .
					ProperDate( $medical_begin ) . ' &amp; ' .
					ProperDate( $medical_end ) . '<br />';
			}
		}
		elseif ( $medical_begin )
		{
			$extra['WHERE'] .= " AND MEDICAL_DATE>='" . $medical_begin . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . $medical_type_label . ' ' . _( 'On or After' ) . ' </b>' .
					ProperDate( $medical_begin ) . '<br />';
			}
		}
		elseif ( $medical_end )
		{
			$extra['WHERE'] .= " AND MEDICAL_DATE<='" . $medical_end . "' ";

			if ( ! $extra['NoSearchTerms'] )
			{
				$extra['SearchTerms'] .= '<b>' . $medical_type_label . ' ' . _( 'On or Before' ) . ' </b>' .
					ProperDate( $medical_end ) . '<br />';
			}
		}

		if ( $medical_begin || $medical_end )
		{
			$extra['WHERE'] .= ") ";
		}

		return $extra;
	}

	function html()
	{
		$medical_begin_default = '';

		return '<tr class="st"><td>
		<label>
			<input type="radio" name="medical_type" value="Immunization" checked>&nbsp;' .
			_( 'Immunization' ) .
		'</label> &nbsp;
		<label>
			<input type="radio" name="medical_type" value="Physical">&nbsp;' .
			_( 'Physical' ) .
		'</label></td><td>
		<table class="cellspacing-0"><tr><td>
		<span class="sizep2">&ge;</span>&nbsp;
		</td><td>' .
		PrepareDate( $medical_begin_default, '_medical_begin', true, [ 'short' => true ] ) .
		'</td></tr><tr><td>
		<span class="sizep2">&le;</span>&nbsp;
		</td><td>' .
		PrepareDate( '', '_medical_end', true, [ 'short' => true ] ) .
		'</td></tr></table>
		</td></tr>';
	}
}
