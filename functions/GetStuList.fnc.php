<?php
/**
 * Get Student List functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get Student List
 * Builds SQL request based on:
 * - User profile
 * - Extra parameters
 *
 * @example $fees_RET = GetStuList( $fees_extra );
 *
 * @see Search()
 *
 * @uses Widgets()               add Widgets SQL to $extra
 * @uses appendSQL()             add Search Student basic fields SQL to $extra['WHERE']
 * @uses CustomFields()          add Custom Fields SQL to $extra['WHERE']
 * @uses DBGet()                 return Students
 * @uses makeParents()           generate Parents info popup
 * @uses makeEmail()             format Email address
 * @uses makePhone()             format Phone number
 * @uses makeContactInfo()       generate Contact Info tooltip
 * @uses makeFieldTypeFunction() make / format custom fields based on their type
 *
 * @global $contacts_RET   Student Contacts array
 * @global $view_other_RET Used by makeParents() (see below)
 *
 * @param  array &$extra Extra for SQL request ('SELECT_ONLY', 'FROM', 'WHERE', 'ORDER_BY', 'functions', 'columns_after', 'DATE',...).
 *
 * @return array DBGet return of the built SQL query
 */
function GetStuList( &$extra = array() )
{
	global $contacts_RET,
		$view_other_RET;

	// FJ fix Advanced Search.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& isset( $_REQUEST['advanced'] )
		&& $_REQUEST['advanced'] === 'Y' )
	{
		Widgets( 'all', $extra );
	}

	if ( ! isset( $extra['WHERE'] ) )
	{
		$extra['WHERE'] = '';
	}

	$extra['WHERE'] .= appendSQL( '', $extra );

	$extra['WHERE'] .= CustomFields( 'where', 'student', $extra );

	if ( ( ! isset( $extra['SELECT_ONLY'] )
			|| mb_strpos( $extra['SELECT_ONLY'], 'GRADE_ID' ) !== false )
		&& ! isset( $extra['functions']['GRADE_ID'] ) )
	{
		$functions = array( 'GRADE_ID' => 'GetGrade' );
	}
	else
		$functions = array();

	if ( isset( $extra['functions'] ) )
	{
		$functions += (array) $extra['functions'];
	}

	if ( ! isset( $extra['MP'] )
		&& ! isset( $extra['DATE'] ) )
	{
		$extra['MP'] = UserMP();

		$extra['DATE'] = DBDate();
	}
	elseif ( empty( $extra['MP'] ) )
	{
		$extra['MP'] = GetCurrentMP( 'QTR', $extra['DATE'], false );
	}
	elseif ( empty( $extra['DATE'] ) )
	{
		$extra['DATE'] = DBDate();
	}

	// Expanded View.
	if ( isset( $_REQUEST['expanded_view'] )
		&& $_REQUEST['expanded_view'] == 'true' )
	{
		/**
		 * Add Sudent Photo Tip Message to Expanded View
		 *
		 * @since 3.8
		 */
		if ( empty( $functions['FULL_NAME'] ) )
		{
			$functions['FULL_NAME'] = 'makePhotoTipMessage';
		}

		if ( ! $extra['columns_after'] )
		{
			$extra['columns_after'] = array();
		}

		$view_fields_RET = DBGet( "SELECT cf.ID,cf.TYPE,cf.TITLE
			FROM CUSTOM_FIELDS cf
			WHERE ((SELECT VALUE
				FROM PROGRAM_USER_CONFIG
				WHERE TITLE=cast(cf.ID AS TEXT)
				AND PROGRAM='StudentFieldsView'
				AND USER_ID='" . User( 'STAFF_ID' ) . "')='Y'" .
				( $extra['student_fields']['view'] ?
					" OR cf.ID IN (" . $extra['student_fields']['view'] . ")" :
					'' ) . ")
			ORDER BY cf.SORT_ORDER,cf.TITLE" );

		$view_address_RET = DBGetOne( "SELECT VALUE
			FROM PROGRAM_USER_CONFIG
			WHERE PROGRAM='StudentFieldsView'
			AND TITLE='ADDRESS'
			AND USER_ID='" . User( 'STAFF_ID' ) . "'" );

		$view_other_RET = DBGet( "SELECT TITLE,VALUE
			FROM PROGRAM_USER_CONFIG
			WHERE PROGRAM='StudentFieldsView'
			AND TITLE IN ('USERNAME','CONTACT_INFO','HOME_PHONE','GUARDIANS','ALL_CONTACTS')
			AND USER_ID='" . User( 'STAFF_ID' ) . "'", array(), array( 'TITLE' ) );

		if ( ! $view_fields_RET
			&& ! $view_address_RET
			&& ! isset( $view_other_RET['CONTACT_INFO'] ) )
		{
			$extra['columns_after'] = array(
				'ADDRESS' => _( 'Mailing Address' ),
				'CITY' => _( 'City' ),
				'STATE' => _( 'State' ),
				'ZIPCODE' => _( 'Zipcode' ) )
				+ $extra['columns_after'];

			// Add Gender + Ethnicity fields if exist.
			$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
				FROM CUSTOM_FIELDS
				WHERE ID IN (200000000, 200000001)" );

			$select = '';

			foreach ( (array) $custom_fields_RET as $field)
			{
				$extra['columns_after'] = array(
					'CUSTOM_' . $field['ID'] => ParseMLField( $field['TITLE'] ) )
					+ $extra['columns_after'];

				// If gender and ethnicity are converted to exports type.
				if ( $field['TYPE'] === 'exports' )
				{
					$functions['CUSTOM_' . $field['ID']] = 'DeCodeds';
				}

				$select .= ',s.CUSTOM_' . $field['ID'];
			}

			$extra['columns_after'] = array(
				'CONTACT_INFO' => button( 'down_phone' ) )
				+ $extra['columns_after'];

			$select .= ',ssm.STUDENT_ID AS CONTACT_INFO,coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,
				coalesce(a.MAIL_CITY,a.CITY) AS CITY,coalesce(a.MAIL_STATE,a.STATE) AS STATE,
				coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE ';

			$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID AND sam.RESIDENCE='Y')
				LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) " . $extra['FROM'];

			$functions['CONTACT_INFO'] = 'makeContactInfo';

			$extra['singular'] = 'Student Address';

			$extra['plural'] = 'Student Addresses';

			$extra2['NoSearchTerms'] = true;

			$extra2['SELECT_ONLY'] = 'ssm.STUDENT_ID,p.PERSON_ID,
				p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME,
				sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';

			$extra2['FROM'] .= ',ADDRESS a,STUDENTS_JOIN_ADDRESS sja LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID)
				LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID)
				LEFT OUTER JOIN PEOPLE_JOIN_CONTACTS pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';

			$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID
				AND sja.STUDENT_ID=ssm.STUDENT_ID
				AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\') ';

			$extra2['ORDER_BY'] .= 'sjp.CUSTODY';

			$extra2['group'] = array( 'STUDENT_ID', 'PERSON_ID' );

			// EXPANDED VIEW AND ADDR BREAKS THIS QUERY ... SO, TURN 'EM OFF.
			if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$expanded_view = isset( $_REQUEST['expanded_view'] ) ? $_REQUEST['expanded_view'] : null;

				$_REQUEST['expanded_view'] = false;

				$addr = isset( $_REQUEST['addr'] ) ? $_REQUEST['addr'] : null;

				unset( $_REQUEST['addr'] );

				$contacts_RET = GetStuList( $extra2 );

				$_REQUEST['expanded_view'] = $expanded_view;

				$_REQUEST['addr'] = $addr;
			}
			else
				unset( $extra['columns_after']['CONTACT_INFO'] );
		}
		else
		{
			if ( $view_other_RET['CONTACT_INFO'][1]['VALUE'] == 'Y'
				&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
			{
				$select .= ',ssm.STUDENT_ID AS CONTACT_INFO ';

				$extra['columns_after']['CONTACT_INFO'] = button( 'down_phone' );

				$functions['CONTACT_INFO'] = 'makeContactInfo';

				$extra2 = $extra;

				$extra2['NoSearchTerms'] = true;

				$extra2['SELECT'] = '';

				$extra2['SELECT_ONLY'] = 'ssm.STUDENT_ID,p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME,
					sjp.STUDENT_RELATION,pjc.TITLE,pjc.VALUE,a.PHONE,sjp.ADDRESS_ID ';

				$extra2['FROM'] .= ',ADDRESS a,STUDENTS_JOIN_ADDRESS sja
					LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sja.STUDENT_ID=sjp.STUDENT_ID AND sja.ADDRESS_ID=sjp.ADDRESS_ID)
					LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID)
					LEFT OUTER JOIN PEOPLE_JOIN_CONTACTS pjc ON (pjc.PERSON_ID=p.PERSON_ID) ';

				$extra2['WHERE'] .= ' AND a.ADDRESS_ID=sja.ADDRESS_ID
					AND sja.STUDENT_ID=ssm.STUDENT_ID
					AND (sjp.CUSTODY=\'Y\' OR sjp.EMERGENCY=\'Y\') ';

				$extra2['ORDER_BY'] .= 'sjp.CUSTODY';

				$extra2['group'] = array( 'STUDENT_ID', 'PERSON_ID' );

				$extra2['functions'] = array();

				$extra2['link'] = array();

				// EXPANDED VIEW AND ADDR BREAKS THIS QUERY ... SO, TURN 'EM OFF.
				$expanded_view = isset( $_REQUEST['expanded_view'] ) ? $_REQUEST['expanded_view'] : null;

				$_REQUEST['expanded_view'] = false;

				$addr = isset( $_REQUEST['addr'] ) ? $_REQUEST['addr'] : null;

				unset( $_REQUEST['addr'] );

				$contacts_RET = GetStuList( $extra2 );

				$_REQUEST['expanded_view'] = $expanded_view;

				$_REQUEST['addr'] = $addr;
			}

			// Student Fields: search Username.
			if ( $view_other_RET['USERNAME'][1]['VALUE'] === 'Y' )
			{
				$extra['columns_after']['USERNAME'] = _( 'Username' );

				$select .= ',s.USERNAME';
			}

			foreach ( (array) $view_fields_RET as $field )
			{
				$field_key = 'CUSTOM_' . $field['ID'];
				$extra['columns_after'][ $field_key ] = $field['TITLE'];

				if ( Config( 'STUDENTS_EMAIL_FIELD' ) === $field['ID'] )
				{
					$functions[ $field_key ] = 'makeEmail';
				}
				else
				{
					$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );
				}

				$select .= ',s.' . $field_key;
			}

			if ( $view_address_RET )
			{

				$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID AND sam." . $view_address_RET . "='Y')
					LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) " . $extra['FROM'];

				$extra['columns_after'] += array(
					'ADDRESS' => _( ucwords( mb_strtolower( str_replace( '_', ' ', $view_address_RET ) ) ) ) . ' ' . _( 'Address' ),
					'CITY' => _( 'City' ),
					'STATE' => _( 'State' ),
					'ZIPCODE' => _( 'Zipcode' ) );

				if ( $view_address_RET != 'MAILING' )
				{
					$select .= ",a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,
						ssm.STUDENT_ID AS PARENTS";
				}
				else
				{
					$select .= ",a.ADDRESS_ID,coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,
						coalesce(a.MAIL_CITY,a.CITY) AS CITY,coalesce(a.MAIL_STATE,a.STATE) AS STATE,
						coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE,a.PHONE,
						ssm.STUDENT_ID AS PARENTS ";
				}

				$extra['singular'] = 'Student Address';

				$extra['plural'] = 'Student Addresses';

				if ( $view_other_RET['HOME_PHONE'][1]['VALUE'] === 'Y' )
				{
					$functions['PHONE'] = 'makePhone';

					$extra['columns_after']['PHONE'] = _( 'Home Phone' );
				}

				if ( $view_other_RET['GUARDIANS'][1]['VALUE'] === 'Y'
					|| $view_other_RET['ALL_CONTACTS'][1]['VALUE'] === 'Y' )
				{
					$functions['PARENTS'] = 'makeParents';

					if ( $view_other_RET['ALL_CONTACTS'][1]['VALUE'] === 'Y' )
					{
						$extra['columns_after']['PARENTS'] = _( 'Contacts' );
					}
					else
						$extra['columns_after']['PARENTS'] = _( 'Guardians' );
				}
			}
			elseif ( $_REQUEST['addr']
				|| $extra['addr'] )
			{
				$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID " . $extra['STUDENTS_JOIN_ADDRESS'] . ")
					LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) " . $extra['FROM'];

				$distinct = 'DISTINCT ';
			}
		}

		$extra['SELECT'] .= $select;
	}
	// Original View.
	else
	{
		if ( isset( $extra['student_fields']['view'] ) )
		{
			if ( ! isset( $extra['columns_after'] ) )
			{
				$extra['columns_after'] = array();
			}

			$view_fields_RET = DBGet( "SELECT cf.ID,cf.TYPE,cf.TITLE
				FROM CUSTOM_FIELDS cf
				WHERE cf.ID IN (" . $extra['student_fields']['view'] . ")
				ORDER BY cf.SORT_ORDER,cf.TITLE" );

			foreach ( (array) $view_fields_RET as $field )
			{
				$field_key = 'CUSTOM_' . $field['ID'];

				$extra['columns_after'][ $field_key ] = $field['TITLE'];

				if ( Config( 'STUDENTS_EMAIL_FIELD' ) === $field['ID'] )
				{
					$functions[ $field_key ] = 'makeEmail';
				}
				else
				{
					$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );
				}

				$select .= ',s.' . $field_key;
			}

			$extra['SELECT'] .= $select;
		}

		if ( ! empty( $_REQUEST['addr'] )
			|| ! empty( $extra['addr'] ) )
		{
			$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sam ON (ssm.STUDENT_ID=sam.STUDENT_ID " . $extra['STUDENTS_JOIN_ADDRESS'] . ")
			LEFT OUTER JOIN ADDRESS a ON (sam.ADDRESS_ID=a.ADDRESS_ID) " . $extra['FROM'];

			$distinct = 'DISTINCT ';
		}
	}

	// Get options:
	// SELECT only.
	$is_select_only = isset( $extra['SELECT_ONLY'] ) && !empty( $extra['SELECT_ONLY'] );

	$is_include_inactive = isset( $_REQUEST['include_inactive'] ) && $_REQUEST['include_inactive'] === 'Y';

	// Build SELECT.
	$sql = 'SELECT ';

	// SELECT only.
	if ( $is_select_only )
	{
		$sql .= $extra['SELECT_ONLY'];
	}
	// Normal SELECT.
	else
	{
		// Student Full Name.
		$sql .= DisplayNameSQL( 's' ) . " AS FULL_NAME,";

		// Student Details.
		$sql .='s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME,s.STUDENT_ID,ssm.SCHOOL_ID,ssm.GRADE_ID ' .
			( empty( $extra['SELECT'] ) ? '' : $extra['SELECT'] );
	}

	switch ( User( 'PROFILE' ) )
	{
		case 'admin':

			// Get Search All Schools option.
			$is_search_all_schools = isset( $_REQUEST['_search_all_schools'] )
				&& $_REQUEST['_search_all_schools'] == 'Y';

			// Normal SELECT.
			if ( ! $is_select_only )
			{

				// Search All Schools.
				if ( $is_search_all_schools )
				{
					// School Title.
					$sql .= ",(SELECT sch.TITLE FROM SCHOOLS sch
						WHERE ssm.SCHOOL_ID=sch.ID
						AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
				}

				// Include Inactive Students.
				if ( $is_include_inactive )
				{
					$active = "'" . DBEscapeString( '<span style="color:green">' . _( 'Active' ) . '</span>' ) . "'";

					$inactive = "'" . DBEscapeString( '<span style="color:red">' . _( 'Inactive' ) . '</span>' ) . "'";

					$sql .= ',' . db_case(
						array(
							"(ssm.SYEAR='" . UserSyear() . "'
								AND ('" . $extra['DATE'] . "'>=ssm.START_DATE
									AND ('" . $extra['DATE'] . "'<=ssm.END_DATE
										OR ssm.END_DATE IS NULL ) ) )",
							'TRUE',
							$active,
							$inactive
						) ) . ' AS ACTIVE';

					$extra['columns_after']['ACTIVE'] = _( 'Status' );
				}

			}

			// FROM.
			$sql .= " FROM STUDENTS s JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID";

			// Include Inactive Students: enrollment.
			if ( $is_include_inactive )
			{
				//$sql .= " AND ssm.ID=(SELECT max(ID) FROM STUDENT_ENROLLMENT WHERE STUDENT_ID=ssm.STUDENT_ID AND SYEAR<='".UserSyear()."')";
				$sql .= " AND ssm.ID=( SELECT ID
					FROM STUDENT_ENROLLMENT
					WHERE STUDENT_ID=ssm.STUDENT_ID
					AND SYEAR='" . UserSyear() . "'
					ORDER BY SYEAR DESC,START_DATE DESC
					LIMIT 1 )";
			}
			// Active / Enrolled students.
			else
			{
				$sql .= " AND ssm.SYEAR='" . UserSyear() . "'
					AND ('" . $extra['DATE'] . "'>=ssm.START_DATE
						AND (ssm.END_DATE IS NULL
							OR '" . $extra['DATE'] . "'<=ssm.END_DATE ) )";
			}

			if ( UserSchool()
				&& ! $is_search_all_schools )
			{
				$sql .= " AND ssm.SCHOOL_ID='" . UserSchool() . "'";
			}
			// Search All Schools.
			else
			{
				if ( User( 'SCHOOLS' ) )
				{
					$sql .= " AND ssm.SCHOOL_ID IN (" . mb_substr( str_replace( ',', "','", User( 'SCHOOLS' ) ), 2, -2 ) . ") ";
				}

				$extra['columns_after']['SCHOOL_TITLE'] = _( 'School' );
			}

		break;

		case 'teacher':

			//$sql = 'SELECT '.$distinct;

			$extra['MPTable'] = isset( $extra['MPTable'] ) ? $extra['MPTable'] : '';

			// Normal SELECT.
			if ( ! $is_select_only )
			{
				// Include Inactive Students.
				if ( $is_include_inactive )
				{
					$active = "'" . DBEscapeString( '<span style="color:green">' . _( 'Active' ) . '</span>' ) . "'";

					$inactive = "'" . DBEscapeString( '<span style="color:red">' . _( 'Inactive' ) . '</span>' ) . "'";

					$sql .= ',' . db_case(
						array(
							"(ssm.SYEAR='" . UserSyear() . "'
								AND ('" . $extra['DATE'] . "'>=ssm.START_DATE
									AND ('" . $extra['DATE'] . "'<=ssm.END_DATE
										OR ssm.END_DATE IS NULL ) ) )",
							'TRUE',
							$active,
							$inactive
						) ) . ' AS ACTIVE';

					$sql .= ',' . db_case(
						array(
							"('" . $extra['DATE'] . "'>=ss.START_DATE
								AND (ss.END_DATE IS NULL
									OR '" . $extra['DATE'] . "'<=ss.END_DATE))
							AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( $extra['MPTable'], $extra['MP'] ) . ")",
							'TRUE',
							$active,
							$inactive
						) ) . ' AS ACTIVE_SCHEDULE';

					$extra['columns_after']['ACTIVE'] = _( 'School Status' );
					$extra['columns_after']['ACTIVE_SCHEDULE'] = _( 'Course Status' );
				}
			}

			// FROM.
			$sql .= " FROM STUDENTS s JOIN SCHEDULE ss ON (ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='" . UserSyear() . "'";

			// Include Inactive Students: scheduled.
			if ( $is_include_inactive )
			{
				$sql .= " AND ss.START_DATE=(SELECT START_DATE
					FROM SCHEDULE WHERE STUDENT_ID=s.STUDENT_ID
					AND SYEAR=ss.SYEAR
					AND COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
					ORDER BY START_DATE DESC LIMIT 1)";
			}
			// Active / Scheduled Students.
			else
			{
				$sql .= " AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( $extra['MPTable'], $extra['MP'] ) . ")
					AND ('" . $extra['DATE'] . "'>=ss.START_DATE
						AND ('" . $extra['DATE'] . "'<=ss.END_DATE
							OR ss.END_DATE IS NULL))";
			}

			$sql .= ") JOIN COURSE_PERIODS cp ON (cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND " .
				( isset( $extra['all_courses'] ) && $extra['all_courses'] === 'Y' ?
					"cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'" :
					"cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" ) . ")
				JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
					AND ssm.SYEAR=ss.SYEAR
					AND ssm.SCHOOL_ID='" . UserSchool() . "'";

			// Include Inactive Students: enrollment.
			if ( $is_include_inactive )
			{
				$sql .= " AND ssm.ID=(SELECT ID FROM STUDENT_ENROLLMENT
					WHERE STUDENT_ID=ssm.STUDENT_ID
					AND SYEAR=ssm.SYEAR
					ORDER BY START_DATE DESC LIMIT 1)";
			}
			// Active / Enrolled Students.
			else
			{
				$sql .= " AND ('" . $extra['DATE'] . "'>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR '" . $extra['DATE'] . "'<=ssm.END_DATE))";
			}

		break;

		case 'parent':
		case 'student':

			// FROM.
			$sql .= " FROM STUDENTS s JOIN STUDENT_ENROLLMENT ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
				AND ssm.SYEAR='".UserSyear()."'
				AND ssm.SCHOOL_ID='".UserSchool()."'
				AND ('" . $extra['DATE'] . "'>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR '" . $extra['DATE'] . "'<=ssm.END_DATE))
				AND s.STUDENT_ID" . ( $extra['ASSOCIATED'] ?
					" IN (SELECT STUDENT_ID FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='" . $extra['ASSOCIATED'] . "')" :
					"='" . UserStudentID() . "'" );

		break;

		default:

			$error[] = 'Invalid user profile'; // Should never be displayed, so do not translate.

			return ErrorMessage( $error, 'fatal' );
	}

	// Extra FROM.
	$sql .= ")" . ( isset( $extra['FROM'] ) ? $extra['FROM'] : '' ) . " WHERE TRUE";

	//$sql = appendSQL($sql,array('NoSearchTerms' => $extra['NoSearchTerms']));

	// WHERE.
	$sql .= ' ' . ( isset( $extra['WHERE'] ) ? $extra['WHERE'] : '' ) . ' ';

	// GROUP BY.
	if ( isset( $extra['GROUP'] ) )
	{
		$sql .= ' GROUP BY ' . $extra['GROUP'];
	}

	// ORDER BY.
	if ( ! isset( $extra['ORDER_BY'] )
		&& ! isset( $extra['SELECT_ONLY'] ) )
	{
		$sql .= ' ORDER BY ';

		if ( Preferences( 'SORT' ) === 'Grade' )
		{
			$sql .= '(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID),';
		}

		// It would be easier to sort on full_name but postgres sometimes yields strange results.
		$sql .= 's.LAST_NAME,s.FIRST_NAME';

		if ( isset( $extra['ORDER'] ) )
		{
			$sql .= $extra['ORDER'];
		}
	}
	elseif ( isset( $extra['ORDER_BY'] ) )
	{
		$sql .= ' ORDER BY ' . $extra['ORDER_BY'];
	}

	// FJ bugfix if PDF, dont echo SQL.
	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ROSARIO_DEBUG ) // Activate only for debug purpose.
	{
		echo '<!--' . $sql . '-->';
	}

	// DBGet group arg.
	$group = ( isset( $extra['group'] ) ? $extra['group'] : array() );

	// Execute Query & return.
	return DBGet( $sql, $functions, $group );
}


