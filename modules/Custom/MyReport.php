<?php

DrawHeader( ProgramTitle() );

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
			FROM custom_fields
			WHERE ID IN (200000000, 200000001)" );

		$address_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
			FROM address_fields" );

		$people_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
			FROM people_fields" );

		$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

		foreach ( (array) $custom_fields_RET as $field )
		{
			$extra['SELECT'] .= ",s.CUSTOM_" . $field['ID'];
		}

		$extra['SELECT'] .= ",adr.ADDRESS,adr.CITY,adr.STATE,adr.ZIPCODE,adr.MAIL_ADDRESS,adr.MAIL_CITY,
			adr.MAIL_STATE,adr.MAIL_ZIPCODE,adr.PHONE";

		foreach ( (array) $address_fields_RET as $field )
		{
			$extra['SELECT'] .= ",adr.CUSTOM_" . $field['ID'] . " AS ADDRESS_" . $field['ID'];
		}

		$extra['SELECT'] .= ",p.PERSON_ID,CONCAT(p.FIRST_NAME, ' ', p.LAST_NAME) AS PERSON_NAME";

		foreach ( (array) $people_fields_RET as $field )
		{
			$extra['SELECT'] .= ",p.CUSTOM_" . $field['ID'] . " AS PEOPLE_" . $field['ID'];
		}

		$extra['functions'] = [];

		$maxTV = 0;

		for ( $i = 1; $i <= 10; $i++ )
		{
			$extra['SELECT'] .= ",NULL AS TITLE_" . $i . ",NULL AS VALUE_" . $i;

			$extra['functions'] += [ 'TITLE_' . $i => '_makeTV', 'VALUE_' . $i => '_makeTV' ];
		}

		$extra['FROM'] = " LEFT OUTER JOIN students_join_address sja
				ON (sja.STUDENT_ID=ssm.STUDENT_ID AND sja.ADDRESS_ID!='0')
			LEFT OUTER JOIN address a
				ON (adr.ADDRESS_ID=sja.ADDRESS_ID)";

		$extra['FROM'] = " LEFT OUTER JOIN students_join_address sja
				ON (sja.STUDENT_ID=ssm.STUDENT_ID)
			LEFT OUTER JOIN address adr
				ON (adr.ADDRESS_ID=sja.ADDRESS_ID)";

		$extra['FROM'] .= " LEFT OUTER JOIN students_join_people sjp
				ON (sjp.STUDENT_ID=ssm.STUDENT_ID AND sjp.ADDRESS_ID=adr.ADDRESS_ID)
			LEFT OUTER JOIN people p
				ON (p.PERSON_ID=sjp.PERSON_ID)";

		//$extra['WHERE'] = " AND (adr.ADDRESS_ID IS NULL OR adr.ADDRESS_ID=sja.ADDRESS_ID)";

		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID
				FROM students_join_address
				WHERE STUDENT_ID=ssm.STUDENT_ID
				AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";

			$extra['group'] = $LO_group = [ 'FAMILY_ID', 'STUDENT_ID' ];

			//$LO_group = array(array('FAMILY_ID','STUDENT_ID'));

			$LO_columns = [ 'FAMILY_ID' => _( 'Address ID' ) ];

			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => '' ] ) . '">' .
					_( 'Ungroup by Family' ) . '</a>';
			}
		}
		else
		{
			$extra['group'] = $LO_group = ['STUDENT_ID'];

			$LO_columns = [];

			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => 'Y' ] ) . '">' .
					_( 'Group by Family' ) . '</a>';
			}
		}

		$LO_columns += [
			'FULL_NAME' => _( 'Student' ),
			'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'GRADE_ID' => _( 'Grade Level' ),
		];

		foreach ( (array) $custom_fields_RET as $field )
		{
			$LO_columns += [ 'CUSTOM_' . $field['ID'] => _makeFieldTitle( $field['TITLE'] ) ];
		}

		$LO_columns += [
			'ADDRESS' => _( 'Street' ),
			'CITY' => _( 'City' ),
			'STATE' => _( 'State' ),
			'ZIPCODE' => _( 'Zip Code' ),
			'PHONE' => _( 'Phone' ),
		];

		$extra['functions']['PHONE'] = 'makePhone';

		// FJ disable mailing address display.
		if ( Config( 'STUDENTS_USE_MAILING' ) )
		{
			$LO_columns += [
				'MAIL_ADDRESS' => _( 'Mailing Street' ),
				'MAIL_CITY' => _( 'Mailing City' ),
				'MAIL_STATE' => _( 'Mailing State' ),
				'MAIL_ZIPCODE' => _( 'Mailing Zipcode' ),
			];
		}

		foreach ( (array) $address_fields_RET as $field )
		{
			$field_key = 'ADDRESS_' . $field['ID'];

			$extra['functions'][ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );

			$LO_columns[ $field_key ] = _makeFieldTitle( $field['TITLE'] );
		}

		$LO_columns += [ 'PERSON_NAME' => _( 'Person Name' ) ];

		foreach ( (array) $people_fields_RET as $field )
		{
			$field_key = 'PEOPLE_' . $field['ID'];

			$extra['functions'][ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );

			$LO_columns[ $field_key ] = _makeFieldTitle( $field['TITLE'] );
		}

		$students_RET = GetStuList( $extra );

		for ( $i = 1; $i <= $maxTV; $i++ )
		{
			$LO_columns += [
				'TITLE_' . $i => _( 'Title' ) . ' ' . $i,
				'VALUE_' . $i => _( 'Value' ) . ' ' . $i,
			];
		}

		DrawHeader( $header_left );

		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );

		if ( empty( $_REQUEST['LO_save'] ) )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'bottom_back' ] );

			if ( $_SESSION['Back_PHP_SELF'] !== 'student' )
			{
				$_SESSION['Back_PHP_SELF'] = 'student';

				unset( $_SESSION['Search_PHP_SELF'] );
			}

			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		ListOutput( $students_RET, $LO_columns, 'Student', 'Students', false, $LO_group );
	}
	else
	{
		$extra['new'] = true;

		Search( 'student_id', $extra );
	}
}

