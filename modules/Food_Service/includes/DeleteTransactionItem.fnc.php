<?php

/**
 * Delete Transacion Item
 *
 * @since 10.0 Use db_trans_*() functions
 *
 * @uses DeleteTransaction()
 *
 * @param $transaction_id Transaction ID.
 * @param $item_id        Item ID.
 * @param $type           'student' or 'staff'.
 */
function DeleteTransactionItem( $transaction_id, $item_id, $type = 'student' )
{
	if ( $type == 'staff' )
	{
		/**
		 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
		 *
		 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
		 */
		$staff_id = DBGetOne( "SELECT STAFF_ID
			FROM food_service_staff_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );

		$sql1 = "UPDATE food_service_staff_transactions
		SET BALANCE=BALANCE-(SELECT AMOUNT
			FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "')
		WHERE TRANSACTION_ID>='" . (int) $transaction_id . "'
		AND STAFF_ID='" . (int) $staff_id . "'";

		$sql2 = "UPDATE food_service_staff_accounts
		SET BALANCE=BALANCE-(SELECT AMOUNT
			FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "')
		WHERE STAFF_ID='" . (int) $staff_id . "'";

		$sql3 = "DELETE FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "'";
	}
	else
	{
		/**
		 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
		 *
		 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
		 */
		$account_id = DBGetOne( "SELECT ACCOUNT_ID
			FROM food_service_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );

		$sql1 = "UPDATE food_service_transactions
		SET BALANCE=BALANCE-(SELECT AMOUNT
			FROM food_service_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "')
		WHERE TRANSACTION_ID>='" . (int) $transaction_id . "'
		AND ACCOUNT_ID='" . (int) $account_id . "'";

		$sql2 = "UPDATE food_service_accounts
		SET BALANCE=BALANCE-(SELECT AMOUNT
			FROM food_service_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "')
		WHERE ACCOUNT_ID='" . (int) $account_id . "'";

		$sql3 = "DELETE FROM food_service_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'
			AND ITEM_ID='" . (int) $item_id . "'";
	}

	db_trans_start();

	db_trans_query( $sql1 . '; ' . $sql2 . '; ' . $sql3 . ';' );

	db_trans_commit();

	//FJ if no more transaction items, delete transaction
	$trans_items_RET = DBGet( "SELECT ITEM_ID FROM " . ( $type == 'staff' ? "food_service_staff_transaction_items" : "food_service_transaction_items" ) . " WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );

	if ( empty( $trans_items_RET ) )
	{
		require_once 'modules/Food_Service/includes/DeleteTransaction.fnc.php';
		DeleteTransaction( $transaction_id, $type );
	}
}
