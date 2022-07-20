<?php
/**
 * Student Labels functions
 * Define your own functions in an add-on module or plugin.
 *
 * @package RosarioSIS
 * @subpackage modules
 */

if ( ! function_exists( 'GetStudentLabelsFormHTML' ) )
{
	/**
	 * Get Student Labels Form HTML
	 *
	 * @since 4.0
	 *
	 * @return string Student Labels Form HTML
	 */
	function GetStudentLabelsFormHTML()
	{
		$form = '<fieldset><legend><label><input type="radio" name="mailing_labels" value="" autocomplete="off" checked /> ' .
		_( 'Student Labels' ) . '</label></legend>';

		$form .= '<span>' . _( 'Include On Labels' ) . ':</span>';

		if ( User( 'PROFILE' ) === 'admin' )
		{
			if ( ! empty( $_REQUEST['w_course_period_id_which'] )
				&& $_REQUEST['w_course_period_id_which'] === 'course_period'
				&& ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				$course_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS TEACHER,cp.ROOM
				FROM staff s,course_periods cp
				WHERE s.STAFF_ID=cp.TEACHER_ID
				AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "'" );

				$form .= '<br /><label><input type="checkbox" name="teacher" value="Y"> ' .
				_( 'Teacher' ) . ' (' . $course_RET[1]['TEACHER'] . ')</label>';

				$form .= '<br /><label><input type="checkbox" name="room" value="Y"> ' .
				_( 'Room' ) . ' (' . $course_RET[1]['ROOM'] . ')</label>';
			}
			else
			{
				$form .= '<br /><label><input type="checkbox" name="teacher" value="Y"> ' .
				_( 'Attendance Teacher' ) . '</label>';

				$form .= '<br /><label><input type="checkbox" name="room" value="Y"> ' .
				_( 'Attendance Room' ) . '</label>';
			}
		}
		else
		{
			$form .= '<br /><label><input type="checkbox" name="teacher" value="Y"> ' .
			_( 'Teacher' ) . '</label>';

			$form .= '<br /><label><input type="checkbox" name="room" value="Y"> ' .
			_( 'Room' ) . '</label>';
		}

		$form .= '</fieldset><br />';

		return $form;
	}
}

if ( ! function_exists( 'GetMailingLabelsFormHTML' ) )
{
	/**
	 * Get Mailing Labels Form HTML
	 *
	 * @since 4.0
	 *
	 * @return string Mailing Labels Form HTML
	 */
	function GetMailingLabelsFormHTML()
	{
		// Do not Display Form if User cannot access Addresses & Contacts Student Info tab.
		if ( ! AllowUse( 'Students/Student.php&category_id=3' ) )
		{
			return '';
		}

		$form = '<fieldset disabled><legend><label><input type="radio" name="mailing_labels" value="Y" autocomplete="off" /> ' .
		_( 'Address Labels' ) . '</label></legend>';

		$form .= '<label>
			<input type="radio" name="to_address" value="" checked /> ' .
		_( 'To Contacts' ) . '</label>';

		$form .= '<br /><label><input type="radio" name="to_address" value="student" /> ' .
		_( 'To Student' ) . '</label>';

		$form .= '<br /><label><input type="radio" name="to_address" value="family" /> ' .
		_( 'To the parents of' ) . '</label>';

		if ( Config( 'STUDENTS_USE_MAILING' ) )
		{
			$form .= '<br /><br /><label><input type="radio" name="residence" value="" checked /> ' .
			_( 'Mailing' ) . '</label>';

			$form .= '<br /><label><input type="radio" name="residence" value="Y" /> ' .
			_( 'Residence' ) . '</label>';
		}
		else
		{
			$form .= '<input type="hidden" name="residence" value="Y" />';
		}

		$form .= '</fieldset><br />';

		return $form;
	}
}

