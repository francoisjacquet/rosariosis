<?php

DrawHeader( ProgramTitle() );

//$extra['header_left'] .= sprintf(_('Include courses active as of %s'),PrepareDate('','_include_active_date'));

$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
	FROM custom_fields
	WHERE ID=200000004
	AND TYPE='date'", [], [ 'ID' ] );

if ( $custom_fields_RET['200000004'] )
{
	MyWidgets( 'birthmonth' );
	MyWidgets( 'birthday' );
}

require_once 'modules/misc/Export.php';

/**
 * @param $item
 */
function MyWidgets( $item )
{
	global $extra, $_ROSARIO;

	$extra['search'] = issetVal( $extra['search'], '' );
	$extra['WHERE'] = issetVal( $extra['WHERE'], '' );
	$extra['NoSearchTerms'] = issetVal( $extra['NoSearchTerms'], '' );
	$_ROSARIO['SearchTerms'] = issetVal( $_ROSARIO['SearchTerms'], '' );

	switch ( $item )
	{
		case 'birthmonth':
			$options = [ '1' => _( 'January' ), '2' => _( 'February' ), '3' => _( 'March' ), '4' => _( 'April' ), '5' => _( 'May' ), '6' => _( 'June' ), '7' => _( 'July' ), '8' => _( 'August' ), '9' => _( 'September' ), '10' => _( 'October' ), '11' => _( 'November' ), '12' => _( 'December' ) ];

			if ( ! empty( $_REQUEST['birthmonth'] ) )
			{
				$extra['WHERE'] .= " AND extract(month from s.CUSTOM_200000004)='" . $_REQUEST['birthmonth'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Birth Month' ) . ': </b>' . $options[$_REQUEST['birthmonth']] . '<br />';
				}
			}

			$extra['search'] .= '<tr><td><label for="birthmonth">' . _( 'Birth Month' ) . '</label></td><td><select name="birthmonth" id="birthmonth"><option value="">' . _( 'N/A' ) . '</option>';

			foreach ( (array) $options as $key => $val )
			{
				$extra['search'] .= '<option value="' . AttrEscape( $key ) . '">' . $val . '</option>';
			}

			$extra['search'] .= '</select></td></tr>';
			break;

		case 'birthday':
			$options = [ '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23', '24' => '24', '25' => '25', '26' => '26', '27' => '27', '28' => '28', '29' => '29', '30' => '30', '31' => '31' ];

			if ( ! empty( $_REQUEST['birthday'] ) )
			{
				$extra['WHERE'] .= " AND extract(day from s.CUSTOM_200000004)='" . $_REQUEST['birthday'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Birth Day' ) . ': </b>' . $options[$_REQUEST['birthday']] . '<br />';
				}
			}

			$extra['search'] .= '<tr><td><label for="birthday">' . _( 'Birth Day' ) . '</label></td><td><select name="birthday" id="birthday"><option value="">' . _( 'N/A' ) . '</option>';

			foreach ( (array) $options as $key => $val )
			{
				$extra['search'] .= '<option value="' . AttrEscape( $key ) . '">' . $val . '</option>';
			}

			$extra['search'] .= '</select></td></tr>';
			break;
	}
}
