<?php

//$key, for example 'photo' (name of the input file field)
//$path with trailing slash, for example $StudentPicturesPath.UserSyear()
//$extensions_white_list, for example array('.jpg', '.jpeg')
//$size_limit in Mb
//$error the errors array
//$final_extension (optional) is the extension of the saved file (useful for .jpg, if .jpeg submitted)
//$file_name_without_extension (optional), for example UserStudentID()

//example:
// FileUpload('photo', $StudentPicturesPath.UserSyear().'/', array('.jpg', '.jpeg'), 2, $error, '.jpg', UserStudentID());

//returns full path to file
function FileUpload($key, $path, $extensions_white_list, $size_limit, &$error, $final_extension=false, $file_name_without_extension=false)
{
	$file_name = $full_path = false;
	
	if ($final_extension!=false && $file_name_without_extension!=false)
		$file_name = $file_name_without_extension.$final_extension;
	
	if (!is_uploaded_file($_FILES[$key]['tmp_name']))
		$error[] = _('File not uploaded'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

	elseif ( !in_array( mb_strtolower(mb_strrchr($_FILES[$key]['name'], '.')), $extensions_white_list ) )
		$error[] = sprintf(_('Wrong file type: %s (%s required)'),$_FILES[$key]['type'],implode(', ', $extensions_white_list));
		
	elseif ($_FILES[$key]['size'] > $size_limit*1024*1024)
		$error[] = sprintf(_('File size > %01.2fMb: %01.2fMb'),$size_limit,(($_FILES[$key]['size']/1024)/1024));
		
	//if folder doesnt exist, create it!
	elseif (!is_dir($path) && !mkdir($path))
		$error[] = sprintf(_('Folder not created').': %s',$path);
			
	elseif (!is_writable($path))
		$error[] = sprintf(_('Folder not writable').': %s',$path); //see PHP user rights

	//store file
	elseif(!move_uploaded_file($_FILES[$key]['tmp_name'],$full_path = ($path.($file_name!=false ? $file_name : ($final_extension==false ? no_accents($_FILES[$key]['name']) : no_accents(mb_substr($_FILES[$key]['name'], 0, mb_strrpos($_FILES[$key]['name'],'.'))).$final_extension)))))
		$error[] = sprintf(_('File invalid or not moveable').': %s',$_FILES[$key]['tmp_name']);

	return $full_path;
}

function no_accents($string_accents){
	 $string_accents = strtr(utf8_decode($string_accents), utf8_decode('ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ/'), 'AAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy/');
	 $string_accents = preg_replace('/([^_\-.a-z\/0-9]+)/i', '_', ucwords($string_accents));//replace characters others than letters and numbers and points by _
	 return utf8_encode($string_accents);
}

?>