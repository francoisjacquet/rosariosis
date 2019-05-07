<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

/*if ( ! UserStudentID() )
{
	$_SESSION['UserSyear'] = Config( 'SYEAR' );

	$student_RET = DBGet( "SELECT sju.STUDENT_ID,
		s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,se.SCHOOL_ID
		FROM STUDENTS s,STUDENTS_JOIN_USERS sju, STUDENT_ENROLLMENT se
		WHERE s.STUDENT_ID=sju.STUDENT_ID
		AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND se.SYEAR='" . UserSyear() . "'
		AND se.STUDENT_ID=sju.STUDENT_ID
		AND (('" . DBDate() . "' BETWEEN se.START_DATE AND se.END_DATE OR se.END_DATE IS NULL)
		AND '" . DBDate() . "'>=se.START_DATE)" );

	// Note: do not use SetUserStudentID() here as this is safe.
	$_SESSION['student_id'] = $student_RET[1]['STUDENT_ID'];
}*/


// Fix PHP error when no Student associated to Parent.
if ( ! UserStudentID() )
{
	$error[] = _( 'No Students were found.' );

	echo ErrorMessage( $error, 'fatal' );
}

// Allow Edit.
$_ROSARIO['allow_edit'] = true;

// Is Student or Parent?
$is_student = false;

if ( isset( $_SESSION['STUDENT_ID'] )
	&& $_SESSION['STUDENT_ID'] ) {

	$is_student = true;
}

// Add eventual Dates to $_REQUEST['students'].
AddRequestedDates( 'students' );

// FJ textarea fields MarkDown sanitize.
$_REQUEST['students'] = FilterCustomFieldsMarkdown( 'CUSTOM_FIELDS', 'students' );