/**
 * Make Contact Info
 * `DBGet()` callback
 *
 * @uses MakeTipMessage()
 *
 * @param  string $student_id Student ID.
 * @param  string $column     'CONTACT_INFO'.
 *
 * @return string Contact Info tooltip
 */
function makeContactInfo( $student_id, $column )
{
	global $contacts_RET;

	if ( ! function_exists( 'MakeTipMessage' ) )
	{
		require_once 'ProgramFunctions/TipMessage.fnc.php';
	}

	$tipmsg = '';

	foreach ( (array) $contacts_RET[ $student_id ] as $person )
	{
		if ( ! $person[1]['FIRST_NAME'] && ! $person[1]['LAST_NAME'] )
		{
			continue;
		}

		$tipmsg .= $person[1]['STUDENT_RELATION'] . ': ' .
			DisplayName(
				$person[1]['FIRST_NAME'],
				$person[1]['LAST_NAME'],
				$person[1]['MIDDLE_NAME']
			) . '<br />';

		$tipmsg .= '<table class="width-100p cellspacing-0">';

		if ( $person[1]['PHONE'] )
		{
			$tipmsg .= '<tr><td>' . _( 'Home Phone' ) .
			'</td><td>' . $person[1]['PHONE'] . '</td></tr>';
		}

		foreach ( (array) $person as $info )
		{
			if ( $info['TITLE']
				|| $info['VALUE'] )
			{
				$tipmsg .= '<tr><td>' . $info['TITLE'] .
				'</td><td>' . $info['VALUE'] . '</td></tr>';
			}
		}

		$tipmsg .= '</table>';
	}

	if ( ! $tipmsg )
	{
		return '';
	}

	return MakeTipMessage( $tipmsg, _( 'Contact Information' ), button( 'phone' ) );
}


