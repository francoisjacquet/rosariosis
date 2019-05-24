<?php
/**
 * Transcripts functions
 */

if ( ! function_exists( 'TranscriptsIncludeForm' ) )
{
	/**
	 * Get Include on Transcript form
	 *
	 * @todo Use Inputs.php functions.
	 *
	 * @example $return = TranscriptsIncludeForm();
	 *
	 * @since 4.0 Define your custom function in your addon module or plugin.
	 *
	 * @global $extra Get $extra['search'] for Mailing Labels Widget
	 *
	 * @uses _getOtherAttendanceCodes()
	 *
	 * @param  string  $include_on_title Form title (optional). Defaults to 'Include on Transcript'.
	 * @param  boolean $mailing_labels   Include Mailing Labels widget (optional). Defaults to true.
	 * @return string  Include on Transcript form
	 */
	function TranscriptsIncludeForm( $include_on_title = 'Include on Transcript', $mailing_labels = true )
	{
		global $extra;

		$return = '<table class="width-100p">';

		$return .= '<tr><td colspan="2"><b>' . _( 'Include on Transcript' ) .
		'</b><input type="hidden" name="SCHOOL_ID" value="' . UserSchool() . '" /><br /></td></tr>';

		// FJ history grades & previous school years in Transripts.

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$syear_history_RET = DBGet( "SELECT DISTINCT SYEAR
				FROM HISTORY_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				UNION SELECT DISTINCT SYEAR
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SYEAR DESC" );

			// If History School Years or previous school years.

			if ( $syear_history_RET )
			{
				$return .= '<tr class="st"><td>';

				$syoptions[UserSyear()] = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );

				// Chosen Multiple select input.
				$syextra = 'multiple';

				foreach ( (array) $syear_history_RET as $syear_history )
				{
					$syoptions[$syear_history['SYEAR']] = FormatSyear(
						$syear_history['SYEAR'],
						Config( 'SCHOOL_SYEAR_OVER_2_YEARS' )
					);
				}

				$return .= ChosenSelectInput(
					UserSyear(),
					'syear_arr[]',
					_( 'School Years' ),
					$syoptions,
					false,
					$syextra,
					false
				);

				$return .= '<hr /></td></tr>';
			}
		}

		$mp_types = DBGet( "SELECT DISTINCT MP_TYPE
			FROM MARKING_PERIODS
			WHERE NOT MP_TYPE IS NULL
			AND SCHOOL_ID='" . UserSchool() . "'", array(), array() );

		$return .= '<tr class="st"><td class="valign-top">';

		//FJ add translation
		$marking_periods_locale = array(
			'Year' => _( 'Year' ),
			'Semester' => _( 'Semester' ),
			'Quarter' => _( 'Quarter' ),
		);

		foreach ( (array) $mp_types as $mp_type )
		{
			//FJ add <label> on checkbox
			$return .= '<label><input type="checkbox" name="mp_type_arr[]" value="' . $mp_type['MP_TYPE'] . '"> ' . $marking_periods_locale[ucwords( $mp_type['MP_TYPE'] )] . '</label> ';
		}

		$return .= FormatInputTitle( _( 'Marking Periods' ) ) . '<hr /></td></tr>';

		$return .= '<tr class="st"><td class="valign-top">';

		//FJ add Show Grades option
		$return .= '<label><input type="checkbox" name="showgrades" value="1" checked /> ' . _( 'Grades' ) . '</label>';

		$return .= '<br /><br /><label><input type="checkbox" name="showstudentpic" value="1"> ' . _( 'Student Photo' ) . '</label>';

		//FJ add Show Comments option
		$return .= '<br /><br /><label><input type="checkbox" name="showmpcomments" value="1"> ' . _( 'Comments' ) . '</label>';

		//FJ add Show Credits option
		$return .= '<br /><br /><label><input type="checkbox" name="showcredits" value="1" checked /> ' . _( 'Credits' ) . '</label>';

		//FJ add Show Credit Hours option
		$return .= '<br /><br /><label><input type="checkbox" name="showcredithours" value="1"> ' . _( 'Credit Hours' ) . '</label>';

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$field_SSECURITY = ParseMLArray( DBGet( "SELECT TITLE
				FROM CUSTOM_FIELDS
				WHERE ID = 200000003" ), 'TITLE' );

			$return .= '<br /><br /><label><input type="checkbox" name="showcertificate" autocomplete="off" value="1" onclick=\'javascript: document.getElementById("divcertificatetext").style.display="block"; document.getElementById("inputcertificatetext").focus();\'> ' . _( 'Studies Certificate' ) . '</label>';
		}

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$return .= '<div id="divcertificatetext" style="display:none">';

			$return .= TinyMCEInput(
				GetTemplate(),
				'inputcertificatetext',
				_( 'Studies Certificate Text' )
			);

			$substitutions = array(
				'__SSECURITY__' => $field_SSECURITY[1]['TITLE'],
				'__FULL_NAME__' => _( 'Display Name' ),
				'__LAST_NAME__' => _( 'Last Name' ),
				'__FIRST_NAME__' => _( 'First Name' ),
				'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
				'__GRADE_ID__' => _( 'Grade Level' ),
				'__NEXT_GRADE_ID__' => _( 'Next Grade' ),
				'__SCHOOL_ID__' => _( 'School' ),
				'__YEAR__' => _( 'School Year' ),
				'__BLOCK2__' => _( 'Text Block 2' ),
			);

			$return .= '<table><tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'</td></tr>';

			$return .= '</table></div>';
		}

		$return .= '</td></tr></table>';

		return $return;
	}
}
