<?php
/**
 * Staff Widget interface and individual Widget classes
 *
 * @since 8.6
 *
 * @package RosarioSIS
 * @subpackage classes/core
 */

namespace RosarioSIS;

// StaffWidget interface.
// Implement this interface when creating a new StaffWidget.
interface StaffWidget
{
	/**
	 * Check whether StaffWidget can be built:
	 * Usually check if the corresponding Module is active
	 * Maybe check if User is admin
	 * Maybe check if AllowUse() for corresponding modname
	 *
	 * @param  array  $modules $RosarioModules global.
	 *
	 * @return bool True if can build StaffWidget, else false.
	 */
	public function canBuild( $modules );

	/**
	 * Build extra SQL, and search terms
	 *
	 * @param  array  $extra Extra array, see definition in Widgets class.
	 *
	 * @return array         $extra array with StaffWidget extra added.
	 */
	public function extra( $extra );

	/**
	 * Build HTML form
	 *
	 * @return string HTML form.
	 */
	public function html();
}


// Permissions Widget.
class StaffWidget_permissions implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Users'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['permissions'] ) )
		{
			return $extra;
		}

		$extra['WHERE'] .= " AND s.PROFILE_ID IS " . ( $_REQUEST['permissions'] == 'Y' ? 'NOT' : '' ) . " NULL
			AND s.PROFILE!='none'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Permissions' ) . ': </b>' .
				( $_REQUEST['permissions'] == 'Y' ? _( 'Profile' ) : _( 'Custom' ) ) . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td>' .	_( 'Permissions' ) . '</td><td>
		<label><input type="radio" name="permissions" value=""' . ( empty( $value ) ? ' checked' : '' ) . '> ' .
			_( 'All' ) . '</label> &nbsp;
		<label><input type="radio" name="permissions" value="Y"' . ( $value == 'Y' ? ' checked' : '' ) . '> ' .
			_( 'Profile' ) . '</label> &nbsp;
		<label><input type="radio" name="permissions" value="N"' . ( $value == 'N' ? ' checked' : '' ) . '> ' .
			_( 'Custom' ) . '</label>
		</td></tr>';
	}
}


// Permissions Yes Widget.
class StaffWidget_permissions_Y extends StaffWidget_permissions
{
	function html( $value = 'Y' )
	{
		return parent::html( $value );
	}
}


// Permissions No Widget.
class StaffWidget_permissions_N extends StaffWidget_permissions
{
	function html( $value = 'N' )
	{
		return parent::html( $value );
	}
}


// Food Service Balance Widget
class StaffWidget_fsa_balance implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['fsa_balance'] )
			|| ! is_numeric( $_REQUEST['fsa_balance'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ',food_service_staff_accounts fssa';

			$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
		}

		$extra['WHERE'] .= " AND fssa.BALANCE" . ( ! empty( $_REQUEST['fsa_bal_ge'] ) ? '>=' : '<' ) .
			"'" . round( $_REQUEST['fsa_balance'], 2 ) . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Balance' ) . ' </b>
				<span class="sizep2">' . ( ! empty( $_REQUEST['fsa_bal_ge'] ) ? '&ge;' : '&lt;' ) . '</span> ' .
				Currency( $_REQUEST['fsa_balance'] ) . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td><label for="fsa_balance">' . _( 'Balance' ) . '</label></td><td>
		<label class="sizep2">
			<input type="radio" name="fsa_bal_ge" value="" checked> &lt;</label>&nbsp;
		<label  class="sizep2">
			<input type="radio" name="fsa_bal_ge" value="Y"> &ge;</label>
		<input name="fsa_balance" id="fsa_balance" type="number" step="0.01"' .
			( $value ? ' value="' . AttrEscape( $value ) . '"' : '') . ' min="-999999999999999" max="999999999999999">
		</td></tr>';
	}
}


// Food Service Balance Warning Widget
class StaffWidget_fsa_balance_warning extends StaffWidget_fsa_balance
{
	function html( $value = '' )
	{
		$value = $GLOBALS['warning'];

		return parent::html( $value );
	}
}


// Food Service Account Status Widget
class StaffWidget_fsa_status implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_status'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ',food_service_staff_accounts fssa';

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
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Status' ) . ': </b>' .
				$_REQUEST['fsa_status'] . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td><label for="fsa_status">' . _( 'Account Status' ) . '</label></td><td>
		<select name="fsa_status" id="fsa_status">
		<option value="">' . _( 'Not Specified' ) . '</option>
		<option value="Active"' . ( $value == 'active' ? ' selected' : '' ) . '>' . _( 'Active' ) . '</option>
		<option value="Inactive">' . _( 'Inactive' ) . '</option>
		<option value="Disabled">' . _( 'Disabled' ) . '</option>
		<option value="Closed">' . _( 'Closed' ) . '</option>
		</select>
		</td></tr>';
	}
}


// Food Service Active Account Status Widget
class StaffWidget_fsa_status_active extends StaffWidget_fsa_status
{
	function html( $value = 'active' )
	{
		return parent::html( $value );
	}
}


