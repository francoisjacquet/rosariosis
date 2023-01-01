<?php

// @since 4.8 Search Parents by Student Grade Level.
function GetStaffList( &$extra = [] )
{
	$functions = [ 'PROFILE' => 'makeProfile' ];

	if ( User( 'PROFILE' ) !== 'admin'
		&& User( 'PROFILE' ) !== 'teacher' )
	{
		// Parents cannot get Staff lists.
		return;
	}

	if ( ! isset( $extra ) )
	{
		$extra = [];
	}

	if ( isset( $_REQUEST['advanced'] )
		&& $_REQUEST['advanced'] === 'Y' )
	{
		// Advanced Search: all Widgets.
		StaffWidgets( 'all', $extra );
	}
	else
	{
		StaffWidgets( 'user', $extra );
	}


	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	$extra['FROM'] = issetVal( $extra['FROM'], '' );

	$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

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
			FROM staff_fields cf,staff_field_categories sfc
			WHERE ((SELECT VALUE
				FROM program_user_config
				WHERE TITLE=cast(cf.ID AS char(10))
				AND PROGRAM='StaffFieldsView'
				AND USER_ID='" . User('STAFF_ID') . "')='Y'" .
				( ! empty( $extra['staff_fields']['view'] ) ?
					" OR cf.ID IN (" . $extra['staff_fields']['view'] . ")" :
					''
				) .
			")
			AND cf.CATEGORY_ID=sfc.ID
			ORDER BY sfc.SORT_ORDER IS NULL,sfc.SORT_ORDER,cf.SORT_ORDER,cf.TITLE" );

		foreach ( $view_fields_RET as $field )
		{
			$field_key = 'CUSTOM_' . $field['ID'];

			// @since 5.9 Move Email & Phone Staff Fields to custom fields.
			if ( $field['ID'] === '200000000' )
			{
				$field_key = 'EMAIL';

				$functions[ $field_key ] = 'makeEmail';
			}
			elseif ( $field['ID'] === '200000001' )
			{
				$functions[ $field_key ] = 'makePhone';
			}
			else
			{
				$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'], 'staff' );
			}

			$extra['columns_after'][ $field_key ] = $field['TITLE'];

			$select .= ',s.' . $field_key;
		}

		$extra['SELECT'] .= $select;
	}
	else
	{
		if ( empty( $extra['columns_after'] ) )
		{
			$extra['columns_after'] = [];
		}

		if ( ! empty( $extra['staff_fields']['view'] ) )
		{
			$view_fields_RET = DBGet( "SELECT cf.ID,cf.TYPE,cf.TITLE
				FROM staff_fields cf
				WHERE cf.ID IN (" . $extra['staff_fields']['view'] . ")
				ORDER BY cf.SORT_ORDER IS NULL,cf.SORT_ORDER,cf.TITLE" );

			foreach ( $view_fields_RET as $field )
			{
				$field_key = 'CUSTOM_' . $field['ID'];
				$extra['columns_after'][ $field_key ] = $field['TITLE'];

				if ( $field['ID'] === '200000000' )
				{
					$field_key = 'EMAIL';

					$functions[ $field_key ] = 'makeEmail';
				}
				elseif ( $field['ID'] === '200000001' )
				{
					$functions[ $field_key ] = 'makePhone';
				}
				else
				{
					$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'], 'staff' );
				}
			}

			$extra['SELECT'] .= $select;
		}
	}

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$extra['WHERE'] .= " AND (s.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			OR s.PROFILE='parent'
			AND exists(SELECT ''
				FROM students_join_users _sju,student_enrollment _sem,schedule _ss
				WHERE _sju.STAFF_ID=s.STAFF_ID
				AND _sem.STUDENT_ID=_sju.STUDENT_ID
				AND _sem.SYEAR='" . UserSyear() . "'
				AND _ss.STUDENT_ID=_sem.STUDENT_ID
				AND _ss.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'";

		if ( ! isset( $_REQUEST['include_inactive'] )
			|| $_REQUEST['include_inactive'] !== 'Y' )
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
			" FROM staff s " . $extra['FROM'] .
			" WHERE	s.SYEAR='" . UserSyear() . "'";

	if ( ! isset( $_REQUEST['_search_all_schools'] )
		|| $_REQUEST['_search_all_schools'] !== 'Y' )
	{
		$sql .= " AND (s.SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN s.SCHOOLS)>0) ";
	}
	// Search All Schools: if user is not assigned to "All Schools".
	elseif ( trim( User( 'SCHOOLS' ), ',' ) )
	{
		// Restrict Search All Schools to user schools.
		$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

		$sql_schools_like = implode( ",' IN s.SCHOOLS)>0 OR position(',", $sql_schools_like );

		$sql_schools_like = "position('," . $sql_schools_like . ",' IN s.SCHOOLS)>0";

		$sql .= " AND (s.SCHOOLS IS NULL OR " . $sql_schools_like . ") ";
	}

	// Extra WHERE.
	$sql .= $extra['WHERE'] . ' ';

	// ORDER BY.
	if ( ! isset( $extra['ORDER_BY'] )
		/*&& ! isset( $extra['SELECT_ONLY'] )*/ )
	{
		$sql .= ' ORDER BY FULL_NAME';

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
function appendStaffSQL( $sql, $extra = [] )
{
	global $_ROSARIO;

	$_ROSARIO['SearchTerms'] = issetVal( $_ROSARIO['SearchTerms'], '' );

	$no_search_terms = isset( $extra['NoSearchTerms'] ) && $extra['NoSearchTerms'];

	if ( isset( $_REQUEST['usrid'] )
		&& $_REQUEST['usrid'] )
	{
		// FJ allow comma separated list of staff IDs
		$usrid_array = explode( ',', $_REQUEST['usrid'] );

		$usrids = [];

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
				$_ROSARIO['SearchTerms'] .= '<b>' . _( 'User ID' ) . ':</b> ' . $usrids . '<br />';
			}
		}
	}

	// Last Name.
	if ( isset( $_REQUEST['staff_last'] )
		&& $_REQUEST['staff_last'] !== '' )
	{
		$last_name = [
			'COLUMN_NAME' => 'LAST_NAME',
			'VALUE' => $_REQUEST['staff_last'],
			'TITLE' => _( 'Last Name' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		];

		$sql .= SearchField( $last_name, 'staff', $extra );
	}

	// First Name.
	if ( isset( $_REQUEST['staff_first'] )
		&& $_REQUEST['staff_first'] !== '' )
	{
		$first_name = [
			'COLUMN_NAME' => 'FIRST_NAME',
			'VALUE' => $_REQUEST['staff_first'],
			'TITLE' => _( 'First Name' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		];

		$sql .= SearchField( $first_name, 'staff', $extra );
	}

	// Profile.
	if ( isset( $_REQUEST['profile'] )
		&& $_REQUEST['profile'] !== '' )
	{
		$options = [
			'teacher' => _( 'Teacher' ),
			'parent' => _( 'Parent' ),
		];

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$options = [
				'admin' => _( 'Administrator' ),
				'teacher' => _( 'Teacher' ),
				'parent' => _( 'Parent' ),
				'none' => _( 'No Access' ),
			];
		}

		if ( ! empty( $extra['profile'] ) )
		{
			$options = [ $extra['profile'] => $options[ $extra['profile'] ] ];
		}

		if ( isset( $options[ $_REQUEST['profile'] ] ) )
		{
			$sql .= " AND s.PROFILE='" . $_REQUEST['profile'] . "' ";

			$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Profile' ) . ':</b> ' .
				$options[ $_REQUEST['profile'] ] . '<br />';

			if ( ! empty( $_REQUEST['student_grade_level'] )
				&& $_REQUEST['profile'] === 'parent' )
			{
				// @since 4.8 Search Parents by Student Grade Level.
				$sql .= " AND s.STAFF_ID IN(SELECT _sju.STAFF_ID
					FROM students_join_users _sju,student_enrollment _sem
					WHERE _sem.STUDENT_ID=_sju.STUDENT_ID
					AND _sem.SYEAR='" . UserSyear() . "'
					AND _sem.GRADE_ID='" . (int) $_REQUEST['student_grade_level'] . "'";

				if ( empty( $_REQUEST['include_inactive'] ) )
				{
					$sql .= " AND ('" . DBDate() . "'>=_sem.START_DATE
						AND ('" . DBDate() . "'<=_sem.END_DATE OR _sem.END_DATE IS NULL))";
				}

				$sql .= ")";

				$student_grade_level = DBGetOne( "SELECT TITLE
					FROM school_gradelevels
					WHERE SCHOOL_ID='" . UserSchool() . "'
					AND ID='" . (int) $_REQUEST['student_grade_level'] . "'" );

				$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Student Grade Level' ) . ':</b> ' .
					$student_grade_level . '<br />';
			}
		}
	}

	// Username.
	if ( isset( $_REQUEST['username'] )
		&& $_REQUEST['username'] !== '' )
	{
		$username = [
			'COLUMN_NAME' => 'USERNAME',
			'VALUE' => $_REQUEST['username'],
			'TITLE' => _( 'Username' ),
			'TYPE' => 'text',
			'SELECT_OPTIONS' => null,
		];

		$sql .= SearchField( $username, 'staff', $extra );
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
			FROM user_profiles", [], [ 'ID' ] );
	}

	$return = $value;

	if ( $value == 'admin' )
	{
		$return = _( 'Administrator' );
	}
	elseif ( $value == 'teacher' )
	{
		$return = _( 'Teacher' );
	}
	elseif ( $value == 'parent' )
	{
		$return = _( 'Parent' );
	}
	elseif ( $value == 'none' )
	{
		$return = _( 'No Access' );
	}

	if ( ! empty( $THIS_RET['PROFILE_ID'] ) )
	{
		if ( $THIS_RET['PROFILE_ID'] <= 3 )
		{
			return $return;
		}

		$return .= ' / ' . ( ! empty( $profiles_RET[$THIS_RET['PROFILE_ID']] ) ?
			$profiles_RET[$THIS_RET['PROFILE_ID']][1]['TITLE'] :
			'<span style="color:red">' . $THIS_RET['PROFILE_ID'] . '</span>' );
	}
	elseif ( $value != 'none' )
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

		return ProperDateTime( $value, 'short' );
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
	return DeCodeds( $value, $column, 'staff' );
}
