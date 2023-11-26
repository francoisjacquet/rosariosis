<?php

require_once 'modules/School_Setup/includes/MarkingPeriods.fnc.php';

DrawHeader( ProgramTitle() );

// Default MP ID to Full Year.

if ( empty( $_REQUEST['marking_period_id'] ) )
{
	$_REQUEST['marking_period_id'] = GetFullYearMP() ? GetFullYearMP() : 'new';

	$_REQUEST['mp_term'] = 'FY';
}

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
else
{
	$_REQUEST['marking_period_id'] = (string) (int) $_REQUEST['marking_period_id'];
}

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

// UPDATING

if ( ! empty( $_POST['tables'] )
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		//FJ fix SQL bug invalid sort order

		if ( isset( $columns['SORT_ORDER'] )
			&& $columns['SORT_ORDER'] !== ''
			&& ! is_numeric( $columns['SORT_ORDER'] ) )
		{
			$error[] = _( 'Please enter a valid Sort Order.' );

			break 1;
		}

		// UPDATE

		if ( $id !== 'new' )
		{
			foreach ( (array) $columns as $column => $value )
			{
				if ( $column === 'START_DATE'
					|| $column === 'END_DATE'
					|| $column === 'POST_START_DATE'
					|| $column === 'POST_END_DATE' )
				{
					//FJ fix SQL bug START_DATE or END_DATE is null

					if (  ( ! VerifyDate( $value )
						&& $value !== '' )
						|| ( ( $column === 'START_DATE' || $column === 'END_DATE' )
							&& $value === '' ) )
					{
						$error[] = _( 'Not all of the dates were entered correctly.' );

						break 2;
					}

					//FJ verify END_DATE > START_DATE
					$mp_dates_RET = DBGet( "SELECT START_DATE, END_DATE, POST_START_DATE, POST_END_DATE
						FROM school_marking_periods
						WHERE MARKING_PERIOD_ID='" . (int) $id . "'" );

					$start_date = ! empty( $columns['START_DATE'] ) ?
						$columns['START_DATE'] :
						$mp_dates_RET[1]['START_DATE'];

					$end_date = ! empty( $columns['END_DATE'] ) ?
						$columns['END_DATE'] :
						$mp_dates_RET[1]['END_DATE'];

					$post_start_date = ! empty( $columns['POST_START_DATE'] ) ?
						$columns['POST_START_DATE'] :
						$mp_dates_RET[1]['POST_START_DATE'];

					$post_end_date = ! empty( $columns['POST_END_DATE'] ) ?
						$columns['POST_END_DATE'] :
						$mp_dates_RET[1]['POST_END_DATE'];

					if (  ( $column === 'END_DATE'
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
			}

			$go = true;

			$sql = DBUpdateSQL(
				'school_marking_periods',
				$columns,
				[ 'MARKING_PERIOD_ID' => (int) $id ]
			);
		}

		// New: check for Title.
		elseif ( $columns['TITLE'] )
		{
			$insert_columns = [
				'MP' => $_REQUEST['mp_term'],
				'SYEAR' => UserSyear(),
				'SCHOOL_ID' => UserSchool(),
			];

			switch ( $_REQUEST['mp_term'] )
			{
				case 'SEM':
					$insert_columns['PARENT_ID'] = (int) $_REQUEST['year_id'];
					break;

				case 'QTR':
					$insert_columns['PARENT_ID'] = (int) $_REQUEST['semester_id'];
					break;

				case 'PRO':
					$insert_columns['PARENT_ID'] = (int) $_REQUEST['quarter_id'];
					break;
			}

			foreach ( (array) $columns as $column => $value )
			{
				if ( $column === 'START_DATE'
					|| $column === 'END_DATE'
					|| $column === 'POST_START_DATE'
					|| $column === 'POST_END_DATE' )
				{
					//FJ fix SQL bug START_DATE or END_DATE is null

					if ( ! VerifyDate( $value )
						&& $value !== ''
						|| ( ( $column === 'START_DATE'
							|| $column === 'END_DATE' )
							&& $value === '' ) )
					{
						$error[] = _( 'Not all of the dates were entered correctly.' );

						break 2;
					}

					//FJ verify END_DATE > START_DATE

					if (  ( $column === 'END_DATE'
						&& date_create( $value ) <= date_create( $columns['START_DATE'] ) )
						|| ( $column === 'POST_START_DATE'
							&& $columns['POST_END_DATE'] !== ''
							&& date_create( $value ) > date_create( $columns['POST_END_DATE'] ) ) )
					{
						$error[] = _( 'Start date must be anterior to end date.' );

						break 2;
					}
				}
			}

			$go = true;

			$sql = DBInsertSQL(
				'school_marking_periods',
				$insert_columns + $columns
			);
		}

		// CHECK TO MAKE SURE ONLY ONE MP & ONE GRADING PERIOD IS OPEN AT ANY GIVEN TIME
		$dates_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM school_marking_periods
			WHERE MP='" . $_REQUEST['mp_term'] . "'
			AND ( true=false" .
			( ! empty( $columns['START_DATE'] ) ? " OR '" . $columns['START_DATE'] .
				"' BETWEEN START_DATE AND END_DATE" : '' ) .
			( ! empty( $columns['END_DATE'] ) ? " OR '" . $columns['END_DATE'] .
				"' BETWEEN START_DATE AND END_DATE" : '' ) .
			( ! empty( $columns['START_DATE'] ) && ! empty( $columns['END_DATE'] ) ?
				" OR START_DATE BETWEEN '" . $columns['START_DATE'] . "' AND '" . $columns['END_DATE'] . "'" .
				" OR END_DATE BETWEEN '" . $columns['START_DATE'] . "' AND '" . $columns['END_DATE'] . "'" : '' ) . ")
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" .
			( $id !== 'new' ? " AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID!='" . (int) $id . "'" : '' ) );

		$posting_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM school_marking_periods
			WHERE MP='" . $_REQUEST['mp_term'] . "'
			AND ( true=false" .
			( ! empty( $columns['POST_START_DATE'] ) ? " OR '" . $columns['POST_START_DATE'] .
				"' BETWEEN POST_START_DATE AND POST_END_DATE" : '' ) .
			( ! empty( $columns['POST_END_DATE'] ) ? " OR '" . $columns['POST_END_DATE'] .
				"' BETWEEN POST_START_DATE AND POST_END_DATE" : '' ) .
			( ! empty( $columns['POST_START_DATE'] ) && ! empty( $columns['POST_END_DATE'] ) ?
				" OR POST_START_DATE BETWEEN '" . $columns['POST_START_DATE'] . "' AND '" . $columns['POST_END_DATE'] . "'" .
				" OR POST_END_DATE BETWEEN '" . $columns['POST_START_DATE'] . "' AND '" . $columns['POST_END_DATE'] . "'" : '' ) . ")
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'" .
			( $id !== 'new' ? " AND MARKING_PERIOD_ID!='" . (int) $id . "'" : '' ) );

		if ( ! empty( $dates_RET ) )
		{
			$error[] = sprintf(
				_( 'The beginning and end dates you specified for this marking period overlap with those of "%s".' ),
				GetMP( $dates_RET[1]['MARKING_PERIOD_ID'] )
			) . ' ' .
			_( 'Only one marking period can be open at any time.' );

			$go = false;
		}

		if ( ! empty( $posting_RET ) )
		{
			$error[] = sprintf(
				_( 'The grade posting dates you specified for this marking period overlap with those of "%s".' ),
				GetMP( $posting_RET[1]['MARKING_PERIOD_ID'] )
			) . ' ' .
			_( 'Only one grade posting period can be open at any time.' );

			$go = false;
		}

		if ( $go )
		{
			DBQuery( $sql );

			if ( $id === 'new' )
			{
				$_REQUEST['marking_period_id'] = DBLastInsertID();
			}
		}
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables' ] );
}

// DELETING

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	switch ( $_REQUEST['mp_term'] )
	{
		case 'FY':
			$name = _( 'Year' );

			$parent_term = '';
			$parent_id = '';
			break;

		case 'SEM':
			$name = _( 'Semester' );

			$parent_term = 'FY';
			$parent_id = issetVal( $_REQUEST['year_id'] );
			break;

		case 'QTR':
			$name = _( 'Quarter' );

			$parent_term = 'SEM';
			$parent_id = issetVal( $_REQUEST['semester_id'] );
			break;

		case 'PRO':
			$name = _( 'Progress Period' );

			$parent_term = 'QTR';
			$parent_id = issetVal( $_REQUEST['quarter_id'] );
			break;
	}

	if ( DeletePrompt( $name ) )
	{
		DBQuery( MarkingPeriodDeleteSQL( $_REQUEST['marking_period_id'], $_REQUEST['mp_term'] ) );

		$_REQUEST['mp_term'] = $parent_term;

		$_REQUEST['marking_period_id'] = $parent_id;

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	if ( $_REQUEST['marking_period_id']
		&& $_REQUEST['marking_period_id'] !== 'new' )
	{
		// Check marking period ID is valid for current school & syear!
		$marking_period_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM school_marking_periods
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND MARKING_PERIOD_ID='" . (int) $_REQUEST['marking_period_id'] . "'" );

		if ( ! $marking_period_RET )
		{
			$_REQUEST['marking_period_id'] = GetFullYearMP() ? GetFullYearMP() : 'new';

			$_REQUEST['mp_term'] = 'FY';

			// Unset year & semester & quarter IDs & redirect URL.
			RedirectURL( [ 'year_id', 'semester_id', 'quarter_id' ] );
		}

		if ( AllowEdit()
			&& $_REQUEST['marking_period_id'] !== 'new'
			&& $_REQUEST['marking_period_id'] !== GetFullYearMP() )
		{
			// @since 6.6 Add warning when Marking Period dates are not within Parent MP dates range.
			$parent_mp_type = GetMP( $_REQUEST['marking_period_id'], 'MP' ) === 'SEM' ? 'FY' :
				( GetMP( $_REQUEST['marking_period_id'], 'MP' ) === 'QTR' ? 'SEM' : 'QTR' );

			$parent_mp_id = GetParentMP( $parent_mp_type, $_REQUEST['marking_period_id'] );

			$parent_mp_end_date = GetMP( $parent_mp_id, 'END_DATE' );

			$mp_end_date = GetMP( $_REQUEST['marking_period_id'], 'END_DATE' );

			$parent_mp_start_date = GetMP( $parent_mp_id, 'START_DATE' );

			$mp_start_date = GetMP( $_REQUEST['marking_period_id'], 'START_DATE' );

			if ( $mp_end_date > $parent_mp_end_date )
			{
				$warning[] = _( 'End date for current Marking Period is posterior to parent Marking Period\'s end date.' );
			}

			if ( $mp_start_date < $parent_mp_start_date )
			{
				$warning[] = _( 'Start date for current Marking Period is anterior to parent Marking Period\'s start date.' );
			}
		}
	}

	echo ErrorMessage( $warning, 'warning' );

	// ADDING & EDITING FORM.

	if ( $_REQUEST['marking_period_id'] !== 'new' )
	{
		$RET = DBGet( "SELECT TITLE,SHORT_NAME,SORT_ORDER,DOES_GRADES,DOES_COMMENTS,
				START_DATE,END_DATE,POST_START_DATE,POST_END_DATE
			FROM school_marking_periods
			WHERE MARKING_PERIOD_ID='" . (int) $_REQUEST['marking_period_id'] . "'" );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}

	$mp_href = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=' . $_REQUEST['mp_term'] .
		'&year_id=' . issetVal( $_REQUEST['year_id'] ) .
		'&semester_id=' . issetVal( $_REQUEST['semester_id'] ) .
		'&quarter_id=' . issetVal( $_REQUEST['quarter_id'] ) .
		'&marking_period_id=' . $_REQUEST['marking_period_id'];

	$delete_button = '';

	if ( AllowEdit()
		&& $_REQUEST['marking_period_id'] !== 'new' )
	{
		// Is Single Marking Period? Do NOT delete.
		$not_single_mp = $_REQUEST['mp_term'] !== 'FY' || $_REQUEST['mp_term'] === 'PRO';

		if ( $_REQUEST['mp_term'] !== 'FY'
			&& $_REQUEST['mp_term'] !== 'PRO' )
		{
			$mp_count = DBGetOne( "SELECT COUNT(MARKING_PERIOD_ID)
				FROM school_marking_periods
				WHERE MP='" . $_REQUEST['mp_term'] . "'
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			$not_single_mp = $mp_count > 1;
		}

		if ( $not_single_mp )
		{
			// @since 5.0 MP has Course Periods? Do NOT delete.
			$can_delete = DBTransDryRun(
				MarkingPeriodDeleteSQL( $_REQUEST['marking_period_id'], $_REQUEST['mp_term'] )
			);

			if ( $can_delete )
			{
				$delete_URL = URLEscape( $mp_href . "&modfunc=delete" );

				$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
					'" onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $delete_URL ) . ');' ) . '" />';
			}
		}
	}

	echo '<form action="' . URLEscape( $mp_href ) . '" method="POST">';

	DrawHeader( $title, $delete_button . SubmitButton() );

	$header = '<table class="width-100p valign-top fixed-col"><tr class="st">';

	$header .= '<td>' . TextInput(
		issetVal( $RET['TITLE'], '' ),
		'tables[' . $_REQUEST['marking_period_id'] . '][TITLE]',
		_( 'Title' ),
		'required maxlength="50"'
	) . '</td>';

	$header .= '<td>' . TextInput(
		issetVal( $RET['SHORT_NAME'], '' ),
		'tables[' . $_REQUEST['marking_period_id'] . '][SHORT_NAME]',
		_( 'Short Name' ),
		'required maxlength="10"' .
			( $_REQUEST['marking_period_id'] === 'new' ? ' size="3"' : '' )
	) . '</td>';

	if ( AllowEdit() )
	{
		// Hide Sort Order from non editing users.
		$header .= '<td>' . TextInput(
			issetVal( $RET['SORT_ORDER'], '' ),
			'tables[' . $_REQUEST['marking_period_id'] . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td></tr>';
	}

	// @since 4.1 Grade posting date inputs are required when "Graded" is checked.
	$header .= '<script>var mpGradedOnclickPostDatesRequired = function(el) {
		var dates = ["month", "day", "year"],
			dateStartInput,
			dateEndInput;

		for (var i=0,max=dates.length; i<max; i++) {
			dateStartInput = document.getElementsByName( dates[i] + "_tables[' . $_REQUEST['marking_period_id'] .
			'][POST_START_DATE]" )[0];
			dateEndInput = document.getElementsByName( dates[i] + "_tables[' . $_REQUEST['marking_period_id'] .
			'][POST_END_DATE]" )[0];

			dateStartInput.required = dateEndInput.required = el.checked;
		}

		// Add .legend-red CSS class to label if input is required/
		$(dateStartInput).parent().nextAll(".legend-gray").toggleClass("legend-red", el.checked);
		$(dateEndInput).parent().nextAll(".legend-gray").toggleClass("legend-red", el.checked);
	};</script>';

	$js_onclick_post_dates_required = 'onclick="mpGradedOnclickPostDatesRequired( this );"';

	$header .= '<tr class="st"><td>' . CheckboxInput(
		issetVal( $RET['DOES_GRADES'], '' ),
		'tables[' . $_REQUEST['marking_period_id'] . '][DOES_GRADES]',
		_( 'Graded' ),
		'',
		$_REQUEST['marking_period_id'] === 'new',
		button( 'check' ),
		button( 'x' ),
		true,
		$js_onclick_post_dates_required
	) . '</td>';

	if ( AllowEdit()
		|| ! empty( $RET['DOES_GRADES'] ) )
	{
		// Hide Comments from non editing users if MP not Graded.
		$header .= '<td>' . CheckboxInput(
			issetVal( $RET['DOES_COMMENTS'], '' ),
			'tables[' . $_REQUEST['marking_period_id'] . '][DOES_COMMENTS]',
			_( 'Comments' ),
			'',
			$_REQUEST['marking_period_id'] === 'new',
			button( 'check' ),
			button( 'x' )
		) . '</td>';
	}

	$header .= '</tr><tr><td colspan="3"><hr></td></tr>';

	$required = $allow_na = $div = true;

	$header .= '<tr class="st"><td>' . DateInput(
		issetVal( $RET['START_DATE'], '' ),
		'tables[' . $_REQUEST['marking_period_id'] . '][START_DATE]',
		_( 'Begins' ),
		$div,
		$allow_na,
		$required
	) . '</td>';

	$header .= '<td>' . DateInput(
		issetVal( $RET['END_DATE'], '' ),
		'tables[' . $_REQUEST['marking_period_id'] . '][END_DATE]',
		_( 'Ends' ),
		$div,
		$allow_na,
		$required
	) . '</td></tr>';

	$required = ! empty( $RET['DOES_GRADES'] );

	$red = ! empty( $RET['DOES_GRADES'] ) && empty( $RET['POST_END_DATE'] );

	if ( AllowEdit()
		|| ! empty( $RET['DOES_GRADES'] ) )
	{
		// Hide Grade Posting Dates from non editing users if MP not Graded.
		$header .= '<tr class="st"><td>' . DateInput(
			issetVal( $RET['POST_START_DATE'], '' ),
			'tables[' . $_REQUEST['marking_period_id'] . '][POST_START_DATE]',
			( $red ? '<span class="legend-red">' : '' ) . _( 'Grade Posting Begins' ) . ( $red ? '</span>' : '' ),
			$div,
			$allow_na,
			$required
		) . '</td>';

		$header .= '<td>' . DateInput(
			issetVal( $RET['POST_END_DATE'], '' ),
			'tables[' . $_REQUEST['marking_period_id'] . '][POST_END_DATE]',
			( $red ? '<span class="legend-red">' : '' ) . _( 'Grade Posting Ends' ) . ( $red ? '</span>' : '' ),
			$div,
			$allow_na,
			$required
		) . '</td></tr>';
	}

	$header .= '</table>';

	DrawHeader( $header );

	echo '</form>';

	// DISPLAY THE MENU
	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	// FY
	$fy_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
		FROM school_marking_periods
		WHERE MP='FY'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "' ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	if ( ! empty( $fy_RET ) )
	{
		if ( ! empty( $_REQUEST['mp_term'] ) )
		{
			if ( $_REQUEST['mp_term'] === 'FY' )
			{
				$_REQUEST['year_id'] = issetVal( $_REQUEST['marking_period_id'] );
			}

			foreach ( (array) $fy_RET as $key => $value )
			{
				if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['year_id'] )
				{
					$fy_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
				}
			}
		}
	}

	echo '<div class="st">';

	$columns = [ 'TITLE' => _( 'Year' ) ];

	$link = [];

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=FY';

	$link['TITLE']['variables'] = [ 'marking_period_id' => 'MARKING_PERIOD_ID' ];

	if ( empty( $fy_RET ) )
	{
		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=FY&marking_period_id=new';
	}

	ListOutput( $fy_RET, $columns, 'Year', 'Years', $link, [], $LO_options );

	echo '</div>';

	// SEMESTERS

	if (  ( $_REQUEST['mp_term'] === 'FY'
		&& $_REQUEST['marking_period_id'] !== 'new' )
		|| $_REQUEST['mp_term'] === 'SEM'
		|| $_REQUEST['mp_term'] === 'QTR'
		|| $_REQUEST['mp_term'] === 'PRO' )
	{
		$sem_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
			FROM school_marking_periods
			WHERE MP='SEM'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND PARENT_ID='" . (int) $_REQUEST['year_id'] . "'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

		if ( ! empty( $sem_RET ) )
		{
			if ( ! empty( $_REQUEST['mp_term'] ) )
			{
				if ( $_REQUEST['mp_term'] === 'SEM' )
				{
					$_REQUEST['semester_id'] = issetVal( $_REQUEST['marking_period_id'] );
				}

				foreach ( (array) $sem_RET as $key => $value )
				{
					if ( ! empty( $_REQUEST['semester_id'] )
						&& $value['MARKING_PERIOD_ID'] === $_REQUEST['semester_id'] )
					{
						$sem_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
					}
				}
			}
		}

		echo '<div class="st">';

		$columns = [ 'TITLE' => _( 'Semester' ) ];

		$link = [];

		$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=SEM&year_id=' . $_REQUEST['year_id'];

		$link['TITLE']['variables'] = [ 'marking_period_id' => 'MARKING_PERIOD_ID' ];

		$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=SEM&marking_period_id=new&year_id=' . $_REQUEST['year_id'];

		ListOutput( $sem_RET, $columns, 'Semester', 'Semesters', $link, [], $LO_options );

		echo '</div>';

		// QUARTERS

		if ( ( $_REQUEST['mp_term'] === 'SEM'
				&& $_REQUEST['marking_period_id'] !== 'new' )
			|| $_REQUEST['mp_term'] === 'QTR'
			|| $_REQUEST['mp_term'] === 'PRO' )
		{
			$qtr_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
				FROM school_marking_periods
				WHERE MP='QTR'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'
				AND PARENT_ID='" . (int) $_REQUEST['semester_id'] . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

			if ( ! empty( $qtr_RET ) )
			{
				if ( ( $_REQUEST['mp_term'] === 'QTR'
						&& $_REQUEST['marking_period_id'] !== 'new' )
					|| $_REQUEST['mp_term'] === 'PRO' )
				{
					if ( $_REQUEST['mp_term'] == 'QTR' )
					{
						$_REQUEST['quarter_id'] = issetVal( $_REQUEST['marking_period_id'] );
					}

					foreach ( (array) $qtr_RET as $key => $value )
					{
						if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['quarter_id'] )
						{
							$qtr_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
						}
					}
				}
			}

			echo '<div class="st">';

			$columns = [ 'TITLE' => _( 'Quarter' ) ];

			$link = [];

			$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=QTR&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'];

			$link['TITLE']['variables'] = [ 'marking_period_id' => 'MARKING_PERIOD_ID' ];

			$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=QTR&marking_period_id=new&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'];

			ListOutput( $qtr_RET, $columns, 'Quarter', 'Quarters', $link, [], $LO_options );

			echo '</div>';

			// PROGRESS PERIODS

			if ( ( $_REQUEST['mp_term'] === 'QTR'
					&& $_REQUEST['marking_period_id'] !== 'new' )
				|| $_REQUEST['mp_term'] === 'PRO' )
			{
				$pro_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
					FROM school_marking_periods
					WHERE MP='PRO'
					AND SCHOOL_ID='" . UserSchool() . "'
					AND SYEAR='" . UserSyear() . "'
					AND PARENT_ID='" . (int) $_REQUEST['quarter_id'] . "'
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

				if ( ! empty( $pro_RET ) )
				{
					if ( $_REQUEST['mp_term'] === 'PRO'
						&& $_REQUEST['marking_period_id'] !== 'new' )
					{
						$_REQUEST['progress_period_id'] = issetVal( $_REQUEST['marking_period_id'] );

						foreach ( (array) $pro_RET as $key => $value )
						{
							if ( $value['MARKING_PERIOD_ID'] === $_REQUEST['marking_period_id'] )
							{
								$pro_RET[$key]['row_color'] = Preferences( 'HIGHLIGHT' );
							}
						}
					}
				}

				echo '<div class="st">';

				$columns = [ 'TITLE' => _( 'Progress Period' ) ];

				$link = [];

				$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=' . $_REQUEST['modfunc'] . '&mp_term=PRO&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'] . '&quarter_id=' . $_REQUEST['quarter_id'];

				$link['TITLE']['variables'] = [ 'marking_period_id' => 'MARKING_PERIOD_ID' ];

				$link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_term=PRO&marking_period_id=new&year_id=' . $_REQUEST['year_id'] . '&semester_id=' . $_REQUEST['semester_id'] . '&quarter_id=' . $_REQUEST['quarter_id'];

				ListOutput( $pro_RET, $columns, 'Progress Period', 'Progress Periods', $link, [], $LO_options );

				echo '</div>';
			}
		}
	}
}