/**
 * Remove .00 from float string
 *
 * @example if ( $field['TYPE'] === 'numeric' )	$functions[ $field_key ] = 'removeDot00';
 *
 * @see DBGet() callback
 *
 * @param  string $value  Value.
 * @param  string $column Column (optional). Defaults to ''.
 *
 * @return string Value without .00
 */
function removeDot00( $value, $column = '' )
{
	return str_replace( '.00', '', $value );
}


/**
 * Make / Format Email address
 *
 * @example if ( Config( 'STUDENTS_EMAIL_FIELD' ) === $field['ID'] ) $functions['EMAIL'] = 'makeEmail';
 *
 * @since 2.9.10
 *
 * @see DBGet() callback
 *
 * @param  string $email  Email address.
 * @param  string $column Column (optional). Defaults to ''.
 *
 * @return string Formatted email address
 */
function makeEmail( $email, $column = '' )
{
	$email = trim( $email );

	if ( $email == '' )
	{
		return '';
	}

	// Validate email.
	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) )
	{
		return $email;
	}

	return '<a href="mailto:' . $email . '">' . $email . '</a>';
}


/**
 * Make / Format Phone number
 *
 * @example if ( $view_other_RET['HOME_PHONE'][1]['VALUE'] === 'Y' ) $functions['PHONE'] = 'makePhone';
 *
 * @since 2.9.10 Wrap phone inside tel dial link.
 *
 * @see DBGet() callback
 *
 * @param  string $phone  Phone number.
 * @param  string $column Column (optional). Defaults to ''.
 *
 * @return string Formatted phone number
 */
