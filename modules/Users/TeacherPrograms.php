<?php

// Bugfix $_REQUEST['include'] 2 times in links.
$REQUEST_include = issetVal( $_REQUEST['include'] );

unset( $_REQUEST['include'] );

$_REQUEST['modname'] .= '&include=' . $REQUEST_include;

// @since 5.4 Do not Display Teacher Programs frame if is program modfunc PDF.
$is_program_modfunc_pdf = isset( $_REQUEST['_ROSARIO_PDF'] )
	&& $_REQUEST['modfunc']
	&& ( empty( $_GET['bottomfunc'] ) || $_GET['bottomfunc'] === 'print' );

if ( ! $is_program_modfunc_pdf )
{
	DrawHeader( _( 'Teacher Programs' ) . ' - ' . ProgramTitle( $_REQUEST['modname'] ) );
}

if ( UserStaffID() )
{
	$profile = DBGetOne( "SELECT PROFILE
		FROM staff
		WHERE STAFF_ID='" . UserStaffID() . "'" );

	if ( $profile !== 'teacher' )
	{
		unset( $_SESSION['staff_id'] );
	}
}

$extra['profile'] = 'teacher';

Search( 'staff_id', $extra );

if ( UserStaffID() )
{
	if ( ! $is_program_modfunc_pdf )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
	}

	// FJ multiple school periods for a course period
	//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM course_periods cp,school_periods sp,courses c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".UserStaffID()."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER");

	$all_qtr_mp = GetAllMP( 'QTR', UserMP() );

	// Fix SQL error when no Quarter MP.
	if ( ! $all_qtr_mp )
	{
		$cp_RET = [];
	}
	else
	{
		$cp_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.MARKING_PERIOD_ID,
			c.TITLE AS COURSE_TITLE,cp.SHORT_NAME AS CP_SHORT_NAME
			FROM course_periods cp,courses c
			WHERE c.COURSE_ID=cp.COURSE_ID
			AND cp.SYEAR='" . UserSyear() . "'
			AND cp.SCHOOL_ID='" . UserSchool() . "'
			AND cp.TEACHER_ID='" . UserStaffID() . "'
			AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
			ORDER BY c.TITLE,cp.SHORT_NAME" );
	}

	/**
	 * Get the Full Year marking period id
	 * there should be exactly one fy marking period per school.
	 */
	$fy_id = GetFullYearMP();

	if ( ! empty( $_REQUEST['period'] ) )
	{
		// @since RosarioSIS 10.9 Set current User Course Period before Secondary Teacher logic.
		SetUserCoursePeriod( $_REQUEST['period'] );
	}

	$period_select = '<label for="period" class="a11y-hidden">' . _( 'Course Periods' ) . '</label>
		<select name="period" id="period" autocomplete="off" onChange="ajaxPostForm(this.form,true);">';

	$optgroup = $current_cp_found = false;

	foreach ( (array) $cp_RET as $period )
	{
		// Add optroup to group periods by course periods.
		if ( ! empty( $period['COURSE_TITLE'] )
			&& $optgroup != $period['COURSE_TITLE'] )
		{
			// New optgroup.
			$period_select .= '<optgroup label="' . AttrEscape( $optgroup = $period['COURSE_TITLE'] ) . '">';
		}

		if ( $optgroup !== false
			&& $optgroup != $period['COURSE_TITLE'] ) {
			// Close optgroup.
			$period_select .= '</optgroup>';
		}

		$selected = '';

		if ( UserCoursePeriod() === $period['COURSE_PERIOD_ID'] )
		{
			$selected = ' selected';

			$current_cp_found = true;
		}

		$mp_text = '';

		if ( $period['MARKING_PERIOD_ID'] != $fy_id )
		{
			$mp_text = GetMP( $period['MARKING_PERIOD_ID'], 'SHORT_NAME' ) . ' - ';
		}

		$period_select .= '<option value="' . AttrEscape( $period['COURSE_PERIOD_ID'] ) . '"' . $selected . '>' .
			$mp_text . $period['CP_SHORT_NAME'] . '</option>';
	}

	if ( ! $current_cp_found )
	{
		// Do not use SetUserCoursePeriod() here as this is safe.
		$_SESSION['UserCoursePeriod'] = issetVal( $cp_RET[1]['COURSE_PERIOD_ID'] );

		if ( empty( $cp_RET[1]['COURSE_PERIOD_ID'] ) )
		{
			$period_select .= '<option value="">' . _( 'No courses found' ) . '</option>';
		}
	}

	$period_select .= '</select>';

	if ( ! $is_program_modfunc_pdf )
	{
		DrawHeader( $period_select );

		echo '</form><br />';

		unset( $_ROSARIO['DrawHeader'] );

		$_ROSARIO['HeaderIcon'] = false;

		echo '<div class="teacher-programs-wrapper">';
	}

	$_ROSARIO['allow_edit'] = AllowEdit( $_REQUEST['modname'] );

	// @since 6.9 Add UserImpersonateTeacher() function.
	UserImpersonateTeacher( UserStaffID() );

	// Security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	// Bugfix $_REQUEST['include'] 2 times in links.
	if ( mb_substr( $REQUEST_include, -4, 4 ) != '.php'
		|| mb_strpos( $REQUEST_include, '..' ) !== false
		|| ! is_file( 'modules/' . $REQUEST_include ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}
	else
	{
		$_ROSARIO['HeaderIcon'] = true;

		require_once 'modules/' . $REQUEST_include;
	}

	if ( ! $is_program_modfunc_pdf )
	{
		echo '</div>';
	}
}
