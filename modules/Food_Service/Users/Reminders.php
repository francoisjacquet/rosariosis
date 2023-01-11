<?php
$food_service_config = ProgramConfig( 'food_service' );

$target = $food_service_config['FOOD_SERVICE_BALANCE_TARGET'][1]['VALUE'];
$warning = $food_service_config['FOOD_SERVICE_BALANCE_WARNING'][1]['VALUE'];
$warning_note = _( 'Your lunch account is getting low.  Please send in at least %P with your reminder slip.  THANK YOU!' );
$negative_note = _( 'You now have a <b>negative balance</b> in your lunch account. Please send in the negative balance plus %T.  THANK YOU!' );
$minimum = $food_service_config['FOOD_SERVICE_BALANCE_MINIMUM'][1]['VALUE'];
$minimum_note = _( 'You now have a <b>negative balance</b> below the allowed minimum.  Please send in the negative balance plus %T.  THANK YOU!' );

if ( ! empty( $_REQUEST['staff_id'] ) )
{
	// Unset staff ID & redirect URL.
	RedirectURL( 'staff_id' );
}

if ( UserStaffID() )
{
	unset( $_SESSION['staff_id'] );
}

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$staffs = DBGet( "SELECT s.FIRST_NAME," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			s.PROFILE,fsa.STATUS,fsa.BALANCE,s.STAFF_ID
			FROM staff s,food_service_staff_accounts fsa
			WHERE s.STAFF_ID IN (" . $st_list . ")
			AND fsa.STAFF_ID=s.STAFF_ID
			AND s.SYEAR='" . UserSyear() . "'" );

		$handle = PDFStart();

		$reminders_count = 0;

		foreach ( (array) $staffs as $staff )
		{
			$payment = $target - $staff['BALANCE'];

			if ( $payment < 0 )
			{
				continue;
			}

			if ( $staff['BALANCE'] < $minimum )
			{
				$note = $minimum_note;
			}
			elseif ( $staff['BALANCE'] < 0 )
			{
				$note = $negative_note;
			}
			elseif ( $staff['BALANCE'] < $warning )
			{
				$note = $warning_note;
			}
			else
			{
				continue;
			}

			if ( $reminders_count++ % 3 === 0 )
			{
				// 3 per page, insert page break.
				echo '<div style="page-break-after: always;"></div>';
			}

			// @since 9.3 SQL use CAST(X AS char(X)) instead of to_char() for MySQL compatibility
			$last_deposit = DBGet( "SELECT
			(SELECT sum(AMOUNT) FROM food_service_staff_transaction_items WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,
			CAST(fst.TIMESTAMP AS char(10)) AS DATE
			FROM food_service_staff_transactions fst
			WHERE fst.SHORT_NAME='DEPOSIT'
			AND fst.STAFF_ID='" . (int) $staff['STAFF_ID'] . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY fst.TRANSACTION_ID DESC LIMIT 1", [ 'DATE' => 'ProperDate' ] );
			$last_deposit = $last_deposit[1];

			$staff['SCHOOL_TITLE'] = SchoolInfo( 'TITLE' );

			FoodServiceReminderOutput( $staff, $target, $last_deposit, $payment, $note );

			if ( $reminders_count % 3 !== 0 )
			{
				// 3 per page, insert spaces & horizontal ruler.
				echo '<br /><br /><hr><br /><br />';
			}
		}

		PDFStop( $handle );
	}
	else
	{
		BackPrompt( _( 'You must choose at least one user' ) );
	}
}

if ( ! $_REQUEST['modfunc'] || $_REQUEST['search_modfunc'] === 'list' )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true' ) . '" method="POST">';
		DrawHeader( '', SubmitButton( _( 'Create Reminders for Selected Users' ) ) );
	}

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX";
	$extra['functions'] = [ 'CHECKBOX' => '_makeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) ];
	$extra['new'] = true;
	$extra['options']['search'] = false;

	StaffWidgets( 'fsa_balance_warning' );
	StaffWidgets( 'fsa_status' );
	StaffWidgets( 'fsa_exists_Y' );

	$status = DBEscapeString( _( 'Active' ) );

	// Fix MySQL 5.6 syntax error when WHERE without FROM clause, use dual table
	$extra['SELECT'] .= ",coalesce(fsa.STATUS,'" . $status . "') AS STATUS,fsa.BALANCE
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < '" . $warning . "' AND fsa.BALANCE >= 0) AS WARNING
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < 0 AND fsa.BALANCE >= '" . $minimum . "') AS NEGATIVE
		,(SELECT 'Y' FROM dual WHERE fsa.BALANCE < '" . $minimum . "') AS MINIMUM";

	if ( ! mb_strpos( $extra['FROM'], 'fsa' ) )
	{
		$extra['FROM'] .= ',food_service_staff_accounts fsa';
		$extra['WHERE'] .= ' AND fsa.STAFF_ID=s.STAFF_ID';
	}

	$extra['functions'] += [
		'BALANCE' => 'red',
		'WARNING' => 'x',
		'NEGATIVE' => 'x',
		'MINIMUM' => 'x',
	];

	$extra['columns_after'] = [
		'BALANCE' => _( 'Balance' ),
		'STATUS' => _( 'Status' ),
		'WARNING' => _( 'Warning' ) . ' &lt;' . $warning,
		'NEGATIVE' => _( 'Negative' ),
		'MINIMUM' => _( 'Minimum' ) . ' ' . $minimum,
	];

	Search( 'staff_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Reminders for Selected Users' ) ) . '</div>';
		echo '</form>';
	}
}
