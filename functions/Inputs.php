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

	$options = [
		'required' => $required,
	];

	if ( $value == ''
		|| ! $div )
	{
		return PrepareDate( $value, '_' . $name, $allow_na, $options ) . $ftitle;
	}

	$options = $options + [ 'Y' => 1, 'M' => 1, 'D' => 1 ];

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
 * @example TextInput( Config( 'NAME' ), 'values[config][NAME]', _( 'Program Name' ), 'required' )
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

	// mab - support array style $option values
	$display_val = is_array( $value ) ? $value[1] : $value;

	$value = is_array( $value ) ? $value[0] : $value;

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( $value != '' ? $display_val : '-' ) . FormatInputTitle( $title );
	}

	// Input size / length based on value number of chars
	if ( mb_strpos( $extra, 'size=' ) === false )
	{
		// Max size is 32 (more or less 300px)
		$size = min( mb_strlen( (string) $value ), 32 );

		// Min size is 2 (more or less 35px)
		$size = max( $size, 2 );

		$extra .= $value != '' ? ' size="' . $size . '"' : ' size="12"';
	}

	// Specify input type via $extra (email,...).
	$type = mb_strpos( $extra, 'type=' ) === false ? 'type="text"' : '';

	$input = '<input ' . $type . ' id="' . $id . '" name="' . AttrEscape( $name ) .
		'" value="' . AttrEscape( $value ) . '" ' . $extra . '>' .
		FormatInputTitle( $title, $id, $required );

	if ( is_null( $value )
		|| trim( $value ) == ''
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
 * @example echo PasswordInput( '********', 'PASSWORD', _( 'Password' ), 'required strength' );
 *
 * @since 4.4
 * @since 5.5.1 Fill input value if $value != '********'.
 * @since 11.1 Prevent using App name, username, or email in the password
 *
 * @global $_ROSARIO['PasswordInput']['user_inputs'] used in PasswordReset.php, FirstLogin.fnc.php & Preferences.php
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
	global $_ROSARIO;

	$id = GetInputID( $name );

	$strength = ( mb_strpos( $extra, 'strength' ) !== false );

	// mab - support array style $option values
	$display_val = is_array( $value ) ? $value[1] : $value;

	$value = is_array( $value ) ? $value[0] : $value;

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( $value != '' ? $display_val : '-' ) . FormatInputTitle( $title );
	}

	// Default input size.
	if ( $value == ''
		&& mb_strpos( $extra, 'size=' ) === false )
	{
		$extra .= ' size="17"';
	}
	elseif ( mb_strpos( $extra, 'size=' ) === false )
	{
		$extra .= ' size="' . ( strlen( $value ) + 5 ) . '"';
	}

	$extra .= ' type="password" autocomplete="new-password"';

	$input = TextInput( ( $value !== str_repeat( '*', 8 ) ? $value : '' ), $name, '', $extra, false );

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

	// @since 11.1 Prevent using App name, username, or email in the password
	$user_inputs = array_merge(
		[ Config( 'NAME' ) ],
		// Add username & email to this global var before calling PasswordInput().
		issetVal( $_ROSARIO['PasswordInput']['user_inputs'], [] )
	);

	ob_start();

	// Call our jQuery PasswordStrength plugin based on zxcvbn.
	?>
	<script>
		$('#' + <?php echo json_encode( $id ); ?>).passwordStrength(
			<?php echo (int) $min_required_strength; ?>,
			// Error message when trying to submit the form.
			<?php echo json_encode( _( 'Your password must be stronger.' ) ); ?>,
			<?php echo json_encode( $user_inputs ); ?>
		);
	</script>
	<?php
	$password_strength_js = ob_get_clean();

	$input .= $lock_icons . $password_strength_bars .
		FormatInputTitle( $title, $id, $required ) . $password_strength_js;

	$input = '<div class="password-input-wrapper">' . $input . '</div>';

	if ( is_null( $value )
		|| trim( $value ) == ''
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
 * @since 5.5.2 Fix save first language in ML fields if not en_US.utf8.
 *
 * @example MLTextInput( Config( 'TITLE' ), 'values[config][TITLE]', _( 'Program Title' ) )
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

		$return .= '<div class="ml-text-input"><input type="hidden" id="' . $id . '" name="' . AttrEscape( $name ) . '" value="' . AttrEscape( $value ) . '" autocomplete="off">';

		if ( mb_strpos( $extra, 'size=' ) === false
			&& $value != '' )
		{
			// MLInput size based on current locale value length.
			$extra .=  ' size="' . mb_strlen( ParseMLField( $value ) ) . '"';
		}

		foreach ( (array) $RosarioLocales as $key => $loc )
		{
			$language = function_exists( 'locale_get_display_language' ) ?
				ucfirst( locale_get_display_language( $loc, $locale ) ) :
				str_replace( '.utf8', '', $loc );

			$return .= '<label><img src="locale/' . $loc . '/flag.png" class="button bigger" alt="' . AttrEscape( $language ) . '" title="' . AttrEscape( $language ) . '"> ';

			//$return .= TextInput(ParseMLField($value, $loc),'ML_'.$name.'['.$loc.']','',$extra." onchange=\"javascript:setMLvalue('".$name."','".($id==0?'':$loc)."',this.value);\"",false);

			$onchange_js = 'setMLvalue(' . json_encode( $id ) . ',' . json_encode( $loc ) . ',this.value);';

			$return .= TextInput(
				ParseMLField( $value, $loc ),
				'ML_' . $name . '[' . $loc . ']',
				'',
				$extra . ( $key == 0 ? ' required' : '' ) .
					' onchange="' . AttrEscape( $onchange_js ) . '"',
				false
			);

			$return .= '</label><br>';
		}

		$return .= '</div>';

		$title_break = '';
	}
	else
	{
		$return = ParseMLField( $value );

		$title_break = '<br>';
	}

	return $return . FormatInputTitle( $title, '', false, $title_break );
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
 * @param  string  $type     markdown|tinymce|text Text Type (optional). Defaults to 'markdown'.
 *
 * @return string  Input HTML
 */
