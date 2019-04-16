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
			if ( $id !== 'new' )
			{
				$sql = "UPDATE RESOURCES SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
				DBQuery( $sql );
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$sql = "INSERT INTO RESOURCES ";

				$fields = 'ID,SCHOOL_ID,';
				$values = db_seq_nextval( 'RESOURCES_SEQ' ) . ",'" . UserSchool() . "',";

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
		DBQuery( "DELETE FROM RESOURCES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$sql = "SELECT ID,TITLE,LINK FROM RESOURCES WHERE SCHOOL_ID='" . UserSchool() . "' ORDER BY ID";
	$QI = DBQuery( $sql );
	$resources_RET = DBGet( $QI, array( 'TITLE' => '_makeTextInput', 'LINK' => '_makeLink' ) );

	$columns = array( 'TITLE' => _( 'Title' ), 'LINK' => _( 'Link' ) );
	$link['add']['html'] = array( 'TITLE' => _makeTextInput( '', 'TITLE' ), 'LINK' => _makeLink( '', 'LINK' ) );
	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = array( 'id' => 'ID' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';
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

	if ( $THIS_RET['ID'] )
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
	if ( isset( $_REQUEST['LO_save'] )
		&& $_REQUEST['LO_save'] )
	{
		// Export list.
		return $value;
	}

	if ( AllowEdit() )
	{
		if ( $value )
		{
			return '<div style="display:table-cell;"><a href="' . $value . '" target="_blank">' .
			_( 'Link' ) . '</a>&nbsp;</div>
				<div style="display:table-cell;">' . _makeTextInput( $value, $name ) . '</div>';
		}
		else
		{
			return _makeTextInput( $value, $name );
		}
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

	return '<a href="' . $value . '" target="_blank">' . $truncated_link . '</a>';
}
