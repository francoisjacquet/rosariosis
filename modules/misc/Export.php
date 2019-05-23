<?php
require_once 'ProgramFunctions/miscExport.fnc.php';

//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';

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
			document.search.relation.value=document.getElementById(\'relation\').value;
			document.search.residence.value=document.getElementById(\'residence\').checked;
			document.search.mailing.value=document.getElementById(\'mailing\').checked;
			document.search.bus_pickup.value=document.getElementById(\'bus_pickup\').checked;
			document.search.bus_dropoff.value=document.getElementById(\'bus_dropoff\').checked;
		}
	</script>';

$extra['action'] .= '" onsubmit="exportSubmit();';

$extra['new'] = true;

$_ROSARIO['CustomFields'] = true;

if ( $_REQUEST['fields']['ADDRESS']
	|| $_REQUEST['fields']['CITY']
	|| $_REQUEST['fields']['STATE']
	|| $_REQUEST['fields']['ZIPCODE']
	|| $_REQUEST['fields']['PHONE']
	|| $_REQUEST['fields']['MAIL_ADDRESS']
	|| $_REQUEST['fields']['MAIL_CITY']
	|| $_REQUEST['fields']['MAIL_STATE']
	|| $_REQUEST['fields']['MAIL_ZIPCODE']
	|| $_REQUEST['fields']['PARENTS'] )
{
	$extra['SELECT'] .= ',a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,' .
		db_case( array( 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_ADDRESS,a.ADDRESS)', 'NULL' ) ) . ' AS MAIL_ADDRESS,' .
		db_case( array( 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_CITY,a.CITY)', 'NULL' ) ) . ' AS MAIL_CITY,' .
		db_case( array( 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_STATE,a.STATE)', 'NULL' ) ) . ' AS MAIL_STATE,' .
		db_case( array( 'sam.MAILING', "'Y'", 'coalesce(a.MAIL_ZIPCODE,a.ZIPCODE)', 'NULL' ) ) . ' AS MAIL_ZIPCODE';

	$extra['addr'] = true;

	if ( $_REQUEST['residence'] != 'false'
		|| $_REQUEST['mailing'] != 'false'
		|| $_REQUEST['bus_pickup'] != 'false'
		|| $_REQUEST['bus_dropoff'] != 'false' )
	{
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

	if ( ! empty( $_REQUEST['fields']['PARENTS'] ) )
	{
		$extra['SELECT'] .= ',ssm.STUDENT_ID AS PARENTS';

		$view_other_RET['ALL_CONTACTS'][1]['VALUE'] = 'Y';

		//FJ PrintClassLists with all contacts
		/*if ( $_REQUEST['relation']!='')
		{*/
			$_ROSARIO['makeParents'] = $_REQUEST['relation'];
			/*$extra['STUDENTS_JOIN_ADDRESS'] .= " AND EXISTS (SELECT '' FROM STUDENTS_JOIN_PEOPLE sjp WHERE sjp.ADDRESS_ID=sam.ADDRESS_ID AND ".($_REQUEST['relation']!='!'?"lower(sjp.STUDENT_RELATION) LIKE '".mb_strtolower($_REQUEST['relation'])."%'":"sjp.STUDENT_RELATION IS NULL").") ";
		}*/
	}
}

$extra['SELECT'] .= ",ssm.NEXT_SCHOOL,ssm.CALENDAR_ID,ssm.SYEAR,
	(SELECT sch.SCHOOL_NUMBER
		FROM SCHOOLS sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_NUMBER"; // Fix PHP error removed s.*.

if ( ! empty( $_REQUEST['fields']['FIRST_INIT'] ) )
{
	$extra['SELECT'] .= ',SUBSTR(s.FIRST_NAME,1,1) AS FIRST_INIT';
}

// @deprecated, use FULL_NAME or your own combination of LAST, FIRST, MIDDLE & SUFFIX.
if ( ! empty( $_REQUEST['fields']['GIVEN_NAME'] ) )
{
	$extra['SELECT'] .= ",s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS GIVEN_NAME";
}

// @deprecated, use FULL_NAME or your own combination of LAST, FIRST, MIDDLE & SUFFIX.
if ( ! empty( $_REQUEST['fields']['COMMON_NAME'] ) )
{
	$extra['SELECT'] .= ",s.LAST_NAME||', '||s.FIRST_NAME AS COMMON_NAME";
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
	$extra['SELECT'] .= ",(SELECT sch.TITLE FROM SCHOOLS sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
}



if ( ! $extra['functions'] )
{
	$extra['functions'] = array(
		'NEXT_SCHOOL' => '_makeNextSchool',
		'CALENDAR_ID' => '_makeCalendar',
		'PARENTS' => 'makeParents',
		'LAST_LOGIN' => 'makeLogin',
	);
}

// Generate Report.
if ( $_REQUEST['search_modfunc'] === 'list' )
{
	if ( empty( $_REQUEST['fields'] ) )
		if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
			BackPrompt( _( 'You must choose at least one field' ) );
		else
			echo ErrorMessage( array( _( 'You must choose at least one field' ) ), 'fatal' );

	if ( ! $fields_list )
	{
		$fields_list = array(
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
		);

		//FJ disable mailing address display
		if ( Config( 'STUDENTS_USE_MAILING' ) )
		{
			$fields_list += array(
				'MAIL_ADDRESS' => _( 'Mailing Address' ),
				'MAIL_CITY' => _( 'Mailing City' ),
				'MAIL_STATE' => _( 'Mailing State' ),
				'MAIL_ZIPCODE' => _( 'Mailing Zipcode' ),
			);
		}

		if ( $extra['field_names'] )
			$fields_list += $extra['field_names'];

		$fields_list['PERIOD_ATTENDANCE'] = _( 'Teacher' );

		$periods_RET = DBGet( "SELECT TITLE,PERIOD_ID
			FROM SCHOOL_PERIODS
			WHERE SYEAR='".UserSyear()."'
			AND SCHOOL_ID='".UserSchool()."'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
			$fields_list['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'] . ' ' . _( 'Teacher' ) .' - ' . _( 'Room' );
	}

	$custom_RET = DBGet( "SELECT TITLE,ID,TYPE
		FROM CUSTOM_FIELDS
		ORDER BY SORT_ORDER,TITLE", array(), array( 'ID' ) );

	foreach ( (array) $custom_RET as $id => $field )
	{
		if ( ! empty( $_REQUEST['fields'][ 'CUSTOM_' . $id ] ) )
		{
			if ( ! $fields_list[ 'CUSTOM_' . $id ] )
			{
				$fields_list[ 'CUSTOM_' . $id ] = $field[1]['TITLE'];
			}

			// Fix PHP error removed s.*, select each student field.
			$extra['SELECT'] .= ',s.CUSTOM_'  . $id;
		}
	}

	$address_RET = DBGet( "SELECT TITLE,ID,TYPE
		FROM ADDRESS_FIELDS
		ORDER BY SORT_ORDER,TITLE", array(), array( 'ID' ) );

	foreach ( (array) $address_RET as $id => $field )
	{
		$fields_list[ 'ADDRESS_' . $id ] = $field[1]['TITLE'];

		if ( ! empty( $_REQUEST['fields'][ 'ADDRESS_' . $id ] ) )
		{
			$extra['SELECT'] .= ',a.CUSTOM_' . $id . ' AS ADDRESS_' . $id;
			$extra['addr'] = true;
		}
	}

	if ( $_REQUEST['fields']['START_DATE']
		|| $_REQUEST['fields']['END_DATE']
		|| $_REQUEST['fields']['ENROLLMENT_SHORT']
		|| $_REQUEST['fields']['DROP_SHORT'] )
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

        	$extra['FROM'] .= ',STUDENT_ENROLLMENT xse';

			$extra['WHERE'] .= " AND xse.STUDENT_ID=s.STUDENT_ID AND xse.SYEAR='" . UserSyear() . "'";
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
		//$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,SCHOOL_PERIODS p WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=p.PERIOD_ID AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).') AND p.ATTENDANCE=\'Y\') AS PERIOD_ATTENDANCE';
		$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \')
		FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,SCHOOL_PERIODS p,COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND ss.STUDENT_ID=ssm.STUDENT_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND cp.TEACHER_ID=st.STAFF_ID
		AND cpsp.PERIOD_ID=p.PERIOD_ID
		AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL)
		AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')
		AND p.ATTENDANCE=\'Y\' LIMIT 1) AS PERIOD_ATTENDANCE';
	}

	foreach ( (array) $periods_RET as $period )
	{
		if ( $_REQUEST['fields']['PERIOD_' . $period['PERIOD_ID']] == 'Y' )
		{
			//FJ multiple school periods for a course period
			//$extra['SELECT'] .= ',array(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=\''.$period['PERIOD_ID'].'\' AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')) AS PERIOD_'.$period['PERIOD_ID'];
			$extra['SELECT'] .= ',array(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \')
			FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND ss.STUDENT_ID=ssm.STUDENT_ID
			AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
			AND cp.TEACHER_ID=st.STAFF_ID
			AND cpsp.PERIOD_ID=\''.$period['PERIOD_ID'].'\'
			AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE
				OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL)
			AND ss.MARKING_PERIOD_ID IN (' . GetAllMP( 'QTR', UserMP() ) . '))
			AS PERIOD_'.$period['PERIOD_ID'];

			$extra['functions']['PERIOD_' . $period['PERIOD_ID']] = '_makeTeachers';
		}
	}

	if ( $RosarioModules['Food_Service']
		&& ($_REQUEST['fields']['FS_ACCOUNT_ID'] == 'Y'
			|| $_REQUEST['fields']['FS_DISCOUNT'] == 'Y'
			|| $_REQUEST['fields']['FS_STATUS'] == 'Y'
			|| $_REQUEST['fields']['FS_BARCODE'] == 'Y'
			|| $_REQUEST['fields']['FS_BALANCE'] == 'Y' ) )
	{
		$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		$extra['WHERE'] .= ' AND fssa.STUDENT_ID=ssm.STUDENT_ID';

		if ( $_REQUEST['fields']['FS_ACCOUNT_ID'] == 'Y' )
			$extra['SELECT'] .= ',fssa.ACCOUNT_ID AS FS_ACCOUNT_ID';

		if ( $_REQUEST['fields']['FS_DISCOUNT'] == 'Y' )
			$extra['SELECT'] .= ",coalesce(fssa.DISCOUNT,'" . DBEscapeString( _( 'Full' ) ) . "') AS FS_DISCOUNT";

		if ( $_REQUEST['fields']['FS_STATUS'] == 'Y' )
			$extra['SELECT'] .= ",coalesce(fssa.STATUS,'" . DBEscapeString( _( 'Active' ) ) . "') AS FS_STATUS";

		if ( $_REQUEST['fields']['FS_BARCODE'] == 'Y' )
			$extra['SELECT'] .= ',fssa.BARCODE AS FS_BARCODE';

		if ( $_REQUEST['fields']['FS_BALANCE'] == 'Y' )
			$extra['SELECT'] .= ',(SELECT fsa.BALANCE
				FROM FOOD_SERVICE_ACCOUNTS fsa
				WHERE fsa.ACCOUNT_ID=fssa.ACCOUNT_ID) AS FS_BALANCE';

		$fields_list += array(
			'FS_ACCOUNT_ID' => _( 'Food Service' ) . ' ' . _( 'Account ID' ),
			'FS_DISCOUNT' => _( 'Food Service' ) . ' ' . _( 'Discount' ),
			'FS_STATUS' => _( 'Food Service' ) . ' ' . _( 'Status' ),
			'FS_BARCODE' => _( 'Food Service' ) . ' ' . _( 'Barcode' ),
			'FS_BALANCE' => _( 'Food Service' ) . ' ' . _( 'Balance' ),
		);
	}

	if ( $RosarioModules['Student_Billing']
		&& ( $_REQUEST['fields']['SB_BALANCE'] == 'Y' ) )
	{

		// FJ Add Balance field to Advanced Report
		if ( $_REQUEST['fields']['SB_BALANCE'] == 'Y'
			&& AllowUse( 'Student_Billing/StudentFees.php' ) )
			$extra['SELECT'] .= ",( coalesce( ( SELECT sum( p.AMOUNT )
				FROM BILLING_PAYMENTS p
				WHERE p.STUDENT_ID=ssm.STUDENT_ID
				AND p.SYEAR=ssm.SYEAR ), 0 )
				- coalesce( ( SELECT sum( f.AMOUNT )
					FROM BILLING_FEES f
					WHERE f.STUDENT_ID=ssm.STUDENT_ID
					AND f.SYEAR=ssm.SYEAR ), 0 ) ) AS SB_BALANCE";

		$fields_list += array( 'SB_BALANCE' => _( 'Student Billing' ) . ' ' . _( 'Balance' ) );

		$extra['functions'] += array( 'SB_BALANCE' => 'Currency' );
	}


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

		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID
				FROM STUDENTS_JOIN_ADDRESS
				WHERE STUDENT_ID=ssm.STUDENT_ID
				AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";

			$extra['group'] = $extra['LO_group'] = array( 'FAMILY_ID' );
		}

		$RET = GetStuList( $extra );

		if ( $extra['array_function']
			&& function_exists( $extra['array_function'] ) )
			$extra['array_function']( $RET );

		if ( ! $_REQUEST['LO_save']
			&& ! $extra['suppress_save'] )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], array( 'bottom_back' ) );

			if ( $_SESSION['Back_PHP_SELF'] != 'student' )
			{
				$_SESSION['Back_PHP_SELF'] = 'student';

				unset( $_SESSION['Search_PHP_SELF'] );
			}

			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		if ( !isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( empty( $_REQUEST['address_group'] ) )
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'address_group' => 'Y' ) ) . '">' .
					_( 'Group by Family' ) . '</a>';
			}
			else
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, array(), array( 'address_group' => '' ) ) . '">'.
					_( 'Ungroup by Family' ) . '</a>';

			DrawHeader( $header_left );
		}

		DrawHeader( str_replace( '<br />', '<br /> &nbsp;', mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) ) );

		if ( ! empty( $_REQUEST['address_group'] ) )
			ListOutput( $RET, $columns, 'Family', 'Families', array(), $extra['LO_group'], $extra['LO_options'] );
		else
			ListOutput( $RET, $columns, 'Student', 'Students', array(), $extra['LO_group'], $extra['LO_options'] );
	}
}
// Advanced Report form
else
{
	if ( ! $fields_list )
	{
		// General Info
		if ( AllowUse( 'Students/Student.php&category_id=1' ) )
		{
			$fields_list['General'] = array(
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
			);
		}

		// Addresses & Contacts
		if ( AllowUse( 'Students/Student.php&category_id=3' ) )
		{
			//FJ disable mailing address display
			if ( Config( 'STUDENTS_USE_MAILING' ) )
				$fields_list['Address'] = array(
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
				);
			else
				$fields_list['Address'] = array(
					'ADDRESS' => _( 'Street' ),
					'CITY' => _( 'City' ),
					'STATE' => _( 'State' ),
					'ZIPCODE' => _( 'Zip Code' ),
					'PHONE' => _( 'Home Phone' ),
					'PARENTS' => _( 'Contacts' ),
				);

			$categories_RET = DBGet( "SELECT ID,TITLE
				FROM ADDRESS_FIELD_CATEGORIES
				ORDER BY SORT_ORDER,TITLE" );

			$address_RET = DBGet( "SELECT TITLE,ID,TYPE,CATEGORY_ID
				FROM ADDRESS_FIELDS
				ORDER BY SORT_ORDER,TITLE", array() ,array ( 'CATEGORY_ID' ) );

			foreach ( (array) $categories_RET as $category )
			{
				foreach ( (array) $address_RET[$category['ID']] as $field )
				{
					$fields_list['Address']['ADDRESS_' . $field['ID']] = str_replace( "'", '&#39;', $field['TITLE'] );
				}
			}
		}

		if ( $extra['field_names'] )
			$fields_list['General'] += $extra['field_names'];
	}

	// Other Student Field Categories
	$categories_RET = DBGet( "SELECT ID,TITLE
		FROM STUDENT_FIELD_CATEGORIES
		ORDER BY SORT_ORDER,TITLE" );

	$custom_RET = DBGet( "SELECT TITLE,ID,TYPE,CATEGORY_ID
		FROM CUSTOM_FIELDS
		ORDER BY SORT_ORDER,TITLE", array(), array('CATEGORY_ID') );

	foreach ( (array) $categories_RET as $category )
	{
		if ( AllowUse( 'Students/Student.php&category_id=' . $category['ID'] ) )
		{
			//FJ fix error Warning: Invalid argument supplied for foreach()
			if ( isset( $custom_RET[$category['ID']] ) )
			{
				foreach ( (array) $custom_RET[$category['ID']] as $field )
					$fields_list[$category['TITLE']]['CUSTOM_' . $field['ID']] = str_replace( "'", '&#39;', $field['TITLE'] );
			}
		}
	}

	// Food Service
	if ( $RosarioModules['Food_Service'] )
	{
		$fields_list['Food_Service'] = array(
			'FS_ACCOUNT_ID' => _( 'Account ID' ),
			'FS_DISCOUNT' => _( 'Discount' ),
			'FS_STATUS' => _( 'Status' ),
			'FS_BARCODE' => _( 'Barcode' ),
			'FS_BALANCE' => _( 'Balance' ),
		);
	}

	// Student Billing
	if ( $RosarioModules['Student_Billing'] )
	{
		// FJ Add Balance field to Advanced Report
		if ( AllowUse( 'Student_Billing/StudentFees.php' ) )
			$fields_list['Student_Billing'] = array(
				'SB_BALANCE' => _( 'Balance' ),
			);
	}

	// Scheduling
	if ( $RosarioModules['Scheduling'] )
	{
		$fields_list['Scheduling']['PERIOD_ATTENDANCE'] = _( 'Attendance Period Teacher' ) . ' - ' . _( 'Room' );

		$periods_RET = DBGet( "SELECT TITLE,PERIOD_ID
			FROM SCHOOL_PERIODS
			WHERE SYEAR='".UserSyear()."'
			AND SCHOOL_ID='".UserSchool()."'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $periods_RET as $period )
			$fields_list['Scheduling']['PERIOD_' . $period['PERIOD_ID']] = $period['TITLE'] . ' ' .
				_( 'Teacher' ) . ' - ' .
				_( 'Room' );
	}

	DrawHeader( '<ol><span id="names_div"></span></ol>' );

	echo '<div class="st"><br />';

	// Left side of the screen
	PopTable( 'header', _( 'Fields' ) );


	// Draw fields & categories
	foreach ( (array) $fields_list as $category => $fields )
	{

		// Draw category box
		if ( ParseMLField( $category ) == $category )
			$category_title = _( str_replace( '_', ' ', $category ) );
		else
			$category_title = ParseMLField( $category );

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

		// Draw fields
		foreach ( (array) $fields as $field => $title )
		{
			$i++;

			echo '<td>';

			$addJS = '<script>
					var field' . $field . '=' .
						json_encode( '<li>' . ParseMLField( $title ) . '</li>' ) . ';
					var fielddiv' . $field . '=' .
						json_encode( '<input type="hidden" name="fields[' . $field . ']" value="Y" />' ) . ';
				</script>';

			echo $addJS .
				'<label>
					<input type="checkbox" autocomplete="off" onclick=\'addHTML(field' . $field . ',"names_div",false);
						addHTML(fielddiv'.$field.',"fields_div",false);
						this.disabled=true\' />&nbsp;' . ParseMLField( $title ) .
				'</label>';

			if ( ParseMLField( $category, 'default' ) == 'Address'
				&& $field == 'PARENTS' )
			{
				$relations_RET = DBGet( "SELECT DISTINCT STUDENT_RELATION
					FROM STUDENTS_JOIN_PEOPLE
					ORDER BY STUDENT_RELATION" );

				$select = '<select id="relation"><option value="">' . _( 'N/A' );

				foreach ( (array) $relations_RET as $relation )
					if ( $relation['STUDENT_RELATION'] != '' )
						$select .= '<option value="' . $relation['STUDENT_RELATION'] . '">' . $relation['STUDENT_RELATION'];
					else
						$select .= '<option value="!">' . _( 'No Value' );

				$select .= '</select>';

				echo '&nbsp;-&nbsp;<label for="relation">' .
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

	if ( $Search
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
