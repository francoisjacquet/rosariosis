<?php
/**
 * Save CSP violation report
 *
 * @package Content Security Policy plugin
 */

chdir( '../..' );

require_once 'Warehouse.php';

if ( empty( $_SERVER['CONTENT_TYPE'] )
	|| $_SERVER['CONTENT_TYPE'] !== 'application/csp-report' )
{
	return _errorDie( 'Not a CSP report' );
}

$json = json_decode( file_get_contents('php://input'), true );

if ( empty( $json['csp-report'] ) )
{
	return _errorDie( 'No CSP report found' );
}

$csp_report = $json['csp-report'];

// Security fix #397 Unauthenticated stored XSS: remove HTML tags
array_rwalk( $csp_report, 'strip_tags' );

// Do not save violations triggered by browser extensions.
$source_file_skip = [
	'moz-extension',
	'chrome-extension',
	'safari-extension',
	'user-script',
	// @link https://bugzilla.mozilla.org/show_bug.cgi?id=1707107
	'sandbox eval code',
];

if ( isset( $csp_report['source-file'] )
	&& ( in_array( $csp_report['source-file'], $source_file_skip )
		|| mb_strpos( $csp_report['source-file'], 'safari-web-extension' ) === 0
		|| mb_strpos( $csp_report['source-file'], 'safari-extension' ) === 0 ) )
{
	return _skipDie( 'Skip CSP violation triggered by browser extension' );
}

// Do not save violations triggered by the following domains.
$domain_skip = [
	'https://www.google-analytics.com', // AJAX request to Google Analytics
	'https://www.gstatic.com', // CSS file injected by Google Translate
	'kis.v2.scr.kaspersky-labs.com', // JS script injected by Kaspersky antivirus
	'https://connect.facebook.net', // JS file injected by Facebook
	'https://safesearchinc.com', // ??
	'https://infird.com', // JS file injected by malware
	'googleapis.com', // CSS file injected by Google Fonts / Page translation
	'https://overbridgenet.com', // JS malware
	'.goguardian', // GoGuardian
	'https://tl.ytlogs.ru', // Russian YouTube logs?
	'https://mainf.global-cache.online', // JS file injected by ??
	'https://wallet.binance.com', // Binance
	'https://filtering.adblock360.com', // AdBlock360
	'https://static.contextall.com', // VisualSPHost
];

foreach ( $domain_skip as $domain )
{
	if ( stripos( $csp_report['blocked-uri'], $domain ) !== false )
	{
		return _skipDie( 'Skip CSP violation triggered by domain: ' . $domain );
	}
}

// Do not save the following violations
/**
 * Really common violation but was not able to trace it...
 *
 * "violated-directive": "script-src-elem",
 * "blocked-uri": "inline",
 * "script-sample": "function yf9behvg8uwqfnuk() { if (!0 ==="
 */
if ( $csp_report['violated-directive'] === 'script-src-elem'
	&& $csp_report['blocked-uri'] === 'inline'
	&& ! empty( $csp_report['script-sample'] )
	&& $csp_report['script-sample'] === 'function yf9behvg8uwqfnuk() { if (!0 ===' )
{
	return _skipDie( 'Skip CSP violation triggered by script sample: "' . $csp_report['script-sample'] . '"' );
}

/**
 * Violation triggered just before AJAX request to Google Analytics (see above)
 *
 * "violated-directive": "script-src-elem",
 * "blocked-uri": "blob",
 * "line-number": 1,
 * "column-number": 267 (or 155)
 */
if ( $csp_report['violated-directive'] === 'script-src-elem'
	&& $csp_report['blocked-uri'] === 'blob'
	&& $csp_report['line-number'] === 1
	&& ( $csp_report['column-number'] === 267 || $csp_report['column-number'] === 155 ) )
{
	return _skipDie( 'Skip CSP violation triggered by blob script: line 1, column 267 or 155' );
}

/**
 * Really common violation but was not able to trace it...
 *
 * "violated-directive": "script-src-elem",
 * "blocked-uri": "inline",
 * "source-file": "sandbox eval code",
 * "script-sample": "(function setupDetection() {\n    const d…"
 */
if ( $csp_report['violated-directive'] === 'script-src-elem'
	&& $csp_report['blocked-uri'] === 'inline'
	&& ! empty( $csp_report['source-file'] )
	&& $csp_report['source-file'] === 'sandbox eval code'
	&& ! empty( $csp_report['script-sample'] )
	&& mb_strpos( $csp_report['script-sample'], '(function setupDetection() {' ) === 0 )
{
	return _skipDie( 'Skip CSP violation triggered by sandbox eval code: "' . $csp_report['script-sample'] . '"' );
}

/**
 * Violation triggered by Facebook Click Identifier
 *
 * "violated-directive": "script-src-elem",
 * "blocked-uri": "inline",
 * "referrer": "https://rosariosis.com/Modules.php?modname=Students%2FStudent.php&fbclid=[...]"
 */
if ( $csp_report['violated-directive'] === 'script-src-elem'
	&& $csp_report['blocked-uri'] === 'inline'
	&& ! empty( $csp_report['referrer'] )
	&& mb_strpos( $csp_report['referrer'], '&fbclid=' ) !== false )
{
	return _skipDie( 'Skip CSP violation triggered by Facebook Click Identifier' );
}

/**
 * Common violation but was not able to trace it...
 *
 * "violated-directive": "script-src-elem",
 * "blocked-uri": "inline",
 * "script-sample": "(function() {'use strict';function shuff"
 */
if ( $csp_report['violated-directive'] === 'script-src-elem'
	&& $csp_report['blocked-uri'] === 'inline'
	&& ! empty( $csp_report['script-sample'] )
	&& mb_strpos( $csp_report['script-sample'], "(function() {'use strict';function shuff" ) === 0 )
{
	return _skipDie( 'Skip CSP violation triggered by inline code: "' . $csp_report['script-sample'] . '"' );
}

$insert_columns = [
	'FULL_REPORT' => DBEscapeString( json_encode( $csp_report ) ),
	'VIOLATED_DIRECTIVE' => DBEscapeString( str_replace( '-elem', '', $csp_report['violated-directive'] ) ),
	'BLOCKED_URI' => DBEscapeString( $csp_report['blocked-uri'] ),
	'SCRIPT_SAMPLE' => DBEscapeString( trim( issetVal( $csp_report['script-sample'], '' ) ) ),
];

// Only save unique CSP report once a day.
$csp_reported_today = DBGetOne( "SELECT 1
	FROM csp_reports
	WHERE CREATED_AT>='" . DBDate() . " 00:00:00'
	AND FULL_REPORT='" . DBEscapeString( json_encode( $csp_report ) ) . "'" );

if ( ! $csp_reported_today )
{
	DBInsert( 'csp_reports', $insert_columns );
}

/**
 * JSON Error and Die.
 *
 * Local function
 *
 * @param string $error Error message.
 */
function _errorDie( $error )
{
	http_response_code( 500 );

	header( 'Content-type: application/json; charset=utf-8' );

	echo json_encode( [ 'error' => $error ] );

	error_log( 'Content Security Policy plugin error: ' . $error );

	exit;
}

/**
 * JSON Message and Die.
 *
 * Local function
 *
 * @param string $msg Message.
 */
function _skipDie( $msg )
{
	// 422 Unprocessable Content
    // The request was well-formed (i.e., syntactically correct) but could not be processed.
	http_response_code( 422 );

	header( 'Content-type: application/json; charset=utf-8' );

	echo json_encode( [ 'message' => $msg ] );

	exit;
}
