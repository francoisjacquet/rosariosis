<?php

if ( $_REQUEST['modfunc'] === 'redirect_take_attendance' )
{
	_redirectTakeAttendance();
}

DrawHeader( ProgramTitle() );

DrawHeader( '<span id="salute"></span>' );

?>
<script>
var hours = new Date().getHours(),
	salute = document.getElementById("salute");
	if (hours < 12)
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Morning, %s.' ), User( 'NAME' ) ) ); ?>;
	else if (hours < 18)
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Afternoon, %s.' ), User( 'NAME' ) ) ); ?>;
	else
		salute.innerHTML=<?php echo json_encode( sprintf( _( 'Good Evening, %s.' ), User( 'NAME' ) ) ); ?>;
</script>
<?php

$welcome = '';

if ( ! empty( $_SESSION['LAST_LOGIN'] ) )
{
	$welcome .= sprintf(
		_( 'Your last login was <b>%s</b>.' ),
		ProperDateTime( $_SESSION['LAST_LOGIN'] )
	);
}

if ( ! empty( $failed_login ) )
{
	$welcome .= ErrorMessage(
		array( sprintf(
			_( 'There have been <b>%d</b> failed login attempts since your last successful login.' ),
			$failed_login
		) ),
		'warning'
	);
}

switch ( User( 'PROFILE' ) )
{
	case 'admin':
		$welcome .= '<br />' . _( 'You are an <b>Administrator</b> on the system.' );

		break;

	case 'teacher':
		$welcome .= '<br />' . _( 'You are a <b>Teacher</b> on the system.' );

		break;

	case 'parent':
		$welcome .= '<br />' . _( 'You are a <b>Parent</b> on the system.' );

		break;

	default:

		$welcome .= '<br />' . _( 'You are a <b>Student</b> on the system.' );
}

DrawHeader( $welcome );

if ( $RosarioModules['Discipline'] )
{
	// Discipline alerts.
	require_once 'modules/Discipline/includes/PortalAlerts.fnc.php';
}

if ( $RosarioModules['Student_Billing'] )
{
	// @since 5.4 Student Billing alerts.
	require_once 'modules/Student_Billing/includes/PortalAlerts.fnc.php';
}

// Do portal_alerts hook.
do_action( 'misc/Portal.php|portal_alerts' );

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $warning, 'warning' );

require_once 'ProgramFunctions/Dashboard.fnc.php';

// Dashboard.
DashboardOutput();

$portal_LO_options = array( 'save' => false, 'search' => false );

$notes_LO_columns = array(
	'PUBLISHED_DATE' => _( 'Date Posted' ),
	'TITLE' => _( 'Title' ),
	'CONTENT' => _( 'Note' ),
	'FILE_ATTACHED' => _( 'File Attached' ),
);

$polls_LO_columns = array(
	'PUBLISHED_DATE' => _( 'Date Posted' ),
	'TITLE' => _( 'Title' ),
	'OPTIONS' => _( 'Poll' ),
);

$events_LO_columns = array(
	'DAY' => _( 'Day' ),
	'SCHOOL_DATE' => _( 'Date' ),
	'TITLE' => _( 'Event' ),
	'DESCRIPTION' => _( 'Description' ),
);

$missing_attendance_LO_columns = array(
	'SCHOOL_DATE' => _( 'Date' ),
	'TITLE' => _( 'Period' ) . ' ' . _( 'Days' ) . ' - ' . _( 'Short Name' ) . ' - ' . _( 'Teacher' ),
);

if ( User( 'PROFILE' ) !== 'student'
	|| SchoolInfo( 'SCHOOLS_NB' ) > 1 )
{
	// More than 1 school, display School column.
	$notes_LO_columns['SCHOOL'] = _( 'School' );

	$polls_LO_columns['SCHOOL'] = _( 'School' );

	$events_LO_columns['SCHOOL'] = _( 'School' );
}

if ( User( 'PROFILE' ) === 'admin'
	&& SchoolInfo( 'SCHOOLS_NB' ) > 1 )
{
	$missing_attendance_LO_columns['SCHOOL'] = _( 'School' );
}

$assignments_LO_columns = array(
	// 'DAY' => _( 'Day' ),
	'DUE_DATE' => _( 'Due Date' ),
	'ASSIGNMENT_TITLE' => _( 'Assignment' ),
	// 'DESCRIPTION' => _( 'Notes' ),
	'COURSE' => _( 'Course' ),
);

