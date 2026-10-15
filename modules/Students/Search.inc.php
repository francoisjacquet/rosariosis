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

			echo '<br />';

			PopTable(
				'header',
				! empty( $extra['search_title'] ) ? $extra['search_title'] : _( 'Find a Student' )
			);

			echo '<form name="search" id="search" action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				( $_REQUEST['modfunc'] ? '&modfunc=' . $_REQUEST['modfunc'] : '' ) .
				'&search_modfunc=list' .
				'&advanced=' . ( ! empty( $_REQUEST['advanced'] ) ? $_REQUEST['advanced'] : '' ) .
				( ! empty( $extra['action'] ) ? $extra['action'] : '' )  ) . '" method="GET">';

			echo '<table class="width-100p col1-align-right" id="general_table">';

			Search( 'general_info', issetVal( $extra['grades'], [] ) );

			if ( ! isset( $extra ) )
			{
				$extra = [];
			}

			Widgets( 'user', $extra );

			// @since 12.4 Remove unused `$extra['student_fields']['view']`
			Search( 'student_fields', [] );

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
				// @deprecated since 12.4.1 `$extra['second_col']` & `$extra['extra_search']`, use `$extra['search']` instead
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
					/**
					 * Deprecate `$extra['extra_search']`, use `$extra['search']` instead
					 *
					 * @deprecated since 12.4.1
					 *
					 * Raise deprecation notice.
					 */
					trigger_error(
						'$extra[\'extra_search\'] is deprecated since RosarioSIS 12.4.1, use $extra[\'search\'] instead',
						E_USER_DEPRECATED
					);

					echo $extra['extra_search'];
				}

				if ( ! empty( $extra['second_col'] ) )
				{
					/**
					 * Deprecate `$extra['second_col']`, use `$extra['search']` instead
					 *
					 * @deprecated since 12.4.1
					 *
					 * Raise deprecation notice.
					 */
					trigger_error(
						'$extra[\'second_col\'] is deprecated since RosarioSIS 12.4.1, use $extra[\'search\'] instead',
						E_USER_DEPRECATED
					);

					echo $extra['second_col'];
				}

				echo '</table><br />';
			}

			if ( isset( $_REQUEST['advanced'] ) && $_REQUEST['advanced'] === 'Y' )
			{
				$extra['search'] = '';

				Widgets( 'all', $extra );

				PopTable( 'header', _( 'Widgets' ) );

				echo $extra['search'];

				PopTable( 'footer' ) . '<br />';

				PopTable( 'header', _( 'Student Fields' ) );

				// @since 12.4 Remove unused `$extra['student_fields']['view']`
				Search( 'student_fields_all', [] );

				PopTable( 'footer' ) . '<br />';

				echo '<a href="' . PreparePHP_SELF( $_REQUEST, [ 'advanced' ] ) . '">' .
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
				( $_REQUEST['modfunc'] ? '&modfunc=' . $_REQUEST['modfunc'] : '' ) .
				'&search_modfunc=list' .
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

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
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

		if ( ! $students_RET
			&& ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
			&& empty( $_REQUEST['include_inactive'] ) )
		{
			// @since 13.0 Add "Include Inactive Students" checkbox when no students were found
			DrawHeader(
				CheckBoxOnclick( 'include_inactive', _( 'Include Inactive Students' ) )
			);
		}

		if ( empty( $_REQUEST['LO_save'] ) && empty( $extra['suppress_save'] ) )
		{
			if ( User( 'PROFILE' ) === 'admin'
				|| User( 'PROFILE' ) === 'teacher' )
			{
				/**
				 * Remove need to make an AJAX call to Bottom.php
				 *
				 * @since 12.0 JS Show BottomButtonBack & update its URL & text
				 */
				require_once 'ProgramFunctions/Bottom.fnc.php';

				BottomButtonBackUpdate( 'student' );
			}
		}

		$extra['LO_group'] = issetVal( $extra['LO_group'], [] );

		$extra['options'] = issetVal( $extra['options'], [] );

		if ( empty( $extra['columns_before'] )
			&& ( empty( $extra['link']['remove'] ) || ! AllowEdit() ) )
		{
			// @since 12.6 CSS Fix first column (list, table)
			if ( ! empty( $extra['options']['class'] ) )
			{
				$extra['options']['class'] .= ' fix-first-column';
			}
			else
			{
				$extra['options']['class'] = 'fix-first-column';
			}
		}

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
				$user_schools = trim( (string) User( 'SCHOOLS' ), ',' ) ?
					explode( ',', trim( User( 'SCHOOLS' ), ',' ) ) : '';

				// Check user is in student's school.
				if ( ! $user_schools
					|| in_array( $students_RET[1]['SCHOOL_ID'], $user_schools ) )
				{
					// Fix HACKING ATTEMPT in SetUserStudentID() when "Search All Schools" checked
					$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];
				}
			}

			SetUserStudentID( $students_RET[1]['STUDENT_ID'] );

			// Unset search modfunc & redirect URL.
			RedirectURL( 'search_modfunc' );
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

		if ( ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' )
			&& empty( $_REQUEST['include_inactive'] ) )
		{
			// @since 13.0 Add "Include Inactive Students" checkbox when no students were found
			DrawHeader(
				CheckBoxOnclick( 'include_inactive', _( 'Include Inactive Students' ) )
			);
		}

		echo ErrorMessage( [ _( 'No Students were found.' ) ] );
	}
}
