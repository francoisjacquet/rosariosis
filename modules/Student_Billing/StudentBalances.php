<?php
/**
* @file $Id: StudentBalances.php 403 2007-01-22 21:04:44Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

DrawHeader(ProgramTitle());

$extra['SELECT'] = ',(COALESCE((SELECT SUM(f.AMOUNT) FROM BILLING_FEES f WHERE f.STUDENT_ID=ssm.STUDENT_ID AND f.SYEAR=ssm.SYEAR),0)-COALESCE((SELECT SUM(p.AMOUNT) FROM BILLING_PAYMENTS p WHERE p.STUDENT_ID=ssm.STUDENT_ID AND p.SYEAR=ssm.SYEAR),0)) AS BALANCE';

$extra['columns_after'] = array('BALANCE'=>_('Balance'));

$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = array('BALANCE'=>'_makeCurrency');

//Widgets('all');

if(User('PROFILE')=='parent' || User('PROFILE')=='student')
	$_REQUEST['search_modfunc'] = 'list';
Search('student_id',$extra);

function _makeCurrency($value,$column)
{
	return Currency($value*-1);
}

?>