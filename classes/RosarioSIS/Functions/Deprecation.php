<?php
/**
 * Deprecation notices helper.
 *
 * Triggers an E_USER_DEPRECATED notice, once per message and per caller
 * during a request. Nothing is computed if E_USER_DEPRECATED is not reported.
 *
 * Example 1: simple message
 * ```php
 * // Inside the deprecated oldFunction(), called from Modules/School_Setup/Calendar.php line 42:
 * Deprecation::trigger( 'RosarioSIS', '13.1', 'oldFunction() is deprecated, use newFunction() instead.' );
 * ```
 * Result:
 * ```
 * Deprecated: Since RosarioSIS 13.1: oldFunction() is deprecated, use newFunction() instead. Called in /var/www/rosariosis/Modules/School_Setup/Calendar.php on line 42.
 * ```
 *
 * Example 2: message with sprintf() arguments
 * ```php
 * Deprecation::trigger(
 *     'RosarioSIS',
 *     '13.1',
 *     'The "%s" argument of %s() is deprecated, use "%s" instead.',
 *     'old_arg',
 *     'MyFunction',
 *     'new_arg'
 * );
 * ```
 * Result:
 * ```
 * Deprecated: Since RosarioSIS 13.1: The "old_arg" argument of MyFunction() is deprecated, use "new_arg" instead. Called in /var/www/rosariosis/Modules/Grades/Grades.php on line 120.
 * ```
 *
 * Example 3: call inside a loop
 * ```php
 * foreach ( $students as $student )
 * {
 *     // Only one notice is triggered for this caller, not one per iteration.
 *     Deprecation::trigger( 'RosarioSIS', '13.1', 'oldFunction() is deprecated.' );
 * }
 * ```
 *
 * Example 4: no package nor version
 * ```php
 * Deprecation::trigger( '', '', 'oldFunction() is deprecated.' );
 * ```
 * Result (no "Since ..." prefix):
 * ```
 * Deprecated: oldFunction() is deprecated. Called in /var/www/rosariosis/Modules/Grades/Grades.php on line 130.
 * ```
 *
 * @since 13.1
 *
 * @package RosarioSIS
 */

namespace RosarioSIS\Functions;

class Deprecation
{
	/** @var array Caller already reported during this request. */
	private static $notified = [];

	/**
	 * @param string $package 'RosarioSIS'
	 * @param string $version Version since which this is deprecated
	 * @param string $message Message, optional sprintf() format
	 * @param mixed ...$args vsprintf() arguments (read via func_get_args(), PHP 5.5)
	 *
	 * @return void
	 */
	public static function trigger( $package, $version, $message )
	{
		$args = array_slice( func_get_args(), 3 );

		$file = 'unknown';
		$line = 0;

		// Only compute backtrace in debug mode for caller info.
		if ( defined( 'ROSARIO_DEBUG' ) && ROSARIO_DEBUG ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			$caller = isset( $trace[1] ) ? $trace[1] : [];
			$file = isset( $caller['file'] ) ? $caller['file'] : 'unknown';
			$line = isset( $caller['line'] ) ? $caller['line'] : 0;
		}

		// Once per message AND per caller, per HTTP request/process:
		// a call inside a loop only logs once, and each caller that needs
		// fixing appears in the logs.
		$key = $message . '@' . $file . ':' . $line;

		if ( isset( self::$notified[ $key ] ) ) {
			return;
		}

		self::$notified[ $key ] = true;

		$deprecation_message = ( $package || $version ? "Since $package $version: " : '' ) .
			( $args ? vsprintf( $message, $args ) : $message );

		// Always show deprecation message, but only show caller info in debug mode.
		if ( defined( 'ROSARIO_DEBUG' ) && ROSARIO_DEBUG ) {
			$deprecation_message .= " Called in $file on line $line.";
		}

		trigger_error(
			$deprecation_message,
			E_USER_DEPRECATED
		);
	}
}
