<?php
/**
 * Prompt functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Prompt before Delete
 * and display OK & Cancel buttons
 *
 * @example if ( DeletePrompt( _( 'Title' ) ) ) DBQuery( "DELETE FROM BOK WHERE ID='" . (int) $_REQUEST['benchmark_id'] . "'" );
 *
 * @param  string  $title                    Prompt title.
 * @param  string  $action                   Prompt action (optional). Defaults to 'Delete'.
 * @param  boolean $remove_modfunc_on_cancel Remove &modufnc=XXX part of the cancel button URL (optional).
 *
 * @return boolean true if user clicks OK or Cancel + modfunc, else false
 */
function DeletePrompt( $title, $action = 'Delete', $remove_modfunc_on_cancel = true )
{
	global $locale;

	// Display prompt.
	if ( empty( $_REQUEST['delete_ok'] )
		&& empty( $_REQUEST['delete_cancel'] ) )
	{
		// Set default action text.
		if ( $action === 'Delete' )
		{
			$action = _( 'Delete' );
		}

		if ( mb_substr( $_SESSION['locale'], 0, 2 ) !== 'de' )
		{
			// We are inside a sentence, convert nouns to lowercase (except for German).
			$title = mb_strtolower( $title );
		}

		// Force action to lowercase.
		$action = mb_strtolower( $action );

		echo '<br>';

		$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST );

		$remove = $remove_modfunc_on_cancel ? [ 'modfunc' ] : [];

		$PHP_tmp_SELF_cancel = PreparePHP_SELF( $_REQUEST, $remove, [ 'delete_cancel' => true ] );

		PopTable( 'header', _( 'Confirm' ) . ( mb_strpos( $action, ' ' ) === false ? ' ' . $action : '' ) );

		echo '<br><div class="center">' . button( 'warning', '', '', 'bigger' ) .
			'<h4>' . sprintf( _( 'Are you sure you want to %s that %s?' ), $action, $title ) . '</h4>
			<form action="' . $PHP_tmp_SELF . '" method="POST">' .
				SubmitButton( _( 'OK' ), 'delete_ok', '' ) .
				'<input type="button" name="delete_cancel" class="button-primary" value="' . AttrEscape( _( 'Cancel' ) ) . '"
					onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $PHP_tmp_SELF_cancel ) . ');' ) . '">
			</form>
		</div><br>';

		PopTable( 'footer' );

		return false;
	}

	// If user clicked OK or Cancel + modfunc.
	RedirectURL( [ 'delete_ok', 'delete_cancel' ] );

	return true;
}


/**
 * Prompt question to user
 * and display OK & Cancel buttons
 *
 * Go back in browser history on Cancel
 *
 * @example if ( Prompt( _( 'Confirm' ), _( 'Do you want to dance?' ), $message ) )
 *
 * @param  string  $title    Prompt title (optional). Defaults to 'Confirm'.
 * @param  string  $question Prompt question (optional). Defaults to ''.
 * @param  string  $message  Prompt message (optional). Defaults to ''.
 *
 * @return boolean true if user clicks OK, else false
 */
function Prompt( $title = 'Confirm', $question = '', $message = '' )
{
	// Display prompt.
	if ( empty( $_REQUEST['delete_ok'] ) )
	{
		// Set default title.
		if ( $title === 'Confirm' )
		{
			$title = _( 'Confirm' );
		}

		echo '<br>';

		$PHP_tmp_SELF = PreparePHP_SELF();

		PopTable( 'header', $title );

		echo '<h4 class="center">' . $question . '</h4>
			<form action="' . $PHP_tmp_SELF . '" method="POST">' .
				$message .
				'<div class="center"><br>' .
				SubmitButton( _( 'OK' ), 'delete_ok', '' ) .
				'<input type="button" name="delete_cancel" class="button-primary" value="' . AttrEscape( _( 'Cancel' ) ) . '" onclick="javascript:self.history.go(-1);">
				</div>
			</form><br>';

		PopTable( 'footer' );

		return false;
	}

	// If user clicked OK.
	$_REQUEST['delete_ok'] = false;

	$_SESSION['_REQUEST_vars']['delete_ok'] = false;

	return true;
}


/**
 * Prompt message in JS Alert box & close window
 *
 * Use the BackPrompt function only if there is an error
 * in a script opened in a new window (ie. PDF printing)
 * BackPrompt will alert the message and close the window
 *
 * @param  string $message Alert box message.
 *
 * @return string JS Alert box & close window, then exits
 */
function BackPrompt( $message )
{
	?>
	<script>
		alert(<?php echo json_encode( (string) $message ); ?>);
		window.close();
	</script>

	<?php exit();
}
