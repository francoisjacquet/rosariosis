<?php
function PortalNotesFiles($file, &$PortalNotesFilesError)
{	global $PortalNotesFilesPath;
	if (!$file || !is_uploaded_file($file['tmp_name'])) //no file uploaded
		$PortalNotesFilesError = _('File not uploaded'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

	//extensions white list
	$white_list = array('.doc', '.docx', '.txt', '.pdf', '.xls', '.xlsx', '.csv', '.jpg', '.jpeg', '.png', '.gif', '.zip', '.ppt', '.pptx', '.mp3', '.wav', '.avi', '.mp4', '.ogg');
	if ( !in_array( mb_strtolower(mb_strrchr($file['name'], '.')), $white_list ) )
		$PortalNotesFilesError = _('Unauthorized file attached extension').': '.mb_strtolower(mb_strrchr($file['name'], '.')); 
			
	if ($file['size'] > 10240000) // file size must be < 10Mb
		$PortalNotesFilesError = _('File attached size').' > 10Mb: '. ($file['size']/1024)/1024 .'Mb';

	//if current sYear folder doesnt exist, create it!
	if (!is_dir($PortalNotesFilesPath))
		if (!mkdir($PortalNotesFilesPath))
			$PortalNotesFilesError = _('Folder not created').': '.$PortalNotesFilesPath;
			
	if (!is_writable($PortalNotesFilesPath))
		$PortalNotesFilesError = _('Folder not writable').': '.$PortalNotesFilesPath; //see PHP user rights

	if (!empty($PortalNotesFilesError))
		return '';
		
	//store file
	$file_name = str_replace(' ', '_', trim($file['name'])); //sanitize name
	$file_name = no_accents($file_name);
	$new_file = $PortalNotesFilesPath.$file_name;
	if(move_uploaded_file($file['tmp_name'],$new_file))
		return $new_file;

	return '';
}

function no_accents($string_accents){
         $string_accents = strtr(utf8_decode($string_accents), utf8_decode('ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñðòóôõöùúûüýÿ/'), 'AAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy/');
         $string_accents = preg_replace('/([^_\-.a-z\/0-9]+)/i', '_', ucwords($string_accents));//replace characters others than letters and numbers and points by _
         return utf8_encode($string_accents);
}
?>