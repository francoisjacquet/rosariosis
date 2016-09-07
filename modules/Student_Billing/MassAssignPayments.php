<?php

if ( $_REQUEST['modfunc'] === 'save' )
{
	if (count($_REQUEST['student']) && AllowEdit())
	{
		$date = RequestedDate(
			$_REQUEST['year_date'],
			$_REQUEST['month_date'],
			$_REQUEST['day_date']
		);

		// FJ fix SQL bug invalid amount.
		if ( is_numeric( $_REQUEST['amount'] ) )
		{
			if ( $date )
			{
				foreach ( (array) $_REQUEST['student'] as $student_id => $yes)
				{
					$sql = "INSERT INTO BILLING_PAYMENTS (ID,SYEAR,SCHOOL_ID,STUDENT_ID,PAYMENT_DATE,AMOUNT,COMMENTS)
								values(" . db_seq_nextval( 'BILLING_PAYMENTS_SEQ' ) . ",'" . UserSyear() . "','" . UserSchool() . "','" . $student_id . "','" . $date . "','" . preg_replace( '/[^0-9.-]/', '', $_REQUEST['amount'] ) . "','" . $_REQUEST['comments'] . "')";
					DBQuery($sql);
				}

				$note[] = button('check') .'&nbsp;'._('That payment has been added to the selected students.');
			}
			else
				$error[] = _( 'The date you entered is not valid' );
		}
		else
			$error[] = _( 'Please enter a valid Amount.' );
	}
	else
		$error[] = _( 'You must choose at least one student.' );

	$_SESSION['_REQUEST_vars']['modfunc'] = false;
	$_REQUEST['modfunc'] = false;
}

if ( ! $_REQUEST['modfunc'] )

{
	DrawHeader(ProgramTitle());

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';

		DrawHeader('',SubmitButton(_('Add Payment to Selected Students')));

		echo '<br />';

		PopTable('header', _('Payment'));

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

		PopTable('footer');

		echo '<br />';
	}
}

if ( ! $_REQUEST['modfunc'] )

{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'student\');" /><A>');
	$extra['new'] = true;

	Search('student_id',$extra);

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton(_('Add Payment to Selected Students')) . '</div>';
		echo '</form>';
	}

}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '<input type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y" />';
}
