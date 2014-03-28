<?php
function SchoolLogo($file)
{
	if (!$file || !is_uploaded_file($file['tmp_name'])) //no file uploaded
		$SchoolLogoError = _('File not uploaded'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

	//extensions white list
	$white_list = array('.jpg', '.jpeg');
	if ( !in_array( mb_strtolower(mb_strrchr($file['name'], '.')), $white_list ) )
		$SchoolLogoError = sprintf(_('Wrong file type: %s (JPG required)'),$file['type']); 
			
	$size_limit = 2097152; // file size must be < 2Mb
	if ($file['size'] > $size_limit)
		$SchoolLogoError = sprintf(_('File size > %01.2fMb: %01.2fMb'),(($size_limit/1024)/1024),(($file['size']/1024)/1024));

	if (!is_writable('assets/'))
		$SchoolLogoError = _('Folder not writable').': assets/'; //see PHP user rights

	if (!empty($SchoolLogoError))
		return $SchoolLogoError;
		
	//store file
	$file_name = 'school_logo_'.UserSchool().'.jpg';
	$new_file = 'assets/'.$file_name;
	move_uploaded_file($file['tmp_name'],$new_file);
	return '';
}
?>