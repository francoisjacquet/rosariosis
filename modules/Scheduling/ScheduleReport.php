<?php
DrawHeader( ProgramTitle() );

echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';

if ( $_REQUEST['modfunc'] !== 'students' )
{
	DrawHeader( CheckBoxOnclick(
		'include_child_mps',
		_( 'Show Child Marking Period Details' )
	) );
}

// Check if Subject ID is valid for current school & syear!
if ( isset( $_REQUEST['subject_id'] ) )
{
	$subject_RET = DBGet( "SELECT SUBJECT_ID
		FROM COURSE_SUBJECTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

	if ( ! $subject_RET )
	{
		// Unset modfunc & subject ID & course ID & course period ID & students & redirect URL.
		RedirectURL( array( 'modfunc', 'subject_id', 'course_id', 'course_period_id', 'students' ) );
	}
}


if ( ! empty( $_REQUEST['subject_id'] ) )
{
	$subject_title = DBGetOne( "SELECT TITLE
		FROM COURSE_SUBJECTS
		WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'" );

	//FJ add translation
	$header .= '<a href="Modules.php?modname=' . $_REQUEST['modname'] .
		'&include_child_mps=' . $_REQUEST['include_child_mps'] . '">' . _( 'Top' ) . '</a>
		&rsaquo; <a href="Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=courses&subject_id=' . $_REQUEST['subject_id'] .
		'&include_child_mps=' . $_REQUEST['include_child_mps'] . '">' .
		$subject_title . '</a>';

	if ( ! empty( $_REQUEST['course_id'] ) )
	{
		$header2 = '<a href="Modules.php?modname=' . $_REQUEST['modname'] .
			'&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'];

		$location = 'courses';

		$course_RET = DBGet( "SELECT TITLE
			FROM COURSES
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'" );

		$header .= ' &rsaquo; <a href="Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=students&subject_id=' . $_REQUEST['subject_id'] .
			'&course_id=' . $_REQUEST['course_id'] .
			'&include_child_mps=' . $_REQUEST['include_child_mps'] . '">' .
			$course_RET[1]['TITLE'] . '</a>';

		$header2 .= '&students=' . $location .
			'&modfunc=students&include_child_mps=' . $_REQUEST['include_child_mps'] . '">' .
			_( 'List Students' ) . '</a> | ' . $header2 .
			'&unscheduled=true&students=' . $location .
			'&modfunc=students&include_child_mps=' . $_REQUEST['include_child_mps'] . '">' .
			_( 'List Unscheduled Students' ) . '</a>';

		DrawHeader( $header );

		DrawHeader( $header2 );
	}
	else
	{
		DrawHeader( $header );
	}
}

echo '</form>';

// SUBJECTS ----
$subject_RET = DBGet( "SELECT s.SUBJECT_ID,s.TITLE
	FROM COURSE_SUBJECTS s
	WHERE s.SYEAR='" . UserSyear() . "'
	AND s.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY s.SORT_ORDER,s.TITLE" );

if ( ! empty( $subject_RET ) && $_REQUEST['subject_id'] )
{
	foreach ( (array) $subject_RET as $key => $value)
	{
		if ( $value['SUBJECT_ID'] == $_REQUEST['subject_id'] )
		{
			$subject_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
		}
	}
}

$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
	'&modfunc=courses&include_child_mps=' . $_REQUEST['include_child_mps'];

$link['TITLE']['variables'] = array( 'subject_id' => 'SUBJECT_ID' );

$LO_options = array(
	'save' => false,
	'search' => false,
	'print' => false,
	'responsive' => false,
);

echo '<div class="st">';

ListOutput(
	$subject_RET,
	array( 'TITLE' => _( 'Subject' ) ),
	'Subject',
	'Subjects',
	$link,
	array(),
	$LO_options
);

echo '</div>';

// Now, Course & Course periods Lists are responsive (multiple columns).
$LO_options['responsive'] = true;

// COURSES ----
if ( $_REQUEST['modfunc'] === 'courses'
	|| $_REQUEST['modfunc'] === 'course_periods'
	|| $_REQUEST['modfunc'] === 'students' )
{
	$QI = DBQuery( "SELECT c.COURSE_ID,c.TITLE,cp.TOTAL_SEATS,cp.COURSE_PERIOD_ID,
	cp.MARKING_PERIOD_ID,cp.MP,cp.CALENDAR_ID,
	(SELECT count(*) FROM SCHEDULE_REQUESTS sr WHERE sr.COURSE_ID=c.COURSE_ID) AS COUNT_REQUESTS
	FROM COURSES c,COURSE_PERIODS cp
	WHERE c.SUBJECT_ID='" . $_REQUEST['subject_id'] . "'
	AND c.COURSE_ID=cp.COURSE_ID
	AND c.SYEAR='" . UserSyear() . "'
	AND c.SCHOOL_ID='" . UserSchool() . "'
	ORDER BY c.TITLE");

	$_RET = DBGet($QI,array(),array('COURSE_ID'));

	$RET = calcSeats($_RET,array('COURSE_ID','TITLE','COUNT_REQUESTS'));

	if (! empty( $RET ) && $_REQUEST['course_id'])
	{
		foreach ( (array) $RET as $key => $value)
		{
			if ( $value['COURSE_ID']==$_REQUEST['course_id'])
			{
				$RET[ $key ]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	$link['TITLE']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=course_periods&subject_id='.$_REQUEST['subject_id'].'&include_child_mps='.$_REQUEST['include_child_mps'];

	$link['TITLE']['variables'] = array('course_id' => 'COURSE_ID');

	$columns = array('TITLE' => _('Course'),'COUNT_REQUESTS' => _('Requests'));

	if ( ! empty( $_REQUEST['include_child_mps'] ) )
	{
		$OFT_string = mb_substr(_('Open'),0,1).'&#124;'.mb_substr(_('Filled'),0,1).'&#124;'.mb_substr(_('Total'),0,1);

		// FJ fix error Missing argument 1.
		foreach ( explode( ',', GetAllMP( '' ) ) as $mp )
		{
			$mp = trim($mp,"'");

			$columns += array('OFT_'.$mp=>(GetMP($mp,'SHORT_NAME') ?
				GetMP($mp,'SHORT_NAME') :
				GetMP($mp)).'<br />'.$OFT_string);
		}
	}
	else
	{
		$columns += array('OPEN_SEATS' => _('Open'),'FILLED_SEATS' => _('Filled'),'TOTAL_SEATS' => _('Total'));
	}

	echo '<div class="st">';

	ListOutput($RET,$columns,'Course','Courses',$link,array(),$LO_options);

	echo '</div>';
}

// COURSE PERIODS ----
if ( $_REQUEST['modfunc'] === 'course_periods'
	|| $_REQUEST['students'] === 'course_periods' )
{
	//FJ multiple school periods for a course period
	//$QI = DBQuery("SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID),TITLE");
	$QI = DBQuery("SELECT COURSE_PERIOD_ID,TITLE,MARKING_PERIOD_ID,MP,CALENDAR_ID,TOTAL_SEATS FROM COURSE_PERIODS cp WHERE COURSE_ID='".$_REQUEST['course_id']."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SHORT_NAME,TITLE");

	$RET = DBGet($QI);

	foreach ( (array) $RET as $key => $period)
	{
		$value = array();
		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			$total_seats = $filled_seats = array();
		}
		else
		{
			$total_seats = $filled_seats = 0;
		}

		calcSeats1($period,$total_seats,$filled_seats);

		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			foreach ( (array) $total_seats as $mp => $total)
			{
				$value += array('OFT_'.$mp=>($total!==false?($filled_seats[ $mp ]!==false?$total-$filled_seats[ $mp ]:'') : _( 'N/A' ) ).'|'.($filled_seats[ $mp ]!==false?$filled_seats[ $mp ]:'').'|'.($total!==false?$total : _( 'N/A' ) ));
			}
		}
		else
		{
			$value += array('OPEN_SEATS'=>($total_seats!==false?($filled_seats!==false?$total_seats-$filled_seats:'') : _( 'N/A' ) ),'FILLED_SEATS'=>($filled_seats!==false?$filled_seats:''),'TOTAL_SEATS'=>($total_seats!==false?$total_seats : _( 'N/A' ) ));
		}

		$RET[ $key ] += $value;
	}

	if (! empty( $RET ) && $_REQUEST['course_period_id'])
	{
		foreach ( (array) $RET as $key => $value)
		{
			if ( $value['COURSE_PERIOD_ID']==$_REQUEST['course_period_id'])
			{
				$RET[ $key ]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	$link = array();

	$link['TITLE']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=students&students=course_periods&subject_id='.$_REQUEST['subject_id'].'&course_id='.$_REQUEST['course_id'].'&include_child_mps='.$_REQUEST['include_child_mps'];

	$link['TITLE']['variables'] = array('course_period_id' => 'COURSE_PERIOD_ID');

	$columns = array('TITLE' => _('Period').' '._('Days').' - '._('Short Name').' - '._('Teacher'));

	if ( ! empty( $_REQUEST['include_child_mps'] ) )
	{
		// FJ fix error Missing argument 1.
		foreach ( explode( ',', GetAllMP( '' ) ) as $mp )
		{
			$mp = trim($mp,"'");

			$columns += array('OFT_'.$mp=>(GetMP($mp,'SHORT_NAME')?GetMP($mp,'SHORT_NAME'):GetMP($mp)).'<br />O|F|T');
		}
	}
	else
	{
		$columns += array('OPEN_SEATS' => _('Open'),'FILLED_SEATS' => _('Filled'),'TOTAL_SEATS' => _('Total'));
	}

	echo '<div class="st">';

	ListOutput($RET,$columns,'Course Period','Course Periods',$link,array(),$LO_options);

	echo '</div>';
}


// LIST STUDENTS ----
if ( $_REQUEST['modfunc']=='students')
{
	$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE FROM CUSTOM_FIELDS WHERE ID=200000004 AND TYPE='date'",array(),array('ID'));

	$sql_birthdate = '';

	$function_birthdate = $column_birthdate = array();

	if ( $custom_fields_RET['200000004'])
	{
		$sql_birthdate = ',s.CUSTOM_200000004';

		$function_birthdate = array('CUSTOM_200000004' => 'ProperDate');

		$column_birthdate = array('CUSTOM_200000004'=>ParseMLField($custom_fields_RET['200000004'][1]['TITLE']));
	}

	if ( $_REQUEST['unscheduled']=='true')
	{
		$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.STUDENT_ID" . $sql_birthdate . ",ssm.GRADE_ID
			FROM SCHEDULE_REQUESTS sr,STUDENTS s,STUDENT_ENROLLMENT ssm
			WHERE (('" . DBDate() . "' BETWEEN ssm.START_DATE
			AND ssm.END_DATE OR ssm.END_DATE IS NULL))
			AND s.STUDENT_ID=sr.STUDENT_ID
			AND s.STUDENT_ID=ssm.STUDENT_ID
			AND ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "' ";

		if ( ! empty( $_REQUEST['course_id'] ) )
		{
			$sql .= "AND sr.COURSE_ID='".$_REQUEST['course_id']."' ";
		}
		elseif ( ! empty( $_REQUEST['course_id'] ) )
		{
			$sql .= "AND sr.COURSE_ID='".$_REQUEST['course_id']."' ";
		}

		$sql .= "AND NOT EXISTS (SELECT '' FROM SCHEDULE ss WHERE ss.COURSE_ID=sr.COURSE_ID AND ss.STUDENT_ID=sr.STUDENT_ID AND ('".DBDate()."' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL))";
	}
	else
	{
		$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.STUDENT_ID" . $sql_birthdate . ",ssm.GRADE_ID
			FROM SCHEDULE ss,STUDENTS s,STUDENT_ENROLLMENT ssm
			WHERE ('" . DBDate() . "' BETWEEN ss.START_DATE AND ss.END_DATE OR ss.END_DATE IS NULL)
			AND (('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL))
			AND s.STUDENT_ID=ss.STUDENT_ID
			AND s.STUDENT_ID=ssm.STUDENT_ID
			AND ssm.SYEAR='" . UserSyear() . "'
			AND ssm.SCHOOL_ID='" . UserSchool() . "' ";

		if ( ! empty( $_REQUEST['course_period_id'] ) )
		{
			$sql .= "AND ss.COURSE_PERIOD_ID='".$_REQUEST['course_period_id']."'";
		}
		elseif ( ! empty( $_REQUEST['course_id'] ) )
		{
			$sql .= "AND ss.COURSE_ID='".$_REQUEST['course_id']."'";
		}
	}

	$sql .= ' ORDER BY s.LAST_NAME,s.FIRST_NAME';

	$RET = DBGet( $sql,array('GRADE_ID' => 'GetGrade') + $function_birthdate);

	$link = array();

	if (AllowUse('Scheduling/Schedule.php'))
	{
		$link['FULL_NAME']['link'] = "Modules.php?modname=Scheduling/Schedule.php";
		$link['FULL_NAME']['variables'] = array('student_id' => 'STUDENT_ID');
	}

	echo '<div style="clear: both;">';

	if ( $_REQUEST['unscheduled']=='true')
	{
		ListOutput(
			$RET,
			array(
				'FULL_NAME' => _( 'Student' ),
				'GRADE_ID' => _( 'Grade Level' )
			) + $column_birthdate,
			'Unscheduled Student',
			'Unscheduled Students',
			$link,
			array(),
			$LO_options
		);
	}
	else
	{
		ListOutput(
			$RET,
			array(
				'FULL_NAME' => _( 'Student' ),
				'GRADE_ID' => _( 'Grade Level' )
			) + $column_birthdate,
			'Student',
			'Students',
			$link,
			array(),
			$LO_options
		);
	}
}

function calcSeats1($period,&$total_seats,&$filled_seats)
{

	if ( ! empty( $_REQUEST['include_child_mps'] ) )
	{
		$mps = GetChildrenMP($period['MP'],$period['MARKING_PERIOD_ID']);
		if ( $period['MP']=='FY' || $period['MP']=='SEM')
			$mps = "'".$period['MARKING_PERIOD_ID']."'".($mps?','.$mps:'');
	}
	else
		$mps = "'".$period['MARKING_PERIOD_ID']."'";

	foreach ( explode( ',', $mps ) as $mp )
	{
		// Fix SQL error if MP was deleted.
		if ( ! GetMP( $mp, 'MP' ) )
		{
			// Skip MP, does not exist, silently fail?
			continue;
		}

		$mp = trim( $mp, "'" );

		$seats = DBGet( "SELECT max((SELECT count(1)
			FROM SCHEDULE ss
			JOIN STUDENT_ENROLLMENT sem ON (sem.STUDENT_ID=ss.STUDENT_ID AND sem.SYEAR=ss.SYEAR)
			WHERE ss.COURSE_PERIOD_ID='" . $period['COURSE_PERIOD_ID'] . "'
			AND (ss.MARKING_PERIOD_ID='" . $mp . "'
				OR ss.MARKING_PERIOD_ID IN (" . GetAllMP( GetMP( $mp, 'MP' ), $mp ) . "))
			AND (ac.SCHOOL_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ac.SCHOOL_DATE<=ss.END_DATE))
			AND (ac.SCHOOL_DATE>=sem.START_DATE AND (sem.END_DATE IS NULL OR ac.SCHOOL_DATE<=sem.END_DATE)))) AS FILLED_SEATS
		FROM ATTENDANCE_CALENDAR ac
		WHERE ac.CALENDAR_ID='" . $period['CALENDAR_ID'] . "'
		AND ac.SCHOOL_DATE BETWEEN " . db_case( array(
			"(CURRENT_DATE>'" . GetMP( $mp, 'END_DATE' ) . "')",
			'TRUE',
			"'" . GetMP( $mp, 'START_DATE' ) . "'",
			'CURRENT_DATE'
		) ) . " AND '" . GetMP( $mp, 'END_DATE' ) . "'" );

		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			if ( $total_seats[ $mp ]!==false)
				if ( $period['TOTAL_SEATS'])
					$total_seats[ $mp ] += $period['TOTAL_SEATS'];
				else
					$total_seats[ $mp ] = false;
			if ( $filled_seats!==false)
				if ( $seats[1]['FILLED_SEATS']!='')
					$filled_seats[ $mp ] += $seats[1]['FILLED_SEATS'];
				else
					$filled_seats[ $mp ] = false;
		}
		else
		{
			if ( $total_seats!==false)
				if ( $period['TOTAL_SEATS'])
					$total_seats += $period['TOTAL_SEATS'];
				else
					$total_seats = false;
			if ( $filled_seats!==false)
				if ( $seats[1]['FILLED_SEATS']!='')
					$filled_seats += $seats[1]['FILLED_SEATS'];
				else
					$filled_seats = false;
		}
	}
}

function calcSeats(&$_RET,$columns)
{
	$RET = array(0 => array());
	foreach ( (array) $_RET as $periods)
	{
		$value = array();
		foreach ( (array) $columns as $column)
			$value += array($column => $periods[key($periods)][ $column ]);
		if ( ! empty( $_REQUEST['include_child_mps'] ) )
			$total_seats = $filled_seats = array();
		else
			$total_seats = $filled_seats = 0;
		foreach ( (array) $periods as $period)
			calcSeats1($period,$total_seats,$filled_seats);
		if ( ! empty( $_REQUEST['include_child_mps'] ) )
		{
			foreach ( (array) $total_seats as $mp => $total)
			{
				$filled = $filled_seats[ $mp ];
				$value += array('OFT_'.$mp=>($total!==false?($filled!==false?$total-$filled:'') : _( 'N/A' ) ).'|'.($filled!==false?$filled:'').'|'.($total!==false?$total : _( 'N/A' ) ));
			}
		}
		else
			$value += array('OPEN_SEATS'=>($total_seats!==false?($filled_seats!==false?$total_seats-$filled_seats:'') : _( 'N/A' ) ),'FILLED_SEATS'=>($filled_seats!==false?$filled_seats:''),'TOTAL_SEATS'=>($total_seats!==false?$total_seats : _( 'N/A' ) ));
		$RET[] = $value;
	}
	unset($RET[0]);

	return $RET;
}
