<?php
require_once 'ProgramFunctions/miscExport.fnc.php';

//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';

$extra['extra_search'] = issetVal( $extra['extra_search'], '' );
$extra['extra_search'] .= '<tr>
		<td></td>
		<td><div id="fields_div"></div></td>
	</tr>';

$extra['extra_search'] .= '<tr>
		<td></td>
		<td>
			<input type="hidden" name="relation" />
			<input type="hidden" name="residence" />
			<input type="hidden" name="mailing" />
			<input type="hidden" name="bus_pickup" />
			<input type="hidden" name="bus_dropoff" />
		</td>
	</tr>';

$extra['extra_search'] .= '<script>
	function exportSubmit() {
		document.search.relation.value=document.getElementById("relation").value;
		document.search.residence.value=document.getElementById("residence").checked;
		document.search.mailing.value=document.getElementById("mailing").checked;
		document.search.bus_pickup.value=document.getElementById("bus_pickup").checked;
		document.search.bus_dropoff.value=document.getElementById("bus_dropoff").checked;
	}

	$("form[name=search]").submit(exportSubmit);
</script>';

$extra['new'] = true;

$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
$extra['FROM'] = issetVal( $extra['FROM'], '' );
$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

$_ROSARIO['CustomFields'] = true;

// Has Address Custom Field.
$has_address_custom_field = false;

if ( isset( $_REQUEST['fields'] ) )
{
	foreach ( (array) $_REQUEST['fields'] as $fields_index => $fields_val )
	{
		if ( mb_strpos( $fields_index, 'ADDRESS_' ) !== false )
		{
			$has_address_custom_field = true;

			break;
		}
	}
}