if ( User( 'PROFILE' ) === 'student'
	|| User( 'PROFILE' ) === 'parent' )
{
	// Student or Parent, add Teacher & Submitted columns.
	$assignments_LO_columns['STAFF_ID'] = _( 'Teacher' );
	$assignments_LO_columns['SUBMITTED'] = _( 'Submitted' );
}

// @todo Create functions & put in misc/includes/Portal.fnc.php.
switch ( User( 'PROFILE' ) )
{
	case 'admin':
		$PHPCheck = PHPCheck();

		if ( ! empty( $PHPCheck ) )
		{
			echo ErrorMessage( $PHPCheck, 'warning' );
		}

		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
//FJ file attached to portal notes
		//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pn.TITLE||'</b>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC",array('PUBLISHED_DATE' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pn.TITLE||'</b>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC",
			array(
				'PUBLISHED_DATE' => 'ProperDate',
				'CONTENT' => 'makeTextarea',
				'FILE_ATTACHED' => 'makeFileAttached',
			) );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				array(),
				array(),
				$portal_LO_options
			);
		}

		//FJ Portal Polls
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pp.TITLE||'</b>' AS TITLE,'options' AS OPTIONS,pp.ID
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',admin,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ) );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				array(),
				array(),
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE AS SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL
		FROM CALENDAR_EVENTS ce,SCHOOLS s,STAFF st
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE
		AND CURRENT_DATE+11
		AND ce.SYEAR='" . UserSyear() . "'
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||ce.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", array(
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		) );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				array(),
				array(),
				$portal_LO_options
			);
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y'
			&& AllowEdit( 'School_Setup/Rollover.php' ) )
		{
			// Add Do Rollover warning when School Year has ended.
			$do_rollover = DBGetOne( "SELECT 1 AS DO_ROLLOVER
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND MP='FY'
				AND END_DATE<'" . DBDate() . "'
				AND NOT EXISTS(SELECT 1
					FROM SCHOOL_MARKING_PERIODS
					WHERE SYEAR='" . ( UserSyear() + 1 ) . "')" );

			if ( $do_rollover )
			{
				$do_rollover_warning = array(
					sprintf(
						_( 'The school year has ended. It is time to proceed to %s.' ),
						'<a href="Modules.php?modname=School_Setup/Rollover.php">' . _( 'Rollover' ) . '</a>'
					)
				);

				echo ErrorMessage( $do_rollover_warning, 'warning' );
			}
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// Warn if missing attendances.
			$categories_RET = DBGet( "SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION
			SELECT ID,TITLE,1,SORT_ORDER
			FROM ATTENDANCE_CODE_CATEGORIES
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY 3,SORT_ORDER" );

			foreach ( (array) $categories_RET as $category )
			{
				// FJ days numbered.
				// FJ multiple school periods for a course period.

				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,
					s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE,
					'" . $category['ID'] . "' AS CATEGORY_ID,
					cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID
				FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp
				WHERE EXISTS(SELECT 1
					FROM SCHEDULE se
					WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
					AND se.SYEAR='" . UserSyear() . "'
					AND acc.SCHOOL_DATE>=se.START_DATE
					AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE))
				AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND acc.SYEAR='" . UserSyear() . "'
				AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
				AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0)
				AND cp.SCHOOL_ID=acc.SCHOOL_ID
				AND cp.SYEAR=acc.SYEAR
				AND acc.SCHOOL_DATE<'" . DBDate() . "'
				AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID
				AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
					(SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
					FROM attendance_calendar
					WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=acc.SCHOOL_ID)
					AND school_date<=acc.SCHOOL_DATE
					AND SCHOOL_ID=acc.SCHOOL_ID)
				AS INT) FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='" . $category['ID'] . "')
				AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
				AND s.ID=acc.SCHOOL_ID
				AND s.SYEAR=acc.SYEAR
				ORDER BY cp.TITLE,acc.SCHOOL_DATE", array( 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ), array( 'COURSE_PERIOD_ID' ) );
				}
				else
				{
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,
					s.TITLE AS SCHOOL,acc.SCHOOL_DATE,cp.TITLE,
					'" . $category['ID'] . "' AS CATEGORY_ID,
					cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID
				FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp,SCHOOLS s,STAFF st, COURSE_PERIOD_SCHOOL_PERIODS cpsp
				WHERE EXISTS(SELECT 1
					FROM SCHEDULE se
					WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
					AND se.SYEAR='" . UserSyear() . "'
					AND acc.SCHOOL_DATE>=se.START_DATE
					AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE))
				AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND acc.SYEAR='" . UserSyear() . "'
				AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
				AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND (st.SCHOOLS IS NULL OR position(','||acc.SCHOOL_ID||',' IN st.SCHOOLS)>0)
				AND cp.SCHOOL_ID=acc.SCHOOL_ID
				AND cp.SYEAR=acc.SYEAR
				AND acc.SCHOOL_DATE<'" . DBDate() . "'
				AND cp.CALENDAR_ID=acc.CALENDAR_ID
				AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
				AND sp.PERIOD_ID=cpsp.PERIOD_ID
				AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
				AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='" . $category['ID'] . "')
				AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
				AND s.ID=acc.SCHOOL_ID
				AND s.SYEAR=acc.SYEAR
				ORDER BY cp.TITLE,acc.SCHOOL_DATE", array( 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ), array( 'COURSE_PERIOD_ID' ) );
				}

				if ( $missing_attendance_RET )
				{
					echo ErrorMessage( array( _( 'Teachers have missing attendance data' ) ), 'warning' );

					ListOutput(
						$missing_attendance_RET,
						$missing_attendance_LO_columns,
						'Course Period with missing attendance data',
						'Course Periods with missing attendance data',
						array(),
						array( 'COURSE_PERIOD_ID' ),
						$portal_LO_options
					);
				}
			}
		}

		if ( $RosarioModules['Food_Service']
			&& Preferences( 'HIDE_ALERTS' ) !== 'Y' )
		{
			$FS_config = ProgramConfig( 'food_service' );

			// warn if negative food service balance
			$staff = DBGet( "SELECT (SELECT STATUS
					FROM FOOD_SERVICE_STAFF_ACCOUNTS
					WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE
					FROM FOOD_SERVICE_STAFF_ACCOUNTS
					WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM STAFF s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );

			$staff = $staff[1];

			if ( $staff['BALANCE']
				&& $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage(
					array( sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), $staff['BALANCE'] ) ),
					'warning'
				);
			}

			// warn if students with food service balances below minimum
			$extra = array();
			$extra['SELECT'] = ',fssa.STATUS,fsa.BALANCE';
			$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
			$extra['WHERE'] = " AND fssa.STUDENT_ID=s.STUDENT_ID
				AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
				AND fssa.STATUS IS NULL
				AND fsa.BALANCE<'" . (float) $FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'] . "'";

			$_REQUEST['_search_all_schools'] = 'Y';

			$RET = GetStuList( $extra );

			if ( $RET )
			{
				echo ErrorMessage( array( sprintf( _( 'Some students have food service balances below %1.2f' ), $FS_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'] ) ), 'warning' );

				ListOutput(
					$RET,
					array(
						'FULL_NAME' => _( 'Student' ),
						'GRADE_ID' => _( 'Grade Level' ),
						'BALANCE' => _( 'Balance' ),
					),
					'Student',
					'Students',
					array(),
					array(),
					$portal_LO_options
				);
			}
		}

		echo '<p>&nbsp;' . _( 'Happy administrating...' ) . '</p>';
		break;

	case 'teacher':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pn.TITLE||'</b>' AS TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC",array('PUBLISHED_DATE' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pn.TITLE||'</b>' AS TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ) );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				array(),
				array(),
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pp.TITLE||'</b>' AS TITLE,'options' AS OPTIONS,pp.ID
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',teacher,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ) );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				array(),
				array(),
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.DESCRIPTION,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,s.TITLE AS SCHOOL
		FROM CALENDAR_EVENTS ce,SCHOOLS s
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE
		AND CURRENT_DATE+11
		AND ce.SYEAR='" . UserSyear() . "'
		AND position(','||ce.SCHOOL_ID||',' IN (SELECT SCHOOLS FROM STAFF WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'))>0
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", array(
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		) );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				array(),
				array(),
				$portal_LO_options
			);
		}

		require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

		// FJ Portal Assignments.
		$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
			a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
			c.TITLE AS COURSE,a.MARKING_PERIOD_ID
		FROM GRADEBOOK_ASSIGNMENTS a,COURSES c
		WHERE (a.COURSE_ID=c.COURSE_ID
		OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
		AND a.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
		AND a.DUE_DATE>=CURRENT_DATE
		ORDER BY a.DUE_DATE,a.TITLE",
			array(
				'DUE_DATE' => 'ProperDate',
				/*'DAY' => '_eventDay',*/
				'ASSIGNED_DATE' => 'ProperDate',
				'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
			) );

		if ( $assignments_RET )
		{
			ListOutput(
				$assignments_RET,
				$assignments_LO_columns,
				'Upcoming Assignment',
				'Upcoming Assignments',
				array(),
				array(),
				$portal_LO_options
			);
		}

		if ( Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// warn if missing attendances
			$categories_RET = DBGet( "SELECT '0' AS ID,'Attendance' AS TITLE,0,NULL AS SORT_ORDER UNION
				SELECT ID,TITLE,1,SORT_ORDER
				FROM ATTENDANCE_CODE_CATEGORIES
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY 3,SORT_ORDER" );

			foreach ( (array) $categories_RET as $category )
			{
				// FJ days numbered.
				// FJ multiple school periods for a course period.

				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,
						acc.SCHOOL_DATE,cp.TITLE,
						'" . $category['ID'] . "' AS CATEGORY_ID,
						cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID
					FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
					WHERE EXISTS(SELECT 1
						FROM SCHEDULE se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE))
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.SYEAR='" . UserSyear() . "'
					AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
					AND cp.SCHOOL_ID=acc.SCHOOL_ID
					AND cp.SYEAR=acc.SYEAR
					AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
						(SELECT CASE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(school_date)% " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
						FROM attendance_calendar
						WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=acc.SCHOOL_ID)
						AND school_date<=acc.SCHOOL_DATE
						AND SCHOOL_ID=acc.SCHOOL_ID)
					AS INT) FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
					AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='" . $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					ORDER BY cp.TITLE,acc.SCHOOL_DATE", array( 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ), array( 'COURSE_PERIOD_ID' ) );
				}
				else
				{
					$missing_attendance_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,
						acc.SCHOOL_DATE,cp.TITLE,
						'" . $category['ID'] . "' AS CATEGORY_ID,
						cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID
					FROM ATTENDANCE_CALENDAR acc,COURSE_PERIODS cp,SCHOOL_PERIODS sp, COURSE_PERIOD_SCHOOL_PERIODS cpsp
					WHERE  EXISTS(SELECT 1
						FROM SCHEDULE se
						WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
						AND se.SYEAR='" . UserSyear() . "'
						AND acc.SCHOOL_DATE>=se.START_DATE
						AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE))
					AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
					AND acc.SYEAR='" . UserSyear() . "'
					AND (acc.MINUTES IS NOT NULL AND acc.MINUTES>0)
					AND cp.SCHOOL_ID=acc.SCHOOL_ID
					AND cp.SYEAR=acc.SYEAR AND acc.SCHOOL_DATE<'" . DBDate() . "'
					AND cp.CALENDAR_ID=acc.CALENDAR_ID
					AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
					AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
					AND sp.PERIOD_ID=cpsp.PERIOD_ID
					AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM cast(extract(DOW FROM acc.SCHOOL_DATE) AS INT)+1 FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)
					AND NOT exists(SELECT '' FROM ATTENDANCE_COMPLETED ac WHERE ac.SCHOOL_DATE=acc.SCHOOL_DATE AND ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='" . $category['ID'] . "')
					AND position('," . $category['ID'] . ",' IN cp.DOES_ATTENDANCE)>0
					ORDER BY cp.TITLE,acc.SCHOOL_DATE", array( 'SCHOOL_DATE' => '_makeTakeAttendanceLink' ), array( 'COURSE_PERIOD_ID' ) );
				}

				if ( $missing_attendance_RET )
				{
					echo ErrorMessage( array( _( 'You have missing attendance data' ) ), 'warning' );

					ListOutput(
						$missing_attendance_RET,
						$missing_attendance_LO_columns,
						'Course Period with missing attendance data',
						'Course Periods with missing attendance data',
						array(),
						array( 'COURSE_PERIOD_ID' ),
						$portal_LO_options
					);
				}
			}
		}

		if ( $RosarioModules['Food_Service'] && Preferences( 'HIDE_ALERTS' ) != 'Y' )
		{
			// warn if negative food service balance
			$staff = DBGet( "SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM STAFF s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			$staff = $staff[1];

			if ( $staff['BALANCE'] && $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage( array( sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), $staff['BALANCE'] ) ), 'warning' );
			}
		}

		echo '<p>&nbsp;' . _( 'Happy teaching...' ) . '</p>';
		break;

	case 'parent':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL)) AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0) AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0) AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC",array('PUBLISHED_DATE' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM PORTAL_NOTES pn,SCHOOLS s,STAFF st
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND pn.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=pn.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL))
		AND (st.SCHOOLS IS NULL OR position(','||pn.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pn.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL AND position(','||st.PROFILE_ID||',' IN pn.PUBLISHED_PROFILES)>0)
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ) );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				array(),
				array(),
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,'<b>'||pp.TITLE||'</b>' AS TITLE,'options' AS OPTIONS,pp.ID
		FROM PORTAL_POLLS pp,SCHOOLS s,STAFF st
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND st.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND (st.SCHOOLS IS NULL OR position(','||pp.SCHOOL_ID||',' IN st.SCHOOLS)>0)
		AND (st.PROFILE_ID IS NULL AND position(',parent,' IN pp.PUBLISHED_PROFILES)>0 OR st.PROFILE_ID IS NOT NULL
		AND position(','||st.PROFILE_ID||',' IN pp.PUBLISHED_PROFILES)>0)
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ) );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				array(),
				array(),
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ce.ID,ce.TITLE,ce.SCHOOL_DATE,to_char(ce.SCHOOL_DATE,'Day') AS DAY,ce.DESCRIPTION,s.TITLE AS SCHOOL
		FROM CALENDAR_EVENTS ce,SCHOOLS s
		WHERE ce.SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND ce.SYEAR='" . UserSyear() . "'
		AND ce.SCHOOL_ID IN (SELECT DISTINCT SCHOOL_ID FROM STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se WHERE sju.STAFF_ID='" . User( 'STAFF_ID' ) . "' AND se.SYEAR=ce.SYEAR AND se.STUDENT_ID=sju.STUDENT_ID AND se.START_DATE<=CURRENT_DATE AND (se.END_DATE>=CURRENT_DATE OR se.END_DATE IS NULL))
		AND s.ID=ce.SCHOOL_ID
		AND s.SYEAR=ce.SYEAR
		ORDER BY ce.SCHOOL_DATE,s.TITLE", array(
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		) );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				array(),
				array(),
				$portal_LO_options
			);
		}

		// FJ Portal Assignments.

		if ( AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

			$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
				a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
				c.TITLE AS COURSE,a.SUBMISSION,a.MARKING_PERIOD_ID,
				(SELECT 1
				FROM STUDENT_ASSIGNMENTS sa
				WHERE a.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
				AND sa.STUDENT_ID=s.STUDENT_ID) AS SUBMITTED
			FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s,COURSES c
			WHERE (a.COURSE_ID=c.COURSE_ID
			OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
			AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
			AND s.START_DATE<=CURRENT_DATE
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE>=CURRENT_DATE
			ORDER BY a.DUE_DATE,a.TITLE",
				array(
					'DUE_DATE' => 'MakeAssignmentDueDate',
					/*'DAY' => '_eventDay',*/
					/*'DESCRIPTION' => 'makeTextarea',*/
					'STAFF_ID' => 'GetTeacher',
					'SUBMITTED' => 'MakeAssignmentSubmitted',
					'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
				) );

			if ( $assignments_RET )
			{
				ListOutput(
					$assignments_RET,
					$assignments_LO_columns,
					'Upcoming Assignment',
					'Upcoming Assignments',
					array(),
					array(),
					$portal_LO_options
				);
			}
		}

		if ( $RosarioModules['Food_Service']
			&& Preferences( 'HIDE_ALERTS' ) !== 'Y' )
		{
			$FS_config = ProgramConfig( 'food_service' );

			// Warn if students with low food service balances.
			$extra['SELECT'] = ',fssa.STATUS,fsa.ACCOUNT_ID,fsa.BALANCE AS BALANCE,' .
				(float) $FS_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'] . '-fsa.BALANCE AS DEPOSIT';
			$extra['FROM'] = ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
			$extra['WHERE'] = " AND fssa.STUDENT_ID=s.STUDENT_ID
				AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID
				AND fssa.STATUS IS NULL
				AND fsa.BALANCE<'" . (float) $FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'] . "'";
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$RET = GetStuList( $extra );

			if ( $RET )
			{
				echo ErrorMessage(
					array( sprintf( _( 'You have students with food service balance below %1.2f - please deposit at least the Minimum Deposit into you children\'s accounts.' ), $FS_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'] ) ),
					'warning'
				);

				ListOutput(
					$RET,
					array(
						'FULL_NAME' => _( 'Student' ),
						'GRADE_ID' => _( 'Grade Level' ),
						'ACCOUNT_ID' => _( 'Account ID' ),
						'BALANCE' => _( 'Balance' ),
						'DEPOSIT' => _( 'Minimum Deposit' ),
					),
					'Student',
					'Students',
					array(),
					array(),
					$portal_LO_options
				);
			}

			// Warn if negative food service balance.
			$staff = DBGet( "SELECT (SELECT STATUS FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS,
				(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
				FROM STAFF s
				WHERE s.STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			$staff = $staff[1];

			if ( $staff['BALANCE'] && $staff['BALANCE'] < 0 )
			{
				echo ErrorMessage( array( sprintf( _( 'You have a <b>negative</b> food service balance of <span style="color:red">%s</span>' ), Currency( $staff['BALANCE'] ) ) ), 'warning' );
			}
		}

		echo '<p>&nbsp;' . _( 'Happy parenting...' ) . '</p>';
		break;

	case 'student':
		require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
//FJ fix bug Portal Notes not displayed when pn.START_DATE IS NULL
		//        $notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT FROM PORTAL_NOTES pn,SCHOOLS s WHERE pn.SYEAR='" . UserSyear() . "' AND pn.START_DATE<=CURRENT_DATE AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL) AND pn.SCHOOL_ID='".UserSchool()."' AND  position(',0,' IN pn.PUBLISHED_PROFILES)>0 AND s.ID=pn.SCHOOL_ID AND s.SYEAR=pn.SYEAR ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC",array('PUBLISHED_DATE' => 'ProperDate','CONTENT' => '_formatContent'));
		$notes_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pn.PUBLISHED_DATE) AS PUBLISHED_DATE,pn.TITLE,pn.CONTENT,pn.FILE_ATTACHED,pn.ID
		FROM PORTAL_NOTES pn,SCHOOLS s
		WHERE pn.SYEAR='" . UserSyear() . "'
		AND (pn.START_DATE<=CURRENT_DATE OR pn.START_DATE IS NULL)
		AND (pn.END_DATE>=CURRENT_DATE OR pn.END_DATE IS NULL)
		AND pn.SCHOOL_ID='" . UserSchool() . "'
		AND position(',0,' IN pn.PUBLISHED_PROFILES)>0
		AND s.ID=pn.SCHOOL_ID
		AND s.SYEAR=pn.SYEAR
		ORDER BY pn.SORT_ORDER,pn.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'CONTENT' => 'makeTextarea', 'FILE_ATTACHED' => 'makeFileAttached' ) );

		if ( $notes_RET )
		{
			ListOutput(
				$notes_RET,
				$notes_LO_columns,
				'Note',
				'Notes',
				array(),
				array(),
				$portal_LO_options
			);
		}

		// FJ Portal Polls.
		// FJ Portal Polls add students teacher.
		$polls_RET = DBGet( "SELECT s.TITLE AS SCHOOL,date(pp.PUBLISHED_DATE) AS PUBLISHED_DATE,pp.TITLE,'options' AS OPTIONS,pp.ID
		FROM PORTAL_POLLS pp,SCHOOLS s
		WHERE pp.SYEAR='" . UserSyear() . "'
		AND (pp.START_DATE<=CURRENT_DATE OR pp.START_DATE IS NULL)
		AND (pp.END_DATE>=CURRENT_DATE OR pp.END_DATE IS NULL)
		AND pp.SCHOOL_ID='" . UserSchool() . "'
		AND position(',0,' IN pp.PUBLISHED_PROFILES)>0
		AND s.ID=pp.SCHOOL_ID
		AND s.SYEAR=pp.SYEAR
		AND (pp.STUDENTS_TEACHER_ID IS NULL OR pp.STUDENTS_TEACHER_ID IN (SELECT cp.TEACHER_ID FROM SCHEDULE sch, COURSE_PERIODS cp WHERE sch.SYEAR='" . UserSyear() . "' AND sch.SCHOOL_ID='" . UserSchool() . "' AND sch.STUDENT_ID='" . UserStudentID() . "' AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID))
		ORDER BY pp.SORT_ORDER,pp.PUBLISHED_DATE DESC", array( 'PUBLISHED_DATE' => 'ProperDate', 'OPTIONS' => 'PortalPollsDisplay' ) );

		if ( $polls_RET )
		{
			ListOutput(
				$polls_RET,
				$polls_LO_columns,
				'Poll',
				'Polls',
				array(),
				array(),
				$portal_LO_options
			);
		}

		$events_RET = DBGet( "SELECT ID,TITLE,SCHOOL_DATE,to_char(SCHOOL_DATE,'Day') AS DAY,DESCRIPTION
		FROM CALENDAR_EVENTS
		WHERE SCHOOL_DATE BETWEEN CURRENT_DATE AND CURRENT_DATE+11
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'", array(
			'SCHOOL_DATE' => 'ProperDate',
			'DAY' => '_eventDay',
			'DESCRIPTION' => 'makeTextarea',
		) );

		if ( $events_RET )
		{
			ListOutput(
				$events_RET,
				$events_LO_columns,
				'Day With Upcoming Events',
				'Days With Upcoming Events',
				array(),
				array(),
				$portal_LO_options
			);
		}

		// FJ Portal Assignments.

		if ( AllowUse( 'Grades/StudentAssignments.php' ) )
		{
			require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

			$assignments_RET = DBGet( "SELECT a.ASSIGNMENT_ID,a.TITLE AS ASSIGNMENT_TITLE,
				a.DUE_DATE,to_char(a.DUE_DATE,'Day') AS DAY,a.ASSIGNED_DATE,a.DESCRIPTION,a.STAFF_ID,
				c.TITLE AS COURSE,a.SUBMISSION,a.MARKING_PERIOD_ID,
				(SELECT 1
				FROM STUDENT_ASSIGNMENTS sa
				WHERE a.ASSIGNMENT_ID=sa.ASSIGNMENT_ID
				AND sa.STUDENT_ID=s.STUDENT_ID) AS SUBMITTED
			FROM GRADEBOOK_ASSIGNMENTS a,SCHEDULE s,COURSES c
			WHERE (a.COURSE_ID=c.COURSE_ID
			OR c.COURSE_ID=(SELECT cp.COURSE_ID FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))
			AND (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . UserStudentID() . "'
			AND (s.END_DATE IS NULL OR s.END_DATE>=CURRENT_DATE)
			AND s.START_DATE<=CURRENT_DATE
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND a.DUE_DATE>=CURRENT_DATE
			ORDER BY a.DUE_DATE,a.TITLE",
				array(
					'DUE_DATE' => 'MakeAssignmentDueDate',
					/*'DAY' => '_eventDay',*/
					/*'DESCRIPTION' => 'makeTextarea',*/
					'STAFF_ID' => 'GetTeacher',
					'SUBMITTED' => 'MakeAssignmentSubmitted',
					'ASSIGNMENT_TITLE' => 'MakeAssignmentTitle',
				) );

			if ( $assignments_RET )
			{
				ListOutput(
					$assignments_RET,
					$assignments_LO_columns,
					'Upcoming Assignment',
					'Upcoming Assignments',
					array(),
					array(),
					$portal_LO_options
				);
			}
		}

		echo '<p>&nbsp;' . _( 'Happy learning...' ) . '</p>';
		break;
}

