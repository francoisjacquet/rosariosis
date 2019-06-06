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

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( $value != '' ? ProperDate( $value ) : '-' ) . FormatInputTitle( $title );
	}

	$options = array();

	//FJ date field is required
	if ( $required )
	{
		$options['required'] = true;
	}

	if ( $value == ''
		|| ! $div )
	{
		return PrepareDate( $value, '_' . $name, $allow_na, $options ) . $ftitle;
	}

	$options = $options + array( 'Y' => 1, 'M' => 1, 'D' => 1 );

	$input = PrepareDate( $value, '_' . $name, $allow_na, $options ) . $ftitle;

	return InputDivOnclick(
		$id,
		$input,
		( $value != '' ? ProperDate( $value ) : '-' ),
		FormatInputTitle( $title )
	);
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

	// mab - support array style $option values
	$display_val = is_array( $value ) ? $value[1] : $value;

	$value = is_array( $value ) ? $value[0] : $value;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( $value != '' ? $display_val : '-' ) . FormatInputTitle( $title );
	}

	// Input size / length based on value number of chars
	if ( mb_strpos( $extra, 'size=' ) === false )
	{
		// Max size is 32 (more or less 300px)
		$extra .= $value != '' ? ' size="' . min( mb_strlen( $value ), 32 ) . '"' : ' size="10"';
	}

	// Specify input type via $extra (email,...).
	$type = mb_strpos( $extra, 'type=' ) === false ? 'type="text"' : '';

	$input = '<input ' . $type . ' id="' . $id . '" name="' . $name . '" ' .
		( $value || $value === '0' ? 'value="' . htmlspecialchars( $value, ENT_QUOTES ) . '"' : '' ) .
		' ' . $extra . ' />' . FormatInputTitle( $title, $id, $required );

	if ( trim( $value ) == ''
		|| ! $div )
	{
		return $input;
	}

	return InputDivOnclick(
		$id,
		$input,
		( $value != '' ? $display_val : '-' ),
		FormatInputTitle( $title )
	);
}


/**
 * Password Input
 *
 * @example echo PasswordInput( '****', 'PASSWORD', _( 'Password' ), 'required strength' );
 *
 * @since 4.4
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 * @uses TextInput
 *
 * @param  string  $value Input value.
 * @param  string  $name  Input name.
 * @param  string  $title Input title (optional). Defaults to ''.
 * @param  string  $extra Extra HTML attributes. Pass 'strength' to display strength indicator.
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string  Input HTML
 */
