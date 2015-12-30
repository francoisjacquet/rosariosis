<?php
/**
 * Input functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Date Input
 *
 * @example DateInput( DBDate(), '_values[CATEGORY_' . $category['ID'] . ']' )
 *
 * @uses PrepareDate() to display Month / Day / Year Select fields + JSCalendar integration
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @param  string         $value    Input value.
 * @param  string         $name     Input name.
 * @param  string         $title    Input title (optional). Defaults to ''.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 * @param  boolean        $allow_na Allow N/A (empty value) (optional). Defaults to true.
 * @param  boolean        $required Required date fields (optional). Defaults to false.
 *
 * @return string         Input HTML
 */
function DateInput( $value, $name, $title = '', $div = true, $allow_na = true, $required = false )
{
	$id = GetInputID( $name );

	$ftitle = FormatInputTitle( $title, '', $value == '' && $required );

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$options = array();

		//FJ date field is required
		if ( $required )
		{
			$options['required'] = true;
		}

		if ( $value == ''
			|| ! $div )
		{
			$return = PrepareDate( $value, '_' . $name, $allow_na, $options ) . $ftitle;
		}
		else
		{
			$options = $options + array( 'Y' => 1, 'M' => 1, 'D' => 1 );

			$input = PrepareDate( $value, '_' . $name, $allow_na, $options ) . $ftitle;

			$return = InputDivOnclick(
				$id,
				$input,
				( $value != '' ? ProperDate( $value ) : '-' ),
				$ftitle
			);
		}
	}
	else
		$return = ($value != '' ? ProperDate( $value ) : '-' ) . $ftitle;

	return $return;
}


/**
 * Text Input
 *
 * @example TextInput( Config( 'NAME' ), 'values[CONFIG][NAME]', _( 'Program Name' ), 'required' )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @param  string  $value Input value.
 * @param  string  $name  Input name.
 * @param  string  $title Input title (optional). Defaults to ''.
 * @param  string  $extra Extra HTML attributes added to the input.
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string  Input HTML
 */
function TextInput( $value, $name, $title = '', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	// mab - support array style $option values
	$display_val = is_array( $value ) ? $value[1] : $value;

	$value = is_array( $value ) ? $value[0] : $value;

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Input size / length based on value number of chars
		if ( mb_strpos( $extra, 'size' ) === false )
		{
			// Max size is 32 (more or less 300px)
			$extra .= $value != '' ? ' size="' . min( mb_strlen( $value ), 32 ) . '"' : ' size="10"';
		}

		$input = '<input type="text" id="' . $id . '" name="' . $name . '" ' .
			( $value || $value === '0' ? 'value="' . htmlspecialchars( $value, ENT_QUOTES ) . '"' : '' ) .
			' ' . $extra . ' />' . $ftitle;

		if ( trim( $value ) == ''
			|| ! $div )
		{
			$return = $input;
		}
		else
		{
			$return = InputDivOnclick(
				$id,
				$input,
				( $value != '' ? $display_val : '-' ),
				$ftitle
			);
		}
	}
	else
		$return = ( $value != '' ? $display_val : '-' ) . $ftitle;

	return $return;
}


/**
 * Multi Languages Text Input
 *
 * @example MLTextInput( Config( 'TITLE' ), 'values[CONFIG][TITLE]', _( 'Program Title' ) )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 *
 * @uses ParseMLField() to get localized options
 *
 * @global $RosarioLocales Returns simple TextInput() if only 1 locale set
 * @global $locale         Get current locale
 *
 * @param  string  $value Input value.
 * @param  string  $name  Input name.
 * @param  string  $title Input title (optional). Defaults to ''.
 * @param  string  $extra Extra HTML attributes added to the input.
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string  Input HTML
 */
