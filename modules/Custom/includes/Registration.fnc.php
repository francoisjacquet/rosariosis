<?php
/**
 * Registration form functions
 *
 * @since 6.6
 */

/**
 * Registration Form Config
 * Unserialize values from Config( 'REGISTRATION_FORM' ).
 * Insert default values on first call.
 *
 * @return array Config values.
 */
function RegistrationFormConfig()
{
	$values = Config( 'REGISTRATION_FORM' );

	if ( $values )
	{
		return unserialize( $values );
	}

	// Default values.
	$default_values = [
		'parent' => [
			0 => [
				'relation' => _( 'Parent' ),
				'custody' => 'Y',
				'emergency' => 'Y',
				'address' => '1', // Same as Student.
				'info' => '',
				'info_required' => '',
				'fields' => '', // No Contact Fields categories.
			],
			1 => [
				'relation' => _( 'Parent' ),
				'custody' => 'Y',
				'emergency' => '',
				'address' => '', // No Address.
				'info' => '',
				'info_required' => '',
				'fields' => '', // No Contact Fields categories.
			],
		],
		'address' => [
			'fields' => '', // No Address Fields categories.
		],
		'contact' => [], // No Other Contacts / Grandparents.
		'student' => [
			'fields' => '||1||2||', // General Info, Medical.
		],
	];

	// Save default.
	DBInsert(
		'config',
		[
			'CONFIG_VALUE' => serialize( $default_values ),
			'TITLE' => 'REGISTRATION_FORM',
			'SCHOOL_ID' => '0',
		]
	);

	return $default_values;
}

/**
 * Registration Introduction header.
 */
function RegistrationIntroHeader()
{
	return sprintf(
		_( 'We would appreciate it if you would enter just a little bit of information about you and %s to help us out this school year. Thanks!' ),
		User( 'STAFF_ID' ) ? _( 'your child' ) : _( 'your parents' )
	);
}

/**
 * Has already registered Sibling?
 * Find Sibling based on Student Email Field.
 *
 * @return int Student ID or 0.
 */
