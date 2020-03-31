<?php
/**
 * Charts functions
 *
 * @uses Chart.js plugin
 * @see  assets/js/Chart.js/
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Chart.js Chart generation
 *
 * @since 6.0
 *
 * @link https://www.chartjs.org/docs/latest/
 *
 * @example require_once 'ProgramFunctions/Charts.fnc.php';
 *          echo ChartjsChart( 'pie', $chart_data, $chart_title );
 *
 * @param  string  $type       Chart type line|bar|doughnut|pie.
 * @param  array   $data       associative array containing X axis|ticks|labels [0] & Y axis|values [1]
 *                             Stack series (columns): array( 'series1_label' => $data1, 'series2_label' => $data2, ).
 * @param  string  $title      Chart title.
 *
 * @return string              Chart.js JS file & Chart JS or empty string if error
 */
function ChartjsChart( $type, $data, $title )
{
	static $chart_id = 0;

	$types = array( 'line', 'bar', 'doughnut', 'pie' );

	if ( ! in_array( $type, $types )
		|| ! is_array( $data )
		|| empty( $data ) )
	{
		return '';
	}

	// @link https://github.com/chartjs/Chart.js/issues/815#issuecomment-270186793
	// @link http://clrs.cc/
	$colors_default = array( '#FF851B', '#2ECC40', '#0074D9', '#F012BE', '#FFDC00', '#3D9970', '#001f3f', '#85144b', '#FF4136', '#01FF70', '#39CCCC', '#B10DC9' );

	$dataset_default = array(
		'label' => '',
		'backgroundColor' => $colors_default,
		// 'borderColor' => 'rgb(255, 99, 132)',
		'data' => array(),
	);

	$first_key = key( $data );

	if ( $type === 'bar' && is_string( $first_key ) )
	{
		// Stack series.
		$i = 0;

		foreach ( (array) $data as $label => $data_serie )
		{
			$labels = $data_serie[0];

			$dataset = $dataset_default;

			$dataset['label'] = $label;

			$dataset['backgroundColor'] = $colors_default[ $i % count( $colors_default ) ];

			$dataset['data'] = $data_serie[1];

			$datasets[ $i ] = $dataset;

			$i++;
		}
	}
	else
	{
		$labels = $data[0];

		$dataset = $dataset_default;

		$dataset['data'] = $data[1];

		$datasets = array( $dataset );
	}

	// Chart Options: Show legend on the right.
	$chart_options = 'legend: {
		position: "right"
	},';

	if ( $type === 'line'
		|| $type === 'bar' )
	{
		// Line & Bar Chart Options.
		$chart_options .= 'scales: {
			xAxes: [{
				gridLines: {
					display: false // Turn off only the vertical gridlines.
				},
				stacked: true
			}],
			yAxes: [{
				ticks: {
					beginAtZero: true
				},
				stacked: true
			}]
		},';

		if ( ! is_string( $first_key ) )
		{
			// Remove legend, empty anyway.
			$chart_options .= 'legend: {
				display: false,
			},';
		}
	}

	ob_start();

	if ( ! $chart_id ) : ?>
		<script src="assets/js/Chart.js/Chart.bundle.min.js?v=2.9.3"></script>
	<?php endif; ?>

	<div class="chart">
		<canvas id="chart<?php echo $chart_id; ?>"></canvas>
	</div>
	<script>
		Chart.defaults.global.defaultFontSize = 14;

		var chart<?php echo $chart_id; ?> = new Chart(
			document.getElementById(<?php echo json_encode( 'chart' . $chart_id ); ?>).getContext('2d'), {
			// The type of chart we want to create.
			type: <?php echo json_encode( $type ); ?>,

			// The data for our dataset.
			data: {
				labels: <?php echo json_encode( $labels ); ?>,
				datasets: <?php echo json_encode( $datasets ); ?>
			},

			// Configuration options go here.
			options: {
				<?php echo $chart_options; ?>
				responsive: true,
				title: {
					display: true,
					fontSize: 16,
					text: <?php echo json_encode( $title ); ?>
				}
			}
		});
	</script>
	<?php

	$chart_id++;

	return ob_get_clean();
}

/**
 * jqPlot Chart generation
 *
 * @deprecated since 6.0 use ChartjsChart().
 *
 * @example require_once 'ProgramFunctions/Charts.fnc.php';
 *          echo jqPlotChart( 'pie', $chart_data, $chartTitle );
 *
 * @param  string  $type       Chart type line|column|pie.
 * @param  array   $data       associative array containing X axis|ticks|labels [0] & Y axis|values [1]
 *                             Stack series (columns): array( 'series1_label' => $data1, 'series2_label' => $data2, ).
 * @param  string  $title      Chart title.
 * @param  boolean $save_image Export Chart to image inside ColorBox (to save Chart) (optional). Defaults to true.
 *
 * @return string              JS, CSS files & jqPlot Chart JS or empty string if error
 */
function jqPlotChart( $type, $data, $title, $save_image = true )
{
	if ( $type === 'column' )
	{
		$type = 'bar';
	}

	return ChartjsChart( $type, $data, $title );
}

/**
 * Add Number to Chart X axis
 * Increment occurences of Number in Chart Y axis
 *
 * @global array   $max_min_RET
 * @global array   $chart
 * @global integer $diff        Difference b/w min & max
 * @global array   $mins
 * @global boolean $chartline   Is line chart
 *
 * @param  string  $number      Number.
 * @param  string  $column      TITLE.
 *
 * @return string  Number
 */
function makeNumeric( $number, $column )
{
	global $max_min_RET,
		$chart,
		$diff,
		$mins,
		$chartline;

	if ( is_null( $number ) )
	{
		return;
	}

	if ( $diff == 0 )
	{
		$chart['chart_data'][0][1] = (int) $number;
		$chart['chart_data'][1][1]++;
	}
	elseif ( $diff < 10
		|| $chartline )
	{
		$chart['chart_data'][0][( (int) $number - (int) $max_min_RET[1]['MIN'] + 1 )] = (int) $number;

		$chart['chart_data'][1][( (int) $number - (int) $max_min_RET[1]['MIN'] + 1 )]++;
	}
	else
	{
		for ( $i = 1; $i <= 10; $i++ )
		{
			if ( ( $number >= $mins[ $i ]
					&& $number < $mins[$i + 1] )
				|| $i === 10 )
			{
				$chart['chart_data'][1][ $i ]++;

				break;
			}
		}
	}

	return $number;
}