function MLTextInput( $value, $name, $title = '', $extra = '', $div = true )
{
	global $RosarioLocales,
		$locale;

	$value = is_array( $value ) ? $value[0] : $value;

	if ( count( $RosarioLocales ) < 2 )
	{
		return TextInput( ParseMLField( $value, $locale ), $name, $title, $extra, $div );
	}

	$id = GetInputID( $name );

	// Mab - support array style $option values.
	$display_val = is_array( $value ) ? $value[1] : $value;

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Input size / length based on value number of chars.
		if ( mb_strpos( $extra, 'size' ) === false
			&& $value != '' )
		{
			$nb_loc = mb_substr_count( $value, '|' ) + 1;

			$extra .=  ' size="' .
				( mb_strlen( $value ) - ( $nb_loc - 1 ) * ( mb_strlen( $RosarioLocales[0] ) + 2 ) )
					/ $nb_loc . '"';
		}

		// Ng - foreach possible language.
		ob_start(); ?>
<script>
function setMLvalue(id, loc, value){
	var res = document.getElementById(id).value.split("|");

	if (loc === "") {
		res[0] = value;
	} else {
		var found = 0;
		for ( var i = 1; i < res.length; i++ ) {
			if (res[i].substring(0, loc.length) == loc) {
				found = 1;
				if (value === "") {
					for ( var j = i + 1; j < res.length; j++ )
						res[j - 1] = res[j];
					res.pop();
				} else {
					res[i] = loc + ":" + value;
				}
			}
		}
		if ((found === 0) && (value !== "")) res.push(loc + ":" + value);
	}
	document.getElementById(id).value = res.join("|");
}
</script>
<?php 	$return = ob_get_clean();

		$return .= '<div class="ml-text-input"><input type="hidden" id="' . $id . '" name="' . $name . '" value="' . $value . '" />';

		foreach ( (array) $RosarioLocales as $key => $loc )
		{
			$return .= '<label><img src="assets/flags/' . $loc . '.png" class="button bigger" /> ';

			//$return .= TextInput(ParseMLField($value, $loc),'ML_'.$name.'['.$loc.']','',$extra." onchange=\"javascript:setMLvalue('".$name."','".($id==0?'':$loc)."',this.value);\"",false);
			$return .= TextInput(
				ParseMLField( $value, $loc ),
				'ML_' . $name . '[' . $loc . ']',
				'',
				$extra . ( $key == 0 ? ' required' : '' ) .
					" onchange=\"setMLvalue('" . $id . "','" . ( $key == 0 ? '' : $loc ) . "',this.value);\"",
				false
			);

			$return .= '</label><br />';
		}

		$return .= '</div>';
	}
	else
		$return .= ParseMLField( $value );

	$ftitle = FormatInputTitle( $title );

	$return .= str_replace( '<br />', '', $ftitle );

	return $return;
}


/**
 * Textarea Input
 *
 * @example TextAreaInput( $RET[1]['DESCRIPTION'], 'values[DESCRIPTION]' )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @uses MarkDownInputPreview()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $markdown )
 * @uses ShowDown jQuery plugin for MarkDown rendering called using the .markdown-to-html CSS class
 *
 * @param  string  $value    Input value.
 * @param  string  $name     Input name.
 * @param  string  $title    Input title (optional). Defaults to ''.
 * @param  string  $extra    Extra HTML attributes added to the input.
 * @param  boolean $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 * @param  boolean $markdown Is MarkDown formatted text? (optional). Defaults to true.
 *
 * @return string  Input HTML
 */
function TextAreaInput( $value, $name, $title = '', $extra = '', $div = true, $markdown = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	if ( $value !== '' )
	{
		if ( $markdown )
		{
			// Convert MarkDown to HTML.
			$display_val = '<div class="markdown-to-html">' . $value . '</div>';
		}
		else
			$display_val = nl2br( $value );
	}
	else
		$display_val = '-';

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Columns.
		/*if ( mb_strpos( $extra, 'cols' ) === false )
		{
			$extra .= ' cols=30';
			$cols = 30;
		}
		else
			$cols = mb_substr( $extra, mb_strpos( $extra, 'cols' ) + 5, 2 ) *1;*/

		// Rows.
		if ( mb_strpos( $extra, 'rows' ) === false )
		{
			$extra .= ' rows=5';
		}

		$textarea =  ( $markdown ? MarkDownInputPreview( $id ) : '' ) .
			'<textarea id="' . $id . '" name="' . $name . '" ' . $extra . '>' .
			$value . '</textarea>' . 
			( $markdown ? str_replace( '<br />', '', $ftitle ) : $ftitle );

		if ( $value == ''
			|| ! $div )
		{
			$return = $textarea;
		}
		else
		{
			$return = InputDivOnclick(
				$id,
				$textarea,
				$display_val,
				$ftitle
			);
		}
	}
	else
	{
		$return = $display_val . $ftitle;
	}

	return $return;
}


