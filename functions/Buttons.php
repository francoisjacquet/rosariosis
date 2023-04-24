<?php
/**
 * Buttons functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Submit & Reset buttons
 *
 * @todo  use Buttons() programwide to homogenize code
 *
 * @since 3.8 Add CSS .button-primary class to submit button.
 *
 * @param  string $submit_value Submit button text.
 * @param  string $reset_value  Reset button text (optional).
 *
 * @return string Buttons HTML
 */
function Buttons( $submit_value, $reset_value = '' )
{
	$buttons = '<input type="submit" value="' . AttrEscape( $submit_value ) . '" class="button-primary">';

	if ( $reset_value )
	{
		$buttons .= ' <input type="reset" value="' . AttrEscape( $reset_value ) . '">';
	}

	return $buttons;
}


/**
 * Image button with optional text & link
 *
 * @example echo button( 'x', '', '', 'bigger' );
 * @example echo button( 'remove', '', URLEscape( 'remove_url.php' ) );
 * @example echo button( 'add', '', '"#!" onclick="javascript:popup.open();"' );
 *
 * @since 4.0 Allow for button files missing the "_button" suffix.
 * @since 11.0 HTML put "" around the link href if no spaces in $link & no other attributes
 *
 * @param  string $type  [type]_button.png; ie. 'remove' will display the assets/themes/[user_theme]/btn/remove_button.png image.
 * @param  string $text  button text (optional).
 * @param  string $link  button link (optional). Use URLEscape() to encode URL!
 * @param  string $class CSS classes (optional).
 *
 * @return string        button HTML
 */
function button( $type, $text = '', $link = '', $class = '' )
{
	$button = '';

	if ( $link )
	{
		$title = '';

		if ( $type === 'remove'
			&& ! $text )
		{
			$title = ' title="' . AttrEscape( _( 'Delete' ) ) . '"';
		}

		if ( mb_strpos( $link, ' ' ) === false
			&& mb_strpos( $link, '"' ) === false )
		{
			// HTML put "" around the link href if no spaces in $link & no other attributes.
			$link = '"' . $link . '"';
		}

		// Dont put "" around the link href to allow Javascript code insert.
		$button .= '<a href=' . $link . $title . '>';
	}

	$button_file = 'assets/themes/' . Preferences( 'THEME' ) . '/btn/' . $type . '_button.png';

	if ( ! file_exists( $button_file ) )
	{
		// Allow for button files missing the "_button" suffix.
		$button_file = str_replace( '_button', '', $button_file );
	}

	$button .= '<img src="' . URLEscape( $button_file ) . '" class="button ' . $class . '" alt="' . AttrEscape( ucfirst( str_replace( '_', ' ', $type ) ) ) . '">';

	if ( $text )
	{
		$button .= '&nbsp;<b>' . $text . '</b>';
	}

	if ( $link )
	{
		$button .= '</a>';
	}

	return $button;
}


/**
 * Submit button if user Can Edit
 *
 * @example echo SubmitButton();
 *
 * @since 3.8 $value parameter is optional
 * @since 3.8 $options parameter defaults to 'class="button-primary"'
 * @since 3.9.2 No button when printing PDF
 *
 * @param  string $value   Button text. Defaults to _( 'Save' ) (optional).
 * @param  string $name    Button name attribute (optional).
 * @param  string $options Button options. Defaults to 'class="button-primary"' (optional).
 *
 * @return string          Button HTML, empty string if user not allowed to edit
 */
function SubmitButton( $value = '', $name = '', $options = 'class="button-primary"' )
{
	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( $value === '' )
		{
			$value = _( 'Save' );
		}

		$name_attr = $name ? ' name="' . AttrEscape( $name ) . '" ' : '';

		return '<input type="submit" value="' . AttrEscape( $value ) . '" ' .
			$name_attr . $options . '>';
	}

	return '';
}


/**
 * Reset button if user Can Edit
 *
 * @example echo ResetButton( _( 'Cancel' ) );
 *
 * @since 3.9.2 No button when printing PDF
 *
 * @param  string $value   Button text.
 * @param  string $options Button options (optional).
 *
 * @return string          Button HTML, empty string if user not allowed to edit
 */
function ResetButton( $value, $options = '' )
{
	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return '<input type="reset" value="' . AttrEscape( $value ) . '" ' . $options . '>';
	}

	return '';
}
