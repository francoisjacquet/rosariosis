<?php
/**
 * Accounting module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['Accounting']['admin'] = [
	'title' => _( 'Accounting' ),
	'default' => 'Accounting/Incomes.php',
	'Accounting/Incomes.php' => _( 'Incomes' ),
	'Accounting/Expenses.php' => _( 'Expenses' ),
	1 => _( 'Staff Payroll' ),
	'Accounting/Salaries.php' => _( 'Salaries' ),
	'Accounting/StaffPayments.php' => _( 'Staff Payments' ),
	2 => _( 'Reports' ),
	'Accounting/DailyTransactions.php' => _( 'Daily Transactions' ),
	'Accounting/StaffBalances.php' => _( 'Staff Balances' ),
	'Accounting/Statements.php' => _( 'Print Statements' ),
	3 => _( 'Setup' ),
	'Accounting/Categories.php' => _( 'Categories' ),
];

$menu['Accounting']['teacher'] = [
	'title' => _( 'Accounting' ),
	'default' => 'Accounting/Salaries.php',
	1 => _( 'Staff Payroll' ),
	'Accounting/Salaries.php' => _( 'Salaries' ),
	'Accounting/StaffPayments.php' => _( 'Staff Payments' ),
	2 => _( 'Reports' ),
	'Accounting/Statements.php&_ROSARIO_PDF' => _( 'Print Statements' ),
];

$menu['Accounting']['parent'] = [];