function PasswordInput( $value, $name, $title = '', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$strength = ( mb_strpos( $extra, 'strength' ) !== false );

	// mab - support array style $option values
	$display_val = is_array( $value ) ? $value[1] : $value;

	$value = is_array( $value ) ? $value[0] : $value;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( $value != '' ? $display_val : '-' ) . FormatInputTitle( $title );
	}

	// Default input size.
	if ( $value === ''
		&& mb_strpos( $extra, 'size=' ) === false )
	{
		$extra .= ' size="20"';
	}
	elseif ( mb_strpos( $extra, 'size=' ) === false )
	{
		$extra .= ' size="' . ( strlen( $value ) + 5 ) . '"';
	}

	$extra .= ' type="password" autocomplete="off"';

	$input = TextInput( '', $name, '', $extra, false );

	$lock_icons = button( 'unlocked', '', '', 'password-toggle password-show' ) .
		button( 'locked', '', '', 'password-toggle password-hide' );

	$password_strength_bars = '';

	$min_required_strength = $strength ? Config( 'PASSWORD_STRENGTH' ) : 0;

	if ( $strength
		&& $min_required_strength )
	{
		$password_strength_bars = '<div class="password-strength-bars">
			<span class="score0"></span>
			<span class="score1"></span>
			<span class="score2"></span>
			<span class="score3"></span>
			<span class="score4"></span>
		</div>';
	}

	ob_start();

	// Call our jQuery PasswordStrength plugin based on zxcvbn.
	?>
	<script>
		$('#' + <?php echo json_encode( $id ); ?>).passwordStrength(
			<?php echo (int) $min_required_strength; ?>,
			// Error message when trying to submit the form.
			<?php echo json_encode( _( 'Your password must be stronger.' ) ); ?>
		);
	</script>
	<?php
	$password_strength_js = ob_get_clean();

	$input .= $lock_icons . $password_strength_bars .
		FormatInputTitle(	$title,	$id, $required ) . $password_strength_js;

	$input = '<div class="password-input-wrapper">' . $input . '</div>';

	if ( trim( $value ) == ''
		|| ! $div )
	{
		return $input;
	}

	return InputDivOnclick(
		$id,
		$input,
		( $value != '' ? $display_val : '-' ),
		FormatInputTitle( $title )
	);
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

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Input size / length based on value number of chars.
		if ( mb_strpos( $extra, 'size=' ) === false
			&& $value != '' )
		{
			$nb_loc = mb_substr_count( $value, '|' ) + 1;

			$extra .=  ' size="' .
				round( ( mb_strlen( $value ) - ( $nb_loc - 1 ) * ( mb_strlen( $RosarioLocales[0] ) + 2 ) )
					/ $nb_loc ) . '"';
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
			$language = function_exists( 'locale_get_display_language' ) ?
				ucfirst( locale_get_display_language( $loc, $locale ) ) :
				str_replace( '.utf8', '', $loc );

			$return .= '<label><img src="locale/' . $loc . '/flag.png" class="button bigger" alt="' . htmlspecialchars( $language, ENT_QUOTES ) . '" title="' . htmlspecialchars( $language, ENT_QUOTES ) . '" /> ';

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

		$title_break = '';
	}
	else
	{
		$return .= ParseMLField( $value );

		$title_break = '<br />';
	}

	$return .= FormatInputTitle( $title, '', false, $title_break );

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
 *
 * @uses MarkDownInputPreview()
 *
 * @uses ShowDown jQuery plugin for MarkDown rendering called using the .markdown-to-html CSS class
 *
 * @param  string  $value    Input value.
 * @param  string  $name     Input name.
 * @param  string  $title    Input title (optional). Defaults to ''.
 * @param  string  $extra    Extra HTML attributes added to the input.
 * @param  boolean $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 * @param  boolean $markdown markdown|tinymce|text Text Type (optional). Defaults to 'markdown'.
 *
 * @return string  Input HTML
 */
function TextAreaInput( $value, $name, $title = '', $extra = '', $div = true, $type = 'markdown' )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	$ftitle_nobr = FormatInputTitle( $title, $id, $required, '' );

	if ( $value != '' )
	{
		if ( $type === 'markdown' )
		{
			// Convert MarkDown to HTML.
			$display_val = '<div class="markdown-to-html">' . $value . '</div>';
		}
		elseif ( $type === 'tinymce' )
		{
			$display_val = '<div class="tinymce-html">' . $value . '</div>';
		}
		else
			$display_val = nl2br( $value );
	}
	else
		$display_val = '-';

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $display_val . ( $type !== 'text' && $display_val !== '-' ?
			FormatInputTitle( $title, '', false, '' ) :
			FormatInputTitle( $title ) );
	}

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
		$extra .= ' rows=4';
	}

	$textarea =  ( $type === 'markdown' ? MarkDownInputPreview( $id ) : '' ) .
		'<textarea id="' . $id . '" name="' . $name . '" ' . $extra . '>' .
		$value . '</textarea>' . ( $type === 'tinymce' ? $ftitle_nobr : $ftitle );

	if ( $value == ''
		|| ! $div )
	{
		return $textarea;
	}

	return InputDivOnclick(
		$id,
		$textarea,
		$display_val,
		FormatInputTitle( $title )
	);
}



