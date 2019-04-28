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
						$sql = "UPDATE REPORT_CARD_COMMENT_CODES SET ";
					}
					else
					{
						$sql = "UPDATE REPORT_CARD_COMMENT_CODE_SCALES SET ";
					}

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
					}
					else
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
					}

					DBQuery( $sql );
				}

				// New: check for Title
				elseif ( $columns['TITLE'] )
				{
					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = 'INSERT INTO REPORT_CARD_COMMENT_CODES ';
						$fields = 'ID,SCHOOL_ID,SCALE_ID,';
						$values = db_seq_nextval( 'REPORT_CARD_COMMENT_CODES_SEQ' ) . ',\'' . UserSchool() . '\',\'' . $_REQUEST['tab_id'] . '\',';
					}
					else
					{
						$sql = 'INSERT INTO REPORT_CARD_COMMENT_CODE_SCALES ';
						$fields = 'ID,SCHOOL_ID,';
						$values = db_seq_nextval( 'REPORT_CARD_COMMENT_CODE_SCALES_SEQ' ) . ',\'' . UserSchool() . '\',';
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
			DBQuery( "DELETE FROM REPORT_CARD_COMMENT_CODES
				WHERE ID='" . $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( array( 'modfunc', 'id' ) );
		}
	}
	elseif ( DeletePrompt( _( 'Report Card Grading Scale' ) ) )
	{
		DBQuery( "UPDATE REPORT_CARD_COMMENTS
			SET SCALE_ID=NULL
			WHERE SCALE_ID='" . $_REQUEST['id'] . "'" );

		$delete_sql = "DELETE FROM REPORT_CARD_COMMENT_CODES
			WHERE SCALE_ID='" . $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM REPORT_CARD_COMMENT_CODE_SCALES
			WHERE ID='" . $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$comment_scales_RET = DBGet( "SELECT ID,TITLE
		FROM REPORT_CARD_COMMENT_CODE_SCALES
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER,ID", array(), array( 'ID' ) );

	if ( $_REQUEST['tab_id'] == ''
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

	$tabs = array();
	$comment_scale_select = array();

	foreach ( (array) $comment_scales_RET as $id => $comment_scale )
	{
		$tabs[] = array(
			'title' => $comment_scale[1]['TITLE'],
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id,
		);

		$comment_scale_select[$id] = $comment_scale[1]['TITLE'];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		$sql = "SELECT *
		FROM REPORT_CARD_COMMENT_CODES
		WHERE SCALE_ID='" . $_REQUEST['tab_id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER,ID";

		$functions = array(
			'TITLE' => '_makeCommentsInput',
			'SHORT_NAME' => '_makeCommentsInput',
			'COMMENT' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
		);

		$LO_columns = array( 'TITLE' => _( 'Title' ),
			'SHORT_NAME' => _( 'Short Name' ),
			'COMMENT' => _( 'Comment' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		);

		if ( User( 'PROFILE' ) === 'admin' && AllowEdit() )
		{
			$functions += array( 'SCALE_ID' => '_makeCommentsInput' );
			$LO_columns += array( 'SCALE_ID' => _( 'Comment Scale' ) );
		}

		$link['add']['html'] = array(
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'SHORT_NAME' => _makeCommentsInput( '', 'SHORT_NAME' ),
			'COMMENT' => _makeCommentsInput( '', 'COMMENT' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];

		$link['remove']['variables'] = array( 'id' => _( 'ID' ) );
		$link['add']['html']['remove'] = button( 'add' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = array(
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
			);
		}

		$subject = 'Codes';
	}
	else
	{
		$sql = 'SELECT *
		FROM REPORT_CARD_COMMENT_CODE_SCALES
		WHERE SCHOOL_ID=\'' . UserSchool() . '\'
		ORDER BY SORT_ORDER,ID';

		$functions = array(
			'TITLE' => '_makeCommentsInput',
			'COMMENT' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
		);

		$LO_columns = array(
			'TITLE' => _( 'Comment Scale' ),
			'COMMENT' => _( 'Comment' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		);

		$link['add']['html'] = array(
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'COMMENT' => _makeCommentsInput( '', 'COMMENT' ),
			/*'HHR_GPA_VALUE' => _makeCommentsInput( '', 'HHR_GPA_VALUE' ),
			'HR_GPA_VALUE' => _makeCommentsInput( '', 'HR_GPA_VALUE' ),*/
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new';
		$link['remove']['variables'] = array( 'id' => _( 'ID' ) );
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = array(
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		);

		$subject = 'Comment Code Scales';
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' .
		$_REQUEST['tab_id'] . '" method="POST">';

	DrawHeader( '', SubmitButton() );
	echo '<br />';

	$LO_options = array(
		'save' => false,
		'search' => false,
		'header' => WrapTabs(
			$tabs,
			'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id']
		),
	);

	if ( $subject == 'Codes' )
	{
		ListOutput( $LO_ret, $LO_columns, 'Code', 'Codes', $link, array(), $LO_options );
	}
	elseif ( $subject == 'Comment Code Scales' )
	{
		ListOutput(
			$LO_ret,
			$LO_columns,
			'Comment Code Scale',
			'Comment Code Scales',
			$link,
			array(),
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

	if ( $THIS_RET['ID'] )
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
	}
	elseif ( $name === 'SHORT_NAME' )
	{
		$extra = 'size=15 maxlength=100';
	}
	elseif ( $name === 'SORT_ORDER' )
	{
		$extra = 'size=3 maxlength=5';
	}
	elseif ( $name === 'TITLE' )
	{
		$extra = 'size=15 maxlength=25';

		if ( $_REQUEST['tab_id'] !== 'new' )
		{
			$extra = 'size=5 maxlength=5';
		}

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	else
	{
		$extra = 'size=5 maxlength=5';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