function TextAreaInput( $value, $name, $title = '', $extra = '', $div = true, $type = 'markdown' )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$ftitle = FormatInputTitle( $title, $id, $required );

	$ftitle_nobr = FormatInputTitle( $title, $id, $required, '' );

	if ( $type === 'tinymce'
		&& mb_strpos( (string) $extra, 'required' ) !== false )
	{
		// Remove required attribute, TinyMCE bug.
		$extra = str_replace( 'required', '', $extra );
	}

	$display_val = '-';

	if ( $value != '' )
	{
		$display_val = nl2br( $value );

		if ( $type === 'markdown' )
		{
			// Convert MarkDown to HTML.
			$display_val = '<div class="markdown-to-html">' . $value . '</div>';
		}
		elseif ( $type === 'tinymce' )
		{
			$display_val = '<div class="tinymce-html">' . $value . '</div>';
		}
	}

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
		'<textarea id="' . $id . '" name="' . AttrEscape( $name ) . '" ' . $extra . '>' .
		// Fix Stored XSS security issue: escape textarea HTML.
		htmlspecialchars(
			// Prevent double encoding single quote (&#039;), was encoded by SanitizeHTML() or SanitizeMarkDown()
			str_replace( '&#039;', "'", (string) $value )
		) .
		'</textarea>' . ( $type === 'tinymce' ? $ftitle_nobr : $ftitle );

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

	$extra = 'class="tinymce" ' . $extra;

	if ( mb_strpos( (string) $extra, 'class=' ) !== false )
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
	}

	$textarea = TextAreaInput( $value, $name, $title, $extra, $div, 'tinymce' );

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
				$RTL_languages = [ 'ar', 'he', 'dv', 'fa', 'ur', 'ps' ];

				$tinymce_directionality = in_array( $lang_2_chars, $RTL_languages ) ? 'rtl' : 'ltr';
			}
		}

		// Include main TinyMCE javascript
		// and its configuration (plugin, language...).
		ob_start(); ?>

