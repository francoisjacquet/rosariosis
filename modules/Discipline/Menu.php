<?php
/**
* @file $Id: Menu.php 252 2006-10-19 18:46:09Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

$menu['Discipline']['admin'] = array(
						'Discipline/MakeReferral.php'=>_('Add Referral'),
						'Discipline/Referrals.php'=>_('Referrals'),
						1=>_('Reports'),
						'Discipline/CategoryBreakdown.php'=>_('Category Breakdown'),
						'Discipline/CategoryBreakdownTime.php'=>_('Category Breakdown over Time'),
						'Discipline/StudentFieldBreakdown.php'=>_('Breakdown by Student Field'),
						'Discipline/ReferralLog.php'=>_('Discipline Log'),
						2=>_('Setup'),
						'Discipline/DisciplineForm.php'=>_('Referral Form')
					);
$menu['Discipline']['teacher'] = array(
						'Discipline/MakeReferral.php'=>_('Add Referral'),
						'Discipline/Referrals.php'=>_('Referrals')
					);
//modif Francois: fix error Warning: Invalid argument supplied for foreach()
$menu['Discipline']['parent'] = array(
					);
$exceptions['Discipline'] = array(
					);
?>