// Food Service Barcode Widget
class StaffWidget_fsa_barcode implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_barcode'] ) )
		{
			return $extra;
		}

		if ( ! mb_strpos( $extra['FROM'], 'fssa' ) )
		{
			$extra['FROM'] .= ',food_service_staff_accounts fssa';

			$extra['WHERE'] .= ' AND fssa.STAFF_ID=s.STAFF_ID';
		}

		$extra['WHERE'] .= " AND fssa.BARCODE='" . $_REQUEST['fsa_barcode'] . "'";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Barcode' ) . ': </b>' .
				$_REQUEST['fsa_barcode'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td><label for="fsa_barcode">' . _( 'Barcode' ) .
		'</label></td><td>
		<input type="text" name="fsa_barcode" id="fsa_barcode" size="15" maxlength="50">
		</td></tr>';
	}
}


// Food Service Account Exists Widget
class StaffWidget_fsa_exists implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Food_Service'];
	}

	function extra( $extra )
	{
		if ( empty( $_REQUEST['fsa_exists'] ) )
		{
			return $extra;
		}

		$extra['WHERE'] .= ' AND ' . ( $_REQUEST['fsa_exists'] == 'N' ? 'NOT ' : '' ) . "EXISTS
			(SELECT 'exists'
				FROM food_service_staff_accounts
				WHERE STAFF_ID=s.STAFF_ID)";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Food Service Account Exists' ) . ': </b>' .
				( $_REQUEST['fsa_exists'] == 'Y' ? _( 'Yes' ) : _( 'No' ) ) . '<br />';
		}

		return $extra;
	}

	function html( $value = '' )
	{
		return '<tr class="st"><td>' . _( 'Has Account' ) . '</td><td>
		<label><input type="radio" name="fsa_exists" value=""' . ( empty( $value ) ? ' checked' : '' ) . '> ' .
			_( 'All') . '</label> &nbsp;
		<label><input type="radio" name="fsa_exists" value="Y"' . ( $value == 'Y' ? ' checked' : '' ).'> '.
			_( 'Yes' ) . '</label> &nbsp;
		<label><input type="radio" name="fsa_exists" value="N"' . ( $value == 'N' ? ' checked' : '' ) . '> '.
			_( 'No' ) . '</label>
		</td></tr>';
	}
}


// Food Service Account Exists No Widget
class StaffWidget_fsa_exists_N extends StaffWidget_fsa_exists
{
	function html( $value = 'N' )
	{
		return parent::html( $value );
	}
}


// Food Service Account Exists Yes Widget
class StaffWidget_fsa_exists_Y extends StaffWidget_fsa_exists
{
	function html( $value = 'Y' )
	{
		return parent::html( $value );
	}
}


// Staff Payroll Balance Widget
class StaffWidget_staff_balance implements StaffWidget
{
	function canBuild( $modules )
	{
		return $modules['Accounting'] && AllowUse( 'Accounting/StaffBalances.php' );
	}

	function extra( $extra )
	{
		if ( ! isset( $_REQUEST['balance_low'] )
			|| ! is_numeric( $_REQUEST['balance_low'] )
			|| ! isset( $_REQUEST['balance_high'] )
			|| ! is_numeric( $_REQUEST['balance_high'] ) )
		{
			return $extra;
		}

		if ( $_REQUEST['balance_low'] > $_REQUEST['balance_high'] )
		{
			$temp = $_REQUEST['balance_high'];

			$_REQUEST['balance_high'] = $_REQUEST['balance_low'];

			$_REQUEST['balance_low'] = $temp;
		}

		$extra['WHERE'] .= " AND (coalesce((SELECT sum(p.AMOUNT)
				FROM accounting_payments p
				WHERE p.STAFF_ID=s.STAFF_ID
				AND p.SYEAR=s.SYEAR),0)
			-coalesce((SELECT sum(f.AMOUNT)
				FROM accounting_salaries f
				WHERE f.STAFF_ID=s.STAFF_ID
				AND f.SYEAR=s.SYEAR),0))
			BETWEEN '" . $_REQUEST['balance_low'] . "'
			AND '" . $_REQUEST['balance_high'] . "' ";

		if ( ! $extra['NoSearchTerms'] )
		{
			$extra['SearchTerms'] .= '<b>' . _( 'Staff Payroll Balance' ) . ': </b>' .
				_( 'Between' ) . ' ' . $_REQUEST['balance_low'] .
				' &amp; ' . $_REQUEST['balance_high'] . '<br />';
		}

		return $extra;
	}

	function html()
	{
		return '<tr class="st"><td>' . _( 'Staff Payroll Balance' ) . '</td><td><label>' .
		_( 'Between' ) .
		' <input type="number" name="balance_low" step="0.01" min="-999999999999999" max="999999999999999"></label> <label>&amp;
		<input type="number" name="balance_high" step="0.01" min="-999999999999999" max="999999999999999"></label>
		</td></tr>';
	}
}
