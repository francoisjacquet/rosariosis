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
				$sql .= DBInsertSQL(
					'billing_fees',
					[
						'SYEAR' => UserSyear(),
						'SCHOOL_ID' => UserSchool(),
						'STUDENT_ID' => (int) $student_id,
						'TITLE' => $_REQUEST['title'],
						'AMOUNT' => $_REQUEST['amount'],
						'ASSIGNED_DATE' => DBDate(),
						'DUE_DATE' => $due_date,
						'COMMENTS' => $_REQUEST['comments'],
						// @since 11.2 Add CREATED_BY column to billing_fees & billing_payments tables
						'CREATED_BY' => DBEscapeString( User( 'NAME' ) ),
					]
				);
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
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';
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
			' type="number" step="0.01" max="999999999999" min="-999999999999" required'
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

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) ];
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Fee to Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}
