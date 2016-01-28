<?php
/**
 * File Upload functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * File Upload
 *
 * @example FileUpload( 'photo', $StudentPicturesPath . UserSyear() . '/', array( '.jpg', '.jpeg' ), 2, $error, '.jpg', UserStudentID() );
 *
 * @global $_FILES
 *
 * @param string $input            Name of the input file field, for example 'photo'.
 * @param string $path             Final path with trailing slash, for example $StudentPicturesPath . UserSyear() . '/'.
 * @param array  $ext_white_list   Extensions white list, for example array('.jpg', '.jpeg').
 * @param float  $size_limit       Size Limit in Mb, set it to 0 to use server limit (upload_max_filesize).
 * @param array  $error            The global errors array.
 * @param string $final_ext        Final file extension (useful for .jpg, if .jpeg submitted) (optional).
 * @param string $file_name_no_ext Final file name without extension, for example UserStudentID() (optional).
 *
 * @return string|boolean Full path to file, or false if error
 */
function FileUpload( $input, $path, $ext_white_list, $size_limit, &$error, $final_ext = '', $file_name_no_ext = '' )
{
	$file_name = $full_path = false;

	if ( $final_ext
		&& $file_name_no_ext )
	{
		$file_name = $file_name_no_ext . $final_ext;
	}

	if ( ! is_uploaded_file( $_FILES[ $input ]['tmp_name'] ) )
	{
		// Check the post_max_size & php_value upload_max_filesize values in the php.ini file.
		$error[] = _( 'File not uploaded' );
	}

	elseif ( ! in_array( mb_strtolower( mb_strrchr( $_FILES[ $input ]['name'], '.' ) ), $ext_white_list ) )
	{
		$error[] = sprintf(
			_( 'Wrong file type: %s (%s required)' ),
			$_FILES[ $input ]['type'],
			implode( ', ', $ext_white_list )
		);
	}

	elseif ( $size_limit
		&& $_FILES[ $input ]['size'] > $size_limit * 1024 * 1024 )
	{
		$error[] = sprintf(
			_( 'File size > %01.2fMb: %01.2fMb' ),
			$size_limit,
			( $_FILES[ $input ]['size'] / 1024 ) / 1024
		);
	}

	// If folder doesnt exist, create it!
	elseif ( ! is_dir( $path )
		&& ! mkdir( $path ) )
	{
		$error[] = sprintf( _( 'Folder not created' ) . ': %s', $path );
	}

	elseif ( ! is_writable( $path ) )
	{
		// See PHP / Apache user rights for folder.
		$error[] = sprintf( _( 'Folder not writable' ) . ': %s', $path );
	}

	// Store file.
	elseif ( ! move_uploaded_file(
		$_FILES[ $input ]['tmp_name'],
		$full_path = ( $path . ( $file_name ?
			$file_name :
			( ! $final_ext ?
				no_accents( $_FILES[ $input ]['name'] ) :
				no_accents( mb_substr(
					$_FILES[ $input ]['name'],
					0,
					mb_strrpos( $_FILES[ $input ]['name'], '.' )
				) ) . $final_ext
			)
		) ) 
	) )
	{
		$error[] = sprintf( _( 'File invalid or not moveable' ) . ': %s', $_FILES[ $input ]['tmp_name'] );
	}

	return $full_path;
}


function no_accents( $string_accents )
{
	 $string_accents = strtr(
		utf8_decode( $string_accents ),
		utf8_decode( 'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ/' ),
		'AAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy/'
	 );

	 // Replace characters others than letters and numbers and points with underscore  "_".
	 $string_accents = preg_replace(
		'/([^_\-.a-z\/0-9]+)/i',
		'_',
		ucwords( $string_accents )
	 );

	 return utf8_encode( $string_accents );
}


/**
 * Get server maximum file upload size (Mb)
 *
 * @see  php.ini directives (upload_max_filesize & post_max_size)
 *
 * @uses ReturnMegabytes() function
 *
 * @return float maximum file upload size in Mega Bytes (Mb)
 */
function FileUploadMaxSize()
{
	// Size is limited by server configuration (upload_max_filesize & post_max_size).
	return (float) min(
		ReturnMegabytes( ini_get( 'post_max_size' ) ),
		ReturnMegabytes( ini_get( 'upload_max_filesize' ) )
	);
}


/**
 * Return value in Mega Bytes (MB)
 *
 * @example ReturnMegabytes( ini_get( 'upload_max_filesize' ) )
 *
 * @param  string $val php.ini value, shorthand notation.
 *
 * @return string      value in Mega Bytes (MB)
 */
function ReturnMegabytes( $val ) {

	$val = trim( $val );

	$last = strtolower( $val[ strlen( $val ) - 1 ] );

	switch ( $last ) {

		// The 'G' modifier is available since PHP 5.1.0.
		case 'g':

			$val *= 1024;

		case 'm':

			$val *= 1;

		break;

		default:

			$val /= 1024;

		case 'k':

			$val /= 1024;
	}

	return $val;
}
