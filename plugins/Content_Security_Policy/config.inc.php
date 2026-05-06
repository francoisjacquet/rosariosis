<?php
/**
 * Plugin configuration interface
 *
 * @package Content Security Policy plugin
 */

require_once 'plugins/Content_Security_Policy/includes/common.fnc.php';

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Content_Security_Policy']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

if ( empty( $_REQUEST['view'] ) )
{
	// Automatically clear CSP Reports entries older than one year
	DBQuery( "DELETE FROM csp_reports
		WHERE CREATED_AT<'" . date( 'Y-m-d', strtotime( '1 year ago' ) ) . "'" );
}

$_REQUEST['view'] = issetVal( $_REQUEST['view'], 'reports' );

// Note: no need to call ProgramTitle() here!

if ( isset( $_REQUEST['save'] )
	&& $_REQUEST['save'] === 'true' )
{
	if ( ! empty( $_POST['values'] )
		&& AllowEdit() )
	{
		if ( isset( $_REQUEST['values']['SCRIPT_SRC_DOMAINS'] ) )
		{
			$domains = explode( "\r", str_replace(
				[ "\r\n", "\n" ],
				"\r",
				$_POST['values']['SCRIPT_SRC_DOMAINS']
			) );

			$set = ContentSecurityPolicySetDomains( 'script-src', $domains );
		}

		if ( isset( $_REQUEST['values']['STYLE_SRC_DOMAINS'] ) )
		{
			$domains = explode( "\r", str_replace(
				[ "\r\n", "\n" ],
				"\r",
				$_POST['values']['STYLE_SRC_DOMAINS']
			) );

			ContentSecurityPolicySetDomains( 'style-src', $domains );
		}

		if ( isset( $_REQUEST['values']['CONNECT_SRC_DOMAINS'] ) )
		{
			$domains = explode( "\r", str_replace(
				[ "\r\n", "\n" ],
				"\r",
				$_POST['values']['CONNECT_SRC_DOMAINS']
			) );

			ContentSecurityPolicySetDomains( 'connect-src', $domains );
		}

		if ( isset( $_REQUEST['values']['FORM_ACTION_DOMAINS'] ) )
		{
			$domains = explode( "\r", str_replace(
				[ "\r\n", "\n" ],
				"\r",
				$_POST['values']['FORM_ACTION_DOMAINS']
			) );

			ContentSecurityPolicySetDomains( 'form-action', $domains );
		}

		$note[] = button( 'check' ) . '&nbsp;' . _( 'The plugin configuration has been modified.' );
	}

	// Unset save & values & redirect URL.
	RedirectURL( [ 'save', 'values' ] );
}

if ( empty( $_REQUEST['save'] ) )
{
	$form_url_add = $_REQUEST['view'] === 'reports' ? [] : [ 'save' => 'true' ];

	$form_method = $_REQUEST['view'] === 'reports' ? 'GET' : 'POST';

	echo '<form action="' . PreparePHP_SELF( [], [], $form_url_add ) .
		'" method="' . AttrEscape( $form_method ) . '">';

	$submit_button = $_REQUEST['view'] === 'domains' ? SubmitButton() : '';

	DrawHeader( 'Content Security Policy', $submit_button );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	$tabs = [
		[
			'title' => _( 'Reports' ),
			'link' => PreparePHP_SELF( [], [], [ 'view' => 'reports' ] ),
		],
		[
			'title' => _( 'Domains' ),
			'link' => PreparePHP_SELF( [], [], [ 'view' => 'domains' ] ),
		]
	];

	PopTable( 'header', $tabs, 'style="width:100%;"' );

	if ( $_REQUEST['view'] === 'reports' )
	{
		// Set start date.
		$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

		// Set end date.
		$end_date = RequestedDate( 'end', DBDate() );

		$help_link = '';

		if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			$readme_link = AddonMakeReadMe( 'plugin', 'Content_Security_Policy' );

			$help_link = button( 'help', _( 'Help' ) ) . ' - ' . $readme_link;
		}

		DrawHeader(
			_( 'Content Security Policy Violations' ),
			$help_link
		);

		DrawHeader(
			_( 'From' ) . ' ' . DateInput( $start_date, 'start', '', false, false ) .
			// CSS .rseparator responsive separator: hide text separator & break line
			'<span class="rseparator"> - </span>' .
			_( 'To' ) . ' ' . DateInput( $end_date, 'end', '', false, false ) .
			Buttons( _( 'Go' ) )
		);

		$functions = [
			'CREATED_AT' => 'ProperDateTime',
			'SCRIPT_SAMPLE' => 'ContentSecurityPolicyMakeScriptSample',
			'FULL_REPORT' => 'ContentSecurityPolicyMakeFullReport',
		];

		$reports_RET = DBGet( "SELECT ID,FULL_REPORT,VIOLATED_DIRECTIVE,BLOCKED_URI,SCRIPT_SAMPLE,CREATED_AT
			FROM csp_reports
			WHERE CREATED_AT BETWEEN '" . $start_date . "' AND '" . $end_date . ' 23:59:59' . "'
			ORDER BY CREATED_AT DESC",
			$functions );

		$columns = [
			'CREATED_AT' => _( 'Date' ),
			'VIOLATED_DIRECTIVE' => _( 'Violated Directive' ),
			'BLOCKED_URI' => _( 'Blocked URI' ),
			'SCRIPT_SAMPLE' => _( 'Script Sample' ),
			'FULL_REPORT' => _( 'Report' ),
		];

		// Fix CSS responsive List width: do NOT use the .fixed-col class, use pure CSS.
		echo '<table class="width-100p" style="table-layout: fixed;"><tr><td>';

		ListOutput(
			$reports_RET,
			$columns,
			'Report',
			'Reports'
		);

		echo '</td></tr></table>';
	}
	else
	{
		DrawHeader(
			_( 'Allow requests to external domains' )
		);

		// Fix CSS responsive List width: do NOT use the .fixed-col class, use pure CSS.
		echo '<table class="width-100p" style="table-layout: fixed;">';

		echo '<tr><td><pre><code>Content-Security-Policy-Report-Only: ' .
			Config( 'CONTENT_SECURITY_POLICY' ) . '</code></pre></td></tr>';

		$tooltip = '<div class="tooltip"><i>' . _( 'One per line' ) . '</i></div>';

		$script_src_domains = ContentSecurityPolicyGetDomains( 'script-src' );

		echo '<tr><td>' . TextAreaInput(
			implode( "\r\n", $script_src_domains ),
			'values[SCRIPT_SRC_DOMAINS]',
			'Javascript (script-src)' . $tooltip,
			'rows=5 autocomplete="off" placeholder="rosariosis.org"',
			true,
			'text'
		) . '</td></tr>';

		$style_src_domains = ContentSecurityPolicyGetDomains( 'style-src' );

		echo '<tr><td>' . TextAreaInput(
			implode( "\r\n", $style_src_domains ),
			'values[STYLE_SRC_DOMAINS]',
			'CSS (style-src)' . $tooltip,
			'rows=5 autocomplete="off" placeholder="*.rosariosis.org"',
			true,
			'text'
		) . '</td></tr>';

		$connect_src_domains = ContentSecurityPolicyGetDomains( 'connect-src' );

		echo '<tr><td>' . TextAreaInput(
			implode( "\r\n", $connect_src_domains ),
			'values[CONNECT_SRC_DOMAINS]',
			'AJAX (connect-src)' . $tooltip,
			'rows=5 autocomplete="off" placeholder="https://www.rosariosis.org"',
			true,
			'text'
		) . '</td></tr>';

		$form_action_domains = ContentSecurityPolicyGetDomains( 'form-action' );

		echo '<tr><td>' . TextAreaInput(
			implode( "\r\n", $form_action_domains ),
			'values[FORM_ACTION_DOMAINS]',
			_( 'Form' ) . ' (form-action)' . $tooltip,
			'rows=5 autocomplete="off" placeholder="https://*.rosariosis.org"',
			true,
			'text'
		) . '</td></tr>';

		echo '</table>';
	}

	PopTable( 'footer' );

	echo '<br /><div class="center">' . $submit_button . '</div></form>';
}

