<?php
require_once 'modules/Food_Service/includes/FS_Icons.inc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';

Widgets( 'fsa_status_active' );
Widgets( 'fsa_barcode' );

Search( 'student_id', $extra );

if ( $_REQUEST['modfunc'] === 'submit' )
{
	if ( ! empty( $_SESSION['FSA_sale']['student_' . UserStudentID() ] ) )
	{
		$student = DBGet( "SELECT ACCOUNT_ID,DISCOUNT
			FROM food_service_student_accounts
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		$student = $student[1];

		$fields = 'ACCOUNT_ID,STUDENT_ID,SYEAR,SCHOOL_ID,DISCOUNT,BALANCE,' . DBEscapeIdentifier( 'TIMESTAMP' ) . ',SHORT_NAME,DESCRIPTION,SELLER_ID';

		$values = "'" . $student['ACCOUNT_ID'] . "','" . UserStudentID() . "','" .
			UserSyear() . "','" . UserSchool() . "','" . $student['DISCOUNT'] .
			"',(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] .
			"'),CURRENT_TIMESTAMP,'" . DBEscapeString( $menu_title ) . "','" .
			DBEscapeString( $menu_title . ' - ' . DBDate() ) . "','" . User( 'STAFF_ID' ) . "'";

		$sql = "INSERT INTO food_service_transactions (" . $fields . ") values (" . $values . ")";

		DBQuery( $sql );

		$transaction_id = DBLastInsertID();

		$items_RET = DBGet( "SELECT fsmi.MENU_ITEM_ID,fsi.DESCRIPTION,fsi.SHORT_NAME,
			fsi.PRICE,fsi.PRICE_REDUCED,fsi.PRICE_FREE
			FROM food_service_items fsi,food_service_menu_items fsmi
			WHERE fsi.SCHOOL_ID='" . UserSchool() . "'
			AND fsmi.ITEM_ID=fsi.ITEM_ID
			AND fsmi.MENU_ID='" . (int) $_REQUEST['menu_id'] . "'", [], [ 'SHORT_NAME' ] );

		$item_id = 0;

		foreach ( (array) $_SESSION['FSA_sale']['student_' . UserStudentID() ] as $item_sn )
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

			DBInsert(
				'food_service_transaction_items',
				[
					'ITEM_ID' => $item_id++,
					// @since 11.2.1 FS transaction menu item ID references food_service_menu_items(menu_item_id)
					'MENU_ITEM_ID' => (int) $items_RET[$item_sn][1]['MENU_ITEM_ID'],
					'TRANSACTION_ID' => (int) $transaction_id,
					'AMOUNT' => '-' . $price,
					'DISCOUNT' => $discount,
					'SHORT_NAME' => DBEscapeString( $items_RET[$item_sn][1]['SHORT_NAME'] ),
					'DESCRIPTION' => DBEscapeString( $items_RET[$item_sn][1]['DESCRIPTION'] ),
				]
			);
		}

		DBQuery( "UPDATE food_service_accounts
			SET TRANSACTION_ID='" . (int) $transaction_id . "',BALANCE=BALANCE+(SELECT sum(AMOUNT)
				FROM food_service_transaction_items
				WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
			WHERE ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'" );

		unset( $_SESSION['FSA_sale']['student_' . UserStudentID() ] );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'cancel' )
{
	if ( DeletePrompt( _( 'Sale' ), _( 'Cancel' ) ) )
	{
		unset( $_SESSION['FSA_sale']['student_' . UserStudentID() ] );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

if ( UserStudentID() && ! $_REQUEST['modfunc'] )
{
	$student = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
		(SELECT BALANCE FROM food_service_accounts WHERE ACCOUNT_ID=(SELECT ACCOUNT_ID
			FROM food_service_student_accounts
			WHERE STUDENT_ID=s.STUDENT_ID)) AS BALANCE,
		(SELECT ACCOUNT_ID FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS ACCOUNT_ID,
		(SELECT STATUS FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS STATUS,
		(SELECT DISCOUNT FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS DISCOUNT,
		(SELECT BARCODE FROM food_service_student_accounts WHERE STUDENT_ID=s.STUDENT_ID) AS BARCODE
		FROM students s
		WHERE s.STUDENT_ID='" . UserStudentID() . "'" );

	$student = $student[1];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=submit&menu_id=' . $_REQUEST['menu_id'] .
		'&student_id=' . UserStudentID() ) . '" method="POST">';

	DrawHeader(
		'',
		'<input type="button" value="' .
			AttrEscape( _( 'Cancel Sale' ) ) .
			// Change form action's modfunc to cancel.
			'" onclick="ajaxLink(this.form.action.replace(\'modfunc=submit\',\'modfunc=cancel\'));" />' .
		SubmitButton( _( 'Complete Sale' ) )
	);

	echo '</form>';

	$student_name_photo = MakeStudentPhotoTipMessage( $student['STUDENT_ID'], $student['FULL_NAME'] );

	DrawHeader(
		NoInput( $student_name_photo, $student['STUDENT_ID'] ),
		NoInput( red( $student['BALANCE'] ), _( 'Balance' ) )
	);

	if ( $student['BALANCE'] != '' )
	{
		// @since 9.0 Add Food Service icon to list.
		$functions = [ 'ICON' => 'makeIcon' ];

		$RET = DBGet( "SELECT fsti.DESCRIPTION,fsti.AMOUNT,
			(SELECT ICON FROM food_service_items WHERE SHORT_NAME=fsti.SHORT_NAME LIMIT 1) AS ICON
			FROM food_service_transactions fst,food_service_transaction_items fsti
			WHERE fst.ACCOUNT_ID='" . (int) $student['ACCOUNT_ID'] . "'
			AND fst.STUDENT_ID='" . UserStudentID() . "'
			AND fst.SYEAR='" . UserSyear() . "'
			AND fst.SHORT_NAME='" . DBEscapeString( $menu_title ) . "'
			AND fst.TIMESTAMP BETWEEN  CURRENT_DATE
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

		$items_RET = DBGet( "SELECT fsi.SHORT_NAME,fsi.DESCRIPTION,fsi.PRICE,fsi.PRICE_REDUCED,fsi.PRICE_FREE,fsi.ICON
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
		//FJ fix error Warning: Invalid argument supplied for foreach()

		if ( isset( $_SESSION['FSA_sale']['student_' . UserStudentID() ] ) && is_array( $_SESSION['FSA_sale']['student_' . UserStudentID() ] ) )
		{
			foreach ( (array) $_SESSION['FSA_sale']['student_' . UserStudentID() ] as $id => $item_sn )
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
			$tabs[] = [ 'title' => $menu[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $id ];
		}

		$extra = [
			'save' => false,
			'search' => false,
			'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&menu_id=' . $_REQUEST['menu_id'] ),
		];

		echo '<br />';
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=add&menu_id=' . $_REQUEST['menu_id'] .
			'&student_id=' . UserStudentID() ) . '" method="POST">';

		ListOutput( $LO_ret, $columns, 'Item', 'Items', $link, [], $extra );

		echo '</form>';
	}
	else
	{
		ErrorMessage( [ _( 'This student does not have a valid Meal Account.' ) ], 'fatal' );
	}
}
