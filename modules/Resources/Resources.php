<?php

require_once 'modules/Resources/includes/Resources.fnc.php';

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
					$columns['PUBLISHED_GRADE_LEVELS'] = ',' . $published_grade_levels;
				}
			}

			if ( $id !== 'new' )
			{
				DBUpdate(
					'resources',
					$columns,
					[ 'ID' => (int) $id ]
				);
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$insert_columns = [ 'SCHOOL_ID' => UserSchool() ];

				DBInsert(
					'resources',
					$insert_columns + $columns
				);
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
		'TITLE' => 'ResourcesMakeTextInput',
		'LINK' => 'ResourcesMakeLink',
	];

	if ( AllowEdit() )
	{
		$functions['VISIBLE_TO'] = 'ResourcesMakeVisibleTo';
	}

	$resources_RET = DBGet( "SELECT ID,TITLE,LINK,PUBLISHED_PROFILES,PUBLISHED_GRADE_LEVELS,
		'' AS VISIBLE_TO
		FROM resources
		WHERE SCHOOL_ID='" . UserSchool() . "'
		" . ResourcesVisibilityWhereSQL() . "
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
		'TITLE' => ResourcesMakeTextInput( '', 'TITLE' ),
		'LINK' => ResourcesMakeLink( '', 'LINK' ),
	];

	if ( AllowEdit() )
	{
		$link['add']['html']['VISIBLE_TO'] = ResourcesMakeVisibleTo( '', 'VISIBLE_TO' );
	}

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );

	ListOutput( $resources_RET, $columns, 'Resource', 'Resources', $link );
	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}
