<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['student'] )
		&& AllowEdit() )
	{
		//FJ fix SQL bug invalid amount

		if ( is_numeric( $_REQUEST['amount'] ) )
		{
			$due_date = RequestedDate( 'due', '' );

			// Group SQL inserts.
			$sql = '';

			foreach ( (array) $_REQUEST['student'] as $student_id )
			{
				$sql .= "INSERT INTO BILLING_FEES (STUDENT_ID,ID,TITLE,AMOUNT,SYEAR,SCHOOL_ID,ASSIGNED_DATE,DUE_DATE,COMMENTS)
					VALUES('" . $student_id . "'," . db_seq_nextval( 'billing_fees_id_seq' ) . ",
					'" . $_REQUEST['title'] . "','" . preg_replace( '/[^0-9.-]/', '', $_REQUEST['amount'] ) . "',
					'" . UserSyear() . "','" . UserSchool() . "','" . DBDate() . "','" . $due_date . "',
					'" . $_REQUEST['comments'] . "');";
			}

			if ( $sql )
			{
				DBQuery( $sql );

				$note[] = button( 'check' ) . '&nbsp;' . _( 'That fee has been added to the selected students.' );
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
		DrawHeader( '', SubmitButton( _( 'Add Fee to Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', _( 'Fee' ) );

		echo '<table><tr><td>' . TextInput(
			'',
			'title',
			_( 'Title' ),
			'required size="20"'
		) . '</td></tr>';

		echo '<tr><td>' . TextInput(
			'',
			'amount',
			_( 'Amount' ),
			' type="number" step="any" required'
		) . '</td></tr>';

		echo '<tr><td>' . DateInput( '', 'due', _( 'Due Date' ), false ) . '</td></tr>';

		echo '<tr><td>' . TextInput(
			'',
			'comments',
			_( 'Comment' ),
			'maxlength="1000" size="25"'
		) . '</td></tr></table>';

		PopTable( 'footer' );

		echo '<br />';
	}

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Fee to Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
