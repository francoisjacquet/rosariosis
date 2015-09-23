<?php

// TODO 
// Bug Print after Delete OR Delete OK
/**
 * Prompt before Delete
 * and display OK & Cancel buttons
 *
 * @example
 * if ( DeletePrompt( _( 'Title' ) ) )
 * {
 * 		DBQuery( "DELETE FROM BOK WHERE id='" . $_REQUEST['benchmark_id'] . "'" );
 * }
 *
 * @param string  $title                    Prompt title
 * @param string  $action                   Prompt action (optional)
 * @param boolean $remove_modfunc_on_cancel Remove &modufnc=XXX part of the cancel button URL (optional)
 *
 * @return true if user clicks OK or Cancel + modfunc, else false
 */
function DeletePrompt( $title, $action = 'Delete', $remove_modfunc_on_cancel=true )
{
	// display prompt
	if ( ( !isset( $_REQUEST['delete_ok'] )
			|| empty( $_REQUEST['delete_ok'] ) )
		&&  ( !isset( $_REQUEST['delete_cancel'] )
			|| empty( $_REQUEST['delete_cancel'] ) ) )
	{
		// set default action text
		if ( $action === 'Delete' )
			$action = _( 'Delete' );

		echo '<BR />';

		$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST );

		if ( $remove_modfunc_on_cancel )
			$remove = array( 'modfunc' );
		else
			$remove = array();

		$PHP_tmp_SELF_cancel = PreparePHP_SELF( $_REQUEST, $remove, array( 'delete_cancel' => true ) );

		PopTable( 'header', _( 'Confirm' ) . ( mb_strpos( $action, ' ' ) === false ? ' '. $action : '' ) );

		$addJavascript .= '<script>
			var cancel_link = document.createElement("a");
			cancel_link.href = "' .	$PHP_tmp_SELF_cancel . '";
			cancel_link.target = "body";
		</script>';

		echo $addJavascript . '<span class="center">
			<h4>' . sprintf( _( 'Are you sure you want to %s that %s?' ), $action, $title ).'</h4>
			<FORM action="' . $PHP_tmp_SELF . '" METHOD="POST">' .
				SubmitButton( _( 'OK' ), 'delete_ok' ) .
				'<INPUT type="button" name="delete_cancel" value="' . _( 'Cancel' ) . '" onclick="ajaxLink(cancel_link);" />
			</FORM>
		</span>';

		PopTable( 'footer' );

		return false;
	}
	// if user clicked OK or Cancel + modfunc
	else
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
 * @param string $title    Prompt title (optional)
 * @param string $question Prompt question (optional)
 * @param string $message  Prompt message (optional)
 *
 * @return true if user clicks OK, else false
 */
function Prompt( $title = 'Confirm', $question = '', $message = '' )
{
	// display prompt
	if ( !isset( $_REQUEST['delete_ok'] )
		|| empty( $_REQUEST['delete_ok'] ) )
	{
		// set default title
		if ( $title === 'Confirm' )
			$title = _( 'Confirm' );

		echo '<BR />';

		$PHP_tmp_SELF = PreparePHP_SELF( $_REQUEST );

		PopTable( 'header', $title );

		echo '<div class="center">
			<h4>' . $question . '</h4>
			<FORM action="' . $PHP_tmp_SELF . '" METHOD="POST">' .
				$message .
				'<BR /><BR />' .
				SubmitButton( _( 'OK' ), 'delete_ok' ) .
				'<INPUT type="button" name="delete_cancel" value="' . _( 'Cancel' ) . '" onclick="javascript:self.history.go(-1);">
			</FORM>
		</div>';

		PopTable( 'footer' );

		return false;
	}
	// if user clicked OK
	else
		return true;
}


/**
 * Prompt message in JS Alert box & close window
 *
 * Use the BackPrompt function only if there is an error
 * in a script opened in a new window (ie. PDF printing)
 * BackPrompt will alert the message and close the window
 *
 * @param string $message Alert box message
 */
function BackPrompt( $message )
{
	?>
	<script>
		alert(<?php echo json_encode( $message ); ?>);
		window.close();
	</script>

	<?php exit();
}