/**
 * Check PHP min version, safe mode, and required functions.
 *
 * @return array Warning messages for failed PHP checks.
 */
function PHPCheck()
{
	$ret = array();

	if ( version_compare( PHP_VERSION, '5.4.45' ) == -1 )
	{
		$ret[] = 'RosarioSIS requires PHP 5.4.45 to run, your version is : ' . PHP_VERSION;
	}

	if ( (bool) ini_get( 'safe_mode' ) )
	{
		$ret[] = 'safe_mode is set to On in your PHP configuration.';
	}

	if ( mb_strpos( ini_get( 'disable_functions' ), 'passthru' ) !== false )
	{
		$ret[] = 'passthru is disabled in your PHP configuration.';
	}

	return $ret;
}

/**
 * @param $string
 * @param $key
 */
function _eventDay( $string, $key )
{
	return _( trim( $string ) );
}

/**
 * Make Take Attendance link
 *
 * DBGet callback function.
 *
 * @since 3.6
 *
 * @param  string $value  School date.
 * @param  string $column 'SCHOOL_DATE'.
 * @return string Proper school date + take attendance link.
 */
function _makeTakeAttendanceLink( $value, $column )
{
	global $THIS_RET;

	if ( ! $value )
	{
		return $value;
	}

	$proper_date = ProperDate( $value );

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Printing PDF or exporting list.
		return $proper_date;
	}

	$modname = 'Attendance/TakeAttendance.php';

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$modname = 'Users/TeacherPrograms.php&include=' . $modname;

		if ( ! AllowEdit( $modname ) )
		{
			// Admin cannot take attendance.
			return $proper_date;
		}
	}
	elseif ( ! AllowUse( $modname ) )
	{
		// Teacher cannot take attendance?
		return $proper_date;
	}

	// Attendance category / table.
	$table = $THIS_RET['CATEGORY_ID'];

	// Redirect to TakeAttendance from Portal,
	// in case current School, SYear, MP, CP or Period are wrong.
	$take_attendance_redirect_url = 'Modules.php?modname=misc/Portal.php&modfunc=redirect_take_attendance';

	$take_attendance_redirect_url .= '&school_date=' . $THIS_RET['SCHOOL_DATE'] . '&table=' . $table;

	// Right course period & school period.
	$period = $THIS_RET['COURSE_PERIOD_ID'] . '.' . $THIS_RET['COURSE_PERIOD_SCHOOL_PERIODS_ID'];

	$take_attendance_redirect_url .= '&period=' . $period;

	return '<a href="' . $take_attendance_redirect_url . '">' . $proper_date . '</a>';
}

