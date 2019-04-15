<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$months = array(
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
		);

		// Check Social Security + Gender fields exists before adding them to SELECT.
		$custom_RET = DBGet( "SELECT TITLE,ID
			FROM CUSTOM_FIELDS
			WHERE ID IN ('200000000','200000003')", array(), array( 'ID' ) );

		$extra['SELECT'] = ",ssm.CALENDAR_ID,ssm.START_DATE,ssm.END_DATE";

		foreach ( (array) $custom_RET as $id => $custom )
		{
			$extra['SELECT'] .= ",CUSTOM_" . $id;
		}

		// ACTIVE logic taken from GetStuList().
		$active = "'" . DBEscapeString( _( 'Active' ) ) . "'";
		$inactive = "'" . DBEscapeString( _( 'Inactive' ) ) . "'";

		$extra['SELECT'] .= ',' . db_case( array(
			"(ssm.SYEAR='" . UserSyear() . "' AND ('" . DBDate() . "'>=ssm.START_DATE AND ('" . DBDate() . "'<=ssm.END_DATE OR ssm.END_DATE IS NULL)))",
			'TRUE',
			$active,
			$inactive,
		) ) . ' AS STATUS';

		$RET = GetStuList( $extra );

		if ( ! empty( $RET ) )
		{
			//change orientation to landscape
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
				$calendar_RET = DBGet( "SELECT CASE WHEN
					MINUTES>=" . Config( 'ATTENDANCE_FULL_DAY_MINUTES' ) .
							" THEN '1.0' ELSE '0.5' END AS POS,
					trim(leading '0' from to_char(SCHOOL_DATE,'MM')) AS MON,
					trim(leading '0' from to_char(SCHOOL_DATE,'DD')) AS DAY
					FROM ATTENDANCE_CALENDAR
					WHERE CALENDAR_ID='" . $student['CALENDAR_ID'] . "'
					AND SCHOOL_DATE>='" . $student['START_DATE'] . "'" .
					( $student['END_DATE'] ? " AND SCHOOL_DATE<='" . $student['END_DATE'] . "'" : '' ),
					array(),
					array( 'MON', 'DAY' ) );

				$attendance_RET = DBGet( "SELECT
					trim(leading '0' from to_char(ap.SCHOOL_DATE,'MM')) AS MON,
					trim(leading '0' from to_char(ap.SCHOOL_DATE,'DD')) AS DAY,
					ac.STATE_CODE,
					ac.SHORT_NAME
					FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac,SCHOOL_PERIODS sp
					WHERE ap.STUDENT_ID='" . $student['STUDENT_ID'] . "'
					AND ap.PERIOD_ID=sp.PERIOD_ID
					AND sp.SCHOOL_ID='" . UserSchool() . "'
					AND sp.SYEAR='" . UserSyear() . "'
					AND ac.ID=ap.ATTENDANCE_CODE
					AND sp.ATTENDANCE='Y'", array(), array( 'MON', 'DAY' ) );
				//echo '<pre>'; var_dump($calendar_RET); echo '</pre>';

				echo '<table class="width-100p"><tr><td class="center">
				<h2>' . $student['FULL_NAME'] . '</h2>
				</td></tr>';

				echo '<tr><td style="border: solid 1px"><table class="width-100p">
				<tr class="center"><td>
				<b>' . _( 'Student Name' ) . '</b>
				</td><td>
				<b>' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) . '</b></td>
				<td>
				<b>' . _( 'School' ) . ' / ' . _( 'Year' ) . '</b>
				</td></tr>';

				//FJ school year over one/two calendar years format
				echo '<tr class="center"><td>' . $student['FULL_NAME'] . '</td>
				<td>' . $student['STUDENT_ID'] . '</td>
				<td>' . SchoolInfo( 'SCHOOL_NUMBER' ) .
				' / ' . FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) .
				'</td></tr>';

				echo '<tr><td colspan="3">
				<h3>' . _( 'Demographics' ) . '</h3>
				<table class="width-100p cellspacing-0 center"><tr>';

				foreach ( (array) $custom_RET as $id => $custom )
				{
					echo '<td class="align-right">' . ParseMLField( $custom_RET[$id][1]['TITLE'] ) . ':&nbsp;</td>
					<td>' . $student['CUSTOM_' . $id] . '</td>';
				}

				echo '</tr><tr>
				<td class="align-right">' . _( 'Status' ) . ':&nbsp;</td>
				<td>' . $student['STATUS'] . '</td>
				<td class="align-right">' . _( 'Grade Level' ) . ':&nbsp;</td>
				<td>' . $student['GRADE_ID'] . '</td>
				</tr>
				</table>
				</td></tr>';

				echo '<tr><td colspan="3">
				<h3>' . _( 'Attendance' ) . '</h3>
				<table style="border:solid 1px;" class="width-100p cellspacing-0 center">';

				echo '<tr class="center"><td colspan="32"></td>
				<td colspan="2"><b>' . _( 'Month to Date' ) . '</b></td>
				</tr>';

				echo '<tr class="center"><td>
				<b>' . _( 'Month' ) . '</b>
				</td>';

				for ( $day = 1; $day <= 31; $day++ )
				{
					echo '<td><b>' . ( $day < 10 ? '&nbsp;' : '' ) . $day . '</b></td>';
				}

				echo '<td><b>' . _( 'Absences' ) . '</b></td>
				<td><b>' . _( 'Possible' ) . '</b></td>
				</tr>';

				$abs_tot = $pos_tot = 0;

				$FY_dates = DBGet( "SELECT START_DATE,END_DATE
				FROM SCHOOL_MARKING_PERIODS
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

				for ( $month = $first_month; $month <= $last_month_tmp; $month++ )
				{
					if ( $calendar_RET[$month] || $attendance_RET[$month] )
					{
						echo '<tr><td>' . $months[$month] . '</td>';

						$abs = $pos = 0;

						for ( $day = 1; $day <= 31; $day++ )
						{
							if ( $calendar_RET[$month][$day] )
							{
								$calendar = $calendar_RET[$month][$day][1];

								if ( $attendance_RET[$month][$day] )
								{
									$attendance = $attendance_RET[$month][$day][1];

									echo '<td class="center">' . $attendance['STATE_CODE'] . '</td>';

									$abs += ( $attendance['STATE_CODE'] == 'A' ?
										$calendar['POS'] :
										( $attendance['STATE_CODE'] == 'H' ? $calendar['POS'] / 2 : 0 )
									);
								}
								else
								//green box
								{
									echo '<td class="center" style="background-color:#DDFFDD;">&nbsp;</td>';
								}

								$pos += $calendar['POS'];
							}
							else
							{
								// Attendance record before attendance start date!

								if ( $attendance_RET[$month][$day] )
								{
									$attendance = $attendance_RET[$month][$day][1];

									//red box
									echo '<td class="center" style="background-color:#e80000;">' . $attendance['STATE_CODE'] . '</td>';
								}
								else
								//pink box
								{
									echo '<td class="center" style="background-color:#FFDDDD;">&nbsp;</td>';
								}
							}
						}

						echo '<td class="center">' . number_format( $abs, 1 ) . '</td>
						<td class="center">' . number_format( $pos, 1 ) . '</td></tr>';

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

				echo '<tr><td colspan="32" class="align-right"><b>' . _( 'Year to Date Totals' ) . ':</b></td>';

				echo '<td class="center">' . number_format( $abs_tot, 1 ) . '</td>
				<td class="center">' . number_format( $pos_tot, 1 ) . '</td></tr>';

				echo '</table>
				</td></tr>
				</table>
				</td><tr>
				</table>';

				echo '<div style="page-break-after: always;"></div>';
			}

			PDFStop( $handle );
		}
		else
		{
			BackPrompt( _( 'No Students were found.' ) );
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '&_ROSARIO_PDF=true" method="POST">';

		$extra['header_right'] = SubmitButton( _( 'Create Attendance Report for Selected Students' ) );
	}

	$extra['link'] = array( 'FULL_NAME' => false );

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );

	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );

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
