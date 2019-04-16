<?php
DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			if ( $id !== 'new' )
			{
				$sql = "UPDATE SCHOOL_GRADELEVELS SET ";

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
				$sql = "INSERT INTO SCHOOL_GRADELEVELS ";

				$fields = 'ID,SCHOOL_ID,';
				$values = db_seq_nextval( 'SCHOOL_GRADELEVELS_SEQ' ) . ",'" . UserSchool() . "',";

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
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Grade Level' ) ) )
	{
		DBQuery( "DELETE FROM SCHOOL_GRADELEVELS WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$sql = "SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID
	FROM SCHOOL_GRADELEVELS
	WHERE SCHOOL_ID='" . UserSchool() . "'
	ORDER BY SORT_ORDER";

	$grades_RET = DBGet(
		DBQuery( $sql ),
		array(
			'TITLE' => '_makeTextInput',
			'SHORT_NAME' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
			'NEXT_GRADE_ID' => '_makeGradeInput',
		)
	);

	$columns = array(
		'TITLE' => _( 'Title' ),
		'SHORT_NAME' => _( 'Short Name' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'NEXT_GRADE_ID' => _( 'Next Grade' ),
	);

	$link['add']['html'] = array(
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'NEXT_GRADE_ID' => _makeGradeInput( '', 'NEXT_GRADE_ID' ),
	);

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = array( 'id' => 'ID' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $grades_RET, $columns, 'Grade Level', 'Grade Levels', $link );

	echo '<div class="center">' . SubmitButton() . '</div></form>';
}

/**
 * @param $value
 * @param $name
 * @return mixed
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

	$extra = '';

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=2';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	$comment = '';

	if ( $name === 'SORT_ORDER' )
	{
		$comment = '<!-- ' . $value . ' -->';
	}

	return $comment .
	TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 */
function _makeGradeInput( $value, $name )
{
	global $THIS_RET,
		$grades;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( ! $grades )
	{
		$grades_RET = DBGet( "SELECT ID,TITLE
			FROM SCHOOL_GRADELEVELS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $grades_RET as $grade )
		{
			$grades[$grade['ID']] = $grade['TITLE'];
		}
	}

	return SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $grades, _( 'N/A' ) );
}
