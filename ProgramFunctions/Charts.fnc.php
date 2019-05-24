<?php
/**
 * Charts functions
 *
 * @uses jqPlot jQuery plugin
 * @see  assets/js/jqplot/
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */


/**
 * jqPlot Chart generation
 *
 * @example require_once 'ProgramFunctions/jqPlot.fnc.php';
 *          echo jqPlotChart( 'pie', $chartData, $chartTitle );
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
	static $chartID = 0;

	$types = array(
		'line',
		'column',
		'pie',
	);

	if ( ! in_array( $type, $types ) )
	{
		return '';
	}

	if ( ! is_array( $data )
		|| empty( $data ) )
	{
		return '';
	}

	$chart = includejqPlotOnce();

	// Include Chart type specific JS.
	if ( function_exists( 'includejqPlot' . ucfirst( $type ) . 'Once' ) )
	{
		$chart .= call_user_func( 'includejqPlot' . ucfirst( $type ) . 'Once' );
	}

	$chartData = array();

	// Chart Data & Options.
	switch ( $type )
	{
		case 'line':

			$chartData_count = count( $data[0] );

			for ( $i = 0; $i < $chartData_count; $i++ )
			{
				$chartData[] = '[' . $data[0][ $i ] . ', ' . $data[1][ $i ] . ']';
			}

			$chartData = '[[' . implode( ',', $chartData ) . ']]';

			$chartOptions = 'axesDefaults: {
				pad: 0 //start axes at 0
			},
			highlighter: {
				show: true,
				tooltipAxes: \'both\',
				formatString:\'<span style="font-size:larger;font-weight:bold;">%s; %s</span>\',
			},';

		break;

		case 'column':

			$isStackSeries = false;

			// Detect Stack series (various columns): key is string (series label).
			reset( $data );

			$first_key = key( $data );

			if ( is_string( $first_key ) )
			{
				$isStackSeries = true;

				$seriesLabels = array_keys( $data );

				foreach ( (array) $seriesLabels as $key => $seriesLabel )
				{
					$seriesLabels[ $key ] = "{label:" . json_encode( $seriesLabel ) . "}";
				}

				$series = '[' . implode( ',', $seriesLabels ) . ']';

				$dataSeries = $data;
			}
			else
				$dataSeries = array( $data );

			foreach ( (array) $dataSeries as $data )
			{
				$seriesData = array();

				$ticks = array();

				$chartData_count = count( $data[0] );

				for ( $i = 0; $i < $chartData_count; $i++ )
				{
					$ticks[] = json_encode( trim( $data[0][ $i ] ) );

					$seriesData[] = $data[1][ $i ];
				}

				$chartData[] = '[' . implode( ',', $seriesData ) . ']';

				$ticks = '[' . implode( ',', $ticks ) . ']';
			}

			$chartData = '[' . implode( ',', $chartData ) . ']';

			$chartOptions = 'seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				rendererOptions: {
					fillToZero: true,
					varyBarColor: true
				},
				pointLabels: { show: true }
			},
			axes: {
				xaxis: {
					renderer: $.jqplot.CategoryAxisRenderer,
					ticks: plot' . $chartID . 'ticks,
					tickRenderer: $.jqplot.CanvasAxisTickRenderer,
					tickOptions:{
						angle:-20
					}
				},
			},';

			if ( $isStackSeries )
			{
				$chartOptions = '
					stackSeries: true,
					series:' . $series . ',
					legend: {
						show: true,
						location: "e",
						placement: "outside"
					},
					' .	$chartOptions;
			}

		break;

		case 'pie':

			$chartData_count = count( $data[0] );

			for ( $i = 0; $i < $chartData_count; $i++ )
			{
				$chartData[] = '[' . json_encode( $data[0][ $i ] ) . ', ' .	$data[1][ $i ] . ']';
			}

			$chartData = '[[' . implode( ',', $chartData ) . ']]';

			$chartOptions = 'seriesDefaults:{
				renderer:$.jqplot.PieRenderer,
				rendererOptions: {
					showDataLabels: true,
					padding: screen.width < 648 ? 5 : 20
				},
			},
			legend:{show:true},';

		break;
	}

	ob_start(); ?>

	<div id="chart<?php echo $chartID; ?>" class="chart"></div>
	<script>
		function deferJqPlot(method) {
			if ($.jqplot) {
				method();
			} else {
				setTimeout(function() { deferJqPlot(method); }, 50);
			}
		}

		plot<?php echo $chartID; ?>Init = function () {
			var plot<?php echo $chartID; ?>data = <?php echo $chartData; ?>;

			<?php if ( isset( $ticks ) ) : ?>
			var plot<?php echo $chartID; ?>ticks = <?php echo $ticks; ?>;
			<?php endif; ?>

			/* FJ responsive labels: limit label to 15 char max. */
			if (screen.width < 648)
			{
				/* Pie Chart labels */
				if ( $.jqplot.PieRenderer )
					for ( var i=0; i < plot<?php echo $chartID; ?>data[0].length; i++ )
						plot<?php echo $chartID; ?>data[0][i][0] = plot<?php echo $chartID; ?>data[0][i][0].substr(0, 12);

				/* Column Chart ticks */
				if ( $.jqplot.CanvasAxisTickRenderer )
					for ( var i=0; i < plot<?php echo $chartID; ?>ticks.length; i++ )
						plot<?php echo $chartID; ?>ticks[i] = plot<?php echo $chartID; ?>ticks[i].substr(0, 20);
			}

			var plot<?php echo $chartID; ?> = $.jqplot(
				<?php echo json_encode( 'chart' . $chartID ); ?>,
				plot<?php echo $chartID; ?>data,
				{<?php echo $chartOptions; ?>
				title: <?php echo json_encode( $title ); ?>
			});
		};

		deferJqPlot( plot<?php echo $chartID; ?>Init );
	</script>

