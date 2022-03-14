<?php
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'modules/Accounting/functions.inc.php';

if ( User( 'PROFILE' ) === 'teacher' ) //limit to teacher himself
{
	$_REQUEST['staff_id'] = User( 'STAFF_ID' );
}

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'staff_id', issetVal( $extra ) );
}

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit()
	&& UserStaffID() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( $id !== 'new' )
		{
			$sql = "UPDATE ACCOUNTING_PAYMENTS SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

			DBQuery( $sql );
		}
		elseif ( $columns['AMOUNT'] != ''
			&& $columns['PAYMENT_DATE'] )
		{
			$id = DBSeqNextID( 'accounting_payments_id_seq' );

			$sql = "INSERT INTO ACCOUNTING_PAYMENTS ";

			$fields = 'ID,STAFF_ID,SYEAR,SCHOOL_ID,';
			$values = "'" . $id . "','" . UserStaffID() . "','" . UserSyear() . "','" . UserSchool() . "',";

			if ( isset( $_FILES['FILE_ATTACHED'] ) )
			{
				$columns['FILE_ATTACHED'] = FileUpload(
					'FILE_ATTACHED',
					$FileUploadsPath . UserSyear() . '/staff_' . UserStaffID() . '/',
					FileExtensionWhiteList(),
					0,
					$error
				);

				// Fix SQL error when quote in uploaded file name.
				$columns['FILE_ATTACHED'] = DBEscapeString( $columns['FILE_ATTACHED'] );
			}

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					if ( $column == 'AMOUNT' )
					{
						$value = preg_replace( '/[^0-9.-]/', '', $value );

						//FJ fix SQL bug invalid amount

						if ( ! is_numeric( $value ) )
						{
							$value = 0;
						}
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
	if ( DeletePrompt( _( 'Payment' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM ACCOUNTING_PAYMENTS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM ACCOUNTING_PAYMENTS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$payments_total = 0;

	$functions = [
		'REMOVE' => '_makePaymentsRemove',
		'AMOUNT' => '_makePaymentsAmount',
		'PAYMENT_DATE' => 'ProperDate',
		'COMMENTS' => '_makePaymentsCommentsInput',
		'FILE_ATTACHED' => '_makePaymentsFileInput',
	];

	$payments_RET = DBGet( "SELECT '' AS REMOVE,ID,AMOUNT,PAYMENT_DATE,COMMENTS,FILE_ATTACHED
		FROM ACCOUNTING_PAYMENTS
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY ID", $functions );

	$i = 1;
	$RET = [];

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i] = $payment;
		$i++;
	}

	$columns = [];

	if ( ! empty( $RET )
		&& empty( $_REQUEST['print_statements'] )
		&& AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = [ 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' ];
	}

	$columns += [
		'AMOUNT' => _( 'Amount' ),
		'PAYMENT_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
	];

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$columns += [ 'FILE_ATTACHED' => _( 'File Attached' ) ];
	}

	$link = [];

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		$link['add']['html'] = [
			'REMOVE' => button( 'add' ),
			'AMOUNT' => _makePaymentsTextInput( '', 'AMOUNT' ),
			'PAYMENT_DATE' => _makePaymentsDateInput( DBDate(), 'PAYMENT_DATE' ),
			'COMMENTS' => _makePaymentsCommentsInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makePaymentsFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';
		DrawHeader( '', SubmitButton() );
		$options = [];
	}
	else
	{
		$options = [ 'center' => false, 'add' => false ];
	}

	ListOutput( $RET, $columns, 'Payment', 'Payments', $link, [], $options );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$salaries_total = DBGetOne( "SELECT SUM(f.AMOUNT) AS TOTAL
		FROM ACCOUNTING_SALARIES f
		WHERE f.STAFF_ID='" . UserStaffID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'" );

	$table = '<table class="align-right"><tr><td>' . _( 'Total from Salaries' ) . ': ' . '</td><td>' . Currency( $salaries_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Staff Payments' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
		<td><b>' . Currency(  ( $salaries_total - $payments_total ), 'CR' ) .
		'</b></td></tr></table>';

	DrawHeader( $table );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '</form>';
	}
}