function _makeTV( $value, $column )
{
	global $maxTV,
		$THIS_RET,
		$person_id,
		$person_RET;

	if ( isset( $THIS_RET['PERSON_ID'] )
		&& $THIS_RET['PERSON_ID'] !== $person_id )
	{
		$person_RET = DBGet( "SELECT TITLE,VALUE
			FROM people_join_contacts
			WHERE PERSON_ID='" . (int) $THIS_RET['PERSON_ID'] . "'
			LIMIT 10" );

		if ( count( (array) $person_RET ) > $maxTV )
		{
			$maxTV = count( $person_RET );
		}

		$person_id = $THIS_RET['PERSON_ID'];
		//echo '<pre>'; var_dump($person_RET); echo '</pre>';
	}

	$tv = mb_substr( $column, 0, 5 );
	$i = mb_substr( $column, 6 );

	return isset( $person_RET[ $i ][ $tv ] ) ? $person_RET[ $i ][ $tv ] : null;
}

/**
 * Make (Student, Contact, Address) Field Title
 * Parse Multi-lingual value
 * Truncate column title to 36 chars if > 36 chars
 *
 * Local function.
 *
 * @since 11.0
 *
 * @param  string $value  Title value.
 * @param  string $column Column. Defaults to ''.
 *
 * @return string         Title truncated to 36 chars.
 */
function _makeFieldTitle( $value, $column = '' )
{
	$field_title = ParseMLField( $value );

	// Truncate file name if > 36 chars.
	$field_title_display = mb_strlen( $field_title ) <= 36 ?
		$field_title :
		'<span title="' . AttrEscape( $field_title ) . '">' . mb_substr( $field_title, 0, 33 ) . '...</span>';

	return $field_title_display;
}