function makePhone( $phone, $column = '' )
{
	global $locale;

	$phone = trim( $phone );

	// Keep numbers and extension.
	$unformatted_phone = preg_replace( "/[^0-9\+x]/", "", $phone );

	if ( $phone === '' )
	{
		return '';
	}
	elseif ( $unformatted_phone !== $phone )
	{
		// Phone already contains formatting chars, keep them.
		$fphone = $phone;
	}
	elseif ( mb_strlen( $phone ) === 9 )
	{
		// Spain: 012 345 678.
		$fphone = mb_substr( $phone, 0, 3 ) . ' ' .
			mb_substr( $phone, 3, 3 ) . ' ' . mb_substr( $phone, 6 );
	}
	elseif ( mb_strlen( $phone ) === 10 )
	{
		if ( mb_strpos( $locale, 'fr' ) === 0 )
		{
			// France: 01 23 45 67 89.
			$fphone = mb_substr( $phone, 0, 2 ) . ' ' .
				mb_substr( $phone, 2, 2 ) . ' ' . mb_substr( $phone, 4, 2 ) . ' ' .
				mb_substr( $phone, 6, 2 ) . ' ' . mb_substr( $phone, 8 );
		}
		else
		{
			// US: (012) 345-6789.
			$fphone = '(' . mb_substr( $phone, 0, 3 ) . ') ' .
				mb_substr( $phone, 3, 4 ) . '-' . mb_substr( $phone, 7 );
		}
	}
	elseif ( mb_strlen( $phone ) === 7 )
	{
		// US: 345-6789.
		$fphone = mb_substr( $phone, 0, 3 ) . '-' . mb_substr( $phone, 3 );
	}
	else
		$fphone = $phone;

	$dial_phone = $unformatted_phone;

	$extension_pos = mb_strpos( $dial_phone, 'x' );

	if ( $extension_pos !== false )
	{
		// Remove extension.
		$dial_phone = mb_substr( $dial_phone, 0, $extension_pos );
	}

	return '<a href="tel:' . $dial_phone . '" title="' . _( 'Call' ) . '" class="phone-link">' . $fphone . '</a>';
}


