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

$chart_types = array( 'column', 'list' );

// Set Chart Type.
if ( ! isset( $_REQUEST['chart_type'] )
	|| ! in_array( $_REQUEST['chart_type'], $chart_types ) )
{
	$_REQUEST['chart_type'] = 'column';
}

$timeframes = array( 'month', 'SYEAR' );

// Set Timeframe.
if ( ! isset( $_REQUEST['timeframe'] )
	|| ! in_array( $_REQUEST['timeframe'], $timeframes ) )
{
	$_REQUEST['timeframe'] = 'month';
}

// Advanced Search.
if ( $_REQUEST['modfunc'] === 'search' )
{
	echo '<br />';

	$extra['new'] = true;

	$extra['search_title'] = _( 'Advanced' );

	$extra['action'] = '&category_id=' . $_REQUEST['category_id'] .
		'&chart_type=' . $_REQUEST['chart_type'] .
		'&timeframe=' . $_REQUEST['timeframe'] .
		'&day_start=' . $_REQUEST['day_start'] .
		'&day_end=' . $_REQUEST['day_end'] .
		'&month_start=' . $_REQUEST['month_start'] .
		'&month_end=' . $_REQUEST['month_end'] .
		'&year_start=' . $_REQUEST['year_start'] .
		'&year_end=' . $_REQUEST['year_end'] .
		'&modfunc=&search_modfunc=';

	Search( 'student_id', $extra );
}

