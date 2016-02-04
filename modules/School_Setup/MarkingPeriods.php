<?php

DrawHeader( ProgramTitle() );

// default MP ID to Full Year
if ( !isset( $_REQUEST['marking_period_id'] )
	|| empty( $_REQUEST['marking_period_id'] ) )
{
	$fy_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID
		FROM SCHOOL_MARKING_PERIODS
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "' ORDER BY SORT_ORDER" ) );

	if ( count( $fy_RET ) )
	{
		$_REQUEST['marking_period_id'] = $fy_RET[1]['MARKING_PERIOD_ID'];
	}
	else
	{
		$_REQUEST['marking_period_id'] = 'new';
	}

	$_REQUEST['mp_term'] = 'FY';
}

//unset($_SESSION['_REQUEST_vars']['marking_period_id']);
//unset($_SESSION['_REQUEST_vars']['mp_term']);

if ( $_REQUEST['marking_period_id'] === 'new' )
{
	switch ( $_REQUEST['mp_term'] )
	{
		case 'FY':
			$title = _( 'New Year' );
		break;

		case 'SEM':
			$title = _( 'New Semester' );
		break;

		case 'QTR':
			$title = _( 'New Marking Period' );
		break;

		case 'PRO':
			$title = _( 'New Progress Period' );
		break;
	}
}

// UPDATING
if ( isset( $_POST['day_tables'] )
	&& isset( $_POST['month_tables'] )
	&& isset( $_POST['year_tables'] ) )
{
	$requested_dates = RequestedDates(
		$_REQUEST['day_tables'],
		$_REQUEST['month_tables'],
		$_REQUEST['year_tables']
	);

	$_POST['tables'] = array_replace_recursive( $_POST['tables'], $requested_dates);

	$_REQUEST['tables'] = array_replace_recursive( $_REQUEST['tables'], $requested_dates);
}

