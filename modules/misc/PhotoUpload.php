<?php
//http://jquery.malsup.com/form/files-raw.php
//upload AJAX student photo
//called by jQuery Form in General_Info.inc.php

chdir('../../');
error_reporting(E_ALL ^ E_NOTICE);

$size_limit = 2097152; // file size must be < 2Mb

if ($_FILES['photo'] && is_uploaded_file($_FILES['photo']['tmp_name']) && $_FILES['photo']['type'] == 'image/jpeg') 
{
	if ($_FILES['photo']['size'] < $size_limit)
	{
		//if current sYear folder doesnt exist, create it!
		if (!is_dir($_POST['photoPath'].$_POST['sYear']))
			if (!mkdir($_POST['photoPath'].$_POST['sYear']))
				die('<div class="error">Error: folder: '.$_POST['photoPath'].$_POST['sYear'].' not created</div>' );
		//store file
		$new_file = $_POST['photoPath'].$_POST['sYear'].'/'.$_POST['userId'].'.jpg';
		if(move_uploaded_file($_FILES['photo']['tmp_name'],$new_file))
			echo $new_file;
	//errors, see error message in modules/Students/Student.php or modules/Users/User.php
	} else { echo '<div class="error">'.sprintf($_POST['Error1'],(($size_limit/1024)/1042),(($_FILES['photo']['size']/1024)/1042)).'</div>'; }
} else { echo '<div class="error">'.sprintf($_POST['Error2'],$_FILES['photo']['type']).'</div>'; }

include('modules/Moodle/PhotoUpload.php');
?>