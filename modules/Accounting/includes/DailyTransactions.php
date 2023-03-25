<?php
/**
 * Daily Transactions program
 *
 * @package RosarioSIS
 * @subpackage modules
 */

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

if ( User( 'PROFILE' ) === 'admin' )
{
	DrawHeader( _programMenu( 'transactions' ) );
}

echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&program=transactions&accounting=' ) . '" method="GET">';

$header_checkboxes = '<label><input type="checkbox" value="true" name="accounting" id="accounting" ' .
( ((! isset( $_REQUEST['accounting'] ) && empty( $_REQUEST['staff_payroll'] ) && empty( $_REQUEST['student_billing'] ))
	|| $_REQUEST['accounting'] == 'true') ? 'checked ' : '' ) . '/> ' .
_( 'Income' ) . ' & ' . _( 'Expense' ) . '</label>&nbsp; ';

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

DrawHeader( _( 'Report Timeframe' ) . ': ' .
	PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
	PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );


// Clean $_REQUEST['category'] and define only valid answers and fall-backs
$_REQUEST['category'] = AttrEscape( issetVal( $_REQUEST['category'], '' ) );

if ( empty($_REQUEST['category']) || $_REQUEST['category'] === 'common' || $_REQUEST['category'] === 'income' || $_REQUEST['category'] === 'expense' || is_numeric( $_REQUEST['category'] ) ) { }
else
{
	$_REQUEST['category'] = '';
}

// Only show menu for categories if accounting is active
if ( (! isset( $_REQUEST['accounting'] ) && empty( $_REQUEST['staff_payroll'] ) && empty( $_REQUEST['student_billing'] ) )
	|| $_REQUEST['accounting'] == 'true' )
{
	DrawHeader( _( 'Select') . ' ' . _( 'Category' ) . ': ' ._categoryMenu( $_REQUEST['category'] ) );
}
echo '</form>';

// Sort by date since the list is two lists merged and not already properly sorted.
$_REQUEST['LO_sort'] = issetVal( $_REQUEST['LO_sort'], 'DATE' );

// @global $totals.
$totals = [
	'DEBIT' => 0,
	'CREDIT' => 0,
];


$extra['functions'] = [ 'VALUE' => '_makeCurrency', 'DEBIT' => '_makeCurrency', 'CREDIT' => '_makeCurrency', 'DATE' => 'ProperDate' ];

$RET = $value_col = $debit_col = $credit_col = $name_col = [];

// Accounting.

if ( (! isset( $_REQUEST['accounting'] ) && empty( $_REQUEST['staff_payroll'] ) && empty( $_REQUEST['student_billing'] ) )
	|| $_REQUEST['accounting'] == 'true' )
{
	$name_col_sql = '';
	
	$income_SQL = "SELECT " . $name_col_sql . "f.AMOUNT AS CREDIT,'' AS DEBIT,'' AS VALUE,f.TITLE AS FULL_NAME,COALESCE(f.COMMENTS,'') AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID,cat.TITLE AS CATEGORY
	FROM accounting_incomes f
	LEFT JOIN accounting_categories cat on cat.ID = f.CATEGORY_ID
	WHERE f.SCHOOL_ID='" . UserSchool() . "'
	AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'";

	if ( ! empty ( $_REQUEST['category'] ) && ! ( $_REQUEST['category'] == 'common' || $_REQUEST['category'] == 'income' ) ) {
		$income_SQL .= " AND f.CATEGORY_ID='" .$_REQUEST['category'] ."'";
	}

	$RET = DBGet( $income_SQL, $extra['functions'] );

	$payments_SQL = "SELECT " . $name_col_sql . "'' AS CREDIT,p.AMOUNT AS DEBIT,'' AS VALUE,p.TITLE AS FULL_NAME,COALESCE(p.COMMENTS,'') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID,cat.TITLE AS CATEGORY
	FROM accounting_payments p
	LEFT JOIN accounting_categories cat on cat.ID = p.CATEGORY_ID
	WHERE p.SCHOOL_ID='" . UserSchool() . "'
	AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'
	AND STAFF_ID IS NULL";
	
	if ( ! empty ( $_REQUEST['category'] ) && ! ( $_REQUEST['category'] == 'common' || $_REQUEST['category'] == 'expense' ) ) {
		$payments_SQL .= " AND p.CATEGORY_ID='" .$_REQUEST['category'] ."'";
	}

	$payments_RET = DBGet( $payments_SQL, $extra['functions'] );

	$i = count( $RET ) + 1;

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i++] = $payment;
	}

	$name_col[] = _( 'Title');
	$credit_col[] = _( 'Income' );
	$debit_col[] = _( 'Expense' );
}

// Staff salaries.