if ( isset( $_REQUEST['category_id'] )
	&& ! empty( $_REQUEST['category_id'] ) )
{
	$category_RET = DBGet( "SELECT du.TITLE,du.SELECT_OPTIONS,df.DATA_TYPE
		FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
		WHERE df.ID='" . $_REQUEST['category_id'] . "'
		AND du.DISCIPLINE_FIELD_ID=df.ID
		AND du.SYEAR='" . UserSyear() . "'
		AND du.SCHOOL_ID='" . UserSchool() . "'" );

	$category_RET[1]['SELECT_OPTIONS'] = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category_RET[1]['SELECT_OPTIONS'] ) );

	if ( $_REQUEST['timeframe'] === 'month' )
	{
		$timeframe = "to_char(dr.ENTRY_DATE,'mm')";

		$start = $_REQUEST['month_start'] * 1;

		$end = ( $_REQUEST['month_end'] * 1 ) + 12 *
			( $_REQUEST['year_end'] - $_REQUEST['year_start'] );
	}
	else // SYEAR
	{
		$timeframe = 'dr.SYEAR';

		$start = GetSyear( $start_date );

		$end = GetSyear( $end_date );
	}


	$extra = array();

	$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';

	$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID
		AND dr.SCHOOL_ID=ssm.SCHOOL_ID
		AND dr.ENTRY_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";

	if ( $category_RET[1]['DATA_TYPE'] === 'multiple_radio'
		|| $category_RET[1]['DATA_TYPE'] === 'select' )
	{
		$extra['SELECT_ONLY'] = "dr.CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE,COUNT(*) AS COUNT," . $timeframe . ' AS TIMEFRAME';

		$extra['GROUP'] = 'CATEGORY_' . intval( $_REQUEST['category_id'] ) . ',TIMEFRAME';

		$extra['group'] = array( 'TITLE', 'TIMEFRAME' );

		$totals_RET = GetStuList( $extra );

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			if ( $_REQUEST['timeframe'] === 'month' )
			{
				//FJ bugfix data showed in the wrong month
				$tf = str_pad( ( $i%12 == 0 ? 12 : $i%12 ), 2, '0', STR_PAD_LEFT );

				//FJ add translation
				$chart['chart_data'][ $index ][0] = _( ucwords( mb_strtolower( MonthNWSwitch( str_pad( $i%12, 2, '0', STR_PAD_LEFT ), 'tochar' ) ) ) );
			}
			else // SYEAR
			{
				//$tf = $i-$start+1;
				$tf = $i;

				$chart['chart_data'][ $index ][0] = FormatSyear( $i, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			}

			foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
			{
				$chart['chart_data'][ $index ][] = (int)$totals_RET[ $option ][ $tf ][1]['COUNT'];
			}
		}
	}
	elseif ( $category_RET[1]['DATA_TYPE'] === 'checkbox' )
	{
		$extra['SELECT_ONLY'] = "COALESCE(dr.CATEGORY_" . intval( $_REQUEST['category_id'] ) . ",'N') AS TITLE,COUNT(*) AS COUNT," . $timeframe . ' AS TIMEFRAME';

		$extra['GROUP'] = 'CATEGORY_' . intval( $_REQUEST['category_id'] ) . ',TIMEFRAME';

		$extra['group'] = array( 'TITLE', 'TIMEFRAME' );

		$totals_RET = GetStuList( $extra );

		$chart['chart_data'][0][0] = '';

		$chart['chart_data'][0][] = _( 'Yes' );
		$chart['chart_data'][0][] = _( 'No' );

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			if ( $_REQUEST['timeframe'] === 'month' )
			{
				//FJ bugfix data showed in the wrong month
				$tf = str_pad( ( $i%12 == 0 ? 12 : $i%12 ), 2, '0', STR_PAD_LEFT );

				//FJ add translation
				$chart['chart_data'][ $index ][0] = _( ucwords( mb_strtolower( MonthNWSwitch( str_pad( $i%12, 2, '0', STR_PAD_LEFT ), 'tochar' ) ) ) );
			}
			else // SYEAR
			{
				//$tf = $i-$start+1;
				$tf = $i;

				$chart['chart_data'][ $index ][0] = FormatSyear( $i, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			}

			$chart['chart_data'][ $index ][] = (int)$totals_RET['Y'][ $tf ][1]['COUNT'];

			$chart['chart_data'][ $index ][] = (int)$totals_RET['N'][ $tf ][1]['COUNT'];
		}
	}
	elseif ( $category_RET[1]['DATA_TYPE'] === 'multiple_checkbox' )
	{
		$extra['SELECT_ONLY'] = "CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE," . $timeframe . ' AS TIMEFRAME';

		$referrals_RET = GetStuList( $extra );

		$chart['chart_data'][0][0] = '';

		foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
		{
			$chart['chart_data'][0][] = $option;
		}

		foreach ( (array) $referrals_RET as $referral )
		{
			$referral['TITLE'] = explode( "||", trim( $referral['TITLE'], '|' ) );

			foreach ( (array) $referral['TITLE'] as $option )
				$options_count[$referral['TIMEFRAME']][ $option ]++;
		}

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			if ( $_REQUEST['timeframe'] === 'month' )
			{
				//FJ bugfix data showed in the wrong month
				$tf = str_pad( ( $i%12 == 0 ? 12 : $i%12 ), 2, '0', STR_PAD_LEFT );

				//FJ add translation
				$chart['chart_data'][ $index ][0] = _( ucwords( mb_strtolower( MonthNWSwitch( str_pad( $i%12, 2, '0', STR_PAD_LEFT ), 'tochar' ) ) ) );
			}
			else // SYEAR
			{
				//$tf = $i-$start+1;
				$tf = $i;

				$chart['chart_data'][ $index ][0] = FormatSyear( $i, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			}

			foreach ( (array) $category_RET[1]['SELECT_OPTIONS'] as $option )
			{
				$chart['chart_data'][ $index ][] = (int)$options_count[ $tf ][ $option ];
			}
		}
	}
	elseif ( $category_RET[1]['DATA_TYPE'] === 'numeric' )
	{
		$extra['SELECT_ONLY'] = "COALESCE(max(CATEGORY_" . intval( $_REQUEST['category_id'] ) . "),0) as MAX,COALESCE(min(CATEGORY_" . intval( $_REQUEST['category_id'] ) . "),0) AS MIN ";

		//FJ remove NULL entries
		$extra['WHERE'] .= "AND CATEGORY_" . intval( $_REQUEST['category_id'] ) . " IS NOT NULL ";

		$max_min_RET = GetStuList( $extra );

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			if ( $_REQUEST['timeframe'] === 'month' )
			{
				//FJ bugfix data showed in the wrong month
				$tf = str_pad( ( $i%12 == 0 ? 12 : $i%12 ), 2, '0', STR_PAD_LEFT );

				//FJ add translation
				$chart['chart_data'][ $index ][0] = _( ucwords( mb_strtolower( MonthNWSwitch( str_pad( $i%12, 2, '0', STR_PAD_LEFT ), 'tochar' ) ) ) );
			}
			else // SYEAR
			{
				//$tf = $i-$start+1;
				$tf = $i;

				$chart['chart_data'][ $index ][0] = FormatSyear( $i, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			}
		}

		$chart['chart_data'][0][0] = '';

		$diff_max = 10;

		if ( $diff_max > $diff )
		{
			$diff_max = $diff;

			$diff_max++;
		}

		for ( $o = 1; $o <= $diff_max; $o++ )
		{
			$range_min = ceil( $diff / $diff_max ) * ( $o - 1 );

			$range_max = ( ceil( $diff / $diff_max ) * $o ) - 1;

			$chart['chart_data'][0][ $o ] = ( $range_min == $range_max ) ? (string)$range_min + 1 : $range_min . ' - ' . $range_max;

			$mins[ $o ] = ( ceil( $diff / $diff_max ) * ( $o - 1 ) );

			$index = 0;

			for ( $i = $start; $i <= $end; $i++ )
			{
				$index++;

				$chart['chart_data'][ $index ][ $o ] = 0;
			}
		}

		$chart['chart_data'][0][$o - 1] = ( $diff_max > $diff ?
			( ceil( $diff / $diff_max ) * ( $o - 1 ) ) :
			( ceil( $diff / $diff_max ) * ( $o - 2 ) ) . '+' );

		$mins[ $o ] = ( ceil( $diff / $diff_max ) * ( $o - 1 ) );

		$extra['SELECT_ONLY'] = "CATEGORY_" . intval( $_REQUEST['category_id'] ) . " AS TITLE," . $timeframe . " AS TIMEFRAME";

		$extra['functions'] = array( 'TITLE' => '_makeNumericTime' );

		$referrals_RET = GetStuList( $extra );

		ksort( $chart['chart_data'] );
	}

	//FJ jqplot charts
	if ( $_REQUEST['chart_type'] !== 'list' )
	{
		$datacolumns = 0;
		$ticks = array();

		foreach ( (array) $chart['chart_data'] as $chart_data )
		{
			// Ticks
			if ( $datacolumns == 0 )
			{
				$jump = true;

				foreach ($chart_data as $tick)
				{
					if ( $jump)
						$jump = false;
					else
						$ticks[] = $tick;
				}
			}
			else
			{
				$series = true;

				foreach ( (array) $chart_data as $i => $data )
				{
					if ( $series )
					{
						$series = false;

						$series_label = $data;

						// Set series label + ticks
						$chartData[ $series_label ][0] = $ticks;
					}
					else
					{
						$chartData[ $series_label ][1][] = $data;
					}
				}
			}

			$datacolumns ++;
		}
	}
}


