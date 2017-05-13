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

	if ( ! $final_ext )
	{
		$final_ext = mb_strtolower( mb_strrchr( $_FILES[ $input ]['name'], '.' ) );
	}

	if ( $file_name_no_ext )
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
		&& ! mkdir( $path, 0774, true ) )
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
			no_accents( mb_substr(
				$_FILES[ $input ]['name'],
				0,
				mb_strrpos( $_FILES[ $input ]['name'], '.' )
			) ) . $final_ext
		) )
	) )
	{
		$error[] = sprintf( _( 'File invalid or not moveable' ) . ': %s', $_FILES[ $input ]['tmp_name'] );
	}

	return $full_path;
}


/**
 * Image File Upload
 *
 * @example ImageUpload( 'photo', array( 'width' => 150, 'height' => '150' ), $StudentPicturesPath . UserSyear() . '/', array(), '.jpg', UserStudentID() );
 * @example ImageUpload( $base64_img, array( 'width' => 640, 'height' => '320' ) );
 *
 * @since 3.3
 *
 * @uses FileUpload()
 * @uses ImageResizeGD class.
 *
 * @param string $input            Name of the input file field, for example 'photo', or base64 encoded data, src attribute value.
 * @param array  $target_dim       Target dimensions to determine if can be resized. Defaults to array( 'width' => 994, 'height' => 1405 ) (optional).
 * @param string $path             Final path with trailing slash, for example $StudentPicturesPath . UserSyear() . '/'. Defaults to "assets/FileUploads/[Syear]/[staff_or_student_ID]/" (optional).
 * @param array  $ext_white_list   Extensions white list, for example array('.jpg', '.jpeg').
 * @param string $final_ext        Final file extension (useful for .jpg, if .jpeg submitted) (optional).
 * @param string $file_name_no_ext Final file name without extension, for example UserStudentID() (optional).
 *
 * @return string|boolean Full path to file, or false if error
 */