if ( ! empty( $_REQUEST['staff_payroll'] ) )
{
	
	$name_col_sql = '';
	
	$salaries_SQL = "SELECT " . $name_col_sql . "'' AS CREDIT,f.AMOUNT AS VALUE,'' AS DEBIT,CONCAT(f.TITLE,' ',COALESCE(f.COMMENTS,'')) AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID,'".DBEscapeString(_( 'Staff Payroll' ))."' AS CATEGORY,".DisplayNameSQL( 's' )." AS FULL_NAME
	FROM accounting_salaries f
	LEFT JOIN staff s on s.STAFF_ID = f.STAFF_ID
	WHERE f.SCHOOL_ID='" . UserSchool() . "'
	AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'";

	$salaries_RET = DBGet( $salaries_SQL, $extra['functions'] );

	$i = count( $RET ) + 1;

	foreach ( (array) $salaries_RET as $salary )
	{
		$RET[$i++] = $salary;
	}

	$staff_payments_SQL = "SELECT " . $name_col_sql . "'' AS CREDIT,p.AMOUNT AS DEBIT,'' AS VALUE,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID,'".DBEscapeString(_( 'Staff Payroll' ))."' AS CATEGORY,".DisplayNameSQL( 's' )." AS FULL_NAME
	FROM accounting_payments p
	LEFT JOIN staff s on s.STAFF_ID = p.STAFF_ID
	WHERE p.SCHOOL_ID='" . UserSchool() . "'
	AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
	AND '" . $end_date . "'
	AND p.STAFF_ID IS NOT NULL";

	$staff_payments_RET = DBGet( $staff_payments_SQL, $extra['functions'] );

	$i = count( $RET ) + 1;

	foreach ( (array) $staff_payments_RET as $staff_payment )
	{
		$RET[$i++] = $staff_payment;
	}

	$name_col[] = _( 'Staff' );
	$value_col[] = _( 'Salary' );
	$debit_col[] = _( 'Staff Payment' );
}

// Student Billing.

if ( ! empty( $_REQUEST['student_billing'] )
	&& $RosarioModules['Student_Billing'] )
{
	$fees_extra = $extra;

	// Fix PostgreSQL error ORDER BY "full_name" is ambiguous
	$name_col_sql = DisplayNameSQL( 's' ) . " AS FULL_NAME,";

	$fees_extra['ORDER_BY'] = 'FULL_NAME';

	$fees_extra['SELECT_ONLY'] = issetVal( $fees_extra['SELECT_ONLY'], '' );
	$fees_extra['FROM'] = issetVal( $fees_extra['FROM'], '' );
	$fees_extra['WHERE'] = issetVal( $fees_extra['WHERE'], '' );

	$fees_extra['SELECT_ONLY'] .= $name_col_sql . "f.AMOUNT AS VALUE,'' AS DEBIT,'' AS CREDIT,CONCAT(f.TITLE,' ',COALESCE(f.COMMENTS,'')) AS EXPLANATION,f.ASSIGNED_DATE AS DATE,f.ID AS ID,'".DBEscapeString(_( 'Student Billing' ))."' AS CATEGORY";

	$fees_extra['FROM'] .= ',billing_fees f';

	$fees_extra['WHERE'] .= " AND f.STUDENT_ID=s.STUDENT_ID AND f.SCHOOL_ID=ssm.SCHOOL_ID AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	$fees_RET = GetStuList( $fees_extra );

	$i = count( $RET ) + 1;

	foreach ( (array) $fees_RET as $fee )
	{
		$RET[$i++] = $fee;
	}

	$student_payments_extra = $extra;

	$student_payments_extra['SELECT_ONLY'] = issetVal( $student_payments_extra['SELECT_ONLY'], '' );
	$student_payments_extra['FROM'] = issetVal( $student_payments_extra['FROM'], '' );
	$student_payments_extra['WHERE'] = issetVal( $student_payments_extra['WHERE'], '' );

	$student_payments_extra['SELECT_ONLY'] .= $name_col_sql . "'' AS DEBIT,p.AMOUNT AS CREDIT,'' AS VALUE,COALESCE(p.COMMENTS,' ') AS EXPLANATION,p.PAYMENT_DATE AS DATE,p.ID AS ID,'".DBEscapeString(_( 'Student Billing' ))."' AS CATEGORY";

	$student_payments_extra['FROM'] .= ',billing_payments p';

	$student_payments_extra['WHERE'] .= " AND p.STUDENT_ID=s.STUDENT_ID AND p.SCHOOL_ID=ssm.SCHOOL_ID AND p.PAYMENT_DATE BETWEEN '" . $start_date . "' AND '" . $end_date . "'";

	// Fix PostgreSQL error ORDER BY "full_name" is ambiguous
	$student_payments_extra['ORDER_BY'] = 'FULL_NAME';

	$student_payments_RET = GetStuList( $student_payments_extra );

	$i = count( $RET ) + 1;

	foreach ( (array) $student_payments_RET as $student_payment )
	{
		$RET[$i++] = $student_payment;
	}

	$name_col[] = _( 'Student' );
	$value_col[] = _( 'Fee' );
	$credit_col[] = _( 'Student Payment' );
}