/**
 * Get Program Select options
 *
 * @global $_ROSARIO['Menu']
 *
 * @return array Program options.
 */
function _getProgramOptions()
{
	global $_ROSARIO;

	$program_options = [];

	if ( empty( $_ROSARIO['Menu'] ) )
	{
		require_once 'Menu.php';
	}

	foreach ( $_ROSARIO['Menu'] as $modcat_menu )
	{
		if ( ! $modcat_menu )
		{
			continue;
		}

		$mod_options = [];

		foreach ( $modcat_menu as $modname => $modtitle )
		{
			if ( is_numeric( $modname )
				|| in_array( $modname, [ 'reports', 'title' ] ) )
			{
				continue;
			}

			$mod_options[ $modname ] = $modtitle;

			if ( _programHasPDFHeaderFooter( $modname ) )
			{
				$mod_options[ $modname ] = '*' . $modtitle;
			}
		}

		$program_options[ $modcat_menu['title'] ] = $mod_options;
	}

	return $program_options;
}

/**
 * Program has custom PDF Header & Footer
 *
 * @param  string $modname Program file.
 *
 * @return bool True if program has custom PDF Header & Footer
 */
function _programHasPDFHeaderFooter( $modname )
{
	$program = 'pdf_header_footer_' . $modname;

	return (bool) ProgramConfig( $program, 'CONTENT_SECURITY_POLICY_HEADER' );
}
