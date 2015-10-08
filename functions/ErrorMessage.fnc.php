<?php

// If there are missing vals or similar, show them a msg.
//
// Pass in an array with error messages and this will display them
// in a standard fashion.
//
// in a program you may have:
/*
if(!$sch)
	$error[]="School not provided.";
if($count == 0)
	$error[]="Number of students is zero.";
ErrorMessage($error);
*/
// (note that array[], the brackets with nothing in them makes
// PHP automatically use the next index.

// Why use this?  It will tell the user if they have multiple errors
// without them having to re-run the program each time finding new
// problems.  Also, the error display will be standardized.

// If a 2ND is sent, the list will not be treated as errors, but shown anyway

function ErrorMessage( $errors, $code = 'error' )
{
	$return = '';

	if ( is_array( $errors )
		&& count( $errors ) )
	{
		// Error
		if ( $code === 'error'
			|| $code === 'fatal' )
		{
			$return .= '<div class="error"><p>' . button( 'x' ) .'&nbsp;<b>' . _( 'Error' ) . ':</b> ';
		}
		// Warning
		elseif ( $code === 'warning' )
		{
			$return .= '<div class="error"><p>' . button( 'warning' ) . '&nbsp;<b>' . _( 'Warning' ) . ':</b> ';
		}
		// Note / Update
		else
		{
			$return .= '<div class="updated"><p><b>' . _( 'Note' ) . ':</b> ';
		}

		if( count( $errors ) === 1 )
		{
			$return .= ( isset( $errors[0] ) ? $errors[0] : $errors[1] ) . '</p>';
		}
		// More than one error: list
		else
		{
			$return .= '</p><ul>';
			
			foreach( (array)$errors as $error )
			{
				$return .= '<LI>' . $error . '</LI>';
			}
			
			$return .= '</ul>';
		}

		$return .= '</div><BR />';

		// Fatal error, display error and exit
		if ( $code === 'fatal' )
		{
			echo $return;

			if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
				Warehouse( 'footer' );

			//FJ force PDF on fatal error
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
