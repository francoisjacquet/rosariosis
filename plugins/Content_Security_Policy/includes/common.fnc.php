<?php
/**
 * Common functions
 *
 * @package Content Security Policy plugin
 */

/**
 * Get CSP domains for directive
 *
 * @example ContentSecurityPolicyGetDomains( 'style-src' );
 *
 * @param string $directive CSP directive: 'script-src', 'style-src', 'connect-src' or 'form-action'.
 *
 * @return array Domains.
 */
function ContentSecurityPolicyGetDomains( $directive )
{
	$allowed_directives = [
		'script-src',
		'style-src',
		'connect-src',
		'form-action',
	];

	$csp = Config( 'CONTENT_SECURITY_POLICY' );

	$csp_directives = explode( '; ', $csp );

	if ( ! $csp_directives
		|| ! in_array( $directive, $allowed_directives ) )
	{
		return [];
	}

	foreach ( $csp_directives as $csp_directive )
	{
		if ( mb_strpos( $csp_directive, $directive ) === 0 )
		{
			$directive_key = array_search( $directive, $allowed_directives );

			break;
		}
	}

	if ( ! isset( $directive_key ) )
	{
		return [];
	}

	$base_directives = [
		"script-src 'self' 'unsafe-eval' 'report-sample'",
		"style-src 'self' 'unsafe-inline'",
		"connect-src 'self'",
		"form-action 'self'",
	];

	$domains = trim( str_replace(
		$base_directives[ $directive_key ],
		'',
		$csp_directives[ $directive_key ]
	) );

	if ( ! $domains )
	{
		return [];
	}

	return explode( ' ', $domains );
}

/**
 * Set CSP domains for directive
 *
 * @example ContentSecurityPolicySetDomains( 'style-src', [ 'fonts.google.com' ] );
 *
 * @param string $directive CSP directive: 'script-src', 'style-src', 'connect-src' or 'form-action'.
 * @param array  $domains   Domains to allow.
 *
 * @return bool True if domains set.
 */
function ContentSecurityPolicySetDomains( $directive, $domains )
{
	$allowed_directives = [
		'script-src',
		'style-src',
		'connect-src',
		'form-action',
	];

	$csp = Config( 'CONTENT_SECURITY_POLICY' );

	$csp_directives = explode( '; ', $csp );

	if ( ! $csp_directives
		|| ! in_array( $directive, $allowed_directives ) )
	{
		return false;
	}

	foreach ( $csp_directives as $csp_directive )
	{
		if ( mb_strpos( $csp_directive, $directive ) === 0 )
		{
			$directive_key = array_search( $directive, $allowed_directives );

			break;
		}
	}

	if ( ! isset( $directive_key ) )
	{
		return false;
	}

	$new_domains = [];

	foreach ( (array) $domains as $domain )
	{
		if ( ContentSecurityPolicyIsValidDomain( $domain ) )
		{
			$new_domains[] = $domain;
		}
	}

	$base_directives = [
		"script-src 'self' 'unsafe-eval' 'report-sample'",
		"style-src 'self' 'unsafe-inline'",
		"connect-src 'self'",
		"form-action 'self'",
	];

	$new_csp_directive = $base_directives[ $directive_key ];

	if ( $new_domains )
	{
		$new_csp_directive .= ' ' . implode( ' ', $new_domains );
	}

	$csp_directives[ $directive_key ] = $new_csp_directive;

	$new_csp = implode( '; ', $csp_directives );

	Config( 'CONTENT_SECURITY_POLICY', DBEscapeString( $new_csp ) );

	return true;
}

/**
 * Is Valid Domain Name?
 *
 * Note: added wildcard (*) to first regex
 * @link https://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php
 *
 * @param string $domain Domain name (may include scheme and wildcard).
 *
 * @return bool True if valid domain name.
 */
function ContentSecurityPolicyIsValidDomain( $domain )
{
	if ( version_compare( PHP_VERSION, '7.0', '>=' ) )
	{
		return (bool) filter_var( $domain, FILTER_VALIDATE_DOMAIN );
	}

	$domain = str_ireplace( [ 'http://', 'https://' ], '', $domain );

    return ( preg_match( "/^([a-z\d\*](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain ) // valid chars check
            && preg_match( "/^.{1,253}$/", $domain ) // overall length check
            && preg_match( "/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain ) ); // length of each label
}

/**
 * Send CSP violations report
 *
 * @param string $cron_day CRON day.
 *
 * @return bool True if email sent.
 */
function ContentSecurityPolicySendReport( $cron_day )
{
	global $RosarioErrorsAddress;

	if ( ! filter_var( $RosarioErrorsAddress, FILTER_VALIDATE_EMAIL ) )
	{
		return false;
	}

	$day_begin = $cron_day . ' 00:00:00';
	$day_end = $cron_day . ' 23:59:59';

	$new_reports = DBGet( "SELECT FULL_REPORT,CREATED_AT
		FROM csp_reports
		WHERE CREATED_AT BETWEEN '" . DBEscapeString( $day_begin ) . "'
			AND '" . DBEscapeString( $day_end ) . "'
		ORDER BY CREATED_AT
		LIMIT 100" );

	if ( ! $new_reports )
	{
		return false;
	}

	require_once 'ProgramFunctions/SendEmail.fnc.php';

	$subject = _( 'Content Security Policy Violations' );

	$message = 'System: ' . ParseMLField( Config( 'TITLE' ) ) . "\n";
	$message .= 'Warning: inside the full report, `document-uri` may be inaccurate.' . "\n";
	$message .= 'Help: https://gitlab.com/francoisjacquet/rosariosis/-/blob/mobile/plugins/Content_Security_Policy/README.md' . "\n";

	foreach ( (array) $new_reports as $report )
	{
		$json_pretty = json_encode(
			json_decode( $report['FULL_REPORT'], true ),
			JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
		);

		$message .= "\n\n" . 'Date: ' . $report['CREATED_AT'] . "\n";
		$message .= 'Full Report: ' . "\n" . $json_pretty;
	}

	return SendEmail( $RosarioErrorsAddress, $subject, $message );
}

/**
 * Make Script Sample (from CSP violation report)
 *
 * DBGet() callback
 *
 * @param string $value  Script sample.
 * @param string $column Column name.
 *
 * @return string Script sample inside `<code>`.
 */
function ContentSecurityPolicyMakeScriptSample( $value, $column = 'SCRIPT_SAMPLE' )
{
	if ( ! $value )
	{
		return $value;
	}

	return '<code>' . $value . '</code>';
}

/**
 * Make Full Report (from CSP violation report)
 *
 * DBGet() callback
 *
 * @param string $value  Full Report.
 * @param string $column Column name.
 *
 * @return string Full Report (pretty JSON) inside ColorBox.
 */
function ContentSecurityPolicyMakeFullReport( $value, $column = 'FULL_REPORT' )
{
	global $THIS_RET;

	if ( ! $value
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	$json_pretty = '<pre><code>' . json_encode(
		json_decode( $value, true ),
		JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
	) . '</code></pre>';

	$return = '<div style="display:none;"><div class="csp-full-report" id="csp_full_report' . $THIS_RET['ID'] . '">' .
		$json_pretty . '</div></div>';

	$return .= '<a class="colorboxinline" href="#csp_full_report' . $THIS_RET['ID'] . '">' .
		button( 'visualize', '', '', 'bigger' ) . '</a>';

	return $return;
}
