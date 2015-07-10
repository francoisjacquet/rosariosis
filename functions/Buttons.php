<?php

/**
 * Submit & Reset buttons
 *
 * @todo  use Buttons() programwide to homogenize code
 *
 * @param  string $submit_value Submit button text
 * @param  string $reset_value  Reset button text (optional)
 *
 * @return string Buttons HTML
 */
function Buttons( $submit_value, $reset_value = '' )
{
	$buttons = '<input type="submit" value="' . $submit_value . '" />';

	if ( $reset_value !== '' )
		$buttons .= ' <input type="reset" value="' . $reset_value . '" />';
	
	return $buttons;
}


/**
 * Image button with optional text & link
 *
 * @example echo button( 'x', '', '', 'bigger' );
 *
 * @param  string $type  [type]_button.png
 *                       ie. 'remove' will display
 *                       the assets/themes/WPadmin/btn/remove_button.png image
 * @param  string $text  button text (optional)
 * @param  string $link  button link (optional)
 * @param  string $class CSS classes (optional)
 *
 * @return string        button HTML
 */
function button( $type, $text = '', $link = '', $class = '' )
{
	$button = '';

	if ( $link !== '' )
	{
		$title = '';

		if ( $type === 'remove'
			&& $text === '' )
			$title = ' title="' . _( 'Delete' ) . '"';

		// dont put "" around the link href to allow Javascript code insert
		$button .= '<a href=' . $link . $title . '>';
	}

	$button_file = 'assets/themes/' . Preferences( 'THEME' ) . '/btn/' . $type . '_button.png';

	$button .= '<img src="' . $button_file . '" class="button ' . $class . '" />';

	if ( $text !== '' )
		$button .= ' <b>' . $text . '</b>';

	if ( $link !== '' )
		$button .= '</a>';

	return $button;
}


/**
 * Submit button if user Can Edit
 *
 * @example  echo SubmitButton( _( 'Save' ) );
 *
 * @param  string $value   Button text
 * @param  string $name    Button name attribute (optional)
 * @param  string $options Button options (optional)
 *
 * @return string          Button HTML, empty string if user not allowed to edit
 */
function SubmitButton( $value, $name = '', $options = '' )
{
	if ( AllowEdit() )
	{
		$name_attr = '';

		if ( $name !== '' )
			$name_attr = ' name="' . $name . '" ';

		return '<input type="submit" value="' . $value . '"' . $name_attr . $options . ' />';
	}
	else
		return '';
}


/**
 * Reset button if user Can Edit
 *
 * @example  echo ResetButton( _( 'Cancel' ) );
 *
 * @param  string $value   Button text
 * @param  string $options Button options (optional)
 *
 * @return string          Button HTML, empty string if user not allowed to edit
 */
function ResetButton( $value, $options = '' )
{
	if ( AllowEdit() )
		return '<input type="reset" value="' . $value . '" ' . $options . ' />';
	else
		return '';
}

?>