<?php
/**
 * Staff Widgets class
 *
 * @since 8.6
 *
 * @see StaffWidget.php for individual Widgets
 * @see Widgets.php for base class
 *
 * @package RosarioSIS
 * @subpackage classes/core
 */

namespace RosarioSIS;

// StaffWidgets class, reuse Widgets class.
class StaffWidgets extends Widgets
{
	/**
	 * Build Staff Widget
	 * Calls the all() method or the \RosarioSIS\StaffWidget_[name] class.
	 *
	 * @param  string $name         Staff Widget name or 'all'.
	 * @param  string $class_prefix Staff Widget class prefix with namespace (optional).
	 *
	 * @return bool         True if is already built, if 'all', or if can build.
	 */
	function build( $name, $class_prefix = '\RosarioSIS\StaffWidget_' )
	{
		return parent::build( $name, $class_prefix );
	}

	/**
	 * All Staff Widgets (or almost)
	 * If not already built
	 *
	 * @global $RosarioModules to check if module is enabled
	 */
	function all()
	{
		global $RosarioModules;

		// Users.
		if ( $RosarioModules['Users']
			&& ! $this->isBuilt( 'permissions' ) )
		{
			$this->wrapHeader( _( 'Users' ) );

			$this->build( 'permissions' );

			$this->wrapFooter();
		}

		// Food Service.
		if ( $RosarioModules['Food_Service']
			&& ( ! $this->isBuilt( 'fsa_balance' )
				|| ! $this->isBuilt( 'fsa_status' )
				|| ! $this->isBuilt( 'fsa_barcode' ) ) )
		{
			$this->wrapHeader( _( 'Food Service' ) );

			$this->build( 'fsa_balance' );
			$this->build( 'fsa_status' );
			$this->build( 'fsa_barcode' );
			$this->build( 'fsa_exists' );

			$this->wrapFooter();
		}

		// Accounting.
		if ( $RosarioModules['Accounting']
			&& ! $this->isBuilt( 'staff_balance' )
			&& AllowUse( 'Accounting/StaffBalances.php' ) )
		{
			$this->wrapHeader( _( 'Accounting' ) );

			$this->build( 'staff_balance' );

			$this->wrapFooter();
		}

		// @since 10.4 Add-ons can add their custom Widgets
		$this->custom( $this->extra['Widgets'], 'StaffWidget_' );
	}
}
