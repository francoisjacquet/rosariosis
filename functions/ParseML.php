<?php
/**
 * Parse Multi Language data
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Parse Multi Languages Field value
 *
 * This function extracts the translation of a user-entered field value
 * Users must enter thetext in the following format:
 * default_string|lang_code:translated string
 *
 * @since 5.8.2 Fix ML default value beginning with `|locale:`.
 *
 * @example "My vacation by the sea|fr_FR.utf8:Mes vacances à la mer|de_DE.utf8:Mein urlaub am rand des meeres"
 *          Will display the English text unless the current language is either fr_FR.utf8 or de_DE.utf8
 *
 * @global string $locale
 *
 * @param  string $field  Multi Languages Field value.
 * @param  string $loc    Locale (optional). Defaults to current locale.
 *
 * @return string Current language Field value
 */
function ParseMLField( $field, $loc = '' )
{
	global $locale;

	if ( ! $field
		|| $field === '.' )
	{
		return $field;
	}

	if ( empty( $loc ) )
	{
		$loc = $locale;
	}

	$field = (string) $field;

	// If no separator found, return untouched input.
	$endpos = mb_strpos( $field, '|' );

	if ( $endpos === false )
	{
		return $field;
	}

	// If no locale defined, return default string.
	if ( empty( $loc ) )
	{
		return mb_substr( $field, 0, $endpos );
	}

	// If no current language tag, return default string.
	$begpos = mb_strpos( $field, '|' . $loc . ':' );

	if ( $begpos === false
		&& $endpos > 0 )
	{
		return mb_substr( $field, 0, $endpos );
	}

	// We've found a translation ...
	// skip language tag in itself.
	$begpos = mb_strpos( $field, ':', $begpos ) + 1;

	// Go to end of translated string (ie. next tag or end of field).
	$endpos = mb_strpos( $field, '|', $begpos );

	if ( $endpos === false )
	{
		$endpos = mb_strlen( $field );
	}

	return mb_substr( $field, $begpos, $endpos - $begpos );
}


/**
 * Parse Multi Languages Array
 *
 * Parse an array of any depth for keys that contain ML strings and replaces those with localized strings
 * Recursive function
 * Calls `ParseMLField()`
 *
 * @param  array        $array Multi Languages Array.
 * @param  array|string $keys  Keys of the array containing Multi Languages strings.
 *
 * @return array        Array with localized strings
 */
function ParseMLArray( $array, $keys )
{
	if ( ! $array
		|| ! $keys )
	{
		return [];
	}

	// Modify loop: use for instead of foreach.
	$k = array_keys( (array) $array );

	$size = count( $k );

	for ( $i = 0; $i < $size; $i++ )
	{
		if ( is_array( $array[ $k[ $i ] ] ) )
		{
			$array[ $k[ $i ] ] = ParseMLArray( $array[ $k[ $i ] ], $keys );

			continue;
		}

		foreach ( (array) $keys as $key )
		{
			if ( $k[ $i ] == $key )
			{
				$array[ $k[ $i ] ] = ParseMLField( $array[ $k[ $i ] ] );
			}
		}
	}

	/*foreach ($array as $k => $v) {
		if (is_array($v))
			$array[ $k ] = ParseMLArray($v, $keys);
		else {
			if ( !is_array($keys)) $keys = array($keys);

			foreach ($keys as $key)
				if ( $k == $key) $array[ $k ] = ParseMLField($v);
		}
	}*/

	return $array;
}
