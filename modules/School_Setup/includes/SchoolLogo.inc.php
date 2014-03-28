<?php
function SchoolLogo($file)
{
	if (!$file || !is_uploaded_file($file['tmp_name'])) //no file uploaded
		$SchoolLogoError = _('File not uploaded'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

	//extensions white list
	$white_list = array('.jpg', '.jpeg');
	if ( !in_array( mb_strtolower(mb_strrchr($file['name'], '.')), $white_list ) )
		$SchoolLogoError = _('Unauthorized file extension').': '.mb_strtolower(mb_strrchr($file['name'], '.')); 
			
	if ($file['size'] > 2048000) // file size must be < 2Mb
		$SchoolLogoError = _('File size').' > 2Mb: '. ($file['size']/1024)/1024 .'Mb';

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