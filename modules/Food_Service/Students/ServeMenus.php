<?php

Widgets( 'fsa_status_active' );
Widgets( 'fsa_barcode' );

Search( 'student_id', $extra );

if ( $_REQUEST['modfunc'] === 'submit' )
{
	if ( ! empty( $_REQUEST['submit']['cancel'] ) )
	{
		if ( DeletePrompt( _( 'Sale' ), _( 'Cancel' ) ) )
		{
			unset( $_SESSION['FSA_sale'] );

			// Unset modfunc & redirect URL.
			RedirectURL( 'modfunc' );
		}
	}
	elseif ( $_REQUEST['submit']['save']
		&& ! empty( $_SESSION['FSA_sale'] ) )
	{
		$student = DBGet( "SELECT ACCOUNT_ID,DISCOUNT FROM FOOD_SERVICE_STUDENT_ACCOUNTS WHERE STUDENT_ID='" . UserStudentID() . "'" );
		$student = $student[1];

		$items_RET = DBGet( "SELECT DESCRIPTION,SHORT_NAME,PRICE,PRICE_REDUCED,PRICE_FREE FROM FOOD_SERVICE_ITEMS WHERE SCHOOL_ID='" . UserSchool() . "'", array(), array( 'SHORT_NAME' ) );

		// get next transaction id
		$id = DBSeqNextID( 'FOOD_SERVICE_TRANSACTIONS_SEQ' );

		$item_id = 0;

		foreach ( (array) $_SESSION['FSA_sale'] as $item_sn )
		{
			// determine price based on discount
			$price = $items_RET[$item_sn][1]['PRICE'];
			$discount = $student['DISCOUNT'];

			if ( $student['DISCOUNT'] == 'Reduced' )
			{
				if ( $items_RET[$item_sn][1]['PRICE_REDUCED'] != '' )
				{
					$price = $items_RET[$item_sn][1]['PRICE_REDUCED'];
				}
				else
				{
					$discount = '';
				}
			}
			elseif ( $student['DISCOUNT'] == 'Free' )
			{
				if ( $items_RET[$item_sn][1]['PRICE_FREE'] != '' )
				{
					$price = $items_RET[$item_sn][1]['PRICE_FREE'];
				}
				else
				{
					$discount = '';
				}
			}

			$fields = 'ITEM_ID,TRANSACTION_ID,AMOUNT,DISCOUNT,SHORT_NAME,DESCRIPTION';
			$values = "'" . $item_id++ . "','" . $id . "','-" . $price . "','" . $discount . "','" . $items_RET[$item_sn][1]['SHORT_NAME'] . "','" . $items_RET[$item_sn][1]['DESCRIPTION'] . "'";
			$sql = "INSERT INTO FOOD_SERVICE_TRANSACTION_ITEMS (" . $fields . ") values (" . $values . ")";
			DBQuery( $sql );
		}

		$sql1 = "UPDATE FOOD_SERVICE_ACCOUNTS SET TRANSACTION_ID='" . $id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT) FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . $id . "') WHERE ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'";
		$fields = 'TRANSACTION_ID,ACCOUNT_ID,STUDENT_ID,SYEAR,SCHOOL_ID,DISCOUNT,BALANCE,TIMESTAMP,SHORT_NAME,DESCRIPTION,SELLER_ID';
		$values = "'" . $id . "','" . $student['ACCOUNT_ID'] . "','" . UserStudentID() . "','" . UserSyear() . "','" . UserSchool() . "','" . $discount . "',(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID='" . $student['ACCOUNT_ID'] . "'),CURRENT_TIMESTAMP,'" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "','" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . ' - ' . DBDate() . "','" . User( 'STAFF_ID' ) . "'";
		$sql2 = "INSERT INTO FOOD_SERVICE_TRANSACTIONS (" . $fields . ") values (" . $values . ")";
		DBQuery( 'BEGIN; ' . $sql1 . '; ' . $sql2 . '; COMMIT' );

		unset( $_SESSION['FSA_sale'] );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}

	// Unset submit & redirect URL.
	RedirectURL( 'submit' );
}

