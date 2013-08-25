<?php
//http://jquery.malsup.com/form/files-raw.php
//upload AJAX student photo
//called by jQuery Form in General_Info.inc.php

chdir('../../');
error_reporting(E_ALL ^ E_NOTICE);

$size_limit = 2097152; // file size must be < 2Mb

if (!$_FILES['photo'] || !is_uploaded_file($_FILES['photo']['tmp_name']))
	die('<div class="error">'.$_POST['Error1'].'</div>'); //Check the post_max_size & php_value upload_max_filesize values in the php.ini file

//errors, see error message in modules/Students/Student.php or modules/Users/User.php
if ($_FILES['photo']['type'] != 'image/jpeg')
	die('<div class="error">'.sprintf($_POST['Error2'],$_FILES['photo']['type']).'</div>');
	
if ($_FILES['photo']['size'] > $size_limit)
	die('<div class="error">'.sprintf($_POST['Error3'],(($size_limit/1024)/1024),(($_FILES['photo']['size']/1024)/1024)).'</div>');
	
//if current sYear folder doesnt exist, create it!
if (!is_dir($_POST['photoPath'].$_POST['sYear']))
	if (!mkdir($_POST['photoPath'].$_POST['sYear']))
		die('<div class="error">'.sprintf($_POST['Error4'],$_POST['photoPath'].$_POST['sYear']).'</div>' );
		
if (!is_writable($_POST['photoPath'].$_POST['sYear']))
	die('<div class="error">'.sprintf($_POST['Error5'],$_POST['photoPath'].$_POST['sYear']).'</div>'); //see PHP user rights

//store file
$new_file = $_POST['photoPath'].$_POST['sYear'].'/'.$_POST['userId'].'.jpg';
if(move_uploaded_file($_FILES['photo']['tmp_name'],$new_file))
	echo $new_file;

include('modules/Moodle/PhotoUpload.php');
?>