if ( ! function_exists( 'GetStudentLabelsFormJS' ) )
{
	/**
	 * Get Student Labels Form JS
	 * Disable unchecked fieldset
	 *
	 * @since 9.0
	 *
	 * @return string Student Labels Form JS
	 */
	function GetStudentLabelsFormJS()
	{
		// No JS if User cannot access Addresses & Contacts Student Info tab.
		if ( ! AllowUse( 'Students/Student.php&category_id=3' ) )
		{
			return '';
		}

		ob_start();
		?>
		<script>
			$('input[name=mailing_labels]').click(function(event){
				// Toggle fieldset disabled attribute.
				$('input[name=mailing_labels]').parents('fieldset').prop('disabled', function(i, v) {
					return !v;
				});
			});
		</script>
		<?php

		return ob_get_clean();
	}
}

if ( ! function_exists( 'GetStudentLabelsStartingRowColumnFormHTML' ) )
{
	/**
	 * Get Student Labels Starting Row & Column Form HTML
	 *
	 * @since 4.0
	 *
	 * @param int $max_rows Max rows.
	 * @param int $max_cols Max columns.
	 *
	 * @return string Student Labels Starting Row & Column Form HTML
	 */
	function GetStudentLabelsStartingRowColumnFormHTML( $max_rows, $max_cols )
	{
		$form = '<select name="start_row" id="start-row">';

		for ( $row = 1; $row <= $max_rows; $row++ )
		{
			$form .= '<option value="' . AttrEscape( $row ) . '">' . $row;
		}

		$form .= '</select>';

		$form .= FormatInputTitle( _( 'Starting row' ), 'start-row' );

		$form .= '<br /><select name="start_col" id="start-col">';

		for ( $col = 1; $col <= $max_cols; $col++ )
		{
			$form .= '<option value="' . AttrEscape( $col ) . '">' . $col;
		}

		$form .= '</select>';

		$form .= FormatInputTitle( _( 'Starting column' ), 'start-col' );

		return $form;
	}
}

if ( ! function_exists( 'GetStudentLabelsExtra' ) )
{
	/**
	 * Get Student Labels Extra
	 *
	 * @since 4.0
	 *
	 * @uses GetStudentLabelsExtraAdmin()
	 * @uses GetStudentLabelsExtraNonAdmin()
	 *
	 * @param array $extra Existing extra array options.
	 *
	 * @return array Student Labels Extra
	 */
	function GetStudentLabelsExtra( $extra = [] )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$extra['SELECT'] .= GetStudentLabelsExtraAdmin();
		}
		else
		{
			$extra['SELECT'] .= GetStudentLabelsExtraNonAdmin();
		}

		return $extra;
	}
}