/**
 * TinyMCE Input (HTML editor)
 *
 * Note: if you will pass additional CSS classes in the `$extra` parameter
 * Add the `tinymce-horizontal` class for Horizontal PDF.
 * Do not forget the `tinymce` class required to trigger TinyMCE.
 *
 * @todo Fix <label>, see http://stackoverflow.com/questions/4258701/tinymce-accessibility-label-for
 * @todo Allow passing options to TinyMCE (plugins, ...)
 *
 * @example TinyMCEInput( $RET[1]['TEMPLATE'], 'tinymce_textarea' )
 * @example TinyMCEInput( $html, 'tinymce_horizontal', _( 'Horizontal PDF' ), 'class="tinymce-horizontal"' )
 *
 * @uses TextAreaInput()
 *
 * @see TinyMCE Javascript plugin for HTML edition in assets/js/tinymce/
 *
 * @since 2.9
 *
 * @global $locale Locale to translate TinyMCE interface.
 *
 * @param  string  $value    Input value.
 * @param  string  $name     Input name.
 * @param  string  $title    Input title (optional). Defaults to ''.
 * @param  string  $extra    Extra HTML attributes added to the input (optional). Defaults to ''.
 *
 * @return string  Input HTML
 */
function TinyMCEInput( $value, $name, $title = '', $extra = '' )
{
	global $locale;

	static $js_included = false;

	$div = false;

	$wrapper = '';

	if ( mb_strpos( (string) $extra, 'class=' ) === false )
	{
		$extra = 'class="tinymce" ' . $extra;
	}
	else
	{
		// If has .tinymce-horizontal class, add wrapper, needed here.
		if ( mb_strpos( (string) $extra, 'tinymce-horizontal' ) !== false )
		{
			$extra = str_replace(
				'tinymce-horizontal',
				'',
				$extra
			);

			$wrapper = '<div class="tinymce-horizontal">';
		}

		$extra = str_replace(
			array( 'class="', "class='" ),
			array( 'class="tinymce ', "class='tinymce " ),
			$extra
		);
	}

	if ( mb_strpos( (string) $extra, 'required' ) !== false )
	{
		// Remove required attribute, TinyMCE bug.
		$extra = str_replace( 'required', '', $extra );
	}

	$textarea = TextAreaInput( $value, $name, $title, $extra , $div, 'tinymce' );

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $textarea;
	}

	if ( $wrapper )
	{
		$textarea = $wrapper . $textarea . '</div>';
	}

	$tinymce_js = '';

	if ( ! $js_included )
	{
		$tinymce_language = '';

		$tinymce_directionality = 'ltr';

		if ( $locale !== 'en_US.utf8' )
		{
			if ( file_exists( 'assets/js/tinymce/langs/' . mb_substr( $locale, 0, 2 ) . '.js' ) )
			{
				// For example: es (Spanish).
				$tinymce_language = mb_substr( $locale, 0, 2 );
			}
			elseif ( file_exists( 'assets/js/tinymce/langs/' . mb_substr( $locale, 0, 5 ) . '.js' ) )
			{
				// For example: fr_FR (French).
				$tinymce_language = mb_substr( $locale, 0, 5 );
			}

			if ( $tinymce_language )
			{
				$lang_2_chars = mb_substr( $locale, 0, 2 );

				// Right to left direction.
				$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

				$tinymce_directionality = in_array( $lang_2_chars, $RTL_languages ) ? 'rtl' : 'ltr';
			}
		}

		// Include main TinyMCE javascript
		// and its configuration (plugin, language...).
		ob_start(); ?>

<script src="assets/js/tinymce/tinymce.min.js"></script>
<script>
	tinymce.init({
		selector: '.tinymce',
		plugins: 'link image pagebreak paste table textcolor colorpicker code fullscreen hr media lists',
		toolbar: "bold italic underline bullist numlist alignleft aligncenter alignright alignjustify link image forecolor backcolor code fullscreen",
		menu: {
			// file: {title: 'File', items: 'newdocument'},
			edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext'},
			insert: {title: 'Insert', items: 'media | hr pagebreak | inserttable cell row column'},
			// view: {title: 'View', items: 'visualaid'},
			format: {title: 'Format', items: 'formats | removeformat'}
		},
		paste_data_images: true,
		images_upload_handler: function (blobInfo, success, failure) {
			success("data:" + blobInfo.blob().type + ";base64," + blobInfo.base64());
		},
		pagebreak_separator: '<div style="page-break-after: always;"></div>',
		language: <?php echo json_encode( $tinymce_language ); ?>,
		directionality : <?php echo json_encode( $tinymce_directionality ); ?>,
		relative_urls: false,
		// verify_html: false,
		remove_script_host: false
	});
</script><!-- /TinyMCE -->

		<?php $tinymce_js = ob_get_clean();

		$js_included = true;
	}

	return $tinymce_js . $textarea;
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

		<a href="https://gitlab.com/francoisjacquet/rosariosis/wikis/Markdown-Cheatsheet" title="<?php echo htmlspecialchars( _( 'Mastering MarkDown' ), ENT_QUOTES ); ?>" target="_blank" class="md-link">
			<img class="button" src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/btn/md_button.png" alt="<?php echo htmlspecialchars( _( 'Mastering MarkDown' ), ENT_QUOTES ); ?>" />
		</a>
		<div class="markdown-to-html" id="divMDPreview<?php echo $input_id; ?>"><?php echo _( 'Nothing to preview.' ); ?></div>
	</div>
	<?php

	return ob_get_clean();
}


