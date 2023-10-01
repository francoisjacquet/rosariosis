<?php

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	//if (UserStudentID() && User( 'PROFILE' ) !== 'parent' && User( 'PROFILE' ) !== 'student' && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))

	switch ( User( 'PROFILE' ) )
	{
		case 'admin':
		case 'teacher':
			//if ( $_SESSION['student_id'] && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))

			if ( UserStudentID()
				&& $_REQUEST['student_id'] === 'new' )
			{
				unset( $_SESSION['student_id'] );
			}

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF(
				$_SESSION['_REQUEST_vars'],
				[ 'bottom_back', 'advanced' ]
			);

			if ( empty( $_SESSION['Back_PHP_SELF'] )
				|| $_SESSION['Back_PHP_SELF'] !== 'student' )
			{
				$_SESSION['Back_PHP_SELF'] = 'student';

				unset( $_SESSION['List_PHP_SELF'] );
			}

			echo '<br />';

			PopTable(
				'header',
				! empty( $extra['search_title'] ) ? $extra['search_title'] : _( 'Find a Student' )
			);

			echo '<form name="search" id="search" action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=' . $_REQUEST['modfunc'] .
				'&search_modfunc=list&next_modname=' . $_REQUEST['next_modname'] .
				'&advanced=' . ( ! empty( $_REQUEST['advanced'] ) ? $_REQUEST['advanced'] : '' ) .
				( ! empty( $extra['action'] ) ? $extra['action'] : '' )  ) . '" method="GET">';

			echo '<table class="width-100p col1-align-right" id="general_table">';

			Search( 'general_info', issetVal( $extra['grades'], [] ) );

			if ( ! isset( $extra ) )
			{
				$extra = [];
			}

			Widgets( 'user', $extra );

			Search(
				'student_fields',
				isset( $extra['student_fields'] ) && is_array( $extra['student_fields'] ) ?
				$extra['student_fields'] :
				[]
			);

			echo '</table><div class="center">';

			if ( ! empty( $extra['search_second_col'] ) )
			{
				echo $extra['search_second_col'];
			}

			if ( User( 'PROFILE' ) === 'admin' )
			{
				echo '<label><input type="checkbox" name="address_group" value="Y"' .
				( Preferences( 'DEFAULT_FAMILIES' ) == 'Y' ? ' checked' : '' ) . '>&nbsp;' .
				_( 'Group by Family' ) . '</label><br />';

				// FJ if only one school, no Search All Schools option.
				// Restrict Search All Schools to user schools.

				if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
					&& ( ! trim( (string) User( 'SCHOOLS' ), ',' )
						|| mb_substr_count( User( 'SCHOOLS' ), ',' ) > 2 ) )
				{
					echo '<label><input type="checkbox" name="_search_all_schools" value="Y"' .
					( Preferences( 'DEFAULT_ALL_SCHOOLS' ) == 'Y' ? ' checked' : '' ) . '>&nbsp;' .
					_( 'Search All Schools' ) . '</label><br />';
				}
			}

			echo '<label><input type="checkbox" name="include_inactive" value="Y">&nbsp;' .
			_( 'Include Inactive Students' ) . '</label><br />';

			echo '<br />' . Buttons( _( 'Submit' ) ) . '</div><br />';

			if ( ! empty( $extra['search'] )
				|| ! empty( $extra['extra_search'] )
				|| ! empty( $extra['second_col'] ) )
			{
				echo '<table class="widefat width-100p col1-align-right">';

				if ( ! empty( $extra['search'] ) )
				{
					echo $extra['search'];
				}

				if ( ! empty( $extra['extra_search'] ) )
				{
					echo $extra['extra_search'];
				}

				if ( ! empty( $extra['second_col'] ) )
				{
					echo $extra['second_col'];
				}

				echo '</table><br />';
			}

			if ( isset( $_REQUEST['advanced'] ) && $_REQUEST['advanced'] === 'Y' )
			{
				$extra['search'] = '';

				Widgets( 'all', $extra );

				echo PopTable( 'header', _( 'Widgets' ) );

				echo $extra['search'];

				echo PopTable( 'footer' ) . '<br />';

				echo PopTable( 'header', _( 'Student Fields' ) );

				Search(
					'student_fields_all',
					! empty( $extra['student_fields'] ) ? $extra['student_fields'] : []
				);

				echo PopTable( 'footer' ) . '<br />';

				echo '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'advanced' => 'N' ] ) . '">' .
					_( 'Basic Search' ) . '</a>';
			}
			else
			{
				echo '<br /><a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'advanced' => 'Y' ] ) . '">' .
					_( 'Advanced Search' ) . '</a>';
			}

			echo '</form>';

			PopTable( 'footer' );

			break;

		case 'parent':
		case 'student':

			echo '<br />';

			PopTable( 'header', _( 'Search' ) );

			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=' . $_REQUEST['modfunc'] .
				'&search_modfunc=list&next_modname=' . $_REQUEST['next_modname'] .
				( ! empty( $extra['action'] ) ? $extra['action'] : '' )  ) . '" method="POST">';

			echo '<table class="width-100p col1-align-right">';

			if ( ! empty( $extra['search'] ) )
			{
				echo $extra['search'];
			}

			echo '</table><div class="center"><br />';

			echo Buttons( _( 'Submit' ) );

			echo '</div></form>';

			PopTable( 'footer' );

			break;
	}
}

