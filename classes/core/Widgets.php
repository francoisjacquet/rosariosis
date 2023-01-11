<?php
/**
 * (Student) Widgets class
 *
 * @since 8.6
 *
 * @see Widget.php for individual Widgets
 *
 * @package RosarioSIS
 * @subpackage classes/core
 */

namespace RosarioSIS;

class Widgets
{
	/**
	 * Whether a Widget is already built
	 *
	 * @see isBuilt() method.
	 *
	 * @var array Built Widgets
	 */
	protected $built = [];

	/**
	 * Global Widgets HTML
	 *
	 * @var array Widgets HTML + eventually $extra['search'].
	 */
	protected $html = [];

	/**
	 * Widgets extra, sent to GetStuList() or GetStaffList()
	 *
	 * @since 10.4 Add-ons can add their custom Widgets
	 *
	 * - functions to apply to SQL RET
	 * - search: will end up being imploded $this->html
	 * - NoSearchTerms: set to true to diable SearchTerms
	 * - SearchTerms: HTML displayed on results, ends up in global $_ROSARIO['SearchTerms']
	 * - SELECT: to restrict SQL query
	 * - FROM: to restrict SQL query
	 * - WHERE: to restrict SQL query
	 * - Widgets: to add custom Widgets.
	 *   $extra['Widgets']['Addon_Name'] = [ 'widget_1', 'widget_2' ];
	 *   Custom '\Addon_Name\Widget_' class prefix.
	 *   For Staff Widgets, class prefix is '\Addon_Name\StaffWidget_'
	 *
	 * @var array $extra for GetStuList() or GetStaffList()
	 */
	protected $empty_extra = [
		'functions' => [],
		'search' => '',
		'NoSearchTerms' => '',
		'SearchTerms' => '',
		'SELECT' => '',
		'FROM' => '',
		'WHERE' => '',
		'Widgets' => [],
	];

	protected $extra;

	/**
	 * Set $extra
	 * Reset $html and eventually add $extra['search']
	 *
	 * @param array $extra Widgets extra
	 */
	function setExtra( $extra )
	{
		$this->extra = array_replace_recursive( $this->empty_extra, (array) $extra );

		$this->html = [];

		if ( ! empty( $extra['search'] ) )
		{
			$this->html[] = $extra['search'];
		}
	}

	/**
	 * Get $extra
	 * Remove $extra['SearchTerms']
	 * Set $extra['search'] using $this->html
	 *
	 * @return array $extra Widgets extra
	 */
	function getExtra()
	{
		$extra = $this->extra;

		unset( $extra['SearchTerms'] );

		$extra['search'] = implode( $this->html );

		return $extra;
	}

	/**
	 * Get SearchTerms from $extra
	 *
	 * @return string SearchTerms: HTML displayed on results, ends up in global $_ROSARIO['SearchTerms']
	 */
	function getSearchTerms()
	{
		return $this->extra['SearchTerms'];
	}

	/**
	 * Build Widget
	 * Calls the all() method or the \RosarioSIS\Widget_[name] class.
	 *
	 * @param  string $name         Widget name or 'all'.
	 * @param  string $class_prefix Widget class prefix with namespace (optional).
	 *
	 * @return bool True if is already built, if 'all', or if can build.
	 */
	function build( $name, $class_prefix = '\RosarioSIS\Widget_' )
	{
		global $RosarioModules;

		if ( $this->isBuilt( $name ) )
		{
			return true;
		}

		$this->built[] = $name;

		if ( $name === 'all' )
		{
			$this->all();

			return true;
		}

		$class_name = $class_prefix . $name;

		if ( ! class_exists( $class_name ) )
		{
			return false;
		}

		$widget = new $class_name;

		$can_build = $widget->canBuild( $RosarioModules );

		if ( $can_build )
		{
			$this->extra = $widget->extra( $this->extra );

			if ( $this->isSearch()
				|| $name === 'mailing_labels' )
			{
				// Do NOT generate search HTML if not on the Find a Student / User screen (Mailing Labels is exception).
				$this->html[] = $widget->html();
			}
		}

		return $can_build;
	}

	/**
	 * Is Search?
	 * Are we on the Find a Student / User form?
	 * Or are we on the Student / User list ($_REQUEST['search_modfunc'] === 'list')?
	 *
	 * @return boolean True if is Search.
	 */
	function isSearch()
	{
		return empty( $_REQUEST['search_modfunc'] );
	}

