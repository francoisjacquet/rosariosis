<?php
/**
 * Referral functions
 */

/**
 * Referral Input
 * Get referral input HTML based on its type.
 *
 * @since 4.5
 *
 * @global $_ROSARIO['ReferralInput'] Contains the HTML code.
 * @deprecated Filter $_ROSARIO['ReferralInput'] global. Since 5.4 Use &$input instead.
 *
 * @example echo ReferralInput( $category, $RET['CATEGORY_' . $category['ID'] ], false );
 *
 * @param array   $category Referral category array.
 * @param string  $value    Field value.
 * @param boolean $new      New?
 *
 * @return string Referral Input HTML.
 */
function ReferralInput( $category, $value = '', $new = true )
{
	global $_ROSARIO;

	$input = '';

	switch ( $category['DATA_TYPE'] )
	{
		case 'text':

			$input = TextInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'maxlength=1000'
			);

			break;

		case 'numeric':

			$input = TextInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'type="number" min="-999999999999999999" max="999999999999999999"'
			);

			break;

		case 'textarea':

			$input = TextAreaInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'maxlength=5000 rows=4 cols=30' // @deprecated in 4.7-beta use maxlength=50000.
			);

			break;

		case 'checkbox':

			$input = CheckboxInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'',
				$new
			);

			break;

		case 'date':

			$input = DateInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				! $new
			);

			break;

		case 'multiple_checkbox':

			$options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", (string) $category['SELECT_OPTIONS'] ) );

			// @since 4.2
			$input = MultipleCheckboxInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . '][]',
				$category['TITLE'],
				$options
			);

			break;

		case 'multiple_radio':
		case 'select':

			$options = [];

			$radio_select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", (string) $category['SELECT_OPTIONS'] ) );

			foreach ( (array) $radio_select_options as $option )
			{
				$options[$option] = $option;
			}

			if ( $category['DATA_TYPE'] === 'multiple_radio' )
			{
				$input = RadioInput(
					$value,
					'values[CATEGORY_' . $category['ID'] . ']',
					$category['TITLE'],
					$options,
					false
				);

				break;
			}

			$input = SelectInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				$options,
				'N/A'
			);

			break;
	}

	// @deprecated Filter $_ROSARIO['ReferralInput'] global.
	$_ROSARIO['ReferralInput'] = $input;

	$action_args = [
		'category' => $category,
		'value' => $value,
		'new' => $new,
		'input' => &$input,
	];

	// @since 4.5 Referral Input action hook.
	do_action( 'Discipline/includes/Referral.fnc.php|referral_input', $action_args );

	return $input;
}