<script src="assets/js/tinymce/tinymce.min.js?v=4.9.8"></script>
<script>
	tinymceSettings = {
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
		remove_script_host: false,
		external_plugins: {
			// Add your plugins using the action hook below.
		}
	};
</script>
		<?php
		/**
		 * TinyMCE before init action hook
		 *
		 * @since 5.3
		 */
		do_action( 'functions/Inputs.php|tinymce_before_init' ); ?>
<script>
	tinymce.init(tinymceSettings);
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
		<a href="#" onclick="<?php echo AttrEscape( 'MarkDownInputPreview(' .
			json_encode( $input_id ) .
			'); return false;' ); ?>" class="tab disabled"><?php echo _( 'Write' ); ?></a>

		<a href="#" onclick="<?php echo AttrEscape( 'MarkDownInputPreview(' .
			json_encode( $input_id ) .
			'); return false;' ); ?>" class="tab"><?php echo _( 'Preview' ); ?></a>

		<a href="https://gitlab.com/francoisjacquet/rosariosis/wikis/Markdown-Cheatsheet" title="<?php echo AttrEscape( _( 'Mastering MarkDown' ) ); ?>" target="_blank" class="md-link">
			<img class="button" src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/btn/md_button.png" alt="<?php echo AttrEscape( _( 'Mastering MarkDown' ) ); ?>">
		</a>
		<div class="markdown-to-html" id="<?php echo GetInputID( 'divMDPreview' . $input_id ); ?>"></div>
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

		$checkbox = '<input type="hidden" name="' . AttrEscape( $name ) . '" value="">' . // Save unchecked value!
			'<label class="checkbox-label">
			<input type="checkbox" name="' . AttrEscape( $name ) . '" id="' . $id . '" value="Y"' . $checked . ' ' . $extra . '>&nbsp;' .
			$title . '</label>';

		if ( $new
			|| ! $div )
		{
			return $checkbox;
		}

		return InputDivOnclick(
			$id,
			$checkbox,
			( $value ?
				( $yes === 'Yes' ? _( 'Yes' ) : $yes ) :
				( $no === 'No' ? _( 'No' ) : $no ) ),
			'&nbsp;<span class="checkbox-label">' . $title . '</span>'
		);
	}

	return ( $value ?
		( $yes === 'Yes' || isset( $_REQUEST['LO_save'] ) ? _( 'Yes' ) : $yes ) :
		( $no === 'No' || isset( $_REQUEST['LO_save'] ) ? _( 'No' ) : $no ) ) .
			( $title !== '' ? ' ' . $title : '' );
}


/**
 * Multiple Checkbox Input
 * Do not forget to add '[]' (array) after your input name.
 *
 * @since 4.2
 * @since 4.5 Allow associative $options array.
 * @since 6.1 Allow numeric key (ID) in associative $options array.
 * @since 10.8.1 Fix save all unchecked, add hidden empty checkbox
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

	$multiple_html = '<table class="cellspacing-0 cellpadding-5"><tr class="st">';

	$i = 0;

	$associative_array = $options !== array_values( $options );

	foreach ( (array) $options as $option_value => $option )
	{
		if ( $i++ % 3 == 0 && $i > 1 )
		{
			$multiple_html .= '</tr><tr class="st">';
		}

		if ( ! $associative_array )
		{
			// Not an associative array, use Text as value.
			$option_value = $option;
		}

		$multiple_html .= '<td><label>
			<input type="checkbox" name="' . AttrEscape( $name ) . '"
				value="' . AttrEscape( $option_value ) . '" ' . $extra . ' ' .
				( $option != '' && mb_strpos( (string) $value, '||' . $option_value . '||' ) !== false ? ' checked' : '' ) . '>&nbsp;' .
			( $option != '' ? $option : '-' ) .
		'</label></td>';
	}

	$multiple_html .= '</tr></table>' . FormatInputTitle( $title, '', $required, '' );

	$multiple_html .= '<input type="hidden" name="' . AttrEscape( $name ) . '" value="">';

	if ( trim( (string) $value, '|' ) == ''
		|| ! $div )
	{
		return $multiple_html;
	}

	return InputDivOnclick(
		$id,
		$multiple_html,
		$multiple_value,
		FormatInputTitle( $title )
	);
}


/**
 * Select Input
 *
 * @since 5.6 Support option groups (`<optgroup>`) by adding 'group' to $extra.
 * @since 6.0 Support multiple values.
 * @since 10.8.4 Fix save multiple SelectInput() when none selected, add hidden empty input (only if $allow_na)
 *
 * @example SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $options, 'N/A', $extra )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @param  string         $values   Input value(s).
 * @param  string         $name     Input name.
 * @param  string         $title    Input title (optional). Defaults to ''.
 * @param  array          $options  Input options: array( option_value => option_text ) or with groups: array( group_name => array( option_value => option_text ) ).
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A.
 * @param  string         $extra    Extra HTML attributes added to the input. Add 'group' to enable options grouping.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string         Input HTML
 */