/**
 * Checkbox Input
 *
 * @example CheckboxInput( $value, 'values[' . $id . '][' . $name . ']', '', $value, $id == 'new', button( 'check' ), button( 'x' ) );
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

		$checkbox = '<input type="hidden" name="' . $name . '" value="" />' . // Save unchecked value!
			'<label class="checkbox-label">
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
 * Multiple Checkbox Input
 * Do not forget to add '[]' (array) after your input name.
 *
 * @since 4.2
 * @since 4.5 Allow associative $options array.
 *
 * @example MultipleCheckboxInput( $value, 'values[' . $id . '][' . $name . '][]' );
 *
 * @uses GetInputID() to generate ID from name
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && ! $new && $div )
 * @uses InputDivOnclick()
 *
 * @param  string  $value   Input value(s), delimited by 2 pipes. For example: '||Value1||Value2||'.
 * @param  string  $name    Input name.
 * @param  string  $title   Input title (optional). Defaults to ''.
 * @param  array   $options Input options: array( option_value => option_text ).
 * @param  string  $extra   Extra HTML attributes added to the input. (optional).
 * @param  boolean $div     Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string  Inputs HTML
 */
function MultipleCheckboxInput( $value, $name, $title, $options, $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$multiple_value = ( $value != '' ) ?
		str_replace( '||', ', ', mb_substr( $value, 2, -2 ) ) :
		'-';

	if ( ! AllowEdit()
	 	|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $multiple_value . FormatInputTitle( $title );
	}

	$multiple_html = '<table class="cellpadding-5"><tr class="st">';

	$i = 0;

	foreach ( (array) $options as $option_value => $option )
	{
		if ( $i++ % 3 == 0 )
		{
			$multiple_html .= '</tr><tr class="st">';
		}

		if ( is_int( $option_value ) )
		{
			// Not an associative array, use Text as value.
			$option_value = $option;
		}

		$multiple_html .= '<td><label>
			<input type="checkbox" name="' . $name . '"
				value="' . htmlspecialchars( $option_value, ENT_QUOTES ) . '" ' . $extra . ' ' .
				( $option != '' && mb_strpos( $value, $option_value ) !== false ? ' checked' : '' ) . ' />&nbsp;' .
			( $option != '' ? $option : '-' ) .
		'</label></td>';
	}

	$multiple_html .= '</tr></table>' . FormatInputTitle( $title, '', $required, '' );

	if ( $value != ''
		&& $div )
	{
		$return = InputDivOnclick(
			$id,
			$multiple_html,
			$multiple_value,
			FormatInputTitle( $title )
		);
	}
	else
		$return = $multiple_html;

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

	// Mab - support array style $option values.
	$value = is_array( $value ) ? $value[0] : $value;

	// Mab - append current val to select list if not in list.
	if ( $value != ''
		&& ( ! is_array( $options )
			|| ! array_key_exists( $value, $options ) ) )
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

		$select .= '</select>' . FormatInputTitle( $title, $id, $required );

		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$select,
				( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] ),
				FormatInputTitle( $title )
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

		$return = $display_val . FormatInputTitle( $title );
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

		$select .= '</select>' . FormatInputTitle( $title, $id, $required );

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
				FormatInputTitle( $title )
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

		$return = $display_val . FormatInputTitle( $title );
	}

	return $return;
}


