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
			// FJ fix SQL bug invalid numeric data.
			if ( ( empty( $columns['SORT_ORDER'] )
					|| is_numeric($columns['SORT_ORDER'] ) )
				&& ( empty( $columns['LENGTH'] )
					|| is_numeric( $columns['LENGTH'] ) ) )
			{
				// Deprecated: was used for START_TIME & END_TIME.
				/*if ( $columns['START_TIME_HOUR'] != ''
					&& $columns['START_TIME_MINUTE']
					&& $columns['START_TIME_M'] )
				{
					$columns['START_TIME'] = $columns['START_TIME_HOUR'] . ':' .
						$columns['START_TIME_MINUTE'] . ' ' . $columns['START_TIME_M'];
				}

				unset( $columns['START_TIME_HOUR'] );
				unset( $columns['START_TIME_MINUTE'] );
				unset( $columns['START_TIME_M'] );

				if ( $columns['END_TIME_HOUR'] != ''
					&& $columns['END_TIME_MINUTE']
					&& $columns['END_TIME_M'] )
				{
					$columns['END_TIME'] = $columns['END_TIME_HOUR'] . ':' .
						$columns['END_TIME_MINUTE'] . ' ' . $columns['END_TIME_M'];
				}

				unset( $columns['END_TIME_HOUR'] );
				unset( $columns['END_TIME_MINUTE'] );
				unset( $columns['END_TIME_M'] );*/

				if ( $id !== 'new' )
				{
					$sql = "UPDATE SCHOOL_PERIODS SET ";

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE PERIOD_ID='" . $id . "'";

					DBQuery( $sql );
				}
				// New: check for Title.
				elseif ( $columns['TITLE'] )
				{
					$sql = "INSERT INTO SCHOOL_PERIODS ";

					$fields = 'PERIOD_ID,SCHOOL_ID,SYEAR,';
					$values = db_seq_nextval( 'SCHOOL_PERIODS_SEQ' ) . ",'" .
						UserSchool() . "','" . UserSyear() . "',";

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value === '0' )
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
				$error[] = _( 'Please enter valid Numeric data.' );
		}
	}

	// Unset modfunc & redirect.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Period' ) ) )
	{
		DBQuery( "DELETE FROM SCHOOL_PERIODS
			WHERE PERIOD_ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	// Remove Used for Attendance column, unused.
	$periods_RET = DBGet( "SELECT PERIOD_ID,TITLE,SHORT_NAME,SORT_ORDER,LENGTH,
		START_TIME,END_TIME,BLOCK,
		(SELECT 1
			FROM COURSE_PERIOD_SCHOOL_PERIODS cpsp,COURSE_PERIODS cp
			WHERE cpsp.PERIOD_ID=sp.PERIOD_ID
			AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND cp.SYEAR='" . UserSyear() . "'
			AND cp.SCHOOL_ID='" . UserSchool() . "'
			LIMIT 1) AS REMOVE
		FROM SCHOOL_PERIODS sp
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER",
	array(
		'REMOVE' => '_makeRemoveButton',
		'TITLE' => '_makeTextInput',
		'SHORT_NAME' => '_makeTextInput',
		'SORT_ORDER' => '_makeTextInput',
		'BLOCK' => '_makeTextInput',
		'LENGTH' => '_makeTextInput',
	) ); //	'ATTENDANCE' => '_makeCheckboxInput','START_TIME' => '_makeTimeInput','END_TIME' => '_makeTimeInput'

	$columns = array(
		'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'SHORT_NAME' => _( 'Short Name' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'LENGTH' => _( 'Length (minutes)' ),
		'BLOCK' => _( 'Block' ),
	); // 'ATTENDANCE' => _('Used for Attendance'),'START_TIME' => _('Start Time'),'END_TIME' => _('End Time'));

	$link['add']['html'] = array(
		'REMOVE' => _makeRemoveButton( '', 'REMOVE' ),
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'LENGTH' => _makeTextInput( '', 'LENGTH' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'BLOCK' => _makeTextInput( '', 'BLOCK' ),
	); // 'ATTENDANCE'=>_makeCheckboxInput('','ATTENDANCE'),'START_TIME'=>_makeTimeInput('','START_TIME'),'END_TIME'=>_makeTimeInput('','END_TIME')

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $periods_RET, $columns, 'Period', 'Periods', $link );

	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}


function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['PERIOD_ID'] )
	{
		$id = $THIS_RET['PERIOD_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	return TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}


// Deprecated: Remove Used for Attendance column, unused.
function _makeCheckboxInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['PERIOD_ID'] )
	{
		$id = $THIS_RET['PERIOD_ID'];
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


// Deprecated: was used for START_TIME & END_TIME.
function _makeTimeInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['PERIOD_ID'] )
	{
		$id = $THIS_RET['PERIOD_ID'];
	}
	else
	{
		$id = 'new';
	}

	$hour = mb_substr( $value, 0, mb_strpos( $value, ':' ) );

	$minute = mb_substr( $value, mb_strpos( $value, ':' ), mb_strpos( $value, ' ' ) );

	$m = mb_substr( $value, mb_strpos( $value, ' ' ) );

	for ( $i = 1; $i <= 11; $i++ )
	{
		$hour_options[ $i ] = '' . $i;
	}

	$hour_options['0'] = '12';

	for ( $i = 0; $i <= 9; $i++ )
	{
		$minute_options[ '0' . $i ] = '0'.$i;
	}

	for ( $i = 10; $i <= 59; $i++ )
	{
		$minute_options[ $i ] = '' . $i;
	}

	$m_options = array( 'AM' => 'AM', 'PM' => 'PM' );

	$time_html = '<table><tr><td>' . SelectInput(
		$hour,
		'values[' . $id . '][' . $name . '_HOUR]',
		'',
		$hour_options,
		'N/A',
		'',
		false
	) . ':</td><td>' . SelectInput(
		$minute,
		'values[' . $id . '][' . $name . '_MINUTE]',
		'',
		$minute_options,
		'N/A',
		'',
		false
	) . '</td><td>' . SelectInput(
		$m,
		'values[' . $id . '][' . $name . '_M]',
		'',
		$m_options,
		'N/A',
		'',
		false
	) . '</td></tr></table>';

	if ( $id !== 'new'
    	&& $value )
	{
		return InputDivOnclick(
			$name . $id,
			$time_html,
			$value,
			''
		);
	}
	else
		return $time_html;
}


/**
 * Make Remove button
 *
 * Local function
 * DBGet() callback
 *
 * @since 4.7
 *
 * @param  string $value  Value.
 * @param  string $column Column name, 'REMOVE'.
 *
 * @return string Remove button or add button or none if existing Course Periods use this School Period.
 */
function _makeRemoveButton( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['PERIOD_ID'] ) )
	{
		return button( 'add' );
	}

	if ( $value )
	{
		// Do NOT remove School Period as existing Course Periods use it.
		return '';
	}

	$button_link .= 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' .
		urlencode( $THIS_RET['PERIOD_ID'] );

	return button( 'remove', '', '"' . $button_link . '"' );
}
