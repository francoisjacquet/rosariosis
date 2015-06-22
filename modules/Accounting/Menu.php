<?php
$menu['Accounting']['admin'] = array(
	'default' => 'Accounting/Incomes.php',
	'Accounting/Incomes.php' => _( 'Incomes' ),
	'Accounting/Expenses.php' => _( 'Expenses' ),
	'Accounting/DailyTransactions.php' => _( 'Daily Transactions' ),
	1 => _( 'Staff Payroll' ),
	'Accounting/Salaries.php' => _( 'Salaries' ),
	'Accounting/StaffPayments.php' => _( 'Staff Payments' ),
	'Accounting/StaffBalances.php' => _( 'Staff Balances' ),
	'Accounting/Statements.php' => _( 'Print Statements' )
);

$menu['Accounting']['teacher'] = array(
	'default' => 'Accounting/Salaries.php',
	1 => _( 'Staff Payroll' ),
	'Accounting/Salaries.php' => _( 'Salaries' ),
	'Accounting/StaffPayments.php' => _( 'Staff Payments' ),
	'Accounting/Statements.php&_ROSARIO_PDF' => _( 'Print Statements' )
);

$menu['Accounting']['parent'] = array(
);

?>