/**
 * Adds MarkDown preview to <textarea> input fields
 * Adds Write & Preview tabs + MD button above the field
 *
 * @uses   MarkDownInputPreview() Javascript function
 * @see    warehouse.js, and below for AJAX calls handling
 * @since  2.9
 *
 * @param  string $input_id input ID attribute value.
 *
 * @return HTML   preview link & preview DIV
 */
function MarkDownInputPreview( $input_id )
{
	if ( ! $input_id )
	{
		return false;
	}

	ob_start();

	?>
	<div class="md-preview">
		<a href="#" onclick="MarkDownInputPreview('<?php echo $input_id; ?>'); return false;" class="tab disabled"><?php echo _( 'Write' ); ?></a>

		<a href="#" onclick="MarkDownInputPreview('<?php echo $input_id; ?>'); return false;" class="tab"><?php echo _( 'Preview' ); ?></a>

		<a href="https://guides.github.com/features/mastering-markdown/" title="<?php echo _( 'Mastering MarkDown' ); ?>" target="_blank" class="md-link">
			<img class="button" src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/btn/md_button.png" />
		</a>
		<div class="markdown" id="divMDPreview<?php echo $input_id; ?>">
			<p><?php echo _( 'Nothing to preview.' ); ?></p>
		</div>
	</div>
	<?php

	return ob_get_clean();
}


/**
 * Checkbox Input
 *
 * @example CheckboxInput( $value, "values[ $id ][ $name ]", '', $value, $id == 'new', button( 'check' ), button( 'x' ) );
 *
 * @uses GetInputID() to generate ID from name
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && ! $new && $div )
 *
 * @param  string  $value   Input value.
 * @param  string  $name    Input name.
 * @param  string  $title   Input title (optional). Defaults to ''.
 * @param  string  $checked Deprecated.
 * @param  boolean $new     New input (optional). Defaults to false.
 * @param  string  $yes     Checked value text (optional). Defaults to 'Yes'.
 * @param  string  $yes     Not checked value text (optional). Defaults to 'No'.
 * @param  string  $extra   Extra HTML attributes added to the input.
 * @param  boolean $div     Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string  Input HTML
 */
function CheckboxInput( $value, $name, $title = '', $checked = '', $new = false, $yes = 'Yes', $no = 'No', $div = true, $extra = '' )
{
	$checked = '';

	// $checked has been deprecated -- it remains only as a placeholder.
	if ( $value
		&& $value !== 'N' )
	{
		$checked = ' checked';
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$id = GetInputID( $name );

		$checkbox = '<label class="checkbox-label">
			<input type="checkbox" name="' . $name . '" id="' . $id . '" value="Y"' . $checked . ' ' . $extra . ' />&nbsp;' .
			$title . '</label>';

		if ( $new
			|| ! $div )
		{
			$return = $checkbox;
		}
		else
		{
			$return = InputDivOnclick(
				$id,
				$checkbox,
				( $value ?
					( $yes === 'Yes' ? _( 'Yes' ) : $yes ) :
					( $no === 'No' ? _( 'No' ) : $no ) ),
				'&nbsp;' . $title
			);
		}
	}
	else
	{
		// return ($value?$yes:$no).($title!=''?'<br />'.(mb_stripos( $title,'<span ')===false?'<span class="legend-gray">':'').$title.(mb_stripos( $title,'<span ')===false?'</span>':'').'':'');
		$return = ( $value ?
			( $yes === 'Yes' || isset( $_REQUEST['LO_save'] ) ? _( 'Yes' ) : $yes ) :
			( $no === 'No' || isset( $_REQUEST['LO_save'] ) ? _( 'No' ) : $no ) ) .
				( $title !== '' ? ' ' . $title : '' );
	}

	return $return;
}


/**
 * Select Input
 *
 * @example SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $options, 'N/A', $extra )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @param  string         $value    Input value.
 * @param  string         $name     Input name.
 * @param  string         $title    Input title (optional). Defaults to ''.
 * @param  array          $options  Input options: array( option_value => option_text ).
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A.
 * @param  string         $extra    Extra HTML attributes added to the input.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string         Input HTML
 */
