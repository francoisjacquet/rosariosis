<?php

/**
 * Delete Food Service Transaction
 *
 * @since 10.0 Use db_trans_*() functions
 *
 * @param $transaction_id  Transaction ID.
 * @param $type           'student' or 'staff'.
 */
function DeleteTransaction( $transaction_id, $type = 'student' )
{
	if ( $type == 'staff' )
	{
		$sql1 = "UPDATE FOOD_SERVICE_STAFF_TRANSACTIONS SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0) FROM food_service_staff_transaction_items WHERE TRANSACTION_ID='" . (int) $transaction_id . "') WHERE TRANSACTION_ID>='" . $transaction_id . "' AND STAFF_ID=(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "')";
		$sql2 = "UPDATE food_service_staff_accounts SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0) FROM food_service_staff_transaction_items WHERE TRANSACTION_ID='" . (int) $transaction_id . "') WHERE STAFF_ID=(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "')";
		$sql3 = "DELETE FROM food_service_staff_transaction_items WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
		$sql4 = "DELETE FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
	}
	else
	{
		$sql1 = "UPDATE FOOD_SERVICE_TRANSACTIONS SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0) FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . (int) $transaction_id . "') WHERE TRANSACTION_ID>='" . $transaction_id . "' AND ACCOUNT_ID=(SELECT ACCOUNT_ID FROM FOOD_SERVICE_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "')";
		$sql2 = "UPDATE food_service_accounts SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0) FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . (int) $transaction_id . "') WHERE ACCOUNT_ID=(SELECT ACCOUNT_ID FROM FOOD_SERVICE_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "')";
		$sql3 = "DELETE FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
		$sql4 = "DELETE FROM FOOD_SERVICE_TRANSACTIONS WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
	}

	db_trans_start();

	db_trans_query( $sql1 . '; ' . $sql2 . '; ' . $sql3 . '; ' . $sql4 . ';' );

	db_trans_commit();
}
