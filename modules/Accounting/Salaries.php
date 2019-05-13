<?php

require_once 'modules/Accounting/functions.inc.php';

if ( User( 'PROFILE' ) === 'teacher' ) //limit to teacher himself
{
	$_REQUEST['staff_id'] = User( 'STAFF_ID' );
}

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'staff_id', $extra );
}

if ( $_REQUEST['values']
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
			$values = db_seq_nextval( 'ACCOUNTING_SALARIES_SEQ' ) . ",'" . UserStaffID() . "','" . UserSchool() . "','" . UserSyear() . "','" . DBDate() . "',";

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
	if ( DeletePrompt( _( 'Salary' ) ) )
	{
		DBQuery( "DELETE FROM ACCOUNTING_SALARIES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( UserStaffID() && ! $_REQUEST['modfunc'] )
{
	$salaries_total = 0;
	$functions = array( 'REMOVE' => '_makeSalariesRemove', 'ASSIGNED_DATE' => 'ProperDate', 'DUE_DATE' => '_makeSalariesDateInput', 'COMMENTS' => '_makeSalariesTextInput', 'AMOUNT' => '_makeSalariesAmount' );
	$salaries_RET = DBGet( "SELECT '' AS REMOVE,f.ID,f.TITLE,f.ASSIGNED_DATE,f.DUE_DATE,f.COMMENTS,f.AMOUNT FROM ACCOUNTING_SALARIES f WHERE f.STAFF_ID='" . UserStaffID() . "' AND f.SYEAR='" . UserSyear() . "' AND f.SCHOOL_ID='" . UserSchool() . "' ORDER BY f.ASSIGNED_DATE", $functions );
	$i = 1;
	$RET = array();

	foreach ( (array) $salaries_RET as $salary )
	{
		$RET[$i] = $salary;
		$i++;
	}

	if ( ! empty( $RET ) && ! $_REQUEST['print_statements'] && AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$columns = array( 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' );
	}
	else
	{
		$columns = array();
	}

	$columns += array( 'TITLE' => _( 'Salary' ), 'AMOUNT' => _( 'Amount' ), 'ASSIGNED_DATE' => _( 'Assigned' ), 'DUE_DATE' => _( 'Due' ), 'COMMENTS' => _( 'Comment' ) );

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		$link['add']['html'] = array( 'REMOVE' => button( 'add' ), 'TITLE' => _makeSalariesTextInput( '', 'TITLE' ), 'AMOUNT' => _makeSalariesTextInput( '', 'AMOUNT' ), 'ASSIGNED_DATE' => ProperDate( DBDate() ), 'DUE_DATE' => _makeSalariesDateInput( '', 'DUE_DATE' ), 'COMMENTS' => _makeSalariesTextInput( '', 'COMMENTS' ) );
	}

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = array();
	}
	else
	{
		$options = array( 'center' => false );
	}

	ListOutput( $RET, $columns, 'Salary', 'Salaries', $link, array(), $options );

	if ( ! $_REQUEST['print_statements'] && AllowEdit() )
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