if ( ! function_exists( 'GetStudentLabelsExtraAdmin' ) )
{
	/**
	 * Get Student Labels Extra for Admin users
	 *
	 * @since 5.3
	 *
	 * @return array Student Labels Extra SELECT
	 */
	function GetStudentLabelsExtraAdmin()
	{
		$extra_select = '';

		if ( ! empty( $_REQUEST['w_course_period_id_which'] )
			&& $_REQUEST['w_course_period_id_which'] === 'course_period'
			&& ! empty( $_REQUEST['w_course_period_id'] ) )
		{
			if ( ! empty( $_REQUEST['teacher'] ) )
			{
				$extra_select .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
				FROM staff st,course_periods cp
				WHERE st.STAFF_ID=cp.TEACHER_ID
				AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "') AS TEACHER";
			}

			if ( ! empty( $_REQUEST['room'] ) )
			{
				$extra_select .= ",(SELECT cp.ROOM
					FROM course_periods cp
					WHERE cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "') AS ROOM";
			}

			return $extra_select;
		}

		if ( ! empty( $_REQUEST['teacher'] ) )
		{
			// FJ multiple school periods for a course period.
			// SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL.
			$extra_select .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
			FROM staff st,course_periods cp,school_periods p,schedule ss,course_period_school_periods cpsp
			WHERE st.STAFF_ID=cp.TEACHER_ID
			AND cpsp.PERIOD_id=p.PERIOD_ID
			AND cp.DOES_ATTENDANCE IS NOT NULL
			AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
			AND ss.STUDENT_ID=s.STUDENT_ID
			AND ss.SYEAR='" . UserSyear() . "'
			AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
			AND (ss.START_DATE<='" . DBDate() . "'
				AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
			ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS TEACHER";
		}

		if ( ! empty( $_REQUEST['room'] ) )
		{
			// SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL.
			$extra_select .= ",(SELECT cp.ROOM
				FROM course_periods cp,school_periods p,schedule ss,course_period_school_periods cpsp
				WHERE cpsp.PERIOD_id=p.PERIOD_ID
				AND cp.DOES_ATTENDANCE IS NOT NULL
				AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
				AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
				AND ss.STUDENT_ID=s.STUDENT_ID
				AND ss.SYEAR='" . UserSyear() . "'
				AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
				AND (ss.START_DATE<='" . DBDate() . "'
					AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
				ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS ROOM";
		}

		return $extra_select;
	}
}

if ( ! function_exists( 'GetStudentLabelsExtraNonAdmin' ) )
{
	/**
	 * Get Student Labels Extra SELECT for Non Admin users.
	 *
	 * @since 5.3
	 *
	 * @return array Student Labels Extra SELECT
	 */
	function GetStudentLabelsExtraNonAdmin()
	{
		$extra_select = '';

		if ( ! empty( $_REQUEST['teacher'] ) )
		{
			$extra_select .= ",(SELECT " . DisplayNameSQL( 'st' ) . " AS FULL_NAME
				FROM staff st,course_periods cp
				WHERE st.STAFF_ID=cp.TEACHER_ID
				AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS TEACHER";
		}

		if ( ! empty( $_REQUEST['room'] ) )
		{
			$extra_select .= ",(SELECT cp.ROOM
				FROM course_periods cp
				WHERE cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS ROOM";
		}

		return $extra_select;
	}
}

if ( ! function_exists( 'GetMailingLabelsExtra' ) )
{
	/**
	 * Get Mailing Labels Extra
	 *
	 * @since 4.0
	 *
	 * @param array $extra Existing extra array options.
	 *
	 * @return array Mailing Labels Extra
	 */
	function GetMailingLabelsExtra( $extra = [] )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		// Force no Students found error if no Addresses found!
		$extra['WHERE'] .= " AND EXISTS(SELECT 1
		FROM students_join_address WHERE STUDENT_ID=s.STUDENT_ID)";

		if ( ! empty( $_REQUEST['to_address'] ) )
		{
			$_REQUEST['residence'] = 'Y';
		}

		Widgets( 'mailing_labels', $extra );

		$extra['group'] = [ 'ADDRESS_ID' ];

		return $extra;
	}
}

if ( ! function_exists( 'StudentLabelsPDF' ) )
{
	/**
	 * Generate Student Labels PDF
	 *
	 * @since 4.0
	 *
	 * @deprecated since 5.3 Use StudentLabelsHTML() instead.
	 *
	 * @param array $RET Addresses DB RET.
	 */
	function StudentLabelsPDF( $RET )
	{
		$handle = PDFstart();

		echo StudentLabelsHTML( $RET );

		PDFstop( $handle );
	}
}

