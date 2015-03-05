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
//FJ css WPadmin
		if($code=='error' || $code=='fatal')
			$return .= '<div class="error"><p>'. button('x') .'&nbsp;<b>'._('Error').':</b> ';
		elseif($code=='warning')
			$return .= '<div class="error"><p>'. button('warning') .'&nbsp;<b>'._('Warning').':</b> ';
		else
			$return .= '<div class="updated"><p><b>'._('Note').':</b> ';

		if(count($errors)==1)
			$return .= ($errors[0]?$errors[0]:$errors[1]) .'</p>';
		else
		{
			$return .= '</p><ul>';
			foreach($errors as $value)
					$return .= '<LI class="size-1">'.$value.'</LI>'."\n";
			$return .= '</ul>';
		}
		$return .= '</div><BR />';

		if($code=='fatal')
		{
			echo $return;
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				Warehouse('footer');
//FJ force PDF on fatal error
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