/**
 * SelectInput() wrapper which adds jQuery Chosen.
 *
 * Chosen is a library for making long, unwieldy select boxes more friendly.
 * @link https://github.com/harvesthq/chosen
 *
 * @since 2.9.5
 *
 * @example ChosenSelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $options, 'N/A', $extra )
 *
 * @uses SelectInput() to generate the Select input
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
function ChosenSelectInput( $value, $name, $title = '', $options = array(), $allow_na = 'N/A', $extra = '', $div = true )
{
	static $chosen_included = false;

	$js = '';

	if ( ! $chosen_included
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{

		ob_start();	?>
		<!-- Chosen -->
		<script src="assets/js/jquery-chosen/chosen.jquery.min.js"></script>
		<link rel="stylesheet" href="assets/js/jquery-chosen/chosen.min.css">
		<script>
			$(document).ready(function(){
				$('.chosen-select').chosen('.chosen-select');
			});
		</script>
		<?php $chosen_included = true;

		$js = ob_get_clean();
	}

	// Right to left direction.
	$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

	$chosen_rtl = in_array( mb_substr( $_SESSION['locale'], 0, 2 ), $RTL_languages ) ? ' chosen-rtl' : '';

	if ( ! $extra
		|| mb_strpos( $extra, 'class=' ) === false )
	{
		$extra .= ' class="chosen-select' . $chosen_rtl . '"';
	}
	elseif ( mb_strpos( $extra, 'class=' ) !== false )
	{
		$extra = str_replace(
			array( 'class="', "class='" ),
			array( 'class="chosen-select' . $chosen_rtl . ' ', "class='chosen-select" . $chosen_rtl . ' ' ),
			$extra
		);
	}

	// Translate default "Select Some Options" multiple placeholder.
	if ( mb_strpos( $extra, 'multiple' ) !== false
		&& mb_strpos( $extra, 'data-placeholder=' ) === false )
	{
		$extra .= ' data-placeholder="' . htmlspecialchars( _( 'Select some Options' ), ENT_QUOTES ) . '"';
	}

	$return = $js . SelectInput(
		$value,
		$name,
		$title,
		$options,
		$allow_na,
		$extra,
		$div
	);

	if ( $value != ''
		&& $div
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$id = GetInputID( $name );

		// On InputDivOnClick(), call Chosen.
		$return .= '<script>$("#div' . $id . '").on("click", function(){
			$("#' . $id . '").chosen();
		});</script>';
	}

	return $return;
}


/**
 * Radio Input
 *
 * @example RadioInput( $value, 'values[' . $id . '][COLOR]', '', $color_options );
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
 * @param  string         $extra    Extra HTML attributes added to the input (optional).
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string         Input HTML
 */
function RadioInput( $value, $name, $title = '', $options, $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	// mab - append current val to select list if not in list
	if ( $value != ''
		&& ( ! is_array( $options )
			|| ! array_key_exists( $value, $options ) ) )
	{
		$options[ $value ] = array( $value, '<span style="color:red">' . $value . '</span>' );
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$table = '<table class="cellspacing-0 cellpadding-5"><tr class="st">';

		$i = 0;

		if ( $allow_na !== false )
		{
			$table .= '<td><label><input type="radio" name="' . $name . '" value=""' .
				( $value == '' ? ' checked' : '' ) . ' ' . $extra . ' /> ' .
				( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</label></td>';

			$i++;
		}

		foreach ( (array) $options as $key => $val )
		{
			if ( $i++ % 3 == 0 )
			{
				$table .= '</tr><tr class="st">';
			}

			$checked = '';

			$key .= '';

			if ( $value == $key
				&& ( !( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$checked = ' checked';
			}

			$table .= '<td><label><input type="radio" name="' . $name . '" value="' .
				htmlspecialchars( $key, ENT_QUOTES ) . '"' . $checked . ' ' . $extra . ' /> ' .
				( is_array( $val ) ? $val[0] : $val ) . '</label></td>';
		}

		$table .= '</tr></table>';

		$table .= FormatInputTitle( $title, '', $required, '' );

		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$table,
				is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ],
				FormatInputTitle( $title )
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

		$return = $display_val . FormatInputTitle( $title );
	}

	return $return;
}


/**
 * Color Picker Input
 *
 * @example ColorInput( $value, 'values[' . $id . '][' . $column . ']', '', 'hidden', 'data-position="bottom right"' );
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
 * @param  string  $extra Extra HTML attributes added to the input (optional).
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string  Color Picker Input HTML
 */
function ColorInput( $value, $name, $title = '', $type = 'hidden', $extra = '', $div = true )
{
	static $included = false;

	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$color_rect = '<div style="background-color:' . $value . '; width:30px; height:20px;"></div>';

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $color_rect . FormatInputTitle( $title, '', '', '' );
	}

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

	$color = ob_get_clean() . FormatInputTitle( $title, $id, $required );

	if ( $value != ''
		&& $div )
	{
		return $js . InputDivOnclick(
			$id,
			$color,
			$color_rect,
			FormatInputTitle( $title ) .
			'<script>$("#div' . $id . '").on("click", function(){
				$("#' . $id . '").minicolors({
					position: $("#' . $id . '").attr("data-position") || "bottom left"
				});
			});</script>'
		);
	}

	return $js . $color;
}