if ( isset( $_REQUEST['values'] )
	&& $_REQUEST['values'] )
{
	$inserted_addresses = $address_id = array();

	// Save Address.
	foreach ( (array) $_REQUEST['values']['ADDRESS'] as $key => $columns )
	{
		$address_key = preg_replace( '/[^0-9A-Za-z]+/', '', mb_strtolower( $columns['ADDRESS'] ) );

		if ( $columns['ADDRESS']
			&& ! isset( $inserted_addresses[ $address_key ] ) )
		{
			$address_id[ $key ] = DBSeqNextID( 'ADDRESS_SEQ' );

			if ( $key == 1 )
			{
				// Parent.
				// FJ Add Mailing, Residence checked + Bus checked if configured.
				$students_join_address = array(
					'MAILING' => 'Y',
					'RESIDENCE' => 'Y',
					'BUS_PICKUP' => ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
					'BUS_DROPOFF' => ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
				);

				$address_id[2] = $address_id[ $key ];
			}
			else
			{
				// Grandparents and other contacts.
				$students_join_address = array(
					'MAILING' => '',
					'RESIDENCE' => '',
					'BUS_PICKUP' => '',
					'BUS_DROPOFF' => '',
				);
			}

			$sql = "INSERT INTO ADDRESS ";

			$fields = 'ADDRESS_ID,';

			$values = $address_id[ $key ] . ',';

			$columns += _prepareAddress( $columns['ADDRESS'] );

			// Remove phone formatting?
			// $columns['PHONE'] = mb_substr( preg_replace( '/[^0-9]+/', '', $columns['PHONE'] ), 0, 7 );

			unset( $address['ADDRESS'] );

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value )
					|| $value == '0' )
				{
					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";

					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
				DBQuery( $sql );

				DBQuery( "INSERT INTO STUDENTS_JOIN_ADDRESS (ID,STUDENT_ID,ADDRESS_ID,
					RESIDENCE,MAILING,BUS_PICKUP,BUS_DROPOFF)
					values(" . db_seq_nextval( 'STUDENTS_JOIN_ADDRESS_SEQ' ) . ",'" .
						UserStudentID() . "','" . $address_id[ $key ] . "','" .
						$students_join_address['MAILING'] . "','" .
						$students_join_address['RESIDENCE'] . "','" .
						$students_join_address['BUS_PICKUP'] . "','" .
						$students_join_address['BUS_DROPOFF'] . "')" );
			}

			$inserted_addresses[ $address_key ] = $address_id[ $key ];
		}
		else
		{
			// Already inserted address.
			$address_id[ $key ] = $inserted_addresses[ $address_key ];
		}
	}

	// Save Contacts.
	foreach ( (array) $_REQUEST['values']['PEOPLE'] as $key => $person )
	{
		if ( ! $person['FIRST_NAME']
			|| ! $person['LAST_NAME'] )
		{
			continue;
		}

		$person_id = DBSeqNextID( 'PEOPLE_SEQ' );

		foreach ( (array) $person['extra'] as $column => $value )
		{
			if ( ! empty( $value )
				|| $value == '0' )
			{
				$sql = "INSERT INTO PEOPLE_JOIN_CONTACTS ";

				$fields = 'ID,PERSON_ID,TITLE,VALUE,';

				$values = db_seq_nextval( 'PEOPLE_SEQ' ) . ",'" .
					$person_id . "','" . $column . "','" . $value . "',";

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				DBQuery( $sql );
			}
		}

		unset( $person['extra'] );

		$sql = "INSERT INTO PEOPLE ";

		$fields = 'PERSON_ID,';

		$values = "'" . $person_id . "',";

		$go = false;

		// Set Student Relation.
		if ( $key == 1
			|| $key == 2 )
		{
			$person_student_relation = _( 'Parent' );
		}
		elseif ( $key >= 3
			&& $key <= 6 )
		{
			$person_student_relation = _( 'Grandparent' );
		}
		else
		{
			// Others contacts.
			$person_student_relation = $person['STUDENT_RELATION'];
		}

		unset( $person['STUDENT_RELATION'] );

		foreach ( (array) $person as $column => $value )
		{
			if ( ! empty( $value )
				|| $value == '0' )
			{
				$fields .= $column . ',';

				$values .= "'" . $value . "',";

				$go = true;
			}
		}

		$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

		if ( $go )
		{
			DBQuery( $sql );

			if ( $key == 1
				|| $key == 2 )
			{
				DBQuery( "INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,CUSTODY,STUDENT_RELATION)
					values(" . db_seq_nextval( 'STUDENTS_JOIN_PEOPLE_SEQ' ) . ",'" .
						UserStudentID() . "','" . $person_id . "','" .
						$address_id[ $key ] . "','Y','" . $person_student_relation . "')" );
			}
			elseif ( isset( $address_id[ $key ] ) )
			{
				DBQuery( "INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,STUDENT_RELATION)
					values(" . db_seq_nextval( 'STUDENTS_JOIN_PEOPLE_SEQ' ) . ",'" .
						UserStudentID() . "','" . $person_id . "','" . $address_id[ $key ] . "','" . $person_student_relation . "')" );
			}
			else
			{
				// No address, use parent address.
				DBQuery( "INSERT INTO STUDENTS_JOIN_PEOPLE (ID,STUDENT_ID,PERSON_ID,ADDRESS_ID,EMERGENCY,STUDENT_RELATION)
					values(" . db_seq_nextval( 'STUDENTS_JOIN_PEOPLE_SEQ' ) . ",'" .
						UserStudentID() . "','" . $person_id . "','" . $address_id[1] . "','Y','" . $person_student_relation . "')" );
			}
		}
	}

	// Save Student Info.
	if ( ! empty( $_REQUEST['students'] ) )
	{
		$sql = "UPDATE STUDENTS SET ";

		foreach ( (array) $_REQUEST['students'] as $column_name => $value )
		{
			$sql .= $column_name . "='" . $value . "',";
		}

		$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . UserStudentID() . "'";

		DBQuery( $sql );
	}

	// Send New Registration email to Notify.
	if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
	{
		// FJ add SendEmail function.
		require_once 'ProgramFunctions/SendEmail.fnc.php';

		$student_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM STUDENTS
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		$message = sprintf(
			_( 'New Registration %s (%d) has been registered by %s.' ),
			$student_RET[1]['FULL_NAME'],
			UserStudentID(),
			User( 'NAME' )
		);

		SendEmail( $RosarioNotifyAddress, _( 'New Registration' ), $message );
	}

	// Unset Student & values.
	$_SESSION['_REQUEST_vars']['students'] = false;
	$_SESSION['_REQUEST_vars']['values'] = false;
}

$addresses_RET = DBGet( "SELECT COUNT(*) AS COUNT
	FROM STUDENTS_JOIN_ADDRESS
	WHERE STUDENT_ID='" . UserStudentID() . "'" );

// Registration check.
if ( $addresses_RET[1]['COUNT'] > 0 )
{
	$note[] = button( 'check', '', '', 'bigger' ) . ' ' .
		( $is_student ?
			_( 'Your parents have been registered.' ) :
			_( 'Your child has been registered.' ) );

	echo ErrorMessage( $note, 'note' );

	// Exit.
	Warehouse( 'footer' );

	exit;
}

DrawHeader( sprintf(
	_( 'Welcome, %s, to the %s' ),
	User( 'NAME' ),
	ParseMLField( Config( 'TITLE' ) )
) );

DrawHeader(
	sprintf(
		_( 'We would appreciate it if you would enter just a little bit of information about you and %s to help us out this school year. Thanks!' ),
		$is_student ? _( 'your parents' ) : _( 'your child' )
	)
);

echo '<br /><br />';

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

echo PopTable( 'header', _( 'Registration' ) );

echo '<table class="cellpadding-5"><tr class="st"><td>';

echo '<p><b>' . ( $is_student ? _( 'Information about your parents' ) : _( 'Information about you' ) ) .
	':</b><br /></p><br />';

echo '<table class="width-100p valign-top fixed-col"><tr><td>';

// Parent fields.
// First name & last name required.
echo _makeInput( 'values[PEOPLE][1][FIRST_NAME]', _( 'First Name' ), '', 'size="15" maxlength="50" required' );

echo '<br />' . _makeInput( 'values[PEOPLE][1][MIDDLE_NAME]', _( 'Middle Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][1][LAST_NAME]', _( 'Last Name' ), '', 'size="15" maxlength="50" required' );

echo '<br />' . _makeInput( 'values[PEOPLE][1][extra][Cell]', _( 'Cell Phone' ), '', 'size="30"' );

echo '<br />' . _makeInput( 'values[PEOPLE][1][extra][Workplace]', _( 'Workplace' ), '', 'size="30"' );

echo '</td></tr></table>';

echo '</td><td>';

// Spouse fields.
echo '<p><b>' . _( 'Information about spouse or significant other' ) . ':</b><br />' .
	_( 'Leave this section blank if separated.' ) . '</p>';

echo '<table><tr><td>';

echo _makeInput( 'values[PEOPLE][2][FIRST_NAME]', _( 'First Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][2][MIDDLE_NAME]', _( 'Middle Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][2][LAST_NAME]', _( 'Last Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][2][extra][Cell]', _( 'Cell Phone' ), '', 'size="30"' );

echo '<br />' . _makeInput( 'values[PEOPLE][2][extra][Workplace]', _( 'Workplace' ), '', 'size="30"' );

echo '</td></tr></table>';

echo '</td></tr><tr><td colspan="2">';

// Address.
echo '<hr /><p><b>' . _( 'Your Address' ) . ':</b></p>';

echo '<table class="width-100p valign-top fixed-col"><tr><td>';

// Parent street required.
echo _makeInput( 'values[ADDRESS][1][ADDRESS]', _( 'Street' ), '', 'size="30" maxlength="255" required' );

echo '<br /><table class="cellspacing-0"><tr><td>' .
	_makeInput( 'values[ADDRESS][1][CITY]', _( 'City' ), '', 'size="15" maxlength="60"' ) . '</td>';

echo '<td>' .
	_makeInput( 'values[ADDRESS][1][STATE]', _( 'State' ), '', 'size="3" maxlength=2' ) . '</td>';

echo '<td>' . _makeInput( 'values[ADDRESS][1][ZIPCODE]', _( 'Zip' ), '', 'size="6" maxlength="10"' ) .
	'</td></tr></table>';

echo _makeInput( 'values[ADDRESS][1][PHONE]', _( 'Phone' ), '', 'size="15" maxlength="30"' );

echo '</td></tr></table>';

// Grandparents (4).
echo '<hr /><table class="width-100p valign-top fixed-col">';

for ( $i = 3; $i <= 6; $i++ )
{
	if ( $i == 3
		|| $i == 5 )
	{
		echo '<tr class="st">';
	}

	echo '<td><p><b>' . _( 'Grandparent Information' ) . ':</b></p>';

	echo _makeInput( 'values[PEOPLE][' . $i . '][FIRST_NAME]', _( 'First Name' ), '', 'size="15" maxlength="50"' );

	echo '<br />' . _makeInput( 'values[PEOPLE][' . $i . '][MIDDLE_NAME]', _( 'Middle Name' ), '', 'size="15" maxlength="50"' );

	echo '<br />' . _makeInput( 'values[PEOPLE][' . $i . '][LAST_NAME]', _( 'Last Name' ), '', 'size="15" maxlength="50"' );

	echo '<br />' . _makeInput( 'values[PEOPLE][' . $i . '][extra][Cell]', _( 'Cell Phone' ), '', 'size="30"' );

	echo '<br />' . _makeInput( 'values[ADDRESS][' . $i . '][ADDRESS]', _( 'Address' ), '', 'size="30" maxlength="255"' );

	echo '<br /><table class="cellspacing-0"><tr><td>' .
		_makeInput( 'values[ADDRESS][' . $i . '][CITY]', _( 'City' ), '', 'size="15" maxlength="60"' ) . '</td>';

	echo '<td>' . _makeInput( 'values[ADDRESS][' . $i . '][STATE]', _( 'State' ), '', 'size="3" maxlength="2"' ) . '</td>';

	echo '<td>' . _makeInput( 'values[ADDRESS][' . $i . '][ZIPCODE]', _( 'Zip' ), '', 'size="6" maxlength="10"' ) .
		'</td></tr></table>';

	echo _makeInput( 'values[ADDRESS][' . $i . '][PHONE]', _( 'Phone' ), '', 'size="15" maxlength="30"' );

	if ( $i == 4 )
	{
		echo '<br />';
	}

	echo '</td>';

	if ( $i == 4
		|| $i == 6 )
	{
		echo '</tr>';
	}
}

echo '</table>';

// Other contacts.
echo '<hr /><p><b>' . _( 'Other Contacts' ) . ':</b></p>';

echo '<table class="width-100p valign-top fixed-col"><tr class="st"><td>';

echo _makeInput( 'values[PEOPLE][7][FIRST_NAME]', _( 'First Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][7][MIDDLE_NAME]', _( 'Middle Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][7][LAST_NAME]', _( 'Last Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][7][STUDENT_RELATION]', _( 'Relation to Student' ), '', 'size="30"' );

echo '<br />' . _makeInput( 'values[PEOPLE][7][extra][Cell]', _( 'Cell Phone' ), '', 'size="30"' );

echo '</td><td>';

echo _makeInput( 'values[PEOPLE][8][FIRST_NAME]', _( 'First Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][8][MIDDLE_NAME]', _( 'Middle Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][8][LAST_NAME]', _( 'Last Name' ), '', 'size="15" maxlength="50"' );

echo '<br />' . _makeInput( 'values[PEOPLE][8][STUDENT_RELATION]', _( 'Relation to Student' ), '', 'size="30"' );

echo '<br />' . _makeInput( 'values[PEOPLE][8][extra][Cell]', _( 'Cell Phone' ), '', 'size="30"' );

echo '</td></tr></table>';

$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS
	FROM CUSTOM_FIELDS", array(), array( 'ID' ) );

$student_dataquery = '';

/*if ( isset( $custom_fields_RET['200000000'] )
	&& $custom_fields_RET['200000000'][1]['TYPE'] === 'select' )
{
	$student_dataquery .= ', CUSTOM_200000000';
}

if ( isset( $custom_fields_RET['200000001'] )
	&& $custom_fields_RET['200000001'][1]['TYPE'] === 'select' )
{
	$student_dataquery .= ', CUSTOM_200000000';
}

if ( isset( $custom_fields_RET['200000003'] ) )
{
	$student_dataquery .= ', CUSTOM_200000003';
}

if ( isset( $custom_fields_RET['200000004'] )
	&& $custom_fields_RET['200000004'][1]['TYPE'] === 'date' )
{
	$student_dataquery .= ', CUSTOM_200000004';
}

if ( isset( $custom_fields_RET['200000005'] )
	&& $custom_fields_RET['200000005'][1]['TYPE'] === 'select' )
{
	$student_dataquery .= ', CUSTOM_200000005';
}*/

if ( isset( $custom_fields_RET['200000006'] ) )
{
	$student_dataquery .= ', CUSTOM_200000006';
}

if ( isset( $custom_fields_RET['200000007'] ) )
{
	$student_dataquery .= ', CUSTOM_200000007';
}

if ( isset( $custom_fields_RET['200000008'] ) )
{
	$student_dataquery .= ', CUSTOM_200000008';
}

if ( isset( $custom_fields_RET['200000009'] )
	&& $custom_fields_RET['200000009'][1]['TYPE'] === 'textarea' )
{
	$student_dataquery .= ', CUSTOM_200000009';
}

$student_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME" . $student_dataquery . "
	FROM STUDENTS
	WHERE STUDENT_ID='" . UserStudentID() . "'" );

$student = $student_RET[1];

echo '<hr /><p><b>' . sprintf(
	_( 'Information about %s' ),
	$student['FULL_NAME']
) . ':</b></p>';

// Display General Info's tab custom fields (Other Info).
$_REQUEST['category_id'] = '1';
$separator = '';

include 'modules/Students/includes/Other_Info.inc.php';


/*echo '<table class="width-100p valign-top fixed-col"><tr class="st"><td>';

// Birthdate.
if ( array_key_exists( 'CUSTOM_200000004', $student ) )
{
	echo DateInput(
		$student['CUSTOM_200000004'],
		'CUSTOM_200000004',
		ParseMLField( $custom_fields_RET['200000004'][1]['TITLE'] )
	);
}

echo '</td><td>';

// Social Security.
if ( array_key_exists( 'CUSTOM_200000003', $student ) )
{
	echo _makeInput(
		'students[CUSTOM_200000003]',
		ParseMLField( $custom_fields_RET['200000003'][1]['TITLE'] ),
		$student['CUSTOM_200000003']
	);
}

echo '</td></tr><tr class="st"><td>';

// Ethnicity.
if ( array_key_exists( 'CUSTOM_200000001', $student ) )
{
	$select_options = array();

	$select_options_array = explode(
		"\r",
		str_replace( array( "\r\n", "\n" ), "\r", $custom_fields_RET['200000001'][1]['SELECT_OPTIONS'] )
	);

	foreach ( (array) $select_options_array as $select_option )
	{
		$select_options[ $select_option ] = $select_option;
	}

	echo SelectInput(
		$student['CUSTOM_200000001'],
		'students[CUSTOM_200000001]',
		ParseMLField( $custom_fields_RET['200000001'][1]['TITLE'] ),
		$select_options
	);
}

echo '</td><td>';

// Language.
if ( array_key_exists( 'CUSTOM_200000005', $student ) )
{
	$select_options = array();

	$select_options_array = explode(
		"\r",
		str_replace( array( "\r\n", "\n" ), "\r", $custom_fields_RET['200000005'][1]['SELECT_OPTIONS'] )
	);

	foreach ( (array) $select_options_array as $select_option )
	{
		$select_options[ $select_option ] = $select_option;
	}

	echo SelectInput(
		$student['CUSTOM_200000005'],
		'students[CUSTOM_200000005]',
		ParseMLField( $custom_fields_RET['200000005'][1]['TITLE'] ),
		$select_options,
		'N/A',
		'style="width:200"'
	);
}

echo '</td></tr><tr><td colspan="2">';

// Gender.
if ( array_key_exists( 'CUSTOM_200000000', $student ) )
{
	$select_options = array();

	$select_options_array = explode(
		"\r",
		str_replace( array( "\r\n", "\n" ), "\r", $custom_fields_RET['200000000'][1]['SELECT_OPTIONS'] )
	);

	foreach ( (array) $select_options_array as $select_option )
	{
		$select_options[ $select_option ] = $select_option;
	}

	echo SelectInput(
		$student['CUSTOM_200000000'],
		'students[CUSTOM_200000000]',
		ParseMLField( $custom_fields_RET['200000000'][1]['TITLE'] ),
		$select_options
	);
}

echo '</td></tr></table>';*/

// Medical.
$medical_fields_category_RET = DBGet( "SELECT TITLE
	FROM STUDENT_FIELD_CATEGORIES
	WHERE ID='2'" );

if ( isset( $medical_fields_category_RET[1]['TITLE'] ) )
{
	echo '<b>' . ParseMLField( $medical_fields_category_RET[1]['TITLE'] ) . ':</b>';
}

echo '<table class="width-100p valign-top fixed-col"><tr class="st"><td>';

// Physician.
if ( array_key_exists( 'CUSTOM_200000006', $student ) )
{
	echo '<br />' . _makeInput(
		'students[CUSTOM_200000006]',
		ParseMLField( $custom_fields_RET['200000006'][1]['TITLE'] ),
		$student['CUSTOM_200000006'],
		'size="30" maxlength="1000"'
	);
}

echo '</td><td>';

// Physician Phone.
if ( array_key_exists( 'CUSTOM_200000007', $student ) )
{
	echo '<br />' . _makeInput(
		'students[CUSTOM_200000007]',
		ParseMLField( $custom_fields_RET['200000007'][1]['TITLE'] ),
		$student['CUSTOM_200000007'],
		'size="15" maxlength="1000"'
	);
}

echo '</td></tr><tr class="st"><td>';

// Preferred Hospital.
if ( array_key_exists( 'CUSTOM_200000008', $student ) )
{
	echo '<br />' . _makeInput(
		'students[CUSTOM_200000008]',
		ParseMLField( $custom_fields_RET['200000008'][1]['TITLE'] ),
		$student['CUSTOM_200000008'],
		'size="30" maxlength="1000"'
	);
}

echo '</td><td>';

// Medical Comments.
if ( array_key_exists( 'CUSTOM_200000009', $student ) )
{
	echo '<br />' . TextAreaInput(
		$student['CUSTOM_200000009'],
		'students[CUSTOM_200000009]',
		ParseMLField( $custom_fields_RET['200000009'][1]['TITLE'] )
	);

	// echo '<br /><textarea name=students[CUSTOM_200000009] cols=26 rows=5 style="color: BBBBBB;" onfocus=\'if (this.value=="Medical Comments") this.value=""; this.style.color="000000";\' onblur=\'if (this.value=="") {this.value="Medical Comments"; this.style.color="BBBBBB";}\'">'.ParseMLField($custom_fields_RET['200000009'][1]['TITLE']).'</textarea>';
}

echo '</td></tr></table>';
echo '</td></tr></table>';


echo PopTable( 'footer' );

echo '<br /><div class="center">' . SubmitButton() . '</div></form>';


function _makeInput( $name, $title, $value = '', $extra = '' )
{
	return TextInput( $value, $name, $title, $extra );

	// return '<input type="text" name="'.$name.'" value="'.$title.'" style="color:	BBBBBB" onfocus=\'if (this.value=="'.$title.'") this.value=""; this.style.color="000000"\' onsubmit=\'if (this.value=="'.$title.'") this.value=""; this.style.color="000000"\' onblur=\'if (this.value=="") {this.value="'.$title.'"; this.style.color="BBBBBB"}\' '.$extra.' />';
}

function _prepareAddress( $temp )
{
	$address = array();

	preg_match( '/^[0-9]+/', $temp, $regs );

	$temp = preg_replace( '/^[0-9]+ /', '', $temp );

	if ( $regs[0]
		&& mb_strlen( $regs[0] ) < 6 ) // Fix SQL error field type numeric(5,0).
	{
		$address['HOUSE_NO'] = $regs[0];
	}

	// Extract US Address Direction & Street.
	$temp_dir = mb_strtoupper( str_replace( '.', ' ', mb_substr( $temp, 0, 2 ) ) );

	if ( $temp_dir == 'W '
		|| $temp_dir == 'E '
		|| $temp_dir == 'N '
		|| $temp_dir == 'S ' )
	{
		$address['DIRECTION'] = mb_substr( $temp, 0, 1 );

		$address['STREET'] = mb_substr( $temp, 2 );
	}
	elseif ( $temp_dir == 'NO'
		|| $temp_dir == 'SO'
		|| $temp_dir == 'WE'
		|| $temp_dir == 'EA' )
	{
		$temp_dir = str_replace(
			'.',
			'',
			mb_strtoupper( mb_substr( $temp, 0, mb_strpos( $temp, ' ' ) ) )
		);

		switch ( $temp_dir )
		{
			case 'NORTH':

				$address['DIRECTION'] = 'N';

				$address['STREET'] = mb_substr( $temp, mb_strpos( $temp, ' ' ) );

			break;

			case 'SOUTH':

				$address['DIRECTION'] = 'S';

				$address['STREET'] = mb_substr( $temp, mb_strpos( $temp,' ' ) );

			break;

			case 'EAST':

				$address['DIRECTION'] = 'E';

				$address['STREET'] = mb_substr( $temp, mb_strpos( $temp, ' ' ) );

			break;

			case 'WEST':

				$address['DIRECTION'] = 'W';

				$address['STREET'] = mb_substr( $temp, mb_strpos( $temp, ' ' ) );

			break;

			default:

				$address['STREET'] = $temp;

			break;
		}

		$address['STREET'] = trim( $address['STREET'] );
	}
	else
	{
		$address['STREET'] = $temp;
	}

	// Fix SQL error: STREET column is max 30 chars long.
	$address['STREET'] = mb_substr( $address['STREET'], 0, 30 );

	return $address;
}
