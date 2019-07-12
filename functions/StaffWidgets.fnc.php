<?php
/**
 * Staff Widgets function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Staff Widgets
 * Essentially used in the Find a User form
 *
 * @todo  Fill $extra['search'] only if required (see Search.inc.php, if !search_modfunc?)
 *
 * @global array   $_ROSARIO       Sets $_ROSARIO['StaffWidgets']
 * @global array   $RosarioModules
 * @global array   $extra
 *
 * @param  string  $item           Staff widget name or 'all' Staff widgets.
 * @param  array   $myextra       Search.inc.php extra (HTML, functions...) (optional). Defaults to global $extra.
 *
 * @return boolean true if Staff Widget loaded, false if insufficient rights or already saved widget
 */
function StaffWidgets( $item, &$myextra = null )
{
	global $extra,
		$_ROSARIO,
		$RosarioModules;

	// Do not use `! empty()` here.
	if ( isset( $myextra ) )
	{
		$extra =& $myextra;
	}

	if ( ! isset( $_ROSARIO['StaffWidgets'] )
		|| ! is_array( $_ROSARIO['StaffWidgets'] ) )
	{
		$_ROSARIO['StaffWidgets'] = array();
	}

	if ( ! isset( $extra['functions'] )
		|| ! is_array( $extra['functions'] ) )
	{
		$extra['functions'] = array();
	}

	if ( ! isset( $extra['search'] ) )
	{
		$extra['search'] = '';
	}

	// If insufficient rights or already saved widget, exit.
	if ( ( User('PROFILE') !== 'admin'
			&& User( 'PROFILE' ) !== 'teacher' )
		|| ( isset( $_ROSARIO['StaffWidgets'][ $item ] )
			&& $_ROSARIO['StaffWidgets'][ $item ] ) )
	{
		return false;
	}

	switch ( (string) $item )
	{
		// All Widgets (or almost).
		case 'all':

			// FJ regroup widgets wrap.
			$widget_wrap_header =
			function( $title )
			{
				return '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu">
				<b>' . $title . '</b></a>
				<br />
				<table class="widefat width-100p col1-align-right hide">';
			};

			$widget_wrap_footer = '</table>';

			// Users.
			if ( $RosarioModules['Users']
				&& ( empty( $_ROSARIO['StaffWidgets']['permissions'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Users' ) );

				StaffWidgets( 'permissions', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Food Service.
			if ( $RosarioModules['Food_Service']
				&& ( empty( $_ROSARIO['StaffWidgets']['fsa_balance'] )
					|| empty( $_ROSARIO['StaffWidgets']['fsa_status'] )
					|| empty( $_ROSARIO['StaffWidgets']['fsa_barcode'] ) ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Food Service' ) );

				StaffWidgets( 'fsa_balance', $extra );
				StaffWidgets( 'fsa_status', $extra );
				StaffWidgets( 'fsa_barcode', $extra );
				StaffWidgets( 'fsa_exists', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

			// Accounting.
			if ( $RosarioModules['Accounting']
				&& ( empty( $_ROSARIO['Widgets']['staff_balance'] ) )
				&& AllowUse( 'Accounting/StaffBalances.php' ) )
			{
				$extra['search'] .= $widget_wrap_header( _( 'Accounting' ) );

				StaffWidgets( 'staff_balance', $extra );

				$extra['search'] .= $widget_wrap_footer;
			}

		break;

		// User Widgets (configured in My Preferences).
		case 'user':

			/*$widgets_RET = DBGet( "SELECT TITLE
				FROM PROGRAM_USER_CONFIG
				WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
				AND PROGRAM='StaffWidgetsSearch'" .
				( count( $_ROSARIO['StaffWidgets'] ) ?
					"AND TITLE NOT IN ('" . implode( "','", array_keys( $_ROSARIO['StaffWidgets'] ) ) . "')" :
					''
				) );*/

			$user_widgets = ProgramUserConfig( 'StaffWidgetsSearch' );

			$saved_widget_titles = array_keys( $_ROSARIO['StaffWidgets'] );

			foreach ( (array) $user_widgets as $user_widget_title => $value )
			{
				if ( ! in_array( $user_widget_title, $saved_widget_titles ) )
				{
					StaffWidgets( $user_widget_title, $extra );
				}
			}

		break;

		case 'permissions_Y':
		case 'permissions_N':

			$value = mb_substr( $item, 12 );

			$item = 'permissions';

		// Permissions Widget.
		case 'permissions':

			if ( ! $RosarioModules['Users'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['permissions'] ) )
			{
				$extra['WHERE'] .= " AND s.PROFILE_ID IS " . ( $_REQUEST['permissions'] == 'Y' ? 'NOT' : '' ) . " NULL
					AND s.PROFILE!='none'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Permissions' ) . ': </b>' .
						( $_REQUEST['permissions'] == 'Y' ? _( 'Profile' ) : _( 'Custom' ) ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' .	_( 'Permissions' ) . '</td><td>
			<label><input type="radio" name="permissions" value=""' . ( empty( $value ) ? ' checked' : '' ) . '> ' .
				_( 'All' ) . '</label> &nbsp;
			<label><input type="radio" name="permissions" value="Y"' . ( isset( $value ) && $value == 'Y' ? ' checked' : '' ) . '> ' .
				_( 'Profile' ) . '</label> &nbsp;
			<label><input type="radio" name="permissions" value="N"' . ( isset( $value ) && $value == 'N' ? ' checked' : '' ) . '> ' .
				_( 'Custom' ) . '</label>
			</td></tr>';

		break;

		case 'fsa_balance_warning':

			$value = $GLOBALS['warning'];

			$item = 'fsa_balance';

		// Food Service Balance Widget.
		case 'fsa_balance':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( isset( $_REQUEST['fsa_balance'] )
				&& $_REQUEST['fsa_balance'] != '' )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';

					$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
				}

				$extra['WHERE'] .= " AND fssa.BALANCE" . ( $_REQUEST['fsa_bal_ge'] == 'Y' ? '>=' : '<' ) .
					"'" . round( $_REQUEST['fsa_balance'], 2 ) . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Balance' ) . ' </b>
						<span class="sizep2">' . ( $_REQUEST['fsa_bal_ge'] == 'Y' ? '&ge;' : '&lt;' ) . '</span>' .
						number_format( $_REQUEST['fsa_balance'], 2 ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_balance">' . _( 'Balance' ) . '</label></td><td>
			<label class="sizep2">
				<input type="radio" name="fsa_bal_ge" value="" checked /> &lt;</label>&nbsp;
			<label  class="sizep2">
				<input type="radio" name="fsa_bal_ge" value="Y" /> &ge;</label>
			<input type="text" name="fsa_balance" id="fsa_balance" size="7" maxlength="9"' . ( isset( $value ) ? ' value="' . $value . '"' : '') . ' />
			</td></tr>';

		break;

		case 'fsa_status_active':

			$value = 'active';

			$item = 'fsa_status';

		// Food Service Status Widget.
		case 'fsa_status':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_status'] ) )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';

					$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
				}

				if ( $_REQUEST['fsa_status'] == 'Active' )
				{
					$extra['WHERE'] .= ' AND fssa.STATUS IS NULL';
				}
				else
					$extra['WHERE'] .= " AND fssa.STATUS='" . $_REQUEST['fsa_status'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Status' ) . ': </b>' .
						$_REQUEST['fsa_status'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_status">' . _( 'Account Status' ) . '</label></td><td>
			<select name="fsa_status" id="fsa_status">
			<option value="">' . _( 'Not Specified' ) . '</option>
			<option value="Active"' . ( isset( $value ) && $value == 'active' ? ' selected' : '' ) . '>' . _( 'Active' ) . '</option>
			<option value="Inactive">' . _( 'Inactive' ) . '</option>
			<option value="Disabled">' . _( 'Disabled' ) . '</option>
			<option value="Closed">' . _( 'Closed' ) . '</option>
			</select>
			</td></tr>';

		break;

		// Food Service Barcode Widget
		case 'fsa_barcode':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_barcode'] ) )
			{
				if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
				{
					$extra['FROM'] .= ',FOOD_SERVICE_STAFF_ACCOUNTS fssa';

					$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
				}

				$extra['WHERE'] .= " AND fssa.BARCODE='" . $_REQUEST['fsa_barcode'] . "'";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Barcode' ) . ': </b>' .
						$_REQUEST['fsa_barcode'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td><label for="fsa_barcode">' . _( 'Barcode' ) . '</label></td><td>
			<input type="text" name="fsa_barcode" id="fsa_barcode" size="15" maxlength="50" />
			</td></tr>';

		break;

		case 'fsa_exists_N':
		case 'fsa_exists_Y':

			$value = mb_substr( $item, 11 );

			$item = 'fsa_exists';

		// Food Service Account Exists Widget.
		case 'fsa_exists':

			if ( ! $RosarioModules['Food_Service'] )
			{
				break;
			}

			if ( ! empty( $_REQUEST['fsa_exists'] ) )
			{
				$extra['WHERE'] .= ' AND ' . ( $_REQUEST['fsa_exists'] == 'N' ? 'NOT ' : '' ) . "EXISTS
					(SELECT 'exists'
						FROM FOOD_SERVICE_STAFF_ACCOUNTS
						WHERE STAFF_ID=s.STAFF_ID)";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Food Service Account Exists' ) . ': </b>' .
						( $_REQUEST['fsa_exists'] == 'Y' ? _( 'Yes' ) : _( 'No' ) ) . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Has Account' ) . '</td><td>
			<label><input type="radio" name="fsa_exists" value=""' . ( empty( $value ) ? ' checked' : '' ) . ' /> ' .
				_( 'All') . '</label> &nbsp;
			<label><input type="radio" name="fsa_exists" value="Y"' . ( isset( $value ) && $value == 'Y' ? ' checked' : '' ).' /> '.
				_( 'Yes' ) . '</label> &nbsp;
			<label><input type="radio" name="fsa_exists" value="N"' . ( isset( $value ) && $value == 'N' ? ' checked' : '' ) . ' /> '.
				_( 'No' ) . '</label>
			</td></tr>';

		break;

		// Staff Payroll Balance Widget.
		case 'staff_balance':

			if ( ! $RosarioModules['Accounting']
				|| ! AllowUse( 'Accounting/StaffBalances.php' ) )
			{
				break;
			}

			if ( isset( $_REQUEST['balance_low'] )
				&& is_numeric( $_REQUEST['balance_low'] )
				&& isset( $_REQUEST['balance_high'] )
				&& is_numeric( $_REQUEST['balance_high'] ) )
			{
				if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'] )
				{
					$temp = $_REQUEST['balance_high'];

					$_REQUEST['balance_high'] = $_REQUEST['balance_low'];

					$_REQUEST['balance_low'] = $temp;
				}

				$extra['WHERE'] .= " AND (coalesce((SELECT sum(p.AMOUNT)
						FROM ACCOUNTING_PAYMENTS p
						WHERE p.STAFF_ID=s.STAFF_ID
						AND p.SYEAR=s.SYEAR),0)
					-coalesce((SELECT sum(f.AMOUNT)
						FROM ACCOUNTING_SALARIES f
						WHERE f.STAFF_ID=s.STAFF_ID
						AND f.SYEAR=s.SYEAR),0))
					BETWEEN '" . $_REQUEST['balance_low'] . "'
					AND '" . $_REQUEST['balance_high'] . "' ";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Staff Payroll Balance' ) . ': </b>' .
						_( 'Between' ) . ' ' . $_REQUEST['balance_low'] .
						' &amp; ' . $_REQUEST['balance_high'] . '<br />';
				}
			}

			$extra['search'] .= '<tr class="st"><td>' . _( 'Staff Payroll Balance' ) . '</td><td>' .
			_( 'Between' ) .
			' <input type="text" name="balance_low" size="5" maxlength="10" /> &amp;
			<input type="text" name="balance_high" size="5" maxlength="10" />
			</td></tr>';

		break;
	}

	$_ROSARIO['StaffWidgets'][ $item ] = true;

	return true;
}