function SelectInput( $value, $name, $title = '', $options = array(), $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	// Mab - support array style $option values.
	$value = is_array( $value ) ? $value[0] : $value;

	// Mab - append current val to select list if not in list.
	if ( $value != ''
		&& ( ! is_array( $options )
			|| !array_key_exists( $value, $options ) ) )
	{
		$options[ $value ] = array( $value, '<span style="color:red">' . $value . '</span>' );
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$select = '<select name="'.$name.'" id="' . $id . '" '.$extra.'>';

		if ( $allow_na !== false )
		{
			$select .= '<option value="">' . ( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</option>';
		}

		foreach ( (array) $options as $key => $val )
		{
			$selected = '';

			$key .= '';

			if ( $value == $key
				&& ( !( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$selected = ' selected';
			}

			$select .= '<option value="' . htmlspecialchars( $key, ENT_QUOTES ) . '"' .
				$selected . '>' . ( is_array( $val ) ? $val[0] : $val ) . '</option>';
		}

		$select .= '</select>';

		$select .= $ftitle;

		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$select,
				( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] ),
				$ftitle
			);
		}
		else
			$return = $select;
	}
	else
	{
		$display_val = is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ];

		if ( $display_val == '' )
		{

			if ( $allow_na !== false )
			{
				$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
			}
			else
				$display_val = '-';
		}

		$return = $display_val . $ftitle;
	}

	return $return;
}


/**
 * Multi Languages Select Input
 *
 * @example MLSelectInput(
 *          	$RET['CATEGORY_ID'],
 *          	'tables[' . $_REQUEST['id'] . '][CATEGORY_ID]',
 *          	_( 'User Field Category' ),
 *          	$categories_options,
 *          	false
 *          );
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @uses ParseMLField() to get localized options
 *
 * @global $RosarioLocales Returns simple SelectInput() if only 1 locale set
 * @global $locale         Get current locale
 *
 * @param  string         $value    Input value.
 * @param  string         $name     Input name.
 * @param  string         $title    Input title (optional). Defaults to ''.
 * @param  array          $options  Input options: array( option_value => option_text ).
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A.
 * @param  string         $extra    Extra HTML attributes added to the input.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string         Input HTML
 */
function MLSelectInput( $value, $name, $title = '', $options, $allow_na = 'N/A', $extra = '', $div = true )
{
	global $RosarioLocales,
		$locale;

	// Mab - support array style $option values.
	$value = is_array( $value ) ? $value[0] : $value;

	if ( count( $RosarioLocales ) < 2 )
	{
		return SelectInput( ParseMLField( $value, $locale ), $name, $title, $options, $div );
	}

	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	// Mab - append current val to select list if not in list.
	if ( $value != ''
		&& ( ! is_array( $options )
			|| !array_key_exists( $value, $options ) ) )
	{
		$options[ $value ] = array( $value, '<span style="color:red">' . $value . '</span>' );
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$select = '<select name="'.$name.'" id="' . $id . '" '.$extra.'>';

		if ( $allow_na !== false )
		{
			$select .= '<option value="">' . ( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</option>';
		}

		foreach ( (array) $options as $key => $val )
		{
			$selected = '';

			$key .= '';

			if ( $value == $key
				&& ( ! ( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$selected = ' selected';
			}

			$val_locale = ParseMLField( ( is_array( $val ) ? $val[0] : $val ), $locale );

			$select .= '<option value="' . htmlspecialchars( $key, ENT_QUOTES ) . '"' .
				$selected . '>' . $val_locale . '</option>';
		}

		$select .= '</select>';

		$select .= $ftitle;

		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$select,
				ParseMLField(
					( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] ),
					$locale
				),
				$ftitle
			);
		}
		else
			$return = $select;
	}
	else
	{
		$display_val = is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ];

		if ( $display_val == '' )
		{

			if ( $allow_na !== false )
			{
				$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
			}
			else
				$display_val = '-';
		}
		else
			$display_val = ParseMLField( $display_val, $locale );

		$return = $display_val . $ftitle;
	}

	return $return;
}


/**
 * Radio Input
 *
 * @example RadioInput( $value, "values[" . $id . "][COLOR]", '', $color_options );
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && ! $new && $div )
 *
 * @param  string         $value    Input value
 * @param  string         $name     Input name
 * @param  string         $title    Input title (optional). Defaults to ''
 * @param  array          $options  Input options: array( option_value => option_text )
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A
 * @param  string         $extra    Extra HTML attributes added to the input
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string         Input HTML
 */