/**
 * Captcha Input
 *
 * @example CaptchaInput( 'captcha' . rand( 100, 9999 ), _( 'Captcha' ) );
 *
 * @since 3.5
 *
 * @see assets/js/warehouse.js for captcha JS functions.
 *
 * @uses $_SESSION['CaptchaInput']
 * Places input name in session
 * so we can retrieve & check the input & answer values before processing form
 *
 * @param  string  $name  Input name attribute (array: 'input' & 'answer' indexes available).
 * @param  string  $title Input title (label)
 * @param  string  $extra Extra HTML attributes added to the input (optional).
 *
 * @return string  Captcha Input HTML
 */
function CaptchaInput( $name, $title, $extra = '' )
{
	// Place input name in session
	// so we can retrieve & check the input & answer values before processing form.
	$_SESSION['CaptchaInput'] = $name;

	$id_base = GetInputID( $name );

	$required = true;

	ob_start(); ?>
	<div class="captcha">
		<span id="<?php echo $id_base; ?>-n1"></span> + <span id="<?php echo $id_base; ?>-n2"></span> = <input id="<?php echo $id_base; ?>-input" name="<?php echo $name; ?>[input]" type="number" required />
		<input id="<?php echo $id_base; ?>-answer" name="<?php echo $name; ?>[answer]" type="hidden" <?php echo $extra; ?> />
		<?php echo FormatInputTitle( $title, $id_base . '-input', $required ); ?>
	</div>
	<script>captcha(<?php echo json_encode( $id_base ); ?>);</script>
	<?php
	$captcha_html = ob_get_clean();

	return $captcha_html;
}


/**
 * Check Captcha
 * Compare captcha input with answer.
 *
 * @since 3.5
 *
 * @uses $_SESSION['CaptchaInput'] where captcha name is stored.
 *
 * @see Create User / Student forms.
 *
 * @example if ( ! CheckCaptcha() )	$error[] = _( 'Captcha' );
 *
 * @return boolean True if no captcha or if captcha passed, else false.
 */
function CheckCaptcha()
{
	if ( ! isset( $_SESSION['CaptchaInput'] )
		|| ! $_SESSION['CaptchaInput'] )
	{
		return true;
	}

	// Get submitted captcha using captcha name saved in session.
	$captcha = isset( $_REQUEST[ $_SESSION['CaptchaInput'] ] ) ?
		$_REQUEST[ $_SESSION['CaptchaInput'] ] :
		array();

	// Compare input & answer.
	return $captcha && $captcha['input'] === $captcha['answer'];
}


/**
 * File Input
 *
 * @since 3.8.1
 *
 * @example FileInput( 'values[new][FILE]', _( 'File' ), 'required' )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 *
 * @param  string  $name  Input name.
 * @param  string  $title Input title (optional). Defaults to ''.
 * @param  string  $extra Extra HTML attributes added to the input.
 *
 * @return string  Input HTML
 */
