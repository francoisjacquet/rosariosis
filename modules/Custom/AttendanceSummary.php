<?php

require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	$months = [
		1 => _( 'January' ),
		2 => _( 'February' ),
		3 => _( 'March' ),
		4 => _( 'April' ),
		5 => _( 'May' ),
		6 => _( 'June' ),
		7 => _( 'July' ),
		8 => _( 'August' ),
		9 => _( 'September' ),
		10 => _( 'October' ),
		11 => _( 'November' ),
		12 => _( 'December' ),
	];

	// Check Social Security + Gender fields exists before adding them to SELECT.
	$custom_RET = DBGet( "SELECT TITLE,ID
		FROM custom_fields
		WHERE ID IN ('200000000','200000003')", [], [ 'ID' ] );

	$extra['SELECT'] = ",ssm.CALENDAR_ID,ssm.START_DATE,ssm.END_DATE";

	foreach ( (array) $custom_RET as $id => $custom )
	{
		$extra['SELECT'] .= ",CUSTOM_" . $id;
	}

	// ACTIVE logic taken from GetStuList().
	$active = "'" . DBEscapeString( _( 'Active' ) ) . "'";
	$inactive = "'" . DBEscapeString( _( 'Inactive' ) ) . "'";

	$extra['SELECT'] .= ',' . db_case( [
		"(ssm.SYEAR='" . UserSyear() . "' AND ('" . DBDate() . "'>=ssm.START_DATE AND ('" . DBDate() . "'<=ssm.END_DATE OR ssm.END_DATE IS NULL)))",
		'TRUE',
		$active,
		$inactive,
	] ) . ' AS STATUS';

	$RET = GetStuList( $extra );

	if ( empty( $RET ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	// Change orientation to landscape.
	$_SESSION['orientation'] = 'landscape';

	$handle = PDFStart();

	?>
	<style>
		body {
			font-size: larger;
		}
	</style>
	<?php

	foreach ( (array) $RET as $student )
	{
		$full_day_minutes = Config( 'ATTENDANCE_FULL_DAY_MINUTES' );

		if ( ! $full_day_minutes )
		{
			// @since 11.2 Dynamic Daily Attendance calculation based on total course period minutes
			$full_day_minutes = "(" . AttendanceDailyTotalMinutesSQL(
				$student['STUDENT_ID'],
				'ac.SCHOOL_DATE'
			) . ")";
		}
		else
		{
			// Prevent SQL injection, add quotes around minutes.
			$full_day_minutes = "'" . $full_day_minutes . "'";
		}

		// @since 9.2.1 SQL use extract() instead of to_char() for MySQL compatibility
		$calendar_RET = DBGet( "SELECT CASE WHEN
			MINUTES>=" . $full_day_minutes .
				" THEN '1.0' ELSE '0.5' END AS POS,
			extract(MONTH from SCHOOL_DATE) AS MON,
			extract(DAY from SCHOOL_DATE) AS DAY
			FROM attendance_calendar ac
			WHERE CALENDAR_ID='" . (int) $student['CALENDAR_ID'] . "'
			AND SCHOOL_DATE>='" . $student['START_DATE'] . "'" .
			( $student['END_DATE'] ? " AND SCHOOL_DATE<='" . $student['END_DATE'] . "'" : '' ),
			[],
			[ 'MON', 'DAY' ] );

		// @since 9.2.1 SQL use extract() instead of to_char() for MySQL compatibility
		$attendance_RET = DBGet( "SELECT
			extract(MONTH from ad.SCHOOL_DATE) AS MON,
			extract(DAY from ad.SCHOOL_DATE) AS DAY,
			ad.STATE_VALUE
			FROM attendance_day ad
			WHERE ad.STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'
			AND ad.SYEAR='" . UserSyear() . "'", [], [ 'MON', 'DAY' ] );
		//echo '<pre>'; var_dump($calendar_RET); echo '</pre>';

		echo '<h2 class="center">' . $student['FULL_NAME'] . '</h2>';

		echo '<table class="width-100p">
		<tr><td>' . NoInput(
			$student['FULL_NAME'],
			_( 'Student Name' )
		) . '</td><td>' . NoInput(
			$student['STUDENT_ID'],
			sprintf( _( '%s ID' ), Config( 'NAME' ) )
		) . '</td><td>' . NoInput(
			( SchoolInfo( 'SCHOOL_NUMBER' ) ? SchoolInfo( 'SCHOOL_NUMBER' ) : SchoolInfo( 'TITLE' ) ) .
				' &mdash; ' . FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ),
			_( 'School' ) . ' &mdash; ' . _( 'Year' )
		) . '</td></tr>';

		// HTML remove "Demographics" header to gain space on PDF (if has header or footer).
		// echo '<tr><td colspan="3"><hr><h3>' . _( 'Demographics' ) . '</h3></td></tr><tr>';
		echo '<tr>';

		foreach ( (array) $custom_RET as $id => $custom )
		{
			echo '<td>' . NoInput(
				$student['CUSTOM_' . $id],
				ParseMLField( $custom_RET[$id][1]['TITLE'] )
			) . '</td>';
		}

		echo '</tr><tr>
		<td>' . NoInput(
			$student['STATUS'],
			_( 'Status' )
		) . '</td><td>' . NoInput(
			$student['GRADE_ID'],
			_( 'Grade Level' )
		) . '</td></tr>';

		echo '<tr><td colspan="3"><hr><h3>' . _( 'Attendance' ) . '</h3>
		<table class="width-100p cellspacing-0 center">';

		echo '<tr class="center"><td><b>' . _( 'Month' ) . '</b></td>';

		for ( $day = 1; $day <= 31; $day++ )
		{
			echo '<td><b>' . ( $day < 10 ? '&nbsp;' : '' ) . $day .
				( $day < 10 ? '&nbsp;' : '' ) . '</b></td>';
		}

		echo '<td><b>' . _( 'Absences' ) . '</b></td>
		<td><b>' . _( 'Possible' ) . '</b></td></tr>';

		$abs_tot = $pos_tot = 0;

		$FY_dates = DBGet( "SELECT START_DATE,END_DATE
		FROM school_marking_periods
		WHERE MP='FY'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

		$first_month = explode( '-', $FY_dates[1]['START_DATE'] );
		$first_month = (int) $first_month[1];

		$last_month = explode( '-', $FY_dates[1]['END_DATE'] );
		$last_month = (int) $last_month[1];

		//foreach ( array(7,8,9,10,11,12,1,2,3,4,5,6) as $month)

		if ( $last_month > $first_month )
		{
			$last_month_tmp = $last_month;
		}
		else
		{
			$last_month_tmp = 12;
		}

		$attendance_codes_locale = [
			// Attendance codes.
			'P' => _( 'Present' ),
			'A' => _( 'Absent' ),
			'H' => _( 'Half' ),
			// Daily attendance.
			'1.0' => _( 'Present' ),
			'0.0' => _( 'Absent' ),
			'0.5' => _( 'Half Day' ),
		];

		$attendance_code_classes = [
			// Attendance codes.
			'P' => 'present',
			'A' => 'absent',
			'H' => 'half-day',
			// Daily attendance.
			'1.0' => 'present',
			'0.0' => 'absent',
			'0.5' => 'half-day',
		];

		for ( $month = $first_month; $month <= $last_month_tmp; $month++ )
		{
			if ( ! empty( $calendar_RET[$month] )
				|| ! empty( $attendance_RET[$month] ) )
			{
				echo '<tr class="center"><td>' . $months[$month] . '</td>';

				$abs = $pos = 0;

				for ( $day = 1; $day <= 31; $day++ )
				{
					if ( ! empty( $calendar_RET[$month][$day] ) )
					{
						$calendar = $calendar_RET[$month][$day][1];

						if ( ! empty( $attendance_RET[$month][$day] ) )
						{
							$attendance = $attendance_RET[$month][$day][1];

							echo '<td class="attendance-code ' .
								$attendance_code_classes[ $attendance['STATE_VALUE'] ] . '"
								style="display: table-cell; padding: 0;">' .
								mb_substr( $attendance_codes_locale[ $attendance['STATE_VALUE'] ], 0, 1 ) . '</td>';

							$abs += ( $attendance['STATE_VALUE'] == '0.0' ?
								$calendar['POS'] :
								( $attendance['STATE_VALUE'] == '0.5' ? $calendar['POS'] / 2 : 0 )
							);
						}
						else
						//green box
						{
							echo '<td style="background-color:#dfd;">&nbsp;</td>';
						}

						$pos += $calendar['POS'];
					}
					else
					{
						// Attendance record before attendance start date!

						if ( ! empty( $attendance_RET[$month][$day] ) )
						{
							$attendance = $attendance_RET[$month][$day][1];

							//red box
							echo '<td class="attendance-code ' .
								$attendance_code_classes[ $attendance['STATE_VALUE'] ] . '"
								style="display: table-cell; padding: 0;">' .
								mb_substr( $attendance_codes_locale[ $attendance['STATE_VALUE'] ], 0, 1 ) . '</td>';
						}
						else
						//pink box
						{
							echo '<td style="background-color:#fdd;">&nbsp;</td>';
						}
					}
				}

				echo '<td>' . (float) number_format( $abs, 1 ) . '</td>
				<td>' . (float) number_format( $pos, 1 ) . '</td></tr>';

				$abs_tot += $abs;
				$pos_tot += $pos;
			}

			if ( $month == 12 && $last_month != 12 )
			{
				// School year over 2 calendar years, reset month to January.
				$month = 0;
				$last_month_tmp = $last_month;
			}
		}

		echo '<tr class="center"><td colspan="32" class="align-right"><b>' .
			_( 'Year to Date Totals' ) . '</b></td>';

		echo '<td>' . (float) number_format( $abs_tot, 1 ) . '</td>
		<td>' . (float) number_format( $pos_tot, 1 ) . '</td></tr>';

		echo '</table></td></tr></table>';

		echo '<div style="page-break-after: always;"></div>';
	}

	PDFStop( $handle );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' . issetVal( $_REQUEST['include_inactive'], '' ) .
			'&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = SubmitButton( _( 'Create Attendance Report for Selected Students' ) );
	}

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];

	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];

	$extra['options']['search'] = false;

	$extra['new'] = true;

	Widgets( 'course' );
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Attendance Report for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