//if ( $_REQUEST['search_modfunc']=== 'list')
else
{
	if ( empty( $_REQUEST['next_modname'] ) )
	{
		$_REQUEST['next_modname'] = 'Students/Student.php';
	}

	if ( empty( $extra['NoSearchTerms'] ) )
	{
		$_ROSARIO['SearchTerms'] = issetVal( $_ROSARIO['SearchTerms'] );

		if ( isset( $_REQUEST['_search_all_schools'] )
			&& $_REQUEST['_search_all_schools'] === 'Y'
			&& SchoolInfo( 'SCHOOLS_NB' ) > 1 )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Search All Schools' ) . '</b><br />';
		}

		if ( isset( $_REQUEST['include_inactive'] )
			&& $_REQUEST['include_inactive'] === 'Y' )
		{
			$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Include Inactive Students' ) . '</b><br />';
		}
	}

	if ( ! empty( $_REQUEST['address_group'] ) )
	{
		$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

		$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM students_join_address WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";
		$extra['group'] = $extra['LO_group'] = [ 'FAMILY_ID' ];
	}

	$students_RET = GetStuList( $extra );

	if ( isset( $extra['array_function'] )
		&& function_exists( $extra['array_function'] ) )
	{
		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			foreach ( (array) $students_RET as $id => $student_RET )
			{
				$students_RET[$id] = $extra['array_function']( $student_RET );
			}
		}
		else
		{
			$students_RET = $extra['array_function']( $students_RET );
		}
	}

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['next_modname'];
	$name_link['FULL_NAME']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

	if ( isset( $_REQUEST['_search_all_schools'] )
		&& $_REQUEST['_search_all_schools'] === 'Y' )
	{
		$name_link['FULL_NAME']['variables']['school_id'] = 'SCHOOL_ID';
	}

	if ( isset( $extra['link'] )
		&& is_array( $extra['link'] ) )
	{
		$link = array_replace_recursive( $name_link, $extra['link'] );
	}
	else
	{
		$link = $name_link;
	}

	if ( isset( $extra['columns'] ) && is_array( $extra['columns'] ) )
	{
		$columns = $extra['columns'];
	}
	else
	{
		$columns = [ 'FULL_NAME' => _( 'Student' ), 'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ), 'GRADE_ID' => _( 'Grade Level' ) ];
	}

	if ( isset( $extra['columns_before'] ) && is_array( $extra['columns_before'] ) )
	{
		$columns = $extra['columns_before'] + $columns;
	}

	if ( isset( $extra['columns_after'] ) && is_array( $extra['columns_after'] ) )
	{
		$columns += $extra['columns_after'];
	}

	$extra['header_right'] = issetVal( $extra['header_right'], '' );

	if ( count( (array) $students_RET ) > 1
		|| ! empty( $link['add'] )
		|| ! $link['FULL_NAME']
		|| ! empty( $extra['columns_before'] )
		|| ! empty( $extra['columns'] )
		|| ! empty( $extra['columns_after'] )
		|| ( empty( $extra['BackPrompt'] ) && empty( $students_RET ) )
		|| (  ( isset( $extra['Redirect'] )
			&& $extra['Redirect'] === false
			|| ! empty( $_REQUEST['address_group'] ) )
			&& count( (array) $students_RET ) == 1 ) )
	{
		$header_left = '';

		if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			if ( ! isset( $_REQUEST['expanded_view'] ) || $_REQUEST['expanded_view'] !== 'true' )
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) . '">' .
				_( 'Expanded View' ) . '</a>';
			}
			else
			{
				$header_left = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) . '">' .
				_( 'Original View' ) . '</a>';
			}

			if ( empty( $_REQUEST['address_group'] ) )
			{
				$header_left .= ' | <a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => 'Y' ] ) . '">' .
				_( 'Group by Family' ) . '</a>';
			}
			else
			{
				$header_left .= ' | <a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'address_group' => '' ] ) . '">' .
				_( 'Ungroup by Family' ) . '</a>';
			}
		}

		DrawHeader( $header_left, $extra['header_right'] );

		if ( ! empty( $extra['extra_header_left'] )
			|| ! empty( $extra['extra_header_right'] ) )
		{
			DrawHeader(
				issetVal( $extra['extra_header_left'] ),
				issetVal( $extra['extra_header_right'] )
			);
		}

		if ( ! empty( $_ROSARIO['SearchTerms'] ) )
		{
			DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );
		}

		if ( empty( $_REQUEST['LO_save'] ) && empty( $extra['suppress_save'] ) )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'bottom_back' ] );

			if ( empty( $_SESSION['Back_PHP_SELF'] )
				|| $_SESSION['Back_PHP_SELF'] != 'student' )
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset( $_SESSION['Search_PHP_SELF'] );
			}

			if ( User( 'PROFILE' ) === 'admin'
				|| User( 'PROFILE' ) === 'teacher' )
			{
				// Update Bottom.php.
				$bottom_url = URLEscape( 'Bottom.php?modname=' . $_REQUEST['modname'] . '&search_modfunc=list' );

				echo '<script>ajaxLink(' . json_encode( $bottom_url ) . '); old_modname="";</script>';
			}
		}

		$extra['LO_group'] = issetVal( $extra['LO_group'], [] );

		$extra['options'] = issetVal( $extra['options'], [] );

		if ( ! empty( $_REQUEST['address_group'] ) )
		{
			ListOutput(
				$students_RET,
				$columns,
				'Family',
				'Families',
				$link,
				$extra['LO_group'],
				$extra['options']
			);
		}
		else
		{
			if ( ! empty( $extra['singular'] ) && ! empty( $extra['plural'] ) )
			{
				ListOutput(
					$students_RET,
					$columns,
					$extra['singular'],
					$extra['plural'],
					$link,
					$extra['LO_group'],
					$extra['options']
				);
			}
			else
			{
				ListOutput(
					$students_RET,
					$columns,
					'Student',
					'Students',
					$link,
					$extra['LO_group'],
					$extra['options']
				);
			}
		}
	}
	elseif ( count( (array) $students_RET ) == 1 )
	{
		foreach ( (array) $link['FULL_NAME']['variables'] as $var => $val )
		{
			$_REQUEST[$var] = $students_RET['1'][$val];
		}

		if ( ! is_array( $students_RET[1]['STUDENT_ID'] ) )
		{
			if ( $students_RET[1]['SCHOOL_ID'] != UserSchool() )
			{
				$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];
			}

			SetUserStudentID( $students_RET[1]['STUDENT_ID'] );

			// Unset search modfunc & redirect URL.
			RedirectURL( 'search_modfunc' );
		}

		if ( $_REQUEST['modname'] != $_REQUEST['next_modname'] )
		{
			$modname = $_REQUEST['next_modname'];

			if ( mb_strpos( $modname, '?' ) )
			{
				$modname = mb_substr( $_REQUEST['next_modname'], 0, mb_strpos( $_REQUEST['next_modname'], '?' ) );
			}

			if ( mb_strpos( $modname, '&' ) )
			{
				$modname = mb_substr( $_REQUEST['next_modname'], 0, mb_strpos( $_REQUEST['next_modname'], '&' ) );
			}

			if ( ! empty( $_REQUEST['modname'] ) )
			{
				$_REQUEST['modname'] = $modname;
			}

			//FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html

			if ( mb_substr( $modname, -4, 4 ) != '.php' || mb_strpos( $modname, '..' ) !== false || ! is_file( 'modules/' . $modname ) )
			{
				require_once 'ProgramFunctions/HackingLog.fnc.php';
				HackingLog();
			}
			else
			{
				require_once 'modules/' . $modname;
			}
		}
	}
	else
	{
		DrawHeader( '', $extra['header_right'] );

		if ( ! empty( $extra['extra_header_left'] )
			|| ! empty( $extra['extra_header_right'] ) )
		{
			DrawHeader( $extra['extra_header_left'], $extra['extra_header_right'] );
		}

		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );

		echo ErrorMessage( [ _( 'No Students were found.' ) ] );
	}
}