function SelectInput( $values, $name, $title = '', $options = [], $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $values == '' && mb_strpos( (string) $extra, 'required' ) !== false;

	$is_multiple = is_array( $options ) && mb_strpos( (string) $extra, 'multiple' ) !== false;

	$values = $is_multiple ?
		(array) $values :
		// Mab - support array style $option values.
		( is_array( $values ) ? [ $values[0] ] : [ $values ] );

	$make_display_val = function( $values, $options )
	{
		$display_val = [];

		foreach ( (array) $values as $value )
		{
			if ( isset( $options[ $value ] ) )
			{
				$display_val[] = is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ];
			}
		}

		return implode( ', ', $display_val );
	};

	$is_group = is_array( $options ) && is_array( reset( $options ) ) && mb_strpos( $extra, 'group' ) !== false;

	if ( $is_group )
	{
		$display_val = [];

		foreach ( (array) $options as $group_options )
		{
			$display_value = $make_display_val( $values, $group_options );

			if ( $display_value )
			{
				$display_val[] = $display_value;
			}
		}

		$display_val = implode( ', ', $display_val );
	}
	else
	{
		$display_val = $make_display_val( $values, $options );
	}

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		if ( $display_val == '' )
		{
			$display_val = '-';

			if ( $allow_na !== false )
			{
				$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
			}
		}

		return $display_val . FormatInputTitle( $title );
	}

	$select = '<select name="' . AttrEscape( $name ) . '" id="' . $id . '" ' . $extra . '>';

	if ( $allow_na !== false )
	{
		$select .= '<option value="">' . ( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</option>';
	}

	$make_option = function( $values, $key, $val )
	{
		$selected = '';

		$key .= '';

		foreach ( (array) $values as $value )
		{
			if ( $value == $key
				&& ( !( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$selected = ' selected';

				break;
			}
		}

		return '<option value="' . AttrEscape( $key ) . '"' .
			$selected . '>' . ( is_array( $val ) ? $val[0] : $val ) . '</option>';
	};

	if ( $is_group )
	{
		foreach ( (array) $options as $group => $group_options )
		{
			$select .= '<optgroup label="' . AttrEscape( $group ) . '">';

			foreach ( (array) $group_options as $key => $val )
			{
				$select .= $make_option( $values, $key, $val );
			}

			$select .= '</optgroup>';
		}
	}
	else
	{
		// Mab - append current val to select list if not in list.
		if ( ! $is_multiple
			&& $values[0] != ''
			&& ( ! is_array( $options )
				|| ! array_key_exists( $values[0], $options ) ) )
		{
			$options[ $values[0] ] = [ $values[0], '<span style="color:red">' . $values[0] . '</span>' ];

			$display_val = '<span style="color:red">' . $values[0] . '</span>';
		}

		foreach ( (array) $options as $key => $val )
		{
			$select .= $make_option( $values, $key, $val );
		}
	}

	$select .= '</select>' . FormatInputTitle( $title, $id, $required );

	if ( $is_multiple
		&& $allow_na !== false )
	{
		// Fix save multiple SelectInput() when none selected, add hidden empty input (only if $allow_na)
		$select .= '<input type="hidden" name="' . AttrEscape( $name ) . '" value="">';
	}

	if ( ! isset( $values[0] )
		|| $values[0] == ''
		|| ! $div )
	{
		return $select;
	}

	return InputDivOnclick(
		$id,
		$select,
		$display_val,
		FormatInputTitle( $title )
	);
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
 * @param  string         $title    Input title.
 * @param  array          $options  Input options: array( option_value => option_text ).
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A.
 * @param  string         $extra    Extra HTML attributes added to the input.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string         Input HTML
 */
function MLSelectInput( $value, $name, $title, $options, $allow_na = 'N/A', $extra = '', $div = true )
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
		$options[ $value ] = [ $value, '<span style="color:red">' . $value . '</span>' ];
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$select = '<select name="' . AttrEscape( $name ) . '" id="' . $id . '" '.$extra.'>';

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

			$select .= '<option value="' . AttrEscape( $key ) . '"' .
				$selected . '>' . $val_locale . '</option>';
		}

		$select .= '</select>' . FormatInputTitle( $title, $id, $required );

		if ( $value == ''
			|| ! $div )
		{
			return $select;
		}

		return InputDivOnclick(
			$id,
			$select,
			ParseMLField(
				( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] ),
				$locale
			),
			FormatInputTitle( $title )
		);
	}

	$display_val = is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ];

	if ( $display_val == '' )
	{
		$display_val = '-';

		if ( $allow_na !== false )
		{
			$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
		}
	}
	else
	{
		$display_val = ParseMLField( $display_val, $locale );
	}

	return $display_val . FormatInputTitle( $title );
}