if ( ! function_exists( 'StudentLabelsHTML' ) )
{
	/**
	 * Generate Student Labels HTML
	 *
	 * @since 4.0
	 *
	 * @uses StudentLabelHTML()
	 *
	 * @param array $RET Students DB RET.
	 *
	 * @return string Student Labels HTML
	 */
	function StudentLabelsHTML( $RET )
	{
		ob_start();

		echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

		$max_cols = 3;
		$max_rows = 15;

		$cols = $rows = 0;

		$skipRET = [];

		for ( $i = ( $_REQUEST['start_row'] - 1 ) * $max_cols + $_REQUEST['start_col']; $i > 1; $i-- )
		{
			$skipRET[-$i] = [ 'LAST_NAME' => '&nbsp;' ];
		}

		foreach ( (array) $skipRET + $RET as $i => $student )
		{
			if ( $cols < 1 )
			{
				echo '<tr>';
			}

			echo '<td class="center" style="vertical-align: middle;">' . StudentLabelHTML( $student ) . '</td>';

			$cols++;

			if ( $cols === $max_cols )
			{
				echo '</tr><tr><td clospan="' . $max_cols . '">&nbsp;</td></tr>';

				$rows++;

				$cols = 0;
			}

			if ( $rows === $max_rows )
			{
				echo '</table>';
				echo '<div style="page-break-after: always;"></div>';
				echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

				$rows = 0;
			}
		}

		if ( $cols > 0 && $rows < $max_rows )
		{
			while ( $cols < $max_cols )
			{
				echo '<td class="center" style="vertical-align: middle; padding-bottom: 8px;">&nbsp;</td>';

				$cols++;
			}

			if ( $cols === $max_cols )
			{
				echo '</tr>';
			}
		}

		echo '</table>';

		return ob_get_clean();
	}
}

if ( ! function_exists( 'StudentLabelHTML' ) )
{
	/**
	 * Student Label HTML
	 *
	 * @since 5.3
	 *
	 * @param array $student Student Info.
	 *
	 * @return string Student Label HTML
	 */
	function StudentLabelHTML( $student )
	{
		$html = '';

		if ( ! empty( $student['FULL_NAME'] ) )
		{
			$html .= '<b>' . $student['FULL_NAME'] . '</b>';
		}

		if ( ! empty( $_REQUEST['teacher'] ) )
		{
			if ( ! empty( $student['TEACHER'] ) )
			{
				$html .= '<br />' . _( 'Teacher' ) . ':&nbsp;' . $student['TEACHER'];
			}
			else
			{
				$html .= '<br />&nbsp;';
			}
		}

		if ( ! empty( $_REQUEST['room'] ) )
		{
			if ( ! empty( $student['ROOM'] ) )
			{
				$html .= '<br />' . _( 'Room' ) . ':&nbsp;' . $student['ROOM'];
			}
			else
			{
				$html .= '<br />&nbsp;';
			}
		}

		return $html;
	}
}

if ( ! function_exists( 'MailingLabelsPDF' ) )
{
	/**
	 * Generate Mailing Labels PDF
	 *
	 * @since 4.0
	 *
	 * @deprecated since 5.3 Use MailingLabelsHTML() instead.
	 *
	 * @param array $RET Addresses DB RET.
	 */
	function MailingLabelsPDF( $RET )
	{
		$handle = PDFstart();

		echo MailingLabelsHTML( $RET );

		PDFstop( $handle );
	}
}

