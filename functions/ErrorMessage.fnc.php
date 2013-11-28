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

function ErrorMessage($errors,$code='error')
{
	if($errors)
	{
//modif Francois: css WPadmin
		if(count($errors)==1)
		{
			if($code=='error' || $code=='fatal')
				$return .= '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<b>'.Localize('colon',_('Error')).'</b> ';
			else
				$return .= '<div class="updated"><p><b>'.Localize('colon',_('Note')).'</b> ';
			$return .= ($errors[0]?$errors[0]:$errors[1]) .'</p>';
		}
		else
		{
			if($code=='error' || $code=='fatal')
				$return .= '<div class="error"><p><IMG SRC="assets/x_button.png" class="alignImg" />&nbsp;<b>'.Localize('colon',_('Errors'))."</b></p>";
			else
				$return .= '<div class="updated"><p>&nbsp;<b>'.Localize('colon',_('Note')).'</b></p>';
			$return .= '<ul>';
			foreach($errors as $value)
					$return .= '<LI><span class="size-1">'.$value.'</span></LI>'."\n";
			$return .= '</ul>';
		}
		$return .= '</div><BR />';

		if($code=='fatal')
		{
			echo $return;
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				Warehouse('footer');
//modif Francois: force PDF on fatal error
			else
			{
				global $print_data;
				PDFStop($print_data);
			}
			exit;
		}

		return $return;
	}
}
?>