<?php $chart .= ob_get_clean();

	if ( $save_image )
	{
		$chart .= includejqPlotToColorBoxOnce();
	}

	$chartID++;

	return $chart;
}


/**
 * Include jqPlot JS & CSS once
 *
 * @since 4.8 JS Fix infinite loop when exporting to image.
 *
 * @return string jqPlot JS & CSS or empty string if already included
 */
function includejqPlotOnce()
{
	static $included = false;

	if ( $included )
	{
		return '';
	}

	$included = true;

	ob_start(); ?>

	<!--[if lt IE 9]><script src="assets/js/jqplot/excanvas.min.js"></script><![endif]-->
	<script src="assets/js/jqplot/jquery.jqplot.min.js?v=4.8"></script>
	<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />

	<?php return ob_get_clean();
}


/**
 * Include jqPlot Line Chart specific JS once
 *
 * @return string jqPlot Line Chart specific JS or empty string if already included
 */
function includejqPlotLineOnce()
{
	static $included = false;

	if ( $included )
	{
		return '';
	}

	$included = true;

	ob_start(); ?>

	<script src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>

	<?php return ob_get_clean();
}


/**
 * Include jqPlot Column Chart specific JS once
 *
 * @return string jqPlot Column Chart specific JS or empty string if already included
 */
function includejqPlotColumnOnce()
{
	static $included = false;

	if ( $included )
	{
		return '';
	}

	$included = true;

	ob_start(); ?>

	<script src="assets/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
	<script src="assets/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
	<script src="assets/js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
	<script src="assets/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
	<script src="assets/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>

	<?php return ob_get_clean();
}


/**
 * Include jqPlot Pie Chart specific JS once
 *
 * @return string jqPlot Pie Chart specific JS or empty string if already included
 */
function includejqPlotPieOnce()
{
	static $included = false;

	if ( $included )
	{
		return '';
	}

	$included = true;

	ob_start(); ?>

	<script src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>

	<?php return ob_get_clean();
}


/**
 * Include jqPlot to ColorBox specific JS once
 *
 * @return string jqPlot to ColorBox specific JS or empty string if already included
 */
function includejqPlotToColorBoxOnce()
{
	static $included = false;

	if ( $included )
	{
		return '';
	}

	$included = true;

	ob_start(); ?>

	<script src="assets/js/jquery.jqplottocolorbox.js?v=4.8"></script>
	<script>
		function deferJqPlotToColorBox() {
			if (window.jqplotToColorBox) {
				jqplotToColorBox( <?php echo json_encode( _( 'Right Click to Save Image As...' ) ); ?> );
			} else {
				setTimeout(function() { deferJqPlotToColorBox(); }, 50);
			}
		}

		deferJqPlotToColorBox();
	</script>

	<?php return ob_get_clean();
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
