<?php

//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit()
		&& $_REQUEST['tab_id'] )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// FJ fix SQL bug invalid sort order.

			if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
			{
				if ( $id !== 'new' )
				{
					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = "UPDATE report_card_comment_codes SET ";
					}
					else
					{
						$sql = "UPDATE report_card_comment_code_scales SET ";
					}

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
					}
					else
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
					}

					DBQuery( $sql );
				}

				// New: check for Title
				elseif ( $columns['TITLE'] )
				{
					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = 'INSERT INTO report_card_comment_codes ';
						$fields = 'SCHOOL_ID,SCALE_ID,';
						$values = "'" . UserSchool() . "','" . $_REQUEST['tab_id'] . "',";
					}
					else
					{
						$sql = 'INSERT INTO report_card_comment_code_scales ';
						$fields = 'SCHOOL_ID,';
						$values = "'" . UserSchool() . "',";
					}

					$go = false;

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
			else
			{
				$error[] = _( 'Please enter a valid Sort Order.' );
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		if ( DeletePrompt( _( 'Report Card Comment' ) ) )
		{
			DBQuery( "DELETE FROM report_card_comment_codes
				WHERE ID='" . (int) $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( DeletePrompt( _( 'Comment Code Scale' ) ) )
	{
		DBQuery( "UPDATE report_card_comments
			SET SCALE_ID=NULL
			WHERE SCALE_ID='" . (int) $_REQUEST['id'] . "'" );

		$delete_sql = "DELETE FROM report_card_comment_codes
			WHERE SCALE_ID='" . (int) $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM report_card_comment_code_scales
			WHERE ID='" . (int) $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$comment_scales_RET = DBGet( "SELECT ID,TITLE
		FROM report_card_comment_code_scales
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID", [], [ 'ID' ] );

	if ( ! isset( $_REQUEST['tab_id'] )
		|| $_REQUEST['tab_id'] == ''
		|| $_REQUEST['tab_id'] !== 'new'
		&& empty( $comment_scales_RET[$_REQUEST['tab_id']] ) )
	{
		if ( ! empty( $comment_scales_RET ) )
		{
			$_REQUEST['tab_id'] = key( $comment_scales_RET ) . '';
		}
		else
		{
			$_REQUEST['tab_id'] = 'new';
		}
	}

	$tabs = [];
	$comment_scale_select = [];

	foreach ( (array) $comment_scales_RET as $id => $comment_scale )
	{
		$tabs[] = [
			'title' => $comment_scale[1]['TITLE'],
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id,
		];

		$comment_scale_select[$id] = $comment_scale[1]['TITLE'];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		$sql = "SELECT ID,SCALE_ID,TITLE,SHORT_NAME,COMMENT,SORT_ORDER
		FROM report_card_comment_codes
		WHERE SCALE_ID='" . (int) $_REQUEST['tab_id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID";

		$functions = [
			'TITLE' => '_makeCommentsInput',
			'SHORT_NAME' => '_makeCommentsInput',
			'COMMENT' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
		];

		$LO_columns = [ 'TITLE' => _( 'Title' ),
			'SHORT_NAME' => _( 'Short Name' ),
			'COMMENT' => _( 'Comment' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		if ( User( 'PROFILE' ) === 'admin' && AllowEdit() )
		{
			$functions += [ 'SCALE_ID' => '_makeCommentsInput' ];
			$LO_columns += [ 'SCALE_ID' => _( 'Comment Scale' ) ];
		}

		$link['add']['html'] = [
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'SHORT_NAME' => _makeCommentsInput( '', 'SHORT_NAME' ),
			'COMMENT' => _makeCommentsInput( '', 'COMMENT' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];

		$link['remove']['variables'] = [ 'id' => 'ID' ];
		$link['add']['html']['remove'] = button( 'add' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = [
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
			];
		}

		$subject = 'Codes';
	}
	else
	{
		$sql = "SELECT ID,TITLE,COMMENT,SORT_ORDER
		FROM report_card_comment_code_scales
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,ID";

		$functions = [
			'TITLE' => '_makeCommentsInput',
			'COMMENT' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
		];

		$LO_columns = [
			'TITLE' => _( 'Comment Scale' ),
			'COMMENT' => _( 'Comment' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		];

		$link['add']['html'] = [
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'COMMENT' => _makeCommentsInput( '', 'COMMENT' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		];

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new';
		$link['remove']['variables'] = [ 'id' => 'ID' ];
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = [
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		];

		$subject = 'Comment Code Scales';
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' .
		$_REQUEST['tab_id']  ) . '" method="POST">';

	DrawHeader( '', SubmitButton() );
	echo '<br />';

	$LO_options = [
		'save' => false,
		'search' => false,
		'header' => WrapTabs(
			$tabs,
			'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id']
		),
	];

	if ( $subject == 'Codes' )
	{
		ListOutput( $LO_ret, $LO_columns, 'Code', 'Codes', $link, [], $LO_options );
	}
	elseif ( $subject == 'Comment Code Scales' )
	{
		ListOutput(
			$LO_ret,
			$LO_columns,
			'Comment Code Scale',
			'Comment Code Scales',
			$link,
			[],
			$LO_options
		);
	}

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</td></tr></table></form>';
}


/**
 * Make Comments input
 * Select, and Text inputs
 *
 * Local function
 * DBGet() callback
 *
 * @param string $value  Column value.
 * @param string $column Column name.
 *
 * @return Input HTML.
 */
function _makeCommentsInput( $value, $name )
{
	global $THIS_RET,
		$comment_scale_select;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'SCALE_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$comment_scale_select,
			false
		);
	}
	elseif ( $name === 'COMMENT' )
	{
		$extra = 'size=20 maxlength=100';

		if ( $_REQUEST['tab_id'] !== 'new'
			&& $id !== 'new' )
		{
			// Comment Code input field is required.
			$extra .= ' required';
		}
	}
	elseif ( $name === 'SHORT_NAME' )
	{
		$extra = 'size=10 maxlength=100';
	}
	elseif ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	elseif ( $name === 'TITLE' )
	{
		$extra = 'size=15 maxlength=25';

		if ( $_REQUEST['tab_id'] !== 'new' )
		{
			$extra = 'size=4 maxlength=5';
		}

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	else
	{
		$extra = 'size=4 maxlength=5';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