function ImageUpload( $input, $target_dim = array(), $path = '', $ext_white_list = array(), $final_ext = null, $file_name_no_ext = '' )
{
	global $FileUploadsPath,
		$PNGQuantPath,
		$error;

	require_once 'classes/ImageResizeGD.php';

	$is_base64 = ( strpos( $input, 'data:image' ) === 0 );

	if ( ! $path )
	{
		// Path defaults to "assets/FileUploads/[Syear]/[staff_or_student_ID]/".
		$user_folder = User( 'STAFF_ID' ) ? 'staff_' . User( 'STAFF_ID' ) : 'student_' . UserStudentID();

		$path = $FileUploadsPath . UserSyear() . '/' . $user_folder . '/';
	}

	if ( ! $ext_white_list )
	{
		// Defaults to JPG, PNG & GIF.
		$ext_white_list = array( '.jpg', '.jpeg', '.png', '.gif' );
	}

	// Defaults to horizontal PDF target dimensions.
	$target_dim_default = array( 'width' => 994, 'height' => 1405 );

	$target_dim = array_replace_recursive( $target_dim_default, (array) $target_dim );

	if ( ImageResizeGD::test() )
	{
		// If folder doesnt exist, create it!
		if ( ! is_dir( $path )
			&& ! mkdir( $path, 0774, true ) )
		{
			$error[] = sprintf( _( 'Folder not created' ) . ': %s', $path );

			return ( $is_base64 ? $input : false );
		}
		elseif ( ! is_writable( $path ) )
		{
			// See PHP / Apache user rights for folder.
			$error[] = sprintf( _( 'Folder not writable' ) . ': %s', $path );

			return ( $is_base64 ? $input : false );
		}

		if ( ! $is_base64 )
		{
			if ( ! is_uploaded_file( $_FILES[ $input ]['tmp_name'] ) )
			{
				// Check the post_max_size & php_value upload_max_filesize values in the php.ini file.
				$error[] = _( 'File not uploaded' );

				return false;
			}

			$image_path_or_string = $_FILES[ $input ]['tmp_name'];

			$original_image_size = filesize( $image_path_or_string );
		}
		else
		{
			$image_path_or_string = $input;

			// http://stackoverflow.com/questions/5373544/php-size-of-base64-encode-string-file
			$original_image_size = (int) ( strlen( rtrim( $image_path_or_string, '=' ) ) * 3 / 4 );
		}

		// Build file name.
		if ( $file_name_no_ext )
		{
			$file_name = $file_name_no_ext . $final_ext;
		}
		elseif ( $is_base64 )
		{
			// Use MD5 sum for base64 images.
			$file_name = md5( $image_path_or_string ) . $final_ext;

			$full_path = $path . $file_name;

			// Check if file already exists?
			if ( $final_ext
				&& file_exists( $full_path ) )
			{
				return $full_path;
			}
			elseif ( file_exists( $full_path . '.jpg' ) )
			{
				return $full_path . '.jpg';
			}
			elseif ( file_exists( $full_path . '.png' ) )
			{
				return $full_path . '.png';
			}
			elseif ( file_exists( $full_path . '.gif' ) )
			{
				return $full_path . '.gif';
			}
		}
		else
		{
			// Use original file name.
			$file_name = no_accents( mb_substr(
				$_FILES[ $input ]['name'],
				0,
				mb_strrpos( $_FILES[ $input ]['name'], '.' )
			) ) . $final_ext;
		}

		$extension = null;

		if ( mb_strtolower( $final_ext ) === '.jpg'
			|| mb_strtolower( $final_ext ) === '.jpeg' )
		{
			$extension = IMAGETYPE_JPEG;
		}
		elseif ( mb_strtolower( $final_ext ) === '.png' )
		{
			$extension = IMAGETYPE_PNG;
		}
		elseif ( mb_strtolower( $final_ext ) === '.gif' )
		{
			$extension = IMAGETYPE_GIF;
		}

		try
		{
			$target_jpg_compression = 85;

			$image_resize_gd = new ImageResizeGD(
				$image_path_or_string,
				$target_jpg_compression,
				9,
				$PNGQuantPath
			);

			// 3x or 2x Retina factor depending if small target image.
			$factor = $target_dim['width'] < 994 ? 3 : 2;

			if ( $image_resize_gd->getSourceWidth() > $target_dim['width'] * $factor
				|| $image_resize_gd->getSourceHeight() > $target_dim['height'] * $factor )
			{
				// Image dimensions > target dimensions *2 or 3 (enough for Retina), resize & compress more.
				$image_resize_gd->resizeWithinDimensions(
					$target_dim['width'] * $factor,
					$target_dim['height'] * $factor
				);

				$target_jpg_compression = 65;
			}
			elseif ( $image_resize_gd->getSourceWidth() > $target_dim['width']
				|| $image_resize_gd->getSourceHeight() > $target_dim['height'] )
			{
				// Image dimensions > target dimensions, compress a bit more.
				$target_jpg_compression = 75;
			}

			// Upload image and return path.
			$full_path = $image_resize_gd->saveImageFile(
				$path . $file_name,
				$extension,
				$target_jpg_compression,
				// White background for JPEG.
				( $extension === IMAGETYPE_JPEG ? 'FFFFFF' : null )
			);

			if ( filesize( $full_path ) < $original_image_size )
			{
				return $full_path;
			}
			elseif ( $is_base64 )
			{
				// Our "optimized" file results bigger than the original one...
				$image_data = $image_path_or_string;

				$image_data = substr( $image_data, ( strpos( $image_data, 'base64' ) + 6 ) );

				$image_data = base64_decode( $image_data );

				// Save the original base64 image instead.
				file_put_contents( $full_path, $image_data );

				return $full_path;
			}
		}
		catch ( Exception $e )
		{
			$error[] = 'ImageResizeGD: ' . $e->getMessage();
		}
		catch ( InvalidArgumentException $e )
		{
			$error[] = 'ImageResizeGD: ' . $e->getMessage();
		}
	}

	// No GD library or ImageResizeGD exception...
	if ( $is_base64 )
	{
		// We return the base64 image as is...
		return $input;
	}

	// Use regular FileUpload() function.
	return (string) FileUpload(
		$input,
		$path,
		$ext_white_list,
		0,
		$error,
		(string) $final_ext,
		$file_name_no_ext
	);
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


/**
 * Human filesize
 * Converts bytes into human readable file size.
 *
 * @example $file_size = HumanFilesize( filesize( $file_name ) );
 *
 * @link http://php.net/manual/en/function.filesize.php#106569
 *
 * @since  2.9
 *
 * @param  integer $bytes    File size in Bytes.
 * @param  integer $decimals Decimals (optional). Defaults to 1.
 *
 * @return string            Human readable file size.
 */
function HumanFilesize( $bytes, $decimals = 1 )
{
	$sz = 'BKMGTP';

	$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

	return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[ $factor ];
}
