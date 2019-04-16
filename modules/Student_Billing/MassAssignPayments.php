<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['student'] ) && AllowEdit() )
	{
		$date = RequestedDate( 'date', '' );

		// FJ fix SQL bug invalid amount.

		if ( is_numeric( $_REQUEST['amount'] ) )
		{
			if ( $date )
			{
				// Group SQL inserts.
				$sql = '';

				foreach ( (array) $_REQUEST['student'] as $student_id )
				{
					$sql .= "INSERT INTO BILLING_PAYMENTS (ID,SYEAR,SCHOOL_ID,STUDENT_ID,PAYMENT_DATE,AMOUNT,COMMENTS)
						VALUES(" . db_seq_nextval( 'BILLING_PAYMENTS_SEQ' ) . ",'" . UserSyear() . "',
						'" . UserSchool() . "','" . $student_id . "','" . $date . "',
						'" . preg_replace( '/[^0-9.-]/', '', $_REQUEST['amount'] ) . "',
						'" . $_REQUEST['comments'] . "');";
				}

				if ( $sql )
				{
					DBQuery( $sql );

					$note[] = button( 'check' ) . '&nbsp;' . _( 'That payment has been added to the selected students.' );
				}
			}
			else
			{
				$error[] = _( 'The date you entered is not valid' );
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Amount.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Payment to Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Payment' ) );

		echo '<table class="col1-align-right">';

		echo '<tr><td>' . _( 'Payment Amount' ) . '</td>
			<td><input type="text" name="amount" size="5" maxlength="10" required /></td></tr>';

		echo '<tr><td>' . _( 'Date' ) . '</td>
			<td>' . DateInput(
			DBDate(),
			'date',
			'',
			false,
			false
		) . '</td></tr>';

		echo '<tr><td>' . _( 'Comment' ) . '</td>
			<td><input type="text" name="comments" /></td></tr>';

		echo '</table>';

		PopTable( 'footer' );

		echo '<br />';
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Payment to Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
