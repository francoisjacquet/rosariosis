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

	switch ( $category['DATA_TYPE'] )
	{
		case 'text':

			$_ROSARIO['ReferralInput'] = TextInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'maxlength=1000'
			);

			break;

		case 'numeric':

			$_ROSARIO['ReferralInput'] = TextInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'type="number" min="-999999999999999999" max="999999999999999999"'
			);

			break;

		case 'textarea':

			$_ROSARIO['ReferralInput'] = TextAreaInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'maxlength=5000 rows=4 cols=30' // @deprecated in 4.7-beta use maxlength=50000.
			);

			break;

		case 'checkbox':

			$_ROSARIO['ReferralInput'] = CheckboxInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				'',
				$new
			);

			break;

		case 'date':

			$_ROSARIO['ReferralInput'] = DateInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				! $new
			);

			break;

		case 'multiple_checkbox':

			$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS'] ) );

			// @since 4.2
			$_ROSARIO['ReferralInput'] = MultipleCheckboxInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . '][]',
				$category['TITLE'],
				$options
			);

			break;

		case 'multiple_radio':

			$options = array();

			$radio_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS'] ) );

			foreach ( (array) $radio_options as $option )
			{
				$options[$option] = $option;
			}

			$_ROSARIO['ReferralInput'] = RadioInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				$options,
				false
			);

			break;

		case 'select':

			$options = array();

			$select_options = explode( "\r", $str_replace( "\n", "\r", str_replace( "\r\n", "\r", $category['SELECT_OPTIONS'] ) ) );

			foreach ( (array) $select_options as $option )
			{
				$options[$option] = $option;
			}

			$_ROSARIO['ReferralInput'] = SelectInput(
				$value,
				'values[CATEGORY_' . $category['ID'] . ']',
				$category['TITLE'],
				$options,
				'N/A'
			);

			break;

		default:

			$_ROSARIO['ReferralInput'] = '';
	}

	$action_args = array(
		'category' => $category,
		'value' => $value,
		'new' => $new,
	);

	// @since 4.5 Referral Input action hook.
	// Filter $_ROSARIO['ReferralInput'] global.
	do_action( 'Discipline/includes/Referral.fnc.php|referral_input', $action_args );

	return $_ROSARIO['ReferralInput'];
}
