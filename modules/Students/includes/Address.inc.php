<?php

require_once 'ProgramFunctions/TipMessage.fnc.php';

// Set this to false to disable auto-pull-downs for the contact info Description field.
$info_apd = true;

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( ! empty( $_POST['values'] )
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['values']['EXISTING'] ) )
	{
		if ( ! empty( $_REQUEST['values']['EXISTING']['address_id'] ) && $_REQUEST['address_id'] == 'old' )
		{
			$_REQUEST['address_id'] = $_REQUEST['values']['EXISTING']['address_id'];

			if ( ! DBGetOne( "SELECT 1
				FROM students_join_address
				WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'
				AND STUDENT_ID='" . UserStudentID() . "'" ) )
			{
				DBInsert(
					'students_join_address',
					[ 'STUDENT_ID' => UserStudentID(), 'ADDRESS_ID' => (int) $_REQUEST['address_id'] ]
				);

				if ( $DatabaseType === 'mysql' )
				{
					// @since 10.0 Use GROUP BY instead of DISTINCT ON for MySQL
					DBQuery( "INSERT INTO students_join_people
						(STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION)
						SELECT '" . UserStudentID() . "',PERSON_ID,
						ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION
						FROM students_join_people
						WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'
						GROUP BY PERSON_ID" );
				}
				else
				{
					DBQuery( "INSERT INTO students_join_people
						(STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION)
						SELECT DISTINCT ON (PERSON_ID) '" . UserStudentID() . "',PERSON_ID,
						ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION
						FROM students_join_people
						WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
				}
			}
		}
		elseif ( ! empty( $_REQUEST['values']['EXISTING']['person_id'] ) && $_REQUEST['person_id'] == 'old' )
		{
			$_REQUEST['person_id'] = $_REQUEST['values']['EXISTING']['person_id'];

			if ( ! DBGetOne( "SELECT 1
				FROM students_join_people
				WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'
				AND STUDENT_ID='" . UserStudentID() . "'" ) )
			{
				DBQuery( "INSERT INTO students_join_people (STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,EMERGENCY,STUDENT_RELATION)
					SELECT '" . UserStudentID() . "',PERSON_ID,
					'" . $_REQUEST['address_id'] . "',CUSTODY,EMERGENCY,STUDENT_RELATION
					FROM students_join_people
					WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'
					LIMIT 1" );

				if ( $_REQUEST['address_id'] == '0'
					&& ! DBGetOne( "SELECT 1
						FROM students_join_address
						WHERE ADDRESS_ID='0'
						AND STUDENT_ID='" . UserStudentID() . "'" ) )
				{
					DBInsert(
						'students_join_address',
						[ 'STUDENT_ID' => UserStudentID(), 'ADDRESS_ID' => '0' ]
					);
				}
			}
		}
	}

	if ( ! empty( $_REQUEST['values']['address'] ) )
	{
		// FJ other fields required.
		$required_error = CheckRequiredCustomFields( 'address_fields', $_REQUEST['values']['address'] );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['values']['address'] = FilterCustomFieldsMarkdown( 'address_fields', 'values', 'address' );

		if ( $_REQUEST['address_id'] !== 'new' )
		{
			$fields_RET = DBGet( "SELECT ID,TYPE
				FROM address_fields
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

			$update_columns = [];

			foreach ( (array) $_REQUEST['values']['address'] as $column => $value )
			{
				if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
					&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
					&& $value != ''
					&& ! is_numeric( $value ) )
				{
					// Check numeric fields.
					$error[] = _( 'Please enter valid Numeric data.' );

					continue;
				}

				if ( is_array( $value ) )
				{
					// Select Multiple from Options field type format.
					$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
				}

				$update_columns[$column] = $value;
			}

			if ( $update_columns )
			{
				DBUpdate(
					'address',
					$update_columns,
					[ 'ADDRESS_ID' => (int) $_REQUEST['address_id'] ]
				);

				//hook
				do_action( 'Students/Student.php|update_student_address' );
			}
		}
		else
		{
			$insert_columns = [];

			foreach ( (array) $_REQUEST['values']['address'] as $column => $value )
			{
				if ( is_array( $value ) )
				{
					// Select Multiple from Options field type format.
					$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
				}

				$insert_columns[$column] = $value;
			}

			$id = DBInsert(
				'address',
				$insert_columns,
				'id'
			);

			if ( $id )
			{
				DBInsert(
					'students_join_address',
					[
						'STUDENT_ID' => UserStudentID(),
						'ADDRESS_ID' => (int) $id,
					] + $_REQUEST['values']['students_join_address']
				);

				$_REQUEST['address_id'] = $id;

				//hook
				do_action( 'Students/Student.php|add_student_address' );
			}
		}
	}

	if ( ! empty( $_REQUEST['values']['people'] ) )
	{
		// FJ other fields required.
		$required_error = CheckRequiredCustomFields( 'people_fields', $_REQUEST['values']['people'] );

		// FJ textarea fields MarkDown sanitize.
		$_REQUEST['values']['people'] = FilterCustomFieldsMarkdown( 'people_fields', 'values', 'people' );

		if ( $_REQUEST['person_id'] !== 'new' )
		{
			$fields_RET = DBGet( "SELECT ID,TYPE
				FROM people_fields
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

			$update_columns = [];

			foreach ( (array) $_REQUEST['values']['people'] as $column => $value )
			{
				if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
					&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
					&& $value != ''
					&& ! is_numeric( $value ) )
				{
					$error[] = _( 'Please enter valid Numeric data.' );
					continue;
				}

				if ( is_array( $value ) )
				{
					// Select Multiple from Options field type format.
					$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
				}

				$update_columns[$column] = $value;
			}

			DBUpdate(
				'people',
				$update_columns,
				[ 'PERSON_ID' => (int) $_REQUEST['person_id'] ]
			);
		}
		else
		{
			$insert_columns = [];

			foreach ( (array) $_REQUEST['values']['people'] as $column => $value )
			{
				if ( is_array( $value ) )
				{
					// Select Multiple from Options field type format.
					$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
				}

				$insert_columns[$column] = $value;
			}

			$id = DBInsert(
				'people',
				$insert_columns,
				'id'
			);

			if ( $id )
			{
				DBInsert(
					'students_join_people',
					[
						'STUDENT_ID' => UserStudentID(),
						'PERSON_ID' => (int) $id,
						'ADDRESS_ID' => (int) $_REQUEST['address_id'],
					] + $_REQUEST['values']['students_join_people']
				);

				if ( $_REQUEST['address_id'] == '0'
					&& ! DBGetOne( "SELECT 1
						FROM students_join_address
						WHERE ADDRESS_ID='0'
						AND STUDENT_ID='" . UserStudentID() . "'" ) )
				{
					DBInsert(
						'students_join_address',
						[ 'STUDENT_ID' => UserStudentID(), 'ADDRESS_ID' => '0' ]
					);
				}

				$_REQUEST['person_id'] = $id;
			}
		}
	}

	if ( ! empty( $_REQUEST['values']['people_join_contacts'] ) )
	{
		foreach ( (array) $_REQUEST['values']['people_join_contacts'] as $id => $values )
		{
			if ( $id !== 'new' )
			{
				DBUpdate(
					'people_join_contacts',
					$values,
					[ 'ID' => (int) $id ]
				);
			}
			elseif ( $values['TITLE'] && $values['VALUE'] != '' )
			{
				DBInsert(
					'people_join_contacts',
					[ 'PERSON_ID' => (int) $_REQUEST['person_id'] ] + $values
				);
			}
		}
	}

	if ( ! empty( $_REQUEST['values']['students_join_people'] )
		&& $_REQUEST['person_id'] !== 'new' )
	{
		DBUpdate(
			'students_join_people',
			$_REQUEST['values']['students_join_people'],
			[ 'PERSON_ID' => (int) $_REQUEST['person_id'], 'STUDENT_ID' => UserStudentID() ]
		);
	}

	if ( ! empty( $_REQUEST['values']['students_join_address'] )
		&& $_REQUEST['address_id'] !== 'new' )
	{
		DBUpdate(
			'students_join_address',
			$_REQUEST['values']['students_join_address'],
			[ 'ADDRESS_ID' => (int) $_REQUEST['address_id'], 'STUDENT_ID' => UserStudentID() ]
		);
	}

	if ( $required_error )
	{
		$error[] = _( 'Please fill in the required fields' );
	}

	// Unset modfunc & values & redirect URL.
	RedirectURL( [ 'modfunc', 'values' ] );
}

if ( $_REQUEST['modfunc'] === 'delete_address'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['contact_id'] ) )
	{
		if ( DeletePrompt( _( 'Contact Information' ) ) )
		{
			DBQuery( "DELETE FROM people_join_contacts
				WHERE ID='" . (int) $_REQUEST['contact_id'] . "'" );

			// Unset modfunc & contact ID redirect URL.
			RedirectURL( [ 'modfunc', 'contact_id' ] );
		}
	}
	elseif ( ! empty( $_REQUEST['person_id'] ) )
	{
		if ( DeletePrompt( _( 'Contact' ) ) )
		{
			DBQuery( "DELETE FROM students_join_people
				WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'
				AND ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'
				AND STUDENT_ID='" . UserStudentID() . "'" );

			if ( ! DBGetOne( "SELECT 1
				FROM students_join_people
				WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" ) )
			{
				$delete_sql = "DELETE FROM people WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "';";
				$delete_sql .= "DELETE FROM people_join_contacts WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "';";

				DBQuery( $delete_sql );
			}

			if ( $_REQUEST['address_id'] == '0'
				&& ! DBGetOne( "SELECT 1
					FROM students_join_people
					WHERE ADDRESS_ID='0'
					AND STUDENT_ID='" . UserStudentID() . "'" ) )
			{
				DBQuery( "DELETE FROM students_join_address
					WHERE ADDRESS_ID='0'
					AND STUDENT_ID='" . UserStudentID() . "'" );
			}

			// Unset modfunc & person ID redirect URL.
			RedirectURL( [ 'modfunc', 'person_id' ] );
		}
	}
	elseif ( ! empty( $_REQUEST['address_id'] ) )
	{
		if ( DeletePrompt( _( 'Address' ) ) )
		{
			DBQuery( "UPDATE students_join_people
				SET ADDRESS_ID='0'
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );

			if ( ! DBGetOne( "SELECT 1
				FROM students_join_people
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND ADDRESS_ID='0'" )
				&& DBGetOne( "SELECT 1
					FROM students_join_address
					WHERE ADDRESS_ID='0'
					AND STUDENT_ID='" . UserStudentID() . "'" ) )
			{
				DBQuery( "UPDATE students_join_address
					SET ADDRESS_ID='0',RESIDENCE=NULL,MAILING=NULL,BUS_PICKUP=NULL,BUS_DROPOFF=NULL
					WHERE STUDENT_ID='" . UserStudentID() . "'
					AND ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
			}
			else
			{
				DBQuery( "DELETE FROM students_join_address
					WHERE STUDENT_ID='" . UserStudentID() . "'
					AND ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
			}

			if ( ! DBGetOne( "SELECT 1
				FROM students_join_address
				WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" ) )
			{
				DBQuery( "DELETE FROM address
					WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
			}

			// Unset modfunc & address ID redirect URL.
			RedirectURL( [ 'modfunc', 'address_id' ] );
		}
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	// Fix PostgreSQL error invalid ORDER BY, only result column names can be used
	// Do not use ORDER BY SORT_ORDER IS NULL,SORT_ORDER (nulls last) in UNION.
	$addresses_RET = DBGet( "SELECT a.ADDRESS_ID, sjp.STUDENT_RELATION,a.ADDRESS,
		a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,a.MAIL_ZIPCODE,
		sjp.CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,sja.BUS_DROPOFF," .
		db_case( [ 'a.ADDRESS_ID', "'0'", '1', '0' ] ) . "AS SORT_ORDER
	FROM address a,students_join_address sja,students_join_people sjp
	WHERE a.ADDRESS_ID=sja.ADDRESS_ID
	AND sja.STUDENT_ID='" . UserStudentID() . "'
	AND a.ADDRESS_ID=sjp.ADDRESS_ID
	AND sjp.STUDENT_ID=sja.STUDENT_ID
	UNION
	SELECT a.ADDRESS_ID,'" . DBEscapeString( _( 'No Contact' ) ) . "' AS STUDENT_RELATION,
		a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,
		a.MAIL_ZIPCODE,'' AS CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,sja.BUS_DROPOFF," .
		db_case( [ 'a.ADDRESS_ID', "'0'", '1', '0' ] ) . " AS SORT_ORDER
	FROM address a,students_join_address sja
	WHERE a.ADDRESS_ID=sja.ADDRESS_ID
	AND sja.STUDENT_ID='" . UserStudentID() . "'
	AND NOT EXISTS (SELECT ''
		FROM students_join_people sjp
		WHERE sjp.STUDENT_ID=sja.STUDENT_ID
		AND sjp.ADDRESS_ID=a.ADDRESS_ID)
	ORDER BY SORT_ORDER,RESIDENCE,CUSTODY,STUDENT_RELATION", [], [ 'ADDRESS_ID' ] );

	//echo '<pre>'; var_dump($addresses_RET); echo '</pre>';

	echo '<table><tr class="address st"><td class="valign-top">';
	echo '<table class="widefat cellpadding-5">';

	$i = 1;

	if ( ! isset( $_REQUEST['address_id'] ) || $_REQUEST['address_id'] == '' )
	{
		$_REQUEST['address_id'] = $addresses_RET ? key( $addresses_RET ) . '' : null;
	}

	if ( ( ! AllowEdit()
			|| User( 'PROFILE' ) === 'parent'
			|| User( 'PROFILE' ) === 'student' )
		&& empty( $addresses_RET ) )
	{
		echo '<tr><td colspan="3">' . _( 'This student doesn\'t have an address.' ) . '</td></tr>';
	}

	foreach ( (array) $addresses_RET as $address_id => $addresses )
	{
		echo '<tr>';

		// Do not find other students associated with "No Address".
		$xstudents = [];

		if ( $address_id )
		{
			// Find other students associated with this address.
			$xstudents = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			RESIDENCE,BUS_PICKUP,BUS_DROPOFF,MAILING
			FROM students s,students_join_address sja
			WHERE s.STUDENT_ID=sja.STUDENT_ID
			AND sja.ADDRESS_ID='" . (int) $address_id . "'
			AND sja.STUDENT_ID!='" . UserStudentID() . "'" );
		}

		if ( $xstudents )
		{
			$warning = [];

			foreach ( (array) $xstudents as $xstudent )
			{
				$ximages = '';

				if ( $xstudent['RESIDENCE'] === 'Y' )
				{
					$ximages .= ' ' . button( 'house' );
				}

				if ( $xstudent['BUS_PICKUP'] === 'Y'
					|| $xstudent['BUS_DROPOFF'] === 'Y' )
				{
					$ximages .= ' ' . button( 'bus' );
				}

				if ( $xstudent['MAILING'] === 'Y' )
				{
					$ximages .= ' ' . button( 'mailbox' );
				}

				$warning[] = $xstudent['FULL_NAME'] . $ximages;
			}

			echo '<th>' . makeTipMessage(
				implode( '<br />', $warning ),
				_( 'Other students associated with this address' ),
				button( 'help' )
			) . '</th>';
		}
		else
		{
			echo '<th>&nbsp;</th>';
		}

		$relation_list = '';

		foreach ( (array) $addresses as $address )
		{
			$relation_list .= ( $address['STUDENT_RELATION'] && ( empty( $relation_list ) ? false : mb_strpos( $address['STUDENT_RELATION'] . ', ', $relation_list ) ) == false ? $address['STUDENT_RELATION'] : '---' ) . ', ';
		}

		$address = $addresses[1];
		$relation_list = mb_substr( $relation_list, 0, -2 );

		$images = '';

		if ( $address['RESIDENCE'] == 'Y' )
		{
			$images .= ' ' . button( 'house', '', '', '" title="' . AttrEscape( _( 'Residence' ) ) );
		}

		if ( $address['BUS_PICKUP'] == 'Y' || $address['BUS_DROPOFF'] == 'Y' )
		{
			$button_title = _( 'Bus Dropoff' );

			if ( $address['BUS_PICKUP'] == 'Y' && $address['BUS_DROPOFF'] == 'Y' )
			{
				$button_title = _( 'Bus Pickup' ) . ' - ' . _( 'Bus Dropoff' );
			}
			elseif ( $address['BUS_PICKUP'] == 'Y' )
			{
				$button_title = _( 'Bus Pickup' );
			}

			$images .= ' ' . button( 'bus', '', '', '" title="' . AttrEscape( $button_title ) );
		}

		if ( $address['MAILING'] == 'Y' )
		{
			$images .= ' ' . button( 'mailbox', '', '', '" title="' . AttrEscape( _( 'Mailing Address' ) ) );
		}

		echo '<th colspan="2">' . $images . '&nbsp;' . $relation_list . '</th>';

		echo '</tr>';

		if ( $address_id == $_REQUEST['address_id'] && $_REQUEST['address_id'] !== 'new' )
		{
			$this_address = $address;
		}

		$i++;

		$remove_address_button = '';

		if ( AllowEdit()
			&& User( 'PROFILE' ) !== 'parent'
			&& User( 'PROFILE' ) !== 'student'
			&& $address['ADDRESS_ID'] )
		{
			$remove_address_button = button(
				'remove',
				'',
				'"Modules.php?modname=' . $_REQUEST['modname'] .
				'&category_id=' . $_REQUEST['category_id'] .
				'&address_id=' . $address['ADDRESS_ID'] .
				'&modfunc=delete_address"'
			);
		}

		if ( $_REQUEST['address_id'] == $address['ADDRESS_ID'] )
		{
			echo '<tr class="highlight"><td>' . $remove_address_button . '</td><td>';
		}
		else
		{
			echo '<tr class="highlight-hover"><td>' . $remove_address_button . '</td><td>';
		}

		echo '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $_REQUEST['category_id'] .
			'&address_id=' . $address['ADDRESS_ID'] ) . '">' .
			$address['ADDRESS'] .
			'<br />' . ( $address['CITY'] ? $address['CITY'] . ', ' : '' ) .
			$address['STATE'] . ( $address['ZIPCODE'] ? ' ' . $address['ZIPCODE'] : '' ) . '</a>';

		echo '</td>';
		echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $address['ADDRESS_ID'] ) . '" class="arrow right"></a></td>';

		echo '</tr>';
	}

	if ( AllowEdit()
		&& User( 'PROFILE' ) !== 'parent'
		&& User( 'PROFILE' ) !== 'student'
		&& ! array_key_exists( '0', (array) $addresses_RET ) )
	{
		// No Address link.
		if ( $_REQUEST['address_id'] == '0' )
		{
			echo '<tr class="highlight">';
		}
		else
		{
			echo '<tr class="highlight-hover">';
		}

		echo '<td>&nbsp;</td>';

		echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $_REQUEST['category_id'] . '&address_id=0' ) . '">' .
			_( 'No Address' ) . '</a>';

		echo '</td>';
		echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=0' ) . '" class="arrow right"></a></td>';

		echo '</tr>';
	}

	if ( AllowEdit()
		&& User( 'PROFILE' ) !== 'parent'
		&& User( 'PROFILE' ) !== 'student' )
	{
		// Do not allow Parents/Students to add New/Existing Address.
		$tr_add_highlight = '<tr class="highlight"><td>' . button( 'add' ) . '</td><td>';

		$tr_add_highlight_hover = '<tr class="highlight-hover"><td>' . button( 'add' ) . '</td><td>';

		// New Address.
		echo $_REQUEST['address_id'] == 'new' ? $tr_add_highlight : $tr_add_highlight_hover;

		$link = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $_REQUEST['category_id'] . '&address_id=new';

		echo '<a href="' . URLEscape( $link ) . '">' . _( 'Add a <b>New</b> Address' ) . '</a></td>';

		echo '<td></td></tr>';

		// Existing Address.
		echo $_REQUEST['address_id'] == 'old' ? $tr_add_highlight : $tr_add_highlight_hover;

		$link = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&category_id=' . $_REQUEST['category_id'] . '&address_id=old';

		echo '<a href="' . URLEscape( $link ) . '">' . _( 'Add an <b>Existing</b> Address' ) . '</a></td>';

		echo '<td></td></tr>';
	}

	echo '</table></td>';

	if ( isset( $_REQUEST['address_id'] ) )
	{
		echo '<td class="valign-top">';
		echo '<input type="hidden" name="address_id" value="' . AttrEscape( $_REQUEST['address_id'] ) . '">';

		if ( $_REQUEST['address_id'] !== 'new' && $_REQUEST['address_id'] != 'old' )
		{
			$contacts_RET = DBGet( "SELECT p.PERSON_ID,p.FIRST_NAME,p.MIDDLE_NAME,p.LAST_NAME,
				" . DisplayNameSQL( 'p' ) . " AS FULL_NAME,
				sjp.CUSTODY,sjp.EMERGENCY,sjp.STUDENT_RELATION
				FROM people p,students_join_people sjp
				WHERE p.PERSON_ID=sjp.PERSON_ID
				AND sjp.STUDENT_ID='" . UserStudentID() . "'
				AND sjp.ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'
				ORDER BY sjp.STUDENT_RELATION" );

			if ( ! empty( $contacts_RET ) || AllowEdit() )
			{
				echo '<table class="widefat width-100p"><tr><th colspan="3">';

				echo ( $_REQUEST['address_id'] == '0' ? _( 'Contacts without an Address' ) : _( 'Contacts at this Address' ) ) . '</th></tr>';
			}

			$i = 1;

			foreach ( (array) $contacts_RET as $contact )
			{
				$THIS_RET = $contact;

				if ( isset( $_REQUEST['person_id'] )
					&& $contact['PERSON_ID'] == $_REQUEST['person_id'] )
				{
					$this_contact = $contact;
				}

				$i++;

				$remove_button = '';

				if ( AllowEdit()
					&& User( 'PROFILE' ) !== 'parent'
					&& User( 'PROFILE' ) !== 'student' )
				{
					$remove_button = button(
						'remove',
						'',
						URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
						'&category_id=' . $_REQUEST['category_id'] .
						'&modfunc=delete_address&address_id=' . $_REQUEST['address_id'] .
						'&person_id=' . $contact['PERSON_ID'] )
					);
				}

				if ( isset( $_REQUEST['person_id'] )
					&& $_REQUEST['person_id'] == $contact['PERSON_ID'] )
				{
					echo '<tr class="highlight"><td>' . $remove_button . '</td><td>';
				}
				else
				{
					echo '<tr class="highlight-hover"><td>' . $remove_button . '</td><td>';
				}

				$images = '';

				// Find other students associated with this person.
				$xstudents = DBGet( "SELECT s.STUDENT_ID,
					" . DisplayNameSQL( 's' ) . " AS FULL_NAME,
					STUDENT_RELATION,CUSTODY,EMERGENCY
					FROM students s,students_join_people sjp
					WHERE s.STUDENT_ID=sjp.STUDENT_ID
					AND sjp.PERSON_ID='" . (int) $contact['PERSON_ID'] . "'
					AND sjp.STUDENT_ID!='" . UserStudentID() . "'" );

				if ( $xstudents )
				{
					$warning = [];

					foreach ( (array) $xstudents as $xstudent )
					{
						$ximages = '';

						if ( $xstudent['CUSTODY'] === 'Y' )
						{
							$ximages .= ' ' . button( 'gavel' );
						}

						if ( $xstudent['EMERGENCY'] === 'Y' )
						{
							$ximages .= ' ' . button( 'emergency' );
						}

						$warning[] = $xstudent['FULL_NAME'] .
							( $xstudent['STUDENT_RELATION'] ? ' (' . $xstudent['STUDENT_RELATION'] . ') ' : '' ) .
							$ximages;
					}

					$images .= makeTipMessage(
						implode( '<br />', $warning ),
						_( 'Other students associated with this person' ),
						button( 'help' )
					) . ' ';
				}

				if ( $contact['CUSTODY'] === 'Y' )
				{
					$images .= button( 'gavel', '', '', '" title="' . AttrEscape( _( 'Custody' ) ) ) . ' ';
				}

				if ( $contact['EMERGENCY'] === 'Y' )
				{
					$images .= button( 'emergency', '', '', '" title="' . AttrEscape( _( 'Emergency' ) ) ) . ' ';
				}

				echo $images .
				'<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
					'&category_id=' . $_REQUEST['category_id'] .
					'&address_id=' . $_REQUEST['address_id'] . '&person_id=' . $contact['PERSON_ID'] ) . '">' .
				$contact['FULL_NAME'] . '</a>';

				echo $contact['STUDENT_RELATION'] ? ' (' . $contact['STUDENT_RELATION'] . ')' : '';

				echo '</td><td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $_REQUEST['address_id'] . '&person_id=' . $contact['PERSON_ID'] ) . '" class="arrow right"></a></td>';
				echo '</tr>';
			}

			if ( empty( $this_contact )
				&& isset( $_REQUEST['person_id'] )
				&& $_REQUEST['person_id'] !== 'old'
				&& $_REQUEST['person_id'] !== 'new' )
			{
				// Contact not found, remove person_id & redirect URL.
				RedirectURL( 'person_id' );
			}

			// New Contact

			if ( AllowEdit()
				&& User( 'PROFILE' ) !== 'parent'
				&& User( 'PROFILE' ) !== 'student' )
			{
				// Do not allow Parents/Students to add New/Existing Contact.
				if ( isset( $_REQUEST['person_id'] )
					&& $_REQUEST['person_id'] == 'new' )
				{
					echo '<tr class="highlight"><td>' . button( 'add' ) . '</td><td>';
				}
				else
				{
					echo '<tr class="highlight-hover"><td>' . button( 'add' ) . '</td><td>';
				}

				echo '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $_REQUEST['address_id'] . '&person_id=new' ) . '">' . _( 'Add a <b>New</b> Contact' ) . '</a>';
				echo '</td>';

				echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $_REQUEST['address_id'] . '&person_id=new' ) . '"></a></td>';
				echo '</tr>';

				if ( isset( $_REQUEST['person_id'] )
					&& $_REQUEST['person_id'] == 'old' )
				{
					echo '<tr class="highlight"><td>' . button( 'add' ) . '</td><td>';
				}
				else
				{
					echo '<tr class="highlight-hover"><td>' . button( 'add' ) . '</td><td>';
				}

				echo '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $_REQUEST['address_id'] . '&person_id=old' ) . '">' . _( 'Add an <b>Existing</b> Contact' ) . '</a>';
				echo '</td>';

				echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&category_id=' . $_REQUEST['category_id'] . '&address_id=' . $_REQUEST['address_id'] . '&person_id=old' ) . '"></a></td>';
				echo '</tr>';
			}

			if ( ! empty( $contacts_RET ) || AllowEdit() )
			{
				echo '</table><br />';
			}
		}

		if ( $_REQUEST['address_id'] != '0'
			&& $_REQUEST['address_id'] != 'old' )
		{
			if ( $_REQUEST['address_id'] === 'new' )
			{
				$size = true;
			}
			else
			{
				$size = false;
			}

			// Get City, State & Zip options for auto pull-downs.
			$city_options = _makeAutoSelect(
				'CITY',
				'address',
				[
					[ 'CITY' => issetVal( $this_address['CITY'] ) ],
					[ 'CITY' => issetVal( $this_address['MAIL_CITY'] ) ],
				],
				[]
			);

			$state_options = _makeAutoSelect(
				'STATE',
				'address',
				[
					[ 'STATE' => issetVal( $this_address['STATE'] ) ],
					[ 'STATE' => issetVal( $this_address['MAIL_STATE'] ) ],
				],
				[]
			);

			$zip_options = _makeAutoSelect(
				'ZIPCODE',
				'address',
				[
					[ 'ZIPCODE' => issetVal( $this_address['ZIPCODE'] ) ],
					[ 'ZIPCODE' => issetVal( $this_address['MAIL_ZIPCODE'] ) ],
				],
				[]
			);

			echo '<table class="widefat width-100p"><tr><th colspan="3">' .
			_( 'Address' ) . '</th></tr>';

			echo '<tr><td colspan="3">' .
			TextInput(
				issetVal( $this_address['ADDRESS'] ),
				'values[address][ADDRESS]',
				_( 'Street' ),
				$size ? 'required maxlength=255 size=20' : 'required maxlength=255' ) .
				'</td></tr>';

			$force_st = false;

			if ( mb_strlen( (string) $this_address['CITY'] ) > 22
				&& mb_strpos( $this_address['CITY'], ' ' ) === false )
			{
				// If City length > 22 without space, force stackable table.
				$force_st = true;
			}

			// City, State & Zip auto pull-downs.
			echo '<tr class="st"><td>' .
			_makeAutoSelectInputX(
				issetVal( $this_address['CITY'] ),
				'CITY',
				'address',
				_( 'City' ),
				$city_options
			) . '</td>';

			if ( $force_st )
			{
				echo '</tr><tr>';
			}

			echo '<td>' .
			_makeAutoSelectInputX(
				issetVal( $this_address['STATE'] ),
				'STATE',
				'address',
				_( 'State' ),
				$state_options
			) . '</td>';

			if ( $force_st )
			{
				echo '</tr><tr>';
			}

			echo '<td>' .
			_makeAutoSelectInputX(
				issetVal( $this_address['ZIPCODE'] ),
				'ZIPCODE',
				'address',
				_( 'Zip Code' ),
				$zip_options
			) . '</td></tr>';

			echo '<tr><td colspan="3">' .
			TextInput(
				issetVal( $this_address['PHONE'] ),
				'values[address][PHONE]',
				_( 'Phone' ),
				$size ? 'maxlength=30 size=13' : 'maxlength=30'
			) . '</td></tr>';

			if ( $_REQUEST['address_id'] !== 'new' && $_REQUEST['address_id'] != '0' )
			{
				$display_address = $this_address['ADDRESS'] . ', ' . ( $this_address['CITY'] ? ' ' . $this_address['CITY'] . ', ' : '' ) . $this_address['STATE'] . ( $this_address['ZIPCODE'] ? ' ' . $this_address['ZIPCODE'] : '' );

				$link = URLEscape( 'https://www.openstreetmap.org/search?query=' . $display_address );

				echo '<tr><td class="valign-top" colspan="3">' .
				button(
					'compass_rose',
					_( 'Map It' ),
					'"#" onclick="' . AttrEscape( 'popups.open(
						' . json_encode( $link ) . ',
						"scrollbars=yes,resizable=yes,width=1000,height=700"
						); return false;' ) . '"',
					'bigger'
				) . '</td></tr>';
			}

			echo '</table>';

			$new = false;

			if ( $_REQUEST['address_id'] == 'new' )
			{
				$new = true;
				$this_address['RESIDENCE'] = 'Y';
				$this_address['MAILING'] = 'Y';

				$this_address['BUS_PICKUP'] = '';
				$this_address['BUS_DROPOFF'] = '';

				if ( ProgramConfig( 'students', 'STUDENTS_USE_BUS' ) )
				{
					$this_address['BUS_PICKUP'] = 'Y';
					$this_address['BUS_DROPOFF'] = 'Y';
				}
			}

			echo '<br /><table class="widefat width-100p"><tr><td>' .
				button( 'house', '', '', 'bigger' ) .
				'</td><td>' .
				CheckboxInput(
					issetVal( $this_address['RESIDENCE'] ),
					'values[students_join_address][RESIDENCE]',
					_( 'Residence' ),
					'CHECKED',
					$new,
					button( 'check' ),
					button( 'x' )
				) . '</td></tr>';

			echo '<tr><td>' .
				button( 'bus', '', '', 'bigger' ) .
				'</td><td>' .
				CheckboxInput(
					issetVal( $this_address['BUS_PICKUP'] ),
					'values[students_join_address][BUS_PICKUP]',
					_( 'Bus Pickup' ),
					'CHECKED',
					$new,
					button( 'check' ),
					button( 'x' )
				) . '</td></tr>';

			echo '<tr><td>' .
				button( 'bus', '', '', 'bigger' ) .
				'</td><td>' .
				CheckboxInput(
					issetVal( $this_address['BUS_DROPOFF'] ),
					'values[students_join_address][BUS_DROPOFF]',
					_( 'Bus Dropoff' ),
					'CHECKED',
					$new,
					button( 'check' ),
					button( 'x' )
				) . '</td></tr>';

			if ( Config( 'STUDENTS_USE_MAILING' )
				|| $this_address['MAIL_CITY']
				|| $this_address['MAIL_STATE']
				|| $this_address['MAIL_ZIPCODE'] )
			{
				echo '<script> function show_mailing(checkbox){if (checkbox.checked==true) document.getElementById(\'mailing_address_div\').style.visibility=\'visible\'; else document.getElementById(\'mailing_address_div\').style.visibility=\'hidden\';}</script>';

				echo '<tr><td>' .
					button( 'mailbox', '', '', 'bigger' ) .
					'</td><td>' .
					CheckboxInput(
						$this_address['MAILING'],
						'values[students_join_address][MAILING]',
						_( 'Mailing Address' ),
						'CHECKED',
						$new,
						button( 'check' ),
						button( 'x' ),
						true,
						'onclick=show_mailing(this);'
					) . '</td></tr></table>';

				echo '<div id="mailing_address_div" style="visibility: ' . ( ( $this_address['MAILING'] || $_REQUEST['address_id'] == 'new' ) ? 'visible' : 'hidden' ) . ';">';

				echo '<br /><table class="widefat width-100p"><tr><th colspan="3">' . _( 'Mailing Address' ) . '&nbsp;(' . _( 'If different than above' ) . ')';

				echo '</th></tr>';

				echo '<tr><td colspan="3">' . TextInput(
					issetVal( $this_address['MAIL_ADDRESS'], '' ),
					'values[address][MAIL_ADDRESS]',
					_( 'Street' ),
					! $this_address['MAIL_ADDRESS'] ? 'size=20' : ''
				) . '</td></tr>';

				echo '<tr><td>' . _makeAutoSelectInputX(
					$this_address['MAIL_CITY'],
					'MAIL_CITY',
					'address',
					_( 'City' ),
					[]
				) . '</td>';

				echo '<td>' . _makeAutoSelectInputX(
					$this_address['MAIL_STATE'],
					'MAIL_STATE',
					'address',
					_( 'State' ),
					[]
				) . '</td>';

				echo '<td>' . _makeAutoSelectInputX(
					$this_address['MAIL_ZIPCODE'],
					'MAIL_ZIPCODE',
					'address',
					_( 'Zip Code' ),
					[]
				) . '</td></tr>';

				echo '</table></div>';
			}
			else
			{
				echo '<tr><td>' .
					button( 'mailbox', '', '', 'bigger' ) .
					'</td><td>' .
					CheckboxInput(
						issetVal( $this_address['MAILING'] ),
						'values[students_join_address][MAILING]',
						_( 'Mailing Address' ),
						'CHECKED',
						$new,
						button( 'check' ),
						button( 'x' )
					) . '</td></tr></table>';
			}
		}

		if ( $_REQUEST['address_id'] === 'old' )
		{
			$limit_current_school_sql = '';

			if ( Config( 'LIMIT_EXISTING_CONTACTS_ADDRESSES' ) )
			{
				// Limit Existing Addresses to current school.
				$limit_current_school_sql = " AND ADDRESS_ID IN (SELECT sja.ADDRESS_ID
					FROM students_join_address sja, student_enrollment se
					WHERE sja.STUDENT_ID=se.STUDENT_ID
					AND se.SCHOOL_ID='" . UserSchool() . "')";
			}

			$addresses_RET = DBGet( "SELECT ADDRESS_ID,ADDRESS,CITY,STATE,ZIPCODE
				FROM address
				WHERE ADDRESS_ID!='0'
				AND ADDRESS_ID NOT IN (SELECT ADDRESS_ID
					FROM students_join_address
					WHERE STUDENT_ID='" . UserStudentID() . "')" .
				$limit_current_school_sql .
				" ORDER BY ADDRESS,CITY,STATE,ZIPCODE" );

			$address_select = [];

			foreach ( (array) $addresses_RET as $address )
			{
				$address_select[$address['ADDRESS_ID']] = trim( (string) $address['ADDRESS'] ) . ', ' . trim( (string) $address['CITY'] ) . ', ' . trim( (string) $address['STATE'] ) . ', ' . trim( (string) $address['ZIPCODE'] );
			}

			echo Select2Input(
				'',
				'values[EXISTING][address_id]',
				_( 'Select Address' ),
				$address_select
			);
		}

		echo '</td>';

		if ( ! empty( $_REQUEST['person_id'] ) )
		{
			echo '<td class="valign-top">';
			echo '<input type="hidden" name="person_id" value="' . AttrEscape( $_REQUEST['person_id'] ) . '" />';

			if ( $_REQUEST['person_id'] != 'old' )
			{
				$relation_options = _makeAutoSelect(
					'STUDENT_RELATION',
					'students_join_people',
					issetVal( $this_contact['STUDENT_RELATION'] ),
					[]
				);

				//FJ css WPadmin
				echo '<table class="widefat"><tr><th colspan="3">' . _( 'Contact Information' ) . '</th></tr>';

				if ( $_REQUEST['person_id'] !== 'new' )
				{
					echo '<tr><td id="person_' . $this_contact['PERSON_ID'] . '" colspan="2">';

					$id = 'person_' . $this_contact['PERSON_ID'];

					$person_html = _makePeopleInput(
						$this_contact['FIRST_NAME'],
						'FIRST_NAME',
						_( 'First Name' )
					) . '<br />' .
					_makePeopleInput(
						$this_contact['MIDDLE_NAME'],
						'MIDDLE_NAME',
						_( 'Middle Name' )
					) . '<br />' .
					_makePeopleInput(
						$this_contact['LAST_NAME'],
						'LAST_NAME',
						_( 'Last Name' )
					);

					echo InputDivOnclick(
						$id,
						$person_html,
						$this_contact['FULL_NAME'],
						FormatInputTitle( _( 'Name' ), $id )
					);

					echo '<tr><td colspan="2">' . _makeAutoSelectInputX( $this_contact['STUDENT_RELATION'], 'STUDENT_RELATION', 'students_join_people', _( 'Relation' ), $relation_options ) . '</td>';

					// Custody.
					echo '<tr><td>' . button( 'gavel', '', '', 'bigger' ) . '</td><td>' .
					CheckboxInput(
						$this_contact['CUSTODY'],
						'values[students_join_people][CUSTODY]',
						_( 'Custody' ),
						'',
						false,
						button( 'check' ),
						button( 'x' )
					) . '</td></tr>';

					// Emergency.
					echo '<tr><td>' . button( 'emergency', '', '', 'bigger' ) . '</td><td>' .
					CheckboxInput(
						$this_contact['EMERGENCY'],
						'values[students_join_people][EMERGENCY]',
						_( 'Emergency' ),
						'',
						false,
						button( 'check' ),
						button( 'x' )
					) . '</td></tr>';

					$info_RET = DBGet( "SELECT ID,TITLE,VALUE
						FROM people_join_contacts
						WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" );

					$info_options = _makeAutoSelect(
						'TITLE',
						'people_join_contacts',
						$info_RET,
						[]
					);

					foreach ( (array) $info_RET as $info )
					{
						echo '<tr>';

						if ( AllowEdit() )
						{
							echo '<td>' . button(
								'remove',
								'',
								URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
								'&category_id=' . $_REQUEST['category_id'] .
								'&modfunc=delete_address&address_id=' . $_REQUEST['address_id'] .
								'&person_id=' . $_REQUEST['person_id'] .
								'&contact_id=' . $info['ID'] )
							) . '</td><td>';
						}
						else
						{
							echo '<td></td><td>';
						}

						if ( ! AllowEdit() )
						{
							echo TextInput(
								$info['VALUE'],
								'values[people_join_contacts][' . $info['ID'] . '][VALUE]',
								$info['TITLE']
							);
						}
						else
						{
							$id = 'info_' . $info['ID'];

							$info_html = TextInput(
								$info['VALUE'],
								'values[people_join_contacts][' . $info['ID'] . '][VALUE]',
								'',
								'',
								false
							) . '<br />';

							if ( $info_apd )
							{
								$info_html .= _makeAutoSelectInputX(
									$info['TITLE'],
									'TITLE',
									'people_join_contacts',
									'',
									$info_options,
									$info['ID'],
									false
								);
							}
							else
							{
								$info_html .= TextInput(
									$info['TITLE'],
									'values[people_join_contacts][' . $info['ID'] . '][TITLE]',
									'',
									'',
									false
								);
							}

							echo InputDivOnclick(
								$id,
								$info_html,
								$info['VALUE'],
								FormatInputTitle(
									$info['TITLE'] === '---' ?
									'<span class="legend-red">-' . _( 'Edit' ) . '-</span>' :
									$info['TITLE'],
									$id
								)
							);
						}

						echo '</td></tr>';
					}

					if ( AllowEdit()
						&& ProgramConfig( 'students', 'STUDENTS_USE_CONTACT' ) )
					{
						echo '<tr><td>' . button( 'add' ) . '</td><td>' .
						TextInput(
							'',
							'values[people_join_contacts][new][VALUE]',
							_( 'Value' ),
							'maxlength=100'
						) . '<br />' . ( $info_apd && count( (array) $info_options ) > 1 ?
							_makeAutoSelectInputX(
								'',
								'TITLE',
								'people_join_contacts',
								_( 'Description' ),
								$info_options,
								'new',
								false
							) :
							TextInput(
								'',
								'values[people_join_contacts][new][TITLE]',
								_( 'Description' ),
								'maxlength=100'
							) ) . '</td></tr>';
					}
				}
				else
				{
					echo '<tr>
					<td colspan="2">' .
					_makePeopleInput(
						'',
						'FIRST_NAME',
						_( 'First Name' )
					) .
					'<br />' .
					_makePeopleInput(
						'',
						'MIDDLE_NAME',
						_( 'Middle Name' )
					) . '<br />' .
					_makePeopleInput(
						'',
						'LAST_NAME',
						_( 'Last Name' )
					) . '</tr>';

					echo '<tr><td colspan="3">' .
					_makeAutoSelectInputX(
						'',
						'STUDENT_RELATION',
						'students_join_people',
						_( 'Relation' ),
						$relation_options
					) . '</td></tr>';

					// Custody.
					echo '<tr><td>' . button( 'gavel', '', '', 'bigger' ) . ' ' .
					CheckboxInput(
						'',
						'values[students_join_people][CUSTODY]',
						_( 'Custody' ),
						'',
						true
					) . '</td></tr>';

					// Emergency.
					echo '<tr><td>' . button( 'emergency', '', '', 'bigger' ) . ' ' .
					CheckboxInput(
						issetVal( $this_contact['EMERGENCY'] ),
						'values[students_join_people][EMERGENCY]',
						_( 'Emergency' ),
						'',
						true
					) .	'</td></tr>';
				}

				echo '</table>';

				$categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
					c.CUSTODY,c.EMERGENCY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED
					FROM people_field_categories c,people_fields f
					WHERE f.CATEGORY_ID=c.ID
					ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.TITLE,f.SORT_ORDER IS NULL,f.SORT_ORDER,f.TITLE", [], [ 'CATEGORY_ID' ] );

				if ( $categories_RET )
				{
					echo '<td class="valign-top">';

					if ( $_REQUEST['person_id'] !== 'new' )
					{
						$value = DBGet( "SELECT * FROM people WHERE PERSON_ID='" . (int) $_REQUEST['person_id'] . "'" );
						$value = $value[1];
					}
					else
					{
						$value = [];
					}

					$request = 'values[people]';
					echo '<table class="cellpadding-5">';

					foreach ( (array) $categories_RET as $fields_RET )
					{
						if ( ( empty( $fields_RET[1]['CUSTODY'] )
								&& empty( $fields_RET[1]['EMERGENCY'] ) )
							|| ( $fields_RET[1]['CUSTODY'] == 'Y'
								&& $this_contact['CUSTODY'] == 'Y' )
							|| ( $fields_RET[1]['EMERGENCY'] == 'Y'
								&& $this_contact['EMERGENCY'] == 'Y' ) )
						{
							echo '<tr><td>';
							echo '<fieldset><legend>' . ParseMLField( $fields_RET[1]['CATEGORY_TITLE'] ) . '</legend>';

							// Allow multiple categories, do not use require_once.
							require 'modules/Students/includes/Other_Fields.inc.php';

							echo '</fieldset>';
							echo '</td></tr>';
						}
					}

					echo '</table>';
					echo '</td>';
				}
			}
			elseif ( $_REQUEST['person_id'] === 'old' )
			{
				$limit_current_school_sql = '';

				if ( Config( 'LIMIT_EXISTING_CONTACTS_ADDRESSES' ) )
				{
					// Limit Existing Contacts to current school.
					$limit_current_school_sql = " AND p.PERSON_ID IN (SELECT sjp.PERSON_ID
						FROM students_join_people sjp, student_enrollment se
						WHERE sjp.STUDENT_ID=se.STUDENT_ID
						AND se.SCHOOL_ID='" . UserSchool() . "')";
				}

				$people_RET = DBGet( "SELECT DISTINCT p.PERSON_ID,
					" . DisplayNameSQL( 'p' ) . " AS FULL_NAME
					FROM people p,students_join_people sjp
					WHERE sjp.PERSON_ID=p.PERSON_ID
					AND sjp.ADDRESS_ID" . ( $_REQUEST['address_id'] != '0' ? '!=' : '=' ) . "'0'
					AND p.PERSON_ID NOT IN (SELECT PERSON_ID
						FROM students_join_people
						WHERE STUDENT_ID='" . UserStudentID() . "')" .
					$limit_current_school_sql .
					" ORDER BY FULL_NAME" );

				$people_select = [];

				foreach ( (array) $people_RET as $people )
				{
					$people_select[$people['PERSON_ID']] = $people['FULL_NAME'];
				}

				echo Select2Input(
					'',
					'values[EXISTING][person_id]',
					_( 'Select Person' ),
					$people_select
				);
			}
		}
		elseif ( $_REQUEST['address_id'] != '0' && $_REQUEST['address_id'] != 'old' )
		{
			$categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
				c.RESIDENCE,c.MAILING,c.BUS,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED
				FROM address_field_categories c,address_fields f
				WHERE f.CATEGORY_ID=c.ID
				ORDER BY c.SORT_ORDER IS NULL,c.SORT_ORDER,c.TITLE,f.SORT_ORDER IS NULL,f.SORT_ORDER,f.TITLE", [], [ 'CATEGORY_ID' ] );

			if ( $categories_RET )
			{
				echo '<td class="valign-top">';

				if ( $_REQUEST['address_id'] !== 'new' )
				{
					$value = DBGet( "SELECT * FROM address WHERE ADDRESS_ID='" . (int) $_REQUEST['address_id'] . "'" );
					$value = $value[1];
				}
				else
				{
					$value = [];
				}

				$request = 'values[address]';
				echo '<table class="cellpadding-5">';

				foreach ( (array) $categories_RET as $fields_RET )
				{
					if ( ! $fields_RET[1]['RESIDENCE'] && ! $fields_RET[1]['MAILING'] && ! $fields_RET[1]['BUS'] || $fields_RET[1]['RESIDENCE'] == 'Y' && $this_address['RESIDENCE'] == 'Y' || $fields_RET[1]['MAILING'] == 'Y' && $this_address['MAILING'] == 'Y' || $fields_RET[1]['BUS'] == 'Y' && ( $this_address['BUS_PICKUP'] == 'Y' || $this_address['BUS_DROPOFF'] == 'Y' ) )
					{
						echo '<tr><td>';
						echo '<fieldset><legend>' . ParseMLField( $fields_RET[1]['CATEGORY_TITLE'] ) . '</legend>';

						// Allow multiple categories, do not use require_once.
						require 'modules/Students/includes/Other_Fields.inc.php';

						echo '</fieldset>';
						echo '</td></tr>';
					}
				}

				echo '</table>';
			}
		}

		echo '</td>';
	}

	/*else
	echo '<td></td><td></td>';*/
	echo '</tr>';
	echo '</table>';
	$separator = '<hr>';

	require_once 'modules/Students/includes/Other_Info.inc.php';
}

/**
 * @param $value
 * @param $column
 * @param $title
 */
function _makePeopleInput( $value, $column, $title = '' )
{
	$options = '';

	if ( $column === 'LAST_NAME'
		|| $column === 'FIRST_NAME' )
	{
		$options .= 'required';
	}

	if ( $column === 'LAST_NAME'
		|| $column === 'FIRST_NAME'
		|| $column === 'MIDDLE_NAME' )
	{
		$options .= ' maxlength=50';
	}

	$div = $_REQUEST['person_id'] == 'new';

	$table = $column == 'STUDENT_RELATION' ? 'students_join_people' : 'people';

	return TextInput(
		$value,
		'values[' . $table . '][' . $column . ']',
		$title,
		$options,
		false
	);
}

/**
 * @param $column
 * @param $table
 * @param $values
 * @param array $options
 * @return mixed
 */
function _makeAutoSelect( $column, $table, $values = '', $options = [] )
{
	$fatal_error = [];

	// Tables white list, prevent hacking.
	$tables_white_list = [
		'address',
		'students_join_people',
		'people_join_contacts',
	];

	if ( ! in_array( $table, $tables_white_list ) )
	{
		// Do NOT translate this error, should never be displayed.
		$fatal_error[] = sprintf( '_makeAutoSelect error: unknown table %s', $table );
	}

	// Column sanitize, prevent hacking.

	if ( ! preg_match( "/^[a-zA-Z0-9_]*$/", $column ) )
	{
		// Do NOT translate this error, should never be displayed.
		$fatal_error[] = sprintf( '_makeAutoSelect error: illegal column %s', $column );
	}

	if ( $fatal_error )
	{
		return ErrorMessage( $error, 'fatal' );
	}

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	if ( AllowEdit() ) // We don't really need the select list if we can't edit anyway.
	{
		$limit_current_school_sql = '';

		if ( $table === 'address'
			&& Config( 'LIMIT_EXISTING_CONTACTS_ADDRESSES' ) )
		{
			// Limit Existing Addresses to current school.
			$limit_current_school_sql = " WHERE ADDRESS_ID IN (SELECT sja.ADDRESS_ID
				FROM students_join_address sja, student_enrollment se
				WHERE sja.STUDENT_ID=se.STUDENT_ID
				AND se.SCHOOL_ID='" . UserSchool() . "')";
		}

		// Add values already in table
		$options_RET = DBGet( "SELECT DISTINCT " . DBEscapeIdentifier( $column ) .
			",upper(" . DBEscapeIdentifier( $column ) . ") AS SORT_KEY
			FROM " . DBEscapeIdentifier( $table ) .
			$limit_current_school_sql .
			" ORDER BY SORT_KEY" );

		foreach ( (array) $options_RET as $option )
		{
			if ( $option[$column] == '' )
			{
				continue;
			}

			$option_val = trim( $option[$column] );

			if ( $option_val != ''
				&& ! isset( $options[$option_val] ) )
			{
				$options[$option_val] = [ $option_val, $option_val ];
			}
		}
	}

	// Make sure values are in the list.

	if ( isset( $values )
		&& is_array( $values ) )
	{
		foreach ( (array) $values as $value )
		{
			if ( $value[$column] != ''
				&& ! isset( $options[$value[$column]] ) )
			{
				$options[$value[$column]] = [ $value[$column], $value[$column] ];
			}
		}
	}
	elseif ( $values != ''
		&& ! isset( $options[$values] ) )
	{
		$options[$values] = [ $values, $values ];
	}

	return $options;
}

/**
 * Make Auto Select input
 * aka auto pull-down:
 * When the -Edit- option is selected,
 * the select field is automatically transformed into a text field.
 * If the value is "---" or if there are less than 2 values saved yet,
 * a text field is directly shown.
 *
 * Local function.
 *
 * @uses SelectInput()
 * @uses TextInput()
 *
 * @param  string  $value  Input value.
 * @param  string  $column Column.
 * @param  string  $table  DB table (address or people_join_contacts or students_join_people).
 * @param  string  $title  Input title.
 * @param  array   $select Select options.
 * @param  string  $id     ID. Optional. Defaults to ''.
 * @param  boolean $div    Wrap in div onclick? Optional. Defaults to false.
 * @return string  Select or Text Input.
 */
function _makeAutoSelectInputX( $value, $column, $table, $title, $select, $id = '', $div = true )
{
	static $js_included = false;

	$select_options = '';

	if ( $column === 'CITY'
		|| $column === 'MAIL_CITY' )
	{
		$options = 'maxlength=100';

		$select_options = 'style="max-width:205px"';
	}

	if ( $column === 'STATE'
		|| $column === 'MAIL_STATE' )
	{
		$options = 'size=3 maxlength=50';

		$select_options = 'style="max-width:140px"';
	}
	elseif ( $column === 'ZIPCODE'
		|| $column === 'MAIL_ZIPCODE' )
	{
		$options = 'size=5 maxlength=10';
	}
	elseif ( $column === 'TITLE' )
	{
		$options = 'maxlength=100';

		if ( $table === 'people_join_contacts'
			&& $id !== 'new' )
		{
			$options .= ' required';
		}
	}
	else
	{
		$options = 'maxlength=100';
	}

	$input_name = 'values[' . $table . ']' . ( $id ? '[' . $id . ']' : '' ) . '[' . $column . ']';

	if ( $value !== '---'
		&& count( (array) $select ) > 1 )
	{
		// When -Edit- option selected, change the Address auto pull-downs to text fields.
		$return = '';

		if ( AllowEdit()
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] )
			&& ! $js_included )
		{
			$js_included = true;

			ob_start();?>
			<script>
			function maybeEditTextInput(el) {

				// -Edit- option's value is ---.
				if ( el.value === '---' ) {

					var $el = $( el );

					// Remove parent <div> if any
					if ( $el.parent('div').length ) {
						$el.unwrap();
					}
					// Remove the select input.
					$el.remove();

					// Show & enable the text input of the same name.
					$( '[name="' + el.name + '_text"]' ).prop('name', el.name).prop('disabled', false).show().focus();
				}
			}
			</script>
			<?php $return = ob_get_clean();
		}

		if ( AllowEdit()
			&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			// Add hidden & disabled Text input in case user chooses -Edit-.
			$return .= TextInput(
				'',
				$input_name . '_text',
				'',
				$options . ' disabled style="display:none;"',
				false
			);
		}

		$na = 'N/A';

		if ( $table === 'people_join_contacts'
			&& $column === 'TITLE'
			&& $id !== 'new' )
		{
			$na = false;
		}

		$return .= SelectInput(
			$value,
			$input_name,
			$title,
			$select,
			$na,
			$select_options . ' onchange="maybeEditTextInput(this);"',
			$div
		);

		return $return;
	}
	else
	{
		// FJ new option.
		return TextInput(
			$value === '---' ? [ '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ] : $value,
			$input_name,
			$title,
			$options,
			$div
		);
	}
}
