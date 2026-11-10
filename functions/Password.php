<?php
/**
 * Password functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Encrypt Password
 *
 * @see http://php.net/password_hash
 *
 * @since 13.1 Security use bcrypt algorithm to encrypt passwords
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

	return password_hash( (string) $plain, PASSWORD_BCRYPT );
}


/**
 * Match Password
 *
 * @see http://php.net/password_verify
 *
 * @since 13.1 Security use bcrypt algorithm to encrypt passwords
 *
 * @param  string $crypted Encrypted password.
 * @param  string $plain   Plain text password.
 *
 * @return bool True if password match, else false
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

	return password_verify( (string) $plain, (string) $crypted );
}
