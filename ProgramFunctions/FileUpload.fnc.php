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
 * @example FileUpload( 'FILE_ATTACHED', $FileUploadsPath . UserSyear() . '/staff_' . User( 'STAFF_ID' ) . '/', FileExtensionWhiteList(), 0, $error );
 * @example $file_attached = FileUpload( $input, $path, FileExtensionWhiteList(), 0, $error, '', FileNameTimestamp( $_FILES[ $input ]['name'] ) );
 *
 * @global $_FILES
 *
 * @since 10.6 Resize, compress & store image using ImageUpload()
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
		$final_ext = empty( $_FILES[ $input ]['tmp_name'] ) ? '' :
			mb_strtolower( mb_strrchr( $_FILES[ $input ]['name'], '.' ) );
	}

	if ( $file_name_no_ext )
	{
		$file_name = $file_name_no_ext . $final_ext;
	}

	$caller_function = debug_backtrace();

	$caller_function = isset( $caller_function[1]['function'] ) ? $caller_function[1]['function'] : '';

	if ( empty( $_FILES[ $input ]['tmp_name'] )
		|| ! is_uploaded_file( $_FILES[ $input ]['tmp_name'] ) )
	{
		// Check the post_max_size & php_value upload_max_filesize values in the php.ini file.
		$error[] = _( 'File not uploaded' );
	}

	elseif ( ! in_array( mb_strtolower( mb_strrchr( $_FILES[ $input ]['name'], '.' ) ), $ext_white_list ) )
	{
		$error[] = sprintf(
			_( 'Wrong file type: %s (%s required)' ),
			// Fix reflected XSS via mime-type.
			strip_tags( $_FILES[ $input ]['type'] ),
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
		&& ! @mkdir( $path, 0755, true ) ) // Fix shared hosting: permission 755 for directories.
	{
		$error[] = sprintf( _( 'Folder not created' ) . ': %s', $path );
	}

	elseif ( ! is_writable( $path ) )
	{
		// See PHP / Apache user rights for folder.
		$error[] = sprintf( _( 'Folder not writable' ) . ': %s', $path );
	}

	// Check if file is image.
	elseif ( $caller_function !== 'ImageUpload'
		&& in_array( $final_ext, [ '.jpg', '.jpeg', '.png', '.gif' ] ) )
	{
		// Resize, compress & store image using ImageUpload().
		return ImageUpload(
			$input,
			[],
			$path,
			[],
			$final_ext,
			$file_name_no_ext
		);
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
 * @example ImageUpload( 'photo', [ 'width' => 150, 'height' => '150' ], $StudentPicturesPath . UserSyear() . '/', [], '.jpg', UserStudentID() . '.' . bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
 * @example ImageUpload( $base64_img, [ 'width' => 640, 'height' => '320' ] );
 *
 * @since 3.3
 *
 * @uses FileUpload()
 * @uses ImageResizeGD class.
 *
 * @param string $input            Name of the input file field, for example 'photo', or base64 encoded data, src attribute value.
 * @param array  $target_dim       Target dimensions to determine if can be resized. Defaults to [ 'width' => 994, 'height' => 1405 ] (optional).
 * @param string $path             Final path with trailing slash, for example $StudentPicturesPath . UserSyear() . '/'. Defaults to "assets/FileUploads/[Syear]/[staff_or_student_ID]/" (optional).
 * @param array  $ext_white_list   Extensions white list, for example ['.jpg', '.jpeg'].
 * @param string $final_ext        Final file extension (useful for .jpg, if .jpeg submitted) (optional).
 * @param string $file_name_no_ext Final file name without extension, for example UserStudentID() . '.' . bin2hex( openssl_random_pseudo_bytes( 16 ) ) (optional).
 *
 * @return string|boolean Full path to file, or false (or base64 data) if error
 */
function ImageUpload( $input, $target_dim = [], $path = '', $ext_white_list = [], $final_ext = null, $file_name_no_ext = '' )
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
		$ext_white_list = [ '.jpg', '.jpeg', '.png', '.gif' ];
	}

	// Defaults to horizontal PDF target dimensions.
	$target_dim_default = [ 'width' => 994, 'height' => 1405 ];

	$target_dim = array_replace_recursive( $target_dim_default, (array) $target_dim );

	if ( ImageResizeGD::test() )
	{
		// If folder doesnt exist, create it!
		if ( ! is_dir( $path )
			&& ! @mkdir( $path, 0755, true ) ) // Fix shared hosting: permission 755 for directories.
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

		if ( $final_ext )
		{
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

			if ( filesize( $full_path ) < $original_image_size
				|| ( $extension && $extension !== $image_resize_gd->getSourceType() ) )
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


/**
 * Files field Upload and Update
 * Upload custom Files field & update corresponding DB table.
 * Input name must BEGIN with $request, for example: "valuesCUSTOM_3".
 *
 * @since 4.6
 * @since 10.4 Add optional $id param
 *
 * @example FilesUploadUpdate( 'schools', 'values',	$FileUploadsPath . 'Schools/' . UserSchool() . '/' );
 * @example FilesUploadUpdate( $table, 'tables' . $id, $FileUploadsPath . 'Hostel/', $id );
 *
 * @uses FileUpload()
 *
 * @param string $table   DB Table name.
 * @param string $request Request part of the input name.
 * @param string $path    Path, folder where the files will be uploaded to.
 * @param int    $id      Table row ID. Optional.
 *
 * @return string Empty or last file full path.
 */
function FilesUploadUpdate( $table, $request, $path, $id = 0 )
{
	global $error;

	if ( ! $table
		|| ! $path
		|| empty( $_FILES ) )
	{
		return '';
	}

	$table = mb_strtolower( $table );

	$new_file = '';

	foreach ( $_FILES as $input => $file )
	{
		if ( mb_strpos( $input, $request ) !== 0 )
		{
			// Input name must BEGIN with $request, for example: "valuesCUSTOM_3".
			continue;
		}

		$new_file = FileUpload(
			$input,
			$path,
			FileExtensionWhiteList(),
			0,
			$error,
			'',
			FileNameTimestamp( $_FILES[ $input ]['name'] )
		);

		if ( $new_file )
		{
			$value_append = $new_file . '||';

			$column = str_replace( $request, '', $input );

			if ( $table === 'schools' )
			{
				$id = $id ? $id : UserSchool();

				$where_sql = "ID='" . (int) $id . "' AND SYEAR='" . UserSyear() . "'";
			}
			elseif ( $table === 'students' )
			{
				$id = $id ? $id : UserStudentID();

				$where_sql = "STUDENT_ID='" . (int) $id . "'";
			}
			elseif ( $table === 'address' )
			{
				$id = $id ? $id : $_REQUEST['address_id'];

				$where_sql = "ADDRESS_ID='" . (int) $id . "'";
			}
			elseif ( $table === 'people' )
			{
				$id = $id ? $id : $_REQUEST['person_id'];

				$where_sql = "PERSON_ID='" . (int) $id . "'";
			}
			elseif ( $table === 'staff' )
			{
				$id = $id ? $id : UserStaffID();

				$where_sql = "STAFF_ID='" . (int) $id . "'";
			}
			else
			{
				$id = $id ? $id : $_REQUEST['id'];

				$where_sql = "ID='" . (int) $id . "'";
			}

			DBQuery( "UPDATE " . DBEscapeIdentifier( $table ) . "
				SET " . DBEscapeIdentifier( $column ) . "=CONCAT(COALESCE(" .
				DBEscapeIdentifier( $column ) . ",''),'" . DBEscapeString( $value_append ) . "')
				WHERE " . $where_sql );
		}
	}

	return $new_file;
}

/**
 * Handle `multiple` files attribute for FileUpload().
 * Move $_FILES[ $input ][...][ $i ] to $_FILES[ {$input}_{$i} ] so FileUpload() can handle it.
 *
 * @since 7.8
 *
 * @example foreach ( FileUploadMultiple( 'files' ) as $input ) { FileUpload( $input ) }
 *
 * @param string $input Input name, without square brackets [].
 *
 * @return array Empty if no files. $input if not multiple. {$input}_{$i} if multiple.
 */
function FileUploadMultiple( $input )
{
	if ( ! isset( $_FILES[ $input ] ) )
	{
		return [];
	}

	if ( ! is_array( $_FILES[ $input ]['name'] ) )
	{
		// Not multiple files, return $input.
		return [ $input ];
	}

	$inputs = [];

	$files = [];

	foreach ( $_FILES[ $input ] as $attribute => $files_info )
	{
		foreach ( $files_info as $i => $file_info )
		{
			if ( ! isset( $files[ $i ] ) )
			{
				$files[ $i ] = [];
			}

			$files[ $i ][ $attribute ] = $file_info;
		}
	}

	foreach ( $files as $i => $file )
	{

		$input_new_index = $input . '_' . $i;

		$inputs[] = $input_new_index;

		// Move $_FILES[ $input ][...][ $i ] to $_FILES[ {$input}_{$i} ] so FileUpload() can handle it.
		$_FILES[ $input_new_index ] = $file;
	}

	unset( $_FILES[ $input ] );

	return $inputs;
}

/**
 * Removes accents from string.
 * Also replaces characters others than letters, space, numbers & points
 * with underscores '_'.
 * Perfect to sanitize a filename.
 *
 * @since 3.4 uses PHP intl extension or return microtime in case string does not contain ASCII chars.
 * @since 8.2 Fix replace regex: remove slash & allow space
 *
 * @link http://stackoverflow.com/questions/1017599/how-do-i-remove-accents-from-characters-in-a-php-string
 *
 * @example no_accents( 'рулонпользователей' )
 * Will return 'rulonpol_zovatelej' if PHP intl extension is activated
 * Else it will return microtime, for example '14976328319110'
 *
 * @example no_accents( '集团分配学生信息' )
 * Will return 'ji_tuan_fen_pei_xue_sheng_xin_xi' if PHP intl extension is activated
 * Else it will return microtime, for example '14976328319110'
 *
 * @param string $string String with maybe accents.
 *
 * @return string String with no accents or microtime.
 */
function no_accents( $string )
{
	if ( function_exists( 'transliterator_transliterate' ) )
	{
		/**
		 * Requires PHP intl extension.
		 * Will transliterate to latin ASCII chars.
		 *
		 * @example рулонпользователей => rulonpol_zovatelej
		 * @example 集团分配学生信息 => ji_tuan_fen_pei_xue_sheng_xin_xi
		 */
		$string = transliterator_transliterate(
			'Any-Latin; Latin-ASCII; Lower()',
			$string
		);

		// Replace characters others than letters, space, numbers & points with underscores  "_".
		$string = preg_replace(
			'/([^ _\-.a-z0-9]+)/i',
			'_',
			$string
		);

		return $string;
	}

	$c195 = chr( 195 );
	$c196 = chr( 196 );
	$c197 = chr( 197 );

	$chars = [
	// Decompositions for Latin-1 Supplement.
	$c195 . chr(128) => 'A', $c195 . chr(129) => 'A',
	$c195 . chr(130) => 'A', $c195 . chr(131) => 'A',
	$c195 . chr(132) => 'A', $c195 . chr(133) => 'A',
	$c195 . chr(135) => 'C', $c195 . chr(136) => 'E',
	$c195 . chr(137) => 'E', $c195 . chr(138) => 'E',
	$c195 . chr(139) => 'E', $c195 . chr(140) => 'I',
	$c195 . chr(141) => 'I', $c195 . chr(142) => 'I',
	$c195 . chr(143) => 'I', $c195 . chr(145) => 'N',
	$c195 . chr(146) => 'O', $c195 . chr(147) => 'O',
	$c195 . chr(148) => 'O', $c195 . chr(149) => 'O',
	$c195 . chr(150) => 'O', $c195 . chr(153) => 'U',
	$c195 . chr(154) => 'U', $c195 . chr(155) => 'U',
	$c195 . chr(156) => 'U', $c195 . chr(157) => 'Y',
	$c195 . chr(159) => 's', $c195 . chr(160) => 'a',
	$c195 . chr(161) => 'a', $c195 . chr(162) => 'a',
	$c195 . chr(163) => 'a', $c195 . chr(164) => 'a',
	$c195 . chr(165) => 'a', $c195 . chr(167) => 'c',
	$c195 . chr(168) => 'e', $c195 . chr(169) => 'e',
	$c195 . chr(170) => 'e', $c195 . chr(171) => 'e',
	$c195 . chr(172) => 'i', $c195 . chr(173) => 'i',
	$c195 . chr(174) => 'i', $c195 . chr(175) => 'i',
	$c195 . chr(177) => 'n', $c195 . chr(178) => 'o',
	$c195 . chr(179) => 'o', $c195 . chr(180) => 'o',
	$c195 . chr(181) => 'o',
	$c195 . chr(182) => 'o', $c195 . chr(185) => 'u',
	$c195 . chr(186) => 'u', $c195 . chr(187) => 'u',
	$c195 . chr(188) => 'u', $c195 . chr(189) => 'y',
	$c195 . chr(191) => 'y',
	// Decompositions for Latin Extended-A.
	$c196 . chr(128) => 'A', $c196 . chr(129) => 'a',
	$c196 . chr(130) => 'A', $c196 . chr(131) => 'a',
	$c196 . chr(132) => 'A', $c196 . chr(133) => 'a',
	$c196 . chr(134) => 'C', $c196 . chr(135) => 'c',
	$c196 . chr(136) => 'C', $c196 . chr(137) => 'c',
	$c196 . chr(138) => 'C', $c196 . chr(139) => 'c',
	$c196 . chr(140) => 'C', $c196 . chr(141) => 'c',
	$c196 . chr(142) => 'D', $c196 . chr(143) => 'd',
	$c196 . chr(144) => 'D', $c196 . chr(145) => 'd',
	$c196 . chr(146) => 'E', $c196 . chr(147) => 'e',
	$c196 . chr(148) => 'E', $c196 . chr(149) => 'e',
	$c196 . chr(150) => 'E', $c196 . chr(151) => 'e',
	$c196 . chr(152) => 'E', $c196 . chr(153) => 'e',
	$c196 . chr(154) => 'E', $c196 . chr(155) => 'e',
	$c196 . chr(156) => 'G', $c196 . chr(157) => 'g',
	$c196 . chr(158) => 'G', $c196 . chr(159) => 'g',
	$c196 . chr(160) => 'G', $c196 . chr(161) => 'g',
	$c196 . chr(162) => 'G', $c196 . chr(163) => 'g',
	$c196 . chr(164) => 'H', $c196 . chr(165) => 'h',
	$c196 . chr(166) => 'H', $c196 . chr(167) => 'h',
	$c196 . chr(168) => 'I', $c196 . chr(169) => 'i',
	$c196 . chr(170) => 'I', $c196 . chr(171) => 'i',
	$c196 . chr(172) => 'I', $c196 . chr(173) => 'i',
	$c196 . chr(174) => 'I', $c196 . chr(175) => 'i',
	$c196 . chr(176) => 'I', $c196 . chr(177) => 'i',
	$c196 . chr(178) => 'IJ',$c196 . chr(179) => 'ij',
	$c196 . chr(180) => 'J', $c196 . chr(181) => 'j',
	$c196 . chr(182) => 'K', $c196 . chr(183) => 'k',
	$c196 . chr(184) => 'k', $c196 . chr(185) => 'L',
	$c196 . chr(186) => 'l', $c196 . chr(187) => 'L',
	$c196 . chr(188) => 'l', $c196 . chr(189) => 'L',
	$c196 . chr(190) => 'l', $c196 . chr(191) => 'L',
	$c197 . chr(128) => 'l', $c197 . chr(129) => 'L',
	$c197 . chr(130) => 'l', $c197 . chr(131) => 'N',
	$c197 . chr(132) => 'n', $c197 . chr(133) => 'N',
	$c197 . chr(134) => 'n', $c197 . chr(135) => 'N',
	$c197 . chr(136) => 'n', $c197 . chr(137) => 'N',
	$c197 . chr(138) => 'n', $c197 . chr(139) => 'N',
	$c197 . chr(140) => 'O', $c197 . chr(141) => 'o',
	$c197 . chr(142) => 'O', $c197 . chr(143) => 'o',
	$c197 . chr(144) => 'O', $c197 . chr(145) => 'o',
	$c197 . chr(146) => 'OE',$c197 . chr(147) => 'oe',
	$c197 . chr(148) => 'R', $c197 . chr(149) => 'r',
	$c197 . chr(150) => 'R', $c197 . chr(151) => 'r',
	$c197 . chr(152) => 'R', $c197 . chr(153) => 'r',
	$c197 . chr(154) => 'S', $c197 . chr(155) => 's',
	$c197 . chr(156) => 'S', $c197 . chr(157) => 's',
	$c197 . chr(158) => 'S', $c197 . chr(159) => 's',
	$c197 . chr(160) => 'S', $c197 . chr(161) => 's',
	$c197 . chr(162) => 'T', $c197 . chr(163) => 't',
	$c197 . chr(164) => 'T', $c197 . chr(165) => 't',
	$c197 . chr(166) => 'T', $c197 . chr(167) => 't',
	$c197 . chr(168) => 'U', $c197 . chr(169) => 'u',
	$c197 . chr(170) => 'U', $c197 . chr(171) => 'u',
	$c197 . chr(172) => 'U', $c197 . chr(173) => 'u',
	$c197 . chr(174) => 'U', $c197 . chr(175) => 'u',
	$c197 . chr(176) => 'U', $c197 . chr(177) => 'u',
	$c197 . chr(178) => 'U', $c197 . chr(179) => 'u',
	$c197 . chr(180) => 'W', $c197 . chr(181) => 'w',
	$c197 . chr(182) => 'Y', $c197 . chr(183) => 'y',
	$c197 . chr(184) => 'Y', $c197 . chr(185) => 'Z',
	$c197 . chr(186) => 'z', $c197 . chr(187) => 'Z',
	$c197 . chr(188) => 'z', $c197 . chr(189) => 'Z',
	$c197 . chr(190) => 'z', $c197 . chr(191) => 's'
	];

	$string = strtr( $string, $chars );

	// Replace characters others than letters, space, numbers & points with underscores  "_".
	$string = preg_replace(
		'/([^ _\-.a-z0-9]+)/i',
		'_',
		$string
	);

	if ( $string === '_' )
	{
		// String does not contain any latin ASCII char return microtime!
		$string = number_format( microtime( true ), 4, '', '' );
	}

	return $string;
}


/**
 * Add timestamp (including microseconds) to filename to make it harder to predict
 * For example: my_file.jpg => my_file_2023-04-11_185030.123456.jpg
 *
 * @link https://huntr.dev/bounties/42f38a84-8954-484d-b5ff-706ca0918194/
 *
 * @since 11.1
 *
 * @uses no_accents()
 *
 * @param string $file_name File name. Can be empty.
 * @param bool   $keep_ext  Keep extension. Defaults to false.
 *
 * @return string File name with timestamp.
 */
function FileNameTimestamp( $file_name, $keep_ext = false )
{
	$file_name_safe = no_accents( $file_name );

	$file_ext_pos = mb_strrpos( $file_name_safe, '.' );

	$file_name_no_ext = $file_name_safe;

	if ( $file_ext_pos )
	{
		$file_name_no_ext = mb_substr( $file_name_safe, 0, $file_ext_pos );
	}

	// @since 11.0 Add microseconds to filename format to make it harder to predict.
	$timestamp = date( 'Y-m-d_His' ) . '.' . substr( (string) microtime(), 2, 6 );

	$file_name_timestamp = $file_name_no_ext ? $file_name_no_ext . '_' . $timestamp : $timestamp;

	if ( ! $keep_ext
		|| ! $file_ext_pos )
	{
		return $file_name_timestamp;
	}

	$file_ext = mb_substr( $file_name_safe, $file_ext_pos );

	return $file_name_timestamp . $file_ext;
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

	$val = (int) $val;

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


/**
 * Extensions white list.
 * Common file types.
 * Obviously, we won't include executable types
 * .php, .sql, .js, .exe...
 * If you file type is not white listed,
 * put it in a ZIP archive!
 *
 * @since 3.8.1
 *
 * @link http://fileinfo.com/filetypes/common
 */
function FileExtensionWhiteList() {
	return [
		// Micro$oft Office.
		'.doc',
		'.docx',
		'.dotx',
		'.xls',
		'.xlsm',
		'.xlsx',
		'.xlr',
		'.pps',
		'.ppsx',
		'.ppt',
		'.pptx',
		'.wps',
		'.wpd',
		'.rtf',
		'.mdb',
		'.sldx',
		// Libre Office.
		'.odt',
		'.ods',
		'.odp',
		'.odg',
		'.odc',
		'.odb',
		'.odf',
		// Apple iWork.
		'.key',
		'.numbers',
		'.pages',
		// Images.
		'.jpg',
		'.jpeg',
		'.png',
		'.gif',
		'.bmp',
		// @since 8.9.3 Fix stored XSS security issue: do not allow unsanitized SVG
		// '.svg',
		'.ico',
		'.psd',
		'.ai',
		'.eps',
		'.ps',
		'.webp',
		// Audio.
		'.mp3',
		'.m4a',
		'.ogg',
		'.wav',
		'.mid',
		'.midi',
		'.wma',
		'.aif',
		'.flac',
		'.mka',
		// Video.
		'.avi',
		'.mp4',
		'.mpg',
		'.mpeg',
		'.ogv',
		'.webm',
		'.wmv',
		'.h264',
		'.mkv',
		'.mov',
		'.m4v',
		'.flv', // @deprecated Adobe Flash.
		'.swf', // @deprecated Adobe Flash.
		// Text.
		'.txt',
		'.pdf',
		'.md',
		'.csv',
		'.tsv',
		'.tex',
		'.log',
		'.json',
		'.ics',
		// Email.
		'.email',
		'.eml',
		'.emlx',
		'.msg',
		'.vcf',
		// Web.
		// @since 8.9.5 Fix stored XSS security issue: do not allow unsanitized XML & HTML
		// '.xml',
		// '.xhtml',
		// '.html',
		// '.htm',
		'.css',
		'.rss',
		// Compressed.
		'.zip',
		'.rar',
		'.7z',
		'.tar',
		'.gz',
	];
}
