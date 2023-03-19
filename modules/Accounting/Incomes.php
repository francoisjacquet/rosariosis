<?php

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Accounting/functions.inc.php';

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m' ) . '-01' );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );

$_REQUEST['print_statements'] = issetVal( $_REQUEST['print_statements'], '' );

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );
}

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( $id !== 'new' )
		{
			$sql = "UPDATE accounting_incomes SET ";

			$columns['FILE_ATTACHED'] = _saveIncomesFile( $id );

			if ( ! $columns['FILE_ATTACHED'] )
			{
				unset( $columns['FILE_ATTACHED'] );

				if ( empty( $columns ) )
				{
					// No file, and FILE_ATTACHED was the only column, skip.
					continue;
				}
			}

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
			DBQuery( $sql );
		}
		elseif ( $columns['AMOUNT'] !== ''
			&& $columns['ASSIGNED_DATE'] )
		{
			$sql = "INSERT INTO accounting_incomes ";

			$fields = 'SCHOOL_ID,SYEAR,';
			$values = "'" . UserSchool() . "','" . UserSyear() . "',";

			$columns['FILE_ATTACHED'] = _saveIncomesFile( $id );

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					if ( $column == 'AMOUNT' )
					{
						$value = preg_replace( '/[^0-9.-]/', '', $value );
					}

					$fields .= DBEscapeIdentifier( $column ) . ',';
					$values .= "'" . $value . "',";
					$go = true;
				}
			}

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
				DBQuery( $sql );
			}
		}
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Income' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM accounting_incomes
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM accounting_incomes
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$incomes_total = 0;

	$functions = [
		'REMOVE' => '_makeIncomesRemove',
		'CATEGORY_ID' => '_makeSelectInputCategory',
		'ASSIGNED_DATE' => 'ProperDate',
		'COMMENTS' => '_makeIncomesTextInput',
		'AMOUNT' => '_makeIncomesAmount',
		'FILE_ATTACHED' => '_makeIncomesFileInput',
	];

	$incomes_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.CATEGORY_ID,f.ASSIGNED_DATE,f.COMMENTS,
		f.AMOUNT,f.FILE_ATTACHED
		FROM accounting_incomes f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		AND f.ASSIGNED_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'
		ORDER BY f.ASSIGNED_DATE, ID", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $incomes_RET as $income )
	{
		$RET[$i] = $income;
		$i++;
	}

	$columns = [];

	if ( ! empty( $RET )
		&& ! $_REQUEST['print_statements']
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}

	$columns += [
		'TITLE' => _( 'Income' ),
		'CATEGORY_ID' => _( 'Category' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'TITLE' => _makeIncomesTextInput( '', 'TITLE' ),
			'CATEGORY_ID' => _makeSelectInputCategory('', 'CATEGORY'),
			'AMOUNT' => _makeIncomesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => _makeIncomesDateInput( DBDate(), 'ASSIGNED_DATE' ),
			'COMMENTS' => _makeIncomesTextInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makeIncomesFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '" method="GET">';
	DrawHeader( _( 'Report Timeframe' ) . ': ' .
		PrepareDate( $start_date, '_start', false ) . ' &nbsp; ' . _( 'to' ) . ' &nbsp; ' .
		PrepareDate( $end_date, '_end', false ) . ' ' . Buttons( _( 'Go' ) ) );
	echo '</form>';
	
	if ( ! $_REQUEST['print_statements'] && AllowEdit() )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&' . $_SERVER['QUERY_STRING'] ) . '" method="POST">';
		DrawHeader( '', SubmitButton() );
		$options = [];
	}
	else
	{
		$options = [ 'center' => false, 'add' => false ];
	}

	ListOutput( $RET, $columns, 'Income', 'Incomes', $link, [], $options );

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$incomes_total_unfiltered = DBGetOne( "SELECT SUM(f.AMOUNT) AS TOTAL
		FROM accounting_incomes f
		WHERE f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'" );

	$payments_total_unfiltered = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM accounting_payments p
		WHERE p.STAFF_ID IS NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'" );
		
	$payments_total_filtered = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM accounting_payments p
		WHERE p.STAFF_ID IS NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'
		AND p.PAYMENT_DATE BETWEEN '" . $start_date . "'
		AND '" . $end_date . "'" );

	$table = '<table class="align-right accounting-totals">';
	
	$table .= '<tr><td colspan="2">Balance of this school year:</td></tr><tr><td colspan="2"><hr></td></tr><tr><td>';
	
	$table .='<tr><td>' . _( 'Total from filtered Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from filtered Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total_filtered ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': <b>' . '</b></td><td><b id="update_balance">' . Currency(  ( $incomes_total - $payments_total_filtered ) ) . '</b></td></tr>';

	//add General Balance
	$table .= '<tr><td colspan="2"><hr></td></tr><tr><td>' . _( 'Total from Incomes' ) . ': ' . '</td><td>' . Currency( $incomes_total_unfiltered ) . '</td></tr>';

	if ( $RosarioModules['Student_Billing'] )
	{
		$student_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM billing_payments p
			WHERE p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'" );

		$table .= '<tr><td>& ' . _( 'Total from Student Payments' ) . ': ' . '</td><td>' . Currency( $student_payments_total ) . '</td></tr>';
	}
	else
	{
		$student_payments_total = 0;
	}

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Expenses' ) . ': ' . '</td><td>' . Currency( $payments_total_unfiltered ) . '</td></tr>';

	$staff_payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
		FROM accounting_payments p
		WHERE p.STAFF_ID IS NOT NULL
		AND p.SYEAR='" . UserSyear() . "'
		AND p.SCHOOL_ID='" . UserSchool() . "'" );

	$table .= '<tr><td>& ' . _( 'Total from Staff Payments' ) . ': ' . '</td><td>' . Currency( $staff_payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'General Balance' ) . ': </td>
		<td><b id="update_balance">' . Currency(  ( $incomes_total_unfiltered + $student_payments_total - $payments_total_unfiltered - $staff_payments_total ) ) .
		'</b></td></tr></table>';

	DrawHeader( $table );

	if ( ! $_REQUEST['print_statements']
		&& AllowEdit() )
	{
		echo '</form>';
	}
}

/**
 * @param $value
 * @param $name
 */
function _makeSelectInputCategory( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}
	
	//TYPE: common=0; income=1; expense=2
	$category_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
		FROM accounting_categories
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND ( TYPE='0' OR TYPE='1' )
		ORDER BY SORT_ORDER" );
	
	$options = [];
	
	foreach ( (array) $category_RET as $category )
	{
		$options[$category['ID']] = $category['SHORT_NAME'];
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$options,
		false
	);
}