/**
 * Make Parents information popup
 *
 * @see DBGet() callback
 *
 * @global $THIS_RET       current return row
 * @global $view_other_RET checks $view_other_RET['ALL_CONTACTS'][1]['VALUE']
 * @global $_ROSARIO       checks $_ROSARIO['makeParents']
 *
 * @param  string $student_id Student ID.
 * @param  string $column     'PARENTS'.
 *
 * @return string Parents link to information popup or empty string if no Parents found
 */
function makeParents( $student_id, $column )
{
	global $THIS_RET,
		$view_other_RET,
		$_ROSARIO;

	if ( $THIS_RET['PARENTS'] != $student_id )
	{
		return $THIS_RET['PARENTS'];
	}

	if ( $THIS_RET['ADDRESS_ID'] == '' )
	{
		return '';
	}

	if ( $_ROSARIO['makeParents'] )
	{
		$constraint = " AND sjp.STUDENT_RELATION IS NULL";

		if ( $_ROSARIO['makeParents'] != '!' )
		{
			$constraint = " AND (lower(sjp.STUDENT_RELATION) LIKE '" .
				mb_strtolower( $_ROSARIO['makeParents'] ) . "%')";
		}
	}

	if ( $view_other_RET['ALL_CONTACTS'][1]['VALUE'] != 'Y' )
	{
		$constraint .= " AND sjp.CUSTODY='Y'";
	}

	$people_RET = DBGet( "SELECT p.PERSON_ID,p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME,
		sjp.CUSTODY,sjp.EMERGENCY
		FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p
		WHERE sjp.PERSON_ID=p.PERSON_ID
		AND sjp.STUDENT_ID='" . $student_id . "'
		AND sjp.ADDRESS_ID='" . $THIS_RET['ADDRESS_ID'] . "'" . $constraint .
		" ORDER BY sjp.CUSTODY,sjp.STUDENT_RELATION,p.LAST_NAME,p.FIRST_NAME" );

	if ( ! $people_RET )
	{
		return '';
	}

	foreach ( (array) $people_RET as $person )
	{
		$img = '';

		// FJ PrintClassLists with all contacts.
		if ( $person['CUSTODY'] == 'Y' )
		{
			$img = 'gavel';
		}
		elseif ( $person['EMERGENCY'] == 'Y' )
		{
			$img = 'emergency';
		}

		$parents .= '<div>' . ( ! empty( $img ) ? button( $img ) .'&nbsp;' : '' );

		if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			$parents .= $person['FIRST_NAME'] . ' ' . $person['LAST_NAME'] . '</div>';
		}
		else
		{
			$parents .= '<a href="#" onclick=\'popups.open(
					"Modules.php?modname=misc/ViewContact.php&person_id=' .
					$person['PERSON_ID'] . '&student_id=' . $student_id . '",
					"scrollbars=yes,resizable=yes,width=400,height=300"
				); return false;\'>' .
					$person['FIRST_NAME'] . ' ' . $person['LAST_NAME'] .
				'</a></div>';
		}
	}

	return $parents;
}


