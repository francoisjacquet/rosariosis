<?php

// FJ Bugfix $_REQUEST['include'] 2 times in links.
$REQUEST_include = isset( $_REQUEST['include'] ) ? $_REQUEST['include'] : null;

unset( $_REQUEST['include'] );

$_REQUEST['modname'] .= '&include=' . $REQUEST_include;

DrawHeader( _( 'Teacher Programs' ) . ' - ' . ProgramTitle( $_REQUEST['modname'] ) );

if ( UserStaffID() )
{
	$profile = DBGetOne( "SELECT PROFILE
		FROM STAFF
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
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

	// FJ multiple school periods for a course period
	//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".UserStaffID()."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");

	$all_qtr_mp = GetAllMP( 'QTR', UserMP() );

	// Fix SQL error when no Quarter MP.
	if ( ! $all_qtr_mp )
	{
		$RET = array();
	}
	else
	{
		$RET = DBGet( "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,
		sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,c.TITLE AS COURSE_TITLE,cp.SHORT_NAME AS CP_SHORT_NAME
		FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c,COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND c.COURSE_ID=cp.COURSE_ID
		AND cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cp.SYEAR='" . UserSyear() . "'
		AND cp.SCHOOL_ID='" . UserSchool() . "'
		AND cp.TEACHER_ID='" . UserStaffID() . "'
		AND cp.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
		ORDER BY cp.SHORT_NAME, sp.SORT_ORDER" );
	}

	$fy_id = GetFullYearMP();

	if ( ! empty( $_REQUEST['period'] ) )
	{
		list( $CoursePeriod, $CoursePeriodSchoolPeriod ) = explode( '.', $_REQUEST['period'] );

		$_SESSION['UserCoursePeriod'] = $CoursePeriod;

		$_SESSION['UserCoursePeriodSchoolPeriod'] = $CoursePeriodSchoolPeriod;
	}

	if ( ! UserCoursePeriod() )
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
		$_SESSION['UserCoursePeriodSchoolPeriod'] = $RET[1]['COURSE_PERIOD_SCHOOL_PERIODS_ID'];
	}

	$period_select = '<select name="period" onChange="ajaxPostForm(this.form,true);">';
	$optgroup = FALSE;

	foreach ( (array) $RET as $period )
	{
		//FJ add optroup to group periods by course periods

		if ( ! empty( $period['COURSE_TITLE'] ) && $optgroup != $period['COURSE_TITLE'] ) //new optgroup
		{
			$period_select .= '<optgroup label="' . $period['COURSE_TITLE'] . '">';
			$optgroup = $period['COURSE_TITLE'];
		}

		if ( $optgroup !== FALSE && $optgroup != $period['COURSE_TITLE'] ) //close optgroup
		{
			$period_select .= '</optgroup>';
		}

		//if (UserCoursePeriod()==$period['COURSE_PERIOD_ID'])

		if ( UserCoursePeriodSchoolPeriod() == $period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] )
		{
			$selected = ' selected';
			$_SESSION['UserPeriod'] = $period['PERIOD_ID'];
			$found = true;
		}
		else
		{
			$selected = '';
		}

		//FJ days display to locale
		$days_convert = array( 'U' => _( 'Sunday' ), 'M' => _( 'Monday' ), 'T' => _( 'Tuesday' ), 'W' => _( 'Wednesday' ), 'H' => _( 'Thursday' ), 'F' => _( 'Friday' ), 'S' => _( 'Saturday' ) );
		//FJ days numbered

		if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
		{
			$days_convert = array( 'U' => '7', 'M' => '1', 'T' => '2', 'W' => '3', 'H' => '4', 'F' => '5', 'S' => '6' );
		}

		$period_DAYS_locale = '';
		$days_strlen = mb_strlen( $period['DAYS'] );

		for ( $i = 0; $i < $days_strlen; $i++ )
		{
			$period_DAYS_locale .= mb_substr( $days_convert[mb_substr( $period['DAYS'], $i, 1 )], 0, 3 ) . '.';
		}

		//FJ add subject areas
		//$period_select .= '<option value="'.$period['COURSE_PERIOD_ID'].'.'.$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'].'"'.$selected.'>'.$period['SHORT_NAME'].(mb_strlen($period['DAYS'])<5?' ('.$period_DAYS_locale.')':'').($period['MARKING_PERIOD_ID']!=$fy_id?' '.GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME'):'').' - '.$period['CP_SHORT_NAME'].'</option>';
		$period_select .= '<option value="' . $period['COURSE_PERIOD_ID'] . '.' . $period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] . '"' . $selected . '>' .
		$period['TITLE'] .
		( mb_strlen( $period['DAYS'] ) < 5 ?
			( mb_strlen( $period['DAYS'] ) < 2 ?
				' ' . _( 'Day' ) . ' ' . $period_DAYS_locale . ' - ' :
				' ' . _( 'Days' ) . ' ' . $period_DAYS_locale . ' - ' ) :
			' - ' ) .
		( $period['MARKING_PERIOD_ID'] != $fy_id ?
			GetMP( $period['MARKING_PERIOD_ID'], 'SHORT_NAME' ) . ' - ' :
			'' ) .
		$period['CP_SHORT_NAME'] . '</option>';
	}

	if ( ! $found )
	{
		$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
//FJ fix bug SQL no course period in the user period

		if ( empty( $RET[1]['COURSE_PERIOD_ID'] ) )
		{
			$_SESSION['UserCoursePeriod'] = 0;
			$_SESSION['UserCoursePeriodSchoolPeriod'] = 0;
			$period_select .= '<option value="">' . sprintf( _( 'No %s were found.' ), _( 'Course Period' ) ) . '</option>';
		}

		$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
	}

	$period_select .= '</select>';

	DrawHeader( $period_select );
	echo '</form><br />';
	unset( $_ROSARIO['DrawHeader'] );
	$_ROSARIO['HeaderIcon'] = false;

	$_ROSARIO['allow_edit'] = AllowEdit( $_REQUEST['modname'] );
	$_ROSARIO['User'] = array(
		0 => $_ROSARIO['User'][1],
		1 => array(
			'STAFF_ID' => UserStaffID(),
			'NAME' => GetTeacher( UserStaffID() ),
			'USERNAME' => GetTeacher( UserStaffID(), 'USERNAME' ),
			'PROFILE' => 'teacher',
			'SCHOOLS' => ',' . UserSchool() . ',',
			'SYEAR' => UserSyear(),
		),
	);

	echo '<div class="teacher-programs-wrapper">';

	//FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	//FJ Bugfix $_REQUEST['include'] 2 times in links

	if ( mb_substr( $REQUEST_include, -4, 4 ) != '.php' || mb_strpos( $REQUEST_include, '..' ) !== false || ! is_file( 'modules/' . $REQUEST_include ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}
	else
	{
		$_ROSARIO['HeaderIcon'] = true;

		require_once 'modules/' . $REQUEST_include;
	}

	echo '</div>';
}
