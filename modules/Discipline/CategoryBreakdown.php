<?php

require_once 'ProgramFunctions/Charts.fnc.php';

$_REQUEST['category_id'] = issetVal( $_REQUEST['category_id'] );

DrawHeader( ProgramTitle() );

$min_date = DBGetOne( "SELECT min(SCHOOL_DATE) AS MIN_DATE
	FROM attendance_calendar
	WHERE SYEAR='" . UserSyear() . "'
	AND SCHOOL_ID='" . UserSchool() . "'" );

if ( ! $min_date )
{
	$min_date = date( 'Y-m' ) . '-01';
}

// Set start date.
$start_date = RequestedDate( 'start', $min_date, 'set' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

$chart_types = [ 'bar', 'pie', 'list' ];

// Set Chart Type.
if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'bar';
}

$chartline = false;

// Advanced Search
if ( $_REQUEST['modfunc'] === 'search' )
{
	echo '<br />';

	$extra['new'] = true;

	$extra['search_title'] = _( 'Advanced' );

	$extra['action'] = '&category_id=' . $_REQUEST['category_id'] .
		'&chart_type=' . $_REQUEST['chart_type'] .
		'&day_start=' . $_REQUEST['day_start'] .
		'&day_end=' . $_REQUEST['day_end'] .
		'&month_start=' . $_REQUEST['month_start'] .
		'&month_end=' . $_REQUEST['month_end'] .
		'&year_start=' . $_REQUEST['year_start'] .
		'&year_end=' . $_REQUEST['year_end'] .
		'&modfunc=&search_modfunc=';

	Search( 'student_id', $extra );
}

if ( ! empty( $_REQUEST['category_id'] ) )
{
	$category_RET = DBGet( "SELECT du.TITLE,du.SELECT_OPTIONS,df.DATA_TYPE
		FROM discipline_fields df,discipline_field_usage du
		WHERE df.ID='" . (int) $_REQUEST['category_id'] . "'
		AND du.DISCIPLINE_FIELD_ID=df.ID
		AND du.SYEAR='" . UserSyear() . "'
		AND du.SCHOOL_ID='" . UserSchool() . "'" );

	$category_RET[1]['SELECT_OPTIONS'] = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $category_RET[1]['SELECT_OPTIONS'] ) );

	$extra = [];

	$extra['FROM'] = ',discipline_referrals dr ';

	$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID
		AND dr.SCHOOL_ID=ssm.SCHOOL_ID
		AND dr.ENTRY_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";

	// Multiple Radio or Select
	if ( $category_RET[1]['DATA_TYPE'] === 'multiple_radio'
		|| $category_RET[1]['DATA_TYPE'] === 'select' )
	{
		$extra['SELECT_ONLY'] = "dr.CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE,COUNT(*) AS COUNT ";

		$extra['GROUP'] = 'CATEGORY_' . intval( $_REQUEST['category_id'] );

		$extra['group'] = [ 'TITLE' ];

		$totals_RET = GetStuList( $extra );

		foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;

			$chart['chart_data'][1][] = ( empty( $totals_RET[ $option ][1]['COUNT'] ) ? 0 : $totals_RET[ $option ][1]['COUNT'] );
		}
	}
	// Checkboxes
	elseif ( $category_RET[1]['DATA_TYPE'] === 'checkbox' )
	{
		$extra['SELECT_ONLY'] = "COALESCE(dr.CATEGORY_" . intval($_REQUEST['category_id']) . ",'N') AS TITLE,COUNT(*) AS COUNT ";

		$extra['GROUP'] = 'CATEGORY_' . intval( $_REQUEST['category_id'] );

		$extra['group'] = [ 'TITLE' ];

		$totals_RET = GetStuList( $extra );

		$chart['chart_data'][0][] = _( 'Yes' );

		$chart['chart_data'][1][] = ( empty( $totals_RET['Y'][1]['COUNT'] ) ? 0 : $totals_RET['Y'][1]['COUNT'] );

		$chart['chart_data'][0][] = _( 'No' );

		$chart['chart_data'][1][] = ( empty( $totals_RET['N'][1]['COUNT'] ) ? 0 : $totals_RET['N'][1]['COUNT'] );
	}
	// Multiple Checkboxes
	elseif ( $category_RET[1]['DATA_TYPE'] === 'multiple_checkbox' )
	{
		$extra['SELECT_ONLY'] = "CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE ";

		$referrals_RET = GetStuList( $extra );

		foreach ( (array) $referrals_RET as $referral )
		{
			$referral['TITLE'] = explode( "||", trim( (string) $referral['TITLE'], '|' ) );

			foreach ( (array) $referral['TITLE'] as $option )
			{
				if ( ! isset( $options_count[ $option ] ) )
				{
					$options_count[ $option ] = 0;
				}

				$options_count[ $option ]++;
			}
		}

		foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;

			$chart['chart_data'][1][] = isset( $options_count[ $option ] ) ?
				(int) $options_count[ $option ] : 0;
		}
	}
	// Numeric
	elseif ( $category_RET[1]['DATA_TYPE'] === 'numeric' )
	{

		$extra['SELECT_ONLY'] = "COALESCE(max(CATEGORY_" . intval( $_REQUEST['category_id'] ) . "),0) as MAX,COALESCE(min(CATEGORY_" . intval( $_REQUEST['category_id'] ) . "),0) AS MIN ";

		// Remove NULL entries.
		$extra['WHERE'] .= " AND CATEGORY_" . intval( $_REQUEST['category_id'] ) . " IS NOT NULL ";

		$max_min_RET = GetStuList( $extra );

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if ( $diff > 10
			&& $_REQUEST['chart_type'] !== 'bar' )
		{
			//FJ correct numeric chart
			for ( $i = 1; $i <= 10; $i++ )
			{
				/*$chart['chart_data'][0][ $i ] = (ceil($diff/5)*($i-1)).' - '.((ceil($diff/5)*$i)-1);
				$mins[ $i ] = (ceil($diff/5)*($i-1));
				$chart['chart_data'][1][ $i ] = 0;*/

				$chart['chart_data'][0][ $i ] = ( $max_min_RET[1]['MIN'] + ( ceil( $diff / 10 ) * ( $i - 1 ) ) ) . ' - ' .
					( $max_min_RET[1]['MIN'] + ( ( ceil( $diff / 10 ) * $i ) - 1 ) );

				$mins[ $i ] = ( $max_min_RET[1]['MIN'] + ( ceil( $diff / 10 ) * ( $i - 1 ) ) );

				$chart['chart_data'][1][ $i ] = 0;
			}
			//$chart['chart_data'][0][$i-1] = ($max_min_RET[1]['MIN'] + (ceil($diff/5)*($i-2))).'+';
			$mins[ $i ] = ( ceil( $diff / 10 ) * ( $i - 1 ) );
		}
		else //FJ transform bar chart in line chart
		{
			$chartline = true;
		}

		$extra['SELECT_ONLY'] = "CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE";

		$extra['functions'] = [ 'TITLE' => 'makeNumeric' ];

		$referrals_RET = GetStuList( $extra );

		if ( ! $referrals_RET ) //FJ bugfix no results for numeric fields chart
			$chart['chart_data'][0][0] = $chart['chart_data'][1][0] = 0;

		ksort( $chart['chart_data'][0] );

		ksort( $chart['chart_data'][1] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$categories_RET = DBGet( "SELECT df.ID,du.TITLE,du.SELECT_OPTIONS
		FROM discipline_fields df,discipline_field_usage du
		WHERE df.DATA_TYPE NOT IN ('textarea','text','date')
		AND du.SYEAR='" . UserSyear() . "'
		AND du.SCHOOL_ID='" . UserSchool() . "'
		AND du.DISCIPLINE_FIELD_ID=df.ID
		ORDER BY du.SORT_ORDER IS NULL,du.SORT_ORDER" );

	$select_options = [];

	foreach ( (array) $categories_RET as $category )
	{
		$select_options[$category['ID']] = $category['TITLE'];
	}

	$select = SelectInput(
		$_REQUEST['category_id'],
		'category_id',
		'<span class="a11y-hidden">' . _( 'Category' ) . '</span>',
		$select_options,
		_( 'Please choose a category' ),
		'onchange="ajaxPostForm(this.form,true);"',
		false
	);

	$advanced_link = ' <a href="' . PreparePHP_SELF( $_REQUEST, [ 'search_modfunc' ], [
		'modfunc' => 'search',
		'include_top' => 'false',
	] ) . '">' . _( 'Advanced' ) . '</a>';

	DrawHeader( $select );

	DrawHeader(
		_( 'Report Timeframe' ) . ': ' .
			PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
			PrepareDate( $end_date, '_end', false ) . ' ' .
			SubmitButton( _( 'Go' ) ),
		$advanced_link
	);

	if ( ! empty( $_ROSARIO['SearchTerms'] ) )
	{
		DrawHeader( $_ROSARIO['SearchTerms'] );
	}

	echo '<br />';

	if ( ! empty( $_REQUEST['category_id'] ) )
	{
		if ( $chartline )
		{
			// For Chart Type to bar if Line.
			if ( $_REQUEST['chart_type'] === 'pie' )
			{
				$_REQUEST['chart_type'] = 'bar';
			}

			$tabs = [
				[
					'title' => _( 'Line' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
				],
				[
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
				]
			];
		}
		else
		{
			$tabs = [
				[
					'title' => _( 'Column' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'bar' ] ),
				],
				[
					'title' => _( 'Pie' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'pie' ] ),
				],
				[
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, [], [ 'chart_type' => 'list' ] ),
				]
			];
		}

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

		if ( $_REQUEST['chart_type'] === 'list' )
		{
			$chart_data = [ '0' => '' ];

			foreach ( (array) $chart['chart_data'][1] as $key => $value )
			{
				$chart_data[] = [ 'TITLE' => $chart['chart_data'][0][ $key ], 'VALUE' => $value ];
			}

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			$LO_bars = [ 'TITLE' => _( 'Option' ), 'VALUE' => _( 'Number of Referrals' ) ];

			ListOutput( $chart_data, $LO_bars, 'Option', 'Options', [], [], $LO_options );
		}
		// Chart.js charts.
		else
		{
			$search_terms = '';

			if ( ! empty( $_ROSARIO['SearchTerms'] ) )
			{
				$search_terms = ' - ' . strip_tags( str_replace( '<br />', " - ", mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) ));
			}

			$chart_title = sprintf( _( '%s Breakdown' ), ParseMLField( $category_RET[1]['TITLE'] ) ) . $search_terms;

			if ( $_REQUEST['chart_type'] === 'pie' )
			{
				foreach ( (array) $chart['chart_data'][0] as $index => $label )
				{
					if ( ! is_numeric( $chart['chart_data'][1][ $index ] ) )
					{
						continue;
					}

					// Limit label to 30 char max.
					$chart['chart_data'][0][ $index ] = mb_substr( $label, 0, 30 );
				}
			}

			echo ChartjsChart(
				$chartline ? 'line' : $_REQUEST['chart_type'],
				$chart['chart_data'],
				$chart_title
			);
		}

		PopTable( 'footer' );
	}
	echo '</form>';
}
