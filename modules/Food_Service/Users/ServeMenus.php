<?php
require_once 'modules/Food_Service/includes/FS_Icons.inc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';

StaffWidgets( 'fsa_status_active' );
StaffWidgets( 'fsa_barcode' );
StaffWidgets( 'fsa_exists_Y' );

Search( 'staff_id', $extra );

if ( $_REQUEST['modfunc'] === 'submit' )
{
	if ( ! empty( $_SESSION['FSA_sale']['staff_' . UserStaffID() ] ) )
	{
		$fields = 'STAFF_ID,SYEAR,SCHOOL_ID,BALANCE,' . DBEscapeIdentifier( 'TIMESTAMP' ) . ',SHORT_NAME,DESCRIPTION,SELLER_ID';

		$values = "'" . UserStaffID() . "','" . UserSyear() . "','" . UserSchool() .
			"',(SELECT BALANCE
			FROM food_service_staff_accounts
			WHERE STAFF_ID='" . UserStaffID() . "'),CURRENT_TIMESTAMP,'" .
			DBEscapeString( $menu_title ) . "','" .
			DBEscapeString( $menu_title . ' - ' . DBDate() ) . "','" . User( 'STAFF_ID' ) . "'";

		$sql = "INSERT INTO food_service_staff_transactions (" . $fields . ") values (" . $values . ")";

		DBQuery( $sql );

		$transaction_id = DBLastInsertID();

		$items_RET = DBGet( "SELECT fsmi.MENU_ITEM_ID,fsi.DESCRIPTION,fsi.SHORT_NAME,fsi.PRICE_STAFF
			FROM food_service_items fsi,food_service_menu_items fsmi
			WHERE fsi.SCHOOL_ID='" . UserSchool() . "'
			AND fsmi.ITEM_ID=fsi.ITEM_ID
			AND fsmi.MENU_ID='" . (int) $_REQUEST['menu_id'] . "'", [], [ 'SHORT_NAME' ] );

		$item_id = 0;

		foreach ( (array) $_SESSION['FSA_sale']['staff_' . UserStaffID() ] as $item_sn )
		{
			$price = $items_RET[$item_sn][1]['PRICE_STAFF'];

			DBInsert(
				'food_service_staff_transaction_items',
				[
					'ITEM_ID' => $item_id++,
					// @since 11.2.1 FS transaction item ID references food_service_menu_items(menu_item_id)
					'MENU_ITEM_ID' => (int) $items_RET[$item_sn][1]['MENU_ITEM_ID'],
					'TRANSACTION_ID' => (int) $transaction_id,
					'AMOUNT' => '-' . $price,
					'SHORT_NAME' => DBEscapeString( $items_RET[$item_sn][1]['SHORT_NAME'] ),
					'DESCRIPTION' => DBEscapeString( $items_RET[$item_sn][1]['DESCRIPTION'] ),
				]
			);
		}

		$sql = "UPDATE food_service_staff_accounts
			SET TRANSACTION_ID='" . (int) $transaction_id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT)
				FROM food_service_staff_transaction_items
				WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
			WHERE STAFF_ID='" . UserStaffID() . "'";

		DBQuery( $sql );

		unset( $_SESSION['FSA_sale']['staff_' . UserStaffID() ] );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'cancel' )
{
	if ( DeletePrompt( _( 'Sale' ), _( 'Cancel' ) ) )
	{
		unset( $_SESSION['FSA_sale']['staff_' . UserStaffID() ] );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( UserStaffID()
	&& ! $_REQUEST['modfunc'] )
{
	$staff = DBGet( "SELECT s.STAFF_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
	(SELECT STAFF_ID FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,
	(SELECT BALANCE FROM food_service_staff_accounts WHERE STAFF_ID=s.STAFF_ID) AS BALANCE
	FROM staff s
	WHERE s.STAFF_ID='" . UserStaffID() . "'" );

	$staff = $staff[1];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=submit&menu_id=' . $_REQUEST['menu_id'] .
			'&staff_id=' . UserStaffID()  ) . '" method="POST">';

	DrawHeader(
		'',
		'<input type="button" value="' .
			AttrEscape( _( 'Cancel Sale' ) ) .
			// Change form action's modfunc to delete.
			'" onclick="ajaxLink(this.form.action.replace(\'modfunc=submit\',\'modfunc=cancel\'));" />' .
		SubmitButton( _( 'Complete Sale' ) )
	);

	echo '</form>';

	$staff_name_photo = MakeUserPhotoTipMessage( $staff['STAFF_ID'], $staff['FULL_NAME'] );

	DrawHeader(
		NoInput( $staff_name_photo, $staff['STAFF_ID'] ),
		NoInput( red( $staff['BALANCE'] ), _( 'Balance' ) )
	);

	if ( $staff['ACCOUNT_ID'] && $staff['BALANCE'] != '' )
	{
		// @since 9.0 Add Food Service icon to list.
		$functions = [ 'ICON' => 'makeIcon' ];

		$RET = DBGet( "SELECT fsti.DESCRIPTION,fsti.AMOUNT,
		(SELECT ICON FROM food_service_items WHERE SHORT_NAME=fsti.SHORT_NAME LIMIT 1) AS ICON
		FROM food_service_staff_transactions fst,food_service_staff_transaction_items fsti
		WHERE fst.STAFF_ID='" . UserStaffID() . "'
		AND fst.SYEAR='" . UserSyear() . "'
		AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
		AND fst.TIMESTAMP BETWEEN CURRENT_DATE
		AND (CURRENT_DATE + INTERVAL " . ( $DatabaseType === 'mysql' ? '1 DAY' : "'1 DAY'" ) . ")
		AND fsti.TRANSACTION_ID=fst.TRANSACTION_ID", $functions );

		$columns = [
			'DESCRIPTION' => _( 'Item' ),
			'ICON' => _( 'Icon' ),
			'AMOUNT' => _( 'Amount' ),
		];

		$singular = sprintf( _( 'Earlier %s Sale' ), $menu_title );
		$plural = sprintf( _( 'Earlier %s Sales' ), $menu_title );

		ListOutput( $RET, $columns, $singular, $plural, [], false, [ 'save' => false, 'search' => false ] );

		$items_RET = DBGet( "SELECT fsi.SHORT_NAME,fsi.DESCRIPTION,fsi.PRICE_STAFF,fsi.ICON
		FROM food_service_items fsi,food_service_menu_items fsmi
		WHERE fsmi.MENU_ID='" . (int) $_REQUEST['menu_id'] . "'
		AND fsi.ITEM_ID=fsmi.ITEM_ID
		AND fsmi.CATEGORY_ID IS NOT NULL
		AND fsi.SCHOOL_ID='" . UserSchool() . "'
		ORDER BY fsi.SORT_ORDER IS NULL,fsi.SORT_ORDER", [ 'ICON' => 'makeIcon' ], [ 'SHORT_NAME' ] );
		$items = [];

		foreach ( (array) $items_RET as $sn => $item )
		{
			$items += [ $sn => $item[1]['DESCRIPTION'] ];
		}

		$LO_ret = [ [] ];

		if ( isset( $_SESSION['FSA_sale']['staff_' . UserStaffID() ] ) )
		{
			foreach ( (array) $_SESSION['FSA_sale']['staff_' . UserStaffID() ] as $id => $item_sn )
			{
				$price = $items_RET[$item_sn][1]['PRICE_STAFF'];
				$LO_ret[] = [ 'SALE_ID' => $id, 'PRICE' => $price, 'DESCRIPTION' => $items_RET[$item_sn][1]['DESCRIPTION'], 'ICON' => $items_RET[$item_sn][1]['ICON'] ];
			}
		}

		unset( $LO_ret[0] );

		$link['remove'] = [ 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&menu_id=' . $_REQUEST['menu_id'],
			'variables' => [ 'id' => 'SALE_ID' ] ];

		//		$link['add']['html'] = array('DESCRIPTION' => '<table class="cellspacing-0"><tr><td>'.SelectInput('','item_sn','',$items).'</td></tr></table>','ICON' => '<table class="cellspacing-0"><tr><td><input type=submit value='._('Add').'></td></tr></table>','remove'=>button('add'));
		$link['add']['html'] = [
			'DESCRIPTION' => SelectInput( '', 'item_sn', '', $items ),
			'ICON' => SubmitButton( _( 'Add' ) ),
			'PRICE' => '&nbsp;',
			'remove' => button( 'add' ),
		];

		$columns = [ 'DESCRIPTION' => _( 'Item' ), 'ICON' => _( 'Icon' ), 'PRICE' => _( 'Price' ) ];

		$tabs = [];

		foreach ( (array) $menus_RET as $id => $menu )
		{
			$tabs[] = [
				'title' => $menu[1]['TITLE'],
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id,
			];
		}

		$extra = [
			'save' => false,
			'search' => false,
			'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] ),
		];

		echo '<br />';

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=add&menu_id=' . $_REQUEST['menu_id'] .
			'&staff_id=' . UserStaffID()  ) . '" method="POST">';

		ListOutput( $LO_ret, $columns, 'Item', 'Items', $link, [], $extra );

		echo '</form>';
	}
	else
	{
		ErrorMessage( [ _( 'This user does not have a Food Service Account.' ) ], 'fatal' );
	}
}