if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . PreparePHP_SELF( $_REQUEST ) . '" method="GET">';

	$categories_RET = DBGet( "SELECT df.ID,du.TITLE,du.SELECT_OPTIONS
		FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
		WHERE df.DATA_TYPE NOT IN ('textarea','text','date')
		AND du.SYEAR='" . UserSyear() . "'
		AND du.SCHOOL_ID='" . UserSchool() . "'
		AND du.DISCIPLINE_FIELD_ID=df.ID
		ORDER BY du.SORT_ORDER" );

	$select_options = array();

	foreach ( (array) $categories_RET as $category )
	{
		$select_options[$category['ID']] = $category['TITLE'];
	}

	$select = SelectInput(
		$_REQUEST['category_id'],
		'category_id',
		'',
		$select_options,
		_( 'Please choose a category' ),
		'onchange="ajaxPostForm(this.form,true);"',
		false
	);

	$advanced_link = ' <a href="' . PreparePHP_SELF( $_REQUEST, array( 'search_modfunc' ), array(
		'modfunc' => 'search',
		'include_top' => 'false',
	) ) . '">' . _( 'Advanced' ) . '</a>';

	DrawHeader( $select );

	$timeframe_radio = '<label>
		<input type="radio" name="timeframe" value="month"' . ( $_REQUEST['timeframe'] === 'month' ? ' checked' : '' ) . '>&nbsp;' . _( 'Month' ) .
	'</label> &nbsp;' .
	'<label>
		<input type="radio" name="timeframe" value="SYEAR"' . ( $_REQUEST['timeframe'] === 'SYEAR' ? ' checked' : '' ) . '>&nbsp;' . _( 'School Year' ) .
	'</label>';

	DrawHeader( '<b>' . _( 'Timeframe' ) . ': </b> ' . $timeframe_radio );

	DrawHeader(
		'<b>' . _( 'Report Timeframe' ) . ': </b>' .
			PrepareDate( $start_date, '_start', false ) . ' - ' .
			PrepareDate( $end_date, '_end', false ) .
			$advanced_link,
		SubmitButton( _( 'Go' ) )
	);

	if ( isset( $_ROSARIO['SearchTerms'] )
		&& ! empty( $_ROSARIO['SearchTerms'] ) )
	{
		DrawHeader( $_ROSARIO['SearchTerms'] );
	}

	echo '<br />';

	if ( isset( $_REQUEST['category_id'] )
		&& ! empty( $_REQUEST['category_id'] ) )
	{
		$tabs = array(
			array(
				'title' => _( 'Column' ),
				'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'column' ) ),
			),
			array(
				'title' => _( 'List' ),
				'link' => PreparePHP_SELF( $_REQUEST, array(), array( 'chart_type' => 'list' ) ),
			)
		);

		$_ROSARIO['selected_tab'] = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $tabs );

		if ( $_REQUEST['chart_type'] === 'list' )
		{

			// IGNORE THE 'Series' RECORD
			$LO_columns = array( 'TITLE' => _( 'Option' ) );

			foreach ( (array) $chart['chart_data'] as $timeframe => $values )
			{
				if ( $timeframe != 0 )
				{
					$LO_columns[ $timeframe ] = $values[0];

					unset( $values[0] );

					foreach ( (array) $values as $key => $value )
					{
						$chart_data[ $key ][ $timeframe ] = $value;
					}
				}
				else
				{
					unset( $values[0] );

					foreach ( (array) $values as $key => $value )
					{
						$chart_data[ $key ]['TITLE'] = $value;
					}
				}
			}

			unset( $chart_data[0] );

			$LO_options['responsive'] = false;

			ListOutput( $chart_data, $LO_columns, 'Option', 'Options', array(), array(), $LO_options );
		}
		//FJ jqplot charts
		else
		{
			if ( isset( $_ROSARIO['SearchTerms'] )
				&& ! empty( $_ROSARIO['SearchTerms'] ) )
			{
				$SearchTerms = ' - ' . strip_tags( str_replace( '<br />', " - ", mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) ) );
			}

			$chartTitle = sprintf( _( '%s Breakdown' ), ParseMLField( $category_RET[1]['TITLE'] ) ) . $SearchTerms;

			echo jqPlotChart( 'column', $chartData, $chartTitle );

			unset( $_REQUEST['_ROSARIO_PDF'] );
		}

		PopTable('footer');
	}
	echo '</form>';
}


