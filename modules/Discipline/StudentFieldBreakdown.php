<?php

require_once 'ProgramFunctions/Charts.fnc.php';

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', '', 'set' );

if ( empty( $start_date ) )
{
	$min_date = DBGetOne( "SELECT min(SCHOOL_DATE) AS MIN_DATE
		FROM ATTENDANCE_CALENDAR
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( $min_date )
	{
		$start_date = $min_date;
	}
	else
		$start_date = date( 'Y-m' ) . '-01';
}

// Set end date.
$end_date = RequestedDate( 'end', DBDate(), 'set' );

$chart_types = array( 'column', 'pie', 'list' );

// set Chart Type
if ( !isset( $_REQUEST['chart_type'] )
	|| !in_array( $_REQUEST['chart_type'], $chart_types ) )
	$_REQUEST['chart_type'] = 'column';

$chartline = false;

// Advanced Search
if ( $_REQUEST['modfunc'] === 'search' )
{
	echo '<br />';

	$extra['new'] = true;

	$extra['search_title'] = _( 'Advanced' );

	$extra['action'] = '&field_id=' . $_REQUEST['field_id'] .
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

if ( isset( $_REQUEST['field_id'] )
	&& !empty( $_REQUEST['field_id'] ) )
{
	$fields_RET = DBGet( "SELECT TITLE,SELECT_OPTIONS AS OPTIONS,TYPE
		FROM CUSTOM_FIELDS WHERE ID='" . $_REQUEST['field_id'] . "'" );

	if ( $fields_RET[1]['OPTIONS'] ) // Fixes array( 0 => '' ) when no options
		$fields_RET[1]['OPTIONS'] = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $fields_RET[1]['OPTIONS'] ) );


	$extra = array();

	$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';

	$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID
		AND dr.SCHOOL_ID=ssm.SCHOOL_ID
		AND dr.ENTRY_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";

	if ( in_array( $fields_RET[1]['TYPE'], array( 'select', 'autos', 'exports' ) ) )
	{
		// autos & edits pull-down fields
		if ( $fields_RET[1]['TYPE'] === 'autos' )
		{
			// add values found in current year
			$options_RET = DBGet( "SELECT DISTINCT s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",upper(s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ") AS KEY
				FROM STUDENTS s,STUDENT_ENROLLMENT sse
				WHERE sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "')
				AND s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " IS NOT NULL
				AND s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . " != ''
				ORDER BY KEY" );

			foreach ( (array) $options_RET as $option )
			{
				if ( ! $fields_RET[1]['OPTIONS']
					|| !in_array( $option['CUSTOM_' . intval( $_REQUEST['field_id'] )], $fields_RET[1]['OPTIONS'] ) )
					$fields_RET[1]['OPTIONS'][] = $option['CUSTOM_' . intval( $_REQUEST['field_id'] )];
			}
		}

		$extra['SELECT_ONLY'] = "COALESCE(s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",'*BLANK*') AS TITLE,COUNT(*) AS COUNT ";

		$extra['GROUP'] = 'CUSTOM_' . intval( $_REQUEST['field_id'] );

		$extra['group'] = array( 'TITLE' );

		$totals_RET = GetStuList( $extra );

		$chart['chart_data'][0][] = _( 'No Value' );

		$chart['chart_data'][1][] = (int)$totals_RET['*BLANK*'][1]['COUNT'];

		foreach ( (array) $fields_RET[1]['OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;

			$chart['chart_data'][1][] = (int)$totals_RET[ $option ][1]['COUNT'];
		}
	}
	elseif ( $fields_RET[1]['TYPE'] === 'multiple' )
	{
		$extra['SELECT_ONLY'] = "CUSTOM_" . intval( $_REQUEST['field_id'] ) . " AS TITLE ";

		$referrals_RET = GetStuList( $extra );

		foreach ( (array) $referrals_RET as $referral )
		{
			$referral['TITLE'] = explode( "||", trim( $referral['TITLE'], '|' ) );

			foreach ( (array) $referral['TITLE'] as $option )
				$options_count[ $option ]++;
		}

		foreach ( (array) $fields_RET[1]['OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;

			$chart['chart_data'][1][] = (int)$options_count[ $option ];
		}
	}
	elseif ( $fields_RET[1]['TYPE'] === 'radio' )
	{
		$extra['SELECT_ONLY'] = "COALESCE(s.CUSTOM_" . intval( $_REQUEST['field_id'] ) . ",'N') AS TITLE,COUNT(*) AS COUNT ";

		$extra['GROUP'] = 'CUSTOM_' . intval( $_REQUEST['field_id'] );

		$extra['group'] = array( 'TITLE' );

		$totals_RET = GetStuList( $extra );

		$chart['chart_data'][0][] = _( 'Yes' );

		$chart['chart_data'][1][] = (int) $totals_RET['Y'][1]['COUNT'];

		$chart['chart_data'][0][] = _( 'No' );

		$chart['chart_data'][1][] = (int) $totals_RET['N'][1]['COUNT'];
	}
	elseif ( $fields_RET[1]['TYPE'] === 'numeric' )
	{
		$extra['SELECT_ONLY'] = "COALESCE(max(CUSTOM_" . intval( $_REQUEST['field_id'] ) . "),0) as MAX,COALESCE(min(CUSTOM_" . intval( $_REQUEST['field_id'] ) . "),0) AS MIN ";

		//FJ remove NULL entries
		$extra['WHERE'] .= "AND CUSTOM_" . intval( $_REQUEST['field_id'] ) . " IS NOT NULL ";

		$max_min_RET = GetStuList( $extra );

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if ( $diff > 10
			&& $_REQUEST['chart_type'] !== 'column' )
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
		else //FJ transform column chart in line chart
		{
			$chartline = true;
		}

		$extra['SELECT_ONLY'] = "CUSTOM_" . intval( $_REQUEST['field_id'] ) . " AS TITLE";

		$extra['functions'] = array( 'TITLE' => 'makeNumeric' );

		$referrals_RET = GetStuList( $extra );

		if ( ! $referrals_RET ) //FJ bugfix no results for numeric fields chart
			$chart['chart_data'][0][0] = $chart['chart_data'][1][0] = 0;
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$fields_RET = DBGet( "SELECT ID,TITLE,SELECT_OPTIONS AS OPTIONS,CATEGORY_ID
		FROM CUSTOM_FIELDS
		WHERE TYPE NOT IN ('textarea','text','date','log','holder')
		ORDER BY SORT_ORDER,TITLE", array(), array( 'CATEGORY_ID' ) );

	$categories_RET = DBGet( "SELECT ID,TITLE
		FROM STUDENT_FIELD_CATEGORIES", array(), array( 'ID' ) );

	$select = '<select name=field_id onchange="ajaxPostForm(this.form,true);">';

	$select .= '<option value="">' . _( 'Please choose a student field' ) . '</option>';

	foreach ( (array) $fields_RET as $field_id => $fields )
	{
		$select .= '<optgroup label="' . ParseMLField( $categories_RET[ $field_id ][1]['TITLE'] ) . '">';

		foreach ( (array) $fields as $field )
		{
			$selected = '';

			if ( $_REQUEST['field_id'] == $field['ID'] )
			{
				$selected = ' selected';
				$field_title = $field['TITLE'];
			}

			$select .= '<option value="' . $field['ID'] . '"' . $selected . '>' . ParseMLField( $field['TITLE'] ) . '</option>';
		}

		$select .= '</optgroup>';
	}

	$select .= '</select>';

	$advanced_link = ' <a href="' . PreparePHP_SELF( $_REQUEST, array( 'search_modfunc' ), array(
		'modfunc' => 'search',
		'include_top' => 'false',
	) ) . '">' . _( 'Advanced' ) . '</a>';


	DrawHeader( $select );

	DrawHeader(
		'<b>' . _( 'Report Timeframe' ) . ': </b>' .
			PrepareDate( $start_date, '_start', false ) . ' - ' .
			PrepareDate( $end_date, '_end', false ) .
			$advanced_link,
		SubmitButton( _( 'Go' ) )
	);

	if ( isset( $_ROSARIO['SearchTerms'] )
		&& !empty( $_ROSARIO['SearchTerms'] ) )
	{
		DrawHeader( $_ROSARIO['SearchTerms'] );
	}

	echo '<br />';

	if ( isset( $_REQUEST['field_id'] )
		&& !empty( $_REQUEST['field_id'] ) )
	{
		if ( $chartline )
		{
			// For Chart Type to Column if Line
			if ( $_REQUEST['chart_type'] === 'pie' )
				$_REQUEST['chart_type'] = 'column';

			$tabs = array(
				array(
					'title' => _( 'Line' ),
					'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'column' ) ),
				),
				array(
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'list' ) ),
				)
			);
		}
		else
		{
			$tabs = array(
				array(
					'title' => _( 'Column' ),
					'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'column' ) ),
				),
				array(
					'title' => _( 'Pie' ),
					'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'pie' ) ),
				),
				array(
					'title' => _( 'List' ),
					'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'list' ) ),
				)
			);
		}

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

		if ( $_REQUEST['chart_type'] === 'list' )
		{
			$chart_data = array( '0' => '' );

			foreach ( (array) $chart['chart_data'][1] as $key => $value )
				$chart_data[] = array( 'TITLE' => $chart['chart_data'][0][ $key ], 'VALUE' => $value );

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			$LO_columns = array( 'TITLE' => _( 'Option' ), 'VALUE' => _( 'Number of Referrals' ) );

			ListOutput( $chart_data, $LO_columns, 'Option', 'Options', array(), array(), $LO_options );
		}
		//FJ jqplot charts
		else
		{
			$chartData = array();


			if ( isset( $_ROSARIO['SearchTerms'] )
				&& !empty( $_ROSARIO['SearchTerms'] ) )
				$SearchTerms = ' - ' . strip_tags( str_replace( '<br />', " - ", mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) ));

			$chartTitle = sprintf( _( '%s Breakdown' ), ParseMLField( $field_title ) ) . $SearchTerms;

			// Line Chart
			if ( $chartline )
			{
				foreach ( (array) $chart['chart_data'][1] as $index => $y )
				{
					if ( is_numeric( $chart['chart_data'][0][ $index ] ) )
					{
						$chartData[0][] = $chart['chart_data'][0][ $index ];
						$chartData[1][] = $y;
					}
				}

				echo jqPlotChart( 'line', $chartData, $chartTitle );
			}
			// Column Chart
			elseif ( $_REQUEST['chart_type'] === 'column' )
			{
				echo jqPlotChart( 'column', $chart['chart_data'], $chartTitle );
			}
			// Pie Chart
			else
			{
				foreach ( (array) $chart['chart_data'][1] as $index => $y )
				{
					if ( is_numeric( $chart['chart_data'][1][ $index ] ) )
					{
						//limit label to 30 char max.
						$chartData[0][] = mb_substr( $chart['chart_data'][0][ $index ], 0, 30 );
						$chartData[1][] = $y;
					}
				}

				echo jqPlotChart( 'pie', $chartData, $chartTitle );
			}

			unset( $_REQUEST['_ROSARIO_PDF'] );
		}

		PopTable( 'footer' );
	}

	echo '</form>';
}