function RegistrationSiblingRegistered()
{
	if ( User( 'STAFF_ID' ) )
	{
		// Find already registered (has address) student related to same parent.
		return (int) DBGetOne( "SELECT sju.STUDENT_ID
			FROM students_join_users sju,students_join_address sja
			WHERE sju.STAFF_ID='" . UserStaffID() . "'
			AND sju.STUDENT_ID<>'" . UserStudentID() . "'
			AND sja.STUDENT_ID=sju.STUDENT_ID" );
	}

	if ( ! Config( 'STUDENTS_EMAIL_FIELD' ) )
	{
		return 0;
	}

	$email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
		'USERNAME' : 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );

	// Find already registered (has address) student having same email address.
	$student_id = (int) DBGetOne( "SELECT s.STUDENT_ID
		FROM students s,students_join_address sja
		WHERE s.STUDENT_ID<>'" . UserStudentID() . "'
		AND sja.STUDENT_ID=s.STUDENT_ID
		AND s." . DBEscapeIdentifier( $email_field ) . " IS NOT NULL
		AND LOWER(s." . DBEscapeIdentifier( $email_field ) . ")=(SELECT LOWER(" . DBEscapeIdentifier( $email_field ) . ")
			FROM students
			WHERE STUDENT_ID='" . UserStudentID() . "')" );

	return $student_id;
}

/**
 * Registration Use Sibling Contacts and Address
 * "Use same address and contact information as for %s" checkbox
 *
 * @param int $student_id Sibling Student ID.
 *
 * @return HTML + JS "Use same address and contact information as for %s" checkbox.
 */
function RegistrationSiblingUseContactsAddress( $student_id )
{
	ob_start();
	?>
	<script>
		var RegistrationContactsAddressDisable = function(checked) {
			$('#registration_contacts_address_wrapper input, #registration_contacts_address_wrapper select, #registration_contacts_address_wrapper textarea').prop( 'disabled', checked );
			$('#registration_contacts_address_wrapper' ).toggle();
		};
		$(document).ready(function(){
			RegistrationContactsAddressDisable( true );
		});
	</script>
	<?php
	$js = ob_get_clean();

	$sibling_name = DBGetOne( "SELECT " . DisplayNameSQL() . "
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	$sibling_id_input = '<input type="hidden" name="sibling_id" value="' . AttrEscape( $student_id ) . '" />';

	return $js . $sibling_id_input . CheckboxInput(
		'Y',
		'sibling_use_contacts_address',
		sprintf(
			_( 'Use same address and contact information as for %s' ),
			$sibling_name
		),
		'',
		true,
		'Yes',
		'No',
		false,
		'autocomplete="off" onclick="RegistrationContactsAddressDisable(this.checked);"'
	);
}

/**
 * Registration Form output
 *
 * @param array $config Form config.
 */
function RegistrationFormOutput( $config )
{
	$sibling_id = RegistrationSiblingRegistered();

	if ( $sibling_id )
	{
		echo RegistrationSiblingUseContactsAddress( $sibling_id );
	}

	echo '<div id="registration_contacts_address_wrapper">';

	echo '<table class="valign-top"><tr class="st"><td>';

	RegistrationContact( 'parent[0]', $config['parent'][0] );

	if ( ! empty( $config['parent'][1] ) )
	{
		echo '</td><td>';

		RegistrationContact( 'parent[1]', $config['parent'][1] );
	}

	echo '</td></tr></table>';

	RegistrationYourAddress( $config['address'] );

	$id = -1;

	echo '<table><tr class="st">';

	if ( is_array( $config['contact'] ) )
	{
		foreach ( $config['contact'] as $id => $config_contact )
		{
			echo '<td>';

			RegistrationContact( 'contact[' . $id . ']', $config_contact );

			echo '</td>';

			if ( $id % 2 !== 0 )
			{
				echo '</tr><tr class="st">';
			}
		}
	}

	echo '</tr></table>';

	echo '</div>';

	RegistrationStudent( $config['student'] );
}

/**
 * Registration Contact form output.
 *
 * @uses RegistrationContactName()
 * @uses RegistrationAddress()
 * @uses RegistrationContactInfo()
 * @uses RegistrationContactFields()
 *
 * @param string $name    Input name prefix.
 * @param array  $contact Contact config.
 */
function RegistrationContact( $name, $contact )
{
	echo '<br /><fieldset id="fieldset' . GetInputID( $name ) . '"><legend>' . $contact['relation'] . '</legend>';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	RegistrationContactName( $name );

	echo '</td></tr>';

	if ( ! empty( $contact['address'] )
		&& $contact['address'] == '2' ) // New Address.
	{
		echo '<tr class="st"><td>';

		RegistrationAddress( $name . '[address]' );

		echo '</td></tr>';
	}

	if ( ! empty( $contact['info'] ) )
	{
		echo '<tr><td>';

		RegistrationContactInfo( $name, $contact['info'], issetVal( $contact['info_required'] ) );

		echo '</td></tr>';
	}

	if ( ! empty( trim( $contact['fields'], '||' ) ) )
	{
		echo '<tr><td>';

		RegistrationContactFields( $name, $contact['fields'] );

		echo '</td></tr>';
	}

	echo '</table></fieldset>';
}

/**
 * Registration Contact Name fields output.
 * First Name, Middle Name, Last Name.
 * Required only if first Parent.
 *
 * @param string $name Input name prefix.
 */
function RegistrationContactName( $name )
{
	$required = $name === 'parent[0]' ? ' required' : '';

	echo '<table><tr class="st"><td>' .
		TextInput( '', $name . '[FIRST_NAME]', _( 'First Name' ), 'maxlength="50"' . $required ) . '</td>';

	echo '<td>' . TextInput( '', $name . '[MIDDLE_NAME]', _( 'Middle Name' ), 'maxlength="50"' ) . '</td>';

	echo '<td>' . TextInput( '', $name . '[LAST_NAME]', _( 'Last Name' ), 'maxlength="50"' . $required ) .
		'</td></tr></table>';
}

/**
 * Registration Address fields output.
 * Street, City, State, Zip, Phone.
 * Required only if your/main Address.
 *
 * @param string $name Input name prefix.
 */
function RegistrationAddress( $name )
{
	$required = $name === 'address' ? ' required' : '';

	echo TextInput( '', $name . '[ADDRESS]', _( 'Street' ), 'size="30" maxlength="255"' . $required );

	echo '<br /><table><tr class="st"><td>' .
		TextInput( '', $name . '[CITY]', _( 'City' ), 'maxlength="60"' ) . '</td>';

	echo '<td>' . TextInput( '', $name . '[STATE]', _( 'State' ), 'size="3" maxlength="50"' ) . '</td>';

	echo '<td>' . TextInput( '', $name . '[ZIPCODE]', _( 'Zip Code' ), 'size="6" maxlength="10"' ) .
		'</td></tr></table>';

	echo TextInput( '', $name . '[PHONE]', _( 'Phone' ), 'maxlength="30"' );
}

/**
 * Registration Contact Information fields output.
 *
 * @param string $name          Input name prefix.
 * @param array  $info          Info config.
 * @param string $info_required Fields are required?
 */
function RegistrationContactInfo( $name, $info, $info_required )
{
	$fields = explode( '||', trim( $info, '||' ) );

	$required = $info_required ? ' required' : '';

	foreach ( (array) $fields as $field )
	{
		echo TextInput(
			'',
			$name . '[info][' . $field . ']',
			$field,
			'maxlength="100"' . $required
		) . '<br />';
	}
}

/**
 * Registration Contact Fields output.
 * Based on configured categories.
 *
 * @uses modules/Students/includes/Other_Fields.inc.php
 *
 * @global $request
 * @global $field
 *
 * @param string $name       Input name prefix.
 * @param array  $categories Contact Fields Categories config.
 */
function RegistrationContactFields( $name, $categories )
{
	global $request,
		$field;

	$category_ids = "'0'";

	if ( trim( $categories, '||' ) )
	{
		$category_ids = "'" . str_replace( '||', "','", trim( $categories, '||' ) ) . "'";
	}

	$request = $name . '[fields]';

	// Contact Fields can be required only for Parent 0.
	$not_required = $name !== 'parent[0]';

	$categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
		c.CUSTODY,c.EMERGENCY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION," .
		( $not_required ? "NULL AS REQUIRED" : "f.REQUIRED" ) . "
		FROM people_field_categories c,people_fields f
		WHERE f.CATEGORY_ID=c.ID
		AND f.CATEGORY_ID IN(" . $category_ids . ")
		ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.TITLE,f.SORT_ORDER IS NULL,f.SORT_ORDER,f.TITLE", [], [ 'CATEGORY_ID' ] );

	foreach ( (array) $categories_RET as $fields_RET )
	{
		echo '<br /><fieldset><legend>' . ParseMLField( $fields_RET[1]['CATEGORY_TITLE'] ) . '</legend>';

		require 'modules/Students/includes/Other_Fields.inc.php';

		echo '</fieldset>';
	}
}

/**
 * Registration Your Address form output.
 *
 * @uses RegistrationAddress()
 * @uses RegistrationAddressFields()
 *
 * @param array $address Address config.
 */
function RegistrationYourAddress( $address )
{
	echo '<br /><fieldset><legend>' . _( 'Your Address' ) . '</legend>';

	echo '<table class="width-100p valign-top fixed-col"><tr><td>';

	RegistrationAddress( 'address' );

	if ( ! empty( trim( $address['fields'], '||' ) ) )
	{
		echo '</td></tr><tr><td>';

		RegistrationAddressFields( 'address', $address['fields'] );
	}

	echo '</td></tr></table></fieldset>';
}

/**
 * Registration Address Fields output.
 * Based on configured categories.
 *
 * @uses modules/Students/includes/Other_Fields.inc.php
 *
 * @global $request
 * @global $field
 *
 * @param string $name       Input name prefix.
 * @param array  $categories Address Fields Categories config.
 */
function RegistrationAddressFields( $name, $categories )
{
	global $request,
		$field;

	$category_ids = "'0'";

	if ( trim( $categories, '||' ) )
	{
		$category_ids = "'" . str_replace( '||', "','", trim( $categories, '||' ) ) . "'";
	}

	$request = $name . '[fields]';

	$categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
		c.RESIDENCE,c.MAILING,c.BUS,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED
		FROM address_field_categories c,address_fields f
		WHERE f.CATEGORY_ID=c.ID
		AND f.CATEGORY_ID IN(" . $category_ids . ")
		ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.TITLE,f.SORT_ORDER IS NULL,f.SORT_ORDER,f.TITLE", [], [ 'CATEGORY_ID' ] );

	foreach ( (array) $categories_RET as $fields_RET )
	{
		echo '<br /><fieldset><legend>' . ParseMLField( $fields_RET[1]['CATEGORY_TITLE'] ) . '</legend>';

		require 'modules/Students/includes/Other_Fields.inc.php';

		echo '</fieldset>';
	}
}

/**
 * Registration Student form output.
 *
 * @uses RegistrationStudentFields()
 *
 * @param array $student Student config.
 */
function RegistrationStudent( $student )
{
	if ( ! empty( $student['fields'] ) )
	{
		$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM students
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		echo '<br /><fieldset><legend>' . sprintf( _( 'Information about %s' ), $student_name ) . '</legend>';

		RegistrationStudentFields( 'students', $student['fields'] );

		echo '</fieldset>';
	}
}

/**
 * Registration Student Fields output.
 * Based on configured categories.
 *
 * @uses modules/Students/includes/Other_Fields.inc.php
 *
 * @global $value
 * @global $field
 *
 * @param string $name       Input name prefix.
 * @param array  $categories Student Fields Categories config.
 */
function RegistrationStudentFields( $name, $categories )
{
	global $field,
		$value;

	$category_ids = explode( '||', trim( $categories, '||' ) );

	$separator = '';

	foreach ( (array) $category_ids as $category_id )
	{
		$category_title = DBGetOne( "SELECT TITLE
			FROM student_field_categories
			WHERE ID='" . (int) $category_id . "'" );

		echo '<br /><fieldset class="cellpadding-5"><legend>' . ParseMLField( $category_title ) . '</legend>';

		$_REQUEST['category_id'] = $category_id;

		require 'modules/Students/includes/Other_Info.inc.php';

		echo '</fieldset>';
	}
}