if ( isset( $_POST['tables'] )
	&& count( $_POST['tables'] )
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		//FJ fix SQL bug invalid sort order
		if ( isset( $columns['SORT_ORDER'] )
			&& $columns['SORT_ORDER'] !== ''
			&& !is_numeric( $columns['SORT_ORDER'] ) )
		{
			$error[] = _( 'Please enter a valid Sort Order.' );

			break 1;
		}

		// UPDATE
		if ( $id !== 'new' )
		{
			$sql = "UPDATE SCHOOL_MARKING_PERIODS SET ";

			foreach ( (array) $columns as $column => $value )
			{
				if ( $column === 'START_DATE'
					|| $column === 'END_DATE'
					|| $column === 'POST_START_DATE'
					|| $column === 'POST_END_DATE' )
				{
					//FJ fix SQL bug START_DATE or END_DATE is null
					if ( ( !VerifyDate( $value )
							&& $value !== '' )
						|| ( ($column === 'START_DATE' || $column === 'END_DATE' )
							&& $value === '' ) )
					{
						$error[] = _( 'Not all of the dates were entered correctly.' );

						break 2;
					}

					//FJ verify END_DATE > START_DATE
					$mp_dates_RET = DBGet( DBQuery( "SELECT START_DATE, END_DATE, POST_START_DATE, POST_END_DATE
						FROM SCHOOL_MARKING_PERIODS
						WHERE MARKING_PERIOD_ID='" . $id . "'" ) );

					$start_date = !empty( $columns['START_DATE'] ) ?
						$columns['START_DATE'] :
						$mp_dates_RET[1]['START_DATE'];

					$end_date = !empty($columns['END_DATE']) ?
						$columns['END_DATE'] :
						$mp_dates_RET[1]['END_DATE'];

					$post_start_date = !empty($columns['POST_START_DATE']) ?
						$columns['POST_START_DATE'] :
						$mp_dates_RET[1]['POST_START_DATE'];

					$post_end_date = !empty($columns['POST_END_DATE']) ?
						$columns['POST_END_DATE'] :
						$mp_dates_RET[1]['POST_END_DATE'];

					if ( ( $column ==='END_DATE'
							&& date_create( $value ) <= date_create( $start_date ) )
						|| ( $column === 'START_DATE'
							&& date_create( $end_date ) <= date_create( $value ) )
						|| ( $column === 'POST_END_DATE'
							&& $value !== ''
							&& $post_start_date !== null
							&& date_create( $value ) <= date_create( $post_start_date ) )
						|| ( $column === 'POST_START_DATE'
							&& $value !== ''
							&& $post_end_date !== null
							&& date_create( $post_end_date ) <= date_create( $value ) ) )
					{
						$error[] = _( 'Start date must be anterior to end date.' );

						break 2;
					}
				}

				$sql .= $column . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE MARKING_PERIOD_ID='" . $id . "'";

			$go = true;
		}
		// INSERT
		else
		{
			$id_RET = DBGet( DBQuery( 'SELECT ' . db_seq_nextval( 'MARKING_PERIOD_SEQ' ).' AS ID' ) );

			$sql = "INSERT INTO SCHOOL_MARKING_PERIODS ";

			$fields = "MARKING_PERIOD_ID,MP,SYEAR,SCHOOL_ID,";

			$values = "'" . $id_RET[1]['ID'] . "','" . $_REQUEST['mp_term'] . "','" . UserSyear() . "','" . UserSchool() . "',";

			switch ( $_REQUEST['mp_term'] )
			{
				case 'SEM':
					$fields .= "PARENT_ID,";
					$values .= "'" . $_REQUEST['year_id'] . "',";
				break;

				case 'QTR':
					$fields .= "PARENT_ID,";
					$values .= "'" . $_REQUEST['semester_id'] . "',";
				break;

				case 'PRO':
					$fields .= "PARENT_ID,";
					$values .= "'" . $_REQUEST['quarter_id'] . "',";
				break;
			}

			$go = false;

			foreach ( (array) $columns as $column => $value )
			{
				if ( $column === 'START_DATE'
					|| $column === 'END_DATE'
					|| $column === 'POST_START_DATE'
					|| $column === 'POST_END_DATE' )
				{
					//FJ fix SQL bug START_DATE or END_DATE is null
					if ( !VerifyDate( $value )
						&& $value !== ''
						|| ( ( $column === 'START_DATE'
							|| $column === 'END_DATE' )
						&& $value === '' ) )
					{
						$error[] = _( 'Not all of the dates were entered correctly.' );

						break 2;
					}

					//FJ verify END_DATE > START_DATE
					if ( ( $column === 'END_DATE'
							&& date_create( $value ) <= date_create( $columns['START_DATE'] ) )
						|| ( $column === 'POST_START_DATE'
							&& $columns['POST_END_DATE'] !== ''
							&& date_create( $value ) > date_create( $columns['POST_END_DATE'] ) ) )
					{
						$error[] = _( 'Start date must be anterior to end date.' );

						break 2;
					}
				}

				if ( !empty( $value )
					|| $value === '0' )
				{
					$fields .= $column . ',';

					$values .= "'" . $value . "',";

					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
		}

		// CHECK TO MAKE SURE ONLY ONE MP & ONE GRADING PERIOD IS OPEN AT ANY GIVEN TIME
		$dates_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID 
			FROM SCHOOL_MARKING_PERIODS 
			WHERE MP='" . $_REQUEST['mp_term'] . "' 
			AND ( true=false" .
			( $columns['START_DATE'] ? " OR '" . $columns['START_DATE'] .
				"' BETWEEN START_DATE AND END_DATE" : '' ) .
			( $columns['END_DATE'] ? " OR '" . $columns['END_DATE'] .
				"' BETWEEN START_DATE AND END_DATE" : '' ) .
			( $columns['START_DATE'] && $columns['END_DATE'] ?
				" OR START_DATE BETWEEN '" . $columns['START_DATE'] . "' AND '" . $columns['END_DATE'] . "'" .
				" OR END_DATE BETWEEN '" . $columns['START_DATE'] . "' AND '" . $columns['END_DATE'] . "'" : '') . ") 
			AND SCHOOL_ID='" . UserSchool() . "' 
			AND SYEAR='" . UserSyear() . "'" .
			( $id !== 'new' ? " AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID!='" . $id . "'" : '' ) ) );

		$posting_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID 
			FROM SCHOOL_MARKING_PERIODS 
			WHERE MP='" . $_REQUEST['mp_term'] . "' 
			AND ( true=false" .
			( $columns['POST_START_DATE'] ? " OR '" . $columns['POST_START_DATE'] .
				"' BETWEEN POST_START_DATE AND POST_END_DATE" : '' ) .
			( $columns['POST_END_DATE'] ? " OR '" . $columns['POST_END_DATE'] .
				"' BETWEEN POST_START_DATE AND POST_END_DATE" : '' ) .
			( $columns['POST_START_DATE'] && $columns['POST_END_DATE'] ?
				" OR POST_START_DATE BETWEEN '" . $columns['POST_START_DATE'] . "' AND '" . $columns['POST_END_DATE'] . "'" .
				" OR POST_END_DATE BETWEEN '" . $columns['POST_START_DATE'] . "' AND '" . $columns['POST_END_DATE'] . "'" : '' ) . ") 
			AND SCHOOL_ID='" . UserSchool() . "' 
			AND SYEAR='" . UserSyear() . "'" .
			( $id !== 'new' ? " AND MARKING_PERIOD_ID!='" . $id . "'" : '' ) ) );

		if ( count( $dates_RET ) )
		{
			$error[] = sprintf( 
				_( 'The beginning and end dates you specified for this marking period overlap with those of "%s".' ),
				GetMP( $dates_RET[1]['MARKING_PERIOD_ID'] )
			) . ' ' .
			_( 'Only one marking period can be open at any time.' );

			$go = false;
		}

		if ( count( $posting_RET ) )
		{
			$error[] = sprintf(
				_( 'The grade posting dates you specified for this marking period overlap with those of "%s".' ),
				GetMP( $posting_RET[1]['MARKING_PERIOD_ID'] )
			) . ' ' .
			_( 'Only one grade posting period can be open at any time.' );

			$go = false;
		}

		if ( $go )
			DBQuery( $sql );

		if ( $id === 'new'
			&& $go )
			$_REQUEST['marking_period_id'] = $id_RET[1]['ID'];
	}

	unset( $_REQUEST['tables'] );
	unset( $_SESSION['_REQUEST_vars']['tables'] );
}

// DELETING
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	$extra = array();

	switch ( $_REQUEST['mp_term'] )
	{
		case 'FY':
			$name = _( 'Year' );

			$parent_term = '';
			$parent_id = '';

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID IN
					(SELECT MARKING_PERIOD_ID
						FROM SCHOOL_MARKING_PERIODS
						WHERE PARENT_ID IN
							(SELECT MARKING_PERIOD_ID
								FROM SCHOOL_MARKING_PERIODS
								WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "' ) )";

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID IN
					(SELECT MARKING_PERIOD_ID
						FROM SCHOOL_MARKING_PERIODS
						WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "')";

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "'";
		break;

		case 'SEM':
			$name = _( 'Semester' );

			$parent_term = 'FY';
			$parent_id = $_REQUEST['year_id'];

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID IN
					(SELECT MARKING_PERIOD_ID
						FROM SCHOOL_MARKING_PERIODS
						WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "')";

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "'";
		break;

		case 'QTR':
			$name = _( 'Quarter' );

			$parent_term = 'SEM';
			$parent_id = $_REQUEST['semester_id'];

			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS
				WHERE PARENT_ID='" . $_REQUEST['marking_period_id'] . "'";
		break;

		case 'PRO':
			$name = _( 'Progress Period' );

			$parent_term = 'QTR';
			$parent_id = $_REQUEST['quarter_id'];
		break;
	}

	if ( DeletePrompt( $name ) )
	{
		foreach ( (array) $extra as $sql )
			DBQuery( $sql );

		DBQuery( "DELETE FROM SCHOOL_MARKING_PERIODS
			WHERE MARKING_PERIOD_ID='" . $_REQUEST['marking_period_id'] . "'");

		unset( $_REQUEST['modfunc'] );
		unset( $_SESSION['_REQUEST_vars']['modfunc'] );

		$_REQUEST['mp_term'] = $parent_term;

		$_REQUEST['marking_period_id'] = $parent_id;
	}
}

if ( empty( $_REQUEST['modfunc'] ) )
{
	if ( isset( $error ) )
		echo ErrorMessage( $error );

	// ADDING & EDITING FORM
	if ( $_REQUEST['marking_period_id'] !== 'new' )
	{
		$RET = DBGet( DBQuery( "SELECT TITLE,SHORT_NAME,SORT_ORDER,DOES_GRADES,DOES_COMMENTS,
				START_DATE,END_DATE,POST_START_DATE,POST_END_DATE
			FROM SCHOOL_MARKING_PERIODS
			WHERE MARKING_PERIOD_ID='" . $_REQUEST['marking_period_id'] . "'" ) );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}

	$mp_href = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=' . $_REQUEST['mp_term'] . '&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'] . '&quarter_id=' . $_REQUEST['quarter_id'] . '&marking_period_id=' . $_REQUEST['marking_period_id'];

	$delete_button = '';

	if ( AllowEdit()
		&& $_REQUEST['marking_period_id'] !== 'new' )
	{
		$delete_URL = "'" . $mp_href . "&modfunc=delete'";

		$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_URL . ');" />';
	}

	echo '<form action="' . $mp_href . '" method="POST">';

	DrawHeader( $title, $delete_button . SubmitButton( _( 'Save' ) ) );

	$header .= '<table class="width-100p valign-top fixed-col"><tr class="st">';

	$header .= '<td>' . TextInput(
		$RET['TITLE'],
		'tables[' . $_REQUEST['marking_period_id'] . '][TITLE]',
		( ! $RET['TITLE'] ? '<span class="legend-red">' : '' ) . _( 'Title' ) . ( ! $RET['TITLE'] ? '</span>' : '' ),
		'required'
	) . '</td>';

	$header .= '<td>' . TextInput(
		$RET['SHORT_NAME'],
		'tables[' . $_REQUEST['marking_period_id'] . '][SHORT_NAME]',
		( ! $RET['SHORT_NAME'] ? '<span class="legend-red">' : '' ) . _( 'Short Name' ) . ( ! $RET['SHORT_NAME'] ? '</span>' : '' ),
		'required maxlength="10"'
	) . '</td>';

	$header .= '<td>' . TextInput(
		$RET['SORT_ORDER'],
		'tables[' . $_REQUEST['marking_period_id'] . '][SORT_ORDER]',
		_( 'Sort Order' ),
		'size="3"'
	) . '</td>';

	$header .= '<td><table class="width-100p"><tr>';

	$header .= '<td>' . CheckboxInput(
		$RET['DOES_GRADES'],
		'tables[' . $_REQUEST['marking_period_id'] . '][DOES_GRADES]',
		_( 'Graded' ),
		'',
		$_REQUEST['marking_period_id'] === 'new',
		button( 'check' ),
		button( 'x' )
	) . '</td>';

	$header .= '<td>' . CheckboxInput(
		$RET['DOES_COMMENTS'],
		'tables[' . $_REQUEST['marking_period_id'] . '][DOES_COMMENTS]',
		_( 'Comments' ),
		'',
		$_REQUEST['marking_period_id'] === 'new',
		button( 'check' ),
		button( 'x' )
	) . '</td>';

	$header .= '</tr></table></td></tr><tr class="st">';

	$required = $allow_na = $div = true;

	$header .= '<td>' . DateInput(
		$RET['START_DATE'],
		'tables[' . $_REQUEST['marking_period_id'] . '][START_DATE]',
		( ! $RET['START_DATE'] ? '<span class="legend-red">' : '' ) . _( 'Begins' ) . ( ! $RET['START_DATE'] ? '</span>' : '' ),
		$div,
		$allow_na,
		$required
	) . '</td>';

	$header .= '<td>' . DateInput(
		$RET['END_DATE'],
		'tables[' . $_REQUEST['marking_period_id'] . '][END_DATE]',
		( ! $RET['END_DATE'] ? '<span class="legend-red">' : '' ) . _( 'Ends' ) . ( ! $RET['END_DATE'] ? '</span>' : '' ),
		$div,
		$allow_na,
		$required
	) . '</td>';

	$required = $RET['DOES_GRADES'];

	$red = $RET['DOES_GRADES'] && ! $RET['POST_END_DATE'];

	$header .= '<td>' . DateInput(
		$RET['POST_START_DATE'],
		'tables[' . $_REQUEST['marking_period_id'] . '][POST_START_DATE]',
		( $red ? '<span class="legend-red">' : '' ) . _( 'Grade Posting Begins' ) . ( $red ? '</span>' : '' ),
		$div,
		$allow_na,
		$required
	) . '</td>';

	$header .= '<td>' . DateInput(
		$RET['POST_END_DATE'],
		'tables[' . $_REQUEST['marking_period_id'] . '][POST_END_DATE]',
		( $red ? '<span class="legend-red">' : '' ) . _( 'Grade Posting Ends' ) . ( $red ? '</span>' : '' ),
		$div,
		$allow_na,
		$required
	) . '</td>';

	$header .= '</tr></table>';

	DrawHeader( $header );

	echo '</form>';

	//unset($_SESSION['_REQUEST_vars']['marking_period_id']);
	//unset($_SESSION['_REQUEST_vars']['mp_term']);

	// DISPLAY THE MENU
	$LO_options = array( 'save' => false, 'search' => false, 'responsive' => false );

	// FY
	$fy_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID,TITLE
		FROM SCHOOL_MARKING_PERIODS
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "' ORDER BY SORT_ORDER" ) );

	if ( count( $fy_RET ) )
	{
		if ( $_REQUEST['mp_term'] )
		{
			if ( $_REQUEST['mp_term'] === 'FY' )
				$_REQUEST['year_id'] = $_REQUEST['marking_period_id'];

			foreach ( (array) $fy_RET as $key => $value )
			{
				if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['year_id'] )
					$fy_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	echo '<div class="st">';

	$columns = array( 'TITLE' => _( 'Year' ) );

	$link = array();

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=FY';

	$link['TITLE']['variables'] = array( 'marking_period_id' => 'MARKING_PERIOD_ID' );

	if ( !count( $fy_RET ) )
		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=FY&marking_period_id=new';

	ListOutput( $fy_RET, $columns, 'Year', 'Years', $link, array(), $LO_options );

	echo '</div>';

	// SEMESTERS
	if ( ( $_REQUEST['mp_term'] === 'FY'
			&& $_REQUEST['marking_period_id'] !== 'new' )
		|| $_REQUEST['mp_term'] === 'SEM'
		|| $_REQUEST['mp_term'] === 'QTR'
		|| $_REQUEST['mp_term'] === 'PRO' )
	{
		$sem_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID,TITLE
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='SEM'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND PARENT_ID='" . $_REQUEST['year_id'] . "'
			ORDER BY SORT_ORDER" ) );

		if ( count( $sem_RET ) )
		{
			if ( $_REQUEST['mp_term'] )
			{
				if ( $_REQUEST['mp_term'] === 'SEM' )
					$_REQUEST['semester_id'] = $_REQUEST['marking_period_id'];

				foreach ( (array) $sem_RET as $key => $value )
				{
					if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['semester_id'] )
						$sem_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
				}
			}
		}

		echo '<div class="st">';

		$columns = array( 'TITLE' => _( 'Semester' ) );

		$link = array();

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=SEM&year_id=' . $_REQUEST['year_id'];

		$link['TITLE']['variables'] = array( 'marking_period_id' => 'MARKING_PERIOD_ID' );

		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=SEM&marking_period_id=new&year_id=' . $_REQUEST['year_id'];

		ListOutput( $sem_RET, $columns, 'Semester', 'Semesters', $link, array(), $LO_options );

		echo '</div>';

		// QUARTERS
		if ( ( $_REQUEST['mp_term'] === 'SEM'
				&& $_REQUEST['marking_period_id'] !== 'new' )
			|| $_REQUEST['mp_term'] === 'QTR'
			|| $_REQUEST['mp_term'] === 'PRO' )
		{
			$qtr_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID,TITLE
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='QTR'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				AND PARENT_ID='" . $_REQUEST['semester_id'] . "'
				ORDER BY SORT_ORDER" ) );

			if ( count( $qtr_RET ) )
			{
				if ( ( $_REQUEST['mp_term'] === 'QTR'
					&& $_REQUEST['marking_period_id'] !== 'new' )
					|| $_REQUEST['mp_term'] === 'PRO' )
				{
					if ( $_REQUEST['mp_term']=='QTR')
						$_REQUEST['quarter_id'] = $_REQUEST['marking_period_id'];

					foreach ( (array) $qtr_RET as $key => $value )
					{
						if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['quarter_id'] )
							$qtr_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
					}
				}
			}

			echo '<div class="st">';

			$columns = array( 'TITLE' => _( 'Quarter' ) );

			$link = array();

			$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=QTR&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'];

			$link['TITLE']['variables'] = array( 'marking_period_id' => 'MARKING_PERIOD_ID' );

			$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=QTR&marking_period_id=new&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'];

			ListOutput( $qtr_RET, $columns, 'Quarter', 'Quarters', $link, array(), $LO_options );

			echo '</div>';

			// PROGRESS PERIODS
			if ( ( $_REQUEST['mp_term'] === 'QTR'
					&& $_REQUEST['marking_period_id'] !== 'new' )
				|| $_REQUEST['mp_term'] === 'PRO' )
			{
				$pro_RET = DBGet( DBQuery( "SELECT MARKING_PERIOD_ID,TITLE
					FROM SCHOOL_MARKING_PERIODS
					WHERE MP='PRO'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "'
					AND PARENT_ID='" . $_REQUEST['quarter_id'] . "'
					ORDER BY SORT_ORDER" ) );

				if ( count( $pro_RET ) )
				{
					if ( ( $_REQUEST['mp_term'] === 'PRO'
						&& $_REQUEST['marking_period_id'] !== 'new' ) )
					{
						$_REQUEST['progress_period_id'] = $_REQUEST['marking_period_id'];

						foreach ( (array) $pro_RET as $key => $value )
						{
							if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['marking_period_id'] )
								$pro_RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
						}
					}
				}

				echo '<div class="st">';

				$columns = array( 'TITLE' => _( 'Progress Period' ) );

				$link = array();

				$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=PRO&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'] . '&quarter_id=' . $_REQUEST['quarter_id'];

				$link['TITLE']['variables'] = array( 'marking_period_id' => 'MARKING_PERIOD_ID' );

				$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=PRO&marking_period_id=new&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'] . '&quarter_id=' . $_REQUEST['quarter_id'];

				ListOutput( $pro_RET, $columns, 'Progress Period', 'Progress Periods', $link, array(), $LO_options );

				echo '</div>';
			}
		}
	}
}