function RadioInput( $value, $name, $title = '', $options, $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	// mab - append current val to select list if not in list
	if ( $value != ''
		&& ( !is_array( $options )
			|| !array_key_exists( $value, $options ) ) )
	{
		$options[ $value ] = array( $value, '<span style="color:red">' . $value . '</span>' );
	}

	if ( AllowEdit()
		&& !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$table = '<table class="cellspacing-0 cellpadding-5" ' . $extra . '><tr class="center">';
			
		if ( $allow_na !== false )
		{
			$table .= '<td><label><input type="radio" name="' . $name . '" value=""' .
				( $value == '' ? ' checked' : '' ) . ' /> ' .
				( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</label></td>';
		}

		foreach ( (array) $options as $key => $val )
		{
			$checked = '';

			$key .= '';

			if ( $value == $key
				&& ( !( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$checked = ' checked';
			}

			$table .= '<td><label><input type="radio" name="' . $name . '" value="' .
				htmlspecialchars( $key, ENT_QUOTES ) . '"' . $checked . ' /> ' .
				( is_array( $val ) ? $val[0] : $val ) . '</label></td>';
		}

		$table .= '</tr></table>';
		
		$table .= $ftitle;
			
		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$table,
				is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ],
				$ftitle
			);
		}
		else
			$return = $table;
	}
	else
	{
		$display_val = is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ];

		if ( $display_val == '' )
		{

			if ( $allow_na !== false )
			{
				$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
			}
			else
				$display_val = '-';
		}

		$return = $display_val . $ftitle;
	}

	return $return;
}


/**
 * Color Picker Input
 *
 * @example ColorInput( $value, "values[ $id ][ $column ]", '', 'hidden', 'data-position="bottom right"' );
 *
 * @todo Display bug when inside LO (popping out of overflow hidden / auto)
 *
 * @since 2.9
 *
 * @uses jQuery MiniColors plugin
 *
 * @see assets/js/jquery-minicolors/ for plugin files
 *
 * @link https://github.com/claviska/jquery-minicolors/
 *
 * @param  string  $value Color value
 * @param  string  $name  Input name attribute
 * @param  string  $title Input title (label)
 * @param  string  $type  hidden|text Input type attribute (optional). Defaults to 'hidden'
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string  Color Picker Input HTML
 */
function ColorInput( $value, $name, $title = '', $type = 'hidden', $extra = '', $div = true )
{
	static $included = false;

	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	$color_rect = '<div style="background-color:' . $value . '; width:30px; height:20px;"></div>';

	if ( AllowEdit()
		&& !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$js = '';

		if ( ! $included )
		{
			ob_start();
			?>
			<!-- MiniColors -->
			<link rel="stylesheet" href="assets/js/jquery-minicolors/jquery.minicolors.css" />
			<script src="assets/js/jquery-minicolors/jquery.minicolors.js"></script>
			<script>$(document).ready(function(){
				$('.minicolors').each(function(){
					$(this).minicolors({
						position: $(this).attr('data-position') || 'bottom left'
					});
				});
			});</script>
			<?php
			$js = ob_get_clean();

			$included = true;
		}

		ob_start();
		?>
		<input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $id; ?>"
			class="minicolors" value="<?php echo $value; ?>" <?php echo $extra; ?> />
		<?php

		$color = ob_get_clean() . $ftitle;

		if ( $value != ''
			&& $div )
		{
			$return = $js . InputDivOnclick(
				$id,
				$color,
				$color_rect,
				$ftitle .
				'<script>$("#div' . $id . '").on("click", function(){
					$("#' . $id . '").minicolors({
						position: $("#' . $id . '").attr("data-position") || "bottom left"
					});
				});</script>'
			);
		}
		else
			$return = $js . $color;
	}
	else
		$return = $color_rect;

	return $return;
}


/**
 * No Input
 * Simulate Input formatting for a non-editable Input / Value
 *
 * @example NoInput( $person[1]['PHONE'], _( 'Home Phone' ) )
 * 
 * @uses FormatInputTitle() to format title
 *
 * @param  string $value Input value
 * @param  string $title Input title (optional). Defaults to ''
 *
 * @return string Value + Formatted Title
 */
function NoInput( $value, $title = '' )
{
	$ftitle = FormatInputTitle( $title );

	$value = ( !empty( $value ) || $value == '0' ? $value : '-' );

	if ( AllowEdit()
		&& !isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return '<span class="no-input-value">' .
			$value .
			'</span>' . $ftitle;
	}
	else
		return $value . $ftitle;
}