if ( ! empty( $_REQUEST['fields'] )
	&& ( $has_address_custom_field
		|| ! empty( $_REQUEST['fields']['CITY'] )
		|| ! empty( $_REQUEST['fields']['STATE'] )
		|| ! empty( $_REQUEST['fields']['ZIPCODE'] )
		|| ! empty( $_REQUEST['fields']['PHONE'] )
		|| ! empty( $_REQUEST['fields']['MAIL_ADDRESS'] )
		|| ! empty( $_REQUEST['fields']['MAIL_CITY'] )
		|| ! empty( $_REQUEST['fields']['MAIL_STATE'] )
		|| ! empty( $_REQUEST['fields']['MAIL_ZIPCODE'] )
		|| ! empty( $_REQUEST['fields']['PARENTS'] ) ) )
{
	$extra['SELECT'] .= ',a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,' .
		db_case( [ 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_ADDRESS,a.ADDRESS)', 'NULL' ] ) . ' AS MAIL_ADDRESS,' .
		db_case( [ 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_CITY,a.CITY)', 'NULL' ] ) . ' AS MAIL_CITY,' .
		db_case( [ 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_STATE,a.STATE)', 'NULL' ] ) . ' AS MAIL_STATE,' .
		db_case( [ 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_ZIPCODE,a.ZIPCODE)', 'NULL' ] ) . ' AS MAIL_ZIPCODE';

	$extra['addr'] = true;

	if ( $_REQUEST['residence'] != 'false'
		|| $_REQUEST['mailing'] != 'false'
		|| $_REQUEST['bus_pickup'] != 'false'
		|| $_REQUEST['bus_dropoff'] != 'false' )
	{
		$extra['STUDENTS_JOIN_ADDRESS'] = issetVal( $extra['STUDENTS_JOIN_ADDRESS'], '' );

		$extra['STUDENTS_JOIN_ADDRESS'] .= ' AND (';

		if ( $_REQUEST['residence'] != 'false' )
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.RESIDENCE='Y' OR ";

		if ( $_REQUEST['mailing'] != 'false' )
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.MAILING='Y' OR ";

		if ( $_REQUEST['bus_pickup'] != 'false' )
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_PICKUP='Y' OR ";

		if ( $_REQUEST['bus_dropoff'] != 'false' )
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_DROPOFF='Y' OR ";

		$extra['STUDENTS_JOIN_ADDRESS'] .= 'FALSE)';
	}
	elseif ( empty( $_REQUEST['fields']['PARENTS'] ) )
	{
		$extra['STUDENTS_JOIN_ADDRESS'] = issetVal( $extra['STUDENTS_JOIN_ADDRESS'], '' );

		// SQL skip "No Address" contacts to avoid lines with empty Address fields.
		$extra['STUDENTS_JOIN_ADDRESS'] .= " AND sam.ADDRESS_ID<>'0'";
	}


	if ( ! empty( $_REQUEST['fields']['PARENTS'] ) )
	{
		$extra['SELECT'] .= ',ssm.STUDENT_ID AS PARENTS';

		$view_other_RET['ALL_CONTACTS'][1]['VALUE'] = 'Y';

		//FJ PrintClassLists with all contacts
		/*if ( $_REQUEST['relation']!='')
		{*/
			$_ROSARIO['makeParents'] = issetVal( $_REQUEST['relation'] );
			/*$extra['STUDENTS_JOIN_ADDRESS'] .= " AND EXISTS (SELECT '' FROM students_join_people sjp WHERE sjp.ADDRESS_ID=sam.ADDRESS_ID AND ".($_REQUEST['relation']!='!'?"lower(sjp.STUDENT_RELATION) LIKE '".mb_strtolower($_REQUEST['relation'])."%'":"sjp.STUDENT_RELATION IS NULL").") ";
		}*/
	}
}

$extra['SELECT'] .= ",ssm.NEXT_SCHOOL,ssm.CALENDAR_ID,ssm.SYEAR,
	(SELECT sch.SCHOOL_NUMBER
		FROM schools sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_NUMBER"; // Fix PHP error removed s.*.

if ( ! empty( $_REQUEST['fields']['FIRST_INIT'] ) )
{
	$extra['SELECT'] .= ',SUBSTR(s.FIRST_NAME,1,1) AS FIRST_INIT';
}

if ( ! empty( $_REQUEST['fields']['USERNAME'] ) )
{
	$extra['SELECT'] .= ",s.USERNAME";
}

if ( ! empty( $_REQUEST['fields']['LAST_LOGIN'] ) )
{
	$extra['SELECT'] .= ",s.LAST_LOGIN";
}

// School Title.
if ( ! empty( $_REQUEST['fields']['SCHOOL_TITLE'] ) )
{
	$extra['SELECT'] .= ",(SELECT sch.TITLE FROM schools sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
}



if ( empty( $extra['functions'] ) )
{
	$extra['functions'] = [
		'NEXT_SCHOOL' => '_makeNextSchool',
		'CALENDAR_ID' => '_makeCalendar',
		'PARENTS' => 'makeParents',
		'LAST_LOGIN' => 'makeLogin',
	];
}

// Generate Report.
if ( isset( $_REQUEST['search_modfunc'] )
	&& $_REQUEST['search_modfunc'] === 'list' )
{
	if ( empty( $_REQUEST['fields'] ) )
		if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
			BackPrompt( _( 'You must choose at least one field' ) );
		else
			echo ErrorMessage( [ _( 'You must choose at least one field' ) ], 'fatal' );

	if ( empty( $fields_list ) )
	{
		$fields_list = [
			'FULL_NAME' => _( 'Display Name' ),
			'FIRST_NAME' => _( 'First Name' ),
			'FIRST_INIT' => _( 'First Name Initial' ),
			'LAST_NAME' => _( 'Last Name' ),
			'MIDDLE_NAME' => _( 'Middle Name' ),
			'NAME_SUFFIX' => _( 'Suffix' ),
			'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'GRADE_ID' => _( 'Grade Level' ),
			'SCHOOL_TITLE' => _( 'School' ),
			'SCHOOL_NUMBER' => _( 'School Number' ),
			'NEXT_SCHOOL' => _( 'Rolling / Retention Options' ),
			'CALENDAR_ID' => _( 'Calendar' ),
			'USERNAME' => _( 'Username' ),
			'START_DATE' => _( 'Enrollment Start Date' ),
			'END_DATE' => _( 'Enrollment End Date' ),
			'ENROLLMENT_SHORT' => _( 'Enrollment Code' ),
			'DROP_SHORT' => _( 'Drop Code' ),
			'ADDRESS' => _( 'Street' ),
			'CITY' => _( 'City' ),
			'STATE' => _( 'State' ),
			'ZIPCODE' => _( 'Zip Code' ),
			'PHONE' => _( 'Home Phone' ),
			'PARENTS' => _( 'Contacts' ),
			'LAST_LOGIN'=> _( 'Last Login' ),
		];

		//FJ disable mailing address display
		if ( Config( 'STUDENTS_USE_MAILING' ) )
		{
			$fields_list += [
				'MAIL_ADDRESS' => _( 'Mailing Address' ),
				'MAIL_CITY' => _( 'Mailing City' ),
				'MAIL_STATE' => _( 'Mailing State' ),
				'MAIL_ZIPCODE' => _( 'Mailing Zipcode' ),
			];
		}

		if ( ! empty( $extra['field_names'] ) )
		{
			$fields_list += $extra['field_names'];
		}

		$fields_list['PERIOD_ATTENDANCE'] = _( 'Teacher' );

		$periods_RET = DBGet( "SELECT TITLE,PERIOD_ID
			FROM school_periods
			WHERE SYEAR='".UserSyear()."'
			AND SCHOOL_ID='".UserSchool()."'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
		{
			$fields_list['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'] . ' ' . _( 'Teacher' ) .' - ' . _( 'Room' );
		}
	}

	$custom_RET = DBGet( "SELECT TITLE,ID,TYPE
		FROM custom_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [], [ 'ID' ] );

	foreach ( (array) $custom_RET as $id => $field )
	{
		if ( ! empty( $_REQUEST['fields'][ 'CUSTOM_' . $id ] ) )
		{
			if ( empty( $fields_list[ 'CUSTOM_' . $id ] ) )
			{
				$fields_list[ 'CUSTOM_' . $id ] = $field[1]['TITLE'];
			}

			// Fix PHP error removed s.*, select each student field.
			$extra['SELECT'] .= ',s.CUSTOM_'  . $id;
		}
	}

	$address_RET = DBGet( "SELECT TITLE,ID,TYPE
		FROM address_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [], [ 'ID' ] );

	foreach ( (array) $address_RET as $id => $field )
	{
		$fields_list[ 'ADDRESS_' . $id ] = $field[1]['TITLE'];

		if ( ! empty( $_REQUEST['fields'][ 'ADDRESS_' . $id ] ) )
		{
			$extra['SELECT'] .= ',a.CUSTOM_' . $id . ' AS ADDRESS_' . $id;
			$extra['addr'] = true;
		}
	}

	if ( ! empty( $_REQUEST['fields']['START_DATE'] )
		|| ! empty( $_REQUEST['fields']['END_DATE'] )
		|| ! empty( $_REQUEST['fields']['ENROLLMENT_SHORT'] )
		|| ! empty( $_REQUEST['fields']['DROP_SHORT'] ) )
	{
			//FJ bugfix SQL error: more than one row returned by a subquery used as an expression
			$extra['SELECT'] .= ',xse.START_DATE, xse.END_DATE,
				(SELECT short_name
					FROM student_enrollment_codes
					WHERE id = xse.enrollment_code
					AND syear = xse.syear
					AND xse.STUDENT_ID=s.STUDENT_ID
					LIMIT 1) as enrollment_short,
				(SELECT short_name
					FROM student_enrollment_codes
					WHERE id = xse.drop_code
					AND syear = xse.syear
					AND xse.STUDENT_ID=s.STUDENT_ID
					LIMIT 1) as drop_short';

        	$extra['FROM'] .= ',student_enrollment xse';

			$extra['WHERE'] .= " AND xse.STUDENT_ID=s.STUDENT_ID AND xse.SYEAR='" . UserSyear() . "'";

			$extra['functions']['START_DATE'] = 'ProperDate';
			$extra['functions']['END_DATE'] = 'ProperDate';
	}

	if ( ! empty( $_REQUEST['month_include_active_date'] ) )
		$date = $_REQUEST['day_include_active_date'] . '-' .
			$_REQUEST['month_include_active_date'] . '-' .
			$_REQUEST['year_include_active_date'];
	else
		$date = DBDate();

	if ( ! empty( $_REQUEST['fields']['PERIOD_ATTENDANCE'] ) )
	{
		//FJ multiple school periods for a course period
		//$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM staff st,schedule ss,course_periods cp,school_periods p WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=p.PERIOD_ID AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).') AND p.ATTENDANCE=\'Y\') AS PERIOD_ATTENDANCE';
		$extra['SELECT'] .= ",(SELECT CONCAT(st.FIRST_NAME, ' ', st.LAST_NAME, ' - ', coalesce(cp.ROOM,' '))
		FROM staff st,schedule ss,course_periods cp,school_periods p,course_period_school_periods cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND ss.STUDENT_ID=ssm.STUDENT_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND cp.TEACHER_ID=st.STAFF_ID
		AND cpsp.PERIOD_ID=p.PERIOD_ID
		AND ('" . $date . "' BETWEEN ss.START_DATE AND ss.END_DATE
			OR '" . $date . "'>=ss.START_DATE AND ss.END_DATE IS NULL)
		AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', $date ) ) . ")
		AND p.ATTENDANCE='Y' LIMIT 1) AS PERIOD_ATTENDANCE";
	}

	foreach ( (array) $periods_RET as $period )
	{
		if ( isset( $_REQUEST['fields']['PERIOD_' . $period['PERIOD_ID']] )
			&& $_REQUEST['fields']['PERIOD_' . $period['PERIOD_ID']] == 'Y' )
		{
			//FJ multiple school periods for a course period
			//$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM staff st,schedule ss,course_periods cp WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=\''.$period['PERIOD_ID'].'\' AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')) AS PERIOD_'.$period['PERIOD_ID'];
			$extra['SELECT'] .= ",(SELECT " . DBSQLCommaSeparatedResult(
				"CONCAT(st.FIRST_NAME, ' ', st.LAST_NAME, ' - ', coalesce(cp.ROOM,' '))",
				'<br />'
			) . "
			FROM staff st,schedule ss,course_periods cp,course_period_school_periods cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND ss.STUDENT_ID=ssm.STUDENT_ID
			AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
			AND cp.TEACHER_ID=st.STAFF_ID
			AND cpsp.PERIOD_ID='" . (int) $period['PERIOD_ID'] . "'
			AND ('" . $date . "' BETWEEN ss.START_DATE AND ss.END_DATE
				OR '" . $date . "'>=ss.START_DATE AND ss.END_DATE IS NULL)
			AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))
			AS PERIOD_" . (int) $period['PERIOD_ID'];
		}
	}

	if ( $RosarioModules['Food_Service']
		&& ( isset( $_REQUEST['fields']['FS_ACCOUNT_ID'] ) && $_REQUEST['fields']['FS_ACCOUNT_ID'] == 'Y'
			|| isset( $_REQUEST['fields']['FS_DISCOUNT'] ) && $_REQUEST['fields']['FS_DISCOUNT'] == 'Y'
			|| isset( $_REQUEST['fields']['FS_STATUS'] ) && $_REQUEST['fields']['FS_STATUS'] == 'Y'
			|| isset( $_REQUEST['fields']['FS_BARCODE'] ) && $_REQUEST['fields']['FS_BARCODE'] == 'Y'
			|| isset( $_REQUEST['fields']['FS_BALANCE'] ) && $_REQUEST['fields']['FS_BALANCE'] == 'Y' ) )
	{
		$extra['FROM'] .= ',food_service_student_accounts fssa';
		$extra['WHERE'] .= ' AND fssa.STUDENT_ID=ssm.STUDENT_ID';

		if ( isset( $_REQUEST['fields']['FS_ACCOUNT_ID'] ) && $_REQUEST['fields']['FS_ACCOUNT_ID'] == 'Y' )
		{
			$extra['SELECT'] .= ',fssa.ACCOUNT_ID AS FS_ACCOUNT_ID';
		}

		if ( isset( $_REQUEST['fields']['FS_DISCOUNT'] ) && $_REQUEST['fields']['FS_DISCOUNT'] == 'Y' )
		{
			$extra['SELECT'] .= ",coalesce(fssa.DISCOUNT,'" . DBEscapeString( _( 'Full' ) ) . "') AS FS_DISCOUNT";
		}

		if ( isset( $_REQUEST['fields']['FS_STATUS'] ) && $_REQUEST['fields']['FS_STATUS'] == 'Y' )
		{
			$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS FS_STATUS";
		}

		if ( isset( $_REQUEST['fields']['FS_BARCODE'] ) && $_REQUEST['fields']['FS_BARCODE'] == 'Y' )
		{
			$extra['SELECT'] .= ',fssa.BARCODE AS FS_BARCODE';
		}

		if ( isset( $_REQUEST['fields']['FS_BALANCE'] ) && $_REQUEST['fields']['FS_BALANCE'] == 'Y' )
		{
			$extra['SELECT'] .= ',(SELECT fsa.BALANCE
				FROM food_service_accounts fsa
				WHERE fsa.ACCOUNT_ID=fssa.ACCOUNT_ID) AS FS_BALANCE';
		}

		$fields_list += [
			'FS_ACCOUNT_ID' => _( 'Food Service' ) . ' ' . _( 'Account ID' ),
			'FS_DISCOUNT' => _( 'Food Service' ) . ' ' . _( 'Discount' ),
			'FS_STATUS' => _( 'Food Service' ) . ' ' . _( 'Status' ),
			'FS_BARCODE' => _( 'Food Service' ) . ' ' . _( 'Barcode' ),
			'FS_BALANCE' => _( 'Food Service' ) . ' ' . _( 'Balance' ),
		];
	}

	if ( $RosarioModules['Student_Billing']
		&& AllowUse( 'Student_Billing/StudentFees.php' ) )
	{
		if ( ! empty( $_REQUEST['fields']['SB_BALANCE'] ) )
		{
			// Add Balance field to Advanced Report.
			$extra['SELECT'] .= ",(coalesce((SELECT sum(p.AMOUNT)
				FROM billing_payments p
				WHERE p.STUDENT_ID=ssm.STUDENT_ID
				AND p.SYEAR=ssm.SYEAR), 0)
				- coalesce((SELECT sum(f.AMOUNT)
				FROM billing_fees f
				WHERE f.STUDENT_ID=ssm.STUDENT_ID
				AND f.SYEAR=ssm.SYEAR), 0)) AS SB_BALANCE";

			$extra['functions'] += [ 'SB_BALANCE' => 'Currency' ];

			$fields_list += [ 'SB_BALANCE' => _( 'Student Billing' ) . ' ' . _( 'Balance' ) ];
		}

		// @since 8.0 Add Total from Payments & Total from Fees fields to Advanced Report.
		if ( ! empty( $_REQUEST['fields']['SB_PAYMENTS'] ) )
		{
			$extra['SELECT'] .= ",coalesce((SELECT sum(p.AMOUNT)
				FROM billing_payments p
				WHERE p.STUDENT_ID=ssm.STUDENT_ID
				AND p.SYEAR=ssm.SYEAR), 0) AS SB_PAYMENTS";

			$extra['functions'] += [ 'SB_PAYMENTS' => 'Currency' ];

			$fields_list += [ 'SB_PAYMENTS' => _( 'Total from Payments' ) ];
		}

		if ( ! empty( $_REQUEST['fields']['SB_FEES'] ) )
		{
			$extra['SELECT'] .= ",coalesce((SELECT sum(f.AMOUNT)
				FROM billing_fees f
				WHERE f.STUDENT_ID=ssm.STUDENT_ID
				AND f.SYEAR=ssm.SYEAR), 0) AS SB_FEES";

			$extra['functions'] += [ 'SB_FEES' => 'Currency' ];

			$fields_list += [ 'SB_FEES' => _( 'Total from Fees' ) ];
		}
	}

	/**
	 * Export fields list + extra SQL (student list) action hook.
	 *
	 * @since 8.1
	 *
	 * Add or remove any field to/from the global variable $fields_list.
	 * Add or remove SQL for any field to/from the global variable $extra.
	 * Use in conjonction with the 'misc/Export.php|fields_list' action hook.
	 */
	do_action( 'misc/Export.php|fields_list_extra_sql' );


	if ( ! empty( $_REQUEST['fields'] ) )
	{
		foreach ( (array) $_REQUEST['fields'] as $field => $on )
		{
			$columns[ $field ] = ParseMLField( $fields_list[ $field ] );

			if ( Config( 'STUDENTS_EMAIL_FIELD' ) === str_replace( 'CUSTOM_', '', $field ) )
			{
				$extra['functions'][ $field ] = 'makeEmail';
			}
			elseif ( $field === 'PHONE' )
			{
				$extra['functions'][ $field ] = 'makePhone';
			}
			elseif ( mb_substr( $field, 0, 7 ) === 'CUSTOM_' )
			{
				$field_type = $custom_RET[ mb_substr( $field, 7 ) ][1]['TYPE'];

				if ( ! isset( $extra['functions'][ $field ] )
					|| ! $extra['functions'][ $field ] )
				{
					$extra['functions'][ $field ] = makeFieldTypeFunction( $field_type );
				}
			}
			elseif ( mb_substr( $field, 0, 8 ) === 'ADDRESS_' )
			{
				$field_type = $address_RET[ mb_substr( $field, 8 ) ][1]['TYPE'];

				if ( ! isset( $extra['functions'][ $field ] )
					|| ! $extra['functions'][ $field ] )
				{
					$extra['functions'][ $field ] = makeFieldTypeFunction( $field_type );
				}
			}
		}

		$extra['LO_group'] = [];

		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID
				FROM students_join_address
				WHERE STUDENT_ID=ssm.STUDENT_ID
				AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";

			$extra['group'] = $extra['LO_group'] = [ 'FAMILY_ID' ];
		}

		$RET = GetStuList( $extra );

		if ( ! empty( $extra['array_function'] )
			&& function_exists( $extra['array_function'] ) )
		{
			$extra['array_function']( $RET );
		}

		if ( empty( $_REQUEST['LO_save'] )
			&& empty( $extra['suppress_save'] ) )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'bottom_back' ] );

			if ( isset( $_SESSION['Back_PHP_SELF'] )
				&& $_SESSION['Back_PHP_SELF'] != 'student' )
			{
				$_SESSION['Back_PHP_SELF'] = 'student';

				unset( $_SESSION['Search_PHP_SELF'] );
			}

			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( empty( $_REQUEST['address_group'] ) )
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => 'Y' ] ) . '">' .
					_( 'Group by Family' ) . '</a>';
			}
			else
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => '' ] ) . '">'.
					_( 'Ungroup by Family' ) . '</a>';

			DrawHeader( $header_left );
		}

		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );

		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			ListOutput( $RET, $columns, 'Family', 'Families', [], $extra['LO_group'] );
		}
		else
		{
			ListOutput( $RET, $columns, 'Student', 'Students', [], $extra['LO_group'] );
		}
	}
}
// Advanced Report form
else
{
	if ( empty( $fields_list ) )
	{
		// General Info
		if ( AllowUse( 'Students/Student.php&category_id=1' ) )
		{
			$fields_list['General'] = [
				'FULL_NAME' => _( 'Display Name' ),
				'FIRST_NAME' => _( 'First Name' ),
				'FIRST_INIT' => _( 'First Name Initial' ),
				'LAST_NAME' => _( 'Last Name' ),
				'MIDDLE_NAME' => _( 'Middle Name' ),
				'NAME_SUFFIX' => _( 'Suffix' ),
				'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
				'GRADE_ID' => _( 'Grade Level' ),
				'SCHOOL_TITLE' => _( 'School' ),
				'SCHOOL_NUMBER' => _( 'School Number' ),
				'NEXT_SCHOOL' => _( 'Rolling / Retention Options' ),
				'CALENDAR_ID' => _( 'Calendar' ),
				'USERNAME' => _( 'Username' ),
				'START_DATE' => _( 'Enrollment Start Date' ),
				'END_DATE' => _( 'Enrollment End Date' ),
				'ENROLLMENT_SHORT' => _( 'Enrollment Code' ),
				'DROP_SHORT' => _( 'Drop Code' ),
				'LAST_LOGIN' => _( 'Last Login' ),
			];
		}

		// Addresses & Contacts
		if ( AllowUse( 'Students/Student.php&category_id=3' ) )
		{
			// Disable mailing address display.
			if ( Config( 'STUDENTS_USE_MAILING' ) )
			{
				$fields_list['Address'] = [
					'ADDRESS' => _( 'Address' ),
					'MAIL_ADDRESS' => _( 'Mailing Address' ),
					'CITY' => _( 'City' ),
					'MAIL_CITY' => _( 'Mailing City' ),
					'STATE' => _( 'State' ),
					'MAIL_STATE' => _( 'Mailing State' ),
					'ZIPCODE' => _( 'Zip Code' ),
					'MAIL_ZIPCODE' => _( 'Mailing Zipcode' ),
					'PHONE' => _( 'Home Phone' ),
					'PARENTS' => _( 'Contacts' ),
				];
			}
			else
			{
				$fields_list['Address'] = [
					'ADDRESS' => _( 'Street' ),
					'CITY' => _( 'City' ),
					'STATE' => _( 'State' ),
					'ZIPCODE' => _( 'Zip Code' ),
					'PHONE' => _( 'Home Phone' ),
					'PARENTS' => _( 'Contacts' ),
				];
			}

			$categories_RET = DBGet( "SELECT ID,TITLE
				FROM address_field_categories
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			$address_RET = DBGet( "SELECT TITLE,ID,TYPE,CATEGORY_ID
				FROM address_fields
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [] , [ 'CATEGORY_ID' ] );

			foreach ( (array) $categories_RET as $category )
			{
				if ( empty( $address_RET[$category['ID']] ) )
				{
					continue;
				}

				foreach ( (array) $address_RET[$category['ID']] as $field )
				{
					$fields_list['Address']['ADDRESS_' . $field['ID']] = $field['TITLE'];
				}
			}
		}

		if ( ! empty( $extra['field_names'] ) )
		{
			$fields_list['General'] += $extra['field_names'];
		}
	}

	// Other Student Field Categories
	$categories_RET = DBGet( "SELECT ID,TITLE
		FROM student_field_categories
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

	$custom_RET = DBGet( "SELECT TITLE,ID,TYPE,CATEGORY_ID
		FROM custom_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [], ['CATEGORY_ID'] );

	foreach ( (array) $categories_RET as $category )
	{
		if ( AllowUse( 'Students/Student.php&category_id=' . $category['ID'] ) )
		{
			// Fix error Warning: Invalid argument supplied for foreach().
			if ( isset( $custom_RET[$category['ID']] ) )
			{
				foreach ( (array) $custom_RET[$category['ID']] as $field )
				{
					$fields_list[$category['TITLE']]['CUSTOM_' . $field['ID']] = $field['TITLE'];
				}
			}
		}
	}

	// Food Service
	if ( $RosarioModules['Food_Service'] )
	{
		$fields_list['Food_Service'] = [
			'FS_ACCOUNT_ID' => _( 'Account ID' ),
			'FS_DISCOUNT' => _( 'Discount' ),
			'FS_STATUS' => _( 'Status' ),
			'FS_BARCODE' => _( 'Barcode' ),
			'FS_BALANCE' => _( 'Balance' ),
		];
	}

	// Student Billing
	if ( $RosarioModules['Student_Billing']
		&& AllowUse( 'Student_Billing/StudentFees.php' ) )
	{
		// Add Balance field to Advanced Report.
		$fields_list['Student_Billing'] = [
			'SB_BALANCE' => _( 'Balance' ),
		];

		// @since 8.0 Add Total from Payments & Total from Fees fields to Advanced Report.
		$fields_list['Student_Billing']['SB_PAYMENTS'] = _( 'Total from Payments' );
		$fields_list['Student_Billing']['SB_FEES'] = _( 'Total from Fees' );
	}

	// Scheduling
	if ( $RosarioModules['Scheduling'] )
	{
		$fields_list['Scheduling']['PERIOD_ATTENDANCE'] = _( 'Attendance Period Teacher' ) . ' - ' . _( 'Room' );

		$periods_RET = DBGet( "SELECT TITLE,PERIOD_ID
			FROM school_periods
			WHERE SYEAR='".UserSyear()."'
			AND SCHOOL_ID='".UserSchool()."'
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
			$fields_list['Scheduling']['PERIOD_' . $period['PERIOD_ID']] = $period['TITLE'] . ' ' .
				_( 'Teacher' ) . ' - ' .
				_( 'Room' );
	}

	/**
	 * Export fields list (form) action hook.
	 *
	 * @since 8.1
	 *
	 * Add or remove any field (& category) to/from the global variable $fields_list.
	 */
	do_action( 'misc/Export.php|fields_list' );

	DrawHeader( '<ol><span id="names_div"></span></ol>' );

	echo '<div class="st"><br />';

	// Left side of the screen
	PopTable( 'header', _( 'Fields' ) );


	// Draw fields & categories
	foreach ( (array) $fields_list as $category => $fields )
	{

		// Draw category box
		if ( ParseMLField( $category ) == $category )
		{
			$category_title = _( str_replace( '_', ' ', $category ) );
		}
		else
		{
			$category_title = ParseMLField( $category );
		}

		echo '<table class="widefat width-100p"><tr>
				<th colspan="2">' . $category_title . '</th>
			</tr><tr>';

		if ( ParseMLField( $category, 'default' ) == 'Address' )
		{
			//FJ add <label> on checkbox
			echo '<td>
					<label>
						<input type="checkbox" id="residence" value="Y" />&nbsp;' . _( 'Residence' ) .
					'</label>
				</td>';

			//FJ disable mailing address display
			if ( Config( 'STUDENTS_USE_MAILING' ) )
				echo '<td>
						<label>
							<input type="checkbox" id="mailing" value="Y" />&nbsp;' . _( 'Mailing' ) .
						'</label>
					</td>';
			else
				echo '<td>&nbsp;<input type="hidden" id="mailing" value="" /></td>';

			echo '</tr><tr>';

			echo '<td>
					<label>
						<input type="checkbox" id="bus_pickup" value="Y" />&nbsp;' . _( 'Bus Pickup' ) .
					'</label>
				</td>';

			echo '<td>
					<label>
						<input type="checkbox" id="bus_dropoff" value="Y" />&nbsp;' . _( 'Bus Dropoff' ) .
					'</label>
				</td>';

			echo '</tr><tr>';
		}

		$i = 0;

		// Draw fields
		foreach ( (array) $fields as $field => $title )
		{
			$i++;

			echo '<td>';

			// @since 9.0 JS Sanitize string for legal variable name.
			// @link https://stackoverflow.com/questions/12339942/sanitize-strings-for-legal-variable-names-in-php
			$pattern = '/^(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/';

			$field_var_name_sanitized = preg_replace( $pattern, '', $field );

			$add_js = '<script>
				var field' . $field_var_name_sanitized . '=' .
					json_encode( '<li>' . ParseMLField( $title ) . '</li>' ) . ';
				var fielddiv' . $field_var_name_sanitized . '=' .
					json_encode( '<input type="hidden" name="' . AttrEscape( 'fields[' . $field_var_name_sanitized . ']' ) . '" value="Y" />' ) . ';
			</script>';

			$onclick_js = 'addHTML(field' . $field_var_name_sanitized . ',"names_div",false);
				addHTML(fielddiv' . $field_var_name_sanitized . ',"fields_div",false);
				this.disabled=true';

			echo $add_js .
				'<label>
					<input type="checkbox" autocomplete="off" onclick="' . AttrEscape( $onclick_js ) . '" />&nbsp;' .
					ParseMLField( $title ) .
				'</label>';

			if ( ParseMLField( $category, 'default' ) == 'Address'
				&& $field == 'PARENTS' )
			{
				$relations_RET = DBGet( "SELECT DISTINCT STUDENT_RELATION
					FROM students_join_people
					ORDER BY STUDENT_RELATION" );

				$select = '<select id="relation"><option value="">' . _( 'N/A' );

				foreach ( (array) $relations_RET as $relation )
				{
					if ( $relation['STUDENT_RELATION'] != '' )
					{
						$select .= '<option value="' . AttrEscape( $relation['STUDENT_RELATION'] ) . '">' . $relation['STUDENT_RELATION'];
					}
					else
					{
						$select .= '<option value="!">' . _( 'No Value' );
					}
				}

				$select .= '</select>';

				echo '&nbsp;&mdash;&nbsp;<label for="relation">' .
					_( 'Relation' ) . ':</label>&nbsp;' . $select;
			}

			echo '</td>';

			if ( $i%2 == 0 )
				echo '</tr><tr>';
		}

		if ( $i%2 != 0 )
		{
			echo '<td>&nbsp;</td></tr><tr>';

			$i++;
		}

		echo '</tr></table><br />';
	}

	PopTable( 'footer' );

	echo '</div><div class="st">';

	if ( ! empty( $Search )
		&& function_exists( $Search ) )
	{
		$Search( $extra );
	}
	else
	{
		Search( 'student_id', $extra );
	}

	echo '</div>';
}
