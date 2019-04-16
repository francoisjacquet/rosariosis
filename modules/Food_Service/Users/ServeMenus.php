<?php

StaffWidgets( 'fsa_status_active' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

Search( 'staff_id', $extra );

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
		$items_RET = DBGet( "SELECT DESCRIPTION,SHORT_NAME,PRICE_STAFF FROM FOOD_SERVICE_ITEMS WHERE SCHOOL_ID='" . UserSchool() . "'", array(), array( 'SHORT_NAME' ) );

		// get next transaction id
		$id = DBSeqNextID( 'FOOD_SERVICE_STAFF_TRANSACTIONS_SEQ' );

		$item_id = 0;

		foreach ( (array) $_SESSION['FSA_sale'] as $item_sn )
		{
			$price = $items_RET[$item_sn][1]['PRICE_STAFF'];
			$fields = 'ITEM_ID,TRANSACTION_ID,AMOUNT,SHORT_NAME,DESCRIPTION';
			$values = "'" . $item_id++ . "','" . $id . "','-" . $price . "','" . $items_RET[$item_sn][1]['SHORT_NAME'] . "','" . $items_RET[$item_sn][1]['DESCRIPTION'] . "'";
			$sql = "INSERT INTO FOOD_SERVICE_STAFF_TRANSACTION_ITEMS (" . $fields . ") values (" . $values . ")";
			DBQuery( $sql );
		}

		$sql1 = "UPDATE FOOD_SERVICE_STAFF_ACCOUNTS SET TRANSACTION_ID='" . $id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . $id . "') WHERE STAFF_ID='" . UserStaffID() . "'";
		$fields = 'TRANSACTION_ID,STAFF_ID,SYEAR,SCHOOL_ID,BALANCE,TIMESTAMP,SHORT_NAME,DESCRIPTION,SELLER_ID';
		$values = "'" . $id . "','" . UserStaffID() . "','" . UserSyear() . "','" . UserSchool() . "',(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID='" . UserStaffID() . "'),CURRENT_TIMESTAMP,'" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "','" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . ' - ' . DBDate() . "','" . User( 'STAFF_ID' ) . "'";
		$sql2 = 'INSERT INTO FOOD_SERVICE_STAFF_TRANSACTIONS (' . $fields . ') values (' . $values . ')';
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

if ( UserStaffID()
	&& ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
	FROM STAFF s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=submit&menu_id=' . $_REQUEST['menu_id'] . '" method="POST">';

	DrawHeader(
		'',
		SubmitButton( _( 'Cancel Sale' ), 'submit[cancel]', '' ) . // No .primary button class.
		SubmitButton( _( 'Complete Sale' ), 'submit[save]' )
	);

	echo '</form>';

	DrawHeader( NoInput( $staff['FULL_NAME'], '&nbsp;' . $staff['STAFF_ID'] ), '', NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) ) );

	if ( $staff['ACCOUNT_ID'] && $staff['BALANCE'] != '' )
	{
		echo '<table class="width-100p">';
		echo '<tr class="st"><td class="width-100p valign-top">';

		$RET = DBGet( "SELECT fsti.DESCRIPTION,fsti.AMOUNT
		FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst,FOOD_SERVICE_STAFF_TRANSACTION_ITEMS fsti
		WHERE fst.STAFF_ID='" . UserStaffID() . "'
		AND fst.SYEAR='" . UserSyear() . "'
		AND fst.SHORT_NAME='" . $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] . "'
		AND fst.TIMESTAMP BETWEEN CURRENT_DATE AND CURRENT_DATE+1
		AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID" );

		$columns = array( 'DESCRIPTION' => _( 'Item' ), 'AMOUNT' => _( 'Amount' ) );
		$singular = sprintf( _( 'Earlier %s Sale' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );
		$plural = sprintf( _( 'Earlier %s Sales' ), $menus_RET[$_REQUEST['menu_id']][1]['TITLE'] );

		ListOutput( $RET, $columns, $singular, $plural, $link, false, array( 'save' => false, 'search' => false ) );

		// IMAGE

		if ( $file = @fopen( $picture = $UserPicturesPath . UserSyear() . '/' . UserStaffID() . '.jpg', 'r' ) || $file = @fopen( $picture = $UserPicturesPath . ( UserSyear() - 1 ) . '/' . UserStaffID() . '.jpg', 'r' ) )
		{
			fclose( $file );
			echo '</td><td rowspan="2"><img src="' . $picture . '" width="150" />';
		}

		echo '</td></tr>';
		echo '<tr><td class="width-100p valign-top">';

		$items_RET = DBGet( "SELECT fsi.SHORT_NAME,fsi.DESCRIPTION,fsi.PRICE_STAFF,fsi.ICON
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

		if ( isset( $_SESSION['FSA_sale'] ) )
		{
			foreach ( (array) $_SESSION['FSA_sale'] as $id => $item_sn )
			{
				$price = $items_RET[$item_sn][1]['PRICE_STAFF'];
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
		ErrorMessage( array( _( 'This user does not have a Food Service Account.' ) ), 'fatal' );
	}
}
