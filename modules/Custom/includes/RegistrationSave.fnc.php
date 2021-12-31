<?php
/**
 * Registration Save functions
 *
 * @since 6.6
 */

/**
 * Save Registration
 *
 * @param array $config Registration Form Config.
 * @param array $values Requested values: address, parent, contact & student.
 *
 * @return bool
 */
function RegistrationSave( $config, $values )
{
	if ( ! $config
		|| ! $values )
	{
		return false;
	}

	$address_id = RegistrationSaveAddress( $config['address'], $values['address'] );

	foreach ( (array) $config['parent'] as $id => $config_parent )
	{
		if ( empty( $values['parent'][ $id ] ) )
		{
			continue;
		}

		$values['parent'][ $id ]['fields'] = FilterCustomFieldsMarkdown( 'PEOPLE_FIELDS', 'parent', $id, 'fields' );

		$contact_id = RegistrationSaveContact( $config_parent, issetVal( $values['parent'][ $id ] ) );

		$contact_address_id = 0;

		if ( empty( $config_parent['address'] )
			&& $id == 0 )
		{
			// Parent 1, same Address as Student.
			$config_parent['address'] = '1';
		}
		elseif ( $config_parent['address'] == '2' )
		{
			// New Address.
			$contact_address_id = RegistrationSaveAddress( [], issetVal( $values['parent'][ $id ]['address'] ) );
		}

		RegistrationSaveJoinContact(
			$contact_id,
			// New Address or Same as Student or No Address.
			( ! empty( $contact_address_id ) ? $contact_address_id : ( $config_parent['address'] == '1' ? $address_id : 0 ) ),
			$config_parent
		);
	}

	foreach ( (array) $config['contact'] as $id => $config_contact )
	{
		if ( empty( $values['parent'][ $id ] ) )
		{
			continue;
		}

		$values['contact'][ $id ]['fields'] = FilterCustomFieldsMarkdown( 'PEOPLE_FIELDS', 'contact', $id, 'fields' );

		$contact_id = RegistrationSaveContact( [], issetVal( $values['contact'][ $id ] ) );

		$contact_address_id = 0;

		if ( $config_contact['address'] == '2' )
		{
			// New Address.
			$contact_address_id = RegistrationSaveAddress( $contact_id, issetVal( $values['contact'][ $id ]['address'] ) );
		}

		RegistrationSaveJoinContact(
			$contact_id,
			// New Address or Same as Student or No Address.
			( ! empty( $contact_address_id ) ? $contact_address_id : ( $config_contact['address'] == '1'  ? $address_id : 0 ) ),
			$config_contact
		);
	}

	RegistrationSaveStudent( $config['student'], $values['student'] );

	return true;
}

/**
 * Save Registration using Sibling Address & contact Information
 *
 * @param array $config     Registration Form Config.
 * @param array $values     Requested values: address, parent, contact & student.
 * @param int   $student_id Sibling ID.
 *
 * @return bool
 */
