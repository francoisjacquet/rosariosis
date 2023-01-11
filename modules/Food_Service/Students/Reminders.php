<?php
$food_service_config = ProgramConfig( 'food_service' );

// if $homeroom is null then teacher and subject for period used for attendance are used for homeroom teacher and subject
// if $homeroom is set then teacher for $homeroom subject and $homeroom are used for teacher and subject
//$homeroom = 'Homeroom';
$target = $food_service_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'];
$warning = $food_service_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'];
// Available substitutions for the notes...
// %N = student firstname (given) or nickname (@deprecated) (according to user preference)
// %F = student firstname @deprecated Not used
// %g = he/she according to student gender
// %G = He/She according to student gender @deprecated Not used
// %h = his/her according to student gender
// %H = His/Her according to student gender @deprecated Not used
// %P = payment amount
// %T = balance target amount
$warning_note = _( '%N\'s lunch account is getting low.  Please send in at least %P with %h reminder slip.  THANK YOU!' );
$negative_note = _( '%N now has a <b>negative balance</b> in %h lunch account. Please send in the negative balance plus %T.  THANK YOU!' );
$minimum = $food_service_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'];
$minimum_note = _( '%N now has a <b>negative balance</b> below the allowed minimum.  Please send in the negative balance plus %T.  THANK YOU!' );
$year_end_note = _( '%N\'s lunch account is getting low.  It\'s estimated that %g needs about a %T current balance to finish the year with a zero balance.  Please send in the requested amount with %h reminder slip.  THANK YOU!' );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN(" . $st_list . ")
			AND fsa.STUDENT_ID=s.STUDENT_ID";

		$extra['SELECT'] = ",fsa.ACCOUNT_ID,fsa.STATUS,(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=fsa.ACCOUNT_ID LIMIT 1) AS BALANCE";

		if ( isset( $_REQUEST['year_end'] )
			&& $_REQUEST['year_end'] === 'Y' )
		{
			$extra['SELECT'] .= ",(SELECT count(1)
				FROM attendance_calendar
				WHERE CALENDAR_ID=ssm.CALENDAR_ID
				AND SCHOOL_DATE>CURRENT_DATE) AS DAYS,
			(SELECT -sum(fsti.AMOUNT)
				FROM food_service_transactions fst,food_service_transaction_items fsti
				WHERE fst.SYEAR=ssm.SYEAR
				AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID
				AND fst.ACCOUNT_ID=fsa.ACCOUNT_ID
				AND fsti.AMOUNT<0
				AND fst.TIMESTAMP BETWEEN (CURRENT_DATE - INTERVAL " .
				( $DatabaseType === 'mysql' ? '14 DAY' : "'14 DAY'" ) . ")
				AND (CURRENT_DATE - INTERVAL " .
				( $DatabaseType === 'mysql' ? '1 DAY' : "'1 DAY'" ) . ")) AS T_AMOUNT,
			(SELECT count(1)
				FROM attendance_calendar
				WHERE CALENDAR_ID=ssm.CALENDAR_ID
				AND SCHOOL_DATE BETWEEN (CURRENT_DATE - INTERVAL " .
				( $DatabaseType === 'mysql' ? '14 DAY' : "'14 DAY'" ) . ")
				AND (CURRENT_DATE - INTERVAL " .
				( $DatabaseType === 'mysql' ? '1 DAY' : "'1 DAY'" ) . ")) AS T_DAYS";
		}

		$extra['FROM'] = ",food_service_student_accounts fsa";

		$students = GetStuList( $extra );

		$handle = PDFStart();

		$reminders_count = 0;

		foreach ( (array) $students as $student )
		{
			$payment = $target - $student['BALANCE'];

			if ( isset( $_REQUEST['year_end'] )
				&& $_REQUEST['year_end'] === 'Y' )
			{
				$payment = floor( $payment * 2 + 0.99 ) / 2;
			}

			if ( $payment <= 0 )
			{
				continue;
			}

			if ( $student['BALANCE'] < $minimum )
			{
				$note = $minimum_note;
			}
			elseif ( $student['BALANCE'] < 0 )
			{
				$note = $negative_note;
			}
			elseif ( $student['BALANCE'] < $warning )
			{
				$note = $warning_note;
			}
			else
			{
				continue;
			}

			if ( $reminders_count++ % 3 === 0 )
			{
				// 3 per page, insert page break.
				echo '<div style="page-break-after: always;"></div>';
			}

			if ( ! empty( $homeroom ) )
			{
				$teacher = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,cs.TITLE
				FROM staff s,schedule sch,course_periods cp,courses c,course_subjects cs
				WHERE s.STAFF_ID=cp.TEACHER_ID
				AND sch.STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'
				AND cp.COURSE_ID=sch.COURSE_ID
				AND c.COURSE_ID=cp.COURSE_ID
				AND c.SUBJECT_ID=cs.SUBJECT_ID
				AND cs.TITLE='" . $homeroom . "'
				AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
				AND sch.SYEAR='" . UserSyear() . "'" );
			}
			else
			{
				//FJ multiple school periods for a course period
				/*$teacher = DBGet( "SELECT " . DisplayNameSQL( 's' ) . "  AS FULL_NAME,cs.TITLE
				FROM staff s,schedule sch,course_periods cp,courses c,course_subjects cs,school_periods sp
				WHERE s.STAFF_ID=cp.TEACHER_ID AND sch.STUDENT_ID='".$student['STUDENT_ID']."' AND cp.COURSE_ID=sch.COURSE_ID AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID=cs.SUBJECT_ID AND sp.PERIOD_ID=cp.PERIOD_ID AND sp.ATTENDANCE='Y' AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND sch.SYEAR='".UserSyear()."'" );*/
				// SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL.
				$teacher = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,cs.TITLE
				FROM staff s,schedule sch,course_periods cp,courses c,course_subjects cs,school_periods sp,course_period_school_periods cpsp
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND cp.DOES_ATTENDANCE IS NOT NULL
				AND s.STAFF_ID=cp.TEACHER_ID
				AND sch.STUDENT_ID='" . (int) $student['STUDENT_ID'] . "'
				AND cp.COURSE_ID=sch.COURSE_ID
				AND c.COURSE_ID=cp.COURSE_ID
				AND c.SUBJECT_ID=cs.SUBJECT_ID
				AND sp.PERIOD_ID=cpsp.PERIOD_ID
				AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
				AND sch.SYEAR='" . UserSyear() . "'" );
			}

			$student['TEACHER'] = ! empty( $teacher[1]['FULL_NAME'] ) ?
				$teacher[1]['FULL_NAME'] : '';

			$xstudents = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
			FROM students s,food_service_student_accounts fssa
			WHERE fssa.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
			AND s.STUDENT_ID=fssa.STUDENT_ID
			AND s.STUDENT_ID!='" . (int) $student['STUDENT_ID'] . "'
			AND exists(SELECT ''
				FROM student_enrollment
				WHERE STUDENT_ID=s.STUDENT_ID
				AND SYEAR='" . UserSyear() . "'
				AND (START_DATE<=CURRENT_DATE AND (END_DATE IS NULL OR CURRENT_DATE<=END_DATE)))" );

			// @since 9.3 SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility
			$last_deposit = DBGet( "SELECT (SELECT sum(AMOUNT)
				FROM food_service_transaction_items
				WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
				CAST(fst.TIMESTAMP AS char(10)) AS DATE
			FROM food_service_transactions fst
			WHERE fst.SHORT_NAME='DEPOSIT'
			AND fst.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY fst.TRANSACTION_ID DESC LIMIT 1", [ 'DATE' => 'ProperDate' ] );

			$last_deposit = ! empty( $last_deposit[1] ) ?
				$last_deposit[1] : [];

			if ( isset( $_REQUEST['year_end'] )
				&& $_REQUEST['year_end'] === 'Y' )
			{
				// Fix Transactions amount for the lat 14 days maybe null.
				// Set a minimum of 0.99.
				$student['T_AMOUNT'] = empty( $student['T_AMOUNT'] ) ? '0.99' : $student['T_AMOUNT'];

				$xtarget = $student['DAYS'] * $student['T_AMOUNT'] / $student['T_DAYS'];
			}
			else
			{
				$xtarget = $target * ( count( $xstudents ) + 1 );
			}

			FoodServiceReminderOutput( $student, $xtarget, $last_deposit, $payment, $note, $xstudents );

			if ( $reminders_count % 3 !== 0 )
			{
				// 3 per page, insert spaces & horizontal ruler.
				echo '<br /><br /><hr><br /><br />';
			}
		}

		PDFStop( $handle );
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) . '" method="POST">';
		//DrawHeader('',SubmitButton('Create Reminders for Selected Students'));
		//FJ add translation
		$extra['header_right'] = SubmitButton( _( 'Create Reminders for Selected Students' ) );

		$extra['extra_header_left'] = '<label><input type="checkbox" name="year_end" value="Y" />&nbsp;' . _( 'Estimate for year end' ) . '</label>';
	}

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => '_makeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];
	$extra['new'] = true;
	$extra['options']['search'] = false;

	Widgets( 'fsa_balance_warning' );
	Widgets( 'fsa_status' );

	$status = DBEscapeString( _( 'Active' ) );

	// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
	$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . $status . "') AS STATUS,fsa.BALANCE
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < '" . $warning . "' AND fsa.BALANCE >= 0) AS WARNING
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < 0 AND fsa.BALANCE >= '" . $minimum . "') AS NEGATIVE
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < '" . $minimum . "') AS MINIMUM";

	if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
	{
		$extra['FROM'] .= ',food_service_student_accounts fssa';
		$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID';
	}

	if ( ! mb_strpos( $extra['FROM'], 'fsa' ) )
	{
		$extra['FROM'] .= ',food_service_accounts fsa';
		$extra['WHERE'] .= ' AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID';
	}

	$extra['functions'] += [
		'BALANCE' => 'red',
		'WARNING' => 'x',
		'NEGATIVE' => 'x',
		'MINIMUM' => 'x',
	];

	$extra['columns_after'] = [
		'BALANCE' => _( 'Balance' ),
		'STATUS' => _( 'Status' ),
		'WARNING' => _( 'Warning' ) . ' &lt;' . $warning,
		'NEGATIVE' => _( 'Negative' ),
		'MINIMUM' => _( 'Minimum' ) . ' ' . $minimum,
	];

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Reminders for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