/**
 * Redirect to Take Attendance program.
 * Redirect from Portal,
 * in case current School, SYear, MP, CP or Period are wrong.
 *
 * @see _makeTakeAttendanceLink
 * @since 3.6
 *
 * @return bool False if hack or wrong parameters, else 302 redirection.
 */
function _redirectTakeAttendance()
{
	if ( empty( $_REQUEST['period'] )
		|| ! isset( $_REQUEST['table'] )
		|| empty( $_REQUEST['school_date'] )
		|| ! VerifyDate( $_REQUEST['school_date'] ) )
	{
		// Not enough parameters to redirect.
		return false;
	}

	list( $course_period, $course_period_school_period ) = explode( '.', $_REQUEST['period'] );

	// Get Course Period info.
	$cp_RET = DBGet( "SELECT SCHOOL_ID,TEACHER_ID
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $course_period . "'
		AND SYEAR='" . UserSyear() . "'
		LIMIT 1" );

	if ( ! isset( $cp_RET[1]['SCHOOL_ID'] ) )
	{
		// Course Period not found.
		return false;
	}

	$modname = 'Attendance/TakeAttendance.php';

	if ( User( 'PROFILE' ) === 'admin' )
	{
		$modname = 'Users/TeacherPrograms.php&include=' . $modname;

		if ( ! AllowEdit( $modname ) )
		{
			// Admin cannot take attendance.
			return false;
		}

		// Admin: Teacher Programs.
		$modname .= '&staff_id=' . $cp_RET[1]['TEACHER_ID'];

		$modname .= '&period=' . $_REQUEST['period'];
	}
	elseif ( ! AllowUse( $modname )
		|| User( 'STAFF_ID' ) !== $cp_RET[1]['TEACHER_ID'] )
	{
		// Teacher cannot take attendance?
		// Teachers cannot take others attendance?
		return false;
	}
	else
	{
		$_SESSION['UserCoursePeriod'] = $course_period;

		$_SESSION['UserCoursePeriodSchoolPeriod'] = $course_period_school_period;
	}

	if ( UserSchool() != $cp_RET[1]['SCHOOL_ID'] )
	{
		if ( User( 'SCHOOLS' )
			&& mb_strpos( User( 'SCHOOLS' ), ',' . $cp_RET[1]['SCHOOL_ID'] . ',' ) === false )
		{
			// User does not belong to this school...
			return false;
		}

		// Update current School.
		$_SESSION['UserSchool'] = $cp_RET[1]['SCHOOL_ID'];
	}

	$cp_mp_id = GetCurrentMP( 'QTR', $_REQUEST['school_date'] );

	if ( UserMP() != $cp_mp_id )
	{
		// Update current MP.
		$_SESSION['UserMP'] = $cp_mp_id;
	}

	// Get month, day & year from School Date.
	$date = ExplodeDate( $_REQUEST['school_date'] );

	$take_attendance_url = 'Modules.php?modname=' . $modname . '&table=' . $_REQUEST['table'] .
		'&month_date=' . $date['month'] . '&day_date=' . $date['day'] . '&year_date=' . $date['year'];

	header( 'Location: ' . $take_attendance_url );

	// echo '<script>ajaxLink(' . json_encode( $take_attendance_url ) . ');</script>';

	// Warehouse( 'footer' );

	return die();
}
