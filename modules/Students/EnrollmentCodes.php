<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		//FJ fix SQL bug invalid sort order

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			if ( $id !== 'new' )
			{
				DBUpdate(
					'student_enrollment_codes',
					$columns,
					[ 'ID' => (int) $id ]
				);
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				DBInsert(
					'student_enrollment_codes',
					[ 'SYEAR' => UserSyear() ] + $columns
				);
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
	if ( DeletePrompt( _( 'Enrollment Code' ) ) )
	{
		DBQuery( "DELETE FROM student_enrollment_codes
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

// Check we have 1 and only one Rollover default code.
$rollover_default_RET = DBGet( "SELECT ID
	FROM student_enrollment_codes
	WHERE SYEAR='" . UserSyear() . "'
	AND TYPE='Add'
	AND DEFAULT_CODE='Y'" );

if ( ! $rollover_default_RET
	|| count( $rollover_default_RET ) !== 1 )
{
	$warning[] = _( 'There must be exactly one Rollover default enrollment code (of type Add).' );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	echo ErrorMessage( $warning, 'warning' );

	$codes_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME,TYPE,DEFAULT_CODE,SORT_ORDER
		FROM student_enrollment_codes
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [
			'TITLE' => '_makeTextInput',
			'SHORT_NAME' => '_makeTextInput',
			'TYPE' => '_makeSelectInput',
			'DEFAULT_CODE' => '_makeCheckboxInput',
			'SORT_ORDER' => '_makeTextInput',
		]
	);

	$columns = [
		'TITLE' => _( 'Title' ),
		'SHORT_NAME' => _( 'Short Name' ),
		'TYPE' => _( 'Type' ),
		'DEFAULT_CODE' => _( 'Rollover Default' ),
		'SORT_ORDER' => _( 'Sort Order' ),
	];

	$link['add']['html'] = [
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'TYPE' => _makeSelectInput( '', 'TYPE' ),
		'DEFAULT_CODE' => _makeCheckboxInput( '', 'DEFAULT_CODE' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
	DrawHeader( '', SubmitButton() );

	ListOutput( $codes_RET, $columns, 'Enrollment Code', 'Enrollment Codes', $link );
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

	if ( $name === 'SHORT_NAME' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	elseif ( $name === 'TITLE' )
	{
		$extra = 'maxlength=100';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 */
function _makeSelectInput( $value, $name )
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

	if ( $name === 'TYPE' )
	{
		$options = [ 'Add' => _( 'Add' ), 'Drop' => _( 'Drop' ) ];
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$options,
		false
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeCheckboxInput( $value, $name )
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

	return CheckboxInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		'',
		( $id === 'new' ),
		button( 'check' ),
		button( 'x' )
	);
}
