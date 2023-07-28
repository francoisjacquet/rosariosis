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
	global $FileUploadsPath;

	static $student_join_no_address = false;

	if ( ! $config
		|| ! $values )
	{
		return false;
	}

	// Textarea fields MarkDown sanitize.
	$values['address']['fields'] = FilterCustomFieldsMarkdown( 'address_fields', 'address', 'fields' );

	$address_id = RegistrationSaveAddress( $config['address'], $values['address'] );

	if ( $address_id
		&& ! empty( $_FILES ) )
	{
		$uploaded = FilesUploadUpdate(
			'address',
			'addressfields',
			$FileUploadsPath . 'Address/' . $address_id . '/',
			$address_id
		);
	}

	foreach ( (array) $config['parent'] as $id => $config_parent )
	{
		if ( empty( $values['parent'][ $id ] ) )
		{
			continue;
		}

		$values['parent'][ $id ]['fields'] = FilterCustomFieldsMarkdown( 'people_fields', 'parent', $id, 'fields' );

		$contact_id = RegistrationSaveContact( $config_parent, issetVal( $values['parent'][ $id ] ) );

		if ( $contact_id
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'people',
				'parent' . $id . 'fields',
				$FileUploadsPath . 'Contact/' . $contact_id . '/',
				$contact_id
			);
		}

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
		elseif ( empty( $config_parent['address'] )
			&& ! $student_join_no_address )
		{
			// No Address.
			RegistrationSaveJoinAddress( '0' );

			// Only join "No Address" once.
			$student_join_no_address = true;
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

		$values['contact'][ $id ]['fields'] = FilterCustomFieldsMarkdown( 'people_fields', 'contact', $id, 'fields' );

		$contact_id = RegistrationSaveContact( [], issetVal( $values['contact'][ $id ] ) );

		if ( $contact_id
			&& ! empty( $_FILES ) )
		{
			$uploaded = FilesUploadUpdate(
				'people',
				'contact' . $id . 'fields',
				$FileUploadsPath . 'Contact/' . $contact_id . '/',
				$contact_id
			);
		}

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
		FROM students_join_address
		WHERE STUDENT_ID='" . (int) $student_id . "'
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
		FROM students_join_people
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	foreach ( (array) $contacts_RET as $contact )
	{
		DBInsert(
			'students_join_people',
			[ 'STUDENT_ID' => UserStudentID() ] + $contact
		);
	}
}

/**
 * Save Registration Student Info / Custom Fields.
 * Limit custom fields to the Categories in config.
 *
 * @since 10.5 Save Student Files fields, upload files
 *
 * @param array $config Student Info config.
 * @param array $values Student Info values.
 *
 * @return bool
 */
function RegistrationSaveStudent( $config, $values )
{
	global $FileUploadsPath;

	if ( ! $config['fields']
		|| ! trim( $config['fields'], '||' )
		|| ! $values )
	{
		return false;
	}

	$category_ids = "'" . str_replace( '||', "','", trim( $config['fields'], '||' ) ) . "'";

	$custom_fields_RET = DBGet( "SELECT ID
		FROM custom_fields
		WHERE CATEGORY_ID IN(" . $category_ids . ")", [], [ 'ID' ] );

	$allowed_columns = array_keys( $custom_fields_RET );

	// Textarea fields MarkDown sanitize.
	$values = FilterCustomFieldsMarkdown( 'custom_fields', 'students' );

	$sql = "UPDATE students SET ";

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

	if ( ! empty( $_FILES ) )
	{
		$uploaded = FilesUploadUpdate(
			'students',
			'students',
			$FileUploadsPath . 'Student/' . UserStudentID() . '/'
		);
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

	if ( empty( trim( $values['ADDRESS'] ) ) )
	{
		return 0;
	}

	$address_key = preg_replace( '/[^0-9A-Za-z]+/', '', mb_strtolower( $values['ADDRESS'] ) );

	if ( isset( $inserted_addresses[ $address_key ] ) )
	{
		return $inserted_addresses[ $address_key ];
	}

	if ( $config
		&& ! empty( $values['fields'] ) )
	{
		foreach ( (array) $values['fields'] as $column => $value )
		{
			if ( is_array( $value ) )
			{
				// Select Multiple from Options field type format.
				$values['fields'][ $column ] = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
			}
		}
	}

	$address_id = DBInsert(
		'address',
		[
			'ADDRESS' => trim( $values['ADDRESS'] ),
			'CITY' => trim( $values['CITY'] ),
			'STATE' => trim( $values['STATE'] ),
			'ZIPCODE' => trim( $values['ZIPCODE'] ),
		] + issetVal( $values['fields'], [] ),
		'id'
	);

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

	DBInsert(
		'students_join_address',
		[
			'STUDENT_ID' => UserStudentID(),
			'ADDRESS_ID' => (int) $address_id,
		] + $students_join_address
	);
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

	return DBInsert(
		'students_join_people',
		[
			'STUDENT_ID' => UserStudentID(),
			'PERSON_ID' => (int) $contact_id,
			'ADDRESS_ID' => (int) $address_id,
			'CUSTODY' => issetVal( $config['custody'] ),
			'EMERGENCY' => issetVal( $config['emergency'] ),
			'STUDENT_RELATION' => $config['relation'],
		]
	);
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
	if ( empty( trim( $values['FIRST_NAME'] ) )
		|| empty( trim( $values['LAST_NAME'] ) ) )
	{
		return 0;
	}

	if ( $config
		&& ! empty( $values['fields'] ) )
	{
		foreach ( (array) $values['fields'] as $column => $value )
		{
			if ( is_array( $value ) )
			{
				// Select Multiple from Options field type format.
				$values['fields'][ $column ] = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
			}
		}
	}

	$person_id = DBInsert(
		'people',
		[
			'LAST_NAME' => trim( $values['LAST_NAME'] ),
			'FIRST_NAME' => trim( $values['FIRST_NAME'] ),
			'MIDDLE_NAME' => trim( $values['MIDDLE_NAME'] ),
		] + issetVal( $values['fields'], [] ),
		'id'
	);

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
			DBInsert(
				'people_join_contacts',
				[
					'PERSON_ID' => (int) $contact_id,
					'TITLE' => $column,
					'VALUE' => $value,
				]
			);
		}
	}
}
