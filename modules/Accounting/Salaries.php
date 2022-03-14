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

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit()
	&& UserStaffID() )
{
	// Add eventual Dates to $_REQUEST['values'].
	AddRequestedDates( 'values', 'post' );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( $id !== 'new' )
		{
			$sql = "UPDATE ACCOUNTING_SALARIES SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE STAFF_ID='" . UserStaffID() . "' AND ID='" . $id . "'";
			DBQuery( $sql );
		}

		// New: check for Title
		elseif ( $columns['TITLE'] )
		{
			$sql = "INSERT INTO ACCOUNTING_SALARIES ";

			$fields = 'ID,STAFF_ID,SCHOOL_ID,SYEAR,ASSIGNED_DATE,';
			$values = db_seq_nextval( 'accounting_salaries_id_seq' ) . ",'" . UserStaffID() . "','" . UserSchool() . "','" . UserSyear() . "','" . DBDate() . "',";

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

echo ErrorMessage( $error );

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Salary' ) ) )
	{
		$file_attached = DBGetOne( "SELECT FILE_ATTACHED
			FROM ACCOUNTING_SALARIES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		if ( ! empty( $file_attached )
			&& file_exists( $file_attached ) )
		{
			// Delete File Attached.
			unlink( $file_attached );
		}

		DBQuery( "DELETE FROM ACCOUNTING_SALARIES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$salaries_total = 0;
	$functions = [
		'REMOVE' => '_makeSalariesRemove',
		'ASSIGNED_DATE' => 'ProperDate',
		'DUE_DATE' => '_makeSalariesDateInput',
		'COMMENTS' => '_makeSalariesTextInput',
		'AMOUNT' => '_makeSalariesAmount',
		'FILE_ATTACHED' => '_makeSalariesFileInput',
	];

	$salaries_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,f.DUE_DATE,f.COMMENTS,
		f.AMOUNT,f.FILE_ATTACHED
		FROM ACCOUNTING_SALARIES f
		WHERE f.STAFF_ID='" . UserStaffID() . "'
		AND f.SYEAR='" . UserSyear() . "'
		AND f.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY f.ASSIGNED_DATE",
		$functions
	);

	$i = 1;
	$RET = [];

	foreach ( (array) $salaries_RET as $salary )
	{
		$RET[$i] = $salary;
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
		'TITLE' => _( 'Salary' ),
		'AMOUNT' => _( 'Amount' ),
		'ASSIGNED_DATE' => _( 'Assigned' ),
		'DUE_DATE' => _( 'Due' ),
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
			'TITLE' => _makeSalariesTextInput( '', 'TITLE' ),
			'AMOUNT' => _makeSalariesTextInput( '', 'AMOUNT' ),
			'ASSIGNED_DATE' => ProperDate( DBDate() ),
			'DUE_DATE' => _makeSalariesDateInput( '', 'DUE_DATE' ),
			'COMMENTS' => _makeSalariesTextInput( '', 'COMMENTS' ),
			'FILE_ATTACHED' => _makeSalariesFileInput( '', 'FILE_ATTACHED' ),
		];
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="POST">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = [];
	}
	else
	{
		$options = [ 'center' => false ];
	}

	ListOutput( $RET, $columns, 'Salary', 'Salaries', $link, [], $options );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$payments_total = DBGetOne( "SELECT SUM(p.AMOUNT) AS TOTAL
			FROM ACCOUNTING_PAYMENTS p
			WHERE p.STAFF_ID='" . UserStaffID() . "'
			AND p.SYEAR='" . UserSyear() . "'
			AND p.SCHOOL_ID='" . UserSchool() . "'" );

		$table = '<table class="align-right"><tr><td>' . _( 'Total from Salaries' ) . ': ' . '</td><td>' . Currency( $salaries_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Staff Payments' ) . ': ' . '</td><td>' . Currency( $payments_total ) . '</td></tr>';

		$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
			<td><b>' . Currency(  ( $salaries_total - $payments_total ), 'CR' ) .
			'</b></td></tr></table>';

		DrawHeader( $table );

		echo '</form>';
	}
}