/**
 * DeCodeds
 * Decode codeds / exports type (custom) fields values.
 *
 * DBGet() callback function
 *
 * @since 2.9
 *
 * @param string $value  Value.
 * @param string $column Column.
 * @param string $table  'auto'|'STAFF' (optional). Defaults to 'auto'.
 */
function DeCodeds( $value, $column, $table = 'auto' )
{
	static $decodeds = array();

	$field = explode( '_', $column );

	if ( $table === 'auto' )
	{
		$table = $field[0];
	}

	if ( ! isset( $decodeds[ $column ] ) )
	{
		$RET = DBGet( "SELECT TYPE,SELECT_OPTIONS
			FROM " . DBEscapeIdentifier( $table . '_FIELDS' ) .
			" WHERE ID='" . $field[1] . "'" );

		if ( $RET[1]['TYPE'] === 'exports' )
		{
			$select_options = array();

			$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $RET[1]['SELECT_OPTIONS'] ) );

			foreach ( (array) $options as $option )
			{
				$option = explode( '|', $option );

				if ( $option[0] != ''
					&& $option[1] != '' )
				{
					$select_options[ $option[0] ] = $option[1];
				}
			}

			$RET[1]['SELECT_OPTIONS'] = $select_options;

			$decodeds[ $column ] = $RET[1];
		}
		else
			$decodeds[ $column ] = true;
	}

	if ( $value == '' )
	{
		return '';
	}

	if ( $decodeds[ $column ]['SELECT_OPTIONS'][ $value ] != '' )
	{
		if ( $_REQUEST['_ROSARIO_PDF']
			&& $_REQUEST['LO_save'] )
		{
			return $decodeds[ $column ]['SELECT_OPTIONS'][ $value ];
		}
		else
			return $value;
	}

	return '<span style="color:red">' . $value . '</span>';
}


/**
 * Make Checkbox
 *
 * DBGet() callback function
 *
 * @since 2.9
 *
 * @param  string $value  Checkbox value.
 * @param  string $column Column.
 *
 * @return string         'Yes' or 'No'.
 */
function makeCheckbox( $value, $column )
{
	return $value ? _( 'Yes' ) : _( 'No' );
}