if ( UserStudentID() && ! $_REQUEST['modfunc'] )
{
	$student = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	fsa.ACCOUNT_ID,fsa.STATUS,fsa.DISCOUNT,fsa.BARCODE,
	(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fsa.ACCOUNT_ID) AS BALANCE
	FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fsa
	WHERE s.STUDENT_ID='" . UserStudentID() . "'
	AND fsa.STUDENT_ID=s.STUDENT_ID" );

	$student = $student[1];

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=submit&menu_id=' . $_REQUEST['menu_id'] . '" method="POST">';

	DrawHeader(
		'',
		SubmitButton( _( 'Cancel Sale' ), 'submit[cancel]', '' ) . // No .primary button class.
		SubmitButton( _( 'Complete Sale' ), 'submit[save]' )
	);

	echo '</form>';

	DrawHeader( NoInput( $student['FULL_NAME'], '&nbsp;' . $student['STUDENT_ID'] ), '', NoInput( red( $student['BALANCE'] ), _( 'Balance' ) ) );

	if ( $student['BALANCE'] != '' )
	{
		echo '<table class="width-100p">';
		echo '<tr class="st"><td class="width-100p valign-top">';

		$RET = DBGet( 'SELECT fsti.DESCRIPTION,fsti.AMOUNT FROM FOOD_SERVICE_TRANSACTIONS fst,FOOD_SERVICE_TRANSACTION_ITEMS fsti WHERE fst.ACCOUNT_ID=\'' . $student['ACCOUNT_ID'] . '\' AND fst.STUDENT_ID=\'' . UserStudentID() . '\' AND fst.SYEAR=\'' . UserSyear() . '\' AND fst.SHORT_NAME=\'' . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . '\' AND fst.TIMESTAMP BETWEEN CURRENT_DATE AND \'tomorrow\' AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID' );

		$columns = array( 'DESCRIPTION' => _( 'Item' ), 'AMOUNT' => _( 'Amount' ) );
		$singular = sprintf( _( 'Earlier %s Sale' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
		$plural = sprintf( _( 'Earlier %s Sales' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
		ListOutput( $RET, $columns, $singular, $plural, $link, false, array( 'save' => false, 'search' => false ) );

		// IMAGE
		//FJ fix error Warning: fclose() expects parameter 1 to be resource, boolean given

		if ( file_exists( $picture = $StudentPicturesPath . UserSyear() . '/' . UserStudentID() . '.jpg' ) || file_exists( $picture = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . UserStudentID() . '.jpg' ) )
		{
			echo '</td><td rowspan="2"><img src="' . $picture . '" width="150" />';
		}

		echo '</td></tr>';
		echo '<tr><td class="width-100p valign-top">';

		$items_RET = DBGet( "SELECT fsi.SHORT_NAME,fsi.DESCRIPTION,fsi.PRICE,fsi.PRICE_REDUCED,fsi.PRICE_FREE,fsi.ICON
		FROM FOOD_SERVICE_ITEMS fsi,FOOD_SERVICE_MENU_ITEMS fsmi
		WHERE fsmi.MENU_ID='" . $_REQUEST['menu_id'] . "'
		AND fsi.ITEM_ID=fsmi.ITEM_ID
		AND fsmi.CATEGORY_ID IS NOT NULL
		AND fsi.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY fsi.SORT_ORDER", array( 'ICON' => 'makeIcon' ), array( 'SHORT_NAME' ) );
		$items = array();

		foreach ( (array) $items_RET as $sn => $item )
		{
			$items += array( $sn => $item[1]['DESCRIPTION'] );
		}

		$LO_ret = array( array() );
//FJ fix error Warning: Invalid argument supplied for foreach()

		if ( isset( $_SESSION['FSA_sale'] ) && is_array( $_SESSION['FSA_sale'] ) )
		{
			foreach ( (array) $_SESSION['FSA_sale'] as $id => $item_sn )
			{
				// determine price based on discount
				$price = $items_RET[$item_sn][1]['PRICE'];

				if ( $student['DISCOUNT'] == 'Reduced' )
				{
					if ( $items_RET[$item_sn][1]['PRICE_REDUCED'] != '' )
					{
						$price = $items_RET[$item_sn][1]['PRICE_REDUCED'];
					}
				}
				elseif ( $student['DISCOUNT'] == 'Free' )
				{
					if ( $items_RET[$item_sn][1]['PRICE_FREE'] != '' )
					{
						$price = $items_RET[$item_sn][1]['PRICE_FREE'];
					}
				}

				$LO_ret[] = array( 'SALE_ID' => $id, 'PRICE' => $price, 'DESCRIPTION' => $items_RET[$item_sn][1]['DESCRIPTION'], 'ICON' => $items_RET[$item_sn][1]['ICON'] );
			}
		}

		unset( $LO_ret[0] );

		$link['remove'] = array( 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&menu_id=' . $_REQUEST['menu_id'],
			'variables' => array( 'id' => 'SALE_ID' ) );
//FJ css WPadmin
		//		$link['add']['html'] = array('DESCRIPTION' => '<table class="cellspacing-0"><tr><td>'.SelectInput('','item_sn','',$items).'</td></tr></table>','ICON' => '<table class="cellspacing-0"><tr><td><input type=submit value='._('Add').'></td></tr></table>','remove'=>button('add'));
		$link['add']['html'] = array( 'DESCRIPTION' => SelectInput( '', 'item_sn', '', $items ), 'ICON' => SubmitButton( _( 'Add' ) ), 'PRICE' => '&nbsp;', 'remove' => button( 'add' ) );
		$columns = array( 'DESCRIPTION' => _( 'Item' ), 'ICON' => _( 'Icon' ), 'PRICE' => _( 'Price' ) );

		$tabs = array();

		foreach ( (array) $menus_RET as $id => $menu )
		{
			$tabs[] = array( 'title' => $menu[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id );
		}

		$extra = array( 'save' => false, 'search' => false,
			'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] ) );

		echo '<br />';
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=add&menu_id=' . $_REQUEST['menu_id'] . '" method="POST">';
		ListOutput( $LO_ret, $columns, 'Item', 'Items', $link, array(), $extra );
		echo '</form>';

		echo '</td></tr></table>';
	}
	else
	{
		ErrorMessage( array( _( 'This student does not have a valid Meal Account.' ) ), 'fatal' );
	}
}
