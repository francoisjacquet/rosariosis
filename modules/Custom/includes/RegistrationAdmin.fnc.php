<?php
/**
 * Registration Admin functions
 *
 * @since 6.6
 */

/**
 * Save Registration Form Config
 * Save under config table in 'REGISTRATION_FORM'. Serialized array.
 *
 * @param array $values Config values.
 *
 * @return array Config values.
 */
function RegistrationFormConfigSave( $values )
{
	// Multiple Checkbox Input.
	$formatMultipleCheckbox = function( $checked_array )
	{
		if ( ! is_array( $checked_array ) )
		{
			return '';
		}

		return implode( '||', $checked_array ) ?
			'||' . implode( '||', $checked_array ) : '';
	};

	$values['parent'][0]['info'] = $formatMultipleCheckbox( issetVal( $values['parent'][0]['info'] ) );

	$values['parent'][0]['fields'] = $formatMultipleCheckbox( issetVal( $values['parent'][0]['fields'] ) );

	if ( ! empty( $values['parent'][1] ) )
	{
		$values['parent'][1]['info'] = $formatMultipleCheckbox( issetVal( $values['parent'][1]['info'] ) );

		$values['parent'][1]['fields'] = $formatMultipleCheckbox( issetVal( $values['parent'][1]['fields'] ) );
	}

	$values['address']['fields'] = $formatMultipleCheckbox( issetVal( $values['address']['fields'] ) );

	foreach ( (array) $values['contact'] as $i => $contact )
	{
		$values['contact'][$i]['info'] = $formatMultipleCheckbox( issetVal( $values['contact'][$i]['info'] ) );

		$values['contact'][$i]['fields'] = $formatMultipleCheckbox( issetVal( $values['contact'][$i]['fields'] ) );
	}

	$values['student']['fields'] = $formatMultipleCheckbox( issetVal( $values['student']['fields'] ) );

	Config( 'REGISTRATION_FORM', serialize( $values ) );

	return $values;
}

/**
 * Registration Preview header
 *
 * @return string Preview or Back link.
 */
function RegistrationAdminPreviewHeader()
{
	if ( $_REQUEST['modfunc'] === 'preview' )
	{
		return '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '">« ' . _( 'Back' ) . '</a>';
	}

	return '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=preview' ) . '">' . _( 'Preview' ) . '</a>';
}

/**
 * Registration Admin Form output
 *
 * @param array $config Registration Form config.
 */
function RegistrationAdminFormOutput( $config )
{
	echo '<table class="valign-top"><tr class="st"><td>';

	RegistrationAdminContact( 'parent[0]', $config['parent'][0] );

	echo '</td><td>';

	RegistrationAdminContact( 'parent[1]', issetVal( $config['parent'][1], [] ) );

	echo '</td></tr></table><br />';

	RegistrationAdminAddress( $config['address'] );

	echo '<br />';

	$id = -1;

	echo '<table><tr class="st">';

	if ( is_array( $config['contact'] ) )
	{
		foreach ( $config['contact'] as $id => $config_contact )
		{
			echo '<td>';

			RegistrationAdminContact( 'contact[' . $id . ']', $config_contact );

			echo '</td>';

			if ( $id % 2 !== 0 )
			{
				echo '</tr><tr class="st">';
			}
		}
	}

	echo '<td>';

	RegistrationAdminContact( 'contact[' . ++$id . ']', [] );

	echo '</td></tr></table><br />';

	RegistrationAdminStudent( $config['student'] );
}

/**
 * Registration Contact Custody checkbox
 *
 * @param string $name    Input name prefix.
 * @param string $custody Input value.
 *
 * @return string Checkbox Input.
 */
function RegistrationAdminContactCustody( $name, $custody )
{
	return button( 'gavel', '', '', 'bigger' ) . ' ' . CheckboxInput(
		$custody,
		$name . '[custody]',
		_( 'Custody' ),
		'',
		true
	);
}

/**
 * Registration Contact Emergency checkbox
 *
 * @param string $name      Input name prefix.
 * @param string $emergency Input value.
 *
 * @return string Checkbox Input.
 */
function RegistrationAdminContactEmergency( $name, $emergency )
{
	return button( 'emergency', '', '', 'bigger' ) . ' ' . CheckboxInput(
		$emergency,
		$name . '[emergency]',
		_( 'Emergency' ),
		'',
		true
	);
}

/**
 * Registration Contact Information Required checkbox
 *
 * @param string $name          Input name prefix.
 * @param string $info_required Input value.
 *
 * @return string Checkbox Input.
 */
function RegistrationAdminContactInfoRequired( $name, $info_required )
{
	return CheckboxInput(
		$info_required,
		$name . '[info_required]',
		_( 'Required' ),
		'',
		true
	);
}

/**
 * Registration Admin Contact Form
 *
 * @param string $name    Input name prefix.
 * @param array  $contact Contact config.
 */
