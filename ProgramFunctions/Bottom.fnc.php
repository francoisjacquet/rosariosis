<?php
/**
 * Bottom.php related functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * "Back to List" Bottom.php button update
 * Remove need to make an AJAX call to Bottom.php
 *
 * @see Bottom.php
 *
 * @since 12.0 JS Show BottomButtonBack & update its URL & text
 *
 * @param string Back to list PHP self URL.
 */
function BottomButtonBackUpdate( $back_php_self )
{
	$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_REQUEST, [ 'bottom_back' ] );

	$_SESSION['Back_PHP_SELF'] = $back_php_self;

	$back_url = $_SESSION['List_PHP_SELF'] . '&bottom_back=true';

	switch ( $_SESSION['Back_PHP_SELF'] )
	{
		case 'student':

			$back_text = _( 'Student List' );
		break;

		case 'staff':

			$back_text = _( 'User List' );
		break;

		case 'course':

			$back_text = _( 'Course List' );
		break;

		default:

			$back_text = sprintf( _( '%s List' ), $_SESSION['Back_PHP_SELF'] );
	}

	?>
	<script>
		$('#BottomButtonBack span').text(<?php echo json_encode( $back_text ); ?>);
		$('#BottomButtonBack').removeClass('hide')
			.attr('href', <?php echo json_encode( URLEscape( $back_url ) ); ?>)
			.attr('title', <?php echo json_encode( $back_text ); ?>);
	</script>
	<?php

	ob_start();

	// Do bottom_buttons action hook.
	do_action( 'ProgramFunctions/Bottom.fnc.php|bottom_buttons' );

	$bottom_buttons = ob_get_clean();

	if ( ! $bottom_buttons )
	{
		return;
	}

	?>
	<script>
		$('#BottomButtonBack').after(<?php echo json_encode( $bottom_buttons ); ?>);
	</script>
	<?php
}
