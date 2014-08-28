<?php
$size_limit = 2097152; // file size must be < 2Mb

$PicturesPath = $StudentPicturesPath.UserSyear();
$userID = UserStudentID();

if (isset($photo_profile) && $photo_profile=='user')
{
	$PicturesPath = $UserPicturesPath.UserSyear();
	$userID = UserStaffID();
}

$new_photo_file = $PicturesPath.'/'.$userID.'.jpg';

if (!is_uploaded_file($_FILES['photo']['tmp_name']))
	$error[] = _('File not uploaded'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

//errors, see error message in modules/Students/Student.php or modules/Users/User.php
elseif ($_FILES['photo']['type'] != 'image/jpeg')
	$error[] = sprintf(_('Wrong file type: %s (JPG required)'),$_FILES['photo']['type']);
	
elseif ($_FILES['photo']['size'] > $size_limit)
	$error[] = sprintf(_('File size > %01.2fMb: %01.2fMb'),(($size_limit/1024)/1024),(($_FILES['photo']['size']/1024)/1024));
	
//if current sYear folder doesnt exist, create it!
elseif (!is_dir($PicturesPath) && !mkdir($PicturesPath))
	$error[] = sprintf(_('Folder not created').': %s',$PicturesPath);
		
elseif (!is_writable($PicturesPath))
	$error[] = sprintf(_('Folder not writable').': %s',$PicturesPath); //see PHP user rights

//store file
elseif(!move_uploaded_file($_FILES['photo']['tmp_name'],$new_photo_file))
{
	$error[] = sprintf(_('Error').': '._('File invalid or not moveable').': %s',$_FILES['photo']['tmp_name']);
	$new_photo_file = '';
}

$moodleError = Moodle($_REQUEST['modname'], 'core_files_upload');
?>