/**
 * SelectInput() wrapper which adds jQuery Chosen.
 *
 * Chosen is a library for making long, unwieldy select boxes more friendly.
 * @link https://github.com/harvesthq/chosen
 *
 * @since 2.9.5
 * @deprecated since 10.7 Use Select2Input() instead. Fixes the overflow issue.
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
function ChosenSelectInput( $value, $name, $title = '', $options = [], $allow_na = 'N/A', $extra = '', $div = true )
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
	$RTL_languages = [ 'ar', 'he', 'dv', 'fa', 'ur' ];

	$chosen_rtl = in_array( mb_substr( $_SESSION['locale'], 0, 2 ), $RTL_languages ) ? ' chosen-rtl' : '';

	if ( ! $extra
		|| mb_strpos( $extra, 'class=' ) === false )
	{
		$extra .= ' class="chosen-select' . $chosen_rtl . '"';
	}
	elseif ( mb_strpos( $extra, 'class=' ) !== false )
	{
		$extra = str_replace(
			[ 'class="', "class='" ],
			[ 'class="chosen-select' . $chosen_rtl . ' ', "class='chosen-select" . $chosen_rtl . ' ' ],
			$extra
		);
	}

	// Translate default "Select Some Options" multiple placeholder.
	if ( mb_strpos( $extra, 'multiple' ) !== false
		&& mb_strpos( $extra, 'data-placeholder=' ) === false )
	{
		$extra .= ' data-placeholder="' . AttrEscape( _( 'Select some Options' ) ) . '"';
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
 * SelectInput() wrapper which adds jQuery Select2.
 *
 * Select2 gives you a customizable select box with support for searching, tagging, remote data sets, infinite scrolling, and many other highly used options.
 * @link https://select2.org/
 *
 * @since 10.7
 *
 * @example Select2Input( $value, 'values[' . $id . '][' . $name . ']', '', $options, 'N/A', $extra )
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
function Select2Input( $value, $name, $title = '', $options = [], $allow_na = 'N/A', $extra = '', $div = true )
{
	static $select2_included = false;

	$js = '';

	if ( ! $select2_included
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		ob_start();	?>
		<!-- Select2 -->
		<script src="assets/js/jquery-select2/select2.min.js"></script>
		<link rel="stylesheet" href="assets/js/jquery-select2/select2.min.css">
		<script>
			$(document).ready(function(){
				$('.select2-select').select2({
					language: {
						noResults: function() { return ''; }
					}
				});
			});
		</script>
		<?php $select2_included = true;

		$js = ob_get_clean();
	}

	if ( ! $extra
		|| mb_strpos( $extra, 'class=' ) === false )
	{
		$extra .= ' class="select2-select"';
	}
	elseif ( mb_strpos( $extra, 'class=' ) !== false )
	{
		$extra = str_replace(
			[ 'class="', "class='" ],
			[ 'class="select2-select ', "class='select2-select " ],
			$extra
		);
	}

	// Translate default "Select Some Options" multiple placeholder.
	if ( mb_strpos( $extra, 'multiple' ) !== false
		&& mb_strpos( $extra, 'data-placeholder=' ) === false )
	{
		$extra .= ' data-placeholder="' . AttrEscape( _( 'Select some Options' ) ) . '"';
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

		// On InputDivOnClick(), call Select2 (once).
		$return .= '<script>var select2Div' . $id . '=false;
		$("#div' . $id . '").on("click", function() {
			if (select2Div' . $id . ') return;

			select2Div' . $id . '=true;
			$("#' . $id . '").select2({
				language: {
					noResults: function() { return ""; }
				}
			});
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
 * @param  string         $title    Input title.
 * @param  array          $options  Input options: array( option_value => option_text )
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A
 * @param  string         $extra    Extra HTML attributes added to the input (optional).
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string         Input HTML
 */
