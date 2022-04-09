<?php
/**
 * Implementation for PHP functions either missing from older PHP versions or not included by default.
 * Shim, polyfill, emulation...
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Include PHP strftime function replacement.
 * It is deprecated since PHP 8.1
 *
 * @since 9.0 Fix PHP8.1 deprecated strftime() use strftime_compat() instead
 */
require_once 'functions/PHPCompatibility/strftime_compat.php';

if ( ! function_exists( 'gettext' ) )
{
	/**
	 * Include PHP gettext extension emulation by PhpMyAdmin.
	 *
	 * @since 3.8
	 */
	require_once 'functions/PHPCompatibility/gettext.php';
}

// Deactivate PHP iconv extension emulation
// Causing bugs (see Student Info enrolment start date field) & lots of PHP notices.
/*if ( ! function_exists( 'iconv' ) )
{
	/**
	 * Include PHP iconv extension emulation by Symfony.
	 *
	 * @since 3.8
	 */
	/*require_once 'functions/PHPCompatibility/iconv.php';
}*/


if ( ! function_exists( 'mb_substr' ) )
{
	/**
	 * Include PHP mbstring extension emulation by Symfony.
	 *
	 * @since 3.8
	 */
	require_once 'functions/PHPCompatibility/mbstring.php';
}


if ( ! function_exists( 'json_encode' ) )
{
	/**
	 * Include PHP json extension emulation by WordPress.
	 *
	 * @since 3.8
	 */
	require_once 'functions/PHPCompatibility/json.php';
}


if ( ! function_exists( 'utf8_encode' ) )
{
	/**
	 * Include PHP xml extension emulation by Symfony.
	 *
	 * @since 3.8
	 */
	require_once 'functions/PHPCompatibility/xml.php';
}
