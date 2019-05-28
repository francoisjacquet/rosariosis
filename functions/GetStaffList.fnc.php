<?php

// @since 4.8 Search Parents by Student Grade Level.
function GetStaffList( &$extra = array() )
{
	global $profiles_RET;

	$functions = array( 'PROFILE' => 'makeProfile' );

	if ( User( 'PROFILE' ) !== 'admin'
		&& User( 'PROFILE' ) !== 'teacher' )
	{
		// Parents cannot get Staff lists.
		return;
	}

	if ( isset( $_REQUEST['advanced'] )
		&& $_REQUEST['advanced'] === 'Y' )
	{
		// Advanced Search: all Widgets.
		StaffWidgets( 'all', $extra );
	}

	if ( ! isset( $extra['SELECT'] ) )
	{
		$extra['SELECT'] = '';
	}

	if ( ! isset( $extra['FROM'] ) )
	{
		$extra['FROM'] = '';
	}

	if ( ! isset( $extra['WHERE'] ) )
	{
		$extra['WHERE'] = '';
	}

	$extra['WHERE'] .= appendStaffSQL( '', $extra );

	$extra['WHERE'] .= CustomFields( 'where', 'staff', $extra );

	// Expanded View.
	if ( isset( $_REQUEST['expanded_view'] )
		&& $_REQUEST['expanded_view'] === 'true' )
	{
		/**
		 * Add User Photo Tip Message to Expanded View
		 *
		 * @since 3.8
		 */
		if ( empty( $functions['FULL_NAME'] ) )
		{
			$functions['FULL_NAME'] = 'makePhotoTipMessage';
		}

		$select = ',LAST_LOGIN';
		$extra['columns_after']['LAST_LOGIN'] = _( 'Last Login' );
		$functions['LAST_LOGIN'] = 'makeLogin';

		//FJ add failed login to expanded view
		$select .= ',FAILED_LOGIN';
		$extra['columns_after']['FAILED_LOGIN'] = _( 'Failed Login' );
		$functions['FAILED_LOGIN'] = 'makeLogin';

		$view_fields_RET = DBGet( "SELECT cf.ID,cf.TYPE,cf.TITLE
			FROM STAFF_FIELDS cf
			WHERE ((SELECT VALUE
				FROM PROGRAM_USER_CONFIG
				WHERE TITLE=cast(cf.ID AS TEXT)
				AND PROGRAM='StaffFieldsView'
				AND USER_ID='" . User('STAFF_ID') . "')='Y'" .
				( $extra['staff_fields']['view'] ?
					" OR cf.ID IN (" . $extra['staff_fields']['view'] . ")" :
					''
				) .
			")
			ORDER BY cf.SORT_ORDER,cf.TITLE" );

		foreach ( (array) $view_fields_RET as $field )
		{
			$field_key = 'CUSTOM_' . $field['ID'];
			$extra['columns_after'][ $field_key ] = $field['TITLE'];

			$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'], 'STAFF' );

			$select .= ',s.' . $field_key;
		}

		// User Fields: search Email Address & Phone.
		$view_other_RET = DBGet( "SELECT TITLE,VALUE
			FROM PROGRAM_USER_CONFIG
			WHERE PROGRAM='StaffFieldsView'
			AND TITLE IN ('EMAIL','PHONE')
			AND USER_ID='" . User( 'STAFF_ID' ) . "'", array(), array( 'TITLE' ) );

		if ( $view_other_RET['EMAIL'][1]['VALUE'] === 'Y' )
		{
			$extra['columns_after']['EMAIL'] = _( 'Email Address' );

			$functions['EMAIL'] = 'makeEmail';

			$select .= ',s.EMAIL';
		}

		if ( $view_other_RET['PHONE'][1]['VALUE'] === 'Y' )
		{
			$extra['columns_after']['PHONE'] = _( 'Phone Number' );

			$functions['PHONE'] = 'makePhone';

			$select .= ',s.PHONE';
		}

		$extra['SELECT'] .= $select;
	}
	else
	{
		if ( empty( $extra['columns_after'] ) )
		{
			$extra['columns_after'] = array();
		}

		if ( ! empty( $extra['staff_fields']['view'] ) )
		{
			$view_fields_RET = DBGet( "SELECT cf.ID,cf.TYPE,cf.TITLE
				FROM STAFF_FIELDS cf
				WHERE cf.ID IN (" . $extra['staff_fields']['view'] . ")
				ORDER BY cf.SORT_ORDER,cf.TITLE" );

			foreach ( (array) $view_fields_RET as $field )
			{
				$field_key = 'CUSTOM_' . $field['ID'];
				$extra['columns_after'][ $field_key ] = $field['TITLE'];

				$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'], 'STAFF' );
			}

			$extra['SELECT'] .= $select;
		}
	}

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$extra['WHERE'] .= " AND (s.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			OR s.PROFILE='parent'
			AND exists(SELECT ''
				FROM STUDENTS_JOIN_USERS _sju,STUDENT_ENROLLMENT _sem,SCHEDULE _ss
				WHERE _sju.STAFF_ID=s.STAFF_ID
				AND _sem.STUDENT_ID=_sju.STUDENT_ID
				AND _sem.SYEAR='" . UserSyear() . "'
				AND _ss.STUDENT_ID=_sem.STUDENT_ID
				AND _ss.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'";

		if ( $_REQUEST['include_inactive'] !== 'Y' )
		{
			$extra['WHERE'] .= " AND _ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")
				AND ('" . DBDate() . "'>=_sem.START_DATE
				AND ('" . DBDate() . "'<=_sem.END_DATE OR _sem.END_DATE IS NULL))
				AND ('" . DBDate() . "'>=_ss.START_DATE
					AND ('" . DBDate() . "'<=_ss.END_DATE OR _ss.END_DATE IS NULL))";
		}

		$extra['WHERE'] .= '))';
	}

	$sql = "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.PROFILE,s.PROFILE_ID,s.STAFF_ID,s.SCHOOLS " . $extra['SELECT'] .
			" FROM STAFF s " . $extra['FROM'] .
			" WHERE	s.SYEAR='" . UserSyear() . "'";

	if ( ! isset( $_REQUEST['_search_all_schools'] )
		|| $_REQUEST['_search_all_schools'] !== 'Y' )
	{
		$sql .= " AND (s.SCHOOLS LIKE '%," . UserSchool() . ",%' OR s.SCHOOLS IS NULL OR s.SCHOOLS='') ";
	}
	// Search All Schools: if user is not assigned to "All Schools".
	elseif ( trim( User( 'SCHOOLS' ), ',' ) )
	{
		// Restrict Search All Schools to user schools.
		$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

		$sql_schools_like = implode( ",%' OR s.SCHOOLS LIKE '%,", $sql_schools_like );

		$sql_schools_like = "s.SCHOOLS LIKE '%," . $sql_schools_like . ",%'";

		$sql .= " AND (" . $sql_schools_like . " OR s.SCHOOLS IS NULL OR s.SCHOOLS='') ";
	}

	// Extra WHERE.
	$sql .= $extra['WHERE'] . ' ';

	// ORDER BY.
	if ( ! isset( $extra['ORDER_BY'] )
		/*&& ! isset( $extra['SELECT_ONLY'] )*/ )
	{
		// It would be easier to sort on full_name but postgres sometimes yields strange results.
		$sql .= ' ORDER BY s.LAST_NAME,s.FIRST_NAME';

		if ( isset( $extra['ORDER'] ) )
		{
			$sql .= $extra['ORDER'];
		}
	}
	elseif ( isset( $extra['ORDER_BY'] ) )
	{
		$sql .= ' ORDER BY ' . $extra['ORDER_BY'];
	}

	if ( isset( $extra['functions'] ) )
	{
		// Extra functions.
		$functions += (array) $extra['functions'];
	}

	return DBGet( $sql, $functions );
}


/**
 * Append:
 * - User ID(s)
 * - Last Name
 * - First Name
 * - Profile
 * - Username
 * Search terms to Students SQL WHERE part
 *
 * @example $extra['WHERE'] .= appendStaffSQL( '', $extra );
 *
 * @global $_ROSARIO sets $_ROSARIO['SearchTerms']
 *
 * @uses SearchField()
 *
 * @param  string $sql   Staff SQL query.
 * @param  array  $extra Extra for SQL request (optional). Defaults to empty array.
 *
 * @return string Appended SQL WHERE part
 */
function appendStaffSQL( $sql, $extra = array() )
{
	global $_ROSARIO;

	$no_search_terms = isset( $extra['NoSearchTerms'] ) && $extra['NoSearchTerms'];

	$search_terms = '';

	if ( isset( $_REQUEST['usrid'] )
		&& $_REQUEST['usrid'] )
	{
		// FJ allow comma separated list of staff IDs
		$usrid_array = explode( ',', $_REQUEST['usrid'] );

		$usrids = array();

		foreach ( $usrid_array as $usrid )
		{
			if ( is_numeric( $usrid ) )
			{
				$usrids[] = $usrid;
			}
		}

		if ( $usrids )
		{
			$usrids = implode( ',', $usrids );

			$sql .= " AND s.STAFF_ID IN (" . $usrids . ")";

			if ( ! $no_search_terms )
			{
				$search_terms .= '<b>' . _( 'User ID' ) . ':</b> ' . $usrids . '<br />';
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

		$sql .= SearchField( $last_name, 'staff', $extra );
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

		$sql .= SearchField( $first_name, 'staff', $extra );
	}

	// Profile.
	if ( isset( $_REQUEST['profile'] )
		&& $_REQUEST['profile'] !== '' )
	{
		if ( User( 'PROFILE' ) === 'admin' )
		{
			$options = array(
				'admin' => _( 'Administrator' ),
				'teacher' => _( 'Teacher' ),
				'parent' => _( 'Parent' ),
				'none' => _( 'No Access' ),
			);
		}
		else
		{
			$options = array(
				'teacher' => _( 'Teacher' ),
				'parent' => _( 'Parent' ),
			);
		}

		if ( $extra['profile'] )
		{
			$options = array( $extra['profile'] => $options[ $extra['profile'] ] );
		}

		if ( isset( $options[ $_REQUEST['profile'] ] ) )
		{
			$sql .= " AND s.PROFILE='" . $_REQUEST['profile'] . "' ";

			$search_terms .= '<b>' . _( 'Profile' ) . ':</b> ' .
				$options[ $_REQUEST['profile'] ] . '<br />';

			if ( ! empty( $_REQUEST['student_grade_level'] )
				&& $_REQUEST['profile'] === 'parent' )
			{
				// @since 4.8 Search Parents by Student Grade Level.
				$sql .= " AND s.STAFF_ID IN(SELECT _sju.STAFF_ID
					FROM STUDENTS_JOIN_USERS _sju,STUDENT_ENROLLMENT _sem
					WHERE _sem.STUDENT_ID=_sju.STUDENT_ID
					AND _sem.SYEAR='" . UserSyear() . "'
					AND _sem.GRADE_ID='" . $_REQUEST['student_grade_level'] . "'";

				if ( $_REQUEST['include_inactive'] !== 'Y' )
				{
					$sql .= " AND ('" . DBDate() . "'>=_sem.START_DATE
						AND ('" . DBDate() . "'<=_sem.END_DATE OR _sem.END_DATE IS NULL))";
				}

				$sql .= ")";

				$student_grade_level = DBGetOne( "SELECT TITLE
					FROM SCHOOL_GRADELEVELS
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND ID='" . $_REQUEST['student_grade_level'] . "'" );

				$search_terms .= '<b>' . _( 'Student Grade Level' ) . ':</b> ' .
					$student_grade_level . '<br />';
			}
		}
	}

	// Username.
	if ( isset( $_REQUEST['username'] )
		&& $_REQUEST['username'] !== '' )
	{
		$username = array(
			'COLUMN' => 'USERNAME',
			'VALUE' => $_REQUEST['username'],
			'TITLE' => _( 'Username' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		);

		$sql .= SearchField( $username, 'staff', $extra );
	}

	if ( $search_terms )
	{
		$_ROSARIO['SearchTerms'] = isset( $_ROSARIO['SearchTerms'] ) ? $_ROSARIO['SearchTerms'] : '';

		$_ROSARIO['SearchTerms'] .= $search_terms;
	}

	return $sql;
}

function makeProfile( $value, $column = 'PROFILE' )
{
	global $THIS_RET;

	static $profiles_RET;

	if ( empty( $profiles_RET ) )
	{
		$profiles_RET = DBGet( "SELECT ID,PROFILE,TITLE
			FROM USER_PROFILES", array(), array( 'ID' ) );
	}

	if ( $value=='admin')
		$return = _('Administrator');
	elseif ( $value=='teacher')
		$return = _('Teacher');
	elseif ( $value=='parent')
		$return = _('Parent');
	elseif ( $value=='none')
		$return = _('No Access');
	else $return = $value;

	if ( $THIS_RET['PROFILE_ID'] )
	{
		if ( $THIS_RET['PROFILE_ID'] <= 3 )
		{
			return $return;
		}

		$return .= ' / ' . ( $profiles_RET[$THIS_RET['PROFILE_ID']] ?
			$profiles_RET[$THIS_RET['PROFILE_ID']][1]['TITLE'] :
			'<span style="color:red">' . $THIS_RET['PROFILE_ID'] . '</span>' );
	}
	elseif ( $value!='none')
	{
		$return .= _( ' w/Custom' );
	}

	return $return;
}

function makeLogin( $value, $column = 'LAST_LOGIN' )
{
	if ( $column === 'LAST_LOGIN' )
	{
		if ( empty( $value ) )
		{
			return button( 'x' );
		}
		else
		{
			return ProperDateTime( $value, 'short' );
		}
	}

	// FJ add failed login to expanded view.
	// Column should be FAILED_LOGIN.
	return empty( $value ) ? '0' : $value;
}


/**
 * Staff DeCodeds
 * Decode codeds / exports type (custom staff) fields values.
 *
 * DBGet() callback function
 *
 * @uses DeCodeds() function.
 *
 * @param string $value  Value.
 * @param string $column Column.
 */
function StaffDeCodeds( $value, $column )
{
	return DeCodeds( $value, $column, 'STAFF' );
}
