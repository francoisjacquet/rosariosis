<?php
/**
 * Prepare PHP SELF function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Prepare PHP SELF
 * Generates `Modules.php` SELF URL with GET parameters
 *
 * @example PreparePHP_SELF( $_REQUEST, array(), array( 'modfunc' => 'delete' ) );
 *
 * @uses _myURLEncode()
 *
 * @param  array  $tmp_REQUEST REQUEST vars (optional). Defaults to $_REQUEST array.
 * @param  array  $remove      Remove indexes from $tmp_REQUEST (optional).
 * @param  array  $add         Add values $tmp_REQUEST (associative array) (optional).
 *
 * @return string Modules.php SELF URL
 */
function PreparePHP_SELF( $tmp_REQUEST = array(), $remove = array(), $add = array() )
{
	if ( empty( $tmp_REQUEST ) )
	{
		$tmp_REQUEST = $_REQUEST;
	}

	// Remove Cookie vars.
	foreach ( (array) $_COOKIE as $key => $value )
	{
		unset( $tmp_REQUEST[ $key ] );
	}

	// Remove vars in $remove.
	foreach ( (array) $remove as $key )
	{
		unset( $tmp_REQUEST[ $key ] );
	}

	// Unescape DB strings.
	array_rwalk(
		$tmp_REQUEST,
		function ( $input )
		{
			return str_replace( "''", "'", $input );
		}
	);

	// Add vars in $add.
	foreach ( (array) $add as $key => $value )
	{
		$tmp_REQUEST[ $key ] = $value;
	}


	// Add modname param.
	$PHP_tmp_SELF = 'Modules.php?modname=' . $tmp_REQUEST['modname'];

	unset( $tmp_REQUEST['modname'] );

	// Add other params.
	foreach ( (array) $tmp_REQUEST as $key => $value )
	{
		if ( is_array( $value ) )
		{
			foreach ( (array) $value as $key1 => $value1 )
			{
				if ( is_array( $value1 ) )
				{
					foreach ( (array) $value1 as $key2 => $value2 )
					{
						if ( is_array( $value2 ) )
						{
							foreach ( (array) $value2 as $key3 => $value3 )
							{
								if ( $value3 !== '' )
								{
									$PHP_tmp_SELF .= '&' . $key . '[' . $key1 . '][' . $key2 . '][' . $key3 . ']=' .
										_myURLEncode( $value3 );
								}
							}
						}
						elseif ( $value2 !== '' )
						{
							$PHP_tmp_SELF .= '&' . $key . '[' . $key1 . '][' . $key2 . ']=' .
								_myURLEncode( $value2 );
						}
					}
				}
				elseif ( $value1 !== '' )
				{
					$PHP_tmp_SELF .= '&' . $key . '[' . $key1 . ']=' .
						_myURLEncode( $value1 );
				}
			}
		}
		elseif ( $value !== '' )
		{
			$PHP_tmp_SELF .= '&' . $key . "=" .
				_myURLEncode( $value );
		}
	}

	return $PHP_tmp_SELF;
}


/**
 * Redirect URL
 * Will update the requested URL in the browser,
 * (soft redirection using the X-Redirect-Url header)
 * removing the requested parameters passed as argument.
 * Use after a successful remove / delete / update / save operation.
 * Prevents showing an obsolete & confusing delete confirmation screen on page reload.
 *
 * @since 3.3
 *
 * @example RedirectURL( array( 'modfunc', 'id' ) );
 *
 * @uses X-Redirect-Url header.
 * @uses PreparePHP_SELF
 *
 * @see warehouse.js check for X-Redirect-Url
 *
 * @param array|string $remove Parameters to remove from the $_REQUEST & $_SESSION['_REQUEST_vars'] arrays.
 *
 * @return boolean     False if nothing to remove, else true.
 */
function RedirectURL( $remove )
{
	static $remove_all = array();

	if ( ! $remove )
	{
		return false;
	}

	foreach ( (array) $remove as $request_key )
	{
		if ( ! isset( $_REQUEST[ $request_key ] ) )
		{
			continue;
		}

		$_REQUEST[ $request_key ] = false;

		if ( isset( $_SESSION['_REQUEST_vars'][ $request_key ] ) )
		{
			$_SESSION['_REQUEST_vars'][ $request_key ] = false;
		}
	}

	$remove_all = array_unique( array_merge( $remove_all, (array) $remove ) );

	$redirect_url = PreparePHP_SELF( $_GET, $remove_all );

	// Redirect URL.
	header( 'X-Redirect-Url: ' . $redirect_url );

	return true;
}


/**
 * My URL encode
 * RFC 3986 compliant
 *
 * Local function
 *
 * @see http://php.net/manual/en/function.urlencode.php#97969
 *
 * @param  string $string String to encode.
 *
 * @return string Encoded string
 */
function _myURLEncode( $string )
{
	$entities = array(
		'%21',
		'%2A',
		'%27',
		'%28',
		'%29',
		'%3B',
		'%3A',
		'%40',
		'%26',
		'%3D',
		'%2B',
		'%24',
		'%2C',
		'%2F',
		'%3F',
		'%25',
		'%23',
		'%5B',
		'%5D',
	);

	$replacements = array(
		'!',
		'*',
		"'",
		'(',
		')',
		';',
		':',
		'@',
		'&',
		'=',
		'+',
		'$',
		',',
		'/',
		'?',
		'%',
		'#',
		'[',
		']',
	);

	return str_replace(
		$entities,
		$replacements,
		urlencode( (string) $string )
	);
}
