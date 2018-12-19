<?php
/**
 * Substitutions functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Substitutions Input
 * Substitution select input + help tooltip & Copy button.
 *
 * @since 4.3
 *
 * @example echo SubstitutionsInput( array( '__FULL_NAME__' => _( 'Display Name' ), '__SCHOOL_ID__' => _( 'School' ) ) );
 *
 * @uses SelectInput
 *
 * @param array $substitutions Associative array containing code as key and
 *
 * @return string Input HTML.
 */
function SubstitutionsInput( $substitutions )
{
	static $id = 0;

	if ( empty( $substitutions )
		|| ! is_array( $substitutions ) )
	{
		return '';
	}

	$allow_na = $div = $required = false;

	$id++;

	$input_html = SelectInput(
		$substitutions,
		'substitutions_input_' . $id,
		'',
		$substitutions,
		$allow_na,
		'autocomplete="off"',
		$div
	);

	$code = ' <code id="substitutions_code_' . $id . '">' . key( $substitutions ) . '</code>';

	$copy_button = '<input id="substitutions_button_' . $id . '" type="button" value="' . _( 'Copy' ) . '" />';

	$tooltip_html = '<div class="tooltip"><i>' .
		_( 'Copy the substitution code and paste it into your text. The code will be dynamically replaced with the corresponding value.' ) .
	'</i></div>';

	$title_html = FormatInputTitle(
		_( 'Substitutions' ) . $tooltip_html,
		'substitutions_input_' . $id
	);

	ob_start(); ?>

	<script>
		var substitutionsUpdateCode = function(event) {

			var select = event.target,
				codeValue = select.options[ select.selectedIndex ].value,
				code = $('#substitutions_code_' + <?php echo json_encode( $id ); ?>);

			// Update code with corresponding selected input value.
			code.html( codeValue );
		};

		var substitutionsCopyCode = function(event) {

			var code = $('#substitutions_code_' + <?php echo json_encode( $id ); ?>),
				codeValue = code.html();

			// Copy code into clipboard.
			// @todo!!
		};

		// Set select onchange & button onclick functions.
		$('#substitutions_input_' + <?php echo json_encode( $id ); ?>).change(substitutionsUpdateCode);
		$('#substitutions_button_' + <?php echo json_encode( $id ); ?>).click(substitutionsCopyCode);
	</script>

	<?php
	$js_update_copy_code = ob_get_clean();

	return $input_html . $code . $copy_button . $title_html . $js_update_copy_code;
}


/**
 * Make Substitions for text.
 *
 * @since 4.3
 *
 * @example $text_s = SubstitutionsTextMake( array( '__FIRST_NAME__' => _( 'First Name' ), '__SCHOOL_ID__' => _( 'School' ) ), $text );
 *
 * @param array  $substitutions Associative array containing code as key and
 * @param string $text          Text with substitution codes.
 *
 * @return text Substituted text.
 */
function SubstitutionsTextMake( $substitutions, $text )
{
	if ( empty( $text )
		|| empty( $substitutions )
		|| ! is_array( $substitutions ) )
	{
		return $text;
	}

	$text_substituted = str_replace(
		array_keys( $substitutions ),
		$substitutions,
		$text
	);

	return $text_substituted;
}