function RadioInput( $value, $name, $title, $options, $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	// mab - append current val to select list if not in list
	if ( $value != ''
		&& ( ! is_array( $options )
			|| ! array_key_exists( $value, $options ) ) )
	{
		$options[ $value ] = [ $value, '<span style="color:red">' . $value . '</span>' ];
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$table = '<table class="cellspacing-0 cellpadding-5"><tr class="st">';

		$i = 0;

		if ( $allow_na !== false )
		{
			$table .= '<td><label><input type="radio" name="' . AttrEscape( $name ) . '" value=""' .
				( $value == '' ? ' checked' : '' ) . ' ' . $extra . '> ' .
				( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</label></td>';

			$i++;
		}

		foreach ( (array) $options as $key => $val )
		{
			if ( $i++ % 3 == 0 && $i > 1 )
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

			$table .= '<td><label><input type="radio" name="' . AttrEscape( $name ) . '" value="' .
				AttrEscape( $key ) . '"' . $checked . ' ' . $extra . '> ' .
				( is_array( $val ) ? $val[0] : $val ) . '</label></td>';
		}

		$table .= '</tr></table>';

		$table .= FormatInputTitle( $title, '', $required, '' );

		if ( $value == ''
			|| ! $div )
		{
			return $table;
		}

		return InputDivOnclick(
			$id,
			$table,
			is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ],
			FormatInputTitle( $title )
		);
	}

	$display_val = ! isset( $options[ $value ] ) ? '' :
		( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] );

	if ( $display_val == '' )
	{
		$display_val = '-';

		if ( $allow_na !== false )
		{
			$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
		}
	}

	return $display_val . FormatInputTitle( $title );
}


/**
 * Color Picker Input
 *
 * @example ColorInput( $value, 'values[' . $id . '][' . $column . ']', _( 'Color' ) );
 *
 * @since 2.9
 * @since 5.4 Remove $type param.
 *
 * @param  string  $value Color value
 * @param  string  $name  Input name attribute
 * @param  string  $title Input title (label)
 * @param  string  $extra Extra HTML attributes added to the input (optional).
 * @param  boolean $div   Is input wrapped into <div onclick>? (optional). Defaults to true
 *
 * @return string  Color Picker Input HTML
 */