/**
 * Make Textarea
 *
 * DBGet() callback function
 *
 * @uses ShowDown jQuery plugin for MarkDown rendering called using the .markdown-to-html CSS class
 * @uses ColorBox jQuery plugin to display various lines texts in ListOutput on mobiles called using the .rt2colorBox CSS class
 *
 * @since 2.9
 *
 * @param  string $value  Textarea value.
 * @param  string $column Column.
 *
 * @return string         Markdown rendered text.
 */
function makeTextarea( $value, $column )
{
	static $i = 1;

	return $value != '' ?
		'<div id="' . $column . $i++ . '" class="rt2colorBox"><div class="markdown-to-html">' .
			$value . '</div></div>' :
		'';
}


/**
 * Append:
 * - RosarioSIS ID(s)
 * - Last Name
 * - First Name
 * - Grade Level
 * - (Not) Grade Levels
 * - Address (City, State, Zip code)
 * Search terms to Students SQL WHERE part
 *
 * @example $extra['WHERE'] .= appendSQL( '', $extra );
 *
 * @global $_ROSARIO sets $_ROSARIO['SearchTerms']
 *
 * @uses SearchField()
 *
 * @param  string $sql   Students SQL query.
 * @param  array  $extra Extra for SQL request (optional). Defaults to empty array.
 *
 * @return string Appended SQL WHERE part
 */
function appendSQL( $sql, $extra = array() )
{
	global $_ROSARIO;

	$no_search_terms = isset( $extra['NoSearchTerms'] ) && $extra['NoSearchTerms'];

	// RosarioSIS ID(s).
	if ( isset( $_REQUEST['stuid'] )
		&& ! empty( $_REQUEST['stuid'] ) )
	{
		// FJ allow comma separated list of student IDs.
		$stuid_array = explode( ',', $_REQUEST['stuid'] );

		$stuids = array_filter( $stuid_array, function( $stuid ){
			return (string)(int)$stuid == $stuid && $stuid > 0;
		});

		if ( $stuids )
		{
			$stuids = implode( ',', $stuids );

			//$sql .= " AND ssm.STUDENT_ID IN '".$_REQUEST['stuid']."'";
			$sql .= " AND ssm.STUDENT_ID IN (" . $stuids . ")";

			if ( ! $no_search_terms )
			{
				$_ROSARIO['SearchTerms'] .= '<b>' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) .
					': </b>' . $stuids . '<br />';
			}
		}
	}

	// Last Name.
	if ( isset( $_REQUEST['last'] )
		&& $_REQUEST['last'] !== '' )
	{
		$last_name = array(
			'COLUMN' => 'LAST_NAME',
			'VALUE' => $_REQUEST['last'],
			'TITLE' => _( 'Last Name' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		);

		$sql .= SearchField( $last_name, 'student', $extra );
	}

	// First Name.
	if ( isset( $_REQUEST['first'] )
		&& $_REQUEST['first'] !== '' )
	{
		$first_name = array(
			'COLUMN' => 'FIRST_NAME',
			'VALUE' => $_REQUEST['first'],
			'TITLE' => _( 'First Name' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		);

		$sql .= SearchField( $first_name, 'student', $extra );
	}

	// Grade Level.
	if ( isset( $_REQUEST['grade'] )
		&& $_REQUEST['grade'] !== ''
		&& (string) (int) $_REQUEST['grade'] == $_REQUEST['grade']
		&& $_REQUEST['grade'] > 0 )
	{
		$sql .= " AND ssm.GRADE_ID = '" . $_REQUEST['grade'] . "'";

		if ( ! $no_search_terms )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Grade Level' ) . ': </b>' .
				GetGrade( $_REQUEST['grade'] ) . '<br />';
		}
	}

	// (Not) Grade Levels.
	if ( isset( $_REQUEST['grades'] )
		&& count( $_REQUEST['grades'] ) )
	{
		$is_grades_not = isset( $_REQUEST['grades_not'] ) && $_REQUEST['grades_not'] === 'Y';

		if ( ! $no_search_terms )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . ngettext( 'Grade', 'Grades', count( $_REQUEST['grades'] ) ) .
				': </b>' . ( $is_grades_not ? _( 'Excluded' ) . ' ' : '' );
		}

		$grade_list = $sep = '';

		foreach ( (array) $_REQUEST['grades'] as $grade_id => $y )
		{
			$grade_list .= $sep . "'" . $grade_id . "'";

			if ( ! $no_search_terms )
			{
				$_ROSARIO['SearchTerms'] .= $sep . GetGrade( $grade_id );
			}

			$sep = ',';
		}

		if ( ! $no_search_terms )
		{
			$_ROSARIO['SearchTerms'] .= '<br />';
		}

		$sql .= " AND ssm.GRADE_ID " . ( $is_grades_not ? 'NOT ' : '' ) . " IN (" . $grade_list . ")";
	}

	// Address (City, State, Zip code) (contains, case insensitive).
	if ( isset( $_REQUEST['addr'] )
		&& $_REQUEST['addr'] !== '' )
	{
		$sql .= " AND (LOWER(a.ADDRESS) LIKE '%" . mb_strtolower( $_REQUEST['addr'] ) .
			"%' OR LOWER(a.CITY) LIKE '" . mb_strtolower( $_REQUEST['addr'] ) .
			"%' OR LOWER(a.STATE)='" . mb_strtolower( $_REQUEST['addr'] ) .
			"' OR a.ZIPCODE LIKE '" . $_REQUEST['addr'] . "%')";

		if ( ! $no_search_terms )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Address contains' ) . ': </b>' .
				str_replace( "''", "'", $_REQUEST['addr'] ) . '<br />';
		}
	}

	return $sql;
}


/**
 * Get make / format function by field type.
 *
 * @example $extra['functions'][ 'CUSTOM_' . $field['ID'] ] = makeFieldTypeFunction( $field['TYPE'] );
 *
 * @since 2.9.10
 *
 * @param string  $field_type Field type.
 * @param string  $table      'auto'|'STAFF' (optional). Defaults to 'auto'.
 *
 * @return string             Make function name or empty if type not found.
 */
