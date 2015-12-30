<?php
/**
 * Error Message function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Error Message
 *
 * Use 'fatal' code to exit program.
 * Use 'note' code for Notes and Update messages.
 *
 * If there are missing vals or similar, show them a msg.
 * Pass in an array with error messages and this will display them
 * in a standard fashion.
 * In a program you may have:
 *
 * @example if ( ! $sch ) $error[] = _( 'School not provided.' );
 * @example if ( $count === 0 ) $error[] = _( 'Number of students is zero.' ); ErrorMessage( $error );
 *
 * Why use this? It will tell the user if they have multiple errors
 * without them having to re-run the program each time finding new
 * problems.  Also, the error display will be standardized.
 *
 * @global string $print_data PDF print data
 *
 * @param  array  $errors     Array of errors or notes.
 * @param  string $code       error|fatal|note (optional). Defaults to 'error'.
 *
 * @return string Error / Note Message, exits if 'fatal' code
 */
function ErrorMessage( $errors, $code = 'error' )
{
	$return = '';

	if ( is_array( $errors )
		&& count( $errors ) )
	{
		// Error.
		if ( $code === 'error'
			|| $code === 'fatal' )
		{
			$return .= '<div class="error"><p>' . button( 'x' ) .'&nbsp;<b>' . _( 'Error' ) . ':</b> ';
		}
		// Warning.
		elseif ( $code === 'warning' )
		{
			$return .= '<div class="error"><p>' . button( 'warning' ) . '&nbsp;<b>' . _( 'Warning' ) . ':</b> ';
		}
		// Note / Update.
		else
		{
			$return .= '<div class="updated"><p><b>' . _( 'Note' ) . ':</b> ';
		}

		if ( count( $errors ) === 1 )
		{
			$return .= ( isset( $errors[0] ) ? $errors[0] : $errors[1] ) . '</p>';
		}
		// More than one error: list.
		else
		{
			$return .= '</p><ul>';

			foreach ( (array) $errors as $error )
			{
				$return .= '<li>' . $error . '</li>';
			}

			$return .= '</ul>';
		}

		$return .= '</div><br />';

		// Fatal error, display error and exit.
		if ( $code === 'fatal' )
		{
			echo $return;

			if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
				Warehouse( 'footer' );

			// FJ force PDF on fatal error.
			else
			{
				global $print_data;

				PDFStop( $print_data );
			}

			exit;
		}
	}

	return $return;
}
