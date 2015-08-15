<?php

/**
 * MarkDown functions
 *
 */

/**
 * Convert MarkDown textarea content or MD file into HTML
 *
 * @uses Parsedown Markdown Parser class in PHP
 *
 * @example echo MarkDownToHTML( 'Hello _Parsedown_!' );
 *          will print: <p>Hello <em>Parsedown</em>!</p>
 *
 * @since  2.9
 *
 * @param  string $md     MarkDown text
 * @param  string $column DBGet() COLUMN formatting compatibility (optional)
 *
 * @return string HTML
 */
function MarkDownToHTML( $md, $column = '' )
{
	if ( !is_string( $md )
		|| empty( $md ) )
		return $md;

	global $Parsedown;

	// Create $Parsedown object once
	if ( ! ( $Parsedown instanceof Parsedown ) )
	{
		require_once( 'classes/Parsedown.php' );

		$Parsedown = new Parsedown();
	}

	return $Parsedown->setBreaksEnabled( true )->text( $md );
}


/**
 * Adds MarkDown preview to <TEXTAREA> input fields
 *
 * @uses   MarkDownInputPreview() Javascript function
 * @see    warehouse.js, and below for AJAX calls handling
 *
 * @param  string $input_id input ID attribute value
 *
 * @return HTML   preview link & preview DIV
 */
function MarkDownInputPreview( $input_id )
{
	if ( !is_string( $input_id ) )
		return false;

	ob_start();

	?>
	<div class="md-preview">
		<a href="#" onclick="MarkDownInputPreview('<?php echo $input_id; ?>', this); return false;" data-text="<?php echo _( 'Write' ); ?>">
			<?php echo _( 'Preview' ); ?>
		</a>
		<div id="divMDPreview<?php echo $input_id; ?>"></div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * AJAX Calls
 */
if ( isset( $_POST['md_preview'] )
	&& !empty( $_POST['md_preview'] ) )
{

	if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		|| $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest' )
		die( 'Error: no AJAX' );

	chdir('../');

	/**
	 * MarkDown preview
	 *
	 * @return echo HTML preview of MarkDown input
	 */
	if ( isset( $_POST['md_preview'] ) )
	{
		echo MarkDownToHTML( $_POST['md_preview'] );
	}
}