function makeFieldTypeFunction( $field_type, $table = 'auto' )
{
	switch ( $field_type )
	{
		case 'date':

			return 'ProperDate';

		case 'numeric':

			return 'removeDot00';

		case 'codeds':
		case 'exports':

			if ( $table === 'STAFF' )
			{
				return 'StaffDecodeds';
			}

			return 'DeCodeds';

		case 'radio':

			return 'makeCheckbox';

		case 'textarea':

			return 'makeTextarea';
	}

	return '';
}


/**
 * Get Display Name SQL (SELECT)
 * Must be used when retrieving Student or User full names.
 *
 * @since 3.7
 *
 * @example "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME FROM STUDENTS s"
 *
 * @uses Config DISPLAY_NAME option.
 *
 * @param  string $table_alias Table alias, optional.
 * @return string              Display name SQL (with table alias).
 */
function DisplayNameSQL( $table_alias = '' )
{
	$display_name = Config( 'DISPLAY_NAME' );

	// Values have %s. placeholders for table alias.
	$display_names = array(
		"FIRST_NAME||' '||LAST_NAME" => "%s.FIRST_NAME||' '||%s.LAST_NAME",
		"FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')" => "%s.FIRST_NAME||' '||%s.LAST_NAME||coalesce(' '||%s.NAME_SUFFIX,' ')",
		"FIRST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||LAST_NAME" => "%s.FIRST_NAME||coalesce(' '||%s.MIDDLE_NAME||' ',' ')||%s.LAST_NAME",
		"FIRST_NAME||', '||LAST_NAME||coalesce(' '||MIDDLE_NAME,' ')" => "%s.FIRST_NAME||', '||%s.LAST_NAME||coalesce(' '||%s.MIDDLE_NAME,' ')",
		"LAST_NAME||' '||FIRST_NAME" => "%s.LAST_NAME||' '||%s.FIRST_NAME",
		"LAST_NAME||', '||FIRST_NAME" => "%s.LAST_NAME||', '||%s.FIRST_NAME",
		"LAST_NAME||', '||FIRST_NAME||' '||COALESCE(MIDDLE_NAME,' ')" => "%s.LAST_NAME||', '||%s.FIRST_NAME||' '||COALESCE(%s.MIDDLE_NAME,' ')",
	);

	if ( ! isset( $display_names[ $display_name ] ) )
	{
		$display_name = key( $display_names );
	}

	if ( $table_alias )
	{
		$display_name = $display_names[ $display_name ];

		$display_name = str_replace( '%s', $table_alias, $display_name );
	}

	return $display_name;
}


/**
 * Get Display Name from values.
 * Must be used when displaying Student or User full names.
 *
 * @since 3.7
 *
 * @example echo DisplayName( 'John', 'Smith', 'Simon', 'Jr.' );
 *
 * @uses Config DISPLAY_NAME option.
 *
 * @param  string $first_name  First Name.
 * @param  string $last_name   Last Name.
 * @param  string $middle_name Middle Name (optional).
 * @param  string $name_suffix Suffix (optional).
 * @return string              Display Name.
 */
function DisplayName( $first_name, $last_name, $middle_name = '', $name_suffix = '' )
{
	$display_name = Config( 'DISPLAY_NAME' );

	// Values are not SQL formatted.
	$display_names = array(
		"FIRST_NAME||' '||LAST_NAME" => "FIRST_NAME LAST_NAME",
		"FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')" => "FIRST_NAME LAST_NAME NAME_SUFFIX",
		"FIRST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||LAST_NAME" => "FIRST_NAME MIDDLE_NAME LAST_NAME",
		"FIRST_NAME||', '||LAST_NAME||coalesce(' '||MIDDLE_NAME,' ')" => "FIRST_NAME, LAST_NAME MIDDLE_NAME",
		"LAST_NAME||' '||FIRST_NAME" => "LAST_NAME FIRST_NAME",
		"LAST_NAME||', '||FIRST_NAME" => "LAST_NAME, FIRST_NAME",
		"LAST_NAME||', '||FIRST_NAME||' '||COALESCE(MIDDLE_NAME,' ')" => "LAST_NAME, FIRST_NAME MIDDLE_NAME",
	);

	if ( ! isset( $display_names[ $display_name ] ) )
	{
		$display_name = $display_names["FIRST_NAME||' '||LAST_NAME"];
	}
	else
	{
		$display_name = $display_names[ $display_name ];
	}

	$display_name = str_replace(
		array( 'FIRST_NAME', 'LAST_NAME', 'MIDDLE_NAME', 'NAME_SUFFIX' ),
		array( $first_name, $last_name, $middle_name, $name_suffix ),
		$display_name
	);

	return $display_name;
}


/**
 * Make Tip Message containing Student or User Photo
 *
 * Callback for DBGet() column formatting
 *
 * @since 3.8
 *
 * @uses MakeStudentPhotoTipMessage()
 * @uses MakeUserPhotoTipMessage()
 *
 * @see ProgramFunctions/TipMessage.fnc.php
 *
 * @global $THIS_RET, see DBGet()
 *
 * @param  string $full_name Student or User Full Name
 * @param  string $column    'FULL_NAME'
 *
 * @return string Student or User Full Name + Tip Message containing Student Photo
 */
function makePhotoTipMessage( $full_name, $column )
{
	global $THIS_RET;

	require_once 'ProgramFunctions/TipMessage.fnc.php';

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $full_name;
	}

	if ( ! empty( $THIS_RET['STAFF_ID'] ) )
	{
		return MakeUserPhotoTipMessage( $THIS_RET['STAFF_ID'], $full_name );
	}
	elseif ( ! empty( $THIS_RET['STUDENT_ID'] ) )
	{
		return MakeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $full_name );
	}
}