function ColorInput( $value, $name, $title = '', $extra = '', $div = true )
{
	if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' )
		|| strpos( $_SERVER['HTTP_USER_AGENT'], 'Trident/7' ) )
	{
		// Is Internet Explorer: not compatible with color input.
		return ColorInputMiniColors( $value, $name, $title, 'hidden', $extra, $div );
	}

	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$color_rect = '<div class="color-input-value" style="background-color:' . $value . ';"></div>';

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $color_rect . FormatInputTitle( $title, '', '', '' );
	}

	$input = '<input type="color" name="' . AttrEscape( $name ) . '" id="' . $id . '" value="' .
		AttrEscape( $value ) . '"' . $extra . '>';

	$input .= FormatInputTitle( $title, $id, $required );

	if ( $value == ''
		|| ! $div )
	{
		return $input;
	}

	return InputDivOnclick(
		$id,
		$input,
		$color_rect,
		FormatInputTitle( $title )
	);
}


/**
 * Color Picker Input for browsers not supporting HTML5 color input
 * @link http://caniuse.com/#search=input%20type
 *
 * @example ColorInputMiniColors( $value, 'values[' . $id . '][' . $column . ']', '', 'hidden', 'data-position="bottom right"' );
 *
 * @deprecated
 *
 * @since 5.4
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
function ColorInputMiniColors( $value, $name, $title = '', $type = 'hidden', $extra = '', $div = true )
{
	static $included = false;

	$id = GetInputID( $name );

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$color_rect = '<div class="color-input-value" style="background-color:' . $value . ';"></div>';

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
		<link rel="stylesheet" href="assets/js/jquery-minicolors/jquery.minicolors.css">
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
	<input type="<?php echo AttrEscape( $type ); ?>" name="<?php echo AttrEscape( $name ); ?>" id="<?php echo $id; ?>"
		class="minicolors" value="<?php echo AttrEscape( $value ); ?>" <?php echo $extra; ?>>
	<?php

	$color = ob_get_clean() . FormatInputTitle( $title, $id, $required );

	if ( $value == ''
		|| ! $div )
	{
		return $js . $color;
	}

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
		<span id="<?php echo $id_base; ?>-n1"></span> + <span id="<?php echo $id_base; ?>-n2"></span> = <input id="<?php echo $id_base; ?>-input" name="<?php echo AttrEscape( $name ); ?>[input]" type="number" required>
		<input id="<?php echo $id_base; ?>-answer" name="<?php echo AttrEscape( $name ); ?>[answer]" type="hidden" <?php echo $extra; ?>>
		<?php echo FormatInputTitle( $title, $id_base . '-input', $required ); ?>
	</div>
	<script>captcha(<?php echo json_encode( $id_base ); ?>);</script>
	<?php

	return ob_get_clean();
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
		[];

	// Compare input & answer.
	return $captcha && $captcha['input'] === $captcha['answer'];
}


/**
 * File Input
 * Warning: contrary to other *Input() functions, there is no AllowEdit() check
 * so the file input will also be visible to students, parents & teachers.
 *
 * @since 3.8.1
 * @since 5.2 Add $max_file_size param & max file size validation.
 * @since 7.8 Handle `multiple` files attribute. See FileUploadMultiple().
 *
 * @example FileInput( 'file', _( 'File' ), 'required' )
 * @example FileInput( 'files[]', _( 'Files' ), 'multiple' )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 *
 * @param  string  $name          Input name.
 * @param  string  $title         Input title (optional). Defaults to ''.
 * @param  string  $extra         Extra HTML attributes added to the input. Defaults to ''.
 * @param  int     $max_file_size Maximum File Size, in Mb. Optional. Defaults to FileUploadMaxSize().
 *
 * @return string  Input HTML
 */
