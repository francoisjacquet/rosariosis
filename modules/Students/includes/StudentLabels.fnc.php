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
		$form = '<fieldset><legend><label><input type="radio" name="mailing_labels" value="" checked /> ' .
		_( 'Student Labels' ) . '</label></legend>';

		$form .= '<span>' . _( 'Include On Labels' ) . ':</span>';

		if ( User( 'PROFILE' ) === 'admin' )
		{
			if ( ! empty( $_REQUEST['w_course_period_id_which'] )
				&& $_REQUEST['w_course_period_id_which'] === 'course_period'
				&& ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				$course_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS TEACHER,cp.ROOM
				FROM STAFF s,COURSE_PERIODS cp
				WHERE s.STAFF_ID=cp.TEACHER_ID
				AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "'" );

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

		$form = '<fieldset><legend><label><input type="radio" name="mailing_labels" value="Y" /> ' .
		_( 'Address Labels' ) . '</label></legend>';

		$form .= '<label>
			<input type="radio" name="to_address" value="" checked /> ' .
		_( 'To Contacts' ) . '</label>';

		if ( Config( 'STUDENTS_USE_MAILING' ) )
		{
			$form .= '<br /><label><input type="radio" name="residence" value="" checked /> ' .
			_( 'Mailing' ) . '</label>';

			$form .= '<br /><label><input type="radio" name="residence" value="Y" /> ' .
			_( 'Residence' ) . '</label>';
		}
		else
		{
			$form .= '<input type="hidden" name="residence" value="Y" />';
		}

		$form .= '<br /><label><input type="radio" name="to_address" value="student" /> ' .
		_( 'To Student' ) . '</label>';

		$form .= '<br /><label><input type="radio" name="to_address" value="family" /> ' .
		_( 'To the parents of' ) . '</label>';

		$form .= '</fieldset><br />';

		return $form;
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
			$form .= '<option value="' . $row . '">' . $row;
		}

		$form .= '</select>';

		$form .= FormatInputTitle( _( 'Starting row' ), 'start-row' );

		$form .= '<br /><select name="start_col" id="start-col">';

		for ( $col = 1; $col <= $max_cols; $col++ )
		{
			$form .= '<option value="' . $col . '">' . $col;
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
	 * @param array $extra Existing extra array options.
	 *
	 * @return array Student Labels Extra
	 */
	function GetStudentLabelsExtra( $extra = array() )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$extra['SELECT'] = empty( $extra['SELECT'] ) ? '' : $extra['SELECT'];

		if ( User( 'PROFILE' ) === 'admin' )
		{
			if ( ! empty( $_REQUEST['w_course_period_id_which'] )
				&& $_REQUEST['w_course_period_id_which'] === 'course_period'
				&& ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				if ( ! empty( $_REQUEST['teacher'] ) )
				{
					$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
					FROM STAFF st,COURSE_PERIODS cp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "') AS TEACHER";
				}

				if ( ! empty( $_REQUEST['room'] ) )
				{
					$extra['SELECT'] .= ",(SELECT cp.ROOM
						FROM COURSE_PERIODS cp
						WHERE cp.COURSE_PERIOD_ID='" . $_REQUEST['w_course_period_id'] . "') AS ROOM";
				}
			}
			else
			{
				if ( ! empty( $_REQUEST['teacher'] ) )
				{
					// FJ multiple school periods for a course period.
					$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
					FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss, COURSE_PERIOD_SCHOOL_PERIODS cpsp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cpsp.PERIOD_id=p.PERIOD_ID
					AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
					AND p.ATTENDANCE='Y'
					AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
					AND ss.STUDENT_ID=s.STUDENT_ID
					AND ss.SYEAR='" . UserSyear() . "'
					AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
					AND (ss.START_DATE<='" . DBDate() . "'
						AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
					ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				}

				if ( ! empty( $_REQUEST['room'] ) )
				{
					$extra['SELECT'] .= ",(SELECT cp.ROOM
						FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss,COURSE_PERIOD_SCHOOL_PERIODS cpsp
						WHERE cpsp.PERIOD_id=p.PERIOD_ID
						AND cpsp.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						AND p.ATTENDANCE='Y'
						AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
						AND ss.STUDENT_ID=s.STUDENT_ID
						AND ss.SYEAR='" . UserSyear() . "'
						AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
						AND (ss.START_DATE<='" . DBDate() . "'
							AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
						ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
				}
			}
		}
		else
		{
			if ( ! empty( $_REQUEST['teacher'] ) )
			{
				$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . " AS FULL_NAME
					FROM STAFF st,COURSE_PERIODS cp
					WHERE st.STAFF_ID=cp.TEACHER_ID
					AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS TEACHER";
			}

			if ( ! empty( $_REQUEST['room'] ) )
			{
				$extra['SELECT'] .= ",(SELECT cp.ROOM
					FROM COURSE_PERIODS cp
					WHERE cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS ROOM";
			}
		}

		return $extra;
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
	function GetMailingLabelsExtra( $extra = array() )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		// Force no Students found error if no Addresses found!
		$extra['WHERE'] .= " AND EXISTS(SELECT 1
		FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=s.STUDENT_ID)";

		if ( ! empty( $_REQUEST['to_address'] ) )
		{
			$_REQUEST['residence'] = 'Y';
		}

		Widgets( 'mailing_labels', $extra );

		$extra['group'] = array( 'ADDRESS_ID' );

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
	 * @param array $RET Students DB RET.
	 *
	 * @return array Student Labels PDF
	 */
	function StudentLabelsPDF( $RET )
	{
		$handle = PDFstart();

		echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

		$max_cols = 3;
		$max_rows = 15;

		$to_family = _( 'To the parents of' ) . ':';

		$cols = $rows = 0;

		$skipRET = array();

		for ( $i = ( $_REQUEST['start_row'] - 1 ) * $max_cols + $_REQUEST['start_col']; $i > 1; $i-- )
		{
			$skipRET[-$i] = array( 'LAST_NAME' => '&nbsp;' );
		}

		foreach ( (array) $skipRET + $RET as $i => $student )
		{
			if ( $cols < 1 )
			{
				echo '<tr>';
			}

			echo '<td class="center" style="vertical-align: middle;">';

			if ( ! empty( $student['FULL_NAME'] ) )
			{
				echo '<b>' . $student['FULL_NAME'] . '</b>';
			}

			if ( ! empty( $_REQUEST['teacher'] ) )
			{
				if ( ! empty( $student['TEACHER'] ) )
				{
					echo '<br />' . _( 'Teacher' ) . ':&nbsp;' . $student['TEACHER'];
				}
				else
				{
					echo '<br />&nbsp;';
				}
			}

			if ( ! empty( $_REQUEST['room'] ) )
			{
				if ( ! empty( $student['ROOM'] ) )
				{
					echo '<br />' . _( 'Room' ) . ':&nbsp;' . $student['ROOM'];
				}
				else
				{
					echo '<br />&nbsp;';
				}
			}

			echo '</td>';

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

		PDFstop( $handle );
	}
}

if ( ! function_exists( 'MailingLabelsPDF' ) )
{
	/**
	 * Generate Mailing Labels PDF
	 *
	 * @since 4.0
	 *
	 * @param array $RET Addresses DB RET.
	 *
	 * @return array Mailing Labels PDF
	 */
	function MailingLabelsPDF( $RET )
	{
		$handle = PDFstart();

		echo '<table style="height: 100%" class="width-100p cellspacing-0 fixed-col">';

		$max_cols = 3;
		$max_rows = 10;

		$to_family = _( 'To the parents of' ) . ':';

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
					foreach ( (array) $addresses as $key => $address )
					{
						$name = $address['FULL_NAME'];

						$addresses[$key]['MAILING_LABEL'] = $name . '<br />' .
						mb_substr( $address['MAILING_LABEL'], mb_strpos( $address['MAILING_LABEL'], '<!-- -->' ) );
					}
				}
				elseif ( $_REQUEST['to_address'] === 'family' )
				{
					// If grouping by address, replace people list in mailing labels with students list.
					$lasts = array();

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

					$addresses = array(
						1 => array(
							'MAILING_LABEL' => $to_family . '<br />' . mb_substr( $students, 0, -2 ) .
							'<br />' .
							mb_substr(
								$addresses[1]['MAILING_LABEL'],
								mb_strpos( $addresses[1]['MAILING_LABEL'], '<!-- -->' )
							),
						),
					);
				}
			}
			else
			{
				$addresses = array( 1 => array( 'MAILING_LABEL' => ' ' ) );
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

		PDFstop( $handle );
	}
}