/**
 * Checkbox onclick
 * When clicked the checkbox value is submitted & the URL is opened
 *
 * @example DrawHeader( CheckBoxOnclick( 'by_name', _( 'Sort by Name' ) );
 *
 * @param  string $name  Input name
 * @param  string $title Title
 *
 * @return string Checkbox onclick HTML
 */
function CheckBoxOnclick( $name, $title = '' )
{
	$onclick_URL = "'" . PreparePHP_SELF(
		$_REQUEST,
		array(),
		$_REQUEST[ $name ] == 'Y' ? array( $name => '' ) : array( $name => 'Y' )
	) . "'";

	$input = '<input type="checkbox" name="' . $name . '" value="Y"' .
		( $_REQUEST[ $name ] == 'Y' ? ' checked' : '' ) .
		' onclick="ajaxLink(' . $onclick_URL . ');" />';

	if ( $title != '' )
	{
		$input = '<label>' . $input . '&nbsp;' . $title . '</label>';
	}

	return $input;
}


/**
 * Get Javascript friendly input HTML ID attribute
 * From name attribute value
 *
 * @since 2.9
 *
 * @example GetInputID( 'cust[CUSTOM_1]' ); will return "custCUSTOM_1"
 *
 * @param  string $name Input name attribute
 *
 * @return string Input ID attribute
 */
function GetInputID( $name )
{
	if ( empty( $name ) )
		return $name;

	return str_replace( array( '[', ']', '-' ), '', $name );
}


/**
 * Format Input Title
 * Adds line break, <label> & .legend-gray CSS class to Title string
 *
 * @since 2.9
 *
 * @example $id = GetInputID( $name );
 *          $required = $value == '' && mb_strpos( $extra, 'required' ) !== false;
 *          $ftitle = FormatInputTitle( $title, $id, $required );
 *
 * @uses Use it if your *Field type (ie. password) is not supported to get a standardized Title
 *       or as an InputDivOnclick() argument
 *
 * @param  string  $title    Input Title
 * @param  string  $id       Input id attribute (optional). Defaults to ''
 * @param  boolean $required Required Input & AllowEdit() ? CSS class is .legend-red
 *
 * @return string  Formatted Input Title
 */
function FormatInputTitle( $title, $id = '', $required = false )
{
	if ( $title === '' )
	{
		return '';
	}

	// Check if span override
	if ( mb_stripos( $title, '<span ' ) === false )
	{
		$class = $required && AllowEdit() ? 'legend-red' : 'legend-gray';

		$title = '<span class="' . $class . '">' . $title . '</span>';
	}

	// Add label only if id attribute given
	if ( $id !== '' )
	{
		$title = '<label for="' . $id . '">' . $title . '</label>';
	}

	return '<br />' . $title;
}


/**
 * Input <div> onclick
 * Wraps the Input HTML inside a <div> with Value & Input Formatted Title below
 * The <div> makes the Input HTML appear and editable when clicked
 *
 * @todo Fix JS error var is not defined when InputDivOnclick() called twice (ex: see Users > User Info > Schools field)
 *
 * @since 2.9
 *
 * @example $return = InputDivOnclick( $id,	$textarea_html,	$display_val, $ftitle );
 *
 * @uses Use it if you would like to wrap various fields
 *       (ie. Student / User Name => First Name, Middle Name, Last Name)
 *
 * @param  string $id           Input ID
 * @param  string $input_html   Input HTML
 * @param  string $value        Input value
 * @param  string $input_ftitle Input Formatted Title
 *
 * @return string Wrapped Input HTML inside a <div> with Value & Input Formatted Title below
 */
function InputDivOnclick( $id, $input_html, $value, $input_ftitle )
{
	$div_onclick = '<script>var html' . $id . '=' . json_encode( $input_html ).';</script>';

	$value = $value == '' ? '-' : $value;

	$div_onclick .= '<div id="div' . $id . '">
		<div class="onclick" onclick=\'addHTML(html' . $id . ',"div' . $id . '",true); $("#' . $id . '").focus();\'>' .
		( mb_strpos( $value, '<div' ) === 0 ?
			'<div class="underline-dots">' . $value . '</div>' :
			'<span class="underline-dots">' . $value . '</span>' ) .
		$input_ftitle . '</div></div>';

	return $div_onclick;
}