function FileInput( $name, $title = '', $extra = '', $max_file_size = 0 )
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

	$max_file_size = $max_file_size > 0 && $max_file_size < FileUploadMaxSize() ? $max_file_size : FileUploadMaxSize();

	// Input title indicating Maximum file size.
	if ( mb_strpos( $extra, 'title=' ) === false )
	{
		$extra .= ' title="' . AttrEscape( sprintf( _( 'Maximum file size: %01.0fMb' ), $max_file_size ) ) . '"';
	}

	return '<input type="file" id="' . $id . '" name="' . AttrEscape( $name ) . '" ' . $extra .
		' onchange="fileInputSizeValidate(this,' . (float) $max_file_size . ');"><span class="loading"></span>' .
		$ftitle;
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
	$onclick_URL = PreparePHP_SELF(
		$_REQUEST,
		[],
		isset( $_REQUEST[ $name ] ) && $_REQUEST[ $name ] == 'Y' ? [ $name => '' ] : [ $name => 'Y' ]
	);

	$input = '<input type="checkbox" name="' . AttrEscape( $name ) . '" value="Y"' .
		( isset( $_REQUEST[ $name ] ) && $_REQUEST[ $name ] == 'Y' ? ' checked' : '' ) .
		' onclick="' . AttrEscape( 'ajaxLink(' . json_encode( $onclick_URL ) . ');' ) . '">';

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
 * @since 9.0 Use AttrEscape()
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

	$id = str_replace( [ '[', ']', '-', ' ' ], '', $name );

	return AttrEscape( $id );
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
 * @param  string  $break    Break before title (optional). Defaults to '<br>'
 *
 * @return string  Formatted Input Title
 */
function FormatInputTitle( $title, $id = '', $required = false, $break = '<br>' )
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

		$title = '<span class="' . AttrEscape( $class ) . '">' . $title . '</span>';
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
	// @since 9.0 JS Sanitize string for legal variable name.
	// @link https://stackoverflow.com/questions/12339942/sanitize-strings-for-legal-variable-names-in-php
	$pattern = '/^(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/';

	$id_var_name_sanitized = preg_replace( $pattern, '', $id );

	$script = '<script>var html' . $id_var_name_sanitized . '=' . json_encode( $input_html ).';</script>';

	$value = $value == '' ? '-' : $value;

	$onfocus_js = 'addHTML(html' . $id_var_name_sanitized . ',"div' . $id_var_name_sanitized . '",true);
		$("#' . $id_var_name_sanitized . '").focus();
		$("#div' . $id_var_name_sanitized . '").click();';

	$div_onclick = '<div id="div' . $id_var_name_sanitized . '">
		<div class="onclick" tabindex="0" onfocus="' . AttrEscape( $onfocus_js ) . '">' .
		( mb_strpos( $value, '<div' ) === 0 ?
			'<div class="underline-dots">' . $value . '</div>' :
			'<span class="underline-dots">' . $value . '</span>' ) .
		$input_ftitle . '</div></div>';

	return $script . $div_onclick;
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
 * @since 10.0 Fix remove parent link to sort column, see ListOutput.fnc.php
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
			return $checked ? '✔️' : '';
		}

		return '<input type="checkbox" value="Y" name="controller" id="controller"
			onclick="' . AttrEscape( 'checkAll(this.form,this.checked,' .
				json_encode( $controller_name ) .
				');' ) . '"' .
			( $checked ? ' checked' : '' ) . '>
			<label for="controller" class="a11y-hidden">' . _( 'Check All' ) . '</label>';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $checked ? '✔️' : '';
	}

	if ( ! empty( $controller_column ) )
	{
		$value = issetVal( $THIS_RET[ $controller_column ], '' );
	}

	return '<label><input type="checkbox" name="' . AttrEscape( $name ) . '[]" value="' . AttrEscape( $value ) . '"' .
		( $checked ? ' checked' : '' ) . '><span class="a11y-hidden">' .
		_( 'Select' ) . '</span></label>';
}

/**
 * Escape HTML attribute
 * Protects against XSS (Javascript execution)
 *
 * @see URLEscape() to escape href, action & src attributes
 * @see GetInputID() to escape id attribute
 *
 * @example $html = '<span title="' . AttrEscape( $value ) . '">' . _( 'Text' ) . '</span>';
 * @example <span title="<?php echo AttrEscape( $value ); ?\>"><?php echo _( 'Text' ); ?\></span>
 *
 * @uses htmlspecialchars
 *
 * @since 9.0
 *
 * @param string $value Text to be escaped.
 *
 * @return string Escaped string.
 */
function AttrEscape( $value )
{
	return htmlspecialchars( (string) $value, ENT_QUOTES, null, false );
}
