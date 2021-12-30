<?php
/**
 * Password functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

// We use SHA512 algorithm.
if ( ! defined( 'CRYPT_SHA512' ) )
{
	define( 'CRYPT_SHA512', 1 );
}

/**
 * Encrypt Password
 *
 * @see http://php.net/crypt
 *
 * @uses SHA512 algorithm (PHP 5.3.2+ required)
 *
 * @param  string $plain Plain text password.
 *
 * @return string Encrypted password
 */
function encrypt_password( $plain )
{
	if ( ! $plain )
	{
		return '';
	}

	$rand = rand( 999999999, 9999999999 );

	$salt = '$6$' . mb_substr( sha1( $rand ), 0, 16	);

	return crypt( (string) $plain, $salt );
}


/**
 * Match Password
 *
 * @see http://php.net/hash-equals
 *
 * @param  string $crypted Crypted password.
 * @param  string $plain   Plain text password.
 *
 * @return boolean true if password match, else false
 */
function match_password( $crypted, $plain )
{
	if ( ! $plain
		|| ! $crypted )
	{
		return false;
	}

	/**
	 * Match password action hook.
	 *
	 * @since 5.4
	 *
	 * Used to provide external authentication method.
	 * @see LDAP plugin for example.
	 */
	do_action( 'functions/Password.php|match_password', [ &$crypted, $plain ] );

	return function_exists( 'hash_equals' ) ? // PHP < 5.6 compat.
		hash_equals(
			(string) $crypted,
			crypt( (string) $plain, (string) $crypted )
		) :
		$crypted == crypt( (string) $plain, (string) $crypted );
}