/**
 * Increment occurences of Number in Chart Month / Year series X axis
 *
 * @param  string $number Number
 * @param  string $column TITLE
 *
 * @return string         Number
 */
function _makeNumericTime( $number, $column )
{
	global $max_min_RET,
		$chart,
		$diff,
		$diff_max,
		$mins,
		$THIS_RET,
		$start_date,
		$end_date;

	if ( $_REQUEST['timeframe'] === 'month' )
	{
		$index = ( ( $THIS_RET['TIMEFRAME'] * 1 ) -
			( $_REQUEST['month_start'] * 1 ) + 1 +
			12 * ( $_REQUEST['year_end'] - $_REQUEST['year_start'] ) );
	}
	elseif ( $_REQUEST['timeframe'] === 'SYEAR' )
	{
		$start = GetSyear( $start_date );

		$end = GetSyear( $end_date );

		$index = 0;

		for ( $i = $start; $i <= $end; $i++ )
		{
			$index++;

			if ( $i == $THIS_RET['TIMEFRAME'] )
				break;
		}
	}

	if ( is_null( $number ) )
		return;

	if ( $diff == 0 )
	{
		$chart['chart_data'][0][1] = (int)$number;

		$chart['chart_data'][ $index ][1]++;
	}
	elseif ( $diff < $diff_max )
	{
		//$chart['chart_data'][0][((int) $number - (int) $max_min_RET[1]['MIN']+1)] = (int) $number;
		$chart['chart_data'][ $index ][( (int)$number - (int)$max_min_RET[1]['MIN'] + 1 )]++;
	}
	else
	{
		for ( $i = 1; $i <= $diff_max; $i++ )
		{
			if ( ( $number >= $mins[ $i ]
					&& $number < $mins[$i + 1] )
				|| $i === $diff_max )
			{
				$chart['chart_data'][ $index ][ $i ]++;

				break;
			}
		}
	}

	return $number;
}
