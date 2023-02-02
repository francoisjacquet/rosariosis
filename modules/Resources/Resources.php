<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			if ( isset( $columns['PUBLISHED_PROFILES'] ) )
			{
				// @since 10.8 Add Resource Visibility options
				$published_profiles = '';

				foreach ( (array) $columns['PUBLISHED_PROFILES'] as $profile => $yes )
				{
					if ( $yes )
					{
						$published_profiles .= ',' . $profile;
					}
				}

				$columns['PUBLISHED_PROFILES'] = '';

				if ( $published_profiles )
				{
					$columns['PUBLISHED_PROFILES'] = $published_profiles . ',';
				}
			}

			if ( isset( $columns['PUBLISHED_GRADE_LEVELS'] ) )
			{
				// @since 10.8 Add Resource Visibility options
				$published_grade_levels = implode( ',', $columns['PUBLISHED_GRADE_LEVELS'] );

				$columns['PUBLISHED_GRADE_LEVELS'] = '';

				if ( $published_grade_levels )
				{
					$columns['PUBLISHED_GRADE_LEVELS'] = ',' . $published_grade_levels . ',';
				}
			}

			if ( $id !== 'new' )
			{
				$sql = "UPDATE resources SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
				DBQuery( $sql );
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$sql = "INSERT INTO resources ";

				$fields = 'SCHOOL_ID,';
				$values = "'" . UserSchool() . "',";

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
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
				}
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Resource' ) ) )
	{
		DBQuery( "DELETE FROM resources
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$functions = [
		'TITLE' => '_makeTextInput',
		'LINK' => '_makeLink',
	];

	if ( AllowEdit() )
	{
		$functions['VISIBLE_TO'] = '_makeVisibleTo';
	}

	$resources_RET = DBGet( "SELECT ID,TITLE,LINK,PUBLISHED_PROFILES,PUBLISHED_GRADE_LEVELS,
		'' AS VISIBLE_TO
		FROM resources
		WHERE SCHOOL_ID='" . UserSchool() . "'
		" . _resourcesWhereSQL() . "
		ORDER BY ID", $functions );

	$columns = [
		'TITLE' => _( 'Title' ),
		'LINK' => _( 'Link' ),
	];

	if ( AllowEdit() )
	{
		$tooltip = '<div class="tooltip" style="text-transform: none;"><i>' . _( 'Note: All unchecked means visible to all profiles' ) . '</i></div>';

		$columns['VISIBLE_TO'] = _( 'Visible To' ) . $tooltip;
	}

	$link['add']['html'] = [
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'LINK' => _makeLink( '', 'LINK' ),
	];

	if ( AllowEdit() )
	{
		$link['add']['html']['VISIBLE_TO'] = _makeVisibleTo( '', 'VISIBLE_TO' );
	}

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );

	ListOutput( $resources_RET, $columns, 'Resource', 'Resources', $link );
	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'LINK' )
	{
		$extra = 'size="32" maxlength="1000"';
	}

	if ( $name === 'TITLE' )
	{
		$extra = 'maxlength="256"';
	}

	if ( $id !== 'new' )
	{
		$extra .= ' required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeLink( $value, $name )
{
	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	if ( AllowEdit() )
	{
		if ( $value )
		{
			return '<div style="display:table-cell;"><a href="' . URLEscape( $value ) . '" target="_blank">' .
				_( 'Link' ) . '</a>&nbsp;</div>
				<div style="display:table-cell;">' . _makeTextInput( $value, $name ) . '</div>';
		}

		return _makeTextInput( $value, $name );
	}

	if ( ! $value )
	{
		return $value;
	}

	// Truncate links > 100 chars.
	$truncated_link = $value;

	if ( mb_strlen( $truncated_link ) > 100 )
	{
		$separator = '/.../';
		$separator_length = mb_strlen( $separator );
		$max_length = 100 - $separator_length;
		$start = $max_length / 2;
		$trunc = mb_strlen( $truncated_link ) - $max_length;
		$truncated_link = substr_replace( $truncated_link, $separator, $start, $trunc );
	}

	return '<a href="' . URLEscape( $value ) . '" target="_blank">' . $truncated_link . '</a>';
}

/**
 * Resource Visible To field / Published profiles
 * Exclude admin (can always view)
 *
 * @since 10.8 Add Resource Visibility options
 *
 * Local function, DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string Visible To HTML field
 */
function _makePublishedProfiles( $value, $column = 'PUBLISHED_PROFILES' )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	$profiles_RET = DBGet( "SELECT ID,TITLE FROM user_profiles WHERE ID<>1 ORDER BY ID" );

	$custom_permissions = [];

	$there_is_user_with_custom = function( $profile )
	{
		return (bool) DBGetOne( "SELECT 1 FROM staff
			WHERE PROFILE='" . DBEscapeString( $profile ) . "'
			AND PROFILE_ID IS NULL
			AND SYEAR='" . UserSyear() . "'" );
	};

	if ( $there_is_user_with_custom( 'teacher' ) )
	{
		$custom_permissions[] = [ 'ID' => 'teacher', 'TITLE' => _( 'Teacher w/Custom' ) ];
	}

	if ( $there_is_user_with_custom( 'parent' ) )
	{
		$custom_permissions[] = [ 'ID' => 'parent', 'TITLE' => _( 'Parent w/Custom' ) ];
	}

	// Add Profiles with Custom permissions to profiles list.
	$profiles = array_merge( $custom_permissions, $profiles_RET );

	$visible_to = '<tr class="st">';

	$i = 0;

	foreach ( (array) $profiles as $profile )
	{
		$i++;
		$checked = mb_strpos( issetVal( $value, '' ), ',' . $profile['ID'] . ',' ) !== false;

		$visible_to .= '<td>' . CheckboxInput(
			$checked,
			'values[' . $id . '][PUBLISHED_PROFILES][' . $profile['ID'] . ']',
			_( $profile['TITLE'] ),
			'',
			true
		) . '</td>';

		if ( $i % 2 == 0 && $i != count( $profiles ) )
		{
			$visible_to .= '</tr><tr class="st">';
		}
	}

	for ( ; $i % 2 != 0; $i++ )
	{
		$visible_to .= '<td>&nbsp;</td>';
	}

	$visible_to .= '</tr>';

	return $visible_to;
}

/**
 * Limit to Grade Levels field
 *
 * Local function, DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string Limit to Grade Levels HTML field
 */
function _makePublishedGradeLevels( $value, $column = 'PUBLISHED_GRADE_LEVELS' )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	$grade_levels_RET = DBGet( "SELECT ID,TITLE FROM school_gradelevels
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$grade_level_options = [];

	foreach ( (array) $grade_levels_RET as $grade_level )
	{
		$grade_level_options[ $grade_level['ID'] ] = $grade_level['TITLE'];
	}

	// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
	$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'SelectInput';

	$value_array = explode( ',', trim( (string) $value, ',' ) );

	$limit_to = '<tr class="st"><td colspan="2">';

	$limit_to .= '<b>' . _( 'Limit to Grade Levels' ) . ':</b><br>';

	$limit_to .= $select_input_function(
		$value_array,
		'values[' . $id . '][PUBLISHED_GRADE_LEVELS][]',
		'',
		$grade_level_options,
		false,
		'multiple style="width: 240px"', // Multiple select inputs.
		false
	);

	$limit_to .= '</td></tr>';

	return $limit_to;
}

/**
 * Make Visible To
 * Merge Profiles + Limit to Grade Levels
 *
 * @since 10.8 Add Resource Visibility options
 *
 * Local function, DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string visible To HTML
 */
function _makeVisibleTo( $value, $column = 'VISIBLE_TO' )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	$return = '<table class="widefat width-100p cellspacing-0">';

	$return .= _makePublishedProfiles(
		( $id === 'new' ? '' : $THIS_RET['PUBLISHED_PROFILES'] ),
		'PUBLISHED_PROFILES'
	);

	$return .= _makePublishedGradeLevels(
		( $id === 'new' ? '' : $THIS_RET['PUBLISHED_GRADE_LEVELS'] ),
		'PUBLISHED_GRADE_LEVELS'
	);

	$return .= '</table>';

	return $return;
}

/**
 * WHERE SQL to apply Resource Visibility options
 * Checks if User Profile is allowed
 * Checks if Grade Level is allowed
 *
 * @since 10.8 Add Resource Visibility options
 *
 * @return string WHERE SQL
 */
function _resourcesWhereSQL()
{
	$resources_where_sql = "";

	if ( User( 'PROFILE' ) === 'teacher'
		|| User( 'PROFILE' ) === 'parent' )
	{
		$resources_where_sql = " AND (PUBLISHED_PROFILES IS NULL";

		if ( ! User( 'PROFILE_ID' ) )
		{
			$resources_where_sql .= " OR position('," . DBEscapeString( User( 'PROFILE' ) ) . ",' IN PUBLISHED_PROFILES)>0)";
		}
		else
		{
			$resources_where_sql .= " OR position('," . DBEscapeString( User( 'PROFILE_ID' ) ) . ",' IN PUBLISHED_PROFILES)>0)";
		}
	}
	elseif ( User( 'PROFILE' ) === 'student' )
	{
		$resources_where_sql = " AND (PUBLISHED_PROFILES IS NULL
			OR position(',0,' IN PUBLISHED_PROFILES)>0)";

		// Limit to Grade Levels.
		$resources_where_sql .= " AND (PUBLISHED_GRADE_LEVELS IS NULL
			OR position(CONCAT(',', (SELECT GRADE_ID
				FROM student_enrollment
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY START_DATE DESC
				LIMIT 1), ',') IN PUBLISHED_GRADE_LEVELS)>0)";
	}

	return $resources_where_sql;
}