function FileInput( $name, $title = '', $extra = '' )
{
	require_once 'ProgramFunctions/FileUpload.fnc.php';

	$id = GetInputID( $name );

	$required = mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	// Input size / length based on value number of chars.
	if ( mb_strpos( $extra, 'size=' ) === false )
	{
		$extra .= ' size="10"';
	}

	// Input title indicating Maximum file size.
	if ( mb_strpos( $extra, 'title=' ) === false )
	{
		$extra .= ' title="' . sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) . '"';
	}

	$input = '<input type="file" id="' . $id . '" name="' . $name . '" ' . $extra . ' />
		<span class="loading"></span>' . $ftitle;

	return $input;
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

	$value = ( ! empty( $value ) || $value == '0' ? $value : '-' );

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
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
	{
		return $name;
	}

	return str_replace( array( '[', ']', '-', ' ' ), '', $name );
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
 * @param  string  $break    Break before title (optional). Defaults to '<br />'
 *
 * @return string  Formatted Input Title
 */
function FormatInputTitle( $title, $id = '', $required = false, $break = '<br />' )
{
	if ( $title === '' )
	{
		return '';
	}

	if ( mb_strpos( $title, 'a11y-hidden' ) !== false )
	{
		// Accessibility hidden title: force break to empty string.
		$break = '';
	}
	else
	{
		// Not hidden, add legend class color.
		$class = $required && AllowEdit() ? 'legend-red' : 'legend-gray';

		$title = '<span class="' . $class . '">' . $title . '</span>';
	}

	// Add label only if id attribute given
	if ( $id !== '' )
	{
		$title = '<label for="' . $id . '">' . $title . '</label>';
	}

	return $break . $title;
}


/**
 * Input <div> onclick
 * Wraps the Input HTML inside a <div> with Value & Input Formatted Title below
 * The <div> makes the Input HTML appear and editable when clicked
 *
 * @todo Fix JS error var is not defined when InputDivOnclick() called twice!
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
		<div class="onclick" tabindex="0" onfocus=\'addHTML(html' . $id . ',"div' . $id . '",true); $("#' . $id . '").focus(); $("#div' . $id . '").click();\'>' .
		( mb_strpos( $value, '<div' ) === 0 ?
			'<div class="underline-dots">' . $value . '</div>' :
			'<span class="underline-dots">' . $value . '</span>' ) .
		$input_ftitle . '</div></div>';

	return $div_onclick;
}


/**
 * Make Choose Checkbox
 *
 * Use in extra columns for ListOutput
 *
 * DBGet() callback
 * AND use first, to set controller checkbox & default checked value & $THIS_RET column if need be.
 *
 * @since 4.3
 *
 * First, set controller:
 * @example $extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
 * Then, use as DBGet() callback:
 * @example $extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
 *
 * @param string $value           DB value or checked ('Y').
 * @param string $column          Current DBGet column or $THIS_RET column to use (optional).
 * @param string $controller_name Controller name (set first only), ie. 'student' will give 'student[]' (optional).
 */
function MakeChooseCheckbox( $value, $column = '', $controller_name = '' )
{
	global $THIS_RET;

	static $controller_column,
		$name,
		$checked;

	if ( ! empty( $controller_name ) )
	{
		$controller_column = $column;

		$name = $controller_name;

		$checked = $value === 'Y';

		if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			return $checked ? 'x' : '';
		}

		return '</a><input type="checkbox" value="Y" name="controller" id="controller"
			onclick="checkAll(this.form,this.checked,\'' . $controller_name .'\');"' .
			( $checked ? ' checked' : '' ) . ' />
			<label for="controller" class="a11y-hidden">' . _( 'Check All' ) . '</label><a>';
	}

	if ( ! empty( $controller_column ) )
	{
		$value = isset( $THIS_RET[ $controller_column ] ) ? $THIS_RET[ $controller_column ] : '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $checked ? 'x' : '';
	}

	return '<label><input type="checkbox" name="' . $name . '[]" value="' . $value . '"' .
		( $checked ? ' checked' : '' ) . ' /><span class="a11y-hidden">' .
		_( 'Select' ) . '</span></label>';
}
