<?php

if ( empty( $_REQUEST['search_modfunc'] ) )
{
	switch ( User( 'PROFILE' ) )
	{
		case 'admin':
		case 'teacher':
			//if (UserStaffID() && ($_REQUEST['modname']!='Users/Search.php' || $_REQUEST['student_id']=='new'))

			if ( UserStaffID() && User( 'PROFILE' ) === 'admin' && $_REQUEST['staff_id'] == 'new' )
			{
				unset( $_SESSION['staff_id'] );
			}

			echo '<br />';

			PopTable(
				'header',
				! empty( $extra['search_title'] ) ? $extra['search_title'] : _( 'Find a User' )
			);

			echo '<form name="search" id="search" action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=' . $_REQUEST['modfunc'] .
				'&search_modfunc=list' .
				'&advanced=' . issetVal( $_REQUEST['advanced'], '' ) .
				issetVal( $extra['action'], '' )  ) . '" method="GET">';

			echo '<table class="width-100p col1-align-right" id="general_table">';

			Search( 'staff_general_info', $extra );

			if ( ! isset( $extra ) )
			{
				$extra = [];
			}

			StaffWidgets( 'user', $extra );

			Search(
				'staff_fields',
				isset( $extra['staff_fields'] ) && is_array( $extra['staff_fields'] ) ?
				$extra['staff_fields'] :
				[]
			);

			echo '</table><div class="center">';

			if ( ! empty( $extra['search_second_col'] ) )
			{
				echo $extra['search_second_col'];
			}

			if ( User( 'PROFILE' ) === 'admin' )
			{
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

			echo '<label><input type="checkbox" name="include_inactive" value="Y" /> ' .
			_( 'Include Parents of Inactive Students' ) . '</label><br />';

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

			//echo '<table><tr class="valign-top"><td>';

			if ( isset( $_REQUEST['advanced'] ) && $_REQUEST['advanced'] === 'Y' )
			{
				$extra['search'] = '';

				StaffWidgets( 'all', $extra );

				if ( $extra['search'] )
				{
					echo PopTable( 'header', _( 'Widgets' ) );

					echo $extra['search'];

					echo PopTable( 'footer' ) . '<br />';
				}

				ob_start();

				Search(
					'staff_fields_all',
					! empty( $extra['staff_fields'] ) ? $extra['staff_fields'] : []
				);

				$staff_fields_all = ob_get_clean();

				if ( $staff_fields_all )
				{
					echo PopTable( 'header', _( 'User Fields' ) );

					echo $staff_fields_all;

					echo PopTable( 'footer' ) . '<br />';
				}

				echo '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'advanced' => 'N' ] ) . '">' . _( 'Basic Search' ) . '</a>';
			}
			else
			{
				echo '<br /><a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'advanced' => 'Y' ] ) . '">' . _( 'Advanced Search' ) . '</a>';
			}

			echo '</form>';

			PopTable( 'footer' );

			break;

		default:

			echo User( 'PROFILE' );
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
	}

	if ( ! isset( $_ROSARIO['DrawHeader'] ) )
	{
		DrawHeader( _( 'Choose A User' ) );
	}

	$staff_RET = GetStaffList( $extra );

	if ( ! empty( $extra['profile'] ) )
	{
		// DO NOT translate those strings since they will be passed to ListOutput ultimately.
		$options = [
			'admin' => 'Administrator',
			'teacher' => 'Teacher',
			'parent' => 'Parent',
			'none' => 'No Access',
		];

		$options_plural = [
			'admin' => 'Administrators',
			'teacher' => 'Teachers',
			'parent' => 'Parents',
			'none' => 'No Access',
		];

		$singular = $options[$extra['profile']];

		$plural = $options_plural[$extra['profile']];

		$columns = [
			'FULL_NAME' => $singular,
			'STAFF_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		];
	}
	else
	{
		$singular = 'User';

		$plural = 'Users';

		$columns = [
			'FULL_NAME' => _( 'User' ),
			'PROFILE' => _( 'Profile' ),
			'STAFF_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
		];
	}

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
	$name_link['FULL_NAME']['variables'] = [ 'staff_id' => 'STAFF_ID' ];

	if ( isset( $extra['link'] )
		&& is_array( $extra['link'] ) )
	{
		$link = array_replace_recursive( $name_link, $extra['link'] );
	}
	else
	{
		$link = $name_link;
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

	if ( count( (array) $staff_RET ) > 1
		|| ! empty( $link['add'] )
		|| ! $link['FULL_NAME']
		|| ! empty( $extra['columns_before'] )
		|| ! empty( $extra['columns_after'] )
		|| ( empty( $extra['BackPrompt'] ) && empty( $staff_RET ) )
		|| ( isset( $extra['Redirect'] )
			&& $extra['Redirect'] === false
			&& count( (array) $staff_RET ) == 1 ) )
	{
		if ( ! isset( $_REQUEST['expanded_view'] ) || $_REQUEST['expanded_view'] !== 'true' )
		{
			DrawHeader(
				'<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) .
				'">' . _( 'Expanded View' ) . '</a>',
				$extra['header_right']
			);
		}
		else
		{
			DrawHeader(
				'<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) .
				'">' . _( 'Original View' ) . '</a>',
				$extra['header_right']
			);
		}

		if ( ! empty( $extra['extra_header_left'] )
			|| ! empty( $extra['extra_header_right'] ) )
		{
			DrawHeader(
				issetVal( $extra['extra_header_left'], '' ),
				issetVal( $extra['extra_header_right'], '' )
			);
		}

		if ( ! empty( $_ROSARIO['SearchTerms'] ) )
		{
			DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );
		}

		if ( empty( $_REQUEST['LO_save'] ) && empty( $extra['suppress_save'] ) )
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF( $_REQUEST, [ 'bottom_back' ] );

			$_SESSION['Back_PHP_SELF'] = 'staff';

			// Update Bottom.php.
			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		ListOutput(
			$staff_RET,
			$columns,
			$singular,
			$plural,
			$link,
			false,
			( issetVal( $extra['options'], [] ) )
		);
	}
	elseif ( count( (array) $staff_RET ) == 1 )
	{
		foreach ( (array) $link['FULL_NAME']['variables'] as $var => $val )
		{
			$_REQUEST[$var] = $staff_RET['1'][$val];
		}

		if ( ! is_array( $staff_RET[1]['STAFF_ID'] ) )
		{
			SetUserStaffID( $staff_RET[1]['STAFF_ID'] );

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

		echo ErrorMessage( [ _( 'No Users were found.' ) ] );
	}
}
