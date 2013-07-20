<?php
//modif Francois: add password encryption
if (!defined('CRYPT_SHA512'))
	define('CRYPT_SHA512', 1);
	
function encrypt_password($plain) {
	$password = rand(999999999, 9999999999);
	$salt = '$6$'.mb_substr(sha1($password), 0, 16	);
	$password = crypt($plain, $salt);
	return $password;
}

function match_password($password, $plain) {
	//$salt = mb_substr($password, 0, 19);
	return $password == crypt($plain, $password);
}
?>