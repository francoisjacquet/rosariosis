<?php

DrawHeader( ProgramTitle() );

$_REQUEST['delete'] = issetVal( $_REQUEST['delete'], '' );

if ( ! empty( $_REQUEST['mp_arr'] ) )
{
	foreach ( (array) $_REQUEST['mp_arr'] as $mp )
	{
		$mp_list .= ",'" . $mp . "'";
	}

	$mp_list = mb_substr( $mp_list, 1 );
	$last_mp = $mp;
}

if ( $_REQUEST['delete'] === 'true' )
{
	//DeletePrompt(_('Duplicate Attendance Record'));

	if ( ! empty( $_REQUEST['deletecheck'] ) )
	{
		if ( DeletePrompt( _( 'Duplicate Attendance Record' ) ) )
		{
			set_time_limit( 300 );

			$i = 0;
			$ii = 0;
			$iii = 0;
			$sid = issetVal( $_REQUEST['studentidx'] );
			$cnt = issetVal( $_REQUEST['deletecheck'] );
			$pid = issetVal( $_REQUEST['periodidx'] );
			$sdt = issetVal( $_REQUEST['schooldatex'] );

			foreach ( (array) $cnt as $a => $val_dchck )
			{
				$val1 = $val_dchck;

				if ( $val1 >= 0 )
				{
					//echo "$val1 |";

					foreach ( (array) $sid as $b => $val_sid )
					{
						$val2 = $val_sid;

						if ( $val1 == $i )
						{
							//echo "$val2 - $i||| ";

							foreach ( (array) $pid as $c => $val_pid )
							{
								$val3 = $val_pid;

								if ( $val1 == $ii )
								{
									//echo "$val1 - $val2 - $val3 ||| ";

									foreach ( (array) $sdt as $d => $val_sdt )
									{
										$val4 = $val_sdt;

										if ( $val1 == $iii )
										{
											//echo "$val1 - $val2 - $val3 - $val4 ||| ";
											DBQuery( "DELETE FROM attendance_period WHERE STUDENT_ID='" . (int) $val2 . "' AND SCHOOL_DATE='" . $val4 . "' AND COURSE_PERIOD_ID='" . (int) $val3 . "'" );
										}

										$iii++;
									}

									$iii = 0;
								}

								$ii++;
							}

							$ii = 0;
						}

						$i++;
					}

					$i = 0;
				}
			}

			//foreach ( (array) $sid as $b => $val_sid){
			//        $val2 = $val_sid;
			//        echo "$val2| ";
			//}

			$note[] = button( 'check' ) . '&nbsp;' . _( 'The duplicate records have been deleted.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	if ( $note
		|| $error )
	{
		// Unset delete & redirect URL.
		RedirectURL( 'delete' );
	}
}

if ( isset( $_REQUEST['search_modfunc'] )
	&& $_REQUEST['search_modfunc'] === 'list'
	&& $_REQUEST['delete'] !== 'true' )
{
	$RET = GetStuList( $extra );

	if ( isset( $_REQUEST['page'] ) )
	{
		$urlpage = $_REQUEST['page'];
	}
	else
	{
		$urlpage = 1;
	}

	$firstrow = 1;
	$rows_per_page = 25;
	$endrow = $urlpage * $rows_per_page;
	$startrow = $endrow - $rows_per_page;

	//echo "Startrow: $startrow  Endrow: $endrow <br />";

	if ( ! empty( $RET ) )
	{
		unset( $extra );

		$extra['SELECT_ONLY'] = "ap.COURSE_PERIOD_ID,s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		ap.SCHOOL_DATE,cp.TITLE,ap.PERIOD_ID,sc.START_DATE,sc.END_DATE ";

		$extra['FROM'] = " ,attendance_period ap,course_periods cp,schedule sc ";

		//$extra['WHERE'] .= " AND ssm.student_id=s.student_id AND ap.STUDENT_ID=s.STUDENT_ID AND ap.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND ('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL) ";
		//$extra['WHERE'] .= " AND ssm.student_id=s.student_id AND ap.STUDENT_ID=s.STUDENT_ID AND ap.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID ";

		// @since 10.2.1 SQL handle case when student dropped and then later re-enrolled in course.
		$extra['WHERE'] = " AND ap.STUDENT_ID=s.STUDENT_ID
			AND sc.STUDENT_ID=s.STUDENT_ID
			AND ap.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND ap.COURSE_PERIOD_ID=sc.COURSE_PERIOD_ID
			AND sc.END_DATE>'1999-01-01'
			AND NOT EXISTS(SELECT 1 FROM schedule
			WHERE COURSE_PERIOD_ID=sc.COURSE_PERIOD_ID
			AND STUDENT_ID=sc.STUDENT_ID
			AND END_DATE IS NULL) ";

		$extra['ORDER_BY'] = ' STUDENT_ID,COURSE_PERIOD_ID,SCHOOL_DATE';

		$pageresult1 = GetStuList( $extra );

		$totalrows = 0;

		$studentid2 = 0;

		foreach ( (array) $pageresult1 as $rr )
		{
			$afterr = "N";

			$studentidr = $rr['STUDENT_ID'];
			$courseidr = $rr['COURSE_PERIOD_ID'];
			$periodidr = $rr['PERIOD_ID'];
			$full_namer = $rr['FULL_NAME'];
			$schooldater = $rr['SCHOOL_DATE'];
			$titler = $rr['TITLE'];
			$startr = $rr['START_DATE'];
			$endr = $rr['END_DATE'];

			if ( $schooldater > $endr )
			{
				$afterr = "Y";
			}

			if ( $studentid2
				&& $studentidr == $studentid2
				&& $courseidr == $courseid2
				&& $schooldater == $schooldate2
				&& $startr == $start2 )
			{
				$totalrows++;
			}
			elseif (  ( $schooldater > $endr ) && ( $endr != NULL ) && ( $startr == $start2 ) )
			{
				$totalrows++;
			}
			else
			{
				//Do nothing
			}

			$studentid2 = $studentidr;
			$courseid2 = $courseidr;
			$periodid2 = $periodidr;
			$schooldate2 = $schooldater;
			$full_name2 = $full_namer;
			$title2 = $titler;
			$start2 = $startr;
			$end2 = $endr;
		}

		//echo "$totalrows";

		unset( $extra );

		$extra['SELECT_ONLY'] = "ap.COURSE_PERIOD_ID,s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		ap.SCHOOL_DATE,cp.TITLE,cp.SHORT_NAME,ap.PERIOD_ID,sc.START_DATE,sc.END_DATE ";

		$extra['FROM'] = ",attendance_period ap,course_periods cp,schedule sc ";

		//$extra['WHERE'] .= " AND ssm.student_id=s.student_id AND ap.STUDENT_ID=s.STUDENT_ID AND ap.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND ('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL) ";
		//$extra['WHERE'] .= " AND ssm.student_id=s.student_id AND ap.STUDENT_ID=s.STUDENT_ID AND ap.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID ";

		// @since 10.2.1 SQL handle case when student dropped and then later re-enrolled in course.
		$extra['WHERE'] = " AND ap.STUDENT_ID=s.STUDENT_ID
			AND sc.STUDENT_ID=s.STUDENT_ID
			AND ap.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND ap.COURSE_PERIOD_ID=sc.COURSE_PERIOD_ID
			AND sc.END_DATE>'1999-01-01'
			AND NOT EXISTS(SELECT 1 FROM schedule
			WHERE COURSE_PERIOD_ID=sc.COURSE_PERIOD_ID
			AND STUDENT_ID=sc.STUDENT_ID
			AND END_DATE IS NULL) ";

		$extra['ORDER_BY'] = ' STUDENT_ID,COURSE_PERIOD_ID,SCHOOL_DATE';

		$result1 = GetStuList( $extra );

		echo ErrorMessage( $error );

		echo ErrorMessage( $note, 'note' );

		echo '<form action="' . URLEscape( 'Modules.php?modname=Attendance/DuplicateAttendance.php&modfunc=&search_modfunc=list&next_modname=Attendance/DuplicateAttendance.php&delete=true' ) . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Delete' ) ) );

		$num_rows = $totalrows;

		if ( $num_rows > $rows_per_page )
		{
			$totalpages = $num_rows / $rows_per_page;
			$totalpages = ceil( $totalpages );

			echo '<br />';
			echo '<div class="center">' . _( 'Page' ) . ': ';
			$first = 0;
			$ii = 1;

			for ( $i = 0; $i < $totalpages; $i++ )
			{
				if ( $urlpage == $ii )
				{
					echo '<b>' . $ii . '</b> &nbsp;';
				}
				else
				{
					echo '<a href="Modules.php?modname=Attendance/DuplicateAttendance.php&modfunc=&search_modfunc=list&next_modname=Attendance/DuplicateAttendance.php&delete=false&page=' . $ii . '">' . $ii . '</a> &nbsp;';
				}

				$first = $first + $rows_per_page;
				$ii++;
			}

			echo sprintf( _( 'of %d pages.' ), $totalpages );
			echo '</div>';
		}

		echo '<br /><table class="widefat rt center">';
		echo '<thead><tr><th class="column_heading"><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'deletecheck\');" /> &nbsp</th>';

		echo '<th>' . _( 'Student' ) . ' (' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) . ')</th>
			<th>' . _( 'Course' ) . ' (' . _( 'Course Period ID' ) . ')</th>
			<th>' . _( 'Course Start Date' ) . '</th>
			<th>' . _( 'Course End Date' ) . '</th>
			<th>' . _( 'Attendance Date' ) . '</th></tr></thead><tbody>';

		$URIcount = 0;
		$count = 0;
		$yellow = 1;
		$after = "N";

		foreach ( (array) $result1 as $r )
		{
			$after = "N";

			$studentid = $r['STUDENT_ID'];
			$courseid = $r['COURSE_PERIOD_ID'];
			$periodid = $r['PERIOD_ID'];
			$full_name = $r['FULL_NAME'];
			$schooldate = $r['SCHOOL_DATE'];
			$title = $r['TITLE'];
			$short_name = $r['SHORT_NAME'];
			$start = $r['START_DATE'];
			$end = $r['END_DATE'];

			if ( $schooldate > $end )
			{
				$after = "Y";
			}

			if (  ( $studentid == $studentid2 ) && ( $courseid == $courseid2 ) && ( $schooldate == $schooldate2 ) && ( $start == $start2 ) )
			{
				$URIcount++;
				//echo "$URIcount | ";

				if ( $URIcount > $startrow && $URIcount < $endrow )
				{
					echo '<input type="hidden" name="delete" value="true">
						<input type="hidden" name="studentidx[' . $count . ']" value="' . AttrEscape( $studentid ) . '">
						<input type="hidden" name="periodidx[' . $count . ']" value="' . AttrEscape( $courseid ) . '">
						<input type="hidden" name="schooldatex[' . $count . ']" value="' . AttrEscape( $schooldate ) . '">';

					if ( $yellow == 0 )
					{
						$color = 'F8F8F9';
						$yellow++;
					}
					else
					{
						$color = Preferences( 'COLOR' );
						$yellow = 0;
					}

					echo '<tr>
						<td><input type="checkbox" name="deletecheck[' . $count . ']" value="' . AttrEscape( $count ) . '"></td>
						<td>' . $full_name . ' (' . $studentid . ')</td>
						<td>' . $short_name . ' (' . $courseid . ')</td>
						<td>' . ProperDate( $start ) . '</td>
						<td>' . ProperDate( $end ) . '</td>
						<td>' . ProperDate( $schooldate ) . '</td>
					</tr>';

					$count++;
				}
			}
			elseif (  ( $schooldate > $end ) && ( $end != NULL ) && ( $start == $start2 ) )
			{
				$URIcount++;
				//echo "$URIcount | ";

				if ( $URIcount > $startrow && $URIcount < $endrow )
				{
					echo '<input type="hidden" name="delete" value="true">
						<input type="hidden" name="studentidx[' . $count . ']" value="' . AttrEscape( $studentid ) . '">
						<input type="hidden" name="periodidx[' . $count . ']" value="' . AttrEscape( $courseid ) . '">
						<input type="hidden" name="schooldatex[' . $count . ']" value="' . AttrEscape( $schooldate ) . '">';

					if ( $yellow == 0 )
					{
						$color = 'F8F8F9';
						$yellow++;
					}
					else
					{
						$color = Preferences( 'COLOR' );
						$yellow = 0;
					}

					echo '<tr>
						<td><input type="checkbox" name="deletecheck[' . $count . ']" value="' . AttrEscape( $count ) . '"></td>
						<td>' . $full_name . ' (' . $studentid . ')</td>
						<td>' . $short_name . ' (' . $courseid . ')</td>
						<td>' . ProperDate( $start ) . '</td>
						<td>' . ProperDate( $end ) . '</td>
						<td>' . ProperDate( $schooldate ) . '</td>
					</tr>';

					$count++;
				}
			}
			else
			{
				//echo "<tr><td>$studentid</td><td>$courseid</td></tr>";
				$duplicate = 0;
			}

			$studentid2 = $studentid;
			$courseid2 = $courseid;
			$periodid2 = $periodid;
			$schooldate2 = $schooldate;
			$full_name2 = $full_name;
			$title2 = $title;
			$start2 = $start;
			$end2 = $end;
			//echo "<tr><td>$studentid</td><td>$courseid</td></tr>";
			//echo "$studentid | $courseid";
		}

		if ( $count == 0 )
		{
			echo '<tr><td colspan="6"><b>' . _( 'No Duplicates Found' ) . '</b></td></tr>';
			echo '</tbody></table>';
		}
		else
		{
			echo '</tbody></table>';
			echo '<br /><div class="center">' . SubmitButton( _( 'Delete' ) ) . '</div>';
		}

		echo '</form>';
		$RET = " ";
	}
	else
	{
		// Unset search modfunc & redirect URL.
		RedirectURL( 'search_modfunc' );

		$error[] = _( 'No Students were found.' );
	}
}

if ( ! $_REQUEST['search_modfunc']
	&& $_REQUEST['delete'] !== 'true' )
{
	echo ErrorMessage( $error );

	$extra['new'] = true;
	Search( 'student_id', $extra );
}

/**
 * @param $teacher
 * @param $column
 */
function _makeTeacher( $teacher, $column )
{
	return mb_substr( $teacher, mb_strrpos( str_replace( ' - ', ' ^ ', $teacher ), '^' ) + 2 );
}
