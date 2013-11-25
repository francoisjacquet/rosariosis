<?php
/**
* @file $Id: Search.php 161 2006-09-07 06:21:17Z doritojones $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

//modif Francois: fix Fatal error: Maximum function nesting level of '100' reached, aborting
//if(User('PROFILE')=='admin')
	$_REQUEST['modname'] = "Student_Billing/StudentFees.php"; 

$modcat = 'Student_Billing';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}

?>
