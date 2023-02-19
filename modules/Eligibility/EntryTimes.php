<?php

DrawHeader( ProgramTitle() );

if ( AllowEdit()
	&& ! empty( $_REQUEST['values'] ) )
{
	if ( $_REQUEST['values']['START_M'] == 'PM' )
	{
		$_REQUEST['values']['START_HOUR'] += 12;
	}

	if ( $_REQUEST['values']['END_M'] == 'PM' )
	{
		$_REQUEST['values']['END_HOUR'] += 12;
	}

	$start = $_REQUEST['values']['START_DAY'] . $_REQUEST['values']['START_HOUR'] . $_REQUEST['values']['START_MINUTE'];
	$end = $_REQUEST['values']['END_DAY'] . $_REQUEST['values']['END_HOUR'] . $_REQUEST['values']['END_MINUTE'];

	if ( $start <= $end )
	{
		foreach ( (array) $_REQUEST['values'] as $title => $value )
		{
			ProgramConfig( 'eligibility', $title, $value );
		}

		$note[] = button( 'check' ) . ' ' . _( 'Your changes were saved.' );
	}
}

echo ErrorMessage( $note, 'note' );

// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY.
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array) $eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

$days = [
	_( 'Monday' ),
	_( 'Tuesday' ),
	_( 'Wednesday' ),
	_( 'Thursday' ),
	_( 'Friday' ),
	_( 'Saturday' ),
	_( 'Sunday' ),
];

for ( $i = 0; $i < 7; $i++ )
{
	$day_options[$i + 1] = $days[$i];
}

for ( $i = 1; $i <= 11; $i++ )
{
	$hour_options[$i] = $i;
}

$hour_options['0'] = '12';

for ( $i = 0; $i <= 59; $i++ )
{
	$minute_options[$i] = str_pad( $i, 2, '0', STR_PAD_LEFT );
}

$m_options = [ 'AM' => 'AM', 'PM' => 'PM' ];

if ( $START_HOUR > 12 )
{
	$START_HOUR -= 12;
	$START_M = 'PM';
}
else
{
	$START_M = 'AM';
}

if ( $END_HOUR > 12 )
{
	$END_HOUR -= 12;
	$END_M = 'PM';
}
else
{
	$END_M = 'AM';
}


echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

PopTable( 'header', _( 'Allow Eligibility Posting' ) );

echo '<table class="cellpadding-5"><tr><td><b>' . _( 'From' ) . '</b></td><td>' .
	SelectInput( $START_DAY, 'values[START_DAY]', '', $day_options, false, '', false ) . ' ' .
	SelectInput( $START_HOUR, 'values[START_HOUR]', '', $hour_options, false, '', false ) . ' <b>:</b> ' .
	SelectInput( $START_MINUTE, 'values[START_MINUTE]', '', $minute_options, false, '', false ) . ' ' .
	SelectInput( $START_M, 'values[START_M]', '', $m_options, false, '', false ) .
	'</td></tr>';

echo '<tr><td><b>' . _( 'To' ) . '</b></td><td>' .
	SelectInput( $END_DAY, 'values[END_DAY]', '', $day_options, false, '', false ) . ' ' .
	SelectInput( $END_HOUR, 'values[END_HOUR]', '', $hour_options, false, '', false ) . ' <b>:</b> ' .
	SelectInput( $END_MINUTE, 'values[END_MINUTE]', '', $minute_options, false, '', false ) . ' ' .
	SelectInput( $END_M, 'values[END_M]', '', $m_options, false, '', false ) .
	'</td></tr></table>';

PopTable( 'footer' );

echo '<br /><div class="center">' . SubmitButton() . '</div></form>';

