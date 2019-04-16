<?php
DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&accounting=" method="GET">';

$header_checkboxes = '<label><input type="checkbox" value="true" name="accounting" id="accounting" ' .
( ! isset( $_REQUEST['accounting'] )
	|| $_REQUEST['accounting'] == 'true' ? 'checked ' : '' ) . '/> ' .
_( 'Expense' ) . ' & ' . _( 'Income' ) . '</label>&nbsp; ';

$header_checkboxes .= '<label><input type="checkbox" value="true" name="staff_payroll" id="staff_payroll" ' .
( ! empty( $_REQUEST['staff_payroll'] ) ? 'checked ' : '' ) . '/> ' .
_( 'Staff Payroll' ) . '</label>&nbsp; ';

if ( $RosarioModules['Student_Billing'] )
{
	$header_checkboxes .= '<label><input type="checkbox" value="true" name="student_billing" id="student_billing" ' .
	( ! empty( $_REQUEST['student_billing'] ) ? 'checked ' : '' ) . '/> ' .
	_( 'Student Billing' ) . '</label>';
}

DrawHeader( $header_checkboxes, '' );

DrawHeader( '<b>' . _( 'Report Timeframe' ) . ': </b>' .
	PrepareDate( $start_date, '_start', false ) . ' - ' .
	PrepareDate( $end_date, '_end', false ), SubmitButton( _( 'Go' ) ) );

echo '</form>';

// sort by date since the list is two lists merged and not already properly sorted

if ( empty( $_REQUEST['LO_sort'] ) )
{
	$_REQUEST['LO_sort'] = 'DATE';
}

$extra['functions'] = array( 'DEBIT' => '_makeCurrency', 'CREDIT' => '_makeCurrency', 'DATE' => 'ProperDate' );

$RET = $debit_col = $credit_col = $name_col = array();

// Accounting.

if ( ! isset( $_REQUEST['accounting'] )
	|| $_REQUEST['accounting'] == 'true' )
{
	$name_col_sql = '';

	if ( isset( $_REQUEST['staff_payroll'] ) || isset( $_REQUEST['student_billing'] ) )
	{
		$name_col_sql = "'' AS FULL_NAME,";
	}

	$RET = DBGet( "SELECT " . $name_col_sql . "f.AMOUNT AS CREDIT,'' AS DEBIT,f.TITLE||' '||COALESCE(f.COMMENTS,' ') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID
	FROM ACCOUNTING_INCOMES f
	WHERE f.SYEAR='" . UserSyear() . "'
	AND f.SCHOOL_ID='" . UserSchool() . "'
	AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'", $extra['functions'] );

	$payments_SQL = "SELECT " . $name_col_sql . "'' AS CREDIT,p.AMOUNT AS DEBIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID
	FROM ACCOUNTING_PAYMENTS p
	WHERE p.SYEAR='" . UserSyear() . "'
	AND p.SCHOOL_ID='" . UserSchool() . "'
	AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'
	AND STAFF_ID IS NULL";

	$payments_RET = DBGet( $payments_SQL, $extra['functions'] );

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[] = $payment;
	}

	$debit_col[] = _( 'Expense' );
	$credit_col[] = _( 'Income' );
}

// Staff salaries.

if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	$salaries_extra = $extra;
	$name_col_sql = '';

	if ( isset( $_REQUEST['staff_payroll'], $_REQUEST['student_billing'] ) )
	{
		$name_col_sql = ",'' AS STUDENT_NAME";
	}

	$salaries_extra['SELECT'] .= $name_col_sql . ",'' AS DEBIT,f.AMOUNT AS CREDIT,f.TITLE||' '||COALESCE(f.COMMENTS,' ') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID";
	$salaries_extra['FROM'] .= ',ACCOUNTING_SALARIES f';
	$salaries_extra['WHERE'] .= " AND f.STAFF_ID=s.STAFF_ID
		AND f.SYEAR=s.SYEAR
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$salaries_RET = GetStaffList( $salaries_extra );

	foreach ( (array) $salaries_RET as $salary )
	{
		$RET[] = $salary;
	}

	$staff_payments_extra = $extra;
	$staff_payments_extra['SELECT'] .= ",'' AS CREDIT,p.AMOUNT AS DEBIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID";
	$staff_payments_extra['FROM'] .= ',ACCOUNTING_PAYMENTS p';
	$staff_payments_extra['WHERE'] .= " AND p.STAFF_ID=s.STAFF_ID
		AND p.SYEAR=s.SYEAR
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$staff_payments_RET = GetStaffList( $staff_payments_extra );

	foreach ( (array) $staff_payments_RET as $staff_payment )
	{
		$RET[] = $staff_payment;
	}

	$debit_col[] = _( 'Staff Payment' );
	$credit_col[] = _( 'Salary' );
	$name_col = _( 'Staff' );
}

