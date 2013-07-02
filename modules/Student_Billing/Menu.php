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

$menu['Student_Billing']['admin'] = array(
						'Student_Billing/StudentFees.php'=>_('Fees'),
						'Student_Billing/StudentPayments.php'=>_('Payments'),
						'Student_Billing/MassAssignFees.php'=>_('Mass Assign Fees'),
						'Student_Billing/MassAssignPayments.php'=>_('Mass Assign Payments'),
						1=>_('Reports'),
						'Student_Billing/StudentBalances.php'=>_('Student Balances'),
						'Student_Billing/DailyTransactions.php'=>_('Daily Transactions'),
						'Student_Billing/Statements.php'=>_('Print Statements')
					);
//modif Francois: fix error Warning: Invalid argument supplied for foreach()
$menu['Student_Billing']['teacher'] = array();

$menu['Student_Billing']['parent'] = array(
						'Student_Billing/StudentFees.php'=>_('Fees'),
						'Student_Billing/StudentPayments.php'=>_('Payments'),
						1=>_('Reports'),
						'Student_Billing/DailyTransactions.php'=>_('Daily Transactions'),
//modif Francois: fix bug PDF
						'Student_Billing/Statements.php&_ROSARIO_PDF'=>_('Print Statements')
					);

$menu['Student_Billing']['student'] = array(
						'Student_Billing/StudentFees.php'=>_('Fees'),
						'Student_Billing/StudentPayments.php'=>_('Payments'),
						1=>_('Reports'),
						'Student_Billing/DailyTransactions.php'=>_('Daily Transactions'),
//modif Francois: fix bug PDF
						'Student_Billing/Statements.php&_ROSARIO_PDF'=>_('Print Statements')
					);


$exceptions['Student_Billing'] = array(
					);
?>