<?php

// We use SHA512 algorithm
if ( !defined( 'CRYPT_SHA512' ) )
	define( 'CRYPT_SHA512', 1 );

/**
 * Encrypt Password
 *
 * @see http://php.net/crypt
 *
 * @uses SHA512 algorithm (PHP 5.3.2+ required)
 *
 * @param  string $plain Plain text password
 *
 * @return string Encrypted password
 */
function encrypt_password( $plain )
{
	$rand = rand( 999999999, 9999999999 );

	$salt = '$6$' . mb_substr( sha1( $rand ), 0, 16	);

	return crypt( $plain, $salt );
}


/**
 * Match Password
 *
 * @see http://php.net/hash-equals
 *
 * @param  string  $crypted Crypted password
 * @param  string  $plain   Plain text password
 *
 * @return boolean true if password match, else false
 */
function match_password( $crypted, $plain )
{
	//$salt = mb_substr($password, 0, 19);

	return hash_equals( $crypted, crypt( $plain, $crypted ) );
}