function RegistrationAdminContact( $name, $contact )
{
	$default_contact = [
		'relation' => ( strpos( $name, 'parent' ) !== false ? _( 'Parent' ) : _( 'Grandparent' ) ),
	];

	echo '<fieldset id="fieldset' . GetInputID( $name ) . '"><legend>';

	echo RegistrationAdminContactEnable( $name, ! empty( $contact ) );

	echo RegistrationAdminContactRelation( $name, issetVal( $contact['relation'], $default_contact['relation'] ) );

	echo '</legend>';

	echo '<table class="widefat">';

	if ( strpos( $name, 'parent' ) !== false )
	{
		echo '<tr><td>' . RegistrationAdminContactCustody( $name, issetVal( $contact['custody'] ) ) . '</td></tr>';

		echo '<tr><td>' . RegistrationAdminContactEmergency( $name, issetVal( $contact['emergency'] ) ) . '</td></tr>';
	}

	if ( $name !== 'parent[0]' )
	{
		echo '<tr><td>' . RegistrationAdminContactAddress( $name, issetVal( $contact['address'] ) ) . '</td></tr>';
	}

	echo '<tr><td>' . RegistrationAdminContactInfo( $name, issetVal( $contact['info'] ) );

	if ( $name === 'parent[0]' )
	{
		echo ' &nbsp; ' . RegistrationAdminContactInfoRequired( $name, issetVal( $contact['info_required'] ) );
	}

	echo '</td></tr>';

	echo '<tr><td>' . RegistrationAdminContactFields(
		$name,
		issetVal( $contact['fields'] ),
		issetVal( $contact['custody'] ),
		issetVal( $contact['emergency'] )
	) . '</td></tr>';

	echo '</table></fieldset>';
}

/**
 * Registration Contact Enable checkbox
 * Includes JS to disable all Contact input onclick.
 *
 * @param string $name    Input name prefix.
 * @param string $value Input value.
 *
 * @return string Checkbox Input.
 */
function RegistrationAdminContactEnable( $name, $value )
{
	static $js_once;

	if ( $name === 'parent[0]' )
	{
		return '';
	}

	$js = '';

	if ( ! $js_once )
	{
		$js_once = true;

		ob_start();
		?>
		<script>
			var RegistrationAdminContactEnable = function(id, checked) {
				$('#field' + id + ' input, #field' + id + ' select').prop( 'disabled', ! checked );
				$('#field' + id + ' legend input[type="checkbox"]').prop( 'disabled', false );
			};
		</script>
		<?php
		$js .= ob_get_clean();
	}

	if ( ! $value )
	{
		ob_start();
		?>
		<script>
			$(document).ready(function(){
				RegistrationAdminContactEnable(
					<?php echo json_encode( GetInputID( 'set' . $name ) ); ?>,
					false
				);
			});
		</script>
		<?php
		$js .= ob_get_clean();
	}

	return $js . CheckboxInput(
		$value ? 'Y' : '',
		'set' . $name,
		'',
		'',
		false,
		'Yes',
		'No',
		false,
		'autocomplete="off" title="' . AttrEscape( _( 'Activate' ) ) . '" onclick="RegistrationAdminContactEnable(this.id,this.checked);"'
	) . ' ';
}

/**
 * Registration Contact Relation Text input
 *
 * @param string $name     Input name prefix.
 * @param string $relation Input value.
 *
 * @return string Text Input.
 */
function RegistrationAdminContactRelation( $name, $relation )
{
	return TextInput(
		$relation,
		$name . '[relation]',
		'<span class="a11y-hidden">' . _( 'Relation' ) . '</span>',
		'title="' . AttrEscape( _( 'Relation' ) ) . '"',
		false
	);
}

/**
 * Registration Contact Address Select input
 *
 * @param string $name    Input name prefix.
 * @param string $address Input value.
 *
 * @return string Select Input.
 */
function RegistrationAdminContactAddress( $name, $address )
{
	$address_options = [
		'1' => _( 'Same as Student' ),
		'2' => _( 'New Address' ),
	];

	return SelectInput(
		$address,
		$name . '[address]',
		_( 'Address' ),
		$address_options,
		_( 'No Address' ),
		'',
		false
	);
}

/**
 * Registration Contact Information Multiple Checkbox input
 *
 * @param string $name Input name prefix.
 * @param string $info Input value.
 *
 * @return string Multiple Checkbox Input.
 */