function RegistrationSaveSibling( $config, $values, $student_id )
{
	if ( ! $config
		|| ! $values
		|| ! $student_id )
	{
		return false;
	}

	$address_id = DBGetOne( "SELECT ADDRESS_ID
		FROM STUDENTS_JOIN_ADDRESS
		WHERE STUDENT_ID='" . $student_id . "'
		AND MAILING='Y'
		AND RESIDENCE='Y'" );

	if ( $address_id )
	{
		RegistrationSaveJoinAddress( $address_id );
	}

	RegistrationSaveSiblingContacts( $student_id );

	RegistrationSaveStudent( $config['student'], $values['student'] );

	return true;
}

/**
 * Save Registration Join Sibling Contacts to Student
 *
 * @param int $student_id Sibling ID.
 */
function RegistrationSaveSiblingContacts( $student_id )
{
	$contacts_RET = DBGet( "SELECT PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION
		FROM STUDENTS_JOIN_PEOPLE
		WHERE STUDENT_ID='" . $student_id . "'" );

	foreach ( (array) $contacts_RET as $contact )
	{
		$sql_values = db_seq_nextval( 'students_join_people_id_seq' ) . ",'" . UserStudentID() . "','" .
			$contact['PERSON_ID'] . "','" . $contact['ADDRESS_ID'] . "','" . $contact['CUSTODY'] . "','" .
			$contact['EMERGENCY'] . "','" . $contact['STUDENT_RELATION'] . "'";

		DBQuery( "INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION)
			VALUES(" . $sql_values . ")" );
	}
}

/**
 * Save Registration Student Info / Custom Fields.
 * Limit custom fields to the Categories in config.
 *
 * @param array $config Student Info config.
 * @param array $values Student Info values.
 *
 * @return bool
 */
function RegistrationSaveStudent( $config, $values )
{
	if ( ! $config['fields']
		|| ! $values )
	{
		return false;
	}

	$category_ids = "'" . str_replace( '||', "','", mb_substr( $config['fields'], 2, -2 ) ) . "'";

	$custom_fields_RET = DBGet( "SELECT ID
		FROM CUSTOM_FIELDS
		WHERE CATEGORY_ID IN(" . $category_ids . ")", [], [ 'ID' ] );

	$allowed_columns = array_keys( $custom_fields_RET );

	// Textarea fields MarkDown sanitize.
	$values = FilterCustomFieldsMarkdown( 'CUSTOM_FIELDS', 'students' );

	$sql = "UPDATE STUDENTS SET ";

	$go = false;

	foreach ( (array) $values as $column => $value )
	{
		if ( ! in_array( str_replace( 'CUSTOM_', '', $column ), $allowed_columns ) )
		{
			// Limit custom fields to the Categories in config.
			continue;
		}

		if ( is_array( $value ) )
		{
			// Select Multiple from Options field type format.
			$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
		}

		$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";

		$go = true;
	}

	$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

	if ( $go )
	{
		DBQuery( $sql );
	}

	return true;
}

/**
 * Save Registration Address (your/main or contact) & Address Fields if any.
 * And join address to Student.
 *
 * @uses RegistrationSaveJoinAddress()
 *
 * @param array $config Address config or empty.
 * @param array $values Student Info values.
 *
 * @return int Address ID.
 */
function RegistrationSaveAddress( $config, $values )
{
	static $inserted_addresses = [];

	if ( empty( $values['ADDRESS'] ) )
	{
		return 0;
	}

	$address_key = preg_replace( '/[^0-9A-Za-z]+/', '', mb_strtolower( $values['ADDRESS'] ) );

	if ( isset( $inserted_addresses[ $address_key ] ) )
	{
		return $inserted_addresses[ $address_key ];
	}

	$address_id = DBSeqNextID( 'address_address_id_seq' );

	$sql = "INSERT INTO ADDRESS ";

	$fields = 'ADDRESS_ID,ADDRESS,CITY,STATE,ZIPCODE,';

	$values_sql = "'" . $address_id . "','" . $values['ADDRESS'] . "','" . $values['CITY'] . "','" . $values['STATE'] . "','" . $values['ZIPCODE'] . "',";

	// Textarea fields MarkDown sanitize.
	$values = FilterCustomFieldsMarkdown( 'ADDRESS_FIELDS', 'address' );

	if ( $config
		&& ! empty( $values['fields'] ) )
	{
		foreach ( (array) $values['fields'] as $column => $value )
		{
			if ( is_array( $value ) )
			{
				// Select Multiple from Options field type format.
				$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
			}

			if ( ! empty( $value )
				|| $value == '0' )
			{
				$fields .= DBEscapeIdentifier( $column ) . ',';

				$values_sql .= "'" . $value . "',";
			}
		}
	}

	$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values_sql, 0, -1 ) . ')';

	DBQuery( $sql );

	RegistrationSaveJoinAddress( $address_id );

	$inserted_addresses[ $address_key ] = $address_id;

	return $address_id;
}

/**
 * Save Registration Join Address to Student
 * First Address is your/main address: Mailing & Residence checked.
 *
 * @param int $address_id Address ID.
 */
function RegistrationSaveJoinAddress( $address_id )
{
	static $inserted_address;

	// Contact Address.
	$students_join_address = [
		'MAILING' => '',
		'RESIDENCE' => '',
		'BUS_PICKUP' => '',
		'BUS_DROPOFF' => '',
	];

	if ( empty( $inserted_address ) )
	{
		// Your Address.
		$students_join_address = [
			'MAILING' => 'Y',
			'RESIDENCE' => 'Y',
			'BUS_PICKUP' => ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
			'BUS_DROPOFF' => ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
		];

		$inserted_address = true;
	}

	DBQuery( "INSERT INTO STUDENTS_JOIN_ADDRESS (ID,STUDENT_ID,ADDRESS_ID,
		RESIDENCE,MAILING,BUS_PICKUP,BUS_DROPOFF)
		values(" . db_seq_nextval( 'students_join_address_id_seq' ) . ",'" .
			UserStudentID() . "','" . $address_id . "','" .
			$students_join_address['MAILING'] . "','" .
			$students_join_address['RESIDENCE'] . "','" .
			$students_join_address['BUS_PICKUP'] . "','" .
			$students_join_address['BUS_DROPOFF'] . "')" );
}

/**
 * Save Registration Contact (parent, grandparent, etc.)
 *
 * @uses RegistrationSaveContactNameFields()
 * @uses RegistrationSaveContactInfo()
 *
 * @param array $config Parent or contact config.
 * @param array $values Contact values.
 *
 * @return int Contact ID.
 */
function RegistrationSaveContact( $config, $values )
{
	$contact_id = RegistrationSaveContactNameFields( $config, $values );

	if ( ! $contact_id )
	{
		return 0;
	}

	RegistrationSaveContactInfo( $contact_id, $config, $values );

	return $contact_id;
}

/**
 * Join Registration Contact to Student (Relation) and Address
 *
 * @param int   $contact_id Contact ID.
 * @param int   $address_id Address ID.
 * @param array $config     Contact config.
 *
 * @return bool.
 */
function RegistrationSaveJoinContact( $contact_id, $address_id, $config )
{
	if ( ! $contact_id )
	{
		return false;
	}

	$sql_values = db_seq_nextval( 'students_join_people_id_seq' ) . ",'" . UserStudentID() . "','" .
		$contact_id . "','" . $address_id . "','" . issetVal( $config['custody'] ) . "','" .
		issetVal( $config['emergency'] ) . "','" . $config['relation'] . "'";

	DBQuery( "INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION)
		VALUES(" . $sql_values . ")" );

	return true;
}

/**
 * Save Registration Contact Name and Fields (if any).
 *
 * @param array $config Contact config.
 * @param array $values Contact values.
 *
 * @return int Contact ID.
 */
function RegistrationSaveContactNameFields( $config, $values )
{
	if ( empty( $values['FIRST_NAME'] )
		|| empty( $values['LAST_NAME'] ) )
	{
		return 0;
	}

	$person_id = DBSeqNextID( 'people_person_id_seq' );

	$sql = "INSERT INTO PEOPLE ";

	$fields = 'PERSON_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME,';

	$values_sql = "'" . $person_id . "','" . $values['LAST_NAME'] . "','" . $values['FIRST_NAME'] . "','" . $values['MIDDLE_NAME'] . "',";

	if ( $config
		&& ! empty( $values['fields'] ) )
	{
		foreach ( (array) $values['fields'] as $column => $value )
		{
			if ( is_array( $value ) )
			{
				// Select Multiple from Options field type format.
				$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
			}

			if ( ! empty( $value )
				|| $value == '0' )
			{
				$fields .= $column . ',';

				$values_sql .= "'" . $value . "',";
			}
		}
	}

	$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values_sql, 0, -1 ) . ')';

	DBQuery( $sql );

	return $person_id;
}

/**
 * Save Registration Contact Information
 *
 * @param int   $contact_id Contact ID.
 * @param array $config     Contact config.
 * @param array $values     Contact values.
 */
function RegistrationSaveContactInfo( $contact_id, $config, $values )
{
	if ( empty( $values['info'] ) )
	{
		return;
	}

	foreach ( (array) $values['info'] as $column => $value )
	{
		if ( ! empty( $value )
			|| $value == '0' )
		{
			$sql = "INSERT INTO PEOPLE_JOIN_CONTACTS ";

			$fields = 'ID,PERSON_ID,TITLE,VALUE,';

			$values_sql = db_seq_nextval( 'people_join_contacts_id_seq' ) . ",'" .
				$contact_id . "','" . $column . "','" . $value . "',";

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values_sql, 0, -1 ) . ')';

			DBQuery( $sql );
		}
	}
}
