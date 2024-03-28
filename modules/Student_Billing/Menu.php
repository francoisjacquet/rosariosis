<?php
/**
 * Student Billing module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Student_Billing']['admin'] = [
	'title' => _( 'Student Billing' ),
	'default' => 'Student_Billing/StudentFees.php',
	'Student_Billing/StudentFees.php' => _( 'Fees' ),
	'Student_Billing/StudentPayments.php' => _( 'Payments' ),
	'Student_Billing/MassAssignFees.php' => _( 'Mass Assign Fees' ),
	'Student_Billing/MassAssignPayments.php' => _( 'Mass Assign Payments' ),
	1 => _( 'Reports' ),
	'Student_Billing/StudentBalances.php' => _( 'Student Balances' ),
	'Student_Billing/DailyTransactions.php' => _( 'Daily Transactions' ),
	'Student_Billing/Statements.php' => _( 'Print Statements' ),
] + issetVal( $menu['Student_Billing']['admin'], [] );

$menu['Student_Billing']['teacher'] = issetVal( $menu['Student_Billing']['teacher'], [] );

$menu['Student_Billing']['parent'] = [
	'title' => _( 'Student Billing' ),
	'default' => 'Student_Billing/StudentFees.php',
	'Student_Billing/StudentFees.php' => _( 'Fees' ),
	'Student_Billing/StudentPayments.php' => _( 'Payments' ),
	1 => _( 'Reports' ),
	'Student_Billing/DailyTransactions.php' => _( 'Daily Transactions' ),
	// FJ fix bug PDF.
	'Student_Billing/Statements.php&_ROSARIO_PDF' => _( 'Print Statements' ),
] + issetVal( $menu['Student_Billing']['parent'], [] );

$exceptions['Student_Billing'] = [];