if ( ! function_exists( 'MailingLabelsHTML' ) )
{
	/**
	 * Generate Mailing Labels HTML
	 *
	 * @since 4.0
	 *
	 * @uses MailingLabelFormatAddressesToStudent()
	 * @uses MailingLabelFormatAddressesToFamily()
	 *
	 * @param array $RET Addresses DB RET.
	 *
	 * @return string Mailing Labels HTML
	 */
	function MailingLabelsHTML( $RET )
	{
		ob_start();

		echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

		$max_cols = 3;
		$max_rows = 10;

		$cols = 0;
		$rows = 0;
		$RET_count = count( (array) $RET );

		for ( $i = -(  ( $_REQUEST['start_row'] - 1 ) * $max_cols + $_REQUEST['start_col'] - 1 ); $i < $RET_count; $i++ )
		{
			if ( $i >= 0 )
			{
				$addresses = current( $RET );
				next( $RET );

				if ( $_REQUEST['to_address'] === 'student' )
				{
					$addresses = MailingLabelFormatAddressesToStudent( $addresses );
				}
				elseif ( $_REQUEST['to_address'] === 'family' )
				{
					$addresses = MailingLabelFormatAddressesToFamily( $addresses );
				}
			}
			else
			{
				$addresses = [ 1 => [ 'MAILING_LABEL' => ' ' ] ];
			}

			foreach ( (array) $addresses as $address )
			{
				if ( ! $address['MAILING_LABEL'] )
				{
					continue;
				}

				if ( $cols < 1 )
				{
					echo '<tr>';
				}

				echo '<td class="center" style=vertical-align: middle;">' .
					$address['MAILING_LABEL'] . '</td>';

				$cols++;

				if ( $cols === $max_cols )
				{
					echo '</tr><tr><td clospan="' . $max_cols . '">&nbsp;</td></tr>';

					$rows++;

					$cols = 0;
				}

				if ( $rows === $max_rows )
				{
					echo '</table><div style="page-break-after: always"></div>';

					echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

					$rows = 0;
				}
			}
		}

		if ( $cols > 0 && $rows < $max_rows )
		{
			while ( $cols < $max_cols )
			{
				echo '<td class="center" style=height:86px; vertical-align: middle;">&nbsp;</td>';

				$cols++;
			}

			if ( $cols === $max_cols )
			{
				echo '</tr>';
			}
		}

		echo '</table>';

		return ob_get_clean();
	}
}

if ( ! function_exists( 'MailingLabelFormatAddressesToStudent' ) )
{
	/**
	 * Mailing Label Format Addresses To Student
	 *
	 * @since 5.3
	 *
	 * @param array $addresses Addresses.
	 *
	 * @return array $addresses Format Addresses To Student
	 */
	function MailingLabelFormatAddressesToStudent( $addresses )
	{
		foreach ( (array) $addresses as $key => $address )
		{
			$name = $address['FULL_NAME'];

			$addresses[$key]['MAILING_LABEL'] = $name . '<br />' .
			mb_substr( $address['MAILING_LABEL'], mb_strpos( $address['MAILING_LABEL'], '<!-- -->' ) );
		}

		return $addresses;
	}
}

if ( ! function_exists( 'MailingLabelFormatAddressesToFamily' ) )
{
	/**
	 * Mailing Label Format Addresses To Family
	 *
	 * @since 5.3
	 *
	 * @param array $addresses Addresses.
	 *
	 * @return array $addresses Format Addresses To Family
	 */
	function MailingLabelFormatAddressesToFamily( $addresses )
	{
		// If grouping by address, replace people list in mailing labels with students list.
		$lasts = [];

		foreach ( (array) $addresses as $address )
		{
			$lasts[$address['LAST_NAME']][] = $address['FIRST_NAME'];
		}

		$students = '';

		foreach ( (array) $lasts as $last => $firsts )
		{
			$student = '';
			$previous = '';

			foreach ( (array) $firsts as $first )
			{
				if ( $student && $previous )
				{
					$student .= ', ' . $previous;
				}
				elseif ( $previous )
				{
					$student = $previous;
				}

				$previous = $first;
			}

			if ( $student )
			{
				$student .= ' & ' . $previous . ' ' . $last;
			}
			else
			{
				$student = $previous . ' ' . $last;
			}

			$students .= $student . ', ';
		}

		$to_family = _( 'To the parents of' ) . ':';

		$addresses = [
			1 => [
				'MAILING_LABEL' => $to_family . '<br />' . mb_substr( $students, 0, -2 ) .
				'<br />' .
				mb_substr(
					$addresses[1]['MAILING_LABEL'],
					mb_strpos( $addresses[1]['MAILING_LABEL'], '<!-- -->' )
				),
			],
		];

		return $addresses;
	}
}
