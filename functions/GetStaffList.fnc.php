<?php

function GetStaffList(& $extra)
{	global $profiles_RET;

	$functions = array('PROFILE' => 'makeProfile');
	switch (User('PROFILE'))
	{
		case 'admin':
		case 'teacher':

			//FJ fix Advanced Search
			if ( isset( $_REQUEST['advanced'] )
				&& $_REQUEST['advanced'] === 'Y' )
			{
				StaffWidgets( 'all', $extra );
			}

			$extra['WHERE'] .= appendStaffSQL( '', $extra );

			$extra['WHERE'] .= CustomFields( 'where', 'staff', $extra );

			// Expanded View.
			if ( isset( $_REQUEST['expanded_view'] )
				&& $_REQUEST['expanded_view'] === 'true' )
			{
				$select = ',LAST_LOGIN';
				$extra['columns_after']['LAST_LOGIN'] = _('Last Login');
				$functions['LAST_LOGIN'] = 'makeLogin';

				//FJ add failed login to expanded view
				$select .= ',FAILED_LOGIN';
				$extra['columns_after']['FAILED_LOGIN'] = _('Failed Login');
				$functions['FAILED_LOGIN'] = 'makeLogin';

				$view_fields_RET = DBGet(DBQuery("SELECT cf.ID,cf.TYPE,cf.TITLE
				FROM STAFF_FIELDS cf
				WHERE ((SELECT VALUE FROM PROGRAM_USER_CONFIG WHERE TITLE=cast(cf.ID AS TEXT) AND PROGRAM='StaffFieldsView' AND USER_ID='".User('STAFF_ID')."')='Y'".($extra['staff_fields']['view']?" OR cf.ID IN (".$extra['staff_fields']['view'].")":'').")
				ORDER BY cf.SORT_ORDER,cf.TITLE"));

				foreach ( (array) $view_fields_RET as $field )
				{
					$field_key = 'CUSTOM_' . $field['ID'];
					$extra['columns_after'][ $field_key ] = $field['TITLE'];

					$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'], 'STAFF' );

					$select .= ',s.' . $field_key;
				}

				// User Fields: search Email Address & Phone.
				$view_other_RET = DBGet( DBQuery( "SELECT TITLE,VALUE
					FROM PROGRAM_USER_CONFIG
					WHERE PROGRAM='StaffFieldsView'
					AND TITLE IN ('EMAIL','PHONE')
					AND USER_ID='" . User( 'STAFF_ID' ) . "'"), array(), array( 'TITLE' ) );

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
				if ( ! $extra['columns_after'] )
				{
					$extra['columns_after'] = array();
				}

				if ( $extra['staff_fields']['view'] )
				{
					$view_fields_RET = DBGet( DBQuery( "SELECT cf.ID,cf.TYPE,cf.TITLE
						FROM STAFF_FIELDS cf
						WHERE cf.ID IN (" . $extra['staff_fields']['view'] . ")
						ORDER BY cf.SORT_ORDER,cf.TITLE" ) );

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
				$extra['WHERE'] .= " AND (s.STAFF_ID='".User('STAFF_ID')."' OR s.PROFILE='parent' AND exists(SELECT '' FROM STUDENTS_JOIN_USERS _sju,STUDENT_ENROLLMENT _sem,SCHEDULE _ss WHERE _sju.STAFF_ID=s.STAFF_ID AND _sem.STUDENT_ID=_sju.STUDENT_ID AND _sem.SYEAR='".UserSYEAR()."' AND _ss.STUDENT_ID=_sem.STUDENT_ID AND _ss.COURSE_PERIOD_ID='".UserCoursePeriod()."'";
				if ( $_REQUEST['include_inactive']!='Y')
					$extra['WHERE'] .= " AND _ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") AND ('".DBDate()."'>=_sem.START_DATE AND ('".DBDate()."'<=_sem.END_DATE OR _sem.END_DATE IS NULL)) AND ('".DBDate()."'>=_ss.START_DATE AND ('".DBDate()."'<=_ss.END_DATE OR _ss.END_DATE IS NULL))";
				$extra['WHERE'] .= "))";
			}

			$profiles_RET = DBGet(DBQuery("SELECT * FROM USER_PROFILES"),array(),array('ID'));
			$sql = "SELECT
					s.LAST_NAME||', '||s.FIRST_NAME||' '||COALESCE(s.MIDDLE_NAME,' ') AS FULL_NAME,
					s.PROFILE,s.PROFILE_ID,s.STAFF_ID,s.SCHOOLS ".$extra['SELECT']."
				FROM
					STAFF s ".$extra['FROM']."
				WHERE
					s.SYEAR='".UserSyear()."'";

			if ( $_REQUEST['_search_all_schools']!='Y')
				$sql .= " AND (s.SCHOOLS LIKE '%,".UserSchool().",%' OR s.SCHOOLS IS NULL OR s.SCHOOLS='') ";

			$sql .= $extra['WHERE'].' ';

			// it would be easier to sort on full_name but postgres sometimes yields strange results
			$sql .= 'ORDER BY s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME';

			if ( $extra['functions'])
				$functions += $extra['functions'];

			return DBGet(DBQuery($sql),$functions);
		break;
	}
}

function appendStaffSQL($sql,$extra)
{	global $_ROSARIO;

	if ( $_REQUEST['usrid'])
	{
//FJ allow comma separated list of staff IDs
		$usrid_array = explode(',', $_REQUEST['usrid']);
		$usrids = array();
		foreach ($usrid_array as $usrid)
		{
			if (is_numeric($usrid))
				$usrids[] = $usrid;
		}
		if ( !empty($usrids))
		{
			$usrids = implode(',', $usrids);
			//$sql .= " AND s.STAFF_ID='".$_REQUEST['usrid']."'";
			$sql .= " AND s.STAFF_ID IN (".$usrids.")";

			if ( ! $extra['NoSearchTerms'])
				$_ROSARIO['SearchTerms'] .= '<b>'._('User ID').': </b>'.$usrids.'<br />';
		}
	}

	if ( $_REQUEST['last'])
	{
		$sql .= " AND UPPER(s.LAST_NAME) LIKE '".mb_strtoupper($_REQUEST['last'])."%'";

		if ( ! $extra['NoSearchTerms'])
			$_ROSARIO['SearchTerms'] .= '<b>'._('Last Name starts with').': </b>'.str_replace("''", "'", $_REQUEST['last']).'<br />';
	}

	if ( $_REQUEST['first'])
	{
		$sql .= " AND UPPER(s.FIRST_NAME) LIKE '".mb_strtoupper($_REQUEST['first'])."%'";

		if ( ! $extra['NoSearchTerms'])
			$_ROSARIO['SearchTerms'] .= '<b>'._('First Name starts with').': </b>'.str_replace("''", "'", $_REQUEST['first']).'<br />';
	}

	if ( $_REQUEST['profile'])
	{
		$sql .= " AND s.PROFILE='".$_REQUEST['profile']."'";

		if ( ! $extra['NoSearchTerms'])
			$_ROSARIO['SearchTerms'] .= '<b>'._('Profile').': </b>'._(UCFirst($_REQUEST['profile'])).'<br />';
	}

	if ( $_REQUEST['username'])
	{
		$sql .= " AND UPPER(s.USERNAME) LIKE '".mb_strtoupper($_REQUEST['username'])."%'";

		if ( ! $extra['NoSearchTerms'])
			$_ROSARIO['SearchTerms'] .= '<b>'._('UserName starts with').': </b>'.str_replace("''", "'", $_REQUEST['username']).'<br />';
	}

	return $sql;
}

function makeProfile($value)
{	global $THIS_RET,$profiles_RET;

	if ( $value=='admin')
		$return = _('Administrator');
	elseif ( $value=='teacher')
		$return = _('Teacher');
	elseif ( $value=='parent')
		$return = _('Parent');
	elseif ( $value=='none')
		$return = _('No Access');
	else $return = $value;

	if ( $THIS_RET['PROFILE_ID'])
		$return .= ' / '.($profiles_RET[$THIS_RET['PROFILE_ID']]?$profiles_RET[$THIS_RET['PROFILE_ID']][1]['TITLE']:'<span style="color:red">'.$THIS_RET['PROFILE_ID'].'</span>');
	elseif ( $value!='none')
		$return .= _( ' w/Custom' );

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
