<?php
/**
 * Resources functions
 *
 * @package RosarioSIS
 * @subpackage Resources module
 */

/**
 * Make text input (link or title)
 *
 * DBGet() callback
 *
 * @param string $value
 * @param string $column
 *
 * @return string Text input HTML
 */
function ResourcesMakeTextInput( $value, $name )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
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
 * Make link
 *
 * DBGet() callback
 *
 * @uses ResourcesMakeTextInput()
 *
 * @param string $value
 * @param string $column
 *
 * @return string Link input HTML
 */
function ResourcesMakeLink( $value, $name )
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
				<div style="display:table-cell;">' . ResourcesMakeTextInput( $value, $name ) . '</div>';
		}

		return ResourcesMakeTextInput( $value, $name );
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
		$start = (int) ( $max_length / 2 );
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
 * @param string $value
 * @param string $column
 *
 * @return string Visible To HTML field
 */
function ResourcesMakePublishedProfiles( $value, $column = 'PUBLISHED_PROFILES' )
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
 * @param string $value
 * @param string $column
 *
 * @return string Limit to Grade Levels HTML field
 */
function ResourcesMakePublishedGradeLevels( $value, $column = 'PUBLISHED_GRADE_LEVELS' )
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
		'multiple_save_NA', // Save when none selected, add hidden empty input
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
 * DBGet() callback
 *
 * @uses ResourcesMakePublishedProfiles()
 * @uses ResourcesMakePublishedGradeLevels()
 *
 * @param string $value
 * @param string $column
 *
 * @return string visible To HTML
 */
function ResourcesMakeVisibleTo( $value, $column = 'VISIBLE_TO' )
{
	global $THIS_RET;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	// Fix responsive rt td too large.
	$return = '<div id="divVisibleTo' . $id . '" class="rt2colorBox">';

	$return .= '<table class="widefat width-100p cellspacing-0">';

	$return .= ResourcesMakePublishedProfiles(
		( $id === 'new' ? '' : $THIS_RET['PUBLISHED_PROFILES'] ),
		'PUBLISHED_PROFILES'
	);

	$return .= ResourcesMakePublishedGradeLevels(
		( $id === 'new' ? '' : $THIS_RET['PUBLISHED_GRADE_LEVELS'] ),
		'PUBLISHED_GRADE_LEVELS'
	);

	$return .= '</table></div>';

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
function ResourcesVisibilityWhereSQL()
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