// Student Billing.

if ( ! empty( $_REQUEST['student_billing'] )
	&& $RosarioModules['Student_Billing'] )
{
	$fees_extra = $extra;
	$name_col_sql = '';

	if ( isset( $_REQUEST['staff_payroll'], $_REQUEST['student_billing'] ) )
	{
		$name_col_sql = "," . DisplayNameSQL() . " AS STUDENT_NAME, '' AS FULL_NAME";
	}

	$fees_extra['SELECT'] .= $name_col_sql . ",f.AMOUNT AS DEBIT,'' AS CREDIT,f.TITLE||' '||COALESCE(f.COMMENTS,' ') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID";
	$fees_extra['FROM'] .= ',BILLING_FEES f';
	$fees_extra['WHERE'] .= " AND f.STUDENT_ID=s.STUDENT_ID AND f.SYEAR=ssm.SYEAR AND f.SCHOOL_ID=ssm.SCHOOL_ID AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$fees_RET = GetStuList( $fees_extra );

	foreach ( (array) $fees_RET as $fee )
	{
		$RET[] = $fee;
	}

	$student_payments_extra = $extra;
	$student_payments_extra['SELECT'] .= $name_col_sql . ",'' AS DEBIT,p.AMOUNT AS CREDIT,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID";
	$student_payments_extra['FROM'] .= ',BILLING_PAYMENTS p';
	$student_payments_extra['WHERE'] .= " AND p.STUDENT_ID=s.STUDENT_ID AND p.SYEAR=ssm.SYEAR AND p.SCHOOL_ID=ssm.SCHOOL_ID AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$student_payments_RET = GetStuList( $student_payments_extra );

	foreach ( (array) $student_payments_RET as $student_payment )
	{
		$RET[] = $student_payment;
	}

	$debit_col[] = _( 'Fee' );
	$credit_col[] = _( 'Student Payment' );
}

$debit_col = implode( ' / ', $debit_col );
$credit_col = implode( ' / ', $credit_col );

$columns = array( 'FULL_NAME' => ( empty( $name_col ) ? _( 'Total' ) : $name_col ) );

if ( isset( $_REQUEST['staff_payroll'], $_REQUEST['student_billing'] ) )
{
	$columns['STUDENT_NAME'] = _( 'Student' );
}

$columns = $columns + array( 'DEBIT' => $debit_col, 'CREDIT' => $credit_col, 'DATE' => _( 'Date' ), 'EXPLANATION' => _( 'Comment' ) );

$link['add']['html'] = array( 'FULL_NAME' => ( empty( $name_col ) ? '' : _( 'Total' ) . ': ' ) . '<b>' . Currency( $totals['CREDIT'] - $totals['DEBIT'] ) . '</b>' );

if ( isset( $_REQUEST['staff_payroll'], $_REQUEST['student_billing'] ) )
{
	$link['add']['html']['STUDENT_NAME'] = '&nbsp;';
}

$link['add']['html'] = $link['add']['html'] + array( 'DEBIT' => '<b>' . Currency( $totals['DEBIT'] ) . '</b>', 'CREDIT' => '<b>' . Currency( $totals['CREDIT'] ) . '</b>', 'DATE' => '&nbsp;', 'EXPLANATION' => '&nbsp;' );

ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	global $totals;

	$totals[$column] += $value;

	if ( ! empty( $value ) || $value == '0' )
	{
		return Currency( $value );
	}
}
