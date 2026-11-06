<?php
/**
 * Log Hacking attempt function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Log Hacking attempt
 * Send email if `$RosarioNotifyAddress` or `$RosarioErrorsAddress` set
 *
 * @global string $RosarioNotifyAddress or $RosarioErrorsAddress email set in config.inc.php file
 * @since 4.0 Uses ErrorSendEmail() & "« Back" link to Portal or automatic redirection if has just logged in.
 * @since 4.1 Redirect automatically to Portal after 5 seconds.
 * @since 4.3 Reload menu now so it does not contain links to disallowed programs.
 * @since 6.4.1 Only send email and redirect to Portal without displaying error.
 * @since 9.0 Logout after 10 Hacking attempts within 1 minute.
 * @since 10.0 Log "RosarioSIS HACKING ATTEMPT" into Apache error.log
 * @since 10.0 Force URL & menu reloading, always use JS to redirect
 * @since 12.5 Redirection is done in HTML
 * @deprecated since 13.0 Use (new RosarioSIS\Functions\Hacking)->log() instead
 */
function HackingLog()
{
	RosarioSIS\Functions\Deprecation::trigger(
		'RosarioSIS',
		'13.0',
		'HackingLog() function is deprecated, use (new RosarioSIS\Functions\Hacking)->log() instead.'
	);

	(new RosarioSIS\Functions\Hacking)->log();
}
