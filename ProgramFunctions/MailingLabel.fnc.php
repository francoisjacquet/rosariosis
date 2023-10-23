<?php
/**
 * Mailing Label function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Mailing Label
 *
 * @global $THIS_RET DBGet() Current row: $THIS_RET['STUDENT_ID'].
 * @static $mailing_labels Mailing Labels cache.
 *
 * @since 9.0 Add Student name if no Contacts at address
 *
 * @param string $address_id Address ID.
 *
 * @return string Formatted mailing address.
 */
function MailingLabel( $address_id )
{
	global $THIS_RET;

	static $mailing_labels = [];

	if ( ! $address_id )
	{
		return '';
	}

	$student_id = $THIS_RET['STUDENT_ID'];

	if ( ! isset( $mailing_labels[ $address_id ][ $student_id ] ) )
	{
		$people_RET = DBGet( "SELECT p.FIRST_NAME,p.MIDDLE_NAME,p.LAST_NAME,
		coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,coalesce(a.MAIL_CITY,a.CITY) AS CITY,
		coalesce(a.MAIL_STATE,a.STATE) AS STATE,coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE
		FROM address a JOIN students_join_address sja ON (a.ADDRESS_ID=sja.ADDRESS_ID)
		LEFT OUTER JOIN students_join_people sjp ON (sjp.ADDRESS_ID=sja.ADDRESS_ID
			AND sjp.STUDENT_ID=sja.STUDENT_ID
			AND (sjp.CUSTODY='Y' OR sja.RESIDENCE IS NULL))
		LEFT OUTER JOIN people p ON (p.PERSON_ID=sjp.PERSON_ID)
		WHERE sja.STUDENT_ID='" . (int) $student_id . "'
		AND sja.ADDRESS_ID='" . (int) $address_id . "'
		ORDER BY sjp.STUDENT_RELATION", [], [ 'LAST_NAME' ] );

		$return = '';

		// People names.
		foreach ( (array) $people_RET as $people )
		{
			$people_total = count( $people );

			for ( $i = 1; $i < $people_total; $i++ )
			{
				$return .= $people[ $i ]['FIRST_NAME'] . ' &amp; ';
			}

			if ( $i === 1
				&& empty( $people[ $i ]['FIRST_NAME'] ) )
			{
				// Add Student name if no Contacts at address.
				$return .= $THIS_RET['FULL_NAME'] . '<br>';
			}
			else
			{
				$return .= DisplayName(
					$people[ $i ]['FIRST_NAME'],
					$people[ $i ]['LAST_NAME'],
					$people[ $i ]['MIDDLE_NAME']
				) . '<br>';
			}
		}

		// Mab - this is a bit of a kludge but insert an html comment so people and address can be split later.
		$return .= '<!-- -->' . $people[ $i ]['ADDRESS'] . '<br>' .
			$people[ $i ]['CITY'] . ( $people[ $i ]['STATE'] ? ', ' . $people[ $i ]['STATE'] : '' ) .
			( $people[ $i ]['ZIPCODE'] ? ' ' . $people[ $i ]['ZIPCODE'] : '' );

		$mailing_labels[ $address_id ][ $student_id ] = $return;
	}

	return $mailing_labels[ $address_id ][ $student_id ];
}