$name_col = implode( ' / ', $name_col );
$value_col = implode( ' / ', $value_col );
$credit_col = implode( ' / ', $credit_col );
$debit_col = implode( ' / ', $debit_col );

$columns = [
	'DATE' => _( 'Date' ),
	'FULL_NAME' => ( empty( $name_col ) ? _( 'Total' ) : $name_col ),
	'CATEGORY' => _( 'Category' ),
];

$link['add']['html'] = [
	'DATE' => '&nbsp;',
	'FULL_NAME' => ( empty( $name_col ) ? '' : _( 'Total' ) . ': ' ) .
		'<b>' . Currency( $totals['CREDIT'] - $totals['DEBIT'] ) . '</b>',
];

if ( isset( $_REQUEST['staff_payroll'] ) || isset( $_REQUEST['student_billing'] ) )
{
	$columns = $columns + [
		'VALUE' => $value_col,
	];

	$link['add']['html'] = $link['add']['html'] + [
		'VALUE' => '<b>' . Currency( $totals['VALUE'] ) . '</b>',
	];
}

if ( (! isset( $_REQUEST['accounting'] ) && empty( $_REQUEST['staff_payroll'] ) && empty( $_REQUEST['student_billing'] ) )
	|| $_REQUEST['accounting'] == 'true' 
	|| isset( $_REQUEST['student_billing'] ))
{
	$columns = $columns + [
		'CREDIT' => $credit_col,
	];

	$link['add']['html'] = $link['add']['html'] + [
		'CREDIT' => '<b>' . Currency( $totals['CREDIT'] ) . '</b>',
	];
}

if ( (! isset( $_REQUEST['accounting'] ) && empty( $_REQUEST['staff_payroll'] ) && empty( $_REQUEST['student_billing'] ) )
	|| $_REQUEST['accounting'] == 'true' 
	|| isset( $_REQUEST['staff_payroll'] ))
{
	$columns = $columns + [
		'DEBIT' => $debit_col,
	];
	
	$link['add']['html'] = $link['add']['html'] + [
		'DEBIT' => '<b>' . Currency( $totals['DEBIT'] ) . '</b>',
	];
}

$columns = $columns + [
	'EXPLANATION' => _( 'Comment' ),
];

$link['add']['html'] = $link['add']['html'] + [
	'EXPLANATION' => '&nbsp;',
];

ListOutput( $RET, $columns, 'Transaction', 'Transactions', $link );

/**
 * @param $value
 * @param $column
 */
function _makeCurrency( $value, $column )
{
	global $totals;

	$totals[$column] += (float) $value;

	if ( ! empty( $value ) || $value == '0' )
	{
		return Currency( $value );
	}
}

/**
 * Category Menu
 *
 * Local function
 * *
 * @param  string $category Category: lookup in category table.
 *
 * @return string           Select Category input.
 */
function _categoryMenu( $category )
{
	global $_ROSARIO;

	//Temporary AllowEdit so menu can be viewed in read-only
	if ( ! AllowEdit() )
	{
		$_ROSARIO['allow_edit'] = true;

		$allow_edit_tmp = true;
	}

	$link = PreparePHP_SELF(
		[],
		[ 'category' ]
	) . '&category=';

	// Build options menu	
	$category_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME,TYPE
		FROM accounting_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY TYPE,SORT_ORDER" );

	$available_categories = [];	
	$available_categories[''] = _( 'All' ) . ' ' . _( 'Category' );
	$available_categories['dash'] = '---';
	
	$previous_category = -1;
	
	foreach ( (array) $category_RET as $category_a )
	{
		if ($previous_category < $category_a['TYPE']) {
			$previous_category = $category_a['TYPE'];
			if ($previous_category == 0) {
			    $available_categories['common'] = _( 'Incomes' ) . ' & ' . _( 'Expenses' );
			} elseif ($previous_category == 1) {
				$available_categories['income'] = _( 'Incomes' );
			} elseif ($previous_category == 2) {
				$available_categories['expense'] = _( 'Expenses' );
			}
		}
		$available_categories[$category_a['ID']] = '- '.$category_a['TITLE'];
	}
	
	$menu = SelectInput(
		$category,
		'category',
		'',
		$available_categories,
		false,
		'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $link ) . ' + this.value);' ) . '" autocomplete="off"',
		false
	);

	//Temporary AllowEdit removed
	if ( ! empty( $allow_edit_tmp ) )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	return $menu;
}