	/**
	 * Is Widget already built (or at least we tried to)
	 *
	 * @param  string $name Widget name.
	 *
	 * @return boolean      True if Widget already built (or at least we tried to).
	 */
	function isBuilt( $name )
	{
		if ( in_array( $name, $this->built ) )
		{
			return true;
		}

		// Fix for child Widgets.
		// For example, 'fsa_balance': return true if 'fsa_balance_warning' already built.
		foreach ( $this->built as $built )
		{
			if ( strpos( $built, $name ) === 0 )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Wrap header (switch menu + table HTML)
	 * For groups of Widgets, to display on Advanced Search screen.
	 *
	 * @uses $this->html
	 *
	 * @param  string $title Widgets group title.
	 */
	function wrapHeader( $title )
	{
		$this->html[] = '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu">
			<b>' . $title . '</b></a>
			<br>
			<table class="widefat width-100p col1-align-right hide">';
	}

	/**
	 * Wrap footer (table HTML)
	 * For groups of Widgets, to display on Advanced Search screen.
	 *
	 * @uses $this->html
	 */
	function wrapFooter()
	{
		$this->html[] = '</table>';
	}

	/**
	 * All Widgets (or almost)
	 * If not already built
	 *
	 * @global $RosarioModules to check if module is enabled
	 */
	function all()
	{
		global $RosarioModules;

		// Enrollment.
		if ( $RosarioModules['Students']
			&& ( ! $this->isBuilt( 'calendar' )
				|| ! $this->isBuilt( 'next_year' )
				|| ! $this->isBuilt( 'enrolled' )
				|| ! $this->isBuilt( 'rolled' ) ) )
		{
			$this->wrapHeader( _( 'Enrollment' ) );

			$this->build( 'calendar' );
			$this->build( 'next_year' );
			$this->build( 'enrolled' );
			$this->build( 'rolled' );

			$this->wrapFooter();
		}

		// Scheduling.
		if ( $RosarioModules['Scheduling']
			&& ! $this->isBuilt( 'course' )
			&& User( 'PROFILE' ) === 'admin' )
		{
			$this->wrapHeader( _( 'Scheduling' ) );

			$this->build( 'course' );

			$this->wrapFooter();
		}

		// Attendance.
		if ( $RosarioModules['Attendance']
			&& ( ! $this->isBuilt( 'absences' )
				|| ! $this->isBuilt( 'cp_absences' ) ) )
		{
			$this->wrapHeader( _( 'Attendance' ) );

			$this->build( 'absences' );
			$this->build( 'cp_absences' );

			$this->wrapFooter();
		}

		// Grades.
		if ( $RosarioModules['Grades']
			&& ( ! $this->isBuilt( 'gpa' )
				|| ! $this->isBuilt( 'class_rank' )
				|| ! $this->isBuilt( 'letter_grade' ) ) )
		{
			$this->wrapHeader( _( 'Grades' ) );

			$this->build( 'gpa' );
			$this->build( 'class_rank' );
			$this->build( 'letter_grade' );

			$this->wrapFooter();
		}

		// Eligibility.
		if ( $RosarioModules['Eligibility']
			&& ( ! $this->isBuilt( 'eligibility' )
				|| ! $this->isBuilt( 'activity' ) ) )
		{
			$this->wrapHeader( _( 'Eligibility' ) );

			$this->build( 'eligibility' );
			$this->build( 'activity' );

			$this->wrapFooter();
		}

		// Food Service.
		if ( $RosarioModules['Food_Service']
			&& ( ! $this->isBuilt( 'fsa_balance' )
				|| ! $this->isBuilt( 'fsa_discount' )
				|| ! $this->isBuilt( 'fsa_status' )
				|| ! $this->isBuilt( 'fsa_barcode' ) ) )
		{
			$this->wrapHeader( _( 'Food Service' ) );

			$this->build( 'fsa_balance' );
			$this->build( 'fsa_discount' );
			$this->build( 'fsa_status' );
			$this->build( 'fsa_barcode' );

			$this->wrapFooter();
		}

		// Discipline.
		if ( $RosarioModules['Discipline']
			&& ( ! $this->isBuilt( 'reporter' )
				|| ! $this->isBuilt( 'incident_date' )
				|| ! $this->isBuilt( 'discipline_fields' ) ) )
		{
			$this->wrapHeader( _( 'Discipline' ) );

			$this->build( 'reporter' );
			$this->build( 'incident_date' );
			$this->build( 'discipline_fields' );

			$this->wrapFooter();
		}

		// Student Billing.
		if ( $RosarioModules['Student_Billing']
			&& ( ! $this->isBuilt( 'balance' ) )
			&& AllowUse( 'Student_Billing/StudentFees.php' ) )
		{
			$this->wrapHeader( _( 'Student Billing' ) );

			$this->build( 'balance' );

			$this->wrapFooter();
		}

		if ( AllowUse( 'Students/Student.php&category_id=2' )
			&& $_REQUEST['search_modfunc'] === 'list' )
		{
			// @since 5.1 Medical Immunization or Physical Widget displayed under Student Fields.
			// Call here necessary for header.
			$this->build( 'medical_date' );
		}

		// @since 10.4 Add-ons can add their custom Widgets
		$this->custom( $this->extra['Widgets'] );
	}

	/**
	 * Custom Widgets
	 *
	 * @since 10.4 Add-ons can add their custom Widgets
	 *
	 * @param  array  $extra_widgets $this->extra['Widgets'];
	 * @param  string $class_prefix  Class prefix without namespace. Defaults to 'Widget_'.
	 */
	function custom( $extra_widgets, $class_prefix = 'Widget_' )
	{
		foreach ( $extra_widgets as $add_on => $widgets )
		{
			if ( empty( $widgets ) )
			{
				continue;
			}

			/**
			 * If Widget was added by add-on, then:
			 * 1. add-on is activated
			 * 2. Add-on must check first if user has rights to access all Widgets
			 */
			$this->wrapHeader( dgettext( $add_on, str_replace( '_', ' ', $add_on ) ) );

			foreach ( $widgets as $widget )
			{
				/**
				 * Custom '\Addon_Name\Widget_' class prefix.
				 *
				 * @example namespace Hostel_Premium; class Widget_hostel_room implements \RosarioSIS\Widget {...}
				 */
				$this->build( $widget, '\\' . $add_on . '\\' . $class_prefix );
			}

			$this->wrapFooter();
		}
	}
}
