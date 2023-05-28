<?php
/**
 * Class Rank functions & AJAX modfunc.
 *
 * @see GPARankList.php, Transcripts.php, InputFinalGrades.php, ReportCards.php & EditReportCardGrades.php
 *
 * @package RosarioSIS
 * @subpackage modules
 */

if ( $_REQUEST['modfunc'] === 'class_rank_ajax' )
{
	$mp_id = empty( $_REQUEST['mp_id'] ) ? '0' : $_REQUEST['mp_id'];

	// Reset modfunc & mp_id in case Modules is dynamically reloaded based on $_SESSION request.
	RedirectURL( [ 'modfunc', 'mp_id' ] );

	ClassRankCalculateAJAX( $mp_id );
}

/**
 * Class Rank maybe Calculate
 * Check if should calculate Class Rank for MP,
 * If so, call ClassRankCalculateAJAX() using 'class_rank_ajax' modfunc.
 *
 * @since 4.7
 *
 * @param string $mp_id Marking Period ID.
 *
 * @return boolean True if should calculate Class Rank for MP.
 */
function ClassRankMaybeCalculate( $mp_id )
{
	$class_rank_mps = Config( 'CLASS_RANK_CALCULATE_MPS' );

	if ( ! $mp_id
		|| strpos( (string) $class_rank_mps, '|' . $mp_id . '|' ) === false )
	{
		return false;
	}

	// Call ClassRankCalculateAJAX() using 'class_rank_ajax' modfunc.
	?>
	<script>
		$.ajax( 'Modules.php?modname=' + <?php echo json_encode( $_REQUEST['modname'] ); ?> +
			'&modfunc=class_rank_ajax&mp_id=' + <?php echo json_encode( $mp_id ); ?> );
	</script>
	<?php

	return true;
}


/**
 * Class Rank Calculate
 * In separate AJAX call so we do not delay progam load.
 *
 * @since 4.7
 *
 * @param string $mp_id Marking Period ID.
 */
function ClassRankCalculateAJAX( $mp_id )
{
	$class_rank_mps = Config( 'CLASS_RANK_CALCULATE_MPS' );

	if ( ! $mp_id
		|| strpos( (string) $class_rank_mps, '|' . $mp_id . '|' ) === false )
	{
		die( 0 );
	}

	$class_rank_mps = str_replace( '|' . $mp_id . '|', '', $class_rank_mps );

	// Save.
	Config( 'CLASS_RANK_CALCULATE_MPS', $class_rank_mps );

	DBQuery( "SELECT set_class_rank_mp('" . $mp_id . "')" );

	die( 1 );
}


/**
 * Add MP to CLASS_RANK_CALCULATE_MPS config.
 *
 * @since 4.7
 *
 * @param string $mp_id Marking Period ID.
 *
 * @return boolean True if MP was added.
 */
function ClassRankCalculateAddMP( $mp_id )
{
	$class_rank_mps = Config( 'CLASS_RANK_CALCULATE_MPS' );

	if ( ! $mp_id
		|| strpos( (string) $class_rank_mps, '|' . $mp_id . '|' ) !== false )
	{
		return false;
	}

	$class_rank_mps .= '|' . $mp_id . '|';

	// Save.
	Config( 'CLASS_RANK_CALCULATE_MPS', $class_rank_mps );

	return true;
}
