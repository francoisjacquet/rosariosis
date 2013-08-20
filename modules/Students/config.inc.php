<?php

// config variables for include/Address.inc.php
// these are the static items for the dynamic select lists in the format
// $options = array('Item 1'=>'Item 1','Item 2'=>'Item2');
$city_options = array();
$state_options = array();
$zip_options = array();
$relation_options = array(_('Father')=>_('Father'),_('Mother')=>_('Mother'),_('Emergency')=>_('Emergency'));
if($info_apd)
	$info_options_x = array(_('Phone')=>_('Phone'),_('Cell Phone')=>_('Cell Phone'),_('Work Phone')=>_('Work Phone'),_('Employer')=>_('Employer'));
?>