function RegistrationAdminContactInfo( $name, $info )
{
	$options_RET = DBGet( "SELECT DISTINCT TITLE,upper(TITLE) AS SORT_KEY
		FROM people_join_contacts
		ORDER BY SORT_KEY" );

	$info_options = [];

	foreach ( (array) $options_RET as $option )
	{
		$info_options[ $option['TITLE'] ] = $option['TITLE'];
	}

	if ( ! $info_options && AllowEdit( 'Students/Student.php&category_id=3' ) )
	{
		return NoInput(
			'<a href="Modules.php?modname=Students/Student.php&category_id=3">' .
				button( 'add' ) . ' ' . _( 'Add' ) . '</a>',
			_( 'Contact Information' )
		);
	}

	return MultipleCheckboxInput(
		$info,
		$name . '[info][]',
		_( 'Contact Information' ),
		$info_options,
		'',
		false
	);
}

/**
 * Registration Contact Fields Multiple Checkbox input
 * Show Categories depending on Custody / Emergency settings.
 *
 * @param string $name      Input name prefix.
 * @param string $fields    Input value.
 * @param string $custody   Custody contact?
 * @param string $emergency Emergency Contact?
 *
 * @return string Multiple Checkbox Input.
 */
function RegistrationAdminContactFields( $name, $fields, $custody, $emergency )
{
	$where_sql = '';

	if ( ! $custody )
	{
		$where_sql .= " AND CUSTODY IS NULL";
	}

	if ( ! $emergency )
	{
		$where_sql .= " AND EMERGENCY IS NULL";
	}

	// Categories according to Emergency / Custody settings.
	$fields_options_RET = DBGet( "SELECT ID,TITLE
		FROM people_field_categories
		WHERE TRUE" . $where_sql .
		" ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$fields_options = [];

	foreach ( $fields_options_RET as $fields_option )
	{
		$fields_options[ $fields_option['ID'] ] = ParseMLField( $fields_option['TITLE'] );
	}

	if ( ! $fields_options && AllowEdit( 'Students/StudentFields.php' ) )
	{
		return NoInput(
			'<a href="Modules.php?modname=Students/StudentFields.php&category=contact">' .
				button( 'add' ) . ' ' . _( 'Add' ) . '</a>',
			_( 'Contact Fields' )
		);
	}

	return MultipleCheckboxInput(
		$fields,
		$name . '[fields][]',
		_( 'Contact Fields' ),
		$fields_options,
		'',
		false
	);
}

/**
 * Registration Admin Address section output.
 *
 * @uses RegistrationAdminAddressFields()
 *
 * @param array $address Address config.
 */
function RegistrationAdminAddress( $address )
{
	echo '<fieldset><legend>' . _( 'Address' ) . '</legend>';

	echo '<table class="widefat"><tr><td>';

	echo RegistrationAdminAddressFields( 'address', issetVal( $address['fields'] ) );

	echo '</td></tr></table>';

	echo '</fieldset>';
}

/**
 * Registration Address Fields Multiple Checkbox input
 * Show Categories where Residence or Mailing is checked.
 *
 * @param string $name   Input name prefix.
 * @param string $fields Input value.
 *
 * @return string Multiple Checkbox Input.
 */
function RegistrationAdminAddressFields( $name, $fields )
{
	// Categories according to Residence / Mailing settings.
	$fields_options_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM address_field_categories
		WHERE RESIDENCE='Y' OR MAILING='Y' OR (MAILING IS NULL AND RESIDENCE IS NULL AND BUS IS NULL)
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$fields_options = [];

	foreach ( $fields_options_RET as $fields_option )
	{
		$fields_options[ $fields_option['ID'] ] = ParseMLField( $fields_option['TITLE'] );
	}

	if ( ! $fields_options && AllowEdit( 'Students/StudentFields.php' ) )
	{
		return NoInput(
			'<a href="Modules.php?modname=Students/StudentFields.php&category=address">' .
				button( 'add' ) . ' ' . _( 'Add' ) . '</a>',
			_( 'Address Fields' )
		);
	}

	return MultipleCheckboxInput(
		$fields,
		$name . '[fields][]',
		_( 'Address Fields' ),
		$fields_options,
		'',
		false
	);
}

/**
 * Registration Admin Student section output.
 *
 * @uses RegistrationAdminStudentFields()
 *
 * @param array $student Student config.
 */
function RegistrationAdminStudent( $student )
{
	echo '<fieldset><legend>' . _( 'Student' ) . '</legend>';

	echo '<table class="widefat"><tr><td>';

	echo RegistrationAdminStudentFields( 'student', issetVal( $student['fields'] ) );

	echo '</td></tr></table>';

	echo '</fieldset>';
}

/**
 * Registration Student Fields Multiple Checkbox input
 * Show Categories having custom Fields.
 *
 * @param string $name   Input name prefix.
 * @param string $fields Input value.
 *
 * @return string Multiple Checkbox Input.
 */
function RegistrationAdminStudentFields( $name, $fields )
{
	// Categories having Fields.
	$fields_options_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM student_field_categories sfc
		WHERE EXISTS(SELECT 1 FROM custom_fields
			WHERE CATEGORY_ID=sfc.ID)
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$fields_options = [];

	foreach ( $fields_options_RET as $fields_option )
	{
		$fields_options[ $fields_option['ID'] ] = ParseMLField( $fields_option['TITLE'] );
	}

	return MultipleCheckboxInput(
		$fields,
		$name . '[fields][]',
		_( 'Student Fields' ),
		$fields_options,
		'',
		false
	);
}
